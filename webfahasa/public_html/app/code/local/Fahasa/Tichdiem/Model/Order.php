<?php
/**
 * Override Mage_Sales_Model_Order:_setState method to fire event, so we can 
 * see when an order move to "complete" or "fhs_refunded" state
 * @author phamtn8
 */
class Fahasa_Tichdiem_Model_Order extends Mage_Sales_Model_Order{
    
    const FAHASA_REFUND_STATUS = 'fhs_refund';
    
    /**
     * Order state protected setter.
     * By default allows to set any state. Can also update status to default or specified value
     * Сomplete and closed states are encapsulated intentionally, see the _checkState()
     *
     * @param string $state
     * @param string|bool $status
     * @param string $comment
     * @param bool $isCustomerNotified
     * @param $shouldProtectState
     * @return Mage_Sales_Model_Order
     */
    protected function _setState($state, $status = false, $comment = '',
        $isCustomerNotified = null, $shouldProtectState = false)
    {
        // dispatch an event before we attempt to do anything
        Mage::dispatchEvent('sales_order_status_before', 
                array('order' => $this, 'state' => $state, 'status' => $status, 'comment' => $comment, 
                    'isCustomerNotified' => $isCustomerNotified, 'shouldProtectState' => $shouldProtectState));
        // attempt to set the specified state
        if ($shouldProtectState) {
            if ($this->isStateProtected($state)) {
                Mage::throwException(
                    Mage::helper('sales')->__('The Order State "%s" must not be set manually.', $state)
                );
            }
        }
        $this->setData('state', $state);

        // add status history
        if ($status) {
            if ($status === true) {
                $status = $this->getConfig()->getStateDefaultStatus($state);
            }
            $this->setStatus($status);
            $history = $this->addStatusHistoryComment($comment, false); // no sense to set $status again
            $history->setIsCustomerNotified($isCustomerNotified); // for backwards compatibility
        }
        // dispatch an event after status has changed
        Mage::dispatchEvent('sales_order_status_after', 
                array('order' => $this, 'state' => $state, 'status' => $status, 'comment' => $comment, 
                    'isCustomerNotified' => $isCustomerNotified, 'shouldProtectState' => $shouldProtectState));
        return $this;
    }
}
