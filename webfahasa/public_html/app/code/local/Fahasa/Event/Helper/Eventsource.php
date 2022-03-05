<?php

class Fahasa_Event_Helper_Eventsource extends Mage_Core_Helper_Abstract {

    public function getEventSourceOptions($has_affId = false, $area_id, $level_id, $source_id)
    {
        if ($event_id = (int) Mage::getStoreConfig('event_source/config/event_id'))
        {
            $options = null;
            $form_ui = null;

            $query = "select o.id, o.name as name, o.affId, e.form_ui from "
                    . "fahasa_affiliate_campaign e "
                    . "join fahasa_affiliate o on e.id = o.campaignId "
                    . "where now() between e.validFrom and e.validTo and e.id = :event_id and o.name is not null and visibility = 1 and now() between o.validFrom and o.validTo ";
            $binds = array(
                "event_id" => $event_id
            );
            $read = Mage::getSingleton("core/resource")->getConnection("core_read");
            $rs = $read->fetchAll($query, $binds);
            if (count($rs) > 0)
            {
                $options = $this->parseOptions($rs, $has_affId);
                $form_ui = json_decode($rs[0]['form_ui'], true);
                $category_id = Mage::getStoreConfig('event_source/config/category_id');
                $selection = $this->getOptionData($area_id, $level_id, $source_id);
//                $static_block = $this->getStaticBlockByOptionId($area_id, $level_id, $source_id);
                return array(
                    "options" => $options,
                    "form_ui" => $form_ui,
                    "category_id" => $category_id,
                    "selection" => $selection,
//                    "static_block" => $static_block
                );
            }
        }
        return null;
    }

    public function getEventSourceInfoInCheckout()
    {
        $source_info = $this->getEventSourceInfo();
        $matched = false;

        if ($source_info)
        {
            //category_id for filter
            $category_ids = $source_info['category_id'];
            if ($category_ids)
            {
                $category_ids = explode(",", $category_ids);
                static $_getQuoteCallCount = 0;
                if ($_getQuoteCallCount == 0)
                {
                    $_getQuoteCallCount++;
                    $onePage = Mage::getSingleton('checkout/type_onepage');
                    $quote = $onePage->getQuote();
                    $_getQuoteCallCount--;

                    $quoteItems = $quote->getAllItems();
                    foreach ($quoteItems as $quoteItem)
                    {
                        $product = $quoteItem->getProduct();
                        if (in_array($product->getCategoryMainId(), $category_ids) || in_array($product->getCategoryMidId(), $category_ids) || in_array($product->getData('category_1_id'), $category_ids))
                        {
                            $matched = true;
                            break;
                        }
                    }
                }
            }
        }

        $source_info['matched'] = $matched;
        return $source_info;
    }

    public function checkCartHasSourceOptionId($quoteItems)
    {
        $source_info = $this->getEventSourceInfo();
        $matched = false;
        if (!$source_info['success']){
            return null;
        }
        
        if ($source_info['success'])
        {
            //category_id for filter
            $category_ids = $source_info['category_id'];
            if ($category_ids)
            {
                $category_ids = explode(",", $category_ids);

                foreach ($quoteItems as $quoteItem)
                {
                    $product = $quoteItem->getProduct();
                    if (in_array($product->getCategoryMainId(), $category_ids) || in_array($product->getCategoryMidId(), $category_ids) 
                            || in_array($product->getData('category_1_id'), $category_ids))
                    {
                        $matched = true;
                        return array(
                            "event_id" => $source_info['event_id'],
                            "matched" => $matched
                        );
                    }
                }
            }
            
        }

        return array(
            "event_id" => $source_info['event_id'],
            "matched" => $matched
        );
    }
    
    public function checkCartHasExcludedCatIdForTracking($quoteItems)
    {
        //category_id for filter
        $cat_ids_excluded = Mage::getStoreConfig('event_source/config/cat_id_excluded');
        if ($cat_ids_excluded)
        {
            $cat_ids_excluded = explode(",", $cat_ids_excluded);

            foreach ($quoteItems as $quoteItem)
            {
                $product = $quoteItem->getProduct();
                if (in_array($product->getCategoryMainId(), $cat_ids_excluded) || in_array($product->getCategoryMidId(), $cat_ids_excluded) || in_array($product->getData('category_1_id'), $cat_ids_excluded))
                {
                    return true;
                }
            }
        }
        return false;
    }

    public function parseOptions($rs, $has_affId)
    {
        $data = [];
        foreach ($rs as $option)
        {
            $item = array(
                "id" => (int) $option["id"],
                "name" => $option["name"]
            );
            if ($has_affId){
              $item['affId']  = $option['affId'];
            }
            $data[] =$item;
        }
        if (count($data) > 0){
            array_unshift($data, array(
               "id" => "0",
                "name" => "--Bỏ chọn--"
            ));
        }
        return $data;
    }
    
    public function getMappingIdByAffId($affId)
    {
        $query = "select id from fahasa_affiliate where affId = :affId and now() between validFrom and validTo ";
        $binds = array(
            "affId" => $affId
        );
        $read = Mage::getSingleton("core/resource")->getConnection("core_read");
        $rs = $read->fetchAll($query, $binds);
        if (count($rs) > 0){
            return (int) $rs[0]['id'];
        }
        return null;
    }

    public function getEventSourceInfo($get_product_list = false, $affId, $is_mobile, $area_id, $level_id)
    {
        $success = false;
        $cur_option_id = null;
        $source_info = array();
        $options = array();
        $see_all_link = null;
        $product_title = null;
        
        if (Mage::getStoreConfig('event_source/config/is_active'))
        {

            $session = Mage::getSingleton('core/session');
            if ($affId)
            {
                $option_id = $this->getMappingIdByAffId($affId);
                if ($option_id)
                {
                    $event_source_timestamp = time();
                    $session->setAffId($affId);
                    $session->setEventSourceId($option_id);
                    $session->setEventSourceTimestamp($event_source_timestamp);
                    $cur_option_id = $option_id;
                }
                else
                {
                    $cur_option_id = $session->getEventSourceId();
                }
            }
            else
            {
                $cur_option_id = $session->getEventSourceId();
            }
            
            if ($area_id || $level_id){
                $session->setEventSourceId($option_id);
                $session->setEventSourceTimestamp($event_source_timestamp);
                $cur_option_id = null;
                Mage::helper("weblog")->SetAffiliate('', $is_mobile);
            }
            $source_info = $this->getEventSourceOptions(false, $area_id, $level_id, $cur_option_id);
            if ($source_info)
            {
                $event_id = (int) Mage::getStoreConfig('event_source/config/event_id');

                $origin_options = $source_info['options'];

                $options = array_map(function($e) use ($cur_option_id) {
                    if ($e['id'] == $cur_option_id && $cur_option_id != 0)
                    {
                        $e['active'] = true;
                    }
                    return $e;
                }, $origin_options);
                if ($get_product_list)
                {
                    $products = $this->getProductsBasedOnOptionId($event_id, $cur_option_id, $area_id, $level_id);
                    $form_ui = $source_info['form_ui'];
                    if ($cur_option_id){
                        $see_all_link = $form_ui['see_all_link'] . "?option_id=" . $cur_option_id;
                    } else {
                        $params = array(
                            "area_id" => $area_id,
                            "level_id" => $level_id
                        );
                        $params = array_filter($params, function($value) { return !is_null($value) && $value !== ''; });
                        $query_params = http_build_query( $params);
                        $see_all_link = $form_ui['see_all_link'] . "?" .$query_params ;
                    }
                    
                    $product_title = $form_ui['product_title'];
                    $static_block = $this->getStaticBlockByOptionId($area_id, $level_id, $cur_option_id);
                    $related_data = $this->getStaticBlockByIdentifiers($static_block, $is_mobile);
                }
                else
                {
                    //if option_id is not existed in database. Maybe option_id was deleted before => reset value in session
                    $session->unsEventSourceId();
                    $session->unsEventSourceTimestamp();
                }

                $success = true;
            }
        }
        
        return array(
            "success" => $success,
            "cur_option_id" => $cur_option_id,
            "options" => $options,
            "form_ui" => $source_info['form_ui'],
            "product_title" => $product_title,
            "seeAllLink" => $see_all_link,
            "category_id" => $source_info['category_id'],
            "products" => $products,
            "event_id" => $event_id,
            "selection" => $source_info['selection'],
            "related_data" => $related_data,
            "static_block" => $source_info['static_block']
        );
    }
    
    public function getStaticBlockByIdentifiers($static_blocks, $is_mobile)
    {
       
       
        if ($is_mobile)
        {
             $related_data = array();
            foreach ($static_blocks as $static_block)
            {
               $related_data = array_merge($related_data, $this->getStaticBlockData($static_block['static_block'], $is_mobile));
            }
        }
        else
        { 
            $related_data = "";
            foreach ($static_blocks as $static_block)
            {
                $related_data .= $this->getStaticBlockData($static_block['static_block'], $is_mobile);
            }
        }
        return $related_data;
    }

    public function getStaticBlockData($static_block, $is_mobile){
        if ($is_mobile)
        {
            \Fahasa_Mobileui_Model_Observer::initialize();
            \Fahasa_Mobileui_Model_Observer::$enable = true;
        }
        $cms_block = \Mage::getModel('cms/block')->load($static_block);
        $content = $cms_block->getContent(); //get entire content of cms block
        $helper = \Mage::helper('cms');
        $processor = $helper->getPageTemplateProcessor();
        $html = $processor->filter($content);
        if ($is_mobile)
        {
            Fahasa_Mobileui_Model_Observer::$enable = false;
            $related_data = Fahasa_Mobileui_Model_Observer::getMobileJson();
        } else {
            $related_data = $html;

        }
        return $related_data;
    }

    public function saveEventSourceOption($option_id, $is_mobile = false, $area_id, $level_id)
    {
        $success = false;
        $message = null;
        $products = array();
        $see_all_link = null;
        $product_title = null;
        
        $option_id = (int) $option_id;
        try {
            if (isset($option_id) && $option_id !== '' && is_numeric($option_id) && Mage::getStoreConfig('event_source/config/is_active'))
            {
                $session = Mage::getSingleton('core/session');
                $option_id = (int) $option_id;
                $source_info = $this->getEventSourceOptions(true);
                $event_id = (int) Mage::getStoreConfig('event_source/config/event_id');
                 
                if ($option_id == 0)
                {
                    $session->unsEventSourceId();
                    $session->unsEventSourceTimestamp();
                    
                    Mage::helper("weblog")->SetAffiliate('', $is_mobile);
                    $success = true;
                    
                    $products = $this->getProductsBasedOnOptionId($event_id, $option_id, $area_id, $level_id);
                    $form_ui = $source_info['form_ui'];
                    $see_all_link = $form_ui['see_all_link'] . "?option_id=" . $option_id;
                    $product_title = $form_ui['product_title'];

                    $static_block = $this->getStaticBlockByOptionId($area_id, $level_id, null);
                    $related_data = $this->getStaticBlockByIdentifiers($static_block, $is_mobile);
                }
                else
                {
                    $options = $source_info['options'];
                    $cur_option = array_values(array_filter($options, function($e) use ($option_id) {
                        return $e['id'] == $option_id;
                    }));
                    if (count($cur_option) > 0)
                    {
                        $option_id = (int) $option_id;
                        $event_source_timestamp = time();
                        $affId = $cur_option[0]['affId'];
                        
                        Mage::helper("weblog")->SetAffiliate($affId, $is_mobile);
                        
                        $session->setEventSourceId($option_id);
                        $session->setEventSourceTimestamp($event_source_timestamp);
                        $success = true;
                        $products = $this->getProductsBasedOnOptionId($event_id, $option_id);
                        $form_ui = $source_info['form_ui'];
                        $see_all_link = $form_ui['see_all_link'] . "?option_id=" . $option_id;
                        $product_title = $form_ui['product_title'];
                        
                        $static_block = $this->getStaticBlockByOptionId(null, null, $option_id);
                        $related_data = $this->getStaticBlockByIdentifiers($static_block, $is_mobile);
                    }
                }
            }
        } catch (Exception $ex) {
            Mage::log("Exception save option " . $ex, null, "event_source.log");
            $message = "Có lỗi xảy ra. Vui lòng thử lại";
        }

        return array(
            "success" => $success,
            "message" => $message,
            "product_title" => $product_title,
            "seeAllLink" => $see_all_link,
            "products" => $products,
            "related_data" => $related_data,
        );
    }
    
    public function getProductsBasedOnOptionId($event_id, $option_id, $area_id, $level_id)
    {
        if (!$option_id && !$area_id && !$level_id){
            return array();
        }

        $result = array();
        $query = "select ep.product_id,  "
                . "image.value as image, name.value as name, "
                . "if (url1.value is null or url.value = url1.value, url.value, url1.value) as product_url, "
                . "price.price as original_price, "
                . "price.final_price, pe.episode, pe.type_id, if(soon_release.value = 1, 1, 0) as soon_release, stock.is_in_stock "
                . "from fhs_event_source_group g "
                . "join fhs_event_source_group_product ep on ep.group_id = g.group_id "
                . "join fhs_catalog_product_entity pe on pe.entity_id = ep.product_id "
                . "join fhs_catalog_product_entity_varchar name on name.entity_id = pe.entity_id and name.attribute_id = 71 "
                . "join fhs_catalog_product_entity_int soon_release on soon_release.entity_id = pe.entity_id and soon_release.attribute_id = 155 "
                . "join fhs_cataloginventory_stock_item stock on stock.product_Id = pe.entity_id "
                . "LEFT JOIN fhs_catalog_product_entity_varchar image ON pe.entity_id = image.entity_id AND image.attribute_id = 87 "
                . "LEFT JOIN fhs_catalog_product_entity_varchar url ON pe.entity_id = url.entity_id AND url.attribute_id = 98 and url.store_id=0 "
                . "left join fhs_catalog_product_entity_varchar url1 on pe.entity_id = url1.entity_id and url1.attribute_id = 98 and url1.store_id = 1 "
                . "left join fhs_catalog_product_index_price_store price on price.entity_id = pe.entity_id and price.store_id = 1 and price.customer_group_id = 0 and price.website_id = 1 "
                . " left join fhs_event_source_group_linking gl on gl.group_id = g.group_id ";

        
                
        if ($option_id)
        {
            $query .= "where gl.source_id = :source_id group by ep.product_id ";
            $binds = array(
                "source_id" => $option_id,
            );
        }
        else
        {
            $binds = array();
            if ($area_id)
            {
                $query .= " where  g.area_id = :area_id ";
                $binds["area_id"] = $area_id;
            }
            if ($level_id)
            {
                if ($area_id)
                {
                    $query .= " and ";
                } else {
                    $query .= " where ";
                }
                $query .= " ep.level_id = :level_id ";
                $binds["level_id"] = $level_id;
            }
            $query .= " group by ep.product_id ";
        }

        $read = Mage::getSingleton("core/resource")->getConnection("core_read");
        $rs = $read->fetchAll($query, $binds);
        $catalog_helper = Mage::helper('fahasa_catalog/product');
        $image_helper = Mage::helper('catalog/image');
        foreach ($rs as $product)
        {
            $original_price = $product['original_price'];
            $final_price = $product['final_price'];
            $discount_percent = 0;
            if ($final_price < $original_price && $original_price > 0)
            {
                $discount_percent = 100 - ($final_price * 100 / $original_price);
            }
            $image = (string) $image_helper->init(Mage::getModel('catalog/product'), 'thumbnail', $product['image'])->resize(400, 400)->__toString();
            $result[] = array(
                "id" => $product['product_id'],
                "product_url" => $product['product_url'],
                "image_label" => $product['name'],
                "image_src" => $image,
                "name_a_title" => $product['name'],
                "name_a_label" => $product['name'],
                "rating_html" => "",
                "price" => $original_price,
                "final_price" => $final_price,
                "product_id" => $product['product_id'],
                "type_id" => $product['type_id'],
                "discount_percent" => $discount_percent,
                "bar_html" => "",
                "episode" => $product['episode'],
                "soon_release" => (int) $product['soon_release'],
                "stock_available" => $product['is_in_stock'] == 1 ? 'in_stock' : 'out_of_stock',
                "submitUrl" => $catalog_helper->getSubmitUrl($product['product_id']),
            );
        }
        return $result;
    }

    public function getProductIdsBasedOnOptionId($option_id, $area_id, $level_id)
    {
        if (Mage::getStoreConfig('event_source/config/is_active'))
        {
            $query = "select ep.product_id  "
                    . "from fhs_event_source_group g "
                    . "join fhs_event_source_group_product ep on ep.group_id = g.group_id "
                    . "left join fhs_event_source_group_linking gl on gl.group_id = g.group_id ";

            if ($option_id)
            {
                $query .= "where gl.source_id = :source_id group by ep.product_id ";
                $binds = array(
                    "source_id" => $option_id,
                );
            }
            else
            {
                $binds = array();
                if ($area_id)
                {
                    $query .= " where  g.area_id = :area_id ";
                    $binds["area_id"] = $area_id;
                }
                if ($level_id)
                {
                    if ($area_id)
                    {
                        $query .= " and ";
                    } else {
                        $query .= " where ";
                    }
                    $query .= " ep.level_id = :level_id ";
                    $binds["level_id"] = $level_id;
                }
                $query .= " group by ep.product_id ";
            }

            $read = Mage::getSingleton("core/resource")->getConnection("core_read");
            $rs = $read->fetchAll($query, $binds);
            if (count($rs) > 0)
            {
                return implode(",", array_column($rs, "product_id"));
            }
        }

        return null;
    }
    
    public function getBookstoreAffIdBySourceAffId($affId)
    {
        $query = "select * from fahasa_affiliate where now() between validFrom  and validTo and name is null and (affId = :affId "
                . "or bookstoreId = (select bookstoreId from fahasa_affiliate where affId = :affId and name is not null and now() between validFrom  and validTo ));";
        $binds = array(
            "affId" => $affId,
        );
        $read = Mage::getSingleton("core/resource")->getConnection("core_read");
        $rs = $read->fetchAll($query, $binds);
        //must have 1 row for bookstore affId
        if (count($rs) > 0)
        {
            return $rs[0]['affId'];
        }
        return null;
    }
    
    public function getAreaData(){
        $query = "select id as id, name as name from fhs_event_source_area ";
        $read = Mage::getSingleton("core/resource")->getConnection("core_read");
        $rs = $read->fetchAll($query);
        return $rs;
    }
    
    public function getLevelData(){
        $query = "select id as id, level as name from fhs_event_source_level ";
        $read = Mage::getSingleton("core/resource")->getConnection("core_read");
        $rs = $read->fetchAll($query);
        return $rs;
    }
    
    public function getSourceData($area_id, $level_id)
    {
        $query = "select fa.id as id , fa.name as name "
                . "from fhs_event_source_group_linking gl "
                . "join fhs_event_source_group g on g.group_id  = gl.group_id  "
                . "join fahasa_affiliate fa on fa.id = gl.source_id "
                . "where fa.name  is not null and fa.visibility  = 1 and now() between fa.validFrom  and fa.validTo ";
         $binds = array();
        if ($area_id)
        {
            $query .= " and g.area_id = :area_id ";
            $binds['area_id'] = $area_id;
        }
        if ($level_id)
        {
            $query .= " and g.level_id = :level_id ";
            $binds['level_id'] = $level_id;
        }
        $query .= " group by fa.id";
        $read = Mage::getSingleton("core/resource")->getConnection("core_read");
        $rs = $read->fetchAll($query, $binds);
        return $rs;
    }

    public function parseSeletionData($data, $option_id){
        $result = array_map(function($e) use ($option_id) {
            if ($e['id'] == $option_id && $option_id != 0)
            {
                $e['active'] = true;
            }
            return $e;
        }, $data);
        
        if (count($result) > 0)
        {
            array_unshift($result, array(
                "id" => "0",
                "name" => "--Bỏ chọn--"
            ));
        }
        return $result;
    }
    
    public function getOptionData($area_id, $level_id, $source_id){
        $areas = $this->parseSeletionData($this->getAreaData(), $area_id);
        $levels = $this->parseSeletionData($this->getLevelData(), $level_id);
        $sources = $this->parseSeletionData($this->getSourceData($area_id, $level_id), $source_id);

        return array(
            "area" => $areas,
            "level" => $levels,
            "source" => $sources
        );
              
    }
    
    public function getStaticBlockByOptionId($area_id, $level_id, $source_id)
    {
        if (!$area_id && !$level_id && !$source_id){
            return null;
        }
        
        $query = "select distinct(g.static_block) as static_block from fhs_event_source_area a "
                . "left join fhs_event_source_group g on g.area_id = a.id "
                . "left join fhs_event_source_level l on l.id = g.level_id "
                . "left join fhs_event_source_group_linking gl on gl.group_id = g.group_id "
                . "left join fahasa_affiliate fa on fa.id = gl.source_id "
                . "where g.static_block is not null ";
        
        if ($source_id)
        {

            $query .= " and fa.id = :source_id ";
            $binds["source_id"] = $source_id;
        }
        else
        {
            if ($area_id || $level_id)
            {
                $query .= "  and ";
            }
            $binds = array();

            if ($area_id)
            {
                $query .= " a.id = :area_id ";
                $binds["area_id"] = $area_id;
            }
            if ($level_id)
            {
                if ($area_id)
                {
                    $query .= " and ";
                }
                $query .= " l.id = :level_id ";
                $binds["level_id"] = $level_id;
            }
        }


        $read = Mage::getSingleton("core/resource")->getConnection("core_read");
        $rs = $read->fetchAll($query, $binds);
        return $rs;
    }
    
    
}
