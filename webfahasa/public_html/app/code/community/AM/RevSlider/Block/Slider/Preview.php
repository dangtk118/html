<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Slider_Preview extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface{
    protected $sliderHtmlId;
    protected $sliderHtmlIdWrapper;
    protected $numSlides;
    protected $customAnimations;
    protected $oneSlide;

    protected function _construct(){
        parent::_construct();
        $this->setData('cache_lifetime', 60*60*24*7*4);
        $this->setData('cache_tags', array(AM_RevSlider_Model_Slider::CACHE_TAGS));
    }

    public function getCacheKey(){
        return AM_RevSlider_Model_Slider::CACHE_KEY_PREFIX . Mage::app()->getStore()->getId() . $this->getData('id');
    }

    protected function _getCustomAnimations(){
        if (!$this->customAnimations){
            $animations = array();
            $collection = Mage::getModel('revslider/animation')->getCollection();
            foreach ($collection as $item){
                $animations['custom-' . $item->getId()] = Mage::helper('core')->jsonDecode($item->getParams());
            }
            $this->customAnimations = $animations;
        }
        return $this->customAnimations;
    }

    protected function _renderCustomAnimData($data){
        return sprintf('x:%d;y:%d;z:%d;rotationX:%d;rotationY:%d;rotationZ:%d;scaleX:%d;scaleY:%d;skewX:%d;skewY:%d;opacity:%d;transformPerspective:%d;transformOrigin:%s;',
            isset($data['movex'])?(int)$data['movex']:0, isset($data['movey'])?(int)$data['movey']:0, isset($data['movez'])?(int)$data['movez']:0,
            isset($data['rotationx'])?(int)$data['rotationx']:0, isset($data['rotationy'])?(int)$data['rotationy']:0, isset($data['rotationz'])?(int)$data['rotationz']:0,
            isset($data['scalex'])?(int)$data['scalex'] / 100:1, isset($data['scaley'])?(int)$data['scaley'] / 100:1,
            isset($data['skewx'])?(int)$data['skewx']:0, isset($data['skewy'])?(int)$data['skewy']:0,
            isset($data['captionopacity'])?(int)$data['captionopacity'] / 100:0,
            isset($data['captionperspective'])?(int)$data['captionperspective']:500,
            sprintf('%d%% %d%%', isset($data['originx'])?(int)$data['originx']:50, isset($data['originy'])?(int)$data['originy']:50)
        );
    }

    protected function _toHtml(){
        $sliderId   = $this->getData('id');
        $slideId    = $this->getData('slide');

        $slider = Mage::getModel('revslider/slider')->load($sliderId);

        if ($slider->getId() && $slider->getStatus() == 1){
            $dateFrom = $slider->getData('date_from');
            $dateTo = $slider->getData('date_to');
            /* @var $date Mage_Core_Model_Date */
            $date = Mage::getModel('core/date');
            if ($dateFrom && $date->date('Y-m-d') < $dateFrom) return;
            if ($dateTo && $date->date('Y-m-d') > $dateTo) return;

            $html = '';

            if ($slider->getData('load_googlefont') == 'true'){
                $fonts = $slider->getData('google_font');
                if (is_array($fonts)){
                    foreach ($fonts as $font){
                        $html .= $this->getCleanFontImport($font);
                    }
                }else{
                    $html .= $this->getCleanFontImport($fonts);
                }
            }

            $bannerWidth = $slider->getData('width');
            $bannerHeight = $slider->getData('height');
            $this->sliderHtmlId = "rev_slider_{$slider->getId()}";
            $this->sliderHtmlIdWrapper = "{$this->sliderHtmlId}_wrapper";
            $sliderPosition = $slider->getData('position');
            $sliderType = $slider->getData('layout');
            $containerStyle = '';
            if ($sliderType != 'fullscreen'){
                switch ($sliderPosition){
                    case 'center':
                    default:
                        $containerStyle .= 'margin:0px auto;';
                        break;
                    case 'left':
                        $containerStyle .= 'float:left;';
                        break;
                    case 'right':
                        $containerStyle .= 'float:right;';
                        break;
                }
            }

            if ($backgrondColor = $slider->getData('background_color')){
                $containerStyle .= "background-color:#{$this->_cleanColor($backgrondColor)};";
            }

            $containerStyle .= "padding:{$slider->getData('padding')}px;";
            if ($sliderType != 'fullscreen'){
                if ($sliderPosition != 'center'){
                    $containerStyle .= "margin-left:{$slider->getData('margin_left')}px;";
                    $containerStyle .= "margin-right:{$slider->getData('margin_right')}px;";
                }
                $containerStyle .= "margin-top:{$slider->getData('margin_top')}px;";
                $containerStyle .= "margin-bottom:{$slider->getData('margin_bottom')}px;";
            }

            $bannerStyle = 'display:none;';
            if ($slider->getData('show_background_image') == 'true'){
                if ($backgroundImage = $slider->getData('background_image')){
                    $bannerStyle .= sprintf("background-image:url(%s);background-repeat:%s;background-size:%s;background-position:%s;",
                        Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $backgroundImage,
                        $slider->getData('bg_fit'),
                        $slider->getData('bg_repeat'),
                        $slider->getData('bg_position')
                    );
                }
            }

            $sliderWrapperClass = 'rev_slider_wrapper';
            $sliderClass = 'rev_slider';
            $putResponsiveStyles = false;

            switch ($sliderType){
                default:
                case 'fixed':
                    $bannerStyle .= "height:{$bannerHeight}px;width:{$bannerWidth}px;";
                    $containerStyle .= "height:{$bannerHeight}px;width:{$bannerWidth}px;";
                    break;
                case 'responsitive':
                    $putResponsiveStyles = true;
                    break;
                case 'fullwidth':
                    $sliderWrapperClass .= ' fullwidthbanner-container';
                    $sliderClass .= ' fullwidthabanner';
                    $bannerStyle .= "max-height:{$bannerHeight}px;height:{$bannerHeight}px;";
                    $containerStyle .= "max-height:{$bannerHeight}px;";
                    break;
                case 'fullscreen':
                    $sliderWrapperClass .= ' fullscreen-container';
                    $sliderClass .= ' fullscreenbanner';
                    break;
            }

            $htmlTimerBar = '';
            if ($slider->getData('show_timerbar') != 'hide'){
                switch ($slider->getData('show_timerbar')){
                    case 'top':
                        $htmlTimerBar .= '<div class="tp-bannertimer"></div>';
                        break;
                    case 'bottom':
                        $htmlTimerBar .= '<div class="tp-bannertimer tp-bottom"></div>';
                        break;
                }
            }

            if ($slider->getData('padding_type') == 'inner'){
                $sliderWrapperClass .= ' tp_inner_padding';
            }

            $output = '';
            if ($putResponsiveStyles){
                $output .= $this->renderResponsiveStyle($slider);
            }

            $output .= $html;
            $output .= "<div id='{$this->sliderHtmlIdWrapper}' class='{$sliderWrapperClass}' style='{$containerStyle}'>";
            $output .= "<div id='{$this->sliderHtmlId}' class='{$sliderClass}' style='{$bannerStyle}'>";
            $output .= $this->renderSlides($slider, $slideId);
            $output .= $htmlTimerBar;
            $output .= "</div>";
            $output .= "</div>";
            $output .= $this->renderJs($slider);

            return $output;
        }
    }

    protected function _cleanColor($hex){
        return str_replace('#', '', $hex);
    }

    public function getCleanFontImport($font){
        return Mage::helper('revslider')->getCssHref($font);
    }

    public function renderResponsiveStyle($slider){
        $width = (int) $slider->getData('width');
        $height = (int) $slider->getData('height');
        $percent = $height / $width;
        $w1 = (int) $slider->getData('responsitive_w1');
        $w2 = (int) $slider->getData('responsitive_w2');
        $w3 = (int) $slider->getData('responsitive_w3');
        $w4 = (int) $slider->getData('responsitive_w4');
        $w5 = (int) $slider->getData('responsitive_w5');
        $w6 = (int) $slider->getData('responsitive_w6');
        $sw1 = (int) $slider->getData('responsitive_sw1');
        $sw2 = (int) $slider->getData('responsitive_sw2');
        $sw3 = (int) $slider->getData('responsitive_sw3');
        $sw4 = (int) $slider->getData('responsitive_sw4');
        $sw5 = (int) $slider->getData('responsitive_sw5');
        $sw6 = (int) $slider->getData('responsitive_sw6');
        $arrItems = array();

        // add main item:
        $arr = array();
        $arr["maxWidth"] = -1;
        $arr["minWidth"] = $w1;
        $arr["sliderWidth"] = $width;
        $arr["sliderHeight"] = $height;
        $arrItems[] = $arr;

        //add item 1:
        if(!empty($w1)){
            $arr = array();
            $arr["maxWidth"] = $w1-1;
            $arr["minWidth"] = $w2;
            $arr["sliderWidth"] = $sw1;
            $arr["sliderHeight"] = floor($sw1 * $percent);
            $arrItems[] = $arr;
        }

        //add item 2:
        if(!empty($w2)){
            $arr["maxWidth"] = $w2-1;
            $arr["minWidth"] = $w3;
            $arr["sliderWidth"] = $sw2;
            $arr["sliderHeight"] = floor($sw2 * $percent);
            $arrItems[] = $arr;
        }

        //add item 3:
        if(!empty($w3)){
            $arr["maxWidth"] = $w3-1;
            $arr["minWidth"] = $w4;
            $arr["sliderWidth"] = $sw3;
            $arr["sliderHeight"] = floor($sw3 * $percent);
            $arrItems[] = $arr;
        }

        //add item 4:
        if(!empty($w4)){
            $arr["maxWidth"] = $w4-1;
            $arr["minWidth"] = $w5;
            $arr["sliderWidth"] = $sw4;
            $arr["sliderHeight"] = floor($sw4 * $percent);
            $arrItems[] = $arr;
        }

        //add item 5:
        if(!empty($w5)){
            $arr["maxWidth"] = $w5-1;
            $arr["minWidth"] = $w6;
            $arr["sliderWidth"] = $sw5;
            $arr["sliderHeight"] = floor($sw5 * $percent);
            $arrItems[] = $arr;
        }

        //add item 6:
        if(!empty($w6)){
            $arr["maxWidth"] = $w6-1;
            $arr["minWidth"] = 0;
            $arr["sliderWidth"] = $sw6;
            $arr["sliderHeight"] = floor($sw6 * $percent);
            $arrItems[] = $arr;
        }

        $output = "<style type='text/css'>";
        $output .= "#{$this->sliderHtmlId},#{$this->sliderHtmlIdWrapper}{width:{$width}px;height:{$height}px;}";
        foreach ($arrItems as $item){
            $strMaxWidth = '';
            if ($item['maxWidth'] > 0) $strMaxWidth = "and (max-width:{$item['maxWidth']}px)";
            $output .= "@media only screen and (min-width:{$item['minWidth']}px) {$strMaxWidth}{";
            $output .= "#{$this->sliderHtmlId},#{$this->sliderHtmlIdWrapper}{width:{$item['sliderWidth']}px;height:{$item['sliderHeight']}px;}";
            $output .= "}";
        }
        $output .= "</style>";
        return $output;
    }

    /**
     * @param AM_RevSlider_Model_Slider $slider
     * @param int|null $slideId
     * @return string
     */
    public function renderSlides($slider, $slideId=null){
        $slides = $slider->getAllSlides(true, $slideId);
        $this->numSlides = count($slides);
        $sDuration = $slider->getData('delay');
        if ($slider && $this->numSlides){
            if (count($slides) == 1 && $this->oneSlide == false) $this->oneSlide = true;
            $navigationType = $slider->getData('navigaion_type');
            $isThumbsActive = $navigationType == 'thumb';
            $navigationStyle = $slider->getData('navigation_style');
            $isSpecialNav = false;
            switch ($navigationStyle){
                case 'preview1':
                case 'preview2':
                case 'preview3':
                case 'preview4':
                case 'custom':
                    $isSpecialNav = true;
                    break;
            }
            $index = 0;
            $output = "<ul>";
            foreach ($slides as $slide){
                $dateFrom = $slide->getData('date_from');
                $dateTo = $slide->getData('date_to');
                /* @var $date Mage_Core_Model_Date */
                $date = Mage::getModel('core/date');
                if ($dateFrom && $date->date('Y-m-d') < $dateFrom) continue;
                if ($dateTo && $date->date('Y-m-d') > $dateTo) continue;
                $transition = $slide->getData('slide_transition');
                $transitions = explode(',', $transition);
                $transitionRand = '';
                if (!empty($transitions) && is_array($transitions)){
                    foreach ($transitions as $i => $tran){
                        if ($tran == 'random-selected'){
                            $transitionRand = ' data-randomtransition="on" ';
                            unset($transitions[$i]);
                            $transition = implode(',', $transitions);
                            break;
                        }
                    }
                }
                $slotAmount = $slide->getData('slot_amount');

                $bgType = $slide->getData('background_type');
                $urlSlideImage = '';
                if ($bgType == 'image'){
                    $urlSlideImage = strpos($slide->getData('image_url'), 'http') === 0 ?
                        $slide->getData('image_url') :
                        Mage::getBaseUrl('media') . $slide->getData('image_url');
                }elseif ($bgType == 'external'){
                    $urlSlideImage = $slide->getData('bg_external');
                }

                $htmlThumb = '';
                if ($isThumbsActive || $isSpecialNav){
                    if ($urlThumb = $slide->getData('slide_thumb')){
                        $urlThumb = strpos($urlThumb, 'http') === 0 ? $urlThumb : Mage::getBaseUrl('media') . $urlThumb;
                        $htmlThumb = "data-thumb='{$urlThumb}' ";
                    }else{
                        $htmlThumb = $urlSlideImage ? "data-thumb='{$urlSlideImage}' " : '';
                    }
                }

                $htmlLink = '';
                if ($slide->getData('enable_link') == 'true'){
                    switch ($slide->getData('link_type')){
                        case 'regular':
                        default:
                            if ($slide->getData('link_open_in') == 'new'){
                                $htmlLink .= "data-target='_blank' ";
                            }
                            $htmlLink .= "data-link='{$slide->getData('link')}' ";
                            break;
                        case 'slide':
                            $slideLink = $slide->getData('slide_link');
                            if ($slideLink && $slideLink != 'nothing'){
                                $htmlLink = "data-link='slide' data-linktoslide='{$slideLink}' ";
                            }
                            break;
                    }
                    if ($slide->getData('link_pos') == 'back'){
                        $htmlLink .= "data-slideindex='back' ";
                    }
                }

                $htmlDelay = '';
                if (is_numeric($delay = $slide->getData('delay'))){
                    $htmlDelay .= "data-delay='{$delay}' ";
                }

                $htmlDuration = '';
                if (is_numeric($duration = $slide->getData('transition_duration'))){
                    $htmlDuration .= "data-masterspeed='{$duration}' ";
                }

                $htmlPerformance = "data-saveperformance='{$slide->getData('save_performance')}' ";

                $htmlRotation = '';
                if ($rotation = $slide->getData('transition_rotation') != 0){
                    $htmlRotation .= "data-rotate='".($rotation < -720 ? -720 : ($rotation > 720 && $rotation != 999 ? 720 : $rotation))."' ";
                }

                $htmlFirstTrans = '';
                $startWithSlide = (int)$slider->getData('start_with_slide') - 1;
                $startWithSlide = $startWithSlide < 0 ? 0 : ($startWithSlide >= $this->numSlides ? 0 : $startWithSlide);
                if ($index == $startWithSlide){
                    if ($slider->getData('first_transition_active') == 'on'){
                        $htmlFirstTrans .= " data-fstransition='{$slider->getData('first_transition_type')}' ";
                        $htmlFirstTrans .= " data-fsmasterspeed='{$slider->getData('first_transition_duration')}' ";
                        $htmlFirstTrans .= " data-fsslotamount='{$slider->getData('first_transition_slot_amount')}' ";
                    }
                }

                $htmlParams = $htmlDuration.$htmlLink.$htmlThumb.$htmlDelay.$htmlRotation.$htmlFirstTrans.$htmlPerformance;

                $styleImage = '';
                switch ($slide->getData('background_type')){
                    case 'trans':
                        $urlSlideImage = Mage::getBaseUrl('js').'am/revslider/rs-plugin/images/transparent.png';
                        break;
                    case 'solid':
                        $urlSlideImage = Mage::getBaseUrl('js').'am/revslider/rs-plugin/images/transparent.png';
                        $styleImage .= "style='background-color:#{$this->_cleanColor($slide->getData('slide_bg_color'))}'";
                        break;
                }

                $imageAddParams = '';
                if ($slider->getData('lazy_load') == 'on'){
                    $imageAddParams .= "data-lazyload='{$urlSlideImage}' ";
                    $urlSlideImage = Mage::getBaseUrl('js').'am/revslider/rs-plugin/images/dummy.png';
                }

                $bgFit = $slide->getData('bg_fit');
                $bgFitX = intval($slide->getData('bg_fit_x'));
                $bgFitY = intval($slide->getData('bg_fit_y'));
                $bgPosition = $slide->getData('bg_position');
                $bgPositionX = intval($slide->getData('bg_position_x'));
                $bgPositionY = intval($slide->getData('bg_position_y'));
                $bgRepeat = $slide->getData('bg_repeat');

                if($bgPosition == 'percentage'){
                    $imageAddParams .= "data-bgposition='{$bgPositionX}% {$bgPositionY}%' ";
                }else{
                    $imageAddParams .= "data-bgposition='{$bgPosition}' ";
                }

                $kb_pz = '';
                $kenburn_effect = $slide->getData('kenburn_effect');
                if ($kenburn_effect == 'on'){
                    $kb_duration = $slide->getData('kb_duration') ? $slide->getData('kb_duration') : $sDuration;
                    $kb_ease = $slide->getData('kb_easing');
                    $kb_start_fit = $slide->getData('kb_start_fit') ? $slide->getData('kb_start_fit') : 100;
                    $kb_end_fit = $slide->getData('kb_end_fit') ? $slide->getData('kb_end_fit') : 100;
                    if ($bgType == 'image' || $bgType == 'external'){
                        $kb_pz .= " data-kenburns='on'";
                        $kb_pz .= " data-duration='{$kb_duration}'";
                        $kb_pz .= " data-ease='{$kb_ease}'";
                        $kb_pz .= " data-bgfit='{$kb_start_fit}'";
                        $kb_pz .= " data-bgfitend='{$kb_end_fit}'";

                        $bgEndPosition = $slide->getData('bg_end_position');
                        if ($bgEndPosition == 'percentage'){
                            $bgEndPositionX = (int)$slide->getData('bg_end_position_x');
                            $bgEndPositionY = (int)$slide->getData('bg_end_position_y');
                            $kb_pz .= " data-bgpositionend='{$bgEndPositionX}% {$bgEndPositionY}%'";
                        }else{
                            $kb_pz .= " data-bgpositionend='{$bgEndPosition}'";
                        }
                    }
                }else{
                    if ($bgFit == 'percentage'){
                        $imageAddParams .= " data-bgfit='{$bgFitX}% {$bgFitY}%'";
                    }else{
                        $imageAddParams .= " data-bgfit='{$bgFit}'";
                    }
                }

                $imageAddParams .= " data-bgrepeat='{$bgRepeat}' ";

                $slideTitle = '';
                $class_attr = $slide->getData('class_attr');
                if ($class_attr) $htmlParams .= " class='{$class_attr}'";
                $id_attr = $slide->getData('id_attr');
                if ($id_attr) $htmlParams .= " id='{$id_attr}'";
                //$attr_attr = $slide->getData('attr_attr');
                //if ($attr_attr) $htmlParams .= " attr='{$attr_attr}'";
                $data_attr = $slide->getData('data_attr');
                if ($data_attr) $htmlParams .= " {$data_attr}";

                switch ($navigationStyle){
                    case 'preview1':
                    case 'preview2':
                    case 'preview3':
                    case 'preview4':
                    case 'custom':
                        $slideTitle = "data-title=\"".htmlspecialchars(Mage::helper('revslider')->__($slide->getData('title')))."\" ";
                        break;
                }

                $output .= "<li data-transition='{$transition}' data-slotamount='{$slotAmount}' {$transitionRand} {$htmlParams} {$slideTitle}>";
                $output .= "<img src='{$urlSlideImage}' {$styleImage} {$imageAddParams} {$kb_pz}/>";
                $output .= $this->renderLayers($slide, $slider);
                $output .= "</li>";
                $index++;
            }
            $output .= "</ul>";
        }else{
            $output = '<div class="no-slides-text">';
            $output .= $this->__('No slides found, please add some slides');
            $output .= '</div>';
        }
        return $output;
    }

    public function getHtml5LayerHtml($layer){
        $data   = $layer->getData('video_data');
        $ids    = $layer->getData('attrID');
        $ids    = $ids ? " id='{$ids}' " : '';
        $classes = $layer->getData('attrClasses');
        $classes = $classes ? " {$classes} " : '';
        $title  = $layer->getData('attrTitle');
        $title  = $title ? " title='{$title}' " : '';
        $rel    = $layer->getData('attrRel');
        $rel    = $rel ? " rel='{$rel}' " : '';
        $urlPoster  = isset($data['urlPoster']) && $data['urlPoster'] ? (strpos($data['urlPoster'], 'http') === 0 ? $data['urlPoster'] : Mage::getBaseUrl('media').$data['urlPoster']) : '';
        $urlMp4     = isset($data['urlMp4']) && $data['urlMp4'] ? (strpos($data['urlMp4'], 'http') === 0 ? $data['urlMp4'] : Mage::getBaseUrl('media').$data['urlMp4']) : '';
        $urlWebm    = isset($data['urlWebm']) && $data['urlWebm'] ? (strpos($data['urlWebm'], 'http') === 0 ? $data['urlWebm'] : Mage::getBaseUrl('media').$data['urlWebm']) : '';
        $urlOgv     = isset($data['urlOgv']) && $data['urlOgv'] ? (strpos($data['urlOgv'], 'http') === 0 ? $data['urlOgv'] : Mage::getBaseUrl('media').$data['urlOgv']) : '';
        $fullwidth  = isset($data['fullwidth']) ? $data['fullwidth'] : 0;
        $videoloop  = isset($data['loop']) ? $data['loop'] : 0;
        $videoloop  = $videoloop ? 'loop' : '';
        $control    = isset($data['control']) ? $data['control'] : 0;
        $control    = $control ? '' : 'controls';
        $width      = $fullwidth == 1 ? '100%' : (isset($data['width']) ? $data['width'] : 320);
        $height     = $fullwidth == 1 ? '100%' : (isset($data['height']) ? $data['height'] : 240);
        $htmlPoster = $urlPoster ? "poster='{$urlPoster}'" : '';
        $htmlMp4    = $urlMp4 ? "<source src='{$urlMp4}' type='video/mp4'/>" : '';
        $htmlWebm   = $urlWebm ? "<source src='{$urlWebm}' type='video/webm'/>" : '';
        $htmlOgv    = $urlOgv ? "<source src='{$urlOgv}' type='video/ogg'/>" : '';
        $html   = "<video class='{$classes}' {$ids} {$title} {$rel} {$videoloop} {$control} preload='none' width='{$width}' height='{$height}' {$htmlPoster}>";
        $html   .= $htmlMp4;
        $html   .= $htmlWebm;
        $html   .= $htmlOgv;
        $html   .= '</video>';
        return $html;
    }

    public function renderLayers($slide, $slider){
        if (!$slide->getLayers()) return '';
        $output = '';
        $zIndex = 2;
        $customAnimations = $this->_getCustomAnimations();
        $lazyload = $slider->getData('lazy_load') ? $slider->getData('lazy_load') : 'off';

        foreach ($slide->getLayers() as $layer){
            $layer = new Varien_Object($layer);

            $type = $layer->getData('type');

            $videoClass = '';
            if ($type == 'video'){
                $videoClass = ' tp-videolayer ';
            }

            $class = trim($layer->getData('style'));
            $animation = trim($layer->getData('animation'));
            if ($animation == 'fade') $animation = 'tp-fade';

            $customin = '';
            if (array_key_exists($animation, $customAnimations)){
                $customAnimData = $this->_renderCustomAnimData($customAnimations[$animation]);
                $customin = "data-customin='{$customAnimData}' ";
                $animation = 'customin';
            }
            if (strpos($animation, 'custom-') !== false) $animation = "tp-fade";

            $rotation2D = $this->_get($layer, '2d_rotation', 0);
            if ($rotation2D == 0) {
                $oClass = "tp-caption {$class} {$animation}";
            }else{
                $oClass = "tp-caption {$animation} ";
            }

            $left = $layer->getData('left');
            $top = $layer->getData('top');
            $speed = $layer->getData('speed');
            $time = $layer->getData('time');
            $easing = $layer->getData('easing');
            $text = Mage::helper('revslider')->__($layer->getData('text'));

            $splitIn = $layer->getData('split') ? $layer->getData('split') : 'none';
            $splitOut = $layer->getData('endsplit') ? $layer->getData('endsplit') : 'none';
            $splitDelayIn = $layer->getData('splitdelay') ? $layer->getData('splitdelay') : 10;
            $splitDelayIn = $splitDelayIn > 0 ? $splitDelayIn/100 : 10/100;
            $splitDelayOut = $layer->getData('endsplitdelay') ? $layer->getData('endsplitdelay') : 10;
            $splitDelayOut = $splitDelayOut > 0 ? $splitDelayOut/100 : 10/100;

            $maxWidth = $layer->getData('max_width') ? $layer->getData('max_width') : 'auto';
            $maxHeight = $layer->getData('max_height') ? $layer->getData('max_height') : 'auto';
            $whiteSpace = $layer->getData('whitespace') ? $layer->getData('whitespace') : 'nowrap';
            $originX2D = $layer->getData('2d_origin_x') ? $layer->getData('2d_origin_x') : 50;
            $originY2D = $layer->getData('2d_origin_y') ? $layer->getData('2d_origin_y') : 50;

            $inlineStyles = '';
            $rotation = '';
            $doRotation = false;

            $ids = $layer->getData('attrID');
            $ids = $ids ? " id='{$ids}' " : '';
            $classes = $layer->getData('attrClasses');
            $oClass .= $classes ? " {$classes} " : '';
            $title = $layer->getData('attrTitle');
            $title = $title ? " title=\"".htmlspecialchars(Mage::helper('revslider')->__($title))."\" " : '';
            $rel = $layer->getData('attrRel');
            $rel = $rel ? " rel='{$rel}' " : '';

            $html = '';
            $htmlVideo = '';
            $videoFullWidth = false;
            $add_data = '';

            switch ($type){
                case 'text':
                default:
                    if ($layer->getData('link_enable') == 'true'){
                        $link = $layer->getData('link');
                        if ($layer->getData('link_open_in') == 'new'){
                            $html = "<a href='{$link}' target='_blank'>{$text}</a>";
                        }else{
                            $html = "<a href='{$link}'>{$text}</a>";
                        }
                    }else{
                        $html = $text;
                    }

                    $inlineStyles .= "max-width:{$maxWidth};max-height:{$maxHeight};white-space:{$whiteSpace};";

                    if ($rotation2D){
                        $doRotation = true;
                    }
                    break;
                case 'image':
                    $urlImage = $layer->getData('image_url');
                    $urlImage = strpos($urlImage, 'http') === 0 ? $urlImage : Mage::getBaseUrl('media') . $urlImage;
                    $alt = $layer->getData('alt');

                    $additional = '';
                    $scaleX = $layer->getData('scaleX');
                    $scaleY = $layer->getData('scaleY');
                    $additional .= $scaleX ? " data-ww='{$scaleX}' " : '';
                    $additional .= $scaleY ? " data-hh='{$scaleY}' " : '';

                    $imageAddParams = '';
                    if ($lazyload == 'on') {
                        $imageAddParams .= " data-lazyload='{$urlImage}'";
                        $urlImage = Mage::getBaseUrl('js').'am/revslider/rs-plugin/images/dummy.png';
                    }

                    $html = "<img src='{$urlImage}' alt='{$alt}' {$additional} {$imageAddParams}/>";
                    if ($layer->getData('link_enable') == 'true'){
                        $linkAddParams = '';
                        $linkAddParams .= $layer->getData('attrID') ? " id='{$layer->getData('attrID')}'" : '';
                        $linkAddParams .= $layer->getData('attrClasses') ? " class='{$layer->getData('attrClasses')}'" : '';
                        $linkAddParams .= $layer->getData('attrTitle') ? " title='{$layer->getData('attrTitle')}'" : '';
                        $linkAddParams .= $layer->getData('attrRel') ? " rel='{$layer->getData('attrRel')}'" : '';

                        if ($layer->getData('link_type') == 'regular'){
                            $link = $layer->getData('link');
                            if ($layer->getData('link_open_in') == 'new'){
                                $html = "<a href='{$link}' target='_blank' {$linkAddParams}>{$html}</a>";
                            }else{
                                $html = "<a href='{$link}' {$linkAddParams}>{$html}</a>";
                            }
                        }
                    }

                    if ($rotation2D){
                        $doRotation = true;
                    }
                    break;
                case 'video':
                    $videoType = $layer->getData('video_type');
                    $videoId = $layer->getData('video_id');
                    $videoW = $layer->getData('video_width');
                    $videoH = $layer->getData('video_height');
                    $videoData = $layer->getData('video_data');
                    $videoArgs = isset($videoData['args']) ? $videoData['args'] : '';
                    $videoControl = isset($videoData['control']) ? (bool)$videoData['control'] : false;
                    $videoFullWidth = isset($videoData['fullwidth']) ? (bool)$videoData['fullwidth'] : false;
                    if ($videoFullWidth == 1){
                        $videoH = '100%';
                        $videoW = '100%';
                    }

                    switch ($videoType){
                        case 'youtube':
                            if (!$videoArgs) $videoArgs = 'hd=1&wmode=opaque&showinfo=0&rel=0';
                            $baseUrl = Mage::getUrl('', array('_type' => 'direct_link', '_nosid' => 1));
                            $videoArgs .= '&origin='.(strrpos($baseUrl, '/') == strlen($baseUrl)-1 ? substr($baseUrl, 0, strlen($baseUrl)-1) : $baseUrl);
                            $add_data = " data-ytid='{$videoId}' data-videowidth='{$videoW}' data-videoheight='{$videoH}' data-videoattributes='version=3&enablejsapi=1&html5=1&{$videoArgs}' data-videorate='1'";
                            $add_data .= $videoControl ? " data-videocontrols='none'" : " data-videocontrols='controls'";
                            break;
                        case 'vimeo':
                            if (!$videoArgs) $videoArgs = 'title=0&byline=0&portrait=0&api=1';
                            $add_data = " data-vimeoid='{$videoId}' data-videowidth='{$videoW}' data-videoheight='{$videoH}' data-videoattributes='{$videoArgs}'";
                            break;
                        case 'html5':
                            $urlPoster  = isset($videoData['urlPoster']) && $videoData['urlPoster'] ? (strpos($videoData['urlPoster'], 'http') === 0 ? $videoData['urlPoster'] : Mage::getBaseUrl('media').$videoData['urlPoster']) : '';
                            $urlMp4     = isset($videoData['urlMp4']) && $videoData['urlMp4'] ? (strpos($videoData['urlMp4'], 'http') === 0 ? $videoData['urlMp4'] : Mage::getBaseUrl('media').$videoData['urlMp4']) : '';
                            $urlWebm    = isset($videoData['urlWebm']) && $videoData['urlWebm'] ? (strpos($videoData['urlWebm'], 'http') === 0 ? $videoData['urlWebm'] : Mage::getBaseUrl('media').$videoData['urlWebm']) : '';
                            $urlOgv     = isset($videoData['urlOgv']) && $videoData['urlOgv'] ? (strpos($videoData['urlOgv'], 'http') === 0 ? $videoData['urlOgv'] : Mage::getBaseUrl('media').$videoData['urlOgv']) : '';
                            $add_data = " data-videowidth='{$videoW}' data-videoheight='{$videoH}'";
                            $cover = isset($videoData['cover']) ? (bool)$videoData['cover']: false;
                            if ($cover){
                                $add_data .= " data-forcecover='1'";
                                $ratio = isset($videoData['ratio']) ? $videoData['ratio'] : '16:9';
                                if ($ratio) $add_data .= " data-aspectratio='{$ratio}'";
                            }
                            $add_data .= $videoControl ? " data-videocontrols='none'" : " data-videocontrols='controls'";
                            if (!empty($urlPoster)) $add_data .= " data-videoposter='{$urlPoster}'";
                            if (!empty($urlMp4)) $add_data .= " data-videomp4='{$urlMp4}'";
                            if (!empty($urlWebm)) $add_data .= " data-videowebm='{$urlWebm}'";
                            if (!empty($urlOgv)) $add_data .= " data-videoogv='{$urlOgv}'";
                            if (!empty($videoData['preload'])) $add_data .= " data-videopreload='{$videoData['preload']}'";
                            if (!empty($videoData['loop'])) $add_data .= " data-videoloop='loop'";
                            break;
                    }
                    if (isset($videoData['autoplay']) && $videoData['autoplay'] == 1){
                        $htmlVideo .= " data-autoplay='true' ";
                    }
                    if (isset($videoData['nextslide']) && $videoData['nextslide'] == 1){
                        $htmlVideo .= " data-nextslideatend='true' ";
                    }
                    if (isset($videoData['autoplayonlyfirsttime']) && $videoData['autoplayonlyfirsttime'] == 1){
                        $htmlVideo .= " data-autoplayonlyfirsttime='true' ";
                    }
                    if (isset($videoData['force_rewind']) && $videoData['force_rewind'] == 1){
                        $htmlVideo .= " data-forcerewind='on' ";
                    }
                    if (isset($videoData['mute']) && $videoData['mute'] == 1){
                        $htmlVideo .= " data-volume='mute' ";
                    }
                    if (isset($videoData['disable_on_mobile']) && $videoData['disable_on_mobile'] == 1){
                        $htmlVideo .= " data-disablevideoonmobile='1' ";
                    }
                    break;

            }

            if ($doRotation){
                $rotation .= "-moz-transform:rotate({$rotation2D}deg); -ms-transform:rotate({$rotation2D}deg); -o-transform:rotate({$rotation2D}deg); -webkit-transform:rotate({$rotation2D}deg); transform:rotate({$rotation2D}deg);";
                $rotation .= "-moz-transform-origin:{$originX2D}% {$originY2D}%; -ms-transform-origin:{$originX2D}% {$originY2D}%; -o-transform-origin:{$originX2D}% {$originY2D}%; -webkit-transform-origin:{$originX2D}% {$originY2D}%; transform-origin:{$originX2D}% {$originY2D}%;";
            }

            $endTime = $layer->getData('endtime');
            $endWithSlide = $layer->getData('endWithSlide');

            $htmlEnd = '';
            if ($endTime && !$endWithSlide){
                $htmlEnd .= "data-end='{$endTime}' ";
            }
            if ($endSpeed = $layer->getData('endspeed')){
                $htmlEnd .= "data-endspeed='{$endSpeed}' ";
            }
            if ($endEasing = $layer->getData('endeasing')){
                if ($endEasing != 'nothing') $htmlEnd .= "data-endeasing='{$endEasing}' ";
            }
            $customout = '';
            if ($endAnimation = $layer->getData('endanimation')){
                if ($endAnimation == 'fade') $endAnimation = 'tp-fade';

                if (array_key_exists($endAnimation, $customAnimations)){
                    $customEndAnimData = $this->_renderCustomAnimData($customAnimations[$endAnimation]);
                    $customout = "data-customout='{$customEndAnimData}' ";
                    $endAnimation = 'customout';
                }

                if (strpos($endAnimation, 'custom-') !== false) $endAnimation = "";
                if ($endAnimation != 'auto') $oClass .= " {$endAnimation} ";
            }

            $htmlLink = '';
            if ($layer->getData('link_enable') == 'true'){
                $slideLink = $layer->getData('link_slide');
                if ($slideLink && $slideLink != 'nothing' && $slideLink != 'scroll_under'){
                    $htmlLink .= "data-linktoslide='{$slideLink}' ";
                }
                if ($slideLink == 'scroll_under'){
                    $oClass .= ' tp-scrollbelowslider';
                    if ($scrollUnderOffset = $layer->getData('scrollunder_offset')){
                        $htmlLink .= "data-scrolloffset='{$scrollUnderOffset}' ";
                    }
                }
            }

            $htmlHidden = '';
            $layerHidden = $layer->getData('hiddenunder');
            if ($layerHidden == true || $layerHidden == '1'){
                $htmlHidden .= "data-captionhidden='on' ";
            }

            $htmlParams = $add_data.$htmlEnd.$htmlLink.$htmlVideo.$htmlHidden.$customin.$customout;

            $alignHor = $layer->getData('align_hor');
            $alignVert = $layer->getData('align_vert');
            $htmlPosX = '';
            $htmlPosY = '';
            switch ($alignHor){
                case 'left':
                default:
                    $htmlPosX .= "data-x='{$left}' ";
                    break;
                case 'center':
                    $htmlPosX .= "data-x='center' data-hoffset='{$left}' ";
                    break;
                case 'right':
                    $left = (int)$left * -1;
                    $htmlPosX .= "data-x='right' data-hoffset='{$left}' ";
                    break;
            }
            switch ($alignVert){
                case 'top':
                default:
                    $htmlPosY .= "data-y='{$top}' ";
                    break;
                case 'middle':
                    $htmlPosY .= "data-y='center' data-voffset='{$top}' ";
                    break;
                case 'bottom':
                    $top = (int)$top * -1;
                    $htmlPosY .= "data-y='bottom' data-voffset='{$top}' ";
                    break;
            }

            $htmlCorner = '';
            if ($type == 'text'){
                $cLeft = $layer->getData('corner_left');
                $cRight = $layer->getData('corner_right');
                switch ($cLeft){
                    case 'curved':
                        $htmlCorner .= "<div class='frontcorner'></div>";
                        break;
                    case 'reversed':
                        $htmlCorner .= "<div class='frontcornertop'></div>";
                        break;
                }
                switch ($cRight){
                    case 'curved':
                        $htmlCorner .= "<div class='backcorner'></div>";
                        break;
                    case 'reversed':
                        $htmlCorner .= "<div class='backcornertop'></div>";
                        break;
                }
                if ($layer->getData('resizeme') == true){
                    $oClass .= ' tp-resizeme ';
                }
            }

            if ($videoFullWidth == 1){
                $htmlPosY = "data-y='0'";
                $htmlPosX = "data-x='0'";
                $oClass .= ' fullscreenvideo';
            }

            $useParallax = $slider->getData('use_parallax');
            $parallaxClass = '';
            if ($useParallax == 'on'){
                $slideLevel = $layer->getData('parallax_level');
                if ($slideLevel != '-'){
                    $parallaxClass .= "rs-parallaxlevel-{$slideLevel} ";
                }
            }

            $doLoop = $layer->getData('loop_animation');
            $loopData = '';
            $loopClass = '';
            if ($doLoop && $doLoop != 'none'){
                $loopClass .= "{$doLoop} ";
                switch ($doLoop){
                    case 'rs-pendulum':
                        $loopData .= "data-easing='{$this->_get($layer, 'loop_easing', 'Power3.easeInOut')}' ";
                        $loopData .= "data-startdeg='{$this->_get($layer, 'loop_startdeg', -20)}' ";
                        $loopData .= "data-enddeg='{$this->_get($layer, 'loop_enddeg', 20)}' ";
                        $loopData .= "data-speed='{$this->_get($layer, 'loop_speed', 2)}' ";
                        $loopData .= "data-origin='{$this->_get($layer, 'loop_xorigin', 50)}% {$this->_get($layer, 'loop_yorigin', 50)}%' ";
                        break;
                    case 'rs-slideloop':
                        $loopData .= "data-easing='{$this->_get($layer, 'loop_easing', 'Power3.easeInOut')}' ";
                        $loopData .= "data-speed='{$this->_get($layer, 'loop_speed', 1)}' ";
                        $loopData .= "data-xs='{$this->_get($layer, 'loop_xstart', 0)}' ";
                        $loopData .= "data-xe='{$this->_get($layer, 'loop_xend', 0)}' ";
                        $loopData .= "data-ys='{$this->_get($layer, 'loop_ystart', 0)}' ";
                        $loopData .= "data-ye='{$this->_get($layer, 'loop_yend', 0)}' ";
                        break;
                    case 'rs-pulse':
                        $loopData .= "data-easing='{$this->_get($layer, 'loop_easing', 'Power3.easeInOut')}' ";
                        $loopData .= "data-speed='{$this->_get($layer, 'loop_speed', 1)}' ";
                        $loopData .= "data-zoomstart='{$this->_get($layer, 'loop_zoomstart', 1)}' ";
                        $loopData .= "data-zoomend='{$this->_get($layer, 'loop_zoomend', 1)}' ";
                        break;
                    case 'rs-wave':
                        $loopData .= "data-speed='{$this->_get($layer, 'loop_speed', 1)}' ";
                        $loopData .= "data-angle='{$this->_get($layer, 'loop_angle', 1)}' ";
                        $loopData .= "data-radius='{$this->_get($layer, 'loop_radius', 10)}' ";
                        $loopData .= "data-origin='{$this->_get($layer, 'loop_xorigin', 50)}% {$this->_get($layer, 'loop_yorigin', 50)}%' ";
                        break;
                }
            }

            $output .= "<div {$ids} {$rel} {$title} class='{$oClass} {$parallaxClass} {$videoClass}' {$htmlPosX} {$htmlPosY} {$htmlParams} ";
            $output .= "data-speed='{$speed}' ";
            $output .= "data-start='{$time}' ";
            $output .= "data-easing='{$easing}' ";
            if ($type == 'text') {
                $output .= "data-splitin='{$splitIn}' ";
                $output .= "data-splitout='{$splitOut}' ";
            }
            $output .= "data-elementdelay='{$splitDelayIn}' ";
            $output .= "data-endelementdelay='{$splitDelayOut}' ";
            $output .= "style='z-index:{$zIndex};{$inlineStyles}'>";
            if ($doLoop && $doLoop != 'none'){
                $output .= "<div style='{$rotation}' class='tp-layer-inner-rotation {$loopClass} {$class}' {$loopData}>";
            }elseif ($rotation){
                $output .= "<div style='{$rotation}' class='tp-layer-inner-rotation {$class}'>";
            }
            $output .= $html;
            $output .= $htmlCorner;
            if (($doLoop && $doLoop != 'none') || $rotation){
                $output .= '</div>';
            }
            $output .= "</div>";
            $zIndex++;
        }
        return $output;
    }

    protected function _get($object, $property, $default=null){
        return $object->getData($property) ? $object->getData($property) : $default;
    }

    public function renderJs($slider){
        $delay = (int)$slider->getData('delay');
        $startwidth = (int)$this->_get($slider, 'width', 900);
        $startheight = (int)$this->_get($slider, 'height', 300);
        $minHeight = (int)$this->_get($slider, 'min_height', 0);
        $type = $slider->getData('layout');

        $fullWidth = $type == 'fullwidth' ? 'on' : 'off';
        $fullScreen = 'off';
        if ($type == 'fullscreen'){
            $fullScreen = 'on';
            $fullWidth = 'off';
        }

        $spinner = (int)$slider->getData('use_spinner');
        $spinnerColor = $slider->getData('spinner_color') ? $slider->getData('spinner_color') : '#FFFFFF';

        $thumbAmount = (int)$slider->getData('thumb_amount');
        $thumbWidth = (int)$slider->getData('thumb_width');
        $thumbHeight = (int)$slider->getData('thumb_height');
        if ($thumbAmount > $this->numSlides) $thumbAmount = $this->numSlides;

        $stopSlider = $slider->getData('stop_slider');
        $stopAfterLoop = (int)$slider->getData('stop_after_loops');
        $stopAtSlide = (int)$slider->getData('stop_at_slide');
        if ($stopSlider == 'off'){
            $stopAfterLoop = -1;
            $stopAtSlide = -1;
        }

        $oneSlideLoop = $slider->getData('loop_slide') ? $slider->getData('loop_slide') : 'loop';
        if ($oneSlideLoop == 'noloop' && $this->oneSlide == true){
            $stopAfterLoop = 0;
            $stopAtSlide = 1;
        }

        $hideThumb = (int)$slider->getData('hide_thumbs');
        $hideThumb = $hideThumb < 10 ? 10 : $hideThumb;

        $alwayOn = $slider->getData('navigaion_always_on');
        if ($alwayOn == 'true') $hideThumb = 0;

        $hideSliderAtLimit = (int)$slider->getData('hide_slider_under');
        if (!empty($hideSliderAtLimit)) $hideSliderAtLimit++;

        $hideCaptionAtLimit = (int)$slider->getData('hide_defined_layers_under');
        if (!empty($hideCaptionAtLimit)) $hideCaptionAtLimit++;

        $hideAllCaptionAtLimit = (int)$slider->getData('hide_all_layers_under');
        if (!empty($hideAllCaptionAtLimit)) $hideAllCaptionAtLimit++;

        $startWithSlide = (int)$slider->getData('start_with_slide') - 1;
        $startWithSlide = $startWithSlide < 0 ? 0 : ($startWithSlide >= $this->numSlides ? 0 : $startWithSlide);

        $arrowType = $slider->getData('navigation_arrows');

        $hideThumbsOnMobile = $slider->getData('hide_thumbs_on_mobile');
        $hideThumbsDelayMobile = $slider->getData('hide_thumbs_delay_mobile') ? (int)$slider->getData('hide_thumbs_delay_mobile') : 1500;
        $hideBulletsOnMobile = $slider->getData('hide_bullets_on_mobile');
        $hideArrowsOnMobile = $slider->getData('hide_arrows_on_mobile');
        $hideThumbsUnderResolution = (int)$slider->getData('hide_thumbs_under_resolution');

        $timerBar = $slider->getData('show_timerbar') ? $slider->getData('show_timerbar') : 'top';
        $disableKenBurnOnMobile = $this->_get($slider, 'disable_kenburns_on_mobile', 'off');

        $swipeVelocity = $slider->getData('swipe_velocity') ? $slider->getData('swipe_velocity') : 0.7;
        $swipeMinTouches = $slider->getData('swipe_min_touches') ? (int)$slider->getData('swipe_min_touches') : 1;
        $swipeMaxTouches = $slider->getData('swipe_max_touches') ? (int)$slider->getData('swipe_max_touches') : 1;
        $dragBlockVertical = $slider->getData('drag_block_vertical') == 'on' ? 'true' : 'false';

        $useParallax = $slider->getData('use_parallax');
        $disableParallaxMobile = $this->_get($slider, 'disable_parallax_mobile', 'off');
        if ($useParallax == 'on'){
            $parallaxType = $this->_get($slider, 'parallax_type', 'mouse');
            $parallaxBgFreeze = $this->_get($slider, 'parallax_bg_freeze', 'off');
            $parallaxLevel[] = $this->_get($slider, 'parallax_level_1', 5);
            $parallaxLevel[] = $this->_get($slider, 'parallax_level_2', 10);
            $parallaxLevel[] = $this->_get($slider, 'parallax_level_3', 15);
            $parallaxLevel[] = $this->_get($slider, 'parallax_level_4', 20);
            $parallaxLevel[] = $this->_get($slider, 'parallax_level_5', 25);
            $parallaxLevel[] = $this->_get($slider, 'parallax_level_6', 30);
            $parallaxLevel[] = $this->_get($slider, 'parallax_level_7', 35);
            $parallaxLevel[] = $this->_get($slider, 'parallax_level_8', 40);
            $parallaxLevel[] = $this->_get($slider, 'parallax_level_9', 45);
            $parallaxLevel[] = $this->_get($slider, 'parallax_level_10', 50);
            $parallaxLevel = implode(',', $parallaxLevel);
        }

        $shadow = (int)$slider->getData("shadow_type");

        $autoHeight = $type == 'fullwidth' ? $slider->getData('auto_height') : 'off';
        $forceFullWidth = $type == 'fullwidth' || $type == 'fullscreen' ? $slider->getData('force_full_width') : 'off';
        $fullScreenAlignForce = $type == 'fullscreen' ? $slider->getData('full_screen_align_force') : 'off';
        $minFullScreenHeight = $type == 'fullscreen' ? (int)$slider->getData('fullscreen_min_height') : 0;
        $dottedOverlay = $slider->getData('background_dotted_overlay') ? $slider->getData('background_dotted_overlay') : 'none';

        $soloArrowLeftHOffset = (int)$slider->getData("leftarrow_offset_hor");
        $soloArrowLeftVOffset = (int)$slider->getData("leftarrow_offset_vert");

        $soloArrowRightHOffset = (int)$slider->getData("rightarrow_offset_hor");
        $soloArrowRightVOffset = (int)$slider->getData("rightarrow_offset_vert");

        $navigationHOffset = (int)$slider->getData("navigaion_offset_hor");
        $navigationVOffset = (int)$slider->getData("navigaion_offset_vert");

        $keyboardNavigation = $slider->getData('keyboard_navigation') ? $slider->getData('keyboard_navigation') : 'off';

        $html = "";
        $scripts = array();
        if ($slider->getData('using_jquery') == 'true'){
            $scripts[] = Mage::getBaseUrl('js').'am/extensions/jquery/jquery-1.9.1.min.js';
        }
        $html .= '<script type="text/javascript">delete window["console"];</script>';
        $scripts[] = Mage::getBaseUrl('js').'am/revslider/rs-plugin/js/jquery.themepunch.tools.min.js?v=4.6.0';
        $scripts[] = Mage::getBaseUrl('js').'am/revslider/rs-plugin/js/jquery.themepunch.revolution.min.js?v=4.6.0';
        foreach ($scripts as $script){
            $html .= "<script type='text/javascript' src='{$script}'></script>\r\n";
        }
        $styles = array();
        $styles[] = Mage::getBaseUrl('js').'am/revslider/rs-plugin/css/settings.css?v=4.6.0';
        $styles[] = $this->getUrl('revslider/index/getCssCaptions', array('id' => $slider->getId()));
        foreach ($styles as $style){
            $html .= "<link type='text/css' rel='stylesheet' href='{$style}'/>\r\n";
        }

        $html .= "<script type='text/javascript'>
        var setREVStartSize = function(){
            var	tpopt = new Object();
            tpopt.startwidth = {$startwidth};
            tpopt.startheight = {$startheight};
            tpopt.container = jQuery('#{$this->sliderHtmlId}');
            tpopt.fullScreen = '{$fullScreen}';
            tpopt.forceFullWidth = '{$this->_get($slider, 'force_full_width', 'off')}';

            tpopt.container.closest('.rev_slider_wrapper').css({height:tpopt.container.height()});tpopt.width=parseInt(tpopt.container.width(),0);tpopt.height=parseInt(tpopt.container.height(),0);tpopt.bw=tpopt.width/tpopt.startwidth;tpopt.bh=tpopt.height/tpopt.startheight;if(tpopt.bh>tpopt.bw)tpopt.bh=tpopt.bw;if(tpopt.bh<tpopt.bw)tpopt.bw=tpopt.bh;if(tpopt.bw<tpopt.bh)tpopt.bh=tpopt.bw;if(tpopt.bh>1){tpopt.bw=1;tpopt.bh=1}if(tpopt.bw>1){tpopt.bw=1;tpopt.bh=1}tpopt.height=Math.round(tpopt.startheight*(tpopt.width/tpopt.startwidth));if(tpopt.height>tpopt.startheight&&tpopt.autoHeight!='on')tpopt.height=tpopt.startheight;if(tpopt.fullScreen=='on'){tpopt.height=tpopt.bw*tpopt.startheight;var cow=tpopt.container.parent().width();var coh=jQuery(window).height();if(tpopt.fullScreenOffsetContainer!=undefined){try{var offcontainers=tpopt.fullScreenOffsetContainer.split(',');jQuery.each(offcontainers,function(e,t){coh=coh-jQuery(t).outerHeight(true);if(coh<tpopt.minFullScreenHeight)coh=tpopt.minFullScreenHeight})}catch(e){}}tpopt.container.parent().height(coh);tpopt.container.height(coh);tpopt.container.closest('.rev_slider_wrapper').height(coh);tpopt.container.closest('.forcefullwidth_wrapper_tp_banner').find('.tp-fullwidth-forcer').height(coh);tpopt.container.css({height:'100%'});tpopt.height=coh}else{tpopt.container.height(tpopt.height);tpopt.container.closest('.rev_slider_wrapper').height(tpopt.height);tpopt.container.closest('.forcefullwidth_wrapper_tp_banner').find('.tp-fullwidth-forcer').height(tpopt.height)};
        };

        setREVStartSize();
        var revapi{$slider->getId()};

        jQuery(document).ready(function(){
            revapi{$slider->getId()} = jQuery('#{$this->sliderHtmlId}').show().revolution({
                dottedOverlay: '{$dottedOverlay}',
                delay: {$delay},
                startwidth: {$startwidth},
                startheight: {$startheight},

                hideThumbs: {$hideThumb},
                thumbWidth: {$thumbWidth},
                thumbHeight: {$thumbHeight},
                thumbAmount: {$thumbAmount},
        ";
        if ($minHeight > 0){
            $html .= "
                minHeight: {$minHeight},
            ";
        }
        $html .= "
                simplifyAll: '{$this->_get($slider, 'simplify_ie8_ios4', 'off')}',
                navigationType: '{$slider->getData("navigaion_type")}',
                navigationArrows: '{$arrowType}',
                navigationStyle: '{$slider->getData("navigation_style")}',

                touchenabled: '{$slider->getData("touchenabled")}',
                onHoverStop: '{$slider->getData("stop_on_hover")}',
                nextSlideOnWindowFocus: '{$this->_get($slider, 'next_slide_on_window_focus', 'off')}',
        ";
        if ($slider->getData('touchenabled') == 'on') {
            $html .= "
                swipe_threshold: {$swipeVelocity},
                swipe_min_touches: {$swipeMinTouches},
                swipe_max_touches: {$swipeMaxTouches},
                drag_block_vertical: {$dragBlockVertical},
            ";
        }
        if ($useParallax == 'on'){
            $html .= "
                parallax: '{$parallaxType}',
                parallaxBgFreeze: '{$parallaxBgFreeze}',
                parallaxLevels: [{$parallaxLevel}],
            ";
        }
        if ($disableParallaxMobile == 'on'){
            $html .= "
                parallaxDisableOnMobile: 'on',
            ";
        }
        if ($disableKenBurnOnMobile == 'on'){
            $html .= "
                panZoomDisableOnMobile: 'on',
            ";
        }
        $html .= "
            spinner: 'spinner{$spinner}',
            keyboardNavigation: '{$keyboardNavigation}',

            navigationHAlign: '{$slider->getData("navigaion_align_hor")}',
            navigationVAlign: '{$slider->getData("navigaion_align_vert")}',
            navigationHOffset: {$navigationHOffset},
            navigationVOffset: {$navigationVOffset},

            soloArrowLeftHalign: '{$slider->getData("leftarrow_align_hor")}',
            soloArrowLeftValign: '{$slider->getData("leftarrow_align_vert")}',
            soloArrowLeftHOffset: {$soloArrowLeftHOffset},
            soloArrowLeftVOffset: {$soloArrowLeftVOffset},

            soloArrowRightHalign: '{$slider->getData("rightarrow_align_hor")}',
            soloArrowRightValign: '{$slider->getData("rightarrow_align_vert")}',
            soloArrowRightHOffset: {$soloArrowRightHOffset},
            soloArrowRightVOffset: {$soloArrowRightVOffset},

            shadow: {$shadow},
            fullWidth: '{$fullWidth}',
            fullScreen: '{$fullScreen}',

            stopLoop: '{$stopSlider}',
            stopAfterLoops: {$stopAfterLoop},
            stopAtSlide: {$stopAtSlide},

            shuffle: '{$slider->getData("shuffle")}',
        ";
        if ($type == 'fullwidth'){
            $html .= "
                autoHeight: '{$autoHeight}',
            ";
        }
        if ($type == 'fullwidth' || $type == 'fullscreen'){
            $html .= "
                forceFullWidth: '{$forceFullWidth}',
            ";
        }
        if ($timerBar == 'hide'){
            $html .= "
                hideTimerBar: 'on',
            ";
        }
        if ($hideThumbsOnMobile == 'off'){
            $html .= "
                hideNavDelayOnMobile: {$hideThumbsDelayMobile},
            ";
        }
        if ($type == 'fullscreen'){
            $html .= "
                fullScreenAlignForce: '{$fullScreenAlignForce}',
                minFullScreenHeight: {$minFullScreenHeight},
                fullScreenOffsetContainer: '{$slider->getData('fullscreen_offset_container')}',
                fullScreenOffset: '{$slider->getData('fullscreen_offset_size')}',
            ";
        }
        $html .= "
                hideThumbsOnMobile: '{$hideThumbsOnMobile}',
                hideBulletsOnMobile: '{$hideBulletsOnMobile}',
                hideArrowsOnMobile: '{$hideArrowsOnMobile}',
                hideThumbsUnderResolution: {$hideThumbsUnderResolution},

                hideSliderAtLimit: {$hideSliderAtLimit},
                hideCaptionAtLimit: {$hideCaptionAtLimit},
                hideAllCaptionAtLilmit: {$hideAllCaptionAtLimit},
                startWithSlide: {$startWithSlide}
            });
        });
        {$slider->getData('scripts')}</script>";

        switch ($spinner){
            case 1:
            case 2:
                $html .= "<style type='text/css'>";
                $html .= "#{$this->sliderHtmlIdWrapper} .tp-loader.spinner{$spinner}{background-color:#{$this->_cleanColor($spinnerColor)} !important;}";
                $html .= "</style>";
                break;
            case 3:
            case 4:
                $html .= "<style type='text/css'>";
                $html .= "#{$this->sliderHtmlIdWrapper} .tp-loader.spinner{$spinner} div{background-color:#{$this->_cleanColor($spinnerColor)} !important;}";
                $html .= "</style>";
                break;
        }

        return $html;
    }
}
