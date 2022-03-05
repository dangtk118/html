<?php

class Fahasa_Repayment_IndexController extends Mage_Core_Controller_Front_Action {
    
    public function paymentAction() {
	$this->loadLayout();
	$this->renderLayout();
	$customer_id = 'guest';
	if(Mage::getSingleton('customer/session')->isLoggedIn()){
	    $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
	}
	Mage::helper("weblog")->FpointStorePage($this->getLayout()->getBlock('head')->getTitle(), 'repayment_page', $customer_id);
    }
    
    public function rePaymentOrderAction(){
	$rp = json_decode($this->getRequest()->getRawBody());
	$orderId = !empty($rp->orderId)?$rp->orderId:null;
	$paymentMethod = !empty($rp->paymentMethod)?$rp->paymentMethod:'';
	$result = Mage::helper('repayment')->rePaymentOrder($orderId, $paymentMethod, false, '');
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
	
    }
}
