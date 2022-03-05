<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Widget_Form_Reset
    extends Mage_Adminhtml_Block_Widget
    implements Varien_Data_Form_Element_Renderer_Interface{

    public function __construct(){
        parent::__construct();
        $this->setTemplate('am/revslider/widget/form/reset.phtml');
    }

    public function render(Varien_Data_Form_Element_Abstract $element){
        return $this->toHtml();
    }

    protected function _prepareLayout(){
        $this->setChild('resetBtn', $this->getLayout()->createBlock('adminhtml/widget_button', '', array(
            'id'        => 'resetScaleBtn',
            'label'     => Mage::helper('revslider')->__('Reset'),
            'type'      => 'button',
            'onclick'   => 'revLayer.resetScale()'
        )));

        return parent::_prepareLayout();
    }
}
