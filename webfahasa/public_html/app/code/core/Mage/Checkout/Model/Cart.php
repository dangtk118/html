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
 * Shopping cart model
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Checkout_Model_Cart extends Varien_Object implements Mage_Checkout_Model_Cart_Interface
{
    /**
     * Shopping cart items summary quantity(s)
     *
     * @var int|null
     */
    protected $_summaryQty;

    /**
     * List of product ids in shopping cart
     *
     * @var array|null
     */
    protected $_productIds;

    /**
     * Get shopping cart resource model
     *
     * @return Mage_Checkout_Model_Resource_Cart
     */
    protected function _getResource()
    {
        return Mage::getResourceSingleton('checkout/cart');
    }

    /**
     * Retrieve checkout session model
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Retrieve customer session model
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * List of shopping cart items
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract|array
     */
    public function getItems()
    {
        if (!$this->getQuote()->getId()) {
            return array();
        }
        return $this->getQuote()->getItemsCollection();
    }

    /**
     * Retrieve array of cart product ids
     *
     * @return array
     */
    public function getQuoteProductIds()
    {
        $products = $this->getData('product_ids');
        if (is_null($products)) {
            $products = array();
            foreach ($this->getQuote()->getAllItems() as $item) {
                $products[$item->getProductId()] = $item->getProductId();
            }
            $this->setData('product_ids', $products);
        }
        return $products;
    }

    /**
     * Get quote object associated with cart. By default it is current customer session quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if (!$this->hasData('quote')) {
            $this->setData('quote', $this->getCheckoutSession()->getQuote());
        }
        return $this->_getData('quote');
    }

    /**
     * Set quote object associated with the cart
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return Mage_Checkout_Model_Cart
     */
    public function setQuote(Mage_Sales_Model_Quote $quote)
    {
        $this->setData('quote', $quote);
        return $this;
    }

    /**
     * Initialize cart quote state to be able use it on cart page
     *
     * @return Mage_Checkout_Model_Cart
     */
    public function init()
    {
        $quote = $this->getQuote()->setCheckoutMethod('');

        if ($this->getCheckoutSession()->getCheckoutState() !== Mage_Checkout_Model_Session::CHECKOUT_STATE_BEGIN) {
            $quote->removeAllAddresses()->removePayment();
            $this->getCheckoutSession()->resetCheckout();
        }

        if (!$quote->hasItems()) {
            $quote->getShippingAddress()->setCollectShippingRates(false)
                ->removeAllShippingRates();
        }

        return $this;
    }

    /**
     * Convert order item to quote item
     *
     * @param Mage_Sales_Model_Order_Item $orderItem
     * @param mixed $qtyFlag if is null set product qty like in order
     * @return Mage_Checkout_Model_Cart
     */
    public function addOrderItem($orderItem, $qtyFlag=null)
    {
        /* @var $orderItem Mage_Sales_Model_Order_Item */
        if (is_null($orderItem->getParentItem())) {
            $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($orderItem->getProductId());
            if (!$product->getId()) {
                return $this;
            }

            $info = $orderItem->getProductOptionByCode('info_buyRequest');
            $info = new Varien_Object($info);
            if (is_null($qtyFlag)) {
                $info->setQty($orderItem->getQtyOrdered());
            } else {
                $info->setQty(1);
            }

            $this->addProduct($product, $info);
        }
        return $this;
    }

    /**
     * Get product object based on requested product information
     *
     * @param   mixed $productInfo
     * @return  Mage_Catalog_Model_Product
     */
    protected function _getProduct($productInfo)
    {
        $product = null;
        if ($productInfo instanceof Mage_Catalog_Model_Product) {
            $product = $productInfo;
        } elseif (is_int($productInfo) || is_string($productInfo)) {
            $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($productInfo);
        }
        $currentWebsiteId = Mage::app()->getStore()->getWebsiteId();
        if (!$product
            || !$product->getId()
            || !is_array($product->getWebsiteIds())
            || !in_array($currentWebsiteId, $product->getWebsiteIds())
        ) {
            Mage::throwException(Mage::helper('checkout')->__('The product could not be found.'));
        }
        return $product;
    }

    /**
     * Get request for product add to cart procedure
     *
     * @param   mixed $requestInfo
     * @return  Varien_Object
     */
    protected function _getProductRequest($requestInfo)
    {
        if ($requestInfo instanceof Varien_Object) {
            $request = $requestInfo;
        } elseif (is_numeric($requestInfo)) {
            $request = new Varien_Object(array('qty' => $requestInfo));
        } else {
            $request = new Varien_Object($requestInfo);
        }

        if (!$request->hasQty()) {
            $request->setQty(1);
        }

        return $request;
    }

    /**
     * Add product to shopping cart (quote)
     *
     * @param   int|Mage_Catalog_Model_Product $productInfo
     * @param   mixed $requestInfo
     * @return  Mage_Checkout_Model_Cart
     */
    public function addProduct($productInfo, $requestInfo=null)
    {
        $product = $this->_getProduct($productInfo);
        $request = $this->_getProductRequest($requestInfo);

	if($product->getTypeId() == 'series'){return $this;}
	
        $productId = $product->getId();

        if ($product->getStockItem()) {
            $minimumQty = $product->getStockItem()->getMinSaleQty();
            //If product was not found in cart and there is set minimal qty for it
            if ($minimumQty && $minimumQty > 0 && $request->getQty() < $minimumQty
                && !$this->getQuote()->hasProductId($productId)
            ){
                $request->setQty($minimumQty);
            }
        }

        if ($productId) {
            try {
                $result = $this->getQuote()->addProduct($product, $request);
            } catch (Mage_Core_Exception $e) {
                $this->getCheckoutSession()->setUseNotice(false);
                $result = $e->getMessage();
            }
            /**
             * String we can get if prepare process has error
             */
            if (is_string($result)) {
                $redirectUrl = ($product->hasOptionsValidationFail())
                    ? $product->getUrlModel()->getUrl(
                        $product,
                        array('_query' => array('startcustomization' => 1))
                    )
                    : $product->getProductUrl();
                $this->getCheckoutSession()->setRedirectUrl($redirectUrl);
                if ($this->getCheckoutSession()->getUseNotice() === null) {
                    $this->getCheckoutSession()->setUseNotice(true);
                }
                Mage::throwException($result);
            }
        } else {
            Mage::throwException(Mage::helper('checkout')->__('The product does not exist.'));
        }

        Mage::dispatchEvent('checkout_cart_product_add_after', array('quote_item' => $result, 'product' => $product));
        $this->getCheckoutSession()->setLastAddedProductId($productId);
        return $this;
    }

    /**
     * Adding products to cart by ids
     *
     * @param   array $productIds
     * @return  Mage_Checkout_Model_Cart
     */
    public function addProductsByIds($productIds)
    {
        $allAvailable = true;
        $allAdded     = true;

        if (!empty($productIds)) {
            foreach ($productIds as $productId) {
                $productId = (int) $productId;
                if (!$productId) {
                    continue;
                }
                $product = $this->_getProduct($productId);
                if ($product->getId() && $product->isVisibleInCatalog()) {
                    try {
                        $this->getQuote()->addProduct($product);
                    } catch (Exception $e){
                        $allAdded = false;
                    }
                } else {
                    $allAvailable = false;
                }
            }

            if (!$allAvailable) {
                $this->getCheckoutSession()->addError(
                    Mage::helper('checkout')->__('Some of the requested products are unavailable.')
                );
            }
            if (!$allAdded) {
                $this->getCheckoutSession()->addError(
                    Mage::helper('checkout')->__('Some of the requested products are not available in the desired quantity.')
                );
            }
        }
        return $this;
    }

    /**
     * Returns suggested quantities for items.
     * Can be used to automatically fix user entered quantities before updating cart
     * so that cart contains valid qty values
     *
     * $data is an array of ($quoteItemId => (item info array with 'qty' key), ...)
     *
     * @param   array $data
     * @return  array
     */
    public function suggestItemsQty($data)
    {
        foreach ($data as $itemId => $itemInfo) {
            if (!isset($itemInfo['qty'])) {
                continue;
            }
            $qty = (float) $itemInfo['qty'];
            if ($qty <= 0) {
                continue;
            }

            $quoteItem = $this->getQuote()->getItemById($itemId);
            if (!$quoteItem) {
                continue;
            }

            $product = $quoteItem->getProduct();
            if (!$product) {
                continue;
            }

            /* @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
            $stockItem = $product->getStockItem();
            if (!$stockItem) {
                continue;
            }

            $data[$itemId]['before_suggest_qty'] = $qty;
            $data[$itemId]['qty'] = $stockItem->suggestQty($qty);
        }

        return $data;
    }

    /**
     * Update cart items information
     *
     * @param   array $data
     * @return  Mage_Checkout_Model_Cart
     */
    public function updateItems($data)
    {
        Mage::dispatchEvent('checkout_cart_update_items_before', array('cart'=>$this, 'info'=>$data));

        /* @var $messageFactory Mage_Core_Model_Message */
        $messageFactory = Mage::getSingleton('core/message');
        $session = $this->getCheckoutSession();
        $qtyRecalculatedFlag = false;
//        $netcore_enable = Mage::getStoreConfig('netcore/general/enable');
//        if ($netcore_enable == 1){
//            $netcore = Mage::getSingleton('customer/session')->getNetcore();
//            if(!$netcore){
//                $netcore = "";
//            }
//        }
//        $suggestion_enable = Mage::getStoreConfig('suggestion/general/enable');
//        if ($suggestion_enable == 1){
//            $suggestion = Mage::getSingleton('customer/session')->getSuggestion();
//            if(!$suggestion){
//                $suggestion = "";
//            }
//        }
        
//        $enhanced_ecom_enable = Mage::getStoreConfig('enhanced_ecom/general/enable');
//        if ($enhanced_ecom_enable == 1){
//            $enhanced_ecom = Mage::getSingleton('customer/session')->getEnhancedEcom();
//            if (!$enhanced_ecom)
//            {
//                $enhanced_ecom = "";
//            }
//        }
        foreach ($data as $itemId => $itemInfo) {
            $item = $this->getQuote()->getItemById($itemId);
            if (!$item) {
                continue;
            }

            if (!empty($itemInfo['remove']) || (isset($itemInfo['qty']) && $itemInfo['qty']=='0')) {
                $this->removeItem($itemId);
//                if ($netcore_enable == 1){
//                    $netcore .= "smartech('dispatch', 'Remove From Cart',{\"items\": [".$item->getProductInCartNetcore()."]});";
//                }
//                if ($enhanced_ecom_enable == 1){
//                    $currency = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getShortName();
//                    
//                    $enhanced_ecom .= "dataLayer.push({'event': 'removeFromCart', 'ecommerce': {'remove': {'products': ["
//                            . $item->getProductForEnhancedEcom()
//                            . "]}}});";
//                }
                continue;
            }

            $qty = isset($itemInfo['qty']) ? (float) $itemInfo['qty'] : false;
            if ($qty > 0) {
                $qty_cal = (float)$itemInfo['qty'] - (float)$item->getQty();
                $item->setQty($qty);
                
                $itemInQuote = $this->getQuote()->getItemById($item->getId());

                if (!$itemInQuote && $item->getHasError()) {
                    Mage::throwException($item->getMessage());
                }

                if (isset($itemInfo['before_suggest_qty']) && ($itemInfo['before_suggest_qty'] != $qty)) {
                    $qtyRecalculatedFlag = true;
                    $message = $messageFactory->notice(Mage::helper('checkout')->__('Quantity was recalculated from %d to %d', $itemInfo['before_suggest_qty'], $qty));
                    $session->addQuoteItemMessage($item->getId(), $message);
                }
                else{
//                    if ($enhanced_ecom_enable == 1)
//                    {
//                        $final_price = doubleval($item->getPriceInclTax());
//                        if ($qty_cal > 0)
//                        {
//                            $currency = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getShortName();
//                            $enhanced_ecom .= "dataLayer.push({'event': 'addToCart', 'ecommerce': {'currencyCode': '{$currency}', 'add': {'products': ["
//                                    . $item->getProductForEnhancedEcom($qty_cal)
//                                    . "]}}});";
//                        }
//                        else if ($qty_cal < 0)
//                        {
//                            $enhanced_ecom .= "dataLayer.push({'event': 'removeFromCart', 'ecommerce': {'remove': {'products': ["
//                                    . $item->getProductForEnhancedEcom($qty_cal)
//                                    . "]}}});";
//                        }
//                    }

//                    if ($netcore_enable == 1){
//                        if($qty_cal>0){
//                            $netcore .= "smartech('dispatch', 'Add To Cart',{\"items\": [".$item->getProductInCartNetcore($qty_cal)."]});";
//                        }
//                        else if($qty_cal<0){
//                            $netcore .= "smartech('dispatch', 'Remove From Cart',{\"items\": [".$item->getProductInCartNetcore($qty_cal)."]});";
//                            break;
//                        }
//                    }
//		    if ($suggestion_enable == 1){
//                        if($qty_cal>0){
//                            $suggestion .= "Suggestion(SESSION_ID, 'Add To Cart',{\"items\": [".$item->getProductInCartNetcore($qty_cal)."]});";
//                        }
//		    }
                    
                }
            }
        }
//        if ($netcore_enable == 1){
//            Mage::getSingleton('customer/session')->setNetcore($netcore);
//        }
//	if ($suggestion_enable == 1){
//            Mage::getSingleton('customer/session')->setSuggestion($suggestion);
//	}
        
//        if ($enhanced_ecom_enable == 1){
//            Mage::getSingleton('customer/session')->setEnhancedEcom($enhanced_ecom);
//        }

        if ($qtyRecalculatedFlag) {
            $session->addNotice(
                Mage::helper('checkout')->__('Some products quantities were recalculated because of quantity increment mismatch')
            );
        }

        Mage::dispatchEvent('checkout_cart_update_items_after', array('cart'=>$this, 'info'=>$data));
        return $this;
    }

    /**
     * Remove item from cart
     *
     * @param   int $itemId
     * @return  Mage_Checkout_Model_Cart
     */
    public function removeItem($itemId)
    {
        $this->getQuote()->removeItem($itemId);
        return $this;
    }
    
    /**
     * Save cart
     *
     * @return Mage_Checkout_Model_Cart
     */
    public function save()
    {
        Mage::dispatchEvent('checkout_cart_save_before', array('cart'=>$this));

        $this->getQuote()->getBillingAddress();
        //Check if there are multi entry of shipping address for this quote. If so only keep the first one and mark the rest for delete
        //Solution provide here https://stackoverflow.com/questions/38568799/magento-1-9-1-1-checkout-subtotal-shows-double-the-price
        $shipAddr = $this->getQuote()->getAllShippingAddresses();
        if(count($shipAddr) > 1){
            for($i = 1; $i < count($shipAddr); $i++){
                $addr = $shipAddr[$i];
                $addr->isDeleted(true);
            }
        }
        $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        $this->getQuote()->collectTotals();
        $this->getQuote()->save();
        $this->getCheckoutSession()->setQuoteId($this->getQuote()->getId());
        /**
         * Cart save usually called after changes with cart items.
         */
        Mage::dispatchEvent('checkout_cart_save_after', array('cart'=>$this));
        return $this;
    }

    /**
     * Save cart (implement interface method)
     */
    public function saveQuote()
    {
        $this->save();
    }

    /**
     * Mark all quote items as deleted (empty shopping cart)
     *
     * @return Mage_Checkout_Model_Cart
     */
    public function truncate()
    {
        $this->getQuote()->removeAllItems();
        return $this;
    }

    public function getProductIds()
    {
        $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
        if (null === $this->_productIds) {
            $this->_productIds = array();
            if ($this->getSummaryQty()>0) {
               foreach ($this->getQuote()->getAllItems() as $item) {
                   $this->_productIds[] = $item->getProductId();
               }
            }
            $this->_productIds = array_unique($this->_productIds);
        }
        return $this->_productIds;
    }

    /**
     * Get shopping cart items summary (includes config settings)
     *
     * @return int|float
     */
    public function getSummaryQty()
    {
        $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();

        //If there is no quote id in session trying to load quote
        //and get new quote id. This is done for cases when quote was created
        //not by customer (from backend for example).
        if (!$quoteId && Mage::getSingleton('customer/session')->isLoggedIn()) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
        }

        if ($quoteId && $this->_summaryQty === null) {
            if (Mage::getStoreConfig('checkout/cart_link/use_qty')) {
                $this->_summaryQty = $this->getItemsQty();
            } else {
                $this->_summaryQty = $this->getItemsCount();
            }
        }
        return $this->_summaryQty;
    }

    /**
     * Get shopping cart items count
     *
     * @return int
     */
    public function getItemsCount()
    {
        return $this->getQuote()->getItemsCount()*1;
    }

    /**
     * Get shopping cart summary qty
     *
     * @return int|float
     */
    public function getItemsQty()
    {
        return $this->getQuote()->getItemsQty()*1;
    }

    /**
     * Update item in shopping cart (quote)
     * $requestInfo - either qty (int) or buyRequest in form of array or Varien_Object
     * $updatingParams - information on how to perform update, passed to Quote->updateItem() method
     *
     * @param int $itemId
     * @param int|array|Varien_Object $requestInfo
     * @param null|array|Varien_Object $updatingParams
     * @return Mage_Sales_Model_Quote_Item|string
     *
     * @see Mage_Sales_Model_Quote::updateItem()
     */
    public function updateItem($itemId, $requestInfo = null, $updatingParams = null)
    {
        try {
            $item = $this->getQuote()->getItemById($itemId);
            if (!$item) {
                Mage::throwException(Mage::helper('checkout')->__('Quote item does not exist.'));
            }
            $productId = $item->getProduct()->getId();
            $product = $this->_getProduct($productId);
            $request = $this->_getProductRequest($requestInfo);

            if ($product->getStockItem()) {
                $minimumQty = $product->getStockItem()->getMinSaleQty();
                // If product was not found in cart and there is set minimal qty for it
                if ($minimumQty && ($minimumQty > 0)
                    && ($request->getQty() < $minimumQty)
                    && !$this->getQuote()->hasProductId($productId)
                ) {
                    $request->setQty($minimumQty);
                }
            }

            $result = $this->getQuote()->updateItem($itemId, $request, $updatingParams);
        } catch (Mage_Core_Exception $e) {
            $this->getCheckoutSession()->setUseNotice(false);
            $result = $e->getMessage();
        }

        /**
         * We can get string if updating process had some errors
         */
        if (is_string($result)) {
            if ($this->getCheckoutSession()->getUseNotice() === null) {
                $this->getCheckoutSession()->setUseNotice(true);
            }
            Mage::throwException($result);
        }

        Mage::dispatchEvent('checkout_cart_product_update_after', array(
            'quote_item' => $result,
            'product' => $product
        ));
        $this->getCheckoutSession()->setLastAddedProductId($productId);
        return $result;
    }
}
