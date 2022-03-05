<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Shippingweight_Edit extends Mage_Adminhtml_Block_Widget_Form_Container {
	public function __construct() {
		parent::__construct();						 
		$this->_objectId = 'id';
		$this->_blockGroup = 'vietnamshipping';
		$this->_controller = 'adminhtml_shippingweight';
		
		$this->_updateButton('save', 'label', Mage::helper('vietnamshipping')->__('Save'));
		$this->_updateButton('delete', 'label', Mage::helper('vietnamshipping')->__('Delete'));

		$this->_addButton('saveandcontinue', array(
			'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
			'onclick'   => 'saveAndContinueEdit()',
			'class'     => 'save',
		), -100);

		$this->_formScripts[] = "
			function toggleEditor() {
				if (tinyMCE.getInstanceById('vietnamshipping_content') == null) {
					tinyMCE.execCommand('mceAddControl', false, 'vietnamshipping_content');
				} else {
					tinyMCE.execCommand('mceRemoveControl', false, 'vietnamshipping_content');
				}
			}

			function saveAndContinueEdit(){
				editForm.submit($('edit_form').action+'back/edit/');
			}
		";
	}

	public function getHeaderText() {
		if( Mage::registry('shippingweight_data') && Mage::registry('shippingweight_data')->getId() ) {
			return Mage::helper('vietnamshipping')->__("Edit Rule '%s'", $this->htmlEscape(Mage::registry('shippingweight_data')->getRuleName()));
		} else {
			return Mage::helper('vietnamshipping')->__('Add Rule');
		}
	}
}