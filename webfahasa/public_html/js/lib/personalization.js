
var Personalization = function () {

    const SESSION_GRID_URL = "/personal/api/customers/grid";
    const SESSION_PAGE_URL = "/personal/api/customers/page";
    const SESSION_NEXT_PAGE_URL = "/personal/api/customers/next_page";
    const PRODUCTS_PER_ROW = 10;
    const DEFAULT_CAT_ID = 'first_category';
    const MUMBER_IMPORTANT_CATEGORIES = 4;
    const CAMPAIGN_TRACKING_PRODUCT_URL = "fhs_campaign=PERSONALIZE_PRODUCT";
    const CAMPAIGN_TRACKING_TAB_URL = "fhs_campaign=PERSONALIZE_TAB";
    const VERTICALLY_PRODUCTS_PER_ROW = 2;
    let customer_id;
    let $this = this;
    let session_id;
    let $grid_menu_item;
    let current_page_cat_id;
    let products_data = {};
    let $grid_block = $jq("#personalization-grid");
    let $page_bottom = $jq("#personalization-page-bottom");
    let $page_container = $jq("#personalization-page-container");
    let $page_loading_icon = $jq("#personalization-page-loading");
    let order_by;
    let is_mobile;
    let vertically_item_limit = 2;
    let vertically_is_show_btnmore = false;
    let vertically_item_showmore = 0;
    let last_product_id = 0;
    let check_load_grid = false; // phan biet load product o page voi grid (mobile)
    
    let language = {};
    let is_frist = true;
    
    this.initGrid = function (_customer_id, _is_mobile, _session_id, _load_grid = false) {
        
        customer_id = _customer_id;
        is_mobile = _is_mobile;
        session_id = _session_id;
        check_load_grid = _load_grid;
        $this.getGridProducts(DEFAULT_CAT_ID, true);
       
    }
    this.getGridProducts = function (cat_id, to_randomize) {

        let $container = $jq("#personalization-grid-container");
        let $loading_icon = $jq("#personalization-grid-loading");
        $container.empty();
        $container.hide();
        if ($loading_icon != null) {
            $loading_icon.show();
        }

        let url = SESSION_GRID_URL;
        return;
        let content_type = 'application/json; charset=utf-8';
        let data = JSON.stringify({
            customer_id: session_id,
            cat_id: cat_id,
            to_randomize: to_randomize,
            c_id: customer_id
        });
        
        $jq.ajax({
            url: url,
            method: 'post',
            dataType: "json",
            contentType: content_type,
            data: data,
            success: function (data) {
                if ($loading_icon != null) {
                    $loading_icon.hide();
                }

                if (!data.result || !data.categories || !data.products || !data.cat_id) {
                    $jq("#personalization-grid").hide();
                    return;
                }
                
                cat_id = data.cat_id;

                $jq("#personalization-grid").show();
                if (is_mobile) {
                    $this.buildGridMenuMobile(cat_id, data.categories);
                } else {
                    $this.buildGridMenu(cat_id, data.categories);
                }
                //// Grid Products
                $container.show();
                /// add products to $container
                data.products = data.products.filter(function (el) {
                    return el != null;
                });

                // To randomize product order for 'All' only
                if (cat_id == 'all' || cat_id == 'other') {
                    data.products = Helper.shuffle(data.products);
                }

                $this.displayProductsInGrid(data.products, $container);
                $this.setMoreBtnLink(cat_id);
            }
        });
    }

    this.buildGridMenu = function (current_cat_id, categories) {
        //// Grid Block Menu
        let $grid_menu = $jq("#personalization-header-menu");
        $grid_menu.empty();

        let $item, i = 0;
        for (; i < categories.length && i < MUMBER_IMPORTANT_CATEGORIES; i++) {
            $item = $jq("<li class='personalization-menu-item'><a>" + categories[i].name + "</a></li>");
            if (i == 0) {
                $grid_menu_item = $item;
                $item.addClass('active');
            }
            $item.attr('data-id', categories[i].id);
            $item.click($this.gridMenuItemClick);
            $grid_menu.append($item);
        }

        let $more_button;
        if (i < categories.length) {
            $more_button = $jq("<li id='personalization-menu-item-more'>"
                    + "<a>Thêm</a>"
                    + "<ul id='personalization-menu-item-more-option'></ul>"
                    + "</li>");

            $grid_menu.append($more_button);

            let $more_options = $more_button.find("#personalization-menu-item-more-option");
            while (i < categories.length) {
                $item = $jq("<li class='personalization-menu-subitem'><a>" + categories[i].name + "</a></li>");
                $item.attr('data-id', categories[i].id);
                $item.click($this.gridMenuItemClick);
                $more_options.append($item);
                i++;
            }

            $more_button.click(function (e) {
                if ($more_options.is(":visible")) {
                    $more_options.hide();
                } else {
                    $more_options.show();
                }

                return false;
            });

            $jq(document).click(function () {
                if ($more_options.is(":visible")) {
                    $more_options.hide();
                }
            });
        }

        let has_subitem_active = false;
        let $menu_items = $jq("#personalization-header-menu li");
        $menu_items.each(function () {
            let _$this = $jq(this);
            let _id = _$this.attr('data-id');
            if (current_cat_id == _id) {
                if ($grid_menu_item && $grid_menu_item.hasClass('active')) {
                    $grid_menu_item.removeClass('active');
                }

                $grid_menu_item = _$this;
                _$this.addClass('active');
                if (_$this.hasClass('personalization-menu-subitem')) {
                    has_subitem_active = true;
                }
            }
        });

        if ($more_button) {
            if (has_subitem_active) {
                $more_button.addClass('active');
            } else {
                $more_button.removeClass('active');
            }
        }
    }

    this.buildGridMenuMobile = function (current_cat_id, categories) {

        $jq("#personalization-menu-mobile ul").empty();
        let $item;
        for (let i = 0; i < categories.length; i++) {
            products_data[categories[i].id] = {
                is_loading: false,
                is_fully_loaded: false,
                current_page: -1,
                is_active: 0
            }

            $item = $jq("<li class='swiper-slide personalization-menu-item personalization-menu-mobile-item'><a>"
                    + categories[i].name + "</a></li>");

            $item.attr('data-id', categories[i].id);
            $item.click($this.gridMenuItemClick);

            if (i == 0) {
                $grid_menu_item = $item;
                $item.removeClass('active');
            }

            if (current_cat_id == categories[i].id) {
                if ($grid_menu_item && $grid_menu_item.hasClass('active')) {
                    $grid_menu_item.removeClass('active');
                }

                $item.addClass('active');
                $grid_menu_item = $item;
            }

            $jq("#personalization-menu-mobile ul").append($item);
        }

        new Swiper('#personalization-menu-mobile', {
            direction: 'horizontal',
        });
    }

    this.gridMenuItemClick = function (e) {
        let cat_id = $jq(this).attr('data-id');
        if (!cat_id) {
            return;
        }

        $this.setMoreBtnLink(cat_id);

        $this.getGridProducts(cat_id, null);
    }

    this.displayProductsInGrid = function (products, $container) {
        let $current_row;
        if(is_mobile && check_load_grid == true){
            // set div swiper container cho no
            $current_row = $jq("<div class='swiper-personal-mobile-grid'><div class='swiper-wrapper swiper-wrapper-personal-mobile'></div></div>");
            $container.append($current_row);
        }
        for (let i = 0; i < products.length; i++) {
            products[i]['product_url'] += "?" + CAMPAIGN_TRACKING_PRODUCT_URL;
            if (is_mobile && check_load_grid) {
                // display slider for mobile personal
                $item = Helper.printProductHtmlClone(products[i], is_mobile);
                $item_li = $jq("<div class='personalization-item swiper-slide' style='float:left;'></div>");
                $item_li.append($item);
                $jq(".swiper-wrapper-personal-mobile").append($item_li);
            } else {
                if ((i % PRODUCTS_PER_ROW) == 0) {
                    $current_row = $jq("<div ></div>");
                    $container.append($current_row);
                }

                $item = Helper.printProductHtml(products[i], is_mobile);
                $item_li = $jq("<div class=' personalization-item'></div>");
                $item_li.append($item);
                $current_row.append($item_li);
            }
        }
        if (is_mobile && check_load_grid) {
            new Swiper($current_row, {
                slidesPerView: 'auto',
                freeMode: true,
                longSwipesMs: 800,
                preloadImages: false,
                spaceBetween: 0,
                lazy: {
                    enabled: true,
                    loadPrevNext: true,
                    loadPrevNextAmount: 3
                }
            });
        }
    }

    this.initPage = function (_customer_id,_is_mobile, _session_id) {
        
        customer_id = _customer_id;
        is_mobile = _is_mobile;
        session_id = _session_id;      
        current_page_cat_id = Helper.getQueryParam('cat_id');
        if(!current_page_cat_id){
            current_page_cat_id = 'false';
        }
        $this.getFirstPageProducts(current_page_cat_id);
       
    }

    this.getFirstPageProducts = function (cat_id) {
        $page_container.empty();
        $page_loading_icon.show();
       
        let content_type = 'application/json; charset=utf-8';
        let url = SESSION_PAGE_URL;
        let data = JSON.stringify({
                customer_id: session_id,
                cat_id: cat_id,
                order_by: order_by,
                c_id: customer_id
            });
        

        $jq.ajax({
            url: url,
            method: 'post',
            dataType: "json",
            contentType: content_type,
            data: data,
            success: function (data) {
                $page_loading_icon.hide();
                if (!data.result || !data.categories || !data.products) {
                    $jq("#personalization-not-loggedin").show();
                    return;
                }

                cat_id = data.cat_id

                if (is_mobile) {
                    $this.buildPageMobileMenu(cat_id, data.categories);
                } else {
                    $this.buildPageMenu(cat_id, data.categories);
                }

                data.products = data.products.filter(function (el) {
                    return el != null;
                });
                
                if (data.products.length > 0) {
                    last_product_id = data.products[data.products.length - 1].product_id;
                }
                /// add products to $container
                $this.displayProductsInGrid(data.products, $page_container);
                /// Start checking viewport
                var to_check_bottom = true;
                $jq(window).on('resize scroll', function () {
                    if (to_check_bottom) {
                        $this.checkPageBottom();
                        to_check_bottom = false;
                        setTimeout(function () {
                            to_check_bottom = true;
                        }, 250);
                    }
                });
            }


        });
    }

    this.buildPageMenu = function (cat_id, categories) {
        //// Page Menu
        let $page_menu = $jq("#personalization-page-menu");
        $page_menu.empty();

        let $item, i = 0;
        for (; i < categories.length && i < MUMBER_IMPORTANT_CATEGORIES; i++) {
            products_data[categories[i].id] = {
                is_loading: false,
                is_fully_loaded: false,
                current_page: 0,
                is_active: 0
            }

            let url = "?cat_id=" + categories[i].id + "&" + CAMPAIGN_TRACKING_TAB_URL;
            $item = $jq("<li class='personalization-menu-item'><a href='"
                    + url + "'>" + categories[i].name + "</a></li>");

            $item.attr('data-id', categories[i].id);
            $item.click(function () {
                let id = $jq(this).attr('data-id');
                window.location.href = "?cat_id=" + id + "&" + CAMPAIGN_TRACKING_TAB_URL;
            });

            if (i == 0) {
                $grid_menu_item = $item;
                $item.addClass('active');
                current_page_cat_id = categories[i].id;
            }

            $page_menu.append($item);
        }

        let $more_button;
        if (i < categories.length) {
            $more_button = $jq("<li id='personalization-menu-item-more'>"
                    + "<a>Thêm</a>"
                    + "<ul id='personalization-menu-item-more-option'></ul>"
                    + "</li>");

            $page_menu.append($more_button);

            let $more_options = $more_button.find("#personalization-menu-item-more-option");
            while (i < categories.length) {
                products_data[categories[i].id] = {
                    is_loading: false,
                    is_fully_loaded: false,
                    current_page: 0,
                    is_active: 0
                }
                $item = $jq("<li class='personalization-menu-subitem'><a>" + categories[i].name + "</a></li>");

                $item.attr('data-id', categories[i].id);

                $item.click(function () {
                    let id = $jq(this).attr('data-id');
                    window.location.href = "?cat_id=" + id + "&" + CAMPAIGN_TRACKING_TAB_URL;
                });
                $more_options.append($item);
                i++;
            }

            $more_button.click(function (e) {
                if ($more_options.is(":visible")) {
                    $more_options.hide();
                } else {
                    $more_options.show();
                }

                return false;
            });

            $jq(document).click(function () {
                if ($more_options.is(":visible")) {
                    $more_options.hide();
                }
            });
        }

        let has_subitem_active = false
        let $menu_items = $jq("#personalization-page-menu li");
        $menu_items.each(function () {
            let _$this = $jq(this);
            let _id = _$this.attr('data-id');
            if (cat_id == _id) {
                if ($grid_menu_item && $grid_menu_item.hasClass('active')) {
                    $grid_menu_item.removeClass('active');
                }
                $grid_menu_item = _$this;
                _$this.addClass('active');
                current_page_cat_id = cat_id;
                if (_$this.hasClass('personalization-menu-subitem')) {
                    has_subitem_active = true;
                }
            }
        });

        if ($more_button) {
            if (has_subitem_active) {
                $more_button.addClass('active');
            } else {
                $more_button.removeClass('active');
            }
        }
    }

    this.buildPageMobileMenu = function (current_cat_id, categories) {
        $jq("#personalization-menu-mobile ul").empty();

        let $item;
        for (let i = 0; i < categories.length; i++) {
            products_data[categories[i].id] = {
                is_loading: false,
                is_fully_loaded: false,
                current_page: -1,
                is_active: 0
            }

            let url = "?cat_id=" + categories[i].id + "&" + CAMPAIGN_TRACKING_TAB_URL;

            $item = $jq("<li class='swiper-slide personalization-menu-item personalization-menu-mobile-item'><a href='"
                    + url + "'>" + categories[i].name + "</a></li>");

            $item.attr('data-id', categories[i].id);
            $item.click($this.gridMenuItemClick);

//            if (i == 0) {
//                $grid_menu_item = $item;
//                $item.addClass('active');
//            }

            if (current_cat_id == categories[i].id) {
                if ($grid_menu_item && $grid_menu_item.hasClass('active')) {
                    $grid_menu_item.removeClass('active');
                }

                $item.addClass('active');
                $grid_menu_item = $item;
            }

            $jq("#personalization-menu-mobile ul").append($item);
        }

        new Swiper('#personalization-menu-mobile', {
            direction: 'horizontal'
        });
    }

    this.checkPageBottom = function () {
        var data = products_data[current_page_cat_id];
        if (data && !data.is_fully_loaded
                && !data.is_loading
                && Helper.isElementInViewport($page_bottom)) {
            $this.getNextPageProducts(current_page_cat_id, data.current_page + 1);
        }
    }

    this.getNextPageProducts = function (cat_id, page) {
        let $loading_icon = $jq("#personalization-page-loading");
        $loading_icon.show();
        products_data[cat_id].is_loading = true;
       
        let url = SESSION_NEXT_PAGE_URL;
        let content_type = 'application/json; charset=utf-8';
        let data = JSON.stringify({
            customer_id: session_id,
            cat_id: cat_id,
            page: page,
            order_by: order_by,
            c_id: customer_id,
            last_product: last_product_id
        });
        
        $jq.ajax({
            url: url,
            method: 'post',
            dataType: "json",
            contentType: content_type,
            data: data,
            success: function (data) {
                $loading_icon.hide();
                if (!data.result || !data.categories || !data.products) {
                    products_data[cat_id].is_loading = false;
                    return;
                }

                if (data.products.length == 0) {
                    products_data[cat_id].is_fully_loaded = true;
                }

                products_data[cat_id].is_loading = false;
                products_data[cat_id].current_page = page;
                /// add products to $container
                data.products = data.products.filter(function (el) {
                    return el != null;
                });
                
                if (data.products.length > 0) {
                    last_product_id = data.products[data.products.length - 1].product_id;
                }
                
                $this.displayProductsInGrid(data.products, $page_container);
            }
        });
    }

    this.setOrderBy = function (_order_by) {
        order_by = _order_by;
        $this.initPage(customer_id, is_mobile, session_id);
    }

    this.setMoreBtnLink = function (cat_id) {
        let href_data = $jq("#personalization-more-link").attr('href-data');
        $jq("#personalization-more-link").attr("href", href_data + "&cat_id=" + cat_id);
    }

    this.initVertically = function (_customer_id, _is_mobile, _session_id, _language) {
        customer_id = _customer_id;
        is_mobile = _is_mobile;
        session_id = _session_id;
	check_load_grid = true;
       
	$this.language = _language;
	$jq("#desc_viewmore").click(function(){$this.viewMoreVertically();});
	$jq('.product-view-tab-item').click(function(){
	    if($jq(this).hasClass("active")){return;}
	    
	    if(!$jq(this).hasClass("active")){
		$jq('.product-view-tab-item').removeClass("active");
		$jq(this).addClass("active");
	    }
	    if($jq(this).hasClass("product-view-tab-info-item")){
		$jq('#product_view_tab_content_review').fadeOut(0);
		$jq('#product_view_tab_content_ad').fadeIn(0);
		$this.updateQueryStringParam('review','close');
	    }else{
		$jq('#product_view_tab_content_ad').fadeOut(0);
		$jq('#product_view_tab_content_review').fadeIn(0);
		$this.updateQueryStringParam('review','open');
		if(prodComment.is_first){
		    prodComment.loadComment();
		}
	    }
	    setTimeout(function(){$this.redisplayVertically();}, 100);
	});
	
	if(!is_mobile){
	    $this.getVerticallyProducts(DEFAULT_CAT_ID, true);
            
            setTimeout(function(){
		$this.getGridProducts(DEFAULT_CAT_ID, true);
	    },100);
	}else{
	    setTimeout(function(){
		$this.getGridProducts(DEFAULT_CAT_ID, true);
	    },500);
	}
    }
    this.tabVertically_choice = function(_class){
	$tab = $jq('.'+_class);
	if($tab.hasClass("active")){return;}
	    
	    if(!$tab.hasClass("active")){
		$jq('.product-view-tab-item').removeClass("active");
		$tab.addClass("active");
	    }
	    if(_class == 'product-view-tab-info-item'){
		$jq('#product_view_tab_content_review').fadeOut(0);
		$jq('#product_view_tab_content_ad').fadeIn(0);
	    }else{
		$jq('#product_view_tab_content_ad').fadeOut(0);
		$jq('#product_view_tab_content_review').fadeIn(0);
	    }
	    setTimeout(function(){$this.redisplayVertically();}, 100);
    }
    this.getVerticallyProducts = function (cat_id, to_randomize) {
        let $container = $jq("#personalization-vertically-container");
        let $product_additional_description = $jq('#product_view_tab');
        let $personalization_vertically = $jq('#personalization-vertically');
        $container.empty();
        $container.hide();
        
        let url = SESSION_GRID_URL;
        let data = JSON.stringify({
            customer_id: session_id,
            cat_id: cat_id,
            to_randomize: to_randomize,
            c_id: customer_id
        });
        let content_type = 'application/json; charset=utf-8';        

        $jq.ajax({
            url: url,
            method: 'post',
            dataType: "json",
            contentType: content_type,
            data: data,
            success: function (data) {
                if (!data.result || !data.categories || !data.products || !data.cat_id) {
		    setTimeout(function(){$this.resizeDesc();}, 100);
                    return;
                }
                if (!is_mobile && data.products.length < 5) {
		    setTimeout(function(){$this.resizeDesc();}, 100);
                    return;
                }

                cat_id = data.cat_id;

                //// Grid Products
                $container.show();
                /// add products to $container
                data.products = data.products.filter(function (el) {
                    return el != null;
                });
                // To randomize product order for 'All' only
                if (cat_id == 'all' || cat_id == 'other') {
                    data.products = Helper.shuffle(data.products);
                }
		$this.products_data = data.products;
		vertically_item_showmore = 0;
                $this.displayProductsInVertically(data.products, $container);
		if(!is_mobile){
		    $product_additional_description.addClass('personal');
		}
                $personalization_vertically.addClass('personalization-vertically');
		setTimeout(function(){$this.resizeDesc();}, 100);
            },
	    fail: function() {
		setTimeout(function(){$this.resizeDesc();}, 100);
	    },
	    statusCode: {
	    404: function() {
		setTimeout(function(){$this.resizeDesc();}, 100);
	    }
  }
        });
    }
    this.redisplayVertically = function(is_more = false){
	    $jq('#product_view_tab_content_review').removeClass('desc_viewmore_showmore');
	    $jq('#product_view_tab_content_ad').removeClass('desc_viewmore_showmore');
	    $jq("#product_view_tab_content_ad").css('height', "");
	    $jq("#product_view_tab_content_review").css('height', "");
	    $jq("#product_view_tab_content_ad").css('minHeight', "");
	    $jq("#product_view_tab_content_review").css('minHeight', "");
	    vertically_item_showmore = 0;
	    vertically_is_show_btnmore = false;
	    $this.displayProductsInVertically($this.products_data, $jq("#personalization-vertically-container"));
	    
	    setTimeout(function(){$this.resizeDesc(is_more);}, 100);
    }

    this.displayProductsInVertically = function (products, $container) {
	if(!products){return;}
        let $current_row;
	let $content_html = $jq('<div></div>');
        if (is_mobile) {
            vertically_item_limit = 4;
        }
        
        for (let i = 0; i < products.length; i++) {
            if (!vertically_is_show_btnmore) {
                if (i >= vertically_item_limit) {
                    continue;
                }
            } else {
                if (i >= (vertically_item_limit + vertically_item_showmore)){
                    continue;
                }
            }
              // tai sao lai comment ? => tai sao lai render lan 
              // => noi chuoi CAMPAIGN_TRACKING_PRODUCT_URL toi 3 lan  
              // handle trong render product helper printProductVerticallyHtml
              //products[i]['product_url'] += "?" + CAMPAIGN_TRACKING_PRODUCT_URL;
	    
            if (is_mobile) {
                if ((i % VERTICALLY_PRODUCTS_PER_ROW) == 0) {
                    $current_row = $jq("<div ></div>");
                    $content_html.append($current_row);
                }
            } else {
                $current_row = $jq("<div  ></div>");
                $content_html.append($current_row);
            }
            $item = Helper.printProductVerticallyHtml(products[i], is_mobile, true);
            let showmore_class = "";
            let showmore_style = "";
            if (i >= vertically_item_limit) {
                showmore_class = "personalization-item-showmore";
                showmore_style = "style='display:none;'";
            }

            if (is_mobile) {
                $item_li = $jq("<div class='personalization-item'></div>");
            } else {
                $item_li = $jq("<div class='personalization-item " + showmore_class + "' " + showmore_style + "></div>");
            }
            $item_li.append($item);
            $current_row.append($item_li);
        }
	$container.html($content_html.html());
    }
    this.resizeDesc = function (is_more = false) {
        let personal = $jq("#personalization-vertically");
        let info = $jq("#product_view_tab_content_ad");
	let review = $jq('#product_view_tab_content_review');
        let personal_height = 725;
	if(personal.height() < personal_height){
	    personal_height = personal_height + 30;
	}else{
	    personal_height = Math.round(personal.height()) + 30;
	}
	let info_height = Math.round(info.height()+25);
	let review_height = Math.round(review.height()+25);
	let tab_menu_height = Math.round($jq(".product-view-tab").height() +26);
	let more_btn_height = Math.round($jq('#desc_viewmore').height());
	
	let height_full = 0;
	let height_more = 0;
	let height_default = 0;
	
	if($jq("#desc_viewmore").is(":visible")){
	    info_height = info_height + more_btn_height;
	}
	
	if($jq(".product-view-tab-info-item").hasClass('active')){
	    height_full = (info_height + tab_menu_height);
	    height_default = (personal_height - tab_menu_height)-3;
	    height_more = height_full - personal_height;
	    
	    if (is_mobile) {
		if (height_full > 755) {
		    height_more = 755;
		} else {
		    height_more = 0;
		}
	    }
	    
	    if (height_more > 0) {
		info.css('height', (height_default-more_btn_height)+"px");
		info.css('minHeight',(height_default-more_btn_height)+"px");
		$jq('#btn_showmore').text($this.language['ViewMore']);
		$jq("#desc_viewmore").fadeIn(0);
		vertically_is_show_btnmore = true;
		vertically_item_showmore = Math.floor(height_more / 333);
		$this.displayProductsInVertically($this.products_data, $jq("#personalization-vertically-container"));
	    }
	    else{
		if(!is_mobile){
		    if(!$this.products_data){
			info.css('minHeight',"");
		    }else{
			info.css('minHeight',height_default+"px");
		    }
		}else{
		    info.css('minHeight',"");
		}
		vertically_is_show_btnmore = false;
		$jq("#desc_viewmore").fadeOut(0);
	    }
	    
	}else{
	    height_full = (review_height + (tab_menu_height + 15));
	    height_default = personal_height - (tab_menu_height + 15)-3;
	    height_more = height_full - personal_height;
	    
	    if (is_mobile) {
		if (height_full > 755) {
		    height_more = 755;
		} else {
		    height_more = 0;
		}
	    }
	    
	    if (height_more > 0) {
		review.css('height', (height_default-more_btn_height)+"px");
		review.css('minHeight',(height_default-more_btn_height)+"px");
		vertically_is_show_btnmore = true;
		vertically_item_showmore = Math.floor(height_more / 333);
		$this.displayProductsInVertically($this.products_data, $jq("#personalization-vertically-container"));
		if(!is_more){
		    if($jq('#product_view_tab_content_review').hasClass('desc_viewmore_showmore')){
			$jq('#product_view_tab_content_review').removeClass('desc_viewmore_showmore');
		    }
		    $jq('#btn_showmore').text($this.language['ViewMore']);
		    
		}else{
		    if(!$jq('#product_view_tab_content_review').hasClass('desc_viewmore_showmore')){
			$jq('#product_view_tab_content_review').addClass('desc_viewmore_showmore');
		    }
		    $jq(".personalization-item-showmore").show();
		    $jq('#btn_showmore').text($this.language['ViewLess']);
		}
		$jq("#desc_viewmore").fadeIn(0);
	    }
	    else{
		if(!is_mobile){
		    if(!$this.products_data){
			review.css('minHeight',"");
		    }else{
			review.css('minHeight',height_default+"px");
		    }
		}else{
		    review.css('minHeight',"");
		}
		vertically_is_show_btnmore = false;
		$jq("#desc_viewmore").fadeOut(0);
	    }
	}
	setTimeout(function(){$this.randomProductData();}, 100);
    }
    this.viewMoreVertically = function(){
	if($jq(".product-view-tab-info-item").hasClass('active')){
	    $jq('#product_view_tab_content_review').removeClass('desc_viewmore_showmore');
	    
	    if($jq('#product_view_tab_content_ad').hasClass('desc_viewmore_showmore')){
		$jq('#product_view_tab_content_ad').removeClass('desc_viewmore_showmore');
		$jq(".personalization-item-showmore").hide();
		$jq('#btn_showmore').text($this.language['ViewMore']);
		$jq('html, body').stop().animate({
		    scrollTop: $jq('#product_view_tab').offset().top
		}, 1000);
	    }else{
		$jq('#product_view_tab_content_ad').addClass('desc_viewmore_showmore');
		$jq('#btn_showmore').text($this.language['ViewLess']);
		$jq(".personalization-item-showmore").show();
	    }
	}else{
	    $jq('#product_view_tab_content_ad').removeClass('desc_viewmore_showmore');
	    
	    if($jq('#product_view_tab_content_review').hasClass('desc_viewmore_showmore')){
		$jq('#product_view_tab_content_review').removeClass('desc_viewmore_showmore');
		$jq(".personalization-item-showmore").hide();
		$jq('#btn_showmore').text($this.language['ViewMore']);
		$jq('html, body').stop().animate({
		    scrollTop: $jq('#product_view_tab').offset().top
		}, 1000);
	    }else{
		$jq('#product_view_tab_content_review').addClass('desc_viewmore_showmore');
		$jq('#btn_showmore').text($this.language['ViewLess']);
		$jq(".personalization-item-showmore").show();
	    }
	}
    }
    this.randomProductData = function(){
	if($this.products_data){
	    $this.products_data = Helper.shuffle($this.products_data);
	}
    }
    this.updateQueryStringParam = function(key, value) {
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
    
    this.login = function (_oldsession, _newsession, _customer_id) {

        if (typeof _oldsession == 'undefined' || _oldsession == null || _oldsession == '') {
            return;
	}

        $jq.ajax({
            url: "personal/api/customers/login",
            method: 'post',
            dataType: "json",
            contentType: "application/json; charset=utf-8",
            data: JSON.stringify({
                old_session: _oldsession,
                new_session: _newsession,
                customer_id: _customer_id,
            }),
//            success: function(data)
//            {}
            timeout: 2000
        });
    }

}
