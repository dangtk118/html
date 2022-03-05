<?php

class Fahasa_Bundle_Model_Product_Type extends Mage_Bundle_Model_Product_Type {

    public function getSelectionsCollection($optionIds, $product = null) {
        $keyOptionIds = (is_array($optionIds) ? implode('_', $optionIds) : '');
        $key = $this->_keySelectionsCollection . $keyOptionIds;
        if (!$this->getProduct($product)->hasData($key)) {
            $storeId = $this->getProduct($product)->getStoreId();
            $selectionsCollection = Mage::getResourceModel('bundle/selection_collection')
                    ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
                    ->addAttributeToSelect('tax_class_id')
                    ->addAttributeToSelect('visibility')
                    ->setFlag('require_stock_items', true)
                    ->setFlag('product_children', true)
                    ->setPositionOrder()
                    ->addStoreFilter($this->getStoreFilter($product))
                    ->setStoreId($storeId)
                    ->addFilterByRequiredOptions()
                    ->setOptionIdsFilter($optionIds);

            if (!Mage::helper('catalog')->isPriceGlobal() && $storeId) {
                $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
                $selectionsCollection->joinPrices($websiteId);
            }

            $this->getProduct($product)->setData($key, $selectionsCollection);
        }
        return $this->getProduct($product)->getData($key);
    }

}
