<?php

/*////////////////////////////////////////////////////////////////////////////////
 \\\\\\\\\\\\\\\\\\\\\\\\\  FME Fieldsmanager extension  \\\\\\\\\\\\\\\\\\\\\\\\\
 /////////////////////////////////////////////////////////////////////////////////
 \\\\\\\\\\\\\\\\\\\\\\\\\ NOTICE OF LICENSE\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 ///////                                                                   ///////
 \\\\\\\ This source file is subject to the Open Software License (OSL 3.0)\\\\\\\
 ///////   that is bundled with this package in the file LICENSE.txt.      ///////
 \\\\\\\   It is also available through the world-wide-web at this URL:    \\\\\\\
 ///////          http://opensource.org/licenses/osl-3.0.php               ///////
 \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 ///////                      * @category   FME                            ///////
 \\\\\\\                      * @package    FME_Fieldsmanager              \\\\\\\
 ///////    * @author     Malik Tahir Mehmood <malik.tahir786@gmail.com>   ///////
 \\\\\\\                                                                   \\\\\\\
 /////////////////////////////////////////////////////////////////////////////////
 \\* @copyright  Copyright 2010 © free-magentoextensions.com All right reserved\\\
 /////////////////////////////////////////////////////////////////////////////////
 */



/**
 * One page checkout processing model
 */
class FME_Fieldsmanager_Model_Type_Onepage extends Mage_Checkout_Model_Type_Onepage
{
   

    /**
     * Save billing address information to quote
     * This method is called by One Page Checkout JS (AJAX) while saving the billing information.
     *
     * @param   array $data
     * @param   int $customerAddressId
     * @return  Mage_Checkout_Model_Type_Onepage
     */
    public function saveBilling($data, $customerAddressId)
    {
        if (empty($data)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }
        Mage::getSingleton('core/session')->setRegistry('');
        if(isset($_POST['fm_fields'])){
            foreach($_POST['fm_fields'] as $key=>$value){
                if(substr($key,0,3)=='fm_'){
                   Mage::getModel('fieldsmanager/fieldsmanager')->SaveFieldsdata(substr($key,3),$value);
                }
            }
        }
       return parent::saveBilling($data, $customerAddressId);
    }
    public function saveShipping($data, $customerAddressId)
    { 
        if (empty($data)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }
        if(isset($_POST['fm_fields'])){
            foreach($_POST['fm_fields'] as $key=>$value){
                if(substr($key,0,3)=='fm_'){
                   Mage::getModel('fieldsmanager/fieldsmanager')->SaveFieldsdata(substr($key,3),$value);
                }
            }
        }
        return parent::saveShipping($data, $customerAddressId);
    }
     public function saveShippingMethod($shippingMethod)
    {
        if (empty($shippingMethod)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid shipping method.'));
        }
        if(isset($_POST['fm_fields'])){
            foreach($_POST['fm_fields'] as $key=>$value){
                if(substr($key,0,3)=='fm_'){
                   Mage::getModel('fieldsmanager/fieldsmanager')->SaveFieldsdata(substr($key,3),$value);
                }
            }
        }
        return parent::saveShippingMethod($shippingMethod);
    }
     public function savePayment($data)
    {
        if (empty($data)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }
        if(isset($_POST['fm_fields'])){
            foreach($_POST['fm_fields'] as $key=>$value){
                if(substr($key,0,3)=='fm_'){
                   Mage::getModel('fieldsmanager/fieldsmanager')->SaveFieldsdata(substr($key,3),$value);
                }
            }
        }
        return parent::savePayment($data);
    }
    public function saveOrder()
    {
        if(isset($_POST['fm_fields'])){
            foreach($_POST['fm_fields'] as $key=>$value){
                if(substr($key,0,3)=='fm_'){
                   Mage::getModel('fieldsmanager/fieldsmanager')->SaveFieldsdata(substr($key,3),$value);
                }
            }
        }
       // Mage::getModel('fieldsmanager/fieldsmanager')->SaveToFM();
        return parent::saveOrder();
    }

}
