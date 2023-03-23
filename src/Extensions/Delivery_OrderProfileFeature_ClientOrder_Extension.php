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

use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\DropdownField;

//Extends OrderProfileFeature_Basket & OrderProfileFeature_ClientOrder

class Delivery_OrderProfileFeature_ClientOrder_Extension extends DataExtension {
	private static $db=[
		"ShippingDate"=>"Date",
		'DeliverySpecial'=>'Text'
	];
	private static $has_one=[
		"DeliveryType"=>DeliveryType::class,
		"Route"=>Route::class,
		"CollectionDay"=>CollectionDay::class
	]; 
	private static $summary_fields=[
		"DeliveryType.Title"=>" Versandart",
		"CollectionDay.Day"=>" Abholtag",
		"Route.Title"=>" Lieferroute",
		"ShippingDate"=>"Abhol/Liefertermin"
	];
	public function updateCMSFields($fields){
		//$fields=parent::getCMSFields();
		$collectionDay=new DropdownField('CollectionDayID','Abholtag',CollectionDay::get()->map('ID','Title'));
		$collectionDay->setHasEmptyDefault(true);
		$route=new DropdownField('RouteID','Lieferroute',Route::get()->map('ID','Title'));
		$route->setHasEmptyDefault(true);
		$fields->addFieldToTab('Root.Main',new DropdownField('DeliveryTypeID','Lieferart',DeliveryType::get()->map('ID','Title')));
		$fields->addFieldToTab('Root.Main',new DateField('ShippingDate','Liefer/Abholdatum'));	
		$fields->addFieldToTab('Root.Main',$collectionDay);
		$fields->addFieldToTab('Root.Main',$route);
		
		//return $fields;
		
		
	}
	public function getVersandInfo(){
		if($this->owner->DeliveryType()->Type=="delivery" && $this->owner->ShippingDate){
			return "am ".strftime("%d.%m.%Y",strtotime($this->owner->ShippingDate));
		}else if($this->owner->DeliveryType->Type=="collection" && $this->owner->ShippingDate){
			return "am ".strftime("%d.%m.%Y",strtotime($this->owner->ShippingDate))." </br>zwischen ".strftime("%H:%M",strtotime($this->owner->CollectionDay()->TimeFrom))." und ".strftime("%H:%M",strtotime($this->owner->CollectionDay()->TimeTo))." Uhr";
		}else{
			return "nach vorheriger Benachrichtigung";
		}
	}
}
