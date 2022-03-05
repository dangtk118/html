<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Sphinx Search Ultimate
 * @version   2.3.2
 * @revision  886
 * @copyright Copyright (C) 2014 Mirasvit (http://mirasvit.com/)
 */


/**
 * @category Mirasvit
 * @package  Mirasvit_SearchSphinx
 */
class Mage_Customer_Adminhtml_System_ActionController extends Mage_Adminhtml_Controller_Action
{
    public function runregisterqueueAction()
    {
        $result = array();
        try {
	    Mage::app()->setCurrentStore(1);
	    Mage::app('default');
	    
            $result_data = Mage::helper("fahasa_customer/register")->startRegisterOrderQueue();
	    if($result_data['success']){
		$result['message'] = 'run successfully';
	    }else{
		$result['message'] = $result_data['message'];
	    }
        } catch(Exception $e) {
            $result['message'] = nl2br($e->getMessage());
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}