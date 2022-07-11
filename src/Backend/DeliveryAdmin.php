<?php

namespace Schrattenholz\Delivery;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\DropdownField;

use SilverStripe\Forms\Form;
use Terraformers\RichFilterHeader\Form\GridField\RichFilterHeader;

class DeliveryAdmin extends ModelAdmin
{

    private static $menu_title = 'Lieferung';

    private static $url_segment = 'lieferung';

    private static $managed_models = [
		DeliveryConfig::class,
		DeliverySetup::class,
		Route::class,
		CollectionDay::class,
		City::class,
		DeliveryType::class
    ];
	 private static $field_labels = [
      'Route' => 'Lieferrouten',
	  'City'=>'Orte',
	  'DeliveryType'=>'Versandarten'
   ];
      public function getEditForm($id = null, $fields = null): Form
    {
		
		$form = parent::getEditForm($id, $fields);
			 $gridFieldDeliveryCity = $form->Fields()->fieldByName('Schrattenholz-Delivery-City');
			 $gridFieldCollectionDay = $form->Fields()->fieldByName('Schrattenholz-Delivery-CollectionDay');
			  if($gridFieldCollectionDay){
				  $config = $gridFieldCollectionDay->getConfig();
				  $config->addComponent(new GridFieldOrderableRows('SortOrder'));
			  }
			if($gridFieldDeliveryCity) {				
				// Injector::inst()->get(LoggerInterface::class)->error('gridField='.var_dump($gridField));
				/*$config = $gridFieldDeliveryCity->getConfig();
				 $config->removeComponentsByType(GridFieldFilterHeader::class);
				$filter = new RichFilterHeader();
				$filter->setFilterConfig([
				'Created.Nice' => [
					'title' => 'Created',
					'filter' => 'StartsWithFilter',
				],
				'ClientContainer.FirstName' => [
				'title'=>'ClientContainer.FirstName',
				'filter'=>'PartialMatchFilter',
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
				]
			])
			->setFilterFields([
				'Created' => DateField::create('', ''),
				'ClientContainer.FirstName' => TextField::create(""),
				'ClientContainer.Surname' => TextField::create(""),
				'OrderStatus' => $team = DropdownField::create(
                        '',
                        '',
                        singleton('Schrattenholz\OrderProfileFeature\OrderProfileFeature_ClientOrder')->dbObject('OrderStatus')->enumValues()
                    ),
			]);
			$config->addComponent($filter, GridFieldPaginator::class);*/
			}
			return $form;
    }
}