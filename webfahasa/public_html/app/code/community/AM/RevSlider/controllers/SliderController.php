<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_SliderController extends Mage_Adminhtml_Controller_Action{
    public function _initAction(){
        $this->loadLayout()->_setActiveMenu('am/revslider');
        $this->_title(Mage::helper('revslider')->__('AM Extensions'));
        return $this;
    }

    public function indexAction(){
        $this->_initAction();
        $this->_title(Mage::helper('revslider')->__('Manage Slider'));
        $this->renderLayout();
    }

    public function newAction(){
        $this->_forward('edit');
    }

    public function editAction(){
        $model = Mage::getModel('revslider/slider');
        $id = $this->getRequest()->getParam('id', null);
        if (is_numeric($id)) $model->load($id);
        Mage::register('revslider', $model);
        $this->_initAction();
        Mage::helper('amext')->loadJsLibs(array('jscolor', 'browser', 'codemirror'));
        $this->_title(Mage::helper('revslider')->__('Manage Slider'));
        if ($model->getId()) $this->_title($model->getTitle());
        else $this->_title(Mage::helper('revslider')->__('New Slider'));
        $this->renderLayout();
    }

    public function saveAction(){
        if ($data = $this->getRequest()->getPost()){
            $model = Mage::getModel('revslider/slider');
            if (isset($data['form_key'])) unset($data['form_key']);
            $model->setTitle($data['title']);
            $model->setStatus($data['status']);
            if (isset($data['styles'])) {
                $model->setStyles($data['styles']);
                unset($data['styles']);
            }
            if (isset($data['scripts'])) {
                $model->setScripts($data['scripts']);
                unset($data['scripts']);
            }
            $data = $this->_filterDates($data, array('date_from', 'date_to'));
            $model->setParams(Mage::helper('core')->jsonEncode($data));
            if (isset($data['slider_id']) && is_numeric($data['slider_id'])){
                $model->setId($data['slider_id']);
            }

            try{
                $model->save();
                Mage::app()->getCache()->clean('matchingTag', array(AM_RevSlider_Model_Slider::CACHE_TAGS));
                $this->_getSession()->addSuccess(
                    $this->__('"%s" saved successfully', $model->getTitle())
                );
                $back = $this->getRequest()->getParam('back');
                if ($back == 'edit'){
                    $this->_redirect('*/*/edit', array(
                        'id'        => $model->getId(),
                        'activeTab' => $this->getRequest()->getParam('activeTab')
                    ));
                }
                else $this->_redirect('*/*/index');
            }catch (Exception $e){}
        }
    }

    public function deleteAction(){
        $id = $this->getRequest()->getParam('id');
        if (is_numeric($id)){
            $model = Mage::getModel('revslider/slider')->load($id);
            if ($model->getId()){
               try{
                   $model->delete();
                   $this->_getSession()->addSuccess(
                       $this->__('"%s" deleted successfully', $model->getTitle())
                   );
               }catch (Exception $e){
                   $this->_getSession()->addError($e->getMessage());
               }
            }
        }
        $this->_redirect('*/*/index');
    }

    public function slideAction(){
        $this->loadLayout()->renderLayout();
    }

    public function slideGridAction(){
        $this->loadLayout()->renderLayout();
    }

    public function customAction(){
        $this->loadLayout()->renderLayout();
    }

    public function addSlideAction(){
        $slider     = Mage::getModel('revslider/slider');
        $slide      = Mage::getModel('revslider/slide');
        $sliderId   = $this->getRequest()->getParam('sid', null);
        $slideId    = $this->getRequest()->getParam('id', null);
        if (is_numeric($sliderId)) $slider->load($sliderId);
        if (is_numeric($slideId)) $slide->load($slideId);
        Mage::register('revslider', $slider);
        Mage::register('revslide', $slide);
        $this->_initAction();
        Mage::helper('amext')->loadJsLibs(array('jscolor', 'codemirror', 'browser'));
        $this->_title(Mage::helper('revslider')->__('Manage Slider'));
        if ($slide->getId()) $this->_title($slide->getTitle());
        else $this->_title(Mage::helper('revslider')->__('New Slide'));
        $this->renderLayout();
    }

    public function saveSlideAction(){
        if ($data = $this->getRequest()->getPost()){
            if ($data['form_key']) unset($data['form_key']);
            if ($data['layers']){
                $layers = $data['layers'];
                $arrayLayers = Mage::helper('core')->jsonDecode($layers);

                // using for sorting layer order
                function order_sort($a, $b){
                    if (!isset($a['order']) || !isset($b['order'])) return 0;
                    return $a['order'] - $b['order'];
                }

                usort($arrayLayers, 'order_sort');
                unset($data['layers']);
                $model = Mage::getModel('revslider/slide');
                $model->setSliderId($data['slider_id']);
                if (isset($data['fullwidth_centering'])){
                    $data['fullwidth_centering'] = true;
                }
                $data = $this->_filterDates($data, array('date_from', 'date_to'));
                $model->setParams(Mage::helper('core')->jsonEncode($data));
                $model->setLayers(Mage::helper('core')->jsonEncode($arrayLayers));
                if (isset($data['id']) && is_numeric($data['id'])){
                    $model->setId($data['id']);
                }else{
                    $numSlides = Mage::getModel('revslider/slider')->getSlideCount($data['slider_id']);
                    $model->setSlideOrder($numSlides + 1);
                }

                try{
                    $model->save();
                    Mage::app()->getCache()->clean('matchingTag', array(AM_RevSlider_Model_Slider::CACHE_TAGS));
                    $url = $this->getUrl('*/*/edit', array(
                        'id'        => $data['slider_id'],
                        'activeTab' => 'slide_section'
                    ));
                    $this->getResponse()->setBody($url);
                }catch (Exception $e){}
            }
        }
    }

    public function videoAction(){
        $this->loadLayout('overlay_popup');
        $this->renderLayout();
    }

    public function cssAction(){
        $rule = $this->getRequest()->getParam('style', null);
        $rule = sprintf('.tp-caption.%s', $rule);
        $model = Mage::getModel('revslider/css')->loadByRule($rule);
        Mage::register('css', $model);
        $this->loadLayout('overlay_popup');
        $this->renderLayout();
    }

    public function animationAction(){
        $this->loadLayout('overlay_popup');
        $aid = $this->getRequest()->getParam('aid');
        $model = Mage::getModel('revslider/animation');
        if (strpos($aid, 'custom') === 0){
            $part = explode('-', $aid);
            $model->load($part[1]);
            $model->setAnimSpeed(500);
        }
        Mage::register('animation', $model);
        $this->renderLayout();
    }

    public function saveAnimationAction(){
        if ($this->getRequest()->isPost()){
            $data = $this->getRequest()->getPost();
            if (isset($data['form_key'])) unset($data['form_key']);
            $model = Mage::getModel('revslider/animation');
            if (isset($data['id']) && $data['id']){
                $model->load($data['id']);
            }
            if (isset($data['name']) && $data['name']){
                $model->setName($data['name']);
                unset($data['name']);
                $model->setParams(Mage::helper('core')->jsonEncode($data));
                try{
                    $model->save();
                    $out['success'] = 1;
                    $out['data'] = array('id' => $model->getId(), 'name' => $model->getName(), 'params' => Mage::helper('core')->jsonDecode($model->getParams()));
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($out));
                }catch (Exception $e){

                }
            }
        }
    }

    public function deleteAnimationAction(){
        if ($this->getRequest()->isPost()){
            $id = $this->getRequest()->getParam('id');
            $model = Mage::getModel('revslider/animation')->load($id);
            if ($model->getId()){
                try{
                    $model->delete();
                    $out = array('success' => 1, 'id' => $id);
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($out));
                }catch (Exception $e){

                }
            }
        }
    }

    public function deleteCssAction(){
        if ($this->getRequest()->isPost()){
            $id = $this->getRequest()->getParam('id');
            $model = Mage::getModel('revslider/css')->load($id);
            if ($model->getId()){
                try{
                    $model->delete();
                    $this->getResponse()->setBody(
                        Mage::helper('core')->jsonEncode(array('success' => 1))
                    );
                }catch (Exception $e){
                    $this->getResponse()->setBody(
                        Mage::helper('core')->jsonEncode(array('error' => 1))
                    );
                }
            }
        }
    }

    public function saveCssAction(){
        if ($this->getRequest()->isPost()){
            $helper = Mage::helper('core');
            $data = $this->getRequest()->getPost();
            if (isset($data['form_key'])) unset($data['form_key']);
            if (isset($data['handle'])){
                $handle = sprintf('.tp-caption.%s', $data['handle']);
                $model = Mage::getModel('revslider/css')->loadByHandle($handle);
                if ($model->getId()){
                    try{
                        unset($data['handle']);
                        if (isset($data['hover'])){
                            $hover = array();
                            $model->setSettings($helper->jsonEncode(array('hover' => 1)));
                            foreach ($data as $k => $v){
                                if (strpos($k, '__') === 0){
                                    $hover[str_replace('__', '', $k)] = $v;
                                    unset($data[$k]);
                                }
                            }
                            $model->setHover($helper->jsonEncode($hover));
                            unset($data['hover']);
                        }else $model->setSettings(null);
                        $model->setParams($helper->jsonEncode($data));
                        $model->save();
                        $this->getResponse()->setBody(
                            $helper->jsonEncode(array('success' => 1))
                        );
                    }catch (Exception $e){
                        $this->getResponse()->setBody(
                            $helper->jsonEncode(array('error' => 1))
                        );
                    }
                }
            }elseif (isset($data['name'])){
                $model = Mage::getModel('revslider/css');
                $model->setHandle('.tp-caption.' . $data['name']);
                unset($data['name']);
                try{
                    if (isset($data['hover'])){
                        $hover = array();
                        $model->setSettings($helper->jsonEncode(array('hover' => 1)));
                        foreach ($data as $k => $v){
                            if (strpos($k, '__') === 0){
                                $hover[str_replace('__', '', $k)] = $v;
                                unset($data[$k]);
                            }
                        }
                        $model->setHover($helper->jsonEncode($hover));
                        unset($data['hover']);
                    }else $model->setSettings(null);
                    $model->setParams($helper->jsonEncode($data));
                    $model->save();
                    $this->getResponse()->setBody(
                        $helper->jsonEncode(array('success' => 1, 'handle' => $model->getPrettyName()))
                    );
                }catch (Exception $e){
                    $this->getResponse()->setBody(
                        $helper->jsonEncode(array('error' => 1))
                    );
                }
            }
        }
    }

    public function deleteSlideAction(){
        $id = $this->getRequest()->getParam('id');
        $sid = $this->getRequest()->getParam('sid');
        $model = Mage::getModel('revslider/slide');
        if (is_numeric($id)) $model->load($id);
        if ($model->getId()){
            $model->delete();
            Mage::app()->getCache()->clean('matchingTag', array(AM_RevSlider_Model_Slider::CACHE_TAGS));
        }
        $this->_redirect('*/*/edit', array(
            'id' => $sid,
            'activeTab' => 'slide_section'
        ));
    }

    public function ajaxSaveAction(){
        if ($data = $this->getRequest()->getPost()){
            $id = isset($data['entity']) ? (int)$data['entity'] : null;
            $attr = isset($data['attr']) ? $data['attr'] : null;
            $value = isset($data['value']) ? (int)$data['value'] : null;
            $out = array(
                'message' => '',
                'value' => $value
            );
            switch($attr){
                case 'slide_order':
                    $model = Mage::getModel('revslider/slide')->load($id);
                    if ($model->getId()){
                        $model->setData($attr, $value);
                        $model->save();
                        Mage::app()->getCache()->clean('matchingTag', array(AM_RevSlider_Model_Slider::CACHE_TAGS));
                    }else{
                        $out['message'] = Mage::helper('revslider')->__('Slide not avaiable');
                    }
            }
            $this->getResponse()->setBody(json_encode($out));
        }
    }

    public function massDeleteAction(){
        $ids = $this->getRequest()->getPost('ids');

        if (count($ids)){
            $i = 0;
            foreach ($ids as $id){
                $slider = Mage::getModel('revslider/slider')->load($id);
                if ($slider->getId()){
                    $slider->delete();
                    $i++;
                }
            }

            if ($i){
                $this->_getSession()->addSuccess(
                    Mage::helper('revslider')->__('%d slider(s) deleted sucessfully.', $i)
                );
            }
        }

        return $this->_redirect('*/*/index');
    }

    public function importAction(){
        $this->_initAction();
        $this->_setActiveMenu('am/revslider');
        $this->_title(Mage::helper('revslider')->__('Import Slider'));
        $this->renderLayout();
    }

    public function importPostAction(){
        if ($this->getRequest()->isPost()){
            try{
                $uploader = new Varien_File_Uploader('file');
                $uploader->setAllowedExtensions(array('zip'));
                $uploader->setAllowCreateFolders(true);
                $uploader->setAllowRenameFiles(false);
                $uploader->setFilesDispersion(false);
                $uploader->save('var/slider/');

                $file = $uploader->getUploadedFileName();
                $filePath = Mage::getBaseDir('var') . DS . 'slider' . DS . $file;

                $fileInfo = pathinfo($filePath);
                switch ($fileInfo['extension']){
                    case 'zip':
                        $slider = $this->_processZipImport($filePath);
                        break;
                    default:
                        throw new Exception(Mage::helper('revslider')->__('Only zip file supported.'));
                }

                if ($slider->getId()) {
                    $helper = Mage::helper('revslider');
                    /* @var $helper AM_RevSlider_Helper_Data */
                    $this->_getSession()->addSuccess(Mage::helper('revslider')->__(
                        'Importing slider successful. <a href="%s" target="_blank">Edit</a> or <a href="%s" target="_blank">Preview</a> now',
                        $this->getUrl('*/*/edit', array('id' => $slider->getId())),
                        $helper->getFrontendUrl('revslider/index/preview', array('id' => $slider->getId()))
                    ));
                }

                @unlink($filePath);
            }catch (Exception $e){
                $this->_getSession()->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/import');
    }

    protected function _processZipImport($filePath){
        /* @var $helper Mage_Core_Helper_Data */
        $helper = Mage::helper('core');

        if (!$filePath) {
            return null;
        }

        if (!class_exists('ZipArchive')) {
            throw new Exception(Mage::helper('revslider')->__('PHP Zip extension not found.'));
        }

        if (!file_exists($filePath)) {
            throw new Exception(Mage::helper('revslider')->__('Import file not found.'));
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception(Mage::helper('revslider')->__('Import file invalid.'));
        }

        $sliderData = $zip->getFromName('slider_export.txt');
        if ($sliderData === false) {
            throw new Exception(Mage::helper('revslider')->__('File "slider_export.txt" not found.'));
        }

        function format_json_callback($match){
            return sprintf('s:%d:"%s";', strlen($match[2]), $match[2]);
        }

        $sliderData = preg_replace_callback('/s:(\d+):"(.*?)";/', 'format_json_callback', $sliderData);

        $sliderArray = unserialize($sliderData);
        if ($sliderArray === false) {
            throw new Exception(Mage::helper('revslider')->__('File "slider_export.txt" invalid.'));
        }

        $slider = Mage::getModel('revslider/slider');
        $delay = null;

        if (isset($sliderArray['params'])) {
            if (isset($sliderArray['params']['slider_type'])){
                $sliderArray['params']['layout'] = $sliderArray['params']['slider_type'];
            }

            if (isset($sliderArray['params']['background_image'])){
                $sliderArray['params']['background_image'] = $this->_processImageZipImport(
                    $filePath,
                    $sliderArray['params']['background_image']
                );
            }

            if (isset($sliderArray['params']['touchenabled']) && $sliderArray['params']['touchenabled'] == 'on'){
                $sliderArray['params']['swipe_velocity'] = 0.7;
                $sliderArray['params']['swipe_min_touches'] = 1;
                $sliderArray['params']['swipe_max_touches'] = 1;
                $sliderArray['params']['drag_block_vertical'] = 'off';
            }

            if (!isset($sliderArray['params']['hide_thumbs_delay_mobile']) ||
                $sliderArray['params']['hide_thumbs_delay_mobile'] == ''){

                $sliderArray['params']['hide_thumbs_delay_mobile'] = 1500;
            }

            if (!isset($sliderArray['params']['loop_slide'])){
                $sliderArray['params']['loop_slide'] = 'on';
            }

            if (isset($sliderArray['params']['google_font']) && is_array($sliderArray['params']['google_font'])){
                foreach ($sliderArray['params']['google_font'] as $i => $font){
                    if (!$font) continue;
                    $sliderArray['params']['google_font'][$i] = stripslashes($font);
                }
            }

            $sliderArray['params']['status'] = 1;
            $sliderArray['params']['using_jquery'] = 'true';

            $delay = isset($sliderArray['params']['delay']) ? $sliderArray['params']['delay'] : 9000;

            $stylesData = $zip->getFromName('static-captions.css');

            $slider->setData(array(
                'title'     => isset($sliderArray['params']['title']) ? $sliderArray['params']['title'] : '',
                'status'    => 1,
                'params'    => $helper->jsonEncode($sliderArray['params']),
                'styles'    => $stylesData
            ));

            $slider->save();
        }else{
            throw new Exception(Mage::helper('revslider')->__('File "slider_export.txt" invalid.'));
        }

        $cssData = $zip->getFromName('dynamic-captions.css');
        if ($sliderData !== false){
            $cssParser = Mage::helper('revslider/css');
            /* @var $cssParser AM_RevSlider_Helper_Css */
            $cssArray = $cssParser->parse_css($cssData);

            if (is_array($cssArray)){
                foreach ($cssArray as $cssRule => $cssProperties){
                    $cssRuleParts = explode(':', $cssRule);
                    if (count($cssRuleParts) == 2){
                        $handle = $cssRuleParts[0];
                    }else{
                        $handle = $cssRule;
                    }

                    $cssModel = Mage::getModel('revslider/css');
                    /* @var $cssModel AM_RevSlider_Model_Css */
                    $cssModel->loadByRule($handle);
                    $cssModel->setData('handle', $handle);
                    if ($handle != $cssRule) {
                        $cssModel->setData('hover', $helper->jsonEncode($cssProperties));
                    }else {
                        $cssModel->setData('params', $helper->jsonEncode($cssProperties));
                    }

                    $cssModel->save();
                }
            }
        }

        $currentAnimations = array();
        $animationData = $zip->getFromName('custom_animations.txt');
        if ($animationData !== false){
            $animationArray = unserialize($animationData);
            if ($animationArray !== false && is_array($animationArray)){
                foreach ($animationArray as $animation){
                    if (!isset($animation['params']) && !isset($animation['handle'])) continue;

                    $animationModel = Mage::getModel('revslider/animation');
                    /* @var $animationModel AM_RevSlider_Model_Animation */
                    $animationModel->loadByName($animation['handle']);
                    if (!$animationModel->getId()) {
                        $animationModel->setData('name', $animation['handle']);
                    }
                    $animationModel->setData('params', $helper->jsonEncode($animation['params']));

                    $animationModel->save();

                    $currentAnimations['customin-' . $animation['id']] = 'custom-' . $animationModel->getId();
                    $currentAnimations['customout-' . $animation['id']] = 'custom-' . $animationModel->getId();
                }
            }
        }

        if ($slider->getId() && isset($sliderArray['slides'])){
            foreach ($sliderArray['slides'] as $slideArray){
                if (!isset($slideArray['params'])) continue;

                if (isset($slideArray['params']['image_url'])){
                    $slideArray['params']['image_url'] = $this->_processImageZipImport(
                        $filePath,
                        $slideArray['params']['image_url']
                    );
                }

                if (isset($slideArray['params']['image'])){
                    $slideArray['params']['image_url'] = $this->_processImageZipImport(
                        $filePath,
                        $slideArray['params']['image']
                    );
                    unset($slideArray['params']['image']);
                    unset($slideArray['params']['image_id']);
                }

                foreach ($slideArray['layers'] as $i => $layer){
                    if (isset($layer['link']) && $layer['link']){
                        $slideArray['layers'][$i]['link_enable'] = 'true';
                        $slideArray['layers'][$i]['link_type'] = 'regular';
                    }

                    if (isset($layer['link_slide']) && $layer['link_slide'] != 'nothing'){
                        $slideArray['layers'][$i]['link_enable'] = 'true';
                        $slideArray['layers'][$i]['link_type'] = 'slide';
                    }

                    if (isset($layer['animation'])){
                        if (isset($currentAnimations[$layer['animation']])){
                            $slideArray['layers'][$i]['animation'] = $currentAnimations[$layer['animation']];
                        }
                    }

                    if (isset($layer['endanimation'])){
                        if (isset($currentAnimations[$layer['endanimation']])){
                            $slideArray['layers'][$i]['endanimation'] = $currentAnimations[$layer['endanimation']];
                        }
                    }

                    if (isset($layer['image_url'])){
                        $slideArray['layers'][$i]['image_url'] = $this->_processImageZipImport(
                            $filePath,
                            $layer['image_url']
                        );
                    }

                    if (isset($layer['video_data'])){
                        $videoData = $layer['video_data'];
                        if (isset($videoData->urlPoster)){
                            $videoData->urlPoster = $this->_processImageZipImport($filePath, $videoData->urlPoster);
                            $slideArray['layers'][$i]['video_data'] = $videoData;
                        }
                    }

                    if (isset($layer['video_image_url'])){
                        $slideArray['layers'][$i]['video_image_url'] = $this->_processImageZipImport(
                            $filePath,
                            $layer['video_image_url']
                        );
                    }

                    if (!isset($layer['endtime']) || !$layer['endtime']){
                        $slideArray['layers'][$i]['endtime'] = $delay;
                    }

                    if (isset($layer['endWithSlide']) && $layer['endWithSlide'] == 1){
                        $slideArray['layers'][$i]['endtime'] = $delay;
                    }
                }

                $slide = Mage::getModel('revslider/slide');
                $slide->setData(array(
                    'slider_id' => $slider->getId(),
                    'slide_order' => $slideArray['slide_order'],
                    'params'    => isset($slideArray['params']) ? $helper->jsonEncode($slideArray['params']) : '',
                    'layers'    => isset($slideArray['layers']) ? $helper->jsonEncode($slideArray['layers']) : ''
                ));

                $slide->save();
            }
        }

        $zip->close();
        return $slider;
    }

    protected function _processImageZipImport($zipFile, $image){
        if (!$zipFile || !$image) return '';

        if (strpos($image, 'http') === 0){
            if (strpos($image, 'wp-content/uploads') !== false){
                $urlParts = explode('wp-content/', $image);
                if (count($urlParts) == 2){
                    $image = $urlParts[1];
                    if (!$image) return '';
                }
            }else{
                return $image;
            }
        }

        /* @var $helper Mage_Catalog_Model_Product_Url */
        $helper = Mage::getModel('catalog/product_url');
        $zipInfo = pathinfo($zipFile);
        $imageInfo = pathinfo($image);
        $zipName = $helper->formatUrlKey($zipInfo['filename']);

        $targetDir = Mage::getBaseDir('media') . DS . 'wysiwyg' . DS . $zipName . DS;
        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0777, true)) {
                return '';
            }
        }

        if (@copy('zip://' . $zipFile . '#images/' . $image, $targetDir . $imageInfo['basename'])) {
            return sprintf('wysiwyg/%s/%s', $zipName, $imageInfo['basename']);
        }else {
            return '';
        }
    }

    public function exportAction(){
        try {
            if (!class_exists('ZipArchive')) {
                throw new Exception(Mage::helper('revslider')->__('PHP Zip extension not found.'));
            }

            $id = $this->getRequest()->getParam('id');
            $slider = Mage::getModel('revslider/slider')->load($id);

            if ($slider->getId()) {
                /* @var $helper Mage_Catalog_Model_Product_Url */
                $helper     = Mage::getModel('catalog/product_url');
                $zipName    = $helper->formatUrlKey($slider->getTitle()).'.zip';
                $zipDir     = Mage::getBaseDir('var') . DS . 'slider' . DS;
                if (!is_dir($zipDir)){
                    if (!mkdir($zipDir, 0777)){
                        throw new Exception(Mage::helper('revslider')->__('Creating directory error: %s', $zipDir));
                    }
                }
                $zipPath    = $zipDir . $zipName;
                $zip        = new ZipArchive();
                if ($zip->open($zipPath, ZipArchive::CREATE) !== true){
                    throw new Exception(Mage::helper('revslider')->__('Creating zip file error'));
                }

                $sliderData         = array();
                $staticStylesData   = '';
                $dynamicStylesData  = '';
                $animationsData     = array();

                $sliderData['params'] = $slider->getData();
                if (isset($sliderData['params']['slider_id'])) {
                    unset($sliderData['params']['slider_id']);
                }
                if (isset($sliderData['params']['id'])) {
                    unset($sliderData['params']['id']);
                }
                if (isset($sliderData['params']['styles'])) {
                    $staticStylesData = $sliderData['params']['styles'];
                    unset($sliderData['params']['styles']);
                }
                if (isset($sliderData['params']['background_image'])){
                    $this->_processImageExport($zip, $sliderData['params']['background_image']);
                }

                $slides = $slider->getAllSlides();
                foreach ($slides as $slide) {
                    $slideData = array();

                    $slideData['params'] = $slide->getData();
                    if (isset($slideData['params']['id'])){
                        unset($slideData['params']['id']);
                    }
                    if (isset($slideData['params']['slider_id'])){
                        unset($slideData['params']['slider_id']);
                    }
                    if (isset($slideData['params']['image_url'])){
                        $this->_processImageExport($zip, $slideData['params']['image_url']);
                    }

                    $slideData['slide_order'] = $slide->getData('slide_order');

                    if (isset($slideData['params']['layers'])) {
                        $slideData['layers'] = $slideData['params']['layers'];
                        unset($slideData['params']['layers']);

                        foreach ($slideData['layers'] as $layer) {
                            if (isset($layer['animation'])) {
                                $this->_processAnimationExport($animationsData, $layer['animation']);
                            }
                            if (isset($layer['endanimation'])) {
                                $this->_processAnimationExport($animationsData, $layer['endanimation']);
                            }
                            if (isset($layer['style'])) {
                                $this->_processStyleExport($dynamicStylesData, $layer['style']);
                            }
                            if (isset($layer['image_url'])){
                                $this->_processImageExport($zip, $layer['image_url']);
                            }
                        }
                    }

                    $sliderData['slides'][] = $slideData;
                }

                $zip->addFromString('slider_export.txt', serialize($sliderData));
                $zip->addFromString('static-captions.css', $staticStylesData);
                $zip->addFromString('dynamic-captions.css', $dynamicStylesData);
                $zip->addFromString('custom_animations.txt', serialize($animationsData));
                $zip->close();

                $ouput = file_get_contents($zipPath);
                $this->getResponse()->setHeader('Content-Type', 'application/zip');
                $this->getResponse()->setHeader('Content-Disposition', "attachment; filename=\"{$zipName}\"");
                $this->getResponse()->setHeader('Content-Length', strlen($ouput));
                $this->getResponse()->setBody($ouput);
            }
        }catch (Exception $e){
            $this->_getSession()->addError($e->getMessage());
            return $this->_redirectUrl($this->_getRefererUrl());
        }
    }

    protected function _processImageExport(&$zip, $image){
        if (!$image) return $zip;
        if (strpos($image, 'http') === 0) return $zip;

        if (!$zip->addFile(Mage::getBaseDir('media') . DS . $image, 'images' . DS .$image)){
            throw new Exception(Mage::helper('revslider')->__('Exporting image error. Try again'));
        }

        return $zip;
    }

    protected function _processStyleExport(&$dynamicStylesData, $styleName){
        if (!$styleName) return $dynamicStylesData;
        if (isset($dynamicStylesData[$styleName])) return $dynamicStylesData;

        $styleHandle = '.tp-caption.' . $styleName;
        $styleModel = Mage::getModel('revslider/css')->loadByHandle($styleHandle);
        if ($styleModel->getId()){
            $dynamicStylesData .= $styleModel->toCssText()."\n";
        }

        return $dynamicStylesData;
    }

    protected function _processAnimationExport(&$animationsData, $animationName){
        if (!$animationName) return $animationsData;
        if (strpos($animationName, 'custom-') !== 0) return $animationsData;

        $animationNameParts = explode('-', $animationName);
        $animationId = $animationNameParts[1];
        if (!isset($animationsData[$animationId])) {
            $animationModel = Mage::getModel('revslider/animation')->load($animationId);
            if ($animationModel->getId()){
                $animationModel->setData('handle', $animationModel->getName());
                $animationsData[$animationId] = $animationModel->getData();
            }
        }

        return $animationsData;
    }
}
