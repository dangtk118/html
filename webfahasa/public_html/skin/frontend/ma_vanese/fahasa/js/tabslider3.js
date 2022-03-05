var Tabslider3 = function () {
    var $this = this;
    var dataContent;
    var languages = null;
    var is_window_ready = false;
    var current_block_tab;
    var current_block_id;
    var is_load_tab_first = false;
    var is_tabs_load_success = false;
    var post_list_url = "related_api/api/related_products";
    var post_products_url = "related_api/api/related_products/tab"; 
    var product_id = null;
    var curr_type_id_active = null;
    var list_product_backup = {};
    var list_swiper_product = {};
    var tab_loaded_list = {};
    var swipe_loaded_list = {};
    var class_active_tabs = 'active';
    var width_reponsive_siwper = 992;
    var load_only_type = null; // tach type_id
    var type_id_split = 1; // Ban DOc Quan Tam ///
    var type_id_split_2 = 0; // FH-gioi thieu ///
    var type_id_split_3 = -1; // FH-gioi thieu ///
    var type_id_split_4 = 4; // series SKG ///
    var cae_mid_split = [6718,15,"6718","15"];
    var cae_mid_split_2 = [6718,"6718"];
    var cae_mid_split_3 = [15,"15"];
    var cae_main_split_4 = [86,"86"];
    var cate_mid_id = null;
    var cate_main_id = null;
    var cate_array = null;
    var is_set_active = true;
    
    this.init = function (_languages,_current_block , _product_id ,_cate_mid_array ,_load_only_type, _post_list_url , _post_products_url) {
        current_block_id = _current_block;
        current_block_tab = $jq("#fhs_tabslider3_tab_" + _current_block);
        dataContent = null;
        languages = _languages;
//        post_list_url = _post_list_url;
//        post_products_url = _post_products_url;
        product_id = _product_id;
        load_only_type = _load_only_type;
        cate_mid_id =  _cate_mid_array['mid_id'];
        cate_main_id = _cate_mid_array['main_id'];
        cate_array =  _cate_mid_array;
        
        // su kien click tab khac
        $jq('#fhs_tabslider3_ul_' + current_block_id).on('click', 'li.fhs_tabslider3_item', function (e) {
            let click_tab = $jq(this);            
            let tab_id = click_tab.attr("ref");
            if (click_tab.hasClass(class_active_tabs)) {
                return;
            }
	    $this.onActiveTab(tab_id,click_tab);
        });
       
        // Start checking viewport
        $jq(window).ready(function () {
            is_window_ready = true;
            var to_check_resize = true;
            $jq(window).on('resize scroll', function () {
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
    }
   
    this.checkBlockInViewport = function () {
        if (!is_load_tab_first && Helper.isElementInViewport(current_block_tab)) {
            is_load_tab_first = true;
            if (load_only_type['isLoad'] === true) {
                let only_type_id = load_only_type['typeId'];
                curr_type_id_active = only_type_id;
                $this.getOnlyDataTypeId(only_type_id)
            } else {
                $this.getListTabProducts()
            }
        }
    }
    
    this.getListTabProducts = function(){
        let mid_id = (cate_mid_id)
        let main_id = (cate_main_id)
        let content_type = 'application/json; charset=utf-8';
        let countSplitTab = 0;
        let tabs_id = languages['tabs_id'];
        let dataJson = JSON.stringify({
            product_id: product_id,
        });
        $jq.ajax({
            url: post_list_url,
            method: 'post',
            dataType: "json",
            contentType: content_type,
            data: dataJson,
            success: function (result) {
                if (result && result['status'] == true && result['message'] == "Successfull" && result['data'].length > 0) {
                    let htmlForTabs = '';
                    let containerProductHtml = "";
                    let loading_url_icon = languages['iconLoadingURL'];
                    let resultDt = { ...result['data']};
                    let showText = 'none';
                    let active_typeID = null;
                    
                    $jq.each(resultDt, function (key, value) {
                        // split tabs to another block
                        if (tabs_id && tabs_id.length > 0) {
                            // tabs_id have data : 
                            if (tabs_id.includes(value.type_id)) {
                                countSplitTab++;
                                return true;
                            }
                        } else {
                            if (value.type_id == type_id_split && cae_mid_split.includes(mid_id)) {
                                countSplitTab++;
                                return true;
                            }

                            if ((value.type_id === type_id_split_2 && cae_mid_split_2.includes(mid_id)) ||
                                    (value.type_id === type_id_split_3 && cae_mid_split_3.includes(mid_id))) {
                                countSplitTab++;
                                return true;
                            }

                            if ((value.type_id === type_id_split_4 && cae_main_split_4.includes(main_id)) ||
                                    (value.type_id === type_id_split_4 && cae_mid_split_3.includes(mid_id))) {
                                countSplitTab++;
                                return true;
                            }
                        }
                        
                        // handle giu lai 5 san pham :
                        list_product_backup[value.type_id] = value;
                        swipe_loaded_list['swipe_'+ value.type_id] = false;
                        showText = 'none';
                        let activeHTMl = "";
                        if(value['is_active'] == 1 && is_set_active == true){
                            is_set_active = false;
                            activeHTMl = class_active_tabs;
                            active_typeID = curr_type_id_active =  value.type_id;
                            showText = 'block';
                            tab_loaded_list['tab'+active_typeID] = true;
                        }
                        htmlForTabs += '<li class="fhs_tabslider3_item ' + activeHTMl + ' swiper-slide" ref="' + value.type_id + '"><a>' + value.tab_name + '</a><hr/></li>';
                        
                        // show button more for FH-Gioi thieu
                        if (value.type_id === 0 || value.type_id == 3 ) {
                            let link = $this.onButtonHrefTypeEvent(value.type_id,value.seri_id)
                            containerProductHtml += $this.onContainerProductHtml(value.type_id, showText, link, true);
                        } else {
                            containerProductHtml += $this.onContainerProductHtml(value.type_id, showText);
                        }
                        
                    });
                    
                    // count split == tab length => hide tab recommended
                    if (Object.keys(resultDt).length == countSplitTab) {
                        current_block_tab.hide();
                        return;
                    }                  
                    // container cua tabs :
                    $jq("#fhs_tabslider3_ul_" + current_block_id).empty();
                    $jq("#fhs_tabslider3_ul_" + current_block_id).append(htmlForTabs);
                    
                    // container cua content tabs :
                    $jq("#fhs_tabslider3_pro_" + current_block_id).empty();
                    $jq("#fhs_tabslider3_pro_" + current_block_id).append(containerProductHtml);
                    
                    // handle has no active : 
                    if(is_set_active == true){
                        is_set_active = false;
                        let class_active = $jq(".fhs_tabslider3_item").first();
                        let class_active_type_id = class_active.attr("ref");
                        active_typeID = class_active_type_id;
                        $this.onActiveTab(class_active_type_id,class_active);
                    }
                  
                    
                    // ----- 
                    is_tabs_load_success = true;
                    $this.onRenderHtmlSuccess(active_typeID)
                    $this.onSwiperTab(current_block_id);
                    $this.getDataProduct(active_typeID);

                }else{
                    // hiden tab
                    current_block_tab.hide();
                }
            },
            error: function () {
                // hiden tab
                current_block_tab.hide();
            }
        });
    }
    
    this.onShowHideLoadingBlock = function (type = 'show', type_id) {
        // inclue icon conatiner , icon content product 
        let loadingIconBlock = $jq("#fhs_tabslider3_tab_" + current_block_id +" #fhs_tabslider3_loading_" + type_id);
        if (type == "hide") {
            loadingIconBlock.hide();
        }
        if (type == "show") {
            loadingIconBlock.show();
        }
    }
    
    this.onShowHideProductTab = function (type = 'show', type_id) {
        // inclue icon conatiner , icon content product 
        let showProductsTabs = $jq("#fhs_tabslider3_tab_" + current_block_id +" #fhs_tabslider3_products_tabs_" + type_id);
        if (type == "hide") {
            showProductsTabs.hide();
        }
        if (type == "show") {
            showProductsTabs.show();
        }
    }
    
    this.onShowHidebuttonPreNext = function (type = 'show', type_id) {
        // inclue icon conatiner , icon content product 
        let showProductsTabs = $jq("#fhs_tabslider3_tab_" + current_block_id +" #arrow_deActive_" + type_id);
        if (type == "hide") {
            showProductsTabs.hide();
        }
        if (type == "show") {
            showProductsTabs.show();
        }
    }
    
    this.onShowHideContent = function (type = 'show') {
        let loadingIconBlock = $jq("#fhs_tabslider3_tab_" + current_block_id +" #fhs_tabslider3_tabs_content_" + current_block_id);
        if (type == "hide") {
            loadingIconBlock.hide();
        }
        if (type == "show") {
            loadingIconBlock.show();
        }
    }
    
    this.onShowHideContentProduct = function (type = 'show') {
        let loadingIconBlock = $jq("#fhs_tabslider3_tab_" + current_block_id +" #fhs_tabslider3_pro_" + current_block_id);
        if (type == "hide") {
            loadingIconBlock.hide();
        }
        if (type == "show") {
            loadingIconBlock.show();
        }
    }
    
    this.onShowHideButtonMore = function (type = 'show', type_id) {
        let showButtonMore = $jq("#fhs_tabslider3_tab_" + current_block_id +" #fhs_tabslider3_button_more_" + type_id);
        if (type == "hide" && showButtonMore) {
            showButtonMore.hide();
        }
        if (type == "show" && showButtonMore) {
            showButtonMore.show();
        }
    }
    
    this.onRenderHtmlSuccess = function(type_id){
        $this.onShowHideLoadingBlock('hide',current_block_id);
//        $this.onShowHideProductTab('show',type_id);
        $this.onShowHideContent('show');
    }
    
    this.getDataProduct = function (type_id) {
        let content_type = 'application/json; charset=utf-8';
        let dataJson = JSON.stringify({
            product_id: product_id,
            tab_id: type_id
        });
        $jq.ajax({
            url: post_products_url,
            method: 'post',
            dataType: "json",
            contentType: content_type,
            data: dataJson,
            success: function (result) {
                let dataHandle = new Array();
                if (result && result['status'] == true && result['message'] == "Successfull" && Object.keys(result['data']).length > 0) {
                    $this.onRenderProducts(result['data'],type_id)
                }else{
                    $this.onRenderAnotherProducts(type_id)
                }
            },
            error: function () {
                $this.onRenderAnotherProducts(type_id)
            }
        });
    }
    
    this.onProductHtml = function(product){
        var item_name = product.name_a_label;
        let episode = '';
        let item_html = '';
        let baseUrl = 'https://www.fahasa.com/';
	if(product['episode']){
	    episode = "<div class='episode-label'>"+product['episode']+"</div>";
	}
        let hasSeriesId = product.seriesId && product.hasOwnProperty('seriesId') ? true : false;
        if (hasSeriesId && product.type_id == 'series' ) {
            item_html = '<div class="fhs_tabslider3_li_items swiper-slide">'
                    + "<div class='item-inner' style='position: relative'>"
                    + "<div class='ma-box-content'><div class='products clearfix'><div class='product images-container'>"
                    + "<a href='" + product.product_url + product.source + "' title='"
                    + product.image_label + "' class='product-image'><div class='product-image'><img src='"
                    + languages['iconLoadingURLgif'] + "' data-src='"
                    + baseUrl + product.image_src + "' class='lazyload' alt='"
                    + product.image_label + "' /></div></div>"
                    + "</a></div></div>"
                    + "<h2 class='product-name-no-ellipsis fhs-series'>"
                        +"<a class='product-image' href='"+ product.product_url + product.source + "' title='"+ product.name_a_title + "'>" 
                            +"<span class='fhs-series-label'><i></i></span>"
                            + item_name
                        + "</a>"
                    +"</h2>"
                    + "<div class='product-seri-info'>"
                        +" <div class='fhs-series-episode-label'>" + product['episode'] + "</div>"
                        + "<div class='fhs-series-subscribes'>" + product.subscribes + " lượt theo dõi</div>"
                    +"</div>"
                    +'</div>'
        } else {
            let discount_html = '';
            if(product.discount && product.discount > 0){
               discount_html = '<div class="label-pro-sale m-label-pro-sale"><span class="p-sale-label discount-l-fs">'+product.discount+'%</span></div>';
            }
            item_html = '<div class="fhs_tabslider3_li_items swiper-slide">'
                    + "<div class='item-inner' style='position: relative'>"
                    + discount_html
                    + "<div class='ma-box-content'><div class='products clearfix'><div class='product images-container'>"
                    + "<a href='" + product.product_url + product.source + "' title='"
                    + product.image_label + "' class='product-image'><div class='product-image'><img src='"
                    + languages['iconLoadingURLgif'] + "' data-src='"
                    + baseUrl + product.image_src + "' class='lazyload' alt='"
                    + product.image_label + "' /></div></div>"
                    + "</a></div></div>"
                    + "<h2 class='product-name-no-ellipsis'><a href='"
                    + product.product_url + product.source + "' title='"
                    + product.name_a_title + "'>"
                    + item_name + "</a></h2><div class='price-label products-grid'>"
                    + $this.onPriceHtml(product.display_price,product.display_final_price,product.product_id)
                    + episode
                    + "</div><div class='fhs-rating-container' style='height:20px'>"
                    + Helper.printProductRating(product.rating_summary, product.rating_count, false) + "</div></div>"
                    + '</div>'
        }
        return item_html;
    }
    
    this.onPriceHtml = function (price, final_price, product_id) {
        let priceFormat = Helper.formatCurrency(price);
        let finalPriceFormat = Helper.formatCurrency(final_price);
        if (parseInt(final_price) > 0 && final_price != price) {
            return "<p class='special-price'>"
                    + "<span class='price-label'>Special Price</span>"
                    + "<span id='product-price-" + product_id + "' class='price m-price-font'>" + finalPriceFormat + "</span>"
                    + "</p>"
                    + "<p class='old-price bg-white'>"
                    + "<span class='price-label'>Giá bìa: </span>"
                    + "<span id='old-price-" + product_id + "' class='price m-price-font'>" + priceFormat + "</span>"
                    + "</p>"
        } else {
            return "<p class='special-price'>"
                    + "<span class='price-label'>Special Price</span>"
                    + "<span id='product-price-" + product_id + "' class='price m-price-font'>" + finalPriceFormat + "</span>"
                    + "</p>"
        }
    }
    
    this.onContainerProductHtml = function (type_id, showText, href = '' , showButtonMore = false) {
        let htmlButtonMore = '';
        if (showButtonMore && href) {
                htmlButtonMore = '<div id="fhs_tabslider3_button_more_' + type_id + '" style="display:none">'
                    + '<div class="tabs-xem-them xem-them-item-aaa" >'
                    +   '<a href="' + href + '">'+ languages['view_more'] +'</a>'
                    + '</div>'
                    + '</div>'
        }
        let containerProductHtml = '';
        let htmlButton = '<div id="arrow_deActive_' + type_id + '" class="deActive_mobile" style="display:' + showText + '">'
                + '<div class="fhs_tab3_' + type_id + '_prev fhs-tab3-slider-prev swiper-button-prev" style="display:none"></div>'
                + '<div class="fhs_tab3_' + type_id + '_next fhs-tab3-slider-next swiper-button-next"></div>'
                + '</div>';
        containerProductHtml += ''
                + '<div id="fhs_tabslider3_products_tabs_' + type_id + '" class="fhs_tabslider3_products" style="display:none">'
                + '<div class="fhs_tabslider3_products_ul swiper-wrapper"></div>'
                + htmlButton
                + '</div>';
        containerProductHtml += htmlButtonMore
        containerProductHtml += '<div class="fhs_tabslider3_loading_icon" id="fhs_tabslider3_loading_' + type_id + '" style="display:' + showText + '" ><img src="' + languages['iconLoadingURL'] + '" class="img-responsive center-block"></div>'

        return containerProductHtml;
    }
    
    this.onRenderProducts = function (array,type_id) {
        let htmlProducts = '';
        let hasProduct = false;
        $jq("#fhs_tabslider3_pro_"+ current_block_id +" #fhs_tabslider3_products_tabs_" + type_id + " .fhs_tabslider3_products_ul").empty();
        $jq.each(array, function (key, value) {
            if (value['products'].length > 0) {
                hasProduct = true;
                $jq.each(value['products'], function (key1, value1) {
                    htmlProducts = $this.onProductHtml(value1);
                    $jq("#fhs_tabslider3_pro_"+ current_block_id +" #fhs_tabslider3_products_tabs_" + type_id + " .fhs_tabslider3_products_ul").append(htmlProducts);
                });
            }

        });
        if (hasProduct && type_id === curr_type_id_active) {
            $this.onShowHideLoadingBlock('hide', type_id);
            $this.onShowHidebuttonPreNext('show', type_id);
            $this.onShowHideButtonMore('show', type_id);
            $this.onShowHideProductTab('show', type_id);
            $this.onSwipe(type_id);
        } else {
            $this.onShowHideLoadingBlock('hide', type_id);
            $this.onShowHideButtonMore('hide', type_id);
        }
    }
    
    this.onRenderAnotherProducts = function (type_id) {
        if (load_only_type['isLoad'] == true) {
            // handle fail of split tab
            $this.onShowHideLoadingBlock('hide', type_id);
            $this.onShowHideButtonMore('hide', type_id);
            current_block_tab.hide();
            return true;
        }
        // faile : show 5 san pham kia ra : 
        if (Object.keys(list_product_backup).length > 0) {
            let data = new Array();
            data.push(list_product_backup[type_id]);
            $this.onRenderProducts(data, type_id)
        }
    }
    
    this.getOnlyDataTypeId = function(only_type_id){
        let link = $this.onButtonHrefTypeEvent(only_type_id,0)
        let containerProductHtml = $this.onContainerProductHtml(only_type_id,'block',link,true)
        // container cua content tabs :
        $jq("#fhs_tabslider3_pro_" + current_block_id).empty();
        $jq("#fhs_tabslider3_pro_" + current_block_id).append(containerProductHtml);

        // ----- 
        is_tabs_load_success = true;
        $this.onRenderHtmlSuccess(only_type_id)
        $this.onSwiperTab(current_block_id);
        $this.getDataProduct(only_type_id);
    }
    
    this.onButtonHrefTypeEvent = function (type_id, seriesId = 0) {
        let url = languages['baseUrl'];
        let seeAllLink = '';
        if (type_id == 3 && seriesId != 0) {
            seeAllLink = url + 'seriesbook/index/series/id/' + seriesId + languages['fhs_campaign_series']
            return seeAllLink;
        }
        let prodCat3Id = cate_array['cate_id'];
        let prodCat2Id = cate_array['mid_id'];
        let prodCat1Id = cate_array['main_id'];
        
        if (type_id == 0) {
            if (prodCat3Id == 266) {
                seeAllLink = url + "balo?fhs_campaign=SEEALL_PRODREC";
            } else if (prodCat3Id == 6309) {
                seeAllLink = url + "tap?fhs_campaign=SEEALL_PRODREC";
            } else if (prodCat2Id == 279) {
                seeAllLink = url + "but?fhs_campaign=SEEALL_PRODREC";
            } else if (prodCat3Id == 268) {
                seeAllLink = url + "hop-but?fhs_campaign=SEEALL_PRODREC";
            } else if (prodCat2Id == 6365) {
                seeAllLink = url + "board-game?fhs_campaign=SEEALL_PRODREC";
            } else if (prodCat1Id == 5991) {
                seeAllLink = url + "cua-tiem-giac-mo-do-choi?fhs_campaign=SEEALL_PRODREC";
            } else if (prodCat2Id == 11) {
                seeAllLink = url + "sach-kinh-te-mua-manh-giam-bao?fhs_campaign=SEEALL_PRODREC";
            }
        }
        

        return seeAllLink;
    }

    this.onSwipe = function (type_id) {
        let w = window.innerWidth || document.body.clientWidth;
        let $right = $jq(".fhs_tab3_" + type_id + "_next");
        let $left = $jq(".fhs_tab3_" + type_id + "_prev");
        list_swiper_product[type_id] = new Swiper('#fhs_tabslider3_tab_' + current_block_id +' #fhs_tabslider3_products_tabs_'+type_id, {
            // Default parameters
            slidesPerView: (w <= width_reponsive_siwper) ? "auto" : 5,
            slidesPerGroup: (w <= width_reponsive_siwper) ? 1 : 5,
            freeMode: (w <= width_reponsive_siwper) ? true : false,
//            direction: 'horizontal',
            navigation: {
                nextEl: ".fhs_tab3_" + type_id + "_next",
                prevEl: ".fhs_tab3_" + type_id + "_prev",
            },
            on: {
                slideChange: function () {
                    // on the first slide
                    let demSo = list_swiper_product[type_id].activeIndex + 4; // + 5 vi 1 slide co  5 item
                    if (list_swiper_product[type_id].activeIndex == 0) {
                        $right.show();
                        $left.hide();
                    }
                    // most right postion
                    else if (demSo == list_swiper_product[type_id].slides.length - 1) {
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
            // Responsive breakpoints
            breakpoints: {
                640: {
                    slidesPerView: "auto",
//                    spaceBetween: 10,
                },
                992: {
                    slidesPerView: "auto",
//                    spaceBetween: 10,
                },
                1024: {
                    slidesPerView: 5,
//                    spaceBetween: 10,
                },
                1440: {
                    slidesPerView: 5,
//                    spaceBetween: 10,
                },
                2560: {
                    slidesPerView: 5,
//                    spaceBetween: 10,
                },
            }
        });
        swipe_loaded_list['swipe_'+ type_id] = true;
    }
    
    this.onSwiperTab = function (current_block_id) {
        let w = window.innerWidth || document.body.clientWidth;
        new Swiper('#fhs_tabslider3_tabs_content_'+current_block_id + ' .fhs_tabslider3_tabs_swiper_container' , {
            slidesPerView: 'auto',
            allowTouchMove: (w <= 1230) ? true : false
        });
    }
    
    this.onActiveTab = function (tab_id,click_tab){
        $jq('#fhs_tabslider3_ul_' + current_block_id + ' li').removeClass(class_active_tabs);
            click_tab.addClass(class_active_tabs);
            $this.onShowHideProductTab('hide', curr_type_id_active);
            $this.onShowHidebuttonPreNext('hide', curr_type_id_active);
            $this.onShowHideButtonMore('hide', curr_type_id_active);
            $this.onShowHideLoadingBlock('show', tab_id);

            curr_type_id_active = tab_id;

            /// if we already load the tab, we just need to show it.
            if (tab_loaded_list['tab' + tab_id]) {
                $this.onShowHideLoadingBlock('hide', tab_id);
                $this.onShowHidebuttonPreNext('show', tab_id);
                $this.onShowHideProductTab('show', tab_id);
                $this.onShowHideButtonMore('show', tab_id);
                if (Object.keys(swipe_loaded_list).length > 0 && swipe_loaded_list.hasOwnProperty('swipe_' + tab_id) && swipe_loaded_list['swipe_' + tab_id] === false)
                {
                    $this.onSwipe(tab_id);
                }
            } else {
                tab_loaded_list['tab' + tab_id] = true;
                $this.getDataProduct(tab_id);
            }
    }
}
