<?php

class Fahasa_Freeship_Model_Observer {

    public function freeshipUpdate($observer) {
        $helper = Mage::helper('freeship');
        $order = $observer->getEvent()->getOrder();
        $customerEmail = $order->getCustomerEmail();
        $customerId = $order->getCustomerId();
        if ($customerId) {
            if ($order->getIsFreeship() == 1 && $order->getShippingMethod() == "vietnamshippingnormal_vietnamshippingnormal") {
                $numFreeship = Mage::helper('freeship')->getFreeShip();
                if ((int) $numFreeship > 0) {
                    $data = array();
                    $data['customer_email'] = $customerEmail;
                    $data['customer_id'] = $customerId;
                    $data['action'] = "create order";
                    $data['amount'] = "-1";
                    $data['leftOver'] = $numFreeship - 1;
                    $data['order_id'] = $order->getIncrementId();
                    $data['description'] = "create order";
                    $data['amountBefore'] = $numFreeship;
                    $helper->updateCustomerFreeShip($data);
                    $helper->writeActionLogFreeShip($data);
                } else {
                    $this->unsetFreeshipOrder($order);
                    Mage::log("ERROR freeship after < 0: " . $customerId . ". UPDATE fpoint when pay order #" . $order->getIncrementId() .
                            ": current fpoint prior update: " . $numFreeship . ", fpoint after update: " . ($numFreeship - 1), null, "fpoint.log");
                }
            } else {
                $this->unsetFreeshipOrder($order);
            }
        } else {
            $this->unsetFreeshipOrder($order);
            Mage::log("No Email for order #" . $order->getIncrementId() .
                    ". But this order have freeship payment ", null, "fpoint.log");
        }
        // uncheck freeship button
        Mage::getSingleton('checkout/session')->unsetData('onestepcheckout_freeship');
        Mage::getSingleton('checkout/session')->unsetData('onestepcheckout_freeship_amount');
    }

    // unset freeship in order
    public function unsetFreeshipOrder($order) {
        $order->setIsFreeship(0);
        $order->setFreeshipAmount(0);
    }

}
