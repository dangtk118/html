//<![CDATA[    
function AddToCartOnListProduct() {
    //'.category-products .btn-cart, .crosssell .btn-cart,.cms-home .btn-cart'
    //var is_view = $jq('#product_addtocart_form').attr('method');
    var is_list_compare = $jq('.catalog-product-compare-index').length;
    var is_checkout_page = $jq('.checkout-cart-index').length;
    var is_wishlist_page = $jq('.wishlist-index-index').length;
    if(is_list_compare >0 || is_checkout_page >0 || is_wishlist_page>0) return false;
    $jq('.btn-cart').each(function(){
        var linkToCart = $jq(this).attr('onclick');
        var effectToCart = $jq('.effect_to_cart').attr('value');
        if(linkToCart){
            var productId = linkToCart.replace("setLocation('","").replace("')","");
            //$jq(this).attr('name',linkToCart);
            $jq(this).attr('onclick', '');
            $jq(this).on('click',function(e){                
                //getProductInfoFromCart(linkToCart,'type_product=1');
                var base_url = $jq('#ajaxconfig_info a').attr('href');
                linkToCart = constructAddToCartUrl(productId);
                if(linkToCart.search('checkout/cart/add')!= -1 || linkToCart.search('ajaxcartsuper/ajaxcart/add') !=-1) {
                    linkToCart =  linkToCart.replace('checkout/cart', 'rediscart/add');
                    ajaxToCart(linkToCart,"",$jq(this));
                    var img = $jq(this).closest('li').find('img:first');
                    if(!img.length) {
                        img = $jq(this).closest('.actions').parent().find('.product-image');
                        if(!img.length){
                            img = $jq(this).closest('.actions').parent().parent().find('.product-image');
                        }
                        if(!img.length){
                            img = $jq(this).closest('.actions').parent().parent().parent().find('.product-image');
                        }
                        if(!img.length){
                            img = $jq(this).closest('.actions').parent().parent().parent().parent().find('.product-image');
                        }
                        if(!img.length){
                            img = $jq(this).parent().parent().parent().parent().parent().find('.product-image');
                        }
                        if(!img.length){
                            img = $jq(this).parent().parent().parent().parent().parent().parent().find('.product-image');
                        }
                        if(!img.length){
                            img = $jq(this).parent().parent().parent().parent().parent().parent().parent().find('.product-image');
                        }
                        if(!img.length){
                            img = $jq(this).parent().parent().parent().parent().parent().parent().parent().parent().find('.product-image');
                        }

                                            if(!img.length){
                            img = $jq(this).parent().parent().parent().parent().parent().parent().parent().parent().parent().find('.product-image');
                        }
                    }
                    if(effectToCart==1) {
                        flyToCart($jq(img), $jq('.top-link-cart'));
                    }

                } else {
                    location.href = linkToCart; 
					//$jq(this).target = "_blank";
					//window.open(linkToCart);
					//window.open(linkToCart,'_newtab');
                    return false;
                }
                e.stopPropagation();
            // ajaxToCart(linkToCart,"",$jq(this));
            });
        }
    });
    shortCartProductName();
}

function shortCartProductName(){
    $jq("#mini_cart_block .product-name a").each(function(index, value){
        var name = shorten(value.text, 30);
        value.text = name;
    }); 
}

function AddToCartOnProductView() {
    var is_view = $jq('#product_addtocart_form').attr('method');
    var effect_to_cart = $jq('.effect_to_cart').attr('value');
    if(is_view) {
        productAddToCartForm.submit = function(button,url){
            if(this.validator && this.validator.validate()){
                var form = this.form;
                var oldUrl = form.action;
		let is_buyNow = "open_box";
		let this_button = $jq(button);
		if(this_button.attr('is_buyNow')){
		    is_buyNow = this_button.attr('is_buyNow');
		}
                if (url) {
                    form.action = url;
                }
                var e = null;
                // ajax code
                if(!url){
                    url = $jq('#product_addtocart_form').attr('action');
                }
                var data = $jq('#product_addtocart_form').serialize();
                //fly to basket
                
                var img = $jq('#product_addtocart_form').find('.product-img-box .product-image img');
                if(effect_to_cart==1) {
                    if($jq('#ajax_cart_super_product_view').attr('class')!='popup') {
                        flyToCart($jq(img), $jq('.top-link-cart'));
                    }
                }
                
                var fileInput = $jq('#product_addtocart_form input[type="file"]');
                if(fileInput.length>0) {
                    var file = fileInput[0].files[0];
                }
                
                if(!file) {
                    ajaxToCart(url,data,'view',is_buyNow);
                } else {
                    //$jq('#product_addtocart_form').attr('target', '_blank')
                    form.submit();
                }

                
                //End ajax code
                this.form.action = oldUrl;
                if (e) {
                    throw e;
                }
            }
            return false;
        }
    }
}

function getProductIdFrom(url) {
    if(url.search('form_key')!=-1) {
        var a = url.search('/form_key/');
        var p = url.search('/product/');
        var form_key= url.substring(p,a); 
        var product_id = form_key.replace('/product/','');
        return product_id;
    }
    var myURLArray = url.split('/');
    var lastChunk = '';
    while (myURLArray.length > 0 && lastChunk == '') {
      lastChunk = myURLArray.pop();
    }
    return lastChunk;
}


function getProductInfoFromCart(linkToCart) {
	
    $jq.ajax({
        url: linkToCart,
        type: 'GET',
        data: {},
        beforeSend: function(){
            showLoadingAnimation();
        },
        success: function(data) {
            hideLoadingAnimation();
            var htmlObject = $jq(data);
            var formCart = htmlObject.find('.main-container').html();
            //console.log(formCart);
            showProductOption(formCart);
            return false;
        }
    });
}

//compare product
function addProductCompare() {
    $jq('ul.add-to-links li a.link-compare').each(function(){
        var compareUrl = $jq(this).attr('href');
        compareUrl = constructAddToCompare(compareUrl)
        if(compareUrl.search('product_compare/add/product')!=-1){
             $jq(this).bind('click',function(){
                ajaxToCart(compareUrl,'','');
                return false;
            });
        }
    });
}
//wishlist product
function addProductToCartFromWishlist() {
      $jq('li .link-cart').each(function(){
        var addToCartWishlistUrl = $jq(this).attr('href');
        $jq(this).bind('click',function(){
                ajaxToCart(addToCartWishlistUrl,'','');
            return false;
        });
    });
}

function addProductWishlist() {
     $jq('a.link-wishlist').each(function(){
        var wishlistUrl = $jq(this).attr('href');
        wishlistUrl = constructAddWishlist(wishlistUrl);
        if(wishlistUrl.search('wishlist/index/add/product')!=-1){
        $jq(this).bind('click',function(){
            var isLogin = $jq('#ajaxconfig_info input').attr('value');
            if(isLogin !=1){
				 location.href = wishlistUrl;
                return false;
            }
            ajaxToCart(wishlistUrl,'','');
            return false;
        });
       }
    });
}

function addToWishlistCompareOnProductView() {
    var haveLogin = $jq('#ajaxconfig_info input').attr('value');
    if(haveLogin ==1){
        $jq('.product-view .link-wishlist').removeAttr('onclick');
    }
    
    $jq('.product-view .link-wishlist, .product-view .link-compare').bind('click',function(){
        var link = $jq(this).attr('href');
	let is_show = 'open_box';
	if($jq(this).attr('is_show')){
	    is_show = $jq(this).attr('is_show');
	}
        ajaxToCart(link,'','',is_show);
        return false;
    });
}

function removeCompareProductLink(){

      $jq('#compare-items li .btn-remove').each(function(){
        var removeCompareUrl = $jq(this).attr('href');
        try {
            if(removeCompareUrl.search('product_compare/remove/product')!=-1) {
            $jq(this).removeAttr('href');
            $jq(this).removeAttr('onclick');
            $jq(this).on('click',function(){ 
                var confirm_content = $jq('.confirm_delete_product').attr('value');
                 if(confirm(confirm_content)){
                      ajaxToCart(removeCompareUrl,'','');
                 };
                return false;
            });
        }
        } catch (exception) { 
        }
    });
}

function removeWislishProductLink(){
      $jq('#wishlist-sidebar li .btn-remove').each(function(){
        var removeWishlistUrl = $jq(this).attr('href');
        if(removeWishlistUrl.search('wishlist/index/remove')!=-1) {
        $jq(this).attr('href','javascript:void(0)');
        $jq(this).removeAttr('onclick');
        $jq(this).on('click',function(){
             var confirm_content = $jq('.confirm_delete_product').attr('value');
             if(confirm(confirm_content)){
                 ajaxToCart(removeWishlistUrl,'','');
             }
            return false;
        });
        }
    });
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
    //$jq('.header-container').append(loading);
}

function showLoadingAnimationWishlist(){
    var loading_bg = $jq('#ajaxconfig_info button').attr('name');
    var opacity = $jq('#ajaxconfig_info button').attr('value');
    var loading_image = $jq('#ajaxconfig_info img').attr('src');
    var style_wrapper =  "position: fixed;top:0;left:0;filter: alpha(opacity=70); z-index:99999;background-color:"+loading_bg+"; width:100%;height:100%;opacity:"+opacity+"";
    var loading = '<div id ="wraper_ajax_wishlist" style ="'+style_wrapper+'" ><div  class ="loadding_ajaxcart_wishlist" style ="z-index:999999;position:fixed; top:50%; left:50%;"><img src="'+loading_image+'"/></div></div>';
    $jq('.my-wishlist').append(loading);
    //$jq('.header-container').append(loading);
}

function showBoxInfo(product_info) {
    var base_url = $jq('#ajaxconfig_info a').attr('href');
    var cart_url = base_url+ 'checkout/cart'
    var title_shopping_cart = $jq('.title_shopping_cart').attr('value');
    var title_shopping_continue = $jq('.title_shopping_continue').attr('value');
    var title_add_to_cart = $jq('.title_add_to_cart').attr('value');
    var  str = "<div class ='wrapper_box'>";
    str += "<div><p class ='info'>"+title_add_to_cart+"</p></div>";
    str += "<div id ='product_info_box'>"+product_info+"</div>";
    str += "<div><a href= 'javascript:void(0)'  id ='continue_shopping'>"+title_shopping_continue+"</a></div>";
    str += "<div><a href='"+cart_url+"'  id ='shopping_cart'>"+title_shopping_cart+"</a></div></div>";
    //$jq('.loadding_ajaxcart').html(str);
    $jq(str).insertAfter('#wraper_ajax');
    $jq('#wraper_ajax').css('opacity',0.8);
}

function showBoxInfoWishlist(product_info) {
    var base_url = $jq('#ajaxconfig_info a').attr('href');
    var cart_url = base_url+ 'wishlist/'
    var str = "<div class ='wrapper_box pop_wishlist1'>";
    var title_shopping_continue = $jq('.title_shopping_continue').attr('value');
    var title_shopping_wishlist = $jq('.title_shopping_wishlist').attr('value');
    var title_add_to_wishlist = $jq('.title_add_to_wishlist').attr('value');
    str += "<div><p class ='info'>"+title_add_to_wishlist+"</p></div>";
    str += "<div id ='product_info_box'>"+product_info+"</div>";
    str += "<div><a href= 'javascript:void(0)'  id ='continue_shopping'>"+title_shopping_continue+"</a></div>";
    str += "<div><a href='"+cart_url+"' id='shopping_cart'  >"+title_shopping_wishlist+"</a></div></div>";
    $jq('.loadding_ajaxcart').html(str);
    $jq(str).insertAfter('#wraper_ajax');
    $jq('#wraper_ajax').css('opacity',0.8);
}

function showBoxInfoCompare(product_info) {
    var base_url = $jq('#ajaxconfig_info a').attr('href');
    var cart_url = base_url+ 'catalog/product_compare/index/'
    var str = "<div class ='wrapper_box pop_compare1'>";
    var title_shopping_continue = $jq('.title_shopping_continue').attr('value');
    var title_shopping_compare = $jq('.title_shopping_compare').attr('value');
    var title_add_to_compare = $jq('.title_add_to_compare').attr('value');
    str += "<div><p class ='info'>"+title_add_to_compare+"</p></div>";
    str += "<div id ='product_info_box'>"+product_info+"</div>";
    str += "<div><a href= 'javascript:void(0)'  id ='continue_shopping'>"+title_shopping_continue+"</a></div>";
    str += "<div><a id='shopping_cart' target='_blank' href='"+cart_url+"'>"+title_shopping_compare+"</a></div></div>";
 
    $jq('.loadding_ajaxcart').html(str);
    $jq(str).insertAfter('#wraper_ajax');
    $jq('#wraper_ajax').css('opacity',0.8);
}

function showProductOption(product_info) {
    var str = "<div class ='wrapper_box pop_up_product_option'>";
        str += product_info ;
        str +='</div>' ;
    $jq('.loadding_ajaxcart').html(str);
    $jq(str).insertAfter('#wraper_ajax');
    $jq('#wraper_ajax').css('opacity',0.8);
    $jq('#productHaveOption').html(product_info);

}

function hideBundleOptionChoose(){
    // handle hidden bundle-dinamic-options 
    $jq(".bundle-dinamic-options").removeClass("active-block");
    $jq('.youama-ajaxlogin-cover').fadeOut();
    $jq("body").removeClass("overflow-none");
}

function hideLoadingAnimation() {
    $jq('.loadding_ajaxcart,#wraper_ajax,.wrapper_box').remove();

}

function showMiniAjaxCart() {
    $jq('#mini_cart_block').show();
}

function hideMiniAjaxCart() {
    $jq('#mini_cart_block').slideUp()
    $jq('#mini_cart_block').hide();
}

function changeDelelteUrl() {
    var str = '<script type ="text/javascript">\n\
                                   $jq("#cart-sidebar a.btn-remove").each(function(){\n\
                                        var delUrl = $jq(this).attr("href");\n\
                                        $jq(this).attr("href","#"); \n\
                                        $jq(this).on("click",function(){\n\
                                            $jq(this).attr("onclick",ajaxToCart(delUrl,"","view"));\n\
                                                return false;                               \n\
                                        });   \n\
                                });\n\
                                \n\
                    </script>';
    return str;
}

function receive(saveData) {
    if (saveData == null) {
            alert("DATA IS UNDEFINED!");  // displays every time
    }
    console.log("Success is " + saveData);  // 'Success is undefined'
}


function ajaxToCart(url,data,mine,is_buyNow = "open_box", is_logined = false) {
    if(IS_REQUIRE_LOGIN && !is_logined){
	let add_to_cart_data = {};
	add_to_cart_data['url'] = url;
	add_to_cart_data['data'] = data;
	add_to_cart_data['mine'] = mine;
	add_to_cart_data['is_buyNow'] = is_buyNow;
	if(!fhs_account.isLogin(add_to_cart_data)){return;}
    }
    
	var using_ssl = $jq('.using_ssl').attr('value');
    url = url.replace('checkout/cart', 'rediscart/cart');
	  //cross domain request possible fix
    url = url.replace(/http[^:]*:/, document.location.protocol);
	if(using_ssl==1) { 
		url = url.replace("http://", "https://");
	}
    try {
        $jq.ajax({
            url: url,
            dataType: 'jsonP',
            type : 'post',
            data : data,
            crossDomain: true,
            beforeSend: function(){
                showLoadingAnimation();
   
            },
            success: function(data){
                if(data.status==1) {
                    var base_url = $jq('#ajaxconfig_info a').attr('href');
                    
                    if(data.sidebar_cart) {
                        $$('.sidebar .block-cart').each(function (el){
                            el.replace(data.sidebar_cart);
                        });
                        if(mine=='view') {
                             if($jq('#ajax_cart_super_product_view').attr('class')=='popup') {
                                 window.parent.insertContentToParent('.sidebar .block-cart',data.sidebar_cart);
                                 window.parent.deleteCartInSidebar();        
                            }
                        }
                    }
                    if(data.top_link){
                        var topCartContent =    $jq(data.top_link).find('.top-link-cart').html();
                        $jq('.top-link-cart').html('');
                        $jq('.top-link-cart').html(topCartContent);
                        if(mine=='view') {
                             if($jq('#ajax_cart_super_product_view').attr('class')=='popup') {
                                window.parent.insertContentTopLinkToParent('.quick-access ul.links',data.top_link);
                             }
                        }
                    }
                    //show minicart
                    if(data.mini_cart) {
                        $jq('#mini_cart_block').html('');
                        $jq('#mini_cart_block').html($jq(data.mini_cart).find("div#cart"));
                    }
                    
                    if(data.checkout_cart){
                        $jq('.col-main .cart').html('');
                        $jq('.col-main .cart').append(data.checkout_cart);
                    }  
                    
             
                    //compare type
                    if(data.type_sidebar == 'compare'){
                        $$('.sidebar .block-compare').each(function (el){
                            el.replace(data.sidebar_compare);
                        });
						removeCompareProductLink();
                        if(data.product_info) {
                            showBoxInfoCompare(data.product_info);
                            return false;
                        }else {
                            hideLoadingAnimation();
                        }
                    }
                    
                    if(data.type_sidebar == 'wishlist'){
			setTimeout(function(){$jq(window).trigger("netcore_event_add_to_wishlist");},100);
                        
			if($jq('.wishlist-index-index').length) {
			    var wishlist_url = location.href;
			    if(using_ssl==1) { 
				    wishlist_url = wishlist_url.replace("http://", "https://");
			    }	
			    showLoadingAnimationWishlist();
			    $jq.get( wishlist_url, function( data ) {
				    var form_wishlist = $jq(data).find('.my-wishlist').html();
				    $jq('.my-wishlist').html(form_wishlist);
			    });
			}
                        if(!$jq('.sidebar .block-wishlist').length){
                            $jq('<div class="block block-wishlist"></div>').insertAfter('.sidebar .block-cart');
                        }
                        $$('.sidebar .block-wishlist').each(function (el){
                            el.replace(data.wishlist_sidebar);
                        });
						
                    	$$('.quick-access ul.links').each(function (el){
                            el.replace(data.top_link);
                        });
                        
			try{
			    $jq('.link-wishlist-icon').addClass("active");
			}catch(ex){}
			
			removeWislishProductLink();
			
			if(is_buyNow == "open_box"){
			    if(data.product_info) {
				if(is_logined){
				    hideLoadingAnimation();
				    fhs_account.showAlert(fhs_account.languages['add_to_cart_success']);
				    window.location.reload();
				}else{
				    showBoxInfoWishlist(data.product_info);
				}
				return false;
			    }else {
				hideLoadingAnimation();
			    }
			}else{
			    hideLoadingAnimation();
			}
                    }else{
			setTimeout(function(){$jq(window).trigger("suggestion_event_add_to_cart");},100);
			setTimeout(function(){$jq(window).trigger("enhanced_ecom_add_to_cart");},100);
			setTimeout(function(){$jq(window).trigger("netcore_event_add_to_cart");},100);
                    }
		    
		    if(is_buyNow == "true"){
			location.href = "/checkout/cart/";
		    }else if(is_buyNow == "open_box"){
			if(data.product_info) {
			    if(is_logined){
				hideLoadingAnimation();
				fhs_account.showAlert(fhs_account.languages['add_to_cart_success']);
				window.location.reload();
			    }else{
				showBoxInfo(data.product_info);
			    }
			}else {
			    hideLoadingAnimation();
			}
		    }else{
			hideLoadingAnimation();
		    }
                } else { 
                    var base_url = $jq('#ajaxconfig_info a').attr('href');
                    if(data.status==0){ 
                        if(!confirm(data.message.replace("<br/>", ""))){
                            hideLoadingAnimation();
                            return false;
                        }
                        hideLoadingAnimation();
                        if(data.url_wislist) {
                            location.href = data.url_wislist;
                            return false;
                        }
                    }
                    if(data.type_product_ajax==1){
                        location.href = url;
                        return false;
                    }
                    //When error attempt to redirect to checkout/cart page
                    location.href = base_url + "checkout/cart";
                    return false;                    
                }
                deleteCartInSidebar();
            //parent.$jq.fn.fancybox.close();
            return false;
            },
            error: function(request, sta, error){
                var base_url = $jq('#ajaxconfig_info a').attr('href');
//                location.href = base_url + "checkout/cart";
		if(is_logined){
		    window.location.reload();
		}
                return false;                    
            }   
        });
    } catch (e) {
        alert('erreror here')
        setLocation(url);
    }
   
}


  // fly to basket  
  function flyToCart(flyer, flyingTo, callBack) {
      try {
        var $jqfunc = $jq(this);
        var divider = 3;
        var flyerClone = $jq(flyer).clone();
        $jq(flyerClone).css({
            position: 'absolute',
            top: $jq(flyer).offset().top + "px",
            left: $jq(flyer).offset().left + "px",
            opacity: 1,
            'z-index': 1000
        });
        $jq('body').append($jq(flyerClone));
        if($jq(flyingTo)) {
            var gotoX = $jq(flyingTo).offset().left + ($jq(flyingTo).width() / 2) - ($jq(flyer).width()/divider)/2;
            var gotoY = $jq(flyingTo).offset().top + ($jq(flyingTo).height() / 2) - ($jq(flyer).height()/divider)/2;
            $jq(flyerClone).animate({
                opacity: 0.7,
                left: gotoX,
                top: gotoY,
                width: 135,
                height: 135
            }, 1000,
            function () {
                $jq(flyingTo).fadeOut('slowly', function () {
                    $jq(flyingTo).fadeIn('slowly', function () {
                        $jq(flyerClone).fadeOut('slowly', function () {
                            $jq(flyerClone).remove();
                            if( callBack != null ) {
                                callBack.apply($jqfunc);
                            }
                        });
                    });
                });
            });
        }
    
    } catch (exception) { 
    
    }    
}

function insertContentToParent(element,data) {
     $$('.sidebar .block-cart').each(function (el){
        el.replace(data);
    });
    //$jq('.sidebar').append(changeDelelteUrl());
    return false;
}

function insertContentTopLinkToParent(element,data) {
     $$(element).each(function (el){
        el.replace(data);
    });
    return false;
}

function insertContentMiniCartToParent(element,data) {
    $jq(element).html('');
    $jq(element).append(data);
    $jq('#mini_cart_block').show();
    deleteCartInSidebar();
    return false;
}


//delete product out of cart in checkout page
function deleteCartInCheckoutPage(){ 
    $jq('.checkout-cart-index a.btn-remove').removeAttr('onclick');
//    $jq(".checkout-cart-index a.btn-remove2,.checkout-cart-index a.btn-remove").click(function(event) {
//        event.preventDefault();
//        var confirm_content = $jq('.confirm_delete_product').attr('value');
//        if(!confirm(confirm_content)){
//            return false;
//        }
//         var delUrl = $jq(this).attr("href");
//            delUrl = delUrl.replace("checkout/cart/delete", "ajaxcartsuper/index/cartdelete");
//			
//		var using_ssl = $jq('.using_ssl').attr('value');
//		  //cross domain request possible fix
//		delUrl = delUrl.replace(/http[^:]*:/, document.location.protocol);
//		if(using_ssl==1) { 
//			delUrl = delUrl.replace("http://", "https://");
//		}
//		
//        $jq.ajax({
//            url: delUrl,
//			dataType: 'jsonP',
//			crossDomain: true,
//            type: 'POST',
//            data: {},
//            beforeSend:function(){
//               showLoadingAnimation();
//            },
//            success: function(xhr) {
//				var form_cart = xhr['form_cart'];
//				var top_cart = '<div class ="top-cart-contain">'+xhr["top_cart"]+'</div>';
//                $('ajaxcart-checkout-content').innerHTML = form_cart;
//                $('ajaxcart-checkout-content').insert(form_cart);
//                var cart_content = $('ajaxcart-checkout-content').down('.cart_content').innerHTML;
//                 $jq('.top-link-cart').html(cart_content);
//                 $$('.top-cart-contain').each(function (el){
//                     el.replace(top_cart);
//                 });
//                var full_cart_content = $('ajaxcart-checkout-content').down('.ajaxcart_checkout_content').innerHTML;
//                $$('.cart').each(function (el){
//                    el.replace(full_cart_content);
//                });
//                hideLoadingAnimation();
//                $jq('#ajaxcart-checkout-content').html('');
//            },
//			complete: function(xhr) {
//				getQuote();
//				getDiscountCodes();
//				slideEffectAjax();
//			}
//        });
//        
//    });
   
    return false;
}

function getDiscountCodes() {
    $jq('#discount-coupon-form').attr('id','discount-coupon-form-ajax');
    var discountFormAjax = new VarienForm('discount-coupon-form-ajax');
    discountForm.submit = function (isRemove) {
        if (isRemove) {
            $('coupon_code').removeClassName('required-entry');
            $('remove-coupone').value = "1";
        } else {
            $('coupon_code').addClassName('required-entry');
            $('remove-coupone').value = "0";
        }
        return VarienForm.prototype.submit.bind(discountFormAjax)();
    }
}

function getQuote() {
    $jq('#shipping-zip-form').attr('id','shipping-zip-form-ajax');
    var coShippingMethodFormAjax = new VarienForm('shipping-zip-form-ajax');
    coShippingMethodForm.submit = function () {
        var country = $F('country');
        var optionalZip = false;

        for (i=0; i < countriesWithOptionalZip.length; i++) {
            if (countriesWithOptionalZip[i] == country) {
                optionalZip = true;
            }
        }
        if (optionalZip) {
            $('postcode').removeClassName('required-entry');
        }
        else {
            $('postcode').addClassName('required-entry');
        }
        if (this.validator.validate()) {
            this.form.submit();
        }
        console.log(countriesWithOptionalZip.length);
    }.bind(coShippingMethodFormAjax);
}

function slideEffectAjax() {
      $jq('.top-cart-contain').mouseenter(function() {
            $jq(this).find(".top-cart-content").stop(true, true).show();
        });
        //hide submenus on exit
        $jq('.top-cart-contain').mouseleave(function() {
            $jq(this).find(".top-cart-content").stop(true, true).hide();
        });
}

function deleteCartInSidebar() {
    var is_checkout_page = $jq('.checkout-cart-index').length;
    if(is_checkout_page>0) return false;
    $jq('#cart-sidebar a.btn-remove, #mini_cart_block a.btn-remove').each(function(){
        var delUrl = $jq(this).attr('href');
        $jq(this).attr('href','#');
        $jq(this).attr('onclick','');
        if(delUrl.search('checkout/cart/delete')!=-1) {
            $jq(this).on('click',function(){
                  var confirm_content = $jq('.confirm_delete_product').attr('value');
                  if(confirm(confirm_content)){
                        $jq(this).attr('onclick',ajaxToCart(delUrl,'','view'));
                 };
                return false;
            });              
        }
    });
    shortCartProductName();
}  

$jq(document).ready(function(){
    var enable_module = $jq('#enable_module').val();
    if(enable_module==0 || !enable_module) return false;
    //add Class to wishlist link 
    $jq('.quick-access ul li a').each(function(){
        var link = $jq(this).attr('href');
        if(link.search('/wishlist/')!=-1){
            $jq(this).addClass('top_link_wishlist');
        }
    });
    var checkout_url = $jq('.top-link-checkout').attr('href')+'onepage';
    $jq('.top-link-checkout').attr('href',checkout_url);
    //delete product out of cart
    deleteCartInSidebar();
    //delete product out of cart in checkout page
    deleteCartInCheckoutPage();
    //compare link
    addProductCompare();
    removeCompareProductLink();
      
    //wishlist link 
    addToWishlistCompareOnProductView();
    addProductWishlist();
    //addProductToCartFromWishlist();
    removeWislishProductLink();
    //hideMiniAjaxCart();
    //add product to cart on list product 
    AddToCartOnListProduct();
    //Add to cart in product view page
    AddToCartOnProductView();
    //hover on link cart 
//    $jq('.top-link-cart').attr('href','javascript:void(0)')
//    $jq('.top-link-cart').live('click',function(){
//        $jq('#mini_cart_block').slideToggle('slowly')
//    })
    $jq('body').on('click', '#continue_shopping, #shopping_cart', function(){
        hideLoadingAnimation();
        $jq('#mini_cart_block').show();
        if($jq('#ajax_cart_super_product_view').attr('class')=='popup') {
            parent.$jq.fancybox.close();
        }
        
        // handle hidden bundle-dinamic-options 
        hideBundleOptionChoose();
    }); 
    

    $jq('#wraper_ajax').on('click',function(){
        $jq('#wraper_ajax, .wrapper_box').remove();
    })
    //hide mini cart on popup
    $jq('.ajaxcartsuper-index-productview #mini_cart_block').hide();
    //hover on mini cart 
    slideEffectAjax();
});

$jq(document).ajaxComplete(function(){
    var enable_module = $jq('#enable_module').val();
    if(enable_module==0 || !enable_module) return false;
    //hide mini cart on popup
    $jq('.ajaxcartsuper-index-productview #mini_cart_block').hide();
    AddToCartOnListProduct();
    deleteCartInSidebar();
    removeCompareProductLink();
    removeWislishProductLink();
    addProductToCartFromWishlist();
    addProductCompare();
    addProductWishlist();
    //deleteCartInCheckoutPage();

})

//]]>
