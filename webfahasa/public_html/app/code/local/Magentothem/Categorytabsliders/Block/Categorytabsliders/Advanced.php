<?php

class Magentothem_Categorytabsliders_Block_Categorytabsliders_Advanced extends Fahasa_Tabslider_Block_Fhscollection {

    protected function _construct() {
        if ($this->getConfig('enabled') != 1)
            return false;
        parent::_construct();
    }

    protected function _beforeToHtml() {
        //return parent::_beforeToHtml();
    }

    public function getTitle() {
        return $this->getData('title');
    }

    public function getIdentify() {
        return $this->getData('identify');
    }

    public function getProductsCount() {
        return $this->getData('products_count');
    }

    public function getProductsOnRow() {
        return $this->getData('product_on_row');
    }

    public function getConfig($att) {
        $config = Mage::getStoreConfig('categorytabsliders');
        if (isset($config['categorytabsliders_config'])) {
            $value = $config['categorytabsliders_config'][$att];
            return $value;
        } else {
            throw new Exception($att . ' value not set');
        }
    }

//    function getProductCate($id = NULL) {
//        if (!$id)
//            return;
//        $storeId = Mage::app()->getStore()->getId();
//        //$_rootcatID = Mage::app()->getStore()->getRootCategoryId();
//        $_category = Mage::getModel('catalog/category')->load($id);
//        $product = Mage::getModel('catalog/product');
//        $json_products = array();
//        //load the category's products as a collection
//        $_productCollection = $product->getCollection()
//                ->addAttributeToSelect('*')
//                ->setStoreId($storeId)
//                ->addStoreFilter($storeId)
//                ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id=entity_id', null, 'left')
//                ->addAttributeToFilter('category_id', array('in' => $id));
//        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($_productCollection);
//        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($_productCollection);
//        $productLimits = $this->getProductsCount();
//        //Mage::log($_category->getName().'________'.$storeId.'______'.$productLimits.'__________'.$_rootcatID, null, 'fix.log');		
//        if (!$productLimits)
//            $productLimits = 10;
//        $_productCollection->setPageSize($productLimits);
//        $_productCollection->load();
//        return $_productCollection;
//    }
}
