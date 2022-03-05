<?php
class Magestore_Onestepcheckout_AjaxController extends Mage_Core_Controller_Front_Action {
	
    public function add_giftwrapAction()
    {
        $remove = $this->getRequest()->getPost('remove', false);
		$session = Mage::getSingleton('checkout/session');
        if(!$remove){
            $session->setData('onestepcheckout_giftwrap', 1);
        }else{
            $session->unsetData('onestepcheckout_giftwrap');
            $session->unsetData('onestepcheckout_giftwrap_amount');
        }        
        $this->loadLayout(false);
        $this->renderLayout();
    }
    
    public function add_tryoutAction()
    {
        $remove = $this->getRequest()->getPost('remove_tryout', false);
        $session = Mage::getSingleton('checkout/session');
        if(!$remove){
            $session->setData('onestepcheckout_tryout', 1);
        }else{
            $session->unsetData('onestepcheckout_tryout');
            $session->unsetData('onestepcheckout_tryout_amount');
        }        
        $this->loadLayout(false);
        $this->renderLayout();
    }
	
    public function forgotPasswordAction()
    {
        $email = $this->getRequest()->getPost('email', false);

        if (!Zend_Validate::is($email, 'EmailAddress')) {
            $result = array('success'=>false);
        }
        else{
            $customer = Mage::getModel('customer/customer')
                            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                            ->loadByEmail($email);
            if ($customer->getId()) {
                try {
                    $newPassword = $customer->generatePassword();
                    $customer->changePassword($newPassword, false);
                    $customer->sendPasswordReminderEmail();
                    $result = array('success'=>true);
                }catch (Exception $e){
                    $result = array('success'=>false, 'error'=>$e->getMessage());
                }
            }else{
                $result = array('success'=>false, 'error'=>'notfound');
            }
        }
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function loginAction()
    {        
        $username = $this->getRequest()->getPost('onestepcheckout_username', false);
        $password = $this->getRequest()->getPost('onestepcheckout_password',  false);
        $session = Mage::getSingleton('customer/session');
        $result = array('success' => false);
        if ($username && $password) {
            try {
                $session->login($username, $password);
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
            }
            if (! isset($result['error'])) {
                $result['success'] = true;
            }
        } else {
            $result['error'] = $this->__('Please enter a username and password.');
        }        
		/* Start: Modified by Daniel -01042015- Reload data after login */
		if(isset($result['error']))
			$session->setData('login_result_error',$result['error']);
		$this->loadLayout(false);
        $this->renderLayout();
		/* End: Modified by Daniel -01042015- Reload data after login */
    }
	
	/* Create new account on checkout page - Leo 08042015 */
	public function createAccAction() { 		
		$session = Mage::getSingleton('customer/session');
		$firstName =  $this->getRequest()->getPost('onestepcheckout_firstname', false);  
		$lastName =  $this->getRequest()->getPost('onestepcheckout_lastname', false);  
		$pass =  $this->getRequest()->getPost('onestepcheckout_register_password', false);  
		$passConfirm =  $this->getRequest()->getPost('onestepcheckout_confirmation_password', false);  
		$email = $this->getRequest()->getPost('onestepcheckout_register_username', false);        
		
		$customer = Mage::getModel('customer/customer')
						->setFirstname($firstName)
						->setLastname($lastName)
						->setEmail($email)
						->setPassword($pass)
						->setConfirmation($passConfirm);
									
		try{
			$customer->save();
			Mage::dispatchEvent('customer_register_success',
                        array('customer' => $customer)
                    );
			$result = array('success'=>true);
			$session->setCustomerAsLoggedIn($customer);
		}catch(Exception $e){
			$result = array('success'=>false, 'error'=>$e->getMessage());
		}          
		if(isset($result['error']))
			$session->setData('register_result_error',$result['error']);
		$this->loadLayout(false);
        $this->renderLayout();
    }
	/* Create new account on checkout page - Leo 08042015 */
    public function checkingEmailAction(){
	$result = [];
	$result['success'] = true;
	$email = $this->getRequest()->getPost('email', '');
	if(!empty($email)){
	    if (count(Mage::helper('fahasa_customer')->getEmailAvalible($email)) > 0) {
		$result['success'] = false;
	    }
	}
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }
    
    public function checkingTelephoneAction(){
	$result = [];
	$result['success'] = true;
	$telephone= $this->getRequest()->getPost('telephone', '');
	if(!empty($telephone)){
	    if (count(Mage::helper('fahasa_customer')->getTelephoneAvalible($telephone)) > 0) {
		$result['success'] = false;
	    }
	}
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }
  
    public function setAddressDefaultAction(){
	$result = [];
	$result['success'] = true;
	
        $address_id = $this->getRequest()->getPost('address_id', '');
	if($address_id){
	    $result['success'] = Mage::helper('fahasa_customer')->setAddressDefault($address_id);
	}else{
	    $email = $this->getRequest()->getPost('email', '');
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
	    $fullname = ($lastname?:'').($lastname && $firstname?' '.$firstname:'');
	    
	    $address_data = [];
	    $address_data['fullname'] = $fullname;
	    $address_data['email'] = $email;
	    $address_data['ward'] = $ward;
	    $address_data['telephone'] = $telephone;
	    $address_data['street'] = $street;
	    $address_data['region_id'] = $region_id;
	    $address_data['region'] = $region;
	    $address_data['postcode'] = $postcode;
	    $address_data['lastname'] = $lastname;
	    $address_data['firstname'] = $firstname;
	    $address_data['country_id'] = $country_id;
	    $address_data['city'] = $city;
	    Mage::getSingleton('customer/session')->setCheckoutAddressDefault($address_data);
	}
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }
}