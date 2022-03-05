
const FpointStore = function () {
    const MAX_DISPLAY_PERIODS = 4;
    const GIFTS_PER_ROW = 4;
    const PERIOD_GIFTS_ID_KEY = "fpointstore-period-gifts-";
    const MAX_DISPLAY_PERIODS_MOBILE_PAGE = 8;
    const CHAY_HANG_ICON_URL = "frontend/ma_vanese/fahasa/images/fpointstore/Chay-hang-icon.png";
    const FS_GIFT_URL = "/node_api/fpointstore/gift";
    const QUEUE_TIME_LOOP = 3000;
    const QUEUE_TIME_LIMIT_TO_TRY = 5;
    
    var $this = this;
    var is_mobile = false;
    var countdown_timer;
    var countdown_btn_enable = false;
    
    /// Slider Variables
    $fpointstore_slider = $jq("#fpointstore-slider");

    /// Page Variables
    var next_gifts_url;
    var $fpointstore_page = $jq("#fpointstore-page");
    var period_active = 0;
    var $period_list = $jq("#fpointstore-page .fpointstore-page-periods");
    var $period_content = $jq(".fpointstore-page-period-content");
    var $page_loading_icon = $jq(".fpointstore-page-loading-icon");
    var $page_countdown_label = $jq(".fpointstore-page-countdown-label");
    var $page_bottom = $jq(".fpointstore-page-bottom");

    var $current_period_tab;
    var $current_gifts_content;
    var current_period_id;
    var periods_data = {};
    var gift_data = [];
    var text_labels = {};
    var gift_queue_id = 0;
    var time_loaded = 0;
    /// Product page variable
    var $fpointstore_gift_page = $jq("#fpointstore-gift")

    /// Mobile Page
    var $fpointstore_page_mobile = $jq("#fpointstore-page-mobile");
    var $mobile_period_list = $jq("#fpointstore-page-mobile .fpointstore-page-periods .swiper-wrapper");
    var base_skin_url;
    
    this.initSlider = function (url, _text_labels, BASE_SKIN_URL) {
        base_skin_url = BASE_SKIN_URL;
        $jq.ajax({
            url: url,
            method: 'post',
            success: function (data) {
                if (!data.result) {
                    return;
                }
		
                text_labels = _text_labels;
                $fpointstore_slider.show();
                period = data['period'];
		current_period_id = period.period_id;
                if (period) {
                    period.is_active = parseInt(period.is_active?period.is_active:0);
                    if (period.is_active) {
			period_active = parseInt(period.period_id);
                        $this.startCountdown($fpointstore_slider, period['end_date']);
                    } else {
			countdown_btn_enable = true;
                        $this.startCountdown($fpointstore_slider, period['start_date']);
                    }

                    $countdown_label = $jq("#fpointstore-slider .fpointstore-countdown-label");

                    period.ready_time = parseInt(period.ready_time);
                    if (period.ready_time) {
                        $countdown_label.show();
                        $countdown_label.text(text_labels['sap_mo']);
                    } else {
                        $countdown_label.hide();
                    }
                }

                /// List all gifts
                $fpointstore_list = $jq("#fpointstore-slider .fpointstore-list");
                $fpointstore_list.empty();

                gifts = data['gifts'];
                var count = gifts.length;
		gift_data = gifts;
                for (i = 0; i < count; i++) {
		    var gift_data_json = JSON.parse(gift_data[i]);
                    $gift = $jq("<li id='gift-item-"+gift_data_json.gift_id+"-"+gift_data_json.period_id+"' class='item fpointstore-item'>"+$this.addItemGiftHtml(gift_data_json)+"</li>");
                    $fpointstore_list.append($gift);
                }
                $fpointstore_list.bxSlider(
                        {
                            pause: 0,
                            minSlides: 1,
                            maxSlides: 4,
                            slideWidth: 290,
                            slideMargin: 10,
                            infiniteLoop: false,
                            touchEnabled: false,
                            hideControlOnEnd: true,
                            preloadimages: 'visible',
                            onSlideAfter: function (slide, oldIndex, newIndex) {
                                //.var currentS = $fpointstore_list;
                                //loadimages(currentS);
                            }
                        }
                );
            }
        });
    };
    this.initMobileSlider = function (url, _text_labels, BASE_SKIN_URL) {
        base_skin_url = BASE_SKIN_URL;
        
        $jq.ajax({
            url: url,
            method: 'post',
            success: function (data) {
                if (!data.result) {
                    return;
                }

                text_labels = _text_labels;
                period = data['period'];
		current_period_id = period.period_id;
                if (period) {
                    period.is_active = parseInt(period.is_active);
                    if (period.is_active) {
			period_active = parseInt(period.period_id);
                        $this.startCountdown($fpointstore_slider, period['end_date']);
                    } else {
			countdown_btn_enable = true;
                        $this.startCountdown($fpointstore_slider, period['start_date']);
                    }
                }

                /// List all gifts
                $fpointstore_list = $jq("#fpointstore-slider .fpointstore-list");
                $fpointstore_list.empty();
		
                gifts = data['gifts'];
		gift_data = gifts;
                var count = gifts.length;
                for (i = 0; i < count; i++) {
		    var gift_data_json = JSON.parse(gift_data[i]);
                    $gift = "<div id='gift-item-"+gift_data_json.gift_id+"-"+gift_data_json.period_id
			    +"' class='swiper-slide fpointstore-item'>"+$this.addItemGiftHtml(gift_data_json)
			    +"</div>";
                    $fpointstore_list.append($gift);
                }

                $fpointstore_slider.show();

                var fpointstore_swiper = new Swiper('.swiper-container-fpointstore', {
                    direction: 'horizontal',
		    minSlides: 1,
		    maxSlides: 2,
		    pagination: {
			el: '.swiper-pagination',
		    },
                });
            }
        });
    };
    
    this.initPage = function (page_url, get_next_url, _text_labels, BASE_SKIN_URL) {
        next_gifts_url = get_next_url;
        base_skin_url = BASE_SKIN_URL;
        
        $jq.ajax({
            url: page_url,
            method: 'post',
            success: function (data) {
                if (!data.result) {
                    console.log("Error: " + data.error_type);
                    $error_panel = $jq("#fpointstore-error");
                    if (data.error_type == "no_connection") {
                        /// display error msg
                    } else {
                        $error_panel.find(".fpointstore-error-msg").text(_text_labels['error']);
                    }

                    $error_panel.show();
                    $fpointstore_page.hide();
                    return;
                }

                text_labels = _text_labels;
                /// List all gifts
                var active_period = data['period'];
                current_period_id = active_period.period_id;
                var periods = data['fpointstore']['periods'];
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
                        $period.addClass("fpointstore-page-period-active");
                        $current_period_tab = $period;
                    }

                    $period.find("a").click($this.periodTabClicked);
                    $period_list.append($period);
                    blank_period_html_count--;
                    added_period_count++;

                    /// add period_content ( div tag that content gifts )
                    var $period_gifts_html = $jq("<div id='" + PERIOD_GIFTS_ID_KEY + periods[i].period_id + "' class='fpointstore-page-gifts'>");
                    $period_content.append($period_gifts_html);
                }

                for (i = 0; i < blank_period_html_count; i++) {
                    $period_list.append($jq("<div class='col-md-5ths col-xs-6 fpointstore-page-period'></div>"));
                }

                /// Start Period Countdown
                active_period.is_active = parseInt(active_period.is_active);

                if (active_period.is_active) {
		    period_active = parseInt(active_period.period_id);
                    $page_countdown_label.text(text_labels['ket_thuc']);
                    $this.startCountdown($fpointstore_page, active_period['end_date']);
                } else {
		    countdown_btn_enable = true;
                    $page_countdown_label.text(text_labels['bat_dau']);
                    $this.startCountdown($fpointstore_page, active_period['start_date']);
                }

                /// Load Active Period Products
                $active_period_gifts = $jq("#" + PERIOD_GIFTS_ID_KEY + data['period'].period_id);
                $current_gifts_content = $active_period_gifts;
                $current_gifts_content.show();
                $this.addGiftsToPage(data['gifts'], $active_period_gifts);

                /// Initialize periods data
                periods_data = {};
                for (i = 0; i < periods_count; i++) {
                    periods_data[periods[i].period_id] = {
                        is_loading: false,
                        is_fully_loaded: false,
                        current_page: 0,
                        end_date: periods[i].end_date,
                        start_date: periods[i].start_date,
                        is_active: parseInt(periods[i].is_active)
                    }
                }
                /// Start checking viewport
                $jq(window).ready(function () {
                    $this.checkFpointStoreBottom();
                    var to_check_bottom = true;
                    $jq(window).on('resize scroll', function () {
                        $this.checkFpointStoreBottom();
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
        next_gifts_url = get_next_url;
        is_mobile = true;
        base_skin_url = BASE_SKIN_URL;
        
        $jq.ajax({
            url: page_url,
            method: 'post',
            success: function (data) {
                if (!data.result) {
                    console.log("Error: " + data.error_type);
                    $error_panel = $jq("#fpointstore-error");
                    if (data.error_type == "no_connection") {
                        /// display error msg
                    } else {
                        $error_panel.find(".fpointstore-error-msg").text(_text_labels['error']);
                    }

                    $error_panel.show();
                    $fpointstore_page_mobile.hide();
                    return;
                }

                text_labels = _text_labels;
                /// List all gifts
                var active_period = data['period'];
                current_period_id = active_period.period_id;
                var periods = data['fpointstore']['periods'];
                var periods_count = periods.length;

                var added_period_count = 0;
                $mobile_period_list.empty();
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
                        $period.addClass("fpointstore-page-period-active");
                        $current_period_tab = $period;
                    }

                    $period.find(".fpointstore-page-period-item").click($this.periodTabClicked);
                    $mobile_period_list.append($period);
                    added_period_count++;

                    /// add period_content ( div tag that content gifts )
                    var $period_gifts_html = $jq("<div id='" + PERIOD_GIFTS_ID_KEY + periods[i].period_id + "' class='fpointstore-page-gifts'>");
                    $period_content.append($period_gifts_html);
                }

                var swiper = new Swiper('.fpointstore-page-periods', {
                    direction: 'horizontal',
                    slidesPerView: 'auto',
                    freeMode: true,
                    longSwipesMs: 800
                });

                /// Start Period Countdown
                active_period.is_active = parseInt(active_period.is_active);
                if (active_period.is_active) {
		    period_active = parseInt(active_period.period_id);
                    $page_countdown_label.text(text_labels['ket_thuc']);
                    $this.startCountdown($fpointstore_page_mobile, active_period['end_date']);
                } else {
		    countdown_btn_enable = true;
                    $page_countdown_label.text(text_labels['bat_dau']);
                    $this.startCountdown($fpointstore_page_mobile, active_period['start_date']);
                }

                /// Load Active Period Products
                $active_period_gifts = $jq("#" + PERIOD_GIFTS_ID_KEY + data['period'].period_id);
                $current_gifts_content = $active_period_gifts;
                $current_gifts_content.show();
                $this.addGiftsToPage(data['gifts'], $active_period_gifts);

                /// Initialize periods data
                periods_data = {};
                for (i = 0; i < periods_count; i++) {
                    periods_data[periods[i].period_id] = {
                        is_loading: false,
                        is_fully_loaded: false,
                        current_page: 0,
                        end_date: periods[i].end_date,
                        start_date: periods[i].start_date,
                        is_active: parseInt(periods[i].is_active)
                    }
                }

                /// Start checking viewport
                $jq(window).ready(function () {
                    $this.checkFpointStoreBottom();
                    $jq(window).on('resize scroll', function () {
                        $this.checkFpointStoreBottom();
                    });

                    /// Periods menu affix
                    var $period_menu = $jq('#fpointstore-page-mobile-periods');
                    $period_menu.affix({offset: {top: $period_menu.offset().top}});
                });
            }
        });
    }
    
    
    /*
     *  This function only apply for single gift
     */
    this.initProductPage = function (gift, period, is_mobile, _text_labels) {
        $price_element = $jq("#catalog-gift-details-price");
        $discount_element = $jq("#catalog-gift-details-discount");

        if (!gift) {
            $price_element.show();
            $discount_element.show();
            return;
        }

        gift.total_sold = parseInt(gift.total_sold);
        gift.total_items = parseInt(gift.total_items);
        if (gift.total_sold >= gift.total_items) {
            
            $price_element.show();
            return;
        }

        /// Start Period Countdown
        if (period) {
            $this.startCountdown($fpointstore_gift_page, period['end_date']);
        }

        $jq("#fpointstore-gift .fpointstore-countdown-label").html(_text_labels["ket_thuc"]);
        $fpointstore_gift_page.show();
    }

    this.getNextProducts = function (period_id, page) {
        var $period_gifts = $jq("#" + PERIOD_GIFTS_ID_KEY + period_id);
        var children_count = $period_gifts.children().size();
        if (children_count > 0 && page == 0) {
            /// don't load when there are children, and page is 0
            ///TODO: - need to find error of reason why list of gift  is duplicated
        } else {
            periods_data[period_id].is_loading = true;
            $page_loading_icon.show();
            //console.log("Load Product: " + period_id + " - " + page);

            $jq.ajax({
                url: next_gifts_url,
                method: 'post',
                data: {
                    period_id: period_id,
                    page: page
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    setTimeout(function () {
                        periods_data[period_id].is_loading = false;
                        $page_loading_icon.hide();
                    }, 1500);
                },
                success: function (data) {
                    $page_loading_icon.hide();

                    if (!data.result) {
                        return;
                    }
                    periods_data[period_id].is_loading = false;
                    periods_data[period_id].current_page = page;

                    $this.addGiftsToPage(data['gifts'], $period_gifts);

                    if ((data.gifts_per_page * periods_data[period_id].current_page) >= data.gift_count) {
                        periods_data[period_id].is_fully_loaded = true;
                    }
                    ///console.log("Loaded---------------");
                }
            });
        }
    }

    this.addGiftsToPage = function (gifts, $list) {
        /// List all gifts
	
        var count = gifts.length, $current_row;
	Array.prototype.push.apply(gift_data, gifts);
        for (i = 0; i < count; i++) {
            if (i % GIFTS_PER_ROW == 0) {
                $current_row = $jq("<div class='row'></div>");
                $list.append($current_row);
            }
	    var gift_data_json = JSON.parse(gifts[i]);
            
            $gift = $jq("<div class='fs-layout-item col-md-3 col-sm-6 col-xs-12' style='margin-bottom:10px;'><div id='gift-item-"+gift_data_json.gift_id+"-"+gift_data_json.period_id+"' class='fpointstore-item'>"
		    +$this.addItemGiftHtml(gift_data_json)
		    +"</div>");
            $current_row.append($gift);
        }
    }

    this.addPagePeriodHtml = function (period) {
        countdown_btn_enable = false;
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
        period.ready_time = parseInt(period.ready_time);

        if (start_date.getDate() - 1 > now_date.getDate()) {
            //period_status = start_date.getDate() + "/" + (start_date.getMonth() + 1);
            period_status = period.start_date_label;
        } else if (start_date.getDate() > now_date.getDate()) {
            period_status = text_labels['ngay_mai'];
        } else if (period.is_active) {
            period_status = text_labels['dang_ban'];
        } else if (period.ready_time) {
            period_status = text_labels['sap_ban'];
        }

        period_html = "<div class='col-md-3 col-xs-12 fpointstore-page-period'><a class='fpointstore-page-period-label ' data-id='"
                + period.period_id + "'><span>"
                + period_start_time
                + "</span><span><div></div></span><span>"
                + period_status
                + "</span><span></span></a></div>";

        return $jq(period_html);
    }
    
    this.addMobilePagePeriodHtml = function (period) {
        countdown_btn_enable = false;
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

        period_html = "<div class='swiper-slide'><div class='fpointstore-page-period-item' data-id='"
                + period.period_id + "'><div class='fpointstore-page-period-time'>"
                + period.start_time_label + "</div><div class='fpointstore-page-period-status'>"
                + period_status + "</div></div></div>";

        return $jq(period_html);
    }

    this.addItemGiftHtml = function (gift) {
        if (!gift) {
            return;
        }
	
        var gift_short_name = gift.name;
        if (gift.name) {
            gift_short_name = Helper.shortenGiftName(gift.name);
        }
	
        var img_sold_out = "";
        var btn_status = "";
        var btn_text = text_labels['doi_qua'];
        var detail_func = "onclick='openGiftDetail("+gift.gift_id+","+gift.period_id+")'";
        gift.quatity_total = parseInt(gift.quatity_total);
        gift.quatity_used = parseInt(gift.quatity_used);

        if (gift.quatity_used >= gift.quatity_total) {
            img_sold_out = "<div style='position: absolute;background: #261E1E none repeat scroll 0% 0%;opacity: 0.5;z-index: 2; width:100%;height: 100%;top: 0;'></div><img src='" + base_skin_url + CHAY_HANG_ICON_URL + 
	    "'style='position: absolute;top: 50%;left: 50%;-ms-transform: translate(-50%, -50%);transform: translate(-50%, -50%);"
	    +"width: auto;height: 60%; z-index: 3;'/>";
	    btn_status = 'disabled';
	    btn_text = text_labels['het_qua'];
        }
	if(gift.period_id != period_active){
	    btn_status = 'disabled';
	}
	gift_partner_label = "";
	if(gift.partner){
	    gift_partner_label = "<div class='fpointstore-parner'><span>"+text_labels['partner']+"</span><span></span></div>"
	}
	
        var progress_bar_sold_percent = (gift.quatity_used / gift.quatity_total) * 100;

        var item = 
	///add image
		"<div class='item-inner' style='position: relative'><div class='gift images-container'>"
		+"<a href='#.' title='"+ gift.name+ "' "+detail_func+"  class='gift-image'>"
		+"<div style='position: relative;'>"
                + "<img class='fpointstore-item-image' src='"+ gift.image + "' data-src='"
		    + gift.image + "' width='290px' height='134px' class='flazy' alt='"
		    + gift.name + "' />"
		    + img_sold_out
		    + gift_partner_label
                + "</div></a></div>"
	///add content
		+"<div class='ma-box-content'>"
		    +"<div  style='padding:0 10px;'>"
		    /// Name
		    + "<h2 class='gift-name'>"
			+"<a href='#.' "+detail_func+" title='"
			+ gift.name + "'>"
			+ gift_short_name +" - Giảm "+gift.discount
			+"</a></h2>"
		    /// percent
		    + "<div class='fpointstore-percent'>" + Math.round(progress_bar_sold_percent) + "% đã đổi</div>"
			/// Progress bar
		    + "<div class='progress' ><div class='progress-bar' role='progressbar' "
			+"style='width: " + progress_bar_sold_percent + "%;' aria-valuenow='"
			+ progress_bar_sold_percent + "' aria-valuemin='0' aria-valuemax='100'>"
			+ "</div>"
		    +"</div>"
		    + "<div style='height:30px;margin:6px 0 0 0;position: relative'>"
			+ "<div class='fpointstore-fpoint'>" + gift.fpoint_str + " F-point</div>"
			+"<div class='fpointstore-btnconfirm'><button "+detail_func+" "+btn_status+" class='btn-gift confirm btn-gift-"+gift.period_id+"'><span>"+btn_text+"</span></button></div>"
		    +"</div><div style='clear:both;'></div>"
                + "</div></div>";

        return item;
    }
    
    this.getItemGiftHtml = function () {
        if (!gift_data) {
            return false;
        }
        for (i = 0; i < gift_data.length; i++) {
	    let gift = JSON.parse(gift_data[i]);
	    if(gift.gift_id == current_gift_id && gift.period_id == current_gift_period_id){
		var gift_short_name = gift.name;
		if (gift.name) {
		    gift_short_name = Helper.shortenGiftName(gift.name);
		}

		var expire_date = new Date(gift.expire_date.replace(/-/g, '/'));
		var img_sold_out = "";
		var btn_status = "";
		var btn_text = text_labels['confirm'];
		var detail_func = "onclick='changeGift("+gift.gift_id+","+gift.period_id+")'";
		gift.quatity_total = parseInt(gift.quatity_total);
		gift.quatity_used = parseInt(gift.quatity_used);

		if (gift.quatity_used >= gift.quatity_total) {
		    img_sold_out = "<div style='position: absolute;background: #261E1E none repeat scroll 0% 0%;opacity: 0.5;z-index: 2; width:100%; height: 100%;top: 0;'></div><img src='" + base_skin_url + CHAY_HANG_ICON_URL + 
		    "'style='position: absolute;top: 50%;left: 50%;-ms-transform: translate(-50%, -50%);transform: translate(-50%, -50%);"
		    +"width: auto;height: 60%; z-index: 3;'/>";
		    btn_status = 'disabled';
		    btn_text = text_labels['het_qua'];
		}
		if(gift.period_id != period_active){
		    btn_status = 'disabled';
		}

		gift_partner_label = "";
		if(gift.partner){
		    gift_partner_label = "<div class='fpointstore-parner'><span>"+text_labels['partner']+"</span><span></span></div>"
		}
		item = 
		///add image
			"<div class='fpointstore-item'>"
			    +"<div class='gift images-container'>"
				+"<div style='position: relative;'>"
				+ "<img class='fpointstore-item-image' src='"+ gift.image + "' data-src='"
				    + gift.image + "' width='340px' height='157px' class='flazy' alt='"
				    + gift.name + "' />"
				    + img_sold_out
				    + gift_partner_label
				+ "</div>"
			    +"</div>"
		///add content
			    +"<div class='ma-box-content'>"
				+"<div style='padding:0 10px;'>"
				/// Name
				    + "<h2 class='gift-name' style='line-height: 1.5em; height:auto !important'>"+gift_short_name +" - Giảm "+gift.discount +"</h2>"
				    /// Fpoint
				    +"<div style='font-size: 1.15em;color:#F39801; font-family: sans-serif; font-weight: 600; padding: 5px 0;'>" + gift.fpoint_str + " F-point</div>"
				    +"<div style='font-size: 1em;font-family: sans-serif; padding-bottom: 5px;'>Giá trị đơn hàng tối thiểu cho voucher: "+gift.order_limit+"</div>"
				    +"<div style='font-size: 1em;font-family: sans-serif; padding-bottom: 5px;'>Hạn sử dụng voucher: "+getFormattedDate(expire_date)+"</div>"
				    +"<div style='font-size: 1em;font-family: sans-serif; padding-bottom: 5px;'>*Không quy đổi ngược lại ra F-point/tiền mặt: <a style='color:#F39801;' href='/fpointstore-the-le'><u>thể lệ</u></a></div>"
				    +"<div style='height:45px;margin:6px 0 0 0;position: relative'>"
					+"<div class='fpointstore-btn'>"
					    +"<button onclick='closeGiftDetail();' class='btn-gift'><span>"+text_labels['cancel']+"</span></button>"
					    +"<button "+detail_func+" "+btn_status+" class='btn-gift confirm  btn-gift-"+gift.period_id+"'><span>"+btn_text+"</span></button>"
					+"</div>"
				    +"</div>"
				+"<div style='clear:both;'></div>"
				+ "</div>"
			    + "</div>"
			+"</div>";
		$jq('#popup-fpointstore-detail-content').html(item);
	    }
	}
	
	popup_open = "GiftDetail";
	return true;
    }
    
    this.startCountdown = function ($parent, future_date_time) {
        /// fpointstore-countdown-number
        $countdown = $parent.find(".fpointstore-countdown .fpointstore-countdown-number");
        $hours = $countdown.eq(0);
        $minutes = $countdown.eq(1);
        $seconds = $countdown.eq(2);
        
        if(countdown_timer){
            clearInterval(countdown_timer);
        }
        
        $this.calculateTime(future_date_time, $hours, $minutes, $seconds);
        
        countdown_timer = setInterval(function(){
            $this.calculateTime(future_date_time, $hours, $minutes, $seconds);
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
	if($hours.text() == "00" && $minutes.text() == "00" && $seconds.text() == "00"){
	    if(countdown_btn_enable){
		$jq('.btn-gift-'+current_period_id).prop("disabled", false);
		countdown_btn_enable = false;
	    }
	}
    }
    
    this.periodTabClicked = function (event) {
        if ($current_period_tab) {
            $current_period_tab.removeClass("fpointstore-page-period-active");
        }

        $current_period_tab = $jq(this).parent();
        $current_period_tab.addClass("fpointstore-page-period-active");

        //href = $jq(this).attr("data-id");
        //id = href.split("#")[1];
        id = $jq(this).attr("data-id");
        $active_period_gifts = $period_content.find("#" + PERIOD_GIFTS_ID_KEY + id);
        if ($current_gifts_content) {
            $current_gifts_content.hide();
        }
        $current_gifts_content = $active_period_gifts;
        $current_gifts_content.show();

        current_period_id = id;
        var _period_data = periods_data[current_period_id];
        /// First time loading period gifts
        if (_period_data.current_page == 0) {
            $this.getNextProducts(current_period_id, _period_data.current_page);
        }
	else{
	    gift_data = _period_data
	}
	
        //$this.startCountdown($fpointstore_page, _period_data['end_date']);
        if (_period_data.is_active) {
            $page_countdown_label.text(text_labels['ket_thuc']);
            if (is_mobile) {
                $this.startCountdown($fpointstore_page_mobile, _period_data['end_date']);
            } else {
                $this.startCountdown($fpointstore_page, _period_data['end_date']);
            }
        } else {
	    countdown_btn_enable = true;
            $page_countdown_label.text(text_labels['bat_dau']);
            if (is_mobile) {
                $this.startCountdown($fpointstore_page_mobile, _period_data['start_date']);
            } else {
                $this.startCountdown($fpointstore_page, _period_data['start_date']);
            }
        }

        event.stopPropagation();
    }

    this.checkFpointStoreBottom = function () {
        //console.log("TO CHECK");
        var _period_data = periods_data[current_period_id];
        if (_period_data
                && !_period_data.is_fully_loaded
                && !_period_data.is_loading
                && Helper.isElementInViewport($page_bottom)) {

            $this.getNextProducts(current_period_id, _period_data.current_page + 1);
        }
    }
    
    this.changeGift = function (gift_id, period_id) {
	if(gift_queue_id){
	    return;
	}
	popup_open = "";
	new Ajax.Request(
	FPOINTSTORE_CHANGE_URL, {
	    method: 'post',
	    parameters: { gift_id: gift_id,period_id: period_id},
	    onLoading: function () {
		showLoadingAnimation();
	    },
	    onFailure: function () {
		hideLoadingAnimation();
	    },
	    onSuccess: function (result) {
		hideLoadingAnimation();
		fpoint_store_response = JSON.parse(result.responseText);
		if(fpoint_store_response.success){
		    gift_queue_id = fpoint_store_response.gift_queue_id;
		    time_loaded = 0;
		    $jq('#popup-fpointstore-detail').fadeOut();
		    $jq('#popup-fpointstore-loading-context-text').html(text_labels['processing']+"...");
		    $jq('#popup-fpointstore-loading-confirm').hide();
		    $jq('#popup-fpointstore-loading-logo').hide();
		    $jq('#popup-fpointstore-loading-icon').show();
		    $jq('#popup-fpointstore-loading').fadeIn();
		    popup_open = "";
		    setTimeout(function(){$this.getResultQueue();}, 100);
		}else{
		    $jq('#popup-fahasa-alert-logo').html("<center><img src='"+FS_BASE_SKIN_URL+"frontend/ma_vanese/fahasa/images/logo-alert-fail.png') /></center>");
		    $jq('#popup-fahasa-default-content-text').html(fpoint_store_response.message);
		    $jq('#popup-fpointstore-detail').fadeOut();
		    $jq('#popup-fpointstore-alert').fadeIn();
		    popup_open = "FpointAlert";
		}
	    }
	});
    }
    this.getResultQueue = function () {
	new Ajax.Request(
	FPOINTSTORE_QUEUE_URL, {
	    method: 'post',
	    parameters: { gift_queue_id: gift_queue_id},
	    onSuccess: function (result) {
		fpoint_store_response = JSON.parse(result.responseText);
		time_loaded++;
		console.log("time to try="+time_loaded+",status="+fpoint_store_response.status);
		if(fpoint_store_response.status){
		    if(fpoint_store_response.success){
			$jq('#popup-fpointstore-loading').fadeOut();
			$jq('#popup-fahasa-default-content-text').html(fpoint_store_response.message);
			$jq('#popup-fahasa-alert-logo').html("<center><img src='"+FS_BASE_SKIN_URL+"frontend/ma_vanese/fahasa/images/logo-alert-success.png') /></center>");
			$jq('#popup-fpointstore-alert').fadeIn();
		    }else{
			$jq('#popup-fpointstore-loading').fadeOut();
			$jq('#popup-fahasa-alert-logo').html("<center><img src='"+FS_BASE_SKIN_URL+"frontend/ma_vanese/fahasa/images/logo-alert-fail.png') /></center>");
			$jq('#popup-fahasa-default-content-text').html(fpoint_store_response.message);
			$jq('#popup-fpointstore-alert').fadeIn();
		    }
		    gift_queue_id = 0;
		    popup_open = "FpointAlert";
		}else{
		    if(time_loaded < QUEUE_TIME_LIMIT_TO_TRY){
			setTimeout(function(){$this.getResultQueue();}, 3000);
		    }else{
			time_loaded = 0;
			$jq('#popup-fpointstore-loading-icon').hide();
			$jq('#popup-fpointstore-loading-logo').show();
			$jq('#popup-fpointstore-loading-context-text').html(text_labels['timeout']);
			$jq('#popup-fpointstore-loading-confirm').show();
			console.log('Exit load queue!');
		    }
		}
	    },
	    onFailure: function () {
		time_loaded++;
		console.log("time to try="+time_loaded+", loading failured.");
		if(time_loaded < QUEUE_TIME_LIMIT_TO_TRY){
		    setTimeout(function(){$this.getResultQueue();}, 3000);
		}else{
		    time_loaded = 0;
		    $jq('#popup-fpointstore-loading-icon').hide();
		    $jq('#popup-fpointstore-loading-logo').show();
		    $jq('#popup-fpointstore-loading-context-text').html(text_labels['timeout']);
		    $jq('#popup-fpointstore-loading-confirm').show();
		    console.log('Exit load queue!');
		}
	    }
	});
    }
    
    this.reloadGift = function(){
	$jq.ajax({
            url: FS_GIFT_URL,
            method: 'post',
	    data: { gift_id: current_gift_id },
            success: function (data) {
                if (!data.result) {
                    return;
                }
		if(!data['gift']){
		    return;
		}
                var gift_new = data.gift;
		var gift_html = $this.addItemGiftHtml(gift_new);
		if(gift_html){
		    var item = "#gift-item-"+gift_new.gift_id+"-"+gift_new.period_id;
		    var $current_item = $jq(item);
		    $current_item.html(gift_html);
		}

		for (i = 0; i < gift_data.length; i++) {
		    let gift = JSON.parse(gift_data[i]);
		    if(gift.gift_id == gift_new.gift_id && gift.period_id == gift_new.period_id){
			gift.quatity_total = gift_new.quatity_total;
			gift.quatity_used =  gift_new.quatity_used;
		    }
		}
	    }
        });
    }
}

function hideLoadingAnimation() {
    $jq('.loadding_ajaxcart,#wraper_ajax,.wrapper_box').remove();
}

function showLoadingAnimation(){
    var loading_bg = $jq('#ajaxconfig_info button').attr('name');
    var opacity = $jq('#ajaxconfig_info button').attr('value');
    var loading_image = $jq('#ajaxconfig_info img').attr('src');
    var style_wrapper =  "position: fixed;top:0;left:0;filter: alpha(opacity=70); z-index:99999;background-color:"+loading_bg+"; width:100%;height:100%;opacity:"+opacity+"";
    var loading = '<div id ="wraper_ajax" style ="'+style_wrapper+'" ><div  class ="loadding_ajaxcart" style ="z-index:999999;position:fixed; top:50%; left:50%;"><img src="'+loading_image+'"/></div></div>';
    if($jq('#wraper_ajax').length==0) {
	$jq('body').append(loading);
    }
}

function getFormattedDate(date) {
    let year = date.getFullYear();
    let month = (1 + date.getMonth()).toString().padStart(2, '0');
    let day = date.getDate().toString().padStart(2, '0');
  
    return day + '/' + month + '/' + year;
}

$jq(document).keydown(function(e){
    var code = e.keyCode || e.which;
    if(code === 27) {
	switch(popup_open){
	    case "GiftDetail":
		closeGiftDetail();
	    break;
	    case "FpointAlert":
		closeFpointAlert()
	    break;
	}
    }
});

function sleep(milliseconds) {
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  }
}