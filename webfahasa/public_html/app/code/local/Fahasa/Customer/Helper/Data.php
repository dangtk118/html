<?php

class Fahasa_Customer_Helper_Data extends Mage_Core_Helper_Abstract {
    
    function insertTelephoneOtplog($userId, $telephone, $i = 0) {
        try {
            $otpCode = $this->generateRandomString();
            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
		$query = 'insert into fhs_telephone_otp_log (customer_id, telephone, otp_code, expire_otp) values(' . $userId . ', "' . $telephone . '", "' . $otpCode . '", now() + INTERVAL 30 MINUTE);';

            Mage::log("** insertTelephoneOtplog sql: " . $query, null, 'refer.log');
            $writeConnection->query($query);
            // return otpCode => send sms activate
            return $otpCode;
        } catch (Exception $e) {
            // false to insert
            Mage::log("**Error -- can't insertTelephoneOtplog: otpCode:$otpCode, userId:$userId, telephone:$telephone,  mess: " . $e->getMessage(), null, 'refer.log');
            if ($i > 3) {
                // false 3 => return false
                return FALSE;
            } else {
                // retry insert
                $i++;
                Mage::log("**Retry $i insertTelephoneOtplog: userId:$userId, telephone:$telephone,  mess: " . $e->getMessage(), null, 'refer.log');
                $this->insertTelephoneOtplog($userId, $telephone, $i);
            }
        }
        return FALSE;
    }

    function generateRandomString($length = 6) {
        // use only number generate otp code 
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function updateCustomerEntity($userId, $arrEntity) {
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        try {
            $strupdate = "";
            foreach ($arrEntity as $key => $value) {
                if (isset($key) && isset($value)) {
                    if ($strupdate == "") {
                        $strupdate = " " . $key . " = '" . $value . "' ";
                    } else {
                        $strupdate .= ", " . $key . " = '" . $value . "' ";
                    }
                } else {
                    return "ENTITY_VALUE_INVALID";
                }
            }
            $query = 'update fhs_customer_entity set ' . $strupdate . ' where entity_id= ' . $userId . ';';
            Mage::log("** updateCustomerEntity: sql" . $query, null, 'refer.log');
            $writeConnection->query($query);
            return "UPDATE_SUCCESS";
        } catch (Exception $e) {
            Mage::log("** UPDATE_ENTITY_FALSE: sql" . $query, null, 'refer.log');
            return "UPDATE_ENTITY_FALSE";
        }
    }

    function updateTelephoneAddress($customer, $telephone) {
        try {
            $address = $customer->getPrimaryBillingAddress();
            if ($address && ($address->getTelephone() !== $telephone)) {
                $address->setTelephone($telephone);
                $address->save();
                Mage::log("** updateTelephoneAddress ok: userId:" . $customer->getId() . ", with telephone:" . $telephone, null, 'refer.log');
            }
        } catch (Exception $e) {
            Mage::log("** updateTelephoneAddress False: userId:" . $customer->getId() . ", with telephone:" . $telephone . ", mess: " . $e->getMessage(), null, 'refer.log');
        }
    }

    function sendSMSOTP($telephone, $otp, $channel) {
        $resp = Mage::helper("cancelorder")->httpPost("http://app.fahasa.com:8080/api/authenticate", array("userId" => "callcenter@fahasa.com",
            "password" => "1e775fcce0387f24014121f183f6cc7b"));
        $token = $resp['data']['token'];

        Mage::log("** sendSMSOTP: token: " . $token . ", telephone: $telephone, otp: $otp, channel: $channel", null, 'refer.log');
        $param = (object) [
                    "phone" => $telephone,
                    "content" => "FAHASA.COM gui ban ma OTP xac nhan tai khoan: " . $otp,
                    "from" => $channel,
                    "action" => "activate refer_code"
        ];
        $sent = Mage::helper("cancelorder")->httpPost("http://app.fahasa.com:8080/api/sendSmsBrandName", array(
            "userId" => "callcenter@fahasa.com",
            "token" => $token,
            "info" => json_encode($param)
        ));
	if(!empty($sent['data']['errorCode'])){
	    Mage::log("** sendSMSOTP: errorCode: " . $sent['data']['errorCode'] . ", telephone: $telephone, otp: $otp, channel: $channel", null, 'refer.log');
	}
        return $sent;
    }

    /**
     * 
     * @param type $telephone
     * @param type $channel // mobile/web
     * @param type $userId // if userId != null => flow activate account from email
     * @return string
     */
    function checkTelephoneInvalid($telephone, $channel, $userId = null) {
        // handle user spam get otp 
        // max 5 times sent
        $sentOtpSess = Mage::getSingleton('core/session')->getSentOTP();
        if (isset($sentOtpSess) && $sentOtpSess > 5) {
            Mage::log("**sendOTP spam: userId: " . Mage::getSingleton('customer/session')->getId() . ", telephone:$telephone", null, 'refer.log');
            return "ERROR_SENT_OVERTIMES";
        } else {
            Mage::getSingleton('core/session')->setSentOTP($sentOtpSess + 1);
        }
        
	$telephone_avalible = $this->getTelephoneAvalible($telephone);
	
//        $resource = Mage::getSingleton('core/resource');
//        $readConnection = $resource->getConnection('core_read');
//        $query = "select * from fhs_customer_telephone_log where telephone = :telephone;";
//        $var = array("telephone" => $telephone);
//        $rs = $readConnection->fetchAll($query, $var);

        if ($userId == null) {
            $userId = Mage::getSingleton('customer/session')->getId();
        }
        
        // check telephone INVALID
        if (count($telephone_avalible) == 0) {
            // sent refer code
            $otpCode = $this->insertTelephoneOtplog($userId, $telephone);
            if ($otpCode != FALSE) {
                // call api sent sms activate
                $this->sendSMSOTP($telephone, $otpCode, $channel);
                Mage::log("**sendOTP ok: otpCode:$otpCode, userId:$userId, telephone:$telephone", null, 'refer.log');
                return "SENT_OTP";
            } else {
                return "SERVER_ERROR";
            }
        } else {
            return "ERR_TELEPHONE_EXIST";
        }
    }

    public function compareOTP($telephone, $otp, $userId = null, $facebookId = null) {
//        if ($userId == null) {
//            $userId = Mage::getSingleton('customer/session')->getId();
//        }
//        $customer = Mage::getModel('customer/customer')->load($userId);
//        $email = $customer->getEmail();
//
//        $resource = Mage::getSingleton('core/resource');
//        $readConnection = $resource->getConnection('core_read');
//        $query = "select *, if((expire_otp - now()) > 0, 1,0) as active from fhs_telephone_otp_log where telephone = :telephone and customer_id = :customer_id and otp_code = :otp_code";
//        $var = array("telephone" => $telephone, "customer_id" => $userId, "otp_code" => $otp);
//        $rs = $readConnection->fetchAll($query, $var);
//
//        // check opt + user id
//        if (count($rs) !== 0) {
//            if ($rs[0]["active"] == 1) {
//
//                // insert couponCode(then use like refercode)
//                $ruleId = Mage::getStoreConfig("customerregister/refer/ruleid");
//                $salesRule = Mage::getModel('salesrule/rule')->load($ruleId);
//
//                // generate coupon/refer code with rule id
//                $generator = Mage::getModel('salesrule/coupon_codegenerator');
//                $generator->setLength(6);
//                $salesRule->setCouponCodeGenerator($generator);
//                $salesRule->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO);
//                $coupon = $salesRule->acquireCoupon();
//                $coupon->setType(1);
//                $coupon->save();
//                $couponCode = $coupon->getCode();
//                $referCode = $couponCode;
//
//                $entData = array(
//                    "telephone" => $rs[0]["telephone"],
//                    "refer_code" => $referCode,
//                    "refer_status" => 1,
//                    "refer_rule" => $ruleId
//                );
//
//                // update customer_entity
//                $helper = Mage::helper("fahasa_customer");
//                Mage::log("**activate refer code for : otpCode:$otp, userId:$userId, telephone:$telephone", null, 'refer.log');
//                $rsUpdate = $helper->updateCustomerEntity($rs[0]["customer_id"], $entData);
//                if ($rsUpdate == "ENTITY_VALUE_INVALID" || $rsUpdate == "UPDATE_ENTITY_FALSE") {
//                    return "UPDATE_ENTITY_FALSE";
//                } else {
//                    // TODO: update default billing address = telephone
//                    // $helper->updateTelephoneAddress($customer, $rs[0]["telephone"]);
//                    $customername = $customer->getFirstname() . " " . $customer->getLastname();
//                    $expiredDate = date("d-m-Y", strtotime($salesRule->getToDate()));
//                    $data = array(
//                        'couponcode' => $referCode,
//                        'customername' => $customername,
//                        'dateend' => $expiredDate
//                    );
//                    $templateId = Mage::getStoreConfig("customerregister/refer/emailrefercode");
//
//                    $this->sendMail($templateId, $email, $customername, $data);
//                    if(!Mage::getSingleton('customer/session')->isLoggedIn()){
//                        // insert fhs_customer_telephone_log
//                        //$this->insertCustomerTelephoneLog($userId, $rs[0]["telephone"], "create");
//                        // activate account when confirm telephone success
//			$customer->setTelephone($telephone);
//                        $customer->setConfirmation(null);
//                        $customer->save();
//                        Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
//                        Mage::dispatchEvent('fhs_success_register_after_save',
//                            array('controller' => $this, 'customer' => $customer, 'type' => 'account confirm')
//                        );
//                        //check whether link is confirm facebook
//                        if ($facebookId) {
//                            $fbUserHelper = Mage::helper('facebookuser');
//                            $fbUserHelper->mappingFacebookUser($facebookId, $customer->getEmail());
//                            
//                            //send generate password for account created by facebook
//                            if (!$customer->getPasswordHash()){
//                                $customer->setPassword($customer->generatePassword());
//                                $customer->save();
//                                try{
//                                    $customer->sendPasswordReminderEmail();
//                                } catch (Exception $ex) {
//                                    \Mage::log("*** Fail to send password reminder email " . $facebookId . ", telephone = " . $telephone, null, "refer.log");
//                                }
//                            }
//                        }
//                    }else{
//                        // insert fhs_customer_telephone_log
//                        //$this->insertCustomerTelephoneLog($userId, $rs[0]["telephone"], "update");
//                    }
//		    //fpointstore vip check
//		    try{
//			$helper = Mage::helper("fpointstorev2/data");
//			if($customer->getCompanyId()){
//			    $vip_info = $helper->getVipInfo($customer->getEntityId(), $customer->getCompanyId(), false);
//			    if($vip_info['id']){
//				if(!$vip_info['customer_id']){
//				    return "VIP_ACTIVATE_SUCCESS";
//				}
//			    }
//			}
//		    } catch (Exception $ex) {
//			Mage::log("***[ERROR] _loginUser. _userEmail:". $this->_userEmail .", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
//		    }
//                    return "ACTIVATE_SUCCESS";
//                }
//            } else {
//                return "OTP_EXPIRE";
//            }
//        } else {
//            return "OTP_INVALID";
//        }
    }
    
    public function sendMail($templateId, $customerEmail, $customername, $data) {
        $emailTemplate = Mage::getModel('core/email_template')->loadByCode($templateId);
        $emailTemplate->setSenderEmail("services@fahasa.com.vn");
        $emailTemplate->setSenderName("FAHASA");
        $emailTemplate->send($customerEmail, $customername, $data);
        Mage::log("** sendMail when active telephone, create refer code: templateId:" . $templateId . ", customerEmail:" . $customerEmail . ", couponCode:" . $data['couponcode'], null, 'refer.log');
    }
    
    public function insertCustomerTelephoneLog($customerId, $telephone, $action) {
        try {
            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
            $query = 'insert into fhs_customer_telephone_log (customer_id, telephone, action, created_at) values (:customerId, :telephone, :action, now());';
            Mage::log("** updateCustomerEntity: sql" . $query, null, 'refer.log');
            $var = array(
                "customerId" => $customerId,
                "telephone" => $telephone,
                "action" => $action
            );
            $writeConnection->query($query, $var);
        } catch (Exception $e) {
            Mage::log("** insertCustomerTelephoneLog False: userId:" . $customerId . ", with telephone:" . $telephone . ", action: " . $action . ". mess: " . $e->getMessage(), null, 'refer.log');
        }
    }
    public function updateCustomerTelephoneLog($customerId, $telephone) {
        try {
            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
            $query = "update fhs_customer_telephone_log set telephone = :telephone, action = 'update' where customer_id = :customerId;";
            Mage::log("** updateCustomerEntity: sql" . $query, null, 'refer.log');
            $var = array(
                "customerId" => $customerId,
                "telephone" => $telephone
            );
            $writeConnection->query($query, $var);
        } catch (Exception $e) {
            Mage::log("** updateCustomerTelephoneLog False: userId:" . $customerId . ", with current_telephone: ".$current_telephone." -> " . $telephone. ", mess: " . $e->getMessage(), null, 'refer.log');
        }
    }
    
    public function confirmFacebookAccount($id, $key, $facebookId, $facebookKey){
        $session = Mage::getSingleton('customer/session');
        if ($session->isLoggedIn()){
            $session->logout()->regenerateSessionId();
        }

        $result = array();
        
        if ($key) {
            $result = $this->checkConfirmAccount($id, $key);
            if ($result['success']) {
                //account has been actived before
                //ex: - user create new account, send the first email verify
                //- user login facebook, provide the same email -> send the second email verify with facebook email
                //- user verify the first mail (no confirm facebook)
                //- user verify the second mail (with confirm facebook)
                // => no need confirm user, only confirm facebook email
                if ($result['message'] === 'ACCOUNT_ACTIVATED') {
                    $result = $this->checkFacebookKey($result['customer'], $facebookId, $facebookKey);
                }
            }
        } else{
            $result = $this->checkConfirmFacebook($id, $facebookId, $facebookKey);
        }
        
        //account_activated: account has been already  activated -> show login view
        //confirm_facebook_success: customer has email existed in fhs_customer_entity. Then customer login facebook -> validate email facebook
        if ($result['success'] && $result['message'] == 'CONFIRM_FACEBOOK_SUCCESS'){
            $session->setCustomerAsLoggedIn($result['customer']);
        }
        return $result;
    }
     
    public function confirmAccount($id, $key){
        $session = Mage::getSingleton('customer/session');
        if ($session->isLoggedIn()){
            $session->logout()->regenerateSessionId();
        }
         
        $result = $this->checkConfirmAccount($id, $key);
        
        return $result;
    }
    
    public function checkConfirmFacebook($id, $facebookId, $facebookKey) {
        $success = false;
        $message = null;
        
        try{
            if (empty($id) || empty($facebookId) || empty($facebookKey)){
                throw new Exception('Bad request.');
            }
            
            try{
                $customer = Mage::getModel('customer/customer')->load($id);
                if ((!$customer) || (!$customer->getId())){
                    throw new Exception('Failed to load customer by id.');
                }
            } catch (Exception $ex) {
                throw new Exception("Wrong customer account specified.");
            }
            
            //in case: when account is active, send mail without confirmation. then account is inactive by manually
            if ($customer->getConfirmation()){
                throw new Exception('Bad request.');
            }
            
            $resultFacebok = $this->checkFacebookKey($customer, $facebookId, $facebookKey);
            $success = $resultFacebok['success'];
            $message = $resultFacebok['message'];
            
        } catch (Exception $ex) {
            $success = false;
            $message = $ex->getMessage();
        }
        
        return array(
            "success" => $success,
            "message" => $message,
            "customer" => $customer
        );
    }
    
    public function checkFacebookKey($customer, $facebookId, $facebookKey) {
        $success = false;
        $message = null;
        
        try {
            //in case: when account is active, send mail without confirmation. then account is inactive by manually
            $fbUserHelper = Mage::helper('facebookuser');
            $fbUser = $fbUserHelper->getFacebookEmail($facebookId);

            //if there is no facebook email in fhs_facebook_user -> account has never verified with facebook email before
            if (!$fbUser) {
                $fbConfirmHelper = Mage::helper('facebookuser/confirm');
                $originFacebookId = $fbConfirmHelper->getFacebookId($facebookKey);

                if (!$originFacebookId || $originFacebookId !== $facebookId) {
                    throw new Exception('Wrong confirmation key.');
                }

                try {
                    $fbUserHelper->insertFacebookUser($facebookId, $customer->getEmail());
                } catch (Exception $ex) {
                    throw new Exception("Fail to insert facebook user");
                }
                
                $success = true;
                $message = "CONFIRM_FACEBOOK_SUCCESS";
            }
            else {
                //neu customer da tung dang nhap va tai khoan la: @fb.fahasa.com -> customer confirm email
                // => update tai khoan theo email dung
                if (preg_match("/@fb.fahasa.com/i", $fbUser)) {
                    
                    try {
                        $fbConfirmHelper = Mage::helper('facebookuser/confirm');
                        $originFacebookId = $fbConfirmHelper->getFacebookId($facebookKey);

                        if (!$originFacebookId || $originFacebookId !== $facebookId) {
                            throw new Exception('Wrong confirmation key.');
                        }
                        
                        //get confirm_email from fhs_facebook_confirm table -> update fhs_facebook_user based on this $confirmEmail
                        $confirmEmail = $fbConfirmHelper->getConfirmEmail($facebookKey);
                        if ($confirmEmail){
                            //update email in fhs_customer_entity + fhs_faceboook_user based on confirmEmail
                            $fbUserHelper->updateFacebookUser($facebookId, $confirmEmail);
                            $customer->setEmail($confirmEmail);
                            $customer->save();
                        }else{
                            $fbUserHelper->updateFacebookUser($facebookId, $customer->getEmail());
                        }
                                
                    } catch (Exception $ex) {
                        throw new Exception("Fail to update facebook user");
                    }
                    $success = true;
                    $message = "CONFIRM_FACEBOOK_SUCCESS";
                } else {
                    $success = true;
                    $message = "FACEBOOK_ACTIVATED";
                }
            }
        } catch (Exception $ex) {
            $success = false;
            $message = $ex->getMessage();
        }
        return array (
            "success" => $success,
            "message" => $message,
            "customer" => $customer
        );
    }

    public function checkConfirmAccount($id, $key){
        $success = false;
        $message = null;
        
        try{
            if (empty($id) || empty($key)){
                throw new Exception('Bad request.');
            }
            try {
                $customer = Mage::getModel('customer/customer')->load($id);
                if ((!$customer) || (!$customer->getId())){
                    throw new Exception('Failed to load customer by id.');
                }
            } catch (Exception $ex) {
                throw new Exception('Wrong customer account secified.');
            }
            
            $success = true;
            if ($customer->getConfirmation()){
                if ($customer->getConfirmation() !== $key){
                    throw new Exception('Wrong confirmation key.');
                }
                
                $message = "CONFIRM_EMAIL_SUCCESS";
            }
            else{
                $message = "ACCOUNT_ACTIVATED";
            }
            
        } catch (Exception $ex) {
            $success = false;
            $message = $ex->getMessage();
        }
        
        return array(
            "success" => $success,
            "message" => $message,
            "customer" => $customer
        );
    }
    
    public function getMyNotifications($type, $limit){
        
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if (!$customer || !$customer->getEntityId()) {
            return array(
                'result'=> false,
                'error_type' => 'no_customer'
            );
        }
        
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        
        $page = (int)$page;
        $page_size = (int)$page_size;
        
        if($type == 'all'){
            $query_notifications = "SELECT id, title, content, customer_id, DATE_FORMAT(created_at, '%H:%i %d/%m/%Y') as formated_created_at,"
            . " seen_status, page_type, page_value, url, action_type, action_data, action_result "
            . "FROM fhs_mobile_notification WHERE "
            . " customer_id = :customer_id"
            . " ORDER BY seen_status ASC, created_at DESC"
            . " LIMIT ". $limit. " ;";
            
            $query_bindings = array(
                'customer_id' => $customer->getEntityId()
            );
        }else{
            $query_notifications = "SELECT id, title, content, customer_id, DATE_FORMAT(created_at, '%H:%i %d/%m/%Y') as formated_created_at,"
            . " seen_status, page_type, page_value, url, action_type, action_data, action_result "
            . "FROM fhs_mobile_notification WHERE page_type=:type "
            . " AND customer_id = :customer_id"
            . " ORDER BY seen_status ASC, created_at DESC"
            . " LIMIT ". $limit. " ;";

            $query_bindings = array(
                'type' => $type,
                'customer_id' => $customer->getEntityId()
            );
        }

        
        $notes = $connection->fetchAll($query_notifications, $query_bindings);
        
        $query_unseen = "select * from ((
            select * from fhs_mobile_notification 
            where page_type = 'event'
            and customer_id = :customer_id
            and seen_status = '0'
            order by created_at desc
            limit 1
            ) UNION (
            select * from fhs_mobile_notification 
            where page_type = 'coupon'
            and customer_id = :customer_id
            and seen_status = '0'
            order by created_at desc
            limit 1
            ) UNION (
            select * from fhs_mobile_notification 
            where page_type = 'order'
            and customer_id = :customer_id
            and seen_status = '0'
            order by created_at desc
            limit 1
            ) UNION (
            select * from fhs_mobile_notification 
            where page_type = 'action'
            and customer_id = :customer_id
            and seen_status = '0'
            order by created_at desc
            limit 1
            )) a 
            order by created_at desc;";
        
        $query_email_bindings = array(
            'customer_id' => $customer->getEntityId()
        );
        
        $all_types = $connection->fetchAll($query_unseen, $query_email_bindings);
        $unseens = array();
        foreach($all_types as $type){
            $unseens[$type['page_type']] = true;
        }
        
        $query_top_action = "SELECT  id, title, content, customer_id, DATE_FORMAT(created_at, '%H:%i %d/%m/%Y') as formated_created_at, 
            seen_status, page_type, page_value, url, action_type, action_data, action_result 
            FROM fhs_mobile_notification 
            WHERE page_type='action'
            AND customer_id = :customer_id
            AND action_result is NULL 
            ORDER BY created_at DESC
            LIMIT 1;";
        
        $top_msg = $connection->fetchRow($query_top_action, $query_email_bindings);
        
        return array(
            'result' => true,
            'notes' => $notes,
            'unseens' => $unseens,
            'top_msg' => $top_msg
        );
    }
    
    public function clearUnseen($type, $msg_id){
        
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if (!$customer || !$customer->getEntityId()) {
            return array(
                'result'=> false,
                'error_type' => 'no_customer'
            );
        }
        
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        if($msg_id){
            $update_query = "UPDATE fhs_mobile_notification SET seen_status = 1 
                WHERE customer_id = :customer_id AND id = :id ;";
            
            $query_bindings = array(
                'customer_id' => $customer->getEntityId(),
                'id' => $msg_id,
            );
        }else if($type && $type!="all"){
            $update_query = "UPDATE fhs_mobile_notification SET seen_status = 1 
                WHERE customer_id = :customer_id AND page_type = :type AND (url IS NULL OR url = '') AND seen_status = 0;";
            
            $query_bindings = array(
                'customer_id' => $customer->getEntityId(),
                'type' => $type,
            );
        }else{
            $update_query = "UPDATE fhs_mobile_notification SET seen_status = 1 
                WHERE customer_id = :customer_id AND (url IS NULL OR url = '') AND seen_status = 0;";
            
            $query_bindings = array(
                'customer_id' => $customer->getEntityId()
            );
        }
        
        try{
            $connection->query($update_query, $query_bindings);
            $results = array(
                'result' => true,
            );
        } catch (Exception $ex) {
            $results = array(
                'result'=> false,
                'error_type' => 'system'
            );
        }
        
        return $results;        
    }
    
    public function responseToActionMsg($msg_id, $action_result){
        
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if (!$customer || !$customer->getEmail()) {
            return array(
                'result'=> false,
                'error_type' => 'no_customer'
            );
        }

        $update_query = "UPDATE fhs_mobile_notification SET seen_status = 1, action_result = :action_result
            WHERE customer_id = :customer_id AND page_type = :type AND id = :id;";
        
        $query_bindings = array(
            'customer_id' => $customer->getEntityId(),
            'type' => 'action',
            'id' => $msg_id,
            'action_result' => $action_result
        );
        
        try{
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $connection->query($update_query, $query_bindings);
            $results = array(
                'result' => true,
            );
        } catch (Exception $ex) {
            $results = array(
                'result'=> false,
                'error_type' => 'system'
            );
        }
        
        return $results;
    }
    
    public function copyPersonalizationDataToRedis(){
        
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        
        /*
         *  Copy Products
         */
        $products_query = "SELECT pe.entity_id as 'product_id', pe.category_mid, pe.category_mid_id, pe.type_id, 
	name.value as 'product_name', img_url.value as 'image_src', url0.value as product_url_store_0, url1.value as product_url_store_1, 
	IFNULL(rating.awsRatings,0) + IFNULL(rating.grRatings,0) + IFNULL(review.reviews_count,0) as 'rating_count', 
        GREATEST(IFNULL((rating.awsAvgScore/5)*100,0), IFNULL((rating.grAvgScore/5)*100,0),IFNULL(review.rating_summary,0)) as 'rating_summary',
	if(bd.final_price = 0, 0, concat(format(bd.final_price, 0, 'vi_VN'), 'đ')) as 'display_final_price',
	if(bd.price = 0, 0, concat(format(bd.price, 0, 'vi_VN'), 'đ')) as 'display_price',
	if(bd.final_price = 0, 0, ROUND(((bd.price - bd.final_price) / bd.price) * 100)) as 'discount',
	if(bd.final_price = 0, 0, concat(format(bd.final_price, 0, 'vi_VN'), 'đ')) as 'bundle_display_final_price',
	if(bd.price = 0, 0, concat(format(bd.price, 0, 'vi_VN'), 'đ')) as 'bundle_display_price',
	if(bd.final_price = 0, 0, ROUND(((bd.price - bd.final_price) / bd.price) * 100)) as 'bundle_discount'
	FROM fhs_personalize_products pp
	JOIN fhs_catalog_product_entity pe ON pp.related_pid = pe.entity_id
	LEFT JOIN fhs_catalog_product_entity_varchar name ON name.entity_id = pe.entity_id AND name.attribute_id = 71
	LEFT JOIN fhs_catalog_product_entity_varchar img_url ON pe.entity_id=img_url.entity_id AND img_url.attribute_id=85
	LEFT JOIN book_rating rating ON rating.sku = pe.sku
	LEFT JOIN fhs_review_entity_summary review ON review.entity_pk_value = pe.entity_id AND review.store_id=0
	LEFT JOIN fhs_catalog_product_entity_decimal p ON p.entity_id = pe.entity_id AND p.attribute_id = 75
	LEFT JOIN fhs_catalog_product_entity_decimal sp ON sp.entity_id = pe.entity_id AND sp.attribute_id = 76 AND sp.store_id = 0
	LEFT JOIN fhs_catalog_product_entity_varchar url0 ON pe.entity_id = url0.entity_id AND url0.attribute_id = 98 AND url0.store_id=0
	LEFT JOIN fhs_catalog_product_entity_varchar url1 ON pe.entity_id = url1.entity_id AND url1.attribute_id = 98 AND url1.store_id=1
	LEFT JOIN fhs_catalog_product_index_price_store bd ON pe.entity_id = bd.entity_id AND bd.customer_group_id = 0 AND bd.store_id = 1
	INNER JOIN fhs_catalog_product_entity_int AS ei ON ei.entity_id = pe.entity_id AND ei.attribute_id = '102' AND ei.store_id = 0 AND ei.value = 4
	INNER JOIN fhs_catalog_product_entity_int AS ei2 ON ei2.entity_id = pe.entity_id AND ei2.attribute_id = '96' AND ei2.store_id = 0 AND ei2.value = 1
	GROUP BY pe.entity_id;";
        
        $product_results = $connection->fetchAll($products_query);
        
        /// Start Redis Connection
        $helper_redis = Mage::helper("flashsale/redis");
        $redis_client = $helper_redis->createRedisClient();
        
        if (!$redis_client->isConnected()) {
            return array(
                "result" => false,
                "msg" => "Can't connect to Redis."
            );
        }
        
        /// Delete previous redis data with key personalization:product*
        $redis_client->delete($redis_client->keys("personalization:product:*"));
        $image_helper = Mage::helper('catalog/image');
        
        /// Copy all products
        foreach ($product_results as $product) {
            $product_key = "personalization:product:". $product['product_id'];
            $product_model = Mage::getModel('catalog/product')->load($product['product_id']);
            /// Image
            $product['image_src'] = (string)$image_helper->init($product_model, 'small_image')->resize(400, 400);
            
            // Product Url
            if($product['product_url_store_1']){
                $product['product_url'] = $product['product_url_store_1'];
            }else{
                $product['product_url'] = $product['product_url_store_0'];
            }
            
            unset($product['product_url_store_0']);
            unset($product['product_url_store_1']);
            
            /// Bundle Price
            if($product['type_id']=='bundle'){
                $product['display_price'] = $product['bundle_display_price'];
                $product['display_final_price'] = $product['bundle_display_final_price'];
                $product['discount'] = $product['bundle_discount'];
            }
            Mage::log("product_id: " . $product['product_id'], null, "import_personalize_data_to_redis.log");
            Mage::log("display_price: " . $product['display_price'], null, "import_personalize_data_to_redis.log");
            Mage::log("display_final_price: " . $product['display_final_price'], null, "import_personalize_data_to_redis.log");
            Mage::log("----------------", null, "import_personalize_data_to_redis.log");

            unset($product['bundle_display_price']);
            unset($product['bundle_display_final_price']);
            unset($product['bundle_discount']);

            $redis_client->hMSet($product_key, $product);
        }
        
        /*
         *  Copy Category Data
         */
        /*
        $cat_query = "SELECT ce.entity_id as 'customer_id', pe.category_mid_id, pe.category_mid, count(pe.entity_id) as 'product_count', 
                    GROUP_CONCAT(pe.entity_id SEPARATOR ',') as 'product_ids',
                    GROUP_CONCAT(pe.num_orders SEPARATOR ',') as 'best_week', 
                    GROUP_CONCAT(pe.num_orders_month SEPARATOR ',') as 'best_month',
                    GROUP_CONCAT(pe.num_orders_year SEPARATOR ',') as 'best_year'
                    FROM fhs_personalize_products pp
                    JOIN fhs_customer_entity ce ON ce.email = pp.email
                    JOIN fhs_catalog_product_entity pe ON pp.related_pid = pe.entity_id
                    GROUP BY pp.email, pe.category_mid_id
                    ORDER BY pp.email, product_count DESC;";
        
        $cat_results = $connection->fetchAll($cat_query);
        
        //// Create property : best of week, best of month, best of year
        $i = 0;
        foreach ($cat_results as $cat){
            /// customers_data
            $cat['product_ids'] = explode(",", $cat['product_ids']);
            $cat['best_week'] = explode(",", $cat['best_week']);
            $cat['best_month'] = explode(",", $cat['best_month']);
            $cat['best_year'] = explode(",", $cat['best_year']);
            
            $temp_week = array_combine($cat['product_ids'], $cat['best_week']);
            $temp_month = array_combine($cat['product_ids'], $cat['best_month']);
            $temp_year = array_combine($cat['product_ids'], $cat['best_year']);
            
            arsort($temp_week);
            arsort($temp_month);
            arsort($temp_year);
            
            $cat_results[$i]['best_week'] = implode(",", array_keys($temp_week));
            $cat_results[$i]['best_month'] = implode(",", array_keys($temp_month));
            $cat_results[$i]['best_year'] = implode(",", array_keys($temp_year));
            
            $i++;
        }
        
        $customers_data = array();
        $current_customer_id = null;
        $MAX_CATS = 4;
        $i = 0;
        $cat_all = null;
        $cat_other = null;
        
        foreach ($cat_results as $cat){
            if(!$cat['category_mid_id']){
                continue;
            }
            
            if($current_customer_id != $cat['customer_id']){
                //// Customer Categories
                if(!is_null($customers_data[$current_customer_id])){
                    if($cat_other && $cat_other['product_count'] > 0){
                        $customers_data[$current_customer_id][] = array(
                            'id' => 'other',
                            'name' => 'Khác'
                        );
                    }
                    
                    $customers_data[$current_customer_id] = json_encode($customers_data[$current_customer_id]);
                }
                
                $customers_data[$cat['customer_id']] = array();
                $customers_data[$cat['customer_id']][] = array(
                    'id' => 'all',
                    'name' => 'Tất Cả'
                );
                
                $current_customer_id = $cat['customer_id'];
                $i = 0;
                
                //// Category All
                if($cat_all){
                    $cat_key = "personalization:customer:". (int)$cat_all['customer_id'] .":cat:all";
                    $redis_client->hMSet($cat_key, $cat_all);
                }
                
                $cat_all = array();
                $cat_all['customer_id'] = $cat['customer_id'];
                $cat_all['product_ids'] = $cat['product_ids'];
                $cat_all['best_week'] = $cat['best_week'];
                $cat_all['best_month'] = $cat['best_month'];
                $cat_all['best_year'] = $cat['best_year'];
                $cat_all['product_count'] = (int)$cat['product_count'];
                $cat_all['category_mid'] = 'all';
                $cat_all['category_mid_id'] = 0;
                
                //// Category Other
                if($cat_other){
                    $cat_key = "personalization:customer:". (int)$cat_other['customer_id'] .":cat:other";
                    $redis_client->hMSet($cat_key, $cat_other);
                }
                
                $cat_other = array();
                $cat_other['customer_id'] = $cat['customer_id'];
                $cat_other['product_count'] = 0;
                $cat_other['category_mid'] = 'other';
                $cat_other['category_mid_id'] = 0;
                
            }else{
                $cat_all['product_ids'] .= "," .$cat['product_ids'];
                $cat_all['best_week'] .= "," .$cat['best_week'];
                $cat_all['best_month'] .= "," .$cat['best_month'];
                $cat_all['best_year'] .= "," .$cat['best_year'];
                
                $cat_all['product_count'] += (int)$cat['product_count'];
            }
            
            if($i < $MAX_CATS){
                $customers_data[$current_customer_id][] = array(
                    'id' => $cat['category_mid_id'],
                    'name' => $cat['category_mid']
                );
                
                $cat_key = "personalization:customer:". (int)$cat['customer_id'] .":cat:" .$cat['category_mid_id'];
                $redis_client->hMSet($cat_key, $cat);
                $i++;
            }else{
                if($cat_other['product_ids']){
                    $cat_other['product_ids'] .= "," . $cat['product_ids'];
                    $cat_other['best_week'] .= "," . $cat['best_week'];
                    $cat_other['best_month'] .= "," . $cat['best_month'];
                    $cat_other['best_year'] .= "," . $cat['best_year'];

                }else{
                    $cat_other['product_ids'] = $cat['product_ids'];
                    $cat_other['best_week'] = $cat['best_week'];
                    $cat_other['best_month'] = $cat['best_month'];
                    $cat_other['best_year'] = $cat['best_year'];
                }
                
                $cat_other['product_count'] += (int)$cat['product_count'];
            }
        }
        
        $customers_key = "personalization:customers";
        $redis_client->hMSet($customers_key, $customers_data);
        */
        $redis_client->close();
        
        return array(
            "result" => true,
            "total" => count($product_results),
        );
    }
    
    public function getVAT($customer_id){
	$result = array();
	$result['success'] = false;
	$result['company'] = '';
	$result['address'] = '';
	$result['taxcode'] = '';
	$result['message'] = 'no data';
        try{
	    $read = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select * from fhs_customer_tax where entity_id = '".$customer_id."';";
	    $rows = $read->fetchAll($sql);
	    if($rows[0]){
		$result['company'] = $rows[0]['company'];
		$result['address'] = $rows[0]['address'];
		$result['taxcode'] = $rows[0]['taxcode'];
		$result['name'] = $rows[0]['name'];
		$result['email'] = $rows[0]['email'];
		$result['message'] = 'have data';
		$result['success'] = true;
	    }
        } catch (Exception $ex) {}
        return $result;
    }
    
    public function saveVAT($customer_id, $vat_company, $vat_address, $vat_taxcode, $vat_name = '', $vat_email = ''){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$vat_company = $product_helper->cleanBug($vat_company);
	$vat_address = $product_helper->cleanBug($vat_address);
	$vat_taxcode = $product_helper->cleanBug($vat_taxcode);
	$vat_name = $product_helper->cleanBug($vat_name);
	$vat_email = $product_helper->cleanBug($vat_email);
	
	$result = false;
        try{
	    $write = Mage::getSingleton("core/resource")->getConnection("core_write");
	    $sql = "INSERT INTO fhs_customer_tax(entity_id, company, address, taxcode, name, email) 
		    VALUES (:customer_id, :company,:address, :taxcode, :name, :email)
		    ON DUPLICATE KEY UPDATE 
		    company=:company,
		    address=:address,
		    taxcode=:taxcode,
		    name=:name,
		    email=:email;";
	    $binds = array(
		"customer_id" => "$customer_id",
		"company" => "$vat_company",
		"address" => "$vat_address",
		"taxcode" => "$vat_taxcode",
		"name" => "$vat_name",
		"email" => "$vat_email"
	    );
	    $write->query($sql,$binds);
	    $result = true;
        } catch (Exception $ex) {}
	return $result;
    }
    public function createCustomerByExcel($customer, $telephone){
	if(!Mage::registry('is_create_customer')) {Mage::register('is_create_customer', true);}
	
	// insert couponCode(then use like refercode)
	$customer_id = $customer->getEntityId();
	$ruleId = Mage::getStoreConfig("customerregister/refer/ruleid");
	$salesRule = Mage::getModel('salesrule/rule')->load($ruleId);

	// generate coupon/refer code with rule id
	$generator = Mage::getModel('salesrule/coupon_codegenerator');
	$generator->setLength(6);
	$salesRule->setCouponCodeGenerator($generator);
	$salesRule->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO);
	$coupon = $salesRule->acquireCoupon();
	$coupon->setType(1);
	$coupon->save();
	$couponCode = $coupon->getCode();
	$referCode = $couponCode;

	$entData = array(
	    "telephone" => $telephone,
	    "refer_code" => $referCode,
	    "refer_status" => 1,
	    "refer_rule" => $ruleId
	);
	// update customer_entity
	Mage::log("**activate refer code for : otpCode:$otp, userId:$customer_id, telephone:$telephone", null, 'refer.log');
	$rsUpdate = $this->updateCustomerEntity($customer_id, $entData);
	if ($rsUpdate == "ENTITY_VALUE_INVALID" || $rsUpdate == "UPDATE_ENTITY_FALSE") {
	    return "UPDATE_ENTITY_FALSE";
	} else {
	    if(!Mage::getSingleton('customer/session')->isLoggedIn()){
		// insert fhs_customer_telephone_log
		//$this->insertCustomerTelephoneLog($customer_id, $telephone, "create");
		// activate account when confirm telephone success
		$customer->setTelephone($telephone);
		$customer->setConfirmation(null);
		$customer->save();
		Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
		Mage::dispatchEvent('fhs_success_register_after_save',
		    array('controller' => $this, 'customer' => $customer, 'type' => 'account confirm')
		);
	    }else{
		// insert fhs_customer_telephone_log
		//$this->insertCustomerTelephoneLog($customer_id, $telephone, "update");
	    }
	    Mage::getSingleton('customer/session')->logout();
	}
    }
    
    public function saveExpectedAddress($provinceId, $province, $districtId, $district, $wardId, $ward, $sku){
        if (!isset($provinceId) || !isset($districtId) || !isset($wardId)){
            return array(
		"success" => false
	    );
        }
        
        $result = false;
        $session = Mage::getSingleton('customer/session');
        
        try{
            if ($session->isLoggedIn()) {
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                if (!$customer){
                    return array(
                        "success" => false
                    );
                }

                $customer_id = $customer->getId();

                $sql = "insert into fhs_customer_address_shipping (customer_id, province_id, district_id, ward_id) values "
                        . "(:customer_id, :province_id, :district_id, :ward_id) "
                        . "on duplicate key update province_id = :province_id, district_id = :district_id, ward_id = :ward_id ";
                $params = array(
                    "customer_id" => $customer_id,
                    "province_id" => $provinceId,
                    "district_id" => $districtId,
                    "ward_id" => $wardId
                );
                $write = Mage::getSingleton('core/resource')->getConnection('core_write');
                $write->query($sql, $params);

            }

            $expected_addr = array(
                "province_id" => $provinceId,
                "district_id" => $districtId,
                "ward_id" => $wardId,
            );
            
            $session->setExpectedAddress($expected_addr);
            $result = true;
        } catch (Exception $ex) {

        }
        
        
	if ($result) {
	    $expected_addr = array(
		"province_id" => $provinceId,
		"province" => $province,
		"district_id" => $districtId,
		"district" => $district,
		"ward_id" => $wardId,
		"ward" => $ward
	    );
            
            $data = array(
                "success" => true,
                "data" => $expected_addr
            );

	    return $this->getExpectedShippingForProduct($sku, $data);
	}
	
	return array(
	    "success" => false
	);
    }
    
    public function getDefaultCustomerAddress(){
        $expected_shipping = null;
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        try {
            $customer_id = $customer->getId();
            $shipping_query = "select ca.province_id, pr.name as province, ca.district_id, di.district_name as district, ca.ward_id, wa.ward_name as ward "
                    . "from fhs_customer_address_shipping ca "
                    . "join fhs_directory_country_region_name pr on pr.region_id = ca.province_id "
                    . "join fhs_vietnamshipping_district di on di.district_id = ca.district_id "
                    . "join fhs_vietnamshipping_ward wa on wa.ward_id = ca.ward_id "
                    . "where ca.customer_id = :customer_id ";

            $params = array(
                "customer_id" => $customer_id,
            );
            
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $shipping_result = $read->fetchAll($shipping_query, $params);
            
            if (count($shipping_result) > 0) {
                $expected_shipping = $shipping_result[0];
            }
            else{
                $sql = "select ad.entity_id as address_id, pro_id.value as province_id, province.value as province, dis_id.district_id, "
                        . "district.value as district, w_id.ward_id, ward.value as ward, 1 as is_customer_address, if (billing.value, 1, 0) as is_default_billing, "
                        . "if (shipping.value, 1, 0) as is_default_shipping "
                        . "from fhs_customer_address_entity ad "
                        . "left join fhs_customer_address_entity_varchar province on province.entity_id = ad.entity_id and province.attribute_id = 28 "
                        . "left join fhs_customer_address_entity_int pro_id on pro_id.entity_id = ad.entity_id and pro_id.attribute_id = 29 "
                        . "left join fhs_vietnamshipping_province v_pro_id on v_pro_id.province_name = province.value "
                        . "left join fhs_customer_address_entity_varchar district on district.entity_id = ad.entity_id and district.attribute_id = 26 "
                        . "left join fhs_vietnamshipping_district dis_id on v_pro_id.province_id = dis_id.province_id and dis_id.district_name = district.value "
                        . "left join fhs_customer_address_entity_varchar ward on ward.entity_id = ad.entity_id and ward.attribute_id = 200 "
                        . "left join fhs_vietnamshipping_ward w_id on w_id.district_id = dis_id.district_id and w_id.ward_name = ward.value "
                        . "left join fhs_customer_entity_int billing on billing.entity_id = ad.parent_id and billing.attribute_id = 13 and billing.value = ad.entity_id "
                        . "left join fhs_customer_entity_int shipping on shipping.entity_id = ad.parent_id and shipping.attribute_id = 14 and billing.value = ad.entity_id "
                        . "where ad.parent_id = :customer_id ";

                $address_result = $read->fetchAll($sql, $params);
                if (count($address_result) > 0){
                    $billing_idx = array_search(1, array_column($address_result, "is_default_billing"));
                    if ($billing_idx){
                        $billing_addr = $address_result[$billing_idx];
                        $expected_shipping = $billing_addr;
                    }
                    else{
                        $shipping_idx = array_search(1, array_column($address_result, "is_default_shipping"));
                        if ($shipping_idx){
                            $shipping_addr = $address_result[$shipping_idx];
                            $expected_shipping = $shipping_addr;
                        }
                        else{
                            $expected_shipping = $address_result[0];
                        }
                    }
                }
            }
        } catch (Exception $ex) {
            
        }
        return $expected_shipping;
    }

    public function getExpectedAddress() {
        $result = null;
        $expected_shipping = null;

        $session = Mage::getSingleton('customer/session');

        $expected_address = $session->getData("expected_address");
        
        if ($expected_address){
            $province_id = $expected_address["province_id"];
            $district_id = $expected_address["district_id"];
            $ward_id = $expected_address["ward_id"];
            $session_query = "select re.name as province, dis.district_name as district, wa.ward_name as ward "
                    . "from fhs_directory_country_region_name re "
                    . "join fhs_vietnamshipping_province pr on pr.province_name = re.name "
                    . "join fhs_vietnamshipping_district dis on dis.province_id = pr.province_id "
                    . "join fhs_vietnamshipping_ward wa on wa.district_id = dis.district_id "
                    . "where re.region_id = :province_id and dis.district_id = :district_id and wa.ward_id = :ward_id ";
            $params = array(
                "province_id" => $province_id,
                "district_id" => $district_id,
                "ward_id" => $ward_id
            );
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $session_rs = $read->fetchAll($session_query, $params);
            if (count($session_rs) > 0){
                $expected_shipping = array(
                    "province_id" => $province_id,
                    "province" => $session_rs[0]["province"],
                    "district_id" => $district_id,
                    "district" => $session_rs[0]["district"],
                    "ward_id" => $ward_id,
                    "ward" => $session_rs[0]["ward"]
                );
            }
        }
        else {
            if ($session->isLoggedIn()){
               $expected_shipping = $this->getDefaultCustomerAddress();
               if ($expected_shipping != null){
                   $this->saveExpectedAddress($expected_shipping["province_id"],'', $expected_shipping["district_id"],'', $expected_shipping["ward_id"],'','');
               }
            }
        }
        
        
        if ($expected_shipping){
            $result = array(
                "province_id" => $expected_shipping["province_id"],
                "province" => $expected_shipping["province"],
                "district_id" => $expected_shipping["district_id"],
                "district" => $expected_shipping["district"],
                "ward_id" => $expected_shipping["ward_id"],
                "ward" => $expected_shipping['ward'],
            );
        }

        return array(
            "success" => true,
            "data" => $result
        );
    }
    
    //if there is an address, it is called by saveAddressShipping
    public function getExpectedShippingForProduct($sku, $address){
	$result = [];
	$result['success'] = true;
	$result['address'] = null;
	$result['expected_delivery'] = null;
	$result['event_delivery'] = null;
	
	if ($address){
	    $expected_address = $address;
	} else {
	    $expected_address = $this->getExpectedAddress();
	}
	
	$event_delivery_list = $this->getEventDeiveryList(false, null, null, $sku, $expected_address);
        //sort based on is_show_icon because mobile is choosing the index 0 for render UI
        array_multisort(array_column($event_delivery_list, 'is_show_icon'), SORT_DESC, $event_delivery_list);
	$result['event_delivery'] = $event_delivery_list;
	
	if (!$expected_address["success"]){
	    return $result;
	}
	
	
	$success = false;
	try {
	    $address_data = [];
	    if (Mage::getSingleton('customer/session')->isLoggedIn()) {
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$address_data['customer_id'] = $customer->getId();
	    } else {
		$address_data['customer_id'] = "";
	    }
	    $address_data['city'] = $expected_address['data']['province'];
	    $address_data['district'] = $expected_address['data']['district'];
	    $address_data['product_sku_list'] = $sku;

            //if (!empty($address_data['customer_id']) || !empty($address_data['city']) || !empty($address_data['district'])){
//              $expected_data = Mage::helper('cancelorder')->getExpectedShippingForCart($address_data);
//		if ($expected_data){
//                    $expected_data['fpoint'] = (string) $expected_data['fpoint'];
//              }
		$expected_data = Mage::helper('onestepcheckout')->getExpectedDeliveryInProductview($address_data);
            //}
	} catch (Exception $ex) {}
	
	$result['address'] = $expected_address["data"];
	$result['expected_delivery'] = $expected_data;
	return $result;
    }
    
    //check and create email
    public function getEmailInvalid($telephone, $time = 0){
	//over load time
	if($time > 10){return "";}
	
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	$id = rand(99,999);
	$email = "notverify_".$telephone."_".$id.$time."@fahasa.com";
	$sql = "select entity_id from fhs_customer_entity where email = '".$email."' limit 1;";
	$customer_email = $reader->fetchRow($sql);
	if($customer_email['entity_id']){
	    $time++;
	    $email = getEmailInvalid($telephone, $time);
	}
	return $email;
    }
    //check telephone in db
    public function getTelephoneAvalible($telephone){
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $query = "select * from fhs_customer_entity where telephone = :telephone;";
        $var = array("telephone" => $telephone);
        $rs = $readConnection->fetchAll($query, $var);
	return $rs;
    }
    //check email in db
    public function getEmailAvalible($email){
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $query = "select * from fhs_customer_entity where email = :email;";
        $var = array("email" => $email);
        $rs = $readConnection->fetchAll($query, $var);
	return $rs;
    }
    //send telephone otp
    public function sendPhoneOTP($telephone, $userId = '', $logname = '', $channel) {
        $otpCode = $this->insertPhoneOtplog($userId, $telephone);
	if ($otpCode != FALSE) {
	    // call api sent sms activate
	    $this->sendSMSOTP($telephone, $otpCode, $channel);
	    Mage::log("**".$logname." sendOTP ok: otpCode:".$otpCode.", userId:".$userId.", telephone:".$telephone, null, 'refer.log');
	    return $this->__('OTP sent');
	} else {
	    return $this->__('An error occurred, please try again');
	}
    }
    //send email otp
    public function sendEmailOTP($email, $customer_Id, $customer_name, $logname = '') {
        $otpCode = $this->insertPhoneOtplog($customer_Id, $email);
	if ($otpCode != FALSE) {
	    try {
		if(empty(trim($customer_name))){
		    $customer_name = $this->__('Friend');
		}
		$data = array(
		    'customername' => $customer_name,
		    'OTP' => $otpCode
		);
		$emailTemplate = Mage::getModel('core/email_template')->loadByCode("Send OTP");
		$emailTemplate->setSenderEmail("services@fahasa.com.vn");
		$emailTemplate->setSenderName("FAHASA");
		$emailTemplate->send($email, $customer_name, $data);
		
		Mage::log("**".$logname." sendEmailOTP ok: otpCode:".$otpCode.", customer_Id:".$customer_Id.", email:".$email, null, 'refer.log');
	    } catch (Exception $e) {
		Mage::log("**".$logname." sendEmailOTP ok: otpCode:".$otpCode.", customer_Id:".$customer_Id.", email:".$email.", msg:".$e->getMessage(), null, 'refer.log');
		return $this->__('An error occurred, please try again');
	    }
	    return $this->__('OTP sent');
	} else {
	    return $this->__('An error occurred, please try again');
	}
    }
    
    //insert Notification
    public function pushNotification($email, $title, $message, $pageValue, $pageType = 'event', $url = '', $customer_id, $time = 1){
	$result = false;
	try{
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $sql = "INSERT INTO fhs_mobile_notification (title, content, customer_id, created_by, created_at, seen_status, page_type, page_value, url) 
		    VALUES('".$title."', '".$message."', ".$customer_id.", 'magento', now(), 0, '".$pageType."', '".$pageValue."','".$url."'); ";

	    $writer->query($sql);
	    $result['result'] = true;
	} catch (Exception $ex) {}
        return $result;
    }
    //insert otp
    public function insertPhoneOtplog($userId, $telephone, $i = 0) {
        try {
            $otpCode = $this->generateRandomString();
            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
	    if(empty($userId)){
		$query = 'insert into fhs_telephone_otp_log (telephone, otp_code, expire_otp) values("' . $telephone . '", "' . $otpCode . '", now() + INTERVAL 30 MINUTE);';
	    }else{
		$query = 'insert into fhs_telephone_otp_log (customer_id, telephone, otp_code, expire_otp) values(' . $userId . ', "' . $telephone . '", "' . $otpCode . '", now() + INTERVAL 30 MINUTE);';
	    }
            $writeConnection->query($query);
            Mage::log("** insertTelephoneOtplog sql: " . $query, null, 'refer.log');
            // return otpCode => send sms activate
            return $otpCode;
        } catch (Exception $e) {
            // false to insert
            Mage::log("**Error -- can't insertTelephoneOtplog: otpCode:$otpCode, userId:$userId, telephone:$telephone,  mess: " . $e->getMessage(), null, 'refer.log');
            if ($i > 3) {
                // false 3 => return false
                return FALSE;
            } else {
                // retry insert
                $i++;
                Mage::log("**Retry= ". $i ."----------", null, 'refer.log');
                return $this->insertPhoneOtplog($userId, $telephone, $i);
            }
        }
        return FALSE;
    }
    //check otp
    public function checkOTP($telephone, $otp) {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $query = "select *, if((expire_otp - now()) > 0, 1,0) as active from fhs_telephone_otp_log where telephone = :telephone and otp_code = :otp_code";
        $var = array("telephone" => $telephone, "otp_code" => $otp);
        $rs = $readConnection->fetchAll($query, $var);

        if (count($rs) !== 0) {
            if ($rs[0]["active"] == 1) {
                return $this->__('OTP is valid');
            } else {
                return $this->__('OTP expire');
            }
        } else {
	    return $this->__('OTP invalid');
        }
    }
    //update telephone
    public function updateTelephone($customer_id, $telephone){
	$result = false;
	try{
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $sql = "update fhs_customer_entity set telephone = '".$telephone."' where entity_id = ".$customer_id.";";
	    $sql .= "update fhs_customer_telephone_log set telephone = '".$telephone."' where customer_id = ".$customer_id.";";
	    
	    $writer->query($sql);
	    $result = true;
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] updateTelephone, message:".$ex->getMessage(), Zend_Log::ERR, "refer.log");
	}
	return $result;
    }
     //insert facebook user
    public function setFaceBookUser($facebook_id, $customer_id){
	$result = false;
	try{
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $sql = "INSERT INTO fhs_facebook_user(facebook_id, customer_id, created_at) 
		    VALUES (:facebook_id, :customer_id, now())
		    ON DUPLICATE KEY UPDATE 
		    customer_id=:customer_id;";
	    $binds = array(
		"facebook_id" => "$facebook_id",
		"customer_id" => "$customer_id"
	    );
	    $writer->query($sql,$binds);
	    $result = true;
	} catch (Exception $ex) {}
        return $result;
		

    }
    
    // register Account
    public function checkPhoneInvalid($telephone, $channel) {
        // handle user spam get otp 
        // max 5 times sent
        $sentOtpSess = Mage::getSingleton('core/session')->getSentOTP();
        if (isset($sentOtpSess) && $sentOtpSess > 5) {
	    $last_sent_time = Mage::getSingleton('core/session')->getSentOTPTime();
	    if(!empty($last_sent_time)){
		$minute_over = Mage::getStoreConfig("customer/otp/reset_opt_time");
		$last_sent_time = date('Y-m-d H:i:s', strtotime("+".$minute_over." minutes", $last_sent_time));
		if($last_sent_time > date('Y-m-d H:i:s', strtotime('+7 hours'))){
		    Mage::log("**checkPhoneInvalid spam: userId: " . Mage::getSingleton('core/session')->getSessionId() . ", telephone:$telephone", null, 'refer.log');
		    return $this->__('Sent OTP over much times');
		}else{
		    $sentOtpSess = 0;
		}
	    }
        }
	Mage::getSingleton('core/session')->setSentOTP($sentOtpSess + 1);
	Mage::getSingleton('core/session')->setSentOTPTime(strtotime('+7 hours'));
	
	$customer_id = Mage::getSingleton('customer/session')->getEntityId();
        
	$telephone_avalible = $this->getTelephoneAvalible($telephone);
	
        // check telephone INVALID
        if (count($telephone_avalible) == 0) {
	    return $this->sendPhoneOTP($telephone, $customer_id, 'checkPhoneInvalid', $channel);
        } else {
            return $this->__('Phone number already exist');
        }
    }
    public function registerAccount($email, $telephone, $password, $order_id, $skin_login = false, &$customer_id = null){
	if(!Mage::registry('is_create_customer')) {Mage::register('is_create_customer', true);}
	
	//create customer
	$customer = Mage::getModel('customer/customer');
		    $customer->setFirstname('')
				->setLastname('')
				->setEmail($email)
				->save();
	// insert couponCode(then use like refercode)
	$customer_id = $customer->getEntityId();
	$ruleId = Mage::getStoreConfig("customerregister/refer/ruleid");
	$salesRule = Mage::getModel('salesrule/rule')->load($ruleId);

	// generate coupon/refer code with rule id
	$generator = Mage::getModel('salesrule/coupon_codegenerator');
	$generator->setLength(6);
	$salesRule->setCouponCodeGenerator($generator);
	$salesRule->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO);
	$coupon = $salesRule->acquireCoupon();
	$coupon->setType(1);
	$coupon->save();
	$couponCode = $coupon->getCode();
	$referCode = $couponCode;

	$entData = array(
	    "telephone" => $telephone,
	    "refer_code" => $referCode,
	    "refer_status" => 1,
	    "refer_rule" => $ruleId
	);
	// update customer_entity
	Mage::log("**activate refer code for : userId:$customer_id, telephone:$telephone", null, 'refer.log');
	$rsUpdate = $this->updateCustomerEntity($customer_id, $entData);
	if ($rsUpdate == "ENTITY_VALUE_INVALID" || $rsUpdate == "UPDATE_ENTITY_FALSE") {
	    Mage::log("**activate refer code for : userId:$customer_id, telephone:$telephone".", ERROR: update customer_entity false", null, 'refer.log');
	    return $this->__('An error occurred, please try again');
	} else {
	    if(Mage::getStoreConfig("customer/refer_code/enable") == 1){
		// add refer code to notification
		$title = 'FAHASA Tặng Bạn Mã Giới Thiệu';
		$message = 'MÃ GIỚI THIỆU FAHASA của bạn là: '.$referCode;
		$url = '/tryout/refer/';
		$this->pushNotification($email, $title, $message, $couponCode, 'coupon', $url, $customer_id);
	    }
	}
	
	//confirm customer
	//$this->insertCustomerTelephoneLog($customer_id, $telephone, "create");
	    
	$customer->setTelephone($telephone);
	// activate account when confirm telephone success
	$customer->setConfirmation(null);
	$customer->save();
	if(!$skin_login){
	    Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
	}
	if(!empty($order_id)){
	    if(Mage::getStoreConfig("customer/register_guest_order/is_active") == 1){
		$last_guest_orders = Mage::getSingleton('customer/session')->getLastGuestOrders();
		if(!empty($last_guest_orders)){
		    if(in_array($order_id, $last_guest_orders, TRUE)){
			$order = Mage::getModel('sales/order')->load($order_id);
			if(!empty($order->getId())){
			    $this->setCustomerOrder($customer_id, $order_id);
			    $shipping_address = $order->getShippingAddress();
			    
			    $first_name = !empty($shipping_address->getFirstname())?trim($shipping_address->getFirstname()):'';
			    $last_name = !empty($shipping_address->getLastname())?trim($shipping_address->getLastname()):'';
			    
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
			    $this->addCustomerAddress($customer_id, $address_data);
			    Mage::getSingleton('customer/session')->unsLastGuestOrders();
			}
		    }
		}
	    }
	}
	
	//set password
	$customer->setPassword($password);
	if(!empty($first_name)){
	    $customer->setFirstname($first_name);
	}
	if(!empty($last_name)){
	    $customer->setLastname($last_name);
	}
	$customer->save();
	
	Mage::dispatchEvent('fhs_success_register_after_save',
	    array('controller' => $this, 'customer' => $customer, 'type' => 'account confirm')
	);
	return "REGISTER_PASS";
    }
    
    //recovery password
    public function checkEmailValidForRecovery($email) {
        // handle user spam get otp 
        // max 5 times sent
        $sentOtpSess = Mage::getSingleton('core/session')->getSentEmailOTP();
        if (isset($sentOtpSess) && $sentOtpSess > 5) {
	    $last_sent_time = Mage::getSingleton('core/session')->getSentEmailOTPTime();
	    if(!empty($last_sent_time)){
		$minute_over = Mage::getStoreConfig("customer/otp/reset_opt_time");
		$last_sent_time = date('Y-m-d H:i:s', strtotime("+".$minute_over." minutes", $last_sent_time));
		if($last_sent_time > date('Y-m-d H:i:s', strtotime('+7 hours'))){
		    Mage::log("**checkEmailValidForRecovery sendEmailOTP spam: SessionId: " . Mage::getSingleton('core/session')->getSessionId() . ", emaul:$email", null, 'refer.log');
		    return $this->__('Sent OTP over much times');
		}else{
		    $sentOtpSess = 0;
		}
	    }
        }
	
	Mage::getSingleton('core/session')->setSentEmailOTP($sentOtpSess + 1);
	Mage::getSingleton('core/session')->setSentEmailOTPTime(strtotime('+7 hours'));
        
	$customer = Mage::getModel("customer/customer")
			->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
			->loadByEmail($email);
	
	if ($customer->getConfirmation() && $customer->isConfirmationRequired()) {
	    return $this->__("The account has not been confirmed");
	}
        // check email VALID
        if ($customer->getEntityId()) {
	    $customer_id = $customer->getEntityId();
	    $customer_name = $customer->getFirstname() . " " . $customer->getLastname();
	    return $this->sendEmailOTP($email, $customer_id, $customer_name, 'checkEmailValid');
        } else {
            return $this->__("Email isn't exist");
        }
    }
    public function checkRecoveryPhoneValid($telephone, $channel) {
        // handle user spam get otp 
        // max 5 times sent
        $sentOtpSess = Mage::getSingleton('core/session')->getSentOTP();
        if (isset($sentOtpSess) && $sentOtpSess > 5) {
	    $last_sent_time = Mage::getSingleton('core/session')->getSentOTPTime();
	    if(!empty($last_sent_time)){
		$minute_over = Mage::getStoreConfig("customer/otp/reset_opt_time");
		$last_sent_time = date('Y-m-d H:i:s', strtotime("+".$minute_over." minutes", $last_sent_time));
		if($last_sent_time > date('Y-m-d H:i:s', strtotime('+7 hours'))){
		    Mage::log("**checkRecoveryPhoneValid spam: userId: " . Mage::getSingleton('core/session')->getSessionId() . ", telephone:$telephone", null, 'refer.log');
		    return $this->__('Sent OTP over much times');
		}else{
		    $sentOtpSess = 0;
		}
	    }
        }
	Mage::getSingleton('core/session')->setSentOTP($sentOtpSess + 1);
	Mage::getSingleton('core/session')->setSentOTPTime(strtotime('+7 hours'));
        
	$telephone_avalible = $this->getTelephoneAvalible($telephone);
	
        // check telephone VALID
        if (count($telephone_avalible) > 0) {
	    return $this->sendPhoneOTP($telephone, '', 'checkRecoveryPhoneValid', $channel);
        } else {
            return $this->__("Phone number isn't exist");
        }
    }
    public function recoveryAccount($username, $password){
	//get customer
	if(is_numeric($username)){
	    $customer = Mage::getModel("customer/customer")
			->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
			->loadByTelephone($username);
	}else{
	    $customer = Mage::getModel("customer/customer")
			->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
			->loadByEmail($username);
	}
	//set password
	$customer->setPassword($password);
	$customer->save();
	
	Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
	
	return "RECOVERY_PASS";
    }
    
    //change phone number
    public function checkChangePhoneValid($telephone, $channel) {
        // handle user spam get otp 
        // max 5 times sent
        $sentOtpSess = Mage::getSingleton('core/session')->getSentOTP();
        if (isset($sentOtpSess) && $sentOtpSess > 5) {
	    $last_sent_time = Mage::getSingleton('core/session')->getSentOTPTime();
	    if(!empty($last_sent_time)){
		$minute_over = Mage::getStoreConfig("customer/otp/reset_opt_time");
		$last_sent_time = date('Y-m-d H:i:s', strtotime("+".$minute_over." minutes", $last_sent_time));
		if($last_sent_time > date('Y-m-d H:i:s', strtotime('+7 hours'))){
		    Mage::log("**checkChangePhoneValid sendOTP spam: SessionId: " . Mage::getSingleton('core/session')->getSessionId() . ", telephone:$telephone", null, 'refer.log');
		    return $this->__('Sent OTP over much times');
		}else{
		    $sentOtpSess = 0;
		}
	    }
        }
	Mage::getSingleton('core/session')->setSentOTP($sentOtpSess + 1);
	Mage::getSingleton('core/session')->setSentOTPTime(strtotime('+7 hours'));
	
	$customer_id = Mage::getSingleton('customer/session')->getEntityId();
	
	$telephone_avalible = $this->getTelephoneAvalible($telephone);
        
        // check telephone INVALID
        if (count($telephone_avalible) == 0) {
	    return $this->sendPhoneOTP($telephone, $customer_id, 'checkChangePhoneValid', $channel);
        } else {
	    $customer = Mage::getSingleton('customer/session')->getCustomer();
	    if($customer->getTelephone() == $telephone && empty($customer->getReferCode())){
		return $this->sendPhoneOTP($telephone, $customer_id, 'checkChangePhoneValid', $channel);
	    }
            return $this->__("Phone number already exist");
        }
    }
    public function changePhoneAccount($telephone){
	//get customer
	$customer = Mage::getSingleton('customer/session')->getCustomer();
	$customer_id = $customer->getEntityId();
	$customer_telephone = $customer->getTelephone();
	if(!$this->updateTelephone($customer_id, $telephone)){
	    return $this->__('An error occurred, please try again');
	}
	
	if(empty($customer->getTelephone()) || empty($customer->getReferCode())){
	    if($this->updateReferCode($customer)){
		$donateFpoint = $this->getDonateFpoint($customer, ['telephone']);
		if($donateFpoint['telephone'] > 0){
		    $this->donateFpointUpdateInfoAccount($customer_id, $donateFpoint['telephone']);
		}
	    }
	}
	$customer->setTelephone($telephone);
	Mage::dispatchEvent('customer_save_after', array('customer'  => $customer));
	Mage::log("**changePhone telephone customer_id:$customer_id = telephone:$customer_telephone -> $telephone, ERROR: update customer_entity false", null, 'refer.log');
	return "CHANGE_PASS";
    }
    
    //check and send otp email Send OTP
    public function checkEmailValidForChange($email) {
        // handle user spam get otp 
        // max 5 times sent
        $sentOtpSess = Mage::getSingleton('core/session')->getSentEmailOTP();
        if (isset($sentOtpSess) && $sentOtpSess > 5) {
	    $last_sent_time = Mage::getSingleton('core/session')->getSentEmailOTPTime();
	    if(!empty($last_sent_time)){
		$minute_over = Mage::getStoreConfig("customer/otp/reset_opt_time");
		$last_sent_time = date('Y-m-d H:i:s', strtotime("+".$minute_over." minutes", $last_sent_time));
		if($last_sent_time > date('Y-m-d H:i:s', strtotime('+7 hours'))){
		    Mage::log("**checkEmailValid sendEmailOTP spam: SessionId: " . Mage::getSingleton('core/session')->getSessionId() . ", emaul:$email", null, 'refer.log');
		    return $this->__('Sent OTP over much times');
		}else{
		    $sentOtpSess = 0;
		}
	    }
        }
	Mage::getSingleton('core/session')->setSentEmailOTP($sentOtpSess + 1);
	Mage::getSingleton('core/session')->setSentEmailOTPTime(strtotime('+7 hours'));
        
	$email_avalible = $this->getEmailAvalible($email);
	
        // check email VALID
        if (count($email_avalible) == 0) {
	    $customer = Mage::getSingleton('customer/session')->getCustomer();
	    $customer_id = $customer->getEntityId();
	    $customer_name = $customer->getFirstname() . " " . $customer->getLastname();
	    return $this->sendEmailOTP($email, $customer_id, $customer_name, 'checkEmailValid');
        } else {
            return $this->__("Email already exist");
        }
    }
    public function changeEmailAccount($email){
	//get customer
	$customer = Mage::getSingleton('customer/session')->getCustomer();
	$customer_id = $customer->getEntityId();
	
	$entData = array("email" => $email);
	// update customer_entity
	Mage::log("**change email userId:".$customer_id." = email:".$customer->getRealEmail()." -> ".$email, null, 'refer.log');
	$rsUpdate = $this->updateCustomerEntity($customer_id, $entData);
	
	if ($rsUpdate == "ENTITY_VALUE_INVALID" || $rsUpdate == "UPDATE_ENTITY_FALSE") {
	    Mage::log("**change email userId:".$customer_id." = email:".$customer->getRealEmail()." -> ".$email.", ERROR: update customer_entity false", null, 'refer.log');
	    return $this->__('An error occurred, please try again');
	}
        
        if (empty($customer->getRealEmail()))
        {
	    $donateFpoint = $this->getDonateFpoint($customer, ['email']);
	    if($donateFpoint['email'] > 0){
		$this->donateFpointUpdateInfoAccount($customer_id, $donateFpoint['email']);
	    }
        }
	
	$customer->setEmail($email);
	Mage::dispatchEvent('customer_save_after', array('customer'  => $customer));
	return "CHANGE_PASS";
    }
    public function donateFpointUpdateInfoAccount($customer_id, $fpoint){
	$noti_title = "Bạn vừa được tặng F-Point";
	$noti_content = "Chúc mừng bạn vừa được tặng ".number_format($fpoint, 0, ",", ".")." F-Point, khi cập nhật thông tin tài khoản";
	$noti_page_value = "";
	$noti_url = "";
	$action_purchase = "Donate_fpoint";
	$description_purchase = "Account: donate for update info";
	Mage::helper("fpointstorev2/data")->donateFpoint($customer_id, $fpoint, $noti_title, $noti_content, $noti_page_value, $noti_url, $action_purchase, $description_purchase);
    }
    
    //Login Facebook
    public function loginfb($facebook_id, $email){
        
        Mage::log("authen facebook " . $facebook_id . " " . $email, null, "debug_facebook.log");
	try{
	    $customer = Mage::getModel('customer/customer')->loadByFacebookId($facebook_id);

	    if(empty($customer->getEntityId())){
	       if(!empty($email)){
		   $customer = Mage::getModel("customer/customer")
			->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
			->loadByEmail($email);
		    if(empty($customer->getEntityId())){
			return '';
		    }else{
			if (!(($customer->getConfirmation() && $customer->isConfirmationRequired()))) {
			    $this->setFaceBookUser($facebook_id, $customer->getEntityId());
			    Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
			    return 'LOGIN_PASS';
			}else{
			    return $this->__("The account has not been confirmed");
			}
		    }
	       }else{
		    return '';
	       }
	    }else{
		if (($customer->getConfirmation() && $customer->isConfirmationRequired())) {
		    return $this->__("The account has not been confirmed");
		}else{
		    Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
		    return 'LOGIN_PASS';
		}
	    }
	} catch (Exception $ex) {}
	return $this->__('An error occurred, please try again');
    }
    
    public function sendOTPPhoneFb($telephone, $channel) {
        // handle user spam get otp 
        // max 5 times sent
        $sentOtpSess = Mage::getSingleton('core/session')->getSentOTP();
        if (isset($sentOtpSess) && $sentOtpSess > 5) {
	    $last_sent_time = Mage::getSingleton('core/session')->getSentOTPTime();
	    if(!empty($last_sent_time)){
		$minute_over = Mage::getStoreConfig("customer/otp/reset_opt_time");
		$last_sent_time = date('Y-m-d H:i:s', strtotime("+".$minute_over." minutes", $last_sent_time));
		if($last_sent_time > date('Y-m-d H:i:s', strtotime('+7 hours'))){
		    Mage::log("**sendOTPPhoneFb sendOTP spam: SessionId: " . Mage::getSingleton('core/session')->getSessionId() . ", telephone:$telephone", null, 'refer.log');
		    return $this->__('Sent OTP over much times');
		}else{
		    $sentOtpSess = 0;
		}
	    }
        }
	Mage::getSingleton('core/session')->setSentOTP($sentOtpSess + 1);
	Mage::getSingleton('core/session')->setSentOTPTime(strtotime('+7 hours'));
	
	return $this->sendPhoneOTP($telephone, '', 'checkChangePhoneValid', $channel);
    }
    public function registerfb($facebook_id, $telephone, $email = '', $firstname = '', $lastname = '', $type){
	if(!Mage::registry('is_create_customer')) {Mage::register('is_create_customer', true);}
	
	$customer = Mage::getModel("customer/customer")
			->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
			->loadByTelephone($telephone);
	
	if(empty($customer->getEntityId())){
	    if(!empty($email)){
		$customer = Mage::getModel("customer/customer")
			->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
			->loadByEmail($email);
	    }
	}
	
	if(!empty($customer->getEntityId())){
            if ($customer->getConfirmation() && $customer->isConfirmationRequired()){
                $customer->setConfirmation(null);
                $customer->save();
            }
            
            if ($type === 'facebook')
            {
                //insert fb user
                if ($this->setFaceBookUser($facebook_id, $customer->getEntityId()))
                {
                    Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
                    return "REGISTER_PASS";
                }
                else
                {
                    return $this->__('An error occurred, please try again');
                }
            }
            else if ($type === 'apple')
            {
                //inser fhs_apple_user
                //$facebok_id: is apple_id
                if (Mage::helper('fahasa_customer/applelogin')->setAppleUser($facebook_id, $customer->getEntityId()))
                {
                    Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
                    return "REGISTER_PASS";
                }
                else
                {
                    return $this->__('An error occurred, please try again');
                }
            }
        }else{
	    //create new account for fb
            if(empty($email)){
		$email = $this->getEmailInvalid($telephone);
	    }
	    if(empty($email)){
		return $this->__('An error occurred, please try again');
	    }
	    $customer = Mage::getModel('customer/customer');
			$customer->setFirstname($firstname)
				    ->setLastname($lastname)
				    ->setEmail($email)
				    ->save();
	}
	
	// insert couponCode(then use like refercode)
	$customer_id = $customer->getEntityId();
	$ruleId = Mage::getStoreConfig("customerregister/refer/ruleid");
	$salesRule = Mage::getModel('salesrule/rule')->load($ruleId);

	// generate coupon/refer code with rule id
	$generator = Mage::getModel('salesrule/coupon_codegenerator');
	$generator->setLength(6);
	$salesRule->setCouponCodeGenerator($generator);
	$salesRule->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO);
	$coupon = $salesRule->acquireCoupon();
	$coupon->setType(1);
	$coupon->save();
	$couponCode = $coupon->getCode();
	$referCode = $couponCode;

	$entData = array(
	    "telephone" => $telephone,
	    "refer_code" => $referCode,
	    "refer_status" => 1,
	    "refer_rule" => $ruleId
	);
	// update customer_entity
	Mage::log("**activate refer code for : userId:$customer_id, telephone:$telephone", null, 'refer.log');
	$rsUpdate = $this->updateCustomerEntity($customer_id, $entData);
	if ($rsUpdate == "ENTITY_VALUE_INVALID" || $rsUpdate == "UPDATE_ENTITY_FALSE") {
	    Mage::log("**activate refer code for : userId:$customer_id, telephone:$telephone".", ERROR: update customer_entity false", null, 'refer.log');
	    return $this->__('An error occurred, please try again');
	} else {
	    if(Mage::getStoreConfig("customer/refer_code/enable") == 1){
		// add refer code to notification
		$title = 'FAHASA Tặng Bạn Mã Giới Thiệu';
		$message = 'MÃ GIỚI THIỆU FAHASA của bạn là: '.$referCode;
		$url = '/tryout/refer/';
		$this->pushNotification($email, $title, $message, $couponCode, 'coupon', $url, $customer_id);
	    }
	}

	//confirm customer
	//$this->insertCustomerTelephoneLog($customer_id, $telephone, "create");

        if ($type === 'facebook')
        {
            //insert fb user
            $this->setFaceBookUser($facebook_id, $customer_id);
        }
        else if ($type === 'apple')
        {
            //inser fhs_apple_user
            //$facebok_id: is apple_id
            Mage::helper('fahasa_customer/applelogin')->setAppleUser($facebook_id, $customer_id);
        }

	$customer->setTelephone($telephone);
        // activate account when confirm telephone success
	$customer->setConfirmation(null);
	$customer->save();
	Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
        
	//set password
	$password = $customer->generatePassword(8);
	$customer->setPassword($password);
	$customer->save();
	

	Mage::dispatchEvent('fhs_success_register_after_save',
	    array('controller' => $this, 'customer' => $customer, 'type' => 'account confirm')
	);
	return "REGISTER_PASS";
    }
    
    public function createNewAccountForApple($email, $appleId)
    {
        //create new account for fb
        if (empty($email))
        {
            $email = $this->getEmailInvalidFromAppleId($appleId);
        }
        if (empty($email))
        {
            return $this->__('An error occurred, please try again');
        }
        $customer = Mage::getModel('customer/customer');
        $customer->setEmail($email)
                ->save();
        return $customer;
    }

    //register Apple review: telephone is null -> set email = notverify_appleId@fahasa.com
    public function registerApple($facebook_id, $appleId, $email = '', $firstname = '', $lastname = '', $type)
    {
	if(!Mage::registry('is_create_customer')) {Mage::register('is_create_customer', true);}
        if (!empty($email))
        {
            $customer = Mage::getModel("customer/customer")
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                    ->loadByEmail($email);
            if (!empty($customer->getEntityId()))
            {
                if ($customer->getConfirmation() && $customer->isConfirmationRequired())
                {
                    $customer->setConfirmation(null);
                    $customer->save();
                }

                if ($this->setAppleUser($facebook_id, $customer->getEntityId()))
                {
                    Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
                    return "REGISTER_PASS";
                }
                else
                {
                    return $this->__('An error occurred, please try again');
                }
            }
            else
            {
                $customer = $this->createNewAccountForApple($email, $appleId);
            }
        }
        else
        {
            $customer = $this->createNewAccountForApple($email, $appleId);
        }

        //do not insert couponCode(then use like refercode)
        $customer_id = $customer->getEntityId();

        //inser fhs_apple_user
        //$facebok_id: is apple_id
        Mage::helper('fahasa_customer/applelogin')->setAppleUser($facebook_id, $customer_id);

        // activate account when confirm telephone success
        $customer->setConfirmation(null);
        $customer->save();
        Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);

        //set password
        $password = $customer->generatePassword(8);
        $customer->setPassword($password);
        $customer->save();

        Mage::dispatchEvent('fhs_success_register_after_save', array('controller' => $this, 'customer' => $customer, 'type' => 'account confirm')
        );
        return "REGISTER_PASS";
    }

    public function getEmailInvalidFromAppleId($appleId, $time = 0){
	//over load time
	if($time > 10){return "";}
	
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	$id = rand(99,999);
	$email = "notverify_".$appleId."_".$id.$time."_apple@fahasa.com";
	$sql = "select entity_id from fhs_customer_entity where email = '".$email."' limit 1;";
	$customer_email = $reader->fetchRow($sql);
	if($customer_email['entity_id']){
	    $time++;
	    $email = $this->getEmailInvalid($appleId, $time);
	}
	return $email;
    }
    
    
    public function registerAccountByPhone($phone, $otp, $password, $order_id = ''){
	$minLength = (int)Mage::getStoreConfig('customer/password/min_password_length');
	$result = array();
	$result['success'] = false;
	$result['message'] = '';
	
	$order_id = trim($order_id);
	$phone = trim($phone);
	$otp = trim($otp);
	$password = str_replace(array('\'', '%', '\\', '/', ' '), '', $password);
	
	if(empty($phone) || !is_numeric($phone)){
	    $result['message'] = $this->__('Telephone invalid');
	    goto end_check;
	}elseif(empty($otp)){
	    $result['message'] = $this->__('OTP invalid');
	    goto end_check;
	}elseif(empty($password)){
	    $result['message'] = $this->__("Password can't empty");
	    goto end_check;
	}elseif(strlen($password) < $minLength){
	    $result['message'] = $this->__("Password must be %s characters or more!", $minLength);
	    goto end_check;
	}elseif(strlen($password) > 30){
	    $result['message'] = $this->__("Password must be 30 characters or less!");
	    goto end_check;
	}
	
	$otp_mess = $this->checkOTP($phone, $otp);
	if($otp_mess != $this->__('OTP is valid')){
	    $result['message'] = $otp_mess;
	    goto end_check;
	}
	$email = $this->getEmailInvalid($phone);
	if(empty($email)){
	    $result['message'] = $this->__('An error occurred, please try again');
	    goto end_check;
	}
	
	$telephone_avalible = $this->getTelephoneAvalible($phone);
        if(count($telephone_avalible) > 0) {
	    $result['message'] = $this->__('Phone number already exist');
	    goto end_check;
	}
	
	end_check:
	//Check and return error message
	if($result['message']){
            return $result;
	}
	
	//Register account
	$result['message'] = $this->registerAccount($email, $phone, $password, $order_id);
	
	if($result['message'] == 'REGISTER_PASS'){
	    $result['success'] = true;
	}
	
        return $result;
        
    }
    
    
    public function checkPhoneOTP($phone, $otp) {
        $result = array();
        $result['success'] = false;
        $result['message'] = '';
        if (empty($phone) || !is_numeric($phone) || !((strlen($phone) >= 10) && (strlen($phone) <= 11)))
        {
            $result['message'] = $this->__("Phone number invalid");
            return $result;
        }
        if (empty($otp) || strlen($otp) != 6)
        {
            $result['message'] = $this->__("OTP invalid");
            return $result;
        }
        $result['message'] = $this->checkOTP($phone, $otp);
        if ($result['message'] == $this->__('OTP is valid'))
        {
            $result['success'] = true;
        }

        return $result;
    }
    
      public function changeEmailAccountByOTP($email, $otp)
    {
	$result = array();
	$result['success'] = false;
	$result['message'] = '';
	
	$email = trim($email);
	$otp = trim($otp);
	
	if(empty($email) || (strlen($email) >= 200) || !filter_var($email,FILTER_VALIDATE_EMAIL)){
	    $result['message'] = $this->__('Email invalid');
	    goto end_check;
	}elseif(empty($otp)){
	    $result['message'] = $this->__('OTP invalid');
	    goto end_check;
	}
	
	$otp_mess = $this->checkOTP($email, $otp);
	if($otp_mess != $this->__('OTP is valid')){
	    $result['message'] = $otp_mess;
	    goto end_check;
	}
	
	$email_avalible = $this->getEmailAvalible($email);
        if(count($email_avalible) > 0) {
	    $result['message'] = $this->__("Email already exist");
	    goto end_check;
	}
	
	end_check:
	//Check and return error message
	if($result['message']){
            return $result;
	}
	
	//Change phone account
	$result['message'] = $this->changeEmailAccount($email);
	
	if($result['message'] == 'CHANGE_PASS'){
	    $result['success'] = true;
	}
	
        return $result;
    }

    public function checkEmailOTP($email, $otp){
	$result = array();
	$result['success'] = false;
	$result['message'] = '';
	if(empty($email) || (strlen($email) > 200)){
	    $result['message'] = $this->__("Email invalid");
            return $result;
	}
	if(empty($otp) || strlen($otp) != 6){
	    $result['message'] = $this->__("OTP invalid");
            return $result;
	}
	$result['message'] = $this->checkOTP($email, $otp);
	if($result['message'] == $this->__('OTP is valid')){
	    $result['success'] = true;
	}
	
	  return $result;
    }
    
    public function checkChangePhone($phone){
	$result = array();
	$result['success'] = false;
	$result['message'] = '';
	if(empty($phone) || !is_numeric($phone) || !((strlen($phone) >= 10) && (strlen($phone) <= 11))){
	    $result['message'] = $this->__("Phone number invalid");
	    return $result;
	}
	$channel = "web";
	$result['message'] = $this->checkChangePhoneValid($phone, $channel);
	if($result['message'] == $this->__('OTP sent')){
	    $result['success'] = true;
	}
	
	return $result;
    }
    
     public function checkPhone($phone, $channel){
	$result = array();
	$result['success'] = false;
	$result['message'] = '';
        if (empty($phone) || !is_numeric($phone) || !((strlen($phone) >= 10) && (strlen($phone) <= 11))){
            $result['message'] = $this->__("Phone number invalid");
            return $result;
        }
        
        $result['message'] = $this->checkPhoneInvalid($phone, $channel);
	if($result['message'] == $this->__('OTP sent')){
	    $result['success'] = true;
	}
	
	return $result;
    }
    
    
     public function changePhoneAccountByOtp($phone, $otp)
    {
 	$result = array();
	$result['success'] = false;
	$result['message'] = '';
	
	$phone = trim($phone);
	$otp = trim($otp);
	
	if((empty($phone) || !is_numeric($phone)) || !((strlen($phone) >= 10) && (strlen($phone) <= 11))){
	    $result['message'] = $this->__('Telephone invalid');
	    goto end_check;
	}elseif(empty($otp)){
	    $result['message'] = $this->__('OTP invalid');
	    goto end_check;
	}
	
	$otp_mess = $this->checkOTP($phone, $otp);
	if($otp_mess != $this->__('OTP is valid')){
	    $result['message'] = $otp_mess;
	    goto end_check;
	}
	
	$telephone_avalible = $this->getTelephoneAvalible($phone);
        if(count($telephone_avalible) > 0) {
	    $customer = Mage::getSingleton('customer/session')->getCustomer();
	    if(!($customer->getTelephone() == $phone && empty($customer->getReferCode()))){
		$result['message'] = $this->__("Phone number already exist");
		goto end_check;
	    }
	}
	
	end_check:
	//Check and return error message
	if($result['message']){
	   return $result;
	}
	
	//Change phone account
	$result['message'] = $this->changePhoneAccount($phone);
	
	if($result['message'] == 'CHANGE_PASS'){
	    $result['success'] = true;
	}
	
	return $result;
    }
 
    //Recovery password
    public function checkRecoveryPassword($username){
	$channel = "web";
	$result = array();
	$result['success'] = false;
	$result['message'] = '';
	$username = trim($username);
	
	$is_phone = is_numeric($username);
	
	if($is_phone){
	    if(empty($username) || !((strlen($username) >= 10) && (strlen($username) <= 11))){
		$result['message'] = $this->__("Phone number invalid");
		goto end_check;
	    }
	    $telephone_avalible = $this->getTelephoneAvalible($username);
	    if(count($telephone_avalible) <= 0) {
		$result['message'] = $this->__("Phone number isn't exist");
		goto end_check;
	    }
	}else{
	    if(empty($username) || !filter_var($username,FILTER_VALIDATE_EMAIL)){
		$result['message'] = $this->__("Email invalid");
		goto end_check;
	    }
	    $email_avalible = $this->getEmailAvalible($username);
	    if(count($email_avalible) <= 0) {
		$result['message'] = $this->__("Email isn't exist");
		goto end_check;
	    }
	}
	
	end_check:
	if($result['message']){
            return $result;
	}
	
	if($is_phone){
	    $result['message'] = $this->checkRecoveryPhoneValid($username, $channel);
	}else{
	    $result['message'] = $this->checkEmailValidForRecovery($username);
	}
	
	if($result['message'] == $this->__('OTP sent')){
	    $result['success'] = true;
	}
	
	return $result;
    }
    
    
      public function recoveryAccountByOtp($username, $otp, $password){
	$minLength = (int)Mage::getStoreConfig('customer/password/min_password_length');
	$result = array();
	$result['success'] = false;
	$result['message'] = '';
	
	$username = trim($username);
	$otp = trim($otp);
	$password = str_replace(array('\'', '%', '\\', '/', ' '), '', $password);
	
	$is_phone = is_numeric($username);
	
	if(empty($otp)){
	    $result['message'] = $this->__('OTP invalid');
	    goto end_check;
	}elseif(empty($password)){
	    $result['message'] = $this->__("Password can't empty");
	    goto end_check;
	}elseif(strlen($password) < $minLength){
	    $result['message'] = $this->__("Password must be %s characters or more!", $minLength);
	    goto end_check;
	}elseif(strlen($password) > 30){
	    $result['message'] = $this->__("Password must be 30 characters or less!");
	    goto end_check;
	}
	
	if($is_phone){
	    if(empty($username)){
		$result['message'] = $this->__('Telephone invalid');
		goto end_check;
	    }
	    $telephone_avalible = $this->getTelephoneAvalible($username);
	    if(count($telephone_avalible) <= 0) {
		$result['message'] = $this->__("Phone number isn't exist");
		goto end_check;
	    }
	}else{
	    if(empty($username) || !filter_var($username,FILTER_VALIDATE_EMAIL)){
		$result['message'] = $this->__("Email invalid");
		goto end_check;
	    }
	    $email_avalible = $this->getEmailAvalible($username);
	    if(count($email_avalible) <= 0) {
		$result['message'] = $this->__("Email isn't exist");
		goto end_check;
	    }
	}
	
	$otp_mess = $this->checkOTP($username, $otp);
	if($otp_mess != $this->__('OTP is valid')){
	    $result['message'] = $otp_mess;
	    goto end_check;
	}
	
	
	end_check:
	//Check and return error message
	if($result['message']){
            return $result;
	}
	
	//Recovery password account
	$result['message'] = $this->recoveryAccount($username, $password);
	
	if($result['message'] == 'RECOVERY_PASS'){
	    $result['success'] = true;
	}
	
	return $result;
    }
    
    public function loginFacebookByAccessToken($accessToken){
	$result = array();
	$result['success'] = false;
	$result['message'] = '';
	$result['logined'] = false;
	
	
	$accessToken = trim($accessToken);
	Mage::log("Begin login fb " . $accessToken, null, "debug_facebook.log");
	if(empty($accessToken)){
	    $result['message'] = $this->__('Login failed');
	    return $result;
	}
	
	$reponseFbTokenValidate = $this->authenticateFB($accessToken);
        Mage::log("response  login fb " . $accessToken . print_r($reponseFbTokenValidate, true), null, "debug_facebook.log");
        $data_ = json_decode($reponseFbTokenValidate);
	Mage::log("response  login fb data " . $accessToken . print_r($data_, true), null, "debug_facebook.log");
	if (empty($data_) || $data_->error != null) {
	    $result['message'] = $this->__('Login failed');
        } else {
            $facebookId = $data_->id;
	    $email = $data_->email;
	    
	    $result['message'] = $this->loginfb($facebookId, $email);
	
	    if($result['message'] != $this->__('An error occurred, please try again')){
		$result['success'] = true;
		if($result['message'] == 'LOGIN_PASS'){
		    $result['logined'] = true;
		}
	    }
	}
	
	return $result;
    }
    
     //facebook
    public function authenticateFB($accessToken){
        $url = "https://graph.facebook.com/me?fields=id,first_name,last_name,email&access_token=" . $accessToken; 
        Mage::log("url " . $url, null, "debug_facebook.log");
	$timeout = 5;
	$response = null;
	try{
	    $curl = curl_init();
	    // Set some options - we are passing in a useragent too here
	    curl_setopt_array($curl, array(
		 CURLOPT_RETURNTRANSFER => 1,
		 CURLOPT_URL => $url,
		 CURLOPT_CONNECTTIMEOUT => $timeout
	    ));
	    // Send the request & save response to $resp
	    $response = curl_exec($curl);
            Mage::log("curl data from fb url " . print_r($response, true), null, "debug_facebook.log");
	    // Check if any error occurred
	    if(curl_errno($ch))
	    {
		Mage::log("login fb error msg:".curl_error($ch), null, 'loginfb.log');
	    }
	    //Close request to clear up some resources
	    curl_close($curl);
	} catch (Exception $ex) {}
        return $response;
    }
    
    public function checkPhoneConfirm($phone, $channel){
        $result = array();
        $result['success'] = false;
        $result['message'] = '';
        if (empty($phone) || !is_numeric($phone) || !((strlen($phone) >= 10) && (strlen($phone) <= 11)))
        {
            $result['message'] = $this->__("Phone number invalid");
            return $result;
        }
        $result['message'] = $this->sendOTPPhoneFb($phone, $channel);
        if ($result['message'] == $this->__('OTP sent'))
        {
            $result['success'] = true;
        }

        return $result;
    }

    public function registerFacebookAccount($accessToken, $phone, $otp){
	$minLength = (int)Mage::getStoreConfig('customer/password/min_password_length');
	$result = array();
	$result['success'] = false;
	$result['message'] = '';
	
	$accessToken = trim($accessToken);
	$phone = trim($phone);
	$otp = trim($otp);
	
	if(empty($phone) || !is_numeric($phone)){
	    $result['message'] = $this->__('Telephone invalid');
	    goto end_check;
	}elseif(empty($otp)){
	    $result['message'] = $this->__('OTP invalid');
	    goto end_check;
	}elseif(empty($accessToken)){
	    $result['message'] = $this->__("Login failed");
	    goto end_check;
	}
	
	$otp_mess = $this->checkOTP($phone, $otp);
	if($otp_mess != $this->__('OTP is valid')){
	    $result['message'] = $otp_mess;
	    goto end_check;
	}
	
	$reponseFbTokenValidate = $this->authenticateFB($accessToken);
        $data_ = json_decode($reponseFbTokenValidate);
	
	end_check:
	//Check and return error message
	if($result['message']){
	   return $result;
	}
	
	if ($data_->error != null) {
	    $result['message'] = $this->__('Login failed');
        } else {
            $facebookId = $data_->id;
	    $email = $data_->email;
	    $firstname = $data_->first_name;
	    $lastname = $data_->last_name;
	    
	    //Register account fb
	    $result['message'] = $this->registerfb($facebookId, $phone, $email, $firstname, $lastname, "facebook");

	    if($result['message'] == 'REGISTER_PASS'){
		$result['success'] = true;
	    }
	}
	
	return $result;
    }
    
    public function getDonateFpoint($customer, $donate_array = null){
        $donateFpoint = [];
	$donateFpoint['telephone'] = 0;
	$donateFpoint['email'] = 0;
	$donateFpoint['dob'] = 0;
	$donateFpoint['gender'] = 0;
        if (Mage::getStoreConfig("customerregister/donate/enable"))
        {
            $customer_from_date = date('Y-m-d H:i:s', strtotime(Mage::getStoreConfig("customerregister/donate/from_date")));
            $customer_created_at = date('Y-m-d H:i:s', strtotime('+7 hour', strtotime($customer->getCreatedAt())));
            if ($customer_created_at >= $customer_from_date){
		if(empty($get_val)){
		    $donateFpoint['telephone'] = Mage::getStoreConfig("customerregister/donate/telephone");
		    $donateFpoint['email'] = Mage::getStoreConfig("customerregister/donate/email");
		    $donateFpoint['dob'] = Mage::getStoreConfig("customerregister/donate/dob");
		    $donateFpoint['gender'] = Mage::getStoreConfig("customerregister/donate/gender");
		}else{
		    if(in_array('telephone', $donate_array, TRUE)){
			$donateFpoint['telephone'] = Mage::getStoreConfig("customerregister/donate/telephone");
		    }
		    if(in_array('email', $donate_array, TRUE)){
			$donateFpoint['email'] = Mage::getStoreConfig("customerregister/donate/email");
		    }
		    if(in_array('dob', $donate_array, TRUE)){
			$donateFpoint['dob'] = Mage::getStoreConfig("customerregister/donate/dob");
		    }
		    if(in_array('gender', $donate_array, TRUE)){
			$donateFpoint['gender'] = Mage::getStoreConfig("customerregister/donate/gender");
		    }
		}
            }
        }
        return $donateFpoint;
    }
    
    public function updateNetcoreContact($isSessionSave = false){
	$result = "";
	try {
	    if(Mage::getStoreConfig('netcore/general/enable') == 1){
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		if(!empty($customer->getRealEmail()) && !empty($customer->getFirstname()))
		{
		    $result = "smartech('contact', '', {
			'pk^email': '".$customer->getRealEmail()."',
			'mobile': '".$customer->getTelephone()."',
			'FIRST_NAME': '".$customer->getFirstname()."',
			'LAST_NAME': '".$customer->getLastname()."',
			'GENDER': '".$customer->getGender()."',
			'DATE_OF_BIRTH': '".$customer->getDob()."',
			'NON_MEMBER': ''
		    });";
		    if($isSessionSave){
			Mage::getSingleton('customer/session')->setNetcoreContact($result);
		    }
		}
	    }
	} catch (Exception $ex) {}
	return $result;
    }
    
    public function updateReferCode($customer){
	$result = false;
	try{
	    // insert couponCode(then use like refercode)
	    $customer_id = $customer->getEntityId();
	    $ruleId = Mage::getStoreConfig("customerregister/refer/ruleid");
	    $salesRule = Mage::getModel('salesrule/rule')->load($ruleId);

	    // generate coupon/refer code with rule id
	    $generator = Mage::getModel('salesrule/coupon_codegenerator');
	    $generator->setLength(6);
	    $salesRule->setCouponCodeGenerator($generator);
	    $salesRule->setCouponType(Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO);
	    $coupon = $salesRule->acquireCoupon();
	    $coupon->setType(1);
	    $coupon->save();
	    $couponCode = $coupon->getCode();
	    $referCode = $couponCode;

	    $entData = array(
		"refer_code" => $referCode,
		"refer_status" => 1,
		"refer_rule" => $ruleId
	    );
	    // update customer_entity
	    Mage::log("**update ReferCode for : userId:$customer_id", null, 'refer.log');
	    $rsUpdate = $this->updateCustomerEntity($customer_id, $entData);
	    if ($rsUpdate == "ENTITY_VALUE_INVALID" || $rsUpdate == "UPDATE_ENTITY_FALSE") {
		Mage::log("**activate refer code for : userId:$customer_id, telephone:$telephone".", ERROR: update customer_entity false", null, 'refer.log');
		return $result;
	    } else {
		$result = true;
		if(Mage::getStoreConfig("customer/refer_code/enable") == 1){
		    // add refer code to notification
		    $title = 'FAHASA Tặng Bạn Mã Giới Thiệu';
		    $message = 'MÃ GIỚI THIỆU FAHASA của bạn là: '.$referCode;
		    $url = '/tryout/refer/';
		    $this->pushNotification('', $title, $message, $couponCode, 'coupon', $url, $customer_id);
		}
	    }
	} catch (Exception $ex) {}
	return $result;
    }
    
    public function setCustomerOrder($customer_id, $order_id){
	$result = false;
        try {
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $query = "update fhs_sales_flat_order set customer_id = ".$customer_id." where customer_id is null and entity_id = $order_id;";
            $writeConnection->query($query);
            $result = true;
	    Mage::log("[SUCCESS] setCustomerOrder customer_id:".$customer_id.", order_id:".$order_id, null, 'refer.log');
        } catch (Exception $e) {
	    Mage::log("[ERROR] setCustomerOrder customer_id:".$customer_id.", order_id:".$order_id.",  mess: " . $e->getMessage(), null, 'refer.log');
        }
        return $result;
    }
    
    public function addCustomerAddress($customer_id, $address_data){
	$result = false;
	try{
	    $address  = Mage::getModel('customer/address');
	    foreach($address_data as $key=>$item){
		$address->setData($key, $item);
	    }
	    $address->setCustomerId($customer_id)
		->setIsDefaultBilling(true)
		->setIsDefaultShipping(true);
	    $address->save();
	    $result = true;
	} catch (Exception $ex) {}
	return $result;
    }
    
    public function pushNotificationThroughRest($email, $title, $message, $pageType, $pageValue, $url, $urlMobile)
    {
        $urlServer = "https://fahasa.com:88/pushNotificationMobile";
        $hashKey = "824b35b38e2e4fc0f9e88070cbcecd64ccaa592c8e11f9f80413aea36ea6ab84";
        $postHelper = Mage::helper('cancelorder');
        $json = array(
            "email" => $email,
            "hashKey" => $hashKey,
            "title" => $title,
            "message" => $message,
            "pageType" => $pageType,
            "pageValue" => $pageValue,
            "url" => $url,
            "scheduleTime" => '',
            "urlMobile" => $urlMobile,
        );
        
        $postHelper->httpPost($urlServer, json_encode($json));
    }
    
    public function sendNotiToUpdateEmail($customer){
        //send noti update email
        $title = 'Cập nhật email ngay để nhận quà từ Fahasa.com!';
        $donateFpoint = $this->getDonateFpoint($customer, ['email'])['email'];
        if ($donateFpoint && $donateFpoint > 0){
            $message = 'Bạn vừa đăng kí tài khoản tại Fahasa? Hãy cập nhật email ngay để nhận ' . $donateFpoint . ' F-Point! '
                    . 'Click ngay vào đây để cập nhật. '
                    . 'Đừng quên tiếp tục tham gia mua sắm để nhận được những ưu đãi dành riêng cho khách hàng mới tại Fahasa.com.';
        } else {
            $message = 'Bạn vừa đăng kí tài khoản tại Fahasa? Hãy cập nhật email ngay để nhận được các thông báo quà tặng dành cho khách hàng mới! '
                    . 'Click ngay vào đây để cập nhật. '
                    . 'Đừng quên tiếp tục tham gia mua sắm để nhận được những ưu đãi dành riêng cho khách hàng mới tại Fahasa.com.';
        }
        $url = '/customer/account/edit/';
        $urlMobile = "userDetail2";
        $this->pushNotificationThroughRest($customer->getEmail(), $title, $message, '', '', $url, $urlMobile);
    }

    public function getCustomerAddressList(){
        $resut = [];
        $resut['success'] = true;
        $resut['address_list'] = null;
	if (Mage::getSingleton('customer/session')->isLoggedIn()){
	    $customer = Mage::getSingleton('customer/session')->getCustomer();
	    if(count($customer->getAddresses()) > 0){
		$address_list = [];
		$addresses = $customer->getAddresses();
		foreach ($addresses as $address) {
		    $fullname = ($address->getLastname()?$address->getLastname():'').(($address->getLastname() && $address->getFirstname()?' '.$address->getFirstname():($address->getLastname()?$address->getFirstname():'')));
		    $address_str = ($address->getStreet()[0]?$address->getStreet()[0]:'')
			    .($address->getWard()?', '.$address->getWard():'')
			    .($address->getCity()?', '.$address->getCity():'')
			    .($address->getRegion()?', '.$address->getRegion():'')
			    .($address->getPostcode()?', '.$address->getPostcode():'')
			    .($address->getCountryId()?', '.$address->getCountryId():'');
		    $address_list[$address->getId()] = array(
			'value'=>$address->getId(),
			'label'=>$address->format('oneline'),
			'fullname'=>$fullname,
			'firstname'=>$address->getFirstname(),
			'lastname'=>$address->getLastname(),
			'address'=>$address_str,
			'region'=>$address->getRegion(),
			'telephone'=>$address->getTelephone(),
			'country_id'=>$address->getCountryId(),
			'region_id'=>$address->getRegionId(),
			'region'=>$address->getRegion(),
			'city'=>$address->getCity(),
			'ward'=>$address->getWard(),
			'street'=>$address->getStreet(),
			'postcode'=>($address->getPostcode()?$address->getPostcode():''),
			'hasWard'=>((!$address->getWard() && $address->getCountryID() == 'VN')?'false':'true')
		    );
		}
		$addressId = $this->getAddress()->getId();
		if (empty($addressId)) {
		    $address = $customer->getPrimaryShippingAddress();
		    if ($address) {
			    $addressId = $address->getId();
		    }else{
			if(sizeof($address_list) > 0){
			    $addressId = reset($address_list)['value'];
			    //$this->setAddressDefault($addressId);
			}
		    }
		}
		$resut['address_id_defaul'] = $addressId;
		$resut['address_list'] = $address_list;
	    }
	}
	return $resut;
    }
    public function getAddress(){
	if (Mage::getSingleton('customer/session')->isLoggedIn()){
            $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultBilling();
            if ($customerAddressId){
                $billing = Mage::getModel('customer/address')->load($customerAddressId);
            }else{
		$quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
                $billing = $quote->getBillingAddress();
            }
            if(!$billing->getCustomerAddressId()){
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                $default_address = $customer -> getDefaultBillingAddress();
                 if ($default_address) {
                    if ($default_address->getId()) {
                        if ($default_address->getPrefix()) {
                            $billing->setPrefix($default_address->getPrefix());
                        }
                        if ($default_address->getData('firstname')) {
                            $billing->setData('firstname', $default_address->getData('firstname'));
                        }
                        if ($default_address->getData('middlename')) {
                            $billing->setData('middlename', $default_address->getData('middlename'));
                        }if ($default_address->getData('lastname')) {
                            $billing->setData('lastname', $default_address->getData('lastname'));
                        }if ($default_address->getData('suffix')) {
                            $billing->setData('suffix', $default_address->getData('suffix'));
                        }if ($default_address->getData('company')) {
                            $billing->setData('company', $default_address->getData('company'));
                        }if ($default_address->getData('street')) {
                            $billing->setData('street', $default_address->getData('street'));
                        }if ($default_address->getData('city')) {
                            $billing->setData('city', $default_address->getData('city'));
                        }if ($default_address->getData('ward')) {
                            $billing->setData('ward', $default_address->getData('ward'));
                        }if ($default_address->getData('region')) {
                            $billing->setData('region', $default_address->getData('region'));
                        }if ($default_address->getData('region_id')) {
                            $billing->setData('region_id', $default_address->getData('region_id'));
                        }if ($default_address->getData('postcode')) {
                            $billing->setData('postcode', $default_address->getData('postcode'));
                        }if ($default_address->getData('country_id')) {
                            $billing->setData('country_id', $default_address->getData('country_id'));
                        }if ($default_address->getData('telephone')) {
                            $billing->setData('telephone', $default_address->getData('telephone'));
                        }if ($default_address->getData('fax')) {
                            $billing->setData('fax', $default_address->getData('fax'));
                        }
                        $billing->setCustomerAddressId($default_address->getId())
                                ->save();
                    }
                } else {
                    return $billing;
                }
            }
            return $billing;
        } else {
            return Mage::getModel('sales/quote_address');
        }
    }
    public function deleteAddress($address_id){
	$result = [];
	$result['success'] = false;
	$result['message'] = '';
	
	$address = Mage::getModel('customer/address')->load($address_id);

	// Validate address_id <=> customer_id
	if ($address->getCustomerId() != Mage::getSingleton('customer/session')->getCustomerId()) {
	    $result['message'] = $this->__('The address does not belong to this customer.');
	    return $result;
	}

	try {
	    $address->delete();
	    $result['success'] = true;
	    $result['message'] = $this->__('The address has been deleted.');
	} catch (Exception $e){
	    $result['message'] = $this->__('An error occurred while deleting the address.');
	}
	return $result;
    }
    public function updateAddress($address_id, $address_data){
	$result = [];
	$result['success'] = false;
	$result['message'] = '';
	
	$customer = Mage::getSingleton('customer/session')->getCustomer();
	/* @var $address Mage_Customer_Model_Address */
	if ($address_id) {
	    $existsAddress = $customer->getAddressById($address_id);
	    if ($existsAddress->getId() && $existsAddress->getCustomerId() == $customer->getId()) {
		foreach($address_data as $key=>$item){
		    $existsAddress->setData($key, $item);
		}
	    }
	}

	try {
	    $existsAddress->setCustomerId($customer->getEntityId())
		->setIsDefaultBilling(true)
		->setIsDefaultShipping(true);
	    $existsAddress->save();
	    $result['success'] = true;
	    $result['message'] = $this->__('The address has been saved.');
	} catch (Mage_Core_Exception $e) {
	    $result['message'] = $this->__('Cannot save address.');
	} catch (Exception $e) {
	    $result['message'] = $this->__('Cannot save address.');
	}
	return $result;
    }
    public function setAddressDefault($address_id){
	$result = [];
	$result['success'] = false;
	$result['message'] = '';
	
	$customer = Mage::getSingleton('customer/session')->getCustomer();
	
	if ($address_id) {
	    $existsAddress = $customer->getAddressById($address_id);
	    if ($existsAddress->getId() && $existsAddress->getCustomerId() == $customer->getId()) {
		$existsAddress->setCustomerId($customer->getEntityId())
		    ->setIsDefaultBilling(true)
		    ->setIsDefaultShipping(true);
	    }
	}

	try {
	    $existsAddress->save();
	    $result['success'] = true;
	    $result['message'] = $this->__('The address has been saved.');
	} catch (Mage_Core_Exception $e) {
	    $result['message'] = $this->__('Cannot save address.');
	} catch (Exception $e) {
	    $result['message'] = $this->__('Cannot save address.');
	}
	return $result;
    }
    public function getCheckoutAddressDefault(){
	$result = Mage::getSingleton('customer/session')->getCheckoutAddressDefault();
	if(empty($result)){
	    if(Mage::getSingleton('customer/session')->isLoggedIn()){
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$telephone =  $customer->getTelephone()?$customer->getTelephone():'';
		$lastname = $customer->getLastname()?$customer->getLastname():'';
		$firstname = $customer->getFirstname()?$customer->getFirstname():'';
		$fullname = ($lastname?:'').($lastname && $firstname?' '.$firstname:'');
	    
	    $address_data = [];
	    $address_data['fullname'] = $fullname;
	    $address_data['email'] = '';
	    $address_data['ward'] = '';
	    $address_data['telephone'] = $telephone;
	    $address_data['street'] = '';
	    $address_data['region_id'] = '';
	    $address_data['region'] = '';
	    $address_data['postcode'] = '';
	    $address_data['lastname'] = $lastname;
	    $address_data['firstname'] = $firstname;
	    $address_data['country_id'] = 'VN';
	    $address_data['city'] = '';
	    $result = $address_data;
	    }
	}
	return $result;
    }
    public function removeEmoji($text){
      return trim(preg_replace('/[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0077}\x{E006C}\x{E0073}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0073}\x{E0063}\x{E0074}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0065}\x{E006E}\x{E0067}\x{E007F})|[\x{1F3F4}](?:\x{200D}\x{2620}\x{FE0F})|[\x{1F3F3}](?:\x{FE0F}\x{200D}\x{1F308})|[\x{0023}\x{002A}\x{0030}\x{0031}\x{0032}\x{0033}\x{0034}\x{0035}\x{0036}\x{0037}\x{0038}\x{0039}](?:\x{FE0F}\x{20E3})|[\x{1F441}](?:\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B0})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2640}\x{FE0F})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2642}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2695}\x{FE0F})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FF})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FE})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FD})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FC})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FB})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FA}](?:\x{1F1FF})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1FA}](?:\x{1F1FE})|[\x{1F1E6}\x{1F1E8}\x{1F1F2}\x{1F1F8}](?:\x{1F1FD})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F9}\x{1F1FF}](?:\x{1F1FC})|[\x{1F1E7}\x{1F1E8}\x{1F1F1}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1FB})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1FB}](?:\x{1F1FA})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FE}](?:\x{1F1F9})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FA}\x{1F1FC}](?:\x{1F1F8})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F7})|[\x{1F1E6}\x{1F1E7}\x{1F1EC}\x{1F1EE}\x{1F1F2}](?:\x{1F1F6})|[\x{1F1E8}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}](?:\x{1F1F5})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EE}\x{1F1EF}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F8}\x{1F1F9}](?:\x{1F1F4})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1F3})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FF}](?:\x{1F1F2})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F1})|[\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FD}](?:\x{1F1F0})|[\x{1F1E7}\x{1F1E9}\x{1F1EB}\x{1F1F8}\x{1F1F9}](?:\x{1F1EF})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EB}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F3}\x{1F1F8}\x{1F1FB}](?:\x{1F1EE})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1ED})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1EC})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F9}\x{1F1FC}](?:\x{1F1EB})|[\x{1F1E6}\x{1F1E7}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FB}\x{1F1FE}](?:\x{1F1EA})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1E9})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FB}](?:\x{1F1E8})|[\x{1F1E7}\x{1F1EC}\x{1F1F1}\x{1F1F8}](?:\x{1F1E7})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F6}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}\x{1F1FF}](?:\x{1F1E6})|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267E}-\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F3FA}\x{1F400}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F64F}\x{1F680}-\x{1F6C5}\x{1F6CB}-\x{1F6D2}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6F9}\x{1F910}-\x{1F93A}\x{1F93C}-\x{1F93E}\x{1F940}-\x{1F945}\x{1F947}-\x{1F970}\x{1F973}-\x{1F976}\x{1F97A}\x{1F97C}-\x{1F9A2}\x{1F9B0}-\x{1F9B9}\x{1F9C0}-\x{1F9C2}\x{1F9D0}-\x{1F9FF}]/u', '', $text));
    }
    public function setPublicStore($key_store, $value, $type = ''){
	try{
	    $days = Mage::getStoreConfig('flashsale_config/config/redis_day_timelife');
	    if($days == 0){return;}
	    
	    if(empty($days) || !is_numeric($days)){
		$days = 1;
	    }
	    switch($type){
		case 'rating_averages':
		case 'comment_list':
		case 'product_additional':
		    $queryfier = Mage::getStoreConfig('flashsale_config/config/redis_querylier_productview');
		    break;
		default:
		    $queryfier = Mage::getStoreConfig('flashsale_config/config/redis_querylier');
	    }
	    $value = serialize(array('value'=>$value,
				    'timelife'=>date('Y-m-d', strtotime("+7 hours ".$days." days")),
				    'queryfier'=>$queryfier
				));
	    
	    // Start Redis Connection
	    $redis_client = Mage::helper("flashsale/redis")->createRedisClientPublicAction();
	    if (!$redis_client->isConnected()) {
		return;
	    }
	    
	    $redis_client->set("public_store:".$key_store, $value);
	    $redis_client->close();
	}catch (Exception $ex){}
    }
    
    public function getPublicStore($key_store, $type = ''){
	try{
	    if(empty($key_store)){return $result;}
	    if(Mage::getStoreConfig('flashsale_config/config/redis_day_timelife') == 0){return;}
	    
	    // Start Redis Connection
	    $redis_client = Mage::helper("flashsale/redis")->createRedisClientPublicAction();
	    if (!$redis_client->isConnected()) {
		return null;
	    }
	    
	    switch($type){
		case 'rating_averages':
		case 'comment_list':
		case 'product_additional':
		    $queryfier = Mage::getStoreConfig('flashsale_config/config/redis_querylier_productview');
		    break;
		default:
		    $queryfier = Mage::getStoreConfig('flashsale_config/config/redis_querylier');
	    }
	    
            $result = $redis_client->get("public_store:".$key_store);
	    if(!empty($result)){
		$result = unserialize($result);
		if(strtotime($result['timelife']) >  strtotime(date('Y-m-d', strtotime("+7 hours")))
		   && $result['queryfier'] == $queryfier
		){
		    return $result['value'];
		}
	    }
	}catch (Exception $ex) {}
	return null;
    }
    public function setCustomerStore($customer_id, $key_store, $value){
	try{
	    if(empty($customer_id)){return $result;}
	    // Start Redis Connection
	    $redis_client = Mage::helper("flashsale/redis")->createRedisClientCustomerAction();
	    if (!$redis_client->isConnected()) {
		return;
	    }
	    
	    $redis_client->set("customer_store:".$customer_id.":".$key_store, $value);
	    $redis_client->close();
	}catch (Exception $ex){}
    }
    
    public function getCustomerStore($customer_id, $key_store){
	$result = "";
	try{
	    if(empty($customer_id) || empty($key_store)){return $result;}
	    // Start Redis Connection
	    $redis_client = Mage::helper("flashsale/redis")->createRedisClientCustomerAction();
	    if (!$redis_client->isConnected()) {
		return $result;
	    }
	    
            $result = $redis_client->get("customer_store:".$customer_id.":".$key_store);
	}catch (Exception $ex) {}
	return $result;
    }
    
    public function getEventDeiveryList($is_get_in_cart, $product_list, $product, $sku, $address = null){
	$result = [];
	if(!Mage::getStoreConfig('event_delivery/config/is_active')){
	    return $result;
	}
	
	try{
	    $city_id = 0;
	    $district_id = 0;

	    $skuArr = [];
	    if(!$is_get_in_cart){
		if (empty($address)){
		    $address = Mage::helper("fahasa_customer")->getExpectedAddress();
		}

		if (!empty($address['data']["province_id"])){
		    $city_id = $address['data']["province_id"];
		}
		if (!empty($address['data']["district"])){
		    $district_name = $address['data']["district"];
		}

		if(empty($product_list)){
		    if(empty($product)){
			$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
		    }

		    if(empty($product)){
			return $result;
		    }
		    $product_list = [];
		    $product_list[$product->getSku()] = $product;
		}
		$skuArr[$sku] = 1;
	    }else{
		$product_list = [];
		$cart = Mage::getSingleton('checkout/session')->getQuote();
		//$cart->getAllItems() to get ALL items, parent as well as child, configurable as well as it's simple associated item
		foreach ($cart->getAllVisibleItems() as $item) {
		    $product = $item->getProduct();
		    $product->setQty($item->getQty());
		    $product_list[$product->getSku()] = $product;
		    $skuArr[$product->getSku()] = $product->getQty();
		}
		$address = $cart->getBillingAddress();
		$city_id = $address->getRegionId();
		$district_name = $address->getCity();
	    }

	    $result = $this->getEventDelivery($product_list, $city_id, $district_name, $skuArr, $is_get_in_cart);
	}catch (Exception $ex) {
	    Mage::log("getEventDeiveryList msg= " . $ex->getMessage(), null, "event_delivery.log");
	}
	return $result;
    }
  
    public function getEventDelivery($product_list, $city_id, $district_name, $skuArr, $is_payment_page = false){
	$result = [];
	if(empty($city_id)){$city_id = 0;}
	if(empty($district_name)){$district_name = '';}
	
	
	$time_to_nextday = Mage::getStoreConfig('event_delivery/config/time_to_nextday');
	
	$products_error= [];
	$has_apply_event = false;
	    
	$query = "select d.id, d.title, d.description, d.description_shipping, d.price, d.icon_path, d.icon_grey_path, ifnull(d.page_detail, '') as 'page_detail', ifnull(b.content, '') as 'rule_content', if(r.city_id is null, 0, 1) as 'has_region', 
	    p.id as 'period_id', p.name as 'period_name', p.value as 'period_value', p.delivery_date, if(if(p.delivery_date < (now() - INTERVAL ".$time_to_nextday." HOUR),-1, p.capacity) <= p.times_used, 0, 1) as 'period_enable' 
	    from fhs_event_delivery d
	    left join fhs_event_delivery_region r on r.event_delivery_id = d.id and r.city_id = ".$city_id." and r.district_name = '".$district_name."'
	    join fhs_event_delivery_period p on p.event_delivery_id = d.id and p.is_active = 1
	    left join fhs_cms_block b on b.identifier = d.rule_content
	    where d.is_active = 1 and d.start_time < now() and d.end_time > now();";
	
        $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
        $data = $reader->fetchAll($query);
	if(!empty($data)){
	    $event_delivery_ids = '';
	    
	    foreach ($data as $item){
		$event_delivery = [];
		$event_delivery_periods = [];
		if(!empty($result[$item['id']])){
		    $event_delivery = $result[$item['id']];
		    $event_delivery_periods = $event_delivery['periods'];
		}else{
		    if(!empty($event_delivery_ids)){
			$event_delivery_ids .= ",";
		    }
		    $event_delivery_ids .= $item['id'];
		    
		    $event_delivery['id'] = $item['id'];
		    $event_delivery['shippingMethod'] = "vietnamshippingnormal_vietnamshippingnormal";
		    $event_delivery['label'] = $item['title'];
		    $event_delivery['description'] = $item['description'];
		    $event_delivery['methodTitle'] = $item['description_shipping'];
		    $event_delivery['price'] = $item['price'];
		    $event_delivery['is_show_icon'] = true;
		    $event_delivery['icon_path'] = $item['icon_path'];
		    $event_delivery['icon_grey_path'] = $item['icon_grey_path'];
		    $event_delivery['page_detail'] = $item['page_detail'];
		    $event_delivery['rule_content'] = $item['rule_content'];
		    $event_delivery['has_region'] = $item['has_region']?true:false;
		    $event_delivery['products_not_support'] = [];
		    $event_delivery['error'] = [];
		    $event_delivery['enable'] = false;
		    
		}
		
		if(empty($event_delivery_periods[$item['period_id']])){
		    $period = [];
		    $period['id'] = $item['period_id'];
		    $period['name'] = $item['period_name'];
		    $period['value'] = $item['period_value'];
		    $period['delivery_date'] = $item['delivery_date'];
		    $period['enable'] = $item['period_enable']?true:false;

		    $event_delivery_periods[$item['period_id']] = $period;
		    $event_delivery['periods'] = $event_delivery_periods;
		}
		
		$result[$item['id']] = $event_delivery;
	    }
	    $result = $this->filterCategory($product_list, $event_delivery_ids, $result, $is_payment_page);
	    
	    foreach($result as $key=>$item){
		$enable = false;
		foreach($item['periods'] as $period){
		    if($period['enable']){
			$enable = true;
			goto out_loop;
		    }
		}
		out_loop:
		if(!$enable){
		    $item['error']['out_of_option'] = 'Chương trình đã kết thúc';
		}else{
		    if(!empty($item['products_not_support'])){
			$enable = false;
			$item['is_show_icon'] = false;
			$item['error']['product'] = 'Giỏ hàng có một số sản phẩm không nằm trong danh sách trong chương trình';
		    }
		    if(!$item['has_region']){
			$enable = false;
			$item['error']['out_of_option'] = 'Địa chỉ giiao hàng không nằm trong khu vực được hỗ trợ (Tp.HCM, Hà Nội)';
		    }
		}
		
		if($enable){
		    $has_apply_event = true;
		}
		
		$item['enable'] = $enable;
		$result[$key] = $item;
	    }
	    
	    if($has_apply_event){
		$book_store_id = '';
		if($city_id == 485){
		    $book_store_id = 67;
		}elseif($city_id == 487){
		    $book_store_id = 89;
		}
		if(!empty($book_store_id)){
		    $products = $this->getQtyInStore($book_store_id, $skuArr);
		}
		
		if(!empty($products)){
		    $qty_minimum = Mage::getStoreConfig('event_delivery/config/qty_min_stock');
		    if(!empty($qty_minimum) || !is_numeric($qty_minimum)){
			$qty_minimum = 0;
		    }
		    foreach ($skuArr as $sku=>$qty){
			if(!empty($products[$sku])){
			    $product = $products[$sku];
			    $curQty = 1;
			    if (!empty($qty)){
				$curQty = $qty;
			    }

			    $product['qty'] = $product['qty'] - $curQty;

			    if ($product['isAvailable'] != 1){
				$products_error[$sku] = $this->getProductInfo($product_list[$sku], $is_payment_page);
			    }else{
				if($product['qty'] < $qty_minimum){
				    $products_error[$sku] = $this->getProductInfo($product_list[$sku], $is_payment_page);
				}
			    }
			}else{
			    $products_error[$sku] = $this->getProductInfo($product_list[$sku], $is_payment_page);
			}
		    }
		}else{
		    foreach ($skuArr as $sku=>$item){
			$products_error[$sku] = $this->getProductInfo($product_list[$sku], $is_payment_page);
		    }
		}
	    }
	    
	    out_of_stock:
	    if(!empty($products_error) && Mage::getStoreConfig('event_delivery/config/show_choose_delivery_date_payment')){
		foreach ($result as $key=>$item){
		    $item['products_not_support'] = $products_error;
		    $item['error']['product'] = 'Giỏ hàng có một số sản phẩm không đủ tồn trong khu vực giao hàng';
		    $item['enable'] = false;
		    $result[$key] = $item;
		}
	    }
	}
	return $result;
    }
    
    public function filterCategory($product_list, $event_delivery_ids, $event_delivery_list, $is_payment_page){
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	$query = "select c.*
		from fhs_event_delivery d 
		join fhs_event_delivery_category c on c.event_delivery_id = d.id
		where d.is_active = 1 and d.start_time < now() and d.end_time > now();";
        $data = $reader->fetchAll($query);
	
	$event_delivery_cat_list = [];
	if(!empty($data)){
	    foreach($data as $item){
		$event_delivery_cat = [];
		if(!empty($event_delivery_cat_list[$item['event_delivery_id']])){
		    $event_delivery_cat = $event_delivery_cat_list[$item['event_delivery_id']];
		}
		
		$cat['category_id'] = $item['category_id'];
		$cat['position'] = $item['position'];
		$cat['is_apply'] = $item['is_apply'];
		
		array_push($event_delivery_cat, $cat);
		$event_delivery_cat_list[$item['event_delivery_id']] = $event_delivery_cat;
	    }
	}
	
	if(!empty($event_delivery_cat_list)){
	    foreach($event_delivery_list as $key=>$item){
		$products_not_support = [];
		$is_apply = false;
		foreach($product_list as $product){
		    $cat_1 = $product->getCategoryMainId();
		    $cat_2 = $product->getCategoryMidId();
		    $cat_3 = $product->getData('category_1_id');
		    $cat_4 = $product->getCat4Id();
		    
		    $has_apply = false;
		    $has_exclude = false;
		    if(!empty($event_delivery_cat_list[$key])){
			foreach($event_delivery_cat_list[$key] as $cat){
			    if($cat['is_apply'] == 1){
				if(($cat['category_id'] == $cat_1 && $cat['position'] == 1) ||
				    ($cat['category_id'] == $cat_2 && $cat['position'] == 2) ||
				    ($cat['category_id'] == $cat_3 && $cat['position'] == 3) ||
				    ($cat['category_id'] == $cat_4 && $cat['position'] == 4)){
				    $has_apply = true;
				}
			    }else{
				if(($cat['category_id'] == $cat_1 && $cat['position'] == 1) ||
				    ($cat['category_id'] == $cat_2 && $cat['position'] == 2) ||
				    ($cat['category_id'] == $cat_3 && $cat['position'] == 3) ||
				    ($cat['category_id'] == $cat_4 && $cat['position'] == 4)){
				    $has_exclude = true;
				    goto out_loop_check_cat;
				}
			    }
			}
		    }
		    out_loop_check_cat:
		    if(!$has_apply || $has_exclude || !$product->isAvailable() || $product->getSoonRelease() == 1){
			$products_not_support[$product->getEntityId()] = $this->getProductInfo($product, $is_payment_page);
		    }
		}
		$item['products_not_support'] = $products_not_support;
		$event_delivery_list[$key] = $item;
	    }
	}else{
	    foreach($event_delivery_list as $key=>$item){
		$products_not_support = [];
		foreach($product_list as $product){
		    $products_not_support[$product->getEntityId()] = $this->getProductInfo($product, $is_payment_page);
		}
		$item['products_not_support'] = $products_not_support;
		$event_delivery_list[$key] = $item;
	    }
	}
	return $event_delivery_list;
    }
    public function getProductInfo($product, $is_payment_page){
	$result = [];
	if(!$is_payment_page){
	    $result['product_id'] = $product->getEntityId();
	    $result['name'] = $product->getName();
	    return $result;
	}
	if(Mage::helper('discountlabel')->getBundlePrice($product)){
	    $price = $product->getData('price');
	    $final_price = $product->getFinalPrice();
	}
	else{
	    if($product->getFinalPrice()) {
		$final_price = $product->getFinalPrice();
	    } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
		$final_price = Mage::getModel('bundle/product_price')->getTotalPrices($product,'min',1);
	    }
	    $price = $product->getPrice();
	}
	
	$result['product_id'] = $product->getEntityId();
	$result['name'] = $product->getName();
	$result['image'] = Mage::helper('catalog/image')->init($product, 'small_image',$product->getSmallImage())->resize(140, 140)->__toString();
	$result['price'] = round($price, 2);
	$result['final_price'] = round($final_price, 2);
	$result['qty'] = !empty($product->getQty())?$product->getQty():1;
	
	return $result;
    }
    public function getQtyInStore($book_store_id, $skuArr){
	$result = [];
	if(empty($skuArr)){return $result;}
        $skus_str = array_keys($skuArr);
	
	$key_str = "getQtyInStore_".$book_store_id."_".implode("'_'", $skus_str);
        if(Mage::registry($key_str)){
	    return Mage::registry("getQtyInStore_".$key_str);
	}
	try{
	    $sku_str = implode("','", $skus_str);
	    $read = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "SELECT A.code, A.sku, A.qty - IFNULL(B.qty, 0) - IFNULL(C.qty, 0) AS qty, A.isAvailable
		    FROM
		    (
			SELECT bs.code, loc.sku, SUM(IF(b.type = 'T' OR b.type = 'X' OR b.type = 'V', 0, loc.qty)) AS qty,
			  (availDate.value IS NULL OR availDate.value <= curDate()) AS isAvailable
			    FROM fhs_bookshelf_product_location loc
			    JOIN fhs_bookshelf b ON b.entity_id = loc.bookshelf_entity_id
		     JOIN fahasa_bookstore bs ON bs.id = b.bookstore_id
		     LEFT JOIN fhs_catalog_product_entity pe ON pe.sku = loc.sku
		     LEFT JOIN fhs_catalog_product_entity_datetime availDate ON availDate.entity_id = pe.entity_id AND availDate.attribute_id = 191
			    WHERE b.bookstore_id = ".$book_store_id."  AND loc.sku IN ('".$sku_str."')
			    GROUP BY bs.code, loc.sku
		    ) A 
		    LEFT JOIN (
			    SELECT bs.code, pe.sku, SUM(IF(sb.bundle_id IS NULL, soi.qty, sb.qty * soi.qty)) AS qty
			    FROM fahasa_suborder so
			    JOIN fahasa_suborder_item soi ON soi.suborder_id = so.suborder_id
			    LEFT JOIN fahasa_suborder_bundle sb on soi.bundle_id = sb.bundle_id and soi.suborder_id = sb.suborder_id and soi.bundle_type = sb.bundle_type
			    LEFT JOIN fahasa_suborder sop ON sop.suborder_id = so.parent_id
			    JOIN fhs_catalog_product_entity pe ON pe.entity_id = soi.product_id
			    JOIN fahasa_bookstore bs ON bs.id = so.bookstore_id
			    WHERE so.bookstore_id = ".$book_store_id." AND pe.sku IN ('".$sku_str."')
		     AND (so.parent_id IS NULL AND so.status IN ('assigned', 'confirmed') OR (so.parent_id IS NOT NULL AND so.status = 'exporting' AND sop.status IN ('exporting', 'imported')))
			    AND so.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
			    GROUP BY bs.code, pe.sku
		    ) B ON A.code = B.code AND A.sku = B.sku
		    LEFT JOIN ( 
			    SELECT oi.sku, SUM(oi.qty_ordered) AS qty
			    FROM fhs_sales_flat_order o
			    JOIN fhs_sales_flat_order_item oi ON o.entity_id = oi.order_id and oi.sku in ('".$sku_str."')
			    WHERE o.status IN ('pending','tmdt','customer_confirmed','scheduler_failed','paid','pending_payment')
			    AND o.created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH)
			    GROUP BY oi.sku
		    )C ON A.sku = C.sku;";
	    
	    $data = $read->fetchAll($sql);
	    if(!empty($data)){
		foreach ($data as $item){
		    $result[$item['sku']] = $item;
		}
	    }
	    Mage::register("getQtyInStore_".$book_store_id."_".$key_str, $result);
	} catch (Exception $ex) {}
	
	return $result; 
    }
}
