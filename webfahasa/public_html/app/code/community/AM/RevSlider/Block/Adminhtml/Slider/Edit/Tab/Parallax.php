<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Slider_Edit_Tab_Parallax
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    public function getTabLabel(){
        return '<i class="eg-icon-camera-alt"></i>'.Mage::helper('revslider')->__('Parallax');
    }

    public function getTabTitle(){
        return Mage::helper('revslider')->__('Parallax Settings');
    }

    public function canShowTab(){
        return true;
    }

    public function isHidden(){
        return false;
    }

    public function _prepareForm(){
        /* @var $slider AM_RevSlider_Model_Slider */
        $slider = Mage::registry('revslider');

        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('parallax_fieldset',
            array('legend' => Mage::helper('revslider')->__('Parallax Settings'))
        );

        $f1 = $fieldset->addField('use_parallax', 'select', array(
            'name'      => 'use_parallax',
            'label'     => Mage::helper('revslider')->__('Enable Parallax'),
            'title'     => Mage::helper('revslider')->__('Enable Parallax'),
            'note'      => Mage::helper('revslider')->__('Enabling this, will give you new options in the slides to create a unique parallax effect'),
            'values'    => $slider->getOnOffOptions()
        ));
        $f14 = $fieldset->addField('disable_parallax_mobile', 'select', array(
            'name'      => 'disable_parallax_mobile',
            'label'     => Mage::helper('revslider')->__('Disable on Mobile'),
            'title'     => Mage::helper('revslider')->__('Disable on Mobile'),
            'values'    => $slider->getOnOffOptions(),
            'note'      => Mage::helper('revslider')->__('If set to on, parallax will be disabled on mobile devices to save performance')
        ));
        $f2 = $fieldset->addField('parallax_type', 'select', array(
            'name'      => 'parallax_type',
            'label'     => Mage::helper('revslider')->__('Type'),
            'title'     => Mage::helper('revslider')->__('Type'),
            'note'      => Mage::helper('revslider')->__('Defines on what input type the parallax should react to'),
            'values'    => $slider->getParallaxTypeOptions()
        ));
        $f3 = $fieldset->addField('parallax_bg_freeze', 'select', array(
            'name'      => 'parallax_bg_freeze',
            'label'     => Mage::helper('revslider')->__('Background Freeze'),
            'title'     => Mage::helper('revslider')->__('Background Freeze'),
            'note'      => Mage::helper('revslider')->__('Setting this to on will freeze the background so that it is not affected by the parallax effect'),
            'values'    => $slider->getOnOffOptions()
        ));
        $f4 = $fieldset->addField('parallax_level_1', 'text', array(
            'name'      => 'parallax_level_1',
            'label'     => Mage::helper('revslider')->__('Level Depth 1'),
            'title'     => Mage::helper('revslider')->__('Level Depth 1'),
            'note'      => Mage::helper('revslider')->__('Defines a level that can be used in Slide Editor for this Slider')
        ));
        $f5 = $fieldset->addField('parallax_level_2', 'text', array(
            'name'      => 'parallax_level_2',
            'label'     => Mage::helper('revslider')->__('Level Depth 2'),
            'title'     => Mage::helper('revslider')->__('Level Depth 2'),
            'note'      => Mage::helper('revslider')->__('Defines a level that can be used in Slide Editor for this Slider')
        ));
        $f6 = $fieldset->addField('parallax_level_3', 'text', array(
            'name'      => 'parallax_level_3',
            'label'     => Mage::helper('revslider')->__('Level Depth 3'),
            'title'     => Mage::helper('revslider')->__('Level Depth 3'),
            'note'      => Mage::helper('revslider')->__('Defines a level that can be used in Slide Editor for this Slider')
        ));
        $f7 = $fieldset->addField('parallax_level_4', 'text', array(
            'name'      => 'parallax_level_4',
            'label'     => Mage::helper('revslider')->__('Level Depth 4'),
            'title'     => Mage::helper('revslider')->__('Level Depth 4'),
            'note'      => Mage::helper('revslider')->__('Defines a level that can be used in Slide Editor for this Slider')
        ));
        $f8 = $fieldset->addField('parallax_level_5', 'text', array(
            'name'      => 'parallax_level_5',
            'label'     => Mage::helper('revslider')->__('Level Depth 5'),
            'title'     => Mage::helper('revslider')->__('Level Depth 5'),
            'note'      => Mage::helper('revslider')->__('Defines a level that can be used in Slide Editor for this Slider')
        ));
        $f9 = $fieldset->addField('parallax_level_6', 'text', array(
            'name'      => 'parallax_level_6',
            'label'     => Mage::helper('revslider')->__('Level Depth 6'),
            'title'     => Mage::helper('revslider')->__('Level Depth 6'),
            'note'      => Mage::helper('revslider')->__('Defines a level that can be used in Slide Editor for this Slider')
        ));
        $f10 = $fieldset->addField('parallax_level_7', 'text', array(
            'name'      => 'parallax_level_7',
            'label'     => Mage::helper('revslider')->__('Level Depth 7'),
            'title'     => Mage::helper('revslider')->__('Level Depth 7'),
            'note'      => Mage::helper('revslider')->__('Defines a level that can be used in Slide Editor for this Slider')
        ));
        $f11 = $fieldset->addField('parallax_level_8', 'text', array(
            'name'      => 'parallax_level_8',
            'label'     => Mage::helper('revslider')->__('Level Depth 8'),
            'title'     => Mage::helper('revslider')->__('Level Depth 8'),
            'note'      => Mage::helper('revslider')->__('Defines a level that can be used in Slide Editor for this Slider')
        ));
        $f12 = $fieldset->addField('parallax_level_9', 'text', array(
            'name'      => 'parallax_level_9',
            'label'     => Mage::helper('revslider')->__('Level Depth 9'),
            'title'     => Mage::helper('revslider')->__('Level Depth 9'),
            'note'      => Mage::helper('revslider')->__('Defines a level that can be used in Slide Editor for this Slider')
        ));
        $f13 = $fieldset->addField('parallax_level_10', 'text', array(
            'name'      => 'parallax_level_01',
            'label'     => Mage::helper('revslider')->__('Level Depth 10'),
            'title'     => Mage::helper('revslider')->__('Level Depth 1'),
            'note'      => Mage::helper('revslider')->__('Defines a level that can be used in Slide Editor for this Slider')
        ));


        $this->setForm($form);
        if ($slider->getId()) $form->setValues($slider->getData());

        if (version_compare(Mage::getVersion(), '1.7.0.0') < 0){
            $dependenceElement = $this->getLayout()->createBlock('amext/adminhtml_widget_form_element_dependence');
        }else{
            $dependenceElement = $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence');
        }

        $this->setChild('form_after', $dependenceElement
            ->addFieldMap($f1->getHtmlId(), $f1->getName())
            ->addFieldMap($f2->getHtmlId(), $f2->getName())
            ->addFieldMap($f3->getHtmlId(), $f3->getName())
            ->addFieldMap($f4->getHtmlId(), $f4->getName())
            ->addFieldMap($f5->getHtmlId(), $f5->getName())
            ->addFieldMap($f6->getHtmlId(), $f6->getName())
            ->addFieldMap($f7->getHtmlId(), $f7->getName())
            ->addFieldMap($f8->getHtmlId(), $f8->getName())
            ->addFieldMap($f9->getHtmlId(), $f9->getName())
            ->addFieldMap($f10->getHtmlId(), $f10->getName())
            ->addFieldMap($f11->getHtmlId(), $f11->getName())
            ->addFieldMap($f12->getHtmlId(), $f12->getName())
            ->addFieldMap($f13->getHtmlId(), $f13->getName())
            ->addFieldMap($f14->getHtmlId(), $f14->getName())
            ->addFieldDependence($f2->getName(), $f1->getName(), 'on')
            ->addFieldDependence($f3->getName(), $f1->getName(), 'on')
            ->addFieldDependence($f4->getName(), $f1->getName(), 'on')
            ->addFieldDependence($f5->getName(), $f1->getName(), 'on')
            ->addFieldDependence($f6->getName(), $f1->getName(), 'on')
            ->addFieldDependence($f7->getName(), $f1->getName(), 'on')
            ->addFieldDependence($f8->getName(), $f1->getName(), 'on')
            ->addFieldDependence($f9->getName(), $f1->getName(), 'on')
            ->addFieldDependence($f10->getName(), $f1->getName(), 'on')
            ->addFieldDependence($f11->getName(), $f1->getName(), 'on')
            ->addFieldDependence($f12->getName(), $f1->getName(), 'on')
            ->addFieldDependence($f13->getName(), $f1->getName(), 'on')
            ->addFieldDependence($f14->getName(), $f1->getName(), 'on')
        );

        return parent::_prepareForm();
    }
}
