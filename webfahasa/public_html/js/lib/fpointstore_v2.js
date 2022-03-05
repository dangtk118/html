
const FpointStoreV2 = function () {
    const GIFTS_AJAX_URL = "/fpointstore/index/getGiftList";
    const HISTORY_GIFTS_AJAX_URL = "/fpointstore/index/getVoucherHistoryList";
    const CHANGE_GIFT_AJAX_URL = "/fpointstore/index/changeGift";
    const RESULT_GIFT_AJAX_URL = "/fpointstore/index/getResultQueue";
    const CHANGE_VIP_AJAX_URL = "/fpointstore/index/setVIP";
    var media_url = "/media";
    var skin_url = "/skin";
    
    var fpoint = 0;
    var gift_fpoint = 0;
    var category_id = 0;
    var page_current = 1;
    var language = {};
    var data = {};
    
    const TIME_REQUEST_QUEUE = 2000;
    let time_loaded = 0;
    const QUEUE_TIME_LIMIT_TO_TRY = 5;
    var is_loading = false;
    var is_trying = false;
    var is_loading_next_page = false;
    var gift_queue_id = 0;
    
    var $this = this;
    
    $fpointstore_menu_item = $jq(".fpointstore-menu-item");
    $fpointstore_grid = $jq("#fpointstore-grid");
    $fpointstore_info = $jq("#fpointstore_info");
    $vip_info = $jq(".vip-info");
    $txt_vip_code_info = $jq("#txt_vip_code_info");
    $btn_voucher_confirm = $jq("#btn_voucher_confirm");
    $fpointstore_history_grid = $jq("#history_voucher_grid");
    
    // init
    this.initGiftPage = function (_fpoint, _cat_id, _page_current, _over, _media_url, _language) {
	$this.is_loading = false;
	$this.is_loading_next_page = false;
	$this.fpoint = _fpoint;
	$this.category_id = _cat_id;
	$this.page_current = _page_current;
	$this.media_url = _media_url;
	$this.language = _language;
	var gifts_store = {};
	gifts_store['over'] = _over;
	gifts_store['p'] = _page_current;
	gifts_store['html'] = $fpointstore_grid.html();
	$this.data = {}; 
	$this.data[_cat_id] = gifts_store;
	$jq(document).on('scroll', function() {
	    var hT = $jq('#fpointstore_bottom').offset().top,
		hH = $jq('#fpointstore_bottom').outerHeight(),
		wH = $jq(window).height(),
		wS = $jq(this).scrollTop();
		if (wS > (hT+hH-wH)){
		    $this.loadNextPage();
		}
	});
	$txt_vip_code_info.keypress(function(event) {
	if (event.which == 13) {
	    $this.setVIP();
	}
      });
    };
    this.initGiftDetail = function (_skin_url, _gift_fpoint, _language) {
	$this.skin_url = _skin_url;
	$this.gift_fpoint = _gift_fpoint;
	$this.language = _language;
	$jq('.panel-voucher-collapse').click(function(){
	    if($jq(this).hasClass('collapsed')){
		let id = $jq(this).attr('data-id');
		$jq('html, body').stop().animate({
		    scrollTop: $jq('#fpointstore_voucher_info_item_'+id).offset().top
		}, 1000);
	    }
	});
    };
    this.initHistoryGiftPage = function (_over, _media_url, _data, _language) {
	$this.is_loading = false;
	$this.is_loading_next_page = false;
	$this.category_id = 0;
	$this.page_current = 1;
	$this.media_url = _media_url;
	$this.language = _language;
	var gifts_store = {};
	gifts_store['over'] = _over;
	gifts_store['p'] = 1;
	gifts_store['html'] = $fpointstore_history_grid.html();
	gifts_store['data'] = {};
	Object.keys(_data).forEach(function(key){
	    gifts_store['data'][_data[key]['id']] = _data[key];
	});
	$this.data = {}; 
	$this.data[0] = gifts_store;
	$jq(document).on('scroll', function() {
	    if(!$jq(".history-tab-fpoint-item").hasClass("active")){
		var hT = $jq('#fpointstore_bottom').offset().top,
		    hH = $jq('#fpointstore_bottom').outerHeight(),
		    wH = $jq(window).height(),
		    wS = $jq(this).scrollTop();
		    if (wS > (hT+hH-wH)){
			$this.loadHistoryNextPage();
		    }
	    }
	});
    };
    
    // ajax
    this.loadGiftList = function(){
	if($this.is_loading){return;}
	$this.is_loading = true;
	$this.showLoadingAnimation();
	$jq.ajax({
	    url: GIFTS_AJAX_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: { category_id: $this.category_id, currentPage: $this.page_current},
	    fail: function() {
		$this.is_loading = false;
		$this.is_loading_next_page = false;
	    },
	    success: function (data) {
		if(data['success']){
		    if((data['result'].length <= 0)||(data['over'])){
			var gifts_store = $this.data[$this.category_id];
			gifts_store['over'] = true;
		    }
		    $this.DisplayGifts(data['result']);
		    $this.hideLoadingAnimation();
		}
		else{
		    $this.hideLoadingAnimation();
		}
		$this.is_loading = false;
		$this.is_loading_next_page = false;
	    }
	});
    };
    this.changeGift = function (_is_combo, _id) {
	if($this.is_loading){return;}
	$this.is_loading = true;
	$jq.ajax({
	    url: CHANGE_GIFT_AJAX_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: { is_combo: _is_combo, id: _id},
	    loading: function(){
	    },
	    fail: function() {
		$jq('#popup-fpointstore-confirm').fadeOut();
		$this.hideLoadingAnimation();
		$this.is_loading = false;
	    },
	    success: function (data) {
		$this.hideLoadingAnimation();
		$jq("#popup-fpointstore-cover").fadeIn();
		if(data['success']){
		    gift_queue_id = data['gift_queue_id'];
		    $jq('#popup-fpointstore-loading-context-text').html($this.language['processing']+"...");
		    $jq('#popup-fpointstore-loading-confirm').hide();
		    $jq('#popup-fpointstore-loading-logo').hide();
		    $jq('#popup-fpointstore-loading-icon').show();
		    $jq('#popup-fpointstore-loading').fadeIn();
		    setTimeout(function(){$this.getResultQueue();}, 100);
		}else{
		    if(data['reason'] == "out_of_voucher"){
			$btn_voucher_confirm.attr("disabled", true);
			$jq(".popup-fahasa-default-alert-confirm").attr("onclick","window.location='/fpointstore';");
			$jq(".popup-fahasa-default-alert-confirm").html('<span>'+$this.language['back']+'</span>');
		    }
		    $jq('#popup-fahasa-alert-logo').html("<center><img class='lazyload' src='"+loading_icon_url+"' data-src='"+$this.skin_url+"frontend/ma_vanese/fahasa/images/logo-alert-fail.png?q="+$this.language['queryfier']+"') /></center>");
		    $jq('#popup-fahasa-default-content-text').html(data['message']);
		    $jq('#popup-fpointstore-alert').fadeIn();
		}
		$jq('#popup-fpointstore-confirm').fadeOut();
		$this.is_loading = false;
	    }
	});
    }
    this.getResultQueue = function () {
	if($this.is_trying){return;}
	$this.is_trying = true;
	$jq.ajax({
	    url: RESULT_GIFT_AJAX_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: { gift_queue_id: gift_queue_id},
	    loading: function(){
	    },
	    fail: function() {
		$this.is_trying = false;
		time_loaded++;
		console.log("time to try="+time_loaded+", loading failured.");
		if(time_loaded < QUEUE_TIME_LIMIT_TO_TRY){
		    setTimeout(function(){$this.getResultQueue();}, 3000);
		}else{
		    $jq('#popup-default-loading-icon').hide();
		    $jq('#popup-default-loading-logo').show();
		    $jq('#popup-default-loading-context-text').html($this.language['timeout']);
		    $jq('#popup-default-loading-confirm').show();
		}
	    },
	    success: function (data) {
		$this.is_trying = false;
		time_loaded++;
		console.log("time to try="+time_loaded+", status="+data['status']);
		if(data['status']){
		    if(data['fpoint'] < $this.gift_fpoint){
			$btn_voucher_confirm.attr("disabled", true);
		    }
		    if(data['success']){
			$jq('#popup-fpointstore-loading').fadeOut();
			$jq('#popup-fpointstore-alert-success').fadeIn();
		    }else{
			switch(data['reason']){
			    case "out_of_voucher":
			    case "out_of_combo":
				$btn_voucher_confirm.attr("disabled", true);
				$jq(".popup-fahasa-default-alert-confirm").attr("onclick","window.location='/fpointstore';");
				$jq(".popup-fahasa-default-alert-confirm").html('<span>'+$this.language['back']+'</span>');
				break;
			    case "out_of_turn":
				$btn_voucher_confirm.attr("disabled", true);
				break;
			}
			$jq('#popup-fpointstore-loading').fadeOut();
			$jq('#popup-fahasa-alert-logo').html("<center><img class='lazyload' src='"+loading_icon_url+"' data-src='"+$this.skin_url+"frontend/ma_vanese/fahasa/images/logo-alert-fail.png?q="+$this.language['queryfier']+"') /></center>");
			$jq('#popup-fahasa-default-content-text').html(data['message']);
			$jq('#popup-fpointstore-alert').fadeIn();
		    }
		    gift_queue_id = 0;
		}else{
		    if(time_loaded < QUEUE_TIME_LIMIT_TO_TRY){
			setTimeout(function(){$this.getResultQueue();}, 3000);
		    }else{
			time_loaded = 0;
			$jq('#popup-fpointstore-loading-icon').hide();
			$jq('#popup-fpointstore-loading-logo').show();
			$jq('#popup-fpointstore-loading-context-text').html($this.language['timeout']);
			$jq('#popup-fpointstore-loading-confirm').show();
		    }
		}
	    }
	});
    }
    this.setVIP = function () {
	if($this.is_loading){return;}
	let company_id = $txt_vip_code_info.val();
	if(!company_id){return;}
	$this.is_loading = true;
	$jq.ajax({
	    url: CHANGE_VIP_AJAX_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: { company_id: company_id},
	    loading: function(){
		$this.showLoadingAnimation();
	    },
	    fail: function() {
		$this.hideLoadingAnimation();
		$this.is_loading = false;
	    },
	    success: function (data) {
		if(data['status']){
		    if(data['success']){
			location.reload(); 
		    }else{
			$txt_vip_code_info.val('');
			$txt_vip_code_info.attr("placeholder", "*"+$this.language['code_error']);
			if(!$txt_vip_code_info.hasClass("code-error")){
			    $txt_vip_code_info.addClass("code-error");
			}
		    }
		}
		$this.is_loading = false;
		$this.hideLoadingAnimation();
	    }
	});
    }
    // ajax History
    this.loadHistoryGiftList = function(){
	if($this.is_loading){return;}
	$this.is_loading = true;
	$this.showLoadingAnimation();
	$jq.ajax({
	    url: HISTORY_GIFTS_AJAX_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: { currentPage: $this.page_current},
	    fail: function() {
		$this.is_loading = false;
		$this.is_loading_next_page = false;
	    },
	    success: function (data) {
		if(data['success']){
		    if((data['result'].length <= 0)||(data['over'])){
			var vouchers_store = $this.data[$this.category_id];
			vouchers_store['over'] = true;
		    }
		    $this.DisplayHistoryVouchers(data['result']);
		    $this.hideLoadingAnimation();
		}
		else{
		    $this.hideLoadingAnimation();
		}
		$this.is_loading = false;
		$this.is_loading_next_page = false;
	    }
	});
    };
    
    // Display
    this.DisplayGifts = function(gifts_data){
	var gifts_str = "";
	Object.keys(gifts_data).forEach(function(key){
		gifts_str += $this.getPageGift(gifts_data[key]);
	});
	
	if($this.page_current == 1){
	    $fpointstore_grid.html(gifts_str);
	    var gifts_store = $this.data[$this.category_id];
	    gifts_store['html'] = gifts_str;
	}else{
	    $fpointstore_grid.append(gifts_str);
	    var gifts_store = $this.data[$this.category_id];
	    gifts_store['html'] += gifts_str;
	}
    };
    this.DisplayHistoryVouchers = function(vouchers_data){
	var vouchers_str = "";
	var gifts_store = $this.data[$this.category_id];
	Object.keys(vouchers_data).forEach(function(key){
	    vouchers_str += $this.getPageVoucher(vouchers_data[key]);
	    gifts_store['data'][vouchers_data[key]['id']] = vouchers_data[key];
	});
	
	if($this.page_current == 1){
	    $fpointstore_history_grid.html(vouchers_str);
	    gifts_store['html'] = vouchers_str;
	}else{
	    $fpointstore_history_grid.append(vouchers_str);
	    gifts_store['html'] += vouchers_str;
	}
    };
    
    // Event
    this.category_click = function (_cat_id){
	if($this.is_loading){return;}
	$this.category_id = _cat_id;
	$fpointstore_menu_item.removeClass('active');
	$jq('#fpointstore_menu_item_'+_cat_id).addClass("active");
	if($this.data[$this.category_id]){
	    var gifts_store = $this.data[$this.category_id];
	    $this.page_current = gifts_store['p'];
	    $fpointstore_grid.html($this.data[$this.category_id]['html']);
	}else{
	    $this.page_current = 1;
	    var gifts_store = {};
	    gifts_store['over'] = false;
	    gifts_store['p'] = 1;
	    gifts_store['html'] = "";
	    $this.data[$this.category_id] = gifts_store;
	    $this.loadGiftList();
	}
    };
    this.gift_click = function (_voucher_id){
	window.location = "/fpointstore/detail/voucher/id/"+_voucher_id;
    };
    this.changeVoucher_click = function (){
	$jq("#popup-fpointstore-cover").fadeIn();
	$jq('#popup-fpointstore-confirm').fadeIn();
    };
    this.closeAlert_click = function(){
	$jq("#popup-fpointstore-cover").fadeOut();
	$jq('#popup-fpointstore-alert').fadeOut();
    }
    this.tryLoadQueue_click = function(){
	$this.tryLoadQueue();
    }
    this.closeConfirm_Click = function(){
	$jq("#popup-fpointstore-cover").fadeOut();
	$jq('#popup-fpointstore-confirm').fadeOut();
    }
    this.changeConfirm_Click = function (_is_combo, _voucher_id){
	if($this.is_loading){return;}
	$this.showLoadingAnimation();
	setTimeout(function(){$this.changeGift(_is_combo, _voucher_id);}, 100);
    };
    this.updateVIP_Click = function(){
	$this.setVIP();
    }
    this.openHistoryReader_Click = function(gift_id){
	var gifts_store = $this.data[$this.category_id];
	var $content = $jq("<div id='popup-fpointstore-alert-reader-content'></div>");
	$content.append(gifts_store['data'][gift_id]['content']);
	$jq('#popup-fpointstore-alert-reader-content-zoom').empty();
	$jq('#popup-fpointstore-alert-reader-content-zoom').html($content);
	$jq("#popup-fpointstore-alert-reader").fadeIn();
	$jq("#popup-fpointstore-cover").fadeIn();
    }
    this.closeHistoryReader_Click = function(){
	$jq("#popup-fpointstore-alert-reader").fadeOut();
	$jq("#popup-fpointstore-cover").fadeOut();
    }
    
    // Process
    this.getPageGift = function(item){
	let disabled = "";
	if(item['fpoint'] > $this.fpoint){
	    disabled = "disabled";
	}
	return "<li class='col-md-4 col-sm-6 col-xs-12 fpointstore-grid-item'>"
		    +"<a href='/fpointstore/detail/voucher/id/"+ item['id'] +"'>"
			+"<div class='fpointstore-grid-item-box'>"
			    +"<img class='lazyload' src='"+loading_icon_url+"' data-src='"+ $this.media_url + item['image'] + '?q='+$this.language['queryfier']+"'>"
			    +"<div class='fpointstore-grid-item-box-lable'>"+ item['name'] +"&nbsp;-&nbsp;"+$this.language['discount']+" "+item['discount']+"</div>"
			    +"<div class='fpointstore-grid-item-box-bottom'>"
				+"<div class='fpointstore-grid-item-box-button "+disabled+"'>"
				    +"<div><span>"+$this.language['Change_now']+"</span></div>"
				    +"<div><span></span>"+$this.formatCurrency(item['fpoint'])+" F-Point</div>"
				+"</div>"
			    +"</div>"
			+"</div>"
		    +"</a>"
		+"</li>";
    };
    this.loadNextPage = function(){
	if($this.is_loading || $this.is_loading_next_page){return;}
	$this.is_loading_next_page = true;
	var gifts_store = $this.data[$this.category_id];
	if(!gifts_store.over){
	    gifts_store.p += 1;
	    $this.page_current = gifts_store.p;
	    $this.loadGiftList();
	}else{
	    $this.is_loading_next_page = false;
	}
    };
    this.tryLoadQueue = function (){
	$jq('#popup-fpointstore-loading-context-text').html($this.language['processing']+"...");
	$jq('#popup-fpointstore-loading-logo').hide();
	$jq('#popup-fpointstore-loading-icon').show();
	$jq('#popup-fpointstore-loading-confirm').hide();
	time_loaded = 0;
	setTimeout(function(){$this.getResultQueue();}, 100);
    }
    //history process
    this.getPageVoucher = function(item){
	let used_str = "";
	let status_str = "<span class='voucher-status-partner'>"+$this.language['partner']+"</span>";
	let expired_date_str = "";
	
	
	if(!item['times_used']){
	    used_str = "deactive";
	}

	if(!item['partner']){
	    if(item['times_used'] > 0){
		status_str = "<span class='voucher-status-used'>"+$this.language['used']+"</span>";
	    }else{
		status_str = "<span class='voucher-status-avalible'>"+$this.language['not_used_yet']+"</span>";
	    }
	}
	
	let expired_date = new Date(item['expire_date']);
        let now_date = new Date();
        if(now_date < expired_date) {
            expired_date_str = $this.getFormattedDate(expired_date);
        }else{
	    used_str = "deactive";
	    expired_date_str = "<span>"+$this.language['expired']+"</span>";
	}
	
	return "<li class='col-md-6 col-xs-12 history-voucher-grid-item'>"
		    +"<div class='history-voucher-grid-item-box'>"
			+"<div class='history-voucher-grid-item-box-code'>"
			    +"<div class='history-voucher-grid-item-box-code-img'><a href=\""+"/fpointstore/detail/voucher/id/"+item['gift_id']+"\"><img class='lazyload' src='"+loading_icon_url+"' data-src=\""+ $this.media_url + item['image'] + '?q='+$this.language['queryfier']+"\" /></a></div>"
			    +"<div class='history-voucher-grid-item-box-code-info "+ used_str +"\'>"
				+"<div>"
				    +"<label>"+$this.language['voucher_code']+"</label>"
				    +"<label>"+item['code']+"</label>"
				+"</div>"
			    +"</div>"
			+"</div>"
			+"<div class='history-voucher-grid-item-box-status'>"
			+ status_str
			+"</div>"
			+"<div class='history-voucher-grid-item-box-name'>"
			    +item['name']+"&nbsp;-&nbsp;"+$this.language['discount']+" "+item['discount']
			+"</div>"
			+"<div class='history-voucher-grid-item-box-footer'>"
			    +"<div class='history-voucher-grid-item-box-footer-left'>"
				+"<div class='history-voucher-grid-item-box-expire'>"
				    +$this.language['expiry_date']+":&nbsp;"+ expired_date_str
				+"</div>"
				+"<div class='history-voucher-grid-item-box-limit'>"
				 +"*"+$this.language['apply_for_minimum_order']+"&nbsp"+item['order_limit']
				+"</div>"
			    +"</div>"
			    +"<div class='history-voucher-grid-item-box-footer-right'>"
				+"<button type='button' onclick='fpointstore.openHistoryReader_Click("+item['id']+");' class='history-voucher-grid-item-box-footer-right-btn'>"
				    +"<span>"
					+$this.language['details']
				    +"</span>"
				+"</button>"
			    +"</div>"
			+"</div>"
		    +"</div>"
		+"</li>";
    };
    this.loadHistoryNextPage = function(){
	if($this.is_loading || $this.is_loading_next_page){return;}
	$this.is_loading_next_page = true;
	var gifts_store = $this.data[$this.category_id];
	if(!gifts_store.over){
	    gifts_store.p += 1;
	    $this.page_current = gifts_store.p;
	    $this.loadHistoryGiftList();
	}else{
	    $this.is_loading_next_page = false;
	}
    };
    
    //common
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
    this.formatCurrency = function (num) {
	return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')
    };
    this.sleep = function(milliseconds) {
      var start = new Date().getTime();
      for (var i = 0; i < 1e7; i++) {
	if ((new Date().getTime() - start) > milliseconds){
	  break;
	}
      }
    }
    this.copyClipboard = function(str){
	var el = document.createElement('textarea');
	// Set value (string to be copied)
	el.value = str;
	// Set non-editable to avoid focus and move outside of view
	el.setAttribute('readonly', '');
	el.style = {position: 'absolute', left: '-9999px'};
	document.body.appendChild(el);
	// Select text inside element
	el.select();
	// Copy text to clipboard
	document.execCommand('copy');
	// Remove temporary element
	document.body.removeChild(el);
    }
    this.getFormattedDate = function(date) {
	let year = date.getFullYear();
	let month = (1 + date.getMonth()).toString().padStart(2, '0');
	let day = date.getDate().toString().padStart(2, '0');

	return day + '/' + month + '/' + year;
    }
    
    this.updateQueryStringParam = function (key, value) {
	var baseUrl = [location.protocol, '//', location.host, location.pathname].join(''),
	    urlQueryString = document.location.search,
	    newParam = key + '=' + value,
	    params = '?' + newParam;

	// If the "search" string exists, then build params from it
	if (urlQueryString) {
	    var updateRegex = new RegExp('([\?&])' + key + '[^&]*');
	    var removeRegex = new RegExp('([\?&])' + key + '=[^&;]+[&;]?');

	    if( typeof value == 'undefined' || value == null || value == '' ) { // Remove param if value is empty
		params = urlQueryString.replace(removeRegex, "$1");
		params = params.replace( /[&;]$/, "" );

	    } else if (urlQueryString.match(updateRegex) !== null) { // If param exists already, update it
		params = urlQueryString.replace(updateRegex, "$1" + newParam);

	    } else { // Otherwise, add it to end of query string
		params = urlQueryString + '&' + newParam;
	    }
	}

	// no parameter was set so we don't need the question mark
	params = params == '?' ? '' : params;

	window.history.replaceState({}, "", baseUrl + params);
    };
}

