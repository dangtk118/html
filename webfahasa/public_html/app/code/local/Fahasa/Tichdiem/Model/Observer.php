<?php

/**
 * Observer for catching the close on the order, to see if it is a success process
 * order to give user points.
 * @author phamtn8
 */
class Fahasa_Tichdiem_Model_Observer {
    
    /**
     * Tich diem cho khach hanh hoac cancel diem
     * @param type $observer
     */
    public function td_order_load_after($observer){        
        if(isset($observer['status'])){
            $status = $observer['status'];
            if($status == Mage_Sales_Model_Order::STATE_COMPLETE){
                Mage::log("Fahasa_Tichdiem_Model_Observer::td_order_load_after: status is 'complete'");
                $order = $observer['order'];
                //Tich diem cho khach hang
                Mage::getModel("tichdiem/score_scorerule")->tichdiem($order->getGrandTotal(), $order->getCustomerEmail(), $order->getIncrementId());                                
            }else if($status == Fahasa_Tichdiem_Model_Order::FAHASA_REFUND_STATUS){
                Mage::log("Fahasa_Tichdiem_Model_Observer::td_order_load_after: status is 'fhs_refund'");
                $order = $observer['order'];
                Mage::getModel("tichdiem/score_scorerule")->truDiem($order->getIncrementId());
            }
        }else{
            Mage::log("Fahasa_Tichdiem_Model_Observer::td_order_load_after: ERROR: status is missing -- ".print_r($observer->getData(), true));
        }
    }
    
    /**
     * add 'Fahasa Refund' only when order is complete
     * @param type $observer
     */
    public function addTichdiemOrderRefundButton($observer){
        $block = $observer->getBlock();        
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View) {
            $order = $block->getOrder();
            $state = $order->getState();            
            if($state == Mage_Sales_Model_Order::STATE_COMPLETE){
                //Only display 'Refund' when order complete
                $message = Mage::helper('tichdiem')->__('Are you sure you want to refund this order?');
                $block->addButton('fhs_refund', array(
                    'label'     => Mage::helper('tichdiem')->__('Fahasa Refund'),
                    'onclick'   => "confirmSetLocation('{$message}', '{$block->getUrl('*/adminhtml_canceltd/cancel')}')"                    
                ));
            }else{
                $block->removeButton('fhs_refund');
            }            
        }
    }
}
