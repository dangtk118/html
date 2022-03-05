<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Widget_Grid_Column_Renderer_Slide_Edit extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract{
    public function _getValue(Varien_Object $row){
        return sprintf('<a href="%s">%s</a>',
            $this->getUrl('*/*/addSlide', array('sid' => $row->getSliderId(), 'id' => $row->getId())),
            Mage::helper('revslider')->__('Edit')
        );
    }
}
