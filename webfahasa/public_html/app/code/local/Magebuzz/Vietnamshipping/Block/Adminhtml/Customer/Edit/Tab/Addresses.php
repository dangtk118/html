<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Customer addresses forms
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Magebuzz_Vietnamshipping_Block_Adminhtml_Customer_Edit_Tab_Addresses extends Mage_Adminhtml_Block_Customer_Edit_Tab_Addresses {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        } 
  
	public function __construct() {			
		parent::__construct();
		if (Mage::helper($this->getModuleStr())->isVietnamShippingEnabled()) {
			$this->setTemplate($this->getModuleStr() . '/customer/tab/addresses.phtml');
		}
	}

	public function initForm() {
		if (!Mage::helper($this->getModuleStr())->isVietnamShippingEnabled()) {
			return parent::initForm();
		}
		/* @var $customer Mage_Customer_Model_Customer */			
		$customer = Mage::registry('current_customer');

		$form = new Varien_Data_Form();
		$fieldset = $form->addFieldset('address_fieldset', array(
			'legend'    => Mage::helper('customer')->__("Edit Customer's Address"))
		);

		$addressModel = Mage::getModel('customer/address');
		$addressModel->setCountryId(Mage::helper('core')->getDefaultCountry($customer->getStore()));
		/** @var $addressForm Mage_Customer_Model_Form */
		$addressForm = Mage::getModel('customer/form');
		$addressForm->setFormCode('adminhtml_customer_address')
				->setEntity($addressModel)
				->initDefaultValues();

		$attributes = $addressForm->getAttributes();
		if(isset($attributes['street'])) {
			Mage::helper('adminhtml/addresses')
				->processStreetAttribute($attributes['street']);
		}
		foreach ($attributes as $attribute) {
			/* @var $attribute Mage_Eav_Model_Entity_Attribute */
			$attribute->setFrontendLabel(Mage::helper('customer')->__($attribute->getFrontend()->getLabel()));
			$attribute->unsIsVisible();
		}		
		$this->_setFieldset($attributes, $fieldset);

		$regionElement = $form->getElement('region');
		$regionElement->setRequired(true);
		if ($regionElement) {
			$regionElement->setRenderer(Mage::getModel('adminhtml/customer_renderer_region'));
		}

		$regionElement = $form->getElement('region_id');
		if ($regionElement) {
			$regionElement->setNoDisplay(true);
		}
		
		$cityElement = $form->getElement('city');
		$cityElement->setRequired(true);
		if ($cityElement) {
			$cityElement->setRenderer(Mage::getModel($this->getModuleStr() . '/adminhtml_customer_renderer_city'));
		}
		
		$wardElement = $form->getElement('ward');
		if ($wardElement) {
			$wardElement->setRequired(false);
			$wardElement->removeClass('required-entry');
		}
		
		$country = $form->getElement('country_id');
		if ($country) {
			$country->addClass('countries');
		}

		if ($this->isReadonly()) {
			foreach ($addressModel->getAttributes() as $attribute) {
				$element = $form->getElement($attribute->getAttributeCode());
				if ($element) {
					$element->setReadonly(true, true);
				}
			}
		}

		$customerStoreId = null;
		if ($customer->getId()) {
			$customerStoreId = Mage::app()->getWebsite($customer->getWebsiteId())->getDefaultStore()->getId();
		}

		$prefixElement = $form->getElement('prefix');
		if ($prefixElement) {
			$prefixOptions = $this->helper('customer')->getNamePrefixOptions($customerStoreId);
			if (!empty($prefixOptions)) {
				$fieldset->removeField($prefixElement->getId());
				$prefixField = $fieldset->addField($prefixElement->getId(),
					'select',
					$prefixElement->getData(),
					'^'
				);
				$prefixField->setValues($prefixOptions);
			}
		}

		$suffixElement = $form->getElement('suffix');
		if ($suffixElement) {
			$suffixOptions = $this->helper('customer')->getNameSuffixOptions($customerStoreId);
			if (!empty($suffixOptions)) {
				$fieldset->removeField($suffixElement->getId());
				$suffixField = $fieldset->addField($suffixElement->getId(),
					'select',
					$suffixElement->getData(),
					$form->getElement('lastname')->getId()
				);
				$suffixField->setValues($suffixOptions);
			}
		}

		$addressCollection = $customer->getAddresses();
		$this->assign('customer', $customer);
		$this->assign('addressCollection', $addressCollection);
		$form->setValues($addressModel->getData());
		$this->setForm($form);

		return $this;
	}
}
