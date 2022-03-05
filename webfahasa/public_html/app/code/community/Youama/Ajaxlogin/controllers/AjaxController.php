<?php

/**
 * YouAMA.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA that is bundled with this package
 * on http://youama.com/freemodule-license.txt.
 *
 *******************************************************************************
 *                          MAGENTO EDITION USAGE NOTICE
 *******************************************************************************
 * This package designed for Magento Community edition. Developer(s) of
 * YouAMA.com does not guarantee correct work of this extension on any other
 * Magento edition except Magento Community edition. YouAMA.com does not
 * provide extension support in case of incorrect edition usage.
 *******************************************************************************
 *                                  DISCLAIMER
 *******************************************************************************
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future.
 *******************************************************************************
 * @category   Youama
 * @package    Youama_Ajaxlogin
 * @copyright  Copyright (c) 2012-2014 YouAMA.com (http://www.youama.com)
 * @license    http://youama.com/freemodule-license.txt
 */

/**
 * Handle ajax login and registration.
 * Class Youama_Ajaxlogin_AjaxController
 * @author doveid
 */
class Youama_Ajaxlogin_AjaxController extends Mage_Core_Controller_Front_Action
{
    /**
     * Root: ajaxlogin/ajax/index
     */
    public function indexAction()
    {
	$result = '';
        if (isset($_POST['ajax'])){
            // Login request
            if ($_POST['ajax'] == 'login' && Mage::helper('customer')->isLoggedIn() != true) {
                $login = Mage::getSingleton('youama_ajaxlogin/ajaxlogin');
                $result = $login->getResult();
            // Register request
//            } else if ($_POST['ajax'] == 'register' && Mage::helper('customer')->isLoggedIn() != true) {
//                $register = Mage::getSingleton('youama_ajaxlogin/ajaxregister');
//                $result = $register->getResult();
            }elseif(Mage::helper('customer')->isLoggedIn()){
		$result = 'success';
	    }
        }
	echo $result;
    }
    
    public function viewAction()
    {
    }
    
    public function checkPhoneOTPAction() {
        $phone = $this->getRequest()->getPost('phone', '');
        $otp = $this->getRequest()->getPost('otp', '');
        $result = Mage::helper('fahasa_customer')->checkPhoneOTP($phone, $otp);
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }

    public function checkEmailOTPAction(){
        $email = $this->getRequest()->getPost('email', '');
        $otp = $this->getRequest()->getPost('otp', '');

        $result = Mage::helper('fahasa_customer')->checkEmailOTP($email, $otp);
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }

    //Create account by phone number
    public function checkPhoneAction(){
	$phone = $this->getRequest()->getPost('phone', '');
	$channel = "web";
	$result = Mage::helper('fahasa_customer')->checkPhone($phone, $channel);
	return $this->getResponse()->setBody(json_encode($result))
	    ->setHeader('Content-Type', 'application/json');
    }
 
    
    public function registerAccountAction(){
        $phone = $this->getRequest()->getPost('username', '');
        $otp = $this->getRequest()->getPost('otp', '');
        $password = $this->getRequest()->getPost('password', '');

        $helper_customer = Mage::helper('fahasa_customer');
        $result = $helper_customer->registerAccountByPhone($phone, $otp, $password);
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }

    //Recovery password
    public function checkRecoveryPasswordAction(){
	$helper_customer = Mage::helper('fahasa_customer');
	$channel = "web";
	$result = array();
	$result['success'] = false;
	$result['message'] = '';
	$username = $this->getRequest()->getPost('username', '');
	$username = trim($username);
	
	$is_phone = is_numeric($username);
	
	if($is_phone){
	    if(empty($username) || !((strlen($username) >= 10) && (strlen($username) <= 11))){
		$result['message'] = $this->__("Phone number invalid");
		goto end_check;
	    }
	    $telephone_avalible = $helper_customer->getTelephoneAvalible($username);
	    if(count($telephone_avalible) <= 0) {
		$result['message'] = $this->__("Phone number isn't exist");
		goto end_check;
	    }
	}else{
	    if(empty($username) || !filter_var($username,FILTER_VALIDATE_EMAIL)){
		$result['message'] = $this->__("Email invalid");
		goto end_check;
	    }
	    $email_avalible = $helper_customer->getEmailAvalible($username);
	    if(count($email_avalible) <= 0) {
		$result['message'] = $this->__("Email isn't exist");
		goto end_check;
	    }
	}
	
	end_check:
	if($result['message']){
	    return $this->getResponse()->setBody(json_encode($result))
		->setHeader('Content-Type', 'application/json');
	}
	
	if($is_phone){
	    $result['message'] = $helper_customer->checkRecoveryPhoneValid($username, $channel);
	}else{
	    $result['message'] = $helper_customer->checkEmailValidForRecovery($username);
	}
	
	if($result['message'] == $this->__('OTP sent')){
	    $result['success'] = true;
	}
	
	return $this->getResponse()->setBody(json_encode($result))
	    ->setHeader('Content-Type', 'application/json');
    }
    
    public function recoveryAccountAction(){
        $helper_customer = Mage::helper('fahasa_customer');
        $username = $this->getRequest()->getPost('username', '');
        $otp = $this->getRequest()->getPost('otp', '');
        $password = $this->getRequest()->getPost('password', '');

        $result = $helper_customer->recoveryAccountByOtp($username, $otp, $password);
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }
    
    //facebook
    public function authenticateFB($accessToken){
        $url = "https://graph.facebook.com/me?fields=id,first_name,last_name,email&access_token=" . $accessToken; 
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
    
    public function loginfbAction(){
        $accessToken = $this->getRequest()->getPost('accessToken', '');
        $result = Mage::helper('fahasa_customer')->loginFacebookByAccessToken($accessToken);

        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }

    public function checkPhoneConfirmAction() {
        $phone = $this->getRequest()->getPost('phone', '');
        $channel = "web";
        $result = Mage::helper('fahasa_customer')->checkPhoneConfirm($phone, $channel);
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }

    public function registerFaccbookAccountAction(){
        $accessToken = $this->getRequest()->getPost('accessToken', '');
        $phone = $this->getRequest()->getPost('phone', '');
        $otp = $this->getRequest()->getPost('otp', '');

        $result = Mage::helper('fahasa_customer')->registerFacebookAccount($accessToken, $phone, $otp);
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }
    
    public function registerAccountQuickAction(){
        $orderid = $this->getRequest()->getPost('order_id', '');
        $phone = $this->getRequest()->getPost('username', '');
        $otp = $this->getRequest()->getPost('otp', '');
        $password = $this->getRequest()->getPost('password', '');

        $helper_customer = Mage::helper('fahasa_customer');
        $result = $helper_customer->registerAccountByPhone($phone, $otp, $password, $orderid);
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }

}