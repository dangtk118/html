<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2013 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */
class AM_RevSlider_Block_Adminhtml_Slider_Edit_Tab_Single
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    public function getTabLabel(){
        return '<i class="eg-icon-male"></i>'.Mage::helper('revslider')->__('Single Slide');
    }

    public function getTabTitle(){
        return Mage::helper('revslider')->__('Single Slide');
    }

    public function canShowTab(){
        return true;
    }

    public function isHidden(){
        return false;
    }

    public function _prepareForm(){
        /* @var $model AM_RevSlider_Model_Slider */
        $model = Mage::registry('revslider');
        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('general_fieldset', array('legend' => Mage::helper('revslider')->__('Single Slide')));

        $fieldset->addField('loop_slide', 'select', array(
            'name'      => 'loop_slide',
            'label'     => Mage::helper('revslider')->__('Loop Slide'),
            'title'     => Mage::helper('revslider')->__('Loop Slide'),
            'values'    => $model->getOnOffOptions(),
            'value'     => $model->getData('loop_slide') ? $model->getData('loop_slide') : 'on',
            'note'      => Mage::helper('revslider')->__('If only one Slide is in the Slider, you can choose wether the Slide should loop or if it should stop.')
        ));

        $this->setForm($form);
        if ($model->getId()) $form->setValues($model->getData());

        return parent::_prepareForm();
    }
}
