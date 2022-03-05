const Cart = function () {
    var UPDATE_CART_AJAX_URL = "/rediscart/cart/updateCart";
    var DELETE_ITEM_AJAX_URL = "/rediscart/cart/deleteCart";
    var GET_CART_AJAX_URL = "/rediscart/cart/getCart";
    var ADD_CHECKED_PRODUCT_AJAX_URL = "/rediscart/cart/addCheckedProduct";
    var DELETE_ALL_ITEMS_IN_QUOTE_AJAX_URL = "/rediscart/cart/deleteAllItems";
    var CHECK_ALL_PRODUCTS_AJAX_URL = "/rediscart/cart/checkAllProduct";

    var $this = this;

    var $cart = $jq(".product-cart-left");
    var $totals = $jq(".block-totals-cart-page");
    var $total_title_mobile = $jq(".title-price-mobile");
    var $total_price_mobile = $jq(".total-price-mobile");

    var $cart_2_side = $jq(".cart-ui-content");
    var $header_cart_item = $jq(".header-cart-item");
    var $checkbox_all_products = $jq("#checkbox-all-products");
    var $cart_msg = $jq(".fhs_redis_cart_msg");
    var $cart_msg_content = $jq(".fhs_redis_cart_msg .message");
    var $totals_checkout = $jq(".block-total-cart");
    var $cart_loading = $jq(".cart-loading");
    var $checkout_btn = $jq(".btn-proceed-checkout");
    var $title_num_items = $jq(".cart-title-num-items");
    var $num_items_checkbox = $jq(".num-items-checkbox");


    var $event_cart = $jq(".fhs_checkout_event_promotion");
    var $event_cart_container = $jq(".event-promotion-block");

    var _skin_url;
    var _grand_total = {};
    var _subtotal = {};
    var _cart_items = {};
    var _languages = {};
    var _session_id = null;

    this.init = function (skin_url, languages, session_id) {
        _skin_url = skin_url;
        _languages = languages;
        _session_id = session_id;
        console.log('seesion', session_id);
        $this.getCart();
    };


    this.addCheckedProductToBuy = function (productId)
    {
        $this.showCartLoading();
        let is_checked = $jq("#checkbox-product-" + productId).is(":checked");
        let data = {
            "product_id": productId,
            "is_checked": is_checked
        };
        $jq.ajax({
            url: ADD_CHECKED_PRODUCT_AJAX_URL,
            method: 'post',
            dataType: "json",
            data: JSON.stringify(data),
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            success: function (response) {
                $this.hideCartLoading();
                if (response.success) {
                    $this.displayCartAndTotals(response);
                } else {
                    if (response.message) {
                        $this.displayMessage(response.message);
                    }
                }
            },
            error: function () {
            }
        });
    };

    this.displayTotals = function (totals, can_payment) {
        let totals_content = "";
        if (totals && Array.isArray(totals) && totals.length > 0) {
            totals.forEach(function (total) {
                totals_content += $this.displayTotal(total);
            });
        }
        $totals.html(totals_content);
        $totals_checkout.show();
        let grand_total = totals.filter(x => x.code === 'grand_total');
        let subtotal = totals.filter(x => x.code === 'subtotal');

        let error_items = _cart_items.filter(x => x.has_error && x.is_checked);
        if (grand_total.length > 0) {
            _grand_total = grand_total[0];

        }
        if (subtotal.length > 0) {
            _subtotal = subtotal[0];
        }
        $total_title_mobile.text("Tổng cộng");
        $total_price_mobile.text(Helper.formatCurrency(_grand_total.price));
	
        if (can_payment) {
            $checkout_btn.removeClass("btn-checkout-disable");
        } else {
            $checkout_btn.addClass("btn-checkout-disable");
        }
    }

    this.displayTotal = function (total) {
        let bold_style = "";
        let border_line = "";
        if (total.code === "grand_total") {
            bold_style = "title-final-total";
            border_line = '<div class="border-product"></div>';
        }

        return border_line
                + '<div class="total-cart-page ' + bold_style + '">'
                + '<div class="title-cart-page-left">' + total.title + '</div>'
                + '<div class="number-cart-page-right"><span class="price">' + Helper.formatCurrency(total.price) + '</span></div>'
                + '</div>';
    }

    this.subtractQty = function (productId) {
        console.log('sub stra', productId)
        let qty = parseInt($jq('#qty-' + productId).val());
        if (qty > 1) {
            let qty_data = qty - 1;
            $jq('#qty-' + productId).val(qty - 1);
            let data = {
                "product_id": productId,
                "qty": qty_data
            }
            $this.updateCart(data);
        } else {
            $jq('#qty-' + productId).val(1);
        }
    };

    this.addQty = function (productId) {
        let qty = parseInt($jq('#qty-' + productId).val());
        var value = $jq('#qty-' + productId).attr("value");
        let maxlength = parseInt($jq('#qty-' + productId).attr('maxlength'));
        let qtylength = qty.toString().length;

        if (qtylength < maxlength && qty < 99) {
            console.log('inside hiadde loding')
            let qty_data = qty + 1;
            $jq('#qty-' + productId).val(qty + 1);
            let data = {
                "product_id": productId,
                "qty": qty_data
            }
            $this.updateCart(data);

        } else {
            $jq('#qty-' + productId).val(value);
        }
    };

    this.updateCart = function (data) {
        $this.showCartLoading();
        $jq.ajax({
            url: UPDATE_CART_AJAX_URL,
            method: 'post',
            data: JSON.stringify(data),
            dataType: "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            fail: function () {

            },
            success: function (response) {
                $this.hideCartLoading();
                if (response.success) {
                    $this.displayCartAndTotals(response);

                    if (!response.error){
                        console.log("INISDE TRACING");
                        $this.trackingAddToCartNetcore(response.updated_product);
                        $this.trackingAddToCartEnhanceEcom(response.updated_product);
                        $this.trackingAddToCartSuggestion(response.updated_product);
                    }
                    
                } else {
                    if (response.message) {
                        $this.displayMessage(response.message);
                    }
                }
            }
        });
    };

    this.displayMessage = function (msg) {
        if (msg) {
            $cart_msg_content.html(msg);
            $cart_msg.css("display", "flex");
        } else {
            $cart_msg_content.empty();
            $cart_msg.hide();
        }
    }

    this.clearMessage = function () {
        $cart_msg.empty().hide();
    }

    this.deleteItem = function (productId) {
        $this.showCartLoading();
        let data = {
            product_id: productId
        };
        $jq.ajax({
            url: DELETE_ITEM_AJAX_URL,
            method: 'post',
            data: JSON.stringify(data),
            dataType: "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            fail: function () {

            },
            success: function (response) {
                $this.hideCartLoading();
                if (response.success) {
                    $this.displayCartAndTotals(response);

                    $this.trackingAddToCartNetcore(response.deleted_product);
                    $this.trackingAddToCartEnhanceEcom(response.deleted_product);
                }
            }
        });
    };

    this.displayCartAndTotals = function (response) {
        _cart_items = response.items;
        $this.displayMessage(response.message);
        $this.displayCheckboxAllProducts(response.items);
        $this.dispayCart(response.items);
	$this.showMessageError(response.messages)
        $this.displayTotals(response.totals, response.can_payment);
        $this.displayEventCart(response);
        $this.tracking(_cart_items, response.totals);
    }

    this.tracking = function (cart_items, totals) {
        let subtotal_obj = totals.filter(x => x.code === 'grand_total');
        let subtotal = subtotal_obj.length > 0 ? subtotal_obj[0].price : 0;

        //tracking facebook: addToCartFacebook = view cart (dispatch all items in cart)
	$this.trackingAddToCartFacebook(cart_items, subtotal);

        //tracking gtag: view cart (dispatch all items in cart)
        $this.trackingGooogleConversion(cart_items, subtotal); //getGgAddToCartJS
        
        //tracking netcore view cart
	$this.trackingViewCartNetcore(cart_items, subtotal);
    };

    //tracking add to cart suggestion: only for add to cart and not remove from cart
    this.trackingAddToCartSuggestion = function (updated_product) {
        try{
	    if (updated_product) {
		let suggestion_item = $this.getCartItemForNetcore(updated_product);
		if (updated_product.quantity >= 0) {
		    Suggestion(_session_id, 'Add To Cart', {items: [suggestion_item]});
		} 
	    }
	}catch(ex){}
    }

    this.trackingAddToCartEnhanceEcom = function (updated_product) {
        try{
	    let data = $this.getCartItemForEnhanceEcom(updated_product);
	    if (updated_product.quantity >= 0) {
		dataLayer.push({'event': 'addToCart', 'ecommerce': {'currencyCode': 'VND', 'add': {'products': [data]}}});
	    } else {
		dataLayer.push({'event': 'removeFromCart', 'ecommerce': {'currencyCode': 'VND', 'remove': {'products': [data]}}});
	    }
	}catch(ex){}
    };

    this.getCartItemForEnhanceEcom = function (item) {
        if (!item) {
            return null;
        }
        return {
            'name': item.product_id,
            'id': item.sku,
            'category': item.category_mid,
            'brand': item.supplier,
            'price': item.price,
            'quantity': Math.abs(item.quantity)
        };
    };

    //??? TEST OK
    this.trackingAddToCartNetcore = function (updated_product) {
        try{
	    if (updated_product) {
		let netcore_item = $this.getCartItemForNetcore(updated_product);
		if (updated_product.quantity >= 0) {
		    smartech('dispatch', 'Add To Cart', {"items": [netcore_item]});
		} else {
		    smartech('dispatch', 'Remove From Cart', {"items": [netcore_item]});
		}
	    }
	}catch(ex){}
    };


    //??? TEST OK. View amount again
    this.trackingViewCartNetcore = function (cart_items, subtotal) {
        try{
	    let netcore_items = $this.getViewCartForNetcore(cart_items);
	    let netcore_data = {
		"amount": subtotal,
		"items": netcore_items
	    };

	    smartech('dispatch', 'viewcart', netcore_data);
	}catch(ex){}
    };

    this.trackingGooogleConversion = function (cart_items, subtotal) {
        try{
	    let listSkus = [];
	    for (let i = 0; i < cart_items.length; i++) {
		let e = cart_items[i];
		listSkus.push(e.sku);
	    }

	    gtag('event', 'page_view', {
		'send_to': 'AW-857907211',
		'dynx_itemid': listSkus,
		'dynx_pagetype': 'conversionintent',
		'dynx_totalvalue': subtotal,
		'ecomm_prodid': listSkus,
		'ecomm_pagetype': 'cart',
		'ecomm_totalvalue': subtotal
	    });
	    console.log('trackingGooogleConversion')
	}catch(ex){}
    }
    this.trackingAddToCartFacebook = function (cart_items, subtotal) {
        try{
	    let listSkus = [];
	    let contents = [];
	    for (let i = 0; i < cart_items.length; i++) {
		let e = cart_items[i];
		let item = {
		    "id": e.product_id,
		    "quantity": e.quantity,
		    "name": Helper.removeDiacritics(e.name),
		    "category_1": e.category_main_id,
		    "category_2": e.category_mid_id,
		    "category_3": e.category_3_id,
		    "category_4": e.category_4_id,
		    "supplier": e.supplier
		};
		contents.push(item);
		listSkus.push(e.sku);
	    }
	    let data = {
		"content_name": "Shopping Cart",
		"content_ids": listSkus,
		"content_type": "product",
		"value": subtotal,
		"currency": "VND",
		"contents": contents
	    };
	    console.log('data fb', data);
	    fbq('track', 'AddToCart', data);
	    console.log('FINISH FACEBOOK ');
	}catch(ex){}
    }

    this.getViewCartForNetcore = function (cart_items) {
        return cart_items.map(function (e) {
            return $this.getCartItemForNetcore(e);
        });
    }

    this.getCartItemForNetcore = function (e) {
        if (!e) {
            return null;
        }

        return {
            "prid": e.product_id,
            "name": e.name,
            "prqt": e.quantity,
            "price": e.original_price,
            "final_price": e.price,
            "price_text": Helper.numberWithCommas(e.price),
            "final_price_text": Helper.numberWithCommas(e.original_price),
            "category_main": e.category_main,
            "category_mid": e.category_mid,
            "image": e.image,
            "url": e.product_url,
            "discount": e.discount,
            "category_3": e.category_3,
            "category_4": e.category_4
        };
    }

    this.displayEventCart = function (response) {
        let eventCart = response.event_cart;
        let eventCartFront = response.event_cart_front;
	
        let event_block = "";

	$event_cart_container.hide();
	    
	let event_cart_content = "";
	let couponLabel = "";
	if (response.couponCode && response.couponLabel) {
	    couponLabel = '<div class="fhs_label_coupon_label_row">'
		    + '<div class="fhs_label_coupon_label_orange"><div>' + response.couponLabel
		    + '</div><div onclick="fhs_promotion.applyCoupon(this);" coupon="'
		    + response.couponCode
		    + '" apply="0"><img src="' + _skin_url + 'frontend/ma_vanese/fahasa/images/ico_delete_orange.svg?"/></div></div></div>';
	}

	let freeshipLabel = "";
	if (response.freeshipCouponCode && response.freeshipCouponLabel) {
	    freeshipLabel = '<div class="fhs_label_coupon_label_row"><div class="fhs_label_coupon_label_green">'
		    + '<div>' + response.freeshipCouponLabel + '</div><div onclick="fhs_promotion.applyCoupon(this);" coupon="' + response.freeshipCouponCode
		    + '" apply="0"><img src="' + _skin_url + 'frontend/ma_vanese/fahasa/images/ico_delete_green.svg?"/></div></div></div>';
	}
	    
	if(eventCartFront){
	    if(eventCartFront['event_cart_show']){
		Object.keys(eventCartFront['event_cart_show']).forEach(function(key){
		    let keys = {
			'key_type': eventCartFront['event_cart_show'][key]['key_type'],
			'key_name': eventCartFront['event_cart_show'][key]['key_name'],
			'key_index': eventCartFront['event_cart_show'][key]['key_index']
		    };
		    let item = '<div class="fhs-event-promo-item fhs-event-promo-item-line ">'
                        + fhs_promotion.getPromotionItem(eventCartFront['event_cart_show'][key], keys, true, true)
                        + '</div>';
		    event_cart_content += item;
		});
		
		event_block = '<div class="fhs-event-promo">' + $this.getPromoTitle() + event_cart_content + '</div><div class="fhs-event-promo-sumary">' + couponLabel + freeshipLabel + _languages['coupon_info'] + '</div>';
		
	    }
	}
	if(!fhs_account.isEmpty(event_block)){
	    $event_cart.html(event_block);
	    fhs_promotion.displayPopupPromotion(eventCart);
	    $event_cart_container.show();
	}
    }

    this.getPromoTitle = function () {
        return '<div class="fhs-event-promo-title">'
                + '<div class="fhs-event-promo-title-left">'
                + '<span><img src="' + _skin_url + '/frontend/ma_vanese/fahasa/images/promotion/ico_coupon.svg"/></span>'
                + '<span>'+_languages['rewards']+'</span>'
                + '</div>'
                + '<div class="fhs-event-promo-title-viewmore" onclick="fhs_promotion.showEventCart();">'
                + '<span>' + _languages['viewmore'] + '</span>'
                + '<span><img src="' + _skin_url + '/frontend/ma_vanese/fahasa/images/ico_seemore_blue.svg"/></span>'
                + '</div>'
                + '</div>';
    }

    this.displayCheckboxAllProducts = function (cart_items) {
        if (cart_items && Array.isArray(cart_items) && cart_items.length > 0) {
            $header_cart_item.css("display", "flex");
            let num_product_checked = cart_items.filter(function (x) {
                return x.is_checked;
            }).length;
            if (num_product_checked == cart_items.length) {
                $checkbox_all_products.prop('checked', true);
            } else {
                $checkbox_all_products.prop('checked', false);
            }
        } else {
            $header_cart_item.hide();
        }
    }

    this.showCartLoading = function () {
        $cart_loading.css("display", "flex");
    }

    this.hideCartLoading = function () {
        $cart_loading.hide();
    }

    this.getCart = function () {
        $this.showCartLoading();
        $jq.ajax({
            url: GET_CART_AJAX_URL,
            method: 'post',
            dataType: "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            fail: function () {

            },
            success: function (response) {
                $this.hideCartLoading();
                if (response.success) {
                    $this.displayCartAndTotals(response);
                }
            }
        });
    };

    this.deleteAllItemsInQuote = function () {
        $this.showCartLoading();
        $jq.ajax({
            url: DELETE_ALL_ITEMS_IN_QUOTE_AJAX_URL,
            method: 'post',
            dataType: "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            success: function (data) {
                $this.hideCartLoading();
            },
            error: function () {

            }
        });
    };

    this.dispayCart = function (cart_item) {
        let $cart_content = "";

        if (!cart_item || !Array.isArray(cart_item) || cart_item.length == 0) {
            $cart_content = $this.displayEmptyCart();
            $cart_2_side.html($cart_content);
            $title_num_items.html("(0 " + _languages.items + ")");
        } else {
            for (let i = 0; i < cart_item.length; i++) {
                $cart_content += $this.displayItem(cart_item[i]);
            }
            $cart.html($cart_content);
            $title_num_items.html("(" + cart_item.length + " "+ _languages.items + ")");
            $num_items_checkbox.html(cart_item.length);
        }
    };

    this.displayEmptyCart = function () {
        let empty_view = '<div style="box-shadow: 0px 0px 2px rgba(0, 0, 0, 0.1);padding: 20px;background-color: #fff;flex: 1; border-radius: 8px;">'
                + '<div class="cart-empty body-mh-300" style="justify-content: center;display: flex;align-items: center;">'
                + '<div style="text-align: center">'
                + '<div class="icon-empty-cart">'
                + '<img src="' + _skin_url + '/frontend/ma_vanese/fahasa/images/checkout_cart/ico_emptycart.svg" class="center">'
                + '</div>'
                + '<p style="font-size:14px;margin: 20px 0;">Chưa có sản phẩm trong giỏ hàng của bạn.</p>'
                + '<a style="color: white;text-transform: uppercase;" href="/flashsale?fhs_campaign=cta_emptycart"><button class="button-shopping" type="button" title="Mua sắm ngay" style="margin:auto">Mua sắm ngay</button></a>'
                + '</div>'
                + '</div>'
                + '</div>';
        return empty_view;
    }

    this.displayItem = function (item) {
        let product_id = item.product_id;
        let is_checked = "";
        let soon_release = '';
	if (item.is_checked) {
            is_checked = "checked";
        }
	if(item.soon_release){
	    soon_release = "<p class='item-msg notice'>"+fhs_account.languages['comingsoon']+"</p>";
	}

        let disabled_check_product = "";
        if (item.out_of_stock && !item.is_checked) {
            disabled_check_product = "disabled";
        }
        let check_product = '<div class="checked-product-cart">'
                + '<input  type="checkbox" id="checkbox-product-' + product_id + '" name="checkbox_product_' + product_id + '" class="checkbox-add-cart"'
                + `onclick="cart.addCheckedProductToBuy('` + product_id + `')" ` + is_checked + ' ' + disabled_check_product + `/>`
                + '</div>';
        let product_image = '<div class="img-product-cart">'
                + '<a  class="product-image" href="' + item.product_url + '" >'
                + '<img src="' + item.image + '" width="120" height="120" alt="' + item.name + '" />'
                + '</a>'
                + '</div>';
        let product_name = '<h2 class="product-name-full-text">'
                + '<a href="' + item.product_url + '">'
                + item.name
                + "</a>"
                + "</h2>"
		+ soon_release;
        let options = '';
        for (let item_id in item.options){
            console.log('ii------------')
            if (item.options.hasOwnProperty(item_id)) {
                let option = item.options[item_id];
                options += '<dd>'+ option.quantity + " x " + option.name + '</dd>';
            } 
        }
        console.log('objee--------', options)
        if (Object.keys(item.options).length > 0){
            options = '<div class="item-options">' + options + '</div>';
        }
        
        
        
//        let options = ' <?php if ($_options = $_item["options""]): ?>'
//                + '<dl class="item-options">'
//                + '<?php foreach ($_options as $_option) : ?>'
//                + '<?php // var_dump($_options);?>'
//                + '<dd class="truncated"<?php endif; ?>><?php echo $_option['qty'] . " x " . $_option['name'] ?>'
//                + '</dd>'
//                + '<?php endforeach; ?>'
//                + '</dl>'
//                + '<?php endif; ?>';

        let original_price = "";
        if (item.original_price != item.price) {
            original_price = '<div class="fhsItem-price-old"><span class="price">' + Helper.formatCurrency(item.original_price) + '</span></div>';
        }
        let price_product = '<div class="price-original">'
                + '<div class="cart-price">'
                + '<div class="cart-fhsItem-price">'
                + '<div><span class="price">' + Helper.formatCurrency(item.price) + '</span></div>'
                + original_price
                + '</div>'
                + '</div>'
                + '</div>';

        let remove_item = '';
        if (!item.is_free_product) {
            remove_item = '<a onclick="cart.deleteItem(\'' + product_id +  '\', event);" title="Remove item" id="' + product_id + '" class="btn-remove-mobile-cart"><i class="fa fa-trash-o" style="font-size:22px"></i></a>';

        }
        let qty_box = '';
        if (item.is_free_product) {
            qty_box = item.quantity;
        } else {
            let qty_ordered = item.quantity;
            if (item.has_error && item.desired_qty) {
                qty_ordered = item.desired_qty;
            }
            qty_box = '<div class="product-view-quantity-box">'
                    + '<div class="product-view-quantity-box-block">'
                    + `<a class="btn-subtract-qty" onclick="cart.subtractQty('` + product_id + `', event);">`
                    + '<img style="width: 12px; height: auto;vertical-align: middle;" src="' + _skin_url + '/frontend/ma_vanese/fahasa/images/ico_minus2x.png"/></a>'
                    + '<input type="text" class="qty-carts" name="cart[' + product_id + '][qty]" '
                    + 'id="qty-' + product_id + '" maxlength="12" align="center" '
                    + 'value="' + qty_ordered + '" onkeypress="cart.validateNumber(event)" '
                    + 'onchange="cart.validateQty(' + product_id + ')" '
                    + 'title="So luong" class="input-text qty" />'
                    + `<a class="btn-add-qty" onclick="cart.addQty('` + product_id + `', event);">`
                    + '<img style="width: 12px; height: auto;vertical-align: middle;" '
                    + 'src="' + _skin_url + 'frontend/ma_vanese/fahasa/images/ico_plus2x.png"/>'
                    + '</a>'
                    + '</div>'
                    + '<div class="product-view-icon-remove-mobile" style="display:none;">'
                    + remove_item
                    + '</div>'
                    + '</div>';
        }

        let row_total = '<div class="cart-price-total">'
                + '<span class="cart-price">'
                + '<span class="price">' + Helper.formatCurrency(item.row_total) + '</span>'
                + '</span>'
                + '</div>';
        let remove_btn = '';
        if (!item.is_free_product) {
            remove_btn = `<a onclick="cart.deleteItem('` + product_id + `', event);"  title="Remove Item"  `
                    + 'class="btn-remove-desktop-cart" >'
                    + '<i class="fa fa-trash-o" style="font-size:22px"></i>'
                    + '</a>';
        }
        let remove_box = '<div class="div-of-btn-remove-cart">'
                + remove_btn
                + '</div>';

        let qty_box_css = '';
        if (item.is_free_product) {
            qty_box_css = 'style="display:none;" <?php endif; ?>';
        }

        let message = '';
        if (item.message) {
            message = '<p class="item-msg error">* ' + item.message + '</p>';
        }
        let product_view = '<div class="item-product-cart">'
                + check_product
                + product_image
                + '<div class="group-product-info">'
                + '<div class="info-product-cart">'
                + '<div>'+product_name+'</div>'
                
                + options
                + price_product
                + message
                + '</div>'
                + '<div class="number-product-cart" ' + qty_box_css + '>'
                + qty_box
                + row_total
                + '</div>'
                + '</div>'
                + remove_box
                + '</div>'
                + '<div class="border-product"></div>';
        return product_view;

    };

    this.validateQty = function (productId) {
        console.log('vliddat validateQty')
        var value = $jq('#qty-' + productId).attr("value");
        if (!$jq('#qty-' + productId).val()) {
            $jq('#qty-' + productId).val(1);
        } else {
            let qty = parseInt($jq('#qty-' + productId).val());
            let maxlength = parseInt($jq('#qty-' + productId).attr('maxlength'));
            let qtylength = qty.toString().length;
            if (qty < 1) {
                $jq('#qty-' + productId).val(1);
            } else if (qtylength > maxlength) {
                $jq('#qty-' + productId).val(value);
            }
        }
        let qty = parseInt($jq('#qty-' + productId).val());
        if (qty <= 99) {
            let data = {
                product_id: productId,
                qty: qty
            };
            $this.updateCart(data);
        } else {
            $jq('#qty-' + productId).val(value);
        }
    };

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
    };

    this.checkAllProducts = function () {
        $this.showCartLoading();
        let is_checked = $checkbox_all_products.is(":checked");
        let data = {
            "is_checked": is_checked
        };

        $jq.ajax({
            url: CHECK_ALL_PRODUCTS_AJAX_URL,
            method: 'post',
            data: JSON.stringify(data),
            dataType: "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            success: function (response) {
                $this.hideCartLoading();
                if (response.success) {
                    $this.displayCartAndTotals(response);
                }
            },
            error: function () {
            }
        });
    };

    this.goToCheckout = function (e) {
        if (!_grand_total || !_subtotal || $jq(e).hasClass('btn-checkout-disable')) {
            return;
        }
        if (_subtotal.price > 10000) {
            window.location = '/onestepcheckout/index';
        }
    };
    
    this.showMessageError = function(errors){
	let error_html = '';
	if(!fhs_account.isEmpty(errors)){
	    Object.keys(errors).forEach(function(key){
		if(!fhs_account.isEmpty(errors[key])){
		    error_html += '<div class="fhs_redis_cart_msg" style="display: flex;"><div><img src="'+_languages['ico_exclaiming']+'"></div><div class="message">'+errors[key]+'</div></div>'  
		}
	    });
	}
	$jq('.message_error').html(error_html);
    };
};