<?php 	

namespace Schrattenholz\Delivery;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataList;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ArrayData;
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
use Schrattenholz\Order\OrderConfig;
use Schrattenholz\Order\Preis;

class Delivery_Attributes_Extension extends Extension {
	private static $has_one=[
		'DeliverySetups'=>DeliverySetup::class
	];
}
