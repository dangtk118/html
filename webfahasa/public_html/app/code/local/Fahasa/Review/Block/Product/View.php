<?php

class Fahasa_Review_Block_Product_View extends Mage_Review_Block_Product_View {

    public function getReviewsCollection() {
        if ($_POST['sorter']) {
            $sorter = $_POST['sorter'];
        } else {
            $sorter = array_key_exists('sorter', $_GET) ? $_GET['sorter'] : null;
        }
        if (null === $this->_reviewsCollection) {
            $this->_reviewsCollection = Mage::getModel('review/review')
                    ->getCollection()
                    //->addStoreFilter(Mage::app()->getStore()->getId())
                    ->addEntityFilter('product', $this->getProduct()->getId())
                    ->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED);
            $this->_reviewsCollection->getSelect()
                    ->joinLeft(
                            array('ra' => 'fhs_reviews_action'), 
                            'main_table.review_id = ra.review_id and ra.type = "like"', 
                            array("sum(if(ra.customer_email is null, 0, 1)) as countLike")
                    )
                    ->group("main_table.review_id")
            ;
            if ($sorter == 'last-review') {
                $this->_reviewsCollection->setDateOrder();
            } else {
                $this->_reviewsCollection
                        ->setOrder('countLike', 'DESC')
                        ->setDateOrder();
            }
        }
        return $this->_reviewsCollection;
    }

}
