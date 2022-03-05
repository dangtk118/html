<?php

class Fahasa_Phanloaivct_Helper_Data extends Mage_Core_Helper_Abstract{
    /**
     * Lay ten khu vuc theo ID
     * @param type $id 
     * @return type
     */
    // get value from/to && express_from/express_to
    public function getLabelValue($id,$variable){
        $lb = null;
        $kvModel = Mage::getModel('phanloaivct/khuvuc')->getCollection()->addFieldToFilter('khuvuc_id',$id);
        $kv_froms = current($kvModel->getData('khuvuc_from'));
        foreach($kv_froms as $key => $value){
            if($key == $variable){
                $lb = $value;
            }
        }
        return $lb;
    }
}