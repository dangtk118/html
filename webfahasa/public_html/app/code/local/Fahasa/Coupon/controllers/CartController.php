<?php
//require_once 'Mage/Checkout/controllers/CartController.php';
require_once 'Mage/Checkout/controllers/CartController.php';

class Fahasa_Coupon_CartController extends Mage_Checkout_CartController
{
   public function preDispatch()
    {
        parent::preDispatch();
//        $cart = $this->_getCart();
//        if ($cart->getQuote()->getIsMultiShipping()) {
//            $cart->getQuote()->setIsMultiShipping(false);
//        }
//        Mage::dispatchEvent(
//                'controller_action_predispatch_' . $this->getFullActionName(), array('controller_action' => $this)
//        );

        //return the original result in case another method is relying on it
        return $this;
//        Mage_Core_Controller_Front_Action::preDispatch();
    }
    
        public function indexAction()
    {
       
        $this->_getSession()->setCartWasUpdated(true);

        Varien_Profiler::start(__METHOD__ . 'cart_display');
        $this
                ->loadLayout()
                ->_initLayoutMessages('checkout/session')
                ->_initLayoutMessages('catalog/session')
                ->getLayout()->getBlock('head')->setTitle($this->__('Shopping Cart'));
        $this->renderLayout();
        Varien_Profiler::stop(__METHOD__ . 'cart_display');
    }

    
    public function couponPostAction()
    {
        /**
        * No reason continue with empty shopping cart
        */
        if (!$this->_getCart()->getQuote()->getItemsCount()) {
            $this->_goBack();
            return;
        }
        $couponCode = (string) $this->getRequest()->getParam('coupon_code');
        $couponCode = trim($couponCode);
        /* Custom Code */
        $coll = Mage::getResourceModel('salesrule/coupon_collection')->addFieldToFilter('code', array('in'=>$couponCode));
        foreach($coll as $_coll){
            $code = $_coll['code'];
            $enddate = $_coll['expiration_date'];
        }
        $currentdate = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
        /* End custom code */
        if ($this->getRequest()->getParam('remove') == 1) {
            $couponCode = '';
            $this->_getQuote()->setCouponCode($couponCode)
            ->collectTotals()
            ->save();
            $this->_goBack();
            return;
        }
        $oldCouponCode = $this->_getQuote()->getCouponCode();
        if (!strlen($couponCode) && !strlen($oldCouponCode)) {
            $this->_goBack();
            return;
        }        
        try {
            $codeLength = strlen($couponCode);
//            $isCodeLengthValid = $this->_getQuote()->getShippingAddress()->setCollectShippingRates(true);
            $isCodeLengthValid = $codeLength && $codeLength <= Mage_Checkout_Helper_Cart::COUPON_CODE_MAX_LENGTH;
            $this->_getQuote()->setCouponCode($isCodeLengthValid ? $couponCode : '')
            ->collectTotals()
            ->save();
            $coupon_info = Mage::getModel('salesrule/coupon')->load($couponCode, 'code');
            $timeslimit = $coupon_info->getUsageLimit();
            $timesuse = $coupon_info->getTimesUsed();
            if ($coupon_info->getCode()) { // check code
                //Success - code inside Quote
                if ($isCodeLengthValid && $couponCode == $this->_getQuote()->getCouponCode()) {
                    $this->_getSession()->addSuccess(
                    $this->__('Coupon code "%s" was applied.', Mage::helper('core')->escapeHtml($couponCode))
                    );
                } else if($timesuse >= $timeslimit){
                    $this->_getSession()->addError(
                    $this->__('Coupon code "%s" is used.', Mage::helper('core')->escapeHtml($couponCode))
                    );
                } else if($currentdate > $enddate && strtoupper($couponCode) == strtoupper($code)){
                    $this->_getSession()->addError(
                    $this->__('Coupon code "%s" has Expired.', Mage::helper('core')->escapeHtml($couponCode))
                    );
                } else{
                    $this->_getSession()->addError(
                    $this->__('Coupon code "%s" exist but do not apply the conditions. Please see <a target="_blank" href="http://www.fahasa.com/dieu-kien-dung-ma-giam-gia">http://www.fahasa.com/dieu-kien-dung-ma-giam-gia</a>', Mage::helper('core')->escapeHtml($couponCode))
                    );
                }
                
            } else {
                $this->_getSession()->addError(
                    $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->escapeHtml($couponCode))
                );
            }
       
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Cannot apply the coupon code.'));
            Mage::logException($e);
        }
        $this->_goBack();
    }
}
