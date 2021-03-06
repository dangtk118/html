<?php
/******************************************************
 * @package Ves Megamenu module for Magento 1.4.x.x and Magento 1.7.x.x
 * @version 1.0.0.1
 * @author http://landofcoder.com
 * @copyright	Copyright (C) December 2010 LandOfCoder.com <@emai:landofcoder@gmail.com>.All rights reserved.
 * @license		GNU General Public License version 2
*******************************************************/
class Ves_Verticalmenu_Helper_Media extends Mage_Core_Helper_Abstract {

    /**
     * 
     * Add media file ( js, css ) ...
     * @param $type string media type (js, skin_css)
     * @param $source string source path
     * @param $before boolean true/false
     * @param $params mix 
     * @param $if string
     * @param $cond string
     */
    function addMediaFile($type = "", $source = "", $before = false, $params = null, $if = "", $cond = "") {
        $_head = Mage::getSingleton('core/layout')->getBlock('head');
        if (is_object($_head) && !empty($source)) {
            $items = $_head->getData('items');
            $tmpItems = array();
            $search = $type . "/" . $source;
            if (is_array($items)) {
                $key_array = array_keys($items);
                foreach ($key_array as &$_key) {
                    if ($search == $_key) {
                        $tmpItems[$_key] = $items[$_key];
                        unset($items[$_key]);
                    }
                }
            }
            if ($type == 'skin_css' && empty($params)) {
                $params = 'media="all"';
            }
            if (empty($tmpItems)) {
                $tmpItems[$type . '/' . $source] = array(
                    'type' => $type,
                    'name' => $source,
                    'params' => $params,
                    'if' => $if,
                    'cond' => $cond,
                );
            }
            if ($before) {
                $items = array_merge($tmpItems, $items);
            } else {
                $items = array_merge($items, $tmpItems);
            }
            $_head->setData('items', $items);
        }
    }
    public function loadMedia(){
        $this->addMediaFile("js", "venustheme/ves_verticalmenu/jquery/jquerycookie.js", true);
        $this->addMediaFile("js", "venustheme/ves_verticalmenu/admin/verticalmenu/jquery.nestable.js", true);
        $this->addMediaFile("js", "venustheme/ves_verticalmenu/jquery/tabs.js", true);
        $this->addMediaFile("js", "venustheme/ves_verticalmenu/jquery/ui/jquery-ui-1.8.16.custom.min.js", true);
        $this->addMediaFile("js", "venustheme/ves_verticalmenu/jquery/conflict.js", true);
        $this->addMediaFile("js", "venustheme/ves_verticalmenu/jquery/jquery-1.7.1.min.js", true);
        $this->addMediaFile("skin_css", "ves_verticalmenu/ui/themes/ui-lightness/jquery-ui-1.8.16.custom.css");

        $this->addMediaFile("skin_css", "ves_verticalmenu/css/stylesheet.css");
        $this->addMediaFile("skin_css", "ves_verticalmenu/css/verticalmenu.css");
    }
    public function loadMediaLiveEdit(){
        $this->addMediaFile("js_css", "venustheme/ves_verticalmenu/admin/verticalmenu/css/bootstrap.css");
        $this->addMediaFile("skin_css", "ves_verticalmenu/css/verticalmenu_live.css");
        $this->addMediaFile("skin_css", "ves_verticalmenu/css/stylesheet.css");
        $this->addMediaFile("skin_css", "ves_verticalmenu/css/style.css");

        $this->addMediaFile("js", "venustheme/ves_verticalmenu/admin/verticalmenu/bootstrap.js");
        $this->addMediaFile("js", "venustheme/ves_verticalmenu/admin/verticalmenu/editor.js");

        return;
    }

}