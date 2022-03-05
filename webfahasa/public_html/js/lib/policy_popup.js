var PolicyPopUp = function () {
    var GET_CONTENTBY_BLOCK_ID = "/fahasa_catalog/product/getContentByBlockId";
    var _policy_data = {};
    var $this = this;
    var _skin_url;
    var _close_icon = 'frontend/ma_vanese/fahasa/images/ico_delete_gray.svg?q=';
    this.init = function (skin_url) {
         _skin_url = skin_url;
    }
    
    this.showPopUp = function (block_id, title) {
        if (block_id in _policy_data) {
            let content = _policy_data[block_id];
            $this.appendContentInPopUp(content, title);
        } else {
            $jq.ajax({
                url: GET_CONTENTBY_BLOCK_ID,
                method: 'get',
                dataType: "json",
                contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                data: {blockId: block_id},
                success: function (data) {
                    if (data['success']) {
                        _policy_data[block_id] = data['content'];
                        $this.appendContentInPopUp(data['content'], title);
                    }
                }
            });
        }
    }

    this.appendContentInPopUp = function (content, title) {
	if(fhs_account.isEmpty(content)){content = '';}
	
        if (!title){
            title = 'ĐIỀU KIỆN ÁP DỤNG';
        }
        let html = '<div><div class="policy-popup-title">'
        + '<div>' + title +'</div><div class="close lg-close" onclick="policy_popup.hidePolicyPopUp();">'
        + '<img src="' + _skin_url + _close_icon + '"></div></div><div class="event-cart-popup-info">' +
                content +
                '</div></div>';
        $jq('.policy-popup-background').show();
        $jq('.policy-popup-content').html(html);
    }
    
     this.openPolicyPopUp = function(){
        $jq('.policy-popup-background').show();
    }
    
    this.hidePolicyPopUp = function (e) {
        $jq('.policy-popup-background').hide();
    }
    
    this.clickMoreEventCart = function(){
        if ($jq('.product-view-event-cart-rest:visible')[0]) {
            this.hideMoreEventCart();
        } else {
            this.showMoreEventCart();
        }
    }
    
    this.showMoreEventCart = function () {
        $jq('.product-view-event-cart-rest').show();
        $jq('#btn_show_more_event_cart > .event-cart-view-less').show();
        $jq('#btn_show_more_event_cart > .event-cart-view-more').hide();
    };

    this.hideMoreEventCart = function () {
        $jq('.product-view-event-cart-rest').hide();
        $jq('#btn_show_more_event_cart > .event-cart-view-more').show();
        $jq('#btn_show_more_event_cart > .event-cart-view-less').hide();
    }
    
}