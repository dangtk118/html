<?php
class Fahasa_Tabslider_Block_Fhscollection extends Mage_Catalog_Block_Product_List{      

    const MAX_ITEM_WEB = 30;
    const MAX_ITEM_MOBILE = 15;
        
//    public function getProductNewCollection($isMobile){        
//        $catId = $this->getCatId();
//        $category = Mage::getModel('catalog/category')->load($catId);
//        $todayStartOfDayDate  = Mage::app()->getLocale()->date()
//            ->setTime('00:00:00')
//            ->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
//
//        $todayEndOfDayDate  = Mage::app()->getLocale()->date()
//            ->setTime('23:59:59')
//            ->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
//
//        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
//        $collection = Mage::getResourceModel('catalog/product_collection');
//        $collection->addAttributeToSelect('*') 
//                ->joinTable('amazonrating/amazonrating', 'sku = sku', array('amazonRatingURL' => 'ratingURL', 'amazonStarRating' => 'cssStarRating', 'amazonScore' => 'numericScore'), null, 'left')
//                ->joinTable('review/review_aggregate', 'entity_pk_value = entity_id', array('fhs_reviews_count' => 'reviews_count', 'fhs_rating_summary' => 'rating_summary'), '{{table}}.store_id=1','left');
//        $collection->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds());
//
//
//        $collection = $this->_addProductAttributesAndPrices($collection)
//            ->joinField('qty',
//                        'cataloginventory/stock_item',
//                        'qty',
//                        'product_id=entity_id',
//                        '{{table}}.stock_id=1',
//                        'left')
//            ->addStoreFilter()
//            ->addAttributeToFilter('news_from_date', array('or'=> array(
//                0 => array('date' => true, 'to' => $todayEndOfDayDate),
//                1 => array('is' => new Zend_Db_Expr('null')))
//            ), 'left')
//            ->addAttributeToFilter('news_to_date', array('or'=> array(
//                0 => array('date' => true, 'from' => $todayStartOfDayDate),
//                1 => array('is' => new Zend_Db_Expr('null')))
//            ), 'left')
//            ->addAttributeToFilter(
//                array(
//                    array('attribute' => 'news_from_date', 'is'=>new Zend_Db_Expr('not null')),
//                    array('attribute' => 'news_to_date', 'is'=>new Zend_Db_Expr('not null'))
//                    )
//              )
//            ->addAttributeToSort('news_from_date', 'desc')
//            ->addAttributeToSort('created_at', 'desc')
//            ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
//            ->addCategoryFilter($category)
//            ->setPageSize($this->get_prod_count($isMobile))
//            ->setCurPage($this->get_cur_page());
//
//        return $collection;
//    }
    
    /**
     * Get the best seller products list base on category
     */
//    public function getBestSellerBaseOnCategoryId($isMobile, $categoryId){
//        $collection = Mage::getResourceModel('catalog/product_collection')
//                ->addAttributeToSelect('*') 
//                ->joinTable('amazonrating/amazonrating', 'sku = sku', array('amazonRatingURL' => 'ratingURL', 'amazonStarRating' => 'cssStarRating', 'amazonScore' => 'numericScore'), null, 'left')
//                ->joinTable('review/review_aggregate', 'entity_pk_value = entity_id', array('fhs_reviews_count' => 'reviews_count', 'fhs_rating_summary' => 'rating_summary'), '{{table}}.store_id=1', 'left')
//                ->joinTable('multistoreviewpricingpriceindexer/product_index_price', 'entity_id = entity_id', array('final_price' => 'final_price', 'min_price' => 'min_price'), '{{table}}.store_id='.Mage::app()->getStore()->getId(), 'left')
//                ->joinField('qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left')
//                ->addStoreFilter()
//                ->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
//                ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
//                ->addAttributeToFilter('min_price', array('gt' => 0))
//                ->setOrder('num_orders', 'desc')
//                ->setPageSize($this->get_prod_count($isMobile))
//                ->setCurPage($this->get_cur_page());
//        if($categoryId){
//            $category = Mage::getModel('catalog/category')->load($categoryId);
//            if($category){
//                $collection->addCategoryFilter($category);
//            }
//        }  
//        return $collection;
//    }
    
    /**
     * Given attribute code and attribute value, and filter by category, return the list of products.
     * Used for case of attribute code is 'soon_release', 'supplier'
     * If order by is specify, sort by it. Otherwise, default is created_at
     * $isTabSlider: tell whether I should sort by min_price or not. As min_price is sorting differently using observer
     * priority using this over regular attribute_code and attribute_value, as this allow multiple attributes filter
     */
    protected function getProductCollectionBaseOnAttribute($attribute_code, $attribute_value, $attributeData, $isMobile, 
            $categoryId, $orderBy, $isTabSlider, $minCK, $maxCK, $excludeCatId)
    {           
	$product_helper = Mage::helper('fahasa_catalog/product');
	$attribute_code = $product_helper->cleanBug($attribute_code);
	$attribute_value = $product_helper->cleanBug($attribute_value);
	$attributeData = $product_helper->cleanBug($attributeData);
	$isMobile = $product_helper->cleanBug($isMobile);
	$categoryId = $product_helper->cleanBug($categoryId);
	$orderBy = $product_helper->cleanBug($orderBy);
	$isTabSlider = $product_helper->cleanBug($isTabSlider);
	$minCK = $product_helper->cleanBug($minCK);
	$maxCK = $product_helper->cleanBug($maxCK);
	$excludeCatId = $product_helper->cleanBug($excludeCatId);
	
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->addAttributeToFilter($attribute_code, array('eq' => $attribute_value))
                ->addAttributeToSelect('*')
                //->joinTable('amazonrating/amazonrating', 'sku = sku', array('amazonRatingURL' => 'ratingURL', 'amazonStarRating' => 'cssStarRating', 'amazonScore' => 'numericScore'), null, 'left')
                ->joinTable('review/review_aggregate', 'entity_pk_value = entity_id', array('fhs_reviews_count' => 'reviews_count', 'fhs_rating_summary' => 'rating_summary'), '{{table}}.store_id=0', 'left')
                ->joinTable('multistoreviewpricingpriceindexer/product_index_price', 'entity_id = entity_id', array('price'=>'price', 'final_price' => 'final_price', 'min_price' => 'min_price', 'max_price' => 'max_price'), 
                        '{{table}}.store_id='.Mage::app()->getStore()->getId(). ' and {{table}}.customer_group_id=0', 'left')                
                ->setPageSize($this->get_prod_count($isMobile))
                ->setCurPage($this->get_cur_page());
        if($attributeData){
            //priority using attribute data, this allow multiple attributes filter, and structure as array
            foreach($attributeData as $aValue){
                $collection->addAttributeToFilter($aValue['attribute_code'], array('eq' => $aValue['attribute_value']));
            }
        }else{
            //Dont have attribute data, use regular attribute_code and attribute_value
            $collection->addAttributeToFilter($attribute_code, array('eq' => $attribute_value));
        }
        if($minCK){
            $collection->addFieldToFilter('discount_percent', array('gteq' => $minCK));
        }
        if($maxCK){
            $collection->addAttributeToFilter('discount_percent', array('lteq' => $maxCK));
        }
        if($isTabSlider == true){
            //sort everything
            if($orderBy){
                if($orderBy != "min_price"){
                    $collection->getSelect()->order(array($orderBy . ' desc'));      
                }else if($orderBy == "min_price"){
                    $collection->getSelect()->order(array("min_price".' ASC'));
                }
            }else{
                $collection->setOrder('created_at', 'desc');
            }
        }else{
            //do not sort min_price, As min_price is sorting differently using observer
            if($orderBy){
                if($orderBy != "min_price"){
                    $collection->getSelect()->order(array($orderBy. ' desc'));    
//                    $collection->setOrder($orderBy, 'desc');
                }
                else{
                    $sortObs = new Fahasa_Sortprice_Model_Observer();
                    $sortObs->sortByMinPrice($collection, 'min_price');
                }
            }else{
                $collection->setOrder('created_at', 'desc');
//                $collection->order(array('created_at desc'));
            }
        }

        if($categoryId){
            $category = Mage::helper('fahasa_catalog/cache')->getCategory($categoryId);
            if($category){
                $collection->addCategoryFilter($category);
            }
        }
        
        if ($excludeCatId) {
            $excludeCatId = array_map('intval', explode(',', $excludeCatId));
            $collection->getSelect()->where('e.category_mid_id not in (?)', $excludeCatId);
        }
        //Move visibility and stock_status and status in to fhs_catalog_product_entity for better indexing
        $collection->getSelect()->where('e.f_visibility = 4 AND e.f_stock_status = 1 AND e.f_status = 1 AND e.type_id != "series"');
        $collection->distinct(false);
        if($isTabSlider){
            $collection->getSelect()->limit($this->get_prod_count($isMobile));
            $collection->setCurPage(0);
        }else{
                 $collection->setPageSize($this->get_prod_count($isMobile))
                       ->setCurPage($this->get_cur_page());
            }
        return $collection;
    }
    
    /**
     * Filter product collection base on other criteria that are not attribute
     * 1. sort by
     * 2. product ids or not
     * 3. category
     * $isTabSlider: tell whether I should sort by min_price or not. As min_price is sorting differently using observer
     */
    protected function getProductCollectionBaseOnOtherCriteria($productIds, $isMobile, $categoryId, $orderBy, $isTabSlider, $minCK, $maxCK, $excludeCatId, $excludeProdIds = "", $limit = 0,$showSeriesPoduct = false){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$productIds = $product_helper->cleanBug($productIds);
	$isMobile = $product_helper->cleanBug($isMobile);
	$categoryId = $product_helper->cleanBug($categoryId);
	$orderBy = $product_helper->cleanBug($orderBy);
	$isTabSlider = $product_helper->cleanBug($isTabSlider);
	$minCK = $product_helper->cleanBug($minCK);
	$maxCK = $product_helper->cleanBug($maxCK);
	$excludeCatId = $product_helper->cleanBug($excludeCatId);
	$excludeProdIds = $product_helper->cleanBug($excludeProdIds);
	$limit = $product_helper->cleanBug($limit);
	
        if($productIds){
            $productIds = explode(',', $productIds);
        }
            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('*')
                //->joinTable('amazonrating/amazonrating', 'sku = sku', array('amazonRatingURL' => 'ratingURL', 'amazonStarRating' => 'cssStarRating', 'amazonScore' => 'numericScore'), null, 'left')
                ->joinTable('review/review_aggregate', 'entity_pk_value = entity_id', array('fhs_reviews_count' => 'reviews_count', 'fhs_rating_summary' => 'rating_summary'), '{{table}}.store_id=0','left')
                ->joinTable('multistoreviewpricingpriceindexer/product_index_price', 'entity_id = entity_id', array('price'=>'price', 'final_price' => 'final_price', 'min_price' => 'min_price', 'max_price' => 'max_price'), 
                        '{{table}}.store_id='.Mage::app()->getStore()->getId(). ' and {{table}}.customer_group_id=0', 'left');
        $collection->getSelect()->joinLeft(array('sed' => 'fahasa_seribook_extra_data'), 'sed.seriesset_id = e.entity_id',array('subscribes'));
        if($minCK){
            $collection->addAttributeToFilter('discount_percent', array('gteq' => $minCK));
        }
        if($maxCK){
            $collection->addAttributeToFilter('discount_percent', array('lteq' => $maxCK));
        }
        if($productIds){
            $collection->addAttributeToFilter('entity_id', array('in' => $productIds));
        }
        if($isTabSlider == true){
            //sort everything
            if($orderBy == "trending"){
                // position : grid deal hot page;
                // load lan dau tien => khong can sort;
                // data list insert DB bang java da uu tien trending;
                $orderBy = null;
            }
            if($orderBy){
                if($orderBy != "min_price"){
                    $collection->getSelect()->order(array($orderBy . ' desc'));
                }else if($orderBy == "min_price"){
                    $collection->getSelect()->order(array("min_price ASC"));
                }
            }else{
		if($productIds){
		    $orderString = $this->getOrderStringForProductId($productIds);
		    $collection->getSelect()->order(new Zend_Db_Expr($orderString));
		}
            }
        }else{
            //do not sort min_price, As min_price is sorting differently using observer
            if($orderBy){
                if($orderBy != "min_price"){
                    $collection->getSelect()->order(array($orderBy . ' desc'));
                }
            }else{
		if($productIds){
		    $orderString = $this->getOrderStringForProductId($productIds);
		    $collection->getSelect()->order(new Zend_Db_Expr($orderString));
		}
            }
        }        
        if($categoryId){
            $category = Mage::helper('fahasa_catalog/cache')->getCategory($categoryId);
            if($category){
                $collection->addCategoryFilter($category);
            }
	    
	    //loại bỏ hàng thanh lý ra khỏi danh sách mặc định
	    $collection->getSelect()->where("e.f_thanh_ly = 0");
        }

        if ($excludeCatId) {
            $excludeCatId = array_map('intval', explode(',', $excludeCatId));
            $collection->getSelect()->where('e.category_mid_id not in (?)', $excludeCatId);
        }
	if($excludeProdIds){
            $excludeProdIds = explode(',', $excludeProdIds);
            $collection->addAttributeToFilter('entity_id', array('nin' => $excludeProdIds));
	}
        //Move visibility and stock_status and status in to fhs_catalog_product_entity for better indexing
        if ($showSeriesPoduct) {
            $collection->getSelect()->where('e.f_visibility = 4 AND e.f_stock_status = 1 AND e.f_status = 1');
        } else {
            $collection->getSelect()->where('e.f_visibility = 4 AND e.f_stock_status = 1 AND e.f_status = 1 AND e.type_id != "series"');
        }
        $collection->distinct(false);
        if($isTabSlider){
	    if($limit){
		$collection->getSelect()->limit($limit);
		$collection->setCurPage(0);
	    }else{
		$collection->getSelect()->limit($this->get_prod_count($isMobile));
		$collection->setCurPage(0);
	    }
        }else{
            $collection->setPageSize($this->get_prod_count($isMobile))
                       ->setCurPage($this->get_cur_page());
        }
        return $collection;
    }
    
    public function getProductCollectionBaseOnRelations($product_id){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$product_id = $product_helper->cleanBug($product_id);
	
        $product =  Mage::getModel('catalog/product')->load($product_id, 'id');
        if(!$product){
            return array();
        }
        
        /* @var $product Mage_Catalog_Model_Product */
        
        $collections = $product->getRelatedProductCollection()
                ->addAttributeToSelect('*')
                //->joinTable('amazonrating/amazonrating', 'sku = sku', array('amazonRatingURL' => 'ratingURL', 'amazonStarRating' => 'cssStarRating', 'amazonScore' => 'numericScore'), null, 'left')
                ->joinTable('review/review_aggregate', 'entity_pk_value = entity_id', array('fhs_reviews_count' => 'reviews_count', 'fhs_rating_summary' => 'rating_summary'), '{{table}}.store_id=0','left')
                ->joinField('stock_status', 'cataloginventory/stock_status', 'stock_status', 'product_id=entity_id', null, 'left')
            ->addAttributeToSelect('required_options')
            ->setPositionOrder()
            ->addStoreFilter();
        
	//loại bỏ hàng thanh lý ra khỏi danh sách mặc định
	$collections->getSelect()->where("e.f_thanh_ly = 0");
	
        if (Mage::helper('catalog')->isModuleEnabled('Mage_Checkout')) {
            Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter($collections,
                Mage::getSingleton('checkout/session')->getQuoteId()
            );
            $this->_addProductAttributesAndPrices($collections);
        }
//        Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($collections);
        $collections->getSelect()->where('e.f_visibility = 4 AND e.f_stock_status = 1 AND e.f_status = 1 AND e.type_id != "series"');
        //Mobile will display less items        
        $mobile = Mage::helper('fhsmobiledetect')->isMobile();
        if($mobile){
            //On mobile only display 6 items
            $collections->setPageSize(self::MAX_ITEM_MOBILE);
        }else{
            $collections->setPageSize(self::MAX_ITEM_WEB);
        }
        $collections->load();
        
        foreach ($collections as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $collections;        
    }
    
    protected $templateType = "tabslider/review.phtml";

    public function getFahasaSummaryHtml($product)
    {
        $this->setTemplate($this->templateType);

        $this->setDisplayIfEmpty(FALSE);
        $this->setProduct($product);

        return $this->toHtml();
    }
    
    /* for gridSlder page and tabSLider deal hot */
    protected $templateType1 = "tabslider/review1.phtml";

    public function getFahasaSummaryHtml1($product)
    {
        $this->setTemplate($this->templateType1);

        $this->setDisplayIfEmpty(FALSE);
        $this->setProduct($product);

        return $this->toHtml();
    }
    
//    protected function getProductByProductIdsOrderByDiscount($product_ids, $isMobile) {
//        $collection = Mage::getModel('catalog/product')->getCollection()
//                ->addAttributeToSelect('*')                                
//                ->joinTable('amazonrating/amazonrating', 'sku = sku', array('amazonRatingURL' => 'ratingURL', 'amazonStarRating' => 'cssStarRating', 'amazonScore' => 'numericScore'), null, 'left')
//                ->joinTable('review/review_aggregate', 'entity_pk_value = entity_id', array('fhs_reviews_count' => 'reviews_count', 'fhs_rating_summary' => 'rating_summary'), '{{table}}.store_id=1','left')
//                ->joinTable('multistoreviewpricingpriceindexer/product_index_price', 'entity_id = entity_id', array('final_price' => 'final_price'), '{{table}}.store_id='.Mage::app()->getStore()->getId(), 'left')
//                ->addAttributeToFilter('entity_id', array('in' => $product_ids))
//                ->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
//                ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
//                ->setPageSize($this->get_prod_count($isMobile))
//                ->setCurPage($this->get_cur_page());        
//        $collection->getSelect()->order(array('discount_percent DESC'));                
//        return $collection;
//    }
    
//    protected function getProductByProductIdsOrderByNumOrder($product_ids, $isMobile) {
//        $collection = Mage::getModel('catalog/product')->getCollection()
//                ->addAttributeToSelect('*')
//                ->joinTable('amazonrating/amazonrating', 'sku = sku', array('amazonRatingURL' => 'ratingURL', 'amazonStarRating' => 'cssStarRating', 'amazonScore' => 'numericScore'), null, 'left')
//                ->joinTable('review/review_aggregate', 'entity_pk_value = entity_id', array('fhs_reviews_count' => 'reviews_count', 'fhs_rating_summary' => 'rating_summary'), '{{table}}.store_id=1','left')
//                ->addAttributeToFilter('entity_id', array('in' => $product_ids))
//                ->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
//                ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
//                ->setPageSize($this->get_prod_count($isMobile))
//                ->setCurPage($this->get_cur_page());        
//        $collection->getSelect()->order(array('num_orders DESC'));                
//        return $collection;
//    }
       
    /**
     * This currently only use be custom_listing
     */
    protected function getProductByProductIdsWithSortBy($product_ids, $isMobile, $sortBy) {
        $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('*')
                //->joinTable('amazonrating/amazonrating', 'sku = sku', array('amazonRatingURL' => 'ratingURL', 'amazonStarRating' => 'cssStarRating', 'amazonScore' => 'numericScore'), null, 'left')
                ->joinTable('review/review_aggregate', 'entity_pk_value = entity_id', array('fhs_reviews_count' => 'reviews_count', 'fhs_rating_summary' => 'rating_summary'), '{{table}}.store_id=0','left')
                ->joinTable('multistoreviewpricingpriceindexer/product_index_price', 'entity_id = entity_id', array('final_price' => 'final_price', 'min_price' => 'min_price'), 
                        '{{table}}.store_id='.Mage::app()->getStore()->getId(). ' and {{table}}.customer_group_id=0', 'left')
                ->addAttributeToFilter('entity_id', array('in' => $product_ids))                
                ->setPageSize($this->get_prod_count($isMobile))
                ->setCurPage($this->get_cur_page());
        if($sortBy){
            if($sortBy != 'min_price'){
                $collection->getSelect()->order(array($sortBy.' DESC'));
            }
             else{
                 $collection->getSelect()->order(array("min_price ASC"));
            }
        }else{
            $collection->getSelect()->order(array("FIND_IN_SET(`e`.entity_id, '" . implode(",", $product_ids) . "')"));
        }
        //Move visibility and stock_status and status in to fhs_catalog_product_entity for better indexing
        $collection->getSelect()->where('e.f_visibility = 4 AND e.f_stock_status = 1 AND e.f_status = 1 AND e.type_id != "series"');
        $collection->distinct(false);
        return $collection;
    }
    
    /**
     * Given the array of product_ids, create the query string for Zend_Db_Expr
     * to maintain the order of the products returned.     
     */
    function getOrderStringForProductId($product_ids){
        $orderString = array('CASE e.entity_id');
        foreach($product_ids as $i => $productId) {
                $orderString[] = 'WHEN '.$productId.' THEN '.$i;
        }
        $orderString[] = 'END';
        $orderString = implode(' ', $orderString);
        return $orderString;
    }        

    function get_prod_count($isMobile) 
    {         
        if(isset($_GET['limit'])){
           $prodcount = intval($_GET['limit']);
        }  else {
            if($isMobile){
                $prodcount = 8;
            }else{
                $prodcount = 24;
            }
        }
        return $prodcount;
    }

    function get_cur_page() 
    {   
        return (isset($_GET['p'])) ? intval($_GET['p']) : 1; 
    }

    function removeSlashTrailingUrl($url){
        $lastChar = substr($url, -1);
        if($lastChar == '/'){
            return substr($url, 0, strlen($url) - 1);
        }
        return $url;
    } 
}

