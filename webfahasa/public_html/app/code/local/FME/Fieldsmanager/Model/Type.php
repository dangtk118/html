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

 
class FME_Fieldsmanager_Model_Type
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'text','label' => Mage::helper('fieldsmanager')->__('Text Field')),
            array('value' => 'textarea','label' => Mage::helper('fieldsmanager')->__('Text Area')),
            array('value' => 'date','label' => Mage::helper('fieldsmanager')->__('Date')),
            array('value' => 'boolean','label' => Mage::helper('fieldsmanager')->__('Yes/No')),
            array('value' => 'multiselect','label' => Mage::helper('fieldsmanager')->__('Multiple Select')),
            array('value' => 'select','label' => Mage::helper('fieldsmanager')->__('Dropdown')),
            array('value' => 'checkbox','label' => Mage::helper('fieldsmanager')->__('Checkbox')),
            array('value' => 'radio','label' => Mage::helper('fieldsmanager')->__('Radiobutton')),
            array('value' => 'message','label' => Mage::helper('fieldsmanager')->__('Message Display Only'))
        );
    }
    public function toOptionsArray()
    {
        return array(
                    'text' => Mage::helper('fieldsmanager')->__('Text Field'),
                    'textarea' => Mage::helper('fieldsmanager')->__('Text Area'),
                    'date'=> Mage::helper('fieldsmanager')->__('Date'),
                    'boolean'=> Mage::helper('fieldsmanager')->__('Yes/No'),
                    'multiselect'=> Mage::helper('fieldsmanager')->__('Multiple Select'),
                    'select'=> Mage::helper('fieldsmanager')->__('Dropdown'),
                    'checkbox'=> Mage::helper('fieldsmanager')->__('Checkbox'),
                    'radio'=> Mage::helper('fieldsmanager')->__('Radiobutton'),
                    'message'=> Mage::helper('fieldsmanager')->__('Message Display Only')
        );
    }
   
    public function toValidateArray()
    {
        return array(
                array('value' => '','label' => Mage::helper('fieldsmanager')->__('None')),
                array('value' => 'validate-number','label' => Mage::helper('fieldsmanager')->__('Decimal Number')),
                array('value' => 'validate-digits','label' => Mage::helper('fieldsmanager')->__('Integer Number')),
                array('value' => 'validate-email','label' => Mage::helper('fieldsmanager')->__('Email Address')),
                array('value' => 'validate-url','label' => Mage::helper('fieldsmanager')->__('Website Url Address')),
                array('value' => 'validate-alpha','label' => Mage::helper('fieldsmanager')->__('Letters Only')),
                array('value' => 'validate-alphanum','label' => Mage::helper('fieldsmanager')->__('Letters and/or Numbers')),
                array('value' => 'validate-date','label' => Mage::helper('fieldsmanager')->__('Date'))
            );
    }
    
    public function toPositionArray()
    {
        return array(
                array('value' => 1,'label' => Mage::helper('fieldsmanager')->__('At the Top Of the Step')),
                array('value' => 2,'label' => Mage::helper('fieldsmanager')->__('At the Middle of Step')),
                array('value' => 3,'label' => Mage::helper('fieldsmanager')->__('At the Bottom of Step'))
            );
    }
     
    public function toPositionOptionsArray()
    {
        return array(
               1 => Mage::helper('fieldsmanager')->__('At the Top Of the Step'),
               2 => Mage::helper('fieldsmanager')->__('At the Middle of Step'),
               3 => Mage::helper('fieldsmanager')->__('At the Bottom of Step')
            );
    }
    
     public function toPlacementArray()
    {
        return array
                (
                    array('value' => 2,'label' => Mage::helper('fieldsmanager')->__(' Billing')),
                    array('value' => 3,'label' => Mage::helper('fieldsmanager')->__(' Shipping')),
                    array('value' => 4,'label' => Mage::helper('fieldsmanager')->__(' Shipping Method')),
                    array('value' => 5,'label' => Mage::helper('fieldsmanager')->__(' Payment')),
                    array('value' => 6,'label' => Mage::helper('fieldsmanager')->__(' Order Review')),
                );
    }
    
    public function toPlacementOptionsArray()
    {
        return array
                (
                    2=> Mage::helper('fieldsmanager')->__(' Billing'),
                    3=> Mage::helper('fieldsmanager')->__(' Shipping'),
                    4=> Mage::helper('fieldsmanager')->__(' Shipping Method'),
                    5=> Mage::helper('fieldsmanager')->__(' Payment'),
                    6=> Mage::helper('fieldsmanager')->__(' Order Review'),
                );
    }
    public function toCustomerArray()
    {
        return array
                (
                    array('value' => 0,'label' => Mage::helper('fieldsmanager')->__(' No ')),
                    array('value' => 1,'label' => Mage::helper('fieldsmanager')->__(' Account Page')),
                    array('value' => 2,'label' => Mage::helper('fieldsmanager')->__(' Registeration Page and Account Page'))
                );
    }
    
    public function toCustomerOptionsArray()
    {
        return array
                (
                    0=> Mage::helper('fieldsmanager')->__(' No '),
                    1=> Mage::helper('fieldsmanager')->__(' Account Page'),
                    2=> Mage::helper('fieldsmanager')->__(' Registeration Page and Account Page')
                );
    }
    
    public function toPdfArray()
    {
        return array
                (
                    array('value' => 0,'label' => Mage::helper('fieldsmanager')->__(' No ')),
                    array('value' => 1,'label' => Mage::helper('fieldsmanager')->__(' Invoice Pdf')),
                    array('value' => 2,'label' => Mage::helper('fieldsmanager')->__(' Shipping Pdf')),
                    array('value' => 3,'label' => Mage::helper('fieldsmanager')->__(' Both Invoice and Shipping Pdf'))
                );
    }
    
    public function toPdfOptionsArray()
    {
        return array
                (
                    0=> Mage::helper('fieldsmanager')->__(' No '),
                    1=> Mage::helper('fieldsmanager')->__(' Invoice Pdf'),
                    2=> Mage::helper('fieldsmanager')->__(' Shipping Pdf'),
                    3=> Mage::helper('fieldsmanager')->__(' Both Invoice and Shipping Pdf')
                );
    }
}  