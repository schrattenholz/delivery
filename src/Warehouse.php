<?php

namespace Schrattenholz\Delivery;

use SilverStripe\ORM\DataObject;
use Silverstripe\Forms\TextField;
use Silverstripe\Forms\NumericField;
use Silverstripe\Forms\CheckboxField;
use Silverstripe\Forms\DropdownField;
use Silverstripe\Forms\HiddenField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Security\Permission;
class Warehouse extends DataObject
{
	private static $default_sort=['SortOrder'];
	private static $db = array (
		'Title'=>'Varchar',
		'Street'=>'Text',
		'HouseNumber'=>'Varchar(255)',
		'SortOrder' =>'Int'
	);
	private static $summary_fields = [
			'Title' => 'Titel',
			'Price'=>'Preis'
    ];

 	private static $singular_name="Lieferdepot";
	private static $plural_name="Lieferdepots";
	private static $table_name="Warehouse";
	private static $has_one = [
		'Product'=>Product::Class,
		'Unit'=>Unit::Class
	];
 	public function getCMSFields()
	{
		$fields=FieldList::create(TabSet::create('Root'));
		$fields->addFieldsToTab('Root.Main', [
			TextField::create('Title','Titel'),
			TextField::create('Street','Strasse'),
			TextField::create('HouseNumber','Hausnummer'),
           
			//DropdownField::create("UnitID","Einheit, falls abweichend eintragen.",Unit::get()->map("ID", "Title", "Bitte auswählen"))
        ]);
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}

}
?>
