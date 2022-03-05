<?php
/** 
 * @category    Mana
 * @package     Mana_Seo
 * @copyright   Copyright (c) http://www.manadev.com
 * @license     http://www.manadev.com/license  Proprietary License
 */
/**
 * @author Mana Team
 *
 */
class Mana_Seo_Helper_PageType_CmsPage extends Mana_Seo_Helper_PageType  {
    public function getSuffixHistoryType() {
        return Mana_Seo_Model_UrlHistory::TYPE_CMS_PAGE_SUFFIX;
    }

    /**
     * @param Mana_Seo_Model_ParsedUrl $token
     * @return bool
     */
    public function setPage($token) {
        parent::setPage($token);
        $token
            ->addParameter('page_id', $token->getPageUrl()->getCmsPageId());

        return true;
    }

    protected $_urlKeys = array();

    /**
     * @param Mana_Seo_Rewrite_Url $urlModel
     * @return string | bool
     */
    public function getUrlKey($urlModel) {
        /* @var $seo Mana_Seo_Helper_Data */
        $seo = Mage::helper('mana_seo');

        /* @var $logger Mana_Core_Helper_Logger */
        $logger = Mage::helper('mana_core/logger');

        if (($cmsPageId = $urlModel->getSeoRouteParam('page_id')) === false) {
            $logger->logSeoUrl(sprintf('WARNING: while resolving %s, %s route parameter is required', 'CMS page URL key', 'id'));
        }
        if (!isset($this->_urlKeys[$cmsPageId])) {
	    //---------------
	    //@author: lamhung
	    //--------
	    //@note: cache
	    //---------------
	    $cache_key = 'final_url_key'.$cmsPageId;
	    $cache_helper = Mage::helper('fahasa_catalog/cache');
	    $cache_data = $cache_helper->getData($cache_key);
	    if (empty($cache_data)) {
		$urlCollection = $seo->getUrlCollection($urlModel->getSchema(), Mana_Seo_Resource_Url_Collection::TYPE_PAGE);
		$urlCollection->addFieldToFilter('cms_page_id', $cmsPageId);
		$result = $urlModel->getUrlKey($urlCollection);

		$cache_data = array(
		    'data' => $result,
		    'cached' => true
		);
		$cache_helper->setData($cache_key, $cache_data);
	    } else {
		$result = $cache_data['data'];
	    }
            if (!$result) {
		$logger->logSeoUrl(sprintf('WARNING: %s not found by  %s %s', 'CMS page URL key', 'id', $cmsPageId));
	    }
            $this->_urlKeys[$cmsPageId] = $result;
        }

        return $this->_urlKeys[$cmsPageId]['final_url_key'];
    }
}