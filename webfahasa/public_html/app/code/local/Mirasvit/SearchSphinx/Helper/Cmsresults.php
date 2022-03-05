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
class Mirasvit_SearchSphinx_Helper_Cmsresults extends Mage_Core_Helper_Abstract
{
    public function queryFhsCmsKeyword($keySearch){
        $cmsKeys = Mage::helper('searchindex')->getSearchEngine()->queryFhsCms($keySearch);
        $csvCmsId = "";
        $cnt = 0;
        $results = array();
        if($cmsKeys['matches'] != null){
            forEach($cmsKeys['matches'] as $pageId => $cms){
                $weight = $cms['weight'];
                $pageType = $cms['attrs']['type'];
		if($weight >= 1670){
                    if($cnt == 0){
                    	$csvCmsId .= (string)$pageId;
                    }else{
                    	$csvCmsId .= "," . (string)$pageId;
                    }
                    $cnt++;
                    if($cnt > 10){
                        break;
                    }
		}
            }
	    if($csvCmsId != ""){
               $resource = Mage::getSingleton('core/resource');
               $readConnection = $resource->getConnection('core_read');
               $query = "SELECT * FROM fhs_page_keyword_url WHERE id in ( $csvCmsId ) group by pageUrl order by field (id, $csvCmsId ) limit 10";
               $results = $readConnection->fetchAll($query);
	    }
        }
        return $results;
    }
}
