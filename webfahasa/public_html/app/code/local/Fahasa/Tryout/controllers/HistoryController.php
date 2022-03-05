<?php

class Fahasa_Tryout_HistoryController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()):
            $this->_redirect('customer/account/login');
            return;
        endif;
        $this->loadLayout();

        $this->getLayout()->getBlock("head")->setTitle($this->__("F-point History"));
        $this->renderLayout();
    }

    public function updateSuborderDeliveryConfirmCompleteAction() {
	$suborder_id = $this->getRequest()->getParam('suborder_id', 0);
	if(!is_numeric($suborder_id)){$suborder_id = 0;}
	
	$result = Mage::helper('sales')->updateSuborderDeliveryConfirmComplete($suborder_id);
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
}
