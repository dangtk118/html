<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Cancelscore
 *
 * @author Thang Pham
 */
class Fahasa_Tichdiem_Adminhtml_CanceltdController extends Mage_Adminhtml_Controller_Action{        
    
    /**
     * This is happen when the user click button "Fahasa_Refund", which will set the 
     * status of the order to be "cancelled". And it also, subtract the point from 
     * the current user.
     */
    public function cancelAction(){
        $id = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($id);
        if ($order->getId()) {
            //set order to 'fhs_refund'
            try{                                
                $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, Fahasa_Tichdiem_Model_Order::FAHASA_REFUND_STATUS);
                $order->save();                
                $this->_getSession()->addSuccess(
                    $this->__('The order has been refunded.')
                );
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('The order has not been refunded.'));
                Mage::logException($e);
            }            
        }
        $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
    }
}
