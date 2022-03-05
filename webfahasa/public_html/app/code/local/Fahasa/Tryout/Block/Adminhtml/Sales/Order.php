<?php
class Fahasa_Tryout_Block_Adminhtml_Sales_Order extends Mage_Sales_Block_Order_Totals {

    protected function _initTotals() {
        parent::_initTotals();
        $amt = $this->getSource()->getTryoutDiscount();
        $baseAmt = $this->getSource()->getBaseTryoutDiscount();
        if ($amt != 0) {
            $this->addTotal(new Varien_Object(array(
                        'code' => 'tryout_discount',
                        'value' => $amt,
                        'base_value' => $baseAmt,
                        'label' => 'F-point Discount',
                    )), 'tryout_discount');
        }
        return $this;
    }

}
