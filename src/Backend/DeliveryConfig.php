<?php

namespace Schrattenholz\Delivery;

use SilverStripe\ORM\DataObject;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\File;
use SilverStripe\Security\Permission;
use Schrattenholz\OrderProfileFeature\OrderCustomerGroup;
use SilverStripe\Forms\CheckboxSetField;
class DeliveryConfig extends DataObject
{
	private static $db = array (
	);
	private static $table_name="deliveryconfig";
	private static $has_many=array(
		"OrderCustomerGroups"=>OrderCustomerGroup::class,

	);


	private static $singular_name="Konfiguration";
	private static $plural_name="Konfiguration";
	public function getTitle(){
		return "DeliveryConfig";
		
	}
	public function getCMSFields(){
		$fields=parent::getCMSFields();
		$customerGroups=new CheckboxSetField( $name = "OrderCustomerGroups", $title = "Diese Kundegruppen nutzen das Liefermodul", OrderCustomerGroup::get()->map('ID', 'Title'));
		$fields->addFieldToTab("Root.Main",$customerGroups);
		return $fields;
		
	}
	private static $owns=[
	];
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