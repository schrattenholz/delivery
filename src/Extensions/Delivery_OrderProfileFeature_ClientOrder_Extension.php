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

use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\DropdownField;

//Extends OrderProfileFeature_Basket & OrderProfileFeature_ClientOrder

class Delivery_OrderProfileFeature_ClientOrder_Extension extends Extension {
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
			return "am ".date("d.m.Y",strtotime((string) $this->owner->ShippingDate));
		}else if($this->owner->DeliveryType()->Type=="collection" && $this->owner->ShippingDate){
			return "am ".date("d.m.Y",strtotime((string) $this->owner->ShippingDate))." </br>zwischen ".date("H:i",strtotime((string) $this->owner->CollectionDay()->TimeFrom))." und ".date("H:i",strtotime((string) $this->owner->CollectionDay()->TimeTo))." Uhr";
		}else{
			return "nach vorheriger Benachrichtigung";
		}
	}
}
