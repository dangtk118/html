var Promotion = function () {
    var COUPON_APPLY_URL = "/onestepcheckout/index/couponCode";
    
    var event_cart_data = {};
    let is_loading_coupon = false;
    var is_show_error_block = false;
    var is_cart = true;
    let eventCart_keys = {};
    const COUPON_BG_SVG = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" class="coupon_bg"><g fill="none" fill-rule="evenodd"><g><g><g><g transform="translate(-544 -3050) translate(80 2072) translate(0 930) translate(464 48)"><path id="Frame_voucher_Web" d="M 110 144 h -98 a 12 12 0 0 1 -12 -12 v -120 a 12 12 0 0 1 12 -12 H 110 a 12.02 12.02 0 0 0 12 11.971 a 12.02 12.02 0 0 0 12 -11.971 H 524 a 12 12 0 0 1 12 12 V 132 a 12 12 0 0 1 -12 12 H 134 v -0.03 a 12 12 0 0 0 -24 0 v 0 Z" transform="translate(0.5 0.5)" fill="#fff" stroke="rgba(0,0,0,0)" stroke-miterlimit="10" stroke-width="1"/></g></g></g></g></g></svg>';
    var languages = {};
    
    var $this = this;
    
    //----Promotion
    this.initPromotion = function (_event_cart_data, _languages, _eventCart_keys, _is_cart = true) {
	$this.languages = _languages;
	$this.is_cart = _is_cart
	$this.event_cart_data = _event_cart_data;
	$this.eventCart_keys = _eventCart_keys;
	
	fhs_account.hoverCouponBg();
	
	$jq(".btn-close-popup-event").click(function(e){
	    $jq('.youama-ajaxlogin-cover').fadeOut(0);
	    $jq('#popup-loading-event-cart').fadeOut(0);
	});
	$jq(window).on('resize scroll', function() {
	    fhs_account.sizeCouponBg();
	});
	$jq('.popup-loading-event-cart-coupon').bind('DOMSubtreeModified', function(){
	    $this.sizeByCouponBlock();
	});
//	$jq('#popup-loading-event-cart-content-tab li').click(function(){
//	    if(!$jq(this).hasClass("active")){
//		$jq('#popup-loading-event-cart-content-tab li').removeClass("active");
//		$jq(this).addClass("active");
//	    }
//	    if($jq(this).hasClass("fhs-tabs-item-promotion")){
//		$jq('.popup-loading-event-cart-content-wallet').fadeOut(0);
//		$jq('.popup-loading-event-cart-content-promotion').fadeIn(0);
//	    }else{
//		$jq('.popup-loading-event-cart-content-promotion').fadeOut(0);
//		$jq('.popup-loading-event-cart-content-wallet').fadeIn(0);
//		if(!$this.is_cart){
//		    fhs_onestepcheckout.getWalletVoucher();
//		}
//	    }
//	});
    };
    
    //post API
    this.setCoupon = function(element, coupon_code = '', apply = 1, $btn_apply = null){
	if($this.is_loading_coupon){return}
	$this.is_loading_coupon = true;
	fhs_account.animateLoaderBlock('start',element);
	fhs_account.showLoadingAnimation();
	let data = {sessionId: SESSION_ID,couponCode: coupon_code.trim(), apply: apply};
	$jq.ajax({
	    url: COUPON_APPLY_URL,
	    method: 'post',
            dataType : "json",
	    data: JSON.stringify(data),
            headers: ps.getHeader(),
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		let $input_box = element.parents('.fhs-input-box');
		let $alert_message = $input_box.children('.fhs-input-alert');
		$input_box.removeClass('checked-error-text');
		$alert_message.text('');
		if(data['success']){
		    if(apply == 1){
			$jq('.fhs_checkout_coupon').val('');
			if($btn_apply != null){
			    if($btn_apply.length > 0){
				$btn_apply.addClass('applied');
				$btn_apply.attr('apply', '0');
				$btn_apply.children('span').html($this.languages['cancel_apply']);
				
				let $promo_item = $btn_apply.parents('.fhs-event-promo-item');
				if($promo_item.length > 0){
				    let $promo_item_progress = $promo_item.find('.fhs-event-promo-item-progress-bar');
				    if($promo_item_progress.length > 0){
					let progress_bar = '<div class="fhs-event-promo-item-msg"><img src="'+$this.languages['ico_check']+'"/><span style="padding-left: 4px; color: #2F80ED;">'+$this.languages['applied']+'</span></div>';
					$promo_item_progress.html(progress_bar);
				    }
				}
			    }
			}

			fhs_account.showAlert($this.languages['code_applied']);
		    }else{
			if($btn_apply != null){
			    if($btn_apply.length > 0){
				$btn_apply.removeClass('applied');
				$btn_apply.attr('apply', '1');
				$btn_apply.children('span').html($this.languages['apply']);
				
				let $promo_item = $btn_apply.parents('.fhs-event-promo-item');
				if($promo_item.length > 0){
				    let $promo_item_msg = $promo_item.find('.fhs-event-promo-item-msg');
				    if($promo_item_msg.length > 0){
					$promo_item_msg.remove();
				    }
				}
			    }
			}
			fhs_account.showAlert($this.languages['code_canceled']);
		    }
		    
		    if($this.is_cart){
//			$this.reloadPage();
                        cart.getCart();
		    }
		}else{
		    if(!fhs_account.isEmpty(data['message'])){
			$input_box.addClass('checked-error-text');
			$alert_message.text(data['message']);
			if($btn_apply != null){
			    fhs_account.showAlert(data['message']);
			}
		    }
		}
		if(!$this.is_cart){
		    fhs_onestepcheckout.displayCoupon(data['checkout']['couponCode'], data['checkout']['couponLabel'], data['checkout']['freeshipCouponCode'], data['checkout']['freeshipCouponLabel']);
		    fhs_onestepcheckout.getCheckout($jq('#fhs_checkout_products'));
		}
		$this.is_loading_coupon = false;
		fhs_account.animateLoaderBlock('stop',element);
		fhs_account.hideLoadingAnimation();
	    },
	    error: function () {
		$this.is_loading_coupon = false;
		fhs_account.animateLoaderBlock('stop',element);
		fhs_account.hideLoadingAnimation();
	    }
	});
    };
    
    //Reload page
    this.reloadPage = function(){
	setTimeout(function(){location.reload();}, 500)
    }
    //event function
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
    
    this.displayPromotionCart = function(event_cart){
	let result = '';
	try{
	    if(event_cart['affect_carts']){
		if(event_cart['affect_carts']['matched']){
		    let event_cart_affect_carts = {matched: event_cart['affect_carts']['matched']};
		    result = $this.getPromotionList('affect_carts', event_cart_affect_carts, false);
		}
	    }
	    if(fhs_account.isEmpty(result)){
		$jq('#fhs_checkout_event_promotion_block').fadeOut(0);
	    }else{
		$jq('#fhs_checkout_event_promotion_block').fadeIn(0);
	    }
	}catch(ex) {result = '';}
	if(fhs_account.isEmpty(result)){
	    result = "<div class=\"fhs-event-promo-list-empty\"><img src=\""+$this.languages['ico_couponemty']+"\"/><div>"+$this.languages['no_promotion']+"</div></div>";
	}
	$jq('#fhs_checkout_event_promotion').html(result);
	fhs_account.hoverCouponBg();
	fhs_account.sizeCouponBg();
    }
    this.displayPopupPromotion = function(event_cart){
	$this.event_cart_data = event_cart;
	
	if($jq('#popup-loading-event-cart .popup-loading-event-cart-info .popup-loading-event-cart-content .popup-loading-event-cart-content-promotion').length == 0){
	    return;
	}
	
	let result = '';
	try{
	    Object.keys($this.eventCart_keys).forEach(function(key){
		let key_name = $this.eventCart_keys[key];
		if(event_cart[key_name]){
		    result += $this.getPromotionList(key_name, event_cart[key_name]);
		}
	    });
	}catch(ex) {result = '';}
	if(fhs_account.isEmpty(result)){
	    result = "<div class=\"fhs-event-promo-list-empty\"><img src=\""+$this.languages['ico_couponemty']+"\"/><div>"+$this.languages['no_promotion']+"</div></div>";
	}
	$jq('#popup-loading-event-cart .popup-loading-event-cart-info .popup-loading-event-cart-content .popup-loading-event-cart-content-promotion').html(result);
	fhs_account.hoverCouponBg();
	fhs_account.sizeCouponBg();
    }
    this.getPromotionList = function(key_name, list, is_show_title = true){
	let result = '';
	try{
	    let list_html = '';
	    let list_show = '';
	    let list_more = '';
	    let list_expired = [];
	    let title = '';

	    if(list){
		if(is_show_title){
		    switch(key_name){
			case 'affect_coupons':
			    title = '<div class="fhs-event-promo-list-title">'
					+'<span>'+$this.languages['coupon']+'</span>'
					+'<span class="fhs_label_note" style="margin-left: 8px;">'+$this.languages['max_apply']+'1</span>'
				    +'</div>';
			    break;
			case 'affect_freeships':
			    title = '<div class="fhs-event-promo-list-title">'
					+'<span>'+$this.languages['delivery_code']+'</span>'
					+'<span class="fhs_label_note" style="margin-left: 8px;">'+$this.languages['max_apply']+'1</span>'
				    +'</div>';
			    break;
			case 'affect_payments':
			    title = '<div class="fhs-event-promo-list-title">'
					+'<span>'+$this.languages['payment_promotion']+'</span>'
					+'<span class="fhs_label_note" style="margin-left: 8px;">'+$this.languages['apply_at_e_wallet']+'</span>'
				    +'</div>';	    
			    break;
			default:
			    title = '<div class="fhs-event-promo-list-title">'
					+'<span>'+$this.languages['other_promotion']+'</span>'
					+'<span class="fhs_label_note" style="margin-left: 8px;">'+$this.languages['auto_apply']+'</span>'
				    +'</div>';	
		    }
		}
		let i = 0;
		Object.keys(list).forEach(function(key_type){
		    if(list[key_type]){
			if(key_type == 'matched' || key_type == 'not_matched'){
			    let item_type = list[key_type];
			    Object.keys(item_type).forEach(function(key_index){
				let item = item_type[key_index];

				let expired_class = '';
				let almost_run_out = '';
				let icon = '';
				let item_class = '';
				if(is_show_title){
				    if(item['matched']){
					key_type = 'matched';
				    }else{
					key_type = 'not_matched';
				    }
				}
				
				let keys = {
				    'key_type':key_type,
				    'key_name':key_name,
				    'key_index':key_index
				};
				if(item['is_expired']){
				    expired_class = 'expired';
				}
				if(!item['is_expired'] && item['almost_run_out']){
				    almost_run_out = '<div class="label_expired"><img src="'+$this.languages['label_saphet']+'"/></div>';
				}

				switch(item['event_type']){
				    //coupon - yellow
				    case 1: 
				    case 5: 
					item_class = 'fhs-event-promo-list-item-coupon';
					icon = '<div><img src="'+$this.languages['ico_promotion']+'"/></div>';
					break;
				    //coupon freeship - green
				    case 4:
				    case 6:
					item_class = 'fhs-event-promo-list-item-freeship';
					icon = '<div><img src="'+$this.languages['ico_freeship']+'"/></div>';
					break;
				    case 3: //payment - blue
					item_class = 'fhs-event-promo-list-item-payment';
					icon = '<div><img src="'+$this.languages['ico_ewallet']+'"/></div>';
					break;
				    default: //other - purple
					item_class = 'fhs-event-promo-list-item-other';
					icon = '<div><img src="'+$this.languages['ico_gift']+'"/></div>';

				}
				
				let list_item =  '<div class="fhs-event-promo-list-item '+key_type+' '+(item_class?item_class:'')+' '+(expired_class?expired_class:'')+'">'
							+COUPON_BG_SVG
							+icon
							+'<div class="fhs-event-promo-item '+expired_class+'">'+$this.getPromotionItem(item, keys, (!is_show_title), is_show_title)+'</div>'
							+almost_run_out
							+'</div>';
						
				if(!item['is_expired']){
				    if(i < 2 || !is_show_title){
					list_show +=  list_item;
				    }else{
					list_more += list_item;
				    }
				    i++;
				}else{
				    list_expired.push(list_item);
				}
			    });
			}
		    }
		});
		if(list_expired.length > 0){
		    Object.keys(list_expired).forEach(function(key){
			if(i < 2 || !is_show_title){
			    list_show +=  list_expired[key];
			}else{
			    list_more += list_expired[key];
			}
			i++;
		    });
		}
	    }
	    if(!fhs_account.isEmpty(list_show)){
		list_html = '<div class="fhs-event-promo-list">'
			+'<!-- promotion '+key_name+' -->'
			+title
			+list_show;
		if(!fhs_account.isEmpty(list_more)){
		    list_html += '<div id="collapse_promo_list_'+key_name+'" class="panel-collapse collapse out">'
				+list_more
				+'</div>'
				+'<div class="fhs-event-promo-list-viewmore" onclick="setTimeout(function(){fhs_account.sizeCouponBg();},100);"><a class="collapse collapsed" data-toggle="collapse" href="#collapse_promo_list_'+key_name+'"><span class="text-viewmore">'+$this.languages['viewmore']+'</span><span class="text-viewless">'+$this.languages['viewless']+'</span><img src="'+$this.languages['ico_down_orange']+'"/></a></div>';
		}
		list_html += '</div>';
		if(!fhs_account.isEmpty(result)){
		    result += '<div class="fhs-event-promo-list-line"></div>';
		}
		result += list_html;
	    }
	}catch(ex) {result = '';}
	return result;
    };
    this.getPromotionItem = function(item, keys, is_outside = true, is_show_btn = true){
	let result = '';
	    try{
		let error_str = '';
		let title_2 = '';
		let progress_bar = '';
		let btn_apply = '';
		let class_content_detail = "class='fhs-event-promo-list-item-content' onclick=\"fhs_promotion.showEventCartDetail(this, '"+keys['key_name']+"','"+keys['key_index']+"','"+keys['key_type']+"',"+(is_outside?'true':'false')+","+(is_show_btn?'true':'false')+");\"";
		let expired = '';
		
		if(!fhs_account.isEmpty(item['title_2'])){
		    title_2 = "<div class='fhs-event-promo-list-item-content-description'>"+item['title_2']+"</div>";
		}
		
		if(!item['is_expired']){
		    if(!fhs_account.isEmpty(item['error'])){
			error_str += '<div class="fhs-event-promo-error">'+($this.languages['error_msg'].replace('%s',item['error'].length))+'</div>';
		    }
		    switch(item['event_type']){
			//coupon - yellow
			case 1: 
			case 5: 
			//coupon freeship - green
			case 4:
			case 6:
			    if (item['applied']){
				btn_apply = '<button type="button" onclick="fhs_promotion.applyCoupon(this);" title="'+$this.languages['cancel_apply']+'" coupon="'+item['coupon_code']+'" apply="0" class="fhs-btn-view-promo-coupon applied"><span>'+$this.languages['cancel_apply']+'</span></button>';
				progress_bar = '<div class="fhs-event-promo-item-msg"><img src="'+$this.languages['ico_check']+'"/><span style="padding-left: 4px; color: #2F80ED;">'+$this.languages['applied']+'</span></div>';
			    }else{
				if(item['matched']){
				    btn_apply = '<button type="button" onclick="fhs_promotion.applyCoupon(this);" title="'+$this.languages['apply']+'" coupon="'+item['coupon_code']+'" apply="1" class="fhs-btn-view-promo-coupon" ><span>'+$this.languages['apply']+'</span></button>';
				}else{
				    btn_apply = '<a href="/'+item['page_detail']+'"><button type="button" title="'+$this.languages['buy_more']+'" class="fhs-btn-view-promo"><span>'+$this.languages['buy_more']+'</span></button></a>';
				    if(!fhs_account.isEmpty(item['need_total'])){
					progress_bar = '<div class="fhs-event-promo-item-progress"><hr '+((item['matched'] != 0)?"class=\'progress-success\'" : "")+' style="width: '+item['reach_percent']+'%;'+'"/><img class="progress-cheat" src="'+$this.languages['progress_cheat_img']+'"/></div>'
							+'<div class="fhs-event-promo-item-minmax">'
							+'<span>'+(!fhs_account.isEmpty(item['need_total'])?($this.languages['buy_more_for_promotion'].replace('%s',item['need_total'])):'')+'</span>'
							+'<span>'+(!fhs_account.isEmpty(item['max_total'])?item['max_total']:'')+'</span>'
							+'</div>';
				    }
				}
			    }
			    break;
			case 3: //payment - blue
			    if(item['matched']){
				if(!fhs_account.isEmpty(item['coupon_code'])){
				    btn_apply = '<button type="button" onclick="fhs_account.copyCouponCode(\''+item['coupon_code']+'\');" title="'+$this.languages['copy_code']+'" coupon="'+item['coupon_code']+'" class="fhs-btn-view-promo-coupon" ><span>'+$this.languages['copy_code']+'</span></button>';
				}
			    }else{
				btn_apply = '<a href="/'+item['page_detail']+'"><button type="button" title="'+$this.languages['buy_more']+'" class="fhs-btn-view-promo"><span>'+$this.languages['buy_more']+'</span></button></a>';
				if(!fhs_account.isEmpty(item['need_total'])){
				    progress_bar = '<div class="fhs-event-promo-item-progress"><hr '+((item['matched'] != 0)?"class=\'progress-success\'" : "")+' style="width: '+item['reach_percent']+'%;'+'"/><img class="progress-cheat" src="'+$this.languages['progress_cheat_img']+'"/></div>'
						    +'<div class="fhs-event-promo-item-minmax">'
						    +'<span>'+(!fhs_account.isEmpty(item['need_total'])?($this.languages['buy_more_for_promotion'].replace('%s',item['need_total'])):'')+'</span>'
						    +'<span>'+(!fhs_account.isEmpty(item['max_total'])?item['max_total']:'')+'</span>'
						    +'</div>';
				}
			    }
			    break;
			default: //other - purple
			    if(item['applied']){
				progress_bar = '<div class="fhs-event-promo-item-msg"><img src="'+$this.languages['ico_check']+'"/><span style="padding-left: 4px; color: #2F80ED;">'+$this.languages['applied']+'</span></div>';
			    }else{
				if(item['matched']){
				    btn_apply = '<button type="button" onclick="fhs_promotion.applyCoupon(this);" title="'+$this.languages['apply']+'" coupon="'+item['coupon_code']+'" apply="1" class="fhs-btn-view-promo-coupon" ><span>'+$this.languages['apply']+'</span></button>';
				}else{
				    btn_apply = '<a href="/'+item['page_detail']+'"><button type="button" title="'+$this.languages['buy_more']+'" class="fhs-btn-view-promo"><span>'+$this.languages['buy_more']+'</span></button></a>';
				    if(!fhs_account.isEmpty(item['need_total'])){
					progress_bar = '<div class="fhs-event-promo-item-progress"><hr '+((item['matched'] != 0)?"class=\'progress-success\'" : "")+' style="width: '+item['reach_percent']+'%;'+'"/><img class="progress-cheat" src="'+$this.languages['progress_cheat_img']+'"/></div>'
							+'<div class="fhs-event-promo-item-minmax">'
							+'<span>'+(!fhs_account.isEmpty(item['need_total'])?($this.languages['buy_more_for_promotion'].replace('%s',item['need_total'])):'')+'</span>'
							+'<span>'+(!fhs_account.isEmpty(item['max_total'])?item['max_total']:'')+'</span>'
							+'</div>';
				    }
				}
			    }
		    }
		}else{
		    expired = '<div class="label-expired">'
				    +'<img src="'+$this.languages['label_expired']+'" />'
				+'</div>';
		}
		
		result = '<div>'
				+"<div "+class_content_detail+">"
				    +'<div>'
					+'<div class="fhs-event-promo-list-item-content-title">'
					    +item['title']
					+'</div>'
					+'<div class="fhs-event-promo-list-item-detail fhs_blue_link">'+$this.languages['detail']+'</div>'
				    +'</div>'
				    +title_2
				    +error_str
				+'</div>'
			    +'</div>';
			
		
		if(!fhs_account.isEmpty(progress_bar) || !fhs_account.isEmpty(btn_apply) || !fhs_account.isEmpty(expired)){
		    result += 
			    '<div>'
				+'<div class="fhs-event-promo-item-progress-bar">'
				    +progress_bar
				+'</div>'
				+'<div>'
				    +btn_apply
				+'</div>'
			    +'</div>'
			    +expired;;
		}
	}catch(ex) {result = '';}
	return result;
    };
    
    this.showEventCartDetail = function(e, _event_name, index, _type, _is_outside = false, _is_show_btn = true){
	if($this.is_show_error_block){$this.is_show_error_block = false;return;}
	let item = $this.event_cart_data[_event_name][_type][index];
	
	let errors = '';
	let btn_apply = '';
	
	let $item = $jq(e).parents('.fhs-event-promo-item');
	let $progress = '';
	if(!item['is_expired']){
	    $progress = $item.find('.fhs-event-promo-item-progress-bar');
	    if(item['error']){
		Object.keys(item['error']).forEach(function (key) {
		    errors +='<div class="fhs-event-promo-error">* '+item['error'][key]['message']+'</div>';
		});
	    }
	    switch(item['event_type']){
		//coupon - yellow
		case 1: 
		case 5: 
		//coupon freeship - green
		case 4:
		case 6:
		    if (item['applied']){
			btn_apply = '<button type="button" onclick="fhs_promotion.applyCoupon(this);" title="'+$this.languages['cancel_apply']+'" coupon="'+item['coupon_code']+'" apply="0" class="btn-close-popup-event fhs-btn-view-promo-detail-coupon applied"><span>'+$this.languages['cancel_apply']+'</span></button>';
		    }else{
			if(item['matched']){
			    btn_apply = '<button type="button" onclick="fhs_promotion.applyCoupon(this);" title="'+$this.languages['apply']+'" coupon="'+item['coupon_code']+'" apply="1" class="btn-close-popup-event fhs-btn-view-promo-detail-coupon" ><span>'+$this.languages['apply']+'</span></button>';
			}else{
			    btn_apply = '<a href="/'+item['page_detail']+'"><button type="button" title="'+$this.languages['buy_more']+'" class="btn-close-popup-event fhs-btn-view-promo"><span>'+$this.languages['buy_more']+'</span></button></a>';
			}
		    }
		    break;
		case 3: //payment - blue
		    if(item['matched']){
			if(!fhs_account.isEmpty(item['coupon_code'])){
			    btn_apply = '<button type="button" onclick="fhs_account.copyCouponCode(\''+item['coupon_code']+'\');" title="'+$this.languages['copy_code']+'" coupon="'+item['coupon_code']+'" class="btn-close-popup-event fhs-btn-view-promo-detail-coupon" ><span>'+$this.languages['copy_code']+'</span></button>';
			}
		    }else{
			btn_apply = '<a href="/'+item['page_detail']+'"><button type="button" title="'+$this.languages['buy_more']+'" class="btn-close-popup-event fhs-btn-view-promo"><span>'+$this.languages['buy_more']+'</span></button></a>';
		    }
		    break;
		default: //other - purple
		    if(item['matched']){
			//btn_apply = '<button type="button" onclick="fhs_promotion.applyCoupon(this);" title="'+$this.languages['apply']+'" coupon="'+item['coupon_code']+'" apply="1" class="btn-close-popup-event fhs-btn-view-promo-detail-coupon" ><span>'+$this.languages['apply']+'</span></button>';
		    }else{
			btn_apply = '<a href="/'+item['page_detail']+'"><button type="button" title="'+$this.languages['buy_more']+'" class="btn-close-popup-event fhs-btn-view-promo-detail-gift"><span>'+$this.languages['buy_more']+'</span></button></a>';
		    }
	    }
	}
	
	$jq('#fhs-event-promo-list-detail-content').html('');
	if(item['rule_content']){
	    $jq('#fhs-event-promo-list-detail-content').html(item['rule_content']);
	}else{
	    let title = '<div class="fhs-event-promo-list-item-content-title">'+item['title']+'</div>';
	    let description = '';
	    if(item['title_2']){
		description = "<div class='fhs-event-promo-list-item-content-description'>"+item['title_2']+'</div>';
	    }
	    $jq('#fhs-event-promo-list-detail-content').html(title+description);
	}
	if($progress.length > 0){
	    let $progress_item = $progress.clone();
	    if($progress_item.html()){
		$progress_item.appendTo($jq('#fhs-event-promo-list-detail-content'));
	    }
	}
	if(errors){
	    $jq('#fhs-event-promo-list-detail-content').append(errors);
	}
	
	if(btn_apply && _is_show_btn){
	    $jq('#popup_event_cart_detail_close').html(btn_apply);
	    $jq('#popup-loading-event-cart').addClass('popup-loading-event-cart_hasbottom');
	}else{
	    $jq('#popup_event_cart_detail_close').html('');
	    $jq('#popup-loading-event-cart').removeClass('popup-loading-event-cart_hasbottom');
	}
	$jq('#popup_event_cart_info_close').fadeOut(0);
	$jq('#popup_event_cart_detail_close').css("display", "flex").hide().fadeIn(0);
	
	$jq('.popup-loading-event-cart-info').fadeOut(0);
	$jq('.popup-loading-event-cart-detail').fadeIn(0);
	
	if(_is_outside){
	    $jq('.popup-loading-event-cart-detail .fhs-event-promo-title-left').fadeOut(0);
	    $jq('.youama-ajaxlogin-cover').fadeIn(0);
	    $jq('#popup-loading-event-cart').fadeIn(10,function(){$jq("#popup-loading-event-cart").focus();});
	}
	fhs_account.sizeCouponBg();
    };
    this.closeEventCartDetail = function(){
	$jq('#popup-loading-event-cart').removeClass('popup-loading-event-cart_hasbottom');
	
	$jq('.popup-loading-event-cart-detail .fhs-event-promo-title-left').fadeIn(0);
	$jq('#popup_event_cart_detail_close').fadeOut(0);
	$jq('#popup_event_cart_info_close').fadeIn(0);
	$jq('.popup-loading-event-cart-detail').fadeOut(0);
	$jq('.popup-loading-event-cart-info').fadeIn(0);
	fhs_account.sizeCouponBg();
    };
    this.showEventCart = function(){
	$this.closeEventCartDetail();
	$jq('.youama-ajaxlogin-cover').fadeIn();
	$jq('#popup-loading-event-cart').fadeIn(10,function(){$jq("#popup-loading-event-cart").focus();});
	$this.sizeByCouponBlock();
//	if(!$this.is_cart){
//	    fhs_onestepcheckout.getWalletVoucher();
//	}
    };
    this.closeEventCart = function(){
	$this.closeEventCartDetail();
	$jq('#popup-loading-event-cart').fadeOut(0);
	$jq('.youama-ajaxlogin-cover').fadeOut(0);
	$this.sizeByCouponBlock();
    };
    this.showEventCartContentDetail = function(e){
	let $item = $jq(e).parents('.fhs-event-promo-item');
	if($item.length > 0){
	    let $clicker = $item.find('.fhs-event-promo-list-item-detail');
	    if($clicker.length > 0){
		$clicker.trigger('click');
	    }
	}
    };
    $this.showEventCartErrorBlock = function(e){
	let $item = $jq(e).parent().find('.fhs-event-promo-error-block');
	if($item.length > 0){
	    $jq(e).fadeOut(0);
	    $item.css('height','auto');
	    $this.is_show_error_block = true;
	}
    };
    this.applyCoupon = function(e){
	let coupon_code = $jq(e).attr('coupon');
	let apply = $jq(e).attr('apply');
	$this.setCoupon($jq('#fhs_checkout_coupon'), coupon_code.trim(), apply, $jq(e));
	
	if($jq('.popup-loading-event-cart-detail .popup-loading-event-cart-content').is(":visible")){
	    $this.closeEventCartDetail();
	}
    };
    
    //commom
    this.sizeByCouponBlock = function(){
	if($jq('.coupon_bg').is(':visible')){
	    let coupon_block = $jq('.popup-loading-event-cart-coupon').height();
	    if(coupon_block < 0 || !$jq('.popup-loading-event-cart-coupon').is(":visible")){
		coupon_block = 55;
	    }else{
		coupon_block = coupon_block + 55;
	    }
	    $jq('.popup-loading-event-cart-info .popup-loading-event-cart-content').css('height','calc(100% - '+coupon_block+"px)");
	    fhs_account.sizeCouponBg();
	}
    };
}

