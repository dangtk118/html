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
class Bss_StoreConditionRules_Model_Observer
{
	public function addStoreConditionToSalesRule($observer) {
		if(!Mage::helper('storeconditionrules')->isEnabled()) return;
		
		$additional = $observer->getAdditional();
		$conditions = (array) $additional->getConditions();
		
		$conditions = array_merge_recursive($conditions, array(
			array('label'=>Mage::helper('storeconditionrules')->__('Store View'), 'value'=>'storeconditionrules/condition_store'),
		));
		
		$additional->setConditions($conditions);
		$observer->setAdditional($additional);
		
		return $observer;
	}
}