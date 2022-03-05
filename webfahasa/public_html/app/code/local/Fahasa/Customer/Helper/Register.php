<?php

class Fahasa_Customer_Helper_Register extends Mage_Core_Helper_Abstract {
    
    public function registerOrder($order_id, $telephone, &$password, $msg_not_account = null, $mas_has_account = null, $minLength = 7){
	$result = array();
	$result['success'] = false;
	$result['registered'] = false;
	$result['order_changed'] = false;
	$result['sent_sms'] = false;
	$result['message'] = '';
	
	$order_id = trim($order_id);
	$telephone = trim($telephone);
	$sms_content = '';
	$full_name = '';
	
	if(empty($order_id) || empty($telephone)){
	    $result['message'] = "order_id or phone: can't null";
	    return $result;
	}else if(!is_numeric($telephone)){
	    $result['message'] = "Phone isn't numeric";
	    return $result;
	}
	
	$helper = Mage::helper("fahasa_customer");
	
	//start
        $telephone_avalible = $helper->getTelephoneAvalible($telephone);
	if(count($telephone_avalible) == 0){
	    if(empty($password) || strlen($password) < $minLength || strlen($password) > 30){
		$password = $helper->generateRandomString(8);
	    }
	    
	    $email = $helper->getEmailInvalid($telephone);
	    if(empty($email)){
		$result['message'] = 'An error occurred, please try again';
		return $result;
	    }
	    $customer_id = null;
	    
	    //register customer
	    $result['message'] = $helper->registerAccount($email, $telephone, $password, null, true, $customer_id);
	    if($result['message'] != 'REGISTER_PASS' || empty($customer_id)){
		return $result;
	    }
	    $result['registered'] = true;
	    
	    //set order for customer
	    if(!$this->setOrderForCustomer($customer_id, $order_id, $full_name)){
		$result['message'] = "Can't set order for customer";
		return $result;
	    }
	    $result['message'] .= " - ORDER_CHANGED";
	    $result['order_changed'] = true;
	    
	    //send sms
	    if(!empty($msg_not_account)){
		$sms_content = $msg_not_account;
		
		$sms_content = str_replace('{{ORDER_ID}}',$order_id, $sms_content);
		$sms_content = str_replace('{{TELEPHONE}}',$telephone, $sms_content);
		$sms_content = str_replace('{{PASSWORD}}',$password, $sms_content);
		$sms_content = str_replace('{{FULL_NAME}}',$full_name, $sms_content);
	    }
	}else{
	    $customer = Mage::getModel("customer/customer")
			->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
			->loadByTelephone($telephone);
	    $customer_id = $customer->getEntityId();
	    
	    if(empty($customer_id)){
		$result['message'] = "Can't get customer";
		return $result;
	    }
	    
	    if(!$this->setOrderForCustomer($customer_id, $order_id, $full_name, false)){
		$result['message'] = "Can't set order for customer";
		return $result;
	    }
	    $result['message'] = "ORDER_CHANGED";
	    $result['order_changed'] = true;
	    
	    if(!empty($mas_has_account)){
		$sms_content = $mas_has_account;
		
		$sms_content = str_replace('{{ORDER_ID}}',$order_id, $sms_content);
		$sms_content = str_replace('{{TELEPHONE}}',$telephone, $sms_content);
		//$sms_content = str_replace('{{PASSWORD}}',$password, $sms_content);
		$sms_content = str_replace('{{FULL_NAME}}',$full_name, $sms_content);
	    }
	}
	//send sms
	if(!empty($sms_content)){
	    $this->sendSMS($telephone, $sms_content, 'register customer by order');
	    $result['message'] .= " - SMS_SENT";
	    $result['sent_sms'] = true;
	}
	$result['success'] = true;
	return $result;
    }
    
    public function setOrderForCustomer($customer_id, $order_id, &$fullname, $is_add_address = true){
	$result = false;
	
	if(Mage::getStoreConfig("customer/queue_register/enable") == 1){
	    $helper = Mage::helper("fahasa_customer");
		
	    $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
	    if(!empty($order->getId()) && empty($order->getCustomerId())){
		$helper->setCustomerOrder($customer_id, $order->getId());
		
		$shipping_address = $order->getShippingAddress();

		$first_name = !empty($shipping_address->getFirstname())?trim($shipping_address->getFirstname()):'';
		$last_name = !empty($shipping_address->getLastname())?trim($shipping_address->getLastname()):'';
		
		$fullname = ($last_name?$last_name:'').(($last_name && $first_name?' '.$first_name:($last_name?$first_name:'')));
		
		if($is_add_address){
		    $address_data = [];
		    $address_data['ward'] = $shipping_address->getWard()?$shipping_address->getWard():'';
		    $address_data['telephone'] = $shipping_address->getTelephone()?$shipping_address->getTelephone():'';
		    $address_data['street'] = $shipping_address->getStreet()?$shipping_address->getStreet():'';
		    $address_data['region_id'] = $shipping_address->getRegionId()?$shipping_address->getRegionId():'';
		    $address_data['region'] = $shipping_address->getRegion()?$shipping_address->getRegion():'';
		    $address_data['postcode'] = $shipping_address->getPostCode()?$shipping_address->getPostCode():'';
		    $address_data['lastname'] = $last_name;
		    $address_data['firstname'] = $first_name;
		    $address_data['country_id'] = $shipping_address->getCountryId()?$shipping_address->getCountryId():'';
		    $address_data['city'] = $shipping_address->getCity()?$shipping_address->getCity():'';
		    $helper->addCustomerAddress($customer_id, $address_data);
		}
		
		$result = true;
	    }
	}
	return $result;
    }
    
    public function sendSMS($telephone, $msg, $action = 'send_msg', $channel = 'web'){
	$resp = Mage::helper("cancelorder")->httpPost("http://app.fahasa.com:8080/api/authenticate", array("userId" => "callcenter@fahasa.com",
            "password" => "1e775fcce0387f24014121f183f6cc7b"));
        $token = $resp['data']['token'];

        Mage::log("** sendSMS: token: " . $token . ", telephone: $telephone, msg: $msg, channel: $channel", null, 'sms_sent.log');
        $param = (object) [
                    "phone" => $telephone,
                    "content" => $msg,
                    "from" => $channel,
                    "action" => $action
        ];
        $sent = Mage::helper("cancelorder")->httpPost("http://app.fahasa.com:8080/api/sendSmsBrandName", array(
            "userId" => "callcenter@fahasa.com",
            "token" => $token,
            "info" => json_encode($param)
        ));
	if(!empty($sent['data']['errorCode'])){
	    Mage::log("** sendSMS: errorCode: " . $sent['data']['errorCode'] . ", telephone: $telephone, msg: $msg, channel: $channel", null, 'sms_sent.log');
	}
        return $sent;
    }
    
    public function startRegisterOrderQueue(){
	$result = array();
	$result['success'] = false;
	
	if(Mage::getStoreConfig("customer/queue_register/enable") != 1){
	    $result['message'] = 'config is disable';
	    return $result;
	}
	
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	$writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	$sql_write = "update fhs_customer_register_queue set is_processed = 1, password = :password, is_registered_account = :is_registered_account, is_changed_order = :is_changed_order, is_sent_sms = :is_sent_sms, message = :message where id = :id;";

	Mage::log("<------------------------------------------->", null, 'customer_register_queue.log');
	Mage::log("<----------------READY---------------------->", null, 'customer_register_queue.log');
	Mage::log("<------------------------------------------->", null, 'customer_register_queue.log');
	
	Mage::log("Datetime = ".date('H:i:s', strtotime("+7 hours")), null, 'customer_register_queue.log');
	Mage::log("--------------", null, 'customer_register_queue.log');
	Mage::log("------Get Customer register queue------", null, 'customer_register_queue.log');
	
	$customers = $reader->fetchAll("select id, order_id, telephone, ifnull(password,'') as 'password' from fhs_customer_register_queue where is_processed = 0;");
	$customer_size = sizeof($customers);
	
	Mage::log("----> customer size = ". $customer_size, null, 'customer_register_queue.log');
	Mage::log("----------PROCESSING-----------\n", null, 'customer_register_queue.log');

	if(!empty($customers)){
	    $msg_has_account = Mage::getStoreConfig("customer/queue_register/msg_has_account");
	    $msg_not_account = Mage::getStoreConfig("customer/queue_register/msg_not_account");
	    $minLength = (int)Mage::getStoreConfig('customer/password/min_password_length');

	    foreach($customers as $key=>$item){
		$password = $item['password'];
		$id = $item['id'];
		$order_id = $item['order_id'];
		$telephone = $item['telephone'];

		$result_data = $this->registerOrder($order_id, $telephone, $password, $msg_not_account, $msg_has_account, $minLength);

		$message = $result_data['message'];
		$is_registered_account = $result_data['registered'];
		$is_changed_order = $result_data['order_changed'];
		$is_sent_sms = $result_data['sent_sms'];
		
		//update status
		$query_bindings = array(
		    'id' => $id,
		    'password' => $password,
		    'message' => $message,
		    'is_registered_account' => ($is_registered_account?1:0),
		    'is_changed_order' => ($is_changed_order?1:0),
		    'is_sent_sms' => ($is_sent_sms?1:0)
		);
		$writer->query($sql_write, $query_bindings);

		if($result_data['success']){
			Mage::log("[".($key+1)."/".$customer_size."][Success]"
				.($is_registered_account?'[registered_account]':'')
				.($is_changed_order?'[changed_order]':'')
				.($is_sent_sms?'[sent_sms]':'')
				." order_id = ".$order_id." ,telephone = ".$telephone." ,password = ".$password. ", msg = ".$message, null, 'customer_register_queue.log');
		}else{
		    Mage::log("[".($key+1)."/".$customer_size."][Error] order_id = ".$order_id." ,telephone = ".$telephone. ", msg = ".$message, null, 'customer_register_queue.log');
		}
	    }
	    $result['success'] = true;
	}else{
	    $result['message'] = 'queue no item';
	}
	Mage::log("-------------DONE--------------", null, 'customer_register_queue.log');
	
	return $result;
    }
}