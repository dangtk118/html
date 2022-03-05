<?php
/**
* BSS Commerce Co.
*
* NOTICE OF LICENSE
*
* This source file is subject to the EULA
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://bsscommerce.com/Bss-Commerce-License.txt
*
* =================================================================
*                 MAGENTO EDITION USAGE NOTICE
* =================================================================
* This package designed for Magento COMMUNITY edition
* BSS Commerce does not guarantee correct work of this extension
* on any other Magento edition except Magento COMMUNITY edition.
* BSS Commerce does not provide extension support in case of
* incorrect edition usage.
* =================================================================
*
* @category   BSS
* @package    Bss_StoreConditionRules
* @author     Extension Team
* @copyright  Copyright (c) 2015-2016 BSS Commerce Co. ( http://bsscommerce.com )
* @license    http://bsscommerce.com/Bss-Commerce-License.txt
*/
class Bss_StoreConditionRules_Model_Condition_Store extends Mage_Rule_Model_Condition_Abstract 
{
	public function loadAttributeOptions() {
		$attributes = array(
			'storeView' => Mage::helper('storeconditionrules')->__('Store View')
			);

		$this->setAttributeOption($attributes);

		return $this;
	}


	public function getAttributeElement() {
		$element = parent::getAttributeElement();
		$element->setShowAsText(true);
		return $element;
	}


	public function getInputType() {

		switch ($this->getAttribute()) {
			case 'storeView':
			return 'select';
		}
		return 'string';
	}


	public function getValueElementType() {
		return 'select';
	}

	public function getValueSelectOptions()
	{
		foreach (Mage::app()->getWebsites() as $website) {
			foreach ($website->getGroups() as $group) {
				$stores = $group->getStores();
				foreach ($stores as $store) {
					$opt[] = array('value'=>$store->getStoreId(), 'label'=> $website->getName() . ' / ' . $group->getName() . ' / '  . $store->getName());
				}
			}
		}
		return $opt;
	}


	public function validate(Varien_Object $object, $all = false, $true = false) {
		if(!Mage::helper('storeconditionrules')->isEnabled()) return true;
		
		$store_id = Mage::app()->getStore()->getStoreId();
		if ($this->validateAttribute($store_id, $all, $true)) {
			return true;
		}
		
		return false;
	}
}