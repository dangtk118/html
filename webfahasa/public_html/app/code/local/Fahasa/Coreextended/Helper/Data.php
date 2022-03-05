<?php

class Fahasa_Coreextended_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * Look through all the coupon code within the campaign rule_id, and return
     * the first available coupon code as string. The return  coupon code need
     * to have the 'sent' variable equal 0, meaning that it has been sent out.
     * Return false if found nothing     
     * @param $rule_id This is the id for the campaign coupon code rule 
     */
    function getAvailableCouponCode($rule_id) {
        $collection = Mage::getModel("salesrule/coupon")->getCollection()->addFieldToFilter('rule_id', $rule_id)
                ->addFieldToFilter('times_used', 0)
                ->addFieldToFilter('sent', 0);
        $collection->getSelect()->limit(1, 1);

        foreach ($collection as $coupon) {
            $code = $coupon->getCode();
            return $code;
        }
        return false;
    }

    /**
     * Mark the given coupon code as sent in the databases;
     * @param type $couponCode
     */
    function markCouponCodeAsSent($couponCode) {
        $collection = Mage::getModel("salesrule/coupon")->getCollection()->addFieldToFilter('code', $couponCode);
        foreach ($collection as $coupon) {
            $coupon->setSent(1);
            $coupon->save();
        }
    }

    /**
     * get fahasa contact for order
     * ***/
    public function getFhsContactOrder($incretmentId) {
//        $incretmentId = 100152373;
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        //$query = "select * from fahasa_contact_log where order_id='" . $incretmentId . "' order by created_at desc limit 10;";
        $query = "select cl.*, bl.content as `block_content` from fahasa_contact_log cl "
                . "left join fhs_cms_block bl on bl.identifier = cl.block_id "
                . "where cl.order_id='" . $incretmentId . "' order by cl.created_at desc limit 10;";
        $data = $readConnection->fetchAll($query);
        return $data;
    }
    
    /**
     * update fahasa contact for order
     * 
     * ***/
    public function getSeenContactOrder($incretmentId, $listSeen){
        if($listSeen != null && $listSeen != ""){
            $resource = Mage::getSingleton('core/resource');
    //        $incretmentId = 100152373;
            $writeConnection = $resource->getConnection('core_write');
            $query = "update fahasa_contact_log set seen = 1 where order_id = '" . $incretmentId . "' and id in (" . $listSeen . ");";
            \Mage::log("*** getSeenContactOrder: " . $query, null, "restapi.log");
            $data = $writeConnection->query($query);
        }
    }
}
