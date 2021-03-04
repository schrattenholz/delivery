<?php

namespace Schrattenholz\Delivery;

use Silverstripe\Forms\TimeField;
use SilverStripe\View\ArrayData;
use Silverstripe\Forms\NumericField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
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
use Schrattenholz\OrderProfileFeature\OrderCustomerGroup;

//Debugging
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;

use Schrattenholz\Order\Preis;
use SilverStripe\Security\Permission;
class CollectionDay extends DeliveryDay
{
	private static $default_sort=['SortOrder'];
	private static $db = [
		'TimeFrom'=>'Time',
		'TimeTo'=>'Time'
	];
	private static $belongs_many_many=[
		'PriceBlockElement'=>Preis::class
	];
	private static $summary_fields = [
			'TimeFrom'=>'Von',
			'TimeTo'=>'Bis'
    ];
 	private static $singular_name="Abholtag";
	private static $plural_name="Abholtag";
	private static $table_name="CollectionDay";
	public function  Title(){
		return $this->owner->DayTranslated();
	}
 	public function getCMSFields()
	{
		$fields=parent::getCMSFields();
		$fields->addFieldToTab('Root.Main',LiteralField::create('InfoOpeneningHours','Öffnungszeit:'));
		$fields->addFieldToTab('Root.Main',TimeField::create('TimeFrom','von'));
		$fields->addFieldToTab('Root.Main',TimeField::create('TimeTo','bis'));
		return $fields;
	}
/*	public function DeliveryDay_getNextDate_Data($vars){
		//Extend getNextDate in Schrattenholz\Delivery\DeliveryDay
				Injector::inst()->get(LoggerInterface::class)->error("HOOK_DeliveryDay_getNextDate_Data=".$this->TimeFrom);
		$data=$vars->Data;
		$data->push(array("TimeFrom"=>$this->TimeFrom,"TimeTo"=>$this->TimeTo."muh"));

	}*/
	public function getNextDate($currentOrderCustomerGroupID,$deliverySetupID){
		$deliverySetup=DeliverySetup::get()->byID($deliverySetupID);
		$heute = strtotime(date("Y-m-d"));
		$deadline=$this->owner->Deadlines()->filter('OrderCustomerGroupID',$currentOrderCustomerGroupID)->First();
		$nextDeliveryDay=strtotime('next '.$this->owner->Day);
		$deliveryStart=strtotime($deliverySetup->DeliveryStart);
		if($deliverySetup->DeliveryStart && $deliveryStart>=$heute){
			$liefertermin=strtotime($this->owner->Day,$deliveryStart);
			//Injector::inst()->get(LoggerInterface::class)->error("CollectionDay.getNextCollectionDate deliveryStart=".strftime("%Y.%m.%d",$deliveryStart));
		}else{
			$liefertermin=strtotime($this->owner->Day);
		}
		$bestellschluss=strtotime('-'.$deadline->DaysBefore.' day', $liefertermin);
		if($bestellschluss>=$heute){
			//Injector::inst()->get(LoggerInterface::class)->error("bestellschluss in der zukunft");
			$nextDeliveryDay=strtotime($this->owner->Day,$liefertermin);
		}else if (!$deliverySetup->NoNextDeliveryDate){
			// der nächste Abholtag ist nach dem Bestellschluss, es muss der uebernächste Tag genommen werden
			//Injector::inst()->get(LoggerInterface::class)->error("bestellschluss für diesen Abholtag(".$this->owner->Day.") ist abgeluafen; zeige den naechstten abholtag an");
			$nextDeliveryDay=strtotime("next ".$this->owner->Day, strtotime($this->owner->Day));
		}else{
			//Injector::inst()->get(LoggerInterface::class)->error("bestellschluss für diesen Abholtag(".$this->owner->Day.") ist abgeluafen; es gibt keinen weiteren");
			//Da der erste Abholtag im Datumsbereich (deliveryStart) schon vorueber ist, 
			//und der darauffolgende Abholtag nicht verwendet werden 
			//darf NoNextDeliveryDate=true), wird false zurueck gegeben
			return false;
		}
		return new ArrayData(array("TimeFrom"=>$this->TimeFrom,"TimeTo"=>$this->TimeTo,"Eng"=>strftime("%Y.%m.%d",$nextDeliveryDay),"Full"=>strftime("%d.%m.%Y",$nextDeliveryDay),"Short"=>strftime("%d.%m",$nextDeliveryDay)));
	}

}

