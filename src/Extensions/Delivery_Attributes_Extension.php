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
use Schrattenholz\Order\OrderConfig;
use Schrattenholz\Order\Preis;

class Delivery_Attributes_Extension extends DataExtension {
	private static $has_one=[
		'DeliverySetups'=>DeliverySetup::class
	];
}
