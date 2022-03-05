const ExpectedDelivery = function () {
    let GET_EXPECTED_ADDRESS_URL = "/fahasa_catalog/product/getExpectedAddress";
    let SAVE_EXPECTED_ADDRESS_URL = "/fahasa_catalog/product/saveExpectedAddress";
    
    let district_Json = {};
    let ward_Json = {};
    let languages = {};
    let sku = 0;
    let province = "";
    let district = "";
    let ward = "";
    let province_id = 0;
    let district_id = 0;
    let ward_id = 0;
    let is_loading = false;
    let changed = false;
    let expected_delivery_data = [];
    
    var $this = this;
    
    this.initExpectedDelivery = function (_sku, _city_Json, _ward_Json, _languages) {
	$this.sku = _sku;
	$this.district_Json = _city_Json;
	$this.ward_Json = _ward_Json;
	$this.languages = _languages;
	$this.changed = false;
	$this.expected_delivery_data = [];
	$this.getExpectedAddress();
	$jq('.expected_address_option_item').change(function(){
	    if($jq(this).val() <= 0){
		$this.disableSelectbox(true,true,true);
	    }else{
		$this.disableSelectbox(false,false,false);
	    }
	});
	$jq('.popup-fahasa-default-alert-content-select').click(function(){
	    if($jq(this).val() <= 0){
		$jq('#expected_address_option_item_1').prop('checked',true);
		$this.disableSelectbox(false,false,false);
	    }
	});
	
	$jq('#expected-address-select-region').change(function(){
	    $this.province_id = $jq(this).val();
	    $this.province = $jq(this).children("option:selected").html();
	    $this.changed = true;
	    $this.province_change();
	});
	$jq('#expected-address-select-city').change(function(){
	    $this.district_id = $jq(this).val();
	    $this.district = $jq(this).children("option:selected").html();
	    $this.changed = true;
	    $this.district_change();
	});
	$jq('#expected-address-select-ward').change(function(){
	    $this.ward_id = $jq(this).val();
	    $this.ward = $jq(this).children("option:selected").html();
	    $this.changed = true;
	    $this.ward_change();
	});
	$jq('.popup-expected-address-confirm-btn-confirm').click(function(){
	    $this.save_click();
	});
	$jq('.popup-expected-address-confirm-btn-back').click(function(){
	    $this.closeExpectedAddress();
	});
    };
    this.getExpectedDeliveryBySku = function(sku){
	$jq('.fhs_event_delivery_label_icon').fadeOut(0);
	if($this.expected_delivery_data[sku]){
	    let data = $this.expected_delivery_data[sku];
	    if((data['province'] == $this.province)
		&&(data['district'] == $this.district)
		&&(data['ward'] == $this.ward)
		&&(data['province_id'] == $this.province_id)
		&&(data['district_id'] == $this.district_id)
		&&(data['ward_id'] == $this.ward_id)){
		$this.processExpectedAddressRespone(sku,data,true,true);
	    }else{
		$this.getExpectedAddress(sku);
	    }
	}else{
	    $this.getExpectedAddress(sku);
	}
    }
    this.getExpectedAddress = function(sku = ''){
	if(fhs_account.isEmpty(sku)){
	    sku = $this.sku;
	}
	$jq.ajax({
	    url: GET_EXPECTED_ADDRESS_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {sku: sku},
	    error: function() {},
	    success: function (data) {
		$jq('.fhs_event_delivery_label_icon').fadeOut(0);
		if(data['success']){
		    $this.processExpectedAddressRespone(sku,data);
		}
	    }
	});
    };
    
    this.saveExpectedAddress = function(){
	if(is_loading){return;}
	is_loading = true;
	$this.showLoadingAnimation();
	$jq.ajax({
	    url: SAVE_EXPECTED_ADDRESS_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: { province: $this.province, district: $this.district, ward: $this.ward, province_id: $this.province_id, district_id: $this.district_id, ward_id: $this.ward_id, sku: $this.sku},
	    error: function() {
		is_loading = false;
		$this.hideLoadingAnimation();
	    },
	    success: function (data) {
		$jq('.fhs_event_delivery_label_icon').fadeOut(0);
		if(data['success']){
		    $this.processExpectedAddressRespone($this.sku,data,false);
		    $this.closeExpectedAddress();
		    $this.hideLoadingAnimation();
		}
		else{
		    $this.hideLoadingAnimation();
		}
		is_loading = false;
	    }
	});
    };
    
    this.displayEventDelivery = function(data, has_address){
	let img_str = '';
	Object.keys(data).forEach(function(key){
	    if(fhs_account.isEmpty(img_str)){
		let item = data[key];
		if(!fhs_account.isEmpty(item)){
		    if(item['is_show_icon']){
			if(!has_address){
			    if(!fhs_account.isEmpty(item['icon_path'])){
				if(!fhs_account.isEmpty(item['page_detail'])){
				    img_str = '<img class="fhs_mouse_point" onclick="location.href=\''+item['page_detail']+'\'" src="'+item['icon_path']+'"/>';
				}else{
				    img_str = '<img src="'+item['icon_path']+'"/>';
				}
			    }
			}else{
			    if(item['enable']){
				if(!fhs_account.isEmpty(item['icon_path'])){
				    if(!fhs_account.isEmpty(item['page_detail'])){
					img_str = '<img class="fhs_mouse_point" onclick="location.href=\''+item['page_detail']+'\'" src="'+item['icon_path']+'"/>';
				    }else{
					img_str = '<img src="'+item['icon_path']+'"/>';
				    }
				}
			    }
			}
		    }
		    
		    if(!fhs_account.isEmpty(img_str)){
			$jq('.fhs_event_delivery_label_icon').html(img_str);
			$jq('.fhs_event_delivery_label_icon').fadeIn(0);
		    }
		}
	    }
	});
    }
    
    this.processExpectedAddressRespone = function(sku, data, is_get_func = true, is_saved = false){
	if(!is_saved){
	    data['province'] = $this.province;
	    data['district'] = $this.district;
	    data['ward'] = $this.ward;
	    data['province_id'] = $this.province_id;
	    data['district_id'] = $this.district_id;
	    data['ward_id'] = $this.ward_id;
	    $this.expected_delivery_data[sku] = data;
	}
	let address = "";
	let estimatedTimeDelivery = "";
	let fpoint = "";
	try{
	    let expected_delivery = data['expected_delivery'];
	    if(expected_delivery['estimatedTimeDelivery']){
		if(address){
		    estimatedTimeDelivery = expected_delivery['estimatedTimeDelivery'];
		}
		fpoint = data['expected_delivery']['fpoint'];
	    }
	    if(expected_delivery['fpoint']){
		fpoint = data['expected_delivery']['fpoint'];
	    }
	    let address_data = data['address'];
	    if(address_data['ward']){
		address = address_data['ward'];
	    }
	    if(address_data['district']){
		if(address){
		    address = address+", "+address_data['district'];
		}else{
		    address = address_data['district'];
		}
	    }
	    if(address_data['province']){
		if(address){
		    address = address + ", " + address_data['province'];
		}else{
		    address = address_data['province'];
		}
	    }
	    if(expected_delivery['estimatedTimeDelivery']){
		if(address){
		    estimatedTimeDelivery = expected_delivery['estimatedTimeDelivery'];
		}
	    }
	}catch(ex){}

	try{
	    if(!fhs_account.isEmpty(data['event_delivery'])){
		let has_address = false;
		if(data['address']){
		    has_address = true;
		}

		$this.displayEventDelivery(data['event_delivery'], has_address);
	    }
	}catch(ex){}

	if(fpoint){
	    $jq('#expected_delivery_fpoint_content').html(fpoint + "&nbsp;F-Point");
	    $jq('#expected_delivery_fpoint').fadeIn();
	}
	if(is_get_func){
	    $jq('#expected_delivery_address').fadeIn();
	    if(address){
		$jq('#expected_delivery_address_content').html(address);
		$jq('#expected_address_default_content').html(address);
		$jq('#expected_address_default').fadeIn();
		$jq('#expected_address_option_item_0').prop('checked',true);
		$this.disableSelectbox(true,true,true);
	    }
	    if(estimatedTimeDelivery){
		$jq('#expected_delivery_fpoint_time_content').html(estimatedTimeDelivery);
		$jq('#expected_delivery_fpoint_time').fadeIn();
	    }
	}else{
	    if(address){
		$jq('#expected_delivery_address_content').html(address);
		$jq('#expected_address_default_content').html(address);
		$jq('#expected_address_default').fadeIn();
		$jq('#expected_address_option_item_0').prop('checked',true);
		$this.disableSelectbox(true,true,true);
	    }else{
		$jq('#expected_delivery_address_content').html("");
		$jq('#expected_address_default_content').html("");
		$jq('#expected_address_default').fadeOut();
	    }
	    if(estimatedTimeDelivery){
		$jq('#expected_delivery_fpoint_time_content').html(estimatedTimeDelivery);
		$jq('#expected_delivery_fpoint_time').fadeIn();
	    }
	    else{
		$jq('#expected_delivery_fpoint_time').fadeOut();
		$jq('#expected_delivery_fpoint_time_content').html("");
	    }
	}
    }
    
    this.province_change = function(){
	let options = "<option value='' selected>"+$this.languages['choose_district']+"</option>";
	if($this.province_id != 0){
	    let districts = $this.district_Json[$this.province_id];
	    Object.keys(districts).forEach(function(key){
		var district = districts[key];
		let district_option = "<option value='"+key+"'>"+district['name']+"</option>";
		options += district_option;
	    });
	}
	$jq('#expected-address-select-city').empty().html(options);
	let placeholder = "<span class='select2-selection__placeholder'>"+$this.languages['choose_district']+"</span>";
	$jq('#select2-expected-address-select-city-container').html(placeholder);
	
	$this.district_id = 0;
	$this.district = "";
	$this.district_change();
    };
    this.district_change = function(){
	let options = "<option value='' selected>"+$this.languages['choose_wards']+"</option>";
	if($this.district_id != 0){
	    let wards = $this.ward_Json[$this.district_id];
	    Object.keys(wards).forEach(function(key){
		var ward = wards[key];
		let ward_option = "<option value='"+key+"'>"+ward['name']+"</option>";
		options += ward_option;
	    });
	}
	$jq('#expected-address-select-ward').empty().html(options);
	let placeholder = "<span class='select2-selection__placeholder'>"+$this.languages['choose_wards']+"</span>";
	$jq('#select2-expected-address-select-ward-container').html(placeholder);
	
	$this.ward_id = 0;
	$this.ward = "";
	$this.ward_change();
    };
    this.ward_change = function(){
	$this.disableSelectbox(false,false,false);
    };
    this.save_click = function(){
	let expected_address_option_item_1 = $jq('#expected_address_option_item_1').prop('checked');
	if($this.changed && $this.sku && $this.province && $this.district && $this.ward && $this.province_id && $this.district_id && $this.ward_id && expected_address_option_item_1){
	    $this.saveExpectedAddress();
	}
    };
    
    this.openExpectedAddress = function(){
	$jq('#popup-expected-address-cover').fadeIn();
	$jq('#popup-expected-address').fadeIn();
    };
    this.closeExpectedAddress = function(){
	$jq('#popup-expected-address').fadeOut();
	$jq('#popup-expected-address-cover').fadeOut();
    };
    this.hideLoadingAnimation = function () {
	$jq('.loadding_ajaxcart,#wraper_ajax,.wrapper_box').remove();
    };
    this.showLoadingAnimation = function (){
	var loading_bg = $jq('#ajaxconfig_info button').attr('name');
	var opacity = $jq('#ajaxconfig_info button').attr('value');
	var loading_image = $jq('#ajaxconfig_info img').attr('src');
	var style_wrapper =  "position: fixed;top:0;left:0;filter: alpha(opacity=70); z-index:99999;background-color:"+loading_bg+"; width:100%;height:100%;opacity:"+opacity+"";
	var loading = '<div id ="wraper_ajax" style ="'+style_wrapper+'" ><div  class ="loadding_ajaxcart" style ="z-index:999999;position:fixed; top:50%; left:50%;"><img src="'+loading_image+'"/></div></div>';
	if($jq('#wraper_ajax').length==0) {
	    $jq('body').append(loading);
	}
    };
    this.disableSelectbox = function(is_disable_province, is_disable_district, is_disable_ward){
	//province
	if(is_disable_province){
	    $jq('#expected-address-select-region').attr('disabled', 'disabled');
	}else{
	    $jq('#expected-address-select-region').removeAttr('disabled', 'disabled');
	}
	
	//district
	if($jq('#expected-address-select-city').children('option').length <= 1){
	    is_disable_district = true;
	}
	if(is_disable_district){
	    $jq('#expected-address-select-city').attr('disabled', 'disabled');
	}else{
	    $jq('#expected-address-select-city').removeAttr('disabled', 'disabled');
	}
	
	//ward
	if($jq('#expected-address-select-ward').children('option').length <= 1){
	    is_disable_ward = true;
	}
	if(is_disable_ward){
	    $jq('#expected-address-select-ward').attr('disabled', 'disabled');
	}else{
	    $jq('#expected-address-select-ward').removeAttr('disabled', 'disabled');
	}
	
	//save btn
	let expected_address_option_item_1 = $jq('#expected_address_option_item_1').prop('checked');
	if($this.province_id && $this.district_id && $this.ward_id && expected_address_option_item_1){
	    $jq('.popup-expected-address-confirm-btn-confirm').removeAttr('disabled','disabled');
	}else{
	    $jq('.popup-expected-address-confirm-btn-confirm').attr('disabled','disabled');
	}
    }
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