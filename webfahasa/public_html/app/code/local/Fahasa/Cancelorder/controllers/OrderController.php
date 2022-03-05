<?php

/**
 * 
 *
 * @author Thang Pham
 */
require_once(Mage::getModuleDir('controllers','Mage_Sales').DS.'OrderController.php');
class Fahasa_Cancelorder_OrderController extends Mage_Sales_OrderController {
    
    /**
     * This will cancel order that are in pending state. 
     * Simply by set state/status in db of this order as cancelled
     */
    public function cancelOrderPendingAction()
    {
        Mage::log("cancelOrderPendingAction begin ", null, "magento.log");
        if (!$this->_loadValidOrder()) {
            return;
    }
    
        $helper = Mage::helper('cancelorder');
        
        $order = Mage::registry('current_order');
        Mage::log("cancelOrderPendingAction canceling order id " . $order->getIncrementId() . " with status " . $order->getStatus() , null, "magento.log");
	$helper->setStateCancelOrder($order);
        Mage::log("cancelOrderPendingAction success cancel order id " . $order->getIncrementId(), null, "magento.log");
        $this->_redirect('sales/order/view/order_id/'.$order->getEntityId());
    }
    
     /**
      * This will cancel order that are not in pending, complete,
      * cancelled, returned status/state. This will call REST api that cancelled
      * all suborder, notify customer ... 
      */
    public function cancelOrderRestAction()
    {
        Mage::log("cancelOrderRestAction begin ", null, "magento.log");
	$result = $this->_loadValidOrder();
	
	$order = Mage::registry('current_order'); 
	    
	if(!empty($order)){
	    $increment_id = $order->getIncrementId();  
	    $order_id = $order->getEntityId();
	}
	
        if ($result && !empty($order_id) && !empty($increment_id)){
	    Mage::helper('cancelorder')->cancelOrderREST($order, $increment_id);
	    Mage::log("cancel order: ".$increment_id.", order_id: ".$order_id, null, "magento.log");
	}
	
	if(!empty($order_id)){
	    $this->_redirect('sales/order/view/order_id/'.$order_id);
	}else{
	    $this->_redirect('/sales/order/history/');
	}
    }
    
    public function cancelOrderRest($order_id, $increment_id)
    {
	$result = array(
	    'success'=>true,
	    'message'=>$this->__("Cancel fail on order %s. Please call us at 1900636467.", $increment_id),
	    'result'=> false
	);
        Mage::log("cancelOrderRestAction begin ", null, "magento.log");
	$result_validate = $this->_loadValidOrder($order_id);
	
	$order = Mage::registry('current_order'); 
	if(!empty($order)){
	    $increment_id = $order->getIncrementId();  
	    $order_id = $order->getEntityId();
	}
	
        if ($result_validate && !empty($order_id) && !empty($increment_id)){
	    $result['result'] = Mage::helper('cancelorder')->cancelOrderREST($order, $increment_id);
	}
	
	if(!empty($order_id)){
	    if($result['result']){
		$result['message'] = $this->__('Order %s has been successfully cancelled.', $increment_id);
	    }else{
		$result['message']  = $this->__("Cancel fail on order %s. Please call us at 1900636467.", $increment_id);
	    }
	}
	return $result;
    }
    
    public function insertOrderCancelReasonAction() {
        Mage::log("insertOrderCancelReasonAction begin ", null, "magento.log");
        $order_id = $_POST['order_id'];
        $increment_id = $_POST['increment_id'];
        $customer_email = $_POST['customer_email'];
        $reason_id = (int) $_POST['reason_id'];
        $reason_description = $_POST['reason_description'];
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $query = "INSERT INTO fhs_order_cancel_reason(order_id, customer_email, reason_id, reason_description) 
                    VALUES (:order_id, :customer_email,:reason_id, :reason_description)
                    ON DUPLICATE KEY UPDATE 
                    customer_email=:customer_email,
                    reason_id=:reason_id,
                    reason_description=:reason_description;";
        //$query = "insert into fhs_order_cancel_reason(order_id, customer_email, reason_id, reason_description) VALUE (:order_id, '$order_id', '$customer_email',$reason_id, '$reason_description')";
        
        $query_binding = array(
            'order_id' => $increment_id,
            'customer_email' => $customer_email,
            'reason_id' => (int) $reason_id,
            'reason_description' => $reason_description
        );
        
        $writeConnection->query($query, $query_binding);
	
	$result = $this->cancelOrderRest($order_id, $increment_id);
	
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
}
