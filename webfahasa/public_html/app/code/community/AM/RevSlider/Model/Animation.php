<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2013 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */
class AM_RevSlider_Model_Animation extends Mage_Core_Model_Abstract{
    public function _construct(){
        parent::_construct();
        $this->_init('revslider/animation');
    }

    public function loadByName($name){
        return $this->_getResource()->loadByName($this, $name);
    }

    protected function _afterLoad(){
        $this->setData('params', Mage::helper('core')->jsonDecode($this->getData('params')));
        return parent::_afterLoad();
    }
}