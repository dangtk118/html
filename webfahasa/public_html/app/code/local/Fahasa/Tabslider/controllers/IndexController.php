<?php

class Fahasa_Tabslider_IndexController extends Mage_Core_Controller_Front_Action {
    
    /*
     *  
     */
    public function getDataAction(){
        
        /// Params
        $products_limit = $this->getRequest()->getParam('limit');
        $sort_by = $this->getRequest()->getParam('sort_by');
        $max_ck = $this->getRequest()->getParam('max_ck');
        $min_ck = $this->getRequest()->getParam('min_ck');
        $category_id = $this->getRequest()->getParam('category_id');
        $series_id = $this->getRequest()->getParam('series_id');
        $block_type = $this->getRequest()->getParam('block_type');
        $attribute_code = $this->getRequest()->getParam('attribute_code');
        $attribute_value = $this->getRequest()->getParam('attribute_value');
        $attribute_data = $this->getRequest()->getParam('attribute_data');
        $product_ids_str = $this->getRequest()->getParam('list');
        $product_id = $this->getRequest()->getParam('product_id');
        $exclude_catId = $this->getRequest()->getParam('exclude_catId');
        $show_ui_progress = $this->getRequest()->getParam('bar_gridSlider');
        $backup_cat_id = $this->getRequest()->getParam('backup_cat_id');
        $backup_order_by = $this->getRequest()->getParam('backup_sort_by');
        $show_buy_now = $this->getRequest()->getParam('show_buy_now');
	if($show_buy_now == "true"){$show_buy_now = true;}else{$show_buy_now = false;}
        
//        $productsSeriesId = Mage::helper('seriesbook')->getProductsBySeriesId($series_id, $sort_by, $page, $limit);
        $returnProducts = Mage::helper('tabslider/data')->getProducts($products_limit, $sort_by, $max_ck, $min_ck, $category_id, $block_type, $attribute_code, $attribute_value, $attribute_data, $product_ids_str, $product_id, $exclude_catId,true,$show_ui_progress, $backup_cat_id, $backup_order_by,$series_id, null, $show_buy_now);
       
        $response = $this->getResponse()->setBody(json_encode($returnProducts))
                        ->setHeader('Content-type','application/json');
        
        return $response;
    }
    public function getData2Action() {

        //Params
        $success = false;
        $listProductId = $this->getRequest()->getParam('list');
        $limit = $this->getRequest()->getParam('limit');
        $category_id = $this->getRequest()->getParam('category');
        // vi true gui tu ajax js len String ko phai boolean;
        $mobile_grid_page = $this->getRequest()->getParam('mobile_grid_page') == "true" ? true : false;
        $arrayProductId = explode(",", $listProductId);
        $stringProductId = null;
        $type_name_order = $this->getRequest()->getParam("type_name_order");
        $show_ui_progress = $this->getRequest()->getParam("bar_gridSlider") == "true" ? true : false;

        ///
        $attribute_code = $this->getRequest()->getParam('attribute_code');
        $attribute_value = $this->getRequest()->getParam('attribute_value');
        //$attribute_data = $this->getRequest()->getParam('attribute_data');
        $block_type = $this->getRequest()->getParam('block_type');

        if ($block_type == 'attribute') {
            // get by attr : 
            $products_limit = null;
            $sort_by = $type_name_order;
            $max_ck = $min_ck = null;
            $product_ids_str = $product_id = $exclude_catId = null;
            $show_ui_progress = $backup_cat_id = $backup_order_by = $series_id = null;
            $block_type = 'attribute';
            if ($category_id && $category_id != 'all' && !empty($category_id)) {
                $category_id = $category_id;
            } else {
                $category_id = null;
            }
            $returnProducts = Mage::helper('tabslider/data')->getProducts($products_limit, $sort_by, $max_ck, $min_ck, $category_id, $block_type, $attribute_code, $attribute_value, $attribute_data, $product_ids_str, $product_id, $exclude_catId, false, $show_ui_progress, $backup_cat_id, $backup_order_by, $series_id);
            if (count($returnProducts) > 0) {
                $success = true;
            }
        } else {
            // get listId : 
            $resultData = Mage::helper('tabslider/data')->getProductPageSlider($listProductId, $limit, $category_id, $mobile_grid_page, $type_name_order, $show_ui_progress);
            $returnProducts = $resultData['returnProducts'];
            
            if (count($returnProducts) > 0) {
                $success = true;
                // loai bo nhung product da lay trong list:
                $arrayDiff = array_diff($arrayProductId, $resultData['listProductId']);
                $stringProductId = implode(",", $arrayDiff);
            }
        }
        
        $result = [
            "sucess" => $success,
            "list" => $stringProductId,
            "returnProducts" => $returnProducts
        ];

        $response = $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');

        return $response;
    }

    public function getMore2Action(){
        $success = false;
        $checkLastItemList = true;
        //Params
        $listProductId = $this->getRequest()->getParam('list');
        $limit = $this->getRequest()->getParam('limit');
        $category_id = $this->getRequest()->getParam('category');
         // vi true gui tu ajax js len String ko phai boolean;
        $mobile_grid_page = $this->getRequest()->getParam('mobile_grid_page') == "true" ? true : false;
        $arrayProductId = explode(",", $listProductId);
        $stringProductId = null;
        $type_name_order = $this->getRequest()->getParam("type_name_order");
        $show_ui_progress = $this->getRequest()->getParam("bar_gridSlider") == "true" ? true : false;
        //
        $attribute_code = $this->getRequest()->getParam('attribute_code');
        $attribute_value = $this->getRequest()->getParam('attribute_value');
        //$attribute_data = $this->getRequest()->getParam('attribute_data');
        $block_type = $this->getRequest()->getParam('block_type');
        
        
        if ($block_type == 'attribute') {
            $page = $this->getRequest()->getParam('page');
            if (isset($page)) {
                $_GET['p'] = $page;
            }
            // get by attr : 
            $products_limit = null;
            $sort_by = $type_name_order;
            $max_ck = $min_ck = null;
            $product_ids_str = $product_id = $exclude_catId = null;
            $show_ui_progress = $backup_cat_id = $backup_order_by = $series_id = null;
            $block_type = 'attribute';
            if ($category_id && $category_id != 'all' && !empty($category_id)) {
                $category_id = $category_id;
            } else {
                $category_id = null;
            }
            $returnProducts = Mage::helper('tabslider/data')->getProducts($products_limit, $sort_by, $max_ck, $min_ck, $category_id, $block_type, $attribute_code, $attribute_value, $attribute_data, $product_ids_str, $product_id, $exclude_catId, false, $show_ui_progress, $backup_cat_id, $backup_order_by, $series_id);
            if (count($returnProducts) > 0) {
                $success = true;
            }
        } else {
            $resultData = Mage::helper('tabslider/data')->getProductPageSlider($listProductId, $limit, $category_id, $mobile_grid_page, $type_name_order, $show_ui_progress);
            $returnProducts = $resultData['returnProducts'];
            if (count($returnProducts) > 0) {
                $success = true;
                // loai bo nhung product da lay trong list:
                $arrayDiff = array_diff($arrayProductId, $resultData['listProductId']);
                $stringProductId = implode(",", $arrayDiff);
            }
        }

        $result = [
            "success" => $success,
            "list" => $stringProductId,
            "returnProducts" => $returnProducts,
        ];
        $response = $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');

        return $response;
    }
    
    public function getRecommendedDataAction(){
        $productId = $this->getRequest()->getParam('product_id');
        $dataArray = array(
            "product_id" => $productId
        );
        $url = "http://app5.fahasa.com:18082/api/related_products";
        $tabsliderHelper = Mage::helper('tabslider/data');
        $dataResult = $tabsliderHelper->execPostRequest($url,json_encode($dataArray));
        $response = $this->getResponse()->setBody(json_encode($dataResult))
                ->setHeader('Content-type', 'application/json');
        return $response;
    }
    
    public function getProductDataAction() {
        $productId = $this->getRequest()->getParam('product_id');
        $type_id = $this->getRequest()->getParam('tab_id');
        $dataArray = array(
            "product_id" => $productId,
            "tab_id" => $type_id
        );
        $url = "http://app5.fahasa.com:18082/api/tab_related_products";
//        $url = "app4.fahasa.com:18082/api/related_products";
        $tabsliderHelper = Mage::helper('tabslider/data');
        $dataResult = $tabsliderHelper->execPostRequest($url, json_encode($dataArray));
        $response = $this->getResponse()->setBody(json_encode($dataResult))
                ->setHeader('Content-type', 'application/json');
        return $response;
    }

}
