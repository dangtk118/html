<?php
class Fahasa_Reviewsaction_Model_Reviewsaction extends Mage_Core_Model_Abstract{
    
    public function _construct() {
        $this->_init('reviewsaction/reviewsaction');
    }
    public function getCustomerLiked($review_id, $customer_email){
        $collection = $this->getCollection()
            ->addFieldToFilter('customer_email', $customer_email)
            ->addFieldToFilter('review_id', $review_id);
        return $collection;
    }
    
    public function getCustomerLikedList($review_ids, $customer_email){
        $collection = $this->getCollection()
            ->addFieldToFilter('customer_email', $customer_email)
            ->addFieldToFilter('review_id', array('in' => $review_ids));
        return $collection;
    }
    
    public function getCountLike($review_id){
        $collection = $this->getCollection()
            ->addFieldToFilter('type', "like")
            ->addFieldToFilter('review_id', $review_id);
        return $collection->getSize();
    }
}
