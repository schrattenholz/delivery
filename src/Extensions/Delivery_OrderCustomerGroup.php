<?php

namespace Schrattenholz\Delivery;
use SilverStripe\Core\Extension;

class Delivery_OrderCustomerGroup extends Extension{
	private static $has_one=[
		"DeliveryConfig"=>DeliveryConfig::class,
	];
		private static $belongs_many_many=[		
			'DeliveryTypes'=>DeliveryType::class
	];
	public function DeliveryIsActive(){		
		$dC=DeliveryConfig::get()->First();
		if($dC->OrderCustomerGroups()->filter("OrderCustomerGroupsID",$this->owner->ID)->Count()>0){
			return true;
		}else{
			return false;
		}
	}
}


?>
