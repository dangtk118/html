var EventCartProView = function () {
    var $this = this;
    var dataContent;
    var languages = null;
    
    
    this.init = function (_languages, _dataContent) {
        dataContent = _dataContent;
        languages = _languages;

        let w = window.innerWidth || document.body.clientWidth;
        new Swiper('.event_cart_2_tabs_swiper_container', {
            slidesPerView: 'auto',
            allowTouchMove: (w <= 1230) ? true : false
        });
        $this.setSwiperSlider();

        // su kien click tab khac
        $jq(".event_cart_2_tabs li.event_cart_2_tabs_item").click(function (e) {
            let click_tab_slider = $jq(this);
            let click_tab_id = click_tab_slider.attr("ref");
	    $jq(".event_cart_2_tabs li.event_cart_2_tabs_item").removeClass('active');
            //find active isset
            $jq(".event_cart_2_tabs li.event_cart_2_tabs_item").each(function () {
                if ($jq(this).hasClass('event_cart_2_tabs_active')) {
                    $jq(this).removeClass('event_cart_2_tabs_active');
                    $jq(".evt_cart_2_slide_ite_last_mobile").removeClass('visible_btn_mobile');
//                    $jq("#button_more_"+click_tab_id).removeClass('event_cart_2_tabs_active');
                    return true;
                }
            });
            $this.setChangeTabAndContentSlider(click_tab_slider, click_tab_id)
        });
        
        // ---handle event popup cart 2
        $this.onButtonShowMore();
    }
   
    this.setChangeTabAndContentSlider = function (tabName, tabId) {
        //let tab_remove = null;
        // ---- set tab active 
	tabName.addClass('active');
        tabName.addClass('event_cart_2_tabs_active');
        // ---- find conenten isset => set new conent
        $jq(".evt_cart_2_slide_content").each(function () {
            if ($jq(this).hasClass('visible_tab_conent')) {
                $jq(this).removeClass('visible_tab_conent');
//                tab_remove = $jq(this);
//                $jq(this).css({'transform' : 'translateX(-1200px)', 'transition': '1s'});
//                $jq(this).css({'transform' : 'translateX(-1200px)', 'transition': '1s'});
//                $jq("#tab_conent_" + tabId).css({'transform' : 'translateX(3000px)'});
//                $jq(".evt_cart_2_slide_button").css({'transform' : 'translateX(-1200px)', 'transition': '1s'});
//                $jq("#tab_conent_" + tabId).addClass("visible_tab_conent");
            }

            return true;
        });
        $jq("#button_more_"+tabId).addClass('visible_btn_mobile');
        $jq("#tab_conent_" + tabId).addClass("visible_tab_conent");
        $this.setSwiperSlider();
//     setTimeout(function() {
//          tab_remove.removeClass('visible_tab_conent');
//          tab_remove.css({'transform' : 'translateX(0px)'});
//          $jq("#tab_conent_" + tabId).addClass("visible_tab_conent");
//          $jq("#tab_conent_" + tabId).css({'transform' : 'translateX(0px)', 'transition': '1s'});
//        }, 800);
    }
    
    this.setSwiperSlider = function () {
        $jq(".evt_cart_2_slide_swpier_container.visible_tab_conent").each(function () {
            let idName = $jq(this).attr('id');
            let w = window.innerWidth || document.body.clientWidth;
            if (idName) {
                new Swiper('#' + idName, {
                    slidesPerView: 'auto',
                    allowTouchMove: (w <= 1230) ? true : false
                });
            }
            return;
        });
    }
    
    this.onButtonShowMore = function () {
        $jq(".evt_cart_2_pop_viewmore").on('click', function (e) {
            e.preventDefault();
            let target = $jq(this);
            let nameType = target.attr("ref");
            if ($jq(".evt_cart_2_pop_content_more_" + nameType).is(":visible")) {
                let child1 = $jq(this).children(".evt_cart_2_collapsed");
                child1.children(".pop_text_viewmore").text("Xem thêm");
                child1.children(".pop_icon_more_down").css({'transform': 'rotate(0deg)', 'transition': 'all 0.5s'});
                $jq(".evt_cart_2_pop_content_more_" + nameType).slideUp("slow");
            } else {
                let child1 = $jq(this).children(".evt_cart_2_collapsed");
                child1.children(".pop_text_viewmore").text("Rút gọn");
                child1.children(".pop_icon_more_down").css({'transform': 'rotate(180deg)', 'transition': 'all 0.5s'});
                $jq(".evt_cart_2_pop_content_more_" + nameType).slideDown(500);
            }
        });
    }

    this.onHideEvtCart2 = function () {
        $jq(".youama-ajaxlogin-cover").fadeOut(0);
        $jq('#popup-loading-event-cart-2').fadeOut(0);
        $this.clearContentHtml();
    }

    this.onShowEvtCart2 = function () {
        $jq(".popup-loading-event-cart-2-info").show();
        $jq(".youama-ajaxlogin-cover").fadeIn();
        $jq('#popup-loading-event-cart-2').fadeIn(10);
    }

    this.onClickShowDetail = function (id, outside = 'false') {
        $this.onShowEvtCart2();
        $this.onShowDetailCartEvent2(outside);
        $this.clearContentHtml();
        let content = '';
        let data = dataContent;

        if (data && data[id]) {
            content = data[id];
            $jq('#popup-loading-event-cart-2-content-rules').html(content);
    }
    }

    this.clearContentHtml = function () {
        $jq('#popup-loading-event-cart-2-content-rules').html('');
    }

    this.onShowDetailCartEvent2 = function (outside = 'false') {
        if (outside == 'true') {
            // turn off button left (back);
            $jq(".btn_back_evt_2").hide();
        } else {
            $jq(".btn_back_evt_2").show();
        }
        $jq(".popup-loading-event-cart-2-rule").fadeIn(10);
        $jq(".popup-loading-event-cart-2-info").fadeOut(0);
    }
    this.onHideDetailCartEvent2 = function () {
        $jq(".popup-loading-event-cart-2-rule").fadeOut(10);

    }
    this.onBackDetailEvent = function () {
        $this.clearContentHtml();
        $this.onHideDetailCartEvent2();
        $this.onShowEvtCart2();
    }
}