<?php
class Fahasa_Salecustomfield_Helper_Data extends Mage_Core_Helper_Abstract{
    public function getDonViBanHangGiaoHang($order, $key){
        $value = $order->getData($key);
        if(empty($value)){
            return Mage::helper('fhsinvoice')->__('E-Commerce Department');
        }
        return $value;
    }
}