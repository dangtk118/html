<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Slider_Edit_Tab_General
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    public function getTabLabel(){
        return '<i class="eg-icon-cog-alt"></i>'.Mage::helper('revslider')->__('General Settings');
    }

    public function getTabTitle(){
        return Mage::helper('revslider')->__('General Settings');
    }

    public function canShowTab(){
        return true;
    }

    public function isHidden(){
        return false;
    }

    public function _prepareForm(){
        $model = Mage::registry('revslider');
        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('general_fieldset', array('legend' => Mage::helper('revslider')->__('General Settings')));

        $fieldset->addField('delay', 'text', array(
            'name'      => 'delay',
            'label'     => Mage::helper('revslider')->__('Delay'),
            'title'     => Mage::helper('revslider')->__('Delay'),
            'class'     => 'validate-number',
            'note'      => Mage::helper('revslider')->__('The time one slide stays on the screen in Milliseconds'),
            'value'     => $model->getData('delay') ? $model->getData('delay') : 9000
        ));
        $fieldset->addField('shuffle', 'select', array(
            'name'      => 'shuffle',
            'label'     => Mage::helper('revslider')->__('Shuffle Mode'),
            'title'     => Mage::helper('revslider')->__('Shuffle Mode'),
            'values'    => $model->getOnOffOptions(),
            'note'      => Mage::helper('revslider')->__('Turn Shuffle Mode on and off! Will be randomized only once at the start')
        ));
        $fieldset->addField('lazy_load', 'select', array(
            'name'      => 'lazy_load',
            'label'     => Mage::helper('revslider')->__('Lazy Load'),
            'title'     => Mage::helper('revslider')->__('Lazy Load'),
            'values'    => $model->getOnOffOptions(),
            'note'      => Mage::helper('revslider')->__('The lazy load means that the images will be loaded by demand, it speeds the loading of the slider')
        ));
        $fieldset->addField('next_slide_on_window_focus', 'select', array(
            'name'      => 'next_slide_on_window_focus',
            'label'     => Mage::helper('revslider')->__('Next Slide on Focus'),
            'title'     => Mage::helper('revslider')->__('Next Slide on Focus'),
            'values'    => $model->getOnOffOptions(),
            'note'      => Mage::helper('revslider')->__('Enabling this will move to the next slide if the Slider gets into focus if the user switched between tabs')
        ));
        $fieldset->addField('simplify_ie8_ios4', 'select', array(
            'name'      => 'simplify_ie8_ios4',
            'label'     => Mage::helper('revslider')->__('Simplify on IOS4/IE8'),
            'title'     => Mage::helper('revslider')->__('Simplify on IOS4/IE8'),
            'values'    => $model->getOnOffOptions(),
            'note'      => Mage::helper('revslider')->__('Simplyfies the Slider on IOS4 and IE8')
        ));

        $this->setForm($form);
        if ($model->getId()) $form->setValues($model->getData());

        return parent::_prepareForm();
    }
}
