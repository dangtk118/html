<?php

class TTS_Airpay_StandardController extends Mage_Core_Controller_Front_Action {

    public function redirectAction() {
        $session = Mage::getSingleton('checkout/session');
        $url = Mage::getModel('airpay/airpay')->getUrlAirpay($session->getLastRealOrderId());
        $this->_redirectUrl($url);
    }

    public function successAction() {
        $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }
    
    public function responseAction()
    {
        $transaction_id = filter_input(INPUT_GET, 'transaction_id');

        $model = Mage::getModel("airpay/airpay");
        $result = $model->checkStatusFromRedirect($transaction_id);

        if ($result)
        {
            $this->redirectSuccess();
        }
        else
        {
            $this->redirectFailure();
        }
    }

    public function redirectFailure() {
        Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/failure/'));
        Mage::app()->getResponse()->sendResponse();
        return;
    }
    
    public function redirectSuccess() {
        Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/success'));
        Mage::app()->getResponse()->sendResponse();
        return;
    }
}
