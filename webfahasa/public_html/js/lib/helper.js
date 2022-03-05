
var Helper = {
    shortenProductName: function(long_name) {
        var name = long_name;
        // /gi Replacing All Matches
        name = name.replace(/&nbsp;/gi, " ");
        name = name.replace(/&amp;/gi, "&");
        name = shorten(name, 50);

        return name;
    },
    shortenProductName_2: function(long_name) {
        var name = long_name;
        // /gi Replacing All Matches
        name = name.replace(/&nbsp;/gi, " ");
        name = name.replace(/&amp;/gi, "&");
        name = shorten(name, 35);

        return name;
    },
    shortenGiftName: function(long_name) {
        var name = long_name;
        // /gi Replacing All Matches
        name = name.replace(/&nbsp;/gi, " ");
        name = name.replace(/&amp;/gi, "&");
        name = shorten(name, 55);

        return name;
    },
    isElementInViewport: function ($element) {
        var elementTop = $element.offset().top + 200;
        var elementBottom = elementTop + $element.outerHeight();
	let $tabslider = $element.parents('.fhs-grid');
	if($tabslider.length > 0){
	    elementTop = Math.floor($tabslider.offset().top) + 200;
	    elementBottom = Math.floor($tabslider.offset().top) + $tabslider.height();
	}
        
        var viewportTop = $jq(window).scrollTop();
        var viewportBottom = viewportTop + $jq(window).height() + 200;
        
        return elementBottom > viewportTop && elementTop < viewportBottom;
    },
    isInViewportMobile: function ($element) {

        let elem = document.querySelector($element);
        var bounding = elem.getBoundingClientRect();
        // vì function trong loop nên cần check các tabs chưa tới viewport hoặc là nó đang hidden (tất cả giá trị điều bằng 0)
        // => ngan load het 
        if (bounding.top == 0 && bounding.left == 0 && bounding.bottom == 0 && bounding.right == 0
                && bounding.width == 0 && bounding.x == 0 && bounding.y == 0) {
            return false;
        }
        return (
                bounding.top >= 0 &&
                bounding.left >= 0 &&
                bounding.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                bounding.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
    },
    getQueryParam: function(param) {
           var query = window.location.search.substring(1);
           var vars = query.split("&");
           for (var i=0;i<vars.length;i++) {
                   var pair = vars[i].split("=");
                   if(pair[0] == param){return pair[1];}
           }
           return(false);
    },
    substractDates: function(now_date_time, future_date_time){
        /// Date Time Format = 2019/02/30 22:02:03
        /// cb is callback function
        
        let now_time = new Date(now_date_time).getTime();
        future_date_time = future_date_time.replace(/-/g, '/');
        let future_time = new Date(future_date_time).getTime();
        let diff = future_time - now_time;
        var days = Math.floor(diff / (1000 * 60 * 60 * 24));
        var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        days  = days > 0 ? days: 0;
        hours  = hours > 0 ? hours: 0;
        minutes  = minutes > 0 ? minutes: 0;
        seconds  = seconds > 0 ? seconds: 0;
        
        return {
            days: days,
            hours: hours,
            minutes: minutes,
            seconds: seconds
        }
    },
    zeroPad: function (n, width, z) {
        z = z || '0';
        n = n + '';
        return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
    },
    printProductHtml: function (product, is_mobile){
        if (!product) {
            return;
        }
        if(!product.image_src.startsWith('http', 0)){
	    if(!product.image_src.startsWith('/', 0)){
		product.image_src = '/'+product.image_src;
	    }   
	}
	    
	let episode = '';
        if(!fhs_account.isEmpty(product.episode)){
	    episode = "<div class='episode-label'>"+product.episode+"</div>";
	}
//        var product_short_name = Helper.shortenProductName(product.product_name);
        var product_short_name = product.product_name;
        let discount_html = Helper.printDiscount(product.discount, is_mobile);
        let price_html = Helper.printPrices(product.display_price, product.display_final_price, episode);
        let rating_html = Helper.printProductRating(product.rating_summary, product.rating_count, is_mobile);
        
        var $item = $jq("<div class='item-inner' style='background-color: #fff;'>"
        // Discount
        + discount_html
        + "<div class='ma-box-content'>"
        // Image
        + "<div class='products clearfix'><div class='product images-container'>"
        + "<a href='/" + product.product_url + "' title='" + product.product_name + "' class='product-image'>"
        + "<div class='product-image' >"
        + "<img class='lazyload' src='" + loading_icon_url + "' data-src='" + product.image_src + "' width='200' height='200' alt='" + product.product_name + "'/></div></a></div></div>"
        // Product Name
        + "<h2 class='product-name-no-ellipsis'  >"
        + "<a href='/" + product.product_url + "' title='" + product.product_name + "' class='product-image'>"
        + product_short_name +"</a></h2>"
        + price_html
        + rating_html
        + "</div></div>");
        
//        if(is_mobile){
//            $item.css('min-height','265px');
//        }
        
        return $item;
    },
    printProductHtmlClone: function (product, is_mobile){
        if (!product) {
            return;
        }
	
        if(!product.image_src.startsWith('http', 0)){
	    if(!product.image_src.startsWith('/', 0)){
		product.image_src = '/'+product.image_src;
	    }
	}
	
	let episode = '';
        if(!fhs_account.isEmpty(product.episode)){
	    episode = "<div class='episode-label'>"+product.episode+"</div>";
	}
        var product_short_name = Helper.shortenProductName(product.product_name);
        let discount_html = Helper.printDiscount(product.discount, is_mobile);
        let price_html = Helper.printPrices(product.display_price, product.display_final_price, episode);
        let rating_html = Helper.printProductRating(product.rating_summary, product.rating_count, is_mobile);
        
        var $item = $jq("<div class='item-inner'>"
        // Discount
        + discount_html
        + "<div class='ma-box-content'>"
        // Image
        + "<div class='products clearfix'><div class='product images-container'>"
        + "<a href='/" + product.product_url + "' title='" + product.product_name + "' class='product-image'>"
        + "<div class='product-image' >"
        + "<img class='lazyload' src='" + loading_icon_url + "' data-src='" + product.image_src + "' width='200' height='200' alt='" + product.product_name + "'/></div></a></div></div>"
        // Product Name
        + "<h2 class='product-name-no-ellipsis' style='height: auto;'>"
        + "<a href='/" + product.product_url + "' title='" + product.product_name + "' class='product-image'>"
        + product_short_name +"</a></h2>"
        + price_html
        + rating_html
        + "</div></div>");
        
        if(is_mobile){
//            $item.css('min-height','265px');
        }
        
        return $item;
    },
    printDiscount: function(discount, is_mobile){
        let discount_html = "";
        
        if(parseInt(discount)>0){
            if (is_mobile) {
                discount_html = "<div style='margin-left: 20px;' class='m-label-pro-sale'><span class='p-sale-label m-discount-l-fs'>" 
                        + discount + "%</span></div>";
            } else {
                discount_html = "<div class='label-pro-sale'><span class='p-sale-label discount-l-fs'>" 
                        + discount + "%</span></div>";
            }
        }
        
        return discount_html;
    },
    printPrices: function(price, final_price, spisode = ''){
        let price_html = "";
        if(parseInt(final_price)>0 && final_price != price){
            price_html = "<div class='price-label'><p class='special-price'><span class='price m-price-font'>" 
                + final_price + "</span></p><p class='old-price'><span class='price m-price-font'>" 
                + price + "</span></p>"
		+ spisode +"</div>";
        }else if(parseInt(price)>0){
            price_html = "<div class='price-label'><p class='special-price'><span class='price m-price-font'>" 
                + price + "</span></p></div>";
        }
        
        return price_html;
    },
    printProductRating: function(rating_summary, rating_count, is_mobile){
        if(!rating_count || rating_count == "0" || !rating_summary || rating_summary == "" || is_mobile){
            rating_count = 0;
            rating_summary = 0;
        }
        
        let rating_html = "<div class='fhs-rating-container'><div class='ratings fhs-no-mobile-block'><div class='rating-box'>"
                    + "<div class='rating' style='width:" + rating_summary + "%'></div></div>"
                    + "<div class='amount'>(" + rating_count + ")</div></div></div>";

        return rating_html;
    },
    shuffle: function(product_list) {
        // Random item in array.
        let ctr = product_list.length, temp, index;
        while (ctr > 0) {
            index = Math.floor(Math.random() * ctr);
            ctr--;
            temp = product_list[ctr];
            product_list[ctr] = product_list[index];
            product_list[index] = temp;
        }
        
        return product_list;
    },
    formatCurrency: function (value) {
        value = Math.round(value); /// Example: 123000.000 -> 123000
        //value = String(value).replace(/(?<!\..*)(\d)(?=(?:\d{3})+(?:\.|$))/g, '$1.'); /// -> 123.000
	value = String(value).replace(/(.)(?=(\d{3})+$)/g,'$1.'); /// -> 123.000        

        return value + " đ";
    },
    printProductVerticallyHtml: function (product, is_mobile, flagPersonal = false){
        if (!product) {
            return;
        }
        
        if(!product.image_src.startsWith('http', 0)){
	    if(!product.image_src.startsWith('/', 0)){
		product.image_src = '/'+product.image_src;
	    }   
	}
	
        // flagPersonal : render product dang thuoc type la personalization 
        // => them url? fhs_campaign=PERSONALIZE_PRODUCT
        let product_url= ''
        if(flagPersonal === true ){
            product_url = product.product_url + "?fhs_campaign=PERSONALIZE_PRODUCT";
        }else{
            product_url = product.product_url; 
        }
        
	let episode = '';
        if(!fhs_account.isEmpty(product.episode)){
	    episode = "<div class='episode-label'>"+product.episode+"</div>";
	}
	
        var product_short_name = (product.product_name);
        let discount_html = Helper.printDiscount(product.discount, is_mobile);
        let price_html = Helper.printPrices(product.display_price, product.display_final_price, episode);
        let rating_html = Helper.printProductRating(product.rating_summary, product.rating_count, is_mobile);
        var $item = $jq("<div class='item-inner ' style='position: relative;'>"
        // Discount
        + discount_html
        + "<div class='ma-box-content' >"
        // Image
        + "<div class='products clearfix'><div class='product images-container'>"
        + "<a href='/" + product_url + "' title='" + product.product_name + "' class='product-image'>"
        + "<div class='product-image' >"
        + "<img class='lazyload' src='" + loading_icon_url + "' data-src='" + product.image_src + "' alt='" + product.product_name + "'/></div></a></div></div>"
        // Product Name
        + "<h2 class='product-name-no-ellipsis' style='height: 2.6em;'>"
        + "<a href='/" + product_url + "' title='" + product.product_name + "' class='product-image'>"
        + product_short_name +"</a></h2>"
        + price_html
        + rating_html
        + "</div></div>");
        
//        if(is_mobile){
//            $item.css('min-height','265px');
//        }
        
        return $item;
    },
    numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    },
    removeDiacritics(str){
        return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/đ/g, "d").replace(/Đ/g, "D");
    }
    
};
