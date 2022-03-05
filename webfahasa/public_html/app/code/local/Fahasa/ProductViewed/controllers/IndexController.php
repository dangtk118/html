<?php

class Fahasa_ProductViewed_IndexController extends Mage_Core_Controller_Front_Action {
    
    public function indexAction() {
	$this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle($this->__("Product viewed"));
	$breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
	$breadcrumbs->addCrumb('home', array('label'=>$this->__('Home'), 'title'=>$this->__('Go to Home Page'), 'link'=>Mage::getBaseUrl()));
	$breadcrumbs->addCrumb('productviewed', array('label'=>$this->__('Products seen'), 'title'=>$this->__('Product viewed')));
        $this->renderLayout();
	
	if(Mage::getSingleton('customer/session')->isLoggedIn()){
	    $customer = Mage::getSingleton('customer/session')->getCustomer();
	}
	Mage::helper("weblog")->FpointStorePage($this->getLayout()->getBlock('head')->getTitle(), 'product_viewed_page', $customer?$customer->getEntityId():'guest');
    }
    
    public function getProductViewedAction(){
	$is_page = $this->getRequest()->getPost('is_page', false);
	$page = $this->getRequest()->getPost('page', 1);
	$limit = $this->getRequest()->getPost('limit', 6);
	$result = Mage::helper('productviewed')->getProductsViewed($is_page, $page, $limit);
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
	    
    public function addProductViewedAction(){
	$product_id = $this->getRequest()->getPost('product_id', 0);
	$result = Mage::helper('productviewed')->addProductViewed($product_id);
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
        
    public function getKeywordAction(){
	$search_key = $this->getRequest()->getPost('search_key', '');
	$result = Mage::helper('productviewed')->getKeywords($search_key);
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function removeSeachHistoryAction(){
	$result = Mage::helper('productviewed')->removeSearchHistory();
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
}
