<?php

class Fahasa_YKienKhachHang_IndexController extends Mage_Core_Controller_Front_Action {

    public function IndexAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle($this->__("Ý kiến khách hàng"));
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        $breadcrumbs->addCrumb("home", array(
            "label" => $this->__("Home Page"),
            "title" => $this->__("Home Page"),
            "link" => Mage::getBaseUrl()
        ));

        $breadcrumbs->addCrumb("Ý kiến khách hàng", array(
            "label" => $this->__("Ý kiến khách hàng"),
            "title" => $this->__("Ý kiến khách hàng")
        ));

        $this->renderLayout();
    }

    public function postAction() {
	if(Mage::getSingleton('customer/session')->isLoggedIn()){
	    $product_helper = Mage::helper('fahasa_catalog/product');
	    $customer = Mage::getSingleton('customer/session')->getCustomer();
	    
	    $email = $customer->getRealEmail();
	    $customer_id = $customer->getEntityId();
	    
	    $helper = Mage::helper('ykienkhachhang');
	    $sanpham1_note = $giaohang1_note = $giaohang2_note = $cskh1_note = null;
	    $post = $this->getRequest();
	    $order_id = $product_helper->cleanBug($post->getPost("order_id"));
	    $sanpham1 = $product_helper->cleanBug($post->getPost("sanpham1"));
	    
	    if(empty($helper->checkExistOrder($order_id, $customer_id))){
		$this->_redirectReferer();
	    }
	    
	    if ($sanpham1 == 1 || $sanpham1 == 2 || $sanpham1 == 3) {
		$sanpham1_note = $product_helper->cleanBug($post->getPost("sanpham1_note"));
	    }
	    $giaohang1 = $product_helper->cleanBug($post->getPost("giaohang1"));
	    if ($giaohang1 == 1 || $giaohang1 == 2) {
		$giaohang1_note = $product_helper->cleanBug($post->getPost("giaohang1_note"));
	    }
	    $giaohang2 = $product_helper->cleanBug($post->getPost("giaohang2"));
	    if ($giaohang2 == 1) {
		$giaohang2_note = $product_helper->cleanBug($post->getPost("giaohang2_note"));
	    }
	    $giaohang3 = $product_helper->cleanBug($post->getPost("giaohang3"));
	    $cskh1 = $product_helper->cleanBug($post->getPost("cskh1"));
	    if ($cskh1 == 1) {
		$cskh1_note = $product_helper->cleanBug($post->getPost("cskh1_note"));
	    }

	    $write = Mage::getSingleton("core/resource")->getConnection("core_write");
	    $exits = $helper->checkExistSurvey($order_id);
	    if(!empty($exits)) {
		if(empty($exits['coupon'])){
		    $couponCode = $helper->couponCodeSurvey($email, $order_id);
		    //update have coupon
		    $query = "update y_kien_khach_hang set "
			    . "chat_luong_sp = :chat_luong_sp, "
			    . "chat_luong_sp_note = :chat_luong_sp_note, "
			    . "thoi_gian_giao_hang = :thoi_gian_giao_hang, "
			    . "thoi_gian_giao_hang_note = :thoi_gian_giao_hang_note, "
			    . "thai_do_nv_giao_hang = :thai_do_nv_giao_hang, "
			    . "thai_do_nv_giao_hang_note = :thai_do_nv_giao_hang_note, "
			    . "nv_giao_hang_lien_he_truoc_khi_giao = :nv_giao_hang_lien_he_truoc_khi_giao,"
			    . "cham_soc_khach_hang = :cham_soc_khach_hang,"
			    . "cham_soc_khach_hang_note = :cham_soc_khach_hang_note, "
			    . "date = NOW(),"
			    . "status = 'complete' ,"
			    . "customer_id = :customer_id ,"
			    . "coupon = :coupon "
			    . "where "
			    . "order_id = :order_id ";
		    $binds = array(
			'customer_id' => $customer_id,
			'order_id' => $order_id,
			'chat_luong_sp' => $sanpham1,
			'chat_luong_sp_note' => "$sanpham1_note",
			'thoi_gian_giao_hang' => $giaohang1,
			'thoi_gian_giao_hang_note' => "$giaohang1_note",
			'thai_do_nv_giao_hang' => $giaohang2,
			'thai_do_nv_giao_hang_note' => "$giaohang2_note",
			'nv_giao_hang_lien_he_truoc_khi_giao' => $giaohang3,
			'cham_soc_khach_hang' => $cskh1,
			'cham_soc_khach_hang_note' => "$cskh1_note",
			'coupon' => "$couponCode"
		    );
		}else{
		    //update dont update coupon
		    $query = "update y_kien_khach_hang set "
			    . "chat_luong_sp = :chat_luong_sp, "
			    . "chat_luong_sp_note = :chat_luong_sp_note, "
			    . "thoi_gian_giao_hang = :thoi_gian_giao_hang, "
			    . "thoi_gian_giao_hang_note = :thoi_gian_giao_hang_note, "
			    . "thai_do_nv_giao_hang = :thai_do_nv_giao_hang, "
			    . "thai_do_nv_giao_hang_note = :thai_do_nv_giao_hang_note, "
			    . "nv_giao_hang_lien_he_truoc_khi_giao = :nv_giao_hang_lien_he_truoc_khi_giao,"
			    . "cham_soc_khach_hang = :cham_soc_khach_hang,"
			    . "cham_soc_khach_hang_note = :cham_soc_khach_hang_note, "
			    . "date = NOW(),"
			    . "customer_id = :customer_id ,"
			    . "status = 'complete' "
			    . "where "
			    . "order_id = :order_id ";
		    $binds = array(
			'customer_id' => $customer_id,
			'order_id' => $order_id,
			'chat_luong_sp' => $sanpham1,
			'chat_luong_sp_note' => "$sanpham1_note",
			'thoi_gian_giao_hang' => $giaohang1,
			'thoi_gian_giao_hang_note' => "$giaohang1_note",
			'thai_do_nv_giao_hang' => $giaohang2,
			'thai_do_nv_giao_hang_note' => "$giaohang2_note",
			'nv_giao_hang_lien_he_truoc_khi_giao' => $giaohang3,
			'cham_soc_khach_hang' => $cskh1,
			'cham_soc_khach_hang_note' => "$cskh1_note"
		    );
		}
	    } else {
		$couponCode = $helper->couponCodeSurvey($email, $order_id);
		$query = "insert into y_kien_khach_hang ("
			. "customer_id, "
			. "customer_email, "
			. "order_id, "
			. "chat_luong_sp, "
			. "chat_luong_sp_note, "
			. "thoi_gian_giao_hang, "
			. "thoi_gian_giao_hang_note, "
			. "thai_do_nv_giao_hang, "
			. "thai_do_nv_giao_hang_note, "
			. "nv_giao_hang_lien_he_truoc_khi_giao,"
			. "cham_soc_khach_hang,"
			. "cham_soc_khach_hang_note,"
			. "date,"
			. "coupon,"
			. "status"
			. ") values ("
			. ":customer_id, "
			. ":customer_email, "
			. ":order_id, "
			. ":chat_luong_sp, "
			. ":chat_luong_sp_note, "
			. ":thoi_gian_giao_hang, "
			. ":thoi_gian_giao_hang_note, "
			. ":thai_do_nv_giao_hang, "
			. ":thai_do_nv_giao_hang_note, "
			. ":nv_giao_hang_lien_he_truoc_khi_giao,"
			. ":cham_soc_khach_hang,"
			. ":cham_soc_khach_hang_note, "
			. "NOW(),"
			. ":coupon,"
			. "'complete'"
			. ")";
		$binds = array(
		    'customer_id' => $customer_id,
		    'customer_email' => $email,
		    'order_id' => $order_id,
		    'chat_luong_sp' => $sanpham1,
		    'chat_luong_sp_note' => "$sanpham1_note",
		    'thoi_gian_giao_hang' => $giaohang1,
		    'thoi_gian_giao_hang_note' => "$giaohang1_note",
		    'thai_do_nv_giao_hang' => $giaohang2,
		    'thai_do_nv_giao_hang_note' => "$giaohang2_note",
		    'nv_giao_hang_lien_he_truoc_khi_giao' => $giaohang3,
		    'cham_soc_khach_hang' => $cskh1,
		    'cham_soc_khach_hang_note' => "$cskh1_note",
		    'coupon' => $couponCode
		);
	    }
	    
	    Mage::log("**Survey binds query: " . print_r($binds, true), null, 'survey.log');
	    $write->query($query, $binds);
	    if(!empty($couponCode)){
		$helper->sendCouponToWalletVoucher($couponCode, $customer_id, $order_id);
	    }
	}
	$this->_redirectReferer();
    }

    function checkExistOrderAction() {
	$message = 0;
	
	if(Mage::getSingleton('customer/session')->isLoggedIn()){
	    $customer = Mage::getSingleton('customer/session')->getCustomer();
	    $customer_id = $customer->getEntityId();
	    
	    $orderId = $_POST['orderId'];

	    $order_collection = Mage::getModel('sales/order')->getCollection()
		    ->addFieldToFilter('customer_id', array('in' => array('customer_id', $customer_id)))
		    ->addFieldToFilter('increment_id', array('in' => array('increment_id', $orderId)));
	    if (count($order_collection->getAllIds()) > 0) {
		$message = 1;
	    }
	}
	$this->getResponse()
		->setBody(Mage::helper('core')
			->jsonEncode(array('message' => $message)));
    }
}
