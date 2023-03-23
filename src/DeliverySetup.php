<?php

namespace Schrattenholz\Delivery;

use Schrattenholz\Order\Attribute;
use Schrattenholz\Order\Preis;

use SilverStripe\ORM\DataObject;
use Silverstripe\Forms\TextField;
use Silverstripe\Forms\NumericField;
use Silverstripe\Forms\ListboxField;
use Silverstripe\Forms\CheckboxField;
use Silverstripe\Forms\DropdownField;
use Silverstripe\Forms\HTMLEditor\HTMLEditorField;
use Silverstripe\Forms\HiddenField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FormAction;
use Kinglozzer\MultiSelectField\Forms\MultiSelectField;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\Queries\SQLSelect;
use Psr\Log\LoggerInterface;
use SilverStripe\Security\Permission;
use Schrattenholz\OrderProfileFeature\OrderProfileFeature_Basket;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Control\Session;



use SilverStripe\CMS\Model\SiteTree;


use SilverStripe\Control\Email\Email;
use Schrattenholz\Order\Backend;


use SilverStripe\View\Requirements;

use SilverStripe\Security\Security;



class DeliverySetup extends DataObject
{
	private static $singular_name="LieferSetup";
	private static $plural_name="LieferSetup";
	private static $table_name="Delivery_Setup";
	private static $default_sort=['SortOrder'];
	private static $db = array (
		'Title'=>'Varchar(255)',
		'SortOrder'=>'Int',
		'IsDefault'=>'Boolean',
		'IsPrimary'=>'Boolean',
		'DeliveryStart'=>'Date',
		'NoNextDeliveryDate'=>'Boolean',
		'NoDates'=>'Boolean',
		'EnrollDeliverySetup'=>'Boolean',
		'WeeksToShow'=>'Enum("1,2,3,4,5,6,7,8","1")',
		'ContentProductShippingInfo'=>'HTMLText',
		'GeneralShippingInfo'=>'HTMLText',
		'OpenPreSaleSetup'=>'Boolean'
	);
	private static $allowed_actions=[
		"enrollDeliverySetup"
	];
	public function enrollDeliverySetup(){
		//Injector::inst()->get(LoggerInterface::class)->info('enrollDeliverySetup');
	}
	private static $many_many=[
		'Route_DeliveryDays'=>DeliveryDay::class,
		'CollectionDays'=>CollectionDay::class,
		'Routes'=>Route::class,
		'DeliveryTypes'=>DeliveryType::class
	];
	private static $has_many=[
		'Attributes'=>Attribute::class
	];
	private static $summary_fields = [
		'Title' => 'Liefer-Setup'
    ];
	public function MinOrderValue($ocgID,$type){
		//OrderCustomerGroupID und Type werden aus dem Template gesendet Product_Info_ShippingOptions.ss
		
		$deliveryType=DeliveryType::get()->filter("Type",$type)->First();
		$group=$deliveryType->OrderCustomerGroups()->filter(["OrderCustomerGroupID"=>$ocgID,"DeliveryTypeID"=>$deliveryType->ID])->First();
		//Injector::inst()->get(LoggerInterface::class)->error("deliveryType->ID=".$deliveryType->ID."ocgID=".$ocgID. " group minordervlue=".$group->MinOrderValue);
		return $group->MinOrderValue;
		
		
	}
	public function CustomDeliverySetup(){		
		$basket=$this->owner->getBasket();
		if(isset($basket) && $basket->DeliverySpecial){		
			$setup=	 $this->SpecialDeliverySetup();	
			if($basket->ShippingDate){
				$setup->ShippingDate=$basket->ShippingDate;
			}
			return $setup;
		}else{
			$setup=$this->DefaultDeliverySetup();
			if($basket->ShippingDate){
				$setup->ShippingDate=$basket->ShippingDate;
			}
			return $setup;
		}
	}
		function getSession(){
		$request = Injector::inst()->get(HTTPRequest::class);
		$session = $request->getSession();
		return $session;
	}
	public function getSessionBasketID(){
		$request = Injector::inst()->get(HTTPRequest::class);
		//$session = $request->getSession();
		return $this->getSession()->get('basketid');
	}
	public function getSessionOrderID(){
		$request = Injector::inst()->get(HTTPRequest::class);
		//$session = $request->getSession();
		return $this->getSession()->get('orderid');
	}
	public function getBasket(){
		return OrderProfileFeature_Basket::get()->byID($this->getSessionBasketID());
	}
	public function getNextCollectionDays($orderCustomerGroupID,$deliverySetupID,$productID=0,$variantID=0){
		$deliverySetup=DeliverySetup::get()->byID($deliverySetupID);
		$basket=$this->owner->getBasket();
		
			if($basket && $basket->ShippingDate){
					$deliverySetup->ShippingDate=$basket->ShippingDate;
			}
		
		//Injector::inst()->get(LoggerInterface::class)->error("getNextCollectionDays");
		$sortedCollectionDays=new ArrayList();
		foreach($this->CollectionDays() as $cD){
			
			$nextDates=$cD->getNextDates($orderCustomerGroupID,$deliverySetup,$this->WeeksToShow,$productID,$variantID);
			if($nextDates){
				foreach($nextDates as $nextDate){
					$sortedCollectionDays->add(
						array(
						"Sort"=>$nextDate->Short,
						"DayTranslated"=>$cD->DayTranslated(),
						"DayTranslatedShort"=>$cD->DayTranslatedShort(),
						"Date"=>array(
							"Short"=>$nextDate->Short,
							"Eng"=>$nextDate->Eng
						),
						"Eng"=>$nextDate->Eng,
						"Time"=>array(
							"From"=>strftime("%H:%M",strtotime($nextDate->TimeFrom)),
							"To"=>strftime("%H:%M",strtotime($nextDate->TimeTo))
							),
						"Day"=>$cD->Day,
						"ID"=>$cD->ID),
						
					);
					//Injector::inst()->get(LoggerInterface::class)->error("getNextCollectionDays ".$nextDate->Eng);
				}
			}
			
		}
		return $sortedCollectionDays->Sort("Date.Eng","ASC");
	}
	  public function getCMSFields()
    {
        $fields = parent::getCMSFields();
		
		
		//Felder entfernen
		$fields->removeByName('Route_DeliveryDays');
		$fields->removeByName('CollectionDays');
		$fields->removeFieldFromTab('Root.Main','SortOrder');

		$attributes=new ListboxField("Attributes", "Produktattribute",Attribute::get()->map("ID", "Title", "Bitte auswählen"));
		$fields->addFieldToTab('Root.Main',$attributes);
		$fields->addFieldToTab('Root.Main',CheckboxField::create("EnrollDeliverySetup",utf8_encode("Setze dieses Liefer-Setup bei allen Produkten ein, die eines der ausgewählten Produktattribute verwenden. (Häckchen setzen und 'Speichern', um dieses Setup einmalig auszuspielen)")));
		
		// Liefer-Start festlegen
		
		$fields->addFieldToTab('Root.Main',DateField::create("DeliveryStart",utf8_encode("Datum der frühsten Liefermöglichkeit")));
		
		// Legt fest, wieviele Wochen angeboten werden
		$fields->addFieldsToTab('Root.Main', [
			DropdownField::create('WeeksToShow', 'Anzahl Wochen, für die Termine angezeigt werden.( Bei 1 ist es nur der nächst mögliche Termin, bei 2 wird auch die darauf folgende Woche angezeigt)',singleton('Schrattenholz\\Delivery\\DeliverySetup')->dbObject('WeeksToShow')->enumValues())
        ]);
		
		// Regelt ob der nächst mögliche Liefertermin angeboten wird, 
		// wenn der erste Termin durch den Bestellschluss schon abgelaufen ist
		
		$fields->addFieldToTab('Root.Main',CheckboxField::create("NoNextDeliveryDate",utf8_encode("Lieferung nur einmalig möglich. (Ist der Bestellschluss erreicht, wird kein Alternativtermin in der nächsten Woche angezeigt.)")));
		
		$fields->addFieldToTab('Root.Texte',HTMLEditorField::create("ContentProductShippingInfo",utf8_encode("Wird im Produkt unter Abhol/Lieferoptionen angezeigt")));		
		$fields->addFieldToTab('Root.Texte',HTMLEditorField::create("GeneralShippingInfo",utf8_encode("Wird im Checkout angezeigt, wenn andere LieferSetups unterdrückt werden.")));
		
		$fields->addFieldToTab('Root.Main',CheckboxField::create("NoDates",utf8_encode("Es werden nur die Liefermöglchkeiten angezeigt, aber keine Termine gennant. (Aberverkauf. Es wird geliefert, wenn alles verkauft ist)")));
		
		// Als Standard festlegen
		$fields->addFieldToTab('Root.Main',CheckboxField::create("IsDefault",utf8_encode("Standard-Setup   (Dieses Liefer-Setup wird als Standard verwendet, wenn im Produkt im Warenkorb kein spezielles Liefer-Setup ausgewählt wurde.)")));
		// Als Haupt-Setup festlegen festlegen
		$fields->addFieldToTab('Root.Main',CheckboxField::create("IsPrimary",utf8_encode("Haupt-Setup   (Dieses Liefer-Setup wird im Warenkorb als Haupt-Setup verwendet. Andere Setups im Warenkorb, die nicht als Haupt-Setup deklariert sind, werden ignoriert. Sind mehrer Haup-Setups im Warenkorb vorhanden, wird die kleinste gemeinsame Menge an Lieferoptionen angeboten.)")));
		
		//Versandarten aktivieren
		$deliveryTypes=new CheckboxSetField( $name = "DeliveryTypes", $title = "Versandarten", DeliveryType::get() );
		$fields->addFieldToTab('Root.Main', $deliveryTypes);
		
		// OpenPrSaleSetup
		$fields->addFieldToTab('Root.Main',CheckboxField::create("OpenPreSaleSetup",utf8_encode("Dieses Liefersetup wird für offene Vorverkäufe verwendet.")));
		
		
		
		//Auswahl der Abholoption
		$collectionDays=new CheckboxSetField( $name = "CollectionDays", $title = "Abholtage", CollectionDay::get() );
		$fields->addFieldToTab('Root.Main', $collectionDays);
		//Auswahl der Lieferoptionen
		$fields->addFieldToTab('Root.Main',FormAction::create('enrollDeliverySetup')->setTitle('Liefer-Setup ausspielen'));
		
        $deliveryDays = MultiSelectField::create('Route_DeliveryDays', 'Routen / Liefertage', $this,false,DeliveryDay::get()->Filter("ClassName","Schrattenholz\Delivery\DeliveryDay"));
		//Es sollen nur die Einträge von DeliveryDay angezeigt werden, alle andere müssen ausgefiltert werden
		//$collectionsDays=
		//$deliveryDays->setDisabledItems($inChangeSets);
        $fields->addFieldToTab('Root.Main', $deliveryDays);
		
		
		
		
		
        return $fields;
    }
	
	public function getActiveRoutes(){
		
		// DeliveryDays sind die einzelnen Tage an denen Routen zur Verfügung stehen. 
		// DeliveryDayID + RouteID
		if($this->Route_DeliveryDays()){
			$routes=[];
			$sqlQuery = new SQLSelect();
			$sqlQuery->setFrom('Delivery_Setup_Route_DeliveryDays');
			$sqlQuery->selectField('DeliveryDaysID', 'DeliveryDaysID');
			$sqlQuery->addWhere(['Delivery_SetupID = ?' => $this->ID]);
			$result = $sqlQuery->execute();

			$route_deliveryDays=$this->Route_DeliveryDays()->innerJoin("Delivery_Setup_Route_DeliveryDays", "\"RDD\".\"DeliveryDaysID\" = \"DeliveryDays\".\"ID\"", "RDD")->where("Delivery_Setup_Route_DeliveryDays.Delivery_SetupID",3);
			foreach($route_deliveryDays as $dd){
				//Injector::inst()->get(LoggerInterface::class)->error("getCityNEW route=".$dd->Delivery_SetupID);
				array_push($routes,$dd->RouteID);
			}
			return array_unique($routes);
		}else{
			//Es sind keine Routentage hinterlegt.
			return false;
		}
	}
	public function getCityNEW($currentOrderCustomerGroupID,$ZIP,$City){
		$activeRoutes=$this->getActiveRoutes();
		//var_dump($activeRoutes);
		foreach($activeRoutes as $r){
			
		}
		// Object, das den Ort und alle Routen beinhaltet, auf denen der Ort angefahren wird
		$cityAndRoutes=new ArrayList();
		$routes=[];
		$routesArrayList=new ArrayList();
		
		if($activeRoutes){
			
			$cities=ArrayList::create();
			foreach($this->Routes()->filter('ID',$activeRoutes) as $r){
				// Gibt die naechsten Liefertage heraus. Filtert nach Kundengruppe. Und verwendet nur die DeliveryDays der Route, die im Liefersetup aktiviert sind
				//$nextDeliveryDate=$r->getNextDeliveryDates($currentOrderCustomerGroupID,$this->ID);
				// Sucht den verfuegbaren Orte auf der Route
				foreach($r->Cities() as $c){
					if($c->Title==$City && $c->hasZIP($ZIP)){
						//$c->DeliveryDate=$nextDeliveryDate;
						//$c->ArrivalTime=strftime("%H:%M",strtotime($c->ArrivalTime)). " Uhr";
						$cityAndRoutes->City=$c;
						//Injector::inst()->get(LoggerInterface::class)->error("getCityNEW  c->Title=  ".$c->Title);
						array_push($routes,$r->ID);
						//return $c;
					}
				}
			}
			if($cityAndRoutes->City){				
				
				foreach(array_unique($routes) as $route){
					$routesArrayList->push(new ArrayData(array('ID'=>$route)));
					//Injector::inst()->get(LoggerInterface::class)->error("getCityNEW  route->ID=  ".$route);
				}
				$cityAndRoutes->Routes=$routesArrayList;
				return $cityAndRoutes;
			}
			// Es wurden kein Ort gefunden
			return false;
		}else{
			// Es wurden keine Routen gefunden
			return false;
		}
		return false;
	}
	public function getCity($currentOrderCustomerGroupID,$ZIP,$City){
		$routes=$this->getActiveRoutes();
		if($routes){
			$cities=ArrayList::create();
			foreach($this->Routes()->filter('ID',$routes) as $r){
				
				// Gibt die naechsten Liefertage heraus. Filtert nach Kundengruppe. Und verwendet nur die DeliveryDays der Route, die im Liefersetup aktiviert sind
				$nextDeliveryDate=$r->getNextDeliveryDates($currentOrderCustomerGroupID,$this,0,0);
								
				
				// Sucht den verfuegbaren Orte auf der Route
				foreach($r->Cities() as $c){
					if($c->Title==$City && $c->hasZIP($ZIP)){
						$c->DeliveryDate=$nextDeliveryDate;
						$c->ArrivalTime=strftime("%H:%M",strtotime($c->ArrivalTime)). " Uhr";
						return $c;
					}
				}
			}
			// Es wurden kein Ort gefunden
			return false;
		}else{
			// Es wurden keine Routen gefunden
			return false;
		}
		return false;
	}
	public function getCities($currentOrderCustomerGroupID){
		
		$routes=$this->getActiveRoutes();
		if($routes){
			$cities=ArrayList::create();
			foreach($this->Routes()->filter('ID',$routes) as $r){
				Injector::inst()->get(LoggerInterface::class)->error("getCities route=".$r->Title." - ".$r->ID);
				// Gibt die naechsten Liefertage heraus. Filtert nach Kundengruppe. Und verwendet nur die Liefertage der Route, die im Liefersetup aktiviert sind (Route_DeliveryDays)
				//$nextDeliveryDate=$r->getNextDeliveryDates($currentOrderCustomerGroupID,$this->ID);
				
				// Stellt alle verfuebaren Orte auf der Route zusammen
				foreach($r->Cities() as $c){
					//$c->DeliveryDate=$nextDeliveryDate;
					//$c->ArrivalTime=strftime("%H:%M",strtotime($c->ArrivalTime)). " Uhr";
					$cities->push($c);
				}
			}
			return $cities->removeDuplicates('ID');
		}else{
			// Es wurden keine Routen gefunden
			return false;
		}
	}
	public function onBeforeWrite(){
		
		
		$routes=[];
		$this->Routes()->removeAll();
		foreach($this->Route_DeliveryDays() as $dd){
			$this->Routes()->add($dd->Route());
		}
		array_unique($routes);
		parent::onBeforeWrite();
	}
	public function onAfterWrite(){
		//Setz dieses Liefer-Setup bei allen Produkten ein, die die eines der ausgewählten Lieferattribute verwenden
		if($this->EnrollDeliverySetup){
			$attributeIDs=array();
				foreach($this->Attributes() as $s){
				array_push($attributeIDs,$s->ID);
			}
			
	foreach(Preis::get()->innerJoin("Preis_Attributes", "\"pa\".\"PreisID\" = \"Preis\".\"ID\"","pa")->filter(['Attributes.ID'=>$attributeIDs]) as $p){
				
				$p->DeliverySetupID=$this->ID;
				$p->write();
			}	
			$update = SQLUpdate::create('Delivery_Setup')->addWhere(['ID' => $this->owner->ID]);
			$update->assign('EnrollDeliverySetup', false);
			$update->execute();
		}
		parent::onAfterWrite();
	}

}
