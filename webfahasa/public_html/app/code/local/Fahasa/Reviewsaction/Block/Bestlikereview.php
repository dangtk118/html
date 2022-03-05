<?php

class Fahasa_Reviewsaction_Block_Bestlikereview extends Mage_Review_Block_Product_View {

    public function getBestLikeReview($productCollection) {
        $this->_reviewsCollection = Mage::getModel('review/review')
                ->getCollection()
                ->addStoreFilter(Mage::app()->getStore()->getId())
                ->addEntityFilter('product', $productCollection->getId())
                ->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED);
        $this->_reviewsCollection->getSelect()
                    ->joinLeft(
                            array('ra' => 'fhs_reviews_action'), 
                            'main_table.review_id = ra.review_id and ra.type = "like"', 
                            array("sum(if(ra.customer_email is null, 0, 1)) as countLike")
                    )
                    ->group("main_table.review_id")
            ;
        $this->_reviewsCollection->setOrder('countLike', 'DESC');
        return $this->_reviewsCollection->getFirstItem();
    }

}