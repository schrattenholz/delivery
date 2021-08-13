<?php 

namespace Schrattenholz\Delivery;


use Silverstripe\ORM\DataExtension;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\Queries\SQLUpdate;

use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;

class Delivery_Preis_Extension extends DataExtension {
	private static $db=[
		"DeliverySpecial"=>"Boolean"
	];
	private static $has_one=[
		"DeliverySetup"=>DeliverySetup::class
	];
	
	private static $many_many=[
		"DeliveryDays"=>DeliveryDay::class,
		"CollectionDays"=>CollectionDay::class,
		//"Routes"=>Route::class
	];
	public function updateCMSFields($fields){
		// Auswahl eines DeliverySetup
			
			$deliverySetup=DropdownField::create("DeliverySetupID","Liefer-Setup",DeliverySetup::get()->map('ID', 'Title'));
			$fields->addFieldToTab('Root.Lieferung',$deliverySetup);
			
		// Standard Liefervariaten für das neue Produkt aktivieren
		if($this->owner->Created==$this->owner->LastEdited){
			// Standardrouten ermitteln 
			/*$routes=array();
			foreach(Route::get()->filter('IsSpecial','0') as $r){
				//$this->owner->Routes()->add($r);
				array_push($routes,$r->ID);
			}
			*/
			//StandardAbholtage ermitteln
			/*$collectionDays=array();
			foreach(CollectionDay::get()->filter('IsSpecial','0') as $r){
				//$this->owner->CollectionDays()->add($r);
				array_push($collectionDays,$r->ID);
			}
			*/
			//StandardLiefertage ermitteln
			/*$deliveryDays=array();
			foreach(DeliveryDay::get()->filter('IsSpecial','0') as $r){
				//$this->owner->DeliveryDays()->add($r);
				array_push($deliveryDays,$r->ID);
			}
			*/
			//$collectionDay=CheckboxSetField::create('CollectionDays','Abholtage',CollectionDay::get())->setValue($collectionDays);
			//$route=CheckboxSetField::create('Routes','Lieferrouten',Route::get())->setValue($routes);
		//	$deliveryDay=CheckboxSetField::create('DeliveryDays','Liefertage',DeliveryDay::get()->filter('ClassName','Schrattenholz\Delivery\DeliveryDay')->sort('RouteID'))->setValue($deliveryDays);
		}else{
			//$collectionDay=CheckboxSetField::create('CollectionDays','Abholtage',CollectionDay::get());
			//$route=CheckboxSetField::create('Routes','Lieferrouten',Route::get());
			//$deliveryDay=CheckboxSetField::create('DeliveryDays','Liefertage',DeliveryDay::get()->filter('ClassName','Schrattenholz\Delivery\DeliveryDay')->sort('RouteID'));
		}
	//	$fields->addFieldToTab('Root.Lieferung',CheckboxField::create('DeliverySpecial','Dieses hat indivuelle Lieferoptionen und bestimmt die Lieferoptionen für alle Produkte im Warenkorb'));
		//$fields->addFieldToTab('Root.Lieferung',$collectionDay);
		//$fields->addFieldToTab('Root.Lieferung',$route);
	//	$fields->addFieldToTab('Root.Lieferung',$deliveryDay);

	}
	public function onBeforeWrite(){
		if($this->owner->DeliverySetup()->IsPrimary){
			$this->owner->DeliverySpecial=1;
		}else{
			$this->owner->DeliverySpecial=0;
		}
		parent::onBeforeWrite();
	}
}
