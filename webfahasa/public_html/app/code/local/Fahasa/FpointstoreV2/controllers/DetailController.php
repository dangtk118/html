<?php

class Fahasa_FpointstoreV2_DetailController extends Mage_Core_Controller_Front_Action {
    public function voucherAction() {
	$helper = Mage::helper("fpointstorev2/data");
	$islogin = Mage::getSingleton('customer/session')->isLoggedIn();
	$vip_info = [];
	$id= $this->getRequest()->getParam('id', 0);
	if($islogin){
	    $customer = Mage::getSingleton('customer/session')->getCustomer();
	    $fpoint = Mage::helper('tryout')->determinetryout();
	}

	$gift_info = $helper->getGiftInfo($id);
	Mage::register('current_voucher', $gift_info);
	
        $this->loadLayout();
	$breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
	$breadcrumbs->addCrumb('home', array('label'=>$this->__('Home'), 'title'=>$this->__('Go to Home Page'), 'link'=>Mage::getBaseUrl()));
	$breadcrumbs->addCrumb('Fpoint Store', array('label'=>$this->__('Fpoint Store'), 'title'=>$this->__('Fpoint Store'), 'link'=>'/fpointstore'));
	$breadcrumbs->addCrumb('Voucher', array('label'=>$this->__('Voucher'), 'title'=>$this->__('Voucher')));
        $this->renderLayout();
	Mage::helper("weblog")->FpointStorePage(($gift_info?trim($gift_info['name']):$this->getLayout()->getBlock('head')->getTitle()), 'view_voucher', $customer?$customer->getEntityId():'guest');
    }
    
    public function comboAction() {
	$helper = Mage::helper("fpointstorev2/data");
	$islogin = Mage::getSingleton('customer/session')->isLoggedIn();
	$vip_info = [];
	$id= $this->getRequest()->getParam('id', 0);
	if($islogin){
	    $customer = Mage::getSingleton('customer/session')->getCustomer();
	    $fpoint = Mage::helper('tryout')->determinetryout();
	    $vip_info = $helper->getVipInfo($customer->getEntityId(), $customer->getCompanyId());
	    Mage::register('current_vip_info', $vip_info);
	    if($vip_info){
		$combo_can_buy = $vip_info['combo_buy_limit'] - $vip_info['combo_bought_times'];
		if($combo_can_buy > 0){
		    $combo_info = $helper->getComboList($customer->getEntityId(), $vip_info['id'],$vip_info['order_times'], $id);
		}else{
		    $next_order = $helper->getVipNextOrder($vip_info['id'], $vip_info['order_times']);
		    if($next_order){
			$next_order_times = $next_order['order_times'] - $vip_info['order_times'];
			$next_combo_buy_limit = $next_order['combo_buy_limit'] - $vip_info['combo_bought_times'];
			$combo_info = $helper->getCombos($customer->getEntityId(), $vip_info['id'], $next_order['order_times'], $id, false);
		    }
		}
		
		Mage::register('current_combo_voucher', $combo_info[0]);
		if($combo_info[0]){
		    $gift_list = $helper->getComboGiftList($customer->getEntityId(), $combo_info[0]['id'], $vip_info['id']);
		    Mage::register('current_vouche_list', $gift_list);
		}
	    }
	}
        $this->loadLayout();
	$breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
	$breadcrumbs->addCrumb('home', array('label'=>$this->__('Home'), 'title'=>$this->__('Go to Home Page'), 'link'=>Mage::getBaseUrl()));
	$breadcrumbs->addCrumb('Fpoint Store', array('label'=>$this->__('Fpoint Store'), 'title'=>$this->__('Fpoint Store'), 'link'=>'/fpointstore'));
	$breadcrumbs->addCrumb('Combo Voucher', array('label'=>$this->__('Combo Voucher'), 'title'=>$this->__('Combo Voucher')));
        $this->renderLayout();
	Mage::helper("weblog")->FpointStorePage(($combo_info[0]?trim($combo_info[0]['name']):$this->getLayout()->getBlock('head')->getTitle()), 'view_combo', $customer?$customer->getEntityId():'guest');
    }
}
