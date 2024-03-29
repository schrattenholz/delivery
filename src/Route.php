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
		'OrderDeadlineTime'=>'Time',
		'Interval'=>'Enum("even,odd,weekly","weekly")'
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

 	public function getCMSFields()
	{
		$fields=FieldList::create(TabSet::create('Root'));
		$intervalValues=singleton(Route::class)->dbObject('Interval')->enumValues();
		//ENUM-Values uebersetzen
		foreach($intervalValues as $v){
			$intervalValues[$v]=_t('Cycles.'.$v,$v);
			
		}
		$interval=DropdownField::create( 'Interval', 'Intervall', $intervalValues);
		
		
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
		$dataColumns=$config->getComponentByType(GridFieldDataColumns::class)->setDisplayFields(
        array(
            'DayTranslated'   => 'Tag'
		));
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
			$interval,
			$deliveryDate,
			$cities,
			
        ]);
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}	
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
			if(count($zipIDs)>0){
				return $zipIDs;
			}else{
				return 0;				
			}
		}
	public function getNextDeliveryDates($currentOrderCustomerGroupID,$deliverySetup,$productID,$variantID){
		$deliverySetupID=$deliverySetup->ID;
		//Injector::inst()->get(LoggerInterface::class)->error("route getNextDeliveryDates=");
		$deliveryStart=strtotime($deliverySetup->DeliveryStart);
		$deliveryDays=[];
		foreach($deliverySetup->Route_DeliveryDays() as $dd){
			
			array_push($deliveryDays,$dd->ID);
		}
		$heute = strtotime(date("Y-m-d"));
		$dates=new ArrayList();
		$dayFormatter = new \IntlDateFormatter(
			"de-DE",
			\IntlDateFormatter::NONE,
			\IntlDateFormatter::NONE,
			'Europe/Berlin',
			\IntlDateFormatter::GREGORIAN,
			'eee'
		);
		$dates=new ArrayList();
		
		foreach($this->DeliveryDays()->filter('ID',$deliveryDays) as $dd){
			//Injector::inst()->get(LoggerInterface::class)->error("route DeliveryDay=".$dd->Day);
			$nextDate=$dd->getNextDate($currentOrderCustomerGroupID,$deliverySetup,$productID,$variantID);
			if($nextDate){
				//Injector::inst()->get(LoggerInterface::class)->error("route DeliveryDay Datum gefunden".$dd->Day);
				$firstDate=$this->genDateTime($dd->getNextDate($currentOrderCustomerGroupID,$deliverySetup,$productID,$variantID)->Timestamp);
			}
		
			if(isset($firstDate) && $firstDate){
				//Injector::inst()->get(LoggerInterface::class)->error("route ersten Termin RouteID".$this->ID);
				//$naechsterTermin=strtotime('next '.$dd->Day,$heute);
				$dates->push(new ArrayData(
						array(
							"ID"=>$dd->ID,
							"RouteID"=>$this->ID,
							"Route"=>$this,
							"NextDeliveryDay"=>$firstDate,							
							"TimeFrom"=>$dd->TimeFrom,
							"TimeTo"=>$dd->TimeTo,
							"EngNum"=>$firstDate->format("Y.m.d"),
							"Eng"=>$firstDate->format("Y-m-d"),
							"Full"=>$firstDate->format("d.m.Y"),
							"Short"=>$firstDate->format("d.m"),
							"DayShort"=>$dayFormatter->format($firstDate),
							"DayObject"=>$firstDate
						)
					)
				);
				if($this->Interval!="weekly"){
					$interval=2;
					//$weeksToShow=($deliverySetup->WeeksToShow*2);
				}else{
					$interval=1;
					
				}
				$weeksToShow=$deliverySetup->WeeksToShow;
				for($c=1;$c<$weeksToShow;$c++){
					$nextDeliveryDay=$this->genDateTime(strtotime('+'.($c*$interval).' week '.$firstDate->format("l"),$firstDate->getTimestamp()));
					Injector::inst()->get(LoggerInterface::class)->error($firstDate->format("l")." route nextDeliveryDay=".$nextDeliveryDay->format("Y.m.d"));
					$dates->add(new ArrayData(
						array(
							"ID"=>$dd->ID,
							"RouteID"=>$this->ID,
							"NextDeliveryDay"=>$nextDeliveryDay,
							"Route"=>$this,							
							"NextDeliveryDay"=>$nextDeliveryDay,							
							"TimeFrom"=>$dd->TimeFrom,
							"TimeTo"=>$dd->TimeTo,
							"EngNum"=>$nextDeliveryDay->format("Y.m.d"),
							"Eng"=>$nextDeliveryDay->format("Y-m-d"),
							"Full"=>$nextDeliveryDay->format("d.m.Y"),
							"Short"=>$nextDeliveryDay->format("d.m"),
							"DayShort"=>$dayFormatter->format($nextDeliveryDay),
							"DayObject"=>$nextDeliveryDay
							)
						)
					);
					
				}
			}
		}
		return $dates->Sort("EngNum","ASC");
	}
	public function genDateTime($timestamp){
		$dateTime=new \DateTime("now",new \DateTimeZone("Europe/Berlin"));
		return $dateTime->setTimestamp($timestamp);
	}
	public function canView($member = null) 
    {
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }

    public function canEdit($member = null) 
    {
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }

    public function canDelete($member = null) 
    {
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }

    public function canCreate($member = null, $context = []) 
    {
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }
}
?>
