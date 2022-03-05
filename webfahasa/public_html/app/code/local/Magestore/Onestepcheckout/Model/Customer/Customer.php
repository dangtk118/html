<?php

class Magestore_Onestepcheckout_Model_Customer_Customer extends Mage_Customer_Model_Customer
{    

    /**
     * Validate customer attribute values.
     * For existing customer password + confirmation will be validated only when password is set (i.e. its change is requested)
     *
     * @return bool
     */
    public function validate()
    {
        if(Mage::helper('onestepcheckout')->enabledOnestepcheckout()){
			return true;
		}	
		
		return parent::validate();
    }
    
    public function sendEmailWithFacebookId($facebookId = '', $storeId = '0', $confirmEmail = null){
        if (!$storeId) {
            $storeId = $this->_getWebsiteStoreId($this->getSendemailStoreId());
        }

        $fbConfirmHelper = Mage::helper("facebookuser/confirm");
        //replaceEmail: in case when customer login facebook with 123@fb.fahasa.com -> then they provide email, we must send confirm email to replaceEmail
        if ($confirmEmail){
            $this->setEmail($confirmEmail);
            $facebookKey = $fbConfirmHelper->generateKeyConfirmWithConfirmEmail($facebookId, $confirmEmail);
        }
        else{
            $facebookKey = $fbConfirmHelper->generateKeyConfirm($facebookId);
        }
        
        if ($facebookKey){
            $isConfirm = $this->getConfirmation() ? true : false;
            try{
                parent::_sendEmailTemplate(
                'onestepcheckout/confirm_account/facebook_email_template',
                parent::XML_PATH_REGISTER_EMAIL_IDENTITY, 
                array(
                    'customer' => $this, 
                    'facebookId' => $facebookId, 
                    'facebookKey' => $facebookKey,
                    'back_url' => '',
                    'isConfirm' => $isConfirm
                    ), $storeId);
            } catch (Exception $ex) {
                return false;
            }
            return true;
        }
        
        return false;
        
    }
}
