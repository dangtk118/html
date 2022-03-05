<?php

require_once 'Mage/Customer/controllers/AccountController.php';

class Fahasa_Customer_AccountController extends Mage_Customer_AccountController {
     
    public function checkTelephoneAction() {
        $post = $this->getRequest("post");
        $channel = "web";
        $telephone = $post->get("telephone");

        $helper = Mage::helper("fahasa_customer");
        $data = $helper->checkTelephoneInvalid($telephone, $channel);

        return $this->getResponse()->setBody($data);
    }

    public function compareOtpAction() {
        $post = $this->getRequest("post");
        $telephone = $post->get("telephone");
        $otp = $post->get("otp");

        $helper = Mage::helper("fahasa_customer");
        $data = $helper->compareOTP($telephone, $otp);

        return $this->getResponse()->setBody($data);
    }
    
    public function redirectConfirmSuccess($customer, $backUrl) {
        $successUrl = $this->_welcomeCustomer($customer, true);
        $this->_redirectSuccess($backUrl ? $backUrl : $successUrl);
        return;
    }

    public function redirectConfirmFailure($message){
        $this->_getSession()->addError($this->__($message));
        $this->_redirectError($this->_getUrl('*/*/index', array('_secure' => true)));
        return;
    }
    
    public function redirectIndex(){
        $this->_redirectSuccess($this->_getUrl('*/*/index', array('_secure' => true)));
        return;
    }
    
    public function confirmFacebookAction() {
        $key = $this->getRequest()->getParam('key', false);
        $id = $this->getRequest()->getParam('id', false);
        $facebookId = $this->getRequest()->getParam('facebookId', false);
        $facebookKey = $this->getRequest()->getParam('facebookKey', false);
        $backUrl = $this->getRequest()->getParam('backUrl', false);

        $helper = Mage::helper("fahasa_customer");

        $result = $helper->confirmFacebookAccount($id, $key, $facebookId, $facebookKey);
        if ($result['success']){
            if ($key){
                //case: customer has no account in fahasa -> create new account -> verify phone number
                //after customer verity phone number -> it will call to insert into fhs_facebook_user
                if ($result['message'] == 'CONFIRM_EMAIL_SUCCESS'){
                    $this->_redirectSuccess($this->_getUrl('tryout/telephone' , array('_secure' => true, "id"=>$id, "facebookId" => $facebookId)));
                }
                else if ($result['message'] == 'FACEBOOK_ACTIVATED' || $result['message'] == 'ACCOUNT_ACTIVATED'){
                    $this->redirectIndex();
                }
                else{
                    $this->redirectConfirmSuccess($result['customer'], $backUrl);
                }
            }
            else{
                if ($result['message'] == 'FACEBOOK_ACTIVATED' || $result['message'] == 'ACCOUNT_ACTIVATED'){
                    $this->redirectIndex();
                }
                else{
                    $this->redirectConfirmSuccess($result['customer'], $backUrl);
                }
            }
        }
        else{
            $this->redirectConfirmFailure($result['message']);
        }
    }

    //Set vat of customer
    public function saveVATAction() {
	$customer_id = Mage::getSingleton('customer/session')->getCustomer()->getEntityId();
	$company = $this->getRequest()->getPost('company', false);
	$address = $this->getRequest()->getPost('address', false);
	$taxcode = $this->getRequest()->getPost('taxcode', false);
	$name = $this->getRequest()->getPost('name', false);
	$email = $this->getRequest()->getPost('email', false);
	$result = array();
	$result['success'] = Mage::helper('fahasa_customer')->saveVAT($customer_id, $company, $address, $taxcode, $name, $email);
	$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	return;
    }
        
    public function checkChangePhoneAction(){
        $phone = $this->getRequest()->getPost('phone', '');
        $channel = "web";
        $result = Mage::helper('fahasa_customer')->checkChangePhone($phone, $channel);

        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }
    
    public function changePhoneAccountAction(){
	$phone = $this->getRequest()->getPost('username', '');
	$otp = $this->getRequest()->getPost('otp', '');
	
	$result = Mage::helper('fahasa_customer')->changePhoneAccountByOtp($phone, $otp);
	return $this->getResponse()->setBody(json_encode($result))
	    ->setHeader('Content-Type', 'application/json');
    }
        
    public function checkChangeEmailAction(){
	$result = array();
	$result['success'] = false;
	$result['message'] = '';
	$result['netcore_contact'] =  '';
	$email = $this->getRequest()->getPost('email', '');
	if(empty($email) || (strlen($email) > 200) || !filter_var($email,FILTER_VALIDATE_EMAIL)){
	    $result['message'] = $this->__("Email invalid");
	    return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-Type', 'application/json');
	}
	$result['message'] = Mage::helper('fahasa_customer')->checkEmailValidForChange($email);
	if($result['message'] == $this->__('OTP sent')){
	    $result['success'] = true;
	    $result['netcore_contact'] = Mage::helper('fahasa_customer')->updateNetcoreContact();
	}
	
	return $this->getResponse()->setBody(json_encode($result))
	    ->setHeader('Content-Type', 'application/json');
    }
    
    public function changeEmailAccountAction() {
        $helper_customer = Mage::helper('fahasa_customer');
        $email = $this->getRequest()->getPost('email', '');
        $otp = $this->getRequest()->getPost('otp', '');

        $result = $helper_customer->changeEmailAccountByOTP($email, $otp);
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }

    public function getAddressListAction(){
        $result = Mage::helper('fahasa_customer')->getCustomerAddressList();
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }
    public function deleteAddressAction(){
        $address_id = $this->getRequest()->getPost('address_id', '');
        $result = Mage::helper('fahasa_customer')->deleteAddress($address_id);
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }
    public function updateAddressAction(){
	$result = [];
	$result['success'] = true;
	$result['message'] = '';
	$helper = Mage::helper('fahasa_customer');
	
        $address_id = $this->getRequest()->getPost('address_id', '');
        $ward = $this->getRequest()->getPost('ward', '');
        $telephone = $this->getRequest()->getPost('telephone', '');
        $street = $this->getRequest()->getPost('street', '');
        $region_id = $this->getRequest()->getPost('city_id', '');
        $region = $this->getRequest()->getPost('city', '');
        $postcode = $this->getRequest()->getPost('postcode', '');
        $lastname = $this->getRequest()->getPost('lastname', '');
        $firstname = $this->getRequest()->getPost('firstname', '');
        $country_id = $this->getRequest()->getPost('country_id', '');
        $city = $this->getRequest()->getPost('district', '');
	
	if(empty($country_id) || empty($city) || empty($street) || empty($region)
	    || empty($telephone) || empty($firstname) || empty($lastname)){
	    $result = [];
	    $result['success'] = true;
	    $result['message'] = $this->__('Cannot save address.');
	    return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
	}
	
	$address_data = [];
	$address_data['ward'] = $ward;
	$address_data['telephone'] = $helper->removeEmoji($telephone);
	$address_data['street'] = $helper->removeEmoji($street);
	$address_data['region_id'] = $region_id;
	$address_data['region'] = $helper->removeEmoji($region);
	$address_data['postcode'] = $postcode;
	$address_data['lastname'] = $helper->removeEmoji($lastname);
	$address_data['firstname'] = $helper->removeEmoji($firstname);
	$address_data['country_id'] = $country_id;
	$address_data['city'] = $helper->removeEmoji($city);
	
	if($address_id){
	    $result = $helper->updateAddress($address_id, $address_data);
	}else{
	    if(Mage::getSingleton('customer/session')->isLoggedIn()){
		$resut['success'] = $helper->addCustomerAddress(Mage::getSingleton('customer/session')->getId(), $address_data);
		if(!$resut['success']){
		    $result['message'] = $this->__('Cannot add shipping address.');
		}
	    }
	}
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }
    
    public function clearLastSessionIdAction() {
	$customer_id = Mage::getSingleton('customer/session')->getCustomer()->getEntityId();
	if(empty($customer_id)){
	    return $this->getResponse()->setBody(json_encode(array('success'=>false)))
			    ->setHeader('Content-Type', 'application/json');
	}
	
	Mage::helper('productviewed')->setCustomerLastSessionId($customer_id, '');
        return $this->getResponse()->setBody(json_encode(array('success'=>true)))
                        ->setHeader('Content-Type', 'application/json');
    }
    
}
