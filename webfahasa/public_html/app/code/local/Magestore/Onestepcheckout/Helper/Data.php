<?php

class Magestore_Onestepcheckout_Helper_Data extends Mage_Core_Helper_Abstract {

    public function __construct() {
        $this->settings = $this->getConfigData();
    }

    public function enabledOnestepcheckout() {
        if (Mage::getStoreConfig('onestepcheckout/general/active', Mage::app()->getStore(true)->getStoreId())) {
            return true;
        }
        return false;
    }

    public function enabledDelivery() {
        if (Mage::getStoreConfig('onestepcheckout/general/delivery_time_date', Mage::app()->getStore(true)->getStoreId())) {
            return true;
        }
        return false;
    }

    public function enableRegistration() {
        if ($this->settings['enable_registration']) {
            return true;
        }
        return false;
    }

    public function loadDataforDisabledFields(&$data) {
        $configData = $this->getConfigData();
        if (!$configData['show_city']) {
            $data['city'] = '-';
        }
        if (!$configData['show_ward']) {
            $data['ward'] = '-';
        }
        if (!$configData['show_zipcode']) {
            $data['postcode'] = '-';
        }
        if (!$configData['show_company']) {
            $data['company'] = '';
        }
        if (!$configData['show_fax']) {
            $data['fax'] = '';
        }
        if (!$configData['show_telephone']) {
            $data['telephone'] = '-';
        }
        if (!$configData['show_region']) {
            $data['region'] = '-';
            $data['region_id'] = '-';
        }
        return $data;
    }

   /**
    * Get string with frontend validation classes for attribute
    *
    * @param string $attributeCode
    * @return string
    */
    public function getAttributeValidationClass($attributeCode){
           /** @var $attribute Mage_Customer_Model_Attribute */
           $attribute = isset($this->_attributes[$attributeCode]) ? $this->_attributes[$attributeCode]
           : Mage::getSingleton('eav/config')->getAttribute('customer_address', $attributeCode);
           $class = $attribute ? $attribute->getFrontend()->getClass() : '';

           if (in_array($attributeCode, array('firstname', 'middlename', 'lastname', 'prefix', 'suffix', 'taxvat'))) {
                   if ($class && !$attribute->getIsVisible()) {
                           $class = ''; // address attribute is not visible thus its validation rules are not applied
                   }

                   /** @var $customerAttribute Mage_Customer_Model_Attribute */
                   $customerAttribute = Mage::getSingleton('eav/config')->getAttribute('customer', $attributeCode);
                   $class .= $customerAttribute && $customerAttribute->getIsVisible()
                   ? $customerAttribute->getFrontend()->getClass() : '';
                   $class = implode(' ', array_unique(array_filter(explode(' ', $class))));
           }

           return $class;
    }
    
    public function loadEmptyData(&$data) {
        if (!isset($data['city']) || $data['city'] == '') {
            if ($this->settings['city'] != '') {
                $data['city'] = $this->settings['city'];
            } else {
                $data['city'] = '-';
            }
        }
        if (!isset($data['ward']) || $data['ward'] == '') {
            if ($this->settings['ward'] != '') {
                $data['ward'] = $this->settings['ward'];
            } else {
                $data['ward'] = '-';
            }
        }
        if (!isset($data['telephone']) || trim($data['telephone']) == '') {
            $data['telephone'] = '-';
        }
        if (!isset($data['postcode']) || $data['postcode'] == '') {
            if ($this->settings['postcode'] != '') {
                $data['postcode'] = $this->settings['postcode'];
            } else {
                $data['postcode'] = '-';
            }
        }
        if (!isset($data['region']) || $data['region'] == '') {
            $data['region'] = '-';
        }
        if (!isset($data['region_id']) || $data['region_id'] == '') {
            if ($this->settings['region_id'] != '') {
                $data['region_id'] = $this->settings['region_id'];
            } else {
                $data['region_id'] = '-';
            }
        }
        if (!isset($data['country_id']) || $data['country_id'] == '') {
            if ($this->settings['country_id'] != '') {
                $data['country_id'] = $this->settings['country_id'];
            } else {
                $data['country_id'] = '-';
            }
        }
        return $data;
    }

    public function getConfigData() {
        $configData = array();
        $configItems = array('general/active', 'general/checkout_title', 'general/checkout_description',
            'general/show_shipping_address', 'general/country_id',
            'general/default_payment', 'general/default_shipping',
            'general/postcode', 'general/region_id', 'general/city', 'general/ward',
            'general/use_for_disabled_fields', 'general/hide_shipping_method',
            'general/page_layout',
            'field_management/show_city','field_management/show_ward', 'field_management/show_zipcode',
            'field_management/show_company', 'field_management/show_fax',
            'field_management/show_telephone', 'field_management/show_region',
            'general/show_comment', 'general/show_newsletter',
            'general/show_discount', 'general/newsletter_default_checked',
            'field_management/enable_giftmessage',
            'checkout_mode/show_login_link', 'checkout_mode/enable_registration',
            'checkout_mode/allow_guest', 'checkout_mode/login_link_title',
            'ajax_update/enable_ajax', 'ajax_update/ajax_fields',
            'ajax_update/update_payment',
            'ajax_update/reload_payment',
            'terms_conditions/enable_terms', 'terms_conditions/term_html',
            'terms_conditions/term_width', 'terms_conditions/term_height',
            'order_notification/enable_notification', 'order_notification/notification_email');
        foreach ($configItems as $configItem) {
            $config = explode('/', $configItem);
            $value = $config[1];
            $configData[$value] = Mage::getStoreConfig('onestepcheckout/' . $configItem);
        }
        return $configData;
    }

    public function isShowShippingAddress() {
        if ($this->getOnepage()->getQuote()->isVirtual()) {
            return false;
        }
        if ($this->settings['show_shipping_address']) {
            return true;
        }
        return false;
    }

    public function getOnePage() {
        return Mage::getSingleton('checkout/type_onepage');
    }

    public function getCheckoutUrl() {
        return Mage::getUrl('onestepcheckout');
    }

    public function savePaymentMethod($data) {
        if (empty($data)) {
            return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid data.'));
        }
        $onepage = Mage::getSingleton('checkout/session')->getQuote();
        if ($onepage->isVirtual()) {
            $onepage->getBillingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
        } else {
            $onepage->getShippingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
        }
        $payment = $onepage->getPayment();
        $payment->importData($data);

        $onepage->save();

        return array();
    }

    public function saveShippingMethod($shippingMethod) {
        if (empty($shippingMethod)) {
            return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid shipping method.'));
        }
        $rate = $this->getOnepage()->getQuote()->getShippingAddress()->getShippingRateByCode($shippingMethod);
        if (!$rate) {
            return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid shipping method.'));
        }
        $this->getOnepage()->getQuote()->getShippingAddress()->setShippingMethod($shippingMethod);
        $this->getOnepage()->getQuote()->collectTotals()->save();
        return array();
    }

    public function allowGuestCheckout() {
        $_quote = $this->getOnepage()->getQuote();
        $_isAllowed = $this->settings['allow_guest'];
        if ($_isAllowed) {
            $isContain = false;
            foreach ($_quote->getAllItems() as $item) {
                if (($product = $item->getProduct()) &&
                        $product->getTypeId() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE) {
                    $isContain = true;
                }
            }
            $store = Mage::app()->getStore()->getId();
            if ($isContain && Mage::getStoreConfigFlag('catalog/downloadable/disable_guest_checkout', $store)) {
                $_isAllowed = false;
            }
        }
        return $_isAllowed;
    }

    public function isUseDefaultDataforDisabledFields() {
        return $this->settings['use_for_disabled_fields'];
    }

    public function isShowNewsletter() {
        if ($this->settings['show_newsletter'] && !$this->isSignUpNewsletter())
            return true;
        else
            return false;
    }

    public function isSignUpNewsletter() {
        if ($this->isCustomerLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if (isset($customer))
                $customerNewsletter = Mage::getModel('newsletter/subscriber')->loadByEmail($customer->getEmail());
            if (isset($customerNewsletter) && $customerNewsletter->getId() != null && $customerNewsletter->getData('subscriber_status') == 1) {
                return true;
            }
        }
        return false;
    }

    public function isSubscribeByDefault() {
        return $this->settings['newsletter_default_checked'];
    }

    public function enableOrderComment() {
        return $this->settings['show_comment'];
    }

    public function showDiscount() {
        return $this->settings['show_discount'];
    }

    public function enableTermsAndConditions() {
        return $this->settings['enable_terms'];
    }

    public function getTermPopupWidth() {
        return $this->settings['term_width'];
    }

    public function getTermPopupHeight() {
        return $this->settings['term_height'];
    }

    public function getTermsConditionsHtml() {
        return $this->settings['term_html'];
    }

    public function enableNotifyAdmin() {
        return $this->settings['enable_notification'];
    }

    public function getEmailArray() {
        $email_string = (string) $this->settings['notification_email'];
        if ($email_string != '') {
            $email_array = explode(",", $email_string);
            return $email_array;
        }
        return array();
    }

    public function getEmailTemplate() {
        return Mage::getStoreConfig('onestepcheckout/order_notification/notification_email_template');
    }

    public function getStoreId() {
        return Mage::app()->getStore()->getId();
    }

    public function enableGiftMessage() {
        //return $this->settings['enable_giftmessage'];
//		return Mage::getStoreConfig('sales/gift_options/allow_order');
        $giftMessage = Mage::getStoreConfig('onestepcheckout/giftmessage/enable_giftmessage', $this->getStoreId());
        if ($giftMessage) {
            Mage::getConfig()->saveConfig('sales/gift_options/allow_order', 1);
            Mage::getConfig()->saveConfig('sales/gift_options/allow_items', 1);
            return true;
        } else {
            Mage::getConfig()->saveConfig('sales/gift_options/allow_order', 0);
            Mage::getConfig()->saveConfig('sales/gift_options/allow_items', 0);
            return false;
        }
    }

    public function enableCustomSize() {
        return Mage::getStoreConfig('onestepcheckout/terms_conditions/enable_custom_size', $this->getStoreId());
    }

    public function getTermTitle() {
        return Mage::getStoreConfig('onestepcheckout/terms_conditions/term_title', $this->getStoreId());
    }

    public function enableGiftWrap() {
        return Mage::getStoreConfig('onestepcheckout/giftwrap/enable_giftwrap', $this->getStoreId());
    }

    public function getGiftwrapType() {
        return Mage::getStoreConfig('onestepcheckout/giftwrap/giftwrap_type', $this->getStoreId());
    }

    public function getGiftwrapAmount() {
        /*Start: Huy - Fix Bug: Giftwrap amount does not change when switching currency*/
        // return Mage::getStoreConfig('onestepcheckout/giftwrap/giftwrap_amount', $this->getStoreId());
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        $amt = Mage::getStoreConfig('onestepcheckout/giftwrap/giftwrap_amount', $this->getStoreId());
        $amt1= Mage::helper('directory')->currencyConvert($amt, $baseCurrencyCode, $currentCurrencyCode);
        return $amt1;
        /*End: Huy - Fix Bug: Giftwrap amount does not change when switching currency*/
    }

    public function isCustomerLoggedIn() {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    public function showLoginLink() {
        return Mage::getStoreConfig('onestepcheckout/checkout_mode/show_login_link', $this->getStoreId());
    }

    public function checkGiftwrapSession() {
        $session = Mage::getSingleton('checkout/session');
        return $session->getData('onestepcheckout_giftwrap');
    }
    
    public function checkTryoutSession() {
        $session = Mage::getSingleton('checkout/session');
        return $session->getData('onestepcheckout_tryout');
    }

    public function isHideShippingMethod() {
        $_isHide = $this->settings['hide_shipping_method'];
        if ($_isHide) {
            $_quote = $this->getOnepage()->getQuote();
            $rates = $_quote->getShippingAddress()->getShippingRatesCollection();
            $rateCodes = array();
            foreach ($rates as $rate) {
                if (!in_array($rate->getCode(), $rateCodes)) {
                    $rateCodes[] = $rate->getCode();
                }
            }
            if (count($rateCodes) > 1) {
                $_isHide = false;
            }
        }

        return $_isHide;
    }

    /*
     * Save customer comment to the order
     */

    public function saveOrderComment($observer) {
        $session = Mage::getSingleton('checkout/session');
        $billing = $this->_getRequest()->getPost('billing');
        $delivery = $this->_getRequest()->getPost('delivery');
        $session = Mage::getSingleton('checkout/session');
        if ($this->enableOrderComment()) {
            $comment = $billing['onestepcheckout_comment'];
            $comment = trim($comment);
            if ($comment != '') {
                $order = $observer->getEvent()->getOrder();
                try {
                    // use custom attribute to save customer comment - magento 1.3
//                    $order->setOnestepcheckoutOrderComment($comment)
                    $order->addStatusHistoryComment($comment, false);
                    //Magento 1.4.1.1 - can not use custom attribute to save customer comment
                    //$order->setCustomerNote($comment);
                    //$order->save();
                } catch (Exception $e) {
                    
                }
            }
        }

        if ($this->enableSurvey()) {
            $surveyQuestion = $this->getSurveyQuestion();
            $surveyValues = unserialize($this->getSurveyValues());
            $surveyValue = $billing['onestepcheckout-surveybilling'];

            if (!empty($surveyValue)) {
                if ($surveyValue != 'freetext') {
                    $surveyAnswer = $surveyValues[$surveyValue]['value'];
                } else {
					$surveyFreeText = $billing['onestepcheckout-survey-freetext'];
                    $surveyAnswer = $surveyFreeText;
                }
            }

            $order = $observer->getEvent()->getOrder();
            if ($surveyQuestion)
                $session->setData('survey_question', $surveyQuestion);
            if ($surveyAnswer)
                $session->setData('survey_answer', $surveyAnswer);
        }

        //Save delivery
        if ($this->enabledDelivery()) {
            if ($delivery['onestepcheckout-date']) {
                $delivery_date_time = $delivery['onestepcheckout-date'] . ' ' . $delivery['onestepcheckout-time'];
                $session->setData('delivery_date_time', $delivery_date_time);
            }
        }
    }

    /*
     * use to load default data for disabled fields
     * only use if it is enabled
     */

    public function setDefaultDataforDisabledFields(&$data) {
        if (!$this->settings['show_city']) {
            $data['city'] = $this->settings['city'];
        }
        if (!$this->settings['show_ward']) {
            $data['ward'] = $this->settings['ward'];
        }
        if (!$this->settings['show_zipcode']) {
            $data['postcode'] = $this->settings['postcode'];
        }
        if (!$this->settings['show_region']) {
            $data['region_id'] = $this->settings['region_id'];
        }
        return $data;
    }

    public function getStyle() {
        $path = 'onestepcheckout/style_management/style';
        $value = Mage::getStoreConfig($path, Mage::app()->getStore()->getStoreId());
        return $value;
    }
    
    // ver 3.1 - Michael 20140610
    public function getStyleColor() {
        $storeId = Mage::app()->getStore()->getId();
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        $storecode = Mage::app()->getStore()->getCode();
        $path = 'onestepcheckout/style_management/custom';
        $value = Mage::getModel('onestepcheckout/config')->getCollection()
                ->addFieldToFilter('scope', 'website')
                ->addFieldToFilter('path', $path)
                ->addFieldToFilter('scope_id', $websiteId)
                ->getFirstItem()
                ->getValue();

        if (!$value) {
			$value = Mage::getModel('onestepcheckout/config')->getCollection()
					->addFieldToFilter('path', $path)
					->addFieldToFilter('scope', 'stores')
					->addFieldToFilter('scope_id', $storeId)
					->getFirstItem()
					->getValue();
			if(!$value)
				$value = Mage::getModel('onestepcheckout/config')->getCollection()
						->addFieldToFilter('scope', 'default')
						->addFieldToFilter('path', $path)
						->addFieldToFilter('scope_id', 0)
						->getFirstItem()
						->getValue();
        }
        return $value;
    }
    
	public function getCheckoutButtonColor() {
        $storeId = Mage::app()->getStore()->getId();
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        $storecode = Mage::app()->getStore()->getCode();
        $path = 'onestepcheckout/style_management/button';
        $value = Mage::getModel('onestepcheckout/config')->getCollection()
                ->addFieldToFilter('scope', 'stores')
                ->addFieldToFilter('path', $path)
                ->addFieldToFilter('scope_id', $storeId)
                ->getFirstItem()
                ->getValue();

        if (!$value) {
			$value = Mage::getModel('onestepcheckout/config')->getCollection()
					->addFieldToFilter('path', $path)
					->addFieldToFilter('scope', 'websites')
					->addFieldToFilter('scope_id', $websiteId)
					->getFirstItem()
					->getValue();
			if(!$value)
				$value = Mage::getModel('onestepcheckout/config')->getCollection()
						->addFieldToFilter('scope', 'default')
						->addFieldToFilter('path', $path)
						->addFieldToFilter('scope_id', 0)
						->getFirstItem()
						->getValue();
        }
        if(!$value){
            $value = $this->getBackgroundColor('orange');
        }elseif($value == 'custom'){
            $pathButton = 'onestepcheckout/style_management/custombutton';
            $valueCustom = Mage::getModel('onestepcheckout/config')->getCollection()
                    ->addFieldToFilter('scope', 'stores')
                    ->addFieldToFilter('path', $pathButton)
                    ->addFieldToFilter('scope_id', $storeId)
                    ->getFirstItem()
                    ->getValue();

            if (!$valueCustom) {
                $valueCustom = Mage::getModel('onestepcheckout/config')->getCollection()
                        ->addFieldToFilter('scope', 'websites')
                        ->addFieldToFilter('path', $pathButton)
                        ->addFieldToFilter('scope_id', $websiteId)
                        ->getFirstItem()
                        ->getValue();
				if(!$valueCustom)	
					$valueCustom = Mage::getModel('onestepcheckout/config')->getCollection()
                        ->addFieldToFilter('scope', 'default')
                        ->addFieldToFilter('path', $pathButton)
                        ->addFieldToFilter('scope_id', 0)
                        ->getFirstItem()
                        ->getValue();
            }
            if(!$valueCustom)
                $value = $this->getBackgroundColor('orange');
            else
                $value = '#'.$valueCustom;
        }else{
            $value = $this->getBackgroundColor($value);
        }        
        return $value;
    }
    
    //Onestepcheckout v2.0.0
    public function getFieldEnableBackEnd($number, $scope, $scopeId) {
        $path = 'onestepcheckout/field_position_management/row_' . $number;
        $value = Mage::getModel('onestepcheckout/config')->getCollection()
                ->addFieldToFilter('scope', $scope)
                ->addFieldToFilter('path', $path)
                ->addFieldToFilter('scope_id', $scopeId)
                ->getFirstItem()
                ->getValue();
        return $value;
    }

    public function getFieldEnables() {
        $path = 'onestepcheckout/field_position_management/row_';
        for ($i = 0; $i < 20; $i++) {
            $fields[$i]['value'] = Mage::getStoreConfig($path . $i, Mage::app()->getStore()->getStoreId());
            $fields[$i]['position'] = $i;
        }
        return $fields;
    }

    public function getFieldEnable($number) {
        $storeId = Mage::app()->getStore()->getId();
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        $storecode = Mage::app()->getStore()->getCode();
        $path = 'onestepcheckout/field_position_management/row_' . $number;
        $value = Mage::getModel('onestepcheckout/config')->getCollection()
                ->addFieldToFilter('path', $path)
                ->addFieldToFilter('scope', 'stores')
                ->addFieldToFilter('scope_id', $storeId)
                ->getFirstItem()
                ->getValue();
        if (count($value) == 0 && $storecode == 'default') {
            $value = Mage::getModel('onestepcheckout/config')->getCollection()
                    ->addFieldToFilter('path', $path)
                    ->addFieldToFilter('scope', 'websites')
                    ->addFieldToFilter('scope_id', $websiteId)
                    ->getFirstItem()
                    ->getValue();
        }
        if (count($value) == 0) {
            $value = $this->getDefaultField($number);
        }

        return $value;
    }

    public function getDefaultField($number) {
        $path = 'onestepcheckout/field_position_management/row_' . $number;
        $value = Mage::getModel('onestepcheckout/config')->getCollection()
                ->addFieldToFilter('path', $path)
                ->addFieldToFilter('scope_id', 0)
                ->addFieldToFilter('scope', 'default')
                ->getFirstItem()
                ->getValue();
        return $value;
    }

    public function getFieldValue() {
        return array(
            '0' => Mage::helper('onestepcheckout')->__('Null'),
            'firstname' => Mage::helper('onestepcheckout')->__('First Name'),
            'lastname' => Mage::helper('onestepcheckout')->__('Last Name'),
            'prefix' => Mage::helper('onestepcheckout')->__('Prefix Name'),
            'middlename' => Mage::helper('onestepcheckout')->__('Middle Name'),
            'suffix' => Mage::helper('onestepcheckout')->__('Suffix Name'),
            'email' => Mage::helper('onestepcheckout')->__('Email Address'),
            'company' => Mage::helper('onestepcheckout')->__('Company'),
            'street' => Mage::helper('onestepcheckout')->__('Address'),
            'country' => Mage::helper('onestepcheckout')->__('Country'),
            'region' => Mage::helper('onestepcheckout')->__('State/Province'),
            'city' => Mage::helper('onestepcheckout')->__('City'),
            'ward' => Mage::helper('onestepcheckout')->__('Ward'),
            'postcode' => Mage::helper('onestepcheckout')->__('Zip/Postal Code'),
            'telephone' => Mage::helper('onestepcheckout')->__('Telephone'),
            'billing-ctelephone' => Mage::helper('onestepcheckout')->__('Comfirm Telephone'),
            'shipping-ctelephone' => Mage::helper('onestepcheckout')->__('Comfirm Telephone'),
            'fax' => Mage::helper('onestepcheckout')->__('Fax'),
            'birthday' => Mage::helper('onestepcheckout')->__('Date of Birth'),
            'gender' => Mage::helper('onestepcheckout')->__('Gender'),
            'taxvat' => Mage::helper('onestepcheckout')->__('Tax/VAT number'),
        );
    }

    public function getFieldLabel($field) {
        if ($field == 'firstname')
            return Mage::helper('onestepcheckout')->__('First Name');
        if ($field == 'lastname')
            return Mage::helper('onestepcheckout')->__('Last Name');
        if ($field == 'prefix')
            return Mage::helper('onestepcheckout')->__('Prefix Name');
        if ($field == 'middlename')
            return Mage::helper('onestepcheckout')->__('Middle Name');
        if ($field == 'suffix')
            return Mage::helper('onestepcheckout')->__('Suffix Name');
        if ($field == 'email')
            return Mage::helper('onestepcheckout')->__('Email Address');
        if ($field == 'company')
            return Mage::helper('onestepcheckout')->__('Company');
        if ($field == 'street')
            return Mage::helper('onestepcheckout')->__('Address');
        if ($field == 'country')
            return Mage::helper('onestepcheckout')->__('Country');
        if ($field == 'region')
            return Mage::helper('onestepcheckout')->__('State/Province');
        if ($field == 'city')
            return Mage::helper('onestepcheckout')->__('City');
        if ($field == 'ward')
            return Mage::helper('onestepcheckout')->__('Ward');
        if ($field == 'postcode')
            return Mage::helper('onestepcheckout')->__('Zip/Postal Code');
        if ($field == 'telephone')
            return Mage::helper('onestepcheckout')->__('Telephone');
        if ($field == 'fax')
            return Mage::helper('onestepcheckout')->__('Fax');
        if ($field == 'birthday')
            return Mage::helper('onestepcheckout')->__('Date of Birth');
        if ($field == 'gender')
            return Mage::helper('onestepcheckout')->__('Gender');
        if ($field == 'taxvat')
            return Mage::helper('onestepcheckout')->__('Tax/VAT number');
    }

    public function getFieldRequire($field) {
        return Mage::getStoreConfig('onestepcheckout/field_require_management/' . $field, Mage::app()->getStore()->getStoreId());
    }

    //Survey	
    public function enableSurvey() {
        return Mage::getStoreConfig('onestepcheckout/survey/enable_survey', $this->getStoreId());
    }

    public function getSurveyQuestion() {
        return Mage::getStoreConfig('onestepcheckout/survey/survey_question', $this->getStoreId());
    }

    public function enableFreeText() {
        return Mage::getStoreConfig('onestepcheckout/survey/enable_survey_freetext', $this->getStoreId());
    }

    public function getSurveyValues() {
        return Mage::getStoreConfig('onestepcheckout/survey/survey_values', $this->getStoreId());
    }

    public function enableGiftwrapModule() {
        $moduleGiftwrap = Mage::getConfig()->getModuleConfig('Magestore_Giftwrap')->is('active', 'true');
        return $moduleGiftwrap;
    }

    public function getOrderGiftwrapAmount() {
        $amount = $this->getGiftwrapAmount();
        $giftwrapAmount = 0;
        // $freeBoxes = 0;
        $items = Mage::getSingleton('checkout/cart')->getItems();
        if ($this->getGiftwrapType() == 1) {
            foreach ($items as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }
                $giftwrapAmount += $amount * ($item->getQty());
            }
        } elseif (count($items) > 0) {
            $giftwrapAmount = $amount;
        }
        return $giftwrapAmount;
    }

    /*
      Geoip
     */

    public function enableGeoip() {
        return false;
        return Mage::getStoreConfig('onestepcheckout/geoip/enable', $this->getStoreId());
    }

    public function allowDetectCountry() {
        return Mage::getStoreConfig('onestepcheckout/geoip/detect_by_ip', $this->getStoreId());
    }

    public function allowDetectByPostcode() {
        return Mage::getStoreConfig('onestepcheckout/geoip/detect_by_postcode', $this->getStoreId());
    }

    public function allowDetectByCity() {
        return Mage::getStoreConfig('onestepcheckout/geoip/detect_by_city', $this->getStoreId());
    }
    
    public function allowDetectByWard() {
        return Mage::getStoreConfig('onestepcheckout/geoip/detect_by_ward', $this->getStoreId());
    }

    public function getMaxItemsEachImport() {
        return Mage::getStoreConfig('onestepcheckout/geoip/rows', $this->getStoreId());
    }

    public function getMinCharsPostcode() {
        return Mage::getStoreConfig('onestepcheckout/geoip/postcode_characters', $this->getStoreId());
    }

    public function getMinCharsCity() {
        return Mage::getStoreConfig('onestepcheckout/geoip/city_characters', $this->getStoreId());
    }
    
    public function getMinCharsWard() {
        return Mage::getStoreConfig('onestepcheckout/geoip/ward_characters', $this->getStoreId());
    }

    public function getRealIpAddr() {
        //check ip from share internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        //to check ip is pass from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function detectCountryIp() {
        $realIp = $this->getRealIpAddr();
        $geoip = Mage::getModel('onestepcheckout/country');
        $ipInteger = $geoip->convertIpToInteger($realIp);
        $countryips = Mage::getModel('onestepcheckout/country')->getCollection()
                ->addFieldToFilter('first_ip_number', array('lteq' => $ipInteger['ip']))
                ->addFieldToFilter('last_ip_number', array('gteq' => $ipInteger['ip']))
        ;
        if (isset($ipInteger['ip_lower'])) {
            if ($ipInteger['ip_lower'] > 0) {
                $countryips = $countryips->addFieldToFilter('last_ip_number_lower', array('gteq' => $ipInteger['ip_lower']))
                // ->addFieldToFilter('first_ip_number_lower', array('lteq'=>$ipInteger['ip_lower']))									 
                ;
            }
        }
        $countryip = $countryips->getFirstItem();
        if ($countryip->getId())
            return $countryip->getData('country');
        return false;
    }

    public function getStoreByCode($storeCode) {
        $stores = array_keys(Mage::app()->getStores());
        foreach ($stores as $id) {
            $store = Mage::app()->getStore($id);
            if ($store->getCode() == $storeCode) {
                return $store;
            }
        }
        return null; // if not found
    }

    public function getDefaultPositionArray() {
        $arrayDefault = array();
        $arrayDefault[0] = 'firstname';
        $arrayDefault[1] = 'lastname';
        $arrayDefault[2] = 'email';
        $arrayDefault[3] = 'telephone';
        $arrayDefault[4] = 'street';
        $arrayDefault[6] = 'country';
        $arrayDefault[7] = 'city';
        $arrayDefault[8] = 'ward';
        $arrayDefault[10] = 'postcode';
        $arrayDefault[11] = 'region';
        $arrayDefault[12] = 'company';
        $arrayDefault[13] = 'fax';
        return $arrayDefault;
    }

    public function getBackgroundColor($style) {
        if ($style == 'orange')
            return '#F39801';
        if ($style == 'green')
            return '#B6CE5E';
        if ($style == 'black')
            return '#000000';
        if ($style == 'blue')
            return '#3398CC';
        if ($style == 'darkblue')
            return '#004BA0';
        if ($style == 'pink')
            return '#E13B91';
        if ($style == 'red')
            return '#E10E03';
        if ($style == 'violet')
            return '#B962d5';

        return '#F39801';
    }
    
    public function saveMethod($onepage, $countryId, $shipping_method, $payment_method, $payment){
        if ($countryId) {
            Mage::getModel('checkout/session')->getQuote()->getBillingAddress()->setData('country_id', $countryId)->save();
        }
        $onepage->saveShippingMethod($shipping_method);
        try {
            
            $payment['method'] = $payment_method;
            /* Start: Modified by Daniel - 03/04/2015 - improve ajax speed */
            /* $this->getOnepage()->savePayment($payment); */
            /* End: Modified by Daniel - 03/04/2015 - improve ajax speed */
            $this->savePaymentMethod($payment);
        } catch (Exception $e) {
            
        }
        $onepage->getQuote()->collectTotals()->save();
    }
    
    public function saveAddressShipping($onepage, $billing_data, $shipping_data, $billing_address_id, $shipping_address_id, $shipping_method){
        if (isset($billing_data['onestepcheckout_comment']))
            Mage::getModel('checkout/session')->setOSCCM($billing_data['onestepcheckout_comment']);

        //load default data for disabled fields
        if (Mage::helper('onestepcheckout')->isUseDefaultDataforDisabledFields()) {
            Mage::helper('onestepcheckout')->setDefaultDataforDisabledFields($billing_data);
            Mage::helper('onestepcheckout')->setDefaultDataforDisabledFields($shipping_data);
        }

        if (isset($billing_data['use_for_shipping']) && $billing_data['use_for_shipping'] == '1') {
            $shipping_address_data = $billing_data;
        } else {
            $shipping_address_data = $shipping_data;
        }

        /* customize for load country ma khong dien day du thong tin */
        $quote = $onepage->getQuote();
        $shipping = $quote->getShippingAddress();
        $billing = $quote->getBillingAddress();

        $billingCountryId = "";
        $billingRegionId = "";
        $billingZipcode = "";
        $billingRegion = "";
        $billingCity = "";
        $billingWard = "";

        if (isset($shipping_address_data['country_id']))
            $billingCountryId = $shipping_address_data['country_id'];
        if (isset($shipping_address_data['region_id']))
            $billingRegionId = $shipping_address_data['region_id'];
        if (isset($shipping_address_data['postcode']))
            $billingZipcode = $shipping_address_data['postcode'];
        if (isset($shipping_address_data['region']))
            $billingRegion = $shipping_address_data['region'];
        if (isset($shipping_address_data['city']))
            $billingCity = $shipping_address_data['city'];
        if (isset($shipping_address_data['ward']))
            $billingWard = $shipping_address_data['ward'];

        $onepage->getQuote()->getShippingAddress()
                ->setCountryId($billingCountryId)
                ->setRegionId($billingRegionId)
                ->setPostcode($billingZipcode)
                ->setRegion($billingRegion)
                ->setCity($billingCity)
                ->setWard($billingWard)
                ->setCollectShippingRates(true);

        /* end customize */
        
        $billing_street = trim(implode("\n", $billing_data['street']));
        $shipping_street = trim(implode("\n", $shipping_address_data['street']));

        if (isset($billing_data['email'])) {
            $billing_data['email'] = trim($billing_data['email']);
        }

        //2014.18.11 update VAT apply start
        if (isset($billing_data['taxvat'])) {
            $billing_data['vat_id'] = trim($billing_data['taxvat']);
            $shipping_data['vat_id'] = trim($billing_data['taxvat']);
        }
        //2014.18.11 update VAT apply end
        $this->setIgnoreValidation($onepage);
        if (Mage::helper('onestepcheckout')->isShowShippingAddress()) {
            if (!isset($billing_data['use_for_shipping']) || $billing_data['use_for_shipping'] != '1') {
//                $shipping_address_id = $onepage-
                $onepage->saveShipping($shipping_data, $shipping_address_id);
            }
        }

        $onepage->saveBilling($billing_data, $billing_address_id);
        /* Start: Modified by Daniel - 06/04/2015 - Improve Ajax speed */
        if (!$billing_address_id || $billing_address_id == '' || $billing_address_id == null) {
            if ($billing_data['country_id']) {
                Mage::getModel('checkout/session')->getQuote()->getBillingAddress()->setData('country_id', $billing_data['country_id'])->save();
            }
        }
        /* End: Modified by Daniel - 06/04/2015 - Improve Ajax speed */

        if ($shipping_method && $shipping_method != '') {
            Mage::helper('onestepcheckout')->saveShippingMethod($shipping_method);
        }
    }
    
    public function saveAddressWardShipping($ward, $billing_address_id){
	
	if($ward && $billing_address_id){
	    
	}else{
	    
	}
	
        $this->getOnepage()->getQuote()->getShippingAddress()->setShippingMethod($shippingMethod);
        $this->getOnepage()->getQuote()->collectTotals()->save();
	
	
	
        if (isset($billing_data['onestepcheckout_comment']))
            Mage::getModel('checkout/session')->setOSCCM($billing_data['onestepcheckout_comment']);

        //load default data for disabled fields
        if (Mage::helper('onestepcheckout')->isUseDefaultDataforDisabledFields()) {
            Mage::helper('onestepcheckout')->setDefaultDataforDisabledFields($billing_data);
            Mage::helper('onestepcheckout')->setDefaultDataforDisabledFields($shipping_data);
        }

        if (isset($billing_data['use_for_shipping']) && $billing_data['use_for_shipping'] == '1') {
            $shipping_address_data = $billing_data;
        } else {
            $shipping_address_data = $shipping_data;
        }

        /* customize for load country ma khong dien day du thong tin */
        $quote = $onepage->getQuote();
        $shipping = $quote->getShippingAddress();
        $billing = $quote->getBillingAddress();

        $billingCountryId = "";
        $billingRegionId = "";
        $billingZipcode = "";
        $billingRegion = "";
        $billingCity = "";
        $billingWard = "";

        if (isset($shipping_address_data['country_id']))
            $billingCountryId = $shipping_address_data['country_id'];
        if (isset($shipping_address_data['region_id']))
            $billingRegionId = $shipping_address_data['region_id'];
        if (isset($shipping_address_data['postcode']))
            $billingZipcode = $shipping_address_data['postcode'];
        if (isset($shipping_address_data['region']))
            $billingRegion = $shipping_address_data['region'];
        if (isset($shipping_address_data['city']))
            $billingCity = $shipping_address_data['city'];
        if (isset($shipping_address_data['ward']))
            $billingWard = $shipping_address_data['ward'];

        $onepage->getQuote()->getShippingAddress()
                ->setCountryId($billingCountryId)
                ->setRegionId($billingRegionId)
                ->setPostcode($billingZipcode)
                ->setRegion($billingRegion)
                ->setCity($billingCity)
                ->setWard($billingWard)
                ->setCollectShippingRates(true);

        /* end customize */
        
        $billing_street = trim(implode("\n", $billing_data['street']));
        $shipping_street = trim(implode("\n", $shipping_address_data['street']));

        if (isset($billing_data['email'])) {
            $billing_data['email'] = trim($billing_data['email']);
        }

        //2014.18.11 update VAT apply start
        if (isset($billing_data['taxvat'])) {
            $billing_data['vat_id'] = trim($billing_data['taxvat']);
            $shipping_data['vat_id'] = trim($billing_data['taxvat']);
        }
        //2014.18.11 update VAT apply end
        $this->setIgnoreValidation($onepage);
        if (Mage::helper('onestepcheckout')->isShowShippingAddress()) {
            if (!isset($billing_data['use_for_shipping']) || $billing_data['use_for_shipping'] != '1') {
//                $shipping_address_id = $onepage-
                $onepage->saveShipping($shipping_data, $shipping_address_id);
            }
        }

        $onepage->saveBilling($billing_data, $billing_address_id);
        /* Start: Modified by Daniel - 06/04/2015 - Improve Ajax speed */
        if (!$billing_address_id || $billing_address_id == '' || $billing_address_id == null) {
            if ($billing_data['country_id']) {
                Mage::getModel('checkout/session')->getQuote()->getBillingAddress()->setData('country_id', $billing_data['country_id'])->save();
            }
        }
        /* End: Modified by Daniel - 06/04/2015 - Improve Ajax speed */

        if ($shipping_method && $shipping_method != '') {
            Mage::helper('onestepcheckout')->saveShippingMethod($shipping_method);
        }
    }
    
    public function setIgnoreValidation($onepage) {
        $onepage->getQuote()->getBillingAddress()->setShouldIgnoreValidation(true);
        $onepage->getQuote()->getShippingAddress()->setShouldIgnoreValidation(true);
    }
    
    public function getOwnerReferPhone($referCode){
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "
            select telephone from fhs_customer_entity where refer_code = :refer_code";
        $vars = array(
            "refer_code" => $referCode
        );
        $results = $readConnection->fetchAll($query, $vars);
        $phone = count($results) > 0 ? $results[0]['telephone'] : null;
        return $phone;
    }
    
    public function handleCoupponCode($couponCode, $quote, $remove, $isApp){
        if ($remove == '1') {
            $couponCode = '';
            Mage::getSingleton('core/session')->setFhsCoin(null);
        }
         else {
            Mage::getSingleton('core/session')->setFhsCoin(array('code' => trim($couponCode)));
        }
        
        $oldCouponCode = $quote->getCouponCode();
        if (!strlen($couponCode) && !strlen($oldCouponCode)) {
            if($isApp == false){
                return;
            }
        }
        try {
            $error = false;  
            $success = FALSE;
            /* remove by Leo 10042015 */
            // $quote->getShippingAddress()->setCollectShippingRates(true);
            /* remove by Leo 10042015 */
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->setCouponCode(strlen($couponCode) ? $couponCode : '')
                    ->collectTotals()
                    ->save();
            if ($couponCode) {
                if ($couponCode == $quote->getCouponCode()) {
                    $customer = Mage::getSingleton('customer/session')->getCustomer();
                    $referCode = $customer->getReferCode();
                    //Get phone of Refer code owner.
                    //Phone cua refer code owner != phone cua customer dang mua hang
                    $currentCustomerPhone = $customer->getTelephone();                    
                    $referCodeOwnerTelephone = $this->getOwnerReferPhone($couponCode);
                    if(isset($referCodeOwnerTelephone) && isset($currentCustomerPhone) 
                            && $currentCustomerPhone == $referCodeOwnerTelephone){
                        $quote->setCouponCode('')->collectTotals()->save();
                        $message = $this->__('Your current account phone number is the same as the phone of the owner refer code (%s). '
                                . 'Sorry! code will not be applied!', Mage::helper('core')->htmlEscape($couponCode));
                        $error = TRUE;
                        $success = FALSE;
                    }else{
                        $fpointAccureYear = $customer->getFpointAccureYear();
                        if (strtoupper($couponCode) !== strtoupper($referCode)) {
                            // check refer code
                            $ruleId = Mage::getModel('salesrule/coupon')->load($couponCode, 'code')->getRuleId();
                            $listRuleRefer = explode(",",Mage::getStoreConfig("customerregister/refer/listrule"));
                            $isRefer = in_array($ruleId,$listRuleRefer); 
                            if($isRefer && $fpointAccureYear !== "0"){
                                // if is_refercode && old customer
                                $quote->setCouponCode('')->collectTotals()->save();
                                $message = $this->__('Refer codes "%s" can not use by old customer.', Mage::helper('core')->htmlEscape($couponCode));
                                $error = TRUE;
                                $success = FALSE;
                            }else{
                                $message = $this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponCode));
                                Mage::getSingleton('core/session')->setFhsCoin(null);
                                $success = true;
                            }
                        } else {
                            $quote->setCouponCode('')->collectTotals()->save();
                            $message = $this->__('Refer codes "%s" can not use by yourself.', Mage::helper('core')->htmlEscape($couponCode));
                            $error = TRUE;
                            $success = FALSE;
                        }
                    }
                } else {
                    $results = Mage::helper('tryout')->checkCoin($couponCode);
                    if ($results['code'] != null)
                    {
                        if ($results['currentAmount'] > 0)
                        {
                            $message = $this->__('Coin code "%s" is applied.', Mage::helper('core')->htmlEscape($couponCode));
                            $error = FALSE;
                            $success = true;
                        }
                        else
                        {
                            $message = $this->__('The current value of "%s" code is 0 VND', Mage::helper('core')->htmlEscape($couponCode));
                            $error = true;
                            Mage::getSingleton('core/session')->setFhsCoin(null);
                        }
                    }
                    else
                    {
                        ////// start check coupon code
                        $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getEntityId();
                        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
                        $sql = "SELECT c.*, r.is_active, r.from_date, ifnull(cu.times_used, 0) as 'coupon_times_used' , ifnull(ru.times_used, 0) as 'customer_times_used', r.from_created_account
			    FROM fhs_salesrule_coupon as c
			    JOIN fhs_salesrule as r on c.rule_id = r.rule_id
			    LEFT JOIN fhs_salesrule_coupon_usage as cu ON c.coupon_id = cu.coupon_id and cu.customer_id = '" . $customer_id . "' 
			    LEFT JOIN fhs_salesrule_customer as ru ON c.rule_id = ru.rule_id and ru.customer_id = cu.customer_id
			    WHERE c.code = '" . trim($couponCode) . "';";
                        $coupon_info = $read->fetchAll($sql);

                        $currentdate = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                        $isCodeLengthValid = $codeLength && $codeLength <= Mage_Checkout_Helper_Cart::COUPON_CODE_MAX_LENGTH;

                        if ($coupon_info[0])
                        { // check code
                            $is_active = $coupon_info[0]['is_active'];
                            $start_date = $coupon_info[0]['from_date'];
                            $code = $coupon_info[0]['code'];
                            $enddate = str_replace("00:00:00", "23:59:59", $coupon_info[0]['expiration_date']);
                            $usage_limit = $coupon_info[0]['usage_limit'];
                            $times_used = $coupon_info[0]['times_used'];
                            $usage_per_customer = $coupon_info[0]['usage_per_customer'];
                            $coupon_times_used = $coupon_info[0]['coupon_times_used'];
                            $customer_times_used = $coupon_info[0]['customer_times_used'];
                            $from_created_account = $coupon_info[0]['from_created_account'];
                            $from_created_account_msg = "";
                            if ($from_created_account)
                            {
                                $from_created_account = date('Y-m-d', strtotime($from_created_account));
                                $customer_created_at = date('Y-m-d H:i:s', strtotime('+7 hour', strtotime(Mage::getSingleton('customer/session')->getCustomer()->getCreatedAt())));
                                if ($customer_created_at < $from_created_account)
                                {
                                    $from_created_account_msg = $this->__('Coupon code "%s" only applies to accounts created from %s.', Mage::helper('core')->htmlEscape($couponCode), Mage::helper('core')->htmlEscape(date('d/m/Y', strtotime($from_created_account))));
                                }
                            }
                            if (!$is_active)
                            {
                                $message = $this->__('Coupon code "%s" is no longer valid.', Mage::helper('core')->htmlEscape($couponCode));
                            }
                            elseif ($customer_id && $from_created_account_msg)
                            {
                                $message = $from_created_account_msg;
                            }
                            elseif ($usage_limit && $usage_limit <= $times_used)
                            {
                                $message = $this->__('Coupon code "%s" has expired.', Mage::helper('core')->htmlEscape($couponCode));
                            }
                            elseif ($currentdate < $start_date && $start_date)
                            {
                                $message = $this->__('Coupon code "%s" is not valid yet.', Mage::helper('core')->htmlEscape($couponCode));
                            }
                            elseif ($currentdate > $enddate && $enddate)
                            {
                                $message = $this->__('Coupon code "%s" has Expired.', Mage::helper('core')->htmlEscape($couponCode));
                            }
                            elseif (($customer_id && $usage_per_customer) && (($usage_per_customer <= $coupon_times_used) || ($usage_per_customer <= $customer_times_used)))
                            {
                                $message = $this->__('Coupon code "%s" has expired.', Mage::helper('core')->htmlEscape($couponCode));
                            }
                            else
                            {
                                $message = $this->__('Coupon code "%s" exist but do not apply the conditions.', Mage::helper('core')->htmlEscape($couponCode));
                            }
                            $error = true;
                        }
                        else if ($quote->getShippingAddress()->getShippingMethod() == "freeshipping_freeshipping")
                        {
                            // Coin code can not apply with pickup location
                            throw new Exception();
                            $error = FALSE;
                            $success = true;
                            Mage::log("*** quote: " . $quote->getEntityId() . " email:" . $quote->getCustomerEmail() . "Coin code was canceled:" . $couponCode . " , because shipping method= freeshipping_freeshipping.", null, "fhs_coin.log");
                        }
                        else
                        {
                            $message = $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode));
                            $error = true;
                        }
                    }
                    ////// end check coupon code
                }
            } else {
                $message = $this->__('Coupon code was canceled.');
                $success = true;
            }
        } catch (Mage_Core_Exception $e) {
            $error = true;
            $message = $e->getMessage();
        } catch (Exception $e) {
            if ($quote->getShippingAddress()->getShippingMethod() == "freeshipping_freeshipping"){
                $message = $this->__('Coin code can not apply with pickup location.');
            } else {
                $message = $this->__('Cannot apply the coupon code.');
            }
            $error = true;
        }
        $data = array(
            'error' => $error,
            'message' => $message,
            'success' => $success
        );
        return $data;
    }
    
    public function handleCouponErrorMessage($couponCode, $quote)
    {
        $message = null;
        ////// start check coupon code
        $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getEntityId();
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT c.*, r.is_active, r.from_date, ifnull(cu.times_used, 0) as 'coupon_times_used' , ifnull(ru.times_used, 0) as 'customer_times_used', r.from_created_account
			    FROM fhs_salesrule_coupon as c
			    JOIN fhs_salesrule as r on c.rule_id = r.rule_id
			    LEFT JOIN fhs_salesrule_coupon_usage as cu ON c.coupon_id = cu.coupon_id and cu.customer_id = '" . $customer_id . "' 
			    LEFT JOIN fhs_salesrule_customer as ru ON c.rule_id = ru.rule_id and ru.customer_id = cu.customer_id
			    WHERE c.code = '" . trim($couponCode) . "';";
        $coupon_info = $read->fetchAll($sql);

        $currentdate = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));

        if ($coupon_info[0])
        { // check code
            $is_active = $coupon_info[0]['is_active'];
            $start_date = $coupon_info[0]['from_date'];
            $code = $coupon_info[0]['code'];
            $enddate = str_replace("00:00:00", "23:59:59", $coupon_info[0]['expiration_date']);
            $usage_limit = $coupon_info[0]['usage_limit'];
            $times_used = $coupon_info[0]['times_used'];
            $usage_per_customer = $coupon_info[0]['usage_per_customer'];
            $coupon_times_used = $coupon_info[0]['coupon_times_used'];
            $customer_times_used = $coupon_info[0]['customer_times_used'];
            $from_created_account = $coupon_info[0]['from_created_account'];
            $from_created_account_msg = "";
            if ($from_created_account)
            {
                $from_created_account = date('Y-m-d', strtotime($from_created_account));
                $customer_created_at = date('Y-m-d H:i:s', strtotime('+7 hour', strtotime(Mage::getSingleton('customer/session')->getCustomer()->getCreatedAt())));
                if ($customer_created_at < $from_created_account)
                {
                    $from_created_account_msg = $this->__('Coupon code "%s" only applies to accounts created from %s.', Mage::helper('core')->htmlEscape($couponCode), Mage::helper('core')->htmlEscape(date('d/m/Y', strtotime($from_created_account))));
                }
            }
            if (!$is_active)
            {
                $message = $this->__('Coupon code "%s" is no longer valid.', Mage::helper('core')->htmlEscape($couponCode));
            }
            elseif ($customer_id && $from_created_account_msg)
            {
                $message = $from_created_account_msg;
            }
            elseif ($usage_limit && $usage_limit <= $times_used)
            {
                $message = $this->__('Coupon code "%s" has expired.', Mage::helper('core')->htmlEscape($couponCode));
            }
            elseif ($currentdate < $start_date && $start_date)
            {
                $message = $this->__('Coupon code "%s" is not valid yet.', Mage::helper('core')->htmlEscape($couponCode));
            }
            elseif ($currentdate > $enddate && $enddate)
            {
                $message = $this->__('Coupon code "%s" has Expired.', Mage::helper('core')->htmlEscape($couponCode));
            }
            elseif (($customer_id && $usage_per_customer) && (($usage_per_customer <= $coupon_times_used) || ($usage_per_customer <= $customer_times_used)))
            {
                $message = $this->__('Coupon code "%s" has expired.', Mage::helper('core')->htmlEscape($couponCode));
            }
            else
            {
                $message = $this->__('Coupon code "%s" exist but do not apply the conditions.', Mage::helper('core')->htmlEscape($couponCode));
            }
        }
        else if ($quote->getShippingAddress()->getShippingMethod() == "freeshipping_freeshipping")
        {
            // Coin code can not apply with pickup location
            throw new Exception();
            Mage::log("*** quote: " . $quote->getEntityId() . " email:" . $quote->getCustomerEmail() . "Coin code was canceled:" . $couponCode . " , because shipping method= freeshipping_freeshipping.", null, "fhs_coin.log");
        }
        else
        {
            $message = $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode));
        }
        return $message;
    }

    public function handleFreeshipCode($coupon_code, $quote, $remove)
    {
        $error = false;
        $success = FALSE;

        if ($remove)
        {
            Mage::getSingleton('checkout/cart')->getQuote()->setFreeshipCode('')
                    ->save();

            $message = $this->__('Coupon code was canceled.');
            $success = true;
        }
        else
        {
            try {
                Mage::getSingleton('checkout/cart')->getQuote()->setFreeshipCode(strlen($coupon_code) ? $coupon_code : '')
                        ->collectTotals()
                        ->save();
                if ($coupon_code && $coupon_code == $quote->getFreeshipCode())
                {
                    $message = $this->__('Coupon code "%s" is applied.', Mage::helper('core')->htmlEscape($coupon_code));
                    $error = FALSE;
                    $success = true;
                }
                else
                {
                    $message = $this->handleCouponErrorMessage($coupon_code, $quote);
                    $error = true;
                    $success = false;
                }
            } catch (Exception $ex) {
                $message = $this->__('Cannot apply the coupon code.');
                $error = true;
            }
        }

        $data = array(
            'error' => $error,
            'message' => $message,
            'success' => $success
        );
        return $data;
    }

    public function getDuplicatedOrder(){
        $dupArr = array();
         // guest: pass check duplicated order
        if (!$this->isCustomerLoggedIn()){
            return $dupArr;
        }
        $email = Mage::helper('customer')->getCustomer()->getEmail();

        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "
            select *  
            from ( 
            select o.entity_id, o.increment_id as order_id, o.status, 
            null as suborder_id, null as suborder_status, 
            i.sku, i.product_id, i.name as product_name, i.qty_ordered as qty, img.value 
            from fhs_sales_flat_order o 
            JOIN fhs_sales_flat_order_item i ON i.order_id = o.entity_id 
            LEFT JOIN fhs_catalog_product_entity_varchar img ON img.entity_id = i.product_id AND img.attribute_id = 85  
            where o.customer_email = :email 
            and o.status in ('customer_confirmed', 'tmdt', 'paid', 'pending', 'pending_payment') 
            and i.product_type = 'simple' 
            and convert_tz(o.created_at, '+0:00', '+7:00') >= DATE_ADD(CURDATE(), INTERVAL - 4 WEEK) 
            and i.original_price > 0 
            group by i.sku, o.increment_id 

            union 

            select o.entity_id, so.order_id, o.status, 
            so.suborder_id, so.status, 
            pe.sku, pe.entity_id, prodname.value as product_name, 
            sum(if(sb.bundle_id is null, soi.qty, sb.qty * soi.qty)) as qty, img.value 
            from fahasa_suborder so 
            join fhs_sales_flat_order o on o.increment_id = so.order_id 
            left JOIN fahasa_suborder_item soi ON so.suborder_id = soi.suborder_id 
            LEFT JOIN fahasa_suborder_bundle sb on soi.bundle_id = sb.bundle_id AND soi.suborder_id = sb.suborder_id AND soi.bundle_type = sb.bundle_type  
            left join fhs_catalog_product_entity pe on pe.entity_id = soi.product_id 
            LEFT JOIN fhs_catalog_product_entity_varchar prodname ON pe.entity_id = prodname.entity_id AND prodname.attribute_id = 71  
            LEFT JOIN fhs_catalog_product_entity_varchar img ON img.entity_id = soi.product_id AND img.attribute_id = 85 
            where o.customer_email = :email 
            and o.status in ('processing') 
            and so.status not in ('canceled', 'delivery_failed' ,'returned', 'returning', 'delivery_returned', 'ebiz_returned', 'complete') 
            and convert_tz(o.created_at, '+0:00', '+7:00') >= DATE_ADD(CURDATE(), INTERVAL - 4 WEEK)

            and soi.price > 0 
            group by soi.product_id, so.order_id 
            ) a  
            order by entity_id desc;";
        $vars = array(
            "email" => "$email"
        );
        $results = $readConnection->fetchAll($query, $vars);
        $cart = Mage::getModel('checkout/cart')->getQuote();
        $dupArr = array();

        foreach ($cart->getAllItems() as $item){
            $orderCount = 0;
            foreach ($results as $itemOrder){
                if ($orderCount < 3){
                    if ($item->getSku() == $itemOrder["sku"]) {
                        $data = array();
                        $data["name"] = $itemOrder["product_name"];
                        $data["img"] = $itemOrder["value"];
                        $data["productId"] = $itemOrder["product_id"];

                        $dataOrder = array();
                        $dataOrder["orderId"] = $itemOrder["order_id"];
                        $dataOrder["orderStatus"] = $itemOrder["status"];
                        $dataOrder["subOrderStatus"] = $itemOrder["suborder_status"];

                        $dupArr[$itemOrder["sku"]]["data"] = $data;
                        $dupArr[$itemOrder["sku"]]["item"][] = $dataOrder;
                        $orderCount++;
                        continue;
                    }
                    continue;
                }
                break;
            }
        }
        return $dupArr;
    }
    
    public function saveSoonReleaseProductInQuote($observer) {
        $quoteItem = $observer->getQuoteItem();
        $product = $observer->getProduct();
        $soonRelease = 0;
        if ($product->getSoonRelease()) {
            $soonRelease = 1;
	    if($product->getExpectedDate()){
		$quoteItem->setExpectedDate($product->getExpectedDate());
	    }
	    if($product->getBookReleaseDate()){
		$quoteItem->setBookReleaseDate($product->getBookReleaseDate());
	    }
        }
        $quoteItem->setSoonRelease($soonRelease);
    }
    
    public function logQuoteItemBeforeSaveOrder($observer){
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $email = $quote->getCustomerEmail();
        $listCart = $this->createQuoteCart($quote);
        \Mage::log("Before create order: email=" . $email . ", quote_id = " . $quote->getId() . ", quote_data_before=" . print_r($listCart, true), null, "quotecheckout.log");
    }
    
    public function logQuoteItemAfterSaveOrder($observer){
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $email = $quote->getCustomerEmail();
        $listCart = $this->createQuoteCart($quote);
        \Mage::log("After create order: email=" . $email . ", quote_id = " . $quote->getId() . ", quote_data_before=" . print_r($listCart, true), null, "quotecheckout.log");
    }

    public function createQuoteCart($quote){
        $listCart = array();
        foreach ($quote->getAllItems() as $product_item) {
            $stockItem = $product_item->getProduct()->getStockItem();
            $listCart[] = array(
                "product_id" => $product_item["product_id"],
                "sku" => $product_item["sku"],
                "price_incl_tax" => $product_item["price_incl_tax"],
                "quote_item_id" => $product_item["item_id"],
                "parent_item_id" => $product_item["parent_item_id"],
                "qty" => $product_item["qty"],
                "is_in_stock" => $stockItem->getIsInStock(),
                "stock_qty" => $stockItem->getQty()
            );
        }
        return $listCart;
    }
    
    public function getExpectedDeliveryInCart($request){
	Mage::getSingleton('customer/session')->unsExpectedDeliveryDateTimeNormal();
	Mage::getSingleton('customer/session')->unsExpectedDeliveryDateTimeSameday();
	if(empty($request->getAllItems())){
	    return;
	}
        $skuArr = array();
        foreach ($request->getAllItems() as $item) {
	    if(!empty(Mage::helper('fahasa_catalog/product')->getProductExpectedMsg($item)[0])){
		Mage::getSingleton('customer/session')->unsExpectedDeliveryDateTime();
		return false;
	    }
            $skuArr[$item->getSku()] = $item->getQty();
        }
	$city = $request->getDestRegion();
	$district = $request->getDestCity();
	
	$expected_delivery_result = $this->getExpectedDelivery($city, $district, $skuArr);
        $expected_delivery_datetime = $expected_delivery_result["expected_delivery_datetime"];
        $all_fresh_product = $expected_delivery_result["all_fresh_product"];
	if(!empty($expected_delivery_datetime)){
	    $today = date("Y-m-d", strtotime('+7 hours'));
	    $expected_delivery_date = date("Y-m-d", strtotime($expected_delivery_datetime));
	    if($expected_delivery_date > $today || $all_fresh_product){
		Mage::getSingleton('customer/session')->setExpectedDeliveryDateTimeNormal($expected_delivery_datetime);
		return;
	    }
	    
	    $increase_expected_day = Mage::getStoreConfig('carriers/vietnamshippingnormal/increase_expected_day');
	    if(!empty($increase_expected_day) && is_numeric($increase_expected_day)){
		$increase_expected_hour = $increase_expected_day * 8;
	    }else{
		$increase_expected_hour = 8;
	    }
	    $expected_delivery_datetime_normal = $this->calDate($expected_delivery_datetime, $increase_expected_hour);
	    
	    Mage::getSingleton('customer/session')->setExpectedDeliveryDateTimeNormal($expected_delivery_datetime_normal);
	    Mage::getSingleton('customer/session')->setExpectedDeliveryDateTimeSameday($expected_delivery_datetime);
	}
    }
    public function getExpectedDeliveryInProductview($address_data){
	$expected_data = [];
	if(!empty($address_data)){
	    $skuArr = [];
	    if(!is_array($address_data['product_sku_list']) && !empty($address_data['product_sku_list'])){
                $skuArr[$address_data['product_sku_list']] = 1;
	    }else{
                //default qty = 1
                foreach($address_data['product_sku_list'] as $item){
                    $skuArr[$item] = 1;
                }
	    }
	    $expected_delivery_result = $this->getExpectedDelivery($address_data['city'], $address_data['district'], $skuArr);
            $expected_delivery_datetime = $expected_delivery_result["expected_delivery_datetime"];
	    if(!empty($expected_delivery_datetime)){
		$expected_data['estimatedTimeDelivery'] = $this->getDateShippingStr($expected_delivery_datetime, 'product_view');
	    }
	    $expected_data['fpoint'] = $this->getFpointAccure($address_data['customer_id'], $skuArr);
	}
	return $expected_data;
    }
    
    public function getExpectedDelivery($city, $district, $skuArr){
        //flag to mark cart has all fresh product => delivery in the same day. only 1 books in cart -> $all_fresh_product = false
        $all_fresh_product = true;
        $enable_urban_delivery = Mage::getStoreConfig('vietnamshipping/general/enable_urban_delivery');
                
	$expected_delivery_datetime = '';
	try{
	    if($etdInfo = $this->getETDBasedOnCityAndDistrict($city,$district)){
		if($products = $this->getProductStockInformation($skuArr)){
		    $estimateTime = $etdInfo[0]['estimateTime'];
		    $priority = $etdInfo[0]['priority'];
		    
		    if($estimateTime > 10){
			$estimateTime = round(($estimateTime / 24), 0) * 8;
		    }
		    
		    //Uu tien cao xu ly trong 1 tieng
		    if ($priority < 2){
			$to = (int)$estimateTime + 1;
		    }else{
			$to = (int)$estimateTime + 4;
		    }
		    
		    //Thoi gian rut hang tu cac nha sach
		    foreach($products as $key=>$product){
                        //quick fix: show delivery in sameday for fresh product
                        //khoHCM_1: hai ba trung store
                        if ($product['khoHCM_1'] > 0){
                            //if product has stock in khoHCM_1: default show same day or next day (n - n+1)
                            $product_delivery_time = 0;
                            $products[$key]['to'] = $product_delivery_time;
                        } else {
                            $all_fresh_product = false;
                            $curQty = 1;
                            if ($skuArr[$product['sku']])
                            {
                                $curQty = $skuArr[$product['sku']];
                            }

                            $product['khoHCM'] = $product['khoHCM'] - $curQty + 1;
                            $product['khoHN'] = $product['khoHN'] - $curQty + 1;

                            if ($product['khoHCM'] <= 0)
                            {
                                $product['khoHCM'] = 0;
                            }
                            if ($product['khoHN'] <= 0)
                            {
                                $product['khoHN'] = 0;
                            }

                            $product_delivery_time = $to;

                            //handle soon release
                            if ($product['isSoonRelease'] == 1)
                            {
                                return $expected_delivery_datetime;
                            }
                            else
                            {
                                //HCM and HN
                                if ($city == 'Hồ Chí Minh' || $city == 'Hà Nội')
                                {
                                    //import stock from boosktores in HCM
                                    if ($city == 'Hồ Chí Minh')
                                    {
                                        if ($product['khoHCM'] == 0 && $product['khoHN'] == 0)
                                        {
                                            // customer is in HCM, products not in HCM and HN
                                            //import stock from bookstores
                                            $product_delivery_time = $product_delivery_time + 24;
                                        }
                                        else if ($product['khoHCM'] > 0)
                                        {
                                            //do nothing
                                        }
                                        else
                                        {
                                            // customer is in HCM, products in HN only
                                            $product_delivery_time = $product_delivery_time + 16;
                                        }
                                    }
                                    else if ($city == 'Hà Nội')
                                    {
                                        if ($product['khoHCM'] == 0 && $product['khoHN'] == 0)
                                        {
                                            // customer is in HN, products not in HCM and HN
                                            //import stock from bookstores
                                            $product_delivery_time = $product_delivery_time + 24;
                                        }
                                        else if ($product['khoHCM'] > 0)
                                        {
                                            // customer is in HN, products in HCM only
                                            $product_delivery_time = $product_delivery_time + 16;
                                        }
                                        else
                                        {
                                            //Kho HN has stock
                                            //do nothing
                                        }
                                    }
                                }
                                else
                                {
                                    //outside HCM and HN
                                    if ($product['khoHCM'] == 0)
                                    {
                                        $product_delivery_time = $product_delivery_time + 24;
                                    }
                                }
                            }
                            $products[$key]['to'] = $product_delivery_time;
                        }
                       
		    }
		    $maxTo = $products[0]['to'];

		    //get max ETD
		    foreach($products as $product){
			if($maxTo <= $product['to']){
			    $maxTo = $product['to'];
			}
		    }
		    $expected_delivery_datetime = date("Y-m-d H:i:s", strtotime('+7 hours'));
                    
		    //skin this day when over time
		    $hour = date('H', strtotime($expected_delivery_datetime));
                    
                    if ($enable_urban_delivery && $all_fresh_product)
                    {
                        $hourfornextday = Mage::getStoreConfig('vietnamshipping/general/urban_delivery_milestone_hour');
                        if (isset($hourfornextday) && $hourfornextday !== '' && is_numeric($hourfornextday))
                        {
                            if ($hour >= $hourfornextday)
                            {
                                $expected_delivery_datetime = date("Y-m-d H:i:s", strtotime(date("Y-m-d", strtotime("+1 day", strtotime($expected_delivery_datetime))) . " 08:00:00"));
                            }
                        }
                        
                        $add_more_day = Mage::getStoreConfig('vietnamshipping/general/urban_delivery_add_more_day');
                        if (!empty($add_more_day) && is_numeric($add_more_day) && $add_more_day > 0){
                            $expected_delivery_datetime = date("Y-m-d H:i:s", strtotime("+".$add_more_day." day", strtotime($expected_delivery_datetime)));
                        }
                    }
                    else
                    {
                        if ($hour < 8 || $hour > 16)
                        {
                            if ($hour < 8)
                            {
                                $expected_delivery_datetime = date("Y-m-d H:i:s", strtotime(date("Y-m-d", strtotime($expected_delivery_datetime)) . " 08:00:00"
                                ));
                            }
                            else if ($hour > 16)
                            {
                                $expected_delivery_datetime = date("Y-m-d H:i:s", strtotime(date("Y-m-d", strtotime("+1 day", strtotime($expected_delivery_datetime))) . " 08:00:00"
                                ));
                            }
                        }
                        else
                        {
                            $hourfornextday = Mage::getStoreConfig('carriers/vietnamshippingsameday/hourfornextday');

                            if (!empty($hourfornextday) && is_numeric($hourfornextday))
                            {
                                if ($hour >= $hourfornextday)
                                {
                                    $expected_delivery_datetime = date("Y-m-d H:i:s", strtotime(date("Y-m-d", strtotime("+1 day", strtotime($expected_delivery_datetime))) . " 08:00:00"
                                    ));
                                }
                            }
                        }
			
			//Thêm ngày dự kiến trong mùa dịch
			if($city == 'Hồ Chí Minh'){
			    $add_more_day = Mage::getStoreConfig('carriers/vietnamshippingsameday/add_more_day_hcm');
			}else if($city == 'Hà Nội'){
			    $add_more_day = Mage::getStoreConfig('carriers/vietnamshippingsameday/add_more_day_hn');
			}else{
			    $add_more_day = Mage::getStoreConfig('carriers/vietnamshippingsameday/add_more_day_other');
			}
			if(!empty($add_more_day)){
			    if(is_numeric($add_more_day)){
				if($add_more_day > 0){
				    $expected_delivery_datetime = date("Y-m-d H:i:s", strtotime("+".$add_more_day." day", strtotime($expected_delivery_datetime)));
				}
			    }
			}
                    }
		    
		    //$maxTo = 0;
		    //Mage::getSingleton('customer/session')->setExpectedDeliveryDateTimeHour($maxTo);
                    if ($all_fresh_product && $enable_urban_delivery || $add_more_day > 0){
                        $expected_delivery_datetime = $expected_delivery_datetime;
                    } else {
                        $expected_delivery_datetime = $this->calDate($expected_delivery_datetime, $maxTo);
                    }
		    
		}
	    }
	} catch (Exception $ex) {}
	
        return array(
            "expected_delivery_datetime" => $expected_delivery_datetime,
            "all_fresh_product" => $all_fresh_product
        );
    }
    
    public function getProductStockInformation($skuArr){
	$result = [];
	if(empty($skuArr)){return $result;}
        $skus_str = array_keys($skuArr);
	
	$key_str = implode("'_'", $skus_str);
        if(Mage::registry($key_str)){
	    return Mage::registry($key_str);
	}
	try{
	    $read = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select pe.sku, if(eDate.value <= curdate(), null, eDate.value) as expectedDate, ifnull(soonRelease.value, 0) as isSoonRelease,  ".
		    "ifnull(fbs.thuong_mai_dien_tu, 0) + ifnull(fbs.tiki, 0) + ifnull(fbs.lazada, 0) + ifnull(fbs.vnshop, 0) as khoHCM, ".
		    "ifnull(fbs.thuong_mai_dien_tu_ha_noi, 0) as khoHN, ".
                    "ifnull(fbs.thuong_mai_dien_tu_1, 0) as khoHCM_1 " .
		    "from fhs_catalog_product_entity pe  ".
		    "left join fahasa_bookstore_stock fbs on fbs.mabh = pe.sku ".
		    "LEFT JOIN fhs_catalog_product_entity_int soonRelease ON pe.entity_id = soonRelease.entity_id AND soonRelease.attribute_id = 155 ".
		    "left join fhs_catalog_product_entity_datetime eDate ON pe.entity_id = eDate.entity_id AND eDate.attribute_id = 191 ".
		    "where pe.sku in ('".implode("','", $skus_str)."');";
	    $data = $read->fetchAll($sql);
	    if(!empty($data)){
		foreach ($data as $item){
		    $result[$item['sku']] = $item;
		}
	    }
	    Mage::register($key_str, $result);
	} catch (Exception $ex) {}
	
	return $result; 
    }
    
    public function getETDBasedOnCityAndDistrict($city, $district){
	$result = [];
	if(empty($city) || empty($district)){return $result;}
	try{
	    $read = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select dplan.name as plan, dplan.priority, ".
		    "region.province, region.district, estimate_time as estimateTime ".
		    "from fahasa_delivery_region dr  ".
		    "join fahasa_delivery_schedule ds on dr.id = ds.region_id  ".
		    "join fahasa_delivery_plan dplan on dplan.id = ds.plan_id  ".
		    "join fahasa_delivery_region region on ds.region_id = region.id ".
		    "where ds.bookstore_id = (select if(region = 'Miền Bắc', 89, 67) as bookstoreId ".
			"from fahasa_region ".
			"where city = '" .$city. "') ".
		    "and trim(dr.province) = '" .$city. "' ".
		    "and trim(dr.district) = '" .$district. "' ".
		    "group by dr.province, dr.district; ";
	    $result = $read->fetchAll($sql);
	} catch (Exception $ex) {}
	return $result; 
    }
    
    public function getFpointAccure($customer_id, $skuArr){
	$fpointAccure = 0;
	$percent = 0.01;
	$products_fpoint_accure = $this->getProductPercentFpointAccure($skuArr);
	if(!empty($customer_id)){
	    $customer_percent_fpoint_accure = $this->getPercentFpointAccureByCustomerId($customer_id);
	    if(!empty($customer_percent_fpoint_accure['data'])){
		$percent = $customer_percent_fpoint_accure['data'];
	    }
	}
	if(!empty($products_fpoint_accure)){
	    foreach($products_fpoint_accure as $item){
		$fpointAccure = $fpointAccure + (round($item['price'], 0) * $percent);
	    }
	}
	return $fpointAccure;
    }
    
    public function getProductPercentFpointAccure($skuArr){
	$result = [];
	if(empty($skuArr)){return $result;}
	try{
	    $read = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select pe.sku, pip.final_price as price ".
		    "from fhs_catalog_product_entity pe ".
		    "left join fhs_catalog_product_index_price_store pip on pip.entity_id = pe.entity_id and pip.customer_group_id = 0 and pip.store_id = 1 ".
		    "where pe.sku in ('".implode("','", $skuArr)."'); ";
	    $result = $read->fetchAll($sql);
	} catch (Exception $ex) {}
	return $result; 
    }
    
    public function getPercentFpointAccureByCustomerId($customer_id){
	$result = [];
	if(empty($customer_id)){return $result;}
	try{
	    $read = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select percent_point_collect * 0.01 as percent ".
		    "from fhs_fvip_level_rule r ".
		    "join fhs_customer_entity c on c.vip_level = r.entity_id ".
		    "where c.entity_id = ". $customer_id ."; ";
	    $result = $read->fetchAll($sql);
	} catch (Exception $ex) {}
	return $result; 
    }
    
    //Calculate Datetime
    //Subtract saturday and sunday
    //Subtract holiday in list solarholidays and lunarholidays
    //increase hours
    public function calDate($date, $hours){
	$days = floor($hours/8);
	if($days > 0){
	    $hours = $hours - ($days*8);
	}
	
	$solarholidays = Mage::getStoreConfig('carriers/vietnamshippingsameday/solarholidays');
	if($solarholidays){
	    $solarholidays = array_map("trim", explode(',', trim($solarholidays)));
	}
	$lunarholidays = Mage::getStoreConfig('carriers/vietnamshippingsameday/lunarholidays');
	if($lunarholidays){
	    $lunarholidays = array_map("trim", explode(',', trim($lunarholidays)));
	}

	$is_calculated_time = true;
	$is_calculated_hour = false;
	$changed = false;
	while ($is_calculated_time){
	    //check weekend day
	    $weekDay = date('w', strtotime($date));
	    if($weekDay == 0){
		$date = date("Y-m-d H:i:s", 
			    strtotime(date("Y-m-d", strtotime("+1 day", strtotime($date)))." 08:00:00"
			));
		$changed = true;
		goto check_end;
	    }
	    if($weekDay == 6){
		$date = date("Y-m-d H:i:s", 
			    strtotime(date("Y-m-d", strtotime("+2 day", strtotime($date)))." 08:00:00"
			));
		$changed = true;
		goto check_end;
	    }
	    
	    //check have in solar holidays
	    if(!empty($solarholidays)){
		if(in_array(date("d-m", strtotime($date)), $solarholidays)){
		    $date = date("Y-m-d H:i:s", 
				strtotime(date("Y-m-d", strtotime("+1 day", strtotime($date)))." 08:00:00"
			    ));
		    $changed = true;
		    goto check_end;
		}
	    }

	    //check have in lunar holidays
	    if(!empty($lunarholidays)){
		if(in_array(date("d-m", strtotime($date)), $lunarholidays)){
		    $date = date("Y-m-d H:i:s", 
				strtotime(date("Y-m-d", strtotime("+1 day", strtotime($date)))." 08:00:00"
			    ));
		    $changed = true;
		    goto check_end;
		}
	    }
	    if($days > 0){
		$date = date("Y-m-d H:i:s", strtotime("+1 day", strtotime($date)));
		$days = $days -1;
		$changed = true;
		goto check_end;
	    }
	    
	    if($hours > 0 && !$is_calculated_hour){
		$date_hour = date('H', strtotime($date));
		switch ($date_hour){
		    case "9":
			if(($hours + 1) >= 8){
			    $days = $days + 1;
			    $hours = ($hours + 1) - 8;
			}else{
			    $hours = ($hours + 1);
			}
			break;
		    case "10":
			if(($hours + 2) >= 8){
			    $days = $days + 1;
			    $hours = ($hours + 2) - 8;
			}else{
			    $hours = ($hours + 2);
			}
			break;
		    case "11":
			if(($hours + 3) >= 8){
			    $days = $days + 1;
			    $hours = ($hours + 3) - 8;
			}else{
			    $hours = ($hours + 3);
			}
			break;
		    case "12":
			if(($hours + 4) >= 8){
			    $days = $days + 1;
			    $hours = ($hours + 4) - 8;
			}else{
			    $hours = ($hours + 4);
			}
			break;
		    case "13":
			if(($hours + 5) >= 8){
			    $days = $days + 1;
			    $hours = ($hours + 5) - 8;
			}else{
			    $hours = ($hours + 5);
			}
			break;
		    case "14":
			if(($hours + 6) >= 8){
			    $days = $days + 1;
			    $hours = ($hours + 6) - 8;
			}else{
			    $hours = ($hours + 6);
			}
			break;
		    case "15":
			if(($hours + 7) >= 8){
			    $days = $days + 1;
			    $hours = ($hours + 7) - 8;
			}else{
			    $hours = ($hours + 7);
			}
			break;
		    case "16":
			if(($hours + 8) >= 8){
			    $days = $days + 1;
			    $hours = ($hours + 8) - 8;
			}
			break;
		}
		$is_calculated_hour = true;
		if($days > 0){
		    $date = date("Y-m-d H:i:s", 
				strtotime(date("Y-m-d", strtotime("+1 day", strtotime($date)))." 08:00:00"
			    ));
		    $days = $days -1;
		    $changed = true;
		    goto check_end;
		}
	    }
	    
	    if($hours > 0){
		$date = date("Y-m-d H:i:s", 
			strtotime(date("Y-m-d", strtotime($date))." ".$this->getHourStr($hours).":00:00"
		    ));
	    }
	    $changed = false;

	    check_end:
	    if(!$changed){
		$is_calculated_time = false;
	    }
	}
	return $date;
    }
    
    public function getHourStr($hour){
	$hour = $hour +8;
	if(strlen($hour) < 2){
	    $hour = "0".$hour;
	}
	return $hour;
    }
    
    //type = 'shipping_method' use in product view page
    //type = 'product_view' use in product view page
    public function getDateShippingStr($date, $type = 'shipping_method'){
	$result = '';
	if(!empty($date)){
	    $day = date("l", strtotime($date));
	    if(Mage::app()->getLocale()->getLocaleCode() == "vi_VN"){
		$result = Mage::helper("vietnamshipping")->__($day) ." - ". date("d/m", strtotime($date));
	    }else{
		$result = Mage::helper("vietnamshipping")->__($day) ." - ". date("m-d", strtotime($date));
	    }
	}
	return $result;
    }
    
    public function addTryout($apply){
	$session = \Mage::getSingleton('checkout/session');

        if ($apply !== "1") {
            $session->unsetData('onestepcheckout_tryout');
            $message = "Remove F-point";
            $success = true;
        } else {
            $session->setData('onestepcheckout_tryout', 1);
            $message = "Add F-point";
            $success = true;
        }
        $session->getQuote()
                ->collectTotals()
                ->save();
        $data = (object) [
                    "success" => $success,
                    "message" => $message
        ];
        $data->checkout = $this->getCartJson($sessionId);

        return $data;
    }
    public function getCartJson(){
	$cart = \Mage::getSingleton('checkout/cart');
        if ($cart->getQuote()->getItemsCount()) {
            $cart->init();
            $cart->save();
        }
        $session = \Mage::getSingleton('checkout/session');
        $quote = $session->getQuote();
        foreach ($quote->getAllAddresses() as $ad){
            if($ad->getAddressType() == "billing"){
                $billingAddressId = $ad->getAddressId();
            }else{
                $shippingAddressId = $ad->getAddressId();
            }
        }
        $quote->collectTotals();
        $items = $quote->getAllVisibleItems();
        $data = array();
        
	
        $calculateSubtotalCart = 0;
        //loop products 
        foreach ($items as $item) {
            $product = $item->getProduct();
            $isFreeProduct = null;
            $priceFlag = false;
            if($item->getIsFreeProduct()){
                $price = 0;
                $isFreeProduct = 1;
            }  else {
                $price = $item->getBaseOriginalPrice();
                $isFreeProduct = 0;
                $priceFlag = true;
                $calculateSubtotalCart = $calculateSubtotalCart + ($price *(int) $item->getQty());
            }
            
            $original_price = 0;
            if ($priceFlag){
                if (\Mage::helper('discountlabel')->getBundlePrice($product)) {
                    $original_price = $product->getData('price');
                } else {
                    $original_price = $product->getPrice();
                }
            }
	    $soon_release = '';
	    if($product->getSoonRelease() == 1 && ((int) $product->getData('price') !== 0 || $product->getTypeId() == 'bundle')){
		$soon_release = Mage::helper('fahasa_catalog/product')->getProductExpectedMsg($product)[0];
	    }
            $obj = (object) [
                        'productId' => (int) $product->getEntityId(),
                        'sku' => $product->getSku(),
                        'quantity' => (int) $item->getQty(),
                        'name' => $item->getProduct()->getName(),
                        'price' => (int) $price,
                        'image' => \Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(145, 145)->__toString(),
                        'quoteItemId' => $item->getId(),
                        'isFreeProduct' => $isFreeProduct,
                        'message' => $item->getHasError() ? $item->getMessage() : null,
                        'original_price' => $original_price,
                        'category_main' => $product->category_main,
                        'category_mid' => $product->category_mid,
                        'soon_release' => $soon_release
            ];

            if ($product->type_id == 'bundle') {
                $options = $item->getChildren();
//loop bundle selection
                $bundleOptions = array();
                foreach ($options as $option) {
                    $selections = $option->getOptions();
                    $selOption = unserialize($selections[3]->getValue());
                    $optionId = $selOption['option_id'];
                    $optionItem = array(
                        'productId' => (int) $option->getProductId(),
                        'sku' => $product->getSku(),
                        'quantity' => (int) $option->getQty(),
                        'name' => $option->getName(),
                        'price' => (int) $option->getBaseOriginalPrice(),
                        'selectionId' => $selections[0]->getValue()
                    );
                    // Check for duplicate items before pushing
                    if (!in_array($optionItem, $bundleOptions[$optionId], true)) {
                        $bundleOptions[$optionId][] = $optionItem;
                    }
                }
// format arr json 
                $bundleOptionArr = array();
                foreach ($bundleOptions as $k => $v) {
                    if ($k == $bundleOptionArr->optionId) {
                        array_push($bundleOptionArr[$k], $v);
                    } else {
                        $bundleOptionArr[] = array(
                            "optionId" => $k,
                            "selectedProducts" => $v
                        );
                    }
                }
                $obj->options = $bundleOptionArr;
            }
            array_push($data, $obj);
        }
//        $rs["vipId"] = $_POST["vip_id"];
        $rs["fhsCoin"] = \Mage::getSingleton('core/session')->getFhsCoin()["code"];
        $rs["couponCode"] = \Mage::getSingleton('checkout/type_onepage')->getQuote()->getCouponCode();
        $rs["couponLabel"] = \Mage::getSingleton('core/session')->getCouponLabel();
	$rs["freeshipCouponLabel"] = \Mage::getSingleton('core/session')->getFreeshipLabel();
        $rs["freeshipCouponCode"] = \Mage::getSingleton('checkout/type_onepage')->getQuote()->getFreeshipCode();
        
        //set couponCode = fhsCoin (because frontent is only calculted by couponCode
        if ($rs['fhsCoin'] && !empty($rs["fhsCoin"]) && empty($rs["couponCode"])){
            $rs["couponCode"] = $rs["fhsCoin"];
        }
        
        $totals = json_decode($this->getTotals($sessionId));
        $rs["products"] = $data;
        
        //Similate wrong cart
//        foreach ($totals as $k => $v) {
//            if ($v->code == "subtotal" || $v->code == "grand_total") {
//                    $v->price = 200000;
//            }
//        }
        
        // check bug 
        $calculateSubtotalCart = round($calculateSubtotalCart);
        $rs["subTotalPriceItems_calculated"] = $calculateSubtotalCart;
        
        //this behind code fix: when user get cart, total of cart has wrong value. Then customer get cart at the second time, the total is right
        foreach ($totals as $k => $v) {
            if ($v->code == "subtotal") {
                if ((int)$calculateSubtotalCart !== (int)$v->price) {
                    // Mismatch price happen here
                    \Mage::log("BUG cart - Mismatch price happen here - sessionId:" . $sessionId . ", magento " . $v->code . "= " . $v->price . ", calculate SubtotalCart= " . $calculateSubtotalCart . ", email=" . $quote->getCustomerEmail(), null, "restapi.log");
                    // caculate price total
                    $v->price = $calculateSubtotalCart;
                    $misMatchTotal = 1;
                    break;
                }
            }
        }
        if ($misMatchTotal == 1) {
            //update total
            $grandTotalEnd = 0;
            $dataTotalEnd = array();
            foreach ($totals as $key => $value) {
                if ($value->code !== "grand_total") {
                    $grandTotalEnd = $grandTotalEnd + $value->price;
                    $obj = (object) [
                                "title" => $value->title,
                                "price" => round($value->price),
                                "code" => $value->code
                    ];
                    array_push($dataTotalEnd, $obj);
                } else {
                    $rs["grandTotalMagentoWrong"] = $value->price;
                    $objGrandTotal = (object) [
                                "title" => $value->title,                                
                                "code" => $value->code
                    ];
                }
            }

            // Update new calculated grand total
            $objGrandTotal->price = $grandTotalEnd;

            array_push($dataTotalEnd, $objGrandTotal);
            $totals = $dataTotalEnd;
        }
        $rs["totals"] = $totals;
        $rs["billingAddressId"] = $billingAddressId;
        $rs["shippingAddressId"] = $shippingAddressId;
        $rs["hasError"] = $quote->getHasError();
	
        return $rs;
    }
    
    public function getTotals() {
        $session = \Mage::getSingleton('checkout/session');
        
        $quote = $session->getQuote();

        $totals = $quote->getTotals();       
        $data = array();
        foreach ($totals as $k => $v) {
            if ($k == "tax") {
                continue;
            } else if ($k == "shipping") {
                $price = $v->getValue() + $v->getValue() * 0.1;
            } else {
                $price = $v->getValue();
            }
            $obj = (object) [
                        "title" => $v->getTitle(),
                        "price" => round($price),
                        "code" => $v->getCode()
            ];
            array_push($data, $obj);
        }
        return json_encode($data);
    }
    
    public function getShippingMethod1($billing_data, $shipping_data, $shippingMethod, $freeship){
        $error = FALSE;
        $success = true;
	// restyle for param use in magento
        $billing = array();
        $billing['country_id'] = $billing_data->countryId;
        $billing['region_id'] = $billing_data->regionId;
        $billing['city_id'] = $billing_data->cityId;
        $billing['city'] = $billing_data->city;
        $billing['use_for_shipping'] = $billing_data->useForShipping;
        $shipping = array();
        $shipping['country_id'] = $shipping_data->countryId;
        $shipping['region_id'] = $shipping_data->regionId;
        $shipping['city_id'] = $shipping_data->cityId;
        $shipping['city'] = $shipping_data->city;
        
        $session = \Mage::getSingleton('checkout/session');
        if ($freeship == 0) {
            $session->unsetData('onestepcheckout_freeship');
            $session->unsetData('onestepcheckout_freeship_amount');
        } else {
            $session->setData('onestepcheckout_freeship', 1);
        }

        $onePage = \Mage::getSingleton('checkout/type_onepage');
        $this->saveAddressShipping(
                $onePage, $billing, $shipping, $billingAddressId, $shippingAddressId, $shippingMethod);
        
        $quote = $onePage->getQuote();
        $rates = $quote->getShippingAddress()->getAllShippingRates();

        $data = array();
        $data['freeship'] = $session->getData('onestepcheckout_freeship');
        $dataMethod = array();
        
        $shippingRateGroups = $quote->getShippingAddress()->getGroupedAllShippingRates();
        if (empty($shippingRateGroups['matrixrate'])){
            $data['notification'] = array(
                "vi" => \Mage::getStoreConfig('carriers/vietnamshippingnormal/notification', 1),
                "en" => \Mage::getStoreConfig('carriers/vietnamshippingnormal/notification', 2)
            );
        }
        
        foreach ($rates as $shippingRate) {
            if ($shippingRate->getCode() == "freeshipping_freeshipping") {
                // if freeshipping_freeshipping exist => not show in UI
                // freeshipping_freeshipping use for pickupLocation (nhan hang tai nha sach)
                // hidden for mobile app
                continue;
            }
            $methodTitle = $shippingRate->getMethodTitle();
            if (strpos($shippingRate->getCode(), 'matrixrate') === false && !empty($methodTitle))
            {
                $methodTitle = date('Y/m/d', strtotime($methodTitle));
            }
           
            $obj = (object) [
                        "label" => $shippingRate->getCarrierTitle(),
                        "shippingMethod" => $shippingRate->getCode(),
                        "price" => $shippingRate->getPrice(),
                        "methodTitle" => $methodTitle
            ];
            array_push($dataMethod, $obj);
        }
	
        $data['listShippingMethod'] = $dataMethod;
        
        //get config open delivery option (noel delivery)
        if (Mage::getStoreConfig('event_delivery/config/show_choose_delivery_date_payment')){
            $data['event_delivery'] = Mage::helper("fahasa_customer")->getEventDeiveryList(true, null, null, null, null);
        }
        
	if(!empty($data['listShippingMethod']) && !empty($data['event_delivery'])){
	    $price = null;
	    foreach($data['listShippingMethod'] as $item){
		if($item->shippingMethod == 'vietnamshippingnormal_vietnamshippingnormal'){
		    $price = $item->price;
		}
	    }
	    if($price !== null){
		foreach($data['event_delivery'] as $key=>$item){
		    if($item['shippingMethod'] == 'vietnamshippingnormal_vietnamshippingnormal'){
			$item['price'] = $price;
			$data['event_delivery'][$key] = $item;
		    }
		}
	    }
	}
        $data['success'] = $success;
        $data['error'] = $error;
        return $data;
    }
    
    public function addCouponCode($couponCode, $apply, $isApp) {
        $session = \Mage::getSingleton('checkout/session');

        $quote = $session->getQuote();
        if ((int) $apply !== 1) {
            $remove = "1";
        }else{
            $remove = "0";
        }
        
                 
        $couponRule = Mage::getModel('salesrule/coupon')->load($couponCode, 'code');

        $oRule = Mage::getModel('salesrule/rule')->load($couponRule->getRuleId());
        if ($oRule->getSimpleFreeShipping())
        {
            $data = $this->handleFreeshipCode($couponCode, $quote, $remove);
        }
        else
        {
            $data = $this->handleCoupponCode($couponCode, $quote, $remove, $isApp);
        }


        $result = (object) [
                    "message" => $data['message'],
                    "error" => $data['error'],
                    "success" => $data['success']
        ];
        $result->checkout = $this->getCartJson();
        return $result;
    }
    
    public function createOrder($billing, $shipping, $vatData, 
                $shippingMethod, $paymentMethod, $couponCode, $vipCode, 
                $giftwrap, $localeStoreId, $freeship, $tryout, $pickupLocation, $event_cart_option, $event_delivery_option = null) {
	$result = [];
	$result['success'] = true;
	$result['message'] = '';
        $error = "null";

	if(!empty($event_delivery_option->eventDeliveryId) && !empty($event_delivery_option->eventDeliveryId)){
	    $billing->regionId;
	    $shipping->city;
	    $event_delivery = Mage::helper("fahasa_customer")->getEventDeiveryList(true, null, null, null, null);
	    $has_event_delivery = false;
	    if(!empty($event_delivery)){
		foreach($event_delivery as $key=>$item){
		    if($item['enable']){
			if($event_delivery_option->eventDeliveryId == $key){
			    if(!empty($item['periods'])){
				foreach($item['periods'] as $period_key=>$period){
				    if($event_delivery_option->periodId == $period_key){
					if($period['enable']){
					    $has_event_delivery = true;
                                            $delivery_date_event = $period['name'];
					    goto out_loop_check_event_delivery;
					}
				    }
				}
			    }
			}
		    }
		}
	    }
	    out_loop_check_event_delivery:
	    if(!$has_event_delivery){
		$result['success'] = false;
		$result['message'] = "Ngày nhận hàng bạn chọn dã hết";
		return $result;
	    }
	}else{
	    $event_delivery_option = null;
	}
	
        //because mobile app: flashsale queue and ward billing ared deployed in a release version
        if (!array_key_exists("ward", $billing)){
	    $result['success'] = false;
	    $result['message'] = "PLEASE_UPDATE_NEW_VERSION";
            return $result;
        }
        
	// update vatData to info customer
	$fmFields = $vatData;
	if (\Mage::getSingleton('customer/session')->isLoggedIn()) {
	    $customer = \Mage::getSingleton('customer/session')->getCustomer();
	    if ($fmFields->vatCompany && $fmFields->vatAddress && $fmFields->vatTaxcode && $fmFields->vatName && $fmFields->vatEmail) {
		\Mage::helper('fahasa_customer')->saveVAT($customer->getEntityId(), $fmFields->vatCompany, $fmFields->vatAddress, $fmFields->vatTaxcode, $fmFields->vatName, $fmFields->vatEmail);
	    }
	}
	
        //Queue
        $enable_order_queue = \Mage::getStoreConfig('flashsale_config/config/enable_queue');
        
        if ($enable_order_queue == 1) {
            // format lai kieu data hop voi function addOrderToQueue (Mobile)
            $billing_data = array(
                'firstname' => $billing->firstName,
                'lastname' => $billing->lastName,
                'telephone' => $billing->telephone,
                'country_id' => $billing->countryId,
                'region_id' => $billing->regionId,
                'region' => $billing->region,
                'city' => $billing->city,
                'ward' => $billing->ward,
                'postcode' => $billing->postcode,
                'street' => array(
                    '0' => $billing->street,
                ),
                'use_for_shipping' => $billing->useForShipping,
                'email' => $billing->email,
            );

            if ($billing->useForShipping != 1) {
                $shipping_data = array(
                    'firstname' => $shipping->firstName,
                    'lastname' => $shipping->lastName,
                    'telephone' => $shipping->telephone,
                    'country_id' => $shipping->countryId,
                    'region_id' => $shipping->regionId,
                    'region' => $shipping->regionId,
                    'city' => $shipping->city,
                    'ward' => $shipping->ward,
                    'postcode' => $shipping->postcode,
                    'street' => array(
                        '0' => $shipping->street,
                    ),
                    'email' => $shipping->email
                );
            }

            $fm_vatData = (object) [
                        "fm_checkout_note" => $vatData->customerNote,
                        "fm_vat_company" => $vatData->vatCompany,
                        "fm_vat_address" => $vatData->vatAddress,
                        "fm_vat_taxcode" => $vatData->vatTaxcode,
                        "fm_vat_name" => $vatData->vatName,
                        "fm_vat_email" => $vatData->vatEmail
            ];
            
            $dataCreateOrder = array(
                'billing' => $billing_data,
                'shipping' => $shipping_data ? $shipping_data : $shipping,
                'onestepcheckout_freeship_checkbox' => $freeship,
                'fm_fields' => $fm_vatData,
                'shippingMethod' => $shippingMethod,
                'payment' => array(
                    "method" => $paymentMethod),
                'coupon_code' => $couponCode,
                'vipCode' => $vipCode,
                'giftwrap' => $giftwrap,
                'localeStoreId' => $localeStoreId,
                'tryout' => $tryout,
                'pickupLocation' => $pickupLocation,
                "event_cart_option" => $event_cart_option,
                "event_delivery_option" => $event_delivery_option,
                "affId" => Mage::getModel("core/cookie")->get("affId"),
                "deliveriDate" => "",
            );
            // end format 
            $quote_session = \Mage::getSingleton('checkout/session')->getQuote();
            $quote_id = $quote_session->getId();
            
            // lay date cua du kien giao hang --- : 
            $deliveriDate = "";
            $isFormatDate = false;
            if (Mage::getSingleton('customer/session')->getExpectedDeliveryDateTimeSameday()) {
                $deliveriDate = Mage::getSingleton('customer/session')->getExpectedDeliveryDateTimeSameday();
                $isFormatDate = true;
            } else {
                // vi du : Thu 2 20/12/2022
                if ($has_event_delivery && $delivery_date_event) {
                    $deliveriDate = $delivery_date_event;
                } else {
                    $rates = $quote_session->getShippingAddress()->getAllShippingRates();
                    foreach ($rates as $shippingRate) {
                        if ($shippingRate->getCode() == "freeshipping_freeshipping") {
                            continue;
                        }
                        // vi du : 2022-02-12 23:59:59
                        $deliveriDate = $shippingRate->getMethodTitle();
                        $isFormatDate = true;
                    }
                }
            }
            if ($isFormatDate) {
                if (strtotime($deliveriDate)) {
                    //format time to get Day vietnamese : 
                    $timestamp = $deliveriDate;
                    $translateVN = $this->DayOfTimeVN();
                    $time = date('d/m', strtotime($timestamp));
                    $day = date('D', strtotime($timestamp));
                    $day = $translateVN[$day];
                    $deliveriDate = $day . " - " . $time;
                }else{
                    $deliveriDate = "";
                }
            }
            $dataCreateOrder['deliveriDate']= $deliveriDate;
            
            $one_step_checkout_queue_helper = \Mage::helper('onestepcheckout/queue');
            
            //need to set payment_method in $_POST for calculating cod_fee in grand_total
            if ($paymentMethod == "cashondelivery") {
                $_POST['payment_method'] = "cashondelivery";
            }

            $queueRs = $one_step_checkout_queue_helper->addOrderToQueue($dataCreateOrder, $quote_id);
            \Mage::log(" *** createOrder with quote_id = ". $quote_id ." has queueRs = ".$queueRs , null, "payment.log");
	    
            if ($queueRs['result']) {
		$result['isFlashSale'] = true;
                $result['quote_id'] = $quote_id;
            }else{
		$result['success'] = false;
		$result['message'] = $queueRs['message'];
	    }
	    return $result;
        }

        /// At this stage, $quote has an old grand total
        $onePage = \Mage::getSingleton('checkout/type_onepage');
        $totalTemp = $onePage->getQuote()->getTotals();
        $grandTotalCheck = array_filter($totalTemp, function($item){
            return ($item['code'] == 'grand_total');
        });
        $old_grand_total = null;
        if (sizeof($grandTotalCheck) > 0){
            $grandTotalItem = $grandTotalCheck['grand_total'];
            $old_grand_total = $grandTotalItem['value'];
        }
        try{
        $cart = \Mage::getSingleton('checkout/cart');
        if ($cart->getQuote()->getItemsCount()) {
            $cart->init();
            $cart->save();
        }
        
        $session = \Mage::getSingleton('checkout/session');
        $quote = $session->getQuote();
        
        $websiteId = \Mage::app()->getWebsite()->getId();
        $store = \Mage::app()->getStore();
        $quote->setStoreId($store->getId());
        
        $billing_data = array(
            'customer_address_id' => '',
            'prefix' => '',
            'firstname' => $billing->firstName,
            'middlename' => '',
            'lastname' => $billing->lastName,
            'suffix' => '',
            'company' => $billing->company,
            'street' => array(
                '0' => $billing->street,
            ),
            'postcode' => $billing->postcode,
            'telephone' => $billing->telephone,
            'fax' => $billing->fax,
            'city' => $billing->cityId,
            'country_id' => $billing->countryId,
            'region' => $billing->regionId,
            'region_id' => $billing->regionId,
            'ward' => $billing->ward,
            'save_in_address_book' => 1,
            'use_for_shipping' => $billing->useForShipping
        );
        $billingAddress = $quote->getBillingAddress()->addData($billing_data);
        if ($billing->useForShipping == 1) {
            $shipping = $billing;
        } else {
            $shipping = $shipping;
        }
        
        $shipping_data = array(
            'customer_address_id' => '',
            'prefix' => '',
            'firstname' => $shipping->firstName,
            'middlename' => '',
            'lastname' => $shipping->lastName,
            'suffix' => '',
            'company' => $shipping->company,
            'street' => array(
                '0' => $shipping->street,
            ),
            'postcode' => $shipping->postcode,
            'telephone' => $shipping->telephone,
            'fax' => $shipping->fax,
            'city' => $shipping->cityId,
            'country_id' => $shipping->countryId,
            'region' => $shipping->regionId,
            'region_id' => $shipping->regionId,
            'ward' => $shipping->ward,
            'save_in_address_book' => 1
        );
        $shippingAddress = $quote->getShippingAddress()->addData($shipping_data);

        $customer = \Mage::getModel("customer/customer");
        $customer->setWebsiteId($websiteId)->loadByEmail($billing->email);
        if (\Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = \Mage::getSingleton('customer/session')->getCustomer();
            if ($freeship == 0) {
                $session->unsetData('onestepcheckout_freeship');
                $session->unsetData('onestepcheckout_freeship_amount');
            } else {
                $session->setData('onestepcheckout_freeship', 1);
                \Mage::log($customer->getId() . "-" . $customer->getEmail() . "*** freeship =" . $freeship . ", use freeship in mobile", null, "fpoint.log");
                $quote->getShippingAddress()->setIsFreeship(1);
                $quote->getShippingAddress()->setFreeshipAmount($session->getData('onestepcheckout_freeship_amount'));
            }
            if ($tryout == 0) {
                $session->unsetData('onestepcheckout_tryout');
                $session->unsetData('onestepcheckout_tryout_amount');
            } else {
                \Mage::log($customer->getId() . "-" . $customer->getEmail() . "*** fpoint =" . $freeship . ", use fpoint in mobile", null, "fpoint.log");
                $session->setData('onestepcheckout_tryout', 1);
            }
            $customerId = $customer->getId();
            
            // save address billing
            if ((int) $billing->saveBilling == (int) 1) {
                // set customer address
                $customAddress = \Mage::getModel('customer/address');
                $customAddress->setData($billing_data)
                        ->setCustomerId($customerId)
                        ->setSaveInAddressBook('1');
                
                if((int) $billing->isDefaultBilling == (int) 1){
                    $customAddress->setIsDefaultBilling('1');
                }
                if((int) $billing->isDefaultShipping == (int) 1){
                    $customAddress->setIsDefaultShipping('1');
                }
                $customAddress->save();
            }
        } else {
            $quote->setCheckoutMethod('guest')
                    ->setCustomerId(null)
                    ->setCustomerEmail($billing->email)
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(\Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
        }

        //end save vatData
        $shippingAddress->setCollectShippingRates(true)->collectShippingRates()
            ->setShippingMethod($shippingMethod)->setPaymentMethod($paymentMethod);
        
        $quote->getPayment()->importData(array('method' => $paymentMethod));
        
        if ($couponCode) {
            $this->addCouponCode($sessionId, $couponCode, 1);
        }
        if ($vipCode) {
            $this->addVipCode($sessionId, $couponCode, 1);
        }
        if ($giftwrap == 1) {
            $session->setData('onestepcheckout_giftwrap', 1);
        }
        if ($pickupLocation) {
            $quote->setPickupLocation($pickupLocation);
            \Mage::log("*** PickupLocation in quote: " . $quote->getPickupLocation(). ", customer email:" . $billing->email, null, "restapi.log");
            
        }
        if (
                $paymentMethod != "cashondelivery" ||                
                // 'freeshipping_freeshipping' use for pickupLocation // not apply COD
                $shippingMethod == "freeshipping_freeshipping" ||
                // if grand total = 0 => free cod
                $shippingAddress->getGrandTotal() == 0
        ) {
            $codAmount = $quote->getCodfee();
            $shippingAddress->setCodfee(0);
            $quote->setCodfee(0);
        } else {
            $payableAmount = $shippingAddress->getBaseSubtotalInclTax() + $shippingAddress->getBaseShippingInclTax();
            if (\Mage::helper('core')->isModuleEnabled('Fahasa_Codfee')){
                $codAmount = \Mage::helper('codfee')->calculateCodFee($shippingAddress, $payableAmount);
            }
            else{
                $codAmount = 0;
            }
            if ($codAmount > 0.00001) {
                $shippingAddress->setCodfee($codAmount);
                $quote->setCodfee($codAmount);
                $shippingAddress->setGrandTotal($shippingAddress->getGrandTotal() + $codAmount);
                $shippingAddress->setBaseGrandTotal($shippingAddress->getBaseGrandTotal() + $codAmount);
            }
        }
        if ($quote->getAllVisibleItems() == null) {
	    $result['success'] = false;
	    $result['message'] = "CART_IS_EMPTY";
            return $result;
        } else {
            $quote->setMobileChannel(1);
            $quote->collectTotals()->save();
        }
        
         /*
         * FlashSale Check
         */
        $flashsale_check = \Mage::helper("flashsale/data")->checkFlashsaleRules($quote);
        if(!$flashsale_check['result']){
	    $result['success'] = false;
	    $result['message'] = "TOTAL_CART_CHANGE";
            return $result;
        }

        /*
         *  Compare quote Grand Total, between old and stored values
         *  This check is for flash sale and coupons. If a customer add products to cart, and flashsale/coupons
         *  are expired, then grand total changes.
         */
        /// At this stage, $quote has a new grand total
        $storeTotalTemp = \Mage::getSingleton('checkout/type_onepage')->getQuote()->getTotals();
        $storeGrandTotalCheck = array_filter($storeTotalTemp, function($item){
            return ($item['code'] == 'grand_total');
        });
        $stored_grand_total = null;
        if (sizeof($storeGrandTotalCheck) > 0){
            $storeGrandTotalItem = $storeGrandTotalCheck['grand_total'];
            $stored_grand_total = $storeGrandTotalItem['value'];
        }
        if((int)$old_grand_total != (int)$stored_grand_total){
	    $result['success'] = false;
	    $result['message'] = "TOTAL_CART_CHANGE";
            return $result;
        }

        try {
            $service = \Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();
            $increment_id = $service->getOrder()->getRealOrderId();
            if ($pickupLocation) {
                \Mage::log("*** PickupLocation for order Id: " . $increment_id . ", bookstore id:" . 
                        $pickupLocation . " - customer email: " . $service->getOrder()->getCustomerEmail(), null, "restapi.log");

            }
            $orderGrandTotal = $quote->getTotals()['grand_total']->getValue();
            //start save vatData
            $vatData = (object) [
                        "fm_checkout_note" => $fmFields->checkoutNote,
                        "fm_vat_company" => $fmFields->vatCompany,
                        "fm_vat_address" => $fmFields->vatAddress,
                        "fm_vat_taxcode" => $fmFields->vatTaxcode,
                        "fm_vat_Name" => $fmFields->vatName,
                        "fm_vat_Email" => $fmFields->vatEmail
            ];
            \Mage::getSingleton('core/session')->setRegistry('');
            $fieldsmn = \Mage::getModel('fieldsmanager/fieldsmanager');
            if (isset($vatData)) {
                foreach ($vatData as $key => $value) {
                    if (substr($key, 0, 3) == 'fm_') {
                        $fieldsmn->SaveFieldsdata(substr($key, 3), $value);
                    }
                }
            }

            $fieldsmn->SaveToFM('core', $service->getOrder()->getEntityId(), 'orders', 0);
            \Mage::getSingleton('core/session')->unsRegistry();
            
            $order = $service->getOrder();
            \Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                    array('order' => $order, 'quote' => $quote));
            $fhsCoin = \Mage::getSingleton('core/session')->getFhsCoin();
            \Mage::log("createOrder order #" . $increment_id . ", coin_code = " . $fhsCoin['code'], null, "fhs_coin.log");
            if ($fhsCoin['code']) { // check user use coinCode
                if ($quote->getShippingAddress()->getShippingMethod() == "freeshipping_freeshipping") {
                    // Coin code can not apply with pickup location
                    $order->setIsCoin(0);
                    $order->setCoinCode(0);
                    $order->setAmountCoin(0);
                    \Mage::log("createOrder order #" . $increment_id . ", coin_code = " . $fhsCoin['code'] ." remove coin. Because coin code no apply with pickup location", null, "fhs_coin.log");
                } else {
                    $order->setIsCoin(1);
                    $order->setCoinCode($fhsCoin['code']);
                }
            }
            try {
                if($localeStoreId != null){
                    $order->setStoreId($localeStoreId);
                }
                \Mage::helper("weblog")->SaveShippingMethod($shippingMethod, true);
                
                \Fahasa_Weblog_Helper_Data::$isMobile = true;
                $order->sendNewOrderEmail();
                \Fahasa_Weblog_Helper_Data::$isMobile = false;
                
                \Mage::helper("weblog")->FinishCheckout($quote, true);
            } catch (Exception $exx) {
                $exx->getMessage();
            }
        } catch (Exception $e) {
            $e->getMessage();
        } catch (Mage_Core_Exception $ex) {
            echo $ex->getMessage();
        }
        $session->setLastRealOrderId($increment_id);
        $quote = $customer = $service = null;
        \Mage::getSingleton('checkout/cart')->truncate()->save();
        $data['orderId'] = $increment_id;
        $data['orderTotal'] = $orderGrandTotal;
        $data['amount'] = round($order->getGrandTotal());
        $data['products'] = array();

//        loop products 
        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $isFreeProduct = null;
            if($item->getIsFreeProduct()){
                $price = 0;
                $isFreeProduct = 1;
            }  else {
                $price = $item->getBaseOriginalPrice();
                $isFreeProduct = 0;
            }
            $original_price = null;
            if (\Mage::helper('discountlabel')->getBundlePrice($product)) {
                $original_price = $product->getData('price');
            } else {
                $original_price = $product->getPrice();
            }
            
            $obj = (object) [
                        'productId' => (int) $product->getEntityId(),
                        'sku' => $product->getSku(),
                        'quantity' => (int) $item->getQtyOrdered(),
                        'name' => $item->getProduct()->getName(),
                        'price' => $price,
                        'image' => $this->getFullImageURL($product->getSmallImage()),
                        'quoteItemId' => $item->getId(),
                        'isFreeProduct' => $isFreeProduct,
                        'original_price' => $original_price,
                        'category_main' => $product->category_main,
                        'category_mid' => $product->category_mid
            ];

            if ($product->type_id == 'bundle') {
                $options = $item->getChildrenItems();
//                loop bundle selection
                $bundleOptions = array();
                foreach ($options as $option) {
                    $selections = unserialize($option->getProductOptions()["bundle_selection_attributes"]);
                    $optionId = $selections['option_id'];
                    $optionItem = array(
                        'productId' => (int) $option->getProductId(),
                        'sku' => $product->getSku(),
                        'quantity' => (int) $selections["qty"],
                        'name' => $option->getName(),
                        'price' => $option->getBaseOriginalPrice()
                    );
//                    Check for duplicate items before pushing
                    if (!in_array($optionItem, $bundleOptions[$optionId], true)) {
                        $bundleOptions[$optionId][] = $optionItem;
                    }
                }
//                format arr json 
                $bundleOptionArr = array();
                foreach ($bundleOptions as $k => $v) {
                    if ($k == $bundleOptionArr->optionId) {
                        array_push($bundleOptionArr[$k], $v);
                    } else {
                        $bundleOptionArr[] = array(
                            "optionId" => $k,
                            "selectedProducts" => $v
                        );
                    }
                }
                $obj->options = $bundleOptionArr;
            }
            array_push($data['products'], $obj);
        }
        $data['paymentMethod'] = $paymentMethod;
        if ($paymentMethod == "pg123paymaster" || $paymentMethod == "pg123pay") {
            $data['websocket'] = $this->post123pay($increment_id, $paymentMethod);
            //$this->openWebSock($data['websocket']['transactionId'], $increment_id);
        } elseif ($paymentMethod == "webmoney") {
            $data['websocket'] = $this->postWebmoney($increment_id, $paymentMethod);
            //$this->openWebSock($data['websocket']['transactionId'], $increment_id);
        } elseif ($paymentMethod == "zalopay" || $paymentMethod == "zalopayatm" || $paymentMethod == "zalopaycc" || $paymentMethod == "zalopayapp") {
            $data['websocket'] = $this->postZalopay($increment_id, $paymentMethod);
        }elseif ($paymentMethod == "momopay"){
            $data["requestId"] = \TTS_Momopay_Model_Momopay::createMomoOrder($order);
        }
        
        $donateFpoint = round($order->getGrandTotal()) * 0.01;
        $shipping_address = $order->getShippingAddress();
        $shipping_order = array(
            "firstName" => $shipping_address->getFirstname(),
            "lastName" => $shipping_address->getLastname(),
            "country_id" => $shipping_address->getCountryId(),
            "region" => $shipping_address->getRegion(),
            "city" => $shipping_address->getCity(),
            "ward" => $shipping_address->getWard(),
            "street" => $shipping_address->getStreet()[0],
            "telephone" => $shipping_address->getTelephone(),
        );
//        return json_encode($data);
	
	$result['success'] = true;
	$result['orderId'] = $data['orderId'];
	$result['orderTotal'] = $data['orderTotal'];
	$result['paymentMethod'] = $data['paymentMethod'];
	$result['products'] = $data['products'];
	$result['websocket'] = $data['websocket'];
	$result['amount'] = $data['amount']; //amount = grand_total of order
	$result['requestId'] = $data['requestId']; // use for momopay
	$result['donateFpoint'] = $donateFpoint;
	$result['shipping'] = $shipping_order;
	
        \Mage::log("*** REST post123pay response: " . print_r($result, true), null, "123pay.log");
        // clear core session fhs_coin when buy done
        $_SESSION['core']['fhs_coin'] = null;
        }catch(\Exception $ex2){
	    $result['success'] = false;
	    $result['message'] = $ex2->getMessage();
        }
        return $result;
    }
    
    public function validateTaxcode($taxCode){
	if(empty($taxCode)) {
            return false;
        }
        
        try {
            $taxCodeSplit = explode("-",$taxCode);
            if (sizeof($taxCodeSplit) > 2) {
                return false;
            }
            if (sizeof($taxCodeSplit) == 2) {
                $branchId = intval($taxCodeSplit[1]);
                if ($branchId < 1 || $branchId > 999) {
                    return false;
                }
            }
            $taxId = $taxCodeSplit[0];
            if (strlen($taxId) != 10) {
                return false;
            }
            $nArr = array();
            for ($i = 0; $i < 10; $i++) {
                $nArr[$i] = intval(substr($taxId, $i, 1));
            }
            // MOD(10-(S1*31+ S2*29 + S3*23 + S4*19 + S5*17 + S6*13 + S7*7 + S8*5 + S9*3),11) = S10
            $t = $nArr[0] * 31
                    + $nArr[1] * 29
                    + $nArr[2] * 23
                    + $nArr[3] * 19
                    + $nArr[4] * 17
                    + $nArr[5] * 13
                    + $nArr[6] * 7
                    + $nArr[7] * 5
                    + $nArr[8] * 3;
            $t = 10 - $t;
            $t = $this->mod($t, 11);
            if ($t != $nArr[9]) {
                return false;
            }
            return true;
        } catch (Exception $ex) {}
        return false;
    }
    public function mod($n, $m){
	$remain = $n % $m;
	return floor($remain >= 0 ? $remain : $remain + $m);
    }
    
    
    public function saveMessageInQuoteItemForFailOrder($observer)
    {
        if (Mage::getStoreConfig('vietnamshipping/general/enable_urban_delivery'))
        {
            $quote = $observer->getQuote();
            $items = $this->getItemsHasSpecificDelivery($quote->getAllVisibleItems());

            if (count($items) > 0)
            {
                $error_items = array();
                foreach ($items as $error_item)
                {
                    $error_items[$error_item['product_id']] = 'support_province';
                }
                $this->addErrorMessageInItem($quote, $error_items);
            }
        }
    }

    public function addErrorMessageInItem($quote, $error_items){
        if ($error_items)
        {
            $urban_message = Mage::getStoreConfig('vietnamshipping/general/urban_delivery_message');
            foreach ($quote->getAllVisibleItems() as $quoteItem)
            {
                if ($error_items[$quoteItem->getProductId()])
                {
                    $message = $quoteItem->getMessage();
                    $messages = explode("\n", $message);
                    
                    $error_item = $urban_message;
                    if (!in_array($error_item, $messages))
                    {
                        $quoteItem->setHasError(true)->addMessage( $error_item);
                    } 
                }
            }
        }
    }
    
    public function getItemsHasSpecificDelivery($quoteItems)
    {
        $result = array();
        if (Mage::getStoreConfig('vietnamshipping/general/enable_urban_delivery'))
        {
            $product_ids = array();
            foreach ($quoteItems as $quoteItem)
            {
                $product_ids[] = $quoteItem->getProductId();
            }

            if (count($product_ids) > 0)
            {
                $product_ids_str = implode(",", $product_ids);
                $query = "select fd.product_id from fhs_product_delivery fd where fd.product_id in ($product_ids_str) group by fd.product_id ";

                $read = Mage::getSingleton("core/resource")->getConnection("core_read");

                $result = $read->fetchAll($query);
            }
        }
        return $result;
    }

    public function getNumItemsHasSpecificDelivery($quoteItems){
        $items = $this->getItemsHasSpecificDelivery($quoteItems);
        return count($items);
    }

    public function getHashSessionKeyAsciiArr()
    {
        $hashKey = $this->getHashSessionKey();
        $result = array();
        for ($i = 0; $i < strlen($hashKey); $i++)
        {
            $result[] = ord(strtolower($hashKey[$i])) + pow(2, 15);
        }

        return $result;
    }

    public function getHashSessionKey()
    {
        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $key = "Fhs#*&";
        $hashKey = md5($sessionId . $key);
        return $hashKey;
    }
    
    
    public function DayOfTimeVN(){
        return array(
            "Mon" => "Thứ Hai",
            "Tue" => "Thứ Ba",
            "Wed" => "Thứ Tư",
            "Thu" => "Thứ Năm",
            "Fri" => "Thứ Sáu",
            "Sat" => "Thứ Bảy",
            "Sun" => "Chủ Nhật",
        );
    }
}