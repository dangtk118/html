<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_IndexController extends Mage_Core_Controller_Front_Action{
    public function previewAction(){
        $sliderId   = $this->getRequest()->getParam('id');
        $slideId    = $this->getRequest()->getParam('slide');
        $this->loadLayout();
        $this->getLayout()->getBlock('root')->setTemplate('page/empty.phtml');
        $block = $this->getLayout()->createBlock('revslider/slider_preview', '', array(
            'id'    => $sliderId,
            'slide' => $slideId,
            'cache_lifetime' => null
        ));
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }

    public function getCssCaptionsAction(){
        $this->getResponse()->setHeader('Content-Type', 'text/css', true);
        $this->getResponse()->setHeader('X-Content-Type-Options', 'nosniff', true);

        $css = '';

        $sliderId   = $this->getRequest()->getParam('id');
        $slider     = Mage::getModel('revslider/slider')->load($sliderId);
        if ($slider->getId()){
            $css    .= $slider->getStyles();
        }
        $css .= "\n";

        $collection = Mage::getModel('revslider/css')->getCollection();
        foreach ($collection as $item){
            try{
                $rules  = Mage::helper('core')->jsonDecode($item->getParams());
                $css    .= sprintf("%s{%s}\n", $item->getHandle(), implode('', $this->_getCssRule($rules)));
                $css    .= sprintf("%s{%s}\n", str_replace('.tp-caption', '', $item->getHandle()), implode('', $this->_getCssRule($rules)));
                $setting = Mage::helper('core')->jsonDecode($item->getSettings());
                if (isset($setting['hover'])){
                    $hover  = Mage::helper('core')->jsonDecode($item->getHover());
                    $css    .= sprintf("%s:hover{%s}\n", $item->getHandle(), implode('', $this->_getCssRule($hover)));
                    $css    .= sprintf("%s:hover{%s}\n", str_replace('.tp-caption', '', $item->getHandle()), implode('', $this->_getCssRule($hover)));
                }
            }catch (Exception $e){}
        }

        $this->getResponse()->setBody($css);
    }

    protected function _getCssRule($rules){
        $out = array();
        if (is_array($rules)){
            foreach ($rules as $k => $v){
                $out[] = sprintf("%s: %s;", $k, $v);
            }
        }
        return $out;
    }
}
