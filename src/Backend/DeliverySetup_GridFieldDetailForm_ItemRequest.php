<?php
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
class DeliverySetup_GridFieldDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest{
private static $allowed_actions = array("ItemEditForm");

function ItemEditForm() {
	$form = parent::ItemEditForm();
	$formActions = $form->Actions();

	$button = FormAction::create('generateTable');
	$button->setTitle('Tabelle berechnen');
	$button->addExtraClass('ss-ui-action-constructive');
	$formActions->push($button);
	
	$form->setActions($formActions);
	return $form;
}


function generateTable($data, $form) {

	//do things
	$form->sessionMessage('Tabelle wurde berechnet.', 'good');

	if ($this->gridField->getList()->byId($this->record->ID)) {
		return $this->edit(Controller::curr()->getRequest());
	} else {
		$noActionURL = Controller::curr()->removeAction($data['url']);
		Controller::curr()->getRequest()->addHeader('X-Pjax', 'Content');
		return Controller::curr()->redirect($noActionURL, 302);
	}
}

}
?>