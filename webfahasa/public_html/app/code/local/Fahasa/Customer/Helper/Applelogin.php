<?php

require_once Mage::getModuleDir('', 'Fahasa_Customer') . '/lib/php-apple-signin/ASDecoder.php';
require_once Mage::getModuleDir('', 'Fahasa_Customer') . '/lib/php-apple-signin/Vendor/JWK.php';
require_once Mage::getModuleDir('', 'Fahasa_Customer') . '/lib/php-apple-signin/Vendor/JWT.php';
require_once Mage::getModuleDir('', 'Fahasa_Customer') . '/lib/fproject/php-jwt/src/BeforeValidException.php';
require_once Mage::getModuleDir('', 'Fahasa_Customer') . '/lib/fproject/php-jwt/src/JWT.php';
require_once Mage::getModuleDir('', 'Fahasa_Customer') . '/lib/fproject/php-jwt/src/ExpiredException.php';
require_once Mage::getModuleDir('', 'Fahasa_Customer') . '/lib/fproject/php-jwt/src/SignatureInvalidException.php';

//require_once Mage::getModuleDir('', 'Fahasa_Customer') . '/lib/fproject/php-jwt/src/JWT.php';

class Fahasa_Customer_Helper_Applelogin extends Mage_Core_Helper_Abstract {

    public function authenticateApple($apple_id, $identityToken)
    {
        $appleSignInPayload = AppleSignIn\ASDecoder::getAppleSignInPayload($identityToken);

        //Determine whether the client-provided user is valid.
        $isValid = $appleSignInPayload->verifyUser($apple_id);
        if ($isValid)
        {

            return array(
                "is_valid" => true,
                "apple_id" => $appleSignInPayload->getUser(),
                "email" => $appleSignInPayload->getEmail(),
            );
        }
        else
        {
            return array(
                "is_valid" => false
            );
        }
    }

    public function loginAppleByIdentityToken($apple_id, $identityToken, $version)
    {
        $result = array();
        $result['success'] = false;
        $result['message'] = '';
        $result['logined'] = false;
        try {
            $identityToken = trim($identityToken);

            if (empty($identityToken))
            {
                $result['message'] = $this->__('Login failed');
                return $result;
            }

            $authenticate = $this->authenticateApple($apple_id, $identityToken);
            if ($authenticate['is_valid'])
            {

                $result['message'] = $this->loginApple($authenticate, $version);

                if ($result['message'] != $this->__('An error occurred, please try again'))
                {
                    $result['success'] = true;
                    if ($result['message'] == 'LOGIN_PASS')
                    {
                        $result['logined'] = true;
                    }
                }
            }
            else
            {
                $result['message'] = $this->__('Login failed');
            }

            return $result;
        } catch (Exception $ex) {
            return $result;
        }
    }

    public function loginApple($authenticate, $version)
    {
        $apple_id = $authenticate["apple_id"];
        $email = $authenticate["email"];
        try {
            $customer = Mage::getModel('customer/customer')->loadByAppleId($apple_id);

            if (empty($customer->getEntityId()))
            {
                if (!empty($email))
                {
                    $customer = Mage::getModel("customer/customer")
                            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                            ->loadByEmail($email);
                    if (empty($customer->getEntityId()))
                    {
                        return $this->handleForAppleReview($authenticate, $version);
                    }
                    else
                    {
                        if (!(($customer->getConfirmation() && $customer->isConfirmationRequired())))
                        {
                            $this->setAppleUser($apple_id, $customer->getEntityId());
                            Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
                            return 'LOGIN_PASS';
                        }
                        else
                        {
                            return $this->__("The account has not been confirmed");
                        }
                    }
                }
                else
                {
                     return $this->handleForAppleReview($authenticate, $version);
                }
            }
            else
            {
                if (($customer->getConfirmation() && $customer->isConfirmationRequired()))
                {
                    return $this->__("The account has not been confirmed");
                }
                else
                {
                    Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
                    return 'LOGIN_PASS';
                }
            }
        } catch (Exception $ex) {
            
        }
        return $this->__('An error occurred, please try again');
    }

    public function handleForAppleReview($authenticate, $version){
        //only show login apple button with apple team
        //at review time, we set enable_login_apple = false with all customer, only apple see button with coming version
        if (Mage::getStoreConfig('appmobileversion/general/enable_login_apple') || empty($version)){
            return '';
        }else {
            $coming_ios_version = Mage::getStoreConfig('appmobileversion/general/version_ios');
            if ($version && $coming_ios_version == $version){
                $appleId = $authenticate['apple_id'];
                $email = $authenticate['email'];
                $firstname = $authenticate['first_name'];
                $lastname = $authenticate['last_name'];

                //Register account fb
                //create email like: notverify_appleId_123_appple@fahasa.com if email is null
                $result['message'] = Mage::helper('fahasa_customer')->registerApple($appleId, $appleId, $email, $firstname, $lastname, 'apple');

                if ($result['message'] == 'REGISTER_PASS')
                {
                    return 'LOGIN_PASS';
                }
            } else {
                return '';
            }
        }
    }
    
    //insert apple user
    public function setAppleUser($apple_id, $customer_id)
    {
        $result = false;
        try {
            $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
            $sql = "INSERT INTO fhs_apple_user(apple_id, customer_id, created_at) 
		    VALUES (:apple_id, :customer_id, now())
		    ON DUPLICATE KEY UPDATE 
		    customer_id=:customer_id;";
            $binds = array(
                "apple_id" => "$apple_id",
                "customer_id" => "$customer_id"
            );
            $writer->query($sql, $binds);
            $result = true;
        } catch (Exception $ex) {
            
        }
        return $result;
    }

    public function registerAppleAccount($apple_id, $identityToken, $phone, $otp, $firstname, $lastname)
    {
        $minLength = (int) Mage::getStoreConfig('customer/password/min_password_length');
        $result = array();
        $result['success'] = false;
        $result['message'] = '';

        $identityToken = trim($identityToken);
        $phone = trim($phone);
        $otp = trim($otp);

        if (empty($phone) || !is_numeric($phone))
        {
            $result['message'] = $this->__('Telephone invalid');
            goto end_check;
        }
        elseif (empty($otp))
        {
            $result['message'] = $this->__('OTP invalid');
            goto end_check;
        }
        elseif (empty($identityToken))
        {
            $result['message'] = $this->__("Login failed");
            goto end_check;
        }

        $otp_mess = Mage::helper('fahasa_customer')->checkOTP($phone, $otp);
        if ($otp_mess != $this->__('OTP is valid'))
        {
            $result['message'] = $otp_mess;
            goto end_check;
        }

        $authenticate = $this->authenticateApple($apple_id, $identityToken);

        end_check:
        //Check and return error message
        if ($result['message'])
        {
            return $result;
        }

        if ($authenticate['is_valid'])
        {
            $appleId = $authenticate['apple_id'];
            $email = $authenticate['email'];
            $firstname = $authenticate['first_name'];
            $lastname = $authenticate['last_name'];

            //Register account fb
            $result['message'] = Mage::helper('fahasa_customer')->registerfb($appleId, $phone, $email, $firstname, $lastname, 'apple');

            if ($result['message'] == 'REGISTER_PASS')
            {
                $result['success'] = true;
            }
        }
        else
        {
            $result['message'] = $this->__('Login failed');
        }

        return $result;
    }
    
   

}
