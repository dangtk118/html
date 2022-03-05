<?php

class Fahasa_Freeshipamount_Block_Sales_Order_Freeshipamount extends Mage_Sales_Block_Order_Totals {

    public function getFreeshipAmount()
    {
        $order = $this->getOrder();
        return $order->getFreeshipAmount();
    }

    public function getBaseFreeshipAmount()
    {
        $order = $this->getOrder();
        return $order->getFreeshipAmount();
    }

    public function initTotals()
    {
        $amount = $this->getFreeshipAmount();
        if (floatval($amount) < 0)
        {
            $total = new Varien_Object();
            $total->setCode('freeship_amount');
            $total->setValue($amount);
            $total->setBaseValue($this->getFreeshipAmount());
            $total->setLabel($this->__('Freeship Amount'));
            $parent = $this->getParentBlock();
            $parent->addTotal($total, 'shipping');
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
