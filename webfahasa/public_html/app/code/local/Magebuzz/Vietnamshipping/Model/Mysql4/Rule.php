<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Vietnamshipping_Model_Mysql4_Rule extends Mage_Core_Model_Mysql4_Abstract {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        }     
        
	public function _construct() {    
		// Note that the vietnamshipping_id refers to the key field in your database table.
		$this->_init($this->getModuleStr() . '/rule', 'rule_id');
	} 

  protected function _afterLoad(Mage_Core_Model_Abstract $object) {
    if ($object->getRuleId()) {
      $stores = $this->lookupStoreIds($object->getRuleId());    
      $object->setData('store_id', $stores);
    }   
    return parent::_afterLoad($object);
  }
   protected function _afterSave(Mage_Core_Model_Abstract $object) {
    $oldStores = $this->lookupStoreIds($object->getRuleId());    
    $newStores = (array)$object->getStores();
    
   
    if (empty($newStores)) {
      $newStores = (array)$object->getStoreId();
    }
    $this->saveStore($newStores,$oldStores,$object->getRuleId());  
    return parent::_afterSave($object);
  }
  
  public function saveStore($newStores,$oldStores,$ruleId){
    $table  = $this->getTable($this->getModuleStr() . '/store');
    $insert = array_diff($newStores, $oldStores);
    $delete = array_diff($oldStores, $newStores);    
    if ($delete) {
      $where = array(
      'rule_id = ?'     => (int) $ruleId,
      'store_id IN (?)' => $delete
      );
      $this->_getWriteAdapter()->delete($table, $where);
    }
    if ($insert) {
      $data = array();
      foreach ($insert as $storeId) {
        $data[] = array(
        'rule_id'  => (int) $ruleId,
        'store_id' => (int) $storeId
        );
      }
      $this->_getWriteAdapter()->insertMultiple($table, $data);
    }
  }
  public function lookupStoreIds($ruleId) {
    $adapter = $this->_getReadAdapter();
    $select  = $adapter->select()
    ->from($this->getTable($this->getModuleStr() . '/store'), 'store_id')
    ->where('rule_id = ?',(int)$ruleId);
    return $adapter->fetchCol($select);
  }
}