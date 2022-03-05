<?php

class Fahasa_Freeship_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getFreeShip() {
	$result = 0;
	if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return 0;
        }
        $customer = Mage::getSingleton('customer/session')->getCustomer();
	if(Mage::registry('is_gat_customer_info_rest')) {
	    $result = Mage::helper("fahasa_customer/fpoint")->getFreeship($customer);
	}else{
	    $result = Mage::helper("fahasa_customer/fpoint")->getFreeship($customer, true);
	}
	return $result;
    }

    public function writeActionLogFreeShip($data) {
        Mage::log($data['customer_id'] . "-" . $data['customer_email'] . ". "
                . "writeActionLog() freeship Before=" . $data['amountBefore'] . ", After=" . $data['leftOver'], null, "fpoint.log");
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = "INSERT INTO `fhs_purchase_action_log` "
                . "("
                . "`account`, "
                . "`customer_id`, "
                . "`action`, "
                . "`value`, "
                . "`amountAfter`, "
                . "`updateBy`, "
                . "`lastUpdated`, "
                . "`order_id`, "
                . "`description`, "
                . "`amountBefore`, "
                . "`type`) "
                . "  VALUES ("
                . "'" . $data['customer_email'] . "',"
                . "'" . $data['customer_id'] . "',"
                . "'" . $data['action'] . "',"
                . "'" . $data['amount'] . "',"
                . "'" . $data['leftOver'] . "',"
                . "'magento',"
                . "'" . now() . "',"
                . "'" . $data['order_id'] . "',"
                . "'" . $data['description'] . "',"
                . "'" . $data['amountBefore'] . "',"
                . "'freeship'"
                . ");";
        Mage::log($data['customer_id'] . "-" . $data['customer_email'] . ". writeActionLog() freeship query insert log=" . $query, null, "fpoint.log");
        $write->query($query);
    }

    public function updateCustomerFreeShip($data) {
        Mage::log($data['customer_id'] . "-" . $data['customer_email'] . ". UPDATE freeship when pay order #" . $data['order_id'] .
                ": Current freeship prior update: " . $data['amountBefore'] . ", freeship after update: " . $data['leftOver'], null, "fpoint.log");
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = "update fhs_customer_entity "
                . "set "
                . "num_freeship = " . $data['leftOver'] . " "
                . "where "
                . "email='" . $data['customer_email'] . "';";
        Mage::log($data['customer_id'] . "-" . $data['customer_email'] . ". UPDATE freship when pay order: query=" . $query, null, "fpoint.log");
        $write->query($query);
    }

}
