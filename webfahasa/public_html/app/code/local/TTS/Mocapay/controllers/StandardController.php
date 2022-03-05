<?php

class TTS_Mocapay_StandardController extends Mage_Core_Controller_Front_Action {

    public function redirectAction()
    {
        
    }

    public function redirectFailure()
    {
        Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/failure/'));
        Mage::app()->getResponse()->sendResponse();
        return;
    }

    public function redirectSuccess()
    {
        Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/success'));
        Mage::app()->getResponse()->sendResponse();
        return;
    }

    // urlReturn cua moca
    public function responseAction()
    {
        $code = filter_input(INPUT_GET, 'code');
        $state = filter_input(INPUT_GET, 'state');


        $model = Mage::getModel("mocapay/mocapay");
        $result = $model->handleRedirectFromMocaAfterPayment($code, $state);

        if ($result)
        {
            $this->redirectSuccess();
        }
        else
        {
            $this->redirectFailure();
        }
    }

}
