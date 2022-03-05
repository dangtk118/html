<?php

class Fahasa_Codfee_Model_Rule extends Magebuzz_Vietnamshipping_Model_Rule {

    protected function getModuleStr() {
        return "codfee";
    }

    public function save() {
        $this->setData("apply_to_shipping", "discount_shipping_normal");
        parent::save();
    }
}
