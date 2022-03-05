const CheckoutOutStockProduct = function (skin_url, languages) {
    var GET_OUT_STOCK_PRODUCT_URL = '/onestepcheckout/index/getOutStockProduct';
    var DELETE_OUT_STOCK_PRODUCT_URL = '/onestepcheckout/index/deleteOutStockProduct';
    var REPLACE_OUT_STOCK_PRODUCT_URL = '/onestepcheckout/index/replaceOutStockProduct';
    var UPDATE_OUT_STOCK_PRODUCT_URL = '/onestepcheckout/index/updateOutStockProduct';
    var _skin_url = null;
    var _languages = {};
    var $this = this;
    var _data = [];
    var _is_create_order;

    this.init = function (skin_url, languges) {
        this._skin_url = skin_url;
        $this._languages = languages;
        $this._is_create_order = false;
    }

    this.renderOutStockProduct = function (product) {
        let notice = "";
        let button = "";
        if (product.out_stock) {
            notice = '<div class="notice">' + $this._languages['slow_delivery'] + '</div>';
        }

        if (product.replace_products && Object.keys(product.replace_products).length > 0) {
            button = '<button onClick="checkout_outstock_product.openOutStockProductDetail(' + product.product_id + ')">'
                    + '<span>' + $this._languages['choose_replace_product'] + '</span>'
                    + '<img class="icon" src="' + $this._skin_url + '/frontend/ma_vanese/fahasa/images/ico_seemore_blue.svg"/>'
                    + '</button>';
        } else {
            button = '<div class="product-view-quantity-box-block">'
                    + '<a class="btn-subtract-qty" onclick="checkout_outstock_product.subtractQty(' + product.quote_item_id + ',event);">'
                    + '<img src="' + $this._skin_url + '/frontend/ma_vanese/fahasa/images/ico_minus2x.png"></a>'
                    + '<input type="text" class="qty-carts" name="cart[' + product.quote_item_id + '][qty]" id="popup-outstock-qty-' + product.quote_item_id + '" maxlength="12" align="center" value="' + product.qty + '" '
                    + 'onkeypress="checkout_outstock_product.validateNumber(event)" onchange="checkout_outstock_product.validateQty(' + product.quote_item_id + ');" title="Số lượng">'
                    + '<a class="btn-add-qty" onclick="checkout_outstock_product.addQty(' + product.quote_item_id + ',event);">'
                    + '<img '
                    + 'src="' + $this._skin_url + '/frontend/ma_vanese/fahasa/images/ico_plus2x.png"></a>'
                    + '</div>';

        }
        let item_html = '<div class="cart-item">'
                + '<div class="remove-cart-item">'
                + '<button onclick="checkout_outstock_product.deleteOutStockProduct(' + product.quote_item_id + ')">'
                + '<img src="' + $this._skin_url + '/frontend/ma_vanese/fahasa/images/checkout_cart/xcircle.svg" />'
                + '</button>'
                + '</div>'
                + '<a href="' + product.url + '">'
                + '<div class="image">'
                + '<img src="' + product.image + '" />'
                + '</div>'
                + '</a>'
                + '<div class="product-info">'
                + '<a href="' + product.url + '">'
                + '<div class="name-price-container">'
                + '<div class="product-name-no-ellipsis">' + product.name + '</div>'
                + '<div ><span class="price">' + Helper.formatCurrency(product.price) + '</span><span> x ' + product.qty + '</span></div>'
                + notice
                + '</div>'
                + '</a>'
                + '<div  class="view-related-btn-container">'
                + button
                + '</div>'
                + '</div>'
                + '</div>';
        return item_html;

    }

    this.renderOutStockProducts = function (data) {
        $this.hideMessage();
        $this.renderNoticeBoxInAddress();
          
        let products = data.data;
        let province = data.province;
        let mini_cart = data.mini_cart;
        if (!products || Object.keys(products).length == 0) {
            $this.hidePopupOutStockProduct();
            return;
        }

        $jq(".popup-out-stock-product-list .title .province").text(province);

        let content = "";
        for (let i = 0; i < products.length; i++) {
            let product = products[i];
            content += $this.renderOutStockProduct(product);
        }
        $jq("#popup-out-stock-product .list-cart").html(content);

        if (mini_cart) {
            $jq(".cart-top .top-cart-contain").html(mini_cart);
        }
     
    }

    this.checkCartHasOutStockProduct = function () {
        $jq.ajax({
            url: GET_OUT_STOCK_PRODUCT_URL,
            method: 'post',
            dataType: "json",
            headers: ps.getHeader(),
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            success: function (data) {
                if (data['success'] && data.data && Object.keys(data.data).length > 0) {
                    $this._data = data.data;
                    $this.renderOutStockProducts(data);
                }
            }
        });
    }

    this.checkCartHasOutStockProductInCart = function () {
        $jq.ajax({
            url: GET_OUT_STOCK_PRODUCT_URL,
            method: 'post',
            headers: ps.getHeader(),
            dataType: "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            success: function (data) {
                if (data['success'] && data.data && Object.keys(data.data).length > 0) {
                    $this._data = data.data;
                    $this.renderOutStockProducts(data);
                    $this.renderOutStockProductsInCart(data.data);

                }
            }
        });
    }

    this.renderOutStockProductsInCart = function (products) {
        if (!products || Object.keys(products).length == 0) {
            return;
        }

        for (let i = 0; i < products.length; i++) {
            let product = products[i];
            if (product.out_stock) {
                //item-product-cart info-product-cart
                let quote_item_id = product.quote_item_id;
                let message = '<div class="checkout-stock error">' + $this._languages['slow_delivery'] + '</div>';
                let btn_choose_product = "";
                if (product.replace_products && Object.keys(product.replace_products).length > 0) {
                    btn_choose_product = '<div class="view-related-btn-container"><div onClick="checkout_outstock_product.openOutStockProductDetail(' + product.product_id + ')">'
                            + '<span>' + $this._languages['choose_replace_product'] + '</span><img class="icon" src="' + $this._skin_url + '/frontend/ma_vanese/fahasa/images/ico_seemore_blue.svg"/></div></div>';
                    ;
                }

                let content = message + btn_choose_product;
                $jq("#" + quote_item_id).closest(".item-product-cart").find(".info-product-cart").append(content);

            }
        }
    }


    this.renderNoticeBoxInAddress = function () {
        let $address_list = $jq("#fhs_checkout_block_address .fhs_checkout_block_address_list");

        $jq(".fhs_checkout_block_address_list_item_option").closest("div").find(".notice-shipping").remove();
        $jq(".fhs_checkout_block_address_block").find(".notice-shipping").remove();
        
        if (!$this._data) {
            return;
        }
        let out_stock_products = $this._data.filter(function (x) {
            return x.out_stock;
        });
        if (out_stock_products.length > 0) {
            let $cur_address_radio = $jq('.fhs_checkout_block_address_list_item_option:checked').closest('div');
            $cur_address_radio.css("flex-direction", "column").css("align-items", "flex-start");
            let notice_style = "";
            if (!$address_list[0]) {
                notice_style = "margin-left: 0px;";
            }
            let notice_box =
                    '<div class="notice-shipping" onclick="checkout_outstock_product.showPopupOutStockProduct()" style="' + notice_style + '">'
                    + '<img src="' + $this._skin_url + 'frontend/ma_vanese/fahasa/images/ico_exclaiming_orange.svg"/>'
                    + '<span style="margin-left: 8px;">' + $this._languages['notice_slow_delivery'] + '</span> '
                    + '<span class="view-more">' + $this._languages['viewnow'] + '</span></div>';

            if (!$address_list[0]) {
                $jq("#fhs_checkout_block_address .fhs_checkout_block_address_block").append(notice_box);
            } else {
                $cur_address_radio.append(notice_box);
            }
        }
    }

    this.deleteOutStockProduct = function (quote_item_id) {
        fhs_account.showLoadingAnimation();
        $jq.ajax({
            url: DELETE_OUT_STOCK_PRODUCT_URL,
            method: 'post',
            data: {quote_item_id: quote_item_id},
            dataType: "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            success: function (data) {
                fhs_account.hideLoadingAnimation();
                if (data['success']) {
                    $this._data = data.data;

                    $this.renderOutStockProducts(data);
                    fhs_onestepcheckout.getCheckout($jq('#fhs_checkout_products'));
                } else {
                    $this.showErrorMessage(data.message);
                }
            },
            error: function () {
                fhs_account.hideLoadingAnimation();
            }
        });
    }

    this.replaceOutStockProduct = function (product_id, quote_item_id) {
        fhs_account.showLoadingAnimation();
        $jq.ajax({
            url: REPLACE_OUT_STOCK_PRODUCT_URL,
            method: 'post',
            data: {quote_item_id: quote_item_id, product_id: product_id},
            dataType: "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            success: function (data) {
                fhs_account.hideLoadingAnimation();
                if (typeof fhs_onestepcheckout !== 'undefined') {
                    if (data['success']) {
                        $this._data = data.data;
                        $this.hidePopupOutStockProductInDetail();
                        $this.renderOutStockProducts(data);

                        fhs_onestepcheckout.getCheckout($jq('#fhs_checkout_products'));
                        $this.showSuccessMessage(data.message);
                    } else {
                        $this.showErrorMessage(data.message);
                    }
                } else {
                    $this.hidePopupOutStockProduct();
                    window.location.reload();
                }
            },
            error: function () {
                fhs_account.hideLoadingAnimation();
            }
        });
    }

    this.addQty = function (quote_item_id, event) {
        let $qty_input = $jq("#popup-outstock-qty-" + quote_item_id);
        let qty = parseInt($qty_input.val());
        let qty_submit = qty + 1;
        $qty_input.val(qty_submit);
        $this.updateCartQty(quote_item_id, qty_submit);
    }

    this.hideMessage = function (message) {
        if (message) {
            $jq("#popup-out-stock-product .message").removeClass("success").addClass("error").text(message).fadeIn();
        }
    }

    this.showSuccessMessage = function (message) {
        if (message) {
            $jq("#popup-out-stock-product .message").removeClass("error").addClass("success").text(message).fadeIn();
        }
    }

    this.showErrorMessage = function (message) {
        if (message) {
            $jq("#popup-out-stock-product .message").removeClass("success").addClass("error").text(message).fadeIn();
        }
    }

    this.hideMessage = function () {
        $jq("#popup-out-stock-product .message").fadeOut();
    }

    this.subtractQty = function (quote_item_id) {
        let $qty_input = $jq("#popup-outstock-qty-" + quote_item_id);
        let qty = parseInt($qty_input.val());
        let qty_submit = qty - 1;
        if (qty_submit <= 0) {
            qty_submit = 1;
        } else {
            $this.updateCartQty(quote_item_id, qty_submit);
        }
        $qty_input.val(qty_submit);
    }

    this.validateNumber = function (evt) {
        var theEvent = evt || window.event;

        // Handle paste
        if (theEvent.type === 'paste') {
            key = event.clipboardData.getData('text/plain');
        } else {
            // Handle key press
            var key = theEvent.keyCode || theEvent.which;
            key = String.fromCharCode(key);
        }
        var regex = /[0-9]|\./;
        if (!regex.test(key)) {
            theEvent.returnValue = false;
            if (theEvent.preventDefault)
                theEvent.preventDefault();
        }
    }

    this.validateQty = function (quote_item_id) {
        let $qty_input = $jq("#popup-outstock-qty-" + quote_item_id);
        let qty = parseInt($qty_input.val());
        let qty_submit = qty;
        if (qty_submit <= 0) {
            qty_submit = 1;
        } else {
            $this.updateCartQty(quote_item_id, qty_submit);
        }
        $qty_input.val(qty_submit);
    }

    this.updateCartQty = function (quote_item_id, qty_submit) {
        fhs_account.showLoadingAnimation();
        $jq.ajax({
            url: UPDATE_OUT_STOCK_PRODUCT_URL,
            method: 'post',
            data: {quote_item_id: quote_item_id, qty: qty_submit},
            dataType: "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            success: function (data) {
                fhs_account.hideLoadingAnimation();
                if (data['success']) {
                    $this._data = data.data;

                    $this.renderOutStockProducts(data);
                    fhs_onestepcheckout.getCheckout($jq('#fhs_checkout_products'));
                } else {
                    $this.showErrorMessage(data.message);
                }
            },
            error: function () {
                fhs_account.hideLoadingAnimation();
            }
        });
    }
    
    this.onPressContinuePayment = function(){
        $this.hidePopupOutStockProduct();
        if (this._is_create_order && typeof fhs_onestepcheckout !== 'undefined') {
            fhs_onestepcheckout.validateCreateOrder();
        }
        $this._is_create_order = false;
    }
    
    this.hidePopupOutStockProduct = function () {
        $jq(".youama-ajaxlogin-cover").fadeOut(0);
        $jq('#popup-out-stock-product').fadeOut(0);

    }


    this.showPopupOutStockProduct = function () {
        $jq(".youama-ajaxlogin-cover").fadeIn();
        $jq('#popup-out-stock-product').fadeIn();
    }
    
    this.showPopupCheckoutStockBeforePayment = function(){
        $this._is_create_order = true;
        $this.showPopupOutStockProduct();
    }

    this.hidePopupOutStockProductInDetail = function () {
        $jq('#popup-out-stock-product .popup-out-stock-product-list').fadeIn();
        $jq('#popup-out-stock-product .popup-out-stock-product-detail').fadeOut(0);
    }

    this.showPopupOutStockProductInDetail = function () {
        $this.showPopupOutStockProduct();
        $jq('#popup-out-stock-product .popup-out-stock-product-list').fadeOut(0);
        $jq('#popup-out-stock-product .popup-out-stock-product-detail').fadeIn();
    }

    this.openOutStockProductDetail = function (product_id) {
        let cur_product = $this._data.filter(function (x) {
            return x.product_id == product_id;
        });
        if (cur_product.length > 0) {
            cur_product = cur_product[0];
            $this.showPopupOutStockProductInDetail();
            $this.renderMainProductInDetail(cur_product);
            $this.renderListProductInDetail(cur_product.replace_products, cur_product.quote_item_id);
        }

    };

    this.renderListProductInDetail = function (products, cur_quote_item_id) {
        let content = '';
        for (let i = 0; i < products.length; i++) {
            let product = products[i];
            content += $this.renderProductInDetail(product, cur_quote_item_id);
        }
        $jq(".popup-out-stock-product-detail .list-product").html(content);
    }

    this.renderProductInDetail = function (product, cur_quote_item_id) {
        let original_price = "";
        let discount_percent = "";
        if (product.price > product.final_price) {
            original_price = '<div class="old-price"><span class="price">' + Helper.formatCurrency(product.price) + '</span></div>';
            discount_percent = '<span class="discount-percent">' + product.discount_percent + '%</span>';
        }

        let content = '<div class="product-item item-inner">'
                + '<a href="' + product.url + '" alt="' + product.name + '">'
                + '<div class="image">'
                + '<img src="' + product.image + '"/>'
                + '</div>'
                + '<div class="product-info" >'
                + '<div class="name-price-container">'
                + '<div class="product-name-no-ellipsis">' + product.name + '</div>'
                + '<div ><span class="price">' + Helper.formatCurrency(product.final_price) + '</span>' + discount_percent + '</div>'
                + original_price
                + '</div>'
                + '</div>'
                + '</a>'
                + '<button class="btn-replace-product" onclick="checkout_outstock_product.replaceOutStockProduct(' + product.entity_id + ', ' + cur_quote_item_id + ')"><span>' + $this._languages['choose_replace'] + '</span></button>'
                + '</div>';
        return content;
    }

    this.renderMainProductInDetail = function (product) {
        let content =
                '<a href="' + product.url + '">'
                + '<div class="image">'
                + '<img src="' + product.image + '" />'
                + '</div>'
                + '<div class="product-info" >'
                + '<div class="name-price-container">'
                + '<div class="product-name-no-ellipsis">' + product.name + '</div>'
                + '<div ><span class="price">' + Helper.formatCurrency(product.price) + '</span><span> x 1</span></div>'
                + '</div>'
                + '</div>'
                + '</a>';

        $jq(".popup-out-stock-product-detail .main-product").html(content);
    };
    
    this.validateCartHasOutStockProduct = function(){
        if ($this._data && Object.keys($this._data).length > 0) {
            let num_out_stocks = $this._data.filter(function (x) {
                return x.out_stock;
            }).length;
            if (num_out_stocks > 0) {
                return true;
            }
        }
        
        return false;
    }

    this.init(skin_url, languages);
}