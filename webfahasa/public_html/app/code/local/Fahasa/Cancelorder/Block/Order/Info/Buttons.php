<?php

/**
 * 
 * @author Thang Pham
 */
class Fahasa_Cancelorder_Block_Order_Info_Buttons extends Mage_Sales_Block_Order_Info_Buttons{
    
    /**
     * This will create URL post to just cancel order that are in pending state. 
     * Simply by set state/status in db of this order as cancelled
     */
    public function cancelOrderPending($order)
    {
        return $this->getUrl('sales/order/cancelOrderPending', array('order_id' => $order->getId()));
    }
    
    /**
     * This will create URL post to cancel order that are not in pending, complete,
     * cancelled, returned status/state.
     */
    public function cancelOrderRest($order){
        return $this->getUrl('sales/order/cancelOrderRest', array('order_id' => $order->getId()));
    }
}
