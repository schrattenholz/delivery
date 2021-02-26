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
class DeliveryType extends DataObject
{
	private static $default_sort=['SortOrder'];
	private static $db = array (
		'Title'=>'Varchar(255)',
		'SortOrder'=>'Int',
		'Type'=>'Enum("collection,delivery,shipping","collection")',
		'MinOrderValue'=>'Decimal(6,2)'
	);
	private static $has_many=[
		"Routes"=>Route::class
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
		$minOV=new NumericField("MinOrderValue","Mindestbestellwert");
		$minOV->setLocale("DE_De");
		$minOV->setScale(2);
		
		$type=new DropdownField("Type","Versandart",singleton('Schrattenholz\\Delivery\\DeliveryType')->dbObject('Type')->enumValues());
		$fields->addFieldsToTab('Root.Main', [
			TextField::create('Title','Titel'),
			$type,
			$minOV
			//DropdownField::create("UnitID","Einheit, falls abweichend eintragen.",Unit::get()->map("ID", "Title", "Bitte auswählen"))
        ]);
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}

}
?>
