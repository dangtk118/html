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
* @package    Bss_MultiStoreViewPricing
* @author     Extension Team
* @copyright  Copyright (c) 2015-2016 BSS Commerce Co. ( http://bsscommerce.com )
* @license    http://bsscommerce.com/Bss-Commerce-License.txt
*/
abstract class Bss_MultiStoreViewPricingPriceGroupTierStore_Model_Product_Attribute_Backend_Groupprice_Abstract extends Mage_Catalog_Model_Product_Attribute_Backend_Price
{
    /**
     * Website currency codes and rates
     *
     * @var array
     */
    protected $_rates;

    /**
     * Error message when duplicates
     *
     * @abstract
     * @return string
     */
    abstract protected function _getDuplicateErrorMessage();

    /**
     * Retrieve websites currency rates and base currency codes
     *
     * @return array
     */
    protected function _getWebsiteCurrencyRates()
    {
        if (is_null($this->_rates)) {
            $this->_rates = array();
            $baseCurrency = Mage::app()->getBaseCurrencyCode();
            foreach (Mage::app()->getWebsites() as $website) {
                /* @var $website Mage_Core_Model_Website */
                if ($website->getBaseCurrencyCode() != $baseCurrency) {
                    $rate = Mage::getModel('directory/currency')
                        ->load($baseCurrency)
                        ->getRate($website->getBaseCurrencyCode());
                    if (!$rate) {
                        $rate = 1;
                    }
                    $this->_rates[$website->getId()] = array(
                        'code' => $website->getBaseCurrencyCode(),
                        'rate' => $rate
                    );
                } else {
                    $this->_rates[$website->getId()] = array(
                        'code' => $baseCurrency,
                        'rate' => 1
                    );
                }
            }
        }
        return $this->_rates;
    }

    /**
     * Get additional unique fields
     *
     * @param array $objectArray
     * @return array
     */
    protected function _getAdditionalUniqueFields($objectArray)
    {
        return array();
    }

    /**
     * Whether group price value fixed or percent of original price
     *
     * @param Mage_Catalog_Model_Product_Type_Price $priceObject
     * @return bool
     */
    protected function _isPriceFixed($priceObject)
    {
        return $priceObject->isGroupPriceFixed();
    }

    /**
     * Validate group price data
     *
     * @param Mage_Catalog_Model_Product $object
     * @throws Mage_Core_Exception
     * @return bool
     */
    public function validate($object)
    {
        $attribute = $this->getAttribute();
        $priceRows = $object->getData($attribute->getName());
        if (empty($priceRows)) {
            return true;
        }

        // validate per website
        $duplicates = array();
        foreach ($priceRows as $priceRow) {
            if (!empty($priceRow['delete'])) {
                continue;
            }
            $compare = join('-', array_merge(
                array($priceRow['store_id'], $priceRow['cust_group']),
                $this->_getAdditionalUniqueFields($priceRow)
            ));
            if (isset($duplicates[$compare])) {
                Mage::throwException($this->_getDuplicateErrorMessage());
            }
            $duplicates[$compare] = true;
        }

        // if attribute scope is website and edit in store view scope
        // add global group prices for duplicates find
        if (!$attribute->isScopeGlobal() && $object->getStoreId()) {
            $origGroupPrices = $object->getOrigData($attribute->getName());
            foreach ($origGroupPrices as $price) {
                if ($price['store_id'] == 0) {
                    $compare = join('-', array_merge(
                        array($price['store_id'], $price['cust_group']),
                        $this->_getAdditionalUniqueFields($price)
                    ));
                    $duplicates[$compare] = true;
                }
            }
        }

        // validate currency
        $baseCurrency = Mage::app()->getBaseCurrencyCode();
        $rates = $this->_getWebsiteCurrencyRates();
        foreach ($priceRows as $priceRow) {
            if (!empty($priceRow['delete'])) {
                continue;
            }
            if ($priceRow['store_id'] == 0) {
                continue;
            }

            $globalCompare = join('-', array_merge(
                array(0, $priceRow['cust_group']),
                $this->_getAdditionalUniqueFields($priceRow)
            ));
            $websiteCurrency = $rates[$priceRow['store_id']]['code'];

            if ($baseCurrency == $websiteCurrency && isset($duplicates[$globalCompare])) {
                Mage::throwException($this->_getDuplicateErrorMessage());
            }
        }

        return true;
    }

    /**
     * Prepare group prices data for website
     *
     * @param array $priceData
     * @param string $productTypeId
     * @param int $websiteId
     * @return array
     */
    public function preparePriceData(array $priceData, $productTypeId, $websiteId)
    {
        $rates  = $this->_getWebsiteCurrencyRates();
        $data   = array();
        $price  = Mage::getSingleton('catalog/product_type')->priceFactory($productTypeId);
        foreach ($priceData as $v) {
            // print_r($priceData);die;
            $key = join('-', array_merge(array($v['cust_group']), $this->_getAdditionalUniqueFields($v)));
            if ($v['website_id'] == $websiteId) {
                $data[$key] = $v;
                $data[$key]['website_price'] = $v['price'];
            } else if ($v['website_id'] == 0 && !isset($data[$key])) {
                $data[$key] = $v;
                $data[$key]['website_id'] = $websiteId;
                if ($this->_isPriceFixed($price)) {
                    $data[$key]['price'] = $v['price'] * $rates[$websiteId]['rate'];
                    $data[$key]['website_price'] = $v['price'] * $rates[$websiteId]['rate'];
                }
            }
        }

        return $data;
    }

    /**
     * Assign group prices to product data
     *
     * @param Mage_Catalog_Model_Product $object
     * @return Mage_Catalog_Model_Product_Attribute_Backend_Groupprice_Abstract
     */
    public function afterLoad($object)
    {
        $storeId   = $object->getStoreId();
        $websiteId = null;
        // if ($this->getAttribute()->isScopeGlobal()) {
        //     $websiteId = 0;
        // } else if ($storeId) {
        //     $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
        // }

        $data = $this->_getResource()->loadPriceData($object->getId(), $storeId);
    
        foreach ($data as $k => $v) {
            $data[$k]['website_price'] = $v['price'];
            $data[$k]['is_percent']    = isset($v['is_percent']) ? isset($v['is_percent']) : 0;
            if ($v['all_groups']) {
                $data[$k]['cust_group'] = Mage_Customer_Model_Group::CUST_GROUP_ALL;
            }
        }

        if (!$object->getData('_edit_mode') && $storeId) {
            $data = $this->preparePriceData($data, $object->getTypeId(), $storeId);
        }

        $object->setData($this->getAttribute()->getName(), $data);
        $object->setOrigData($this->getAttribute()->getName(), $data);

        $valueChangedKey = $this->getAttribute()->getName() . '_changed';
        $object->setOrigData($valueChangedKey, 0);
        $object->setData($valueChangedKey, 0);

        return $this;
    }

    /**
     * After Save Attribute manipulation
     *
     * @param Mage_Catalog_Model_Product $object
     * @return Mage_Catalog_Model_Product_Attribute_Backend_Groupprice_Abstract
     */
    public function afterSave($object)
    {
        $websiteId  = Mage::app()->getStore($object->getStoreId())->getWebsiteId();
        // $isGlobal   = $this->getAttribute()->isScopeGlobal() || $websiteId == 0;
        $isGlobal = true;

        $priceRows = $object->getData($this->getAttribute()->getName());
        if (empty($priceRows)) {
            // if ($isGlobal) {
            //     $this->_getResource()->deletePriceData($object->getId());
            // } else {
                $this->_getResource()->deletePriceData($object->getId(), $object->getStoreId());
            // }
            return $this;
        }

        $old = array();
        $new = array();

        // prepare original data for compare
        $origGroupPrices = $object->getOrigData($this->getAttribute()->getName());
        if (!is_array($origGroupPrices)) {
            $origGroupPrices = array();
        }
        foreach ($origGroupPrices as $data) {
            if ($data['store_id'] > 0 || ($data['store_id'] == '0' && $isGlobal)) {
                $key = join('-', array_merge(
                    array($data['store_id'], $data['cust_group']),
                    $this->_getAdditionalUniqueFields($data)
                ));
                $old[$key] = $data;
            }
        }

        // prepare data for save
        foreach ($priceRows as $data) {
            $hasEmptyData = false;
            foreach ($this->_getAdditionalUniqueFields($data) as $field) {
                if (empty($field)) {
                    $hasEmptyData = true;
                    break;
                }
            }

            if ($hasEmptyData || !isset($data['cust_group']) || !empty($data['delete'])) {
                continue;
            }
            if ($this->getAttribute()->isScopeGlobal() && $data['store_id'] > 0) {
                continue;
            }
            if (!$isGlobal && (int)$data['store_id'] == 0) {
                continue;
            }

            $key = join('-', array_merge(
                array($data['store_id'], $data['cust_group']),
                $this->_getAdditionalUniqueFields($data)
            ));

            $useForAllGroups = $data['cust_group'] == Mage_Customer_Model_Group::CUST_GROUP_ALL;
            $customerGroupId = !$useForAllGroups ? $data['cust_group'] : 0;

            $new[$key] = array_merge(array(
                'store_id'        => $data['store_id'],
                'all_groups'        => $useForAllGroups ? 1 : 0,
                'customer_group_id' => $customerGroupId,
                'value'             => $data['price'],
                'is_percent'        => isset($data['is_percent']) ? $data['is_percent'] : 0,
            ), $this->_getAdditionalUniqueFields($data));
        }

        $delete = array_diff_key($old, $new);
        $insert = array_diff_key($new, $old);
        $update = array_intersect_key($new, $old);

        $isChanged  = false;
        $productId  = $object->getId();

        if (!empty($delete)) {
            foreach ($delete as $data) {
                $this->_getResource()->deletePriceData($productId, null, $data['price_id']);
                $isChanged = true;
            }
        }

        if (!empty($insert)) {
            foreach ($insert as $data) {
                $price = new Varien_Object($data);
                $price->setEntityId($productId);
                $this->_getResource()->savePriceData($price);

                $isChanged = true;
            }
        }

        if (!empty($update)) {
            foreach ($update as $k => $v) {
                if ($old[$k]['price'] != $v['value'] || $old[$k]['is_percent'] != $v['is_percent']) {
                    $price = new Varien_Object(array(
                        'value_id'  => $old[$k]['price_id'],
                        'value'     => $v['value'],
                        'is_percent' => $v['is_percent']
                    ));
                    $this->_getResource()->savePriceData($price);

                    $isChanged = true;
                }
            }
        }

        if ($isChanged) {
            $valueChangedKey = $this->getAttribute()->getName() . '_changed';
            $object->setData($valueChangedKey, 1);
        }

        return $this;
    }
}
