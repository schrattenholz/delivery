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
use Schrattenholz\Order\Product;

class DeliveryExtension extends DataExtension {
	private static $allowed_actions = array (
		'getCities',
		'getDeliveryTypes',
		'getCollectionDays',
		'makeOrder_ClientOrder',
		'setCheckoutAddress_Basket',
		'LinkCheckoutDelivery',
		'setCheckoutDelivery',
		'getShippingOptions'
	);
	public function getShippingOptions($data){
		$priceBlockElementID=$data['priceBlockElementID'];
		$productID=$data['productID'];

		$data=new ArrayData(['id'=>$productID,'v'=>$priceBlockElementID]);
		return $this->owner->customise($data)->renderWith(ThemeResourceLoader::inst()->findTemplate(
				"Schrattenholz\\OrderProfileFeature\\Includes\\Product_Info_ShippingOptions",
				SSViewer::config()->uninherited('themes')
			));
		//return $paginatedProducts;
	}
	public function BasketDeliverySetup($productID=0,$priceBlockElementID=0){
		if(isset($_GET['v'])){
			$priceBlockElementID=$_GET['v'];
		}
		$values=new ArrayList();
		
		$basket=$this->owner->getBasket();
		$priceBlockElements=Product::get()->byID($productID)->GroupPreise();
		// Hole das DeliverySetup der Produktvariante
		if(!$priceBlockElementID && $priceBlockElements->Count>0){
			$priceBlockElementID=$priceBlockElements->Sort('SortID','ASC')->First()->ID;
			
		}
		$priceBlockElement=Preis::get()->byID($priceBlockElementID);
		if(isset($priceBlockElement) && $priceBlockElement->DeliverySetupID){
			// Die Produktvariante hat ein DeliverySetup
			Injector::inst()->get(LoggerInterface::class)->error("Setup der Produktvariante");
			$values->DeliverySpecial=false;
			$values->DeliverySetup=DeliverySetup::get()->byID($priceBlockElement->DeliverySetupID);
			//return $values;
		}else{
			// Die Produktvariante hat kein DeliverySetup, nimm das Default-Setup
			Injector::inst()->get(LoggerInterface::class)->error("StandrdSetup");
			$values->DeliverySpecial=false;
			$values->DeliverySetup=$this->DefaultDeliverySetup();
			//return $values;
		}
		//DeliverySetup der Produktvriante gefunden
		
		
		//Falls es im Warekoeb ein DeliverySpecial gibt wird dieses vorgezgezogen
		if(isset($basket) && $basket->DeliverySpecial){
			// Es besteht ein Hauptsetup im Warenkorb, dass alle anderen überschreibt (IsPrimary==true)
			$deliverySepcial=$this->SpecialDeliverySetup();
			if($values->DeliverySetup->ID=$deliverySepcial->ID){
				//Das Setup der Produktvariante entspricht dem DeliverySpecial  - > Es wird im Template kein Hinweis benötigt
				$values->DeliverySpecial=false;
			}else{
				//Das Setup der Produktvariante weicht von dem DeliverySpecial ab - > Es wird im Template ein Hinweis benötigt
				$values->DeliverySpecial=true;
			}
			$values->DeliverySetup=$deliverySepcial;
			return $values;
		}else{
			return $values;
		}
	}
	public function DeliverySetup(){
		
		$basket=$this->owner->getBasket();
		if(isset($basket) && $basket->DeliverySpecial){
			
			return $this->SpecialDeliverySetup();
		}else{
			
			return $this->DefaultDeliverySetup();
		}
	}
	public function DefaultDeliverySetup(){
		Injector::inst()->get(LoggerInterface::class)->error('-----------------____-----_____ DefaultDeliverySetup');
		$defaultSetup=DeliverySetup::get()->filter('IsDefault',1)->First();
		return $defaultSetup;
	}
	public function SpecialDeliverySetup(){
		
		$basket=$this->owner->getBasket();
		$deliverySetupAr=explode(",",$basket->DeliverySpecial);
		$priceBlockElement=Preis::get()->byID($deliverySetupAr[0]);
		
		$specialSetup=DeliverySetup::get()->byID($priceBlockElement->DeliverySetupID);
		$values=new ArrayList();
		$values->PriceBlockElement=$priceBlockElement;
		Injector::inst()->get(LoggerInterface::class)->error('-----------------____-----_____ SpecialDeliverySetup specialSetup Title='.$specialSetup->Title);
		return $specialSetup;
	}

	public function getActiveDeliveryTypes(){
		$deliveryTypes=ArrayList::create();
		$basket=$this->owner->getBasket();
		
		foreach(DeliveryType::get() as $dd){
			
			if(floatval($dd->MinOrderValue)<=floatval($basket->TotalPrice()->Price)){
				$deliveryTypes->push($dd);
			}
		}
		return $deliveryTypes;
	}
	public function LinkCheckoutDelivery(){
		$orderConfig=OrderConfig::get()->First();
		$checkoutDelivery=SiteTree::get()->where('ID='.$orderConfig->CheckoutDeliveryID)->First();
		return $checkoutDelivery->Link();
	}
	public function setCheckoutDelivery($data){
		$returnValues=new ArrayList(['Status'=>'good','Message'=>false,'Value'=>false]);
		$delivery=json_decode($this->getOwner()->utf8_urldecode($data['delivery']),true);
		$basket=$this->owner->getBasket();
		$deliveryType=DeliveryType::get()->filter('Type',$delivery['DeliveryType'])->First();
		$basket->DeliveryTypeID=$deliveryType->ID;
		$vars=new ArrayData(array("Basket"=>$basket,"Data"=>$delivery));
		$this->owner->extend('setCheckoutDelivery_SaveToBasket', $vars);
		if($delivery['DeliveryType']=="delivery"){
			// Lieferung
			 if(floatval($deliveryType->MinOrderValue)<=floatval($basket->TotalPrice()->Price)){
				$basket->ShippingDate=str_replace (".","-",$delivery['DeliveryDate']);
				$basket->RouteID=$delivery['DeliveryRoute'];
				$basket->CollectionDayID=0;
			}else{
				// Lieferoptionen nicht gespeichert
				$returnValues->Status="error";
				$returnValues->Message="Der Mindestbestellwert für eine Lieferung ist nicht erreicht. Bitte wählen Sie eine andere Lieferoption.";
				$returnValues->Value='';
				return json_encode($returnValues);
			}
			
		}else if($delivery['DeliveryType']=="collection"){
			// Abholung
			$basket->ShippingDate=str_replace (".","-",$delivery['CollectionDate']);
			$basket->RouteID=0;
			$basket->CollectionDayID=$delivery['CollectionDay'];
		}else{
			// Fehlende Angaben zu den Lieferoptionen
			$returnValues->Status="error";
			$returnValues->Message="Es fehlen Angaben zur den Lieferoptionen. ".$basket->TotalPrice()->Price;
			$returnValues->Value='';
			return json_encode($returnValues);
		}
		if($basket->write()){
			// Lieferoptione gespeichert
			$returnValues->Status="good";
			$returnValues->Message="Lieferoption wurde gespeichert".$basket->TotalPrice()->Price;
			$returnValues->Value='';
		}else{
			// Lieferoptionen nicht gespeichert
			$returnValues->Status="error";
			$returnValues->Message="Beim Speichern der Lieferoptionen ist ein Fehler aufgetreten. Bitte versuche es erneut.";
			$returnValues->Value='';
		}
		return json_encode($returnValues);
	}
	/*public function setCheckoutAddress_Basket($basket){
		$deliveryType=DeliveryType::get()->filter('Type',$basket->personenDaten['DeliveryType'])->First();
		$basket->DeliveryTypeID=$deliveryType->ID;
		if($basket->personenDaten['DeliveryType']=="delivery"){
			$basket->DeliveryDate=strtotime($basket->personenDaten['DeliveryDate']);
			$basket->RouteID=$basket->personenDaten['DeliveryRoute'];
			$basket->CollectionDayID=0;
		}else if($basket->personenDaten['DeliveryType']=="collection"){
			
			$basket->DeliveryDate=0;
			$basket->RouteID=0;
			$basket->CollectionDayID=$basket->personenDaten['CollectionDay'];
		}
	}*/
	// Fügt ein Produkt mit einer bevorzugten LieferSetUp dem Warenkorb hinzu
	public function addProduct_basketSetUp($vars){
		Injector::inst()->get(LoggerInterface::class)->error('-----------------____-----_____ sve 	 addProduct_basketSetUp');
		$basket=$vars->Basket;
		$productDetails=$vars->ProductDetails;

		if($productDetails->DeliverySetup && $productDetails->DeliverySetup()->IsPrimary){
			if($basket->DeliverySpecial){
				$basket->DeliverySpecial.=",".$productDetails->ID;
			}else{
				$basket->DeliverySpecial=$productDetails->ID;
			}
		}
		$basket->write();
	}
	// Entfernt ein Produkt mit einem bevorzugten LieferSetUp aus dem Warenkorb
	public function removeProduct_basketSetUp($vars){
		
		$basket=$vars->Basket;
		$productDetails=$vars->ProductDetails;
		//if($productDetails->DeliverySetup && $productDetails->DeliverySetup()->IsPrimary){
			$specArray=explode(",",$basket->DeliverySpecial);
			$specArray=array_diff($specArray,array($productDetails->ID));
			$basket->DeliverySpecial=implode(",",$specArray);
			$basket->write();
		//}
	}
	public function makeOrder_ClientOrder($vars){
		$basket=$vars->Basket;
		$order=$vars->Order;

		$order->DeliveryTypeID=$basket->DeliveryTypeID;

		if($basket->DeliveryType()->Type=="delivery"){
			$order->ShippingDate=$basket->ShippingDate;
			$order->RouteID=$basket->RouteID;
			$order->CollectionDayID=0;
		}else if($basket->DeliveryType()->Type=="collection"){
			$order->ShippingDate=$basket->ShippingDate;
			$order->RouteID=0;
			$order->CollectionDayID=$basket->CollectionDayID;
		}
	}
	 public function onAfterInit(){
		$vars = [
			"Link"=>$this->getOwner()->Link(),
			"ID"=>$this->owner->ID
		];
		Requirements::javascriptTemplate("schrattenholz/delivery:javascript/delivery.js",$vars);
	}

	public function getDeliveryTypes(){
		return DeliveryType::get();
	}
	public function getCities(){
		return City::get();
	}
	public function getCollectionDays(){
		return CollectionDay::get();
	}
	public function CheckoutAdressDeliveryForm(){
		return $this->getOwner()->renderWith(ThemeResourceLoader::inst()->findTemplate(
				"Schrattenholz\\Delivery\\Includes\\CheckoutAdressDeliveryForm",
				SSViewer::config()->uninherited('themes')
			));
	}
	function utf8_urldecode($str) {
		$str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));
		return html_entity_decode($str,null,'UTF-8');;
	} 
}
