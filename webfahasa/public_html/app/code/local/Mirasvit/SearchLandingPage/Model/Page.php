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


class Mirasvit_SearchLandingPage_Model_Page extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('searchlandingpage/page');
    }

    public function checkIdentifier($identifier)
    {
	//---------------
	//@author: lamhung
	//--------
	//@note: cache
	//---------------
	$cache_key = 'searchlandingpage/page_'.$identifier;
	$core_helper = Mage::helper('core');
	$cache_helper = Mage::helper('fahasa_catalog/cache');
	$cache_data = $cache_helper->getData($cache_key);
	if(empty($cache_data)) {
	    $page = $this->getCollection()
		->addFieldToFilter('url_key', $identifier)
		->addFieldToFilter('is_active', 1)
		->getFirstItem();


	    $cache_data = array(
		'data' =>  $core_helper->jsonEncode($page),
		'model' => 'Mirasvit_SearchLandingPage_Model_Page',
		'cached' => true
	    );
	    $cache_helper->setData($cache_key, $cache_data);
	} else {
	    $page = $cache_helper->getCacheResult($cache_data);
	}
	
       	if ($page->getId()) {
            return $page;
       	}

        return false;
    }
}