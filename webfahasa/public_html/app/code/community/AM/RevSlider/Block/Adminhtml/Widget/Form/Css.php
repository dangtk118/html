<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Widget_Form_Css
    extends Mage_Adminhtml_Block_Widget
    implements Varien_Data_Form_Element_Renderer_Interface{

    public function __construct(){
        parent::__construct();
        $this->setTemplate('am/revslider/widget/form/css.phtml');
    }

    public function render(Varien_Data_Form_Element_Abstract $element){
        return $this->toHtml();
    }

    protected function _prepareLayout(){
        $this->setChild('css', $this->getLayout()->createBlock('adminhtml/widget_button', '', array(
            'id'        => 'editStyleBtn',
            'label'     => Mage::helper('revslider')->__('Edit Style'),
            'type'      => 'button',
            'onclick'   => sprintf('revLayer.openCssDialog(\'%s\', \'editCssWindow\', \'%s\')',
                $this->getUrl('revslideradmin/slider/css'),
                Mage::helper('revslider')->__('Edit Style')
            )
        )));

        return parent::_prepareLayout();
    }
}
