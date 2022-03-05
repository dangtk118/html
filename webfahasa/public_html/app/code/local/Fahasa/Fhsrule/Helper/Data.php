<?php

class Fahasa_Fhsrule_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getRuleData($ruleIds) {
        $colections = Mage::getModel('salesrule/rule')->getCollection();
        $colections->getSelect()
                ->joinLeft(array("od" => "fhs_sales_flat_order"), "rule_coupons.code=od.coupon_code", array("times_used" => "sum(if(od.increment_id is not null, 1, 0))"))
                ->where("main_table.rule_id in ($ruleIds)")
                ->group("main_table.rule_id");
        $rules = array();
        foreach ($colections->getItems() as $rule) {
            $ruleData["ruleId"] = $rule->getId();
            $ruleData["fromDate"] = $rule->getFromDate();
            $ruleData["toDate"] = $rule->getToDate();
            $ruleData["code"] = $rule->getCode();
            $ruleData["usesPerCoupon"] = $rule->getUsesPerCoupon();
            $ruleData["timesUsed"] = $rule->getTimesUsed();
            $ruleData["isActive"] = $rule->getIsActive();
            $rules[] = $ruleData;
        }
        return $rules;
    }

}
