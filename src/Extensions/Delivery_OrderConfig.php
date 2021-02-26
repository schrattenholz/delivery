<?php

namespace Schrattenholz\Delivery;
use SilverStripe\Core\Extension;

class Delivery_ShopConfig extends Extension{
	private static $has_one=[
		"CheckoutDelivery"=>CheckoutDelivery::class,
	];

}


?>
