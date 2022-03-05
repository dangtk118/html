<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(Mage::getModuleDir('controllers','Mage_Review').DS.'ProductController.php');

class Fahasa_Review_ProductController extends Mage_Review_ProductController {
    public function _loadReview($reviewId)
    {
        if (!$reviewId) {
            return false;
        }

        $review = Mage::getModel('review/review')->load($reviewId);
        /* @var $review Mage_Review_Model_Review */
        if (!$review->getId() || !$review->isApproved()) {
            return false;
        }

        Mage::register('current_review', $review);

        return $review;
    }
}