<?php
class Fahasa_Review_Model_Resource_Review_Collection extends Mage_Review_Model_Resource_Review_Collection{
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(Zend_Db_Select::GROUP);
        return $countSelect;
    }      
}