<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Slider_Edit_Tab_Custom extends Mage_Adminhtml_Block_Widget_Form{
    protected $_slider;

    protected function _getSlider(){
        if (!$this->_slider){
            $slider = Mage::getModel('revslider/slider');
            $id = $this->getRequest()->getParam('id', null);
            if (is_numeric($id)){
                $slider->load($id);
            }
            $this->_slider = $slider;
        }
        return $this->_slider;
    }

    public function _prepareForm(){
        /* @var $slider AM_RevSlider_Model_Slider */
        $slider = $this->_getSlider();

        $form = new Varien_Data_Form();
        $fieldset1 = $form->addFieldset('css_fieldset', array(
            'legend'    => Mage::helper('revslider')->__('Static Styles')
        ));
        $fieldset1->addField('styles', 'text', array(
            'name'      => 'styles',
            'label'     => Mage::helper('revslider')->__('Slider Static Styles')
        ));
        $form->getElement('styles')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_editor', null, array(
                'mode'  => 'css'
            ))
        );

        $fieldset2 = $form->addFieldset('js_fieldset', array(
            'legend'    => Mage::helper('revslider')->__('Custom Javascript')
        ));
        $fieldset2->addField('scripts', 'text', array(
            'name'      => 'scripts'
        ));
        $form->getElement('scripts')->setRenderer(
            $this->getLayout()->createBlock('revslider/adminhtml_widget_form_editor', null, array(
                'mode'  => 'javascript'
            ))
        );

        $this->setForm($form);
        if ($slider->getId()){
            $form->setValues($slider->getData());
        }

        return parent::_prepareForm();
    }
}
