<?php 

namespace Schrattenholz\Delivery;


use Silverstripe\ORM\DataExtension;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\Queries\SQLUpdate;

use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;

class Delivery_Order_Extension extends DataExtension {
	private static $allowed_actions = [
		'getDeliveryOptions'
	];
	public function getDeliveryOptions($priceBlockElement){
		$pBE=Preis::get()->by_id($priceBlockElement);
		$routes=Route::get()->filter('ID',[explode(",",$pBW->Routes)]);
		$collectionDays=CollectionDay::get()->filter('ID',[explode(",",$pBW->CollectionDays)]);
		$deliveryDays=DeliveryDays::get()->filter('ID',[explode(",",$pBW->DeliveryDays)]);
		return json_encode(new ArrayData(array('Routes'=>$routes,'CollectionDays'=>$collectionDays,'DeliveryDays'=>$deliveryDays)));
	}
	public function updateCMSFields($fields){
		$collectionDay=CheckboxSetField::create('Routes','Abholtage',CollectionDay::get());
		$route=CheckboxSetField::create('CollectionDays','Lieferrouten',Route::get());
		$deliveryDay=CheckboxSetField::create('DeliveryDays','Liefertage',DeliveryDay::get()->filter('ClassName','Schrattenholz\Delivery\DeliveryDay')->sort('RouteID'));
		// Standard Liefervariaten für das neue Produkt aktivieren
		if($this->owner->Created==$this->owner->LastEdited){
			// Standardrouten ermitteln 
			$routes=array();
			foreach(Route::get()->filter('IsSpecial','0') as $r){
				array_push($routes,$r->ID);
			}
			$route->setValue($routes);
			//StandardAbholtage ermitteln
			$collectionDays=array();
			foreach(CollectionDay::get()->filter('IsSpecial','0') as $r){
				array_push($collectionDays,$r->ID);
			}
			$collectionDay->setValue($collectionDays);
			//StandardLiefertage ermitteln
			$deliveryDays=array();
			foreach(DeliveryDay::get()->filter('IsSpecial','0') as $r){
				array_push($deliveryDays,$r->ID);
			}
			$deliveryDay->setValue($deliveryDays);
		}
		$fields->addFieldToTab('Root.Lieferung',$collectionDay);
		$fields->addFieldToTab('Root.Lieferung',$route);
		$fields->addFieldToTab('Root.Lieferung',$deliveryDay);
	}
}
