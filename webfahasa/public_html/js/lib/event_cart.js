
const EventCart = function () {
    let is_first = true;
    let action_type = "";
    let has_option_active = false;
    let is_loading = false;
    let page_detail = "#";
    $event_cart = $jq('#event_cart');
    $event_cart_content = $jq('.event_cart_content');
    $event_cart_header = $jq('.event_cart_header');
    $event_cart_resutl = $jq('.event_cart_resutl');
    $event_cart_resutl_title = $jq('.event_cart_resutl_title');
    $event_cart_resutl_content = $jq('.event_cart_resutl_content');
    $event_cart_footer = $jq('.event_cart_footer');
    $event_cart_data = $jq('#event_cart_data');
    $event_cart_notificate = $jq('.event_cart_notificate');
    $fahasa_dialog_content_list = $jq('.fahasa_dialog_content_list');
    $fahasa_dialog_wrapper = $jq('.fahasa_dialog_wrapper');
    $btn_confirm = $jq('.fahasa_dialog_footer_btn.confirm');
    $event_cart_cancel = $jq('.event_cart_cancel');
    $event_cart_cancel_btn = $jq('.event_cart_cancel_btn');
    
    let div_clear = "<div class='clear'></div>";
    
    var $this = this;
    
    this.loadEventCart = function(){
	if($this.is_loading){return;}
	$this.is_loading = true;
	if(!$event_cart.hasClass("event_cart_loading")){
	    $event_cart.addClass("event_cart_loading");
	}
	
	$jq.ajax({
	    url: '/eventcart/index/check',
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    error: function() {
		$this.is_loading = false;
		if($event_cart.hasClass("event_cart_loading")){
		    $event_cart.removeClass("event_cart_loading");
		}
	    },
	    success: function (data) {
		if(data['success']){
		    if(data['events'][0]){
			$this.displayEventCart(data['events'][0]);
		    }else{
			$this.clearEventCart();
		    }
		}else{
		    $this.clearEventCart();
		}
		
		$this.is_loading = false;
		if($event_cart.hasClass("event_cart_loading")){
		    $event_cart.removeClass("event_cart_loading");
		}
	    }
	});
    };
    this.displayEventCart = function(data){
	try{
	    setTimeout(function(){eventcart.displayNotificationPopup(data['error']);}, 100);
	    
	    $this.action_type = data['action_type'];
	    let form_ui = data['form_ui'];
	    if(form_ui){
		let header_title = "<span class='event_cart_header_title'>"+form_ui['header']+"</span>"
		if(form_ui['header_icon']){
		    header_title += "<img src='"+form_ui['header_icon']+"' />"
		}
		if(form_ui['header_background']){
		    $event_cart_header.css("background-color", form_ui['header_background'])
		}
		if(form_ui['page_detail']){
		    page_detail = form_ui['page_detail'];
		}
		$event_cart_header.html(header_title);
		
		if(form_ui['content']){
		    let form_ui_content = form_ui['content'];
		    Object.keys(form_ui_content).forEach(function(key){
			let type = form_ui_content[key]['type'];
			let title = form_ui_content[key]['data'];
			if(type == "shipping"){
			    $event_cart_resutl_title.text(title);
			}
			if(type == "text"){
			    $event_cart_footer.html(title);
			    $event_cart_footer.fadeIn();
			}
		    });
		}
		if(form_ui['showCancelBtn']){
		    $event_cart_cancel.fadeIn();
		    $event_cart_cancel_btn.val(form_ui['cancelBtnTitle']);
		}
	    }
	    let content = ""
	    
	    //show ranks
	    let ranks = data['rank'];
	    
	    //check default value
	    $this.checkDefaultValue(ranks);
	    
	    //render ranks option
	    Object.keys(ranks).forEach(function(key){
		content += $this.displayRankHTML(ranks[key]);
	    });
	    
	    if($this.has_option_active){
		$this.syn_shipping_address();
		if(fhs_account.isEmpty($event_cart_resutl_title.val())){
		    $event_cart_resutl.fadeOut();
		}else{
		    $event_cart_resutl.fadeIn();
		}
	    }else{
		$event_cart_resutl.fadeOut();
	    }
	    
	    is_first = false;
	    $event_cart_content.html(content);
	    $this.triggerEvent();
	    $event_cart.fadeIn();
	}catch(ex){$this.clearEventCart(); console.log(ex.message);}
    };
    
    this.checkDefaultValue = function(ranks){
	//check value default and have option active
	let is_march_value = false;
	$this.has_option_active = false;
	    
	Object.keys(ranks).forEach(function(key){
	    let rank = ranks[key];
	    let options = rank['options'];
	    Object.keys(options).forEach(function(key){
		if(options[key]['active']){
		    if(!is_first){
			if(options[key]['option_id'] == $event_cart_data.val()){
			    is_march_value = true;
			}
		    }
		    if(options[key]['option_id'] != 0){
			$this.has_option_active = true;
		    }
		}
	    });
	    //set default value
	    if((!is_first && !is_march_value)||(!$this.has_option_active)){
		$event_cart_data.val(0);
	    }
	});
	    
	    
    }
    this.displayRankHTML = function(rank){
	let result = "";
	try{
	    let options = rank['options'];
	    result = "<div class='event_cart_content_title'>"+rank['title']+"</div>"
	    //set visable option
	    Object.keys(options).forEach(function(key){
		let option_visable = "";
		let option_check = "";
		if(options[key]['active']){
		    if(is_first){
			if(options[key]['default']){
			    option_check = "checked=\'checked\'";
			}
		    }else{
			if(options[key]['option_id'] == $event_cart_data.val()){
			    option_check = "checked=\'checked\'";
			}
		    }
		}else{
		    if(options[key]['option_id'] == 0){
			if(!$this.has_option_active){
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
    
    this.triggerEvent = function(){
	$jq('.event_cart_content_option_item').change(function(){
	    $event_cart_data.val($jq(this).val());
	});
	if($this.action_type == "delivery_time"){
	    Event.observe('billing:ward_id', 'change', function() {
		$this.syn_shipping_address();
	    });
	    Event.observe('billing:street1', 'change', function() {
		$this.syn_shipping_address();
	    });
	    Event.observe('shipping:ward_id', 'change', function() {
		$this.syn_shipping_address();
	    });
	    Event.observe('shipping:street1', 'change', function() {
		$this.syn_shipping_address();
	    });
	}
    };
    this.clearEventCart = function(data){
	try{
	    $this.action_type = "";
	    $event_cart_data.val(0);
	    $event_cart.fadeOut();
	}catch(ex){console.log(ex.message);}
    };
    this.syn_shipping_address = function(){
	try{
	    if($this.action_type == "delivery_time"){
		let ck_different_shipping = $('shipping:different_shipping');

		if(ck_different_shipping == null || !ck_different_shipping.checked){
		    let billing_address_id = 'New Address';
		    if($('billing-address-select')){
			let bIndex = $('billing-address-select').selectedIndex;
			billing_address_id = $('billing-address-select').options[bIndex].value;
		    }
		    if((billing_address_id != 'New Address') && (billing_address_id != 'Địa chỉ mới') && (billing_address_id)){
			
			
			for (customer_address_index in billing_customer_address_list) {
			    customer_address = billing_customer_address_list[customer_address_index];
			    if(billing_address_id == customer_address.value){
				$event_cart_resutl_content.text(customer_address.label);
				return;
			    }
			}
		    }
		    else{
			let country = $('billing:country_id').options[$('billing:country_id').selectedIndex].text;
			let region = '';
			if($('billing:region_id').getStyle('display') === 'none'){
			    region = $('billing:region').value;
			}else{
			    region =  $('billing:region_id').options[$('billing:region_id').selectedIndex].text;
			}
			let city = $('billing:city').value;
			let ward = $('billing:ward').value;
			let street = $('billing:street1').value;
			let address = (street?street:'')
				+(ward?(", "+ward):'')
				+(city?(", "+city):'')
				+(region?(", "+region):'') 
				+(country?(", "+country):'');
			$event_cart_resutl_content.text(address);
		    }
		}
		else{
		    let shipping_address_id = 'New Address';
		    if($('billing-address-select')){
			let sIndex = $('shipping-address-select').selectedIndex;
			shipping_address_id = $('shipping-address-select').options[sIndex].value;
		    }
		    if((shipping_address_id != 'New Address') && (shipping_address_id != 'Địa chỉ mới') && (shipping_address_id)){
			for (customer_address_index in billing_customer_address_list) {
			    customer_address = billing_customer_address_list[customer_address_index];
			    if(shipping_address_id == customer_address.value){
				$event_cart_resutl_content.text(customer_address.label);
				return;
			    }
			}
		    }else{
			let country = $('shipping:country_id').options[$('shipping:country_id').selectedIndex].text;
			let region = '';
			if($('shipping:region_id').getStyle('display') === 'none'){
			    region = $('shipping:region').value;
			}else{
			    region =  $('shipping:region_id').options[$('shipping:region_id').selectedIndex].text;
			}
			let city = $('shipping:city').value;
			let ward = $('shipping:ward').value;
			let street = $('shipping:street1').value;
			let address = (street?street:'')
				+(ward?(", "+ward):'')
				+(city?(", "+city):'')
				+(region?(", "+region):'') 
				+(country?(", "+country):'');
			$event_cart_resutl_content.text(address);
		    }
		}
	    }
	}catch(ex){console.log(ex.message);}
    };
    this.onclickConfirmButton = function(){
	location.href= page_detail;
    };
    this.openNotificationPopup = function(){
	$jq('#fahasa_dialog_wrapper-cover').fadeIn();
	$jq('.fahasa_dialog_wrapper').fadeIn();
    };
    this.clearChoice = function(){
	$event_cart_data.val(0);
	$jq('.event_cart_content_option_item').removeAttr('checked');
    };
    
    this.displayNotificationPopup = function(errors){
	try{
	    let content = "";
	    let product_list = 0;
	    Object.keys(errors).forEach(function(key){
		if(errors[key]['products']){
		    product_list = errors[key];
		}else{
		    content += "<li>"+errors[key]['message']+"</li>";
		}
	    });
	    
	    if(product_list != 0){
		content += "<li>"+product_list['message']
				    +"<ul class='fahasa_dialog_content_list_product'>";
		Object.keys(product_list['products']).forEach(function(key){
		    content += "<li>"
				    +"<div class='fahasa_dialog_content_list_product_info'>"
					+"<div class='fahasa_dialog_content_list_product_info_img'><img src='"+product_list['products'][key]['image']+"'/></div>"
					+"<div class='fahasa_dialog_content_list_product_info_detail'>"
					    +"<div class='fahasa_dialog_content_list_product_info_detail-name'>"+product_list['products'][key]['name']+product_list['products'][key]['name']+"</div>"
					    +"<div class='fahasa_dialog_content_list_product_info_detail-price'>"+product_list['products'][key]['price']+"</div>"
					    +"<div class='fahasa_dialog_content_list_product_info_detail-qt'>Số lượng: "+product_list['products'][key]['qty']+"</div>"
					+"</div>"
					+"<div class='clear'></div>"
				    +"</div>"
				+"</li>";
		});
		content += "</ul></li>";
	    }
	    $fahasa_dialog_content_list.html(content);
	    if(!$fahasa_dialog_wrapper.hasClass("fahasa_dialog_wrapper_over")){
		$fahasa_dialog_wrapper.removeClass("fahasa_dialog_wrapper_over");
	    }
	    $event_cart_notificate.fadeOut();
	    if(product_list == 0 && errors.size() == 1){
		$event_cart_notificate.html(errors[0]['message']);
		$event_cart_notificate.fadeIn();
	    }else if(product_list != 0 && errors.size() == 1){
		if(product_list != 0){
		    if(product_list['products'].length > 2){
			if(!$fahasa_dialog_wrapper.hasClass("fahasa_dialog_wrapper_over")){
			    $fahasa_dialog_wrapper.addClass("fahasa_dialog_wrapper_over");
			}
		    }
		}
		$event_cart_notificate.html(errors[0]['message']+": <a onclick='eventcart.openNotificationPopup();'>Bấm xem chi tiết</a>");
		$event_cart_notificate.fadeIn();
	    }else if(errors.size() > 1){
		if(product_list != 0){
		    if(product_list['products'].length > 2){
			if(!$fahasa_dialog_wrapper.hasClass("fahasa_dialog_wrapper_over")){
			    $fahasa_dialog_wrapper.addClass("fahasa_dialog_wrapper_over");
			}
		    }
		}
		$event_cart_notificate.html("Giỏ hàng của bạn không thỏa điều kiện của chương trình: <a onclick='eventcart.openNotificationPopup();'>Bấm xem chi tiết</a>");
		$event_cart_notificate.fadeIn();
	    }
	}catch(ex){console.log(ex.message);}
    };
    this.isEmpty = function (obj){
	try{
	    for(var key in obj) {
		if(obj.hasOwnProperty(key))
		    return false;
	    }
	}catch(ex){}
	return true;
    };
}