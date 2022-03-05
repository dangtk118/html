<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Helper_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        }   
        
	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu($this->getModuleStr() . '/shipping_rule')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Rule Manager'), Mage::helper('adminhtml')->__('Rule Manager'));
		
		return $this;
	}   
 
	public function indexAction() {
		$this->_initAction()
			->renderLayout();
	}
	
	public function citiesAction() {
		$arrRes = array();
		$regionId = $this->getRequest()->getParam('parent');
		
		$cities = Mage::helper($this->getModuleStr())->getCitiesByRegion($regionId);
		
		if (!empty($cities )) {
			foreach ($cities  as $city) {
				$item = array(
					'title' => $city['district_name'],
					'value' => $city['district_name'],
					'label' => $city['district_name'],
				);
				$arrRes[] = $item;
			}
		}
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($arrRes));
	}
}