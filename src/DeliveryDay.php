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
use SilverStripe\Forms\DateField;
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
use Schrattenholz\OrderProfileFeature\OrderCustomerGroup;
//Debugging
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;
use SilverStripe\Security\Permission;
use Schrattenholz\Order\Preis;
class DeliveryDay extends DataObject
{
	private static $default_sort=['SortOrder'];
	private static $db = array (
		'Title'=>'Varchar(255)',
		'Day'=>'Enum("monday, tuesday, wednesday, thursday, friday, saturday, sunday","monday")',
		'SortOrder'=>'Int'
		
	);
	private static $has_one=[
		'Route'=>Route::class,
	];
	private static $has_many=[
		'Deadlines'=>Deadline::class
	];
	private static $belongs_many_many=[
		'PriceBlockElement'=>Preis::class,
		'DeliverySetups'=>DeliverSetup::class
	];
	public function  Title(){
		return $this->Route()->Title." am ".$this->DayTranslated();
	}
	private static $summary_fields = [
			'Day' => 'Liefertag'
    ];
 	private static $singular_name="Liefertag";
	private static $plural_name="Liefertag";
	private static $table_name="DeliveryDays";
	public function DayTranslated(){
		return _t("Day.".$this->Day,$this->Day);
	}
	public function getTitle(){
		return $this->DayTranslated();
	}
 	public function getCMSFields()
	{
		$fields=FieldList::create(TabSet::create('Root'));
		$fields->addFieldsToTab('Root.Main', [
			DropdownField::create('Day', 'Liefertag',singleton('Schrattenholz\\Delivery\\DeliveryDay')->dbObject('Day')->enumValues())
        ]);
		$this->extend('updateCMSFields', $fields);
		//Deadline pro Kundengruppe
		$gridFieldConfig=GridFieldConfig::create()
			->addComponent(new GridFieldButtonRow('before'))
			->addComponent($dataColumns=new GridFieldDataColumns)
			->addComponent($editableColumns=new GridFieldEditableColumns())
			->addComponent(new GridFieldSortableHeader())
			->addComponent(new GridFieldFilterHeader())
			->addComponent(new GridFieldPaginator())
			
		;
		$dataColumns->setDisplayFields([
			'OrderCustomerGroup.Title' => 'Kundengruppe'
		]);

		
		$editableColumns->setDisplayFields(array(

			'DaysBefore'  =>array(
					'title'=>'Bestellschluss in Tagen vor Liefertag',
					'callback'=>function($record, $column, $grid) {
						return NumericField::create($column)->setScale(0);
				}),
							'Active'  =>array(
					'title'=>'Wird angezeigt',
					'callback'=>function($record, $column, $grid) {
						return CheckboxField::create($column);
				}),
				
		));
		$fields->addFieldToTab('Root.Main', GridField::create(
			'Deadlines',
			'Bestellschluss',
			$this->Deadlines(),
			$gridFieldConfig
		));
		return $fields;
	}
	public function onAfterWrite(){
		parent::onAfterWrite();
		foreach(OrderCustomerGroup::get() as $ocg){
			if($this->Deadlines()->filter('OrderCustomerGroupID',$ocg->ID)->Count()==0){
				
				$deadline=Deadline::create();
				$deadline->OrderCustomerGroupID=$ocg->ID;
				$this->Deadlines()->add($deadline);
			}
		}
	}

}

