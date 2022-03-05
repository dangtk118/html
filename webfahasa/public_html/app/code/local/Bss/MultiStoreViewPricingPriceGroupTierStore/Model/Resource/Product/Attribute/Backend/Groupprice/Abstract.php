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
abstract class Bss_MultiStoreViewPricingPriceGroupTierStore_Model_Resource_Product_Attribute_Backend_Groupprice_Abstract
    extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Load Tier Prices for product
     *
     * @param int $productId
     * @param int $websiteId
     * @return Mage_Catalog_Model_Resource_Product_Attribute_Backend_Tierprice
     */
    public function loadPriceData($productId, $storeId = null)
    {
        $adapter = $this->_getReadAdapter();

        $columns = array(
            'price_id'      => $this->getIdFieldName(),
            'store_id'    => 'store_id',
            'all_groups'    => 'all_groups',
            'cust_group'    => 'customer_group_id',
            'price'         => 'value',
        );

        $columns = $this->_loadPriceDataColumns($columns);

        $select  = $adapter->select()
            ->from($this->getMainTable(), $columns)
            ->where('entity_id=?', $productId);

        $this->_loadPriceDataSelect($select);

        if (!is_null($storeId) && $storeId > 0) {
            $select->where('store_id = ?', $storeId);
        }

        return $adapter->fetchAll($select);
    }

    /**
     * Load specific sql columns
     *
     * @param array $columns
     * @return array
     */
    protected function _loadPriceDataColumns($columns)
    {
        return $columns;
    }

    /**
     * Load specific db-select data
     *
     * @param Varien_Db_Select $select
     * @return Varien_Db_Select
     */
    protected function _loadPriceDataSelect($select)
    {
        return $select;
    }

    /**
     * Delete Tier Prices for product
     *
     * @param int $productId
     * @param int $websiteId
     * @param int $priceId
     * @return int The number of affected rows
     */
    public function deletePriceData($productId, $storeId = null, $priceId = null)
    {
        $adapter = $this->_getWriteAdapter();

        $conds   = array(
            $adapter->quoteInto('entity_id = ?', $productId)
        );

        if (!is_null($storeId)) {
            $conds[] = $adapter->quoteInto('store_id = ?', $storeId);
        }

        if (!is_null($priceId)) {
            $conds[] = $adapter->quoteInto($this->getIdFieldName() . ' = ?', $priceId);
        }

        $where = implode(' AND ', $conds);

        return $adapter->delete($this->getMainTable(), $where);
    }

    /**
     * Save tier price object
     *
     * @param Varien_Object $priceObject
     * @return Mage_Catalog_Model_Resource_Product_Attribute_Backend_Tierprice
     */
    public function savePriceData(Varien_Object $priceObject)
    {
        $adapter = $this->_getWriteAdapter();
        $data    = $this->_prepareDataForTable($priceObject, $this->getMainTable());
        
        if (!empty($data[$this->getIdFieldName()])) {
            $where = $adapter->quoteInto($this->getIdFieldName() . ' = ?', $data[$this->getIdFieldName()]);
            unset($data[$this->getIdFieldName()]);
            $adapter->update($this->getMainTable(), $data, $where);
        } else {
            $adapter->insert($this->getMainTable(), $data);
        }
        return $this;
    }
}
