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
use Silverstripe\Forms\HiddenField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FormAction;
use Kinglozzer\MultiSelectField\Forms\MultiSelectField;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationException;
use Psr\Log\LoggerInterface;
use SilverStripe\Security\Permission;
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
		'EnrollDeliverySetup'=>'Boolean'
	);
	private static $allowed_actions=[
		"enrollDeliverySetup"
	];
	public function enrollDeliverySetup(){
		Injector::inst()->get(LoggerInterface::class)->info('enrollDeliverySetup');
	}
	private static $many_many=[
		'Route_DeliveryDays'=>DeliveryDay::class,
		'CollectionDays'=>CollectionDay::class,
		'Routes'=>Route::class
	];
	private static $has_many=[
		'Attributes'=>Attribute::class
	];
	private static $summary_fields = [
		'Title' => 'Liefer-Setup'
    ];
	public function getNextCollectionDays($orderCustomerGroupID,$deliverySetupID){
		$sortedCollectionDays=new ArrayList();
		foreach($this->CollectionDays() as $cD){
			Injector::inst()->get(LoggerInterface::class)->error("CollectionDays=".$cD->DayTranslated());
			$nextDate=$cD->getNextDate($orderCustomerGroupID,$deliverySetupID);
			if($nextDate){
				
				
				$sortedCollectionDays->add(
					array(
					"Sort"=>$nextDate->Short,
					"DayTranslated"=>$cD->DayTranslated(),
					"Date"=>array(
						"Short"=>$nextDate->Short,
						"Eng"=>$nextDate->Eng
					),
					"Time"=>array(
						"From"=>strftime("%H:%M",strtotime($nextDate->TimeFrom)),
						"To"=>strftime("%H:%M",strtotime($nextDate->TimeTo))
						),
					"Day"=>$cD->Day,
					"ID"=>$cD->ID),
					
				);
			}
		}
		return $sortedCollectionDays->Sort("Sort","ASC");
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
		
		// Regelt ob der nächst mögliche Liefertermin angeboten wird, 
		// wenn der erste Termin durch den Bestellschluss schon abgelaufen ist
		
		$fields->addFieldToTab('Root.Main',CheckboxField::create("NoNextDeliveryDate",utf8_encode("Lieferung nur einmalig möglich. (Ist der Bestellschluss erreicht, wird kein Alternativtermin in der nächsten Woche angezeigt.)")));
		
		// Als Standard festlegen
		$fields->addFieldToTab('Root.Main',CheckboxField::create("IsDefault",utf8_encode("Standard-Setup   (Dieses Liefer-Setup wird als Standard verwendet, wenn im Produkt im Warenkorb kein spezielles Liefer-Setup ausgewählt wurde.)")));
		// Als Haupt-Setup festlegen festlegen
		$fields->addFieldToTab('Root.Main',CheckboxField::create("IsPrimary",utf8_encode("Haupt-Setup   (Dieses Liefer-Setup wird im Warenkorb als Haupt-Setup verwendet. Andere Setups im Warenkorb, die nicht als Haupt-Setup deklariert sind, werden ignoriert. Sind mehrer Haup-Setups im Warenkorb vorhanden, wird die kleinste gemeinsame Menge an Lieferoptionen angeboten.)")));
		//Auswahl der Abholoption
		$collectionDays=new CheckboxSetField( $name = "CollectionDays", $title = "Abholtage", CollectionDay::get() );
		$fields->addFieldToTab('Root.Main', $collectionDays);
		//Auswahl der Lieferoptionen
		$fields->addFieldToTab('Root.Main',FormAction::create('enrollDeliverySetup')->setTitle('Liefer-Setup ausspielen'));
		
        $deliveryDays = MultiSelectField::create('Route_DeliveryDays', 'Routen / Liefertage', $this);
        $fields->addFieldToTab('Root.Main', $deliveryDays);
		
		
		
		
		
        return $fields;
    }
	
	public function getActiveRoutes(){
		
		// DeliveryDays sind die einzelnen Tage an denen Routen zur Verfügung stehen. 
		// DeliveryDayID + RouteID
		if($this->Route_DeliveryDays()){
			$routes=[];
			foreach($this->Route_DeliveryDays() as $dd){
				array_push($routes,$dd->RouteID);
			}
			return array_unique($routes);
		}else{
			//Es sind keine Routentage hinterlegt.
			return false;
		}
	}
	public function getCity($currentOrderCustomerGroupID,$ZIP,$City){
		$routes=$this->getActiveRoutes();
		if($routes){
			$cities=ArrayList::create();
			foreach($this->Routes()->filter('ID',$routes) as $r){
				
				// Gibt die naechsten Liefertage heraus. Filtert nach Kundengruppe. Und verwendet nur die DeliveryDays der Route, die im Liefersetup aktiviert sind
				$nextDeliveryDate=$r->getNextDeliveryDates($currentOrderCustomerGroupID,$this->ID);
								
				
				// Sucht den verfuebaren Orte auf der Route
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
				
				// Gibt die naechsten Liefertage heraus. Filtert nach Kundengruppe. Und verwendet nur die Liefertage der Route, die im Liefersetup aktiviert sind (Route_DeliveryDays)
				$nextDeliveryDate=$r->getNextDeliveryDates($currentOrderCustomerGroupID,$this->ID);
				
				// Stellt alle verfuebaren Orte auf der Route zusammen
				foreach($r->Cities() as $c){
					$c->DeliveryDate=$nextDeliveryDate;
					$c->ArrivalTime=strftime("%H:%M",strtotime($c->ArrivalTime)). " Uhr";
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
