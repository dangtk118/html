var ProductViewed = function () {
    var PRODUCTVIEWED_AJAX_URL = "/productviewed/index/getProductViewed";
    var ADD_PRODUCTVIEWED_AJAX_URL = "/productviewed/index/addProductViewed";
    
    var is_page = false;
    var page_current = 1;
    var is_over = false;
    var limit = 12;
    var is_loading = false;
    var is_inited = false;
    
    var $this = this;
    
    this.initProductViewedPage = function (_is_page, _limit) {
	if($this.is_initing || $this.is_inited){return;}
	$this.is_initing = true;
	
	$this.is_page = _is_page;
	$this.is_over = false;
	$this.page_current = 1;
	$this.limit = _limit;
	
	if(_is_page){
	    $this.loadPage();
	    $jq(document).on('scroll', function() {
		var hT = $jq('#productviewed_bottom').offset().top,
		    hH = $jq('#productviewed_bottom').outerHeight(),
		    wH = $jq(window).height(),
		    wS = $jq(this).scrollTop();
		    if (wS > (hT+hH-wH)){
			$this.loadPage();
		    }
	    });
	}else if ($jq("#fhs-asidebar-product-block-content")) {
            if ($this.page_current == 1) {
               $this.loadPage();
            }
        }else{
	    $jq('.fhs-asidebar-tab-items .productviewed').click(function(){
		if($this.page_current == 1){
		    $this.loadPage();
		}
	    });
	}
    };
    
    this.loadPage = function(){
	if(is_loading || $this.is_over){
	    if($this.is_initing && !$this.is_inited){
		setTimeout(function(){$this.loadPage();},500);
	    }
	    return;
	}
	$this.is_inited = true;
	is_loading = true;
	$jq.ajax({
	    url: PRODUCTVIEWED_AJAX_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {is_page: $this.is_page, page: $this.page_current, limit: $this.limit},
	    404: function(responseObject, textStatus, jqXHR) {
		is_loading = false;
	    },
	    503: function(responseObject, textStatus, errorThrown) {
		is_loading = false;
	    },
	    fail: function() {
		is_loading = false;
	    },
	    success: function (data) {
		if(data['success']){
		    if(!data['products']){
			$this.is_over = true;
		    }else{
			$this.page_current = $this.page_current + 1;
			setTimeout(function(){$this.ProductDataProcess(data);}, 100);
		    }
		}
		is_loading = false;
	    }
	});
    };
    
    this.addProduct = function(product_id){
	if(is_loading){return;}
	is_loading = true;
	return;
	$jq.ajax({
	    url: ADD_PRODUCTVIEWED_AJAX_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {product_id: product_id},
	    404: function(responseObject, textStatus, jqXHR) {
		is_loading = false;
	    },
	    503: function(responseObject, textStatus, errorThrown) {
		is_loading = false;
	    },
	    fail: function() {
		is_loading = false;
	    },
	    success: function (data) {
		is_loading = false;
	    }
	});
    };
    
    this.ProductDataProcess = function(data){
        $this.displayProduct(data['products']);
        if ($jq("#fhs-asidebar-product-block-content")) {
            $this.displayProductBlock(data['products']);
            let ww = $jq(window).width();
            if (ww < 992) {
                var mySwiperAsidebar = new Swiper($jq("#fhs-asidebar-product-block-content"), {
                    slidesPerView: 'auto',
                    freeMode: true,
                    direction: 'horizontal',
                    simulateTouch: true,
                })
            }else{
                let $left = $jq(".fhs-asidebar-block-prev");
                let $right = $jq(".fhs-asidebar-block-next");
                let added_item_count = data['products'].length;
              
                var mySwiperAsidebar = new Swiper($jq("#fhs-asidebar-product-block-content"), {
                    slidesPerView: 5,
                    slidesPerGroup:5,
                    direction: 'horizontal',
                    simulateTouch: true,
                    navigation: {
                        nextEl: ".fhs-asidebar-block-next",
                        prevEl: ".fhs-asidebar-block-prev",
                    },
                    on: {
                        slideChange: function() {
                            if (added_item_count) {
                                // on the first slide
                                let demSo =  mySwiperAsidebar.activeIndex + 5;
                                if (mySwiperAsidebar.activeIndex == 0) {
                                    $right.show();
                                    $left.hide();
                                }
                                // most right postion
                                else if (demSo == added_item_count) {
                                    $left.show();
                                    $right.hide();
                                }
                                // middle positions
                                else {
                                    $left.show();
                                    $right.show();
                                }
                                // --- end-swpier
                            }
                        }
                    },
                });
                
                if(added_item_count && added_item_count > 5){
                    $right.show();
                }
            }
            
        }
    };
    
    this.displayProduct = function(products_data){
	var product_str = "";
	Object.keys(products_data).forEach(function (key) {
	    product_str += fhs_account.getProduct(products_data[key]);
	});
	
	if(product_str){
	    if($this.is_page){
		$jq('#products_viewed_grid_item').append(product_str);
		$jq('#products_viewed_grid').show();
	    }else{
		$jq('#products_viewed_aside').append(product_str);
	    }
	    
	}
    };
    
    this.displayProductBlock = function(products_data){
	var product_str = "";
	Object.keys(products_data).forEach(function (key) {
	    product_str += fhs_account.getProduct(products_data[key]);
//	    product_str += $this.getProductBlock(products_data[key]);
	});
	if(product_str){
		$jq('ul#products_viewed_aside_block').append(product_str);
                $jq(".fhs-asidebar-block").show();
		$jq(".fhs-asidebar-block").addClass('active');
	    }
    };
    
    this.checkBlockInViewport = function(_limit){
	if($jq(window).width() <= 1000){
	    if($jq('#fhs-asidebar-product-block-content') && $jq('#productviewed_viewpoint')){
		if(Helper.isElementInViewport($jq('#productviewed_viewpoint'))){
		    $this.initProductViewedPage(false, _limit);
		}
	    }
	}
    };

//    this.getProduct = function (item){
//	var price = '';
//	let episode = '';
//	if(item['product_price'] != item['product_finalprice']){
//	    price = "<p class='old-price bg-white'><span class='price-label'>Giá bìa: </span><span id='old-price-175744' class='price'>"+item['product_price']+"&nbsp;đ</span></p>";
//	    price = "<p class='old-price'><span class='price'>"+item['product_price']+"đ</span></p>";
//	}
//	if(item['episode']){
//	    episode = "<div class='episode-label'>"+item['episode']+"</div>";
//	}
//	return "<li>"
//		+"<div class='item-inner'>"
//		    +item['discount_label_html']
//		    +"<div class='ma-box-content'>"
//			+"<div class='products clear'>"
//			    +"<div class='product images-container'>"
//				+"<a href='"+item['product_url']+"' title='"+item['product_name']+"' class='product-image'>"
//				    +"<div class='product-image'>"
//					+"<img class='lazyload' src='"+loading_icon_url+"' data-src='"+item['image_src']+"' alt='"+item['product_name']+"'>"
//				    +"</div>"
//				+"</a>"
//			    +"</div>"
//			+"</div>"
//			+"<h2 class='product-name-no-ellipsis'>"
//			    +"<a href='"+item['product_url']+"' title='"+item['product_name']+"' class='product-image'>"+item['product_name']+"</a>"
//			+"</h2>"
//			+"<div class='price-label'>"
//			    +"<p class='special-price'><span class='price m-price-font'>"+item['product_finalprice']+"&nbsp;đ</span></p>"
//			    +price
//			    +episode
//			+"</div>"
//			+"<div class='fhs-rating-container'>"
//			    +item['rating_html']
//			+"</div>"
//		    +"</div>"
//		+"</div>"
//	    +"</li>";
//    };
    
//    this.getProductBlock = function (item){
//	var price = '';
//        let ratingHtml = '';
//	let episode = '';
//	if(item['product_price'] != item['product_finalprice']){
//	    price = "<p class='old-price bg-white'><span class='price-label'>Giá bìa: </span><span id='old-price-175744' class='price'>"+item['product_price']+"&nbsp;đ</span></p>";
//	    price = "<p class='old-price'><span class='price'>"+item['product_price']+"đ</span></p>";
//	}
//         if(item['rating_html'].indexOf("amount") == -1){
//            ratingHtml = '<div class="ratings fhs-no-mobile-block"><div class="rating-box"><div class="rating" style="width:0%"></div></div><div class="amount">(0)</div></div>';
//        }else{
//            ratingHtml = item['rating_html'];
//        }
//	if(item['episode']){
//	    episode = "<div class='episode-label'>"+item['episode']+"</div>";
//	}
//	return "<li class='swiper-slide'>"
//		+"<div class='item-inner'>"
//		    +item['discount_label_html']
//		    +"<div class='ma-box-content'>"
//			+"<div class='products clear'>"
//			    +"<div class='product images-container'>"
//				+"<a href='"+item['product_url']+"' title='"+item['product_name']+"' class='product-image'>"
//				    +"<div class='product-image'>"
//					+"<img class='lazyload' src='"+loading_icon_url+"' data-src='"+item['image_src']+"' alt='"+item['product_name']+"'>"
//				    +"</div>"
//				+"</a>"
//			    +"</div>"
//			+"</div>"
//			+"<h2 class='product-name-no-ellipsis'>"
//			    +"<a href='"+item['product_url']+"' title='"+item['product_name']+"' class='product-image'>"+item['product_name']+"</a>"
//			+"</h2>"
//			+"<div class='price-label'>"
//			    +"<p class='special-price'><span class='price m-price-font'>"+item['product_finalprice']+"&nbsp;đ</span></p>"
//			    +price
//			    +episode
//			+"</div>"
//			+"<div class='fhs-rating-container'>"
//			    +ratingHtml
//			+"</div>"
//		    +"</div>"
//		+"</div>"
//	    +"</li>";
//    };

    this.formatCurrency = function (num) {
	return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')
      };
    this.isEmpty = function (obj){
	for(var key in obj) {
	    if(obj.hasOwnProperty(key))
		return false;
	}
	return true;
    };
}
