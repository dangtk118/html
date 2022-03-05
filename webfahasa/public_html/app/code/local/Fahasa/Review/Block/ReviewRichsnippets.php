<?php

class Fahasa_Review_Block_Product_View_ReviewRichSnippets extends Mage_Review_Block_Product_View {

    public function getReviewRichSnippets() {
        $review = $this->getReviewsCollection();
        $json = array(
            "@context" => "http://schema.org/Review",
//            "url" => Mage::getBaseUrl() . $product->getUrlPath(),
            "name" => $review->getTitle(),
            "author" => $review->getNickname(),
            "datePublished",
            "reviewBody" => $review->getDetail()
            
        );
//        $ratingCount = $rating->getReviewsCount() == null ? 0 : $rating->getReviewsCount();
//
//        // hidden $aggregateRating when $ratingCount = 0;
//        if ($ratingCount > 0) {
//            $aggregateRating = array(
//                "@type" => "AggregateRating",
//                "ratingValue" => $rating->getRatingSummary() == null ? 0 : $rating->getRatingSummary(),
//                "bestRating" => "100",
//                "ratingCount" => $ratingCount,
//                "worstRating" => "0"
//            );
//            $json["aggregateRating"] = $aggregateRating;
//        }
        return json_encode($json, JSON_UNESCAPED_SLASHES);
    }
}
