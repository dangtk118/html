<?php

class Fahasa_Rediscart_Helper_Stock extends Mage_Core_Helper_Abstract {
    
    protected $_checkedQuoteItems = array();
    
    //check stock for multiple items from redis_cart_items(data get from database with product_id + qty in each item object)
    public function checkStockItemForRedisCart($cart_items)
    {

        $data = array();
        $product_id_arr = array_column($cart_items, "product_id");
        $stockItems = $this->getStockBundleByProductIds($product_id_arr);
	$remove_product_ids = array();
	
        foreach ($cart_items as $key=>$item)
        {
            try {
                $stockItem = $stockItems[$item['product_id']]['stockItem'];
                if ($stockItem && $stockItem->getProduct()->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                {
                    $result = $this->checkQuoteItemQtyForRedisCartForBundle($stockItems[$item['product_id']], $item);
                    if ($result->getHasError())
                    {
                        $desired_qty = $result->getDesiredQty();
                        if ($desired_qty <= 0)
                        {
                            $desired_qty = 1;
                        }
			$cart_items[$key]['is_checked'] = $item['is_checked'] = false;
                        $item['has_error'] = true;
                        $item['message'] = $result->getMessage();
                        $item['desired_qty'] = $result->getDesiredQty();
                        $item['out_of_stock'] = $result->getOutOfStock();
                    }
                }
                else
                {
		    $cart_items[$key]['is_checked'] = $item['is_checked'] = false;
                    $item['has_error'] = true;
                    $item['message'] = "Product is not available";
                    $item['out_of_stock'] = true;
                }
		
		if($item['has_error']){
		    $remove_product_ids[] = $item['product_id'];
		    $item['is_checked'] = false;
		}

                $data[] = $item;
            } catch (Exception $ex) {
                Mage::log("Exception check stock item for redis cart " . $ex, null, "rediscart.log");
            }
        }
	if(!empty($remove_product_ids)){
	    Mage::helper('rediscart/cart')->unCheckedProductIntoQuote(implode(",",$remove_product_ids), $cart_items);
	}
        return $data;
    }

    public function getStockBundleByProductIds($product_ids)
    {
        $bundle_arr = $this->getSelectionInProduct($product_ids);

        $child_product_ids = array();
        foreach ($bundle_arr as $key => $parent)
        {
            foreach ($parent as $key => $child)
            {
                array_push($child_product_ids, $key);
            }
        }
        foreach ($product_ids as $simple)
        {
            array_push($child_product_ids, $simple);
        }
        $time_start = microtime(true);


        $stockItems = Mage::helper("rediscart/cart")->getStockItemFromProductIds($child_product_ids);



        $time_end = microtime(true);
        $time = $time_end - $time_start;

//        Mage::log("DEBUG TIME *********  --- " . $time, null, "rediscart.log");


        $stockItems = $this->splitStockItemInBundle($stockItems, $bundle_arr);
        return $stockItems;
    }

    //array of string
    public function getSelectionInProduct($product_ids)
    {
        $time_start = microtime(true);


        if (!empty($product_ids))
        {
            $product_ids_str = implode($product_ids, ",");
            $query = "select parent_product_id, product_id, selection_qty from fhs_catalog_product_bundle_selection where parent_product_id in (" . $product_ids_str . ") ";

            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $rs = $read->fetchAll($query);


            $time_end = microtime(true);
            $time = $time_end - $time_start;

            Mage::log("getSelectionInProduct QUERY%%%%%%%%%%   --- " . $time, null, "rediscart.log");
            return $this->parseSelection($rs);
        }


        return array();
    }

    public function parseSelection($rs)
    {
        $data = array();
        foreach ($rs as $item)
        {
            $data[$item['parent_product_id']][$item['product_id']]['selection_qty'] = (int) $item['selection_qty'];
        }
        return $data;
    }

    public function splitStockItemInBundle($stockItems, $bundle_arr)
    {
        $result = array();

        foreach ($stockItems as $key => $bundle)
        {
            if ($bundle_arr[$key])
            {
                $result[$key]['stockItem'] = $stockItems[$key];
                $bundle = $bundle_arr[$key];
                foreach ($bundle as $key_child => $child)
                {
                    $result[$key]["selection"][$key_child]['stockItem'] = $stockItems[$key_child];
                    $result[$key]["selection"][$key_child]['selection_qty'] = $child['selection_qty'];
                }
            }
            else
            {
                $result[$key]['stockItem'] = $stockItems[$key];
            }
        }
        return $result;
    }

    public function checkStockItemForProductId($product_id, $qty, $is_in_cart = false)
    {
        $result = new Varien_Object();
        $result->setHasError(true);
        try {
            $product_ids = array(
                $product_id
            );

            $child_product_ids = array();
            $bundle_arr = $this->getSelectionInProduct(array($product_id));
            foreach ($bundle_arr as $key => $parent)
            {
                foreach ($parent as $key => $child)
                {
                    array_push($child_product_ids, $key);
                }
            }
            foreach ($product_ids as $simple)
            {
                array_push($child_product_ids, $simple);
            }
            $stockItems = Mage::helper("rediscart/cart")->getStockItemFromProductIds($child_product_ids);
            $stockItems = $this->splitStockItemInBundle($stockItems, $bundle_arr);

            $item = array(
                "quantity" => $qty
            );
            if (!$is_in_cart)
            {
                $session = Mage::getSingleton('checkout/session');

                $cart_id = $session->getRedisCartId();

                if ($cart_id)
                {
                    $cart_item = Mage::helper('rediscart/cart')->getProductInRedisCartDbByProductIds($cart_id, $product_id);
                    if (count($cart_item) > 0)
                    {
                        $item['quantity'] = $cart_item[0]['quantity'];
                        $item['qty_to_add'] = $qty;
                    }
                }
            }

            if (count($stockItems) > 0)
            {
                try {
                    $stockItem = $stockItems[$product_id];
//                $product = $stockItems[$item['product_id']]['stockItem'];
                    if ($stockItem && $stockItem['stockItem']->getProduct()->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                    {
                        $result = $this->checkQuoteItemQtyForRedisCartForBundle($stockItem, $item);
                        $result['product'] = $stockItem['stockItem']->getProduct();
                    }
                    else
                    {
                        $result->setMessage("Product is not available");
                    }
                } catch (Exception $ex) {
                    $result->setMessage($ex);
                }
            }
            else
            {
                $result->setMessage(Mage::helper('cataloginventory')->__('The stock item for Product in option is not valid.'));
            }
        } catch (Exception $ex) {
            $result->setMessage($ex);
        }


        return $result;
    }

    public function checkStockItemForMultipleProductIds($products, $is_in_cart = false)
    {
        $product_ids = array_column($products, 'product_id');

        $child_product_ids = array();
        $bundle_arr = $this->getSelectionInProduct($product_ids);
        foreach ($bundle_arr as $key => $parent)
        {
            foreach ($parent as $key => $child)
            {
                array_push($child_product_ids, $key);
            }
        }
        foreach ($product_ids as $simple)
        {
            array_push($child_product_ids, $simple);
        }
        $stockItems = Mage::helper("rediscart/cart")->getStockItemFromProductIds($child_product_ids);
        $stockItems = $this->splitStockItemInBundle($stockItems, $bundle_arr);



        if (count($stockItems) > 0)
        {
            foreach ($products as $key => $product)
            {
                $stockItem = $stockItems[$key];
                $result = $this->checkQuoteItemQtyForRedisCartForBundle($stockItem, $product);
            }
        }

        foreach ($cart_items as $item)
        {

            $result = $this->checkQuoteItemQtyForRedisCartForBundle($stockItems[$item['product_id']], $item);
            if ($result->getHasError())
            {
                $desired_qty = $result->getDesiredQty();
                if ($desired_qty <= 0)
                {
                    $desired_qty = 1;
                }
                $item['has_error'] = true;
                $item['message'] = $result->getMessage();
                $item['desired_qty'] = $result->getDesiredQty();
                $item['out_of_stock'] = $result->getOutOfStock();
            }
            $data[] = $item;
        }
        return $data;
    }

    public function checkQuoteItemQtyForRedisCartForBundle($stockItemBundle, $cart_item)
    {
        unset($this->_checkedQuoteItems);
        
        $result = new Varien_Object();
        $result->setHasError(false);
        /**
         * Get Qty
         */
        $qty_to_add = $cart_item['qty_to_add'] ? $cart_item['qty_to_add'] : 0;
        $qty = $cart_item['quantity'] + $qty_to_add;

        /**
         * Check if product in stock. For composite products check base (parent) item stosk status
         */
        $parentStockItem = false;

        $stockItem = $stockItemBundle['stockItem'];
        if ($stockItem)
        {
            if (!$stockItem->getIsInStock() || ($parentStockItem && !$parentStockItem->getIsInStock()))
            {
                //This is to help work with product barcode scan. As we can still add to cart an out of stock product
                //as the customer is holding the product
                if (Mage::app()->getStore()->getStoreId() != Mage::getStoreConfig('book_festival/config/store_code'))
                {

                    //???TEST OKE FOR SIMPLE
                    $result->setMessage(Mage::helper('cataloginventory')->__('This product is currently out of stock.'));
                    $result->setHasError(true);
                    $result->setOutOfStock(true);
                    $result->setDesiredQty($stockItem->getMinSaleQty());
                }
                return $result;
            }
        }

        /**
         * Check item for options
         */
        //We don't need to check option of product
        $options = $stockItemBundle['selection'];
        if (count($options) > 0)
        {
            if ($stockItem)
            {
                $result = $this->checkQtyIncrements($stockItem, $qty);
                if ($result->getHasError())
                {
                    $result->setMessage($result->getMessage());
                    $result->setHasError(true);
                }
            }

            $quoteItemHasErrors = false;
            $result_bundle = null;
            foreach ($options as $key => $option)
            {
                $optionValue = $option['selection_qty'];
                /* @var $option Mage_Sales_Model_Quote_Item_Option */
                $optionQty = $qty * $optionValue;
                $increaseOptionQty = $qty_to_add * $optionValue;


                $stockItem = $option['stockItem'];

//                if ($quoteItem->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
//                {
//                    $stockItem->setProductName($quoteItem->getName());
//                }

                /* @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
                if (!$stockItem instanceof Mage_CatalogInventory_Model_Stock_Item)
                {
//                    Mage::throwException(
//                            Mage::helper('cataloginventory')->__('The stock item for Product in option is not valid.')
//                    );

                    $result->setMessage(Mage::helper('cataloginventory')->__('The stock item for Product in option is not valid.'));
                    $result->setHasError(true);
                    return $result;
                }

                /**
                 * define that stock item is child for composite product
                 */
                $stockItem->setIsChildItem(true);
                /**
                 * don't check qty increments value for option product
                 */
                $stockItem->setSuppressCheckQtyIncrements(true);

                $qtyForCheck = $this->_getQuoteItemQtyForCheck(
                        $option['stockItem']->getProduct()->getId(), $cart_item['id'], $increaseOptionQty
                );

                $result = $this->checkQuoteItemQty($stockItem, $optionQty, $qtyForCheck, $optionValue);

//                if (!is_null($result->getItemIsQtyDecimal()))
//                {
////                    $option->setIsQtyDecimal($result->getItemIsQtyDecimal());
//                }
//
//                if ($result->getHasQtyOptionUpdate())
//                {
////                    $option->setHasQtyOptionUpdate(true);
////                    $quoteItem->updateQtyOption($option, $result->getOrigQty());
////                    $option->setValue($result->getOrigQty());
//                    /**
//                     * if option's qty was updates we also need to update quote item qty
//                     */
////                    $quoteItem->setData('qty', intval($qty));
//                }
//                if (!is_null($result->getMessage()))
//                {
////                    $option->setMessage($result->getMessage());
////                    $quoteItem->setMessage($result->getMessage());
//                }
//                if (!is_null($result->getItemBackorders()))
//                {
////                    $option->setBackorders($result->getItemBackorders());
//                }

                if ($result->getHasError())
                {
//                    $option->setHasError(true);
                    $quoteItemHasErrors = true;
                    $result_bundle = $result;
//                    $quoteItem->addErrorInfo(
//                            'cataloginventory', Mage_CatalogInventory_Helper_Data::ERROR_QTY, $result->getMessage()
//                    );
//
//                    $quoteItem->getQuote()->addErrorInfo(
//                            $result->getQuoteMessageIndex(), 'cataloginventory', Mage_CatalogInventory_Helper_Data::ERROR_QTY, $result->getQuoteMessage()
//                    );
                }
                elseif (!$quoteItemHasErrors)
                {
                    // Delete error from item and its quote, if it was set due to qty lack
//                    $this->_removeErrorsFromQuoteAndItem($quoteItem, Mage_CatalogInventory_Helper_Data::ERROR_QTY);
                }

                $stockItem->unsIsChildItem();
            }
            if ($quoteItemHasErrors)
            {
                $result = $result_bundle;
            }
        }
        else
        {

            /* @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
            if (!$stockItem instanceof Mage_CatalogInventory_Model_Stock_Item)
            {
                Mage::throwException(Mage::helper('cataloginventory')->__('The stock item for Product is not valid.'));
            }

            /**
             * When we work with subitem (as subproduct of bundle or configurable product)
             */
//        if ($quoteItem->getParentItem())
//        {
//            $rowQty = $quoteItem->getParentItem()->getQty() * $qty;
//            /**
//             * we are using 0 because original qty was processed
//             */
//            $qtyForCheck = $this->_getQuoteItemQtyForCheck(
//                    $quoteItem->getProduct()->getId(), $quoteItem->getId(), 0
//            );
//        }
//        
//        
//        else
//        {
            $increaseQty = $qty_to_add ? $qty_to_add : $qty;
            $rowQty = $qty;
//            $qtyForCheck = $increaseQty;


            $qtyForCheck = $this->_getQuoteItemQtyForCheck(
                    $stockItem->getProduct()->getId(), $cart_item['id'], $increaseQty
            );

            $productTypeCustomOption = $stockItem->getProduct()->getCustomOption('product_type');
            if (!is_null($productTypeCustomOption))
            {
                // Check if product related to current item is a part of grouped product
                if ($productTypeCustomOption->getValue() == Mage_Catalog_Model_Product_Type_Grouped::TYPE_CODE)
                {
                    $stockItem->setProductName($quoteItem->getProduct()->getName());
                    $stockItem->setIsChildItem(true);
                }
            }

            $result = $this->checkQuoteItemQty($stockItem, $rowQty, $qtyForCheck, $qty);

            if ($stockItem->hasIsChildItem())
            {
                $stockItem->unsIsChildItem();
            }
        }
        
        unset($this->_checkedQuoteItems);

        return $result;
    }

    

    protected function _getQuoteItemQtyForCheck($productId, $quoteItemId, $itemQty)
    {
        $qty = $itemQty;
        if (isset($this->_checkedQuoteItems[$productId]['qty']) &&
                !in_array($quoteItemId, $this->_checkedQuoteItems[$productId]['items']))
        {
            $qty += $this->_checkedQuoteItems[$productId]['qty'];
        }

        $this->_checkedQuoteItems[$productId]['qty'] = $qty;
        $this->_checkedQuoteItems[$productId]['items'][] = $quoteItemId;

        return $qty;
    }

    //copy code from checkQuoteItemQty in core/Mage/CatalogInventory/Model/Observer.php
    //based on core function, this function process for redis cart
//    public function checkQuoteItemQtyForRedisCart($stockItem, $cart_item)
//    {
//
//
//        /**
//         * Get Qty
//         */
//        $qty = $cart_item['quantity'];
//
//        /**
//         * Check if product in stock. For composite products check base (parent) item stosk status
//         */
////        $stockItem = $quoteItem->getProduct()->getStockItem();
//
//        $parentStockItem = false;
////        if ($quoteItem->getParentItem())
////        {
////            $parentStockItem = $quoteItem->getParentItem()->getProduct()->getStockItem();
////        }
//        if ($stockItem)
//        {
//            if (!$stockItem->getIsInStock() || ($parentStockItem && !$parentStockItem->getIsInStock()))
//            {
//                //This is to help work with product barcode scan. As we can still add to cart an out of stock product
//                //as the customer is holding the product
//                if (Mage::app()->getStore()->getStoreId() != Mage::getStoreConfig('book_festival/config/store_code'))
//                {
//                    // return 2 message
//                    $quoteItem->addErrorInfo(
//                            'cataloginventory', Mage_CatalogInventory_Helper_Data::ERROR_QTY, Mage::helper('cataloginventory')->__('This product is currently out of stock.')
//                    );
//                    $quoteItem->getQuote()->addErrorInfo(
//                            'stock', 'cataloginventory', Mage_CatalogInventory_Helper_Data::ERROR_QTY, Mage::helper('cataloginventory')->__('Some of the products are currently out of stock.')
//                    );
//                }
//                return $this;
//            }
//            else
//            {
//                // Delete error from item and its quote, if it was set due to item out of stock
////                $this->_removeErrorsFromQuoteAndItem($quoteItem, Mage_CatalogInventory_Helper_Data::ERROR_QTY);
//            }
//        }
//
//        /**
//         * Check item for options
//         */
//        //We don't need to check option of product
////         $options = $quoteItem->getQtyOptions();
//
//
//
//        /* @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
//        if (!$stockItem instanceof Mage_CatalogInventory_Model_Stock_Item)
//        {
//            Mage::throwException(Mage::helper('cataloginventory')->__('The stock item for Product is not valid.'));
//        }
//
//        /**
//         * When we work with subitem (as subproduct of bundle or configurable product)
//         */
////        if ($quoteItem->getParentItem())
////        {
////            $rowQty = $quoteItem->getParentItem()->getQty() * $qty;
////            /**
////             * we are using 0 because original qty was processed
////             */
////            $qtyForCheck = $this->_getQuoteItemQtyForCheck(
////                    $quoteItem->getProduct()->getId(), $quoteItem->getId(), 0
////            );
////        }
////        
////        
////        else
////        {
//        $increaseQty = $cart_item['qty_to_add'] ? $cart_item['qty_to_add'] : $qty;
//        $rowQty = $qty;
//        $qtyForCheck = $this->_getQuoteItemQtyForCheck(
//                $stockItem->getProduct()->getId(), $cart_item['id'], $increaseQty
//        );
////        $qtyForCheck = $this->_getQuoteItemQtyForCheck(
////                $quoteItem->getProduct()->getId(), $quoteItem->getId(), $increaseQty
////        );
////        }
////        $productTypeCustomOption = $quoteItem->getProduct()->getCustomOption('product_type');
//        $productTypeCustomOption = $stockItem->getProduct()->getCustomOption('product_type');
//        if (!is_null($productTypeCustomOption))
//        {
//            // Check if product related to current item is a part of grouped product
//            if ($productTypeCustomOption->getValue() == Mage_Catalog_Model_Product_Type_Grouped::TYPE_CODE)
//            {
//                $stockItem->setProductName($quoteItem->getProduct()->getName());
//                $stockItem->setIsChildItem(true);
//            }
//        }
//
//        $result = $this->checkQuoteItemQty($stockItem, $rowQty, $qtyForCheck, $qty);
//
//        if ($stockItem->hasIsChildItem())
//        {
//            $stockItem->unsIsChildItem();
//        }
//
//        if (!is_null($result->getItemIsQtyDecimal()))
//        {
////            $quoteItem->setIsQtyDecimal($result->getItemIsQtyDecimal());
////            if ($quoteItem->getParentItem())
////            {
////                $quoteItem->getParentItem()->setIsQtyDecimal($result->getItemIsQtyDecimal());
////            }
//        }
//
//        /**
//         * Just base (parent) item qty can be changed
//         * qty of child products are declared just during add process
//         * exception for updating also managed by product type
//         */
////        if ($result->getHasQtyOptionUpdate()) {
////            $quoteItem->setData('qty', $result->getOrigQty());
////        }
////        if (!is_null($result->getItemUseOldQty()))
////        {
////            $quoteItem->setUseOldQty($result->getItemUseOldQty());
////        }
//        if (!is_null($result->getMessage()))
//        {
////            $abc = $result->getMessage();
////            $quoteItem->setMessage($result->getMessage());
////            $cart_item['message'] = $result->getMessage();
//        }
//
//        if (!is_null($result->getItemBackorders()))
//        {
////            $quoteItem->setBackorders($result->getItemBackorders());
//        }
//
//        if ($result->getHasError())
//        {
////            $quoteItem->addErrorInfo(
////                    'cataloginventory', Mage_CatalogInventory_Helper_Data::ERROR_QTY, $result->getMessage()
////            );
////
////            $quoteItem->getQuote()->addErrorInfo(
////                    $result->getQuoteMessageIndex(), 'cataloginventory', Mage_CatalogInventory_Helper_Data::ERROR_QTY, $result->getQuoteMessage()
////            );
//        }
//        else
//        {
//            // Delete error from item and its quote, if it was set due to qty lack
////            $this->_removeErrorsFromQuoteAndItem($quoteItem, Mage_CatalogInventory_Helper_Data::ERROR_QTY);
//        }
//
//        return $result;
//
////        return array(
////          "result" => $result,
////            "items" => $cart_items,
////            
////        );
//    }

    public function checkQuoteItemQty($stockItem, $qty, $summaryQty, $origQty = 0)
    {
        $result = new Varien_Object();
        $result->setHasError(false);

        if (!is_numeric($qty))
        {
            $qty = Mage::app()->getLocale()->getNumber($qty);
        }

        /**
         * Check quantity type
         */
        $result->setItemIsQtyDecimal($stockItem->getIsQtyDecimal());

        if (!$stockItem->getIsQtyDecimal())
        {
            $result->setHasQtyOptionUpdate(true);
            $qty = intval($qty);

            /**
             * Adding stock data to quote item
             */
            $result->setItemQty($qty);

            if (!is_numeric($qty))
            {
                $qty = Mage::app()->getLocale()->getNumber($qty);
            }
            $origQty = intval($origQty);
            $result->setOrigQty($origQty);
        }

        //if buy in book festival -> do not care: min_sale_qty, max_sale_qty, is_in_stock, requested qty...
        if (Mage::app()->getStore()->getStoreId() == Mage::getStoreConfig('book_festival/config/store_code'))
        {
            return $result;
        }

        if (!$this->checkQty($stockItem, $summaryQty) || !$this->checkQty($stockItem, $qty))
        {
            //???????????????WILL TO DO GET CURRENT STOCK QTY
            $message = Mage::helper('cataloginventory')->__('The requested quantity for "%s" is not available.', $qty);
            $result->setHasError(true)
                    ->setMessage($message)
                    ->setQuoteMessage($message)
                    ->setQuoteMessageIndex('qty')
                    ->setDesiredQty($stockItem->getQty() * 1);
            if (!$stockItem->getIsInStock() || $stockItem->getQty() <= 0)
            {
                $result->setOutOfStock(true);
            }
            return $result;
        }

        if ($stockItem->getMinSaleQty() && $qty < $stockItem->getMinSaleQty())
        {
            $result->setHasError(true)
                    ->setMessage(
                            Mage::helper('cataloginventory')->__('The minimum quantity allowed for purchase is %s.', $stockItem->getMinSaleQty() * 1)
                    )
                    ->setErrorCode('qty_min')
                    ->setQuoteMessage(Mage::helper('cataloginventory')->__('Some of the products cannot be ordered in requested quantity.'))
                    ->setQuoteMessageIndex('qty')
                    ->setDesiredQty($stockItem->getMinSaleQty() * 1);
            return $result;
        }

        if ($stockItem->getMaxSaleQty() && $qty > $stockItem->getMaxSaleQty())
        {
            $result->setHasError(true)
                    ->setMessage(
                            Mage::helper('cataloginventory')->__('The maximum quantity allowed for purchase is %s.', $stockItem->getMaxSaleQty() * 1)
                    )
                    ->setErrorCode('qty_max')
                    ->setQuoteMessage(Mage::helper('cataloginventory')->__('Some of the products cannot be ordered in requested quantity.'))
                    ->setQuoteMessageIndex('qty')
                    ->setDesiredQty($stockItem->getMaxSaleQty() * 1);

            return $result;
        }

        $result->addData($this->checkQtyIncrements($stockItem, $qty)->getData());
        if ($result->getHasError())
        {
            return $result;
        }

        if (!$stockItem->getManageStock())
        {
            return $result;
        }

        if (!$stockItem->getIsInStock())
        {
            //???????????????WILL TO DO GET CURRENT STOCK QTY
            $result->setHasError(true)
                    ->setMessage(Mage::helper('cataloginventory')->__('This product is currently out of stock.'))
                    ->setQuoteMessage(Mage::helper('cataloginventory')->__('Some of the products are currently out of stock.'))
                    ->setQuoteMessageIndex('stock');
            $result->setItemUseOldQty(true);
            return $result;
        }

        if (!$this->checkQty($stockItem, $summaryQty) || !$this->checkQty($stockItem, $qty))
        {
            //???????????????WILL TO DO GET CURRENT STOCK QTY
            $message = Mage::helper('cataloginventory')->__('The requested quantity for "%s" is not available.', $qty);
            $result->setHasError(true)
                    ->setMessage($message)
                    ->setQuoteMessage($message)
                    ->setQuoteMessageIndex('qty')
                    ->setDesiredQty($stockItem->getQty() * 1);
            if (!$stockItem->getIsInStock() || $stockItem->getQty() <= 0)
            {
                $result->setOutOfStock(true);
            }
            return $result;
        }
        else
        {
            if (($stockItem->getQty() - $summaryQty) < 0)
            {
                if ($stockItem->getProductName())
                {
                    if ($stockItem->getIsChildItem())
                    {
                        $backorderQty = ($stockItem->getQty() > 0) ? ($summaryQty - $stockItem->getQty()) * 1 : $qty * 1;
                        if ($backorderQty > $qty)
                        {
                            $backorderQty = $qty;
                        }

                        $result->setItemBackorders($backorderQty);
                    }
                    else
                    {
                        $orderedItems = $stockItem->getOrderedItems();
                        $itemsLeft = ($stockItem->getQty() > $orderedItems) ? ($stockItem->getQty() - $orderedItems) * 1 : 0;
                        $backorderQty = ($itemsLeft > 0) ? ($qty - $itemsLeft) * 1 : $qty * 1;

                        if ($backorderQty > 0)
                        {
                            $result->setItemBackorders($backorderQty);
                        }
                        $this->setOrderedItems($orderedItems + $qty);
                    }

                    if ($stockItem->getBackorders() == Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY)
                    {
                        if (!$stockItem->getIsChildItem())
                        {
                            $result->setMessage(
                                    Mage::helper('cataloginventory')->__('This product is not available in the requested quantity. %s of the items will be backordered.', ($backorderQty * 1))
                            );
                        }
                        else
                        {
                            $result->setMessage(
                                    Mage::helper('cataloginventory')->__('"%s" is not available in the requested quantity. %s of the items will be backordered.', $this->getProductName(), ($backorderQty * 1))
                            );
                        }
                    }
                    elseif (Mage::app()->getStore()->isAdmin())
                    {
                        $result->setMessage(
                                Mage::helper('cataloginventory')->__('The requested quantity for "%s" is not available.', $this->getProductName())
                        );
                    }
                }
            }
            else
            {
                if (!$stockItem->getIsChildItem())
                {
                    $stockItem->setOrderedItems($qty + (int) $stockItem->getOrderedItems());
                }
            }
        }

        return $result;
    }

    public function checkQty($stockItem, $qty)
    {
        if (!$stockItem->getManageStock() || Mage::app()->getStore()->isAdmin())
        {
            return true;
        }

        //use for book festival -> it can not create order in final step if cart has product which has NO_BACKORDER stock status
        if (Mage::app()->getStore()->getStoreId() == Mage::getStoreConfig('book_festival/config/store_code'))
        {
            return true;
        }

        if ($stockItem->getQty() - $stockItem->getMinQty() - $qty < 0)
        {
            switch ($stockItem->getBackorders())
            {
                case Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NONOTIFY:
                case Mage_CatalogInventory_Model_Stock::BACKORDERS_YES_NOTIFY:
                    break;
                default:
                    return false;
                    break;
            }
        }
        return true;
    }

    public function checkQtyIncrements($stockItem, $qty)
    {
        $result = new Varien_Object();
        if ($stockItem->getSuppressCheckQtyIncrements())
        {
            return $result;
        }

        $qtyIncrements = $stockItem->getQtyIncrements();
        if ($qtyIncrements && (Mage::helper('core')->getExactDivision($qty, $qtyIncrements) != 0))
        {
            //??????????????WHAT QTY CHECK
            $result->setHasError(true)
                    ->setQuoteMessage(
                            Mage::helper('cataloginventory')->__('Some of the products cannot be ordered in the requested quantity.')
                    )
                    ->setErrorCode('qty_increments')
                    ->setQuoteMessageIndex('qty');
            if ($stockItem->getIsChildItem())
            {
                $result->setMessage(
                        Mage::helper('cataloginventory')->__('%s is available for purchase in increments of %s only.', $this->getProductName(), $qtyIncrements * 1)
                );
            }
            else
            {
                $result->setMessage(
                        Mage::helper('cataloginventory')->__('This product is available for purchase in increments of %s only.', $qtyIncrements * 1)
                );
            }
        }

        return $result;
    }

}
