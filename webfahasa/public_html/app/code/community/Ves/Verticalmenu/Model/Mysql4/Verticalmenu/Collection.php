<?php
/******************************************************
 * @package Ves Megamenu module for Magento 1.8.x.x and Magento 1.9.x.x
 * @version 1.0.0.1
 * @author http://venustheme.com
 * @copyright	Copyright (C) December 2010 venustheme.com <@emai:venustheme@gmail.com>.All rights reserved.
 * @license		GNU General Public License version 2
*******************************************************/
?>
<?php
class Ves_Verticalmenu_Model_Mysql4_Verticalmenu_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected $_previewFlag = null;
    public function _construct()
    {
        parent::_construct();
        $this->_init('ves_verticalmenu/verticalmenu');
        $this->_previewFlag = false;
    }
    
    
     /**
     * After load processing - adds store information to the datasets
     *
     */
    protected function _afterLoad()
    {
        if ($this->_previewFlag) {
            $items = $this->getColumnValues('verticalmenu_id');
            if (count($items)) {
                $select = $this->getConnection()->select()->from(
                    $this->getTable('ves_verticalmenu/verticalmenu_store')
                )->where(
                    $this->getTable('ves_verticalmenu/verticalmenu_store') . '.verticalmenu_id IN (?)',
                    $items
                );
                if ($result = $this->getConnection()->fetchPairs($select)) {
                    foreach ($this as $item) {
                        if (!isset($result[$item->getData('verticalmenu_id')])) {
                            continue;
                        }
                        if ($result[$item->getData('verticalmenu_id')] == 0) {
                            $stores = Mage::app()->getStores(false, true);
                            $storeId = current($stores)->getId();
                            $storeCode = key($stores);
                        }
                        else {
                            $storeId = $result[$item->getData('verticalmenu_id')];
                            $storeCode = Mage::app()->getStore($storeId)->getCode();
                        }
                        if($item->getData('is_default') == 1){
                            $this->setData('is_default', Mage::helper('ves_verticalmenu')->__('<span class="hightlight">Default</span>'));
                        }else{
                            $this->setData('is_default', Mage::helper('ves_verticalmenu')->__('No'));
                        }
                        $item->setData('_first_store_id', $storeId);
                        $item->setData('store_code', $storeCode);
                    }
                }
            }
        }
        
        parent::_afterLoad();
    }
    
    /**
     * Add Filter by store
     *
     * @param int|Mage_Core_Model_Store $store Store to be filtered
     * @return Ves_verticalmenu_Model_Mysql4_verticalmenu_Collection Self
     */
    public function addStoreFilter($store)
    {
        if ($store instanceof Mage_Core_Model_Store) {
            $store = array (
                 $store->getId()
            );
        }
        $store = is_array($store)?$store:array($store);

        //do stuff
        $this->getSelect()->join(
            array('store_table' => $this->getTable('ves_verticalmenu/verticalmenu_store')),
            'main_table.verticalmenu_id = store_table.verticalmenu_id', array ()
        )->where('store_table.store_id in (?)', $store)->group('main_table.verticalmenu_id');

        return $this;
    }

    /**
     * Add Filter by store
     *
     * @param int|Mage_Core_Model_Store $store Store to be filtered
     * @return Ves_Megamenu_Model_Mysql4_Megamenu_Collection Self
     */
    public function addRootFilter($root_parent = 1)
    {
        //do stuff
        $this->getSelect()->where('main_table.parent_id = (?)', (int)$root_parent);

        return $this;
    }

    public function addIdFilter($verticalmenuIds) {
    	if (is_array($verticalmenuIds)) {
            if (empty($verticalmenuIds)) {
                $condition = '';
            } else {
                $condition = array('in' => $verticalmenuIds);
            }
        } elseif (is_numeric($verticalmenuIds)) {
            $condition = $verticalmenuIds;
        } elseif (is_string($verticalmenuIds)) {
            $ids = explode(',', $verticalmenuIds);
            if (empty($ids)) {
                $condition = $verticalmenuIds;
            } else {
                $condition = array('in' => $ids);
            }
        }
        $this->addFieldToFilter('parent_id', $condition);
        return $this;
    }
}