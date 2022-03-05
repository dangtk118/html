<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Slider_Edit_Tab_Mobile
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    public function getTabLabel(){
        return '<i class="eg-icon-mobile"></i>'.Mage::helper('revslider')->__('Mobile Visibility');
    }

    public function getTabTitle(){
        return Mage::helper('revslider')->__('Mobile Visibility');
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
        $fieldset = $form->addFieldset('general_fieldset', array('legend' => Mage::helper('revslider')->__('Mobile Visibility')));

        $fieldset->addField('disable_on_mobile', 'select', array(
            'name'      => 'disable_on_mobile',
            'label'     => Mage::helper('revslider')->__('Disable Slider on Mobile'),
            'title'     => Mage::helper('revslider')->__('Disable Slider on Mobile'),
            'values'    => $model->getOnOffOptions(),
            'note'      => Mage::helper('revslider')->__('If this is enabled, the slider will not be loaded on mobile devices.')
        ));
        $fieldset->addField('disable_kenburns_on_mobile', 'select', array(
            'name'      => 'disable_kenburns_on_mobile',
            'label'     => Mage::helper('revslider')->__('Disable KenBurn On Mobile'),
            'title'     => Mage::helper('revslider')->__('Disable KenBurn On Mobile'),
            'values'    => $model->getOnOffOptions(),
            'note'      => Mage::helper('revslider')->__('This will disable KenBurns on mobile devices to save performance')
        ));
        $fieldset->addField('hide_slider_under', 'text', array(
            'name'      => 'hide_slider_under',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Hide Slider Under Width'),
            'title'     => Mage::helper('revslider')->__('Hide Slider Under Width'),
            'value'     => $model->getData('hide_slider_under') ? $model->getData('hide_slider_under') : 0,
            'note'      => Mage::helper('revslider')->__('Hide the slider under some slider width. Works only in Responsive Style. Not available for Full Width')
        ));
        $fieldset->addField('hide_defined_layers_under', 'text', array(
            'name'      => 'hide_defined_layers_under',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Hide Defined Layers Under Width'),
            'title'     => Mage::helper('revslider')->__('Hide Defined Layers Under Width'),
            'value'     => $model->getData('hide_defined_layers_under') ? $model->getData('hide_defined_layers_under') : 0,
            'note'      => Mage::helper('revslider')->__('Hide some defined layers in the layer properties under some slider width')
        ));
        $fieldset->addField('hide_all_layers_under', 'text', array(
            'name'      => 'hide_all_layers_under',
            'class'     => 'validate-number',
            'label'     => Mage::helper('revslider')->__('Hide All Layers Under Width'),
            'title'     => Mage::helper('revslider')->__('Hide All Layers Under Width'),
            'value'     => $model->getData('hide_all_layers_under') ? $model->getData('hide_all_layers_under') : 0,
            'note'      => Mage::helper('revslider')->__('Hide all layers under some slider width')
        ));
        $fieldset->addField('hide_arrows_on_mobile', 'select', array(
            'name'      => 'hide_arrows_on_mobile',
            'label'     => Mage::helper('revslider')->__('Hide Arrows on Mobile'),
            'title'     => Mage::helper('revslider')->__('Hide Arrows on Mobile'),
            'values'    => $model->getOnOffOptions(),
            'note'      => Mage::helper('revslider')->__('Show/Hide the Navigation Arrows on Mobile Devices')
        ));
        $fieldset->addField('hide_bullets_on_mobile', 'select', array(
            'name'      => 'hide_bullets_on_mobile',
            'label'     => Mage::helper('revslider')->__('Hide Bullets on Mobile'),
            'title'     => Mage::helper('revslider')->__('Hide Bullets on Mobile'),
            'values'    => $model->getOnOffOptions(),
            'note'      => Mage::helper('revslider')->__('Show/Hide the Navigation Bullets on Mobile Devices')
        ));
        $fieldset->addField('hide_thumbs_on_mobile', 'select', array(
            'name'      => 'hide_thumbs_on_mobile',
            'label'     => Mage::helper('revslider')->__('Hide Thumbnails on Mobile'),
            'title'     => Mage::helper('revslider')->__('Hide Thumbnails on Mobile'),
            'values'    => $model->getOnOffOptions(),
            'note'      => Mage::helper('revslider')->__('Show/Hide the Thumbnails on Mobile Devices')
        ));
        $fieldset->addField('hide_thumbs_under_resolution', 'text', array(
            'name'      => 'hide_thumbs_under_resolution',
            'label'     => Mage::helper('revslider')->__('Hide Thumbs Under Width'),
            'title'     => Mage::helper('revslider')->__('Hide Thumbs Under Width'),
            'value'     => $model->getData('hide_thumbs_under_resolution') ? $model->getData('hide_thumbs_under_resolution') : 0,
            'note'      => Mage::helper('revslider')->__('Hide the Thumbnails on Mobile Devices under some slider width'),
            'class'     => 'validate-number'
        ));
        $fieldset->addField('hide_thumbs_delay_mobile', 'text', array(
            'name'      => 'hide_thumbs_delay_mobile',
            'label'     => Mage::helper('revslider')->__('Hide Mobile Nav After'),
            'title'     => Mage::helper('revslider')->__('Hide Mobile Nav After'),
            'value'     => $model->getData('hide_thumbs_delay_mobile') ? $model->getData('hide_thumbs_delay_mobile') : 1500,
            'note'      => Mage::helper('revslider')->__('Time after that the Navigation and the Thumbs will be hidden on mobile devices. (Default: 1500 ms)'),
            'class'     => 'validate-number'
        ));

        $this->setForm($form);
        if ($model->getId()) $form->setValues($model->getData());
        return parent::_prepareForm();
    }
}
