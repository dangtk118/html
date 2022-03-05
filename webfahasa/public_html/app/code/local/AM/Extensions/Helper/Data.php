<?php
/**
 * @category    AM
 * @package     AM_Extensions
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_Extensions_Helper_Data extends Mage_Core_Helper_Abstract{
    public function loadJsLibs($libs=array()){
        if (is_string($libs)) $libs = array($libs);
        $head = Mage::getSingleton('core/layout')->getBlock('head')->setCanLoadExtJs(true);
        foreach ($libs as $lib){
            switch ($lib){
                case 'jscolor':
                    if (version_compare(Mage::getVersion(), '1.4.1.1') <= 0){
                        $head->addJs('mt/extensions/jscolor/jscolor.js');
                    }elseif (version_compare(Mage::getVersion(), '1.5.1.0') <= 0){
                        $head->addJs('jscolor/jscolor/jscolor.js');
                    }else{
                        $head->addJs('jscolor/jscolor.js');
                    }
                    break;
                case 'codemirror':
                    $head->addJs('am/extensions/codemirror/codemirror.js')
                        ->addJs('am/extensions/codemirror/mode/css.js')
                        ->addJs('am/extensions/codemirror/mode/javascript.js')
                        ->addItem('js_css', 'am/extensions/codemirror/codemirror.css');
                    break;
                case 'browser':
                    $head->addJs('lib/flex.js')
                        ->addJs('lib/FABridge.js')
                        ->addJs('mage/adminhtml/flexuploader.js')
                        ->addJs('am/extensions/browser.js')
                        ->addJs('prototype/window.js')
                        ->addItem('js_css', 'prototype/windows/themes/default.css');
                    if (version_compare(Mage::getVersion(), '1.7.0.0') < 0){
                        $head->addItem('js_css', 'prototype/windows/themes/magento.css');
                    }else{
                        $head->addItem('skin_css', 'lib/prototype/windows/themes/magento.css');
                    }
                    break;
            }
        }
    }

    public function getExtensionInfo($name){
        $id = $name . '_INFO';
        $cache = Mage::app()->getCache()->load($id);
        if ($cache == null){
            $info = Mage::getConfig()->getNode()->modules->$name;
            if ($info && $info->active) $cache = '1';
            else $cache = '0';
            Mage::app()->getCache()->save($cache, $id, array('CONFIG'), 60*60*24*30);
        }
        return $cache;
    }

    /**
     * Retrieve a collection of all rewrites
     *
     * @author      FireGento Team <team@firegento.com>
     * @copyright   2013 FireGento Team (http://firegento.com). All rights served.
     * @license     http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
     */
    public function getRewriteCollection(){
        $collection = new Varien_Data_Collection();
        $rewrites   = $this->_loadRewrites();

        foreach ($rewrites as $rewriteNodes) {
            foreach ($rewriteNodes as $n) {
                $nParent    = $n->xpath('..');
                $module     = (string) $nParent[0]->getName();
                $nSubParent = $nParent[0]->xpath('..');
                $component  = (string) $nSubParent[0]->getName();

                if (!in_array($component, array('blocks', 'helpers', 'models'))) {
                    continue;
                }

                $pathNodes = $n->children();
                foreach ($pathNodes as $pathNode) {
                    $path = (string) $pathNode->getName();
                    $completePath = $module.'/'.$path;

                    $rewriteClassName = (string) $pathNode;

                    $instance = Mage::getConfig()->getGroupedClassName(
                        substr($component, 0, -1),
                        $completePath
                    );

                    $collection->addItem(
                        new Varien_Object(
                            array(
                                'path'          => $completePath,
                                'rewrite_class' => $rewriteClassName,
                                'active_class'  => $instance,
                                'status'        => ($instance == $rewriteClassName)
                            )
                        )
                    );
                }
            }
        }

        return $collection;
    }

    /**
     * Return all rewrites
     *
     * @author      FireGento Team <team@firegento.com>
     * @copyright   2013 FireGento Team (http://firegento.com). All rights served.
     * @license     http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
     */
    protected function _loadRewrites(){
        $fileName = 'config.xml';
        $modules  = Mage::getConfig()->getNode('modules')->children();

        $return = array();
        foreach ($modules as $modName => $module) {
            if ($module->is('active')) {
                $configFile = Mage::getConfig()->getModuleDir('etc', $modName) . DS . $fileName;
                if (file_exists($configFile)) {
                    $xml = file_get_contents($configFile);
                    $xml = simplexml_load_string($xml);

                    if ($xml instanceof SimpleXMLElement) {
                        $return[$modName] = $xml->xpath('//rewrite');
                    }
                }
            }
        }

        return $return;
    }
}
