<?php
/******************************************************
 * @package Ves Megamenu module for Magento 1.4.x.x and Magento 1.7.x.x
 * @version 1.0.0.1
 * @author http://landofcoder.com
 * @copyright   Copyright (C) December 2010 LandOfCoder.com <@emai:landofcoder@gmail.com>.All rights reserved.
 * @license     GNU General Public License version 2
*******************************************************/
?>
<?php

class Ves_Megamenu_Model_Api extends Mage_Api_Model_Resource_Abstract
{

    public function create($menuData)
    {
        try {
            $megamenu = Mage::getModel('ves_megamenu/megamenu')
                ->setData($menuData)
                ->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
            // We cannot know all the possible exceptions,
            // so let's try to catch the ones that extend Mage_Core_Exception
        } catch (Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        return $megamenu->getId();
    }

    public function info($menuId)
    {
        $megamenu = Mage::getModel('ves_megamenu/megamenu')->load($menuId);
        if (!$megamenu->getId()) {
            $this->_fault('not_exists');
            // If megamenu not found.
        }
        return $megamenu->toArray();
        // We can use only simple PHP data types in webservices.
    }

    public function items($filters)
    {
        $collection = Mage::getModel('ves_megamenu/megamenu')->getCollection()
            ->addAttributeToSelect('*');

        if (is_array($filters)) {
            try {
                foreach ($filters as $field => $value) {
                    if($field == "store_id") {
                        $collection->addStoreFilter((int)$value);
                    } else {
                        $collection->addFieldToFilter($field, $value);
                    }
                    
                }
            } catch (Mage_Core_Exception $e) {
                $this->_fault('filters_invalid', $e->getMessage());
                // If we are adding filter on non-existent attribute
            }
        }

        $result = array();
        foreach ($collection as $megamenu) {
            $result[] = $megamenu->toArray();
        }

        return $result;
    }

    public function update($menuId, $menuData)
    {
        $megamenu = Mage::getModel('ves_megamenu/megamenu')->load($customerId);

        if (!$megamenu->getId()) {
            $this->_fault('not_exists');
            // No megamenu found
        }

        $megamenu->addData($menuData)->save();
        return true;
    }

    public function delete($menuId)
    {
        $megamenu = Mage::getModel('ves_megamenu/megamenu')->load($customerId);

        if (!$megamenu->getId()) {
            $this->_fault('not_exists');
            // No megamenu found
        }

        try {
            $megamenu->delete();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('not_deleted', $e->getMessage());
            // Some errors while deleting.
        }

        return true;
    }
}