<?php

class Fahasa_FpointstoreV2_IndexController extends Mage_Core_Controller_Front_Action {
    
    public function indexAction() {
	if(Mage::getSingleton('customer/session')->isLoggedIn()){
	    $customer = Mage::getSingleton('customer/session')->getCustomer();
	}
	$this->loadLayout();
	$breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
	$breadcrumbs->addCrumb('home', array('label'=>$this->__('Home'), 'title'=>$this->__('Go to Home Page'), 'link'=>Mage::getBaseUrl()));
	$breadcrumbs->addCrumb('Fpoint Store', array('label'=>$this->__('Fpoint Store'), 'title'=>$this->__('Fpoint Store')));
        $this->renderLayout();
	Mage::helper("weblog")->FpointStorePage($this->getLayout()->getBlock('head')->getTitle(), 'view_page', $customer?$customer->getEntityId():'guest');
    }
    
    public function getGiftListAction(){
	$result = array();
	$result['success'] = false;
	$result['over'] = false;
	$result['message'] = '';
	$category_id = $this->getRequest()->getPost('category_id', 0);
	$current_page = $this->getRequest()->getPost('currentPage', 1);
	$page_limit = (int) Mage::getStoreConfig('fpointstorev2_config/config/page_limit');
	$helper = Mage::helper("fpointstorev2/data");
	try{
	    $customer_id = 0;
	    if(Mage::getSingleton('customer/session')->isLoggedIn()){
		$customer_id = $this->_getCustomerSession()->getCustomer()->getEntityId();
	    }
	    $result['result'] = $helper->getGiftList($category_id, $current_page, $page_limit, $customer_id);
	    $result['over'] = ($page_limit > sizeof($result['result']));
	    $result['success'] = true;
	} catch (Exception $ex) {
	    $result['message'] = "can't load gift list.";
	    mage::log("getGiftList:".$ex->getMessage(), null, "fpointstore.log");
	}
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    public function getWalletVoucherListAction(){
	$result = array();
	$result['success'] = false;
	$result['over'] = false;
	$result['message'] = '';
	
	if(!Mage::getSingleton('customer/session')->isLoggedIn()){
	    return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
	}
	$customer = $this->_getCustomerSession()->getCustomer();
	$customer_id = $customer->getEntityId();
	if(!Mage::getStoreConfig('fpointstorev2_config/wallet_voucher/is_active')){
	    return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
	}
	$current_page = 1;
	$page_limit = 0;
	//$current_page = $this->getRequest()->getPost('currentPage', 1);
	$is_fhs_voucher = $this->getRequest()->getPost('is_fhs_voucher', 1);
	//$page_limit = (int) Mage::getStoreConfig('fpointstorev2_config/config/page_limit');
	$helper = Mage::helper("fpointstorev2/data");
	try{
	    $result['result'] = $helper->getVoucherHistoryList($customer_id, $is_fhs_voucher, true, true, true, $current_page, $page_limit, false);
	    $result['over'] = ($page_limit > sizeof($result['result']));
	    $result['success'] = true;
	} catch (Exception $ex) {
	    $result['message'] = "can't load gift history list.";
	    mage::log("getVoucherHistoryList:".$ex->getMessage(), null, "fpointstore.log");
	}
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function changeGiftAction() {
	$result = array();
	$result['success'] = false;
	$result['reason'] = '';
	$result['message'] = '';
	$is_combo = $this->getRequest()->getPost('is_combo', 0);
	$id = $this->getRequest()->getPost('id', 0);
        $is_active = (int) Mage::getStoreConfig('fpointstorev2_config/config/is_active');
	$helper = Mage::helper("fpointstorev2/data");
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
	if(!is_numeric($id) || $id == 0){
	    $result['message'] = "<div>"
		    .$this->__('Voucher exchange failed')
		    ."</div><div style='font-weight:normal; margin-top: 20px;'>"
		    .$this->__('Parameter incorrect')
		    ."</div>";
	    goto exit_code;
	}
	
	//check quatity
	if($is_combo){
	    $vip_info = $helper->getVipInfo($customer->getEntityId(), $customer->getCompanyId());
	    if($vip_info){
		if($vip_info['combo_bought_times'] < $vip_info['combo_buy_limit']){
		    $combo = $helper->getComboList($customer->getEntityId(), $vip_info['id'],$vip_info['order_times'], $id);
		    if($combo[0]['is_combo'] && !$combo[0]['is_over']){
			$gift = $combo[0];
		    }
		}
		else{
		    $result['message'] = "<div>"
			    .$this->__('Voucher exchange failed')
			    ."</div><div style='font-weight:normal; margin-top: 20px;'>"
			    .$this->__('Your turn to change of combo voucher was out of')
			    ."</div>";
			    $result['reason'] = 'out_of_turn';
		    goto exit_code;
		}
	    }
	}else{
	    $gift = $helper->getGiftInfo($id);
	}
	if(!$gift || !$gift['expire_date'] || (!$is_combo && !$gift['is_show'])){
	    $result['message'] = "<div>"
		    .$this->__('Voucher exchange failed')
		    ."</div><div style='font-weight:normal; margin-top: 20px;'>"
		    .$this->__('This Voucher is over')
		    ."</div>";
		    $result['reason'] = 'out_of_voucher';
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
	
	if($gift['limit'] > 0){
	    $limit = $helper->getGiftLimit($gift['id'], $customer->getEntityId());
	    if($gift['limit'] <= $limit['bought']){
		$result['message'] = "<div>"
			.$this->__('Voucher exchange failed')
			."</div><div style='font-weight:normal; margin-top: 20px;'>"
			.$this->__('Your turn redeem this voucher was over, please choose another voucher!')
			."</div>";
		goto exit_code;
	    }
	}
	
	//insert to Queue
	$gift_queue_id = $helper->insertQueue($is_combo, $id, $customer);
	
	if($gift_queue_id){
	    $result['success'] = true;
	    $result['gift_queue_id'] = $gift_queue_id;
	    Mage::helper("weblog")->FpointStorePage(trim($gift['name']), $is_combo?'change_combo':'change_voucher', $customer?$customer->getEntityId():'guest', $gift['id']);
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
	$result['reason'] = '';
	$result['message'] = '';
	$gift_queue_id = $this->getRequest()->getPost('gift_queue_id', false);
	$helper = Mage::helper("fpointstorev2/data");
	$customer = $this->_getCustomerSession()->getCustomer();
	
	//get Queue
	$gift_Queue = $helper->getQueue($gift_queue_id, $customer);
	if($gift_Queue){
	    $result['status'] = true;
	    $result['fpoint'] = Mage::helper('tryout')->determinetryout();
	    if($gift_Queue['success']){
		$result['success'] = true;
		$gifts = $helper->getGiftCodeByIds($gift_Queue['gift_code_ids']);
		$result['success'] = $gift_Queue['success'];
		$result['gift_code'] = [];
		$code_str = "";
		foreach ($gifts as $gift){
		    array_push($result['gift_code'],$gift['code']);
		    $code_str .= "<div style='padding-left: 30px;text-align: left;color:#F39801;margin-top: 5px;'>"
			."<span style='color:#3399ff;'>".($gift['partner']?$gift['partner']:'Fahasa.com').":</span> ".$gift['code']
			."</div>";
		}
		$codes_str = 
		$result['message'] = "<div>"
			.$this->__('Change voucher successfully')
			."</div><div style='border: 1px solid #bfbfbf; padding: 10px 0; margin: 15px 50px 0 50px'>"
			."<div style='font-weight:normal;'>"
			.$this->__('your voucher code')
			."</div>"
			.$code_str
			."</div>";
	    }else{
		$result['success'] = false;
		$result['message'] = "<div>"
		    .$this->__('Voucher exchange failed')
		    ."</div><div style='font-weight:normal; margin-top: 20px;'>"
		    .$this->__($gift_Queue['message'])
		    ."</div>";
		switch ($gift_Queue['message']){
		    case 'This Voucher is over':
			$result['reason'] = 'out_of_voucher';
		    break;
		    case 'Your turn to change of combo voucher was out of':
			$result['reason'] = 'out_of_turn';
		    break;
		    case "Voucher in combo isn't enough quatity":
			$result['reason'] = 'out_of_combo';
		    break;
		    case 'Not enough F-points in the account':
			$result['reason'] = 'out_of_fpoint';
		    break;
		    case 'Your turn redeem this voucher was over, please choose another voucher!':
			$result['reason'] = 'out_of_turn';
		    break;
		}
	    }
	    
	}
	//---------exit step--------------
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function setVIPAction(){
	$result = array();
	$result['status'] = false;
	$result['success'] = false;
	$result['message'] = '';
	$company_id = $this->getRequest()->getPost('company_id', false);
	if (!$this->_isLoggedIn() || !$company_id) {
	    return $result;
	}
	$helper = Mage::helper("fpointstorev2/data");
	$customer = $this->_getCustomerSession()->getCustomer();
	if(!$customer->getCompanyId()){
	    $vip_info = $helper->getVipInfo($customer->getEntityId(), $company_id, false);
	    if($vip_info['id']){
		if(!$vip_info['customer_id'] || $customer->getIsEditVip()){
		    $result['success'] = $helper->setVIP($customer->getEntityId(), $company_id);
		    if($result['success']){
			$helper->getVipInfo($customer->getEntityId(), $company_id);
		    }
		}
	    }
	}else{
	    $vip_info = $helper->getVipInfo($customer->getEntityId(), $customer->getCompanyId(), false);
	    if(!$vip_info['id']){
		$vip_info = $helper->getVipInfo($customer->getEntityId(), $company_id, false);
		if($vip_info['id']){
		    if(!$vip_info['customer_id'] || $customer->getIsEditVip()){
			$result['success'] = $helper->setVIP($customer->getEntityId(), $company_id);
		    }
		}
	    }
	}
	$result['status'] = true;
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
