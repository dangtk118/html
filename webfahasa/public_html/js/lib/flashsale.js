
const FlashSale = function () {
    const MAX_DISPLAY_PERIODS = 5;
    const PRODUCTS_PER_ROW = 5;
    const PRODUCTS_PER_ROW_MOBILE = 6;
    const PERIOD_PRODUCTS_ID_KEY = "flashsale-period-products-";
    const MAX_DISPLAY_PERIODS_MOBILE_PAGE = 8;
    const CHAY_HANG_ICON_URL = "frontend/ma_vanese/fahasa/images/flashsale/Chay-hang-icon.svg";
    const CATEGORY_ID_ALL = 0;
    
    var $this = this;
    var is_mobile = false;
    var countdown_timer;
    var countdown_timer1;
    /// Slider Variables
    $flashsale_slider = $jq("#flashsale-slider");

    /// Page Variables
    var next_products_url;
    var $flashsale_page = $jq("#flashsale-page");
    var $period_list = $jq("#flashsale-page .flashsale-page-periods");
    var $period_content = $jq(".flashsale-page-period-content");
    var $page_loading_icon = $jq(".flashsale-page-loading-icon");
    var $page_countdown_label = $jq(".flashsale-page-countdown-label");
    var $page_bottom = $jq(".flashsale-page-bottom");

    var $current_period_tab;
    var $current_products_content;
    var current_period_id;
    var periods_data = {};
    var text_labels = {};
    /// Product page variable
    var $flashsale_product_page = $jq(".flashsale-product-info")

    /// Mobile Page
    var $flashsale_page_mobile = $jq("#flashsale-page-mobile");
    var $mobile_period_list = $jq("#flashsale-page-mobile .flashsale-page-periods .swiper-wrapper");
    var base_skin_url;
    var $categories_list = $jq("#flashsale-page .flashsale-page-categories, #flashsale-page-mobile .flashsale-page-categories");
    var $current_categories_content;
    var current_category_id;
    var $current_category_tab;
    var $mobile_categories_list = $jq("#flashsale-page-mobile .flashsale-page-categories");
    
    this.initSlider = function (url, _text_labels, BASE_SKIN_URL,_data) {
        base_skin_url = BASE_SKIN_URL;
        let dataJson = JSON.parse(_data);
        let supplierData = {
                supplierId: null,
            };
        if (dataJson && dataJson.supplier) {
            supplierData.supplierId = dataJson.supplier
        }
        $jq.ajax({
            url: url,
            method: 'post',
            data : supplierData,
            dataType: "json",
            success: function (data) {
                if (!data.result) {
                    return;
                }

                text_labels = _text_labels;

                $flashsale_slider.show();
                period = data['period'];
                if (period) {
                    period.is_active = parseInt(period.is_active);
                    if (period.is_active) {
                        $page_countdown_label.text(text_labels['ket_thuc']);
                        $this.startCountdown($flashsale_slider, period['end_date']);
                    } else {
                        $page_countdown_label.text(text_labels['bat_dau']);
                        $this.startCountdown($flashsale_slider, period['start_date']);
                    }

                    $countdown_label = $jq("#flashsale-slider .flashsale-countdown-label");

                    period.sap_dien_ra = parseInt(period.sap_dien_ra);
                    if (period.sap_dien_ra) {
                        $countdown_label.show();
                        $countdown_label.text(text_labels['sap_mo']);
                    } else {
                        $countdown_label.hide();
                    }
                }

                /// List all products
                $flashsale_list = $jq("#flashsale-slider .flashsale-list");
                $flashsale_list.empty();

                products = data['products'];
                var count = products.length;
                for (i = 0; i < count; i++) {
                    $product = $this.addSliderProductHtml(products[i]);
                    $flashsale_list.append($product);
                }

                $flashsale_list.bxSlider(
                        {
                            pause: 0,
                            minSlides: 1,
                            maxSlides: 6,
                            slideWidth: 270,
                            slideMargin: 45,
                            infiniteLoop: false,
                            touchEnabled: false,
                            hideControlOnEnd: true,
                            preloadImages: 'visible',
                            adaptiveHeight: true,
                            onSlideAfter: function (slide, oldIndex, newIndex) {
                                //.var currentS = $flashsale_list;
                                //loadImages(currentS);
                            }
                        }
                );
            }
        });
    };

    this.initMobileSlider = function (url, _text_labels, BASE_SKIN_URL, _data) {
        base_skin_url = BASE_SKIN_URL;
        let dataJson = JSON.parse(_data);
        let supplierData = {
                supplierId: null
            };
        if (dataJson && dataJson.supplier) {
            supplierData.supplierId = dataJson.supplier
        }
        
        $jq.ajax({
            url: url,
            method: 'post',
            data : supplierData,
            dataType: "json",
            success: function (data) {
                if (!data.result) {
                    return;
                }

                text_labels = _text_labels;
                period = data['period'];
                if (period) {
                    period.is_active = parseInt(period.is_active);
                    if (period.is_active) {
                        $page_countdown_label.text(text_labels['ket_thuc']);
                        $this.startCountdown($flashsale_slider, period['end_date']);
                    } else {
                        $page_countdown_label.text(text_labels['bat_dau']);
                        $this.startCountdown($flashsale_slider, period['start_date']);
                    }
                }
                
                /// List all products
                $flashsale_list = $jq("#flashsale-slider .flashsale-list");
                $flashsale_list.empty();
                $categories_list.empty();

                products = data['products'];
                var count = products.length;
                for (i = 0; i < count; i++) {
                    $product = $this.addMobileSliderProductHtml(products[i]);
                    $flashsale_list.append($product);
                }

                $flashsale_slider.show();

                var swiper = new Swiper('.swiper-container-flashsale', {
                    direction: 'horizontal',
                    slidesPerView: 'auto',
                    freeMode: true,
                    longSwipesMs: 800
                });
            }
        });
    };

    this.initPage = function (page_url, get_next_url, _text_labels, BASE_SKIN_URL) {
        next_products_url = get_next_url;
        base_skin_url = BASE_SKIN_URL;
        
        $jq.ajax({
            url: page_url,
            method: 'post',
            success: function (data) {
                if (!data.result) {
                    console.log("Error: " + data.error_type);
                    $error_panel = $jq("#flashsale-error");
                    if (data.error_type == "no_connection") {
                        /// display error msg
                    } else {
                        $error_panel.find(".flashsale-error-msg").text(_text_labels['error']);
                    }

                    $error_panel.show();
                    $flashsale_page.hide();
                    return;
                }

                text_labels = _text_labels;
                /// List all products
                var active_period = data['period'];
                current_period_id = active_period.period_id;
                var periods = data['flashsale']['periods'];
                var periods_count = periods.length;

                var blank_period_html_count = MAX_DISPLAY_PERIODS;
                var added_period_count = 0;

                for (i = 0; i < periods_count; i++) {
                    if (added_period_count >= MAX_DISPLAY_PERIODS) {
                        continue;
                    }

                    $period = $this.addPagePeriodHtml(periods[i]);
                    if (!$period) {
                        continue;
                    }
                    
                    if (periods[i].period_id == active_period.period_id) {
                        $period.addClass("flashsale-page-period-active");
                        $current_period_tab = $period;
                        $categories = $this.addPageCategoriesHtml(periods[i], true);
                        $current_category_tab = $categories.find(".flashsale-page-category-active");

                    } else {
                        $categories = $this.addPageCategoriesHtml(periods[i], false);
                    }
                    
                    current_category_id = 0;
                    
                    $period.find("a").click($this.periodTabClicked);
                    
                    $period_list.append($period);
                    blank_period_html_count--;
                    added_period_count++;

                    /// add period_content ( div tag that content products )
                    //check whether group by product is active
                    if (periods[i].categories.length == 0){
                        $categories_list.hide();
                        let category_id = CATEGORY_ID_ALL;
                        var $period_products_html = $jq("<div id='" + PERIOD_PRODUCTS_ID_KEY + periods[i].period_id + "-" + category_id + "' class='flashsale-page-products'>");
                        $period_content.append($period_products_html);
                    } else {
                        $categories.find("a").click($this.categoryTabClicked);
                        $categories.find("div[id^='category-more-option']").click($this.moreOptionClicked);

                        $categories_list.append($categories);
                        for (let j = 0; j < periods[i].categories.length; j++) {
                            let category_id = periods[i].categories[j].id;
                            var $period_products_html = $jq("<div id='" + PERIOD_PRODUCTS_ID_KEY + periods[i].period_id + "-" + category_id + "' class='flashsale-page-products'>");
                            $period_content.append($period_products_html);
                        }
                    }
                  
                }

                for (i = 0; i < blank_period_html_count; i++) {
                    $period_list.append($jq("<div class='col-md-5ths col-xs-6 flashsale-page-period'></div>"));
                }

                /// Start Period Countdown
                active_period.is_active = parseInt(active_period.is_active);

                if (active_period.is_active) {
                    $page_countdown_label.text(text_labels['ket_thuc']);
                    $this.startCountdown($flashsale_page, active_period['end_date']);
                } else {
                    $page_countdown_label.text(text_labels['bat_dau']);
                    $this.startCountdown($flashsale_page, active_period['start_date']);
                }

                /// Load Active Period Products
                $active_period_products = $jq("#" + PERIOD_PRODUCTS_ID_KEY + data['period'].period_id + '-' + CATEGORY_ID_ALL);
                $current_products_content = $active_period_products;
                $current_products_content.show();                
                $this.addProductsToPage(data['products'], $active_period_products);
                
                $active_period_categories = $jq("#" + "flashsale-period-categories-" + data['period'].period_id);
                $current_categories_content = $active_period_categories;
                $current_categories_content.show();

                /// Initialize periods data
                periods_data = {};
                for (i = 0; i < periods_count; i++) {
                    periods_data[periods[i].period_id] = {};
                    //check whether group by product is active
                    if (periods[i].categories.length == 0) {
                        periods_data[periods[i].period_id][CATEGORY_ID_ALL] = {
                            is_loading: false,
                            is_fully_loaded: false,
                            current_page: 0,
                            end_date: periods[i].end_date,
                            start_date: periods[i].start_date,
                            is_active: parseInt(periods[i].is_active)
                        }
                    } else {
                        for (let j = 0; j < periods[i].categories.length; j++) {
                            periods_data[periods[i].period_id][periods[i].categories[j].id] = {
                                is_loading: false,
                                is_fully_loaded: false,
                                current_page: 0,
                                end_date: periods[i].end_date,
                                start_date: periods[i].start_date,
                                is_active: parseInt(periods[i].is_active)
                            }
                        }
                    }
                }

                /// Start checking viewport
                $jq(window).ready(function () {
                    $this.checkFlashSaleBottom();
                    var to_check_bottom = true;
                    $jq(window).on('resize scroll', function () {
                        $this.checkFlashSaleBottom();
                        if (to_check_bottom) {
                            to_check_bottom = false;
                            setTimeout(function () {
                                to_check_bottom = true;
                            }, 250);
                        }
                    });
                });
            }
        });
    };

    this.initMobilePage = function (page_url, get_next_url, _text_labels, BASE_SKIN_URL) {
        next_products_url = get_next_url;
        is_mobile = true;
        base_skin_url = BASE_SKIN_URL;
        
        $jq.ajax({
            url: page_url,
            method: 'post',
            success: function (data) {
                if (!data.result) {
                    console.log("Error: " + data.error_type);
                    $error_panel = $jq("#flashsale-error");
                    if (data.error_type == "no_connection") {
                        /// display error msg
                    } else {
                        $error_panel.find(".flashsale-error-msg").text(_text_labels['error']);
                    }

                    $error_panel.show();
                    $flashsale_page_mobile.hide();
                    return;
                }

                text_labels = _text_labels;
                /// List all products
                var active_period = data['period'];
                current_period_id = active_period.period_id;
                var periods = data['flashsale']['periods'];
                var periods_count = periods.length;

                var added_period_count = 0;
                $mobile_period_list.empty();
                $categories_list.empty();
                $mobile_categories_list.empty();

                for (i = 0; i < periods_count; i++) {
                    if (added_period_count >= MAX_DISPLAY_PERIODS_MOBILE_PAGE) {
                        continue;
                    }

                    $period = $this.addMobilePagePeriodHtml(periods[i]);
                    if (!$period) {
                        /// Ignore $periods that are in the past
                        continue;
                    }

                    if (periods[i].period_id == active_period.period_id) {
                        $period.addClass("flashsale-page-period-active");
                        $current_period_tab = $period;
                        $categories = $this.addPageCategoriesHtml(periods[i], true, true);
                        $current_category_tab = $categories.find(".flashsale-page-category-active");
                    } else {
                        $categories = $this.addPageCategoriesHtml(periods[i], false, true);
                    }
                    
                    current_category_id = CATEGORY_ID_ALL;

                    $period.find(".flashsale-page-period-item").click($this.periodTabClicked);
                    $mobile_period_list.append($period);
                    added_period_count++;

                    /// add period_content ( div tag that content products )
                    for (let j = 0; j < periods[i].categories.length; j++) {
                        let category_id = periods[i].categories[j].id;
                        var $period_products_html = $jq("<div id='" + PERIOD_PRODUCTS_ID_KEY + periods[i].period_id + '-' + category_id + "' class='flashsale-page-products'>");
                        $period_content.append($period_products_html);
                    }
                    
                     //check whether group by product is active
                    if (periods[i].categories.length == 0){
                        $categories_list.hide();
                        let category_id = CATEGORY_ID_ALL;
                        var $period_products_html = $jq("<div id='" + PERIOD_PRODUCTS_ID_KEY + periods[i].period_id + '-' + category_id + "' class='flashsale-page-products'>");
                        $period_content.append($period_products_html);
                    } else {
                        $categories.find("a").click($this.categoryTabClicked);
                        $mobile_categories_list.append($categories);
                        $categories_list.append($categories);
                        // add period_content ( div tag that content products )
                        for (let j = 0; j < periods[i].categories.length; j++) {
                            let category_id = periods[i].categories[j].id;
                            var $period_products_html = $jq("<div id='" + PERIOD_PRODUCTS_ID_KEY + periods[i].period_id + '-' + category_id + "' class='flashsale-page-products'>");
                            $period_content.append($period_products_html);
                        }
                    }
                    
                    
                    $period_content.append($period_products_html);
                }

                var swiper = new Swiper('.flashsale-page-periods', {
                    direction: 'horizontal',
                    slidesPerView: 'auto',
                    freeMode: true,
                    longSwipesMs: 800
                });
                
                var swiper_categories = new Swiper('.categories-container', {
                    direction: 'horizontal',
                    slidesPerView: 'auto',
                    freeMode: true,
                    longSwipesMs: 800,
                    observer: true

                    
                });

                /// Start Period Countdown
                active_period.is_active = parseInt(active_period.is_active);
                if (active_period.is_active) {
                    $page_countdown_label.text(text_labels['ket_thuc']);
                    $this.startCountdown($flashsale_page_mobile, active_period['end_date']);
                } else {
                    $page_countdown_label.text(text_labels['bat_dau']);
                    $this.startCountdown($flashsale_page_mobile, active_period['start_date']);
                }

                /// Load Active Period Products
                $active_period_products = $jq("#" + PERIOD_PRODUCTS_ID_KEY + data['period'].period_id + '-' + CATEGORY_ID_ALL);
                $current_products_content = $active_period_products;
                $current_products_content.show();
                $this.addProductsToPage(data['products'], $active_period_products);


                //load active categories in period
                $active_period_categories = $jq("#" + "flashsale-period-categories-" + data['period'].period_id);
                $current_categories_content = $active_period_categories;
                $current_categories_content.show();

                /// Initialize periods data
                periods_data = {};
                for (i = 0; i < periods_count; i++) {
                    periods_data[periods[i].period_id] = {};
                    if (periods[i].categories.length == 0){
                        periods_data[periods[i].period_id][0] = {
                            is_loading: false,
                            is_fully_loaded: false,
                            current_page: 0,
                            end_date: periods[i].end_date,
                            start_date: periods[i].start_date,
                            is_active: parseInt(periods[i].is_active)
                        }
                    } else {
                        for (let j = 0; j < periods[i].categories.length; j++) {
                            periods_data[periods[i].period_id][periods[i].categories[j].id] = {
                                is_loading: false,
                                is_fully_loaded: false,
                                current_page: 0,
                                end_date: periods[i].end_date,
                                start_date: periods[i].start_date,
                                is_active: parseInt(periods[i].is_active)
                            }
                        }
                    }
                    
                }

                /// Start checking viewport
                $jq(window).ready(function () {
                    $this.checkFlashSaleBottom();
                    $jq(window).on('resize scroll', function () {
                        $this.checkFlashSaleBottom();
                    });

                    /// Periods menu affix
                    var $period_menu = $jq('#flashsale-page-mobile-periods');
                    $period_menu.affix({offset: {top: $period_menu.offset().top}});
                });
            }
        });
    }
    
    /*
     *  This function only apply for single product
     */
    this.initProductPage = function (product, period, is_mobile, _text_labels) {
        $price_element = $jq(".product_price");
        $discount_element = $jq("#catalog-product-details-discount");
	
	$price_configurable = $jq(".product-view-configuable-mobile-popup-content-price");

        if (!product) {
            $price_element.show();
            $discount_element.show();
            return;
        }

        product.total_sold = parseInt(product.total_sold);
        product.total_items = parseInt(product.total_items);
	let percent = Math.round(( product.total_sold * 100)/product.total_items );
	if(percent > 100){percent = 100;}else if(percent < 0){percent = 0;}
	
	if (product.total_sold >= product.total_items) {
	    if(!is_mobile){
		let price_html = $this.printProductPriceV2(product.product_id, product.display_old_price, product.final_price, product.old_discount);
		$jq(".product_price .price-box").html(price_html);
		$price_element.show();
		if($price_configurable.length > 0){
		    $jq(".product-view-configuable-mobile-popup-content-price .price-box").html(price_html);
		}
	    }else{
		$old_final_price_html = $this.printPagePriceHtml(product.product_id, product.display_old_price, product.final_price);
		$price_element.html($old_final_price_html);
		if($price_configurable.length > 0){
		    $price_configurable.html($old_final_price_html);
		}
		
		$old_discount_html = $this.printProductDiscountHtml(product.old_discount, is_mobile);
		$discount_element.html($old_discount_html);

		$price_element.show();
		$discount_element.show();
	    }
	    return;
	}
	if(!is_mobile){
	    let price_html = $this.printProductPriceV2(product.product_id, product.display_old_price, product.flashsale_price, product.discount);
	    $jq(".product_price .price-box").html(price_html);
	    $jq(".flashsale_sold_number").text(product.total_sold);
	    $price_element.show();
	    
	    if($price_configurable.length > 0){
		$jq(".product-view-configuable-mobile-popup-content-price .price-box").html(price_html);
	    }
	}else{
	    $flashsale_price_html = $this.printPagePriceHtml(product.product_id, product.display_old_price, product.flashsale_price);
	    $price_element.html($flashsale_price_html);
	    $price_element.show();

	    $flashsale_discount_html = $this.printProductDiscountHtml(product.discount, is_mobile);
	    $discount_element.html($flashsale_discount_html);
	    $discount_element.show();
	    
	}

	$jq('.flashsale-progress-bar').css('width',percent+'%');
	$jq('.flashsale_sold_number').text(product.total_sold);
	
        /// Start Period Countdown
        if (period) {
            $this.startCountdown($flashsale_product_page, period['end_date']);
        }

        $jq(".flashsale-product-info .flashsale-countdown-label").html(_text_labels["ket_thuc"]);
        $flashsale_product_page.show();
	$jq('.price-block').addClass('desktop_only');
    }

    this.getNextProducts = function (period_id, page, category_id) {

        var $period_products = $jq("#" + PERIOD_PRODUCTS_ID_KEY + period_id + '-' + category_id);
        var children_count = $period_products.children().size();
        if (children_count > 0 && page == 0) {
            /// don't load when there are children, and page is 0
            ///TODO: - need to find error of reason why list of product  is duplicated
        } else {
            periods_data[period_id][category_id].is_loading = true;
            $page_loading_icon.show();
            $jq.ajax({
                url: next_products_url,
                method: 'post',
                data: {
                    period_id: period_id,
                    page: page,
                    category_id: category_id,
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    setTimeout(function () {
                        periods_data[period_id][category_id].is_loading = false;
                        $page_loading_icon.hide();
                    }, 1500);
                },
                success: function (data) {
                    $page_loading_icon.hide();

                    if (!data.result) {
                        return;
                    }
                    periods_data[period_id][category_id].is_loading = false;
                    periods_data[period_id][category_id].current_page = page;
                    $this.addProductsToPage(data['products'], $period_products);

                    if ((data.products_per_page * periods_data[period_id][category_id].current_page) >= data.product_count) {
                        periods_data[period_id][category_id].is_fully_loaded = true;
                    }
                }
            });
        }
    }

    this.addProductsToPage = function (products, $list) {
        /// List all products
        var count = products.length, $current_row;
        for (i = 0; i < count; i++) {
            let _products_per_row = is_mobile ? PRODUCTS_PER_ROW_MOBILE : PRODUCTS_PER_ROW;
            if (i % _products_per_row == 0) {
                $current_row = $jq("<div class='row'></div>");
                $list.append($current_row);
            }
            $product = $this.addPageProductHtml(products[i]);
            $current_row.append($product);
        }
    }

    this.addPagePeriodHtml = function (period) {
        /// Period Time
        var start_date = new Date(period.start_date);
        var end_date = new Date(period.end_date);
        var now_date = new Date();

        if (now_date > end_date) {
            return null;
        }

        period_start_time = period.start_time_label;
        period_status = text_labels['sap_ban'];

        period.is_active = parseInt(period.is_active);
        period.sap_dien_ra = parseInt(period.sap_dien_ra);

        if (start_date.getDate() - 1 > now_date.getDate()) {
            //period_status = start_date.getDate() + "/" + (start_date.getMonth() + 1);
            period_status = period.start_date_label;
        } else if (start_date.getDate() > now_date.getDate()) {
            period_status = text_labels['ngay_mai'];
        } else if (period.is_active) {
            period_status = text_labels['dang_ban'];
        } else if (period.sap_dien_ra) {
            period_status = text_labels['sap_ban'];
        }

        period_html = "<div class='col-md-5ths col-xs-12 flashsale-page-period'><a class='flashsale-page-period-label' data-id='"
                + period.period_id + "'><div class='time'>"
                + period_start_time
                + "</div><div class='status'>"
                + period_status
                + "</div><span></span></a></div>";

        return $jq(period_html);
    }
    
    this.addPageCategoriesHtml = function (period, active, is_mobile) {
        /// Categories in period
        let categories = period.categories;
        let categories_html = "";
        
        if (is_mobile){
            for (let i = 0; i < categories.length; i++) {
                let is_active_label = '';
                if (active && i == 0){
                    is_active_label = 'flashsale-page-category-active';
                }
                
                categories_html += "<a  data-id='" + period.period_id + "-" + categories[i].id + "' class='swiper-slide category-label "+is_active_label+"'><span >" + categories[i].name + "</span></a>";
            }
        } else {
            if (categories.length > 6){
                for (let i = 0; i < 5; i++) {
                    let is_active_label = '';
                    if (active && i == 0) {
                        is_active_label = 'flashsale-page-category-active';
                    }
                    categories_html += "<a data-id='" + period.period_id + "-" + categories[i].id + "' class='category-label " +is_active_label + "'><span >" + categories[i].name + "</span></a>";
                }
                categories_html += "<div id='category-more-option-" + period.period_id + "' class='category-label'><span>Thêm</span>";
                categories_html += "<div class='category-menu-option' id='menu-option-" + period.period_id + "'>";
                for (let i = 5; i < categories.length; i++){
                    categories_html += "<a data-id='" + period.period_id + "-" + categories[i].id + "' class=' category-menu-item'>" + categories[i].name + "</a>";
                }
                categories_html += "</div></div>";
                
            } else {
                for (let i = 0; i < categories.length; i++) {
                    categories_html += "<a data-id='" + period.period_id + "-" + categories[i].id + "' class='category-label'><span >" + categories[i].name + "</span></a>";
                }
            }
        }

        let result;
        if (is_mobile) {
            result = "<div id='flashsale-period-categories-"
                    + period.period_id + "' class='categories-container'  ><div class=' swiper-wrapper' >"
                    + categories_html + "</div></div>";
        } else {
            result = "<div id='flashsale-period-categories-" + period.period_id + "' class='categories-container' style='display: none;'>" + categories_html + "</div>";
        }
        return $jq(result);
    }

    this.addMobilePagePeriodHtml=  function (period) {
        /// Period Time
        var start_date = new Date(period.start_date);
        var end_date = new Date(period.end_date);
        var now_date = new Date();

        if (now_date > end_date) {
            return null;
        }

        period_status = text_labels['sap_ban'];
        period.is_active = parseInt(period.is_active);
        period.sap_dien_ra = parseInt(period.sap_dien_ra);

        if (start_date.getDate() - 1 > now_date.getDate()) {
            //period_status = start_date.getDate() + "/" + (start_date.getMonth() + 1);
            period_status = period.start_date_label;
        } else if (start_date.getDate() > now_date.getDate()) {
            period_status = text_labels['ngay_mai'];
        } else if (period.is_active) {
            period_status = text_labels['dang_ban'];
        } else if (period.sap_dien_ra) {
            period_status = text_labels['sap_ban'];
        }

        period_html = "<div class='swiper-slide'><div class='flashsale-page-period-item' data-id='"
                + period.period_id + "'><div class='flashsale-page-period-time'>"
                + period.start_time_label + "</div><div class='flashsale-page-period-status'>"
                + period_status + "</div></div></div>";

        return $jq(period_html);
    }

    this.addSliderProductHtml = function (product) {
        if (!product) {
            return;
        }
	let episode = '';
	if(!fhs_account.isEmpty(product.episode)){
	    episode = "<div class='episode-label'>"+product.episode+"</div>";
	}
        var product_short_name = product.product_name;

        var img_sold_out = "";
        product.total_sold = parseInt(product.total_sold);
        product.total_items = parseInt(product.total_items);
        
        if (product.total_sold >= product.total_items) {
             img_sold_out = "<div class='outstock-container'><img class='flashsale-item-chay-hang' src='" + base_skin_url + CHAY_HANG_ICON_URL + "'/></div>";
        }

        var progress_bar_sold_percent = (product.total_sold / product.total_items) * 100;
        
        var discount_label = "";
        if (product.discount){
            discount_label = "<div class='new-label-pro-sale'><span class='new-p-sale-label discount-l-fs'>"
                + product.discount + "%</span></div>";

        }

        var item_html = "<li class='item flashsale-item item-inner'><div class='' style='position: relative;'>"
                + discount_label
                /// Image
                + "<div class='ma-box-content'><div class='products clearfix' style='height:203px; '><div class='product images-container'><a href='"
                + product.product_url + "' title='"
                + product.product_name
                + "'  ><div class='product-image'>"
                + "<div class='flashsale-image-container'>"
                + "<img class='lazyload flashsale-item-image' src='"+loading_icon_url+"' data-src='"
                + product.image_src + "' width='200' height='200' alt='"
                + product.product_name + "' />"
                + "</div>"
                + img_sold_out
                + "</div></a></div></div>"
                /// Name
                + "<h2 class='product-name-no-ellipsis'><a href='"
                + product.product_url + "' title='"
                + product.product_name + "'>"
                + product_short_name + "</a></h2>"
                /// Price
                + "<div class='flashsale-price'>"
                + "<div class='flashsale-price-special'>" + product.display_new_price + "</div>"
                + "<div class='flashsale-price-old'>"
                + product.display_old_price
                + "</div>"
		+ episode
		+ "</div>"
                /// Progress bar
                + "<div class='progress' ><span class='progress-value'>"
                + text_labels['da_ban'] + " " + product.total_sold
                + "</span><div class='progress-bar' role='progressbar' style='width: " + progress_bar_sold_percent + "%;' aria-valuenow='"
                + progress_bar_sold_percent + "' aria-valuemin='0' aria-valuemax='100'>"
                + "</div></div>"
                + "</div></div></li>";

        return $jq(item_html);
    }

    this.addMobileSliderProductHtml = function (product) {
        if (!product) {
            return;
        }

	let episode = '';
	if(!fhs_account.isEmpty(product.episode)){
	    episode = "<div class='episode-label'>"+product.episode+"</div>";
	}
	
        var product_short_name = product.product_name;

        var img_sold_out = "";
        product.total_sold = parseInt(product.total_sold);
        product.total_items = parseInt(product.total_items);

        if (product.total_sold >= product.total_items) {
             img_sold_out = "<div class='outstock-container'><img class='flashsale-item-chay-hang' src='" + base_skin_url + CHAY_HANG_ICON_URL + "'/></div>";
        }

        var progress_bar_sold_percent = (product.total_sold / product.total_items) * 100;

        var discount_label = "";
        if (product.discount){
            discount_label = "<div class='new-label-pro-sale'><span class='new-p-sale-label discount-l-fs'>"
                + product.discount + "%</span></div>";
        }
        
        var item_html = "<div class='swiper-slide item flashsale-item'>"
                + "<div class='' style='position: relative;padding-bottom: 10px; width: 12em; '>"
                /// discount
                + discount_label
                /// Image
                + "<div class='ma-box-content'><div class='products clearfix' style='height: 11em;'><div class='product images-container'><a href='"
                + product.product_url + "' title='"
                + product.product_name
                + "' ><div class='product-image'>"
                + "<div class='flashsale-image-container'>"
                + "<img class='lazyload flashsale-item-image' src='" + loading_icon_url + "'"
                + " data-src='" + product.image_src + "' width='200' height='200' alt='"
                + product.product_name + "' style='max-height: 10em'/>"
                + "</div>"
                + img_sold_out
                + "</div></a></div></div>"
                /// Name
                + "<h2 class='product-name-no-ellipsis m-product-name' style='width: 100% !important;'><a href='"
                + product.product_url + "' title='"
                + product.product_name + "'>"
                + product_short_name + "</a></h2>"
                /// Price
                + "<div class='flashsale-price'>"
                + "<div class='flashsale-price-special'>" + product.display_new_price + "</div>"
                + "<div class='flashsale-price-old'>"
                + product.display_old_price
		+ "</div>"
		+ episode
		+ "</div>"
                /// Progress bar
                + "<div class='progress' ><span class='progress-value'>"
                + text_labels['da_ban'] + " " + product.total_sold
                + "</span><div class='progress-bar' role='progressbar' style='width: " + progress_bar_sold_percent + "%;' aria-valuenow='"
                + progress_bar_sold_percent + "' aria-valuemin='0' aria-valuemax='100'>"
                + "</div></div>"
                //// 
                + "</div></div></div>";

        return $jq(item_html);
    }

    this.addPageProductHtml = function (product) {
        if (!product) {
            return;
        }
	
	let episode = '';
	if(!fhs_account.isEmpty(product.episode)){
	    episode = "<div class='episode-label'>"+product.episode+"</div>";
	}
	
        var product_short_name = product.product_name;

        var img_sold_out = "";
        product.total_sold = parseInt(product.total_sold);
        product.total_items = parseInt(product.total_items);
        if (product.total_sold >= product.total_items) {
            img_sold_out = "<div class='outstock-container'><img class='flashsale-item-chay-hang' src='" + base_skin_url + CHAY_HANG_ICON_URL + "'/></div>";
        }

        var progress_bar_sold_percent = (product.total_sold / product.total_items) * 100;

        var discount_html =  "";
        if (product.discount){
            if (is_mobile) {
                discount_html = "<div class='new-label-pro-sale' style='margin-left: 25px;'><span class='new-p-sale-label discount-l-fs'>"
                        + product.discount + "%</span></div>"
            } else {
                discount_html = "<div class='new-label-pro-sale' style='margin-top: 20px;'><span class='new-p-sale-label discount-l-fs'>"
                        + product.discount + "%</span></div>"
            }
        }

        var item_html = "<div class='flashsale-item-container' style=''><div class='flashsale-item'>"
                + discount_html
                /// Image
                + "<div class='ma-box-content'><div class='products clearfix'  ><div class='product images-container'><a href='"
                + product.product_url + "' title='"
                + product.product_name + "' ><div class='product-image'>"
                + "<div class='flashsale-image-container'>"
                + "<img class='lazyload flashsale-item-image' src='" + loading_icon_url + "'"
                + "' data-src='" + product.image_src + "' width='200' height='200' alt='"
                + product.product_name + "' />"
                + "</div>"
                + img_sold_out
                + "</div></a></div></div>"
                /// Name
                + "<h2 class='product-name-no-ellipsis'><a href='"
                + product.product_url + "' title='"
                + product.product_name + "'>"
                + product_short_name + "</a></h2>"
                /// Price
                + "<div class='flashsale-price'><div class='flashsale-price-special'>" + product.display_new_price + "</div>"
                + "<div class='flashsale-price-old'>"
                + product.display_old_price
                + "</div>"
		+ episode
		+ "</div>"
                /// Progress bar
                + "<div class='progress' ><span class='progress-value'>"
                + text_labels['da_ban'] + " " + product.total_sold
                + "</span><div class='progress-bar' role='progressbar' style='width: " + progress_bar_sold_percent + "%;' aria-valuenow='"
                + progress_bar_sold_percent + "' aria-valuemin='0' aria-valuemax='100'>"
                + "</div></div></div></div></div>";

        return $jq(item_html);
    }
    
    this.startCountdown = function ($parent, future_date_time) {
        /// flashsale-countdown-number
        $countdown = $parent.find(".flashsale-countdown .flashsale-countdown-number");
        $hours = $countdown.eq(0);
        $minutes = $countdown.eq(1);
        $seconds = $countdown.eq(2);
        
        $hours1 = $countdown.eq(3);
        $minutes1 = $countdown.eq(4);
        $seconds1 = $countdown.eq(5);
        
        if(countdown_timer){
            clearInterval(countdown_timer);
        }
        
        if(countdown_timer1){
            clearInterval(countdown_timer1);
        }
        
        $this.calculateTime(future_date_time, $hours, $minutes, $seconds);
        
        
        countdown_timer = setInterval(function(){
            $this.calculateTime(future_date_time, $hours, $minutes, $seconds);
        }, 1000);
        
        countdown_timer1 = setInterval(function(){
            $this.calculateTime(future_date_time, $hours1, $minutes1, $seconds1);
        }, 1000);
    }
    
    this.calculateTime = function(future_date_time, $hours, $minutes, $seconds){
        
        let vn_time_moment = moment(new Date()).utcOffset(7);
        let vn_time_formated = vn_time_moment.format("YYYY/MM/DD HH:mm:ss");
        let time = Helper.substractDates(vn_time_formated, future_date_time);

        if (time.days >= 1) {
            time.hours += 24 * time.days;
        }

        $hours.text(Helper.zeroPad(time.hours, 2));
        $minutes.text(Helper.zeroPad(time.minutes, 2));
        $seconds.text(Helper.zeroPad(time.seconds, 2));
    }
    
    this.periodTabClicked = function (event) {
        //change to default category_id
        current_category_id = CATEGORY_ID_ALL;
        if ($current_period_tab) {
            $current_period_tab.removeClass("flashsale-page-period-active");
        }

        $current_period_tab = $jq(this).parent();
        $current_period_tab.addClass("flashsale-page-period-active");

        //href = $jq(this).attr("data-id");
        //id = href.split("#")[1];
        id = $jq(this).attr("data-id");
        
        //get categories view in period
        $active_categories = $categories_list.find("#flashsale-period-categories-" + id);
        if ($current_categories_content){
            $current_categories_content.hide();
        }
        
        $current_categories_content = $active_categories;
        $current_categories_content.show();
        
        $active_period_products = $period_content.find("#" + PERIOD_PRODUCTS_ID_KEY + id + '-' + CATEGORY_ID_ALL);
        if ($current_products_content) {
            $current_products_content.hide();
        }
        $current_products_content = $active_period_products;
        $current_products_content.show();

        current_period_id = id;
        var _period_data = periods_data[current_period_id][current_category_id];
        /// First time loading period products
        if (_period_data.current_page == 0) {
            let _category_id = 0;
            $this.getNextProducts(current_period_id, _period_data.current_page, _category_id);
        }

        //$this.startCountdown($flashsale_page, _period_data['end_date']);
        if (_period_data.is_active) {
            $page_countdown_label.text(text_labels['ket_thuc']);
            if (is_mobile) {
                $this.startCountdown($flashsale_page_mobile, _period_data['end_date']);
            } else {
                $this.startCountdown($flashsale_page, _period_data['end_date']);
            }
        } else {
            $page_countdown_label.text(text_labels['bat_dau']);
            if (is_mobile) {
                $this.startCountdown($flashsale_page_mobile, _period_data['start_date']);
            } else {
                $this.startCountdown($flashsale_page, _period_data['start_date']);
            }
        }
        $current_category_tab.removeClass("flashsale-page-category-active");
        $current_category_tab = $jq("a[data-id|='" + current_period_id + "-" + current_category_id + "']");
        $current_category_tab.addClass("flashsale-page-category-active");

        event.stopPropagation();
    }
    
    this.categoryTabClicked = function (event) {
        if ($current_category_tab) {
            $current_category_tab.removeClass("flashsale-page-category-active");
        }

        $current_category_tab = $jq(this);
        $current_category_tab.addClass("flashsale-page-category-active");

        let id = $jq(this).attr("data-id");
        let data_arr = id.split("-");
        if (data_arr.length == 2) {
            let category_id = data_arr[1];
            current_category_id = category_id;
            $active_period_products = $period_content.find("#" + PERIOD_PRODUCTS_ID_KEY + current_period_id + '-' + current_category_id);
            if ($current_products_content) {
                $current_products_content.hide();
            }
            $current_products_content = $active_period_products;
            $current_products_content.show();

            var _period_data = periods_data[current_period_id][current_category_id];
            /// First time loading period products
            if (_period_data.current_page == 0) {
                $this.getNextProducts(current_period_id, _period_data.current_page, current_category_id);
            }
        }
    }

    this.moreOptionClicked = function (event) {
        let ids = $jq(this).attr("id");
        let id = ids.replace("category-more-option-", "");

        let $menu_options = $jq("#menu-option-" + id);
        if ($menu_options.is(":visible")) {
            $menu_options.hide();
        } else {
            $menu_options.show();
            $jq(document).click(function () {
                if ($menu_options.is(":visible")) {
                    $menu_options.hide();
                }
            });
        }
        event.stopPropagation();
    }
    
    this.checkFlashSaleBottom = function () {
        
        var _period_data = periods_data[current_period_id][current_category_id];
        if (_period_data
                && !_period_data.is_fully_loaded
                && !_period_data.is_loading
                && Helper.isElementInViewport($page_bottom)) {
            $this.getNextProducts(current_period_id, _period_data.current_page + 1, current_category_id);
        }
    }

    this.printPagePriceHtml = function (product_id, price, final_price) {
        html = '<div class="price-box"><p class="special-price"><span class="price-label">Special Price</span><span class="price" id="product-price-'
                + product_id + '">'
                + Helper.formatCurrency(final_price)
                + '</span></p><p class="old-price"><span class="price-label">Regular Price:</span><span class="price" id="old-price-'
                + product_id + '">'
                + price
                + '</span></p></div>';

        return $jq(html);
    }
    this.printProductPriceV2 = function(product_id, price, final_price, discount){
	   return "<p class='special-price'>"
		+"<span class='price-label'>Special Price</span>"
		    +"<span class='price' id='product-price-"+product_id+"'>"+Helper.formatCurrency(final_price)+"</span>"
		+"</p>"
		+"<p class='old-price'>"
		+"<span class='price-label'>Regular Price:</span>"
		+"<span class='price' id='old-price-"+product_id+"'>"+price+"</span>"
		+"<span class='discount-percent'>-" + discount + "%</span>"
	    +"</p>";
    }

    this.printProductDiscountHtml = function (discount, is_mobile) {
        if (discount){
            if (is_mobile) {
                html = "<div class='new-label-pro-sale f-dis-label'><span class='new-p-sale-label'>" + discount + "%</span></div>";
            } else {
                html = "<span class='discount-percent'>-" + discount + " %</span>";
            }
        }

        return $jq(html);
    }
}
