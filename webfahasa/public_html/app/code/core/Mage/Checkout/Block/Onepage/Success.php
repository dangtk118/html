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
 * @package     Mage_Checkout
 * @copyright  Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * One page checkout success page
 *
 * @category   Mage
 * @package    Mage_Checkout
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Checkout_Block_Onepage_Success extends Mage_Core_Block_Template {

    /**
     * @deprecated after 1.4.0.1
     */
    protected $_order;

    /**
     * Retrieve identifier of created order
     *
     * @return string
     * @deprecated after 1.4.0.1
     */
    public function getOrderId() {
        return $this->_getData('order_id');
    }
  
    /**
     * Check order print availability
     *
     * @return bool
     * @deprecated after 1.4.0.1
     */
    public function canPrint() {
        return $this->_getData('can_view_order');
    }

    /**
     * Get url for order detale print
     *
     * @return string
     * @deprecated after 1.4.0.1
     */
    public function getPrintUrl() {
        return $this->_getData('print_url');
    }

    /**
     * Get url for view order details
     *
     * @return string
     * @deprecated after 1.4.0.1
     */
    public function getViewOrderUrl() {
        return $this->_getData('view_order_id');
    }

    /**
     * See if the order has state, visible on frontend
     *
     * @return bool
     */
    public function isOrderVisible() {
        return (bool) $this->_getData('is_order_visible');
    }

    /**
     * Getter for recurring profile view page
     *
     * @param $profile
     */
    public function getProfileUrl(Varien_Object $profile) {
        return $this->getUrl('sales/recurring_profile/view', array('profile' => $profile->getId()));
    }

    /**
     * Initialize data and prepare it for output
     */
    protected function _beforeToHtml() {
        $this->_prepareLastOrder();
        $this->_prepareLastBillingAgreement();
        $this->_prepareLastRecurringProfiles();
        return parent::_beforeToHtml();
    }

    /**
     * Get last order ID from session, fetch it and check whether it can be viewed, printed etc
     */
    protected function _prepareLastOrder() {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        if ($orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            $payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
	    $this->_order = $order;
            if ($order->getId()) {
                $isVisible = !in_array($order->getState(), Mage::getSingleton('sales/order_config')->getInvisibleOnFrontStates());

                $items = array();
                foreach ($order->getAllVisibleItems() as $item) {
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                    $image_url = $this->helper('catalog/image')->init($product, 'small_image');
                    
                    /// price uses selected currency, base price uses default currency
                    $items[] = array(
                        'product_id' => $item->getProductId(),
                        'name' => $item->getName(),
                        'product_url' => $product->getProductUrl(),
                        'sku' => $item->getSku(),
                        'price_tax' => $item->getPriceInclTax(),
                        'base_price_tax' => $item->getBasePriceInclTax(),
                        'qty_ordered' => (int)$item->getQtyOrdered(),
                        'image_url' => (string) ($image_url),
                        'soon_release' => $product->getSoonRelease(),
                        'expected_date' => $product->getExpectedDate(),
                        'book_release_date' => $product->getBookReleaseDate(),
                        'price' => $product->getPrice(),
                        'original_price' => \Mage::helper('discountlabel')->getBundlePrice($item->getProduct()) ? $item->getProduct()->getData('price') : $item->getProduct()->getPrice(),
                        'type_id' => $product->getTypeId()
                    );
                }

                $this->addData(array(
                    'is_order_visible' => $isVisible,
                    'view_order_id' => $this->getUrl('sales/order/view/', array('order_id' => $orderId)),
                    'print_url' => $this->getUrl('sales/order/print', array('order_id' => $orderId)),
                    'can_print_order' => $isVisible,
                    'can_view_order' => Mage::getSingleton('customer/session')->isLoggedIn() && $isVisible,
                    'order_id' => $order->getIncrementId(),
                    'summary' => array(
                        'items' => $items,
                        'total_due' => $order->getTotalDue(),
                        'sub_total_inc' => $order->getSubtotalInclTax(),
                        'shipping_tax_incl' => $order->getShippingInclTax(),
                        'shipping_desc' => $order->getShippingDescription(),
                        'discount_desc' => $order->getDiscountDescription(),
                        'discount_amount' => $order->getDiscountAmount(),
                        'codfee_amount' => $order->getcodfee(),
                        'giftwrap_amount' => $order->getOnestepcheckoutGiftwrapAmount(),
                        'tryout_discount' => $order->getTryoutDiscount(),
                        'is_free_ship' => $order->getIsFreeship(),
                        'freeshipDiscount' => $order->getFreeshipAmount(),
                        'grand_total' => $order->getGrandTotal(),
                        'soon_release' => $order->getSoonRelease(),
                        'expected_date' => $order->getExpectedDate(),
                        'book_release_date' => $order->getBookReleaseDate(),
                        'status' => $order->getStatus(),
                        'payment_method_code' => $payment_method_code,
                        'original_shipping_fee' => $order->getOriginalShippingFee(),
                        'freeship_amount' => $order->getFreeshipAmount(),
                    )
                ));
            }
        }
    }
    
    public function formatPrice($price, $symbol){
        return number_format($price, 0, ",", ".") . $symbol;
    }

    /**
     * Prepare billing agreement data from an identifier in the session
     */
    protected function _prepareLastBillingAgreement() {
        $agreementId = Mage::getSingleton('checkout/session')->getLastBillingAgreementId();
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        if ($agreementId && $customerId) {
            $agreement = Mage::getModel('sales/billing_agreement')->load($agreementId);
            if ($agreement->getId() && $customerId == $agreement->getCustomerId()) {
                $this->addData(array(
                    'agreement_ref_id' => $agreement->getReferenceId(),
                    'agreement_url' => $this->getUrl('sales/billing_agreement/view', array('agreement' => $agreementId)
                    ),
                ));
            }
        }
    }

    /**
     * Prepare recurring payment profiles from the session
     */
    protected function _prepareLastRecurringProfiles() {
        $profileIds = Mage::getSingleton('checkout/session')->getLastRecurringProfileIds();
        if ($profileIds && is_array($profileIds)) {
            $collection = Mage::getModel('sales/recurring_profile')->getCollection()
                    ->addFieldToFilter('profile_id', array('in' => $profileIds))
            ;
            $profiles = array();
            foreach ($collection as $profile) {
                $profiles[] = $profile;
            }
            if ($profiles) {
                $this->setRecurringProfiles($profiles);
                if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                    $this->setCanViewProfiles(true);
                }
            }
        }
    }

}
