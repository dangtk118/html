<?php

class Fahasa_Cmsjson_Block_Block extends Mage_Core_Block_Template {

    public function handleURLLink($url) {
        if (substr($url, 0, 1) == "#"){
            $link = $url;
        }else if (strpos($url, "://") != FALSE) {
            // link other
            $link = $url;
            if (strpos($url, "fahasa.com") != FALSE) {
                // link fahasa full
                $patterns = "/.*ahasa.com\//i";
                $link = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . preg_replace($patterns, "", $url);
            }
        } else {
            // link fahasa rut gon
            $link = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $url;
        }
        return $link;
    }

    public function getDescriptionByPageUrl($pageUrl, $descType) {
        $result = array();
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sqlQuery = "select image_url, desc_title, description
                        from fhs_page_keyword_url
                     where pageUrl = :pageUrl
                     limit 1;";
        $binds = array(
            'pageUrl' => $pageUrl
        );
        $readresult = $read->fetchRow($sqlQuery, $binds);

        $result['image_url'] = $readresult['image_url'];
        $result['desc_title'] = $readresult['desc_title'];
        $result['description'] = $readresult['description'];

        return $result;
    }

}
