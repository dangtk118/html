<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Slider_Edit_Tab_Api
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    public function getTabLabel(){
        return '<i class="eg-icon-tools"></i>'.Mage::helper('revslider')->__('API Functions');
    }

    public function getTabTitle(){
        return Mage::helper('revslider')->__('API Functions');
    }

    public function canShowTab(){
        return true;
    }

    public function isHidden(){
        return false;
    }

    public function _prepareForm(){
        /* @var $slider MT_RevSlider_Model_Slider */
        $model = Mage::registry('revslider');
        $form = new Varien_Data_Form();

        $fieldset1 = $form->addFieldset('method_fieldset', array(
            'legend' => Mage::helper('revslider')->__('API Methods')
        ));
        $fieldset1->addField('api_pause', 'text', array(
            'label'     => Mage::helper('revslider')->__('Pause Slider'),
            'title'     => Mage::helper('revslider')->__('Pause Slider'),
            'readonly'  => true,
            'value'     => $model->getId() ? sprintf('revapi%d.revpause();', $model->getId()) : '',
            'note'      => !$model->getId() ? Mage::helper('revslider')->__('Save to see this value') : ''
        ));
        $fieldset1->addField('api_resume', 'text', array(
            'label'     => Mage::helper('revslider')->__('Resume Slider'),
            'title'     => Mage::helper('revslider')->__('Resume Slider'),
            'readonly'  => true,
            'value'     => $model->getId() ? sprintf('revapi%d.revresume();', $model->getId()) : '',
            'note'      => !$model->getId() ? Mage::helper('revslider')->__('Save to see this value') : ''
        ));
        $fieldset1->addField('api_prev', 'text', array(
            'label'     => Mage::helper('revslider')->__('Previous Slide'),
            'title'     => Mage::helper('revslider')->__('Previous Slide'),
            'readonly'  => true,
            'value'     => $model->getId() ? sprintf('revapi%d.revprev();', $model->getId()) : '',
            'note'      => !$model->getId() ? Mage::helper('revslider')->__('Save to see this value') : ''
        ));
        $fieldset1->addField('api_next', 'text', array(
            'label'     => Mage::helper('revslider')->__('Next Slide'),
            'title'     => Mage::helper('revslider')->__('Next Slide'),
            'readonly'  => true,
            'value'     => $model->getId() ? sprintf('revapi%d.revnext();', $model->getId()) : '',
            'note'      => !$model->getId() ? Mage::helper('revslider')->__('Save to see this value') : ''
        ));
        $fieldset1->addField('api_goto', 'text', array(
            'label'     => Mage::helper('revslider')->__('Go To Slide'),
            'title'     => Mage::helper('revslider')->__('Go To Slide'),
            'readonly'  => true,
            'value'     => $model->getId() ? sprintf('revapi%d.revshowslide(number);', $model->getId()) : '',
            'note'      => !$model->getId() ? Mage::helper('revslider')->__('Save to see this value') : ''
        ));
        $fieldset1->addField('api_length', 'text', array(
            'label'     => Mage::helper('revslider')->__('Get Num Slides'),
            'title'     => Mage::helper('revslider')->__('Get Num Slides'),
            'readonly'  => true,
            'value'     => $model->getId() ? sprintf('revapi%d.revmaxslide();', $model->getId()) : '',
            'note'      => !$model->getId() ? Mage::helper('revslider')->__('Save to see this value') : ''
        ));
        $fieldset1->addField('api_current', 'text', array(
            'label'     => Mage::helper('revslider')->__('Get Current Slide Number'),
            'title'     => Mage::helper('revslider')->__('Get Current Slide Number'),
            'readonly'  => true,
            'value'     => $model->getId() ? sprintf('revapi%d.revcurrentslide();', $model->getId()) : '',
            'note'      => !$model->getId() ? Mage::helper('revslider')->__('Save to see this value') : ''
        ));
        $fieldset1->addField('api_last', 'text', array(
            'label'     => Mage::helper('revslider')->__('Get Last Playing Slide Number'),
            'title'     => Mage::helper('revslider')->__('Get Last Playing Slide Number'),
            'readonly'  => true,
            'value'     => $model->getId() ? sprintf('revapi%d.revlastslide();', $model->getId()) : '',
            'note'      => !$model->getId() ? Mage::helper('revslider')->__('Save to see this value') : ''
        ));
        $fieldset1->addField('api_scroll', 'text', array(
            'label'     => Mage::helper('revslider')->__('External Scroll'),
            'title'     => Mage::helper('revslider')->__('External Scroll'),
            'readonly'  => true,
            'value'     => $model->getId() ? sprintf('revapi%d.revscroll(offset);', $model->getId()) : '',
            'note'      => !$model->getId() ? Mage::helper('revslider')->__('Save to see this value') : ''
        ));
        $fieldset1->addField('api_refresh', 'text', array(
            'label'     => Mage::helper('revslider')->__('Redraw Slider'),
            'title'     => Mage::helper('revslider')->__('Redraw Slider'),
            'readonly'  => true,
            'value'     => $model->getId() ? sprintf('revapi%d.revredraw();', $model->getId()) : '',
            'note'      => !$model->getId() ? Mage::helper('revslider')->__('Save to see this value') : ''
        ));

        $fieldset2 = $form->addFieldset('event_fieldset', array(
            'legend' => Mage::helper('revslider')->__('API Events')
        ));
        $textarea = $fieldset2->addField('api_events', 'text', array(
            'label'     => Mage::helper('revslider')->__('API Events'),
            'title'     => Mage::helper('revslider')->__('API Events'),
            'readonly'  => true,
            'value'     => $model->getId() ? str_replace('%d', $model->getId(), '
revapi%d.bind("revolution.slide.onloaded", function (event) {
	//alert("slider loaded");
});

revapi%d.bind("revolution.slide.onchange", function (event, data) {
	//alert("slide changed to: " + data.slideIndex);
	//data.slideIndex is the index of the li container in this Slider
	//data.slide is the current slide jQuery object (the li element)
});

revapi%d.bind("revolution.slide.onpause", function (event, data) {
	//alert("timer paused");
});

revapi%d.bind("revolution.slide.onresume", function (event, data) {
	//alert("timer resume");
});

revapi%d.bind("revolution.slide.onvideoplay", function (event, data) {
	//alert("video play");
});

revapi%d.bind("revolution.slide.onvideostop", function (event, data) {
	//alert("video stopped");
});

revapi%d.bind("revolution.slide.onstop", function (event, data) {
	//alert("slider stopped");
});

revapi%d.bind("revolution.slide.onbeforeswap", function (event) {
	//alert("before swap");
});

revapi%d.bind("revolution.slide.onafterswap", function (event) {
	//alert("after swap");
});') : '',
            'note'      => $model->getId() ? Mage::helper('revslider')->__('Copy and Paste the Below listed API Functions into your jQuery Functions for Revslider Event Listening') : Mage::helper('revslider')->__('Save to see this value')
        ));
        $textarea->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_textarea')
        );

        $this->setForm($form);
        return parent::_prepareForm();
    }
}
