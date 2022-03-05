<?php

class Magestore_Onestepcheckout_IndexController extends Mage_Core_Controller_Front_Action {

    public function testAction() {
        Zend_Debug::dump($this->getOnepage()->getQuote()->getShippingAddress()->getData());
        die();
        $values = Mage::helper('onestepcheckout')->getFieldEnables();
        $i = 0;
        $fields = array();

        Zend_Debug::dump(Mage::app()->getWebsite()->getData());
        Zend_Debug::dump(Mage::app()->getStore()->getData());
        foreach ($values as $value) {
            Zend_Debug::dump($value);
        }
        die('---');
    }

    public function indexAction() {
        // delete fhs Coin Code
//        Mage::getSingleton('core/session')->setCoinCode('');
	
	if(Mage::getStoreConfig("customer/startup/require_login")){
	    if(!Mage::getSingleton('customer/session')->isLoggedIn()){
		$this->_redirect('customer/account/login', array('goback'=>'cart'));
		return;
	    }
	}
	       
        if (!Mage::helper('magenotification')->checkLicenseKey('Onestepcheckout')) {
            Mage::getSingleton('core/config')->saveConfig('onestepcheckout/general/active', 0);
            return $this->_redirect('checkout/onepage');
        }

        if (!Mage::helper('onestepcheckout')->enabledOnestepcheckout()) {
            $this->_redirect('checkout/onepage');
            return;
        }
        $this->enableCustomerFields();
//        $quote = $this->getOnepage()->getQuote();
        $quote = Mage::helper('rediscart/cart')->getStaticQuote();
        
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message');
            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }

        if (!$quote->getBillingAddress()->getData('country_id')) {
            if (Mage::getStoreConfig('onestepcheckout/general/country_id')) {
                Mage::getModel('checkout/session')->getQuote()->getBillingAddress()->setData('country_id', Mage::getStoreConfig('onestepcheckout/general/country_id'))->save();
            }
        }
        $checkNull = 1;
        $helper = Mage::helper('onestepcheckout');
        for ($i = 0; $i < 15; $i++) {
            if ($helper->getDefaultField($i)) {
                $checkNull = 0;
                break;
            }
        }
        if ($checkNull == 1) {
            $arrayDefaults = $helper->getDefaultPositionArray();
            foreach ($arrayDefaults as $number => $value) {
                $model = Mage::getModel('onestepcheckout/config');
                $model->setScope('default')
                        ->setScopeId(0)
                        ->setPath('onestepcheckout/field_position_management/row_' . $number)
                        ->setValue($value);
                $model->save();
            }
        }
        $this->loadLayout();
        $this->_initLayoutMessages('checkout/session');
        $this->getLayout()->getBlock('head')->setTitle($this->__('One Step Checkout'));
        $this->renderLayout();
    }

    //check if email is registered
    private function _emailIsRegistered($email_address) {
        $model = Mage::getModel('customer/customer');
        $model->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email_address);
        if ($model->getId()) {
            return true;
        } else {
            return false;
        }
    }

    public function getOnepage() {
        return Mage::getSingleton('checkout/type_onepage');
    }

    public function getSession() {
        return Mage::getSingleton('checkout/session');
    }

    /*
     * check if an email is valid
     */

    public function is_valid_emailAction() {
        $validator = new Zend_Validate_EmailAddress();
        $email_address = $this->getRequest()->getPost('email_address');
        $message = 'Invalid';
        if ($email_address != '') {
            // Check if email is in valid format
            if (!$validator->isValid($email_address)) {
                $message = 'invalid';
            } else {
                //if email is valid, check if this email is registered
                if ($this->_emailIsRegistered($email_address)) {
                    $message = 'exists';
                } else {
                    $message = 'valid';
                }
            }
        }
        $result = array('message' => $message);
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function show_loginAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function show_passwordAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    /*
     * send new password to customer 
     */

    public function retrievePasswordAction() {
        $email = $this->getRequest()->getPost('email', false);
        $result = array();
        if ($email) {
            $customer = Mage::getModel('customer/customer')
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                    ->loadByEmail($email);
            if ($customer->getId()) {
                try {
                    $newPassword = $customer->generatePassword();
                    $customer->changePassword($newPassword, false);
                    $customer->sendPasswordReminderEmail();
                    $result = array('success' => true);
                } catch (Exception $e) {
                    $result = array('success' => false, 'error' => $e->getMessage());
                }
            } else {
                $result = array('success' => false, 'error' => 'This email address was not found in our records.');
            }
        }
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    /*
     * show term and condition pop up
     */

    public function show_term_conditionAction() {
        $helper = Mage::helper('onestepcheckout');
        if ($helper->enableTermsAndConditions()) {
            $html = $helper->getTermsConditionsHtml();
            echo $html;
            echo '<p class="a-right"><a href="#" onclick="javascript:TINY.box.hide();return false;">Close</a></p>';
        }
    }

    /*
     * add coupon to the order
     * copy from CartController.php
     */
    public function add_vipAction() {
        $vip_id = str_replace(' ','',(string) $this->getRequest()->getPost('vip_id'));
        try{
            // validate member VIP            
            $member_vip = Mage::helper('vip')->isIdVipMember($vip_id);
            if(!$member_vip){
                $error = true;
                $message = $this->__('Mã không hợp lệ. Vui lòng kiểm tra lại!.');
            }else{
                //Save to session so observer can pick it up
                Mage::getSingleton('customer/session')->setData("vip_id", $vip_id);                
            }
        } catch (Exception $e) {
            $error = true;
            $message = $this->__('Cannot apply the VIP Id.');
        }
        //reload HTML for review order section
        $reviewHtml = $this->_getReviewTotalHtml();
        $result = array(
            'error' => $error,
            'message' => $message,
            'review_html' => $reviewHtml
        );
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }
    
    public function add_couponAction() {
        $couponCode = (string) $this->getRequest()->getPost('coupon_code', '');
        $quote = $this->getOnepage()->getQuote();
        
        $helper = Mage::helper('onestepcheckout');
        $data = $helper->handleCoupponCode($couponCode, $quote, $this->getRequest()->getParam('remove'), FALSE);
        
        //reload HTML for review order section
        $reviewHtml = $this->_getReviewTotalHtml();
        $result = array(
            'error' => $data['error'],
            'message' => $data['message'],
            'review_html' => $reviewHtml
        );
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }
    
    /*
     * process login action in check out.
     */

    public function loginPostAction() {
        $email = $this->getRequest()->getPost('email', false);
        $password = $this->getRequest()->getPost('password', false);

        $error = '';
        if ($email && $password) {
            try {
                $this->_getCustomerSession()->login($email, $password);
            } catch (Exception $ex) {
                $error = $ex->getMessage();
            }
        }
        $result = array();
        $result['error'] = $error;
        if ($error == '')
            $result['success'] = true;
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    /*
     * save billing & shipping address
     */
    /* Thinhnd */

    public function saveAddressOnestepcheckoutAction() {
        $billing_data = $this->getRequest()->getPost('billing', false);
        $shipping_data = $this->getRequest()->getPost('shipping', false);
        $shipping_method = $this->getRequest()->getPost('shipping_method', false);
        $billing_address_id = $this->getRequest()->getPost('billing_address_id', false);
        $shipping_address_id = $this->getRequest()->getPost('shipping_address_id', false);
        
        $uncheckFreeship = $this->getRequest()->getPost('uncheck_freeship');
        if (isset($uncheckFreeship)) { 
            $session = Mage::getSingleton('checkout/session');
            if ($uncheckFreeship == 1) {                
                $session->unsetData('onestepcheckout_freeship');
                $session->unsetData('onestepcheckout_freeship_amount');
            } else {
                $session->setData('onestepcheckout_freeship',1);
            }
        }
        
        Mage::helper('onestepcheckout')->saveAddressShipping($this->getOnepage(), $billing_data, $shipping_data, $billing_address_id, $shipping_address_id, $shipping_method);

        $this->loadLayout(false);
        $this->renderLayout();
    }
    
    /*
     * save shipping & payment method 
     */

    public function save_shippingAction() {
        $shipping_method = $this->getRequest()->getPost('shipping_method', '');
        $payment_method = $this->getRequest()->getPost('payment_method', false);
        $billing_data = $this->getRequest()->getPost('billing', false);
        $payment = $this->getRequest()->getPost('payment', array());
        $countryId = $billing_data['country_id'];
        
        Mage::helper('onestepcheckout')->saveMethod($this->getOnepage(), $countryId, $shipping_method, $payment_method, $payment);
        $this->loadLayout(false);
        $this->renderLayout();
    }

    public function saveOrderAction() {
        $post = $this->getRequest()->getPost();
        $isAjax = $this->getRequest()->getParam('isAjax');
	$result_response = array();
	$result_response['url'] =  "";
	$result_response['success'] = false;
        if (!$post)
	    goto exit_purchase;
        
        /*
         * Add order to queue if it's flashsale order
         */
        $enable_order_queue = Mage::getStoreConfig('flashsale_config/config/enable_queue');
        if ($enable_order_queue == null){
            $enable_order_queue = true;
        }
        
        $paymentData = $this->getRequest()->getPost('payment', array());
        $paymentMethod = null;
        if (count($paymentData) > 0) {
            $paymentMethod = $paymentData['method'];
        }
	
	//validateMinimumAmount cart
	if (!Mage::getSingleton('checkout/session')->getQuote()->validateMinimumAmount()) {
	    $minimumAmount = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())
		->toCurrency(Mage::getStoreConfig('sales/minimum_order/amount'));

	    $warning = Mage::getStoreConfig('sales/minimum_order/description')
		? Mage::getStoreConfig('sales/minimum_order/description')
		: Mage::helper('checkout')->__('Minimum order amount is %s', $minimumAmount);
	    $result_response['message'] =  $warning;
	    goto exit_purchase;
	}
        if ($enable_order_queue) {
//            $customerData = Mage::getSingleton('customer/session')->getCustomer();
//            $customer_id = $customerData->getId();
            $quote_id = Mage::getSingleton('checkout/session')->getQuote()->getId();

            $one_step_checkout_queue_helper = Mage::helper('onestepcheckout/queue');
             /// format lai kieu data hop voi function addOrderToQueue (web)
            $request = $this->getRequest();
            $dataCreateOrder = array(
                'billing' => $request->getPost('billing', array()),
                'shipping' => $request->getPost('shipping', array()),
                'onestepcheckout_freeship_checkbox' => $request->getPost("onestepcheckout_freeship_checkbox", false),
                'fm_fields' => $request->getPost("fm_fields"),
                'shippingMethod' => $request->getPost('shippingMethod', ''),
                'payment' => $request->getPost("payment", false),
                'coupon_code' => $request->getPost('coupon_code', false),
                'vipCode' => $request->getPost('vipCode', ''),
                'giftwrap' => $request->getPost('giftwrap', ''),
                'localeStoreId' => $request->getPost('localeStoreId', ''),
                'tryout' => $request->getPost('tryout', ''),
                'pickupLocation' => $request->getPost('pickupLocation', ''),
                'billing_address_id' => $request->getPost("billing_address_id", false),
                'shipping_address_id' => $request->getPost("shipping_address_id", false),
                "event_cart_option" => $request->getPost("event-cart-option", false),
            );
            
            //$result_response['success'] = $one_step_checkout_queue_helper->addOrderToQueue($this->getRequest(), $customer_id, $quote_id);
            /// code fix
            $result_response['success'] = $one_step_checkout_queue_helper->addOrderToQueue($dataCreateOrder, $quote_id);
	    if(!$result_response['success']){
		$result_response['url'] =  "/checkout/cart/";
	    }
            goto exit_purchase;
        }
	
        $error = false;
        $helper = Mage::helper('onestepcheckout');

        /// At this stage, $quote has an old grand total
        $onePage = Mage::getSingleton('checkout/type_onepage');
        $totalTemp = $onePage->getQuote()->getTotals();
        $grandTotalCheck = array_filter($totalTemp, function($item){
            return ($item['code'] == 'grand_total');
        });
        $old_grand_total = null;
        if (sizeof($grandTotalCheck) > 0){
            $grandTotalItem = $grandTotalCheck['grand_total'];
            $old_grand_total = $grandTotalItem['value'];
        }
        
        $billing_data = $this->getRequest()->getPost('billing', array());
        $shipping_data = $this->getRequest()->getPost('shipping', array());

        //2014.18.11 update VAT apply start
        if (isset($billing_data['taxvat'])) {
            $billing_data['vat_id'] = trim($billing_data['taxvat']);
            $shipping_data['vat_id'] = trim($billing_data['taxvat']);
        }
        //2014.18.11 update VAT apply end

        if (isset($billing_data['onestepcheckout_comment']))
            Mage::getModel('checkout/session')->setOSCCM($billing_data['onestepcheckout_comment']);

        //set checkout method 
        $checkoutMethod = '';
        if (!$this->_isLoggedIn()) {
            $checkoutMethod = 'guest';
            if ($helper->enableRegistration() || !$helper->allowGuestCheckout()) {
                $is_create_account = $this->getRequest()->getPost('create_account_checkbox');
                $email_address = $billing_data['email'];
                if ($is_create_account || !$helper->allowGuestCheckout()) {
                    if ($this->_emailIsRegistered($email_address)) {
                        $error = true;
                        Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Email is already registered.'));
                        $this->_redirect('*/*/index');
                    } else {
                        if (!$billing_data['customer_password'] || $billing_data['customer_password'] == '') {
                            $error = true;
                        } else if (!$billing_data['confirm_password'] || $billing_data['confirm_password'] == '') {
                            $error = true;
                        } else if ($billing_data['confirm_password'] !== $billing_data['customer_password']) {
                            $error = true;
                        }
                        if ($error) {
                            Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Please correct your password.'));
                            if ($isAjax) {
                                $result_response['url'] = Mage::getUrl('onestepcheckout/index/index');
                            } else {
                                $this->_redirect('*/*/index');
                            }
                        } else {
                            $checkoutMethod = 'register';
                        }
                    }
                }
            }
        }
        if ($checkoutMethod != '')
            $this->getOnepage()->saveCheckoutMethod($checkoutMethod);

        //to ignore validation for disabled fields

        /* Start: Modified by Daniel - 03/04/2015 - Improve Ajax speed */
        if (version_compare(Mage::getVersion(), '1.4.1.1', '<=')) {
            $this->setIgnoreValidation();
        }
        /* End: Modified by Daniel - 03/04/2015 - Improve Ajax speed */

        //resave billing address to make sure there is no error if customer change something in billing section before finishing order
        $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
        /* Start: Modified by Daniel - Improve speed */
        $result = array();
        if (isset($customerAddressId)) {
            $result = $this->getOnepage()->saveBilling($billing_data, $customerAddressId);
        }
        /* End: Modified by Daniel - Improve speed */
        if (isset($result['error'])) {
            $error = true;
            if (is_array($result['message']) && isset($result['message'][0]))
                Mage::getSingleton('checkout/session')->addError($result['message'][0]);
            else
                Mage::getSingleton('checkout/session')->addError($result['message']);
            if ($isAjax) {
                $result_response['url'] = Mage::getUrl('onestepcheckout/index/index');
            } else {
                $this->_redirect('*/*/index');
            }
        }

        //re-save shipping address
        $shipping_address_id = $this->getRequest()->getPost('shipping_address_id', false);
        if ($helper->isShowShippingAddress()) {
            if (!isset($billing_data['use_for_shipping']) || $billing_data['use_for_shipping'] != '1') {
                /* Start: Modified by Daniel - Improve speed */
                $result = array();
                if (isset($shipping_address_id)) {
                    $result = $this->getOnepage()->saveShipping($shipping_data, $shipping_address_id);
                }
                /* End: Modified by Daniel - Improve speed */
                if (isset($result['error'])) {
                    $error = true;
                    if (is_array($result['message']) && isset($result['message'][0]))
                        Mage::getSingleton('checkout/session')->addError($result['message'][0]);
                    else
                        Mage::getSingleton('checkout/session')->addError($result['message']);
                    $this->_redirect('*/*/index');
                }
            }
            else {
                $result['allow_sections'] = array('shipping');
                $result['duplicateBillingInfo'] = 'true';
                // $result = $this->getOnepage()->saveShipping($billing_data, $shipping_address_id); 
            }
        }

        //re-save shipping method
        $shipping_method = $this->getRequest()->getPost('shipping_method', '');
        if (!$this->isVirtual()) {
            $result = $this->getOnepage()->saveShippingMethod($shipping_method);
            if (isset($result['error'])) {
                $error = true;
                if (is_array($result['message']) && isset($result['message'][0])) {
                    Mage::getSingleton('checkout/session')->addError($result['message'][0]);
                } else {
                    Mage::getSingleton('checkout/session')->addError($result['message']);
                }
                if ($isAjax) {
                    $result_response['url'] = Mage::getUrl('onestepcheckout/index/index');
                } else {
                    $this->_redirect('*/*/index');
                }
            } else {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request' => $this->getRequest(), 'quote' => $this->getOnepage()->getQuote()));
            }
        }

        $paymentRedirect = false;
        //save payment method		
        try {
            $result = array();
            $payment = $this->getRequest()->getPost('payment', array());
            $result = $helper->savePaymentMethod($payment);
            if ($payment) {
                $this->getOnepage()->getQuote()->getPayment()->importData($payment);
            }
            $paymentRedirect = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = $this->__('Unable to set Payment Method.');
        }

        if (isset($result['error'])) {
            $error = true;
            Mage::getSingleton('checkout/session')->addError($result['error']);
            if ($isAjax) {
                $result_response['url'] = Mage::getUrl('onestepcheckout/index/index');
            } else {
                $this->_redirect('*/*/index');
            }
        }

        if ($paymentRedirect && $paymentRedirect != '') {
            Header('Location: ' . $paymentRedirect);
	    $result_response['url'] =  $paymentRedirect;
	    goto exit_purchase;
            exit();
        }
        
        /*
         * FlashSale Check
         */
        $flashsale_check = Mage::helper("flashsale/data")->checkFlashsaleRules($this->getOnepage()->getQuote());
        if(!$flashsale_check['result']){
            $this->_redirect('checkout/cart');
	    $result_response['url'] =  $this->getResponse()->getRedirect();
	    goto exit_purchase;
        }
        
        //only continue to process order if there is no error
        if ($error) {
            $this->_redirect('*/*/index');
	    $result_response['url'] =  $this->getResponse()->getRedirect();
	    goto exit_purchase;
        }

        /*
         * Final Rule
         */
        $fhsCoin = Mage::getSingleton('core/session')->getFhsCoin();
        $quote = $this->getOnepage()->getQuote(); 
        if ($fhsCoin['code']) { // check user use coinCode
            if ($quote->getShippingAddress()->getShippingMethod() == "freeshipping_freeshipping") {
                // Coin code can not apply with pickup location
                $this->getOnepage()->getQuote()->setIsCoin(0);
                $this->getOnepage()->getQuote()->setCoinCode(0);
                $this->getOnepage()->getQuote()->setAmountCoin(0);
                Mage::log("*** quote: " . $quote->getEntityId() . " email:" . $quote->getCustomerEmail() . "Coin code was canceled:" . $fhsCoin['code'] . " , because shipping method= freeshipping_freeshipping.", null, "fhs_coin.log");
            } else {
                $this->getOnepage()->getQuote()->setIsCoin(1);
                $this->getOnepage()->getQuote()->setCoinCode($fhsCoin['code']);
            }
        }

        /*
         * Check Fpoint
         */
        $session = Mage::getSingleton('checkout/session');
        $currentAmountFpointAccount = Mage::helper('tryout')->determinetryout();
        if($currentAmountFpointAccount <= 0){
            Mage::log("*** quote: " . $quote->getEntityId() . " email:" . $quote->getCustomerEmail() . "fpoint balance <= 0: Fpoint: " . $currentAmountFpointAccount, null, "fpoint.log");
            $session->unsetData('onestepcheckout_tryout');
            $session->unsetData('onestepcheckout_tryout_amount');

            $this->getOnepage()->getQuote()->setTotalsCollectedFlag(false)
                ->collectTotals()
                ->save();
        }
            
        /*
         * Check Freeship
         */
        $currentAmountFreeshipAccount = Mage::helper('freeship')->getFreeShip();
        $is_freeship = $session->getData('onestepcheckout_freeship');
        if ($is_freeship == 1 && $currentAmountFreeshipAccount <= 0) {
            Mage::log("*** quote: " . $quote->getEntityId() . " email:" . $quote->getCustomerEmail() . " freeship balance <= 0: Freeship: " . $currentAmountFreeshipAccount, null, "fpoint.log");
            $session->unsetData('onestepcheckout_freeship');
            $session->unsetData('onestepcheckout_freeship_amount');

            $this->getOnepage()->getQuote()->setTotalsCollectedFlag(false)
                    ->collectTotals()
                    ->save();
        } else if ($is_freeship == 1 && $currentAmountFreeshipAccount > 0) {
            // config sales_convert_quote_address
            $quote->getShippingAddress()->setIsFreeship(1);
            $quote->getShippingAddress()->setFreeshipAmount($session->getData('onestepcheckout_freeship_amount'));
        }
        
        $this->getOnepage()->getQuote()->setIsVip($_POST['is_vip']);
        $this->getOnepage()->getQuote()->setVipId($_POST['vip_id']);
        
        /*
         *  Compare quote Grand Total, between old and stored values
         *  This check is for flash sale and coupons. If a customer add products to cart, and flashsale/coupons
         *  are expired, then grand total changes.
         */
        /// At this stage, $quote has a new grand total
        $storeTotalTemp = Mage::getSingleton('checkout/type_onepage')->getQuote()->getTotals();
        $storeGrandTotalCheck = array_filter($storeTotalTemp, function($item) {
            return ($item['code'] == 'grand_total');
        });
        $stored_grand_total = null;
        if (sizeof($storeGrandTotalCheck) > 0) {
            $storeGrandTotalItem = $storeGrandTotalCheck['grand_total'];
            $stored_grand_total = $storeGrandTotalItem['value'];
        }
        if ((int) $old_grand_total != (int) $stored_grand_total) {
            $error = true;
            Mage::getSingleton('checkout/session')->addError($this->__('Grand total has changed. Please review your cart.'));
            $this->_redirect('checkout/cart');
	    $result_response['url'] =  $this->getResponse()->getRedirect();
	    goto exit_purchase;
        }
        
        /*
         * Newsletter Subscribe
         */
        if ($helper->isShowNewsletter()) {
            $news_billing = $this->getRequest()->getPost('billing');
            // $is_subscriber = $this->getRequest()->getPost('newsletter_subscriber_checkbox', false);	
            $is_subscriber = null;
            if (isset($news_billing['newsletter_subscriber_checkbox']))
                $is_subscriber = $news_billing['newsletter_subscriber_checkbox'];
            if ($is_subscriber) {
                $subscribe_email = '';
                //pull subscriber email from billing data
                if (isset($billing_data['email']) && $billing_data['email'] != '') {
                    $subscribe_email = $billing_data['email'];
                } else if ($this->_isLoggedIn()) {
                    $subscribe_email = Mage::helper('customer')->getCustomer()->getEmail();
                }
                //check if email is already subscribed
                $subscriberModel = Mage::getModel('newsletter/subscriber')->loadByEmail($subscribe_email);
                if ($subscriberModel->getId() === NULL) {
                    Mage::getModel('newsletter/subscriber')->subscribe($subscribe_email);
                } else if ($subscriberModel->getData('subscriber_status') != 1) {
                    $subscriberModel->setData('subscriber_status', 1);
                    try {
                        $subscriberModel->save();
                    } catch (Exception $e) {

                    }
                }
            }
        }
	
	//Save Tax
	if(isset($_POST['fm_fields'])){
	    if ($this->_isLoggedIn()) {
		$customer_id = $this->_getCustomerSession()->getCustomer()->getEntityId();
		$fm_vat_company = $_POST['fm_fields']['fm_vat_company'];
		$fm_vat_address = $_POST['fm_fields']['fm_vat_address'];
		$fm_vat_taxcode = $_POST['fm_fields']['fm_vat_taxcode'];
		$fm_vat_name = $_POST['fm_fields']['fm_vat_name'];
		$fm_vat_email = $_POST['fm_fields']['fm_vat_email'];
		if($fm_vat_company && $fm_vat_address && $fm_vat_taxcode && $fm_vat_name && $fm_vat_email){
		    $this->saveVAT($customer_id,$fm_vat_company, $fm_vat_address, $fm_vat_taxcode, $fm_vat_name, $fm_vat_email);
		}
	    }
	}

        try {
            $result = $this->getOnepage()->saveOrder();
            $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $redirect = Mage::getUrl('onestepcheckout/index/index');
            if ($isAjax) {
                $result_response['url'] = $redirect;
            } else {
                Header('Location: ' . $redirect);
                exit();
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $redirect = Mage::getUrl('onestepcheckout/index/index');
            if ($isAjax) {
                $result_response['url'] = $redirect;
            } else {
                Header('Location: ' . $redirect);
		exit();
            }
        }

        $this->getOnepage()->getQuote()->save();
        Mage::dispatchEvent('controller_action_postdispatch_checkout_onepage_saveOrder', array('post' => $post, 'controller_action' => $this));

        if ($redirectUrl) {
            $redirect = $redirectUrl;
        } else {
            $redirect = Mage::getUrl('checkout/onepage/success');
        }
	if($isAjax == 'queue'){
	    $result_response['url'] = $redirect;
	}
	else{
	    if ($isAjax == 'wirecard') {
		$this->getResponse()->setBody(json_encode($result_response));
	    } elseif ($isAjax == 'tco') {
		//Nothing to do here
		//tco payment response the JSON code automatically
	    } else {
		Header('Location: ' . $redirect);
		//exit();
	    }
	}
        
	exit_purchase:
        if ($isAjax == 'queue') {
	    $this->getResponse()->setBody(json_encode($result_response));
	}
	return;
    }

    public function saveOrderProAction() {
        $post = $this->getRequest()->getPost();
        $result = new stdClass();
        if (!$post)
            return;
        $error = false;
        $helper = Mage::helper('onestepcheckout');

        $billing_data = $this->getRequest()->getPost('billing', array());
        $shipping_data = $this->getRequest()->getPost('shipping', array());

        if (isset($billing_data['onestepcheckout_comment']))
            Mage::getModel('checkout/session')->setOSCCM($billing_data['onestepcheckout_comment']);

        //set checkout method 
        $checkoutMethod = '';
        if (!$this->_isLoggedIn()) {
            $checkoutMethod = 'guest';
            if ($helper->enableRegistration() || !$helper->allowGuestCheckout()) {
                $is_create_account = $this->getRequest()->getPost('create_account_checkbox');
                $email_address = $billing_data['email'];
                if ($is_create_account || !$helper->allowGuestCheckout()) {
                    if ($this->_emailIsRegistered($email_address)) {
                        $error = true;
                        Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Email is already registered.'));
                        $redirect = Mage::getUrl('onestepcheckout/index/index');
                        // Header('Location: ' . $redirect);
                        // exit();
                        $result->url = $redirect;
                        $this->getResponse()->setBody(json_encode($result));
                    } else {
                        if (!$billing_data['customer_password'] || $billing_data['customer_password'] == '') {
                            $error = true;
                        } else if (!$billing_data['confirm_password'] || $billing_data['confirm_password'] == '') {
                            $error = true;
                        } else if ($billing_data['confirm_password'] !== $billing_data['customer_password']) {
                            $error = true;
                        }
                        if ($error) {
                            Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Please correct your password.'));
                            $redirect = Mage::getUrl('onestepcheckout/index/index');
                            // Header('Location: ' . $redirect);
                            // exit();
                            $result->url = $redirect;
                            $this->getResponse()->setBody(json_encode($result));
                        } else {
                            $checkoutMethod = 'register';
                        }
                    }
                }
            }
        }
        if ($checkoutMethod != '')
            $this->getOnepage()->saveCheckoutMethod($checkoutMethod);

        //to ignore validation for disabled fields

        /* Start: Modified by Daniel - 03/04/2015 - Improve Ajax speed */
        if (version_compare(Mage::getVersion(), '1.4.1.1', '<=')) {
            $this->setIgnoreValidation();
        }
        /* End: Modified by Daniel - 03/04/2015 - Improve Ajax speed */

        //resave billing address to make sure there is no error if customer change something in billing section before finishing order
        $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
        $result = $this->getOnepage()->saveBilling($billing_data, $customerAddressId);
        if (isset($result['error'])) {
            $error = true;
            if (is_array($result['message']) && isset($result['message'][0]))
                Mage::getSingleton('checkout/session')->addError($result['message'][0]);
            else
                Mage::getSingleton('checkout/session')->addError($result['message']);
            $redirect = Mage::getUrl('onestepcheckout/index/index');
            // Header('Location: ' . $redirect);
            // exit();
            $result->url = $redirect;
            $this->getResponse()->setBody(json_encode($result));
        }

        //re-save shipping address
        $shipping_address_id = $this->getRequest()->getPost('shipping_address_id', false);
        if ($helper->isShowShippingAddress()) {
            if (!isset($billing_data['use_for_shipping']) || $billing_data['use_for_shipping'] != '1') {
                $result = $this->getOnepage()->saveShipping($shipping_data, $shipping_address_id);
                if (isset($result['error'])) {
                    $error = true;
                    if (is_array($result['message']) && isset($result['message'][0]))
                        Mage::getSingleton('checkout/session')->addError($result['message'][0]);
                    else
                        Mage::getSingleton('checkout/session')->addError($result['message']);
                    $redirect = Mage::getUrl('onestepcheckout/index/index');
                    // Header('Location: ' . $redirect);
                    // exit();
                    $result->url = $redirect;
                    $this->getResponse()->setBody(json_encode($result));
                }
            }
            else {
                $result['allow_sections'] = array('shipping');
                $result['duplicateBillingInfo'] = 'true';
                // $result = $this->getOnepage()->saveShipping($billing_data, $shipping_address_id); 
            }
        }

        //re-save shipping method
        $shipping_method = $this->getRequest()->getPost('shipping_method', '');
        if (!$this->isVirtual()) {
            $result = $this->getOnepage()->saveShippingMethod($shipping_method);
            if (isset($result['error'])) {
                $error = true;
                if (is_array($result['message']) && isset($result['message'][0])) {
                    Mage::getSingleton('checkout/session')->addError($result['message'][0]);
                } else {
                    Mage::getSingleton('checkout/session')->addError($result['message']);
                }
                $redirect = Mage::getUrl('onestepcheckout/index/index');
                // Header('Location: ' . $redirect);
                // exit();
                $result->url = $redirect;
                $this->getResponse()->setBody(json_encode($result));
            } else {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request' => $this->getRequest(), 'quote' => $this->getOnepage()->getQuote()));
            }
        }

        $paymentRedirect = false;
        //save payment method		
        try {
            $result = array();
            $payment_method = $this->getRequest()->getPost('payment', array());
            $payment['method'] = $payment_method;
            $result = $helper->savePaymentMethod($payment);
            if ($payment) {
                $this->getOnepage()->getQuote()->getPayment()->importData($payment);
            }
            $paymentRedirect = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
        } catch (Mage_Payment_Exception $e) {

            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {

            $result['error'] = $e->getMessage();
        } catch (Exception $e) {

            Mage::logException($e);
            $result['error'] = $this->__('Unable to set Payment Method.');
        }

        if (isset($result['error'])) {
            $error = true;
            Mage::getSingleton('checkout/session')->addError($result['error']);
            $redirect = Mage::getUrl('onestepcheckout/index/index');
            $result->url = $redirect;
            $this->getResponse()->setBody(json_encode($result));
        }

        if ($paymentRedirect && $paymentRedirect != '') {
            $result = new stdClass();
            $result->url = $paymentRedirect;
            $this->getResponse()->setBody(json_encode($result));
        } else {

            //only continue to process order if there is no error
            if (!$error) {
                //newsletter subscribe
                if ($helper->isShowNewsletter()) {
                    $news_billing = $this->getRequest()->getPost('billing');
                    $is_subscriber = null;
                    if (isset($news_billing['newsletter_subscriber_checkbox']))
                        $is_subscriber = $news_billing['newsletter_subscriber_checkbox'];
                    // var_dump($is_subscriber);die();
                    if ($is_subscriber) {
                        $subscribe_email = '';
                        //pull subscriber email from billing data
                        if (isset($billing_data['email']) && $billing_data['email'] != '') {
                            $subscribe_email = $billing_data['email'];
                        } else if ($this->_isLoggedIn()) {
                            $subscribe_email = Mage::helper('customer')->getCustomer()->getEmail();
                        }
                        //check if email is already subscribed
                        $subscriberModel = Mage::getModel('newsletter/subscriber')->loadByEmail($subscribe_email);
                        if ($subscriberModel->getId() === NULL) {
                            Mage::getModel('newsletter/subscriber')->subscribe($subscribe_email);
                        } else if ($subscriberModel->getData('subscriber_status') != 1) {
                            $subscriberModel->setData('subscriber_status', 1);
                            try {
                                $subscriberModel->save();
                            } catch (Exception $e) {
                                
                            }
                        }
                    }
                }

                try {
                    $result = $this->getOnepage()->saveOrder();
                    $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
                } catch (Mage_Core_Exception $e) {
                    Mage::logException($e);
                    Mage::getSingleton('checkout/session')->addError($e->getMessage());
                    Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                    $redirect = Mage::getUrl('onestepcheckout/index/index');
                    // Header('Location: ' . $redirect);
                    // exit();
                    $result->url = $redirect;
                    $this->getResponse()->setBody(json_encode($result));
                } catch (Exception $e) {
                    Mage::logException($e);
                    Mage::getSingleton('checkout/session')->addError($e->getMessage());
                    Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                    $redirect = Mage::getUrl('onestepcheckout/index/index');
                    // Header('Location: ' . $redirect);
                    // exit();
                    $result->url = $redirect;
                    $this->getResponse()->setBody(json_encode($result));
                }
                $this->getOnepage()->getQuote()->save();

                if ($payment['method'] == 'hosted_pro') {
                    $this->loadLayout('checkout_onepage_review');
                    $html = $this->getLayout()->getBlock('paypal.iframe')->toHtml();

                    $result->html = $html;
                    $result->url = 'null';

                    $this->getResponse()->setBody(json_encode($result));
                } else {
                    if ($redirectUrl) {
                        $redirect = $redirectUrl;
                    } else {
                        $redirect = Mage::getUrl('checkout/onepage/success');
                    }
                    $result->html = '';
                    $result->url = $redirect;

                    $this->getResponse()->setBody(json_encode($result));
                }
            } else {
                $result = new stdClass();
                $redirect = Mage::getUrl('onestepcheckout/index/index');
                // Header('Location: ' . $redirect);
                // exit();
                $result->url = $redirect;
                $this->getResponse()->setBody(json_encode($result));
            }
        }
    }

    protected function _getCustomerSession() {
        return Mage::getSingleton('customer/session');
    }

    /*
     * Reload shipping method html
     */

    protected function _getShippingMethodsHtml() {
        //$this->_cleanLayoutCache();
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('onestepcheckout_onestepcheckout_shippingmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    /*
     * Reload payment method html
     */

    public function _getPaymentMethodsHtml() {
        //$this->_cleanLayoutCache();
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('onestepcheckout_onestepcheckout_paymentmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    public function _getReviewTotalHtml() {
        //$this->_cleanLayoutCache();
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('onestepcheckout_onestepcheckout_review');
        $layout->unsetBlock('shippingmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    protected function _isLoggedIn() {
        return $this->_getCustomerSession()->isLoggedIn();
    }

    public function isVirtual() {
        return $this->getOnepage()->getQuote()->isVirtual();
    }

    /*
     * this function is to pass the validation 
     * Only available for Magento 1.4.x 
     */

    public function setIgnoreValidation() {
        $this->getOnepage()->getQuote()->getBillingAddress()->setShouldIgnoreValidation(true);
        $this->getOnepage()->getQuote()->getShippingAddress()->setShouldIgnoreValidation(true);
    }

    protected function _cleanLayoutCache() {
        Mage::app()->cleanCache(LAYOUT_GENERAL_CACHE_TAG);
    }

    public function enableCustomerFields() {
        $helper = Mage::helper('onestepcheckout');
        $fieldValue = $helper->getFieldValue();
        $prefix = 0;
        $suffix = 0;
        $middlename = 0;
        $birthday = 0;
        $gender = 0;
        $taxvat = 0;
        $fields = $helper->getFieldEnables();
        for ($i = 0; $i < 20; $i++) {
            if ($fields[$i]['value'] == 'prefix')
                $prefix = 1;
            if ($fields[$i]['value'] == 'suffix')
                $suffix = 1;
            if ($fields[$i]['value'] == 'middlename')
                $middlename = 1;
            if ($fields[$i]['value'] == 'birthday')
                $birthday = 1;
            if ($fields[$i]['value'] == 'gender')
                $gender = 1;
            if ($fields[$i]['value'] == 'taxvat')
                $taxvat = 1;
        }

        try {
            if ($prefix == 1) {
                if ($helper->getFieldRequire('prefix')) {
                    Mage::getConfig()->saveConfig('customer/address/prefix_show', 'req');
                    $this->updateAttribute('prefix', 'reg');
                } else {
                    Mage::getConfig()->saveConfig('customer/address/prefix_show', 'opt');
                    $this->updateAttribute('prefix', 'opt');
                }
            }
            if ($suffix == 1) {
                if ($helper->getFieldRequire('suffix')) {
                    Mage::getConfig()->saveConfig('customer/address/suffix_show', 'req');
                    $this->updateAttribute('suffix', 'req');
                } else {
                    Mage::getConfig()->saveConfig('customer/address/suffix_show', 'opt');
                    $this->updateAttribute('suffix', 'opt');
                }
            }
            if ($middlename == 1) {
                Mage::getConfig()->saveConfig('customer/address/middlename_show', '1');
                $this->updateAttribute('middlename', '1');
            }
            if ($birthday == 1) {
                if ($helper->getFieldRequire('birthday')) {
                    Mage::getConfig()->saveConfig('customer/address/dob_show', 'req');
                    $this->updateAttribute('dob', 'req');
                } else {
                    Mage::getConfig()->saveConfig('customer/address/dob_show', 'opt');
                    $this->updateAttribute('dob', 'opt');
                }
            }
            if ($gender == 1) {
                if ($helper->getFieldRequire('gender')) {
                    Mage::getConfig()->saveConfig('customer/address/gender_show', 'req');
                    $this->updateAttribute('gender', 'req');
                } else {
                    Mage::getConfig()->saveConfig('customer/address/gender_show', 'opt');
                    $this->updateAttribute('gender', 'opt');
                }
            }
            if ($taxvat == 1) {
                if ($helper->getFieldRequire('taxvat')) {
                    Mage::getConfig()->saveConfig('customer/address/taxvat_show', 'req');
                    $this->updateAttribute('taxvat', 'req');
                } else {
                    Mage::getConfig()->saveConfig('customer/address/taxvat_show', 'opt');
                    $this->updateAttribute('taxvat', 'opt');
                }
            }
        } catch (Exception $e) {
            
        }
    }

    public function updateAttribute($attribute, $option) {
        $attributeObject = Mage::getSingleton('eav/config')->getAttribute('customer', $attribute);
        $valueConfig = array(
            '' => array('is_required' => 0, 'is_visible' => 0),
            'opt' => array('is_required' => 0, 'is_visible' => 1),
            '1' => array('is_required' => 0, 'is_visible' => 1),
            'req' => array('is_required' => 1, 'is_visible' => 1),
        );
        $data = $valueConfig[$option];
        $attributeObject->setData('is_required', $data['is_required']);
        $attributeObject->setData('is_visible', $data['is_visible']);
        $attributeObject->save();
    }

    public function getreionidAction() {
        $data = $this->getRequest()->getPost();
        $resion = Mage::getModel('directory/region')->getCollection()
                ->addFieldToFilter('country_id', $data['country'])
                ->addFieldToFilter('code', $data['region_id'])
                ->getFirstItem();
        $result = array();
        $result['id'] = $resion->getId();

        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    protected function _getCart() {
        return Mage::getSingleton('checkout/cart');
    }

    /* Start: Modified by Daniel - 02042015 - reload data after modify items - decrease ajax request */

    public function deleteproductAction() {
        $session = $this->getSession();
        $id = (int) $this->getRequest()->getParam('id');
        $result = array();
        $result['success'] = false;
        if ($id) {
            try {
                $this->_getCart()->removeItem($id)
                        ->save();
                $result['success'] = true;
                if (!$this->_getCart()->getQuote()->getItemsCount()) {
                    $result['url'] = Mage::getUrl('checkout/cart', array('_secure' => true));
                }
            } catch (Exception $e) {
                $result['error'] = Mage::helper('onestepcheckout')->__('Cannot remove the item.');
                Mage::logException($e);
            }
        }
        if (isset($result['error']))
            $session->setData('error', $result['error']);
        if (isset($result['url']))
            $session->setData('url', $result['url']);
        if ($result['success'])
            $session->setData('success', true);
        $this->loadLayout(false);
        $this->renderLayout();
    }

    public function minus_productAction() {
        $session = $this->getSession();
        $id = (int) $this->getRequest()->getParam('id');
        /* Start: Huy - fix bug not accept decimal number */
        $qty = (double) $this->getRequest()->getParam('qty');
        /* End: Huy - fix bug not accept decimal number */
        $result = array();
        $result['success'] = false;
        /* Start: added by Daniel - 31/03/2015 - qty increment */
        $citem = Mage::getModel('checkout/session')->getQuote()->getItemById($id);
        if (isset($citem)) {
            $productId = $citem->getProductId();
            $product = Mage::getModel('catalog/product')->load($productId);
            if (isset($product)) {
                $productData = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                if ($productData->getEnableQtyIncrements() == true) {
                    $qtyIncrements = $productData->getQtyIncrements();
                }
            }
        }
        if (isset($qtyIncrements))
            $cartData = array($id => array('qty' => $qty - $qtyIncrements));
        else
        /* End: added by Daniel - 31/03/2015 - qty increment */
            $cartData = array($id => array('qty' => $qty - 1));

        try {


            $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
            );
            foreach ($cartData as $index => $data) {
                if (isset($data['qty'])) {
                    $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                }
            }
            $cart = $this->_getCart();
            if (!$cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                $cart->getQuote()->setCustomerId(null);
            }

            $cartData = $cart->suggestItemsQty($cartData);
            $cart->updateItems($cartData)
                    ->save();

            $result['qty'] = $cartData[$id]['qty'];
            if (!$this->_getCart()->getQuote()->getItemsCount()) {
                $result['url'] = Mage::getUrl('checkout/cart', array('_secure' => true));
            }
            $result['success'] = true;
        } catch (Mage_Core_Exception $e) {
            $result['error'] = Mage::helper('core')->escapeHtml($e->getMessage());
        } catch (Exception $e) {
            $result['error'] = $this->__('Cannot update shopping cart.');
            Mage::logException($e);
        }

        if (isset($result['error']))
            $session->setData('error', $result['error']);
        if (isset($result['url']))
            $session->setData('url', $result['url']);
        if ($result['success'])
            $session->setData('success', true);
        $this->loadLayout(false);
        $this->renderLayout();
    }

    public function add_productAction() {
        $session = $this->getSession();
        $id = (int) $this->getRequest()->getParam('id');
        /* Start: Huy - fix bug not accept decimal number */
        $qty = (double) $this->getRequest()->getParam('qty');
        /* End: : Huy - fix bug not accept decimal number */
        $result = array();
        /* Start: added by Daniel - 31/03/2015 - qty increment */
        $citem = Mage::getModel('checkout/session')->getQuote()->getItemById($id);
        if (isset($citem)) {
            $productId = $citem->getProductId();
            $product = Mage::getModel('catalog/product')->load($productId);
            if (isset($product)) {
                $productData = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                if ($productData->getEnableQtyIncrements() == true) {
                    $qtyIncrements = $productData->getQtyIncrements();
                }
            }
        }
        if (isset($qtyIncrements))
            $cartData = array($id => array('qty' => $qty + $qtyIncrements));
        else
        /* End: added by Daniel - 31/03/2015 - qty increment */
            $cartData = array($id => array('qty' => $qty + 1));

        $result['success'] = false;

        try {
            $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
            );
            foreach ($cartData as $index => $data) {
                if (isset($data['qty'])) {
                    $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                }
            }
            $cart = $this->_getCart();
            if (!$cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                $cart->getQuote()->setCustomerId(null);
            }

            $cartData = $cart->suggestItemsQty($cartData);
            $cart->updateItems($cartData)
                    ->save();
            $message = $cart->getQuote()->getMessages();
            if ($message) {
                $result['error'] = $message['qty']->getCode();
                $cartData = array($id => array('qty' => $qty));
                $cartData = $cart->suggestItemsQty($cartData);
                $cart->updateItems($cartData)->save();
            }
            $result['qty'] = $cartData[$id]['qty'];
            $result['success'] = true;
        } catch (Mage_Core_Exception $e) {
            $result['error'] = Mage::helper('core')->escapeHtml($e->getMessage());
        } catch (Exception $e) {
            $result['error'] = $this->__('Cannot update shopping cart.');
            Mage::logException($e);
        }

        if (isset($result['error']))
            $session->setData('error', $result['error']);
        if ($result['success'])
            $session->setData('success', true);
        $this->loadLayout(false);
        $this->renderLayout();
    }
    
    // get duplicated order
    function duplicatedOrderAction() {
        $dupArr = Mage::helper('onestepcheckout')->getDuplicatedOrder();
        return $this->getResponse()->setBody(json_encode($dupArr));
    }
    
    // get duplicated item
    function duplicatedItemAction() {
        $cart = Mage::getModel('checkout/cart')->getQuote();
        foreach ($cart->getAllItems() as $item) {
            if ($item->getQty() == 2 && $item->getPrice() > 0) {
                return $this->getResponse()->setBody(TRUE);
            }
        }
        return $this->getResponse()->setBody(FALSE);
    }
        

    /* End: Modified by Daniel - 02042015 - reload data after modify items - decrease ajax request */
    
    //update ward by customer address id
    public function updateWardAction() {
        $address_id = $this->getRequest()->getPost('address_id', false);
        $ward_name = $this->getRequest()->getPost('ward_name', false);
        $city_name = $this->getRequest()->getPost('city_name', false);
	
	$result = array();
	$result['success'] = $this->updateWard($address_id, $ward_name);
	if($city_name){
	    $result['city_success'] = $this->updateDistrict($address_id, $city_name);
	}
	$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	return;
    }
    
    public function updateWard($address_id, $ward_name){
	$result = false;
	$ward_att = Mage::getModel('eav/config')->getAttribute('customer_address', 'ward');
	$ward_att_id = $ward_att->getAttributeId();
        try{
	    $read = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select count(*) as 'count' from fhs_customer_address_entity_varchar where attribute_id = '".$ward_att_id."' and entity_id = '".$address_id."'";
	    $rows = $read->fetchAll($sql);
            if($rows[0]){
                if($rows[0]['count'] > 0){
		    $sql = "UPDATE fhs_customer_address_entity_varchar SET value=:ward_name WHERE entity_id=:entity_id AND attribute_id=:attribute_id;";
		}else{
		    $sql = "INSERT INTO fhs_customer_address_entity_varchar (entity_type_id, attribute_id, entity_id, value) VALUES(2, :attribute_id, :entity_id, :ward_name)";
		}	    
		$binds = array(
                    "attribute_id" => "$ward_att_id",
                    "entity_id" => "$address_id",
                    "ward_name" => "$ward_name"
		);
		$write = Mage::getSingleton("core/resource")->getConnection("core_write");
		$write->query($sql,$binds);
		$result = true;
            }
        } catch (Exception $ex) {}
        return $result;
    }
    
    public function updateDistrict($address_id, $district_name){
	$result = false;
	$city_att = Mage::getModel('eav/config')->getAttribute('customer_address', 'city');
	$city_att_id = $city_att->getAttributeId();
        try{
	    $read = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select count(*) as 'count' from fhs_customer_address_entity_varchar where attribute_id = '".$city_att_id."' and entity_id = '".$address_id."'";
	    $rows = $read->fetchAll($sql);
            if($rows[0]){
                if($rows[0]['count'] > 0){
		    $sql = "UPDATE fhs_customer_address_entity_varchar SET value=:city_name WHERE entity_id=:entity_id AND attribute_id=:attribute_id;";
		}else{
		    $sql = "INSERT INTO fhs_customer_address_entity_varchar (entity_type_id, attribute_id, entity_id, value) VALUES(2, :attribute_id, :entity_id, :city_name)";
		}	    
		$binds = array(
                    "attribute_id" => "$city_att_id",
                    "entity_id" => "$address_id",
                    "city_name" => "$district_name"
		);
		$write = Mage::getSingleton("core/resource")->getConnection("core_write");
		$write->query($sql,$binds);
		$result = true;
            }
        } catch (Exception $ex) {}
        return $result;
    }
    
    //get vat of customer
    public function getVATAction() {
	$result = $this->getVAT();
	$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	return;
    }
    
    public function getVAT(){
	$result = array();
	$result['success'] = false;
	$result['company'] = '';
	$result['address'] = '';
	$result['taxcode'] = '';
	$result['name'] = '';
	$result['email'] = '';
	$result['message'] = 'no data';
        try{
	    if ($this->_isLoggedIn()) {
		$customer_id = $this->_getCustomerSession()->getCustomer()->getEntityId();
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
	    }
        } catch (Exception $ex) {}
        return $result;
    }
    
    public function saveVAT($customer_id, $vat_company, $vat_address, $vat_taxcode, $vat_name = '', $vat_email = ''){
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
        } catch (Exception $ex) {}
    }
    
    
     public function redirectWaitingAction() {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'onestepcheckout', array('template' => 'onestepcheckout/redirectWaiting.phtml'));
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }

    public function redirectPendingAction() {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'onestepcheckout', array('template' => 'onestepcheckout/redirectPending.phtml'));
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }

    public function checkOrderStatusAction() {
        $result = Mage::helper('onestepcheckout/queue')->checkOrderIsProcessed();
     
        return $this->getResponse()->setBody(json_encode($result));
    }

    public function addGiftAction(){
        $apply = (boolean) $this->getRequest()->getParam('apply');
        $ruleId = $this->getRequest()->getParam('ruleId');

        $isApply = false;
        if ($apply == '1'){
            $isApply = true;
        }

        $almostCart = \Mage::helper("almostcart");
        $data = $almostCart->chooseFreeGift($ruleId, $isApply);
        
        return $this->getResponse()->setBody(json_encode($data));
    }
    
    public function couponCodeAction(){
	$rp = json_decode($this->getRequest()->getRawBody());
	$data = \Mage::helper('onestepcheckout')->addCouponCode($rp->couponCode, $rp->apply);
        return $this->getResponse()->setBody(json_encode($data))
                        ->setHeader('Content-Type', 'application/json');
    }
    
    public function tryoutAction(){
	$rp = json_decode($this->getRequest()->getRawBody());
	$data = \Mage::helper('onestepcheckout')->addTryout($rp->tryout);
        return $this->getResponse()->setBody(json_encode($data))
                        ->setHeader('Content-Type', 'application/json');
    }
    public function methods1Action(){
	$rp = json_decode($this->getRequest()->getRawBody());
	$data = \Mage::helper('onestepcheckout')->getShippingMethod1($rp->billing, $rp->shipping, $rp->shippingMethod, $rp->freeship);
        return $this->getResponse()->setBody(json_encode($data))
                        ->setHeader('Content-Type', 'application/json');
    }
    public function shippingAction(){
	$success = true;
	$rp = json_decode($this->getRequest()->getRawBody());
        $paymentMethod = $rp->paymentMethod;
	$onePage = \Mage::getSingleton('checkout/type_onepage');
        if ($paymentMethod == "cashondelivery") {
            $_POST['payment_method'] = "cashondelivery";
        }else{
            $payment['method'] = $paymentMethod;
        }
	
        \Mage::helper('onestepcheckout')->saveMethod($onePage, $rp->countryId, $rp->shippingMethod, $rp->paymentMethod, $payment);
        $data->checkout = \Mage::helper('onestepcheckout')->getCartJson();
        $data->success = $success;
        if ($rp->eventCart){
            $data->event_cart = \Mage::helper("eventcart")->checkEventCart(null, true, true);
        }
        return $this->getResponse()->setBody(json_encode($data))
                        ->setHeader('Content-Type', 'application/json');
    }
    public function createOrderAction(){
	$rp = json_decode($this->getRequest()->getRawBody());
	$full_name = $rp->billing->fullName;
	$first_name = '';
	$last_name = '';
	if(!empty($full_name)){
	    $full_name_array = explode(' ', $full_name);
	    for ($i = 0; $i < sizeof($full_name_array); $i++) {
		if($i < (sizeof($full_name_array)-1)){
		    if(empty($last_name)){
			$last_name = $full_name_array[$i];
		    }else{
			$last_name = $last_name." ".$full_name_array[$i];
		    }
		}else{
		    $first_name = $full_name_array[$i];
		}
	    }
	    if(!empty($last_name) && empty($rp->billing->lastName)){
		$rp->billing->lastName = $last_name;
	    }
	    if(!empty($first_name) && empty($rp->billing->firstName)){
		$rp->billing->firstName = $first_name;
	    }
	}
	
	$helper_customer = Mage::helper('fahasa_customer');
	$rp->billing->firstName = $helper_customer->removeEmoji($rp->billing->firstName);
	$rp->billing->lastName = $helper_customer->removeEmoji($rp->billing->lastName);
	$rp->billing->telephone = $helper_customer->removeEmoji($rp->billing->telephone);
	$rp->billing->street = $helper_customer->removeEmoji($rp->billing->street);
	$rp->billing->postcode = $helper_customer->removeEmoji($rp->billing->postcode);
	$rp->billing->email = $helper_customer->removeEmoji($rp->billing->email);
	$rp->billing->region = $helper_customer->removeEmoji($rp->billing->region);
	$rp->billing->city = $helper_customer->removeEmoji($rp->billing->city);
	
	if(empty($rp->billing->regionId)){
	    $rp->billing->regionId = 0;
	}else{
	    $rp->billing->regionId = intval($rp->billing->regionId);
	}
	if(empty($rp->billing->region) && $rp->billing->region != 0){
	    $rp->billing->region = '';
	}else{
	    $rp->billing->region = strval($rp->billing->region);
	}
	
	$data = \Mage::helper('onestepcheckout')->createOrder(
		$rp->billing,
		$rp->shipping,
		$rp->vatData,
		$rp->shippingMethod,
		$rp->paymentMethod,
		$rp->couponCode,
		$rp->vipCode,
		$rp->giftwrap,
		$rp->localeStoreId,
		$rp->freeship,
		$rp->tryout,
		$rp->pickupLocation,
		$rp->event_cart_option,
		$rp->event_delivery_option
	    );
	if($data['success'] && $rp->billing->saveInAddressBook && Mage::getSingleton('customer/session')->isLoggedIn()){
	    $address_data = [];
	    $address_data['ward'] = $rp->billing->ward;
	    $address_data['telephone'] = $rp->billing->telephone;
	    $address_data['street'] = $rp->billing->street;
	    $address_data['region_id'] = $rp->billing->regionId;
	    $address_data['region'] = $rp->billing->region;
	    $address_data['postcode'] = $rp->billing->postcode;
	    $address_data['lastname'] = $rp->billing->lastName;
	    $address_data['firstname'] = $rp->billing->firstName;
	    $address_data['country_id'] = $rp->billing->countryId;
	    $address_data['city'] = $rp->billing->city;
	    
	    Mage::helper('fahasa_customer')->addCustomerAddress(Mage::getSingleton('customer/session')->getId(), $address_data);
	}   
        \Mage::log(" *** createOrderAction data = ". print_r($data,true), null, "payment.log");
        
        return $this->getResponse()->setBody(json_encode($data))
                        ->setHeader('Content-Type', 'application/json');
    }
    
    public function replaceOutStockProductAction()
    {
        $quote_item_id = $this->getRequest()->getPost("quote_item_id");
        $product_id = $this->getRequest()->getPost("product_id");
        $data = Mage::helper('onestepcheckout/relatedproduct')->replaceProductInCart($product_id, $quote_item_id);
        $data = $this->addMiniCartInOustockProduct($data);
        return $this->getResponse()->setBody(json_encode($data))
                        ->setHeader('Content-Type', 'application/json');
    }

    public function getOutStockProductAction(){
	$data = Mage::helper('onestepcheckout/relatedproduct')->getOutStockProductInCheckout();
        return $this->getResponse()->setBody(json_encode($data))
                        ->setHeader('Content-Type', 'application/json');
    }
    
    public function deleteOutStockProductAction(){
       $quote_item_id = $this->getRequest()->getPost("quote_item_id");
	$data = Mage::helper('onestepcheckout/relatedproduct')->deleteProductInCart($quote_item_id);
        $data = $this->addMiniCartInOustockProduct($data);
        return $this->getResponse()->setBody(json_encode($data))
                        ->setHeader('Content-Type', 'application/json');
    }
    
    public function updateOutStockProductAction()
    {
        $quote_item_id = $this->getRequest()->getPost("quote_item_id");
        $qty = $this->getRequest()->getPost("qty");
        $data = Mage::helper('onestepcheckout/relatedproduct')->updateProductInCart($quote_item_id, $qty);
        $data = $this->addMiniCartInOustockProduct($data);
        return $this->getResponse()->setBody(json_encode($data))
                        ->setHeader('Content-Type', 'application/json');
    }
    
    public function addMiniCartInOustockProduct($data)
    {
        if ($data['success'])
        {
            $mini_cart = $this->getLayout()
                    ->createBlock('checkout/cart_sidebar')
                    ->setTemplate('magentothem/ajaxcartsuper/checkout/cart/topcart.phtml')
                    ->toHtml();
            $data['mini_cart'] = $mini_cart;
        }
        return $data;
    }

}
