<?php

class Fahasa_Redeem_Model_Redeem extends Mage_Core_Model_Abstract {

    protected function _construct() {

        $this->_init("redeem/redeem");
    }
    
    public function loadByCode($redeemCode) {
        $collection = $this->getCollection()
                ->addFieldToFilter('redeem_code', $redeemCode);
        return $collection->getFirstItem();
    }

}
