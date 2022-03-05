<?php
class Fahasa_Catalog_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getDataSupplier($supplierId){
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $table = "fhs_page_keyword_url";
        $query = "select * from $table where "
                . "dataId = :supplier_id and type='supplier'";
        $binds = array(
            "supplier_id" => $supplierId
        );
        $results = $readConnection->fetchAll($query, $binds);
        $rs =  $results[0];
        return $rs;
    }
    
    public function getCategoryPath($_product) {
	$categoryIds = $_product->getCategoryIds();
	$cat_id = end($categoryIds);
        $_category = Mage::helper('fahasa_catalog/cache')->getCategory($cat_id);
        $_cateLink = Mage::getBaseUrl().$_category->getData('url_path');
        return $_cateLink;
    }
    
    public function hasAuthor($productLinkings){
	if($productLinkings){
	    foreach ($productLinkings as $links){
	    if($links['type'] == 'author'){
		    return true;
		}
	    }
	}
	return false;;
    }
    
    public function getContentByBlockId($blockId)
    {
        $content = Mage::helper('fahasa_catalog/cache')->getBlockId($blockId);
        return array(
            "success" => true,
            "content" => $content
        );
    }
    
    public function initProduct($productId, $controller ,$params){
	// Prepare data for routine
        if (!$params) {
            $params = new Varien_Object();
        }

        // Init and load product
        Mage::dispatchEvent('catalog_controller_product_init_before', array(
            'controller_action' => $controller,
            'params' => $params,
        ));

        if (!$productId) {
            return false;
        }

        $product = null;
	
	$product = $this->getInfoProductID($productId, false);
//	$product = Mage::helper('fahasa_catalog/Productredis')->getProductID($productId, false);
	
//	$producta = Mage::getModel('catalog/product')
//            ->setStoreId(Mage::app()->getStore()->getId())
//            ->load($productId);
	
        if (empty($product['entity_id'])) {
            return false;
        }

//        if (!in_array(Mage::app()->getStore()->getWebsiteId(), $product->getWebsiteIds())) {
//            return false;
//        }

        // Load product current category
//        $categoryId = $params->getCategoryId();
//        if (!$categoryId && ($categoryId !== false)) {
//            $lastId = Mage::getSingleton('catalog/session')->getLastVisitedCategoryId();
//            if ($product->canBeShowInCategory($lastId)) {
//                $categoryId = $lastId;
//            }
//        } elseif (!$product->canBeShowInCategory($categoryId)) {
//            $categoryId = null;
//        }
	

//        if ($categoryId) {
//            $category = Mage::getModel('catalog/category')->load($categoryId);
//            //$product->setCategory($category);
//            Mage::register('current_category', $category);
//        }
                
        // Register current data and dispatch final events
	Mage::register('current_product_redis',$product);
//        Mage::register('current_product', $producta);
//        Mage::register('product', $producta);

//        try {
//	    Mage::dispatchEvent('catalog_controller_product_init', array('product' => $product));
//            Mage::dispatchEvent('catalog_controller_product_init_after',
//                            array('product' => $product,
//                                'controller_action' => $controller
//                            )
//            );
//        } catch (Mage_Core_Exception $e) {
//            Mage::logException($e);
//            return false;
//        }

        return $product;
    }
    public function getDesignSettings($object)
    {
        return (new Varien_Object);
    }
    
    public function addWishListItem($product_id){
	if(empty($product_id)){return false;}
	if(!Mage::getSingleton('customer/session')->isLoggedIn()){return false;}

	$session = Mage::getSingleton('customer/session');
	$wishlist = $this->_getWishlist();

	$product = Mage::getModel('catalog/product')->load($product_id);
	if (!$product->getId() || !$product->isVisibleInCatalog()) {
	    $session->addError($this->__('Cannot specify product.'));
	    return false;
	}

	try {
	    $result = $wishlist->addNewItem($product);
	    if (is_string($result)) {
		Mage::throwException($result);
	    }
	    $wishlist->save();

	    Mage::dispatchEvent(
		'wishlist_add_product',
		array(
		    'wishlist' => $wishlist,
		    'product' => $product,
		    'item' => $result
		)
	    );

	    Mage::helper('wishlist')->calculate();
	    return true;

	}catch (Exception $e) {}

	return false;
    }
    
    public function removeWishListItem($product_id){
	$result = false;
	
	if(empty($product_id)){return $result;}
	if(!Mage::getSingleton('customer/session')->isLoggedIn()){return $result;}

	$session = Mage::getSingleton('customer/session');
	$wishlist = $this->_getWishlist();
	
	try {
	    $items = $wishlist->getItemCollection();
	    foreach ($items as $item) 
	    {
		if ($item->getProductId() == $product_id) 
		{
		    $item->delete();
		    $wishlist->save();   
		    $result = true;
		    
		    $product = Mage::getModel('catalog/product')->load($product_id);
		    if ($product->getId()) {
			Mage::dispatchEvent(
			    'wishlist_remove_product',
			    array(
				'wishlist' => $wishlist,
				'product' => $product,
			    )
			);
		    }
		    
		    Mage::helper('wishlist')->calculate();
		    return $result;
		}
	    }
	    

	}catch (Exception $e) {}

	return $result;
    }
    
    
    protected function _getWishlist()
    {
        $wishlist = Mage::registry('wishlist');
        if ($wishlist) {
            return $wishlist;
        }

        try {
            $customerId = Mage::getSingleton('customer/session')->getCustomerId();
	    
            /* @var Mage_Wishlist_Model_Wishlist $wishlist */
            $wishlist = Mage::getModel('wishlist/wishlist');
            if ($wishlistId) {
                $wishlist->load($wishlistId);
            } else {
                $wishlist->loadByCustomer($customerId, true);
            }

            if (!$wishlist->getId() || $wishlist->getCustomerId() != $customerId) {
                $wishlist = null;
                Mage::throwException(
                    Mage::helper('wishlist')->__("Requested wishlist doesn't exist")
                );
            }

            Mage::register('wishlist', $wishlist);
        }catch (Exception $e) {
            return false;
        }

        return $wishlist;
    }
    
    
    public function getInfoProductID($productId , $is_mobile = true) {
        $mess = 'WEB';
        if($is_mobile){
             $mess = 'MOBILE';
        }
        if (\Mage::getStoreConfig('flashsale_config/config_product_redis/is_active')) {
            $data = \Mage::helper('fahasa_catalog/productredis')->getProductID($productId, $is_mobile);
            if (!empty($data)) {
                \Mage::helper("weblog")->ViewProduct($data, $is_mobile);
            } else {
                \Mage::log("[ERROR-" . $mess . "] product_id= " . $productId . " has null then loadDB ", null, 'redis_product.log');
                return \Mage::helper('fahasa_catalog/productdb')->getProductID($productId, $is_mobile);
            }
            return $data;
        } else {
            \Mage::log("[ERROR-".$mess."] product_id= " . $productId . " turn off redis then loadDB ", null, 'redis_product.log');
            $data = \Mage::helper('fahasa_catalog/productdb')->getProductID($productId, $is_mobile);
            if (!empty($data)) {
               \Mage::helper("weblog")->ViewProduct($data, $is_mobile);
            }
            return $data;
        }
    }

}
?>