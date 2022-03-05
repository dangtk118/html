<?php
class Fahasa_Vip_Helper_Data extends Mage_Core_Helper_Abstract{  
    
    public function isIdCompanyMember($company_id) {
        $company_id = strtolower(trim($company_id));
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $binds = array(
            "companyId" => $company_id            
        );
        $sqlQuery = "
            select vl.level, vl.group_id, vl.discount_primary, vl.discount_increment, 'company_vip' as type
            from fhs_vip_company vm 
            join fhs_vip_level vl on vm.vip_level = vl.level and vm.group_id = vl.group_id

            where vm.companyId=:companyId
            group by vl.level, vl.group_id";
            $results = $read->fetchAll($sqlQuery, $binds);
        if ($results) {
            $member = new Fahasa_Vip_Model_Member();
            $member->companyId = $company_id;
            $member->vipId = $company_id;
            $member->groupId = $results[0]['group_id'];
            $member->level = $results[0]['level'];
            return $member;
        }
        return FALSE;
    }
    
    
    public function isIdVipMember($vip_id) {
        Mage::log("*** determineVipMember: VipId: " . $vip_id, null, "vip_id.log");
        $customer_vip = Mage::getModel("vip/customervip")->load($vip_id,"vip_id");
        if ($customer_vip->getVipId() != null) {
            $member = new Fahasa_Vip_Model_Member();
            $member->vipId = $vip_id;
            $member->type = Fahasa_Vip_Model_Member::VIP_ID_TYPE;
            $member->customerVip = $customer_vip;
            $member->groupId = $customer_vip->getGroupId();
            $member->level = $customer_vip->getVipLevel();
            return $member;
        }  else {
            //This might be ssc, but before making rest call, check local db first
//            $isSSCLocal = $this->isIdSSCLocal($vip_id);
//            if($isSSCLocal){
//                Mage::log("*** SSCId is local: " . $vip_id, null, "vip_id.log");
//                $member = new Fahasa_Vip_Model_Member();
//                $member->vipId = $vip_id;
//                $member->type = Fahasa_Vip_Model_Member::VIP_ID_TYPE;
//                $member->isSSC = true;
//                $member->customerVip = $customer_vip;
//                return $member;
//            }else{
//                $response = $this->api_fetch_auth($vip_id);            
//                if($response != null){
//                    Mage::log("*** Response REST fetch: " . print_r($response, true), null, "vip_id.log");
//                    $resCode = $response['ResponseCode'];
//                    if($resCode === 0 && $response['ResponseData'][0] != null){
//                        $fullName = $response['ResponseData'][0]['FullName'];
//                        $gender = $response['ResponseData'][0]['GenderDescription'];
//                        $email = $response['ResponseData'][0]['Email'];
//                        $code = $response['ResponseData'][0]['Code'];
//                        $phone = $response['ResponseData'][0]['Telephone'];
//                        $curTime = $this->getCurrentTime();
//                        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
//                        $sqlQuery="insert into ssc_info (sscId, full_name, gender, email, code, created, phone) values ('" .
//                                $vip_id . "','" . $fullName . "','" . $gender . "','" . $email . "','" . $code . "','" .
//                                $curTime . "','" . $phone . "');";
//                        $write->query($sqlQuery);
//                        //Insert data 
//                        $member = new Fahasa_Vip_Model_Member();
//                        $member->vipId = $vip_id;
//                        $member->type = Fahasa_Vip_Model_Member::VIP_ID_TYPE;
//                        $member->isSSC = true;
//                        $member->customerVip = $customer_vip;
//                        return $member;
//                    }
//                }
//            }
            return false;
        }
    }
    
    public function isIdSSCLocal($vip_id){
        if($vip_id == null || empty($vip_id)) {
            return false;
        }
        $vip_id = trim($vip_id);
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sqlQuery = "select * from ssc_info where sscId = :ssc";
        $binds = array(
            'ssc' => $vip_id
        );         
        $readresult = $read->query($sqlQuery, $binds);
        while ($row = $readresult->fetch())
        {
            $id = $row['sscId'];
            if($id === $vip_id){
                return true;
            }
        }
        return false;
    }
    
    function getCurrentTime(){
        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone("Asia/Ho_Chi_Minh"));
        return $dt->format('Y-m-d H:i:s');        
    }
    
    public function isEmailVipMember($customerEmailAddress) {
        if ($customerEmailAddress == null || empty($customerEmailAddress)) {
            return false;
        }
        $customerEmailAddress = trim($customerEmailAddress);
        $customer_exp = explode("@", $customerEmailAddress);
        $companyEmailDomain = $customer_exp[1];
        $binds = array(
            "companyEmailDomain" => $companyEmailDomain,
            "customer_email" => $customerEmailAddress,
            
        );
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sqlQuery = "select * from
            (select vl.level, vl.group_id, vl.discount_primary, vl.discount_increment, 'customer_vip' as type
            from fhs_customer_vip cv join 
            fhs_vip_level vl on cv.vip_level = vl.level and cv.group_id = vl.group_id
            where cv.customer_email=:customer_email

            union all

            select vl.level, vl.group_id, vl.discount_primary, vl.discount_increment, 'company_vip' as type
            from fhs_vip_company_domain vm join
            fhs_vip_level vl on vm.vip_level = vl.level and vm.group_id = vl.group_id

            where vm.companyEmailDomain=:companyEmailDomain) A
            group by A.level, A.group_id";
        $results = $read->fetchAll($sqlQuery, $binds);
        foreach ($results as $r) {
            $vipLevel = $r['level'];
            $groupId = $r['group_id'];
            if ($r['type'] == "customer_vip") {
                break;
            }
        }
        if ($results) {
            $member = new Fahasa_Vip_Model_Member();
            $member->type = Fahasa_Vip_Model_Member::VIP_EMAIL_TYPE;
            $member->customerEmail = $customerEmailAddress;
            $member->groupId = $groupId;
            $member->level = $vipLevel;
            return $member;
        }
        return false;
    }

    /**
     * Return true if the current login user belong to a group, and false otherwise.
     * Also return false if user are not login.
     */
    public function determineVipMember() {
        //vannguyen: performance: currently, we stop vip member event. So for improve performance, we stop event temporily
        return false;
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $customer_email = $customer->getEmail();
            $emailTypeCustomer = $this->isEmailVipMember($customer_email);
            if($emailTypeCustomer){
                return $emailTypeCustomer;
            }
            $vip_id = $customer->getVipId();
            $companyId = $customer->getCompanyId();
        }else{
            //Customer can manually also input vip id during payment, which is store inside session data
            $vip_id = Mage::getSingleton('customer/session')->getData("vip_id");
        }
        if($vip_id == null && $companyId == null){
            return false;
        }else{
            //handle vip id
            if($vip_id !== null){
                $idTypeCustomer = $this->isIdVipMember($vip_id);
                if($idTypeCustomer){
                    return $idTypeCustomer;
                }
            }else if($companyId !== null){
                $member = $this->isIdCompanyMember($companyId);
                if($member){
                    return $member;
                }
            }
        }
        return false;        
    }     
    
    function api_fetch($url, $data)
    {
	$data_string = json_encode($data);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	  'Content-Type: application/json;charset=UTF-8',
	  'Content-Length: ' . strlen($data_string))
	);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	//execute post
	$result = curl_exec($ch);
	curl_close($ch);
	return json_decode($result, true);
	//return $result;
    }

    function api_fetch_auth($SSCID)
    {            
        $param = array(Fahasa_Vip_Model_Observer::SSC_ACCESSKEY => Fahasa_Vip_Model_Observer::SSC_ACCESSKEYVALUE, 
            Fahasa_Vip_Model_Observer::SSC_REQUESTPARAMS => array($SSCID));
        return $this->api_fetch(Fahasa_Vip_Model_Observer::SSC_URL_GETSTUDENT_BY_ID, $param);
    }
}
