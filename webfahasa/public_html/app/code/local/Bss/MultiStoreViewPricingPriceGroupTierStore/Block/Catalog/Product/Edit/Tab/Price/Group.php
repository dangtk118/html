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
class Bss_MultiStoreViewPricingPriceGroupTierStore_Block_Catalog_Product_Edit_Tab_Price_Group
    extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Price_Group_Abstract
{
    protected $_storeviews;
    /**
     * Initialize block
     */
    public function __construct()
    {
        $this->setTemplate('bss/multistoreviewpricing/catalog/product/edit/price/group.phtml');
    }

    /**
     * Sort values
     *
     * @param array $data
     * @return array
     */
    protected function _sortValues($data)
    {
        usort($data, array($this, '_sortGroupPrices'));
        return $data;
    }

    /**
     * Sort group price values callback method
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function _sortGroupPrices($a, $b)
    {
        if ($a['store_id'] != $b['store_id']) {
            return $a['store_id'] < $b['store_id'] ? -1 : 1;
        }
        if ($a['cust_group'] != $b['cust_group']) {
            return $this->getCustomerGroups($a['cust_group']) < $this->getCustomerGroups($b['cust_group']) ? -1 : 1;
        }
        return 0;
    }

    /**
     * Prepare global layout
     *
     * Add "Add Group Price" button to layout
     *
     * @return Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Price_Group
     */
    protected function _prepareLayout()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label' => Mage::helper('catalog')->__('Add Group Price'),
                'onclick' => 'return groupPriceStoreControl.addItem()',
                'class' => 'add'
            ));
        $button->setName('add_group_price_item_button');

        $this->setChild('add_button', $button);
        return parent::_prepareLayout();
    }


    public function getDefaultStoreView()
    {
        return Mage::app()->getStore($this->getProduct()->getStoreId())->getId();
    }
    

    public function getStoreViews()
    {
        if (!is_null($this->_storeviews)) {
            return $this->_storeviews;
        }

        $this->_storeviews = array(
            // 0 => array(
            //     'name' => Mage::helper('catalog')->__('All Websites'),
            //     'currency' => Mage::app()->getBaseCurrencyCode()
            // )
        );

        // if (!$this->isScopeGlobal() && $this->getProduct()->getStoreId()) {
        //     /** @var $website Mage_Core_Model_Website */
        //     $website = Mage::app()->getStore($this->getProduct()->getStoreId())->getWebsite();

        //     foreach ($website->getGroups() as $group) {
        //             $stores = $group->getStores();
        //             foreach ($stores as $store) {
        //                 $this->_storeviews[$store->getId()] = array(
        //                     'name' => $website->getName().' / '.$store->getName(),
        //                     'currency' => $store->getBaseCurrencyCode()
        //                 );
        //             }
        //         }

        // } elseif (!$this->isScopeGlobal()) {
            $websites = Mage::app()->getWebsites(false);
            $productWebsiteIds  = $this->getProduct()->getWebsiteIds();
            foreach ($websites as $website) {
                /** @var $website Mage_Core_Model_Website */
                if (!in_array($website->getId(), $productWebsiteIds)) {
                    continue;
                }

                foreach ($website->getGroups() as $group) {
                    $stores = $group->getStores();
                    foreach ($stores as $store) {
                        $this->_storeviews[$store->getId()] = array(
                            'name' => $website->getName().' / '.$store->getName(),
                            'currency' => $store->getBaseCurrencyCode()
                        );
                    }
                }
            }
        // }

        return $this->_storeviews;
    }

    /**
     *  Get is percent flag
     *
     * @return int
     */
    public function getIsPercent() {
        return $this->getData('is_percent') ? $this->getData('is_percent') : 0;
    }
}
