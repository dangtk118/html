<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Slider_Edit_Tab_Touch
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    public function getTabLabel(){
        return '<i class="eg-icon-up-hand"></i>'.Mage::helper('revslider')->__('Mobile Touch');
    }

    public function getTabTitle(){
        return Mage::helper('revslider')->__('Mobile Touch');
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
        $fieldset = $form->addFieldset('general_fieldset', array('legend' => Mage::helper('revslider')->__('Mobile Touch')));

        $f = $fieldset->addField('touchenabled', 'select', array(
            'name'      => 'touchenabled',
            'label'     => Mage::helper('revslider')->__('Touch Enabled'),
            'title'     => Mage::helper('revslider')->__('Touch Enabled'),
            'values'    => $model->getOnOffOptions(),
            'value'     => $model->getData('touchenabled') ? $model->getData('touchenabled') : 'on',
            'note'      => Mage::helper('revslider')->__('Enable Swipe Function on touch devices')
        ));
        $f1 = $fieldset->addField('swipe_velocity', 'text', array(
            'name'      => 'swipe_velocity',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Swipe Velocity (0 - 1)'),
            'title'     => Mage::helper('revslider')->__('Swipe Velocity'),
            'value'     => $model->getData('swipe_velocity') ? $model->getData('swipe_velocity') : '0.7',
            'note'      => Mage::helper('revslider')->__('Defines the sensibility of gestures. Smaller values mean a higher sensibility')
        ));
        $f2 = $fieldset->addField('swipe_min_touches', 'text', array(
            'name'      => 'swipe_min_touches',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Swipe Min Touches'),
            'title'     => Mage::helper('revslider')->__('Swipe Min Touches'),
            'value'     => $model->getData('swipe_min_touches') ? $model->getData('swipe_min_touches') : '1',
            'note'      => Mage::helper('revslider')->__('Defines how many fingers are needed minimum for swiping')
        ));
        /*$f3 = $fieldset->addField('swipe_max_touches', 'text', array(
            'name'      => 'swipe_max_touches',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Swipe Max Touches'),
            'title'     => Mage::helper('revslider')->__('Swipe Max Touches'),
            'value'     => $model->getData('swipe_max_touches') ? $model->getData('swipe_max_touches') : '1',
            'note'      => Mage::helper('revslider')->__('Defines how many fingers are allowed for swiping at the same time')
        ));*/
        $f4 = $fieldset->addField('drag_block_vertical', 'select', array(
            'name'      => 'drag_block_vertical',
            'label'     => Mage::helper('revslider')->__('Drag Block Vertical'),
            'title'     => Mage::helper('revslider')->__('Drag Block Vertical'),
            'values'    => $model->getOnOffOptions(),
            'value'     => $model->getData('drag_block_vertical') ? $model->getData('drag_block_vertical') : 'off',
            'note'      => Mage::helper('revslider')->__('Prevent vertical scroll during swipe')
        ));

        $this->setForm($form);
        if ($model->getId()) $form->setValues($model->getData());

        if (version_compare(Mage::getVersion(), '1.7.0.0') < 0){
            $dependenceElement = $this->getLayout()->createBlock('amext/adminhtml_widget_form_element_dependence');
        }else{
            $dependenceElement = $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence');
        }
        $this->setChild('form_after', $dependenceElement
            ->addFieldMap($f->getHtmlId(), $f->getName())
            ->addFieldMap($f1->getHtmlId(), $f1->getName())
            ->addFieldMap($f2->getHtmlId(), $f2->getName())
            //->addFieldMap($f3->getHtmlId(), $f3->getName())
            ->addFieldMap($f4->getHtmlId(), $f4->getName())
            ->addFieldDependence($f1->getName(), $f->getName(), 'on')
            ->addFieldDependence($f2->getName(), $f->getName(), 'on')
            //->addFieldDependence($f3->getName(), $f->getName(), 'on')
            ->addFieldDependence($f4->getName(), $f->getName(), 'on')
        );

        return parent::_prepareForm();
    }
}
