<?php

namespace Schrattenholz\Delivery;

use Silverstripe\ORM\DataExtension;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridField_ActionMenu;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SwiftDevLabs\DuplicateDataObject\Forms\GridField\GridFieldDuplicateAction;
use Silverstripe\ORM\ArrayList;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Security;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;
use Silverstripe\Security\Group;
use SilverStripe\ORM\ValidationException;

use SilverStripe\Forms\ListboxField;

use Schrattenholz\Order\Preis;

class DeliverySetup_ProductListExtension extends DataExtension{
	private static $has_one=['DeliverySetup'=>DeliverySetup::class];
	private static $allowed_actions = array (
	);
	// Extension for ProductList::getCMSFields
	public function addExtension(FieldList $fields){
		$fields->addFieldToTab("Root.Produkte",DropdownField::create("DeliverySetupID","Liefer-Setup",DeliverySetup::get()->map('ID', 'Title'))->setEmptyString(utf8_encode('(Bitte auswählen)')),'Preise');
	}
	
	//Speichert in jedes PriceBlockElement der gewählten Liste die Deliverywerte ein
	public function HOOK_Order_ProductListExtension_AfterWrite_Product($product){
		if($this->owner->DeliverySetup()->IsPrimary){
			$deliverySpecial=true;
		}else{
			$deliverySpecial=false;
		}
		$product->DeliverySpecial=$deliverySpecial;
		$product->DeliverySetupID=$this->owner->DeliverySetupID;
	}
	public function HOOK_Order_ProductListExtension_AfterWrite($ref){
		//Liefertermin für Datum
		
		$deliverySetup=$ref->DeliverySetup();
		if($ref->InPreSale){
			Injector::inst()->get(LoggerInterface::class)->error($ref->PreSaleStart.'neues Lieferdateum setzten altes Datum='.$deliverySetup->DeliveryStart);
			$deliverySetup->DeliveryStart=strftime("%Y-%m-%d",strtotime($ref->PreSaleEnd));
			$deliverySetup->write(); // saves the record
		}else if($ref->ResetPreSale){
			Injector::inst()->get(LoggerInterface::class)->error($ref->PreSaleStart.' Lieferdateum entfernen altes Datum='.$deliverySetup->DeliveryStart);
			$deliverySetup->DeliveryStart=null;
			$deliverySetup->write(); // saves the record
		}
		
		if($ref->DeliverySetup()->IsPrimary){
			$deliverySpecial=true;
		}else{
			$deliverySpecial=false;
		}
		//parent::onAfterWrite();
	}
}