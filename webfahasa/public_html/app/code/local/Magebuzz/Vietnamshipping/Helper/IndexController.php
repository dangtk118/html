<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Helper_IndexController extends Mage_Core_Controller_Front_Action {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  }  
        
  public function getListDistrictBillingAction() {
    $this->loadLayout();
		$this->renderLayout();
		$regionId = $this->getRequest()->getParam('region_id');
    $tableCountryRegion = $this->_getTableName('directory_country_region');
    $query = "SELECT * FROM ".$this->_getTableName($this->getModuleStr() . '_district')." vd JOIN ".$tableCountryRegion." dr ON vd.`province_id`=dr.`province_id` WHERE dr.`region_id`= ".$regionId."";
    $result = $this->_getReadConnection()->fetchAll($query);
    if (count($result)) {
       $html =  '';
       $html .= '<select id="billing:city" name="billing[city]" class="required-entry">';
       $html .= '<option value="">Please select district</option>';
       foreach($result as $_district){
       $isSelected = $selectedCity == $_district['district_name'] ? ' selected="selected"' : null;
       $html .= '<option value="' . $_district['district_name'] . '"' . $isSelected . '>' . $_district['district_name'] . '</option>';
       }
       $html .= '</select>';      
    } else {
       $html .= '<select id="billing:city" name="billing[city]" class="required-entry">';
       $html .= '<option value="">Please select district</option>';
       $html .= '<option value=""></option>';
       $html .=      '</select>';   
    }   
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($html));	
  }
	
  public function getListDistrictShippingAction() {
    $this->loadLayout();
		$this->renderLayout();
		$regionId = $this->getRequest()->getParam('region_id');
    $tableCountryRegion = $this->_getTableName('directory_country_region');
    $query = "SELECT * FROM ".$this->_getTableName($this->getModuleStr() . '_district')." vd JOIN ".$tableCountryRegion." dr ON vd.`province_id`=dr.`province_id` WHERE dr.`region_id`= ".$regionId."";
    $result = $this->_getReadConnection()->fetchAll($query);
    if (count($result)) {
       $html =  '';
       $html .= '<select id="shipping:city" name="shipping[city]" class="required-entry">';
       $html .= '<option value="">Please select district</option>';
       foreach($result as $_district){
       $isSelected = $selectedCity == $_district['district_name'] ? ' selected="selected"' : null;
       $html .= '<option value="' . $_district['district_name'] . '"' . $isSelected . '>' . $_district['district_name'] . '</option>';
       }
       $html .= '</select>';      
    } else {
       $html .= '<select id="shipping:city" name="shipping[city]" class="required-entry">';
       $html .= '<option value="">Please select district</option>';
       $html .= '<option value=""></option>';
       $html .=      '</select>';   
    }   
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($html));	
  } 
	
  public function getListDistrictCreateCustomerAction() {
    $this->loadLayout();
		$this->renderLayout();
		$regionId = $this->getRequest()->getParam('region_id');
    $tableCountryRegion = $this->_getTableName('directory_country_region');
    $query = "SELECT * FROM ".$this->_getTableName($this->getModuleStr() . '_district')." vd JOIN ".$tableCountryRegion." dr ON vd.`province_id`=dr.`province_id` WHERE dr.`region_id`= ".$regionId."";
    $result = $this->_getReadConnection()->fetchAll($query);
    if (count($result)) {
       $html =  '';
       $html .= '<select id="city" name="city" class="required-entry">';
       $html .= '<option value="">Please select district</option>';
       foreach($result as $_district){
       $isSelected = $selectedCity == $_district['district_name'] ? ' selected="selected"' : null;
       $html .= '<option value="' . $_district['district_name'] . '"' . $isSelected . '>' . $_district['district_name'] . '</option>';
       }
       $html .= '</select>';      
    } else {
       $html .= '<select id="city" name="city" class="required-entry">';
       $html .= '<option value="">Please select district</option>';
       $html .= '<option value=""></option>';
       $html .=      '</select>';   
    }   
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($html));	
  }  
	
  public function getListDistrictCreateNewOrderBillingAdminAction() {
    $this->loadLayout();
		$this->renderLayout();
		$regionId = $this->getRequest()->getParam('region_id');
    $tableCountryRegion = $this->_getTableName('directory_country_region');
    $query = "SELECT * FROM ".$this->_getTableName($this->getModuleStr() . '_district')." vd JOIN ".$tableCountryRegion." dr ON vd.`province_id`=dr.`province_id` WHERE dr.`region_id`= ".$regionId."";
    $result = $this->_getReadConnection()->fetchAll($query);
    if (count($result)) {
       $html =  '';
       $html .= '<select id="order-billing_address_city" name="order[billing_address][city]" class="required-entry"  style=" width: 240px; " >';
       $html .= '<option value="">Please select district</option>';
       foreach($result as $_district){
       $isSelected = $selectedCity == $_district['district_name'] ? ' selected="selected"' : null;
       $html .= '<option value="' . $_district['district_name'] . '"' . $isSelected . '>' . $_district['district_name'] . '</option>';
       }
       $html .= '</select>';      
    } else {
       $html .= '<select id="order-billing_address_city" name="order[billing_address][city]" class="required-entry"  style=" width: 240px; " >';
       $html .= '<option value="">Please select district</option>';
       $html .= '<option value=""></option>';
       $html .=      '</select>';   
    }   
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($html));	
  } 
	
  public function getListDistrictCreateNewOrderShippingAdminAction() {
    $this->loadLayout();
		$this->renderLayout();
		$regionId = $this->getRequest()->getParam('region_id');
    $tableCountryRegion = $this->_getTableName('directory_country_region');
    $query = "SELECT * FROM ".$this->_getTableName($this->getModuleStr() . '_district')." vd JOIN ".$tableCountryRegion." dr ON vd.`province_id`=dr.`province_id` WHERE dr.`region_id`= ".$regionId."";
    $result = $this->_getReadConnection()->fetchAll($query);
    if (count($result)) {
       $html =  '';
       $html .= '<select id="order-shipping_address_city" name="order[shipping_address][city]" class="required-entry"  style=" width: 240px; " >';
       $html .= '<option value="">Please select district</option>';
       foreach($result as $_district){
       $isSelected = $selectedCity == $_district['district_name'] ? ' selected="selected"' : null;
       $html .= '<option value="' . $_district['district_name'] . '"' . $isSelected . '>' . $_district['district_name'] . '</option>';
       }
       $html .= '</select>';      
    } else {
       $html .= '<select id="order-shipping_address_city" name="order[shipping_address][city]" class="required-entry"  style=" width: 240px; " >';
       $html .= '<option value="">Please select district</option>';
       $html .= '<option value=""></option>';
       $html .=      '</select>';   
    }   
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($html));	
  } 
	
  public function getListDistrictCustomerAddressAdminAction() {
    $this->loadLayout();
		$this->renderLayout();
		$regionId = $this->getRequest()->getParam('region_id');
    $itemId = $this->getRequest()->getParam('item_id');
    $tableCountryRegion = $this->_getTableName('directory_country_region');
    $query = "SELECT * FROM ".$this->_getTableName($this->getModuleStr() . '_district')." vd JOIN ".$tableCountryRegion." dr ON vd.`province_id`=dr.`province_id` WHERE dr.`region_id`= ".$regionId."";
    $result = $this->_getReadConnection()->fetchAll($query);
    if (count($result)) {
       $html =  '';
       $html .= '<select id="'.$itemId.'city" name="address['.$itemId.'][city]" class="required-entry"  style=" width: 240px; " >';
       $html .= '<option value="">Please select district</option>';
       foreach($result as $_district){
       $isSelected = $selectedCity == $_district['district_name'] ? ' selected="selected"' : null;
       $html .= '<option value="' . $_district['district_name'] . '"' . $isSelected . '>' . $_district['district_name'] . '</option>';
       }
       $html .= '</select>';      
    } else {
       $html .= '<select id="'.$itemId.'city" name="address['.$itemId.'][city]" class="required-entry"  style=" width: 240px; " >';
       $html .= '<option value="">Please select district</option>';
       $html .= '<option value=""></option>';
       $html .=      '</select>';   
    }   
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($html));	
  }       

	public function testAction() {
		$attribute = '';
		$attributeModel = Mage::getModel('eav/entity_attribute')->loadByCode('customer_address', 'region');
		echo $attributeModel->getId();die('aaaa');
	}
		
  protected function _getWriteConnection() {
		return Mage::getSingleton('core/resource')->getConnection('core_write');
	}
  
  protected function _getReadConnection() {
		return Mage::getSingleton('core/resource')->getConnection('core_read');
	}
  
  protected function _getTableName($name) {
		return Mage::getSingleton('core/resource')->getTableName($name);
  
	}
}
