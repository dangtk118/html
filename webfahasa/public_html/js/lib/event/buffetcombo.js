
const EventBuffetCombo = function () {
    /// Constants
    const PAGE_URL = "/node_api/event/buffetcombo/page";
    const NEXT_PAGE_URL = "/node_api/event/buffetcombo/next";
    const PRODUCTS_PER_ROW = 10;
    
    /// Variables
    let $this = this;
    let products_data = {};
    let category_data;
    let text_labels;
    let is_mobile;
    let is_buffetpage;
    let $page_bottom = $jq("#event-buffetcombo-bottom");
    let $loading_icon = $jq("#event-buffetcombo-loading-icon");
    let $current_container;
    let $current_menu_item;
    let current_category_id;
    let order_by = 'week';
    
    this.init = function (_text_labels, _is_mobile, _is_page, category_data_input) {
        
        text_labels = _text_labels;
        if (category_data_input){
            category_data = category_data_input;
        } else{
            category_data = [
                {
                    id: 'all',
                    display_name: _text_labels['all']
                },
                {
                    id: '9',
                    display_name: _text_labels['van_hoc']
                },
                {
                    id: '11',
                    display_name: _text_labels['kinh_te']
                },
                {
                    id: '12',
                    display_name: _text_labels['tam_ly_ky_nang']
                },
                {
                    id: '6009',
                    display_name: _text_labels['nuoi_day_con']
                }
            ];
        }
        
        
        is_mobile = _is_mobile;
        is_buffetpage = _is_page;
        
        current_category_id = Helper.getQueryParam('cat_id') || category_data[0].id;
        products_data = {};
        
        if(is_mobile && is_buffetpage){
            $this.createMobileMenu();
        }else{
            if (is_mobile) {
                new Swiper('.buffetcombo-swiper-menu', {
                    direction: 'horizontal',
                    slidesPerView: 'auto',
                    freeMode: true,
                    longSwipesMs: 800
                });
            }
            $this.createWebMenu();
        }
        
        if(!products_data[current_category_id]){
            current_category_id = category_data[0].id;
            $this.init(_text_labels, _is_mobile, _is_page);
            return;
        }
        
        $current_container = $jq("#event-buffetcombo-container-" + current_category_id);
        $current_container.html("");
        
        $current_container.show();
        
        $this.getProducts($current_container, current_category_id, 0);
    }
    
    this.createMobileMenu = function(){
        new Swiper('.buffetcombo-swiper-menu', {
            direction: 'horizontal',
            slidesPerView: 'auto',
            freeMode: true,
            longSwipesMs: 800
        });
        
        $jq("#event-buffetcombo-menu-mobile ul").empty();
        
        for (i = 0; i < category_data.length; i++) {
            products_data[category_data[i].id] = {
                is_loading: false,
                is_fully_loaded: false,
                current_page: -1,
                is_active: 0
            }
            
            $menu_item = $jq("<li id='buffetcombo-menu-item-"+ category_data[i].id 
                + "' data-id='" + category_data[i].id + "' class='swiper-slide buffetcombo-menu-mobile-item'><a href='?cat_id="
                + category_data[i].id + "#event-buffetcombo-header'>"
                + category_data[i].display_name +"</a></li>");
            
            if(current_category_id == category_data[i].id){
                $menu_item.addClass('active');
                $current_menu_item = $menu_item;
            }
            
            $jq("#event-buffetcombo-menu-mobile ul").append($menu_item);
            $container = $jq("<div id='event-buffetcombo-container-"+ category_data[i].id +"'></div>");
            $container.hide();
            $jq("#event-buffetcombo-container").append($container);
        }
    }
    
    this.createWebMenu = function(){
        $jq("#event-buffetcombo-menu ul").empty();
        
        for (i = 0; i < category_data.length; i++) {
            products_data[category_data[i].id] = {
                is_loading: false,
                is_fully_loaded: false,
                current_page: -1,
                is_active: 0
            }
            
            if(is_buffetpage){
                $menu_item = $jq("<li id='buffetcombo-menu-item-"+ category_data[i].id 
                    + "' data-id='" + category_data[i].id + "' class='buffetcombo-menu-item'><a href='?cat_id="
                    + category_data[i].id + "#event-buffetcombo-header'>"
                    + category_data[i].display_name +"</a></li>");
            }else{
                $menu_item = $jq("<li id='buffetcombo-menu-item-"+ category_data[i].id 
                    + "' data-id='" + category_data[i].id + "' class='buffetcombo-menu-item'><a>"
                    + category_data[i].display_name +"</a></li>");
                $menu_item.click($this.clickTab);
            }
            
            if(current_category_id == category_data[i].id){
                $menu_item.addClass('active');
                $current_menu_item = $menu_item;
            }
            
            $jq("#event-buffetcombo-menu ul").append($menu_item);
            $container = $jq("<div id='event-buffetcombo-container-"+ category_data[i].id +"'></div>");
            $container.hide();
            $jq("#event-buffetcombo-container").append($container);
        }
        
        /// menu navigation underline effect
//        $jq("#event-buffetcombo-menu ul").append("<hr/>");
    }
    
    this.clickTab = function(event){
        let cat_id = $jq(this).attr("data-id");
        if(current_category_id){
            current_category_id = cat_id;
        }
        
        if($current_container){
            $current_container.hide();
        }
        
        if($current_menu_item){
            $current_menu_item.removeClass('active');
        }
        
        $current_container = $jq("#event-buffetcombo-container-" + current_category_id);
        $current_container.show();
        
        $current_menu_item = $jq("#buffetcombo-menu-item-" + current_category_id);
        $current_menu_item.addClass('active');
        
        if(!products_data[cat_id].is_fully_loaded){
            $this.getProducts($current_container, current_category_id, 0);
        }
    }
    
    this.getProducts = function ($container, cat_id, page) {

        $loading_icon.show();
        products_data[cat_id].is_loading = true;
        $jq.ajax({
            url: PAGE_URL,
            method: 'post',
            data: {
                cat_id: cat_id,
                page: page,
                is_buffetpage: is_buffetpage,
                order_by: order_by
            },
            success: function (data) {
                if(!data.result || !data.products){
                    products_data[cat_id].is_loading = false;
                    return;
                }
                
                if(data.products.length == 0){
                    products_data[cat_id].is_fully_loaded = true;
                }
                $loading_icon.hide();
                products_data[cat_id].is_loading = false;
                products_data[cat_id].current_page = page;
                $this.addProductsToContainer($container, data.products);
                
                if(page == 0){
                    if(is_buffetpage){
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
                    }else{
                        if(is_mobile){
                            new Swiper('#event-buffetcombo-container .swiper-container-buffetcombo', {
                                direction: 'horizontal',
                                slidesPerView: 'auto',
                                freeMode: true,
                                longSwipesMs: 800
                            });
                        }else{
                            $slider_container = $container.find("ul");
                            $slider_container.bxSlider(
                                {
                                    pause: 0,
                                    minSlides: 1,
                                    maxSlides: 4,
                                    slideWidth: '380px',
                                    infiniteLoop: false,
                                    touchEnabled: false,
                                    hideControlOnEnd: true,
                                    preloadImages: 'visible',
                                    onSliderLoad: function (slide, oldIndex, newIndex) {
                                        $container.find(".bx-wrapper").css("max-width","inherit");
                                    }
                                }
                            );
                        }
                        products_data[cat_id].is_fully_loaded = true;
                    }
                    
                    if(data['total_combo'] <= 0){
                        $jq("#event-buffetcombo-msg-total").show();
                    }
                }
            }
        });
    }
    
    this.addProductsToContainer = function($container, products){
        let product_html, count = products.length;
        
        if(is_buffetpage){
            let $current_row;
            for(let i=0; i < count; i++){
                if((i % PRODUCTS_PER_ROW) == 0){
                    $current_row = $jq("<div class='row' style='margin-right: 0px;'></div>");
                    $container.append($current_row);
                }
                
                product_html = $this.printProductHtml(products[i]);
                $current_row.append($jq(product_html));
            }
        }else{
            if(is_mobile){
                $list = $jq("<div style='overflow: hidden;' class='swiper-container-buffetcombo'><div class='bxslider swiper-wrapper'></div></div>");
                $div = $list.find(".bxslider");
                for (let i = 0; i < count; i++) {
                    product_html = $this.printSliderProductHtml(products[i]);
                    $div.append($jq(product_html));
                }
                $container.append($list);
            }else{
                $ul = $jq("<ul style='margin-left: 15px;'></ul>");
                for(let i=0; i < count; i++){
                    product_html = $this.printSliderProductHtml(products[i]);
                    $ul.append($jq(product_html));
                }
                $container.append($ul);
            }
        }
    }

    this.checkPageBottom = function () {
        var data = products_data[current_category_id];
        if (!data.is_fully_loaded
                && !data.is_loading
                && Helper.isElementInViewport($page_bottom)) {
            
            $this.getProducts($current_container, current_category_id, data.current_page + 1);
        }
    }

    /*
     *  Return Product Html ( no review ratings)
     */
    this.printProductHtml = function (product){
        if (!product) {
            return;
        }
        
        var product_short_name = product.product_name;
        let discount_html = $this.printDiscount(product.discount);
        let price_html = $this.printPrices(product.display_price, product.display_final_price);
        let img_sold_out = $this.printSoldOut(product.current_qty);

        var item_html = "<div class='event-buffetcombo-page-item'>"
        + "<div class='item-inner' style='position: relative;background-color: #fff;'>"
        // Discount
        + discount_html
        + "<div class='ma-box-content'>"
        // Image
        + "<div class='products clearfix'><div class='product images-container'>"
        + "<a href='/" + product.product_url + "' title='" + product.product_name + "' class='product-image'>"
        + "<div class='product-image' >"
        + "<img src='" + product.image_src + "' data-src='" + product.image_src + "' alt='" + product.product_name + "'/></div></a></div></div>"
        // Sold Out
        + img_sold_out
        // Product Name
        + "<h2 class='product-name-no-ellipsis'>"
        + "<a href='/" + product.product_url + "' title='" + product.product_name + "' class='product-image'>"
        + product_short_name +"</a></h2>"
        + price_html
        + "</div></div></div>";

        return item_html;
    }
    
    this.printSliderProductHtml = function (product) {
        if (!product) {
            return;
        }
        
        let product_short_name = product.product_name;
        let discount_html = $this.printDiscount(product.discount);
        let price_html = $this.printPrices(product.display_price, product.display_final_price);
        let img_sold_out = $this.printSoldOut(product.current_qty);
        
        let item_html;
        if(is_mobile){
            item_html = "<div class='swiper-slide item'>";
        }else{
            item_html = "<li class='item sl-width event-buffetcombo-item'>";
        }
        
        item_html += "<div class='item-inner'>"
        // Discount
        + discount_html
        + "<div class='ma-box-content'>"
        // Image
        + "<div class='products clearfix'><div class='product images-container'>"
        + "<a href='/" + product.product_url + "' title='" + product.product_name + "' class='product-image'>"
        + "<div class='product-image'>"
        + "<img src='" + product.image_src + "' data-src='" + product.image_src + "' alt='" + product.product_name + "'/></div></a></div></div>"
        // Sold Out
        + img_sold_out
        // Product Name
        + "<h2 class='product-name-no-ellipsis'>"
        + "<a href='/" + product.product_url + "' title='" + product.product_name + "' class='product-image'>"
        + product_short_name +"</a></h2>"
        + price_html
        + "</div></div>";
        
        if(is_mobile){
            item_html += "</div>";
        }else{
            item_html += "</li>";
        }
        return item_html;
    }
    
    this.printDiscount = function(discount){
        let discount_html = "";
        
        if(parseInt(discount)>0){
            if (is_mobile) {
                discount_html = "<div class='m-label-pro-sale'><span class='p-sale-label m-discount-l-fs'>" 
                        + discount + "%</span></div>";
            } else {
                discount_html = "<div class='label-pro-sale'><span class='p-sale-label discount-l-fs'>" 
                        + discount + "%</span></div>";
            }
        }
        
        return discount_html;
    }
    
    this.printPrices = function(price, final_price){
        let price_html = "";
        if(parseInt(final_price) < parseInt(price)){
            price_html = "<div class='price-label'><p class='special-price'><span class='price m-price-font'>" 
                + final_price + "</span></p><p class='old-price'><span class='price m-price-font'>" 
                + price + "</span></p></div>";
        }else if(parseInt(price)>0){
            price_html = "<div class='price-label'><p class='special-price'><span class='price m-price-font'>" 
                + price + "</span></p></div>";
        }
        
        return price_html;
    }
    
    this.printSoldOut = function(current_qty){
        let img_sold_out = "<img class='buffetcombo-item-out-of-stock' style='display:none' src='/skin/frontend/ma_vanese/fahasa/images/flashsale/Chay-hang-icon.png'/>";
        current_qty = parseInt(current_qty);
        if (current_qty <= 0) {
            if(is_mobile){
                img_sold_out = "<img class='buffetcombo-item-out-of-stock-mobile' src='/skin/frontend/ma_vanese/fahasa/images/flashsale/Chay-hang-icon.png'/>";
            }else{
                img_sold_out = "<img class='buffetcombo-item-out-of-stock' src='/skin/frontend/ma_vanese/fahasa/images/flashsale/Chay-hang-icon.png'/>";
            }
        }
        
        return img_sold_out;
    }
    
    this.setOrderBy = function(_order_by){
        order_by = _order_by;
        console.log("Order :" + order_by);
        $this.init(text_labels, is_mobile, is_buffetpage);
    }
}
    