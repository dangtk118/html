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

class FME_Fieldsmanager_Model_Customer extends Mage_Customer_Model_Customer
{
     /**
     * Processing object before save data
     *
     * @return Mage_Customer_Model_Customer
     */
    protected function _beforeSave()
    {	if(Mage::helper('fieldsmanager')->getStoredDatafor('enable')){
	    Mage::getSingleton('customer/session')->setRegistry('');
	    $data=array();$fmedata=array();
	    if(isset($_POST['fme_register'])){
		$fmedata=$_POST['fme_register'];
	    }elseif(isset($_POST['fme_account'])){
		$fmedata=$_POST['fme_account'];
	    }
	    foreach($fmedata as $key=>$value){
	      if(substr($key,0,3)=='fm_'){
		$data[substr($key,3)]=$value;
		}
	    }
	    if(is_array($data) and count($data)!=0){
		Mage::getSingleton('customer/session')->setRegistry($data);
	    }
	}
        return  parent::_beforeSave();
    }


} 