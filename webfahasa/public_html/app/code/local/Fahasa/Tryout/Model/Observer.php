<?php

class Fahasa_Tryout_Model_Observer {

    // mua hang, - tien trong tai khoan vip
    public function updateMoneyTryout($orderObj) {
        $order = $orderObj->getOrder();
        $coinCode = $order->getDiscountDescription();
        $results = Mage::helper('tryout')->checkCoin($coinCode);
        if ($results['currentAmount'] > 0) {
            $this->updateMoneyFhsCoin($order);
        } else {
            $money = round($order->getTryoutDiscount());
            if($money != null && $money != 0){
                $customerEmail = $order->getCustomerEmail();
                $customerId = $order->getCustomer()->getId();
                if ($customerId) {
                    $currentAmountFpointAccount = Mage::helper('tryout')->determinetryout();
                    $tryoutMoney = $currentAmountFpointAccount + $money;
                    if($tryoutMoney >= 0){
                        Mage::log($customerId . "-" . $customerEmail . ". UPDATE fpoint when pay order #". $order->getIncrementId() . 
                                ": Fpoint discount:" . $money . ", current fpoint prior update: " . $currentAmountFpointAccount . ", fpoint after update: " . $tryoutMoney, 
                                null, "fpoint.log");
                        $resource = Mage::getSingleton('core/resource');
                        $writeConnection = $resource->getConnection('core_write');
                        $query = 'update fhs_customer_entity set fpoint=' . $tryoutMoney . ' where entity_id="' . $customerId . '";';
                        Mage::log($order->getCustomerEmail() . ". UPDATE fpoint when pay order: query=" . $query, null, "fpoint.log");
                        $results = $writeConnection->query($query);


                        //write action log for this order payment with fpoint
                        $amountAfter = $tryoutMoney;
                        $amountBefore = $currentAmountFpointAccount;
                        $orderId = $order->getIncrementId();
                        Mage::log($customerId . "-" . $customerEmail . ". writeActionLog() amountBefore=" . $amountBefore . ", amountAfter=" . $amountAfter, null, "fpoint.log");
                        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
                        $query1 = "INSERT INTO `fhs_purchase_action_log` (`customer_id`, `account`, `action`, `amountBefore`, `value`, `amountAfter`, `updateBy`, `lastUpdated`, `order_id`,`suborder_id`, `description`, `type`) "
                                . "  VALUES (" . $customerId . ", '" . $customerEmail . "','pay order f-point'," . $amountBefore . "," . $money . "," . $amountAfter . ",'magento','"
                                . date('Y-m-d H:i:s') . "','" . $orderId . "','','Paid through web', 'fpoint')";
                        Mage::log($customerEmail . ". writeActionLog() query insert log=" . $query1, null, "fpoint.log");
                        $write->query($query1);
                    }else{
                        Mage::log("ERROR fpoint after < 0: " . $customerId . ". UPDATE fpoint when pay order #" . $order->getIncrementId() .
                                ": Fpoint discount:" . $money . ", current fpoint prior update: " . $currentAmountFpointAccount . ", fpoint after update: " . $tryoutMoney, null, "fpoint.log");
                    }
                }else{
                    Mage::log("User not login for order #". $order->getIncrementId() . 
                            ". But this order have fpoint payment ", null, "fpoint.log");
                }
            } else {
                return FALSE;
            }
        }
    }

    // update fhs_coin
    public function updateMoneyFhsCoin($order) {
        try {
            $coinObj = Mage::getSingleton('core/session')->getFhsCoin();
            $results = Mage::helper('tryout')->checkCoin($coinObj['code']);
            if ($results['currentAmount'] > 0) {
                if ($results['currentAmount'] > (-$order->getDiscountAmount())) { // currentAmount > grandtotal
                    $money = $results['currentAmount'] - ($order->getDiscountAmount() * -1);
                } else {
                    $money = 0;
                }
                $resource = Mage::getSingleton('core/resource');
                $writeConnection = $resource->getConnection('core_write');
                $query = 'update fhs_coin set current_amount = "' . $money . '" where code = "' . $results['code'] . '";';
                Mage::log("updateMoneyFhsCoin order #" . $order->getIncrementId() . ", query = " . $query , null, "fhs_coin.log");
                $results = $writeConnection->query($query);
            }
            Mage::getSingleton('core/session')->setFhsCoin(null);
        } catch (Exception $ex) {
            Mage::log("can't updateMoneyFhsCoin order #" . $order->getIncrementId(), null, "fhs_coin.log");
        }
    }

    //Currently only write coin action log
    public function writeActionLog($observer) {
        $order = $observer->getEvent()->getOrder();
        if ($order) {
            $this->writeActionLogFhsCoin($order);
        }
    }

    public function writeActionLogFhsCoin($order) {
        $coinCode = $order->getDiscountDescription();
        $coinDiscountAmount = $order->getDiscountAmount();
        $results = Mage::helper('tryout')->checkCoin($coinCode, 1);
        if ($results) {
            $amountAfter = $results['currentAmount'];
            $amountBefore = $amountAfter + ($coinDiscountAmount * -1);
            $orderId = $order->getIncrementId();
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $query = "INSERT INTO `fhs_coin_action_log` (`code`, `action`, `amountBefore`, `value`, `amountAfter`, `updateBy`, `lastUpdated`, `order_id`,`suborder_id`, `description`) "
                    . "  VALUES ('" . $coinCode . "','pay order'," . $amountBefore . "," . $coinDiscountAmount . "," . $amountAfter . ",'magento','"
                    . date('Y-m-d H:i:s') . "','" . $orderId . "','','Paid through web')";
            Mage::log("writeActionLogFhsCoin query order #" . $orderId . ", query = " . $query, null, "fhs_coin.log");
            $write->query($query);
        }
        Mage::getSingleton('core/session')->setFhsCoin(null);
    }

    public function refundMoneyTryout($observer) {
        // da xu ly refund o app.fahasa.com
        
    }

}
