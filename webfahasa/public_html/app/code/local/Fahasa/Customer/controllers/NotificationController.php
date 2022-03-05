<?php

class Fahasa_Customer_NotificationController extends Mage_Core_Controller_Front_Action {
    
    const NOTIFICATION_LIST_LIMIT = 20;
    
    public function indexAction()
    {
        if (!Mage::helper('customer')->isLoggedIn()) {
            $session = Mage::getSingleton('customer/session');
            
            $url = Mage::getUrl("customer/notification");
            $session->setAfterAuthUrl($url);
            
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/account'));
        }
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Notifications'));
        
        $this->renderLayout();
    }
    
    public function listAction(){
        /// Params
        $type = $this->getRequest()->getPost('type');
        
        $helper = Mage::helper("fahasa_customer/data");
        $data = $helper->getMyNotifications($type, self::NOTIFICATION_LIST_LIMIT);
        
        return $this->getResponse()->setBody(json_encode($data))
                        ->setHeader('Content-type','application/json');
    }
    
    public function clearUnseenAction(){
        $type = $this->getRequest()->getPost('type');
        $msg_id = $this->getRequest()->getPost('msg_id');
        
        $helper = Mage::helper("fahasa_customer/data");
        $data = $helper->clearUnseen($type, $msg_id);
        
        return $this->getResponse()->setBody(json_encode($data))
                        ->setHeader('Content-type','application/json');
    }
    
    public function responseToMsgAction(){
        $msg_id = $this->getRequest()->getPost('msg_id');
        $action_result = $this->getRequest()->getPost('action_result');
        
        $helper = Mage::helper("fahasa_customer/data");
        $data = $helper->responseToActionMsg($msg_id, $action_result);
        
        return $this->getResponse()->setBody(json_encode($data))
                        ->setHeader('Content-type','application/json');
    }
}