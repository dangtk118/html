<?php

class Fahasa_Discountlabel_Helper_Data extends Mage_Core_Helper_Abstract {

    const CUSTOM_BACKORDERS_YES_NOTIFY = 2;
    const CUSTOM_STOCK_INSTOCK = 1;
    
    /**
     * Handle display discount label everywhere, including mobile vs desktop
     * Handle if discount percent is too high, then dont display it
     */
    public function handleDisplayDiscountLabel($_product, $isTabslider, $isProductDetail) {        
        $mobile = Mage::helper('fhsmobiledetect')->isMobile();
        $discountHtml = "";
        $isBundleDynamicPrice = false;
        if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            if($_product->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC){
                $isBundleDynamicPrice = true;                
                $bundleSpecialPrice = 0.0;
                $bundlePrice = 0.0;
                $_selections = $_product->getTypeInstance(true)->getSelectionsCollection($_product->getTypeInstance(true)->getOptionsIds($_product), $_product);
                foreach($_selections as $_selection){
                    $sp = $_product->getPriceModel()->getSelectionPreFinalPrice($_product, $_selection);
                    $bundleSpecialPrice += $sp;
                    $bundlePrice += $_selection->getPrice() * $_selection->getSelectionQty();
                }
                
                if($bundlePrice > 0) {
                    $maxDiscount = round((1 - $bundleSpecialPrice / $bundlePrice) * 100);
                } else {
                    $maxDiscount = 0;
                }
                $discountHtml = $this->displayDiscountLabel($mobile, $maxDiscount, $isTabslider, $isProductDetail, $isBundleDynamicPrice);
            }else{
                $special = $_product->getSpecialPrice();
                if ($special) {
                    $discount = 100 - $special;                
                    $discountHtml = $this->displayDiscountLabel($mobile, $discount, $isTabslider, $isProductDetail, $isBundleDynamicPrice);
                }
            }
        } else {
            // Get the Special Price
            $specialprice = $_product->getFinalPrice();
            $price = $_product->getPrice();            
            if ($specialprice && $specialprice > 0 && $specialprice < $price && Mage::getStoreConfig('themeoptions/themeoptions_config/sale_label')) {
                $discount = round((1 - $specialprice / $price) * 100);                
                $discountHtml .= $this->displayDiscountLabel($mobile, $discount, $isTabslider, $isProductDetail, $isBundleDynamicPrice);        
            }
        }
        return $discountHtml;
    }
    
    /*
     * viet lai tu ham Fahasa_Discountlabel_Helper_Data => handleDisplayDiscountLabel
    */
    public static function handleDiscountPercent($_product) {
        $discount = 0;
        if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            if ($_product->getPriceType() == \Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC) {
                $maxDiscount = 0;
                $_selections = $_product->getTypeInstance(true)->getSelectionsCollection($_product->getTypeInstance(true)->getOptionsIds($_product), $_product);
                $bundleSpecialPrice = 0.0;
                $bundlePrice = 0.0;
                foreach ($_selections as $_selection) {
                    $sp = $_product->getPriceModel()->getSelectionPreFinalPrice($_product, $_selection);
                    $bundleSpecialPrice += $sp;
                    $bundlePrice += $_selection->getPrice() * $_selection->getSelectionQty();
                }
                
                if ($bundlePrice > 0) {
                    $maxDiscount = round((1 - $bundleSpecialPrice / $bundlePrice) * 100);
                } else {
                    $maxDiscount = 0;
                }
                $discount = $maxDiscount;
            } else {
                $specialPrice = $_product->getSpecialPrice();
                $specialDateFrom = $_product->getSpecialFromDate();
                $pecialDateTo = $_product->getSpecialToDate();
                if (!is_null($specialPrice) && $specialPrice != false) {
                    if (Mage::app()->getLocale()->isStoreDateInInterval(null, $specialDateFrom, $pecialDateTo)) {
                        $discount = 100 - $specialPrice;
                    }
                }
            }
        } else {
            $specialprice = $_product->getFinalPrice();
            $price = $_product->getPrice();
            if ($specialprice && $specialprice > 0 && $specialprice < $price) {
                $discount = round((1 - $specialprice / $price) * 100);
            } 
        }
        return $discount;
    }

    private function displayDiscountLabel($mobile, $discount, $isTabslider, $isProductDetail, $isBundleDynamicPrice){
        if($discount <= 0){
            return "";
        }
        $shouldDisplayDeepDiscount = $discount > Mage::getStoreConfig("discountlabel/general/maxdiscount") ;
        $discountHtml = $this->displayHtmlDiscountLabel($mobile, $discount, $isTabslider, $isProductDetail, $isBundleDynamicPrice, $shouldDisplayDeepDiscount);
        return $discountHtml;
    }
    
    public function handleDiscountLabelForBundleItem($_product){
        $mobile = Mage::helper('fhsmobiledetect')->isMobile();
        $discountHtml = "";
        $price = $_product->getPrice();
        $specialprice = $this->getCurrentSelectedProduct()->getPriceModel()->getSelectionPreFinalPrice($this->getCurrentSelectedProduct(), $_product);
        if ($specialprice && $specialprice > 0 && $specialprice < $price && Mage::getStoreConfig('themeoptions/themeoptions_config/sale_label')) {
            $discount = round((1 - $specialprice / $price) * 100);
            //if($discount > Mage::getStoreConfig("discountlabel/general/maxdiscount") || $discount <= 0){
            if($discount > Mage::getStoreConfig("discountlabel/general/maxdiscount") || $discount <= 0){
                $discountHtml = '<div class="label-pro-sale deepdiscount"></div>';
                return $discountHtml;
            }
            $discountHtml = '<div class="bundle-item-label-pro-sale dis-label-m-mar"><span class="m-discount-l-fs dis-per-bundle-item">' . $discount . '%</span></div>';         
        }
        return $discountHtml;
    }
    
    public function getCurrentSelectedProduct(){        
        return Mage::registry('current_product');
    }
    
    private function displayHtmlDiscountLabel($mobile, $discountAmt, $isTabslider, $isProductDetail, $isBundleDynamicPrice, $shouldDisplayDeepDiscount) {
        $discountHtml = "";
        $discountAmt .= "%"; 
        
        if($isProductDetail){
            if(!$shouldDisplayDeepDiscount){
                $discountHtml .= '<div class="label-pro-sale f-dis-label"><span class="p-sale-label">' . $discountAmt .'</span></div>';
            }else{
                $discountHtml .= '<div class="label-pro-sale deepdiscount"></div>';
            }            
        }else if($isTabslider){
            if($mobile){
                if(!$shouldDisplayDeepDiscount){
                    $discountHtml .= '<div class="m-label-pro-sale label-pro-sale"><span class="p-sale-label m-discount-l-fs">' . $discountAmt .'</span></div>';
                }else{
                    $discountHtml .= '<div class="m-label-pro-sale m-deepdiscount label-pro-sale"></div>';
                }                
            }else{
                if(!$shouldDisplayDeepDiscount){
                    $discountHtml .= '<div class="label-pro-sale m-label-pro-sale"><span class="p-sale-label discount-l-fs">' . $discountAmt .'</span></div>';
                }else{
                    $discountHtml .= '<div class="label-pro-sale deepdiscount m-label-pro-sale"></div>';
                }
                
            }
        }else{
            if ($mobile) {
                if(!$shouldDisplayDeepDiscount){
                    $discountHtml .= '<div class="m-label-pro-sale dis-label-m-mar label-pro-sale"><span class="p-sale-label m-discount-l-fs">' . $discountAmt . '</span></div>';
                }else{
                    $discountHtml .= '<div class="m-label-pro-sale m-deepdiscount label-pro-sale"></div>';
                }
            } else {
                if(!$shouldDisplayDeepDiscount){
                    $discountHtml .= '<div class="label-pro-sale m-label-pro-sale"><span class="p-sale-label">' . $discountAmt . '</span></div>';
                }else{
                    $discountHtml .= '<div class="label-pro-sale deepdiscount m-label-pro-sale"></div>';
                }                
            }
        }   
        return $discountHtml;
    }

    /**
     * Display "Add To Cart" button. This will handle
     * 1. Dynamically display either Pre-order/Add To Cart or Out of stock button
     * 2. Handle different for case of bundle as the user need to check which product
     * will be in their combo. Therefore in this case, click this button will redirect
     * to the product page     
     */
    public function displayBuyButton($_product){        
        if($_product->isSaleable()){
            $quan = $_product->getQty();
            $rquantity = 0;
            if($quan){
                $rquantity = (int)$quan;
            }else{
                $rquantity= (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product)->getQty(); 
            }            
            //$rbackorder = $_product->getStockItem()->getBackorders();
            $rstockavailability = $_product->getStockItem()->getIsInStock();
            if (($rquantity <= 0)  && ($rstockavailability == self::CUSTOM_STOCK_INSTOCK)) {
                if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
                    return '<p><button type="button" title="'.$this->__('Add to Cart').'" class="button btn-bundle-cart" onclick="'."location.href='".$_product->getProductUrl()."';".'"><span><span><i class="fa fa-shopping-cart"></i>'.$this->__('Add to Cart').'</span></span></button></p>';
                }else{
                    return '<p><button type="button" title="'.$this->__('Pre-Order').'" class="button btn-bundle-cart" onclick="'."location.href='".$_product->getProductUrl()."';".'"><span><span><i class="fa fa-calendar"></i>'.$this->__('Pre-Order').'</span></span></button></p>';
                }                
            } else {
                if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
                    return '<p><button type="button" title="'.$this->__('Add to Cart').'" class="button btn-bundle-cart" onclick="'."location.href='".$_product->getProductUrl()."';".'"><span><span><i class="fa fa-shopping-cart"></i>'.$this->__('Add to Cart').'</span></span></button></p>';
                }else{
                    return '<p><button type="button" title="'.$this->__('Add to Cart').'" class="button btn-cart" onclick="'."setLocation('".$_product->getId()."')".'"><span><span><i class="fa fa-shopping-cart"></i>'.$this->__('Add to Cart') .'</span></span></button></p>';
                }                
            } 
        }else{
            return '<p><button type="button" title="'.$this->__('Out of stock').'" class="button btn-cart hethang" onclick="'."setLocation('".$_product->getId()."')".'"><span><span><i class="fa fa-shopping-cart hhcart"></i>'.$this->__('Out of stock').'</span></span></button></p>';
        }        
    }
    
    public function shortenText($text, $maxlength){
        $shorten_text = "";
        if(mb_strlen($text, 'UTF-8') > $maxlength){
            $shorten_text .= mb_substr($text, 0, $maxlength, 'UTF-8') . '...';
        }else{
            $shorten_text .= $text;
        }
        return $shorten_text;
    }
    
    /**
     * Display product price
     * Handle if discount percent is too high, then dont display it
     */
    public function displayProductPrice($_product, $price, $specialprice){
        $sym = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
        $html = "";
        if($price != $specialprice){

            if($specialprice != '')
            {
                $html .= '<p class="special-price">
                <span class="price-label">Special Price</span>
                <span id="product-price-'.$_product->getId().'" class="price">'.''.number_format($specialprice,0, ",", ".").$sym.'</span>
                </p> ';
            }             
            if($price != ''){
                //$discount = round((1 - $specialprice / $price) * 100);
                //if($discount > Mage::getStoreConfig("discountlabel/general/maxdiscount") || $discount <= 0){
                //    return "";
                //}
                $html .= '<p class="old-price bg-white"><span class="price-label">';
                $html .= $this->__('Regular Price'). ': </span>';
                $html .= '<span id="old-price-'.$_product->getId().'" class="price">'.''.number_format($price,0, ",", ".").$sym.'</span></p>';
            }
        }else {
            return false;
        }
        return $html;
    }
    
    /**
     * Display product price mobile
     * Handle if discount percent is too high, then dont display it
     */
    public function displayProductPriceMobile($_product, $price, $specialprice){
        $sym = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
        $html = "";
        if($price != $specialprice){

            if($specialprice != '')
            {
                $html .= '<div class="special-price">
                <span id="product-price-'.$_product->getId().'" class="price">'.''.number_format($specialprice,0, ",", ".")." ".$sym.'</span>
                </div> ';
            }             
            if($price != ''){
                //$discount = round((1 - $specialprice / $price) * 100);
                //if($discount > Mage::getStoreConfig("discountlabel/general/maxdiscount") || $discount <= 0){
                //    return "";
                //}
                $html .= '<div class="old-price">';
                $html .= '<span id="old-price-'.$_product->getId().'" class="price">'.''.number_format($price,0, ",", ".")." ".$sym.'</span></div>';
            }
        }else {
                $html = "";
                $html .= '<div class="special-price">
                <span id="product-price-'.$_product->getId().'" class="price">'.''.number_format($specialprice,0, ",", ".")." ".$sym.'</span>
                </div> ';
        }
        return $html;
    }
    
    /// Replace DiscountLabel Helper -> displayProductPrice()
    public function displayProductPriceOnWeb($product){
        $symbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
        $id = $product->getId();
        
        /*
         *  Note:
         *  - Can't use $product->getPrice(), Check Core/Mage/Catalog/Model/Product.php , getPrice()
         */
        $price = $product->getData('price');
        $final_price = $product->getFinalPrice();
        
        $type_id = $product->getTypeId();
        
        $html = "";
        switch($type_id){
            /// Type Id = Simple, Bundle, Configurable, Grouped, Virtual
            case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                if ($product->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC) {
                    $min_price = $product->getMinPrice();
                    $max_price = $product->getMaxPrice();
                    
                    if($min_price == $max_price){
                        $html = $this->getProductPriceHtml($id, $price, $final_price, $symbol);
                    }else{
                        $html = "";
                    }
                }else{
                    $html = $this->getProductPriceHtml($id, $price, $final_price, $symbol);
                }
            break;
            default:
                /// Type Id - Simple
                $html = $this->getProductPriceHtml($id, $price, $final_price, $symbol);
        }
        
        return $html;
    }
    
    public function getProductPriceHtml($id, $price, $final_price, $symbol){
        $html = "";
        
        if ($final_price != '' && $final_price !=0 ) {
            $html .= "<p class='special-price'><span class='price-label'>Special Price</span>"
                . "<span id='product-price-" . $id . "' class='price m-price-font'>" . number_format($final_price, 0, ",", ".") . $symbol 
                . "</span></p>";
        }
        
        if($price != $final_price){
            if ($price != '') {
                $html .= "<p class='old-price bg-white'><span class='price-label'>" . $this->__('Regular Price') . ": </span>"
                    . "<span id='old-price-" . $id . "' class='price m-price-font'>". number_format($price, 0, ",", ".") . $symbol
                    . "</span></p>";
            }
        }
        
        return $html;
    }   
    public function getBundlePrice($_product){
        try{
            if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                if($_product->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC){
                    if($_product->getPrice() == 0){
                        $_selections = $_product->getTypeInstance(true)->getSelectionsCollection($_product->getTypeInstance(true)->getOptionsIds($_product), $_product);
                        foreach($_selections as $_selection){
                            $sp = $_product->getPriceModel()->getSelectionPreFinalPrice($_product, $_selection);
                            $bundleSpecialPrice += $sp;
                            $bundlePrice += $_selection->getPrice() * $_selection->getSelectionQty();
                        }
                        $_product->setFinalPrice($bundleSpecialPrice);
                        $_product->setPrice($bundlePrice);
                        return true;
                    }
                }
            }
        } catch (Exception $ex) {}
        return false;
     }
     
    public function displayDiscountLabelHtml($mobile, $discount){
        if($discount <= 0){
            return "";
        }
        $shouldDisplayDeepDiscount = $discount > Mage::getStoreConfig("discountlabel/general/maxdiscount") ;
        $discountHtml = $this->displayHtmlDiscountLabel($mobile, $discount, false, false, false, $shouldDisplayDeepDiscount);
        return $discountHtml;
    }
}
