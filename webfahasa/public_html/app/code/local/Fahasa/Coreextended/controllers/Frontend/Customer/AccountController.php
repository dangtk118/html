<?php
require_once Mage::getModuleDir('controllers', 'Mage_Customer').DS.'AccountController.php';
/**
 * Override AccountController, so that logout when redirect to current page,
 * instead of logout page
 * @author Thang Pham
 */
class Fahasa_Coreextended_Frontend_Customer_AccountController extends Mage_Customer_AccountController {
    
    /**
     * update fahasa contact log
     * **/
    public function seenContactAction(){
        $this->getRequest();
        $orderId = $_POST['orderId'];
        $listSeen = $_POST['listSeen'];
        Mage::helper('coreextended')->getSeenContactOrder($orderId, $listSeen);
    }

    //Comment for now as varnish cache current url
    //public function logoutAction()
    //{
    //    $url = Mage::getSingleton('core/session')->getLastUrl();
    //    $this->_getSession()->logout()
    //        ->renewSession();
    //    session_destroy();
    //    // $facebook->destroySession();
    //    $this->_redirectUrl($url);
    //}
    
    public function editPostAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/edit');
        }
	
	
        if ($this->getRequest()->isPost()) {
            /** @var $customer Mage_Customer_Model_Customer */
            $customer = $this->_getSession()->getCustomer();

            /** @var $customerForm Mage_Customer_Model_Form */
            $customerForm = $this->_getModel('customer/form');
            $customerForm->setFormCode('customer_account_edit')
                ->setEntity($customer);

            $customerData = $customerForm->extractData($this->getRequest());
	    
	    //donate when update account info
            $donateFpoint = 0;
	    $donateFpoint_list = Mage::helper('fahasa_customer')->getDonateFpoint($customer, ['gender', 'dob']);
	    
            // remove dob if customer had dob => no update
            if($customerData['dob'] == null || empty($customerData['dob'])){
                unset($customerData['dob']);
            }
	    else{
		if($customer->getDob()){
		    unset($customerData['dob']);
		}else{
		    if($donateFpoint_list['dob'] > 0){
			$donateFpoint = $donateFpoint + $donateFpoint_list['dob'];
		    }
		}
	    }
	    unset($customerData['email']);
	    
	    if($customerData['gender'] == null || empty($customerData['gender']) 
		    || ($customerData['gender'] != 1 && $customerData['gender'] != 2)){
		 unset($customerData['gender']);
	    }else{
		if(!$customer->getGender() && empty($customer->getGender())){
		    if($donateFpoint_list['gender'] > 0){
			$donateFpoint = $donateFpoint + $donateFpoint_list['gender'];
		    }
		}
	    }
	    
	    //check fpointstore VIP
	    $fpointstore_redirect = false;
	    $helper = Mage::helper("fpointstorev2/data");
	    if(!$customer->getCompanyId()){
		if($customerData['company_id']){
		    $vip_info = $helper->getVipInfo($customer->getEntityId(), $customerData['company_id']);
		    if($vip_info['id']){
			if($vip_info['customer_id'] && !$customer->getIsEditVip()){
			    unset($customerData['company_id']);
			}else{
			    $fpointstore_redirect = true;
			}
		    }
		}
	    }else{
		$vip_info = $helper->getVipInfo($customer->getEntityId(), $customer->getCompanyId(), false);
		if($vip_info['id']){
		    if($vip_info['customer_id'] && !$customer->getIsEditVip()){
			unset($customerData['company_id']);
		    }
		}else{
		    if($customerData['company_id']){
			$vip_info = $helper->getVipInfo($customer->getEntityId(), $customerData['company_id']);
			if($vip_info['id']){
			    if(!$vip_info['customer_id']){
				$fpointstore_redirect = true;
			    }
			}
		    }
		}
	    }
	    

            $errors = array();
            $customerErrors = $customerForm->validateData($customerData);
            if ($customerErrors !== true) {
                $errors = array_merge($customerErrors, $errors);
            } else {
                $customerForm->compactData($customerData);
                $errors = array();

                // If password change was requested then add it to common validation scheme
                if ($this->getRequest()->getParam('change_password')) {
                    $currPass   = $this->getRequest()->getPost('current_password');
                    $newPass    = $this->getRequest()->getPost('password');
                    $confPass   = $this->getRequest()->getPost('confirmation');

                    if ($this->_getSession()->getCustomer()->validatePassword($currPass)) {
                        if (strlen($newPass)) {
			    if($newPass == $confPass){
				$customer->setPassword($newPass);
				$customer->setPasswordConfirmation($confPass);
			    }else {
				$errors[] = $this->__('The confirmation password is incorrect');
			    }
			}else{
			    $errors[] = $this->__('New password field cannot be empty.');
			}
                    } else {
                        $errors[] = $this->__('Invalid current password');
                    }
                }

                // Validate account and compose list of errors if any
                $customerErrors = $customer->validate();
                if (is_array($customerErrors)) {
                    $errors = array_merge($errors, $customerErrors);
                }
            }

            if (!empty($errors)) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
                foreach ($errors as $message) {
                    $this->_getSession()->addError($message);
                }
                $this->_redirect('*/*/edit');
                return $this;
            }

            try {
                $customer->cleanPasswordsValidationData();
                $customer->save();
		Mage::helper('fahasa_customer')->updateNetcoreContact(true);
		
                $this->_getSession()->setCustomer($customer)
                    ->addSuccess($this->__('The account information has been saved.'));
		
		if($donateFpoint > 0){
		    Mage::helper('fahasa_customer')->donateFpointUpdateInfoAccount($customer->getEntityId(), $donateFpoint);
		}
//		if($fpointstore_redirect){
//		    $this->_redirectUrl('/fpointstore');
//		}else{
		    $this->_redirect('customer/account');
//		}
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the customer.'));
            }
        }

	    $this->_redirect('*/*/edit');
    }
}
