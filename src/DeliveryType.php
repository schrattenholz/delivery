<?php

namespace Schrattenholz\Delivery;

use Schrattenholz\OrderProfileFeature\OrderCustomerGroup;
use SilverStripe\ORM\DataObject;
use Silverstripe\Forms\TextField;
use Silverstripe\Forms\NumericField;
use Silverstripe\Forms\CheckboxField;
use SilverStripe\Forms\CheckboxSetField;
use Silverstripe\Forms\DropdownField;
use Silverstripe\Forms\HiddenField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;

class DeliveryType extends DataObject
{
	private static $default_sort=['SortOrder'];
	private static $db = array (
		'Title'=>'Varchar(255)',
		'SortOrder'=>'Int',
		'Type'=>'Enum("collection,delivery,shipping","collection")'
	);
	private static $has_many=[
		"Routes"=>Route::class,
		"MinOrderValues"=>MinOrderValue::class,
		
	];
	private static $many_many=[
		"OrderCustomerGroups"=>OrderCustomerGroup::class
	];
	private static $many_many_extraFields = [
		'OrderCustomerGroups' => [
			'IsActive' => 'Boolean',
			'MinOrderValue' => 'Decimal(6,2)'
		]
	];
	private static $summary_fields = [
			'Title' => ' Versandart'
    ];
 	private static $singular_name="Versandart";
	private static $plural_name="Versandarten";
	private static $table_name="DeliveryType";

 	public function getCMSFields()
	{
		$fields=FieldList::create(TabSet::create('Root'));



		
		$type=new DropdownField("Type","Versandart",singleton('Schrattenholz\\Delivery\\DeliveryType')->dbObject('Type')->enumValues());
		$fields->addFieldsToTab('Root.Main', [
			TextField::create('Title','Titel'),
			$type
			
			//DropdownField::create("UnitID","Einheit, falls abweichend eintragen.",Unit::get()->map("ID", "Title", "Bitte auswählen"))
        ]);
				/*$ocg=new CheckboxSetField( $name = "CollectionDays", $title = "Anzeige für folgende Kundengruppen", OrderCustomerGroup::get() );
		$fields->addFieldToTab('Root.Main', $ocg,'MinOrderValues');*/
		//MinOrderValue pro Kundengruppe
		$gridFieldConfig=GridFieldConfig::create()
			->addComponent(new GridFieldButtonRow('before'))
			->addComponent($dataColumns=new GridFieldDataColumns)
			->addComponent($editableColumns=new GridFieldEditableColumns())
			->addComponent(new GridFieldSortableHeader())
			->addComponent(new GridFieldFilterHeader())
			->addComponent(new GridFieldPaginator())
			
		;
		$dataColumns->setDisplayFields([
			'Title' => 'Kundengruppe'
		]);

		
		$editableColumns->setDisplayFields(array(

			'MinOrderValue'  =>array(
					'title'=>'Mindestbestellwert',
					'callback'=>function($record, $column, $grid) {
						return NumericField::create($column)->setScale(2);
				}),
			'IsActive'  =>array(
					'title'=>'Aktiv für diese Gruppe',
					'callback'=>function($record, $column, $grid) {
						return CheckboxField::create($column);
				}),
				
		));
		$fields->addFieldToTab('Root.Main', GridField::create(
			'OrderCustomerGroups',
			'Mindestbestellwert',
			$this->OrderCustomerGroups(),
			$gridFieldConfig
		));
		
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	public function onAfterWrite(){
		parent::onAfterWrite();
		//Injector::inst()->get(LoggerInterface::class)->error('-----------------____-----_____ Export before Value='.$label.' Field='.$field);
		$ocgs=OrderCustomerGroup::get();
		//if($this->OrderCustomerGroups()->Count()>$ocgs->Count()){
			foreach($ocgs as $ocg){
				$this->OrderCustomerGroups()->add($ocg);
			}
		//}
	}
	public function getMinOrderValue(){
		return "muh";
		if($this->MinOrderValues()->filter('OrderCustomerGroupID',$ocg->ID)>0){
				return $this->MinOrderValues()->filter('OrderCustomerGroupID',$ocg->ID);
			
		}else{
			return false;
		}
	
	}
}
?>
