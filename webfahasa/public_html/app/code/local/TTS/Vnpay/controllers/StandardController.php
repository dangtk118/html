<?php

class TTS_Vnpay_StandardController extends Mage_Core_Controller_Front_Action {

    public function redirectAction() {

        $session = Mage::getSingleton('checkout/session');
        $url = Mage::getModel('vnpay/vnpay')->getUrlVnpay($session->getLastRealOrderId());
        $this->_redirectUrl($url);
    }

    /**
     * When a customer cancel payment from paypal.
     */
    public function successAction() {
        $SECURE_SECRET = Mage::getStoreConfig('payment/vnpay/hash_code', Mage::app()->getStore());
        if (isset($_GET['vnp_TxnRef'])) {
            $order_id = $_GET['vnp_TxnRef'];
        } else {
            $message = '';
            $order_id = 0;
            \Mage::log("*** vnpay - redirect to fahasa - Data GET is not isset =====", null, "vnpay.log");
            $this->_redirect('checkout/onepage/failure', array('_secure' => true));
        }
        $vnp_SecureHash = $_GET['vnp_SecureHash'];
        $vnp_TxnResponseCode = $_GET['vnp_ResponseCode'];
        $hashSecret = $SECURE_SECRET;
        $get = $_GET;
        
        \Mage::log("*** vnpay - redirect to fahasa with Data GET: " . print_r($_GET, true), null, "vnpay.log");
        
        $data = array();
        foreach ($get as $key => $value) {
            $data[$key] = $value;
        }
        unset($data["vnp_SecureHashType"]);
        unset($data["vnp_SecureHash"]);
        ksort($data);
        $i = 0;
        $data2 = "";
        foreach ($data as $key => $value) {
            if ($i == 1) {
                $data2 .= '&' . $key . "=" . $value;
            } else {
                $data2 .= $key . "=" . $value;
                $i = 1;
            }
        }
        $secureHash = hash('sha256', $hashSecret . $data2);
//        Mage::getSingleton('checkout/session')->addSuccess(Mage::getModel('vnpay/vnpay')->transStatus($vnp_TxnResponseCode));
        if ($vnp_SecureHash == $secureHash) {
            if ($vnp_TxnResponseCode == "00") {
                \Mage::log("*** vnpay - Redirect to fahasa Success with order: ".$order_id. " and responseCode : " .$vnp_TxnResponseCode, null, "vnpay.log");
                $this->_redirect('checkout/onepage/success', array('_secure' => true));
            }else{
                \Mage::log("*** vnpay - Redirect to fahasa failed with order : ".$order_id. " and responseCode : " .$vnp_TxnResponseCode, null, "vnpay.log");
                $this->_redirect('checkout/onepage/failure', array('_secure' => true));
            }
        }else{
            \Mage::log("*** vnpay - Redirect to fahasa failed with order : ".$order_id ." secureHash NOT MATCHED ", null, "vnpay.log");
            $this->_redirect('checkout/onepage/failure', array('_secure' => true));
        }
    }
}
