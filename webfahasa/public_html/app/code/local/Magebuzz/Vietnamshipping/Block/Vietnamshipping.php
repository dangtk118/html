<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Vietnamshipping extends Mage_Core_Block_Template {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        } 
  
	public function _prepareLayout() {
		return parent::_prepareLayout();
        }
    
	public function getVietnamshipping() { 
		if (!$this->hasData($this->getModuleStr())) {
			$this->setData($this->getModuleStr(), Mage::registry($this->getModuleStr()));
		}
		return $this->getData($this->getModuleStr());		
	}
}