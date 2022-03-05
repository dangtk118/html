<?php

class Fahasa_Redeem_Helper_Data extends Mage_Core_Helper_Abstract {

    public function redeemFpoint($redeemCode) {
        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
        Mage::log("** Redeem False: userId:" . $customerId . ", with redeemCode:" . $redeemCode, null, 'redeem.log');
        try {
            $customer = $this->getCustomerData($customerId);
            if ($customer !== null) {
                $redeem = Mage::getModel("redeem/redeem")->loadByCode(strtoupper($redeemCode));
                if ($redeem->getId() == null) {
                    return "ERROR_CODE_INVALID";
                } elseif ($redeem->getIsUsed() == 1) {
                    return "ERROR_CODE_HAS_BEEN_USED";
                } elseif ($redeem->getExpiredAt() < now()) {
                    return "ERROR_CODE_EXPIRED";
                } elseif ($redeem->getActive() == 0) {
                    return "ERROR_CODE_IS_LOCKED";
                } else {
                    // update redeem
                    $redeem->setIsUsed(1)->save();
                    $action_purchase = "redeem";
                    $description_purchase = "Redeem: code: ".$redeemCode;
                    if ($redeem->getFpointValue() > 0) {
			Mage::helper("fahasa_customer/fpoint")->transationFpoint($customerId, $redeem->getFpointValue(), 'fpoint', "Redeem_fpoint", $description_purchase);
                    }
                    if ($redeem->getFreeshipValue() > 0) {
			Mage::helper("fahasa_customer/fpoint")->transationFpoint($customerId, $redeem->getFreeshipValue(), 'freeship', "Redeem_freeship", $description_purchase);
		    }
                }
            } else {
                return "ERR_NEED_LOGIN";
            }
        } catch (Exception $e) {
            Mage::log("** Redeem False: userId:" . $customerId . ", with redeemCode:" . $redeemCode . ", mess: " . $e->getMessage(), null, 'redeem.log');
            return "SERVER_ERROR";
        }
    }

    /**
     * 
     * @param type $customerId
     * no use load model magento because Fpoint,num_freeship,... manual insert, not entity
     */
    public function getCustomerData($customerId) {
        try {
            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $query = "select * from fhs_customer_entity where entity_id = " . $customerId . ";";
            Mage::log("**getCustomerData : customerId:" . $customerId . ", query: " . $query, null, 'redeem.log');
            $rs = $readConnection->fetchAll($query);
            $data = count($rs) > 0 ? $rs[0] : null;
            return $data;
        } catch (Exception $e) {
            Mage::log("**getCustomerData Exception: redeemCode:" . $redeemCode . ", email:" . $customer["email"] . ", error:" . $e->getMessage(), null, 'redeem.log');
            return FALSE;
        }
    }

    public function updateCustomerData($customerId, $key, $value) {
        try {
            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
            $query = "update fhs_customer_entity set " . $key . " =  " . $value . " where entity_id = " . $customerId . ";";
            Mage::log("**updateCustomerData : customerId:" . $customerId . ", sql:" . $query, null, 'redeem.log');
            $writeConnection->query($query);
            return;
        } catch (Exception $e) {
            Mage::log("**updateCustomerData Exception: redeemCode:" . $redeemCode . ", email:" . $customer["email"] . ", error:" . $e->getMessage(), null, 'redeem.log');
            return FALSE;
        }
    }

    public function insertPurchaseActionLog($customer, $redeemCode, $type, $beforeValue, $value, $afterValue) {
        try {
            $write = Mage::getSingleton("core/resource")->getConnection("core_write");
            $sql = "insert into fhs_purchase_action_log (account , customer_id , action , value , amountAfter , updateBy ,lastUpdated, description , amountBefore , type, redeem_code) "
                    . "values ('" . $customer["email"] . "', " . $customer["entity_id"] . ", 'redeem', " . $value . ", " . $afterValue . ", 'admin', now(), 'Redeem " . $type . " with code: " . $redeemCode . "', " . $beforeValue . ", '" . $type . "', '" . $redeemCode . "');";
            Mage::log("**insertPurchaseActionLog: customerId: " . $customer["entity_id"] . ",sql:" . $sql, null, 'redeem.log');
            $write->query($sql);
            return TRUE;
        } catch (Exception $e) {
            Mage::log("**insertPurchaseActionLog Exception: redeemCode:" . $redeemCode . ", email:" . $customer["email"] . ", error:" . $e->getMessage(), null, 'redeem.log');
            return FALSE;
        }
    }

}
