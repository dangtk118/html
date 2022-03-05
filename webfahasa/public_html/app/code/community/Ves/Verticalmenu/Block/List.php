<?php
/******************************************************
 * @package Ves Megamenu module for Magento 1.4.x.x and Magento 1.7.x.x
 * @version 1.0.0.1
 * @author http://landofcoder.com
 * @copyright	Copyright (C) December 2010 LandOfCoder.com <@emai:landofcoder@gmail.com>.All rights reserved.
 * @license		GNU General Public License version 2
*******************************************************/


class Ves_Verticalmenu_Block_List extends Mage_Catalog_Block_Product_Abstract {

    protected $_config = '';
    protected $_listDesc = array();
    protected $_show = 0;
    protected $_theme = "";

    public function __construct($attributes = array()) {
        $helper = Mage::helper('ves_verticalmenu/data');
        $this->_config = $helper->get($attributes);
        
        if($attributes) {
            $this->convertAttributesToConfig($attributes);
        }
        /* End init meida files */
        parent::__construct();

        $my_template = $this->getTemplate();
        if($this->hasData("template") && $this->getData("template")) {
            $this->setTemplate($this->getData("template"));
        } else {
           $this->setTemplate('ves/verticalmenu/default.phtml');
        }
        
        /*Cache Block*/
          $enable_cache = $this->getConfig("enable_cache", 1 );
          if(!$enable_cache) {
            $cache_lifetime = null;
          } else {
            $cache_lifetime = $this->getConfig("cache_lifetime", 86400 );
            $cache_lifetime = (int)$cache_lifetime>0?$cache_lifetime: 86400;
          }

          $this->addData(array('cache_lifetime' => $cache_lifetime));
          $this->addCacheTag(array(
            Mage_Core_Model_Store::CACHE_TAG,
            Mage_Cms_Model_Block::CACHE_TAG,
            Ves_Verticalmenu_Model_Verticalmenu::CACHE_BLOCK_TAG
          ));

        /*End Cache Block*/
    }

    /**
     * Get Key pieces for caching block content
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        return array(
           'VES_VERTICALMENU_BLOCK_LIST',
           $this->getNameInLayout(),
           Mage::app()->getStore()->getId(),
           Mage::getDesign()->getPackageName(),
           Mage::getDesign()->getTheme('template'),
           Mage::getSingleton('customer/session')->getCustomerGroupId(),
           'template' => $this->getTemplate(),
        );
    }

    public function convertAttributesToConfig($attributes = array()) {
      if($attributes) {
        foreach($attributes as $key=>$val) {
            $this->setConfig($key, $val);
        }
      }
    }

    /**
     * get value of the extension's configuration
     *
     * @return string
     */
    public function getConfig($key, $default = "", $panel = "ves_verticalmenu") {

        $return = "";
        $value = $this->getData($key);
        //Check if has widget config data
        if($this->hasData($key) && $value !== null) {
          if($key == "pretext") {
            $value = base64_decode($value);
          }
          if($value == "true") {
            return 1;
          } elseif($value == "false") {
            return 0;
          }
          
          return $value;
          
        } else {

          if(isset($this->_config[$key])){

            $return = $this->_config[$key];

            if($return == "true") {
              $return = 1;
            } elseif($return == "false") {
              $return = 0;
            }

          }else{
            $return = Mage::getStoreConfig("ves_verticalmenu/$panel/$key");
          }
          if($return == "" && $default) {
            $return = $default;
          }

        }

        return $return;
        //return (!isset($this->_config[$key]) || (isset($this->_config[$key]) && empty($this->_config[$key]))) ? $default : $this->_config[$key];
    }

    /**
     * overrde the value of the extension's configuration
     *
     * @return string
     */
    function setConfig($key, $value) {
        $this->_config[$key] = $value;
        return $this;
    }

    /**
     *
     */
    function parseParams($params) {
        $params = html_entity_decode($params, ENT_QUOTES);
        $regex = "/\s*([^=\s]+)\s*=\s*('([^']*)'|\"([^\"]*)\"|([^\s]*))/";
        preg_match_all($regex, $params, $matches);
        $paramarray = null;
        if (count($matches)) {
            $paramarray = array();
            for ($i = 0; $i < count($matches[1]); $i++) {
                $key = $matches[1][$i];
                $val = $matches[3][$i] ? $matches[3][$i] : ($matches[4][$i] ? $matches[4][$i] : $matches[5][$i]);
                $paramarray[$key] = $val;
            }
        }
        return $paramarray;
    }

    function isStaticBlock() {
        $name = isset($this->_config["name"]) ? $this->_config["name"] : "";
        if (!empty($name)) {
            $regex1 = '/static_(\s*)/';
            if (preg_match_all($regex1, $name, $matches)) {
                return true;
            }
        }
        return false;
    }

    function set($params) {
        $params = preg_split("/\n/", $params);
        foreach ($params as $param) {
            $param = trim($param);
            if (!$param)
                continue;
            $param = split("=", $param, 2);
            if (count($param) == 2 && strlen(trim($param[1])) > 0)
                $this->_config[trim($param[0])] = trim($param[1]);
        }
        $theme = $this->getConfig("theme");
        if ($theme != $this->_theme) {
            $mediaHelper = Mage::helper('ves_verticalmenu/media');
            $mediaHelper->addMediaFile("skin_css", "ves_verticalmenu/" . $theme . "/style.css");
        }
    }
    public function substring($producttext, $length = 100, $replacer = '...', $isStriped = true) {
        $producttext = strip_tags($producttext);
        if (strlen($producttext) <= $length) {
            return $producttext;
        }
        $producttext = substr($producttext, 0, $length);
        $posSpace = strrpos($producttext, ' ');
        return substr($producttext, 0, $posSpace) . $replacer;
    }

}
