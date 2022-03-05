<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Thang Pham
 */
class Fahasa_Cancelorder_Helper_Data extends Mage_Core_Helper_Abstract {

    function httpPost($service_url, $post_data) {
        // Params are a map from names to values
        
        $curl = curl_init($service_url);        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
//        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
        
        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            curl_close($curl);
//            die('error occured during curl exec. Additioanl info: ' . var_export($info));
            Mage::log('error occured during curl exec. Additioanl info: ' . var_export($info), null, "curl_error.log");
        }
        curl_close($curl);
        $decoded_resp = json_decode($curl_response, true);
        if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
//            die('error occured: ' . $decoded->response->errormessage);
            Mage::log('error occured: ' . $decoded->response->errormessage, null, "curl_error.log");
        }
        return $decoded_resp;                
    }
    function httpPost2($service_url, $post_data) {
        // Params are a map from names to values
        
        $curl = curl_init($service_url);        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        $curl_response = curl_exec($curl);
        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            curl_close($curl);
//            die('error occured during curl exec. Additioanl info: ' . var_export($info));
        }
        curl_close($curl);
        $decoded_resp = json_decode($curl_response, true);
        if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
//            die('error occured: ' . $decoded->response->errormessage);
        }
        return $decoded_resp;                
    }
    
    public function setStateCancelOrder($order)
    {
        $comment = 'Khach huy bang website';
        $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, $comment)->save();
        Mage::dispatchEvent('order_cancel_after', array('order' => $order, 'orgin' => 'web'));
    }
    
    /**
     * Get token in order to call any REST api
     * @return type
     */
    function getCredentialToken(){
        $resp = $this->httpPost("http://app.fahasa.com:8080/api/authenticate",
                array("userId" => "callcenter@fahasa.com",
                      "password" => "1e775fcce0387f24014121f183f6cc7b"));
        $token = $resp['data']['token']; 
        return $token;
    }
    
    /**
     * Get token in order to call any REST api
     * @return type
     */
    function getCredentialToken2(){
        $resp = $this->httpPost2("http://app.fahasa.com:8080/api/authenticate",
                array("userId" => "callcenter@fahasa.com",
                      "password" => "1e775fcce0387f24014121f183f6cc7b"));
        $token = $resp['data']['token']; 
        return $token;
    }
    
    function thirdPartyPaymentFailRest($order, $increment_id){
        Mage::log("thirdPartyPaymentFailRest fail for order id " . $increment_id . " with status " . $order->getStatus(), null, "magento.log");
        $token = $this->getCredentialToken();
        $resp = $this->httpPost("http://app.fahasa.com:8080/api/cancelOrderDueToPaymentFailure",
                array(
                    "userId" => "callcenter@fahasa.com",
                    "token" => $token,
                    "orderId" => $increment_id
                ));
        Mage::log("thirdPartyPaymentFailRest response Object for post action cancelOrderDueToPaymentFailure " . print_r($resp, true), null, "magento.log");
        $returnCode = $resp['error'];
        if($returnCode != 0){
            Mage::log("cancelOrderRestAction: Cancel error with error code $returnCode for order $increment_id", null, "magento.log");
        }else{
            Mage::log("thirdPartyPaymentFailRest success cancel order id " . $order->getIncrementId(), null, "magento.log");
        }
//	$this->setStateCancelOrder($order);
    }
    

    function thirdPartyPaymentFailRestRerturnCode($order, $increment_id){
        Mage::log("thirdPartyPaymentFailRest fail for order id " . $increment_id . " with status " . $order->getStatus(), null, "magento.log");
        $token = $this->getCredentialToken(); // dang o 8080
        $resp = $this->httpPost("http://app.fahasa.com:8080/api/cancelOrderDueToPaymentFailure",
                array(
                    "userId" => "callcenter@fahasa.com",
                    "token" => $token,
                    "orderId" => $increment_id
                ));
        // Cong Test.fahasa.com 
//        $resp = $this->httpPost("http://app.fahasa.com:8082/api/cancelOrderDueToPaymentFailure", array(
//            "userId" => "callcenter@fahasa.com",
//            "token" => $token,
//            "orderId" => $increment_id
//        ));
        Mage::log("thirdPartyPaymentFailRest response Object for post action cancelOrderDueToPaymentFailure " . print_r($resp, true), null, "magento.log");
        $returnCode = $resp['errorCode'];
        $resultData = array();
        if($returnCode === 0 || $returnCode === "0"){
            Mage::log("thirdPartyPaymentFailRest success cancel order id " . $order->getIncrementId(), null, "magento.log");
            $resultData['success'] = TRUE;
            $resultData['resp'] = $resp;
            return $resultData;
        }else{
            Mage::log("cancelOrderRestAction: Cancel error with error code" . $returnCode . "for order" . $increment_id, null, "magento.log");
            $resultData['success'] = FALSE;
            $resultData['resp'] = $resp;
            return $resultData;
        }
//	$this->setStateCancelOrder($order);
    }
        
    function cancelOrderREST($order, $increment_id){
	$result = false;
        Mage::log("cancelOrderRestAction canceling order id " . $increment_id . " with status " . $order->getStatus(), null, "magento.log");
        $token = $this->getCredentialToken();
        $resp = $this->httpPost("http://app.fahasa.com:8080/api/cancelOrderViaWebsite",
                array(
                    "userId" => "callcenter@fahasa.com",
                    "token" => $token,
                    "orderId" => $increment_id
                ));
        //Mage::log("cancelOrderRestAction response Object" . print_r($resp, true), null, "magento.log");
	
        $returnCode = $resp['error'];
        if($returnCode != 0 || empty($resp)){
            Mage::log("cancelOrderRestAction: Cancel error with error code ".$returnCode." for order $increment_id", null, "magento.log");
            Mage::getSingleton('customer/session')->addError($this->__("Cancel fail on order %s. Please call us at 1900636467.", $increment_id));
        }else{
	    $result = true;
            Mage::getSingleton('customer/session')->addSuccess($this->__('Order %s has been successfully cancelled.', $increment_id));            
	}
//	$this->setStateCancelOrder($order);
        Mage::log("cancelOrderRestAction success cancel order id " . $order->getIncrementId(), null, "magento.log");
	return $result;
    }
    
    public function createRedmineIssue($projectId, $subject, $description, $categoryId) {
        $dueDateStr = date('Y-m-d');
        $issue = array(
            "project_id" => $projectId,
            "subject" => $subject,
            "description" => $description,
            "category_id" => $categoryId,
            "due_date" => $dueDateStr
        );
        $param = json_encode(array(
            "issue" => $issue
        ));
        $header = array(
            'Content-Type:application/json',
            'Content-Length: ' . strlen($param)
        );
        $resp = $this->apiPost("http://app.fahasa.com/redmine/issues.json?key=fa11199161bdf7576524f31d3b6441581a8475a3", $param, $header);
        return $resp;
    }

    public function apiPost($url, $jsonMessage, $header) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonMessage);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
        return $server_output;
    }
    
    public function getReasonCancel() {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $query = "select reason_id, reason_description from fhs_order_cancel_reason_id where reason_id != 9;";
        $reason_cancel = $connection->fetchAll($query);
        return $reason_cancel;
    }
    
    public function getExpectedShippingForCart($data){
	$token = $this->getCredentialToken2();
	try {
	    $resp = $this->httpPost2("http://app.fahasa.com:8080/api/getProductExternalData",
		    array(
		    "userId" => "callcenter@fahasa.com",
		    "token" => $token,
		    "info" => json_encode($data)
		));
	} catch (Exception $ex) {
	    $resp['data'] = null;
	}

	return $resp['data'];
    }
    
    public function cancelOrderReturn($order, $log_file)
    {
        $status = $order->getStatus();
        $helper = \Mage::helper('cancelorder');
        $increment_id = $order->getIncrementId();
        $result = $helper->thirdPartyPaymentFailRestRerturnCode($order, $increment_id);
        \Mage::log("*** cancelOrderReturn method from payment failure: order id " . $order->getIncrementId() . " status: " . $status . "and param from thirdPartyPaymentFail (true = 1, false = ''/empty) :" . print_r($result, true), null, $log_file);
        if ($result['success'])
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
        return TRUE;
    }

}
