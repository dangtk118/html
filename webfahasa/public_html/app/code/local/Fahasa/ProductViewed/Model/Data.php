<?php

class Fahasa_ProductViewed_Model_Data extends Mage_Eav_Model_Entity_Attribute
{
    public function MergeProductViewed($event){
	$customer = $event->getEvent();
	$customer_id = $customer->getCustomer()->getEntityId();
	Mage::helper('productviewed')->mergeProductViewedSessionToDB($customer_id);
	Mage::helper('productviewed')->mergeSearchHistorySessionToDB($customer_id);
	return;
    }
    
}
