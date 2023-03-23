<?php

namespace Schrattenholz\Delivery;

use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use Silverstripe\Forms\TextField;
use Silverstripe\Forms\NumericField;
use Silverstripe\Forms\CheckboxField;
use Silverstripe\Forms\DropdownField;
use Silverstripe\Forms\HiddenField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\DateField;
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
use SilverStripe\Security\Permission;
use Schrattenholz\Order\Preis;
class DeliveryDay extends DataObject
{
	private static $default_sort=['SortOrder'];
	private static $db = array (
		'Title'=>'Varchar(255)',
		'Day'=>'Enum("monday, tuesday, wednesday, thursday, friday, saturday, sunday","monday")',
		'SortOrder'=>'Int'
		
	);
	private static $has_one=[
		'Route'=>Route::class
	];
	private static $has_many=[
		'Deadlines'=>Deadline::class
	];
	private static $belongs_many_many=[
		'PriceBlockElement'=>Preis::class,
		'DeliverySetups'=>DeliverSetup::class
	];
	public function  Title(){
		return $this->Route()->Title." am ".$this->DayTranslated();
	}
	private static $summary_fields = [
			'Day' => 'Liefertag'
    ];
 	private static $singular_name="Liefertag";
	private static $plural_name="Liefertag";
	private static $table_name="DeliveryDays";
	public function DayTranslated(){
		return _t("Day.".$this->Day,$this->Day);
	}
	public function DayTranslatedShort(){
		return _t("Day.".$this->Day."S",$this->Day);
	}
	public function getTitle(){
		return $this->DayTranslated();
	}
 	public function getCMSFields()
	{
		$fields=FieldList::create(TabSet::create('Root'));
		
		$day=singleton('Schrattenholz\\Delivery\\DeliveryDay')->dbObject('Day')->enumValues();
		foreach($day as $v){
			$day[$v]=_t('Day.'.$v,$v);
			
		}
		
		$fields->addFieldsToTab('Root.Main', [
			DropdownField::create('Day', 'Liefertag',$day)
        ]);
		$this->extend('updateCMSFields', $fields);
		//Deadline pro Kundengruppe
		$gridFieldConfig=GridFieldConfig::create()
			->addComponent(new GridFieldButtonRow('before'))
			->addComponent($dataColumns=new GridFieldDataColumns)
			->addComponent($editableColumns=new GridFieldEditableColumns())
			->addComponent(new GridFieldSortableHeader())
			->addComponent(new GridFieldFilterHeader())
			->addComponent(new GridFieldPaginator())
			
		;
		$dataColumns->setDisplayFields([
			'OrderCustomerGroup.Title' => 'Kundengruppe'
		]);

		
		$editableColumns->setDisplayFields(array(

			'DaysBefore'  =>array(
					'title'=>'Bestellschluss in Tagen vor Liefertag',
					'callback'=>function($record, $column, $grid) {
						return NumericField::create($column)->setScale(0);
				}),
							'Active'  =>array(
					'title'=>'Wird angezeigt',
					'callback'=>function($record, $column, $grid) {
						return CheckboxField::create($column);
				}),
				
		));
		$fields->addFieldToTab('Root.Main', GridField::create(
			'Deadlines',
			'Bestellschluss',
			$this->Deadlines(),
			$gridFieldConfig
		));
		return $fields;
	}

	public function onAfterWrite(){
		parent::onAfterWrite();
		foreach(OrderCustomerGroup::get() as $ocg){
			if($this->Deadlines()->filter('OrderCustomerGroupID',$ocg->ID)->Count()==0){
				
				$deadline=Deadline::create();
				$deadline->OrderCustomerGroupID=$ocg->ID;
				$this->Deadlines()->add($deadline);
			}
		}
	}
	// Gibt eine spanne von Lieferterminen zurueck
	public function getNextDates($currentOrderCustomerGroupID,$deliverySetup,$weeks,$productID,$variantID){
		$dates=new ArrayList();
		$firstDate=$this->genDateTime($this->getNextDate($currentOrderCustomerGroupID,$deliverySetup,$productID,$variantID)->Timestamp);
		//Injector::inst()->get(LoggerInterface::class)->error("firstDate=".$firstDate->format('Y.m.d'));
		if($firstDate){			
			$dates->add(
						array(
						"TimeFrom"=>$this->TimeFrom,
						"TimeTo"=>$this->TimeTo,
						"Eng"=>$firstDate->format("%Y-%m-%d"),
						"Full"=>$firstDate->format("%d.%m.%Y"),
						"Short"=>$firstDate->format("%d.%m"),
						"Timestamp"=>$firstDate->getTimestamp()
						)
					);
			for($c=1;$c<$weeks;$c++){
				
				
				$nextDeliveryDay=$this->genDateTime(strtotime('+'.$c.' week '.strtotime($firstDate->getTimestamp()),$firstDate->getTimestamp()));
				$dates->add(
					new ArrayData(
						array(
							"TimeFrom"=>$this->TimeFrom,
							"TimeTo"=>$this->TimeTo,
							"Eng"=>$nextDeliveryDay->format("%Y.%m.%d"),
							"Full"=>$nextDeliveryDay->format("%d.%m.%Y"),
							"Short"=>$nextDeliveryDay->format("%d.%m"),
							"Timestamp"=>$nextDeliveryDay->getTimestamp()
							)
						)
					);
				
			}
			return $dates;
		}else{
			return false;
		}
	}
	public function WeekIsInActiveInterval($week,$date){
			//Injector::inst()->get(LoggerInterface::class)->error($date.": ".$week."----".(date("oW", strtotime($date))%2)."==1"); 
		if(date("oW", strtotime($date))%2==1 && $week=="odd"){	
			//Injector::inst()->get(LoggerInterface::class)->error("odd"); 
			return true;
		}else if(date("oW", strtotime($date))%2==0 && $week=="even"){
			//Injector::inst()->get(LoggerInterface::class)->error("even"); 
			return true;	
			
		}else if($week=="weekly"){
			//Injector::inst()->get(LoggerInterface::class)->error("weekly"); 
			return true;
		}else{
			
			return false;
		}
	}
	public function genDateTime($timestamp){
		$dateTime=new \DateTime("now",new \DateTimeZone("Europe/Berlin"));
		return $dateTime->setTimestamp($timestamp);
	}
	// Gibt den nachst möeglichen Liefertermin zurueck. Fuer Es wird nur ein Liefertermin automatisch angezeigt
	public function getNextDate($currentOrderCustomerGroupID,$deliverySetup,$productID,$variantID){
		$deliverySetupID=$deliverySetup->ID;
		$heute = strtotime(date("Y-m-d"));
		$deadline=$this->owner->Deadlines()->filter('OrderCustomerGroupID',$currentOrderCustomerGroupID)->First();
		$nextDeliveryDay=strtotime('next '.$this->owner->Day);
		//$deliveryStart=strtotime($deliverySetup->DeliveryStart);
		Injector::inst()->get(LoggerInterface::class)->error("deadline= ". $deadline->Active);
		
		if($variantID>0){
			$product=Preis::get()->byID($variantID);
			if($product->getPreSaleMode()=="presale"){			
				$deliveryStart=strtotime($product->PreSaleEnd);
			}else{
				$deliveryStart=strtotime($deliverySetup->DeliveryStart);
			}
		}else if ($deliverySetup->ShippingDate){
			
			$deliveryStart=strtotime($deliverySetup->ShippingDate);
		}else{
			$deliveryStart=strtotime($deliverySetup->DeliveryStart);			
		}
		
		
		
		if($deadline->Active){
			if($deliverySetup->DeliveryStart && $deliveryStart>=$heute  or isset($product) && $product->getPreSaleMode()=="presale" && $deliveryStart>=$heute or $deliverySetup->ShippingDate){
				$liefertermin=$this->genDateTime(strtotime($this->owner->Day,$deliveryStart));		
			}else{
				$liefertermin=$this->genDateTime(strtotime($this->owner->Day));
			}
			Injector::inst()->get(LoggerInterface::class)->error("original Termin ". $liefertermin->format("Y.m.d"));
			if(!$this->WeekIsInActiveInterval($this->Route->Interval,$liefertermin->format("Y-m-d"))){	
				// Wenn das Interval (gerade/ungerade) nicht passt, nimmm die naechste Woche
				//Injector::inst()->get(LoggerInterface::class)->error("nehste Woche wegen Interval ". $liefertermin->format("Y.m.d"));
				$liefertermin=$this->genDateTime(strtotime("next ".$this->owner->Day, $liefertermin->getTimestamp()));			
			}
			$bestellschluss=$this->genDateTime(strtotime('-'.$deadline->DaysBefore.' day', $liefertermin->getTimestamp()));
			//Injector::inst()->get(LoggerInterface::class)->error("Bestellschluss ". $bestellschluss->getTimestamp()." heute=".$heute);
			if($bestellschluss->getTimestamp()>=$heute){
				//Injector::inst()->get(LoggerInterface::class)->error("bestellschluss in der zukunft ". $liefertermin->format("Y.m.d"));
				//$liefertermin=$this->genDateTime(strtotime("next ".$this->owner->Day, $liefertermin->getTimestamp()));	
				if(!$this->WeekIsInActiveInterval($this->Route->Interval,$liefertermin->format("Y-m-d"))){	
					//Injector::inst()->get(LoggerInterface::class)->error("Woche ist nicht im interval,... erhoehen");			
					$liefertermin=$this->genDateTime(strtotime("next ".$this->owner->Day, $liefertermin->getTimestamp()));			
				}
				$nextDeliveryDay=$this->genDateTime(strtotime($this->owner->Day,$liefertermin->getTimestamp()))->getTimestamp();
			}else if (!$deliverySetup->NoNextDeliveryDate){
				// der nächste Liefertag ist nach dem Bestellschluss, es muss der uebernächste Tag genommen werden
				//Injector::inst()->get(LoggerInterface::class)->error("naechstten Liefertag vorher   ".$liefertermin->format("Y.m.d"));	
				$liefertermin=$this->genDateTime(strtotime("next ".$this->owner->Day, $liefertermin->getTimestamp()));
				if(!$this->WeekIsInActiveInterval($this->Route->Interval,$this->genDateTime(strtotime("next ".$this->owner->Day, $liefertermin->getTimestamp()))->format('Y-m-d'))){		
			
					$liefertermin=$this->genDateTime(strtotime("next ".$this->owner->Day, $liefertermin->getTimestamp()));			
				}
				//Injector::inst()->get(LoggerInterface::class)->error("naechstten Liefertag erhoeht   ".$liefertermin->format("Y.m.d"));	
				$nextDeliveryDay=$liefertermin->getTimestamp();
			}else{
			//	Injector::inst()->get(LoggerInterface::class)->error("bestellschluss für diesen Liefertag(".$this->owner->Day.") ist abgeluafen; es gibt keinen weiteren");
				//Da der erste Liefertag im Datumsbereich (deliveryStart) schon vorueber ist, 
				//und der darauffolgende Liefertag nicht verwendet werden 
				//darf NoNextDeliveryDate=true), wird false zurueck gegeben
				return false;
			}
			$data=new ArrayData(
				array(
					"Eng"=>$liefertermin->format('Y-m-d'),
					"Full"=>$liefertermin->format("%d.%m.%Y"),
					"Short"=>$liefertermin->format("%d.%m"),
					"Timestamp"=>$liefertermin->getTimestamp(),
					"Day"=>$this->Day,
					"DayObject"=>$liefertermin
				)
			);
			$vars=new ArrayData(array("Data"=>$data));
			//Injector::inst()->get(LoggerInterface::class)->error("call HOOK_DeliveryDay_getNextDate_Data");
			$this->extend('DeliveryDay_getNextDate_Data', $vars);
		}else{
			$data=false;
		}

		return $data;
	}
}

