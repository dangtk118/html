<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/*
 *  hung.lam
 */

class Fahasa_Customer_Helper_Fpoint extends Mage_Core_Helper_Abstract {
    

    const ALGORITHM = 'sha256';
    const ACCESS_ID = 1;
    const ORIGIN_ID = 1;
    const VERSION = 1;
    const SECRET_KEY = 'ziqNaFmE9C79MZc2TfaTlW+yP2p3H/uMJqLihnvD8Qw=';
    const FPOINT_REST_CUSTOMER_CREATE_URL = '/customer';
    const FPOINT_REST_CUSTOMER_INFO_URL = '/customer/info';
    const FPOINT_REST_CUSTOMER_INFO_UPDATE_URL = '/customer/info/edit';
    const FPOINT_REST_CUSTOMER_TRANSACTION_URL = '/customer/transaction';
    const FPOINT_REST_CUSTOMER_TRANSACTION_LOG_URL = '/customer/transaction/list';
    
    public function getFpoint($customer = null, $is_newest = false){
	$result = 0;
	
	$customer_info = $this->getCustomerInfo($customer, $is_newest);
	if(!empty($customer_info['fpoint'])){
	    $result = $customer_info['fpoint'];
	}
	return $result;
    }
    public function getFreeship($customer = null, $is_newest = false){
	$result = 0;
	
	$customer_info = $this->getCustomerInfo($customer, $is_newest);
	if(!empty($customer_info['numFreeship'])){
	    $result = $customer_info['numFreeship'];
	}
	return $result;
    }
    public function getFpointAccureYear($customer = null, $is_newest = false){
	$result = 0;
	
	$customer_info = $this->getCustomerInfo($customer, $is_newest);
	if(!empty($customer_info['fpointAccureYear'])){
	    $result = $customer_info['fpointAccureYear'];
	}
	return $result;
    }
    public function getVipLevel($customer = null, $is_newest = false){
	$result = 0;
	
	$customer_info = $this->getCustomerInfo($customer, $is_newest);
	if(!empty($customer_info['vipLevel'])){
	    $result = $customer_info['vipLevel'];
	}
	return $result;
    }
    
    //---------- CALL REST ---------------
    public function getCustomerInfo($customer = null, $is_newest = false){
	$result = array('fpoint' => 0, 'numFreeship'=> 0, 'vipLevel' => 0, 'fpointAccureYear'=>0);
	
	if(empty($customer)){
	    if(Mage::getSingleton('customer/session')->isLoggedIn()){
		$customer = Mage::getSingleton('customer/session')->getCustomer();
	    }
	}
	if(empty($customer)){
	    return $result;
	}
	
	//get in redis
	if(!$is_newest){
	    $result = Mage::helper('fahasa_customer')->getCustomerStore($customer->getEntityId(), "customer_info");
	    if(!empty($result)){
		return unserialize($result);
	    }
	}
	
	//get in global store
	if(Mage::registry('customer_info')) {
	    return Mage::registry('customer_info');
	}
	$timestamp = time();
	$url = Mage::getStoreConfig("customer/fpoint_rest/url_host").self::FPOINT_REST_CUSTOMER_INFO_URL;
	$signature = $this->getSignature(self::ORIGIN_ID
					."|".$customer->getEntityId()
					."|".$timestamp);
	
	$data = array(
	    'accessId'=> self::ACCESS_ID,
	    'originId'=> self::ORIGIN_ID ,
	    'customerId'=> $customer->getEntityId(),
	    'signature'=> $signature,
	    'version'=> self::VERSION,
	    'timestamp'=> $timestamp
	);
	
	//get in rest
	$data_response = $this->execPostRequest($url, json_encode($data));
	
	if($data_response['success']){
	    if(!empty($data_response['customer']) && !empty($data_response['signature'])){
		$customer_data = $data_response['customer'];
		if($this->validateAuth($customer_data['id']
				    ."|".$customer_data['customerId']
				    ."|".$customer_data['originId']
				    ."|".$customer_data['fpoint']
				    ."|".$customer_data['numFreeship']
				    ."|".$customer_data['fpointAccureYear']
				    ."|".$data_response['message']
				    , 'respone'
				    , $data_response['signature'])){

		    $result = $data_response['customer'];
		}
	    }
	}
	Mage::helper('fahasa_customer')->setCustomerStore($customer->getEntityId(), "customer_info", serialize($result));
	Mage::register('customer_info', $result);
	return $result;
    }
    
    public function createCustomer($customer){
	$result = false;
	if(empty($customer)){
	    return $result;
	}
	
	$timestamp = time();
	$url = Mage::getStoreConfig("customer/fpoint_rest/url_host").self::FPOINT_REST_CUSTOMER_CREATE_URL;
	$signature = $this->getSignature(self::ORIGIN_ID 
					."|".$customer->getEntityId()
					."|".$timestamp
					."|".$customer->getTelephone()
					."|".$customer->getEmail()
					."|".$customer->getFirstname()
					."|".$customer->getLastname()
					);
	
	$data = array(
	    'accessId'=> self::ACCESS_ID,
	    'originId'=> self::ORIGIN_ID ,
	    'customerId'=> $customer->getEntityId(),
	    'telephone'=> $customer->getTelephone(),
	    'email'=> $customer->getEmail(),
	    'firstName'=> $customer->getFirstname(),
	    'lastName'=> $customer->getLastname(),
	    'birthday'=> (!empty($customer->getDob())?(date('Y-m-d', strtotime($customer->getDob()))):null),
	    'gender'=> (!empty($customer->getGender())?($customer->getGender()):null),
	    'password'=> $customer->getPasswordHash(),
	    'signature'=> $signature,
	    'version'=> self::VERSION,
	    'timestamp'=> $timestamp
	);
	
	//get in rest
	$data_response = $this->execPostRequest($url, json_encode($data));
	
	if($data_response['success']){
	    if(!empty($data_response['customer']) && !empty($data_response['signature'])){
		$customer_data = $data_response['customer'];
		if($this->validateAuth($customer_data['id']
				    ."|".$customer_data['customerId']
				    ."|".$customer_data['originId']
				    ."|".$customer_data['fpoint']
				    ."|".$customer_data['numFreeship']
				    ."|".$customer_data['fpointAccureYear']
				    ."|".$data_response['message']
				    , 'respone'
				    , $data_response['signature'])){
		    $result = true;
		}
	    }
	}
	return $result;
    }
    
    public function updateCustomer($customer){
	$result = false;
	if(empty($customer)){
	    return $result;
	}
	
	$timestamp = time();
	$url = Mage::getStoreConfig("customer/fpoint_rest/url_host").self::FPOINT_REST_CUSTOMER_INFO_UPDATE_URL;
	$signature = $this->getSignature(self::ORIGIN_ID 
					."|".$customer->getEntityId()
					."|".$timestamp);
	
	$data = array(
	    'accessId'=> self::ACCESS_ID,
	    'originId'=> self::ORIGIN_ID ,
	    'customerId'=> $customer->getEntityId(),
	    'telephone'=> $customer->getTelephone(),
	    'email'=> $customer->getEmail(),
	    'firstName'=> $customer->getFirstname(),
	    'lastName'=> $customer->getLastname(),
	    'birthday'=> (!empty($customer->getDob())?(date('Y-m-d', strtotime($customer->getDob()))):null),
	    'gender'=> (!empty($customer->getGender())?($customer->getGender()):null),
	    'password'=> $customer->getPasswordHash(),
	    'signature'=> $signature,
	    'version'=> self::VERSION,
	    'timestamp'=> $timestamp
	);
	
	//get in rest
	$data_response = $this->execPostRequest($url, json_encode($data));
	
	if($data_response['success']){
	    if(!empty($data_response['customer']) && !empty($data_response['signature'])){
		$customer_data = $data_response['customer'];
		if($this->validateAuth($customer_data['id']
				    ."|".$customer_data['customerId']
				    ."|".$customer_data['originId']
				    ."|".$customer_data['fpoint']
				    ."|".$customer_data['numFreeship']
				    ."|".$customer_data['fpointAccureYear']
				    ."|".$data_response['message']
				    , 'respone'
				    , $data_response['signature'])){
		    $result = true;
		}
	    }
	}
	return $result;
    }
    
    public function transationFpoint($customer_id, $amount, $type, $action, $description){
	$result = false;
	if(empty($customer_id)){
	    return $result;
	}
	
	$timestamp = time();
	$url = Mage::getStoreConfig("customer/fpoint_rest/url_host").self::FPOINT_REST_CUSTOMER_TRANSACTION_URL;
	$signature = $this->getSignature(self::ORIGIN_ID 
					."|".$customer_id
					."|".$timestamp
					."|".$amount
					."|".$type
					."|".$description
					."|".$action);
	
	$data = array(
	    'accessId'=> self::ACCESS_ID,
	    'originId'=> self::ORIGIN_ID ,
	    'customerId'=> $customer_id,
	    'amount'=> $amount,
	    'type'=> $type,
//	    'orderId'=> 0,
//	    'suborderId'=> 0,
	    'description'=> $description,
	    'action'=> $action,
	    'signature'=> $signature,
	    'version'=> self::VERSION,
	    'timestamp'=> $timestamp
	);
	
	//get in rest
	$data_response = $this->execPostRequest($url, json_encode($data));
	
	if($data_response['success']){
	    if(!empty($data_response['transactionLog']) && !empty($data_response['signature'])){
		$transactionLog_data = $data_response['transactionLog'];
		if($this->validateAuth($data_response['originId']
				    ."|".$data_response['customerId']
				    ."|".$transactionLog_data['id']
				    ."|".$transactionLog_data['amount']
				    ."|".$transactionLog_data['amountBefore']
				    ."|".$transactionLog_data['amountAfter']
				    ."|".$transactionLog_data['type']
				    ."|".$transactionLog_data['action']
				    ."|".$transactionLog_data['orderId']
				    ."|".$data_response['message']
				    , 'respone'
				    , $data_response['signature'])){
		    $result = true;
		}
	    }
	}
	return $result;
    }
    
    public function getTransationLog($customer_id, $page, $pageSize){
	$result = array();
	if(empty($customer_id)){
	    return $result;
	}
	
	$timestamp = time();
	$url = Mage::getStoreConfig("customer/fpoint_rest/url_host").self::FPOINT_REST_CUSTOMER_TRANSACTION_LOG_URL;
	$signature = $this->getSignature(self::ORIGIN_ID 
					."|".$customer_id
					."|".$timestamp
					."|".$page
					."|".$pageSize);
	
	$data = array(
	    'accessId'=> self::ACCESS_ID,
	    'originId'=> self::ORIGIN_ID ,
	    'customerId'=> $customer_id,
	    'page'=> $page,
	    'pageSize'=> $pageSize,
	    'signature'=> $signature,
	    'version'=> self::VERSION,
	    'timestamp'=> $timestamp
	);
	
	//get in rest
	$data_response = $this->execPostRequest($url, json_encode($data));
	
	if($data_response['success']){
	    if(!empty($data_response['data']) && !empty($data_response['signature']) 
		    &&  !empty($data_response['customerId']) && !empty($data_response['originId'])){
		if($this->validateAuth($data_response['customerId']
				    ."|".$data_response['originId']
				    , 'respone'
				    , $data_response['signature'])){
		    $result = $data_response['data'];
		}
	    }
	}
	
	return $result;
    }
    
    //---------- common ---------------
    public function getSignature($rawHash, $secret_type = 'request'){
	$result = '';
	try{
	    $encrypted = hash_hmac(self::ALGORITHM, $rawHash, Mage::getStoreConfig("customer/fpoint_rest/secret_key_".$secret_type), true);
	    $result = base64_encode($encrypted);
	}catch (Exception $ex) {}
	return $result;
    }
    public function validateAuth($rawHash, $secret_type = 'request', $signature){
	$result = false;
	try{
	    $hash = $this->getSignature($rawHash, $secret_type);
	    if($hash == $signature){
		$result = true;
	    }
	}catch (Exception $ex) {}
	return $result;
    }
    public function execPostRequest($url, $data, $log_file_name = 'fpoint_rest') {
        $curl = curl_init($url);
	curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
        );
//        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $Response = curl_exec($curl);
	
	$result = json_decode($Response, true);
	
	$is_debug_log = Mage::app()->getRequest()->getParam('is_debug_log', false);
        if(curl_errno($curl)){
            $error_msg = curl_error($curl);
            Mage::log("[ERROR] curl exec " . $url . ", " . print_r($data, true) . " - message: " . print_r($error_msg, true)
                    . " - response: " . print_r($result, true), null, $log_file_name.'.log');
	    
	    //for debug write to log
	    if($is_debug_log){
		Mage::log("[ERROR] curl exec " . $url . ", " . print_r($data, true). " - message: " . print_r($error_msg, true). " - response: " . print_r($result, true), null, 'debug_log.log');
	    }
        }else{
	    //for debug write to log
	    if($is_debug_log){
		Mage::log("[SUCCESS] curl exec " . $url . ", " . print_r($data, true). " - response: " . print_r($result, true), null, 'debug_log.log');
	    }
	}
	
	
        //close connection
        curl_close($curl);
        return $result;
    }
}