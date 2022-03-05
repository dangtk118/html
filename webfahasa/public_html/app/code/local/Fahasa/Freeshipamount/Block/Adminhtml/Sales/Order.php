<?php
class Fahasa_Freeshipamount_Block_Adminhtml_Sales_Order extends Mage_Sales_Block_Order_Totals {

    protected function initTotals() {
        parent::_initTotals();
//        $amt = 1000;
//        if ($amt != 0) {
//            $this->addTotal(new Varien_Object(array(
//                        'code' => 'freeship_amount',
//                        'value' => $amt,
//                        'base_value' => $amt,
//                        'label' => 'Freeship Discount',
//                    )), 'freeship_amount');
//        }
        return $this;
    }

}
