<?php

class Fahasa_Fpointstore_IndexController extends Mage_Core_Controller_Front_Action {
    
    public function indexAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function changeGiftAction() {
	$result = array();
	$result['success'] = false;
	$result['message'] = '';
	$gift_id = $this->getRequest()->getPost('gift_id', false);
	$period_id = $this->getRequest()->getPost('period_id', false);
        $fpointstore_id = (int) Mage::getStoreConfig('fpointstore_config/config/active_fpointstore_id');
        $is_active = (int) Mage::getStoreConfig('fpointstore_config/config/is_active');
	$helper = Mage::helper("fpointstore/data");
	//check enable
	if($is_active != 1){
	    $result['message'] = $this->__('Error');
	    goto exit_code;
	}
	
	//check islogin
	if (!$this->_isLoggedIn()) {
	    $result['message'] = "<div>"
		    .$this->__('Voucher exchange failed')
		    ."</div><div style='font-weight:normal; margin-top: 20px;'>"
		    .$this->__('Please login before change voucher')
		    ."</div>";
	    goto exit_code;
	}
	$customer = $this->_getCustomerSession()->getCustomer();
	
	//check param\
	if(!is_numeric($gift_id) || !is_numeric($period_id)){
	    $result['message'] = "<div>"
		    .$this->__('Voucher exchange failed')
		    ."</div><div style='font-weight:normal; margin-top: 20px;'>"
		    .$this->__('Parameter incorrect')
		    ."</div>";
	    goto exit_code;
	}
	
	//check quatity
	$gift = $helper->getGiftByID($fpointstore_id, $period_id, $gift_id);
	if(!$gift){
	    $result['message'] = "<div>"
		    .$this->__('Voucher exchange failed')
		    ."</div><div style='font-weight:normal; margin-top: 20px;'>"
		    .$this->__('Voucher not exist')
		    ."</div>";
	    goto exit_code;
	}
	
	//check quatity
	if(intval($gift['quatity']) <= intval($gift['quatity_used'])){
	    $result['message'] = "<div>"
		    .$this->__('Voucher exchange failed')
		    ."</div><div style='font-weight:normal; margin-top: 20px;'>"
		    .$this->__('This Voucher is over')
		    ."</div>";
	    goto exit_code;
	}
	
	//check fpoint 
	if(Mage::helper('tryout')->determinetryout() < $gift['fpoint']){
	    $fpoint_require = $gift['fpoint'] - Mage::helper('tryout')->determinetryout();
	    $result['message'] = "<div>"
		    .$this->__('Voucher exchange failed')
		    ."</div><div style='font-weight:normal; margin-top: 20px;'>"
		    .$this->__('You need more %s F-point to exchange this voucher',number_format($fpoint_require, 0, ",", "."))
		    ."</div>";
	    goto exit_code;
	}
	
	//insert to Queue
	$gift_queue_id = $helper->insertQueue($fpointstore_id, $period_id, $gift_id, $customer);
	
	if($gift_queue_id){
	    $result['success'] = true;
	    $result['gift_queue_id'] = $gift_queue_id;
	}
	else{
	    $result['message'] = "<div>"
		    .$this->__('Voucher exchange failed')
		    ."</div><div style='font-weight:normal; margin-top: 20px;'>"
		    .$this->__('Website is busy, please change it later')
		    ."</div>";
	}
	//exit
	goto exit_code;
	
	//change gift
	$gift_code = $helper->exchangeGift($fpointstore_id, $period_id, $gift_id, $customer);
	if($gift_code){
	    $result['success'] = true;
	    $result['gift_code'] = $gift_code['code'];
	    $result['message'] = "<div>"
		    .$this->__('Change voucher successfully')
		    ."</div><div style='border: 1px solid #bfbfbf; padding: 10px 0; margin: 15px 50px 0 50px'>"
		    ."<div style='font-weight:normal;'>"
		    .$this->__('your voucher code')
		    ."</div>"
		    ."<div style='color:#F39801;margin-top: 5px;'>"
		    .$gift_code['code']
		    ."</div>"
		    ."</div>";
	}
	else{
	    $result['message'] = "<div>"
		    .$this->__('Voucher exchange failed')
		    ."</div><div style='font-weight:normal; margin-top: 20px;'>"
		    .$this->__('please come back in a few minutes')
		    ."</div>";
	}
	    
	//---------exit step--------------
	exit_code:
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function getResultQueueAction() {
	$result = array();
	$result['status'] = false;
	$result['success'] = false;
	$result['message'] = '';
	$gift_queue_id = $this->getRequest()->getPost('gift_queue_id', false);
	$helper = Mage::helper("fpointstore/data");
	$customer = $this->_getCustomerSession()->getCustomer();
	
	//get Queue
	$gift_Queue = $helper->getQueue($gift_queue_id, $customer);
	if($gift_Queue){
	    $result['status'] = true;
	    if($gift_Queue['success']){
		$result['success'] = true;
		$gift = $helper->getGiftCodeByID($gift_Queue['gift_code_id']);
		$result['success'] = $gift_Queue['success'];
		$result['gift_code'] = $gift['code'];
		$result['message'] = "<div>"
			.$this->__('Change voucher successfully')
			."</div><div style='border: 1px solid #bfbfbf; padding: 10px 0; margin: 15px 50px 0 50px'>"
			."<div style='font-weight:normal;'>"
			.$this->__('your voucher code')
			."</div>"
			."<div style='color:#F39801;margin-top: 5px;'>"
			.$gift['code']
			."</div>"
			."</div>";
	    }else{
		$result['success'] = false;
		$result['message'] = "<div>"
		    .$this->__('Voucher exchange failed')
		    ."</div><div style='font-weight:normal; margin-top: 20px;'>"
		    .$this->__($gift_Queue['message'])
		    ."</div>";
		
	    }
	    
	}
	//---------exit step--------------
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }

    protected function _isLoggedIn() {
        return $this->_getCustomerSession()->isLoggedIn();
    }
    protected function _getCustomerSession() {
        return Mage::getSingleton('customer/session');
    }
}
