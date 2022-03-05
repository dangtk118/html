var SeriesBook = function () {
    var SERIESBOOK_URL = "/seriesbook/index/getProductsBySeriesId";
    var SERIES_SET_URL = "/seriesbook/index/getSeriesSet";
    var SERIESBOOKAccount_URL = "/seriesbook/index/getSeriesBook";
    var SET_SERIESBOOK_URL = "/seriesbook/index/setSeriesBook";
    var SET_SERIESBOOK_PAGE_URL = "/seriesbook/index/setSeriesBookPage";
    
    var series_id = 1;
    var block_id = '';
    var page_current = 1;
    var sort_by = '';
    var is_first = true;
    var is_over = false;
    var series_info = null;
    var is_show_info = true;
    var limit = 12;
    var is_loading = false;
    var is_follow_series = false;
    var load_type = '';
    var is_page_type = true;
    var is_grid = false;
    var has_product = false;
    var data_lenght = 0;
    var language = {};
    
    var is_over_follow = false;
    var is_loading_follow = false;
    var page_current_follow = 1;
    var follow_item_size = 0;
    
    var is_over_recommended = false;
    var is_loading_recommended = false;
    var page_current_recommended = 1;
    var recommended_item_size = 0;
    var fhs_campaign_str = "";
    
    var has_change_status = false;
    
    var $this = this;
    
    //----SERIES BOOK
    this.initSeriesBookPage = function (_is_page_type, _is_grid, _block_id, _language, _series_id, _sort_by, _limit , _fhs_Campaign_str = '', _is_lazy_loading = false, _is_show_info = true) {
	$this.language = _language;
	$this.series_id = _series_id;
	$this.is_page_type = _is_page_type;
	$this.is_grid = _is_grid;
	$this.block_id = _block_id;
	$this.sort_by = _sort_by;
	$this.is_over = false;
	$this.page_current = 1;
	$this.limit = _limit;
	$this.is_loading = false;
	$this.is_follow_series = false;
	$this.has_product = false;
        $this.fhs_campaign_str = _fhs_Campaign_str; 
	$this.load_type = 'series_book';
	$this.is_show_info = _is_show_info;
	$this.is_first = true;
		
	if(_is_lazy_loading){
	    setTimeout(function(){$this.checkBlockInViewport();},1000);
	}else{
	    $this.loadPage();
	}
	if($this.is_page_type){
	    $jq(window).on('resize scroll', function() {
		var hT = $jq('#seriesbook_bottom'+$this.block_id).offset().top,
		    hH = $jq('#seriesbook_bottom'+$this.block_id).outerHeight(),
		    wH = $jq(window).height(),
		    wS = $jq(this).scrollTop();
		    if (wS > (hT+hH-wH)){
			$this.loadPage();
		    }
	    });
	    $this.enableSelectBoxesList('order'+$this.block_id);
	}else{
	    if(_is_lazy_loading){
		$jq(window).on('resize scroll', function () {
		    $this.checkBlockInViewport();
		});
	    }
	}
    };
    this.loadPage = function(){
	if($this.is_loading || $this.is_over){return;}
	if($this.page_current == 1){
	    $jq('#seriesbook_grid'+$this.block_id).hide();
	}
	$this.loaderPage(true);
	$jq.ajax({
	    url: SERIESBOOK_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {series_id: $this.series_id, is_first: ($this.is_first?1:0), sort_by: $this.sort_by, page: $this.page_current, limit: $this.limit},
	    success: function (data) {
		if(data['success']){
		    if(data['is_follow']){
			$this.is_follow_series = data['is_follow'];
		    }
		    if(data['products'] && $this.is_first){
			$this.has_product = true;
		    }
		    if(data['series_info']){
			$this.series_info = data['series_info'];
		    }
		    $this.getSeriesInfo();
		    
		    if(data['is_over']){
			$this.is_over = true;
		    }
		    
		    if(data['products']){
			if($this.is_page_type){
			    if($this.page_current == 1){
				if(data['products'][0]){
				    //$this.product_first = data['products'][0];
				    $this.getSeriesInfo();
				}
			    }
			    $this.page_current = $this.page_current + 1;
			}else{
			    $this.page_current = $this.page_current;
			}
			$this.displayProduct(data['products']);
		    }
		}
		if(!$this.is_page_type){
		    $this.is_over = true;
		    $this.data_lenght = data['products'].length;
		    $this.showSlider();
		}
		$this.loaderPage(false);
	    },
	    error: function(){
		$this.loaderPage(false);
	    }
	});
    };
    this.displayProduct = function(products_data){
	var product_str = "";
	Object.keys(products_data).forEach(function (key) {
	    product_str += fhs_account.getProduct(products_data[key]);
	});
	
	if(product_str){
	    $jq('#seriesbook_grid_item'+$this.block_id).append(product_str);
	    $jq('#seriesbook_grid'+$this.block_id).show();
	}
    }
//    this.getProduct = function (item){
//	let price = '';
//	let episode = '';
//	let slider_class = '';
//	if(!$this.is_page_type){
//	    slider_class = 'class="swiper-slide"';
//	}
//	let btn_add_to_cart = '';
//	if(item['product_price'] != item['product_finalprice']){
//	    price += "<p class='old-price'><span class='price'>"+item['product_price']+"đ</span></p>";
//	}
//	if(item['episode']){
//	    episode = "<div class='episode-label'>"+item['episode']+"</div>";
//	}
//	if(item['add_to_cart_info']){
//	    btn_add_to_cart = fhs_account.getAddToCartButton(item['add_to_cart_info']);
//	}
//	return "<li "+slider_class+">"
//		+"<div class='item-inner'>"
//		    +item['discount_label_html']
//		    +"<div class='ma-box-content'>"
//			+"<div class='products clear'>"
//			    +"<div class='product images-container'>"
//				+"<a href='"+item['product_url']+ $this.fhs_campaign_str +"' title='"+item['product_name']+"' class='product-image'>"
//				    +"<div class='product-image'>"
//					+"<img class='lazyload' src='"+loading_icon_url+"' data-src='"+item['image_src']+"' alt='"+item['product_name']+"'>"
//				    +"</div>"
//				+"</a>"
//			    +"</div>"
//			+"</div>"
//			+"<h2 class='product-name-no-ellipsis'>"
//			    +"<a href='"+item['product_url']+ $this.fhs_campaign_str +"' title='"+item['product_name']+"' class='product-image'>"+item['product_name']+"</a>"
//			+"</h2>"
//			+"<div class='price-label'>"
//			    +"<p class='special-price'><span class='price m-price-font'>"+item['product_finalprice']+"&nbsp;đ</span></p>"
//			    +price
//			    +episode
//			+"</div>"
//			+"<div class='fhs-rating-container'>"
//			    +item['rating_html']
//			+"</div>"
//			+"<div class='clear'></div>"
//			+btn_add_to_cart
//		    +"</div>"
//		+"</div>"
//	    +"</li>";
//    };
    this.getSeriesInfo = function (){
	if($this.series_info && $this.is_first){
	    let item = $this.series_info;
	    if($this.is_show_info && $this.is_first){
		let attribute = '';
		let subscribes = '';
		let episode = '';
		let outOfStock = '';
		let btn = $this.language['btn_follow'].replace('{{seriesbook_id}}',$this.series_id).replace('seriesbook_page','seriesbook_page_'+$this.block_id);
		let btn_mobile = $this.language['btn_follow'].replace('{{seriesbook_id}}',$this.series_id).replace('seriesbook_page','seriesbook_page_'+$this.block_id);
		if($this.is_follow_series){
		    btn = $this.language['btn_unfollow'].replace('{{seriesbook_id}}',$this.series_id).replace('seriesbook_page','seriesbook_page_'+$this.block_id);
		    btn_mobile = $this.language['btn_unfollow_white'].replace('{{seriesbook_id}}',$this.series_id).replace('seriesbook_page','seriesbook_page_'+$this.block_id);
		}
		if(!$this.has_product){
		    outOfStock = "<div class='seriesbook_info_outofstock'></div>";
		}
		if(item['attributes']){
		    let attributes = item['attributes'];
		    Object.keys(attributes).forEach(function (key) {
			attribute += "<div class='product-attribute-"+key+"'>"+$this.language[key]+":&nbsp;"+attributes[key]['value']+"</div>";
		    });
		    attribute = "<div class='product-attribute'>"+attribute+"</div>";
		}

    //	    if(item['episode_label']){
    //		episode = "<div class='fhs-series-episode-label'>Tập mới nhất: "+item['episode_label']+"</div>";
    //	    }
		if(item['subscribes']){
		    subscribes = "<div class='fhs-series-subscribes'>"+item['subscribes']+" lượt theo dõi</div>";
		}

		let series_info_str = 
		    "<div>"
			+"<div class=\"product-image\"><img src='"+item['image_src']+"' /></div>"
			+"<div>"
			    +"<div>"
				+"<div class=\"product-name-no-ellipsis\"><span class='fhs-series-label'><i></i></span>"+item['seriesbook_name']+"</div>"
				+attribute
				+episode
				+subscribes
				+outOfStock
			    +"</div>"
			    +"<div>"
				+btn
			    +"</div>"
			+"</div>"
		    +"</div>";

		if($jq('.fhs-btn-view-promo-follow').length > 0){
		    if($this.is_follow_series){
			$jq('.fhs-btn-view-promo-follow').attr('onclick',"seriesbook_page_"+$this.block_id+".setSeriesBookPage("+$this.series_id+", false);event.stopPropagation();");
			$jq('.seriesbook_first_item .fhs-btn-view-promo-follow').html($this.language['unfollow']);
			$jq('.fhs-bsidebar-tab-items-series .fhs-btn-view-promo-follow').html($this.language['unfollow_white']);
			$jq('.fhs-btn-view-promo-follow').removeClass('active');
		    }else{
			$jq('.fhs-btn-view-promo-follow').attr('onclick',"seriesbook_page_"+$this.block_id+".setSeriesBookPage("+$this.series_id+", true);event.stopPropagation();");
			$jq('.fhs-btn-view-promo-follow').html($this.language['follow']);
			$jq('.fhs-btn-view-promo-follow').addClass('active');
		    }
		}else{
		    let btn_bottom = "<div class=\"fhs-bsidebar\">"
				    +"<div class=\"fhs-bsidebar-tab\">"
					+"<ul class=\"fhs-bsidebar-tab-items\">"
					    +"<li class=\"fhs-bsidebar-tab-items-series\">"
						+btn_mobile
					    +"</li>"
					+"</ul>"
				    +"</div>"
				+"</div>"
		    $jq('#seriesbook_follow_btn'+$this.block_id).html(btn_bottom);
		}
		$jq('#seriesbook_grid_item_first'+$this.block_id).html(series_info_str);
	    }
	    
	    $jq('#seriesbook_info_name'+$this.block_id).html("<span><span class='fhs-series-label'><i></i></span>"+item['seriesbook_name']+"</span>");
	}else{
	    if($this.is_first && !$this.is_page_type){
		$jq('#seriesbook_info_name'+$this.block_id).html("<span><span class='fhs-series-label'><i></i></span>"+$this.language['series_set']+"</span>");
	    }
	}
	$this.is_first = false;
	return;
    };
    this.enableSelectBoxesList = function(button){
        $jq('span.selectOption-' + button).each(function (index, value) {
            var selected = $jq(this).attr('selected');
            if (selected) {
                $jq('span.selected-' + button).html($jq(value).text());
            }
        });

        $jq('div.selectBox-' + button).each(function () {
            $jq(this).children('span.selected,span.selectArrow-' + button).click(function () {
                // remove all class if it still has
                $jq('span.selectOption-' + button).each(function (index, value) {
                    $jq(value).removeClass('hightlight');
                    //hightlight when selected 
                    if ($jq('span.selected-' + button).text() == $jq(value).text()) {
                        $jq(value).addClass("hightlight");
                    }
                });
                // show/hide option fields
                if ($jq(this).parent().children('div.selectOptions-' + button).css('display') == 'none') {
                    $jq(this).parent().children('div.selectOptions-' + button).css('display', 'block');
                } else
                {
                    $jq(this).parent().children('div.selectOptions-' + button).css('display', 'none');
                }
            });

            // action change sort
            $jq(this).find('span.selectOption-' + button).click(function () {
                $jq(this).parent().css('display', 'none');
                $jq(this).closest('div.selectBox-' + button).attr('value', $jq(this).attr('value'));
                $jq(this).parent().siblings('span.selected-' + button).html($jq(this).html());
		$this.sort_by = $jq(this).attr('value');
		if($this.load_type == 'series_book'){
		    $this.reloadProduct();
		}else{
		    $this.reloadSeries();
		}
            });
            // tat option fields khi re chuot ra ngoai fields 
            $jq("div.selectOptions-" + button).mouseleave(function () {
                $jq(this).parent().children('div.selectOptions-' + button).css('display', 'none');
            });
        });
    };
    this.reloadProduct = function(){
	$this.page_current = 1;
	$this.is_over = false;
	$jq('#seriesbook_grid_item'+$this.block_id).empty();
	$this.loadPage();
    };
    
    //-----SERIES BOOK ACCOUNT
    this.initSeriesBookAccount = function (_languages, _page_current_follow, _is_over_follow, _page_current_recommended, _is_over_recommended, _limit, _follow_item_size, _recommended_item_size , _fhs_campaign_str) {
	$this.is_over_follow = _is_over_follow;
	$this.is_loading_follow = false;
	$this.page_current_follow = _page_current_follow;
	$this.follow_item_size = _follow_item_size;
    
	$this.is_over_recommended = _is_over_recommended;
	$this.is_loading_recommended = false;
	$this.page_current_recommended = _page_current_recommended;
	$this.recommended_item_size = _recommended_item_size;
        $this.fhs_campaign_str = _fhs_campaign_str;
	$this.language = _languages;
	$this.limit = _limit;
	
	$this.has_change_status = false;
	
	$jq(window).on('resize scroll', function() {
	    if($jq('#seriesbook_follow_bottom').is(":visible")){
		var hT = $jq('#seriesbook_follow_bottom').offset().top,
		hH = $jq('#seriesbook_follow_bottom').outerHeight(),
		wH = $jq(window).height(),
		wS = $jq(this).scrollTop();
		if (wS > (hT+hH-wH)){
		    $this.loadSeriesBook(true);
		}
	    }
	    
	    if($jq('#seriesbook_recommended_bottom').is(":visible")){
		var hT = $jq('#seriesbook_recommended_bottom').offset().top,
		hH = $jq('#seriesbook_recommended_bottom').outerHeight(),
		wH = $jq(window).height(),
		wS = $jq(this).scrollTop();
		if (wS > (hT+hH-wH)){
		    $this.loadSeriesBook(false);
		}
	    }
	});
    };
    this.loadSeriesBook = function(is_follow){
	let data = {};
	if(is_follow){
	    if($this.is_loading_follow || $this.is_over_follow){return;}
	    $this.is_loading_follow = true;
	    data = {is_follow: 1, page: $this.page_current_follow, limit: $this.limit};
	}else{
	    if($this.is_loading_recommended || $this.is_over_recommended){return;}
	    $this.is_loading_recommended = true;
	    data = {is_follow: 0, page: $this.page_current_recommended, limit: $this.limit};
	}
	fhs_account.showLoadingAnimation();
	$jq.ajax({
	    url: SERIESBOOKAccount_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: data,
	    success: function (data) {
		if(data['success']){
		    if(data['is_over']){
			if(is_follow){
			    $this.is_over_follow = true;
			}else{
			    $this.is_over_recommended = true;
			}
		    }
		    
		    $this.loadSeriesBook(is_follow);
		    
		    if(data['seriesbook'] && data['seriesbook'].length > 0){
			if(is_follow){
			    $this.follow_item_size += data['seriesbook'].length;
			    $this.page_current_follow = $this.page_current_follow + 1;
			}else{
			    $this.recommended_item_size += data['seriesbook'].length;
			    $this.page_current_recommended = $this.page_current_recommended + 1;
			}
			
			$this.SeriesBookDisplay(is_follow,data['seriesbook']);
		    }
		    
		    if(is_follow){
			$this.is_loading_follow = false;
		    }else{
			$this.is_loading_recommended = false;
		    }
		}
		$this.showEmptyForm();
		fhs_account.hideLoadingAnimation();
	    },
	    error: function(){
		if(is_follow){
		    $this.is_loading_follow = false;
		}else{
		    $this.is_loading_recommended = false;
		}
		$this.showEmptyForm();
		fhs_account.hideLoadingAnimation();
	    }
	});
    };
    this.setSeriesBookPage = function(series_id, is_follow){
	if(fhs_account.isEmpty(CUSTOMER_ID)){
	    fhs_account.showLoginPopup('login');
	    $jq('#login_username').focus();
	    return;
	}
	
	fhs_account.showLoadingAnimation();
	$jq.ajax({
	    url: SET_SERIESBOOK_PAGE_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {series_id: series_id, is_follow: is_follow},
	    success: function (data) {
		if(data['success']){
		    if(data['result']){
			$this.is_follow_series = is_follow;
			$this.is_first = true;
			if(data['subscribes']){
			    if($this.series_info){
				$this.series_info['subscribes'] = data['subscribes'];
			    }
			}
			$this.getSeriesInfo();
		    }
		}
		fhs_account.hideLoadingAnimation();
	    },
	    error: function(){
		fhs_account.hideLoadingAnimation();
	    }
	});
    };
    this.setSeriesBook = function(series_id, is_follow, _page_current, _limit){
	if(fhs_account.isEmpty(CUSTOMER_ID)){
	    fhs_account.showLoginPopup('login');
	    $jq('#login_username').focus();
	    return;
	}
	
	let data = {};
	if(is_follow){
	    data = {series_id: series_id, is_over: $this.is_over_recommended, is_follow: is_follow, page: _page_current, limit: _limit};
	}else{
	    data = {series_id: series_id, is_over: $this.is_over_follow, is_follow: is_follow, page: _page_current, limit: _limit};
	}
	
	fhs_account.showLoadingAnimation();
	$jq.ajax({
	    url: SET_SERIESBOOK_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: data,
	    success: function (data) {
		if(data['success']){
		    if(data['is_over']){
			if(!is_follow){
			    $this.is_over_follow = true;
			}else{
			    $this.is_over_recommended = true;
			}
		    }
		    
		    if(data['seriesbook']){
			if(!is_follow){
			    $this.follow_item_size += data['seriesbook'].length;
			}else{
			    $this.recommended_item_size += data['seriesbook'].length;
			}
			$this.SeriesBookDisplay(!is_follow,data['seriesbook']);
		    }
		}
		fhs_account.hideLoadingAnimation();
	    },
	    error: function(){
		fhs_account.hideLoadingAnimation();
	    }
	});
    };
    this.SeriesBookDisplay = function(is_follow,data){
	var series_str = "";
	Object.keys(data).forEach(function (key) {
	    series_str += $this.getSeries(data[key]);
	});
	
	if(series_str){
	    if(is_follow){
		$jq('#fhs_seriesbook_follow_block .fhs-event-promo-list-seriesbook').append(series_str);
	    }else{
		$jq('#fhs_seriesbook_recommended_block .fhs-event-promo-list-seriesbook').append(series_str);
	    }
	}
    };
    this.getSeries = function(item){
	let btn_follow = '';
	//let label_follow = '';
	let label_new = '';
	if(item['is_follow'] == 1){
	    btn_follow = $this.language['btn_unfollow'].replace('{{seriesbook_id}}',item['seribook_id']).replace('seriesbook_page','seriesbook_page_'+$this.block_id);
	    //label_follow = "<div class='fhs-event-promo-list-seriesbook-item-label'><span>"+$this.language['following']+"</span></div>";
	}else{
	    btn_follow = $this.language['btn_follow'].replace('{{seriesbook_id}}',item['seribook_id']).replace('seriesbook_page','seriesbook_page_'+$this.block_id);
	}
	if(item['is_new']){
	    label_new = "<div class='fhs-series-new'><span>"+$this.language['new']+"</span></div>";
	}
	return '<a class="fhs-event-promo-list-seriesbook-item" href="/seriesbook/index/series/id/'+item['seribook_id']+$this.fhs_campaign_str+'">'
			+"<div>"
			+label_new
			+"<img class='lazyload' src='"+loading_icon_url+"' data-src='"+item['image_src']+"'/></div>"
			+'<div>'
			    +'<div>'
				+'<div class="fhs-event-promo-list-seriesbook-item-name"><span class="fhs-series-label"><i></i></span>'+item['product_name']+'</div>'
				+'<div class="fhs-event-promo-list-seriesbook-item-episode">'+item['episode_label']+'</div>'
				+'<div class="fhs-series-subscribes">'+item['subscribes']+' lượt theo dõi</div>'
			    +'</div>'
			    +'<div>'
				+'<div>'+ btn_follow +'</div>'
			    +'</div>'
			    +'<div class="clear"></div>'
			+'</div>'
		    +'</a>';
    };
    this.followSeriesBook = function(e, series_id, is_follow){
	$jq(e).parents('.fhs-event-promo-list-seriesbook-item').fadeOut(0);
	if(is_follow){
	    $this.recommended_item_size--;
	    $this.setSeriesBook(series_id, is_follow, ($this.limit * ($this.page_current_recommended - 1)), 1);
	    $this.is_over_follow = false;
	    $this.page_current_follow = 1;
	    $this.follow_item_size = 0;
	    $jq('#fhs_seriesbook_follow_block .fhs-event-promo-list-seriesbook').empty();
	}else{
	    $this.follow_item_size--;
	    $this.setSeriesBook(series_id, is_follow, ($this.limit * ($this.page_current_follow - 1)), 1);
	    $this.is_over_recommended = false;
	    $this.page_current_recommended = 1;
	    $this.recommended_item_size = 0;
	    $jq('#fhs_seriesbook_recommended_block .fhs-event-promo-list-seriesbook').empty();
	}
	$this.showEmptyForm();
	eval("seriesbook_page_"+$this.block_id+".has_change_status = true;");
    };
    this.loaderPage = function(is_loading){
	$this.is_loading = is_loading;
	$jq('#fhs-product-grid'+$this.block_id).removeClass('loading');
	if(is_loading){
	    if($jq('#seriesbook_grid_item'+$this.block_id+' li').length <= 0){
		$jq('#seriesbook_empty'+$this.block_id).fadeOut(0);
	    }
	    $jq('#fhs-product-grid'+$this.block_id).addClass('loading');
	}else{
	    if($jq('#seriesbook_grid_item'+$this.block_id+' li').length <= 0){
		$jq('#seriesbook_empty'+$this.block_id).fadeIn(0);
	    }else{
		$jq('#seriesbook_empty'+$this.block_id).fadeOut(0);
	    }
	}
    };
    this.showEmptyForm = function(){
	if($this.recommended_item_size <= 0){
	    $jq('#fhs_seriesbook_recommended_block .fhs-event-promo-list-item').fadeOut(0);
	    $jq('#fhs_seriesbook_recommended_block .seriesbook_empty').fadeIn(0);
	}else{
	    $jq('#fhs_seriesbook_recommended_block .fhs-event-promo-list-item').fadeIn(0);
	    $jq('#fhs_seriesbook_recommended_block .seriesbook_empty').fadeOut(0);
	}
	if($this.follow_item_size <= 0){
	    $jq('#fhs_seriesbook_follow_block .fhs-event-promo-list-item').fadeOut(0);
	    $jq('#fhs_seriesbook_follow_block .seriesbook_empty').fadeIn(0);
	}else{
	    $jq('#fhs_seriesbook_follow_block .fhs-event-promo-list-item').fadeIn(0);
	    $jq('#fhs_seriesbook_follow_block .seriesbook_empty').fadeOut(0);
	}
    };
    this.onclickSeriesBtn = function(series_id){
	window.location.href = '/seriesbook/index/series/id/'+series_id;
    };
    
    //----SERIES SET
    
    this.initSeriesSet = function (_is_page_type, _is_grid, _block_id, _language, _sort_by, _limit , _fhs_Campaign_str = '', _is_lazy_loading = false) {
	$this.language = _language;
	$this.is_page_type = _is_page_type;
	$this.is_grid = _is_grid;
	$this.block_id = _block_id;
	$this.sort_by = _sort_by;
	$this.is_over = false;
	$this.page_current = 1;
	$this.limit = _limit;
	$this.is_loading = false;
	$this.product_first = null;
        $this.fhs_campaign_str = _fhs_Campaign_str; 
	
	$this.load_type = 'series_set';
	
	if(_is_lazy_loading){
	    setTimeout(function(){$this.checkBlockInViewport();},1000);
	}else{
	    $this.loadSeriesSet();
	}
	    
	if($this.is_page_type){
	    $jq(window).on('resize scroll', function() { 
		var hT = $jq('#seriesbook_bottom'+$this.block_id).offset().top,
		    hH = $jq('#seriesbook_bottom'+$this.block_id).outerHeight(),
		    wH = $jq(window).height(),
		    wS = $jq(this).scrollTop();
		    if (wS > (hT+hH-wH)){
			$this.loadSeriesSet();
		    }
	    });
	    $this.enableSelectBoxesList('order'+$this.block_id);
	}else{
	    if(_is_lazy_loading){
		$jq(window).on('resize scroll', function () {
		    $this.checkBlockInViewport();
		});
	    }
	}
    };
    this.loadSeriesSet = function(){
	if($this.is_loading || $this.is_over){return;}
	if($this.page_current == 1){
	    $jq('#seriesbook_grid'+$this.block_id).hide();
	}
	$this.loaderPage(true);
	$jq.ajax({
	    url: SERIES_SET_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {sort_by: $this.sort_by, page: $this.page_current, limit: $this.limit},
	    success: function (data) {
		if(data['success']){
		    if(data['is_over']){
			$this.is_over = true;
		    }
		    
		    if(data['series']){
			$this.page_current = $this.page_current + 1;
			$this.data_lenght = data['series'].length;
			$this.displaySeriesSet(data['series']);
		    }
		}
		if(!$this.is_page_type){
		    $this.is_over = true;
		}
		$this.loaderPage(false);
	    },
	    error: function(){
		$this.loaderPage(false);
	    }
	});
    };
    this.displaySeriesSet = function(series_data){
	var series_str = "";
	Object.keys(series_data).forEach(function (key) {
	    series_data[key]['product_url'] = series_data[key]['product_url'] + $this.fhs_campaign_str;
	    series_data[key]['episode'] = series_data[key]['episode_label'];
	    series_str += fhs_account.getProduct(series_data[key]);
	});
	
	if(series_str){
	    $jq('#seriesbook_grid_item'+$this.block_id).append(series_str);
	    $jq('#seriesbook_grid'+$this.block_id).show();
	    if(!$this.is_page_type){
		$this.showSlider();
	    }
	}
    }
    this.getSeriesSet = function (item){
	let url = "/seriesbook/index/series/id/"+item['seribook_id']+ $this.fhs_campaign_str;
	let slider_class = '';
	if(!$this.is_page_type){
	    slider_class = 'class="swiper-slide"';
	}
	return "<li "+slider_class+">"
		+"<div class='item-inner'>"
		    +"<div class='ma-box-content'>"
			+"<div class='products clear'>"
			    +"<div class='product images-container'>"
				+"<a href='"+url+"' title='"+item['seriesbook_name']+"' class='product-image'>"
				    +"<div class='product-image'>"
					+"<img class='lazyload' src='"+loading_icon_url+"' data-src='"+item['image_src']+"' alt='"+item['seriesbook_name']+"'>"
				    +"</div>"
				+"</a>"
			    +"</div>"
			+"</div>"
			+"<h2 class='product-name-no-ellipsis fhs-series'>"
			    +"<a href='"+url +"' title='"+item['seriesbook_name']+"'><span class='fhs-series-label'><i></i></span>"+item['seriesbook_name']+"</a>"
			+"</h2>"
			+"<div class='fhs-series-episode-label'>"+
			    item['episode_label']
			+"</div>"
			+"<div class='fhs-series-subscribes'>"
			    +item['subscribes']+" lượt theo dõi"
			+"</div>"
			+"<div class='clear'></div>"
		    +"</div>"
		+"</div>"
	    +"</li>";
    };
    this.reloadSeries = function(){
	$this.page_current = 1;
	$this.is_over = false;
	$jq('#seriesbook_grid_item'+$this.block_id).empty();
	$this.loadSeriesSet();
    };
    this.checkBlockInViewport = function(){
	if($jq('#fhs-product-grid'+$this.block_id)){
	    if(Helper.isElementInViewport($jq('#fhs-product-grid'+$this.block_id))){
		if($this.load_type == 'series_book'){
		    $this.loadPage();
		}else{
		    $this.loadSeriesSet();
		}
	    }
	}
    };
    this.showSlider = function(){
	$jq('#fhs-tab-slider-prev'+$this.block_id).hide();
	$jq('#fhs-tab-slider-next'+$this.block_id).hide();
	
	if ($jq(window).width() < 992) {
	    eval("var mySwiperAsidebar"+$this.block_id+" = new Swiper($jq('#seriesbook_slider"+$this.block_id+"'), {"
		    +"slidesPerView: 'auto',"
		    +"freeMode: true,"
		    +"direction: 'horizontal',"
		    +"simulateTouch: true,"
		    +"});");
	}else{
		if(!$this.is_grid && $this.data_lenght && $this.data_lenght > 5){
		    $jq('#fhs-tab-slider-next'+$this.block_id).show();
		}
		let row_param = "";
		if($this.is_grid){
		    row_param = "slidesPerColumnFill: 'row',slidesPerColumn: 2,";
		}
		eval("var mySwiperAsidebar"+$this.block_id+" = new Swiper($jq('#seriesbook_slider"+$this.block_id+"'), {"
		    +"slidesPerView: 5,"
		    +"slidesPerGroup: 5,"
		    +"spaceBetween: 8,"
		    +row_param
		    +"direction: 'horizontal',"
		    +"simulateTouch: true,"
		    +"navigation: {"
			+"nextEl: '#fhs-tab-slider-next"+$this.block_id+"',"
			+"prevEl: '#fhs-tab-slider-prev"+$this.block_id+"'"
		    +"},"
			+"on: {"
			    +"slideChange: function() {"
				+"if ("+$this.data_lenght+") { "
				    // on the first slide
				    +"let demSo =  mySwiperAsidebar"+$this.block_id+".activeIndex + 5;"
				    +"if (mySwiperAsidebar"+$this.block_id+".activeIndex == 0) {"
					+"$jq('#fhs-tab-slider-next"+$this.block_id+"').show();"
					+"$jq('#fhs-tab-slider-prev"+$this.block_id+"').hide();"
				    +"}"
				    // most right postion
				    +"else if (demSo == "+$this.data_lenght+") {"
					+"$jq('#fhs-tab-slider-prev"+$this.block_id+"').show();"
					+"$jq('#fhs-tab-slider-next"+$this.block_id+"').hide();"
				    +"}"
				    // middle positions
				    +"else {"
					+"$jq('#fhs-tab-slider-prev"+$this.block_id+"').show();"
					+"$jq('#fhs-tab-slider-next"+$this.block_id+"').show();"
				    +"}"
				    // --- end-swpier
				+"}"
			    +"}"
			+"},"
		+"});");
	}
    };
}
