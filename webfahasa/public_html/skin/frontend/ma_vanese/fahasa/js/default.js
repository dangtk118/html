$jq(window).load(function () {
    checkCOD();
    if (window.Touch) {
        $jq('.product-view button.btn-cart, #back-top').bind('touchstart', function (e) {
            e.preventDefault();
        });
        $jq('.product-view button.btn-cart, #back-top').bind('touchend', function (e) {
            e.preventDefault();
            return $jq(this).trigger('click');
        });
    }
    $jq("#billing\\:country_id").on('change', function (event) {
        checkCOD();
    });
    $jq("#shipping\\:country_id").on('change', function (event) {
        checkCOD();
    });
    $jq(".button-search").bind("click", function () {
        $jq("form#search_mini_form_desktop").submit();
    });
    $jq("section#page").bind("click", function () {
        $jq(".top-cart-content").hide();
    });
    $jq("img.lang-flag-img").bind("click", function (event) {
        var tDom = event.target;
        var langValue = $jq(tDom).attr("lang");
        if (typeof langValue === 'undefined' || langValue === null || langValue === "") {
            langValue = 'default';
        } else if (langValue !== "default" && langValue !== "english" && langValue !== "japan") {
            langValue = 'default';
        }
        setCookie("store", langValue, 10);
        location.reload();
    });
    $jq(".lang-flag-img-target").click(function(e){
	let langValue = $jq(e.target).attr("lang");
	if (typeof langValue === 'undefined' || langValue === null || langValue === "") {
            langValue = 'default';
        } else if (langValue !== "default" && langValue !== "english" && langValue !== "japan") {
            langValue = 'default';
        }
        setCookie("store", langValue, 10);
        location.reload();
    });

});
function checkCOD() {
    var billingCountry = $jq("#billing\\:country_id").val();
    var shippingCountry = $jq("#shipping\\:country_id").val();
    var shipping_equal_billing = $jq("#billing\\:use_for_shipping").val();
    if (shipping_equal_billing == 1) {
        if (billingCountry == "VN") {
            enableCOD();
        } else {
            disableCOD();
        }
    } else {
        if (shippingCountry == "VN") {
            enableCOD();
        } else {
            disableCOD();
        }
    }
}
function disableCOD() {
    $jq("input#p_method_cashondelivery").prop("disabled", true);
    $jq("input#p_method_cashondelivery").prop("checked", false);
    $jq("input#p_method_cashondelivery").next().css("opacity", "0.3");
}
function enableCOD() {
    $jq("input#p_method_cashondelivery").prop("disabled", false);
    $jq("input#p_method_cashondelivery").next().css("opacity", "1");
}
function shorten(text, maxlength) {
    var rel = text;
    if (rel.length > maxlength) {
        rel = rel.substring(0, maxlength - 3) + "...";
    }
    return rel;
}
function handleNoMobileBanner() {
    $jq(".fhs-no-mobile img").each(function (i, val) {
        var img = $jq(val);
        var src = img.attr('data-src');
        if (src !== '') {
            img.attr('src', src).one('load', function () {
                $jq(this).removeAttr('data-src');
            });
        }
    });
    $jq("a.first-3-banner-no-mobile").each(function (i, val) {
        $jq(this).wrap("<div class='col-sm-3 col-md-3 col-xs-12 fhs-no-mobile-block'><div class='banner-home-inner fhs-no-mobile'></div></div>");
    });
}
function chooseActive(Dom) {
    var item = 0;
    for (var i = Dom.length; i--; ) {
        var a = Dom[i];
        if (a.className.indexOf('active') > -1) {
            item = i;
        }
    }
    var choose = Dom[item];
    choose.classList.add("active");
    return choose;
}

function loadImages(currentS) {
    var slides = currentS.find('li.item');
    var view = currentS.closest('.bx-viewport')[0].getBoundingClientRect();
    var size = slides.size();
    for (var i = 0; i < size; i++) {
        curSlide = slides.eq(i);
        if (isElementInView(curSlide, view)) {
            curSlide.find('img').each(function (i, e) {
                var img = $jq(e);
                var imgsrc = img.attr('data-src');
                if (imgsrc && imgsrc !== '') {
                    //img.hide().attr('src', imgsrc).fadeIn("slow").removeAttr('data-src').removeClass('flazy');
                    img.attr('src', imgsrc).one('load', function () {
                        $jq(this).removeAttr('data-src').removeClass('flazy');
                    });
                }
            });
        }
    }
}

function constructAddToCartUrl(productId) {
    //Assume we have fhsformkey, encodeCurrentUrl and addToCartUrl as global variable
    var url = addToCartUrl + "uenc/" + encodeCurrentUrl + "/product/" + productId + "/form_key/" + fhsformkey + "/";
    return url;
}

function constructAddWishlist(productId) {
    var url = addWishlistUrl + "product/" + productId + "/form_key/" + fhsformkey + "/";
    return url;
}

function constructAddToCompare(productId) {
    var url = addCompareUrl + "product/" + productId + "/uenc/" + encodeCurrentUrl + "/form_key/" + fhsformkey + "/";
    return url;
}

function isElementInView(e, view) {
    var eBox = e[0].getBoundingClientRect();
    return (
            eBox.top >= view.top &&
            eBox.left >= view.left &&
            (eBox.right - e.outerWidth(true) - 100) <= view.right
            );
}

$jq(document).ready(function () {
    //shorten the product name with ellipsis. This should be load on all page to handle long product name
    /*
     * SHOULD BE REMOVED !!!
     * MORE COMMENT
     */
    shortenProductNames($jq(document));
    
    //phan dau cua breadcrumbs
    var bc_product = $jq(".breadcrumbs li a").each(function (index, value) {
        var name = shorten(value.text, 25);
        $jq(value).text(name);
    });
    
    //phan cuoi cua breadcrumbs
    var bc_product = $jq(".breadcrumbs li strong").text();
    $jq(".breadcrumbs li strong").text(shorten(bc_product, 25));
    
    /*
     * FlashSale Init Check
     */
    var flashsale_data = localStorage.getItem("flashsale");
    flashsale_data = JSON.parse(flashsale_data);
    if (flashsale_data) {
        /// Don't send products data
        flashsale_data['products'] = null;
        /// Check if flashsale date is expired;
        var now_time = new Date().getTime();
        var one_day = 86400 /// 24*60*60 , 1 day, clear flashsale data after 1 day
        if (flashsale_data['date'] && now_time >= (flashsale_data['date'] + one_day)) {
            localStorage.setItem("flashsale", null);
            flashsale_data = null;
        }
    }
    
    //console.log("POST");
    //console.log(flashsale_data);
    const FLASHSALE_CHECK_URL = "/node_api/flashsale/check";
    $jq.ajax({
        url: FLASHSALE_CHECK_URL,
        method: 'post',
        data: {
            flashsale: flashsale_data,
        },
        success: function (data) {
            //console.log(data);
            if (data.to_clear) {
                localStorage.setItem("flashsale", null);
                //console.log("Flashsale: clear data")
            }

            if (data.to_update) {
                data.new_data['date'] = new Date().getTime();
                var new_data_str = JSON.stringify(data.new_data);
                localStorage.setItem("flashsale", new_data_str);
                //console.log("Flashsale: new data stored!");
            }

            $jq(window).trigger("flashsale_storage");
        }
    });
});

function shortenProductNames($parent_element) {
    $parent_element.find(".product-name a").each(function (index, value) {
        var name = value.innerHTML;
        // /gi Replacing All Matches
        name = name.replace(/&nbsp;/gi, " ");
        name = name.replace(/&amp;/gi, "&");
        name = shorten(name, 50);
        $jq(value).text(name);
    });
}

function randomIntFromInterval(min, max)
{
    return Math.floor(Math.random() * (max - min + 1) + min);
}

var waitForFinalEvent = (function () {
    var timers = {};
    return function (callback, ms, uniqueId) {
        if (!uniqueId) {
            uniqueId = "Don't call this twice without a uniqueId";
        }
        if (timers[uniqueId]) {
            clearTimeout(timers[uniqueId]);
        }
        timers[uniqueId] = setTimeout(callback, ms);
    };
})();

