<?php

class TTS_Momopay_StandardController extends Mage_Core_Controller_Front_Action {

    public function redirectAction() {
        $session = Mage::getSingleton('checkout/session');
        $getUrl = Mage::getModel('momopay/momopay')->getUrlMomopay($session->getLastRealOrderId());
        if($getUrl['payUrl']){
            $this->_redirectUrl($getUrl['payUrl']);
            return;
        }else{
             $this->_redirect('checkout/cart');
            return;
        }
    }
    public function redirectFailure() {
        Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/failure/'));
        Mage::app()->getResponse()->sendResponse();
        return;
    }
    
    public function redirectPending() {
        Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/pending/'));
        Mage::app()->getResponse()->sendResponse();
        return;
    }

    public function redirectSuccess() {
        Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('checkout/onepage/success'));
        Mage::app()->getResponse()->sendResponse();
        return;
    }

    // urlReturn cua momo
    public function responseAction() {

        $params = $this->getRequest()->getParams();
        $resultReturn = array(
            'partnerCode' => $params["partnerCode"], // 'MOMOBKUN20180529
            'accessKey' => $params["accessKey"], // klm05TvNBzhg7h7j
            'requestId' => $params["requestId"], // 101065668
            'amount' => $params["amount"], // 79000
            'orderId' => $params["orderId"], // 101065668
            'orderInfo' => $params["orderInfo"], // Tesssssssssst
            'extraData' => $params["extraData"], //  "merchantName=MoMo Partner"
            'orderType' => $params["orderType"], // momo_wallet
            'payType' => $params["payType"], // "web"
            'responseTime' => $params["responseTime"], // "2020-02-12 16:57:39"
            'errorCode' => $params["errorCode"], // "0"
            'transId' => $params["transId"], // 2310679184
            'message' => $params["message"],// Success
            'localMessage' => $params["localMessage"], //"Thành công"
            'signature' => $params["signature"] // "683c8e3f47e056c0ff946d0bff595b22ff2df1622b24ff26041889e89e23694f"  ======== MoMo signature    
            
        );
        
        $model = Mage::getModel("momopay/momopay");
        $result = $model->checkOrderStatusFromRedirectMomo($resultReturn);
        if ($result['success']) {
            if ($result['status'] == 0) {
                $this->redirectSuccess();
            } else {
                $this->redirectFailure();
            }
        }else{
            if ($result['status'] == 7) {
                $this->redirectPending();
            } else {
                $this->redirectFailure();
            }
        }
    }
}
