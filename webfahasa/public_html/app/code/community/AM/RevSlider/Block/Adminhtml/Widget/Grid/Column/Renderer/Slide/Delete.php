<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2014 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */

class AM_RevSlider_Block_Adminhtml_Widget_Grid_Column_Renderer_Slide_Delete extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract{
    public function _getValue(Varien_Object $row){
        return sprintf('<a href="javascript:confirmSetLocation(\'%s\', \'%s\')">%s</a>',
            Mage::helper('revslider')->__('Do you realy want to delete this slide?'),
            $this->getUrl('*/*/deleteSlide', array('sid'=>$row->getSliderId(), 'id'=>$row->getId(), 'activeTab'=>'slide_section')),
            Mage::helper('revslider')->__('Delete')
        );
    }
}
