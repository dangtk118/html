<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Slider_Edit_Tab_Loop
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    public function getTabLabel(){
        return '<i class="eg-icon-chart-bar"></i>'.Mage::helper('revslider')->__('Loop and Progress');
    }

    public function getTabTitle(){
        return Mage::helper('revslider')->__('Loop and Progress');
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
        $fieldset = $form->addFieldset('general_fieldset', array(
            'legend' => Mage::helper('revslider')->__('Loop and Progress')
        ));

        $stop = $fieldset->addField('stop_slider', 'select', array(
            'name'      => 'stop_slider',
            'label'     => Mage::helper('revslider')->__('Stop Slider'),
            'title'     => Mage::helper('revslider')->__('Stop Slider'),
            'values'    => $model->getOnOffOptions(),
            'note'      => Mage::helper('revslider')->__('On / Off to stop slider after some amount of loops / slides')
        ));
        $stop1 = $fieldset->addField('stop_after_loops', 'text', array(
            'name'      => 'stop_after_loops',
            'label'     => Mage::helper('revslider')->__('Stop After Loops'),
            'title'     => Mage::helper('revslider')->__('Stop After Loops'),
            'class'     => 'validate-number',
            'note'      => Mage::helper('revslider')->__('Stop the slider after certain amount of loops. 0 related to the first loop'),
            'value'     => $model->getData('stop_after_loops') ? $model->getData('stop_after_loops') : 0
        ));
        $stop2 = $fieldset->addField('stop_at_slide', 'text', array(
            'name'      => 'stop_at_slide',
            'label'     => Mage::helper('revslider')->__('Stop At Slide'),
            'title'     => Mage::helper('revslider')->__('Stop At Slide'),
            'class'     => 'validate-number',
            'note'      => Mage::helper('revslider')->__('Stop the slider at the given slide. Default: 2'),
            'value'     => $model->getData('stop_at_slide') ? $model->getData('stop_at_slide') : 2
        ));
        $fieldset->addField('show_timerbar', 'select', array(
            'name'      => 'show_timerbar',
            'label'     => Mage::helper('revslider')->__('Show Progressbar'),
            'title'     => Mage::helper('revslider')->__('Show Progressbar'),
            'values'    => $model->getShadowLineOptions(),
            'note'      => Mage::helper('revslider')->__('Show running timer line')
        ));
        $fieldset->addField('loop_slide', 'select', array(
            'name'      => 'loop_slide',
            'label'     => Mage::helper('revslider')->__('Loop Single Slide'),
            'title'     => Mage::helper('revslider')->__('Loop Single Slide'),
            'values'    => $model->getOnOffOptions(),
            'value'     => $model->getData('loop_slide') ? $model->getData('loop_slide') : 'on',
            'note'      => Mage::helper('revslider')->__('If only one Slide is in the Slider, you can choose wether the Slide should loop or if it should stop.')
        ));

        $this->setForm($form);
        if ($model->getId()) $form->setValues($model->getData());
        $this->setChild('form_after', $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
            ->addFieldMap($stop->getHtmlId(), $stop->getName())
            ->addFieldMap($stop1->getHtmlId(), $stop1->getName())
            ->addFieldMap($stop2->getHtmlId(), $stop2->getName())
            ->addFieldDependence($stop1->getName(), $stop->getName(), 'on')
            ->addFieldDependence($stop2->getName(), $stop->getName(), 'on')
        );

        return parent::_prepareForm();
    }
}
