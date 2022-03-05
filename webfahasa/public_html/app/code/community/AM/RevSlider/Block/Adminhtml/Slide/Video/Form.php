<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Slide_Video_Form extends Mage_Adminhtml_Block_Widget_Form{
    protected function _prepareForm(){
        $form = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'method'    => 'post'
        ));

        $container = $form->addFieldset('video_form_container', array(
            'class'     => 'no-spacing'
        ));

        $right = $container->addFieldset('right_fieldset', array(
            'class'     => 'no-spacing right-fieldset',
        ));
        $view = $right->addFieldset('video_view_fieldset', array(
            'class'     => 'popup-half-fieldset',
            'legend'    => $this->helper('revslider')->__('Video Preview')
        ));
        $view->addField('video_title', 'text', array(
            'label'     => $this->helper('revslider')->__('Video Title')
        ));
        $view->addField('video_thumb', 'text', array(
            'label'     => $this->helper('revslider')->__('Video Thumb')
        ));
        $form->getElement('video_thumb')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_thumb')
        );

        $left = $container->addFieldset('left_fieldset', array(
            'class'     => 'no-spacing left-fieldset'
        ));
        $fieldset = $left->addFieldset('video_info_fieldset', array(
            'class'     => 'popup-half-fieldset',
            'legend'    => $this->helper('revslider')->__('Video Settings')
        ));
        if ($serial = $this->getRequest()->getParam('serial')){
            $fieldset->addField('video_serial', 'hidden', array(
                'value'     => $serial
            ));
        }
        $v = $fieldset->addField('video_type', 'select', array(
            'name'      => 'video_type',
            'label'     => $this->helper('revslider')->__('Select video service'),
            'required'  => true,
            'values'    => $this->getVideoServices(),
            'onchange'  => 'revLayer.onChangeVideoType(this)'
        ));
        $v1 = $fieldset->addField('video_src', 'text', array(
            'name'      => 'video_src',
            'label'     => $this->helper('revslider')->__('Enter video ID or URL'),
            'required'  => true,
            'note'      => $this->helper('revslider')->__('Ex: T8--OggjJKQ or 30300114')
        ));
        $v2 = $fieldset->addField('video_poster', 'text', array(
            'name'      => 'video_poster',
            'label'     => $this->helper('revslider')->__('Poster Image Url'),
            'note'      => $this->helper('revslider')->__('Ex: http://video-js.zencoder.com/oceans-clip.png'),
            'onchange'  => 'revLayer.onChangeVideoPoster(this)'
        ));
        $form->getElement('video_poster')->setRenderer(
            $this->getLayout()->createBlock('amext/adminhtml_widget_form_element_browser', '', array(
                'element' => $v2
            ))
        );
        $v3 = $fieldset->addField('video_mp4', 'text', array(
            'name'      => 'video_mp4',
            'label'     => $this->helper('revslider')->__('Video MP4 Url'),
            'note'      => $this->helper('revslider')->__('Ex: http://video-js.zencoder.com/oceans-clip.mp4')
        ));
        $form->getElement('video_mp4')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_browser', '', array(
                'element' => $v3
            ))
        );
        $v4 = $fieldset->addField('video_webm', 'text', array(
            'name'      => 'video_webm',
            'label'     => $this->helper('revslider')->__('Video WEBM Url'),
            'note'      => $this->helper('revslider')->__('Ex: http://video-js.zencoder.com/oceans-clip.webm')
        ));
        $form->getElement('video_webm')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_browser', '', array(
                'element' => $v4
            ))
        );
        $v5 = $fieldset->addField('video_ogv', 'text', array(
            'name'      => 'video_ogv',
            'label'     => $this->helper('revslider')->__('Video OGV Url'),
            'note'      => $this->helper('revslider')->__('Ex: http://video-js.zencoder.com/oceans-clip.ogv')
        ));
        $form->getElement('video_ogv')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_browser', '', array(
                'element' => $v5
            ))
        );
        $s = $fieldset->addField('video_search', 'text', array());
        $form->getElement('video_search')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_search', '', array(
                'element' => $s
            ))
        );
        $fieldset->addField('video_width', 'text', array(
            'name'      => 'video_width',
            'label'     => $this->helper('revslider')->__('Video Width'),
            'required'  => true,
            'class'     => 'validate-number',
            'value'     => '320'
        ));
        $fieldset->addField('video_height', 'text', array(
            'name'      => 'video_height',
            'label'     => $this->helper('revslider')->__('Video Height'),
            'required'  => true,
            'class'     => 'validate-number',
            'value'     => '240'
        ));
        $fieldset->addField('video_fullwidth', 'checkbox', array(
            'name'      => 'video_fullwidth',
            'label'     => $this->helper('revslider')->__('Full Width'),
            'onchange'  => 'revLayer.onChangeVideoFullWidth(this)'
        ));
        $fieldset->addField('video_cover', 'checkbox', array(
            'name'      => 'video_cover',
            'label'     => $this->helper('revslider')->__('Cover'),
            'onchange'  => 'revLayer.onChangeVideoCover(this)'
        ));
        $loop = $fieldset->addField('video_loop', 'select', array(
            'name'      => 'video_loop',
            'label'     => $this->helper('revslider')->__('Loop Video'),
            'values'    => $this->getLoopOptions()
        ));
        $fieldset->addField('video_control', 'checkbox', array(
            'name'      => 'video_control',
            'label'     => $this->helper('revslider')->__('Hide Controls'),
        ));
        $a = $fieldset->addField('video_autoplay', 'checkbox', array(
            'name'      => 'video_autoplay',
            'label'     => $this->helper('revslider')->__('Autoplay')
        ));
        $a1 = $fieldset->addField('video_autoplayonlyfirsttime', 'checkbox', array(
            'name'      => 'video_autoplay_first_time',
            'label'     => $this->helper('revslider')->__('Autoplay Only First Time'),
        ));
        $fieldset->addField('video_nextslide', 'checkbox', array(
            'name'      => 'video_nextslide',
            'label'     => $this->helper('revslider')->__('Next Slide On End')
        ));
        $fieldset->addField('video_force_rewind', 'checkbox', array(
            'name'      => 'video_force_rewind',
            'label'     => $this->helper('revslider')->__('Force Rewind')
        ));
        $fieldset->addField('video_mute', 'checkbox', array(
            'name'      => 'video_mute',
            'label'     => $this->helper('revslider')->__('Mute')
        ));
        $fieldset->addField('video_disable_on_mobile', 'checkbox', array(
            'name'      => 'video_disable_on_mobile',
            'label'     => $this->helper('revslider')->__('Disable Mobile')
        ));
        $preload = $fieldset->addField('video_preload', 'select', array(
            'name'      => 'video_preload',
            'label'     => $this->helper('revslider')->__('Video Preload'),
            'values'    => $this->getPreloadOptions()
        ));
        $fieldset->addField('video_args', 'text', array(
            'name'      => 'video_args',
            'label'     => $this->helper('revslider')->__('Arguments')
        ));

        $form->setUseContainer(true);
        $this->setForm($form);
        $this->setChild('form_after', $this->getLayout()->createBlock('amext/adminhtml_widget_form_element_dependence')
            ->addFieldMap($v->getHtmlId(), $v->getName())
            ->addFieldMap($v1->getHtmlId(), $v1->getName())
            ->addFieldMap($v2->getHtmlId(), $v2->getName())
            ->addFieldMap($v3->getHtmlId(), $v3->getName())
            ->addFieldMap($v4->getHtmlId(), $v4->getName())
            ->addFieldMap($v5->getHtmlId(), $v5->getName())
            ->addFieldMap($s->getHtmlId(), $s->getName())
            ->addFieldMap($a->getHtmlId(), $a->getName())
            ->addFieldMap($a1->getHtmlId(), $a1->getName())
            ->addFieldMap($loop->getHtmlId(), $loop->getName())
            ->addFieldMap($preload->getHtmlId(), $preload->getName())
            ->addFieldDependence($v1->getName(), $v->getName(), array('youtube', 'vimeo'))
            ->addFieldDependence($s->getName(), $v->getName(), array('youtube', 'vimeo'))
            ->addFieldDependence($v2->getName(), $v->getName(), 'html5')
            ->addFieldDependence($v3->getName(), $v->getName(), 'html5')
            ->addFieldDependence($v4->getName(), $v->getName(), 'html5')
            ->addFieldDependence($v5->getName(), $v->getName(), 'html5')
            ->addFieldDependence($loop->getName(), $v->getName(), 'html5')
            ->addFieldDependence($preload->getName(), $v->getName(), 'html5')
            ->addFieldDependence($a1->getName(), $a->getName(), true)
        );

        return parent::_prepareForm();
    }

    protected function getVideoServices(){
        return array(
            array('value' => 'youtube', 'label' => $this->helper('revslider')->__('Youtube')),
            array('value' => 'vimeo', 'label' => $this->helper('revslider')->__('Vimeo')),
            array('value' => 'html5', 'label' => $this->helper('revslider')->__('HTML5'))
        );
    }

    protected function getLoopOptions(){
        return array(
            array('value' => 'none', 'label' => $this->helper('revslider')->__('Disable')),
            array('value' => 'loop', 'label' => $this->helper('revslider')->__('Loop')),
            array('value' => 'loopandnoslidestop', 'label' => $this->helper('revslider')->__('Loop, Slide does not stop'))
        );
    }

    protected function getPreloadOptions(){
        return array(
            array('value' => 'auto', 'label' => $this->helper('revslider')->__('Auto')),
            array('value' => 'none', 'label' => $this->helper('revslider')->__('Disable')),
            array('value' => 'metadata', 'label' => $this->helper('revslider')->__('Metadata'))
        );
    }
}
