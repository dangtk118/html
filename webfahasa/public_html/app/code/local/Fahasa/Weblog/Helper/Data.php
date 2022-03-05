<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Fahasa_Weblog_Helper_Data extends Mage_Core_Helper_Abstract {

    static protected $SESSIONID = 'sid=';
    static protected $ORIGSESSIONID = 'osid=';
    static protected $ACTION = 'a=';
    static protected $PRODUCTID = 'pid=';
    static protected $PRODUCTNAME = 'pname=';
    static protected $PRODUCTQTY = 'pqty=';
    static protected $PRODUCTDISCOUNT = 'pdiscount=';
    static protected $PRODUCTSOONRELEASE = 'psoonrelease=';
    static protected $CATEGORYID = 'catid=';
    static protected $USERID = 'uid=';
    static protected $REFERER = 'ref=';
    static protected $USERAGENT = 'uagent=';
    static protected $SHIPPNGMETHOD = 'shipping=';
    static protected $PAYMENTMETHOD = 'payment=';
    static protected $SEARCH = 'search=';
    static protected $SEARCHSTROUT = 'result=';
    static protected $CMSPAGETITLE = 'title=';
    static protected $URL = 'url=';
    static protected $QUOTEID = 'quoteid=';
    static protected $AFFID = 'affid=';
    //ACTION NAME
    static protected $ACT_VIEWPRODUCT = 'view_product';
    static protected $ACT_VIEWCATEGORY = 'view_category';
    static protected $ACT_ADDPROTOCART = 'add_product_to_cart';
    static protected $ACT_REMPROFROMCART = 'remove_product_from_cart';
    static protected $ACT_SUSSESSORDER = 'submit_order';
    static protected $ACT_SHIPPINGMETHOD = 'shipping_method';
    static protected $ACT_PAYMENTMETHOD = 'payment_method';
    static protected $ACT_LOGIN = 'login';
    static protected $ACT_SEARCH = 'search';
    static protected $ACT_VIEWCMSPAGE = 'view_cms_page';
    static protected $ACT_VIEWFSTOREPAGE = 'view_fpointstore_page';
    static protected $ACT_VIEWVOUCHERPAGE = 'view_voucher_fpointstore';
    static protected $ACT_VIEWCOMBOPAGE = 'view_combo_fpointstore';
    static protected $ACT_CHANGEVOUCHER = 'change_voucher_fpointstore';
    static protected $ACT_CHANGECOMBOE = 'change_combo_fpointstore';
    static protected $ACT_PRODUCTVIEWED = 'product_viewed_page';
    static protected $ACT_SETAFFILIATE = 'set_affiliate';
    static protected $LOG_FILE = '__web.log';
    static protected $LOG_FILE_MOBILE = '__mobile.log';
    public static $isMobile = false;

    public function logFileBasedOnChannel($strlog, $isMobile = false) {
        if ($isMobile) {
            Mage::log($strlog, null, gethostname() . self::$LOG_FILE_MOBILE);
        } else {
            Mage::log($strlog, null, gethostname() . self::$LOG_FILE);
        }
    }

    public function logFileBasedOnGlobal($strlog) {
        if (self::$isMobile) {
            Mage::log($strlog, null, gethostname() . self::$LOG_FILE_MOBILE);
        } else {
            Mage::log($strlog, null, gethostname() . self::$LOG_FILE);
        }
    }

    public function GetUserId() {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $uId = Mage::getSingleton('customer/session')->getId(); // Id 
        } else {
            $uId = 'guest';
        }
        return $uId;
    }

    public function FinishCheckout($quote, $isMobile = false, $affId) {
        $productId = $quote->getAllItems();
        $str = null;
        foreach ($productId as $pro) {
            if ($str == null) {
                $str = $pro->getProductId();
            } else {
                $str = $str . "-" . $pro->getProductId();
            }
        }
        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $strlog = self::$SESSIONID . $sessionId . ','
                . self::$ACTION . self::$ACT_SUSSESSORDER . ','
                . self::$PRODUCTID . '"' . $str . '",'
                . self::$QUOTEID . $quote->getEntityId() . ','
                . self::$AFFID . '"' . $affId . '",'
                . self::$USERID . $this->GetUserId()
        ;
        $this->logFileBasedOnChannel($strlog, $isMobile);
    }

    public function SaveShippingMethod($shipping, $isMobile) {
        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $strlog = self::$SESSIONID . $sessionId . ','
                . self::$ACTION . self::$ACT_SHIPPINGMETHOD . ','
                . self::$SHIPPNGMETHOD . $shipping . ','
                . self::$USERID . $this->GetUserId()
        ;
        $this->logFileBasedOnChannel($strlog, $isMobile);
    }

    public function SavePaymentMethod($payment) {
        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $strlog = self::$SESSIONID . $sessionId . ','
                . self::$ACTION . self::$ACT_PAYMENTMETHOD . ','
                . self::$PAYMENTMETHOD . $payment . ','
                . self::$USERID . $this->GetUserId()
        ;
        $this->logFileBasedOnGlobal($strlog);
    }

    public function Search($isMobile = false) {
        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();

        if (isset($_GET['f'])) {
            $str = self::$SEARCH . '"' . str_replace('"', ' ', $_GET['f']) . '",'
                    . self::$SEARCHSTROUT . '"' . str_replace('"', ' ', $_GET['q']) . '"';
        } else {
            $str = self::$SEARCH . '"' . str_replace('"', ' ', $_GET['q']) . '"';
        }

        $strlog = self::$SESSIONID . $sessionId . ','
                . self::$ACTION . self::$ACT_SEARCH . ','
                . self::$URL . '"' . Mage::helper('core/url')->getCurrentUrl() . '",'
                . self::$USERID . $this->GetUserId() . ','
                . $str;

        $this->logFileBasedOnChannel($strlog, $isMobile);
    }

    public function ViewProduct($product, $isMobile = false) {//view product
	if(!empty($product['product_id'])){
	    $productId = $product['product_id'];
	    $productName = $product['product_name'];
	    $productName = str_replace("\n", "", $productName);
	    $productName = str_replace("\r", "", $productName);
	    $productFinalPrice = $product['final_price'];
	    $productPrice = $product['price'];
	    $discountPercent = $product['discount'];
	    $productSoonRelease = $product['soon_release'];
	}else if(!empty($product['entity_id'])){
	    $productId = $product['entity_id'];
	    $productName = $product['name'];
	    $productName = str_replace("\n", "", $productName);
	    $productName = str_replace("\r", "", $productName);
	    $productFinalPrice = $product['final_price'];
	    $productPrice = $product['price'];
	    $discountPercent = $product['discount_percent'];
	    $productSoonRelease = $product['soon_release'];
	}else{
	    $productId = $product->getId();
	    $productName = $product->getName();
	    $productName = str_replace("\n", "", $productName);
	    $productName = str_replace("\r", "", $productName);
	    $productFinalPrice = $product->getFinalPrice();
	    $productPrice = $product->getPrice();
	    $discountPercent = 0;
	    if ($product->getTypeId() == "simple") {
		$discountPercent = round(100 - (($productFinalPrice * 100) / $productPrice), 0);
	    }
	    $productSoonRelease = $product->getSoonRelease();
	}
	
        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $strlog = self::$SESSIONID . $sessionId . ','
                . self::$ACTION . self::$ACT_VIEWPRODUCT . ','
                . self::$PRODUCTID . $productId . ','
                . self::$PRODUCTNAME . '"' . $productName . '",'
                . self::$PRODUCTDISCOUNT . '"' . $discountPercent . '",'
                . self::$PRODUCTSOONRELEASE . '"' . $productSoonRelease . '",'
                . self::$URL . '"' . Mage::helper('core/url')->getCurrentUrl() . '",'
                . self::$USERID . $this->GetUserId();
        $this->logFileBasedOnChannel($strlog, $isMobile);
    }

    public function ViewCategory($category, $isMobile = false) {
//        $category = $observer->getEvent()->getData('category');
        $categoryId = $category->getName();
        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $strlog = self::$SESSIONID . $sessionId . ','
                . self::$ACTION . self::$ACT_VIEWCATEGORY . ','
                . self::$CATEGORYID . $categoryId . ','
                . self::$URL . '"' . Mage::helper('core/url')->getCurrentUrl() . '",'
                . self::$USERID . $this->GetUserId();
        $this->logFileBasedOnChannel($strlog, $isMobile);
    }

    public function ViewCmsPage($page, $isMobile = false) {
//        $page = $observer->getEvent()->getPage();
        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $strlog = self::$SESSIONID . $sessionId . ','
                . self::$ACTION . self::$ACT_VIEWCMSPAGE . ','
                . self::$CMSPAGETITLE . '"' . $page->getTitle() . '",'
                . self::$URL . '"' . Mage::helper('core/url')->getCurrentUrl() . '",'
                . self::$USERID . $this->GetUserId();
//        Mage::log($strlog, null, self::$LOG_FILE);
        $this->logFileBasedOnChannel($strlog, isset($isMobile) && $isMobile);
    }

    public function AddProductToCart($product) {
//        $product = $observer->getEvent()->getData('product');
        $productId = $product->getId();
        $productName = $product->getName();
        $productQty = $product->getQty();
        $productFinalPrice = $product->getFinalPrice();
        $productPrice = $product->getPrice();
        $discountPercent = 0;
        if ($product->getTypeId() == "simple") {
            $discountPercent = round(100 - (($productFinalPrice * 100) / $productPrice), 0);
        }
        $productSoonRelease = $product->getSoonRelease();
        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $strlog = self::$SESSIONID . $sessionId . ','
                . self::$ACTION . self::$ACT_ADDPROTOCART . ','
                . self::$PRODUCTID . $productId . ','
                . self::$PRODUCTNAME . '"' . $productName . '",'
                . self::$PRODUCTQTY . '"' . $productQty . '",'
                . self::$PRODUCTDISCOUNT . '"' . $discountPercent . '",'
                . self::$PRODUCTSOONRELEASE . '"' . $productSoonRelease . '",'
                . self::$USERID . $this->GetUserId();
        $this->logFileBasedOnGlobal($strlog);
    }

    public function RemoveProductFromCart($quoteItem) {
//        $quoteItem = $observer->getEvent()->getQuoteItem();
        $product = $quoteItem->getProduct();
        $productId = $product->getId();
        $productName = $product->getName();
        $productQty = $quoteItem->getQty(); // qty from carts
        $productFinalPrice = $product->getFinalPrice();
        $productPrice = $product->getPrice();
        $discountPercent = 0;
        if ($product->getTypeId() == "simple") {
	    if($productPrice > 0){
		$discountPercent = round(100 - (($productFinalPrice * 100) / $productPrice), 0);
	    }else{
		$discountPercent = 0;
	    }
        }
        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $strlog = self::$SESSIONID . $sessionId . ','
                . self::$ACTION . self::$ACT_REMPROFROMCART . ','
                . self::$PRODUCTID . $productId . ','
                . self::$PRODUCTNAME . '"' . $productName . '",'
                . self::$PRODUCTQTY . '"' . $productQty . '",'
                . self::$PRODUCTDISCOUNT . '"' . $discountPercent . '",'
                . self::$USERID . $this->GetUserId();
        $this->logFileBasedOnGlobal($strlog);
    }

    public function LogIn() {
        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $origSessId = $_COOKIE["frontend"];
        if ($origSessId == null) {
            $origSessId = '';
        }
        $strlog = self::$SESSIONID . $sessionId . ','
                . self::$ORIGSESSIONID . $origSessId . ','
                . self::$ACTION . self::$ACT_LOGIN . ','
                . self::$USERID . $this->GetUserId()
        ;
         $this->logFileBasedOnGlobal($strlog);
    }

//    static protected $ACT_VIEWFSTOREPAGE = 'view_fpointstore_page';
//    static protected $ACT_VIEWVOUCHERPAGE = 'view_voucher_fpointstore';
//    static protected $ACT_VIEWCOMBOPAGE = 'view_combo_fpointstore';
//    static protected $ACT_CHANGEVOUCHER = 'change_voucher_fpointstore';
//    static protected $ACT_CHANGECOMBOE = 'change_combo_fpointstore';
    public function FpointStorePage($title, $action, $customer_Id, $id = 0, $isMobile = false) {
	switch ($action){
	    case 'view_page':
		$action_log = self::$ACT_VIEWFSTOREPAGE;
		break;
	    case 'view_voucher':
		$action_log = self::$ACT_VIEWVOUCHERPAGE;
		break;
	    case 'view_combo':
		$action_log = self::$ACT_VIEWCOMBOPAGE;
		break;
	    case 'change_voucher':
		$action_log = self::$ACT_CHANGEVOUCHER;
		break;
	    case 'change_combo':
		$action_log = self::$ACT_CHANGECOMBOE;
		break;
	    case 'product_viewed_page':
		$action_log = self::$ACT_PRODUCTVIEWED;
		break;
	    default :
		$action_log = $action;
	}
	
        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $strlog = self::$SESSIONID . $sessionId . ','
                . self::$ACTION . $action_log . ','
                . self::$CMSPAGETITLE . '"' . $title . '",'
                . self::$URL . '"' . Mage::helper('core/url')->getCurrentUrl() . ($id?('/id/'.$id):'').'",'
                . self::$USERID . $customer_Id;
        $this->logFileBasedOnChannel($strlog, isset($isMobile) && $isMobile);
    }
    
    public function SetAffiliate($affId, $isMobile = false) {
        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $strlog = self::$SESSIONID . $sessionId . ','
                . self::$ACTION . self::$ACT_SETAFFILIATE . ','
                . self::$AFFID . '"' . $affId . '",'
                . self::$USERID . $this->GetUserId();
        $this->logFileBasedOnChannel($strlog, isset($isMobile) && $isMobile);
    }
}
