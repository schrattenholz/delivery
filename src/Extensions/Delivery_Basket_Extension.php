<?php 	

namespace Schrattenholz\Delivery;

use Silverstripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Control\Email\Email;
use Schrattenholz\Order\Backend;
use SilverStripe\ORM\ValidationException;
use Psr\Log\LoggerInterface;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;
use Schrattenholz\Order\Preis;

class Delivery_Basket_Extension extends DataExtension {

	function utf8_urldecode($str) {
		$str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));
		return html_entity_decode($str,null,'UTF-8');;
	} 
	public function getDeliverySpecialProduct(){
		$specArray=explode(",",$this->owner->DeliverySpecial);
		return Preis::get()->byID($specArray[0]);
	}
}
