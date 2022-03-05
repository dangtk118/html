
/*
 *  SCRIPT LOAD TWICE, prevent to load once
 * 
 */

var Tabslider = function () {
    const MAX_ITEMS = 60;
    const TOTAL_GRID_PRODUCTS_PER_SLIDE = 4;
    const FHS_CAMPAIGN_RECOMMENDED = "?fhs_campaign=RELATED_PRODUCT_2";
    const FAHASA_DOMAIN   = window.location.origin;
    const POST_GRIDSLIDER = FAHASA_DOMAIN + "/node_api/gridproduct/dataProgressBar";
    
    var $this = this;
    var get_url;
    var block_limit;
    var current_block_id;
    var is_grid_slider;
    var block_item_width;
    var loading_icon_url;
    var number_of_rows;
    var fhs_campaign_str;
    
    var current_tab_active_id = "";
    var tab_loaded_list = {};
    var $current_tab_label;
    var is_tab_first_loading = false;
    var current_block_data_array = {};
    var $current_block_tab;
    
    var flashsale_data;
    var is_window_ready = false;
    var has_check_flashsale = false;
    var is_blog = false;
    
    var data_slider;
    var swiper_list = {};
    
    // variable for girdslider 
    var check_gird_slider = false;
    var has_block_left_data = false;
    let $page_bottom = $jq("#girdslider-page-bottom"); // check srocll view khi srocll xuong 
    var is_data_next_loaded = false; // on/off get more product;
    var is_data_first_loaded = false;
    var last_item_id = 0; // lay danh sach tiep theo tinh tu last_item
    var lastItem;
    var get_url_next;
    var get_url_load;
    var jsonData = ""; // danh sach list 
    var limitProduct = 24;
    var current_tab_slider = null;
    var tab_first_mobile = false;
    var tab_active_key_child = null;
    var loading_icon_girds;
    var mobile_grid_page; // kt mobile o page grid
    var type_name_order = "num_orders";
    var data_list_loadmore = null; // for loadmore gridslider page
    var is_show_bar = true; // show bar gridslider page;
    var arrowPrevTab; // giu ten tab of block da deActive;
    var array_fhs_campaign; // array gom : on/off - text fhs_campaign trong link tung product (-hien tai active products trong page-hot-deal)
    var blockTypePage = null; // load by attribute or listId ?
    var attribute_grid_code = null;
    var attribute_grid_value = null;
    var page_grid = 1;
    var show_buy_now = false;
    //-------------------
    
    this.setBlog = function (_is_blog){
	is_blog = _is_blog;
    }
    
    this.init = function (_get_url, _block_limit, _block_id, _block_data_str, _is_grid, _item_width
    , _loading_icon_url, _number_of_rows, _fhs_campaign_str, _check_gird_slider = false, _has_block_left_data = false, _show_buy_now = false) {
        get_url = _get_url;
        block_limit = _block_limit;
        if (block_limit == "") {
            block_limit = MAX_ITEMS;
        }
        
        check_gird_slider = _check_gird_slider;
        has_block_left_data = _has_block_left_data;
        current_block_id = _block_id;
        var block_data_json = JSON.parse(_block_data_str);
        is_grid_slider = _is_grid;
        block_item_width = _item_width;
        loading_icon_url = _loading_icon_url;
        number_of_rows = _number_of_rows || 1;
        fhs_campaign_str = _fhs_campaign_str;
        
	show_buy_now = _show_buy_now;
	
        current_tab_active_id;
        // check tab active ?????
        for (var i = 0; i < block_data_json.length; i++) {
            for (var key in block_data_json[i]) {
                current_block_data_array[key] = block_data_json[i][key];
                if (current_block_data_array[key]['active']) {
                    current_tab_active_id = key;
                }
            }
        }
        // neu khong tab nao co active => mac dinh tab dau tien
        if (!current_tab_active_id) {
            current_tab_active_id = Object.keys(current_block_data_array)[0];
        }

        $current_block_tab = $jq("#tab" + current_tab_active_id + "-" + current_block_id);
        $current_block_tab.show();
        
        var $block = $jq("#categorytab-" + current_block_id);
        $block.bind('query', function () {
            $this.checkBlockInViewport();
        });
         
        // su kien click tab khac
        $jq("#categorytab-" + current_block_id + " .tabslider-tabs li").click(function (e) {
            if ($current_tab_label) {
                $current_tab_label.removeClass("active");
            }

            $current_tab_label = $jq(e.target);
            var tab_id = $current_tab_label.attr("rel");

            $current_tab_label.addClass("active");
            $current_block_tab.hide();
            current_tab_active_id = tab_id;
            $current_block_tab = $jq("#tab" + tab_id + "-" + current_block_id);

            /// if we already load the tab, we just need to show it.
            if (tab_loaded_list[tab_id]) {
                $current_block_tab.fadeIn();
                if (_check_gird_slider == false) {
                    // display none tab cu // khi da load data xong;
                    $this.activeArrowSliderTab(arrowPrevTab, current_block_id, false);
                    // show arrow tab moi // khi da load data xong;
                    $this.activeArrowSliderTab(tab_id, current_block_id, true);
                }
                return;
            }

            tab_loaded_list[tab_id] = true;
            if(_check_gird_slider == true){
                $this.loadTabsliderProductsSlider(tab_id, current_block_data_array[tab_id]);
                check_gird_slider = true;
            }else{
                $this.loadTabsliderProducts(tab_id, current_block_data_array[tab_id]);
                // display none tab cu;
                $this.activeArrowSliderTab(arrowPrevTab, current_block_id, false);
                // show arrow tab moi;
                $this.activeArrowSliderTab(tab_id, current_block_id, true);
                check_gird_slider = false;
            }
        });
        
        /// Start checking viewport
        $jq(window).ready(function () {
            is_window_ready = true;
            ///console.log("1");
            if(has_check_flashsale){
                //console.log("Is check ready");
                $this.checkBlockInViewport();
            }
            
            var to_check_resize = true;
            $jq(window).on('resize scroll', function () {
                /// console.log("Is Resizing");
                if(to_check_resize){
                    $this.checkBlockInViewport();
                    to_check_resize = false;
                    setTimeout(function(){
                        to_check_resize = true;
                    }, 200);
                }
            });
	    setTimeout(function(){$this.checkBlockInViewport();},1000);
        });
        
        $jq(window).bind("flashsale_storage", function (event) {
            var data = localStorage.getItem("flashsale");
            try {
                flashsale_data = JSON.parse(data);
            } catch (e) {
                flashsale_data = null;
            }
            
            has_check_flashsale = true;
//          console.log("2");
            if(is_window_ready){
//              console.log("Is window ready");
                $this.checkBlockInViewport();
            }
        });
    }

    this.checkBlockInViewport = function () {
        if (!is_tab_first_loading && Helper.isElementInViewport($current_block_tab)) {
            is_tab_first_loading = true;
            tab_loaded_list[current_tab_active_id] = true;
            if (check_gird_slider == true) {
                $this.loadTabsliderProductsSlider(current_tab_active_id, current_block_data_array[current_tab_active_id]);
            } else {
                $this.loadTabsliderProducts(current_tab_active_id, current_block_data_array[current_tab_active_id]);
                // load Arrow cua tab dau tien : 
                $this.activeArrowSliderTab(current_tab_active_id,current_block_id,true);
            }
            //// Some tabs don't have active attribute, in magento static block, manually select first active tab
            $all_tab_labels = $jq("#categorytab-" + current_block_id + " .tabslider-tabs li");
	    if($all_tab_labels.length > 0){
		var selected_dom = chooseActive($all_tab_labels);
		$current_tab_label = $jq(selected_dom);
	    }
        }
    }

    this.loadTabsliderProducts = function (tab_id, data_str) {
//        console.log("Data");
//        console.log(flashsale_data);
        var $block_tab = $jq("#tab" + tab_id + "-" + current_block_id);
        var $block_tab_xem_them = $jq("#tab" + tab_id + "-" + current_block_id + " .tabs-xem-them");
        var $slider_ul = $jq("#tab" + tab_id + "-" + current_block_id + " .bxslider");
        var $current_block_loading_icon = $jq("#categorytab-" + current_block_id + " .tabslider-loading-icon");
        data_str = JSON.stringify(data_str);
        //console.log("Load Slider for tab " + tab_id + " -- grid = " + is_grid_slider);
        var data_json = JSON.parse(data_str);
        var randomFlag = true;
        if(data_json['shouldRandom'] === "false")
        {
           randomFlag = false;
        }
        if(data_json['fhsCampaign']){
            fhs_campaign_str = data_json['fhsCampaign'];
        }else {
            fhs_campaign_str = "";
        }
        $block_tab.show();
        $current_block_loading_icon.show();
        let backup_cat_id = 0;
	if(data_json['backup_cat_id']){
	    backup_cat_id = data_json['backup_cat_id'];
	}
        let backup_sort_by = 'num_orders';
	if(data_json['backup_sort_by']){
	    backup_sort_by = data_json['backup_sort_by'];
	}
        $jq.ajax({
            url: get_url,
            method: 'get',
            data: {
                limit: block_limit,
                sort_by: data_json['sort_by'],
                min_ck: data_json['min_ck'],
                max_ck: data_json['max_ck'],
                category_id: data_json['category_id'],
                block_type: data_json['block_type'],
                attribute_code: data_json['attribute_code'],
                attribute_value: data_json['attribute_value'],
                attribute_data: data_json['attribute_data'],
                list: data_json['list'],
                product_id: data_json['product_id'],
                exclude_catId: data_json['exclude_catId'],
		backup_cat_id: backup_cat_id,
		backup_sort_by: backup_sort_by,
		show_buy_now: show_buy_now,
                series_id : data_json['series_id']
            },
            success: function (product_list) {
                $slider_ul.empty();
                var product_count = product_list.length;
                if (product_count === 0 && tab_id == "related-products") {
                    $jq("#categorytab-" + current_block_id).hide();
                    return;
                }
                
                if(fhs_campaign_str != FHS_CAMPAIGN_RECOMMENDED && randomFlag){
                    product_list = Helper.shuffle(product_list);
                }
                
                if (is_grid_slider) {
                    let $right = $jq( "." + tab_id + "-" + current_block_id + "-next");
                    let $left = $jq("." + tab_id + "-" + current_block_id + "-prev");
                    var product_count = product_list.length;
                    product_count = product_count < MAX_ITEMS ? product_count : MAX_ITEMS;

                    var $item_inner_cur, $item_row_cur, added_item_count = 0;                     
                    
                    
                    var $item_row = $jq("<div class='row products-row swiper-wrapper'></div>");
                    var $item_li = $jq("<li class='fhsgrid first active products-grid no-margin'><div class='item-inner-"+tab_id+"-"+current_block_id+"'></div></li>");
                    
                    $item_inner_cur = $item_li.children(":first");
                    $slider_ul.append($item_li);
                    $item_inner_cur.append($item_row);
                    for (var i = 0; i < product_count; i++) {
                        if ($this.isFlashSaleProduct(product_list[i].id)) {
                            continue;
                        }
                        added_item_count++;
                        var _html = fhs_account.getProduct(product_list[i]);
                        //var _html = $this.addGridSliderProductHtml(product_list[i]);
                        $item_row.append(_html);
                    }
                    
                    $right.show();
                    
                    $block_tab_xem_them.show();
                    $current_block_loading_icon.hide();
                   
                    var mySwiperFhsNotGrid = new Swiper($item_inner_cur, {
                        slidesPerView: 2,
                        slidesPerColumn: 2,
                        direction: 'horizontal',
                        simulateTouch: true,
			spaceBetween: 8,
                        navigation: {
                            nextEl: "."+tab_id+"-"+current_block_id+"-next",
                            prevEl: "."+tab_id+"-"+current_block_id+"-prev",
                        },
                        on: {
                            slideChange: function () {
                                // on the first slide
                                let demSo;
                                if(added_item_count%2 == 0){                                    
                                    demSo = parseInt(added_item_count/2) - 2 ; 
                                }else{
                                    demSo = parseInt(added_item_count/2) - 1;
                                }
                                if (mySwiperFhsNotGrid.activeIndex == 0) {
                                    $right.show();
                                    $left.hide();
                                }
                                // most right postion
                                else if (demSo == mySwiperFhsNotGrid.activeIndex) {
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
                        },
                    });
        
                } else {
                    let $right = $jq( "." + tab_id + "-" + current_block_id + "-next");
                    let $left = $jq("." + tab_id + "-" + current_block_id + "-prev");
                    var product, $item, $item_li_cur;
                    var added_item_count = 0;
                    for (var i = 0; i < product_count; i++) {
                        product = product_list[i];
                        if ($this.isFlashSaleProduct(product_list[i].id)) {
//                            console.log("FLASH PRODUCT !!");
                            continue;
                        }
                       
                        if ((added_item_count % number_of_rows) == 0) {
                            var $item_li = $jq("<li class='item ul-items-sl-width swiper-slide'></li>");
			    if(is_blog){
				$item_li = $jq("<li class='item sl-blog-width'></li>");
			    }
                            $item_li_cur = $item_li;
                            $slider_ul.append($item_li);
                        }

			$item = fhs_account.getProduct(product_list[i]);
//                        $item = $this.addSliderProductHtml(product);
                        $item_li_cur.append($item);
                        added_item_count++;
                    }
                    
                    $right.show();
                    
                    $block_tab_xem_them.show();
                    $current_block_loading_icon.hide();
                    let slidersPerView = 5;
                    if (has_block_left_data){
                        slidersPerView = 4;
                    }
                    var mySwiperFhs = new Swiper($block_tab, {
                        slidesPerView: slidersPerView,
                        slidesPerGroup: slidersPerView,
                        direction: 'horizontal',
                        simulateTouch: true,
			spaceBetween: 8,
                        navigation: {
                            nextEl: "." + tab_id + "-" + current_block_id + "-next",
                            prevEl: "." + tab_id + "-" + current_block_id + "-prev",
                        },
                        on: {
                            slideChange: function () {
                                // on the first slide
                                let demSo = mySwiperFhs.activeIndex + 4; // + 5 vi 1 slide co  5 item
                                if (mySwiperFhs.activeIndex == 0) {
                                    $right.show();
                                    $left.hide();
                                }
                                // most right postion
                                else if (demSo == mySwiperFhs.slides.length - 1) {
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
                        },
                    });
                    
                }
            }
        });
    }
    this.activeArrowSliderTab = function (arrow_current_tab_active_id, arrow_current_block_id, name_tab_first_active) {
        var arrowBlockActive = $jq('.' + arrow_current_tab_active_id + '-' + arrow_current_block_id + "-button-parent");
        if (arrowBlockActive.hasClass("arrow-deActive"))
        {
            arrowBlockActive.removeClass("arrow-deActive");
        } else {
            arrowBlockActive.addClass("arrow-deActive");
        }
        // giu lai ten tab da deActive;
        if (name_tab_first_active) {
            arrowPrevTab = arrow_current_tab_active_id; 
        }
    }
    this.addSliderProductHtml = function (product) {
        var item_name = product.name_a_label;
        let episode = '';
	if(product['episode']){
	    episode = "<div class='episode-label'>"+product['episode']+"</div>";
	}
        var item_html = "<div class='item-inner' style='position: relative'>"
                + product.discount_label_html
                + "<div class='ma-box-content'><div class='products clearfix'><div class='product images-container'><a href='"
                + product.product_url + fhs_campaign_str + "' title='" 
                + product.image_label + "' class='product-image'><div class='product-image'><img src='"
                + loading_icon_url + "' data-src='"
                + product.image_src + "' class='lazyload' alt='"
                + product.image_label + "' /></div></a></div></div><h2 class='product-name-no-ellipsis'><a href='"
                + product.product_url + "' title='"
                + product.name_a_title + "'>"
                + item_name + "</a></h2><div class='price-label'>"
                + product.price_html  
		+ episode
		+ "</div><div class='fhs-rating-container' style='height:20px'>"
                + product.rating_html + "</div></div>"//// end li tag
                + product.bar_html;
        
        return $jq(item_html);
    }

    this.addGridSliderProductHtml = function (product) {
        let episode = '';
	if(product['episode']){
	    episode = "<div class='episode-label'>"+product['episode']+"</div>";
	}
        var item_name = product.name_a_label;
        var item_grid = "<div class='col-lg-6 col-md-6 col-sm-6 col-xs-12 _item first product-col swiper-slide'><div class='wrap-item'><div class='product-block'><div class='col-lg-6 col-md-6 col-sm-6 padd-l-r no-padding'>"
                + product.discount_label_html
                + "<div class='product-img img'><a href='"
                + product.product_url + fhs_campaign_str + "' title='" 
                + product.image_label + "' class='product-image'><div class='product-image'><img src='"
                + loading_icon_url + "' data-src='"
                + product.image_src + "' class='lazyload' alt='"
                + product.image_label + "' /></div></a></div></div><div class='col-lg-6 col-md-6 col-sm-6 padd-l-r no-padding'><h3 class='product-name-no-ellipsis name'><a href='"
                + product.product_url + "' title='"
                + product.name_a_title + "'>"
                + item_name + "</a></h3><div class='price-label'>"
                + product.price_html 
		+ episode
		+ "</div><div class='fhs-rating-container'>"
                + product.rating_html + "</div></div></div></div></div>";
        
        return $jq(item_grid);
    }
    
    this.isFlashSaleProduct = function (product_id) {
        //temporiry: do not remove flashsale product in tabslider
//        console.log("CHECK : " + product_id);
//        if (flashsale_data && flashsale_data['products'] && flashsale_data['products'][product_id]) {
//            return true;
//        }
        return false;
    }
    
    // mobile
    
    this.initMobile = function (_get_url, _block_limit, _block_id, _block_data_str,  _loading_icon_url, _fhs_campaign_str,grid_slider_mobile = false, _show_buy_now) {
        get_url = _get_url;
        block_limit = _block_limit;
        if (block_limit == "") {
            block_limit = MAX_ITEMS;
        }
	$this.data_slider = JSON.parse(_block_data_str);
        current_block_id = _block_id;
        loading_icon_url = _loading_icon_url;
        fhs_campaign_str = _fhs_campaign_str;
	show_buy_now = _show_buy_now;
        
        $this.checkBlockInViewportMoblie();
        if (grid_slider_mobile == false) {
            $jq(document).on('scroll',function () {
                Object.keys($this.data_slider).forEach(function (key) {
                    Object.keys($this.data_slider[key]).forEach(function (key_child) {
                        if ($this.data_slider[key][key_child]['is_loaded'] == false && Helper.isElementInViewport($jq("#tab" + key_child + "-" + current_block_id))) {
                            $this.data_slider[key][key_child]['is_loaded'] = true;
                            $jq(".tabslider-loading-icon-bottom").css('display', 'none');
                            $jq(".swiper-container-mobile").removeClass('active-visible');
                            $this.loadTabsliderMobileProducts(key_child, $this.data_slider[key][key_child]);
                        }
                    });
                });
            });
        }else{
            // neu muon khi load data (trong data da co san flag active) => read function init()
            // vi data mobile hien van chua them active vo nen se luon luon lay index 0 load trc
            $jq(document).on('scroll', async function () {
                let key_child = Object.keys($this.data_slider[0]);
                if(!tab_first_mobile && Helper.isElementInViewport($jq(".container-mobile-"+current_block_id))){
                    if ($this.data_slider[0][key_child]['is_loaded'] == false) {
                        $this.data_slider[0][key_child]['is_loaded'] = true;
                        $this.loadTabsliderMobileProducts(key_child, $this.data_slider[0][key_child]);
                    }
                    tab_first_mobile = true;
                    tab_active_key_child = key_child;
                    // add class active 
                    await $jq(".swiper-container-mobile").removeClass('active-visible');
                    $jq(".tabslider-loading-icon-bottom").css('display', 'none');
                    $jq(".swiper-slide.tab-slider-mobile.button-"+key_child).addClass("active-slider-mobile")
                    $jq(".categorytab-slider.m-ts-con-margin.categorytab-slider-"+key_child).css('display','');
                    
                   new Swiper(".container-mobile-" + current_block_id + " .swiper-container-mobile", {
                        slidesPerView: 'auto',
                        freeMode: true,
                        direction: 'horizontal',
                        simulateTouch: true,
			spaceBetween: 8,
//                        spaceBetween: 150,
                    });
                    
                }
             }); 
                
        // su kien click tab khac
        $jq(".container-mobile-"+current_block_id+" .swiper-container-mobile .swiper-wrapper div.tab-slider-mobile").click(function (e) {
            let click_tab_slider = $jq(e.target);
            let click_tab_id = click_tab_slider.attr("ref");
                if (tab_active_key_child != null && tab_active_key_child != click_tab_id) {
                    // display none and remove active-tab-slider
                    $jq(".swiper-slide.tab-slider-mobile.button-" + tab_active_key_child).removeClass("active-slider-mobile");
                    $jq(".categorytab-slider.m-ts-con-margin.categorytab-slider-" + tab_active_key_child).css('display', 'none');
                    // new display and active-tab-slider
                    Object.keys($this.data_slider).forEach(function (key) {
                        if ($this.data_slider[key].hasOwnProperty(click_tab_id)) {
                            if ($this.data_slider[key][click_tab_id]['is_loaded'] == false) {
                                $this.data_slider[key][click_tab_id]['is_loaded'] = true;
                                $jq(".categorytab-slider.m-ts-con-margin.categorytab-slider-" + click_tab_id).css('display', '');
                                $jq(".swiper-slide.tab-slider-mobile.button-" + click_tab_id).addClass("active-slider-mobile");
                                tab_active_key_child = click_tab_id;
                                $this.loadTabsliderMobileProducts(click_tab_id, $this.data_slider[key][click_tab_id]);
                            } else { // da load rui` thi show ra 
                                $jq(".categorytab-slider.m-ts-con-margin.categorytab-slider-" + click_tab_id).css('display', '');
                                $jq(".xem-them-" + click_tab_id).css('display', '');
                                $jq(".swiper-slide.tab-slider-mobile.button-" + click_tab_id).addClass("active-slider-mobile");
                                tab_active_key_child = click_tab_id;
                            }
                        }
                    });
                }
        });
            
        } 
         
    }
    
    this.checkBlockInViewportMoblie = function(){
        Object.keys($this.data_slider).forEach( function (key) {
             Object.keys($this.data_slider[key]).forEach(async function (key_child) {
                $this.data_slider[key][key_child]['is_loaded'] = false;
                if (Helper.isInViewportMobile("#tab" + key_child + "-" + current_block_id)) {
                    $this.data_slider[key][key_child]['is_loaded'] = true;
                    await $this.loadTabsliderMobileProducts(key_child, $this.data_slider[key][key_child]);
                }
                if (!tab_first_mobile && Helper.isInViewportMobile(".container-mobile-" + current_block_id)) {
                    if ($this.data_slider[0][key_child]['is_loaded'] == false) {
                        $this.data_slider[0][key_child]['is_loaded'] = true;
                        $this.loadTabsliderMobileProducts(key_child, $this.data_slider[0][key_child]);
                    }
                    tab_first_mobile = true;
                    tab_active_key_child = key_child;
                    // add class active 
                    await $jq(".swiper-container-mobile").removeClass('active-visible');
                    $jq(".tabslider-loading-icon-bottom").css('display', 'none');
                    $jq(".swiper-slide.tab-slider-mobile.button-" + key_child).addClass("active-slider-mobile")
                    $jq(".categorytab-slider.m-ts-con-margin.categorytab-slider-" + key_child).css('display', '');

                    new Swiper(".container-mobile-" + current_block_id + " .swiper-container-mobile", {
                        slidesPerView: 'auto',
                        freeMode: true,
                        direction: 'horizontal',
                        simulateTouch: true,
			spaceBetween: 8,
                    });
                }
            });
        });
    }
    
    this.loadTabsliderMobileProducts = function (tab_id, data_str) {
        var $block_tab = $jq("#tab" + tab_id + "-" + current_block_id);
        var $block_tab_xem_them = $jq(".tabs-xem-them-"+ tab_id + "-" + current_block_id);
        var $slider_ul = $jq("#tab" + tab_id + "-" + current_block_id + " .bxslider");
        var show_bar_gridslider = false;
        data_str = JSON.stringify(data_str);
        var data_json = JSON.parse(data_str);
        if (data_json['showBar']== "true" && data_json['showBar'])
        {
            show_bar_gridslider = true;
        }
        if(data_json['fhsCampaign']){
            fhs_campaign_str = data_json['fhsCampaign'];
        }else {
            fhs_campaign_str = "";
        }
        let backup_cat_id = 0;
	if(data_json['backup_cat_id']){
	    backup_cat_id = data_json['backup_cat_id'];
	}
        let backup_sort_by = '';
	if(data_json['backup_sort_by']){
	    backup_sort_by = data_json['backup_sort_by'];
	}
        $block_tab.show();

        $jq.ajax({
            url: get_url,
            method: 'get',
            data: {
                limit: block_limit,
                sort_by: data_json['sort_by'],
                min_ck: data_json['min_ck'],
                max_ck: data_json['max_ck'],
                category_id: data_json['category_id'],
                block_type: data_json['block_type'],
                attribute_code: data_json['attribute_code'],
                attribute_value: data_json['attribute_value'],
                attribute_data: data_json['attribute_data'],
                list: data_json['list'],
                product_id: data_json['product_id'],
                exclude_catId: data_json['exclude_catId'],
                bar_gridSlider : show_bar_gridslider,
		backup_cat_id: backup_cat_id,
		backup_sort_by: backup_sort_by,
                series_id : data_json['series_id'],
                show_buy_now : show_buy_now,
            },
            success: function (product_list) {
		var content_html = "";
		var i = 1;
		var item_index = ''
                Object.keys(product_list).forEach(function(key){
		    if(i == 1){
			item_index = 'first'
		    }else if(i == (product_list.length)){
			item_index = 'last'
		    }else{
			item_index = ''
		    }
		    content_html += fhs_account.getProduct(product_list[key], 'swiper-slide item '+item_index);
//		    content_html += $this.addSliderMobileProductHtml(product_list[key], item_index);
		    i++;
		});
		$slider_ul.html(content_html);
		var swiper_class = '.swiper-container-'+current_block_id+"-"+tab_id;
		setTimeout(function(){
		    swiper_list['swiper-container-'+tab_id+"-"+current_block_id] = new Swiper(swiper_class, {
			slidesPerView: 'auto',
			freeMode: true,
			longSwipesMs: 800,
			preloadImages: false,
			spaceBetween: 8,
			lazy: {
			    enabled: true,
			    loadPrevNext: true,
			    loadPrevNextAmount: 3
			}
		    });
		},100);
                
                // after render product : post -> update dataBarValue
                let product_count = product_list.length;
                let listProId = [];
                    if (show_bar_gridslider && product_count > 0) {
                    $jq.each(product_list, function (index, value) {
                        listProId.push(value.id)
                    });
                    $jq.ajax({
                        type: "post",
                        url: POST_GRIDSLIDER,
                        data : {
                            listProId : listProId
                        },
                        success: function (reponse) {
                           if(reponse.success){
                              $this.updateTextBarValue(reponse.data);
                           }
                        },
                    });
                }
                $jq(".tabs-xem-them.fhs_bar_bottom.xem-them-" + tab_id).css('display', '');
		$block_tab_xem_them.show();
            }
        });
    }
    

    this.addSliderMobileProductHtml = function (product, item_index) {
        let episode = '';
	if(product['episode']){
	    episode = "<div class='episode-label'>"+product['episode']+"</div>";
	}
        var item_name = product.name_a_label;
	
	return "<div class='swiper-slide item "+item_index+"'>"                         
	    +"<div class='item-inner' style='position: relative'>"
		+ product.discount_label_html
		+"<div class='ma-box-content'>"
		    +"<div class='products clearfix'>"
			+"<div class='product images-container'>"
			    +"<a href='"+ product.product_url + fhs_campaign_str + "' title='"+ product.image_label + "' class='product-image'>"
				+"<div class='product-image'>"
				    +"<img style='padding-bottom:0; min-height: 125px;' data-src='"+product.image_src+"' class='swiper-lazy' alt='"+product.image_label+"' />"
				    +"<div class='swiper-lazy-preloader'><img style='padding-bottom:0' src='"+loading_icon_url+"'/></div>"
				+"</div>"							    
			    +"</a>"
			+"</div>"
			+"<h2 class='product-name-no-ellipsis m-product-name'><a href='"+product.product_url+"' title='"+ product.name_a_title + "'>"+item_name+"</a></h2>"							
			+"<div class='price-label'>"+ product.price_html + episode + "</div>"
                        +  product.bar_html
			+"<div class='fhs-rating-container'>"+product.rating_html+"</div>"
		    +"</div>"    
		+"</div>" 
	    +"</div>"
	+"</div>";
    }
    
    // -------------------load data product new for girdslider------------------
    this.loadTabsliderProductsSlider = function (tab_id, data_str) {
        var $block_tab = $jq("#tab" + tab_id + "-" + current_block_id);
        var $block_tab_xem_them = $jq("#tab" + tab_id + "-" + current_block_id + " .tabs-xem-them");
        var $slider_ul = $jq("#tab" + tab_id + "-" + current_block_id + " .bxslider");
        var $current_block_loading_icon = $jq("#categorytab-" + current_block_id + " .tabslider-loading-icon");
        var show_bar_gridslider = false;
        data_str = JSON.stringify(data_str);
        var data_json = JSON.parse(data_str);
        
        if(data_json['showBar'] == "true" && data_json['showBar'])
        {
            show_bar_gridslider = true;
        }
        if(data_json['fhsCampaign']){
            fhs_campaign_str = data_json['fhsCampaign'];
        }else {
            fhs_campaign_str = "";
        }
        $block_tab.show();
        $current_block_loading_icon.show();
        var randomFlag = true;
        if(data_json['shouldRandom'] === "false")
        {
           randomFlag = false;
        }
        $jq.ajax({
            url: get_url,
            method: 'get',
            data: {
                limit: block_limit,
                sort_by: data_json['sort_by'],
                min_ck: data_json['min_ck'],
                max_ck: data_json['max_ck'],
                category_id: data_json['category_id'],
                block_type: data_json['block_type'],
                attribute_code: data_json['attribute_code'],
                attribute_value: data_json['attribute_value'],
                attribute_data: data_json['attribute_data'],
                list: data_json['list'],
                product_id: data_json['product_id'],
                exclude_catId: data_json['exclude_catId'],
                bar_gridSlider: show_bar_gridslider,
                series_id : data_json['series_id'],
            },
            success: function (product_list) {
                $slider_ul.empty();
                
                var product_count = product_list.length;
                if (product_count === 0 && tab_id == "related-products") {
                    $jq("#categorytab-" + current_block_id).hide();
                    return;
                }
                
                if(fhs_campaign_str != FHS_CAMPAIGN_RECOMMENDED && randomFlag){
                    product_list = Helper.shuffle(product_list);
                }
                if (is_grid_slider) {
                    var product_count = product_list.length;
                    product_count = product_count < MAX_ITEMS ? product_count : MAX_ITEMS;

                    var $item_inner_cur, $item_row_cur, added_item_count = 0;
                    for (var i = 0; i < product_count; i++) {
                        if ($this.isFlashSaleProduct(product_list[i].id)) {
                            continue;
                        }
                        
                        if ((added_item_count % TOTAL_GRID_PRODUCTS_PER_SLIDE) == 0) {
                            var $item_li = $jq("<li class='fhsgrid item first active products-grid no-margin'><div class='item-inner'></div></li>");
                            $item_inner_cur = $item_li.children(":first");
                            $slider_ul.append($item_li);
                        }
                        
                        if ((added_item_count % (TOTAL_GRID_PRODUCTS_PER_SLIDE / 2)) == 0) {
                            var $item_row = $jq("<div class='row products-row'></div>");
                            $item_row_cur = $item_row;
                            
                            if($item_inner_cur){
                                $item_inner_cur.append($item_row);
                            }
                        }
                        
                        if($item_row_cur){
                            var _html = $this.addGridSliderProductHtml(product_list[i]);
                            $item_row_cur.append(_html);
                        }
                        added_item_count++;
                    }

                } else {
                    let count_items = 10; // render ra chi 10 san pham
                    number_of_rows = 2;
                    var product, $item, $item_li_cur, slot_items_li;
                   
                    // so row trong 1 column (vd : co 2 row trong 1 column) 
                    slot_items_li = number_of_rows; 
                    
                    // 1 items_li chua items dua vao row (vd: rows 2 => 2 item) 
                    var flag_create_li = 1;
                    var $item_li = $jq("<li class='item items-sl-width'></li>");
                    $slider_ul.append($item_li);
                    for (var i = 0; i < product_count; i++) {
                        product = product_list[i];
                        if ($this.isFlashSaleProduct(product_list[i].id)) {
                            continue;
                        }
                        
                        // khoi tao li dau tien
//                        if (flag_create_li == 1) {
//                            var $item_li = $jq("<li class='item sl-width'></li>");
//                            if (is_blog) {
//                                $item_li = $jq("<li class='item sl-blog-width'></li>");
//                            }
//                            $item_li_cur = $item_li;
//                            $slider_ul.append($item_li);
//                            flag_create_li = 0;
//                        }
//                        // check slot trong li neu full thi bat flag
//                        if(slot_items_li != 0){
//                            $item = $this.addSliderProductHtml(product);
//                            $item_li_cur.append($item);
//                            slot_items_li--;
//                        }else{
//                            // khi slot_items_li == 0 thi se set lai = 2 va tao item 
//                            slot_items_li = number_of_rows;
//                            var $item_li = $jq("<li class='item sl-width'></li>");
//                            if (is_blog) {
//                                $item_li = $jq("<li class='item sl-blog-width'></li>");
//                            }
//                            $item_li_cur = $item_li;
//                            $slider_ul.append($item_li);
//                            $item_li_cur.append($item);
//                            slot_items_li--;
//                        }
//                        
//                        if ((i % number_of_rows) == 0) {
//                            var $item_li = $jq("<li class='item sl-width'></li>");
//			    if(is_blog){
//				$item_li = $jq("<li class='item sl-blog-width'></li>");
//			    }
//                            $item_li_cur = $item_li;
//                            $slider_ul.append($item_li);
//                        }
                        
//                        $item = $this.addSliderProductHtml(product);
//                        $item_li_cur.append($item);
                          $item = $this.addSliderProductHtml(product);
                          $item_li.append($item); 
                          count_items--;
                          if(count_items <= 0){
                              break; 
                          }
                    }
                }
                
                $block_tab_xem_them.show();
                $current_block_loading_icon.hide();
//                su dung lazyload thay cho bxslider lazy
//                $slider_ul.bxSlider(
//                    {
//                        pause: 0,
//                        pager : false,
//                        minSlides: 0,
//                        maxSlides: 10,
//                        slideWidth: block_item_width,
//                        slideMargin: 45,
//                        infiniteLoop: false,
//                        touchEnabled: false,
//                        hideControlOnEnd: true,
//                        preloadImages: 'all',
//                        onSliderLoad: function (slide, oldIndex, newIndex) {
//                            loadImages($slider_ul);
//                            $block_tab.find(".bx-viewport").css("height", "inherit");
//                            $block_tab.find(".bx-wrapper").css("max-width", "inherit");
//                        },
//                        onSlideAfter: function (slide, oldIndex, newIndex) {
//                            var currentS = $jq($slider_ul);
//                            loadImages(currentS);
//                        },
//                    }
//                );
                // after render product : post -> update dataBarValue
                let listProId = [];
                    if (show_bar_gridslider && product_count > 0) {
                    $jq.each(product_list, function (index, value) {
                        listProId.push(value.id)
                    });
                    $jq.ajax({
                        type: "post",
                        url: POST_GRIDSLIDER,
                        data : {
                            listProId : listProId
                        },
                        success: function (reponse) {
                           if(reponse.success){
                              $this.updateTextBarValue(reponse.data);
                           }
                        },
                    });
                }
            }
        });
    }
    //-----------------------------girdslider : gird---page---nextpage------------------------------------------
    // khoi tao bien o ben php qua
    this.loadData = function (get_url, data_str, get_url_more, _loading_icon_girds, _check_is_mobile = false, category_id, categories,_type_name_order,show_bar,_array_fhs_campaign, _limit_products = 48, _blockType) {
        jsonData = JSON.parse(data_str);
        is_show_bar = show_bar;
        get_url_next = get_url_more;
        get_url_load = get_url;
        loading_icon_girds = _loading_icon_girds;
        type_name_order = _type_name_order; // get type order
        mobile_grid_page =  _check_is_mobile;
        array_fhs_campaign = _array_fhs_campaign;
        limitProduct = _limit_products;
        blockTypePage = _blockType;
        // kiem tra categories : 
        let checkExist = categories.filter((category) => {
            return category.id === category_id
        })
        if (checkExist.length > 0) {
            if (category_id != 'all') {
                $this.loadproducts(get_url, jsonData, limitProduct, category_id, _check_is_mobile)
            } else {
                $this.loadproducts(get_url, jsonData, limitProduct, "all", _check_is_mobile)
            }
        } else {
            $jq(".tabslider-loading-icon").css("display", "none");
            $jq("#girdslider-page-body").show();
            $jq(".tabslider-loading-icon-bottom").css("display", "none");
            $jq('#girdslider-page-container .row').append($this.failedHtml());
        }
        
        /// Start checking page bottom
        var to_check_bottom = true;
        $jq(window).on('resize scroll', function () {
            if (to_check_bottom) {
                $this.checkPageBottomGirdSlider();
                to_check_bottom = false;
                setTimeout(function () {
                    to_check_bottom = true;
                }, 250);
            }
        });
        
        // event click sort by :
        this.enableSelectBoxes('order');
        
        window.addEventListener('popstate', function (event) {
            window.history.go();
        }, false);
        
    }
    //------load product------
    this.loadproducts = function (get_url, json_data, limit_product, category_id = null, _check_is_mobile = false) {
        let key_child_jsondata = Object.keys(json_data[0]);
        var classNameForUI = ".row"; // className handle UI product of Mobile web vs web
        if(_check_is_mobile){
            classNameForUI = ".products_grid_mobile";
        }
        $jq("#girdslider-page-body #girdslider-page-container " + classNameForUI).empty();
        // on/off active cua tab :
        if (current_tab_slider != null && $jq(".ts-header-grid #" + current_tab_slider).hasClass("active")) {
            $jq('.ts-header-grid #' + current_tab_slider).removeClass("active");
            $jq('.ts-header-grid #' + category_id).addClass("active");
            current_tab_slider = category_id;
        } else {
            $jq('.ts-header-grid #' + category_id).addClass("active");
            current_tab_slider = category_id;
        }
        attribute_grid_code = json_data[0][key_child_jsondata] && json_data[0][key_child_jsondata].attribute_code ? json_data[0][key_child_jsondata].attribute_code : null;
        attribute_grid_value = json_data[0][key_child_jsondata] && json_data[0][key_child_jsondata].attribute_value ? json_data[0][key_child_jsondata].attribute_value : null;
        page_grid = 1;
        $jq.ajax({
            url: get_url,
            method: 'get',
            data: {
                list: json_data[0][key_child_jsondata].list,
                limit: limit_product,
                category: category_id,
                mobile_grid_page : _check_is_mobile,
                type_name_order : type_name_order,
                bar_gridSlider : is_show_bar,
                attribute_code: attribute_grid_code,
                attribute_value: attribute_grid_value,
                //attribute_data: data_json['attribute_data'],
                block_type: blockTypePage,
                
            },
            success: function (result) {
                $jq("#girdslider-page-body #girdslider-page-container "+ classNameForUI).empty();
                $jq(".tabslider-loading-icon").css("display", "none");
                if (result.sucess) {
                    if ($jq(".tabslider-loading-icon-body").css("display") == 'block') {
                        $jq(".tabslider-loading-icon-body").hide();
                    }
                    $jq("#girdslider-page-body").show();
                    $jq(".girdslider-header").show();
                    $jq(".sort-grid-page").show(); // show sort-grid-page
                    
                    $jq.each(result.returnProducts, function (index, value) {
                        $jq('#girdslider-page-container '+ classNameForUI).append($this.displayProductInHtml(index, value, _check_is_mobile));
                    });
                    //$this.lazyLoading();
                    
                    data_list_loadmore = result.list;
                    is_data_first_loaded = true;

                    if (is_data_first_loaded) {
                        new Swiper(".swiper-container-grid-page", {
                            slidesPerView: 'auto',
                            freeMode: true,
                            direction: 'horizontal',
                            simulateTouch: true,
			    spaceBetween: 8,
                        });
                    }
                    // after render product : post -> update dataBarValue
                    if (blockTypePage == 'attribute') {}else{
                        let product_list = result.returnProducts;
                        let product_count = result.returnProducts.length;
                        let listProId = [];
                        if (is_show_bar && product_count > 0) {
                            $jq.each(product_list, function (index, value) {
                                listProId.push(value.product_id)
                            });

                            $jq.ajax({
                                type: "post",
                                url: POST_GRIDSLIDER,
                                data: {
                                    listProId: listProId
                                },
                                success: function (reponse) {
                                    if (reponse.success) {
                                        $this.updateTextBarValue(reponse.data);
                                    }
                                },
                            });
                        }
                    }
                } else {
                    if ($jq(".tabslider-loading-icon-body").css("display") == 'block') {
                        $jq(".tabslider-loading-icon-body").hide();
                        $jq('#girdslider-page-container '+ classNameForUI).append($this.failedHtml())
                    }
                }
            }
        });
    }
    //------load product more------
    this.loadMoreProduct = function (limitMoreProduct,category_id = null) {
        is_data_next_loaded = true; // dong no lai de ngung get more
        var classNameForUI = ".row"; // className handle UI product of Mobile web vs web
        if(mobile_grid_page){
            classNameForUI = ".products_grid_mobile";
        }
        $jq.ajax({
            url: get_url_next,
            method: 'get',
            data: {
                list: data_list_loadmore,
                limit: limitMoreProduct,
                category: category_id,
                mobile_grid_page : mobile_grid_page,
                type_name_order : type_name_order,
                bar_gridSlider : is_show_bar,
                attribute_code: attribute_grid_code,
                attribute_value: attribute_grid_value,
                block_type: blockTypePage,
                page : page_grid
            },
            success: function (result) {
                if (result.success) {
                    
                    data_list_loadmore = result.list;
                    
                    is_data_next_loaded = false; // mo no ra load tiep;
                    $jq(".tabslider-loading-icon-bottom").css("display", "block");
                    $jq.each(result.returnProducts, function (index, value) {
                        $jq('#girdslider-page-container ' + classNameForUI).append($this.displayProductInHtml(index, value, mobile_grid_page));
                    });
                    $jq(".tabslider-loading-icon-bottom").css("display", "none");
                    
                     // after render product : post -> update dataBarValue
                    let product_list = result.returnProducts;
                    let product_count = result.returnProducts.length;
                    if (is_show_bar && product_count > 0) {
                        let listProId = [];
                        $jq.each(product_list, function (index, value) {
                            listProId.push(value.product_id)
                        });
                        
                        $jq.ajax({
                            type: "post",
                            url: POST_GRIDSLIDER,
                            data: {
                                listProId: listProId
                            },
                            success: function (reponse) {
                                if (reponse.success) {
                                    $this.updateTextBarValue(reponse.data);
                                }
                            },
                        });
                    }
                    
                } else {
                    $jq(".tabslider-loading-icon-bottom").css("display", "none");
                    is_data_next_loaded = true;
                }
            },
            error: function () {
                $jq(".tabslider-loading-icon-bottom").css("display", "none");
            }
        });
    }
    // checking load more :
    this.checkPageBottomGirdSlider = function () {
        var limitMoreProduct = 40;
        if (Helper.isElementInViewport($page_bottom) && !is_data_next_loaded && is_data_first_loaded) {
            $jq(".tabslider-loading-icon-bottom").css("display", "block");
            page_grid++;
            $this.loadMoreProduct(limitMoreProduct,current_tab_slider);
        }
    }
    
    this.displayProductInHtml = function(index,product,_check_mobile = false) {
        var item_name = (product.name_a_label);
        var item = '';
        let fhs_campaign_text = '';
        if(array_fhs_campaign && array_fhs_campaign.fhs_campaign_products){
            fhs_campaign_text = array_fhs_campaign.fhs_campaign_text !='' ? '?fhs_campaign='+array_fhs_campaign.fhs_campaign_text : '';
        }
	
        let episode = '';
	if(product['episode']){
	    episode = "<div class='episode-label'>"+product['episode']+"</div>";
	}
	
        if(_check_mobile === true){
            item_name = (product.name_a_label);
            item += '<li class="product-item-mobile">';
            item+= '<div class="item-inner-mobile">'
                        // display discount
                        + product.discount_label_html
                        // display content
                        +'<div class="ma-box-content">'
                            +'<div class="products clearfix">'
                                    // display image
                                    + $this.displayImageItem(product.image_src,product.image_label,product.product_url)
                            +'</div>'
                            +'<h2 class="product-name-no-ellipsis p-name-list">'
                                +'<a href="'+product.product_url+ fhs_campaign_text +'" title="'+product.image_label+'" class="product-image">'+item_name+'</a>'
                            +'</h2>'
                            +'<div class="price-mobile">'
                                + product.price_html
                            +'</div>'
                            +'<div class="rating-container-mobile" style="margin-left:-10px";>'
                                + product.rating_html
                            +'</div>'
                        +'</div>'
                        // end content
                    +'</div>'
                    + product.bar_html
                +'</li>'; 
        } else {
            item += '<div class="girdslider-item-aaa">';
            item+= '<div class="item-inner">'
                        // display discount
                        + product.discount_label_html
                        // display content
                        +'<div class="ma-box-content" style="padding-bottom: 0px">'
                            +'<div class="products clearfix">'
                                    // display image
                                    + $this.displayImageItem(product.image_src,product.image_label,product.product_url)
                            +'</div>'
                            +'<h2 class="product-name-no-ellipsis">'
                                +'<a href="'+product.product_url+ fhs_campaign_text +'" title="'+product.image_label+'" class="product-image">'+item_name+'</a>'
                            +'</h2>'
                            +'<div class="price-label">'
                                + product.price_html
				+ episode
                            +'</div>'
                            +'<div class="rating-label">'
                                + product.rating_html
                            +'</div>'
                        +'</div>'
                       + product.bar_html
                        // end content
                    +'</div>'
                +'</div>'; 
        }
        return item;
    }
    
    this.displayImageItem = function(image_src,image_label,product_url){
        let fhs_campaign_text = '';
        if(array_fhs_campaign && array_fhs_campaign.fhs_campaign_products){
            fhs_campaign_text = array_fhs_campaign.fhs_campaign_text !='' ? '?fhs_campaign='+array_fhs_campaign.fhs_campaign_text : '';
        }
        var image_item ='<div class="product images-container">'
                +'<a href="'+product_url+fhs_campaign_text+'" title="'+image_label+'" class="product-image">'
                    +'<div class="product-image"><img class="lazyload" data-src="'+image_src+'" src="'+loading_icon_girds+'" alt="'+image_label+'"></div>'
                +'</a>'
            +'</div>';
            
         return image_item;                   
    }
    this.clickCategory = function (category_id, _check = false) {
        is_data_first_loaded = false;
        is_data_next_loaded = false;
        if(_check == "true"){
            _check = true;
        }else{
            _check = false;
        }
        // param url : 
        if (category_id != $this.urlParam('category')) {
            let firstPath = window.location.pathname.split('/')[1];
            if (firstPath) {
                history.pushState('', '', '/' + firstPath + '?category=' + category_id + '&sort=' + type_name_order);
            } else {
                history.pushState('', '', '/deal-hot-pages?category=' + category_id + '&sort=' + type_name_order);
            }
        }
        $jq("#girdslider-page-body").hide();
        $jq(".tabslider-loading-icon-body").show();
        $this.loadproducts(get_url_load, jsonData, limitProduct, category_id, _check);
    }
    this.lazyLoading = function () {
        $jq('img.lazy').Lazy({
            delay: 100,
        });
    }
    this.failedHtml = function () {
        var string = '<div class="grid-error" >Không tìm thấy sản phẩm nào.</div>';
        return string;
    }
    this.urlParam = function (name) {
        var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
        if(!results){
            return null;
        }
        return results[1] || 0;
    }
    /// click selected sort : 
    this.enableSelectBoxes = function (button) {
        // show option selected
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
                // handle click : 
                type_name_order = $jq(this).attr('value');
                // param url : 
                if (type_name_order != $this.urlParam('sort')) {
                    let firstPath = window.location.pathname.split('/')[1];
                    if (firstPath) {
                        history.pushState('', '', '/' + firstPath + '?category=' + current_tab_slider + '&sort=' + type_name_order)
                    } else {
                        history.pushState('', '', '/deal-hot-pages?category=' + current_tab_slider + '&sort=' + type_name_order);
                    }
                   ;
                }
                $jq("#girdslider-page-body").hide();
                $jq(".tabslider-loading-icon-body").show();
                is_data_first_loaded = false;
                is_data_next_loaded = false;
                $this.loadproducts(get_url_load, jsonData, limitProduct, current_tab_slider,mobile_grid_page);

            });
            // tat option fields khi re chuot ra ngoai fields 
            $jq("div.selectOptions-" + button).mouseleave(function () {
                $jq(this).parent().children('div.selectOptions-' + button).css('display', 'none');
            });

        });
    }
    this.updateTextBarValue = function(data){
        $jq.each(data, function (index, value) {
            let jsonData = JSON.parse(value);
            let qtySold = parseInt(jsonData.qty_sold);
            if (jsonData.percent > 80) {
                $jq('.progress-bar.color-bar-grid.' + jsonData.product_id + '-bar').width(jsonData.percent+"%");
                $jq('.text-progress-bar span.' + jsonData.product_id + '-bar').text("Sắp hết");
            } else {
                $jq('.progress-bar.color-bar-grid.' + jsonData.product_id + '-bar').width(jsonData.percent+"%");
                $jq('.text-progress-bar span.' + jsonData.product_id + '-bar').text("Đã bán " +qtySold);
            }
        });
    }
}

