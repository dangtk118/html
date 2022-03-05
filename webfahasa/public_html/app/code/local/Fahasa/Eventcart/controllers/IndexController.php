<?php

class Fahasa_Eventcart_IndexController extends Mage_Core_Controller_Front_Action {
    
    public function checkAction() {
        $only_Invalid = $this->getRequest()->getParam('facebookKey', false);
	
	$result = Mage::getSingleton('customer/session')->getEventCart();
	if(empty($result)){
	    $result = Mage::helper('eventcart')->checkEventCart(null, $only_Invalid);
	}else{
	    Mage::getSingleton('customer/session')->unsEventCart();
	}
	
        return $this->getResponse()->setBody(json_encode($result));
	
    }
    
    public function loadEventCartProductAction(){
	$product_id = $this->getRequest()->getParam('product_id', 0);
        try{
	    $product_id = Mage::helper('fahasa_catalog/product')->cleanBug($product_id);
            $data = Mage::helper('eventcart')->getProductPromotion($product_id);
        }catch(Exception $e){
            $data = array();
            $data['success'] = 0;
        }
        
        return $this->getResponse()->setBody(json_encode($data))
                ->setHeader('Content-Type', 'application/json');
    }
}
