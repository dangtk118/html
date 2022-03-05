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
class Bss_MultiStoreViewPricingCatalogRule_Model_Action_Index_Refresh_Row extends Bss_MultiStoreViewPricingCatalogRule_Model_Action_Index_Refresh
{
	/**
     * Product Id
     *
     * @var int
     */
    protected $_productId;

    /**
     * Constructor with parameters
     * Array of arguments with keys
     *  - 'connection' Varien_Db_Adapter_Interface
     *  - 'factory' Mage_Core_Model_Factory
     *  - 'resource' Mage_Core_Model_Resource_Db_Abstract
     *  - 'app' Mage_Core_Model_App
     *  - 'value' int|Mage_Catalog_Model_Product
     *
     * @param array $args
     */
    public function __construct(array $args)
    {
        parent::__construct($args);
        $this->_productId = $args['value'] instanceof Mage_Catalog_Model_Product
            ? $args['value']->getId()
            : $args['value'];
    }

    /**
     * Do not recreate rule group website for row refresh
     */
    protected function _prepareGroupWebsite($timestamp)
    {
    }

    /**
     * Prepare temporary data
     *
     * @param Mage_Core_Model_Website $website
     * @return Varien_Db_Select
     */
    protected function _prepareTemporarySelect(Mage_Core_Model_Website $website, $store = null)
    {
        $select = parent::_prepareTemporarySelect($website , $store);
        return $select->where('rp.product_id IN (?)', $this->_productId);
    }

	/**
     * Remove old index data
     *
     * @param Mage_Core_Model_Website $website
     */
    protected function _removeOldIndexData(Mage_Core_Model_Website $website, $store = null)
    {
    	if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::_removeOldIndexData($website);

        $this->_connection->query(
            $this->_connection->deleteFromSelect(
                $this->_connection->select()
                    ->from($this->_resource->getTable('multistoreviewpricingcatalogrule/rule_product_price'))
                    ->where('product_id IN (?)', $this->_productId)
                    ->where('store_id = ?', $store->getId()),
                $this->_resource->getTable('multistoreviewpricingcatalogrule/rule_product_price')
            )
        );
    }

    /**
     * Return data for affected product
     *
     * @return int
     */
    protected function _getProduct()
    {
        return $this->_productId;
    }
}
