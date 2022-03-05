<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2013 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */
class AM_RevSlider_Helper_Data extends Mage_Core_Helper_Abstract{
    public function getCssHref($data, $admin=false){
        if (strpos($data, 'googleapis.com') !== false){
            if ($admin){
                if (preg_match_all('/href=[\"\']([^\"\']+)[\"\']/', $data, $match)) {
                    return $match[1][0];
                }
            }else{
                return stripslashes($data);
            }
        }else{
            if ($admin) {
                return sprintf('//fonts.googleapis.com/css?family=%s', $data);
            }else{
                return sprintf('<link href="//fonts.googleapis.com/css?family=%s" rel="stylesheet" type="text/css" media="all" />', $data);
            }
        }
    }

    public function getCssFromController($path, $params=array()){
        $stores = Mage::app()->getStores();
        foreach ($stores as $store){
            $params = array_merge($params, array('_store' => $store->getId()));
            if (Mage::getStoreConfigFlag('web/secure/use_in_adminhtml')){
                $params['_forced_secure'] = true;
            }
            return Mage::getUrl($path, $params);
            break;
        }
    }

    public function getFrontendUrl($path, $params=array()){
        return $this->getCssFromController($path, $params);
    }
}