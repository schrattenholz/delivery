<?php
namespace Schrattenholz\Delivery;

use Page;
use PageController;
class CheckoutDelivery extends Page
{
}
class CheckoutDeliveryController extends PageController
{
	protected function init()
    {
        parent::init();
		//Requirements::javascript('public/resources/vendor/schrattenholz/order/template/javascript/order.js');
	}
}