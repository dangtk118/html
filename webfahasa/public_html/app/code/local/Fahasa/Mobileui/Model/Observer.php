<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * This observer constructs json ui for mobile by listening to block 
 * rendering events.
 *
 * @author trunglt
 */
class Fahasa_Mobileui_Model_Observer {

    public static $enable = false;
    private static $mobileBlocks = null;
    private static $thele = null;

    public static function initialize() {
        self::$mobileBlocks = array();
    }
    
    public function AfterBlockRenderEvent($observer) {
        if (self::$enable) {
            $block = $observer->getBlock();
            if (
                    strcmp($block->getType(), 'tabslider/tabslider1') == 0 ||
                    strcmp($block->getType(), 'cmsjson/block') == 0 ||
                    strcmp($block->getType(), 'custom_listing/products') == 0 ||
                    strcmp($block->getType(), 'custom_listing/attribute') == 0 ||
                    strcmp($block->getType(), 'fhsrule/block') === 0 ||
                    strcmp($block->getType(), 'event/block' === 0) ||
                    strcmp($block->getType(), 'flashsale/slider' == 0)
            ) {
                if (array_key_exists(spl_object_hash($block), self::$mobileBlocks)) {
                    self::$mobileBlocks[spl_object_hash($block)]['block'] = $block;
                } else {
                    self::$mobileBlocks[spl_object_hash($block)] = array(
                        'block' => $block,
                        'index' => count(self::$mobileBlocks)
                    );
                }
            }
        }
    }

    public static function getMobileJson() {
        $result = array();
        $blockThele = null;
        for ($i = 0; $i < count(self::$mobileBlocks); $i++) {

            //find the block with the $i index
            $block = null;
            foreach (self::$mobileBlocks as $blockHash => $value) {
                if ($value['index'] == $i) {
                    $block = $value['block'];
                    if($block['template'] == 'cmsjson/popup_rule_event.phtml'){
                        $blockThele = $block;
                    }
                    break;
                }
            }

            if ($block != null) {
                // get mobile json from the block                
                if (strcmp($block->getType(), 'tabslider/tabslider1') == 0) {
                    $result[] = self::tabSliderToMobileJson($block);
                } elseif (strcmp($block->getType(), 'cmsjson/block') == 0) {
                    $result[] = self::bannerMobileJson($block);
                } elseif (strcmp($block->getType(), 'custom_listing/products') == 0 || strcmp($block->getType(), 'custom_listing/attribute') == 0) {
                    $result[] = self::customListingMobileJson($block);
                }
                else if (strcmp($block->getType(), 'fhsrule/block') == 0){
                    $result[] = self::fhsRuleBlockMobileJson($block);
                }
                else if (strcmp($block->getType(), 'event/block') == 0){
                    $resultTemp = self::fhsEventMobileJson($block);
                    if (!empty($resultTemp)){
                        $result[] = $resultTemp;
                    }
                }
                else if (strcmp($block->getType(), 'flashsale/slider') == 0){
                    $result[] = self::blockFlashsaleSlider($block);
                }
                else if (strcmp($block->getType(), 'flashsale/page') == 0){
                    $result[] = self::blockFlashsalePage($block);
                }
                else if (strcmp($block->getType(), 'fahasa_customer/block') == 0){
                    $result[] = self::blockPersonalize($block);
                }
                else if (strcmp($block->getType(), 'productviewed/slider') == 0){
                    $result[] = self::blockProductViewed($block);
                }
                else if (strcmp($block->getType(), 'seriesbook/book') == 0){
                    $result[] = self::blockSeriesBook($block,"book");
                }
                else if (strcmp($block->getType(), 'seriesbook/set') == 0){
                    $result[] = self::blockSeriesBook($block,"set");
                }
                
            }
        }
        if (self::$thele && $blockThele) {
            self::$thele = null;
            $result[] = self::BlockThele($blockThele);
        }
        return $result;
    }
    
    private static function BlockThele($blockThele) {
        $result = array();
        $data = json_decode($blockThele['data']);
        $result["data"] = $data ;
        $result["type"] = "RuleEvents";
        return $result;
    }
    
    private static function tabSliderToMobileJson($block) {
        $result = array();
        
        if ($block->getTemplate() == 'tabslider/girdslider_page.phtml'){
            $result['type'] = 'grid_slider';
            if ($block['category_data']){
                $result['category_data'] = json_decode($block['category_data']);
            }else {
                $default_category = '[{"id": "all", "display_name": "Tất cả"}]';
                $result['category_data'] = json_decode($default_category);
            }
        }else{
            $result['type'] = 'productCollections';
        }
        $result['cmsType'] = 'slider';
        $result['title'] = $block->getTitle();
        $result['inlineBanner'] = self::inlineBannerJson($block);
        $result['topBanner'] = self::topBannerJson($block);
        $result['collections'] = array();
        $handleDiscount = Mage::helper('discountlabel/data');
        $blockData = $block->getData();
        $result['tabId'] =$blockData['tabId'];
        $result['tabGroup'] =$blockData['tabGroup'];
        $result['tabActive'] =$blockData['tabActive'];
        $tabSliderConfigStr = $blockData['data'];
        $tabSliderConfigs = json_decode($tabSliderConfigStr);
        for ($i = 0; $tabSliderConfigs != null && $i < count($tabSliderConfigs); $i++) {
            $config = $tabSliderConfigs[$i];

            //This sub collection is configured to be shown on mobile
            if (!array_key_exists('mobile', $config) || $config['mobile'] == true) {
                $j = array();
                $config = reset($config);
                $mobile_label = $config->mobile_label;
                if($mobile_label != null){
                    $j['label'] = $mobile_label;
                }else{
                    $j['label'] = $config->label;
                }
                if ($config->seeAllLink) {
                    $j['seeAllLink'] = $config->seeAllLink;
                }
                // check xem co showBar
                if(($config->showBar && $config->showBar == "true") || ($block->getTemplate() == 'tabslider/girdslider_page.phtml' && $block['showBar']))
                {
                    $j['showBar'] = true;
                }
                $j['limit'] = $config->limit;
                $j['sort_by'] = $config->sort_by;
                $j['min_ck'] = $config->min_ck;
                $j['max_ck'] = $config->max_ck;
                $j['category_id'] = $config->category_id;
                $j['block_type'] = $config->block_type;
                $j['attribute_code'] = $config->attribute_code;
                $j['attribute_value'] = $config->attribute_value;
                $j['attribute_data'] = $config->attribute_data;
                $j['list'] = $config->list;
                $j['product_id'] = $config->product_id;
                $j['exclude_catId'] = $config->exclude_catId;
                $j['backup_cat_id'] = $config->backup_cat_id;
                $j['backup_sort_by'] = $config->backup_sort_by;
                $j['series_id'] = $config->series_id;
                $j['showBuyNow'] = $blockData['show_buy_now'];
                
                if($config->fhsCampaign){
                    $textFhsCampaign = str_replace('?', '', $config->fhsCampaign);
                    $j['fhsCampaign'] = $textFhsCampaign;
                }

                //products in the collection
                $collectionCacheWS = $block->getCollectionCacheWS();
                $collection = $collectionCacheWS[$i];

                foreach ($collection as $product) {
                    $p = array();
                    $p['soon_release'] = $product->getSoonRelease();
                    if ($product->stockItem->is_in_stock > 0) {
                        $p["stock_available"] = "in_stock";
                    } else {
                        $p["stock_available"] = "out_of_stock";
                    }
                    $p['productId'] = $product->getId();
                    $p['type'] = $product->getTypeId();
                    $p['name'] = $product->getName();
                    $p['discount_percent'] = $handleDiscount->handleDiscountPercent($product);
                    //$p['image'] = \Mage::getBaseUrl('media') . 'catalog/product/' . $product->getSmallImage();
                    $p['image'] = \Mage::helper('catalog/image')->init($product, 'small_image')->resize(400, 400)->__toString();
                    if ($product->getTypeId() == "bundle") {
                        $prices = \Mage::getModel('bundle/product_price')->getTotalPrices($product);
                        $p["minPrice"] = $prices[0];
                        $p["maxPrice"] = $prices[1];
                    } else {
                        $p['finalPrice'] = $product->getFinalPrice();
                        $p['originalPrice'] = $product->getPrice();
                    }
                    $p['rating_summary'] = self::getReviewCountDetail($product);
                    $p['sku'] = $product->getSku();
                    $p['episode'] = $product->getEpisode();
                    $j['products'][] = $p;
                }

                $result['collections'][] = $j;
            }
        }

        if (count($result) > 0) {
            return $result;
        } else {
            return null;
        }
    }

    private static function inlineBannerJson($block) {
        $data = array();
        $inline = json_decode($block->getBlockContentLeftData());
        foreach ($inline as $value) {
            $item = array();
            $item['image'] = \Mage::getBaseUrl('media') . $value->urlMobileImg;
            $item['link'] = $value->pageUrl;
            $item['productId'] = $value->productId;
            $item['categoryId'] = $value->categoryId;
            $item['position'] = $value->position;
            $data[] = $item;
        }

        if (count($data) > 0) {
            return $data;
        } else {
            return null;
        }
    }

    private static function topBannerJson($block) {
        $data = array();
        $top = json_decode($block->getBlockContentTopData());
        foreach ($top as $value) {
            $item = array();
            $item['type'] = $value->type;
            foreach ($value->data as $v) {
                $p = array();
                $p['link'] = $v->urlLink;
                $p['image'] = \Mage::getBaseUrl('media') . $v->urlMobileImg;
                $item['data'][] = $p;
            }
            $data[] = $item;
        }

        if (count($data) > 0) {
            return $data;
        } else {
            return null;
        }
    }

    private static function bannerMobileJson($block) {
        if ($block["template"] == "cmsjson/top_categories.phtml"){
            return null;
        }
        $result = array();
        if ($block["template"] == 'cmsjson/top_authors.phtml'){
            $result['type'] = 'topAuthors';
            
            $jsonAuthors = json_decode(($block['author_images']));
            foreach($jsonAuthors as $author){
                $item = array();
                $item["image"] = \Mage::getBaseUrl('media') . $author->img;
                $item["link"] = $author->link;
                $item["title"] = $author->name;
                $result['authors'][] = $item;
            }
            
            $jsonBooks = json_decode($block['book_images']);
            foreach($jsonBooks as $book){
                $item = array();
                $item["image"] = \Mage::getBaseUrl('media') . $book->img;
                $item["link"] = $book->link;
                $item["title"] = $book->title;
                $item["subTitle"] = $book->subtitle;
                $result['books'][] = $item;
            }
            
            $headerBanner = json_decode($block['header']);
            if ($headerBanner){
                $header['image'] = $headerBanner->image ? Mage::getBaseUrl('media') . $headerBanner->image : null;
                $header['title'] = $headerBanner->title;
                $result['header'] = $header;
            }
            return $result;
        }
        
        if ($block["template"] == 'cmsjson/horizontal_slider.phtml'){
            $result['type'] = 'horizontalSlider';
           
        }
        if ($block["template"] == "cmsjson/icon_menu.phtml" || $block["template"] == "cmsjson/note_product.phtml"){
            if ($block['data_mobile'] == null){
                return null;
            }
            
            $result['type'] = "iconMenu";
            $jsonBanner = json_decode($block['data_mobile']);

            foreach ($jsonBanner as $value)
            {
                $item = array();
                $item['image'] = Mage::getBaseUrl('media') . $value->img;
                $item['link'] = $value->link;
                $item['title'] = $value->title;
                $result['data'][] = $item;
            }
            return $result;
        }
        if ($block["template"] == "cmsjson/horizontal_slider.phtml"){
            $jsonBanner = json_decode($block['data']);
            
            foreach ($jsonBanner as $value)
            {
                $item = array();
                $item['image'] = Mage::getBaseUrl('media') . $value->img;
                $item['link'] = $value->link;
                $item['name'] = $value->name;
                $result['data'][] = $item;
            }
            
            $headerBanner = json_decode($block['header']);
            if ($headerBanner){
                $header['image'] = Mage::getBaseUrl('media') . $headerBanner->image;
                $header['title'] = $headerBanner->title;
                $result['header'] = $header;
            }
            return $result;
        }
        if ($block["template"] == "cmsjson/block_youtube.phtml"){
            $jsonBanner = json_decode($block['data']);
            $result['type'] = 'blockYoutube';
            $result['tabId'] = null;
            $result['tabGroup'] = null;
            $result['tabActive'] = null;
            $result['data'] = $jsonBanner ?? null;
            return $result;
        }
        
        // handle countdownTime block 
        if($block["template"] == "cmsjson/countdowntime.phtml"){
            $jsonBanner = json_decode($block['data']);
            $result['type'] = 'blockCountDownTime';
            $result['tabId'] = null;
            $result['tabGroup'] = null;
            $result['tabActive'] = null;
            $result['data'] = null;
            
            foreach ($jsonBanner as $value) {
            if (!empty($value->linkImageStart) && !empty($value->linkImageRun)){
                $item = array();
                $item['linkImageStart'] = \Mage::getBaseUrl('media') . $value->linkImageStart;
                $item['linkImageRun'] = \Mage::getBaseUrl('media') . $value->linkImageRun;
                $item['timeStart'] = $value->timeStart;
                $item['timeEnd'] = $value->timeEnd;
                $item['colorNum'] = $value->colorNum;
                $item['colorText'] = $value->colorText;
                $result['data'][] = $item;
            }
        }
            return $result;
        }
        
        $result = array();
        $result['type'] = 'blockBanner';
        if($block["template"] == "cmsjson/pagebanner.phtml"){
            $result['type'] = 'pageBanner';
        }
        if($block["template"] == "cmsjson/sliderbanner.phtml"){
            $result['type'] = 'sliderBanner';
        }
        
        $result['tabId'] = $block->getTabId();
        $result['tabGroup'] = $block->getTabGroup();
        $result['tabActive'] = $block->getTabActive();
        
        $blockData = $block->getData();
        $jsonBanner = json_decode($blockData['data']);

        foreach ($jsonBanner as $value) {
            if (!empty($value->urlMobileImg)){
                $item = array();
                $item['webClass'] = $value->webClass;
                $item['image'] = \Mage::getBaseUrl('media') . $value->urlMobileImg;
                if(!empty($value->shareurlLink) && !empty($value->event_name)){
                    $item['shareUrlLink'] = $value->shareurlLink;
                    $item['eventName'] = $value->event_name;
                } else {
                    $item['link'] = $value->urlLink;
                }
                
                if($value->thele){
                    $item['thele'] = $value->thele;
                    self::$thele = true;
                }
                $result['data'][] = $item;
            }
        }

        if (count($result) > 0) {
            return $result;
        } else {
            return null;
        }
    }
    
 

    private static function customListingMobileJson($block) {
        $result = array();
        $result['type'] = 'productCollections';
        $result['cmsType'] = 'grid';
        $result['collections'] = array();
        $page = $_GET['p'];
        $pageSize = $_GET['limit'];
        $value = $block->getValue();
        $handleDiscount = Mage::helper('discountlabel/data');
        $collectionCacheWS = $block->getCollectionCacheWS();
        $i = 0;
        $j['products'] = array();
//        get last collection call
        $collections = $collectionCacheWS[count($collectionCacheWS) - 1];
        if ($collections->getSize() <= (($page - 1) * $pageSize)) {
            $result['collections'] = [];
        } else {
            foreach ($collections as $product) {
                $p = array();
                $p['soon_release'] = $product->getSoonRelease();
                if ($product->stockItem->is_in_stock > 0) {
                    $p["stock_available"] = "in_stock";
                }  else {
                    $p["stock_available"] = "out_of_stock";
                }
                $p['productId'] = $product->getId();
                $p['type'] = $product->getTypeId();
                $p['name'] = $product->getName();
                $p['discount_percent'] = $handleDiscount->handleDiscountPercent($product);
                $p['image'] = \Mage::helper('catalog/image')->init($product, 'small_image')->resize(400, 400)->__toString();
                if ($product->getTypeId() == "bundle") {
                    $prices = Mage::getModel('bundle/product_price')->getTotalPrices($product);
                    $p["min_price"] = $prices[0];
                    $p["max_price"] = $prices[1];
                } else {
                    $p['finalPrice'] = $product->getFinalPrice();
                    $p['originalPrice'] = $product->getPrice();
                }
                $p['rating_summary'] = self::getReviewCountDetail($product);
                if (!in_array($p, $j['products'])) {
                    $j['products'][] = $p;
                }
            }
            $result['collections'][] = $j;
        }
        if (count($result) > 0) {
            return $result;
        } else {
            return null;
        }
    }

    private function getReviewCountDetail($product) {
        //danh gia
        $summaryData = \Mage::getModel('review/review_summary')
                ->setStoreId(0)
                ->load($product->getId());

        $summaryDataAma = \Mage::getModel('amazonrating/amazonrating')
                ->setStoreId(0)
                ->load($product->getSku());

        $result = array();
        $result['reviews_count_fahasa'] = $summaryData->reviews_count;
        $result['rating_summary_fahasa'] = $summaryData->rating_summary;

        $result['reviews_count_amazon'] = $summaryDataAma->getData('numericScore');
        $result['rating_summary_amazon'] = self::convertAmazonRatingToStart($summaryDataAma->getData('cssStarRating'));

        return $result;
    }

    //chuyen doi sao trong amazon rating
    private function convertAmazonRatingToStart($css) {
        if ($css == null || empty($css)) {
            return null;
        }
        if (strpos($css, ' a-star-medium-5 ')) {
            return 5 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-4-5')) {
            return 4.5 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-4')) {
            return 4 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-3-5')) {
            return 3.5 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-3')) {
            return 3 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-2-5')) {
            return 2.5 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-2')) {
            return 2 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-1-5')) {
            return 1.5 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-1')) {
            return 1 * 100 / 5;
        }
    }

    private function fhsRuleBlockMobileJson($block) {
        $result = array();
        $template = $block->getTemplate();
       
        if ($template == 'fhsrule/remain_times_used'){
             $result['type'] = 'fhsBlockRule';

            $blockData = $block->getData();
            $jsonBanner = json_decode($blockData['data']);

            $ruleIds = array();

            $jsonArray = array();
            foreach ($jsonBanner as $value) {
                $item = array();
                $ruleIds[] = $value->ruleId;
                $item['ruleId'] = $value->ruleId;
                $item['webClass'] = $value->webClass;
                $item['image'] = Mage::getBaseUrl('media') . $value->urlMobileImg;
                $item['imageExpire'] = Mage::getBaseUrl('media') . $value->urlMobileExpiresImg;
                $jsonArray[] = $item;
            }

            $ruleIdsString = implode(",", $ruleIds);
            $rules = Mage::helper("fhsrule")->getRuleData($ruleIdsString);
            $rulesWithKey = array_combine(array_column($rules, 'ruleId'), $rules);

            foreach ($jsonArray as $key => $value) {
                $ruleData = $rulesWithKey[$value["ruleId"]];
                $value["isActive"] = $ruleData["isActive"];
                $value["usesPerCoupon"] = $ruleData["usesPerCoupon"];
                $value["timesUsed"] = $ruleData["timesUsed"];
                $result["data"][] = $value;
            }
        }
        else if ($template == 'event/discount_original.phtml' || $template == 'event/discount_original_v2.phtml'
                || $template == 'event/discount_original_v3.phtml'){
            $result['type'] = 'couponDiscount';
            $result['data']['id'] = $block['id'];
            $result['data']['limit'] = $block['limit'] ? $block['limit'] : 50;
            if ($template == 'event/discount_original_v3.phtml'){
                $result['data']['version'] = 3;
                $result['data']['is_slider'] = $block['coupon_type'] == 'slider' ? true : false;
            }
            $result['data']['the_le_link'] = $block['the-le-link'];
        }
        
        if (count($result) > 0) {
            return $result;
        } else {
            return null;
        }
    }
    
    private function fhsEventMobileJson($block){
        $result = array();
        if(strcmp($block->getTemplate(), 'event/topvoted.phtml') == 0 || strcmp($block->getTemplate(), 'event/voteproduct/block.phtml') == 0){
            $result['type'] = 'fhsVoteProduct';
            $data = array();
            $headerData = json_decode($block['header']);
            foreach($headerData as $item){
                $data[] = array(
                  "webClass" => $item->webClass,
                  "image" => \Mage::getBaseUrl('media') . $item->urlMobileImg,
                  "link" => $item->urlLink
                );
        }
            $tagName = array();
            $tagNameData = json_decode($block['tagName']);
            foreach ($tagNameData as $tag) {
                $tagName[] = array(
                    "webClass" => $tag->webClass,
                    "image" => \Mage::getBaseUrl('media') . $tag->urlMobileImg,
                    "tabId" => $tag->tabId, 
                    
                );
            }
            $result['data'] = $data;
            $result["category"] = Mage::helper('event')->getCategoryNameByCatIds();
            $result["banner"] = Mage::getBaseUrl('media') . $block["banner"];
            $result["limit"] = $block["limit"] == "true" || !$block["limit"] ? true : false;
            $result["urlLink"] = $block['urlLink'];
        }
//        else if (strcmp($block->getTemplate(), 'event/game.phtml') == 0 || strcmp($block->getTemplate(), 'event/wheelgame.phtml') == 0){
//            $result['type'] = 'fhsBlockEvent';
//            $result['eventId'] = $block["event_id"];
//        }
        else if (strcmp($block->getTemplate(), 'event/share_facebook.phtml') == 0){
            $result['type'] = 'buffetCouponShare';
            $result['data'] = $block['data'];
            $result['sharedLink'] = $block['sharedLink'];
            $result["eventId"] = $block["eventId"];
            $result["image"] = Mage::getBaseUrl('media') . $block["urlMobileImg"];
        }
        else if(strcmp($block->getTemplate(), 'event/marathon.phtml') == 0){
            $result['type'] = 'marathon';
            $result['data'] = json_decode($block['data']);
        }else if (strcmp($block->getTemplate(), 'event/buffetcombo/block.phtml') == 0 || strcmp($block->getTemplate(), 'event/buffetcombo/page.phtml') == 0){
            if (strcmp($block->getTemplate(), 'event/buffetcombo/page.phtml') == 0){
                $result['type'] = 'buffetComboPage';
            }else {
                $result['type'] = 'buffetComboSlider';
            }
            $result['data']['urlLink'] = $block['urlLink'];
            if ($block['category_data']){
                $result['data']['category_data'] = json_decode($block['category_data']);
            }else {
                $default_category = '[{"id": "all", "display_name": "Tất cả"},{"id": "9", "display_name": "Văn học"},{"id": "11", "display_name": "Kinh tế"},'
                        . '{"id": "12", "display_name": "Tâm lý kỹ năng"},'
                        . '{"id": "6009", "display_name": "Nuôi dạy con"}]';
                $result['data']['category_data'] = json_decode($default_category);
            }
            
            if (!$result['data']['category_data']){
                $result['data']['category_data'] = array();
            }
            
            $result['data']['icon'] = Mage::helper("event/buffetcombo")->getBuffetIcon();
        } else if (strcmp($block->getTemplate(), 'event/choose_source_tracking_v2.phtml') == 0){
            $result['type'] = 'chooseSourceTracking';
            $result['data'] = array(
                "title_choose_option" => $block['title_choose_option'],
                "title_list" => $block['title_list'],
                "title_search" => $block['title_search'],
                "background_color" => $block['background_color'],
                "color" => $block['color']
                    
            );
        }
        return $result;
    }
    
    private function blockFlashsaleSlider($block){
        return array(
            "type" => "flashsaleSlider",
            "data" => json_decode($block["data"])
        );
    }
    
    private function blockFlashsalePage($block){
        return array(
            "type" => "flashsalePage"
        );
    }
    private function blockPersonalize($block){
        return array(
            "type" => "personalization"
        );
    }
    
     private function blockProductViewed($block){
        return array(
            "type" => "productViewed"
        );
    }
    
    private function blockSeriesBook($block,$type){
        $blockData = $block->getData();
        $typeBlock = $blockData['page_type'] ? $blockData['page_type'] : null;
        if($typeBlock == "grid"){
            $typeBlock = "slider";
        }
        $blockSeriesId = $blockData['series_id'] ? $blockData['series_id'] : null ;
        $page_size = Mage::getStoreConfig('seriesbook_config/config/page_size');
        $sort_by = Mage::getStoreConfig('seriesbook_config/config/sort_by');
        
        // data response : 
        $result['type'] = "blockSeriesBook";
        $result['data']['main'] = $type;
        $result['data']['type'] = $typeBlock;
        $result['data']['seriesId'] = $blockSeriesId;
        $result['data']['limit'] = $page_size;
        $result['data']['sortBy'] = $sort_by;
        switch ($type) {
            case 'set':
                $result['data']['nameBlock'] = "SERIES BỘ";
                $result['data']['color'] = "#289aef";
                $result['data']['background'] = "#cff2fc";
                $result['data']['isName'] = false;
                break;
            default:
                $rName = "SÁCH THEO BỘ";
                $rColor = "#ffa951";
                $rBackground = "#fff2cc";
                $isName = false;
                
                if($type == 'book' && $blockSeriesId){
                    $infoSeries = \Mage::helper('seriesbook')->getSeriesInfoFromDB($blockSeriesId, true);
                    if (count($infoSeries) > 0 && $infoSeries['has_series_name'] == "1") {
                        $rName = $infoSeries['seriesbook_name'];
                        $isName = true;
                    }
                }
                $result['data']['nameBlock'] = $rName;
                $result['data']['color'] = $rColor;
                $result['data']['background'] = $rBackground;
                $result['data']['isName'] = $isName;
                break;
        }
        return $result;
    }
}
