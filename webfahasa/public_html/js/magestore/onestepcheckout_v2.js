const OneStepCheckout = function () {
    let GET_ADDRESS_LIST = "/customer/account/getAddressList/";
    let UPDATE_ADDRESS = "/customer/account/updateAddress/";
    let DELETE_ADDRESS = "/customer/account/deleteAddress/";
    let UPDATE_ADDRESS_DEFAULT = "/onestepcheckout/ajax/setAddressDefault/";
    let CHECKING_EMAIL = "/onestepcheckout/ajax/checkingEmail/";
    let CHECKING_TELEPHONE = "/onestepcheckout/ajax/checkingTelephone/";
    
    let GET_SHIPPING_METHOD = "/onestepcheckout/index/methods1";
    let SET_COUPON_CODE = "/onestepcheckout/index/couponCode";
    let SET_TRYOUT = "/onestepcheckout/index/tryout";
    let GET_CHECKOUT = "/onestepcheckout/index/shipping";
    
    let CREATE_ORDER = '/onestepcheckout/index/createOrder';
    let CHECK_ORDER_STATUS = '/onestepcheckout/index/checkOrderStatus';
    
    let GET_VAT = '/onestepcheckout/index/getVAT';
    let GET_WALLET_VOUCHER = '/fpointstore/index/getWalletVoucherList';
    
    let languages = {};
    let city_Json = {};
    let district_Json = {};
    let ward_Json = {};
    let vat_Json = {};
    let is_login = false;
    let has_address_list = false;
    let address_list = [];
    let address_id= "";
    let is_loading = false;
    let is_loading_coupon = false;
    let is_loading_create_order = false;
    let is_loading_shipping = false;
    let shipping_method_changed = '';
    let event_delivery_method_changed = '';
    let event_delivery_option_changed = '';
    let event_delivery_data = {};
    let payment_method_changed = '';
    let email_checked = '';
    let email_checked_result = false;
    let telephone_checked = '';
    let telephone_checked_result = false;
    let is_first_shipping_address = false;
    let has_changed_shipping_address = false;
    let has_changed_shipping_address_for_getmethod = false;
    let has_changed_group_text = false;
    let has_changed_membership = false;
    let reloaded_wallet_voucher = false;
    let coupon = '';
    
    let require_confirm_shiping_address = false;
    
    let event_cart_is_first = true;
    let event_cart_action_type = '';
    let event_cart_has_option_active = false;
    let event_cart_page_detail = "#";
    let div_clear = "<div class='clear'></div>";
    
    const TIME_REQUEST_QUEUE = 2000;
    const QUEUE_TIME_LIMIT_TO_TRY = 5;
    let time_loaded = 0;
    
    let progressBar = null;
    let event_cart_limit = 2;
    
    var $this = this;
    this.init = function (_is_login, _has_address_list, _address_list, _city_Json, _district_Json, _ward_Json, _languages, _require_confirm_shiping_address, _event_cart_limit = 2, _code_applied) {
	$this.vat_Json = null;
	$this.is_login = _is_login;
	$this.has_address_list = _has_address_list;
	$this.address_list = _address_list;
	$this.city_Json = _city_Json;
	$this.district_Json = _district_Json;
	$this.ward_Json = _ward_Json;
	$this.languages = _languages;
	$this.eventButtonClick();
	$this.eventInputPress();
	$this.shipping_method_changed = '';
	$this.payment_method_changed = $jq('.fhs_checkout_paymentmethod_option:checked').val();
	$this.require_confirm_shiping_address = _require_confirm_shiping_address;
	$this.is_first_shipping_address = false;
	$this.has_changed_membership = false;
	$this.event_cart_limit = _event_cart_limit;
	$this.reloaded_wallet_voucher = false;
	$this.coupon = '';
	
	if($this.has_address_list){
	    $this.updateShippingAddress();
	}else{
	    $jq('#fhs_shipping_country').val($jq('#fhs_shipping_country_select').val());
	    $this.country_change($jq('#fhs_shipping_country_select').val());
	}
	//$this.getAddressList($jq('.fhs_checkout_block_address_list'));
    };
    this.setShippingDefault = function(data){
	$this.is_first_shipping_address = true;
	if(!$this.has_address_list){
	    $jq('#fhs_shipping_fullname').val(data['fullname']);
	    $jq('#fhs_shipping_firstname').val(data['firstname']);
	    $jq('#fhs_shipping_lastname').val(data['lastname']);
	    if(!$this.is_login){
		$jq('#fhs_shipping_email').val(data['email']);
	    }
	    $jq('#fhs_shipping_telephone').val(data['telephone']);
	    $jq('#fhs_shipping_street').val(data['street']);
	    $jq('#fhs_address_postcode').val(data['postcode']);
	    $jq('#fhs_shipping_country_select').val(data['country_id']).trigger('change');
	    $jq('#fhs_shipping_city_select').val(data['region_id']).trigger('change');
	    if(!$jq('#fhs_shipping_city_select').val()){
		$jq('#fhs_shipping_city').val(data['region']);
	    }
	    let district_id = $this.getDistrictId(data['country_id'], data['region_id'], data['city']);
	    if(district_id){
		$jq('#fhs_shipping_district_select').val(district_id).trigger('change');
		let ward_id = $this.getWardId(data['country_id'], data['region_id'], district_id, data['ward']);
		if(ward_id){
		    $jq('#fhs_shipping_wards_select').val(ward_id).trigger('change');
		}
	    }else{
		$jq('#fhs_shipping_district').val(data['city']);
	    }
	}
	setTimeout(function(){
	    $this.is_first_shipping_address = false;
	},100);
    };
    
    //POST
    this.getAddressList = function(element){
	if(has_address_list){return;}
	fhs_account.animateLoaderBlock('start',element);
	$jq.ajax({
	    url: GET_ADDRESS_LIST,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    $this.address_list = data['address_list'];
		    $this.renderAddressList(data['address_id_defaul'],data['address_list'])
		}
		fhs_account.animateLoaderBlock('stop',element);
		$this.updateShippingAddress();
	    }
	});
    };
    this.newAddress = function(firstname, lastname, telephone,
				country_id, city_id, city, district,
				ward, postcode, street){
	$jq.ajax({
	    url: UPDATE_ADDRESS,
	    method: 'post',
	    data:{address_id:'', firstname:firstname, lastname:lastname, telephone:telephone,
				country_id:country_id, city_id, city:city, district:district,
				ward:ward, postcode:postcode, street:street},
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    $this.getAddressList($jq('.fhs_checkout_block_address_list'));
		}
	    }
	});
    };
    this.updateAddress = function(element, firstname, lastname, telephone,
				country_id, city_id, city, district,
				ward, postcode, street){
	if(is_loading && has_address_list){return;}
	fhs_account.animateLoaderBlock('start',element);
	
	$jq.ajax({
	    url: UPDATE_ADDRESS,
	    method: 'post',
	    data:{address_id:$this.address_id, firstname:firstname, lastname:lastname, telephone:telephone,
				country_id:country_id, city_id, city:city, district:district,
				ward:ward, postcode:postcode, street:street},
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    $this.getAddressList($jq('.fhs_checkout_block_address_list'));
		}
		fhs_account.animateLoaderBlock('stop',element);
		$this.closePopupAddress();
	    }
	});
    };
    this.deleteAddress = function(address_id){
	if(is_loading && has_address_list){return;}
	fhs_account.animateLoaderBlock('start',$jq('.fhs_checkout_block_address_list_item'));
	$jq.ajax({
	    url: DELETE_ADDRESS,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {address_id: address_id},
	    success: function (data) {
		if(data['success']){
		    $this.getAddressList($jq('.fhs_checkout_block_address_list'));
		}
		fhs_account.animateLoaderBlock('stop',$jq('.fhs_checkout_block_address_list_item'));
	    }
	});
    };
    this.updateAddressDefault = function(){
	let address_id = '';
	let firstname = '';
	let lastname = '';
	let email = '';
	let telephone = '';
	let country_id = '';
	let city_id = '';
	let city = '';
	let district = '';
	let ward = '';
	let postcode = '';
	let street = '';
	
	if($this.has_address_list){
	    address_id = $jq('.fhs_checkout_block_address_list_item_option:checked').val(); 
	}else{
	    firstname = $jq('#fhs_shipping_firstname').val();
	    lastname = $jq('#fhs_shipping_lastname').val();
	    if(!$this.is_login){
		email = $jq('#fhs_shipping_email').val();
	    }
	    telephone = $jq('#fhs_shipping_telephone').val();
	    country_id = $jq('#fhs_shipping_country').val();
	    city_id = $jq('#fhs_shipping_city_select option:selected').val();
	    city = $jq('#fhs_shipping_city').val();
	    district = $jq('#fhs_shipping_district').val();
	    ward = $jq('#fhs_shipping_ward').val();
	    postcode = $jq('#fhs_shipping_postcode').val();
	    street = $jq('#fhs_shipping_street').val();
	}
	
	$jq.ajax({
	    url: UPDATE_ADDRESS_DEFAULT,
	    method: 'post',
	    data:{address_id:address_id, firstname:firstname, lastname:lastname, email:email, telephone:telephone,
				country_id:country_id, city_id, city:city, district:district,
				ward:ward, postcode:postcode, street:street},
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		}
	    }
	});
    };
    this.checkingEmail = function(email){
	let $input_box = $jq('#fhs_shipping_email').parents('.fhs-input-box');
	let $alert_message = $input_box.children('.fhs-input-alert');
	if($this.email_checked == email){
	    if($this.email_checked_result){
		$input_box.addClass('checked-pass');
	    }else{
		$input_box.addClass('checked-msg');
		$alert_message.html($this.languages['email_exist']);
	    }
	    return;
	}
	$jq.ajax({
	    url: CHECKING_EMAIL,
	    method: 'post',
	    data:{email:email},
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    $input_box.addClass('checked-pass');
		}else{
		    $input_box.addClass('checked-msg');
		    $alert_message.html($this.languages['email_exist']);
		}
		$this.email_checked = email;
		$this.email_checked_result = data['success'];
	    }
	});
    };
    this.checkingTelephone = function(telephone){
	let $input_box = $jq('#fhs_shipping_telephone').parents('.fhs-input-box');
	let $alert_message = $input_box.children('.fhs-input-alert');
	if($this.telephone_checked == telephone){
	    if($this.email_checked_result){
		$input_box.addClass('checked-pass');
	    }else{
		$input_box.addClass('checked-msg');
		$alert_message.html($this.languages['telephone_exist']);
	    }
	    return;
	}
	$jq.ajax({
	    url: CHECKING_TELEPHONE,
	    method: 'post',
	    data:{telephone:telephone},
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    $input_box.addClass('checked-pass');
		}else{
		    $input_box.addClass('checked-msg');
		    $alert_message.html($this.languages['telephone_exist']);
		}
	$this.telephone_checked = telephone;
		$this.telephone_checked_result = data['success'];
	    }
	});
    };
    
    this.getVAT = function(element){
	fhs_account.animateLoaderBlock('start',element);
	$jq.ajax({
	    url: GET_VAT,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    let vat = [];
		    vat['company'] = data['company'];
		    vat['address'] = data['address'];
		    vat['taxcode'] = data['taxcode'];
		    vat['name'] = data['name'];
		    vat['email'] = data['email'];
		    $this.vat_Json = vat;
		}
		$this.showVAT();
		fhs_account.animateLoaderBlock('stop',element);
	    }
	});
    };
    
    this.setShipping = function(element, data){
	fhs_account.animateLoaderBlock('start',element);
	$jq.ajax({
	    url: GET_SHIPPING_METHOD,
	    method: 'post',
            dataType : "json",
	    data: JSON.stringify(data),
            headers: ps.getHeader(),
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    if(data['listShippingMethod']){
			let title = '';
			if(!fhs_account.isEmpty(data['notification'])){
			    if($this.languages['locale'] == 'vi-VN'){
				title = data['notification']['vi'];
			    }else{
				title = data['notification']['en'];
			    }
			    $jq('#fhs_checkout_shippingmethod_title').html(title);
			}else{
			    $jq('#fhs_checkout_shippingmethod_title').fadeOut(0);
			}
			if(!fhs_account.isEmpty(data['listShippingMethod'])){
			    $jq('#fhs_checkout_shippingmethod_msg').fadeOut(0);
			    if(!fhs_account.isEmpty(data['listShippingMethod'])){
				$this.renderShippingMethod(data['listShippingMethod'], data['event_delivery']);
			    }
			    if(!fhs_account.isEmpty(title)){
				$jq('#fhs_checkout_shippingmethod_title').fadeIn(0);
			    }
			    $jq('#fhs_checkout_shippingmethod').fadeIn(0);
			}else{
			    $jq('#fhs_checkout_shippingmethod').html('');
			    $jq('#fhs_checkout_shippingmethod_title').fadeOut(0);
			    $jq('#fhs_checkout_shippingmethod').fadeOut(0);
			    $jq('#fhs_checkout_shippingmethod_msg').fadeIn(0);
			}
		    }else{
			$jq('#fhs_checkout_shippingmethod').html('');
			$jq('#fhs_checkout_shippingmethod_title').fadeOut(0);
			$jq('#fhs_checkout_shippingmethod').fadeOut(0);
			$jq('#fhs_checkout_shippingmethod_msg').fadeIn(0);
		    }
                    if (typeof checkout_outstock_product !== 'undefined') {
//                        checkout_outstock_product.checkCartHasOutStockProduct();
                    }                    
		    $this.getCheckout($jq('#fhs_checkout_products'));
		}else{
		    $jq('#fhs_checkout_shippingmethod').html('');
		    $jq('#fhs_checkout_shippingmethod_title').fadeOut(0);
		    $jq('#fhs_checkout_shippingmethod').fadeOut(0);
		    $jq('#fhs_checkout_shippingmethod_msg').fadeIn(0);
		}
		fhs_account.animateLoaderBlock('stop',element);
	    }
	});
    };
//    this.setCoupon = function(element, coupon_code = '', apply = 1, $btn_apply = null){
//	if($this.is_loading_coupon){return;}
//	$this.is_loading_coupon = true;
//	fhs_account.animateLoaderBlock('start',element);
//	fhs_account.showLoadingAnimation();
//	let data = {sessionId: SESSION_ID,couponCode: coupon_code.trim(), apply: apply};
//	$jq.ajax({
//	    url: SET_COUPON_CODE,
//	    method: 'post',
//            dataType : "json",
//	    data: JSON.stringify(data),
//            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
//	    success: function (data) {
//		if($btn_apply != null){
//		    $jq('.fhs-btn-view-promo-coupon').attr('apply', '1');
//		    $jq('.fhs-btn-view-promo-coupon').children('span').html($this.languages['apply']);
//		    try{
//			let $item = $btn_apply.parents('.fhs-event-promo-list-item');
//			let $btn = $item.find('.fhs-event-promo-list-item-btndata').find(':button');
//			if($btn.length > 0){
//			    if(fhs_account.isEmpty($btn.attr('coupon'))){
//				$btn.attr('apply', '1');
//				$btn.children('span').html($this.languages['apply']);
//				$btn.addClass('fhs-btn-view-promo-detail-coupon');
//			    }
//			}
//		    }catch(ex){}
//		}
//		//let $btn_coupon = $jq('#fhs_checkout_btn_coupon');
//		let $input_box = element.parents('.fhs-input-box');
//		let $alert_message = $input_box.children('.fhs-input-alert');
//		$input_box.removeClass('checked-error-text');
//		$alert_message.text('');
//		if(data['success']){
//		    if(!fhs_account.isEmpty(data['checkout']['couponCode']) || !fhs_account.isEmpty(data['checkout']['freeshipCouponCode'])){
//			$this.displayCoupon(data['checkout']['couponCode'], data['checkout']['couponLabel'], data['checkout']['freeshipCouponCode'], data['checkout']['freeshipCouponLabel']);
//			if(apply == 1){
//			    $this.getCheckout($jq('#fhs_checkout_products'));
//			    if($btn_apply != null){
//				if($btn_apply.length > 0){
//				    $jq('.fhs_checkout_coupon').val('');
//				    $btn_apply.attr('apply', '0');
//				    $btn_apply.children('span').html($this.languages['cancel_apply']);
//				    try{
//					let $item = $btn_apply.parents('.fhs-event-promo-list-item');
//					let $btn = $item.find('.fhs-event-promo-list-item-btndata').find(':button');
//					if($btn.length > 0){
//					    if(fhs_account.isEmpty($btn.attr('coupon'))){
//						$btn.attr('apply', '0');
//						$btn.children('span').html($this.languages['cancel_apply']);
//						$btn.removeClass('fhs-btn-view-promo-detail-coupon');
//					    }
//					}
//				    }catch(ex){}
//				}
//			    }
//			    fhs_account.showAlert($this.languages['code_applied']);
//			}
//		    }else{
//			$this.displayCoupon();
//			if(apply != 1){
//			    $this.getCheckout($jq('#fhs_checkout_products'));
//			    fhs_account.showAlert($this.languages['code_canceled']);
//			}
//		    }
//		}else{
//		    $this.displayCoupon();
//		    if(!fhs_account.isEmpty(data['message'])){
//			$input_box.addClass('checked-error-text');
//			$alert_message.text(data['message']);
//			if($btn_apply != null){
//			    $this.getCheckout($jq('#fhs_checkout_products'));
//			    fhs_account.showAlert(data['message']);
//			}
//		    }
//		}
//		$this.is_loading_coupon = false;
//		fhs_account.animateLoaderBlock('stop',element);
//		fhs_account.hideLoadingAnimation();
//	    },
//	    error: function () {
//		$this.is_loading_coupon = false;
//		fhs_account.animateLoaderBlock('stop',element);
//		fhs_account.hideLoadingAnimation();
//	    }
//	});
//    };
    this.setTryOut = function(element, coupon_code = '', apply = 1){
	fhs_account.animateLoaderBlock('start',element);
	let tryout = 0;
	if($jq('#fhs_checkout_fpoint').prop('checked')){
	    tryout = '1';
	}
	let data = {tryout: tryout};
	$jq.ajax({
	    url: SET_TRYOUT,
	    method: 'post',
            dataType : "json",
            headers: ps.getHeader(),
	    data: JSON.stringify(data),
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    $this.getCheckout($jq('#fhs_checkout_products'));
		}
		fhs_account.animateLoaderBlock('stop',element);
	    }
	});
    };
    this.getCheckout = function(element){
	fhs_account.animateLoaderBlock('start',$jq('#fhs_checkout_paymentmethod'));
	fhs_account.animateLoaderBlock('start',element);
	let shipping_method = $jq('.fhs_checkout_shippingmethod_option:checked').val();
	if(fhs_account.isEmpty(shipping_method)){
	    shipping_method = '';
	}
	let payment_method = $jq('.fhs_checkout_paymentmethod_option:checked').val();
	if(fhs_account.isEmpty(payment_method)){
	    payment_method = '';
	}
	let country_id = $this.getCurentAddress()['countryId'];
	if(is_loading_shipping){return;}
	is_loading_shipping = true;
	let data = {shippingMethod: shipping_method, paymentMethod: payment_method,countryId: country_id, eventCart: true};
	$jq.ajax({
	    url: GET_CHECKOUT,
	    method: 'post',
            dataType : "json",
	    data: JSON.stringify(data),
            headers: ps.getHeader(),
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    $this.renderCheckout(data['checkout'],data['event_cart']);
		    //$this.reloaded_wallet_voucher = false;
		    //$this.getWalletVoucher();
		    is_loading_shipping = false;
		}
		fhs_account.animateLoaderBlock('stop',element);
		fhs_account.animateLoaderBlock('stop',$jq('#fhs_checkout_paymentmethod'));
	    }
	});
    };
    
    this.createOrder_post = function (data, try_time = 0){
        $this.progressSet(0.5,0.25);
	$jq(".youama-ajaxlogin-cover").fadeIn();
	$jq('#popup-default-loading-confirm').hide();
	$jq('#popup-default-loading-context-text').html($this.languages['processing']+"...");
	$jq('#popup-default-loading-logo').hide();
	$jq('#popup-default-loading-icon').show();
	$jq('#popup-default-loading').fadeIn();
	if(is_loading || is_loading_shipping){
	    if(try_time >= 10){ 
		is_loading_create_order = false;
		$jq('.popup-fahasa-default-alert-content .popup-fahasa-default-content-text').text($this.languages['overload']);
		$jq('#popup-default-loading').fadeOut();
		$jq('#popup-fahasa-alert').show();
	    }
	    try_time++;
	    setTimeout(function(){$this.createOrder_post(data,try_time);}, 1000);
	    return;
	}
	if(is_loading_create_order){return;}
	is_loading_create_order = true;
	$jq.ajax({
	    url: CREATE_ORDER,
	    method: 'post',
            dataType : "json",
            headers: ps.getHeader(),
	    data: JSON.stringify(data),
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    $this.time_loaded = 0;
		    $this.checkOrderStatus();
		    return;
		}
		if(!fhs_account.isEmpty(data['url'])){
		    //window.location.href = data['url'];
                    $this.progressFinish(data['url']);
		}else if(!fhs_account.isEmpty(data['message'])){
		    $jq('.popup-fahasa-default-alert-content .popup-fahasa-default-content-text').html(data['message']);
		    $jq('#popup-default-loading').fadeOut(0);
		    $jq('#popup-fahasa-alert').show();
                    $this.progressReset();
		}else{
		    $jq(".youama-ajaxlogin-cover").fadeOut(0);
		    $jq('#popup-default-loading').fadeOut(0);
		}
		is_loading_create_order = false;
	    },
	    error: function () {
                $this.progressFinish();
		is_loading_create_order = false;
		$jq('.popup-fahasa-default-alert-content .popup-fahasa-default-content-text').text($this.languages['overload']);
		$jq('#popup-default-loading').fadeOut();
		$jq('#popup-fahasa-alert').show();
	    }
	});
    };
    this.checkOrderStatus = function () {
	$jq.ajax({
	    url: CHECK_ORDER_STATUS,
	    method: 'post',
            headers: ps.getHeader(),
            dataType : "json",            
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		$this.time_loaded++;
                $this.progressSet($this.time_loaded,0.25);
		console.log("time to try="+$this.time_loaded+", status="+data['success']+", process status="+data['isProcessed']);
		if (data['success']) {
		    if (data['isProcessed']) {
			if (data['orderId']) {
                            $this.progressFinish(data['redirectUrl']);
                            //window.location.href = data['redirectUrl'];
			}else {
                            $this.progressFinish('/checkout/cart');
//			    window.location.href = '/checkout/cart';
			}
			return;
		    }else{
			if($this.time_loaded < QUEUE_TIME_LIMIT_TO_TRY){
			    setTimeout(function(){$this.checkOrderStatus();}, TIME_REQUEST_QUEUE);
			}else{
			    $jq('#popup-default-loading-icon').hide();
			    $jq('#popup-default-loading-logo').show();
			    $jq('#popup-default-loading-context-text').html($this.languages['timeout']);
			    $jq('#popup-default-loading-confirm').show();
                            $this.progressReset();
			}
		    }
		}
	    },
	    error: function(){
		$this.time_loaded++;
                $this.progressSet($this.time_loaded,0.25);
		console.log("time to try="+$this.time_loaded+", loading failured.");
		if($this.time_loaded < QUEUE_TIME_LIMIT_TO_TRY){
		    setTimeout(function(){$this.checkOrderStatus();}, TIME_REQUEST_QUEUE);
		}else{
		    $jq('#popup-default-loading-icon').hide();
		    $jq('#popup-default-loading-logo').show();
		    $jq('#popup-default-loading-context-text').html($this.languages['timeout']);
		    $jq('#popup-default-loading-confirm').show();
                    $this.progressReset();
		}
	    }
	});
    };
    
//    this.getWalletVoucher = function(element){
//	return;
//	if($jq('#popup-loading-event-cart .popup-loading-event-cart-info .popup-loading-event-cart-content .popup-loading-event-cart-content-wallet').length == 0){return;}
//	if($jq('.popup-loading-event-cart-detail').is(":visible")){closeEventCartDetail();}
//	if(!$jq('#popup-loading-event-cart .popup-loading-event-cart-info .popup-loading-event-cart-content .popup-loading-event-cart-content-wallet').is(":visible")){return;}
//	if($jq('#popup-loading-event-cart-content-tab').css('display') == 'none'){return;}
//	if($this.reloaded_wallet_voucher){return;}
//	$this.reloaded_wallet_voucher = true;
//	$jq('#popup-loading-event-cart .popup-loading-event-cart-info .popup-loading-event-cart-content-wallet').html('<div class="popup-loading-event-cart-content-tabs_loading"><div id="default-icon-loading" style="height: 40px; width: 40px; background: url(\''+$this.languages['loading_img']+'\') no-repeat center center transparent; background-size: 40px;"></div></div></div>');
//
//	let data = {is_fhs_voucher: 1};
//	$jq.ajax({
//	    url: GET_WALLET_VOUCHER,
//	    method: 'post',
//            dataType : "json",
//	    data: JSON.stringify(data),
//            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
//	    success: function (data) {
//		if(data['success']){
//		    wallet_vouchers = data['result'];
//		    $this.renderWalletVoucher(data['result']);
//		}else{
//		    $this.reloaded_wallet_voucher = false;
//		    $jq('#popup-loading-event-cart .popup-loading-event-cart-info .popup-loading-event-cart-content .popup-loading-event-cart-content-wallet').html("<div class=\"fhs-event-promo-list-empty\"><img src=\""+ $this.languages['ico_couponemty']+"\"/><div>"+ $this.languages['no_promotion']+"</div></div>");
//		}
//	    },
//	    error: function () {
//		$this.reloaded_wallet_voucher = false;
//		$jq('#popup-loading-event-cart .popup-loading-event-cart-info .popup-loading-event-cart-content .popup-loading-event-cart-content-wallet').html("<div class=\"fhs-event-promo-list-empty\"><img src=\""+ $this.languages['ico_couponemty']+"\"/><div>"+ $this.languages['no_promotion']+"</div></div>");
//	    }
//	});
//    };
    
    //EVENT
    this.eventButtonClick = function(){
	$jq('.fhs-textbox-alert').click(function(){
	    let $alert_icon = $jq(this);
	    let $input_group = $alert_icon.parents('.fhs-input-group');
	    let $input_box = $alert_icon.parents('.fhs-input-box');
	    let $text_box = $input_group.children('.fhs-textbox');
	    let $alert_msg = $input_box.children('.fhs-input-alert');
	    if($input_box.hasClass('checked-error')){
		$alert_msg.empty();
		$text_box.val('');
		$input_box.removeClass('checked-error');
	    }
	    if($input_group.hasClass('checked-error')){
		$alert_msg.empty();
		$text_box.val('');
		$input_group.removeClass('checked-error');
	    }
	    $text_box.focus();
	});
	$jq('.fhs-btn-saveaddress').click(function(){
	    if($this.Validate_address){
		let firstname = $jq('#fhs_address_firstname').val();
		let lastname = $jq('#fhs_address_lastname').val();
		let telephone = $jq('#fhs_address_telephone').val();
		let country_id = $jq('#fhs_address_country').val();
		let city_id = $jq('#fhs_address_city_select option:selected').val();
		let city = $jq('#fhs_address_city').val();
		let district = $jq('#fhs_address_district').val();
		let ward = $jq('#fhs_address_ward').val();
		let postcode = $jq('#fhs_address_postcode').val();
		let street = $jq('#fhs_address_street').val();
		if(!fhs_account.isEmpty(country_id) && !fhs_account.isEmpty(city) && !fhs_account.isEmpty(street) && !fhs_account.isEmpty(district)
		    && !fhs_account.isEmpty(telephone) && !fhs_account.isEmpty(firstname) && !fhs_account.isEmpty(firstname)){
		    $this.updateAddress($jq(this), firstname, lastname, telephone,
					country_id, city_id, city, district,
					ward, postcode, street)
		}else{
		    $jq('.fhs-btn-saveaddress').attr('disabled','disabled');
		}
	    }
	});
	$jq('#fhs_shipping_district_select').change(function(){
	    if($this.is_first_shipping_address){return;}
	    setTimeout(function(){
		if(!fhs_account.isEmpty($jq('#fhs_shipping_district').val())){
		    $this.updateShippingAddress();
		}
		if($this.Validate_shipping_address() && !$this.is_first_shipping_address){
		    $this.updateAddressDefault();
		}
	    },100);
	});
	$jq('#fhs_shipping_wards_select').change(function(){
	    setTimeout(function(){
		if($this.Validate_shipping_address() && !$this.is_first_shipping_address){
		    $this.updateAddressDefault();
		}
	    },100);
	});
	$jq('.fhs-btn-saveaddress-cancel').click(function(){
	    $this.closePopupAddress();
	});
	
	$jq('#fhs_checkout_note_checkbox').click(function(){
	    let $text_box = $jq('#fhs_checkout_note');
	    $text_box.val('');
	    
	    let $checkbox = $jq(this);
	    let $input_box = $checkbox.parents('.fhs-input-box');
	    
	    fhs_account.removeAlert($text_box);
	    
	    if($input_box.hasClass('checked')){
		$input_box.removeClass('checked');
	    }else{
		$input_box.addClass('checked');
		$text_box.focus();
	    }
	});
	
	$jq('#fhs_checkout_vat_checkbox').click(function(){
	    let $name = $jq('#fhs_checkout_customername');
	    let $company_name = $jq('#fhs_checkout_companyname');
	    let $company_address = $jq('#fhs_checkout_companyaddress');
	    let $company_vat = $jq('#fhs_checkout_companyvat');
	    let $email = $jq('#fhs_checkout_email');
	    
	    let $checkbox = $jq(this);
	    let $input_box = $checkbox.parents('.fhs-input-box');
	    fhs_account.removeAlert($company_name);
	    if($input_box.hasClass('checked')){
		$input_box.removeClass('checked');
		$name.val('');
		$company_name.val('');
		$company_address.val('');
		$company_vat.val('');
		$email.val('');
		
		fhs_account.isInputTexted($name);
		fhs_account.isInputTexted($company_name);
		fhs_account.isInputTexted($company_address);
		fhs_account.isInputTexted($company_vat);
		fhs_account.isInputTexted($email);
		$name.focus();
	    }else{
		$input_box.addClass('checked');
		if(!$this.vat_Json && $this.is_login){
		    $this.getVAT($company_name);
		}else{
		    $this.showVAT();
		}
	    }
	});
	
	$jq('#fhs_checkout_fpoint').click(function(){
	    $this.setTryOut($jq(this));
	});
	
	$jq('#fhs_checkout_freeship').click(function(){
	    $this.has_changed_membership = true;
	    $this.updateShippingAddress();
	});
	
	$jq('#fhs_checkout_shippingmethod').change(function(){
	    $jq('#fhs_checkout_block_shippingmethod').removeClass('block_checked_error');
	    if($jq('.fhs_event_delivery_select').is(":visible")){
		$jq('.fhs_event_delivery_select').attr('disabled', 'disabled'); 
	    }
	    
	    if($this.shipping_method_changed != $jq('.fhs_checkout_shippingmethod_option:checked').val() 
		    || $this.has_changed_membership){
		$this.shipping_method_changed = $jq('.fhs_checkout_shippingmethod_option:checked').val();
		$this.getCheckout($jq('#fhs_checkout_products'));
	    }
	    
	    if($jq('.fhs_checkout_shippingmethod_option:checked').hasClass('eventmethod')){
		$event_delivery_select = $jq('.fhs_checkout_shippingmethod_option:checked').parent().find('.fhs_event_delivery_select');
		$event_delivery_select.removeAttr('disabled');
		$this.event_delivery_method_changed = $jq('.fhs_checkout_shippingmethod_option:checked').attr('value_id');
		$this.event_delivery_option_changed = $event_delivery_select.val();
		$jq('.fhs_event_delivery_select').each(function() {
		    if($jq(this).hasClass('require_check')){
			fhs_account.validateTextbox($jq(this).attr('validate_type'), "value", $jq(this));
		    }
		});
	    }else{
		$this.event_delivery_method_changed = '';
		$this.event_delivery_option_changed = '';
		$jq('.fhs_event_delivery_select').each(function() {
		    if($jq(this).hasClass('require_check')){
			fhs_account.validateTextbox($jq(this).attr('validate_type'), "value", $jq(this));
		    }
		});
	    }
	    if($this.is_login){
		if($this.shipping_method_changed == "vietnamshippingnormal_vietnamshippingnormal" && $jq('#fhs_checkout_freeship').val() > 0){
		    $jq('#fhs_checkout_freeship').removeAttr('disabled');
		    $jq('#fhs_checkout_freeship').parent().children('.fhs_input_checkbox').removeClass('fhs_input_checkbox_disabled');
		}else{
		    $jq('#fhs_checkout_freeship').removeAttr('checked');
		    $jq('#fhs_checkout_freeship').parent().children('.fhs_input_checkbox').addClass('fhs_input_checkbox_disabled');
		    $jq('#fhs_checkout_freeship').attr('disabled','disabled');
		}
	    }
	    $this.has_changed_membership = false;
	});
	
	$jq('.fhs_checkout_paymentmethod_option').change(function(){
	    $jq('#fhs_checkout_block_paymentmethod').removeClass('block_checked_error');
	    if($this.payment_method_changed != $jq(this).val()){
		$this.payment_method_changed = $jq(this).val();
		$this.getCheckout($jq('#fhs_checkout_products'));
	    }
	});
	
	$jq('.fhs-btn-orderconfirm').click(function(){
	    $this.onClickPayment();
	});
    };
    
    this.onClickPayment = function(){
        //check checkout stock for display popup to remind customer
        if (typeof checkout_outstock_product !== 'undefined') {
            if (checkout_outstock_product.validateCartHasOutStockProduct()){
                checkout_outstock_product.showPopupCheckoutStockBeforePayment();
                return;
            }
        }
        
        $this.validateCreateOrder();
    }
    this.btn_coupon_click = function(e){
	let $input_box = $jq(e).parents('.fhs-input-box');
	let $counpon_input = $input_box.find('.fhs_checkout_coupon');
	if(fhs_account.validateTextbox('text', $counpon_input.val().trim(), $counpon_input)){
	    let coupon_code = $counpon_input.val();
	    let apply = 1;
//	    if($jq(e).hasClass('applied')){
//		apply = 0;
//	    }
	    $this.setCoupon($counpon_input, coupon_code, apply, $jq(e));
	}
	setTimeout(function(){fhs_account.removeAlert($counpon_input);},5000);
    };
    this.eventInputPress = function(){
	$jq(window).on('resize scroll', function () {
	    let height = Math.round($jq('.fhs-bsidebar-content').height());
	    $jq('body').css('margin-bottom', (height+16)+"px");
	});
	$jq('#fhs_shipping_fullname').keyup(function(e){
	    $this.changeFullName();
	});
	$jq('.require_check').change(function(e){
	    $this.has_changed_shipping_address_for_getmethod = true;
	});
	$jq('.require_check').blur(function(e){
	    if($this.has_changed_shipping_address_for_getmethod){
		fhs_account.validateTextbox($jq(this).attr('validate_type'), $jq(this).val().trim(), $jq(this));
		if($jq(this).attr('id') == 'fhs_shipping_email'){
		    if(!fhs_account.validateData($jq(this).attr('validate_type'), $jq(this).val().trim())){
			let telephone = $jq('#fhs_shipping_telephone');
			if(!telephone.parents('.fhs-input-box').hasClass('checked-warning')){
			    if(!$this.is_login){$this.checkingEmail($jq(this).val().trim());}
			}
		    }
		}
		if($jq(this).attr('id') == 'fhs_shipping_telephone'){
		    if(!fhs_account.validateData($jq(this).attr('validate_type'), $jq(this).val().trim())){
			let email = $jq('#fhs_shipping_email');
			if(!email.parents('.fhs-input-box').hasClass('checked-warning')){
			    if(!$this.is_login){$this.checkingTelephone($jq(this).val().trim());}
			}
		    }
		}
		if($jq(this).attr('id') == 'fhs_shipping_district'){
		    if(!fhs_account.isEmpty($jq('#fhs_shipping_district').val())){
			$this.updateShippingAddress();
		    }
		}
	    }else{
		if(fhs_account.isEmpty($jq(this).val())){
		    fhs_account.validateTextbox($jq(this).attr('validate_type'), $jq(this).val().trim(), $jq(this));
		}
	    }
	    $this.has_changed_shipping_address_for_getmethod = false;
	});
	$jq('.require_group_check').change(function(e){
	    $this.has_changed_group_text = true;
	});
	$jq('.require_group_check').blur(function(e){
	    if($this.has_changed_group_text){
		fhs_account.validateTextboxInGroup($jq(this).attr('validate_type'), $jq(this).val().trim(), $jq(this));
	    }else{
		if(fhs_account.isEmpty($jq(this).val())){
		    fhs_account.validateTextboxInGroup($jq(this).attr('validate_type'), $jq(this).val().trim(), $jq(this));
		}
	    }
	    $this.has_changed_group_text = false;
	});
	$jq('.check_shipping_address').change(function(e){
	    $this.has_changed_shipping_address = true;
	});
	$jq('.check_shipping_address').blur(function(e){
	    if($this.has_changed_shipping_address){
		if($this.Validate_shipping_address()){
		    $this.updateAddressDefault();
		}
	    }
	    $this.has_changed_shipping_address = false;
	});
	
	$jq('#fhs_shipping_country_select').change(function(){
	    let $input_group = $jq(this).parents('.fhs-input-group');
	    $input_group.children('.fhs-textbox').val($jq(this).val());
	    $this.country_change($jq(this).val());
	});
	$jq('#fhs_shipping_city_select').change(function(){
	    let $input_group = $jq(this).parents('.fhs-input-group');
	    if($jq('#fhs_shipping_city_select option:selected').val()){
		$input_group.children('.fhs-textbox').val($jq('#fhs_shipping_city_select option:selected').text());
	    }else{
		$input_group.children('.fhs-textbox').val('');
	    }
	    $this.city_change($jq(this).val());
	});
	$jq('#fhs_shipping_district_select').change(function(){
	    let $input_group = $jq(this).parents('.fhs-input-group');
	    if($jq('#fhs_shipping_district_select option:selected').val()){
		$input_group.children('.fhs-textbox').val($jq('#fhs_shipping_district_select option:selected').text());
	    }else{
		$input_group.children('.fhs-textbox').val('');
	    }
	    $this.district_change($jq(this).val());
	});
	$jq('#fhs_shipping_wards_select').change(function(){
	    let $input_group = $jq(this).parents('.fhs-input-group');
	    if($jq('#fhs_shipping_wards_select option:selected').val()){
		$input_group.children('.fhs-textbox').val($jq('#fhs_shipping_wards_select option:selected').text());
	    }else{
		$input_group.children('.fhs-textbox').val('');
	    }
	    $this.ward_change();
	});
	
	$jq('#fhs_address_fullname').keyup(function(e){
	    $this.changeFullName('fhs_address');
	});
	$jq('.require_check_address').blur(function(e){
	    fhs_account.validateTextbox($jq(this).attr('validate_type'), $jq(this).val().trim(), $jq(this));
	});
	$jq('.require_check_address').keyup(function(e){
	    $this.Validate_address();
	});
        
        $jq('#fhs_address_telephone').change(function(){
            fhs_account.validateTextbox($jq(this).attr('validate_type'), $jq(this).val().trim(), $jq(this));
	});
	$jq('#fhs_address_country_select').change(function(){
	    let $input_group = $jq(this).parents('.fhs-input-group');
	    $input_group.children('.fhs-textbox').val($jq(this).val());
	    $this.country_change($jq(this).val(), 'fhs_address');
	});
	$jq('#fhs_address_city_select').change(function(){
	    let $input_group = $jq(this).parents('.fhs-input-group');
	    if($jq('#fhs_address_city_select option:selected').val()){
		$input_group.children('.fhs-textbox').val($jq('#fhs_address_city_select option:selected').text());
	    }else{
		$input_group.children('.fhs-textbox').val('');
	    }
	    $this.city_change($jq(this).val(), 'fhs_address');
	});
	$jq('#fhs_address_district_select').change(function(){
	    let $input_group = $jq(this).parents('.fhs-input-group');
	    if($jq('#fhs_address_district_select option:selected').val()){
		$input_group.children('.fhs-textbox').val($jq('#fhs_address_district_select option:selected').text());
	    }else{
		$input_group.children('.fhs-textbox').val('');
	    }
	    $this.district_change($jq(this).val(), 'fhs_address');
	});
	$jq('#fhs_address_wards_select').change(function(){
	    let $input_group = $jq(this).parents('.fhs-input-group');
	    if($jq('#fhs_address_wards_select option:selected').val()){
		$input_group.children('.fhs-textbox').val($jq('#fhs_address_wards_select option:selected').text());
	    }else{
		$input_group.children('.fhs-textbox').val('');
	    }
	    $this.ward_change('fhs_address');
	});
	$jq("input:text").keydown(function(e){
	    let key = '';
	    let keyCode = 0;
	    if (e.type === 'paste') {
		key = e.clipboardData.getData('text/plain');
	    }else{
		keyCode = (e.keyCode ? e.keyCode : e.which);
		key = String.fromCharCode(keyCode);
	    }
	    
	    let validate_type = $jq(this).attr('validate_type');
	    if(!e.ctrlKey && !e.altKey){
		if(validate_type == 'shipping_telephone'){
		    if(!((keyCode >= 37) && (keyCode <= 40))  
			&& (keyCode != 17) 
			&& !((keyCode >= 8) && (keyCode <= 9)) 
			&& !((keyCode >= 46) && (keyCode <= 47))
			&& (keyCode != 49) && (keyCode != 116)){
		    if(!((keyCode >= 48 && keyCode <= 57) || (keyCode >= 96 && keyCode <= 105))){
			    if(!$jq.isNumeric(key)) {
			      e.returnValue = false;
			      if(e.preventDefault) e.preventDefault();
			    }  
			}
		    }
		    if(e.shiftKey && $jq.isNumeric(key)) {
			e.returnValue = false;
			if(e.preventDefault) e.preventDefault();
		    }
		    $txt = $jq(this);
		    setTimeout(function(){
			let new_str = fhs_account.keepOnlyNumber($txt.val());
			$txt.val(new_str);
		    });
		}
		if(keyCode == 0){
		    e.returnValue = false;
		    if(e.preventDefault) e.preventDefault();
		}
	    }
	});
	$jq("input:text").bind('paste', null, function(e) {
	    let validate_type = $jq(this).attr('validate_type');
	    if(validate_type == 'shipping_telephone'){
		if(!$jq.isNumeric(e.originalEvent.clipboardData.getData('text'))){
		    e.returnValue = false;
		    if(e.preventDefault) e.preventDefault();
		}
	    }
	    $txt = $jq(this);
	    setTimeout(function(){
		let new_str = fhs_account.removeEmojiIcon($txt.val()).trim();
		$txt.val(new_str);
	    });
	});
	$jq("input:text").focusin(function (){
	    let $input_box = $jq(this).parents('.fhs-input-box');
	    let $input_group = $jq(this).parents('.fhs-input-group');
	    
	    if($input_group.length){
		$input_group.addClass('fousing');
		$input_group.removeClass('texting');
	    }else if($input_box.length){
		$input_box.addClass('fousing');
		$input_box.removeClass('texting');
	    }
	});
	$jq("input:text").focusout(function (){
	    let $input_box = $jq(this).parents('.fhs-input-box');
	    let $input_group = $jq(this).parents('.fhs-input-group');
	    let text_value = $jq(this).val();
	    
	    if($input_group.length){
		$input_group.removeClass('fousing');
		if(!fhs_account.isEmpty(text_value)){
		    $input_group.addClass('texting');
		}
	    }else if($input_box.length){
		$input_box.removeClass('fousing');
		if(!fhs_account.isEmpty(text_value)){
		    $input_box.addClass('texting');
		}
	    }
	});
	
	$jq('.fhs_checkout_coupon').keyup(function(e){
	    $jq('.fhs_checkout_coupon').val($jq(this).val());
	});
	
    };
    this.EventCartEvent = function(){
	$jq('.event_cart_content_option_item').change(function(){
	    $jq('#event_cart_data').val($jq(this).val());
	});
    };
    this.EventDeliveryEvent = function(){
	$jq('.fhs_event_delivery_select').change(function(){
	    $this.event_delivery_option_changed = $jq(this).val();
	    fhs_account.validateTextbox($jq(this).attr('validate_type'), $jq(this).val(), $jq(this));
	});
    };
    
    this.country_change = function(country_id = 0, type = 'fhs_shipping'){
	let options = "<option value='' selected>"+$this.languages['choose_city']+"</option>";
	if(country_id != 0){
	    let items = $this.city_Json[country_id];
	    if(items){
		Object.keys(items).forEach(function(key){
		    var item = items[key];
		    let option = "<option value='"+key+"'>"+item['name']+"</option>";
		    options += option;
		}); 
		$this.vnHiddenTextbox('city',type);
	    }else{
		$this.vnHiddenTextbox('country',type);
	    }
	}
	$jq('#'+type+'_city_select').empty().html(options);
	let placeholder = "<span class='select2-selection__placeholder'>"+$this.languages['choose_city']+"</span>";
	$jq('#select2-'+type+'_city_select-container').html(placeholder);
	$jq('#'+type+'_city_select').trigger('change');
    };
    this.city_change = function(city_id = 0, type = 'fhs_shipping'){
	let options = "<option value='' selected>"+$this.languages['choose_district']+"</option>";
	if(city_id != 0){
	    let items = $this.district_Json[city_id];
	    if(items){
		Object.keys(items).forEach(function(key){
		    var item = items[key];
		    let option = "<option value='"+key+"'>"+item['name']+"</option>";
		    options += option;
		});
	    }
	}
	$jq('#'+type+'_district_select').empty().html(options);
	let placeholder = "<span class='select2-selection__placeholder'>"+$this.languages['choose_district']+"</span>";
	$jq('#select2-'+type+'_district_select-container').html(placeholder);

	$jq('#'+type+'_district_select').trigger('change');
    };
    this.district_change = function(district_id = 0, type = 'fhs_shipping'){
	let options = "<option value='' selected>"+$this.languages['choose_wards']+"</option>";
	if(district_id != 0){
	    let items = $this.ward_Json[district_id];
	    if(items){
		Object.keys(items).forEach(function(key){
		    var item = items[key];
		    let option = "<option value='"+key+"'>"+item['name']+"</option>";
		    options += option;
		});
	    }
	}
	$jq('#'+type+'_wards_select').empty().html(options);
	let placeholder = "<span class='select2-selection__placeholder'>"+$this.languages['choose_wards']+"</span>";
	$jq('#select2-'+type+'_wards_select-container').html(placeholder);
	$jq('#'+type+'_wards_select').trigger('change');
    };
    this.ward_change = function(type = 'fhs_shipping'){
	$this.disableSelectbox(false,false,false,type);
    };
    this.Validate_other_info_require = function(){
	let result = true;
	$jq('.check_other_info').each(function() {
	    if($jq(this).is(":visible")){
		if($jq(this).hasClass('require_check')){
		    if(!fhs_account.validateTextbox($jq(this).attr('validate_type'), $jq(this).val().trim(), $jq(this))){
			if(result){
			    result = false;
			}
		    }
		}
	    }
	});
	return result;
    };
    this.Validate_shipping_address_require = function(){
	let result = true;
	$jq('.check_shipping_address').each(function() {
	    if($jq(this).is(":visible")){
		if($jq(this).hasClass('require_check')){
		    if(!fhs_account.validateTextbox($jq(this).attr('validate_type'), $jq(this).val().trim(), $jq(this))){
			if(result){
			    result = false;
			}
		    }
		}
	    }else{
		let id = $jq(this).attr('id');
		if(id == 'fhs_shipping_city' || id == 'fhs_shipping_district'){
		    if(!fhs_account.validateTextbox($jq(this).attr('validate_type'), $jq(this).val().trim(), $jq(this))){
			if(result){
			    result = false;
			}
		    }
		}
		if(id == 'fhs_shipping_ward' && $jq('#fhs_shipping_wards_select').is(":visible")){
		    if(!fhs_account.validateTextbox($jq(this).attr('validate_type'), $jq(this).val().trim(), $jq(this))){
			if(result){
			    result = false;
			}
		    }
		}
	    }
	});
	return result;
    };
    this.Validate_shipping_address = function(){
	let result = true;
	$jq('.check_shipping_address').each(function() {
	    if($jq(this).is(":visible")){
		if($jq(this).hasClass('require_check')){
		    if(fhs_account.validateData($jq(this).attr('validate_type'), $jq(this).val().trim())){
			if(result){
			    result = false;
			}
		    }
		}
	    }else{
		let id = $jq(this).attr('id');
		if(id == 'fhs_shipping_city' || id == 'fhs_shipping_district'){
		    if(fhs_account.validateData($jq(this).attr('validate_type'), $jq(this).val().trim())){
			if(result){
			    result = false;
			}
		    }
		}
		if(id == 'fhs_shipping_ward' && $jq('#fhs_shipping_wards_select').is(":visible")){
		    if(fhs_account.validateData($jq(this).attr('validate_type'), $jq(this).val().trim())){
			if(result){
			    result = false;
			}
		    }
		}
	    }
	});
	return result;
    };
    this.Validate_address = function(){
	let result = true;
	$jq('.require_check_address').each(function() {
	    if($jq(this).is(":visible")){
		if(fhs_account.validateData($jq(this).attr('validate_type'), $jq(this).val().trim())){
		    if(result){
			result = false;
		    }
		}
	    }else{
		let id = $jq(this).attr('id');
		if(id == 'fhs_address_city' || id == 'fhs_address_district'){
		    if(fhs_account.validateData($jq(this).attr('validate_type'), $jq(this).val().trim())){
			if(result){
			    result = false;
			}
		    }
		}
		if(id == 'fhs_address_ward' && $jq('#fhs_address_wards_select').is(":visible")){
		    if(fhs_account.validateData($jq(this).attr('validate_type'), $jq(this).val().trim())){
			if(result){
			    result = false;
			}
		    }
		}
	    }
	});
	if(result){
	    $jq('.fhs-btn-saveaddress').removeAttr('disabled');
	}else{
	    $jq('.fhs-btn-saveaddress').attr('disabled','disabled');
	}
	return result;
    };
    this.disableSelectbox = function(is_disable_city, is_disable_district, is_disable_ward, type = 'fhs_shipping'){
	//province
	if(is_disable_city){
	    $jq('#'+type+'_city_select').attr('disabled', 'disabled');
	}else{
	    $jq('#'+type+'_city_select').removeAttr('disabled', 'disabled');
	}
	//district
	if($jq('#'+type+'_district_select').children('option').length <= 1){
	    is_disable_district = true;
	}
	if(is_disable_district){
	    $jq('#'+type+'_district_select').attr('disabled', 'disabled');
	}else{
	    $jq('#'+type+'_district_select').removeAttr('disabled', 'disabled');
	}
	
	//ward
	if($jq('#'+type+'_wards_select').children('option').length <= 1){
	    is_disable_ward = true;
	}
	if(is_disable_ward){
	    $jq('#'+type+'_wards_select').attr('disabled', 'disabled');
	}else{
	    $jq('#'+type+'_wards_select').removeAttr('disabled', 'disabled');
	}
	
	fhs_account.removeAlert($jq('#'+type+'_city_select'));
	fhs_account.removeAlert($jq('#'+type+'_district_select'));
	fhs_account.removeAlert($jq('#'+type+'_wards_select'));
	//save btn
	if(type == 'fhs_address'){
	    $this.Validate_address();
	}
    };
    this.vnHiddenTextbox = function(step = 'country' ,type = 'fhs_shipping'){
	let $country_select = $jq('#'+type+'_country_select');
	let $input_group_city = $jq($jq('#'+type+'_city_select')).parents('.fhs-input-group');
	let $input_group_district = $jq($jq('#'+type+'_district_select')).parents('.fhs-input-group');
	let $input_group_wards = $jq($jq('#'+type+'_wards_select')).parents('.fhs-input-group');
	
	let $city_select = $input_group_city.children('.select2-container');
	let $district_select = $input_group_district.children('.select2-container');
	let $wards_select = $input_group_wards.children('.select2-container');
	let $input_box_wards = $jq($jq('#'+type+'_wards_select')).parents('.fhs-input-box');
	let $input_box_post = $jq($jq('#'+type+'_postcode')).parents('.fhs-input-box');
	
	let $city_textbox = $jq('#'+type+'_city');
	let $district_textbox = $jq('#'+type+'_district');
	let $wards_textbox = $jq('#'+type+'_wards');
	let $postcode_textbox = $jq('#'+type+'_postcode');
	    
	if($country_select.val() == 'VN'){
	    $city_select.fadeIn(0);
	    $district_select.fadeIn(0);
	    $input_box_wards.fadeIn(0);
	    $city_textbox.fadeOut(0);
	    $district_textbox.fadeOut(0);
	    $wards_textbox.fadeOut(0);
	    $input_box_post.fadeOut(0);
	    
	    $postcode_textbox.val('');
	}else{
	    if(step == 'country'){
		$city_select.fadeOut(0);
		$city_textbox.fadeIn(0);
	    }else{
		$city_select.fadeIn(0);
		$city_textbox.fadeOut(0);
	    }
	    $district_select.fadeOut(0);
	    $district_textbox.fadeIn(0);
	    $input_box_wards.fadeOut(0);
	    $wards_textbox.fadeIn(0);
	    $input_box_post.fadeIn(0);

	    $postcode_textbox.val('');
	}
    };
    
    this.onclickEventCartConfirmButton = function(){
	location.href= event_cart_page_detail;
    };
    this.openEventCartNotiPopup = function(){
	$jq('#fahasa_dialog_wrapper-cover').fadeIn();
	$jq('.fahasa_dialog_wrapper').fadeIn();
    };
    //RENDER BY DATA
    this.renderAddressList = function(address_id_default, address_list){
	if(!fhs_account.isEmpty(address_list)){
	    let address_list_str = '';
	    Object.keys(address_list).forEach(function(key){
		let option_default = '';
		if(key == address_id_default){
		    option_default = 'checked';
		}
		address_list_str += $this.displayAddress(option_default, address_list[key]);
	    });
	    $jq('#fhs_checkout_address').html(address_list_str);
	}
    };
    this.renderCheckout = function(checkout_data, event_cart_data){
	if(!fhs_account.isEmpty(checkout_data)){
	    $this.displayCoupon(checkout_data['couponCode'], checkout_data['couponLabel'], checkout_data['freeshipCouponCode'], checkout_data['freeshipCouponLabel']);
	    $this.displayProduct(checkout_data['products'], event_cart_data);
	    $this.displayTotal(checkout_data['totals']);
	    //$this.this.displayEventCart(event_cart_data['events'][0]);
	    let address = $this.getCurentAddress();
	    let grand_total = 5000;
	    Object.keys(checkout_data['totals']).forEach(function(key){
		let option_default = '';
		if(checkout_data['totals'][key]['code'] == 'grand_total'){
		    grand_total = checkout_data['totals'][key]['price'];
		}
	    });
	    $this.displayPayment(address['countryId'], grand_total);
	}
	fhs_promotion.displayPopupPromotion(event_cart_data);
	fhs_promotion.displayPromotionCart(event_cart_data);
	//$this.displayCouponSearch(event_cart_data);
	//$this.displayPromotionCart(event_cart_data['affect_carts']);
    };
    
    this.renderShippingMethod = function(shipping_methods, event_deliveries){
	let method_list_str = '';
	let has_option_default = false;
	let has_delivery_option_default = false;
	
	if(!fhs_account.isEmpty(event_deliveries) && !fhs_account.isEmpty($this.event_delivery_method_changed)){
	    Object.keys(event_deliveries).forEach(function(key){
		if($this.shipping_method_changed == event_deliveries[key]['shippingMethod'] && event_deliveries[key]['enable'] && $this.event_delivery_method_changed == event_deliveries[key]['id']){
		    if(!has_option_default){
			has_delivery_option_default = true;
		    }
		}
		
	    });
	}
	if(!has_delivery_option_default){
	    Object.keys(shipping_methods).forEach(function(key){
		if($this.shipping_method_changed == shipping_methods[key]['shippingMethod']){
		    if(!has_option_default){
			has_option_default = true;
		    }
		}
	    });
	}
	    
	Object.keys(shipping_methods).forEach(function(key){
	    let option_default = '';
	    if(!has_delivery_option_default){
		if(has_option_default){
		    if($this.shipping_method_changed == shipping_methods[key]['shippingMethod']){
			option_default = 'checked';
			$this.event_delivery_method_changed = '';
			$this.event_delivery_option_changed = '';
		    }
		}else{
		    if(key == 0){
			option_default = 'checked';
			has_option_default = true;
			$this.event_delivery_method_changed = '';
			$this.event_delivery_option_changed = '';
		    }
		}
	    }
	    method_list_str += $this.displayShippingMethod(option_default, shipping_methods[key]);
	});
	
	if(!fhs_account.isEmpty(event_deliveries)){
	    Object.keys(event_deliveries).forEach(function(key){
		let error_content = '';
		if(!fhs_account.isEmpty(event_deliveries[key]['error'])){
		    Object.keys(event_deliveries[key]['error']).forEach(function(error_key){
			let error = event_deliveries[key]['error'][error_key];
			error_content += "<div style='color:#dc3545;padding: 4px 0;'>*"+error+"</div>";
		    }); 
		}
		if(!fhs_account.isEmpty(event_deliveries[key]['products_not_support'])){
		    Object.keys(event_deliveries[key]['products_not_support']).forEach(function(product_key){
			let product = event_deliveries[key]['products_not_support'][product_key];
			let price_str = '';
			if(product['final_price'] < product['price']){
			    price_str = "<span>"
					    +Helper.formatCurrency(product['price'])
					+"</span>";
			}
			error_content += "<div class='fhs_popup-default-info-detail-content_product'>"
					    +"<div>"
						+"<img src='"+product['image']+"'/>"
					    +"</div>"
					    +"<div>"
						+"<div>"
						    +product['name']
						+"</div>"
						+"<div>"
						    +"<span>"
							+Helper.formatCurrency(product['final_price'])
						    +"</span>"
						    +price_str
						+"</div>"
						+"<div>"
						    +"Số lượng "+product['qty']
						+"</div>"
					    +"</div>"
					+"</div>";
		    }); 
		}
		if(!fhs_account.isEmpty(error_content)){
		    event_deliveries[key]['error_content'] = error_content;
		}
		let option_default = '';
		if(has_delivery_option_default){
		    if($this.shipping_method_changed == event_deliveries[key]['shippingMethod'] && event_deliveries[key]['enable'] && $this.event_delivery_method_changed == event_deliveries[key]['id']){
			option_default = 'checked';
		    }
		}
		method_list_str += $this.displayEventDelivery(option_default, event_deliveries[key]);
	    });
	    $this.event_delivery_data = event_deliveries;
	}
	
	$jq('#fhs_checkout_shippingmethod').html(method_list_str);
	$jq('#fhs_checkout_shippingmethod').trigger('change');
	
	if(!fhs_account.isEmpty(event_deliveries)){
	    $jq(".fhs_event_delivery_select").select2({
		minimumResultsForSearch: -1,
		placeholder: $this.languages['choose_delivery_time'],
		allowClear: false
	    });
	    $this.EventDeliveryEvent();
	}
    };
    this.displayShippingMethod = function(option_default, item){
	if(item['shippingMethod'].startsWith("matrixrate", 10)){
	    return "<li class=\"fhs_checkout_block_radio_list_item fhs_radio_top\">"
			+"<div>"
			    +"<label class=\"fhs-radio-big\">"
				+"<div style=\"font-weight: 600;\">"+item['methodTitle']+": "+Helper.formatCurrency(item['price'])+"</div>"
				+"<input type=\"radio\" id=\"fhs_checkout_shippingmethod_"+item['shippingMethod']+"\" name=\"fhs_checkout_shippingmethod_option\" class=\"fhs_checkout_shippingmethod_option\" value=\""+item['shippingMethod']+"\" "+option_default+">"
				+"<span class=\"radiomark-big\"></span>"
			    +"</label>"
			+"</div>"
		    +"</li>";
	}else{
	    let delivery_date = '';
	    if(!fhs_account.isEmpty(item['methodTitle'])){
		delivery_date = "<div>"+fhs_account.formatDateTime(item['methodTitle'])+"</div>";
	    }
	    return "<li class=\"fhs_checkout_block_radio_list_item fhs_radio_top\">"
			+"<div>"
			    +"<label class=\"fhs-radio-big\">"
				+"<div style=\"font-weight: 600;\">"+item['label']+": "+Helper.formatCurrency(item['price'])+"</div>"
				+delivery_date
				+"<input type=\"radio\" id=\"fhs_checkout_shippingmethod_"+item['shippingMethod']+"\" name=\"fhs_checkout_shippingmethod_option\" class=\"fhs_checkout_shippingmethod_option\" value=\""+item['shippingMethod']+"\" "+option_default+">"
				+"<span class=\"radiomark-big\"></span>"
			    +"</label>"
			+"</div>"
		    +"</li>";
	}
	
    };
    this.displayEventDelivery = function(option_default, item){
	let disabled_str = '';
	let icon_str = '';
	let label_style = 'font-weight: 600;';
//	let page_detail = '';
	let rule_content = '';
	let error_content = '';
	let options = "<option value='' >"+$this.languages['choose_delivery_time']+"</option>";
	let select_box = '';
	if(!item['enable']){
	    disabled_str = 'disabled';
	    if(!fhs_account.isEmpty(item['icon_grey_path'])){
		icon_str = "<span style='padding-right: 8px;'><img style='max-height: 1.4em;' src='"+item['icon_grey_path']+"'/></span>";
		label_style += "padding-bottom: 0.25em;";
	    }
	}else{
	    if(!fhs_account.isEmpty(item['icon_path'])){
		icon_str = "<span style='padding-right: 8px;'><img style='max-height: 1.4em;' src='"+item['icon_path']+"'/></span>";
		label_style += "padding-bottom: 0.25em;";
	    }
	}
	if(!fhs_account.isEmpty(item['error_content'])){
	    error_content = "<div>*Giỏ hàng chưa thỏa diều kiện. <a class='fhs_blue_link' onclick=\"fhs_account.showPopup('Chi tiết điều kiện chưa thỏa',fhs_onestepcheckout.event_delivery_data["+item['id']+"]['error_content']);\">Xem chi tiết</a>";
	}
	if(item['periods']){
	    Object.keys(item['periods']).forEach(function(key){
		    let period = item['periods'][key];
		    let option_disable_str = '';
		    let option_select_str = '';
		    if(!period['enable']){
			option_disable_str = "disabled";
		    }else{
			if($this.event_delivery_option_changed == key){
			    option_select_str = "selected"
			}
		    }
		    let option = "<option value='"+key+"' "+option_select_str+" "+option_disable_str+">"+period['name']+"</option>";
		    options += option;
		}); 
	}
//	if(!fhs_account.isEmpty(item['page_detail'])){
//	    if(!item['page_detail'].startsWith("/")){
//		item['page_detail'] = "/"+item['page_detail'];
//	    }
//	    page_detail = "<span style='padding-left: 8px;'><a class='fhs_blue_link' href='"+item['page_detail']+"'>"+$this.languages['event_detail']+"</a></span>";
//	}
	if(!fhs_account.isEmpty(item['rule_content'])){
	    rule_content = "<span style='padding-left: 8px; font-weight: 400;'><a class='fhs_blue_link' onclick=\"fhs_account.showPopup('"+$this.languages['terms_conditions']+"',fhs_onestepcheckout.event_delivery_data["+item['id']+"]['rule_content']);\">"+$this.languages['event_detail']+"</a></span>";
	}
	select_box = "<div class=\"fhs-input-box fhs-input-group-horizontal-eventdelivery\">"
	    +"<label>Ngày nhận hàng</label>"
	    +"<div class=\"fhs-input-item\">"
		+"<div class=\"fhs-input-group\">"
		    +"<select id=\"fhs_event_delivery_select_"+item['id']+"\" validate_type='text' class=\"fhs-input-select require_check fhs_event_delivery_select\" "+disabled_str+">"
		    +options
		    +"</select>"
		+"</div>"
	    +"</div>"
	+"</div>";

	return "<li class=\"fhs_checkout_block_radio_list_item fhs_radio_top "+disabled_str+"\">"
		    +"<div>"
			+"<label class=\"fhs-radio-big\">"
			    +"<div style='"+label_style+"'>"+ icon_str + item['label']+": "+Helper.formatCurrency(item['price'])+rule_content+"</div>"
			    +"<div>"+item['methodTitle']+"</div>"
			    +"<input type=\"radio\" "+disabled_str+" id=\"fhs_checkout_shippingmethod_"+item['shippingMethod']+"_"+item['id']+"\" name=\"fhs_checkout_shippingmethod_option\" class=\"fhs_checkout_shippingmethod_option eventmethod \" value_id='"+item['id']+"' value=\""+item['shippingMethod']+"\" "+option_default+">"
			    +"<span class=\"radiomark-big\"></span>"
			    +select_box
			    +error_content
			+"</label>"
		    +"</div>"
		+"</li>";
	
    };
    
//    this.renderWalletVoucher = function(coupons){
//	if($jq('#popup-loading-event-cart .popup-loading-event-cart-info .popup-loading-event-cart-content .popup-loading-event-cart-content-wallet').length == 0){
//	    return;
//	}
//	if($jq('#popup-loading-event-cart-content-tab').css('display') == 'none'){
//	    return;
//	}
//	if(fhs_account.isEmpty(coupons)){
//	    $jq('#popup-loading-event-cart .popup-loading-event-cart-info .popup-loading-event-cart-content .popup-loading-event-cart-content-wallet').html("<div class=\"fhs-event-promo-list-empty\"><img src=\""+ $this.languages['ico_couponemty']+"\"/><div>"+ $this.languages['no_promotion']+"</div></div>");
//	    return;
//	}
//	
//	let result = '';
//	let matched_list = '';
//	let matched_title = '<div class="fhs-event-promo-list-title">'+$this.languages['matched_voucher_title']+'</div>';
//	let matched = '';
//	let matched_more = '';
//	
//	let not_matched_list = '';
//	let not_matched_title = '<div class="fhs-event-promo-list-title">'+$this.languages['notmatched_voucher_title']+'</div>';
//	let not_matched = '';
//	let not_matched_more = '';
//	let matched_viewmore_btn = '<div class="fhs-event-promo-list-viewmore">'
//		    +'<a class="collapse" data-toggle="collapse" href="#collapse_walletvoucher_list_matched"><span class="text-viewmore">'+$this.languages['viewmore']+'</span><span class="text-viewless">'+$this.languages['viewless']+'</span><img src="'+$this.languages['ico_down_orange']+'"/></a>'
//		+'</div>';
//	let line = '<div class="fhs-event-promo-list-line"></div>';
//	let not_matched_viewmore_btn = '<div class="fhs-event-promo-list-viewmore">'
//		    +'<a class="collapse" data-toggle="collapse" href="#collapse_walletvoucher_list_not_matched"><span class="text-viewmore">'+$this.languages['viewmore']+'</span><span class="text-viewless">'+$this.languages['viewless']+'</span><img src="'+$this.languages['ico_down_orange']+'"/></a>'
//		+'</div>';
//	$this.event_cart_limit;
//	
//	let coupons_match = {};
//	let coupons_not_match = {};
//	
//	Object.keys(coupons).forEach(function(key){
//	    if(coupons[key]['matched']){
//		coupons_match[key] = coupons[key];
//	    }else{
//		coupons_not_match[key] = coupons[key];
//	    }
//	});
//	
//	if(!fhs_account.isEmpty(coupons_match)){
//	    let count = 0;
//	    Object.keys(coupons_match).forEach(function(key){
//		if(count < $this.event_cart_limit){
//		    matched += $this.displayWalletVoucher(key, 'matched', coupons_match[key]);
//		}else{
//		    matched_more += $this.displayWalletVoucher(key, 'matched', coupons_match[key]);
//		}
//		count++;
//	    });
//	    matched_list = '<div class="fhs-event-promo-list">';
//		matched_list += matched_title;
//		matched_list += matched;
//		if(!fhs_account.isEmpty(matched_more)){
//		    matched_list += '<div id="collapse_walletvoucher_list_matched" class="panel-collapse collapse in">';
//		    matched_list += matched_more;
//		    matched_list += '</div>';
//		    matched_list += matched_viewmore_btn;
//		}
//	    matched_list += '</div>';
//	}
//	
//	if(!fhs_account.isEmpty(coupons_not_match)){
//	    let count = 0;
//	    Object.keys(coupons_not_match).forEach(function(key){
//		if(count < $this.event_cart_limit){
//		    not_matched += $this.displayWalletVoucher(key, 'not_matched',coupons_not_match[key]);
//		}else{
//		    not_matched_more += $this.displayWalletVoucher(key, 'not_matched',coupons_not_match[key]);
//		}
//		count++;
//	    });
//	    not_matched_list = '<div class="fhs-event-promo-list">';
//		not_matched_list += not_matched_title;
//		not_matched_list += not_matched;
//		if(!fhs_account.isEmpty(not_matched_more)){
//		    not_matched_list += '<div id="collapse_walletvoucher_list_not_matched" class="panel-collapse collapse in">';
//		    not_matched_list += not_matched_more;
//		    not_matched_list += '</div>';
//		    not_matched_list += not_matched_viewmore_btn;
//		}
//	    not_matched_list += '</div>';
//	}
//	
//	if(!fhs_account.isEmpty(matched_list)){
//	    result = matched_list;
//	}
//	if(!fhs_account.isEmpty(matched_list) && !fhs_account.isEmpty(not_matched_list)){
//	    result += line;
//	}
//	if(!fhs_account.isEmpty(not_matched_list)){
//	    result += not_matched_list;
//	}
//	
//	if(fhs_account.isEmpty(matched_list) && fhs_account.isEmpty(not_matched_list)){
//	    result = icon_empty;
//	}
//	$jq('#popup-loading-event-cart .popup-loading-event-cart-info .popup-loading-event-cart-content .popup-loading-event-cart-content-wallet').html(result);
//    };
//    this.displayWalletVoucher = function(index, _type, coupon){
//	let class_color = "fhs-event-promo-list-item-blue";
//	let img_coupon = $this.languages['ico_couponblue'];
//	let description = '';
//	let coupon_code = '';
//	let expire_date = '';
//	let error_total = '';
//	let errors = '';
//	let rule_content = '';
//	let class_content_detail = '';
//	let btn_apply = '';
//	let btn_detail = '';
//	let almost_over = '';
//	let progress_bar = '';
//	let progress_bar_class = '';
//	let total = '';
//	let class_button_more = '';
//	
//	if(coupon['almost_run_out']){
////	    if(_type == 'matched'){
////		almost_over = "<span class=\"fhs-event-promo-almost-over-red\">"+$this.languages['selling_out']+"</span>";
////	    }else{
//		almost_over = "<span class=\"fhs-event-promo-almost-over-red\">"+$this.languages['selling_out']+"</span>";
////	    }
//	}
//	
//	if(!fhs_account.isEmpty(coupon['min_total']) && !fhs_account.isEmpty(coupon['max_total'])){
//	    total = "<div class=\"fhs-event-promo-item-minmax\"><span>"+coupon['min_total']+"</span><span>"+coupon['max_total']+"</span></div>";
//	}
//	if(coupon['matched']){
//	    class_color = "fhs-event-promo-list-item-green";
//	    img_coupon = $this.languages['ico_coupongreen'];
//	    progress_bar_class = "class=\"progress-success\"";
//	}else{
//	    if(!fhs_account.isEmpty(coupon['sub_total'])){
//		progress_bar = "<div class=\"fhs-event-promo-item-progress-bar\">"
//			    +"<div class=\"fhs-event-promo-item-progress\"><hr "+progress_bar_class+" style=\"width:"+ coupon['reach_percent'] + "%;\"/><div>"+coupon['sub_total']+"</div><img class='progress-cheat' src='"+$this.languages['progress_cheat_img']+"'/></div>"
//			    +total
//			+"</div>";
//	    }
//	}
//	
//	if(coupon['description']){
//	    description = "<div>"+coupon['description']+"</div>";
//	}
//	if(coupon['coupon_code']){
//	    coupon_code = "<div class='fhs_voucher_code'>"+$this.languages['Voucher_code']+" - "+coupon['coupon_code']+"</div>";
//	}
//	if(coupon['expire_date']){
//	    expire_date = "<div class='fhs_voucher_expiry'>HSD: "+coupon['expire_date']+"</div>";
//	}
//	if(!fhs_account.isEmpty(coupon['error'])){
//	    if(coupon['error'].length > 1){
//		error_total = "<div class=\"fhs-event-promo-error\" onclick='showEventCartErrorBlock(this)'>* "+coupon['error'].length+" điều kiện không thỏa "+"<img src='"+$this.languages['ico_viewmore']+"' /></div>";
//		errors = "<div class='fhs-event-promo-error-block'>";
//		Object.keys(coupon['error']).forEach(function(key){
//		    errors += "<div class=\"fhs-event-promo-error\">* "+coupon['error'][key]['message']+"</div>";
//		});
//		errors += "</div>";
//	    }else{
//		Object.keys(coupon['error']).forEach(function(key){
//		    errors += "<div class=\"fhs-event-promo-error\">* "+coupon['error'][key]['message']+"</div>";
//		});
//	    }
//	    
//	}
//	if(coupon['applied']){
//	    btn_apply = "<button type='button' onclick='applyCoupon(this);' title='"+$this.languages['cancel_apply']+"' coupon='"+coupon['coupon_code']+"' apply='0' class='fhs-btn-view-promo-coupon' ><span>"+$this.languages['cancel_apply']+"</span></button>";
//	    btn_detail = "<button type=\"button\" title=\""+$this.languages['cancel_apply']+"\" onclick=\"applyCoupon(this);\" coupon=\""+coupon['coupon_code']+"\" apply=\"0\" class=\"btn-close-popup-event\"><span>"+$this.languages['cancel_apply']+"</span></button>";
//	}else{
//	    if(coupon['matched']){
//		btn_apply = "<button type=\"button\" onclick=\"applyCoupon(this);\" title=\""+$this.languages['apply']+"\" coupon=\""+coupon['coupon_code']+"\" apply=\"1\" class=\"fhs-btn-view-promo-coupon\"><span>"+$this.languages['apply']+"</span></button>";
//		btn_detail = "<button type=\"button\" title=\""+$this.languages['apply']+"\" onclick=\"applyCoupon(this);\" coupon=\""+coupon['coupon_code']+"\" apply=\"1\" class=\"btn-close-popup-event fhs-btn-view-promo-detail-coupon\"><span>"+$this.languages['apply']+"</span></button>";
//	    }else{
//		class_button_more = "class='no-more-button'";
////		if(coupon['page_detail']){
////		    btn_apply = "<a href=\""+coupon['page_detail']+"\"><button type=\"button\" title=\""+$this.languages['buy_more']+"\" class=\"fhs-btn-view-promo\"><span>"+$this.languages['buy_more']+"</span></button></a>";
////		    btn_detail = "<a href=\""+coupon['page_detail']+"\"><button type=\"button\" title=\""+$this.languages['buy_more']+"\" class=\"btn-close-popup-event fhs-btn-view-promo-detail-gift\"><span>"+$this.languages['buy_more']+"</span></button></a>";
////		}else{
////		    class_button_more = "class='no-more-button'";
////		}
//	    }
//	}
//	if(coupon['rule_content']){
//	    class_content_detail = 'class="fhs-event-promo-list-item-content" onclick="showVoucherDetail(this, '+index+');"';
//	    rule_content = "<div class=\"fhs-event-promo-list-item-detail\" onclick=\"showVoucherDetail(this, "+index+")\">"+$this.languages['detail']
//		    +"<div class=\"fhs-event-promo-list-item-btndata\">"+btn_detail+"</div>"
//		    +"</div>";
//	}
//	return "<div class=\"fhs-event-promo-list-item "+class_color+"\">"
//		    +"<div>"
//			+"<img src=\""+img_coupon+"\"/>"
//		    +"</div>"
//		    +"<div "+class_button_more+">"
//			+"<div>"
//			    +"<div "+class_content_detail+">"
//				+"<div>"+almost_over + coupon['name']+"</div>"
//				+description
//				+coupon_code
//				+expire_date
//				+error_total
//				+errors
//			    +"</div>"
//			+"<div>"
//			+rule_content
//			+"<div class=\"fhs-event-promo-list-item-button\">"
//			    +btn_apply
//			+"</div>"
//			+"</div>"
//			+"</div>"
//			+progress_bar
//		    +"</div>"
//		+"</div>";
//    };
    
    //DISPLAY
    this.changeFullName = function(type = 'fhs_shipping'){
	let $fullname = $jq('#'+type+'_fullname');
	let fullname = $fullname.val().trim();
	let firstname = '';
	let lastname = '';
	if(fhs_account.isEmpty(fhs_account.validateData($fullname.attr('validate_type'), fullname))){
	    fullname = fullname.split(" ");
	    Object.keys(fullname).forEach(function(key){
		if((fullname.length - 1) != key){
		    if(lastname == ''){
			lastname = fullname[key];
		    }else{
			lastname += " "+fullname[key];
		    }
		}else{
		    firstname = fullname[key];
		}
	    });
	}
	$jq('#'+type+'_firstname').val(firstname)
	$jq('#'+type+'_lastname').val(lastname);
    };
    this.showVAT = function(){
	let $name = $jq('#fhs_checkout_customername');
	let $company_name = $jq('#fhs_checkout_companyname');
	let $company_address = $jq('#fhs_checkout_companyaddress');
	let $company_vat = $jq('#fhs_checkout_companyvat');
	let $email = $jq('#fhs_checkout_email');
	if($this.vat_Json && $this.is_login){
	    $company_name.val(this.vat_Json['company']);
	    $company_address.val(this.vat_Json['address']);
	    $company_vat.val(this.vat_Json['taxcode']);
	    $name.val(this.vat_Json['name']);
	    $email.val(this.vat_Json['email']);
	}else{
	    $company_name.val('');
	    $company_address.val('');
	    $company_vat.val('');
	    $name.val('');
	    $email.val('');
	}
	    
	fhs_account.isInputTexted($company_name);
	fhs_account.isInputTexted($company_address);
	fhs_account.isInputTexted($company_vat);
	fhs_account.isInputTexted($name);
	fhs_account.isInputTexted($email);
	$name.focus();
    };
    this.showPopupAddress = function(address_id = '',type = ''){
	$this.address_id = address_id;
	$jq('.require_check_address').each(function() {
	    fhs_account.removeAlert($jq(this));
	});
	if(type != ''){
	    $jq('#fhs_checkout_block_address_popup').addClass('change');
	}else{
	    $jq('#fhs_checkout_block_address_popup').removeClass('change');
	}
	if(address_id == ''){
	    $jq('#fhs_address_fullname').val('');
	    $jq('#fhs_address_firstname').val('');
	    $jq('#fhs_address_lastname').val('');
	    $jq('#fhs_address_telephone').val('');
	    $jq('#fhs_address_postcode').val('');
	    $jq('#fhs_address_street').val('');
	    $jq('#fhs_address_country_select').val('VN').trigger('change');
	    $this.country_change('VN', 'fhs_address');
	}else{
	    let address = $this.address_list[address_id];
	    if(address){
		$jq('#fhs_address_fullname').val(address['fullname']);
		$jq('#fhs_address_firstname').val(address['firstname']);
		$jq('#fhs_address_lastname').val(address['lastname']);
		$jq('#fhs_address_telephone').val(address['telephone']).trigger('change');
		$jq('#fhs_address_street').val(address['street'][0]);
		$jq('#fhs_address_country_select').val(address['country_id']).trigger('change');
		$jq('#fhs_address_city_select').val(address['region_id']).trigger('change');
		if(!$jq('#fhs_address_city_select').val()){
		    $jq('#fhs_address_city').val(address['region']);
		}
		let district_id = $this.getDistrictId(address['country_id'], address['region_id'], address['city']);
		if(district_id){
		    $jq('#fhs_address_district_select').val(district_id).trigger('change');
		    let ward_id = $this.getWardId(address['country_id'], address['region_id'], district_id, address['ward']);
		    if(ward_id){
			$jq('#fhs_address_wards_select').val(ward_id).trigger('change');
		    }
		}else{
		    $jq('#fhs_address_district').val(address['city']);
		}
		$jq('#fhs_address_postcode').val(address['postcode']);
	    }else{
		$jq('#fhs_address_fullname').val('');
		$jq('#fhs_address_firstname').val('');
		$jq('#fhs_address_lastname').val('');
		$jq('#fhs_address_telephone').val('');
		$jq('#fhs_address_postcode').val('');
		$jq('#fhs_address_street').val('');
		$jq('#fhs_address_country_select').val('VN').trigger('change');
	    }
	}
	$jq('#fhs_checkout_block_address_popup').fadeIn();
	$jq('.youama-ajaxlogin-cover').fadeIn();
	$this.Validate_address();
    };
    this.closePopupAddress = function(){
	$jq('#fhs_checkout_block_address_popup').fadeOut();
	$jq('.youama-ajaxlogin-cover').fadeOut();
    };
    
    this.displayPayment = function(country_id = 'VN', price = 5000){
	let $banktransfer = $jq('#fhs_checkout_paymentmethod_banktransfer');
	let $cashondelivery = $jq('#fhs_checkout_paymentmethod_cashondelivery');
	let $zalopayatm = $jq('#fhs_checkout_paymentmethod_zalopayatm');
	let $zalopaycc = $jq('#fhs_checkout_paymentmethod_zalopaycc');
	let $zalopayapp = $jq('#fhs_checkout_paymentmethod_zalopayapp');
	let $momopay = $jq('#fhs_checkout_paymentmethod_momopay');
	let $vnpay = $jq('#fhs_checkout_paymentmethod_vnpay');
        let $airpay = $jq('#fhs_checkout_paymentmethod_airpay');
        let $mocapay = $jq('#fhs_checkout_paymentmethod_mocapay');
	
	if(country_id != 'VN' && price > 0){
	    $cashondelivery.parents('.fhs_checkout_block_radio_list_item').fadeOut(0);
	}else{
	    let $cashondelivery = $jq('#fhs_checkout_paymentmethod_cashondelivery');
	    $cashondelivery.parents('.fhs_checkout_block_radio_list_item').fadeIn(0);
	}
	if(price <= 0){
	    if($banktransfer.length != 0){
		$banktransfer.parents('.fhs_checkout_block_radio_list_item').fadeOut(0);
	    }
	    if($zalopayatm.length != 0){
		$zalopayatm.parents('.fhs_checkout_block_radio_list_item').fadeOut(0);
	    }
	    if($zalopaycc.length != 0){
		$zalopaycc.parents('.fhs_checkout_block_radio_list_item').fadeOut(0);
	    }
	    if($zalopayapp.length != 0){
		$zalopayapp.parents('.fhs_checkout_block_radio_list_item').fadeOut(0);
	    }
	}else{
	    if($banktransfer.length != 0){
		$banktransfer.parents('.fhs_checkout_block_radio_list_item').fadeIn(0);
	    }
	    if($zalopayatm.length != 0){
		$zalopayatm.parents('.fhs_checkout_block_radio_list_item').fadeIn(0);
	    }
	    if($zalopaycc.length != 0){
		$zalopaycc.parents('.fhs_checkout_block_radio_list_item').fadeIn(0);
	    }
	    if($zalopayapp.length != 0){
		$zalopayapp.parents('.fhs_checkout_block_radio_list_item').fadeIn(0);
	    }
	}
	if(price < 1000){
	    if($momopay.length != 0){
		$momopay.parents('.fhs_checkout_block_radio_list_item').fadeOut(0);
	    }
	}else{
	    if($momopay.length != 0){
		$momopay.parents('.fhs_checkout_block_radio_list_item').fadeIn(0);
	    }
	}
	if(price < 10000){
	    if($vnpay.length != 0){
		$vnpay.parents('.fhs_checkout_block_radio_list_item').fadeOut(0);
	    }
	}else{
	    if($vnpay.length != 0){
		$vnpay.parents('.fhs_checkout_block_radio_list_item').fadeIn(0);
	    }
	}
        
        if(price < 1){
	    if($airpay.length != 0){
		$airpay.parents('.fhs_checkout_block_radio_list_item').fadeOut(0);
	    }
	}else{
	    if($airpay.length != 0){
		$airpay.parents('.fhs_checkout_block_radio_list_item').fadeIn(0);
	    }
	}
        
        if(price < 1000){
	    if($mocapay.length != 0){
		$mocapay.parents('.fhs_checkout_block_radio_list_item').fadeOut(0);
	    }
	}else{
	    if($mocapay.length != 0){
		$mocapay.parents('.fhs_checkout_block_radio_list_item').fadeIn(0);
	    }
	}
        
	setTimeout(function(){$this.validatePayment();},100);
    };
    this.validatePayment = function(){
	$jq('#fhs_checkout_block_paymentmethod').removeClass('block_checked_error');
	let default_option = 'cashondelivery';
	let has_default_option = false;
	let has_option = false;
	$jq('.fhs_checkout_paymentmethod_option').each(function() {
	    if($jq(this).is(":visible")){
		if($jq(this).val() == default_option){
		    has_default_option = true;
		}
		if($jq(this).val() == $this.payment_method_changed){
		    has_option = true;
		}
	    }else{
		$jq(this).removeAttr('checked');
	    }
	});
	
	let is_first = true;
	$jq('.fhs_checkout_paymentmethod_option').each(function() {
	    if($jq(this).is(":visible")){
		if(has_option){
		    if($jq(this).val() == $this.payment_method_changed){
			$jq(this).attr('checked','checked').trigger('change');
		    }
		}else if(has_default_option){
		    if($jq(this).val() == default_option){
			$jq(this).attr('checked','checked').trigger('change');
		    }
		}else{
		    if(is_first){
			$jq(this).attr('checked','checked').trigger('change');
		    }
		    is_first = false;
		}
	    }
	});
    };
    
    this.displayAddress = function(option_default, item){
	return "<li class='fhs_checkout_block_address_list_item'>"
		    +"<div>"
			+"<label class='fhs-radio-big' style='margin-top: 2px;'>" + fhs_account.encodeHTML(item['fullname']) + "&nbsp;&nbsp;|&nbsp;&nbsp;"+ fhs_account.encodeHTML(item['address']) + "&nbsp;&nbsp;|&nbsp;&nbsp;" + fhs_account.encodeHTML(item['telephone'])
			    +"<input type='radio' id='fhs_checkout_block_address_list_item_" + item['value'] + "' name='fhs_checkout_block_address_list_item_option' class='fhs_checkout_block_address_list_item_option' value='" + item['value'] + "' onchange=\"fhs_onestepcheckout.updateShippingAddress();\" "+option_default+">"
			    +"<span class='radiomark-big'></span>"
			+"</label>"
		    +"</div>"
		    +"<div>"
			+"<span onclick=\"fhs_onestepcheckout.showPopupAddress(" + item['value'] + ", 'change');\">"
			    +$this.languages['edit']
			+"</span>"
			+"<span onclick='fhs_onestepcheckout.deleteAddress(" + item['value'] + ");'>"
			    +$this.languages['delete']
			+"</span>"
		    +"</div>"
		+"</li>";
    };
    this.displayCoupon = function(coupon = '', label = '', freeship_coupon = '', freeship_label = ''){
	let $btn_coupon = $jq('#fhs_checkout_btn_coupon');
	let $input_box = $btn_coupon.parents('.fhs-input-box');
	let $coupon = $jq('#fhs_checkout_coupon');
	let $label_mobile = $jq('.fhs_checkout_block_coupon_mobile .fhs_checkout_block_content');
	if($label_mobile.length > 0){
	    $label_mobile.fadeOut(0);
	}
	$jq('.fhs_label_coupon_label').html('');
	if(!fhs_account.isEmpty(coupon)){
	    $this.coupon = coupon;
	}else{
	    $this.coupon = '';
	}
	if((!fhs_account.isEmpty(coupon) && !fhs_account.isEmpty(label)) 
	    || (!fhs_account.isEmpty(freeship_coupon) && !fhs_account.isEmpty(freeship_label))){
//	    $coupon.attr('disabled','disabled');
//	    $btn_coupon.addClass('applied');
//	    $btn_coupon.text($this.languages['cancel']);
	    
	    let label_show = '';
	    let need_whitespace= false;
	    if(!fhs_account.isEmpty(label) && !fhs_account.isEmpty(coupon)){
		need_whitespace= true;
		label_show = '<div class="fhs_label_coupon_label_orange"><div>'+label+'</div><div onclick="fhs_promotion.applyCoupon(this);" coupon="'+coupon+'" apply="0"><img src="'+$this.languages['ico_delete_orange']+'"/></div></div>';
	    }
	    if(!fhs_account.isEmpty(freeship_label) && !fhs_account.isEmpty(freeship_coupon)){
		if(need_whitespace){
		    label_show += '<div class="white_space_4"></div>';
		}
		label_show += '<div class="fhs_label_coupon_label_green"><div>'+freeship_label+'</div><div onclick="fhs_promotion.applyCoupon(this);" coupon="'+freeship_coupon+'" apply="0"><img src="'+$this.languages['ico_delete_green']+'"/></div></div>';
	    }
	    if(label_show){
		$jq('.fhs_label_coupon_label').html(label_show);
		if($label_mobile.length > 0){
		    $label_mobile.fadeIn(0);
		}
	    }
	}else{
//	    $coupon.removeAttr('disabled');
//	    $btn_coupon.removeClass('applied');
//	    $btn_coupon.text($this.languages['apply']);
	}
    };
    this.displayProduct = function(products_data, event_cart_data){
	var product_str = "";
	Object.keys(products_data).forEach(function (key) {
	    let soon_release = '';
	    let event_cart = '';
	    if(!fhs_account.isEmpty(products_data[key]['soon_release'])){
		soon_release = "<div class='notice'>"+products_data[key]['soon_release']+"</div>";
	    }
	    if(!fhs_account.isEmpty(event_cart_data['affect_items'])){
		Object.keys(event_cart_data['affect_items']).forEach(function (eckey) {
		    if(event_cart_data['affect_items'][eckey]['product_id'] == products_data[key]['productId']){
			if(event_cart_data['affect_items'][eckey]['reach_percent'] >= 100){
			    event_cart = "<div class='fhs-info-promo-icon complete-color'>"
					+"<div><img src='"+$this.languages['ico_check']+"'></div>"
					+"<div>"+event_cart_data['affect_items'][eckey]['promo_message']+"</div>"
					+"</div>";
			}else{
			    event_cart = "<div class='fhs-info-promo-icon process-color'>"
					+"<div><img src='"+$this.languages['ico_promo_sp']+"'></div>"
					+"<div>"+event_cart_data[eckey]['promo_message']+"</div>"
					+"</div>";
			}
		    }
		});
	    }
            let stringOriginalPrice = "";
            if(products_data[key]['original_price'] && products_data[key]['original_price'] != 0 && products_data[key]['price'] != 0 && products_data[key]['price'] != products_data[key]['original_price'] ){
                stringOriginalPrice = "<div class='fhs_checkout_products_item_original_price'>"
                                +Helper.formatCurrency(products_data[key]['original_price'])
                            +"</div>";
            }
	    product_str += "<div class='fhs_checkout_products_item'>"
		    +"<div class='fhs_checkout_products_item_img'>"
			+"<img src='"+products_data[key]['image']+"'/>"
		    +"</div>"
		    +"<div class='fhs_checkout_products_item_detail'>"
			+"<div class='fhs_checkout_products_item_name'>"
			    +"<div>"+products_data[key]['name']+"</div>"
			    +soon_release
			    +event_cart
			+"</div>"
			+"<div class='fhs_checkout_products_item_price'>"
			    +"<div>"
                                +Helper.formatCurrency(products_data[key]['price'])
                            +"</div>"
                            +stringOriginalPrice
			+"</div>"
			+"<div class='fhs_checkout_products_item_qty'>"
			    +"<span>"+$this.languages['quantity']+": </span>"
			    +products_data[key]['quantity']
			+"</div>"
			+"<div class='fhs_checkout_products_item_total'>"
			    +Helper.formatCurrency(Math.floor(products_data[key]['price'] * products_data[key]['quantity']))
			+"</div>"
		    +"</div>"
		+"</div>";
	});
	$jq('#fhs_checkout_products').html(product_str);
    };
    this.displayTotal = function(data_total){
	let total_html = '';
	let grandtotal_html = '';
	let is_freeship_html = '';
	
	Object.keys(data_total).forEach(function (key) {
	    if(data_total[key]['code'] == 'grand_total'){
	       grandtotal_html = "<div>"+data_total[key]['title']+"</div>"
		   +"<div>"+Helper.formatCurrency(data_total[key]['price']) +"</div>";
	    }else if(data_total[key]['code'] == 'is_freeship'){
		if(data_total[key]['price'] == 1){
		    is_freeship_html = "<div class='fhs_checkout_total_is_freeship'><div>"+data_total[key]['title']+"</div><div>"+$this.languages['yes']+"</div></div>";
		}
	    }else{
		total_html += "<div class='fhs_checkout_total_"+data_total[key]['code']+"'>"
			+"<div>"+data_total[key]['title']+"</div>"
			+"<div>"+Helper.formatCurrency(data_total[key]['price']) +"</div>"
			+"</div>";
	    }
	});
	
	$jq('.fhs_checkout_total_grand_total').html(grandtotal_html);
	$jq('.fhs_checkout_total').html(total_html+is_freeship_html+"<div class='fhs_checkout_total_grand_total'>"+grandtotal_html+"</div>");
    };
    this.displayEventCart = function(data){
	if(!fhs_account.isEmpty(data)){
	    try{
		setTimeout(function(){$this.displayEventCartNotiPopup(data['error']);}, 100);

		$this.event_cart_action_type = data['action_type'];
		let form_ui = data['form_ui'];
		if(form_ui){
		    let header_title = "<span class='event_cart_header_title'>"+form_ui['header']+"</span>"
		    if(form_ui['header_icon']){
			header_title += "<img src='"+form_ui['header_icon']+"' />"
		    }
		    if(form_ui['header_background']){
			$jq('.event_cart_header').css("background-color", form_ui['header_background'])
		    }
		    if(form_ui['page_detail']){
			event_cart_page_detail = form_ui['page_detail'];
		    }
		    $jq('.event_cart_header').html(header_title);

		    if(form_ui['content']){
			let form_ui_content = form_ui['content'];
			Object.keys(form_ui_content).forEach(function(key){
			    let type = form_ui_content[key]['type'];
			    let title = form_ui_content[key]['data'];
			    if(type == "shipping"){
				$jq('.event_cart_resutl_title').text(title);
			    }
			    if(type == "text"){
				$jq('.event_cart_footer').html(title);
				$jq('.event_cart_footer').fadeIn();
			    }
			});
		    }
		    if(form_ui['showCancelBtn']){
			$jq('.event_cart_cancel').fadeIn();
			$jq('.event_cart_cancel_btn').val(form_ui['cancelBtnTitle']);
		    }
		}
		let content = ""

		//show ranks
		let ranks = data['rank'];

		//check default value
		$this.EventCartcheckDefaultValue(ranks);

		//render ranks option
		Object.keys(ranks).forEach(function(key){
		    content += $this.displayEventCartRankHTML(ranks[key]);
		});

		if($this.event_cart_has_option_active){
		    $jq('.event_cart_resutl_content').text($this.getCurentAddress()['address']);
		    if(fhs_account.isEmpty($jq('.event_cart_resutl_title').val())){
			$jq('.event_cart_resutl').fadeOut();
		    }else{
			$jq('.event_cart_resutl').fadeIn();
		    }
		}else{
		    $jq('.event_cart_resutl').fadeOut();
		}

		is_first = false;
		$jq('.event_cart_content').html(content);
		$this.EventCartEvent();
		$jq('#fhs_checkout_block_event_cart').fadeIn();
	    }catch(ex){$this.clearEventCart(); console.log(ex.message);}
	}
    };
    this.displayEventCartRankHTML = function(rank){
	let result = "";
	try{
	    let options = rank['options'];
	    result = "<div class='event_cart_content_title'>"+rank['title']+"</div>"
	    //set visable option
	    Object.keys(options).forEach(function(key){
		let option_visable = "";
		let option_check = "";
		if(options[key]['active']){
		    if(event_cart_is_first){
			if(options[key]['default']){
			    option_check = "checked=\'checked\'";
			}
		    }else{
			if(options[key]['option_id'] == $jq('#event_cart_data').val()){
			    option_check = "checked=\'checked\'";
			}
		    }
		}else{
		    if(options[key]['option_id'] == 0){
			if(!$this.event_cart_has_option_active){
			    option_check = "checked=\'checked\'";
			}
		    }else{
			option_visable = "disabled";
		    }

		}
		result += "<div class='event_cart_content_option'>"
			+"<label class='fhs-radio-big' style='margin-top: 2px;'>" + options[key]['option_name']				
			    +"<input type='radio' class='event_cart_content_option_item' name='event-cart-option' value='"+options[key]['option_id']+"' "+option_check+" "+option_visable+"/>"
			    +"<span class='radiomark-big'></span>"
			+"</label>"
			+div_clear
		    +"</div>";
	    });
	}catch(ex){}
	return result;
    };
    this.displayEventCartNotiPopup = function(data){
	if(!fhs_account.isEmpty(data)){
	    
	}
    };
    this.EventCartcheckDefaultValue = function(ranks){
	//check value default and have option active
	let is_march_value = false;
	$this.event_cart_has_option_active = false;
	    
	Object.keys(ranks).forEach(function(key){
	    let rank = ranks[key];
	    let options = rank['options'];
	    Object.keys(options).forEach(function(key){
		if(options[key]['active']){
		    if(!event_cart_is_first){
			if(options[key]['option_id'] == $event_cart_data.val()){
			    is_march_value = true;
			}
		    }
		    if(options[key]['option_id'] != 0){
			$this.event_cart_has_option_active = true;
		    }
		}
	    });
	    //set default value
	    if((!event_cart_is_first && !is_march_value)||(!$this.event_cart_has_option_active)){
		$jq('#event_cart_data').val(0);
	    }
	});
	    
	    
    }
//    this.displayCouponSearch = function(coupons){
//	if($jq('#popup-loading-event-cart .popup-loading-event-cart-info .popup-loading-event-cart-content .popup-loading-event-cart-content-promotion').length == 0){
//	    return;
//	}
//	let icon_empty = "<div class=\"fhs-event-promo-list-empty\"><img src=\""+ $this.languages['ico_couponemty']+"\"/><div>"+ $this.languages['no_promotion']+"</div></div>";
//	if(fhs_account.isEmpty(coupons)){
//	    $jq('#popup-loading-event-cart .popup-loading-event-cart-info .popup-loading-event-cart-content .popup-loading-event-cart-content-promotion').html(icon_empty);
//	    return;
//	}
//	event_cart_data = coupons;
//	let result = '';
//	let matched_list = '';
//	let matched_title = '<div class="fhs-event-promo-list-title">'+$this.languages['matched_title']+'</div>';
//	let matched = '';
//	let matched_more = '';
//	
//	let not_matched_list = '';
//	let not_matched_title = '<div class="fhs-event-promo-list-title">'+$this.languages['notmatched_title']+'</div>';
//	let not_matched = '';
//	let not_matched_more = '';
//	let matched_viewmore_btn = '<div class="fhs-event-promo-list-viewmore">'
//		    +'<a class="collapse" data-toggle="collapse" href="#collapse_promo_list_matched"><span class="text-viewmore">'+$this.languages['viewmore']+'</span><span class="text-viewless">'+$this.languages['viewless']+'</span><img src="'+$this.languages['ico_down_orange']+'"/></a>'
//		+'</div>';
//	let line = '<div class="fhs-event-promo-list-line"></div>';
//	let not_matched_viewmore_btn = '<div class="fhs-event-promo-list-viewmore">'
//		    +'<a class="collapse" data-toggle="collapse" href="#collapse_promo_list_notmatched"><span class="text-viewmore">'+$this.languages['viewmore']+'</span><span class="text-viewless">'+$this.languages['viewless']+'</span><img src="'+$this.languages['ico_down_orange']+'"/></a>'
//		+'</div>';
//	$this.event_cart_limit;
//	
//	
//	if(!fhs_account.isEmpty(coupons['matched'])){
//	    let count = 0;
//	    Object.keys(coupons['matched']).forEach(function(key){
//		if(count < $this.event_cart_limit){
//		    matched += $this.renderCouponSearch(key, 'matched', coupons['matched'][key]);
//		}else{
//		    matched_more += $this.renderCouponSearch(key, 'matched', coupons['matched'][key]);
//		}
//		count++;
//	    });
//	    matched_list = '<div class="fhs-event-promo-list">';
//		matched_list += matched_title;
//		matched_list += matched;
//		if(!fhs_account.isEmpty(matched_more)){
//		    matched_list += '<div id="collapse_promo_list_matched" class="panel-collapse collapse in">';
//		    matched_list += matched_more;
//		    matched_list += '</div>';
//		    matched_list += matched_viewmore_btn
//		}
//	    matched_list += '</div>';
//	}
//	
//	if(!fhs_account.isEmpty(coupons['not_matched'])){
//	    let count = 0;
//	    Object.keys(coupons['not_matched']).forEach(function(key){
//		if(count < $this.event_cart_limit){
//		    not_matched += $this.renderCouponSearch(key, 'not_matched',coupons['not_matched'][key]);
//		}else{
//		    not_matched_more += $this.renderCouponSearch(key, 'not_matched',coupons['not_matched'][key]);
//		}
//		count++;
//	    });
//	    not_matched_list = '<div class="fhs-event-promo-list">';
//		not_matched_list += not_matched_title;
//		not_matched_list += not_matched;
//		if(!fhs_account.isEmpty(not_matched_more)){
//		    not_matched_list += '<div id="collapse_promo_list_notmatched" class="panel-collapse collapse in">';
//		    not_matched_list += not_matched_more;
//		    not_matched_list += '</div>';
//		    not_matched_list += not_matched_viewmore_btn
//		}
//	    not_matched_list += '</div>';
//	}
//	
//	if(!fhs_account.isEmpty(matched_list)){
//	    result = matched_list;
//	}
//	if(!fhs_account.isEmpty(matched_list) && !fhs_account.isEmpty(not_matched_list)){
//	    result += line;
//	}
//	if(!fhs_account.isEmpty(not_matched_list)){
//	    result += not_matched_list;
//	}
//	
//	if(fhs_account.isEmpty(matched_list) && fhs_account.isEmpty(not_matched_list)){
//	    result = icon_empty;
//	}
//	$jq('#popup-loading-event-cart .popup-loading-event-cart-info .popup-loading-event-cart-content .popup-loading-event-cart-content-promotion').html(result);
//    };
//    this.renderCouponSearch = function(index, _type, coupon){
//	let class_color = "fhs-event-promo-list-item-blue";
//	let img_coupon = $this.languages['ico_couponblue'];
//	let title_2 = '';
//	let title_3 = '';
//	let errors = '';
//	let rule_content = '';
//	let class_content_detail = '';
//	let btn_apply = '';
//	let btn_detail = '';
//	let progress_bar = '';
//	let progress_bar_class = '';
//	let total = '';
//	let almost_over = '';
//	let class_button_more = '';
//	
//	if(coupon['almost_run_out']){
////	    if(_type == 'matched'){
////		almost_over = "<span class=\"fhs-event-promo-almost-over-red\">"+$this.languages['selling_out']+"</span>";
////	    }else{
//		almost_over = "<span class=\"fhs-event-promo-almost-over-red\">"+$this.languages['selling_out']+"</span>";
////	    }
//	}
//	
//	if(!fhs_account.isEmpty(coupon['min_total']) && !fhs_account.isEmpty(coupon['max_total'])){
//	    total = "<div class=\"fhs-event-promo-item-minmax\"><span>"+coupon['min_total']+"</span><span>"+coupon['max_total']+"</span></div>";
//	}
//	if(coupon['matched']){
//	    class_color = "fhs-event-promo-list-item-green";
//	    img_coupon = $this.languages['ico_coupongreen'];
//	    progress_bar_class = "class=\"progress-success\"";
//	}else{
//	    if(!fhs_account.isEmpty(coupon['sub_total'])){
//		progress_bar = "<div class=\"fhs-event-promo-item-progress-bar\">"
//				+"<div class=\"fhs-event-promo-item-progress\"><hr "+progress_bar_class+" style=\"width:"+ coupon['reach_percent'] + "%;\"/><div>"+coupon['sub_total']+"</div><img class='progress-cheat' src='"+$this.languages['progress_cheat_img']+"'/></div>"
//				+total
//			    +"</div>";
//	    }
//	}
//	
//	if(coupon['title_2']){
//	    title_2 = "<div>"+coupon['title_2']+"</div>";
//	}
//	if(coupon['title_3']){
//	    title_3 = "<div>"+coupon['title_3']+"</div>";
//	}
//	if(!fhs_account.isEmpty(coupon['error'])){
//	    Object.keys(coupon['error']).forEach(function(key){
//		errors += "<div class=\"fhs-event-promo-error\">* "+coupon['error'][key]['message']+"</div>";
//	    });
//	}
//	if(coupon['applied']){
//	    btn_apply = "<button type='button' onclick='applyCoupon(this);' title='"+$this.languages['cancel_apply']+"' coupon='"+coupon['coupon_code']+"' apply='0' class='fhs-btn-view-promo-coupon' ><span>"+$this.languages['cancel_apply']+"</span></button>"
//	    btn_detail = "<button type=\"button\" title=\""+$this.languages['cancel_apply']+"\" onclick=\"applyCoupon(this);\" coupon=\""+coupon['coupon_code']+"\" apply=\"0\" class=\"btn-close-popup-event\"><span>"+$this.languages['cancel_apply']+"</span></button>";
//	}else{
//	    if(coupon['matched']){
//		btn_apply = "<button type=\"button\" onclick=\"applyCoupon(this);\" title=\""+$this.languages['apply']+"\" coupon=\""+coupon['coupon_code']+"\" apply=\"1\" class=\"fhs-btn-view-promo-coupon\"><span>"+$this.languages['apply']+"</span></button>";
//		btn_detail = "<button type=\"button\" title=\""+$this.languages['apply']+"\" onclick=\"applyCoupon(this);\" coupon=\""+coupon['coupon_code']+"\" apply=\"1\" class=\"btn-close-popup-event fhs-btn-view-promo-detail-coupon\"><span>"+$this.languages['apply']+"</span></button>";
//	    }else{
//		class_button_more = "class='no-more-button'";
////		if(coupon['reach_percent'] >= 100){
////		    btn_apply = "<button type=\"button\" onclick=\"applyCoupon(this);\" title=\""+$this.languages['apply']+"\" coupon=\""+coupon['coupon_code']+"\" apply=\"1\" class=\"fhs-btn-view-promo-coupon\" disabled><span>"+$this.languages['apply']+"</span></button>";
////		    btn_detail = "<button type=\"button\" title=\""+$this.languages['apply']+"\" onclick=\"applyCoupon(this);\" coupon=\""+coupon['coupon_code']+"\" apply=\"1\" class=\"btn-close-popup-event fhs-btn-view-promo-detail-coupon\" disabled><span>"+$this.languages['apply']+"</span></button>";
////		}else{
////		    if(coupon['page_detail']){
////			btn_apply = "<a href=\"/"+coupon['page_detail']+"\"><button type=\"button\" title=\""+$this.languages['buy_more']+"\" class=\"fhs-btn-view-promo\"><span>"+$this.languages['buy_more']+"</span></button></a>";
////			btn_detail = "<a href=\"/"+coupon['page_detail']+"\"><button type=\"button\" title=\""+$this.languages['buy_more']+"\" class=\"btn-close-popup-event fhs-btn-view-promo-detail-gift\"><span>"+$this.languages['buy_more']+"</span></button></a>";
////		    }else{
////			class_button_more = "class='no-more-button'";
////		    }
////		}
//	    }
//	}
//	let is_show_btn_detail = 'true';
//	if(coupon['event_type'] == 1 && !coupon['applied'] && (coupon['reach_percent'] < 100) && fhs_account.isEmpty(coupon['page_detail'])){
//	    is_show_btn_detail = 'false';
//	}else if( coupon['event_type'] != 1 && !coupon['matched'] && empty(coupon['page_detail'])){
//	    is_show_btn_detail = 'false';
//	}else if(coupon['event_type'] != 1 && coupon['matched']){
//	    is_show_btn_detail = 'false';
//	}
//	if(coupon['rule_content']){
//	    class_content_detail = 'class="fhs-event-promo-list-item-content" onclick="showEventCartContentDetail(this);"';
//	    rule_content = "<div class=\"fhs-event-promo-list-item-detail\" onclick=\"showEventCartDetail(this, "+index+", '"+_type+"', "+is_show_btn_detail+")\">"+$this.languages['detail']
//		    +"<div class=\"fhs-event-promo-list-item-btndata\">"+btn_detail+"</div>"
//		    +"</div>";
//	}
//	return "<div class=\"fhs-event-promo-list-item "+class_color+"\">"
//		    +"<div>"
//			+"<img src=\""+img_coupon+"\"/>"
//		    +"</div>"
//		    +"<div "+class_button_more+">"
//			+"<div>"
//			    +"<div "+class_content_detail+">"
//				+"<div>"+almost_over + coupon['title']+"</div>"
//				+title_2
//				+title_3
//				+errors
//			    +"</div>"
//			+"<div>"
//			+rule_content
//			+"<div class=\"fhs-event-promo-list-item-button\">"
//			    +btn_apply
//			+"</div>"
//			+"</div>"
//			+"</div>"
//			+progress_bar
//		    +"</div>"
//		+"</div>";
//    };
//    this.displayPromotionCart = function(data){
//	if(data == null){
//	    $jq('#fhs_checkout_event_promotion_block').fadeOut(0);
//	    return;
//	}
//	if(fhs_account.isEmpty(data['matched'])){
//	    $jq('#fhs_checkout_event_promotion_block').fadeOut(0);
//	    return;
//	}else{
//                $jq('#fhs_checkout_event_promotion_block').fadeIn(0);
//            }
//            event_cart_data_outside = data['matched'];
//            let promotions_html = '';
//	Object.keys(data['matched']).forEach(function(key){
//                promotions_html += $this.renderPromotionCart(key, data['matched'][key]);
//            });
//           $jq("#fhs_checkout_event_promotion .fhs-event-promo-list").html(promotions_html);
//    };
//    this.renderPromotionCart = function(index, data){
//	let class_color = "fhs-event-promo-list-item-blue";
//	let img_coupon = '';
//	let title_2 = '';
//	let title_3 = '';
//	let errors = '';
//	let rule_content = '';
//	let class_content_detail = '';
//	let btn_apply = '';
//	let progress_bar = '';
//	let progress_bar_class = '';
//	let total = '';
//	let class_button_more = '';
//	let almost_over = '';
//	
//	if(data['almost_run_out']){
//	    almost_over = "<span class=\"fhs-event-promo-almost-over-red\">"+$this.languages['selling_out']+"</span>";
//	}
//	
//	if(!fhs_account.isEmpty(data['min_total']) && !fhs_account.isEmpty(data['max_total'])){
//	    total = "<div class=\"fhs-event-promo-item-minmax\"><span>"+data['min_total']+"</span><span>"+data['max_total']+"</span></div>";
//	}
//	
//	if(data['matched']){
//	    progress_bar_class = "class=\"progress-success\"";
//	}
//	
//	    
//	if(data['matched']){
//	    class_color = "fhs-event-promo-list-item-green";
//	    progress_bar_class = "class=\"progress-success\"";
//	    if(data['event_type'] == 1){
//		img_coupon = $this.languages['ico_coupongreen'];
//	    }else if(data['event_type'] == 2){
//		img_coupon = $this.languages['ico_giftgreen'];
//	    }else{
//		img_coupon = $this.languages['ico_promotiongreen'];
//	    }
//	}else{
//	    if(data['event_type'] == 1){
//		img_coupon = $this.languages['ico_couponblue'];
//	    }else if(data['event_type'] == 2){
//		img_coupon = $this.languages['ico_giftblue'];
//	    }else{
//		img_coupon = $this.languages['ico_promotionblue'];
//	    }
//	    if(!fhs_account.isEmpty(data['sub_total'])){
//		progress_bar = "<div class=\"fhs-event-promo-item-progress-bar\">"
//			    +"<div class=\"fhs-event-promo-item-progress\"><hr "+progress_bar_class+" style=\"width:"+ data['reach_percent'] + "%;\"/><div>"+data['sub_total']+"</div><img class='progress-cheat' src='"+$this.languages['progress_cheat_img']+"'/></div>"
//			    +total
//			+"</div>";
//	    }
//	}
//	
//	if(data['title_2']){
//	    title_2 = "<div>"+data['title_2']+"</div>";
//	}
//	if(data['title_3']){
//	    title_3 = "<div>"+data['title_3']+"</div>";
//	}
//	if(fhs_account.isEmpty(data['error'])){
//	    Object.keys(data['error']).forEach(function(key){
//		errors += "<div class=\"fhs-event-promo-error\">* "+data['error'][key]['message']+"</div>";
//	    });
//	}
//	if(data['matched']){
//	    btn_apply = "<img src=\""+$this.languages['ico_check']+"\"/><span style=\"padding-left: 4px; color: #28B928;\">"+$this.languages['applied']+"</span>";
//	}else{
//	    class_button_more = "class='no-more-button'";
////	    if(data['page_detail']){
////		btn_apply = "<a href=\"/"+data['page_detail']+"\"><button type=\"button\" title=\""+$this.languages['buy_more']+"\" class=\"fhs-btn-view-promo\"><span>"+$this.languages['buy_more']+"</span></button></a>";
////	    }else{
////		class_button_more = "class='no-more-button'";
////	    }
//	}
//	
//	if(data['rule_content']){
//	    class_content_detail = 'class="fhs-event-promo-list-item-content" onclick="showEventCartContentDetail(this);"';
//	    rule_content = "<div class=\"fhs-event-promo-list-item-detail\" onclick=\"showEventCartDetailOutSide(this,"+index+")\">"+$this.languages['detail']+"</div>";
//	}
//		
//	return "<div class=\"fhs-event-promo-list-item "+class_color+"\">"
//		+"<div "+class_button_more+">"
//		    +"<img src=\""+img_coupon+"\"/>"
//		+"</div>"
//		+"<div>"
//		    +"<div>"
//			+"<div "+class_content_detail+">"
//			    +"<div>"+almost_over + data['title']+"</div>"
//			    +title_2
//			    +title_3
//			    +errors
//			+"</div>"
//		    +"<div>"
//		    +rule_content
//		    +btn_apply
//		    +"</div>"
//		    +"</div>"
//		    +progress_bar
//		+"</div>"
//	    +"</div>";
//    };
    
    //METHOD PROCESS
    this.updateShippingAddress = function(){
	let shipping = {billing:{}, shipping:{}, shipping_method:'', freeship:'0'};
	shipping.billing.firstName = '';
	shipping.billing.lastName = '';
	shipping.billing.company = '';
	shipping.billing.street = '';
	shipping.billing.postcode = '';
	shipping.billing.email = '';
	shipping.billing.telephone = '';
	shipping.billing.fax = '';
	shipping.billing.city = '';
	shipping.billing.cityId = '';
	shipping.billing.countryId = '';
	shipping.billing.regionId = '';
	shipping.billing.region = '';
	shipping.billing.ward = '';
	shipping.billing.saveInAddressBook = '0';
	shipping.billing.saveBilling = '0';
	shipping.billing.useForShipping = '1';
	
	if($this.has_address_list){
	    let address_id = $jq('.fhs_checkout_block_address_list_item_option:checked').val(); 
	    let address = $this.address_list[address_id];
	    shipping.billing.firstName  = address['firstname'];
	    shipping.billing.lastName = address['lastname'];
	    shipping.billing.street = address['street'][0];
	    shipping.billing.postcode = address['postcode'];
	    shipping.billing.telephone = address['telephone'];
	    shipping.billing.city = address['city'];
	    shipping.billing.cityId = address['city'];
	    shipping.billing.countryId = address['country_id'];
	    shipping.billing.regionId = address['region_id'];
	    shipping.billing.region = address['region'];
	    shipping.billing.ward = address['ward'];
	}else{
	    shipping.billing.firstName  = $jq('#fhs_shipping_firstname').val().trim();
	    shipping.billing.lastName = $jq('#fhs_shipping_lastname').val().trim();
	    shipping.billing.street = $jq('#fhs_shipping_street').val().trim();
	    shipping.billing.postcode = $jq('#fhs_shipping_postcode').val().trim();
	    shipping.billing.email = $jq('#fhs_shipping_email').val().trim();
	    shipping.billing.telephone = $jq('#fhs_shipping_telephone').val();
	    shipping.billing.city = $jq('#fhs_shipping_district').val();
	    shipping.billing.cityId = $jq('#fhs_shipping_district').val();
	    shipping.billing.countryId = $jq('#fhs_shipping_country').val();
	    shipping.billing.regionId = $jq('#fhs_shipping_city_select option:selected').val();
	    shipping.billing.region = $jq('#fhs_shipping_city').val();
	    shipping.billing.ward = $jq('#fhs_shipping_ward').val();
	}
	
	if(!$this.has_address_list && $this.is_login){
	    shipping.billing.saveInAddressBook = 1;
	}
	
	let shipping_method = $jq('.fhs_checkout_shippingmethod_option:checked').val();
	if(fhs_account.isEmpty(shipping_method)){
	    shipping_method = '';
	}
	let freeship = '0';
	if($this.is_login){
	    if(shipping.billing.countryId == 'VN' && $jq('#fhs_checkout_freeship').val() > 0){
		$jq('#fhs_checkout_freeship').removeAttr('disabled');
		$jq('#fhs_checkout_freeship').parent().children('.fhs_input_checkbox').removeClass('fhs_input_checkbox_disabled');
		if($jq('#fhs_checkout_freeship').prop('checked')){
		    freeship = '1';
		}
	    }else{
		$jq('#fhs_checkout_freeship').removeAttr('checked');
		$jq('#fhs_checkout_freeship').parent().children('.fhs_input_checkbox').addClass('fhs_input_checkbox_disabled');
		$jq('#fhs_checkout_freeship').attr('disabled','disabled');
	    }
	}
	shipping.shipping_method = shipping_method;
	shipping.freeship = freeship;
	
	$this.setShipping($jq('#fhs_checkout_shippingmethod'), shipping);
    };
    this.validateCreateOrder = function(){
	if(!$this.has_address_list){
	    if(!$this.Validate_shipping_address_require()){
		$jq('html, body').stop().animate({
		    scrollTop: $jq('#fhs_checkout_block_address').offset().top
		}, 1000);
		return;
	    }
	}
	let shipping_method = $jq('.fhs_checkout_shippingmethod_option:checked').val();
	
	if($jq('.fhs_checkout_shippingmethod_option:checked').hasClass('eventmethod')){
	    if(fhs_account.isEmpty($this.event_delivery_method_changed) || fhs_account.isEmpty($this.event_delivery_option_changed)){
		$jq('.fhs_event_delivery_select').each(function() {
		    if($jq(this).is(":visible") && fhs_account.isEmpty($jq(this).attr('disabled'))){
			if($jq(this).hasClass('require_check')){
			    fhs_account.validateTextbox($jq(this).attr('validate_type'), $jq(this).val(), $jq(this));
			}
		    }
		});
		$jq('#fhs_checkout_block_shippingmethod').addClass('block_checked_error');
		    
		$jq('html, body').stop().animate({
		    scrollTop: $jq('#fhs_checkout_block_shippingmethod').offset().top
		}, 1000);
		return;
	    }
	}else{
	    if(fhs_account.isEmpty(shipping_method)){
		$jq('#fhs_checkout_block_shippingmethod').addClass('block_checked_error');
		$jq('html, body').stop().animate({
		    scrollTop: $jq('#fhs_checkout_block_shippingmethod').offset().top
		}, 1000);
		return;
	    }
	}
	
	let payment_method = $jq('.fhs_checkout_paymentmethod_option:checked').val();
	if(fhs_account.isEmpty(payment_method)){
	    $jq('#fhs_checkout_block_paymentmethod').addClass('block_checked_error');
	    $jq('html, body').stop().animate({
		scrollTop: $jq('#fhs_checkout_block_paymentmethod').offset().top
	    }, 1000);
	    return;
	}
	
	if(!$this.Validate_other_info_require() || !fhs_account.validateTextboxGroup($jq('#fhs_checkout_companyname'))){
	    $jq('html, body').stop().animate({
		scrollTop: $jq('#fhs_checkout_block_otherInfo').offset().top
	    }, 1000);
	    return;
	}
	
	if($this.has_address_list){
	    let is_pass_address = true;
	    
	    let address_id = $jq('.fhs_checkout_block_address_list_item_option:checked').val(); 
	    let address = $this.address_list[address_id];
	    if(fhs_account.isEmpty(address['fullname']) ||
		fhs_account.isEmpty(address['firstname']) ||
		fhs_account.isEmpty(address['lastname']) ||
		fhs_account.isEmpty(address['street'][0]) ||
		( fhs_account.isEmpty(address['telephone']) && fhs_account.validateData('shipping_telephone',address['telephone']) ) ){
		is_pass_address = false;
	    }
	    
	    if(address['country_id'] == 'VN'){
		if(fhs_account.isEmpty(address['city']) || fhs_account.isEmpty(address['ward'])){
		    is_pass_address = false;
		}
	    }
	    
	    if(!is_pass_address){
		$jq('html, body').stop().animate({
		    scrollTop: $jq('#fhs_checkout_block_address').offset().top
		}, 1000);
		$this.showPopupAddress(address_id, 'change');
		return;
	    }
	    
	}
	
	if($this.require_confirm_shiping_address){
	    $jq('#shipping_address_confirm_content').text($this.getCurentAddress()['address']);
	    $jq('.youama-ajaxlogin-cover').fadeIn();
	    $jq('#popup_fahasa_shipping_confirm').fadeIn();
	}else{
            $this.progressReset();
	    $this.createOrder();
	}
    };
    this.createOrder = function(){
	let shipping = {billing: {}, shipping:{}, vatData:{}, shippingMethod:'', paymentMethod:'', couponCode:'', vipCode:'', giftwrap:'', freeship:'0', tryout:'0', pickupLocation:'', event_delivery_option:{}};
	shipping.billing.firstName = '';
	shipping.billing.lastName = '';
	shipping.billing.company = '';
	shipping.billing.street = '';
	shipping.billing.postcode = '';
	shipping.billing.email = '';
	shipping.billing.telephone = '';
	shipping.billing.fax = '';
	shipping.billing.city = '';
	shipping.billing.cityId = '';
	shipping.billing.countryId = '';
	shipping.billing.regionId = '';
	shipping.billing.region = '';
	shipping.billing.ward = '';
	shipping.billing.saveInAddressBook = '0';
	shipping.billing.saveBilling = '0';
	shipping.billing.useForShipping = '1';
	shipping.billing.event_cart_option = '';
	
	shipping.vatData.customerNote = '';
	shipping.vatData.vatCompany = '';
	shipping.vatData.vatAddress = '';
	shipping.vatData.vatTaxcode = '';
	
	if($this.has_address_list){
	    let address_id = $jq('.fhs_checkout_block_address_list_item_option:checked').val(); 
	    let address = $this.address_list[address_id];
	    shipping.billing.fullName  = address['fullname'];
	    shipping.billing.firstName  = address['firstname'];
	    shipping.billing.lastName = address['lastname'];
	    shipping.billing.street = address['street'][0];
	    shipping.billing.postcode = address['postcode'];
	    shipping.billing.telephone = address['telephone'];
	    shipping.billing.city = address['city'];
	    shipping.billing.cityId = address['city'];
	    shipping.billing.countryId = address['country_id'];
	    shipping.billing.regionId = address['region_id'];
	    shipping.billing.region = address['region'];
	    shipping.billing.ward = address['ward'];
	}else{
	    shipping.billing.fullName  = $jq('#fhs_shipping_fullname').val().trim();
	    shipping.billing.firstName  = $jq('#fhs_shipping_firstname').val().trim();
	    shipping.billing.lastName = $jq('#fhs_shipping_lastname').val().trim();
	    shipping.billing.street = $jq('#fhs_shipping_street').val().trim();
	    shipping.billing.postcode = $jq('#fhs_shipping_postcode').val().trim();
	    shipping.billing.email = $jq('#fhs_shipping_email').val().trim();
	    shipping.billing.telephone = $jq('#fhs_shipping_telephone').val();
	    shipping.billing.city = $jq('#fhs_shipping_district').val();
	    shipping.billing.cityId = $jq('#fhs_shipping_district').val();
	    shipping.billing.countryId = $jq('#fhs_shipping_country').val();
	    shipping.billing.regionId = $jq('#fhs_shipping_city_select option:selected').val();
	    shipping.billing.region = $jq('#fhs_shipping_city').val();
	    shipping.billing.ward = $jq('#fhs_shipping_ward').val();
	}
	
	if(!$this.has_address_list && $this.is_login){
	    shipping.billing.saveInAddressBook = 1;
	}
	
	shipping.vatData.customerNote = $jq('#fhs_checkout_note').val().trim();
	shipping.vatData.vatCompany = $jq('#fhs_checkout_companyname').val().trim();
	shipping.vatData.vatAddress = $jq('#fhs_checkout_companyaddress').val().trim();
	shipping.vatData.vatTaxcode = $jq('#fhs_checkout_companyvat').val().trim();
	shipping.vatData.vatName = $jq('#fhs_checkout_customername').val().trim();
	shipping.vatData.vatEmail = $jq('#fhs_checkout_email').val().trim();
	
	shipping.shippingMethod = $jq('.fhs_checkout_shippingmethod_option:checked').val();
	shipping.paymentMethod = $jq('.fhs_checkout_paymentmethod_option:checked').val();
	
	if(!fhs_account.isEmpty($this.coupon)){
	    shipping.couponCode = $this.coupon.trim();
	}
	
	if($this.is_login){
	    let freeship = '0';
	    let tryout = '0';
	    if($jq('#fhs_checkout_freeship').prop('checked')){
		freeship = '1';
	    }
	    if($jq('#fhs_checkout_fpoint').prop('checked')){
		tryout = '1';
	    }
	    shipping.freeship = freeship;
	    shipping.tryout = tryout;
	}
	
	if(!fhs_account.isEmpty()){
	    shipping.billing.event_cart_option = $jq('#event_cart_data').val();
	}
	
	if(!fhs_account.isEmpty($this.event_delivery_method_changed) && !fhs_account.isEmpty($this.event_delivery_option_changed)){
	    shipping.event_delivery_option.eventDeliveryId = $this.event_delivery_method_changed;
	    shipping.event_delivery_option.periodId = $this.event_delivery_option_changed;
	}
	
	$this.createOrder_post(shipping);
    };
    this.tryLoadOrderStatus = function (){
	$jq('#popup-default-loading-confirm').hide();
	$jq('#popup-default-loading-context-text').html($this.languages['processing']+"...");
	$jq('#popup-default-loading-logo').hide();
	$jq('#popup-default-loading-icon').show();
	$this.time_loaded = 0;
        $this.progressSet(0.5,0.25);
	$this.checkOrderStatus();
    };
    
    //COMMON
    this.fillLoginName = function(e){
	let $fhs_input_box = $jq(e).parents('.fhs-input-box');
	let text = $fhs_input_box.find('.fhs-textbox').val();
	$jq('#login_username').val(text);
	setTimeout(function(){$jq('#login_username').trigger('keyup');}, 500);
    }
    this.getCurentAddress = function(){
	let address = {};
	address.firstname = '';
	address.lastname = '';
	address.fullname = '';
	address.company = '';
	address.street = '';
	address.postcode = '';
	address.email = '';
	address.telephone = '';
	address.fax = '';
	address.city = '';
	address.cityId = '';
	address.countryId = '';
	address.regionId = '';
	address.region = '';
	address.ward = '';
	
	if($this.has_address_list){
	    let address_id = $jq('.fhs_checkout_block_address_list_item_option:checked').val(); 
	    let address_item = $this.address_list[address_id];
	    address.firstname  = address_item['firstname'];
	    address.lastname = address_item['lastname'];
	    address.fullname = address_item['fullname'];
	    address.street = address_item['street'][0];
	    address.postcode = address_item['postcode'];
	    address.telephone = address_item['telephone'];
	    address.city = address_item['city'];
	    address.cityId = address_item['city'];
	    address.countryId = address_item['country_id'];
	    address.regionId = address_item['region_id'];
	    address.region = address_item['region'];
	    address.ward = address_item['ward'];
	    address.address = address_item['address'];
	}else{
	    address.firstname  = $jq('#fhs_shipping_firstname').val().trim();
	    address.lastname = $jq('#fhs_shipping_lastname').val().trim();
	    address.fullname = $jq('#fhs_shipping_fullname').val().trim();
	    address.street = $jq('#fhs_shipping_street').val().trim();
	    address.postcode = $jq('#fhs_shipping_postcode').val().trim();
	    address.email = $jq('#fhs_shipping_email').val().trim();
	    address.telephone = $jq('#fhs_shipping_telephone').val();
	    address.city = $jq('#fhs_shipping_district').val();
	    address.cityId = $jq('#fhs_shipping_district').val();
	    address.countryId = $jq('#fhs_shipping_country').val();
	    address.regionId = $jq('#fhs_shipping_city_select option:selected').val();
	    address.region = $jq('#fhs_shipping_city').val();
	    address.ward = $jq('#fhs_shipping_ward').val();
	    address.address = (address.street?address.street:'')
			+(address.ward?(", "+address.ward):'')
			+(address.city?(", "+address.city):'')
			+(address.region?(", "+address.region):'') 
			+(address.postcode?(", "+address.postcode):'') 
			+(address.countryId?(", "+address.countryId):'');
	}
	return address;
    }
    this.getDistrictId = function(country_id, city_id, district_name){
	let result = '';
	let countries = $this.city_Json[country_id];
	if(countries){
	    let cities = $this.district_Json[city_id];
	    if(cities){
		Object.keys(cities).forEach(function(key){
		    let city = cities[key];
		    if(city['name'] == district_name){
			result = key;
		    }
		}); 
	    }
	}
	return result;
    };
    this.getWardId = function(country_id, city_id, district_id, ward_name){
	let result = '';
	let countries = $this.city_Json[country_id];
	if(countries){
	    let cities = $this.district_Json[city_id];
	    if(cities){
		let districts = $this.ward_Json[district_id];
		if(districts){
		    Object.keys(districts).forEach(function(key){
			if(districts[key]['name'] == ward_name){
			    result = key;
			}
		    }); 
		}
	    }
	}
	return result;
    };
    
    this.loadProgress = function () {
        var bar = new ProgressBar.Circle('#container-progress-loading', {
            color: '#737373',
            strokeWidth: 4,
            trailWidth: 4,
            easing: 'easeInOut',
            duration: 1400,
            text: {
                autoStyleContainer: false,
                style: {
                    color: '#F7941E',
                    position: 'absolute',
                    left: '50%',
                    top: '50%',
                    padding: 0,
                    margin: 0,
                    fontSize : '2.3rem',
                    transform: {
                        prefix: true,
                        value: 'translate(-50%, -50%)'
                    }
                }
            },
            svgStyle: {
                //display: 'block',
                //width: '50%',
                height: '90px',
            },
            from: {color: '#F7941E', width: 4},
            to: {color: '#F7941E', width: 4},
            step: function (state, circle) {
                circle.path.setAttribute('stroke', state.color);
                circle.path.setAttribute('stroke-width', state.width);

                var value = Math.round(circle.value() * 100);
                if (value === 0) {
                    circle.setText('0%');
                } else {
                    circle.setText(value + "%");
                }
            }
        });
        //bar.text.style.fontFamily = '"Raleway", Helvetica, sans-serif';
        //bar.text.style.fontSize = '2rem';
        // bar.animate(0.1);  // Number from 0.0 to 1.0
        $this.progressBar = bar;
    };
    this.progressFinish = function (linkHref = null) {
        if ($this.progressBar) {
            $this.progressBar.animate(1, {
                duration: 800
            }, function () {
                if (linkHref) {
                    window.location.href = linkHref
                }
            });
        } else {
            if (linkHref) {
                window.location.href = linkHref
            }
    }
    };
    this.progressReset = function () {
        if ($this.progressBar) {
            $this.progressBar.animate(0);
        }
    };
    this.progressSet = function (time, value) {
        let val = value * time;
        if ($this.progressBar) {
            $this.progressBar.animate(val);
        }
    };
};
