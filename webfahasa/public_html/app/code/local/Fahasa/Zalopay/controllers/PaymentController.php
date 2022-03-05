<?php

class Fahasa_Zalopay_PaymentController extends Mage_Core_Controller_Front_Action {

    public function redirectAction() {
        $this->loadLayout();
        $params = $this->getRequest()->getParams();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'zalopay', array('template' => 'zalopay/redirect.phtml'))->setData('zalopayCode', $params['zalopayCode']);
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
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

       // response action when zalopay server return
    public function responseAction() {
        $app_id = filter_input(INPUT_GET, 'appid');
        $app_trans_id = filter_input(INPUT_GET, 'apptransid');
        $pmcid = filter_input(INPUT_GET, 'pmcid');
        $bankcode = filter_input(INPUT_GET, 'bankcode');
        $amount = filter_input(INPUT_GET, 'amount');
        $discountamount = filter_input(INPUT_GET, 'discountamount');
        $status = filter_input(INPUT_GET, 'status');
        $checksum = filter_input(INPUT_GET, 'checksum');


        $model = Mage::getModel("zalopay/payment");
        $result = $model->checkStatusFromRedirect($app_id, $app_trans_id, $pmcid, $bankcode, $amount, $discountamount, $status, $checksum);
        
        if ($result == 1)
        {
            $this->redirectSuccess();
        }
        else
        {
            $this->redirectFailure();
        }
    }

}
