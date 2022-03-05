<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Slider_Edit_Tab_Spinner
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    public function getTabLabel(){
        return '<i class="eg-icon-back-in-time"></i>'.Mage::helper('revslider')->__('Spinner');
    }

    public function getTabTitle(){
        return Mage::helper('revslider')->__('Spinner');
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
        $fieldset = $form->addFieldset('spinner_fieldset', array('legend' => Mage::helper('revslider')->__('Spinner')));

        $fieldset->addField('spinner_preview', 'text', array(
            'label'     => Mage::helper('revslider')->__('Spinner Preview')
        ));
        $form->getElement('spinner_preview')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_spinner')
        );
        $a = $fieldset->addField('use_spinner', 'select', array(
            'name'      => 'use_spinner',
            'label'     => Mage::helper('revslider')->__('Choose Spinner'),
            'title'     => Mage::helper('revslider')->__('Choose Spinner'),
            'note'      => Mage::helper('revslider')->__('Select a Spinner for your Slider'),
            'onchange'  => 'onChangeSpinner()',
            'values'    => $model->getSpinners()
        ));
        $b = $fieldset->addField('spinner_color', 'text', array(
            'name'      => 'spinner_color',
            'label'     => Mage::helper('revslider')->__('Spinner Color'),
            'class'     => 'color',
            'onchange'  => 'onChangeSpinner()',
            'title'     => Mage::helper('revslider')->__('Spinner Color'),
            'note'      => Mage::helper('revslider')->__('The Color the Spinner will be shown in')
        ));

        $this->setForm($form);
        if ($model->getId()) $form->setValues($model->getData());

        if (version_compare(Mage::getVersion(), '1.7.0.0') < 0){
            $dependenceElement = $this->getLayout()->createBlock('amext/adminhtml_widget_form_element_dependence');
        }else{
            $dependenceElement = $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence');
        }
        $this->setChild('form_after', $dependenceElement
            ->addFieldMap($a->getHtmlId(), $a->getName())
            ->addFieldMap($b->getHtmlId(), $b->getName())
            ->addFieldDependence($b->getName(), $a->getName(), array('1', '2', '3', '4'))
        );

        return parent::_prepareForm();
    }
}
