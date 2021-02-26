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
class Delivery_ZIPCode extends DataObject
{
	private static $default_sort=['SortOrder'];
	private static $db = [
		'Title'=>'Int',
		'SortOrder'=>'Int'
	];
	private static $belongs_many_many=[
		'Cities'=>City::class,
	];
	private static $summary_fields = [
		'Title' => 'PLZ'
    ];
 	private static $singular_name="Postleitzahl";
	private static $plural_name="Postleitzahlen";
	private static $table_name="Delivery_ZIPCode";

 	public function getCMSFields()
	{
		$fields=FieldList::create(TabSet::create('Root'));
		$fields->addFieldsToTab('Root.Main', [
			TextField::create('Title','PLZ')           
			//DropdownField::create("UnitID","Einheit, falls abweichend eintragen.",Unit::get()->map("ID", "Title", "Bitte auswählen"))
        ]);
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}

}
?>
