<?php

class Fahasa_Freeshipamount_Block_Sales_Order_OriginalShippingFee extends Mage_Sales_Block_Order_Totals {

    public function getOriginalShippingFee()
    {
        $order = $this->getOrder();
        return $order->getOriginalShippingFee();
    }

    

    public function initTotals()
    {
        $amount = $this->getOriginalShippingFee();
        if (floatval($amount))
        {
            $total = new Varien_Object();
            $total->setCode('original_shipping_fee');
            $total->setValue($amount);
            $total->setBaseValue($this->getOriginalShippingFee());
            $total->setLabel($this->__('Original Shipping Fee'));
            $parent = $this->getParentBlock();
            $parent->addTotal($total, 'subtotal');
        }
    }

    public function getOrder()
    {
        if (!$this->hasData('order'))
        {
            $order = $this->getParentBlock()->getOrder();
            $this->setData('order', $order);
        }
        return $this->getData('order');
    }

}
