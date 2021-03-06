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
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Product description block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Block_Product_View_Attributes extends Mage_Core_Block_Template
{
    protected $_product = null;

    function getProduct()
    {
        if (!$this->_product) {
            $this->_product = Mage::registry('product');
        }
        return $this->_product;
    }

    /**
     * $excludeAttr is optional array of attribute codes to
     * exclude them from additional data array
     *
     * @param array $excludeAttr
     * @return array
     */
    public function getAdditionalData(array $excludeAttr = array(), $is_array = false)
    {
        $data = array();
        $product = $this->getProduct();
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {
//            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
            if (($attribute->getIsVisibleOnFront() || ($is_array && $attribute->getAttributeCode() == 'supplier_list')) && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
                $value = $attribute->getFrontend()->getValue($product, $is_array);

                if (!$product->hasData($attribute->getAttributeCode())) {
                    $value = Mage::helper('catalog')->__('N/A');
                } elseif ((string)$value == '') {
                    $value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                }

		if(!empty($value)){
		    if(is_array($value)){
			if (is_string($value['values']) && strlen($value['values'])) {
			    $is_multiple = false;
			    if(!empty($value['options'])){
				if(!empty($value['options'][0])){
				    $is_multiple = true;
				}
			    }
			    $data[$attribute->getAttributeCode()] = array(
				'label' => $attribute->getStoreLabel(),
				'value' => $value['values'],
				'code'  => $attribute->getAttributeCode(),
				'options' => $value['options'],
				'is_multiple' => $is_multiple
			    );
			}
		    }else{
			if (is_string($value) && strlen($value)) {
			    $data[$attribute->getAttributeCode()] = array(
				'label' => $attribute->getStoreLabel(),
				'value' => $value,
				'code'  => $attribute->getAttributeCode()
			    );
			}
		    }
		    
		}
                
            }
        }
        return $data;
    }
}
