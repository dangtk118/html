<?php

class Fahasa_Tryout_Model_Tryouthistory extends Mage_Core_Model_Abstract {

    public function _construct() {
        $this->_init('tryout/tryouthistory');
    }

    public function loadByEmail($customer_email, $page = 1, $pageSize = 20, $type = 'fpoint') {
        $collection = $this->getCollection()
                ->addFieldToFilter('account', $customer_email)
                ->addFieldToFilter(
			array('type'),
			array(
			    array(
				array('eq'=> $type),
				array('eq'=> 'fpoint_combo')
				)
			)
		    )
                ->setOrder('lastUpdated', 'DESC')
                ->setPageSize($pageSize)
                ->setCurPage($page);
        return $collection->getItems();
    }

    public function loadByCustomerId($customer_id, $page = 1, $pageSize = 20, $type = 'fpoint') {
        $collection = $this->getCollection()
                ->addFieldToFilter('customer_id', $customer_id)
                ->addFieldToFilter(
			array('type'),
			array(
			    array(
				array('eq'=> $type),
				array('eq'=> 'fpoint_combo')
				)
			)
		    )
                ->setOrder('lastUpdated', 'DESC')
                ->setPageSize($pageSize)
                ->setCurPage($page);
        return $collection->getItems();
    }

}
