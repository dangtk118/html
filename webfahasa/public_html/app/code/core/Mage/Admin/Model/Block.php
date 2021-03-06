<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Admin
 * @copyright   Copyright (c) 2015 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Class Mage_Admin_Model_Block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Admin_Model_Block extends Mage_Core_Model_Abstract
{
    /**
     * Initialize variable model
     */
    protected function _construct()
    {
        $this->_init('admin/block');
    }

    /**
     * @return array|bool
     * @throws Exception
     * @throws Zend_Validate_Exception
     */
    public function validate()
    {
        $errors = array();

        if (!Zend_Validate::is($this->getBlockName(), 'NotEmpty')) {
            $errors[] = Mage::helper('adminhtml')->__('Block Name is required field.');
        }
        $disallowedBlockNames = Mage::helper('admin')->getDisallowedBlockNames();
        if (in_array($this->getBlockName(), $disallowedBlockNames)) {
            $errors[] = Mage::helper('adminhtml')->__('Block Name is disallowed.');
        }
        if (!Zend_Validate::is($this->getBlockName(), 'Regex', array('/^[-_a-zA-Z0-9]+\/[-_a-zA-Z0-9\/]+$/'))) {
            $errors[] = Mage::helper('adminhtml')->__('Block Name is incorrect.');
        }

        if (!in_array($this->getIsAllowed(), array('0', '1'))) {
            $errors[] = Mage::helper('adminhtml')->__('Is Allowed is required field.');
        }

        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * Check is block with such type allowed for parsinf via blockDirective method
     *
     * @param $type
     * @return int
     */
    public function isTypeAllowed($type)
    {
	//---------------
	//@author: lamhung
	//--------
	//@note: cache
	//---------------
	$cache_key = 'permission_block_'.$type;
	$cache_helper = Mage::helper('fahasa_catalog/cache');
	$cache_data = $cache_helper->getData($cache_key);
	if(empty($cache_data)) {
	    /** @var Mage_Admin_Model_Resource_Block_Collection $collection */
	    $collection = Mage::getResourceModel('admin/block_collection');
	    $collection->addFieldToFilter('block_name', array('eq' => $type))
		->addFieldToFilter('is_allowed', array('eq' => 1));
	    $result = $collection->load()->count();
	    $cache_data = array(
		'data' => $result,
		'cached' => true
	    );
	    $cache_helper->setData($cache_key, $cache_data);
	} else {
	    $result = $cache_data['data'];
	}
	return $result;
    }
}
