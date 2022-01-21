<?php

/*

Join-Tabelle für die many_many Beziehung der Produkte mit der entsprechenden Kundengruppen und den jeweiligen GruppenEinstellungen (Preise/Active)

*/

namespace Schrattenholz\Delivery;

use Schrattenholz\OrderProfileFeature\OrderCustomerGroup;
use Silverstripe\ORM\DataObject;
use SilverStripe\Security\Permission;
class MinOrderValue extends DataObject{
	private static $table_name="Delivery_MinOrderValue";
	private static $db = [
		'Value' => 'Decimal(6,2)'
	];
	private static $has_one = [
		'OrderCustomerGroup' => OrderCustomerGroup::class,
		'DeliveryType' => DeliveryType::class,
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