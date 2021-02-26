<?php

namespace Schrattenholz\Delivery;

use SilverStripe\ORM\DataObject;
use Silverstripe\Forms\TextField;
use Silverstripe\Forms\NumericField;
use Silverstripe\Forms\CheckboxField;
use Silverstripe\Forms\LiteralField;
use Silverstripe\Forms\DropdownField;
use Silverstripe\Forms\HiddenField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Security\Permission;
class City extends DataObject
{
	private static $singular_name="Orte";
	private static $plural_name="Orte";
	private static $table_name="Delivery_City";
	private static $default_sort=['SortOrder'];
	private static $db = array (
		'Title'=>'Varchar(255)',
		'ZIP'=>'Int',
		'SortOrder'=>'Int'
	);
	private static $has_many=[
		'Warehouses'=>Warehouse::class
	];
	private static $many_many=[
		'Delivery_ZIPCodes'=>Delivery_ZIPCode::class
	];
	private static $many_many_extraFields =[
		'Delivery_ZIPCodes'=>[
			'Latidude'=>'Float',
			'Longitude'=>'Float',
			'State'=>'Varchar(255)',
			'Community'=>'Varchar(255)'
			]
	];
	private static $belongs_many_many=[
		'Routes'=>Route::class,
	];
	private static $summary_fields = [
		'Title' => 'Ortsname'
    ];
   private static $searchable_fields = [
      'Title'
   ];
	public function hasZIP($ZIP){
		foreach($this->Delivery_ZIPCodes() as $z){
			if($z->Title==$ZIP){
				return true;
			}
		}
		return false;
	}
 	public function getCMSFields()
	{
		$fields=FieldList::create(TabSet::create('Root'));
		$config = GridFieldConfig_RecordEditor::create();
		$warehouses = new GridField('Warehouses', 'Depots in diesem Ort', Warehouse::get());
		$warehouses->setConfig($config);
		$config = GridFieldConfig_RelationEditor::create();
		$zipCodes = new GridField('Delivery_ZIPCodes', 'Postleitzahlen für diesen Ort', $this->Delivery_ZIPCodes());
		$zipCodes->setConfig($config);

		$info=LiteralField::create("Info","<h3 style='margin-top:2rem;'><strong>Voricht bei PLZs!!!</strong></h3><p style='margin-top:-1rem;margin-bottom:2rem;'>Bitte nutze erst die Suche um zu prüfen ob die Postleitzahl bereits hinterlegt ist</p>");
		$fields->addFieldsToTab('Root.Main', [
			TextField::create('Title','Ortsname'),
			$warehouses,
			$info,
			$zipCodes
			//DropdownField::create("UnitID","Einheit, falls abweichend eintragen.",Unit::get()->map("ID", "Title", "Bitte auswählen"))
        ]);
		
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}	
	 public function canView($member = null) 
    {
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }

    public function canEdit($member = null) 
    {
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }

    public function canDelete($member = null) 
    {
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }

    public function canCreate($member = null, $context = []) 
    {
        return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }
}
?>
