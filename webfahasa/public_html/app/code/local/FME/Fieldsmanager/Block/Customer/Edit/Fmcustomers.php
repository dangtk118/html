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
 \\* @copyright  Copyright 2010 � free-magentoextensions.com All right reserved\\\
 /////////////////////////////////////////////////////////////////////////////////
 */

 
class FME_Fieldsmanager_Block_Customer_Edit_Fmcustomers extends Mage_Adminhtml_Block_Widget_Form {
 
    public function __construct()
    {
        parent::__construct();
    }
    public function getfieldshtml($locate)
    {
	Mage::getSingleton('adminhtml/session')->setIsAdmin(true);
	 if(!Mage::helper('fieldsmanager')->getStoredDatafor('enable')){
	   return;
	}
	$customer = Mage::registry('current_customer');
	if($customer->getId()){
		Mage::getSingleton('adminhtml/session')->setIsNew(false);
		 $collection=Mage::getModel('fieldsmanager/fieldsmanager')->getAllFieldsHtml('2', $locate , 'fme_account', 'catalog' , '<li id="fme_customer_'.$locate.'" class="fields">' , '</li>');
	}else{
		Mage::getSingleton('adminhtml/session')->setIsNew(true);
		 $collection=Mage::getModel('fieldsmanager/fieldsmanager')->getAllFieldsHtml('2', $locate , 'fme_register', 'catalog' , '<li id="fme_customer_'.$locate.'" class="fields">' , '</li>');
	}
	
       
      return $collection;
       
    }

    /**
     * Return predefined additional element types
     *
     * @return array
     */
    protected function _getAdditionalElementTypes()
    {
        return array(
            'file'      => Mage::getConfig()->getBlockClassName('adminhtml/customer_form_element_file'),
            'image'     => Mage::getConfig()->getBlockClassName('adminhtml/customer_form_element_image'),
            'boolean'   => Mage::getConfig()->getBlockClassName('adminhtml/customer_form_element_boolean'),
        );
    }
	
}

?>
