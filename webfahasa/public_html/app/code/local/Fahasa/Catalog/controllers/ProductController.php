<?php

class Fahasa_Catalog_ProductController extends Mage_Core_Controller_Front_Action {
    
    public function loadCatalogAction(){
	$cat_id = $this->getRequest()->getParam('category_id', 0);
	$filter = $this->getRequest()->getParam('filters', []);
	$currentPage = $this->getRequest()->getParam('currentPage', 1);
	$limit = $this->getRequest()->getParam('limit', 24);
	$order = $this->getRequest()->getParam('order', 'created_at');
	$is_series_type = false;
	if($this->getRequest()->getParam('series_type', 0)){
	    $is_series_type = true;
	}
        try{
            $product_helper = Mage::helper('fahasa_catalog/product');
            $data = $product_helper->loadCatalog($cat_id, $filter, $limit, $currentPage, $order, $is_series_type);
            if($data['status'] == 1){
		$seo = $product_helper->getSEO();
		if($seo){
		    $data['category']['title'] = $seo['title'];
		    $data['category']['description'] = $seo['description'];
		    $data['category']['keywords'] = $seo['keywords'];
		}
	    }
        }catch(Exception $e){
            $data = array();
            $data['status'] = 0;
	    $data['parent_categories'] = [];
	    $data['category'] = [];
	    $data['children_categories'] = [];
	    $data['attributes'] = [];
	    $data['product_list'] = [];
	    $data['total_products'] = 0;
            $data['message'] = "Catalog Listing failed";
	    Mage::log("loadCatalogAction msg:".$e->getMessage(), null, "buffet.log");
        }
        return $this->getResponse()->setBody(json_encode($data))
                ->setHeader('Content-Type', 'application/json');
    }
    
//    public function loadMenuAction(){
//	$cat_id = $this->getRequest()->getParam('category_id', 0);
//	$filter = $this->getRequest()->getParam('filters', []);
//        try{
//            $product_helper = Mage::helper('fahasa_catalog/product');
//            $data = $product_helper->loadMenu($cat_id, $filter);
//            
//        }catch(Exception $e){
//            $data = array();
//            $data['status'] = 0;
//	    $result['parent_categories'] = [];
//	    $result['category'] = [];
//	    $result['children_categories'] = [];
//	    $result['attributes'] = [];
//            $data['message'] = "Category Listing failed";
//	    Mage::log("loadMenuAction msg:".$e->getMessage(), null, "buffet.log");
//        }
//        return $this->getResponse()->setBody(json_encode($data))
//                ->setHeader('Content-Type', 'application/json');
//    }
    
    public function loadProductsAction(){
	$cat_id = $this->getRequest()->getParam('category_id', 0);
	$filter = $this->getRequest()->getParam('filters', []);
	$currentPage = $this->getRequest()->getParam('currentPage', 1);
	$limit = $this->getRequest()->getParam('limit', 24);
	$order = $this->getRequest()->getParam('order', 'created_at');
	$is_series_type = false;
	if($this->getRequest()->getParam('series_type', 0)){
	    $is_series_type = true;
	}
        try{
            $product_helper = Mage::helper('fahasa_catalog/product');
	    $product_helper->setOrder($order);
	    $product_helper->setLimit($limit);
            $data = $product_helper->loadProducts($cat_id, $filter, $limit, $currentPage, $order, $is_series_type);
        }catch(Exception $e){
            $data = array();
            $data['status'] = 0;
	    $data['product_list'] = [];
	    $data['total_products'] = 0;
            $data['message'] = "Product Listing failed";
	    Mage::log("loadProductsAction msg:".$e->getMessage(), null, "buffet.log");
        }
        
        return $this->getResponse()->setBody(json_encode($data))
                ->setHeader('Content-Type', 'application/json');
    }
    
    public function loadCommentAction(){
	$product_id = $this->getRequest()->getParam('product_id', 0);
	$page = $this->getRequest()->getParam('page', 1);
	$page_size = $this->getRequest()->getParam('page_size', 12);
	$sort = $this->getRequest()->getParam('sort', 'best-like');
	try{
	    $product_helper = Mage::helper('fahasa_catalog/product');
	    $data = $product_helper->getCommentList($product_id, $page, $page_size, $sort);
	} catch (Exception $ex) {
            $data = array();
            $data['success'] = false;
	    $data['comment_list'] = [];
	    $data['total_comments'] = 0;
            $data['message'] = "Comment Listing failed";
	}
	
	return $this->getResponse()->setBody(json_encode($data))
                ->setHeader('Content-Type', 'application/json');
	
    }
    public function addCommentAction(){
	$result = [];
	$result['success'] = false;
	if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-Type', 'application/json');
        }
	$product_id = $this->getRequest()->getParam('product_id', 0);
	$nickname = $this->getRequest()->getParam('nickname', '');
	$star = $this->getRequest()->getParam('star', 0);
	$comment = $this->getRequest()->getParam('comment', '');
	try{
	    $product_helper = Mage::helper('fahasa_catalog/product');
	    $reponse = $product_helper->addComment($product_id, $star, $nickname, '', $comment);
	    if($reponse){
		$result['message'] = $reponse;
	    }else{
		$result['success'] = true;
	    }
	} catch (Exception $ex) {}
	
	return $this->getResponse()->setBody(json_encode($result))
	    ->setHeader('Content-Type', 'application/json');
    }
    public function reviewCommentAction(){
	$result = [];
	$result['success'] = false;
	if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-Type', 'application/json');
        }
	$review_id = $this->getRequest()->getParam('review_id', 0);
	$type = $this->getRequest()->getParam('type', '');
	if(!$review_id || !$type){
            return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-Type', 'application/json');
	}
        $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        $review_count = $this->checkExist($review_id, $email, $type);
        if($review_count == 0){
            $model = Mage::getModel('reviewsaction/reviewsaction');
            $model->setCustomerEmail($email);
                    $model->setType($type);
                    $model->setCreatedAt(now());
                    $model->setReviewId($review_id);
                    $model->save();
	    $result['success'] = true;
        }
	return $this->getResponse()->setBody(json_encode($result))
	    ->setHeader('Content-Type', 'application/json');
    }
    public function checkExist($review_id,$email,$type) {
        $model = Mage::getModel('reviewsaction/reviewsaction')
                ->getCollection()
                ->addFilter('review_id',$review_id)
                ->addFilter('customer_email',$email)
                ->addFilter('type',$type)
                ->getSize();
        return $model;
    }
    
    public function saveExpectedAddressAction() {
	$province_id = $this->getRequest()->getParam('province_id', 0);
	$district_id = $this->getRequest()->getParam('district_id', 0);
	$ward_id = $this->getRequest()->getParam('ward_id', 0);
	$province = $this->getRequest()->getParam('province', 0);
	$district = $this->getRequest()->getParam('district', 0);
	$ward = $this->getRequest()->getParam('ward', 0);
	$sku = $this->getRequest()->getParam('sku', 0);
	
	$helper_customer = Mage::helper("fahasa_customer/data");
	$result = $helper_customer->saveExpectedAddress($province_id, $province, $district_id, $district, $ward_id, $ward, $sku);

	return $this->getResponse()->setBody(json_encode($result))
			->setHeader('Content-Type', 'application/json');
    }
    
    public function getExpectedAddressAction() {
	$sku = $this->getRequest()->getParam('sku', 0);
	
	$helper_customer = Mage::helper("fahasa_customer/data");
	$result = $helper_customer->getExpectedShippingForProduct($sku, '');

	return $this->getResponse()->setBody(json_encode($result))
			->setHeader('Content-Type', 'application/json');
    }
    
    public function isWishlistedAction() {
	$product_id = $this->getRequest()->getParam('product_id', 0);
	$result = array();
	$result['success'] = true;
	$result['is_wished'] = 0;
	
	try{
	    $product_helper = Mage::helper('fahasa_catalog/product');
	    $result['is_wished'] = $product_helper->checkProductInWishlist($product_id);
	} catch (Exception $ex) {}
	
	return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-Type', 'application/json');
    }
    
    public function getContentByBlockIdAction() {
	$blockId = $this->getRequest()->getParam('blockId', 0);
	
	$helper_customer = Mage::helper("fahasa_catalog/data");
	$result = $helper_customer->getContentByBlockId($blockId, '');

	return $this->getResponse()->setBody(json_encode($result))
			->setHeader('Content-Type', 'application/json');
    }
    
    public function addWishlistAction(){
	$result = array('success' => false);
	$product_id = $this->getRequest()->getParam('product_id', 0);
	
        $result['success'] = Mage::helper("fahasa_catalog")->addWishListItem($product_id);
	return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function removeWishlistAction(){
	$result = array('success' => false);
	$product_id = $this->getRequest()->getParam('product_id', 0);
	
        $result['success'] = Mage::helper("fahasa_catalog")->removeWishListItem($product_id);
	return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }

}
