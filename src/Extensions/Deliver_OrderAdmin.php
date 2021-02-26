<?php

namespace Schrattenholz\Delivery;
use SilverStripe\Core\Extension;
use Schrattenholz\Order\OrderConfig;
use Schrattenholz\Order\Unit;
use Schrattenholz\Order\Ingredient;
use Schrattenholz\Order\Addon;

use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\DropdownField;
use Terraformers\RichFilterHeader\Form\GridField\RichFilterHeader;
use SilverStripe\Forms\Form;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;

class Delivery_OrderAdmin extends Extension
{


      public function updateEditForm(&$form) 
    {
		
		//$form = parent::getEditForm($id, $fields);
		 $gridField = $form->Fields()->fieldByName('Schrattenholz-OrderProfileFeature-OrderProfileFeature_ClientOrder');
		
			//Injector::inst()->get(LoggerInterface::class)->error('-----------------____-----_____ Delivery_admin before');
			if($gridField) {
				$useExtendedConfig=true;
				
				
				$config = $gridField->getConfig();
				$dataColumns=$config->getComponentByType(GridFieldDataColumns::class);
				$dataColumns->setFieldCasting([
					"Created"=>"Date->Nice",
					"ShippingDate"=>"Date->Nice"
				]);
				$config->removeComponentsByType(GridFieldFilterHeader::class);
	
				$filter = new RichFilterHeader();
				$filter->setFilterConfig([
				'Created' => [
					'title' => 'Created',
					'filter' => 'GreaterThanOrEqualFilter',
				],
				'ShippingDate' => [
					'title' => 'ShippingDate',
					'filter' => 'PartialMatchFilter',
				],
				'DeliveryType.Title'=>[
					'title'=>'DeliveryType.Title',
					'filter'=>'PartialMatchFilter'
				],
				'ClientContainer.PhoneNumber' => [
					'title'=>'ClientContainer.PhoneNumber',
					'filter'=>'PartialMatchFilter',
				],
				'ClientContainer.Surname' => [
					'title'=>'ClientContainer.Surname',
					'filter'=>'PartialMatchFilter',
				],
				'ClientContainer.Email' => [
					'title'=>'ClientContainer.Email',
					'filter'=>'PartialMatchFilter',
				],
				'OrderStatus'=>[
					'title'=>'OrderStatus',
					'filter'=>'ExactMatchFilter'
				],
				'CollectionDay.Day'=>[
					'title'=>'CollectionDay.Day',
					'filter'=>'ExactMatchFilter'
				],
				'Route.Title'=>[
					'title'=>'Route.Title',
					'filter'=>'ExactMatchFilter'
				]
			])
			->setFilterFields([
				'Created' => DateField::create('', ''),
				'ShippingDate' => DateField::create('', ''),
				'ClientContainer.FirstName' => TextField::create(""),
				'ClientContainer.Surname' => TextField::create(""),
				'DeliveryType.Title' => TextField::create(""),
				'OrderStatus' => $orderStatus = DropdownField::create(
                        '',
                        '',
                        singleton('Schrattenholz\OrderProfileFeature\OrderProfileFeature_ClientOrder')->dbObject('OrderStatus')->enumValues()
                 ),
				'CollectionDay.Day' => $collectionDay = DropdownField::create(
                        '',
                        '',
                        singleton('Schrattenholz\Delivery\CollectionDay')->dbObject('Day')->enumValues()
                ),
				'Route.Title' => $route = DropdownField::create(
                        '',
                        '',
                        Route::get()->map('Title', 'Title')
                )
			]);
			$orderStatus->setHasEmptyDefault(true);
			$collectionDay->setHasEmptyDefault(true);
			$route->setHasEmptyDefault(true);
			$config->addComponent($filter, GridFieldPaginator::class);
			}
			return $form;
    }
}
	?>