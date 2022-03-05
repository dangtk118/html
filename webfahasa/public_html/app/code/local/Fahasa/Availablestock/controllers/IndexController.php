<?php

class Fahasa_Availablestock_IndexController extends Mage_Core_Controller_Front_Action {

    public function insertAction() {
        $email = $_POST['email'];
        $sku = $_POST['sku'];
        $message = '';
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $helper = Mage::helper('availablestock');
        // check email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $model = Mage::getModel('availablestock/availablestock')->loadByMultiple($email, $sku);
            // check exist
            if (count($model->getData('customer_email')) == 0) {
                $helper->insertAvailablestock($email, $sku);
            }else{
                // check notify
                $notify = $helper->checkNotify($model);
                if($notify == 1){
                    $helper->insertAvailablestock($email, $sku);
                }
            }
        } else {
            $message = $this->__('Email address is invalid.');
            
        }
        $this->getResponse()
                ->setBody(Mage::helper('core')
                ->jsonEncode(array('message' => $message)));
    }
    
}
