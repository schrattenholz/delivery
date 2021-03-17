<?php

namespace Schrattenholz\Delivery;

use SilverStripe\ORM\DataObject;
use Silverstripe\Forms\TextField;
use Silverstripe\Forms\NumericField;
use Silverstripe\Forms\CheckboxField;
use Silverstripe\Forms\DropdownField;
use Silverstripe\Forms\HiddenField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TimeField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationException;
use Psr\Log\LoggerInterface;

use Schrattenholz\Order\Preis;
use Schrattenholz\Order\Attribute;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Security\Permission;
class Route extends DataObject
{
	private static $default_sort=['SortOrder'];
	private static $singular_name="Lieferroute";
	private static $plural_name="Lieferrouten";
	private static $table_name="Delivery_Route";
	private static $db = array (
		'Title'=>'Varchar(255)',
		'SortOrder'=>'Int',
		'OrderDeadline'=>'Int',
		'OrderDeadlineTime'=>'Time'
	);
	private static $summary_fields = [
		'Title' => 'Title'
    ];
	private static $belongs_many_many=[
		'PriceBlockElement'=>Preis::class
	];
	private static $many_many = [
		'Cities'=>City::class
	];
	private static $many_many_extraFields = [
        'Cities' => [
            'ArrivalTime' => 'Time'
        ]
    ];
	private static $has_many=[
		'DeliveryDays'=>DeliveryDay::class
	];
	private static $has_one=[
		'DeliveryType'=>DeliveryType::class
	];
	public function onAfterWrite(){
		parent::onAfterWrite();
		$update = SQLUpdate::create('Delivery_Route')->addWhere(['ID' => $this->ID]);
		$update->assign('DeliveryTypeID', DeliveryType::get()->filter("Type","delivery")->First()->ID);
		$update->execute();
	}
		
		public function getZIPs($cityID){
			$sqlQuery = new SQLSelect();
			$sqlQuery->setFrom('Delivery_City_Delivery_ZIPCodes');
			$sqlQuery->selectField('Delivery_ZIPCodeID', 'ZIPID');
			$sqlQuery->selectField('Delivery_CityID', 'CityID');
			$sqlQuery->addWhere(['Delivery_CityID = ?' => $cityID]);
			// $sqlQuery->setOrderBy(...);
			// $sqlQuery->setGroupBy(...);
			// $sqlQuery->setHaving(...);
			// $sqlQuery->setLimit(...);
			// $sqlQuery->setDistinct(true);

			// Get the raw SQL (optional) and parameters
			//$rawSQL = $sqlQuery->sql($parameters);

			// Execute and return a Query object
			$result = $sqlQuery->execute();

			// Iterate over results
			$zipIDs=[];
			foreach($result as $row) {
				array_push($zipIDs,$row['ZIPID']);
			}
			return $zipIDs;
		}
 	public function getCMSFields()
	{
		$fields=FieldList::create(TabSet::create('Root'));
	//Cities
		$gridFieldConfig=GridFieldConfig::create()
			->addComponent($autocompleter=new GridFieldAddExistingAutocompleter())
			->addComponent(new GridFieldSortableHeader('before'))
			->addComponent($editableColumns=new GridFieldEditableColumns('before'))
			->addComponent(new GridFieldDeleteAction('before'))
			
		;
		$autocompleter->setResultsLimit(2000);
		$editableColumns->setDisplayFields([
			'Title'=>[
				'title'=>'Ort',
				'callback'=>function($record, $column, $grid) {
						return ReadonlyField::create($column);
				}],
			'ArrivalTime'  =>[
					'title'=>'Ankunftszeit',
					'callback'=>function($record, $column, $grid) {
						return TimeField::create($column);
				}],
			'Delivery_ZIPCodes'  =>array(
					'title'=>'PLZ',
					'callback'=>function($record, $column, $grid){
						return  ListboxField::create($column,'Delivery_ZIPCodes',Delivery_ZIPCode::get()->filter(['ID'=>$this->getZIPs($record->ID)])->map("ID", "Title", "Bitte auswählen"));
				}),
			/*'Price'  =>array(
					'title'=>'',
					'callback'=>function($record, $column, $grid) {
						return NumericField::create($column)->setScale(2);
				})*/
		]);
		$cities= GridField::create(
			'Cities',
			'Orte',
			$this->Cities(),
			$gridFieldConfig
		);
		// END Cities
		
		$config = GridFieldConfig_RecordEditor::create();
		
		$deliveryDate = new GridField('DeliveryDays', 'Liefertage', $this->DeliveryDays());
		$deliveryDate->setConfig($config);
		
		$orderDeadline=new NumericField("OrderDeadline","Bestellschluss (Anzahl Tage vor Lieferung)");
		$orderDeadline->setLocale("DE_De");
		$orderDeadline->setScale(0);
		$orderDeadlineTime=new TimeField("OrderDeadlineTime","Uhrzeit des Bestellschluss ");
		$fields->addFieldsToTab('Root.Main', [
			TextField::create('Title','Title'),
			//$orderDeadline,
			//$orderDeadlineTime,
			$deliveryDate,
			$cities,
			
        ]);
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}	
	public function getNextDeliveryDates($currentOrderCustomerGroupID,$deliverySetupID){
		$deliverySetup=DeliverySetup::get()->byID($deliverySetupID);
		$deliveryStart=strtotime($deliverySetup->DeliveryStart);
		$deliveryDays=[];
		foreach($deliverySetup->Route_DeliveryDays() as $dd){
			array_push($deliveryDays,$dd->ID);
		}
		$heute = strtotime(date("Y-m-d"));
		$nextDeliveryDays=new ArrayList();
		
		foreach($this->DeliveryDays()->filter('ID',$deliveryDays) as $dd){
			$nextDate=$dd->getNextDate($currentOrderCustomerGroupID,$deliverySetupID);
			//Injector::inst()->get(LoggerInterface::class)->error("nextDeliveryDay=".$nextDate->Short);
			if($nextDate){
				$naechsterTermin=strtotime('next '.$dd->Day,$heute);
				$nextDeliveryDays->push(new ArrayData(array("ID"=>$dd->ID,"NextDeliveryDay"=>$nextDate->Org)));
			}
		}
		$nextDeliveryDay=$nextDeliveryDays->First()->NextDeliveryDay;
		
		foreach($nextDeliveryDays->Sort("NextDeliveryDay","ASC") as $dd){
			if($nextDeliveryDay>$dd->NextDeliveryDay){
				$nextDeliveryDay=$dd->NextDeliveryDay;
				if(strtotime('-'.$dd->Deadline.' day', $nextDeliveryDay)>=strtotime($heute)){
					return strftime("%d.%m.%Y",$nextDeliveryDay);
				}
			}
		}
		return strftime("%d.%m.%Y",$nextDeliveryDay);
	}

}
?>
