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
 * Catalog product related items block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Block_Product_List_Related extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * Default MAP renderer type
     *
     * @var string
     */
    protected $_mapRenderer = 'msrp_noform';

    protected $_itemCollection;

    protected function _prepareData()
    {
        $product = Mage::registry('product');
        /* @var $product Mage_Catalog_Model_Product */

        $this->_itemCollection = $product->getRelatedProductCollection()
                ->addAttributeToSelect('*')
                //->joinTable('amazonrating/amazonrating', 'sku = sku', array('amazonRatingURL' => 'ratingURL', 'amazonStarRating' => 'cssStarRating', 'amazonScore' => 'numericScore'), null, 'left')
                ->joinTable('review/review_aggregate', 'entity_pk_value = entity_id', array('fhs_reviews_count' => 'reviews_count', 'fhs_rating_summary' => 'rating_summary'), '{{table}}.store_id=0','left')
            ->addAttributeToSelect('required_options')
            ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))    
            ->setPositionOrder()
            ->addStoreFilter();

        if (Mage::helper('catalog')->isModuleEnabled('Mage_Checkout')) {
            Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter($this->_itemCollection,
                Mage::getSingleton('checkout/session')->getQuoteId()
            );
            $this->_addProductAttributesAndPrices($this->_itemCollection);
        }
//        Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($this->_itemCollection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->_itemCollection);
        //Mobile will display less items        
        $mobile = Mage::helper('fhsmobiledetect')->isMobile();
        if($mobile){
            //On mobile only display 6 items
            $this->_itemCollection->setPageSize(15);
        }else{
            $this->_itemCollection->setPageSize(30);
        }
        $this->_itemCollection->load();
        
        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }

    protected function _beforeToHtml()
    {
        $this->_prepareData();
        return parent::_beforeToHtml();
    }

    public function getItems()
    {
        return $this->_itemCollection;
    }

    /**
     * Get tags array for saving cache
     *
     * @return array
     */
    public function getCacheTags()
    {
        return array_merge(parent::getCacheTags(), $this->getItemsTags($this->getItems()));
    }
}
