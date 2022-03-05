<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2013 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */
class AM_RevSlider_Block_Adminhtml_Widget_Grid_Column_Renderer_Slider_Preview
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract{

    public function _getValue(Varien_Object $row){
        return sprintf('<a href="%s" title="%s" target="_blank">%s</a>',
            Mage::helper('revslider')->getFrontendUrl('revslider/index/preview', array('id' => $row->getId())),
            Mage::helper('revslider')->__('Click to see preview'),
            Mage::helper('revslider')->__('Preview')
        );
    }
}