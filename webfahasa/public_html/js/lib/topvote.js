const TopVote = function (base_skin_url) {
    let number_top = 5;
    let arrow_up = "fa fa-arrow-up";
    let arrow_down = "fa fa-arrow-down";
    let product_id = 0;
    
    var $this = this;

    this.getTopVotedByCatId = function (catId) {
        let catIdTemp = catId;
        if (catId == "rest") {
            catIdTemp = "rest";
        }
        jQuery('div[id*=tagname-]').css("display", "none");
        jQuery("#tagname-" + catId).css("display", "block");
        jQuery("button[id*=cat-]").removeClass("active");
        jQuery("#cat-" + catIdTemp).addClass("active");
        $jq.ajax({
            url: 'event/index/getTopVoted',
            method: 'post',
            data: {catId: catId}
        }).then((data) => {
            jQuery("#top-voted-content").empty();
            jQuery(".block-vote-lower").empty();
            var params = JSON.parse(data);
            listProduct = params;
            previewProduct = listProduct[0];
            listProduct.forEach(function (item, index) {
                var image = `<img class="small-image2" src="${item.image}" />`;
                let arrowIcon = null;
                let color = "";
                if (item.isRaise) {
                    arrowIcon = arrow_up;
                    color = "green";
                } else {
                    arrowIcon = arrow_down;
                    color = "red";
                }
                let column_class = "";
                if (index >= number_top) {
                    column_class = "";
                }

                let arrowImage = `<i class='${arrowIcon}' style='color: ${color};'></i>`;
                var block = `<div class="${column_class} product-item2-container">`
                        + `<a  class="product-item2" id="id-${item.productId}" onclick="topvote.onClickShowProduct(${item.productId}, ${index})" href="/${item.productUrl}">`
                        + `<div class="index-number-container"><div>${('0' + (index + 1)).slice(-2)}</div>`
                        + `${arrowImage}`
                        + `</div>`
                        + `${image}`
                        + `<div class="vote-info">`
                        + `<div class="name">${item.name}</div>`
                        + `<div style="" class="author">${item.author}</div>`
                        + `<div class="vote-message">${item.percentVoted} điểm</div>`
                        + `</div>`;
                if (index < number_top) {
                    jQuery("#top-voted-content").append(block);
                } else {
                    jQuery(".block-vote-lower").append(block);
                }
            });
            this.setPreviewProduct(previewProduct);

            let self = this;
            jQuery(".product-item2")
                    .filter(function (index) {
                        return index < number_top;
                    })
                    .hover(function () {
                        var productId = this.id.replace("id-", "");
                        previewProduct = listProduct.find(x => x.productId == productId);
                        self.setPreviewProduct(previewProduct);
                    }, function () {
                    });
        });
    }

    this.init = function () {
        var defaultCatId = "#catId9";
        var catId = defaultCatId.replace(/.*#catId/gi, "");
        this.getTopVotedByCatId(catId);

        new Swiper("#block-fhs-vote > .swiper-container", {
            direction: 'horizontal',
            slidesPerView: 'auto',
            freeMode: true,
            longSwipesMs: 800,
            observer: true
        });
    }

    this.onClickVoteProduct = function () {
        jQuery("#vote-product-button").prop('disabled', true);
        let productId = previewProduct.productId;
        new Ajax.Request(
                "<?php echo $this->getUrl('event/index/postVoteProduct', array('_secure' => true)) ?>", {
                    method: 'post',
                    parameters: {productId: productId},
                    onLoading: function () {
                        jQuery('.youama-ajaxlogin-loader').fadeIn();
                    },
                    onLoaded: function () {
                        jQuery('.youama-ajaxlogin-loader').fadeOut();
                    },
                    onSuccess: function (transport) {
                        if (transport.status == 200) {
                            var params = JSON.parse(transport.responseText);
                            var success = params.success;
                            var hadVoted = params.hadVoted;
                            let curProductIdx = listProduct.findIndex(x => x.productId == productId);
                            if (curProductIdx != -1) {
                                listProduct[curProductIdx].hadVoted = 1;
                            }
                            if (params.success) {
                                jQuery("#vote-product-button").removeClass("btn-vote-inactive").addClass("btn-vote-active");
                            } else {
                                if (params.message == "ERR_NEED_LOGIN") {
                                    jQuery(".youama-login-window").fadeIn();
                                    jQuery('div.youama-ajaxlogin-cover').fadeIn();
                                } else {
                                    var errormess = "Co loi xay ra";
                                    jQuery('#alert-vote-product div.youama-noti-window-sucess').fadeIn();
                                }
                            }
                        }
                    }
                }
        );
    }

    this.setPreviewProduct = function (previewProduct) {
        if (previewProduct) {
            var priceText = 0;
            var finalPrice = 0;
            priceText = Math.round(previewProduct.price).toLocaleString('en-US');
            finalPrice = Math.round(previewProduct.finalPrice).toLocaleString('en-US');
            jQuery("#preview-product .image").attr("src", previewProduct.image);
            jQuery("#preview-product .name").text(previewProduct.name);
            jQuery("#preview-product .final-price").text(finalPrice + " đ");
            jQuery("#preview-product .price").text(priceText + " đ");
            jQuery("#preview-product .discount-percent").text("-" + previewProduct.discountPercent + "%");
            jQuery("#preview-product .author").text("Tác giả: " + previewProduct.author);
            jQuery("#preview-product .publisher").text("Nhà xuất bản: " + previewProduct.publisher);
            let description = previewProduct.description;
            if (description && description.length > 1000) {
                description = description.substring(0, 1000);
            }
            jQuery("#preview-product .description").html(description + "...");
            if (previewProduct.hadVoted == 1) {
                jQuery("#vote-product-button").removeClass("btn-vote-inactive btn-vote-active").addClass("btn-vote-active");
            } else {
                jQuery("#vote-product-button").removeClass("btn-vote-inactive btn-vote-active").addClass("btn-vote-inactive");
            }
            jQuery("#preview-product .percent-voted").text(previewProduct.percentVoted);
            jQuery("#preview-product .product-link").attr("href", previewProduct.productUrl);
        }
    };

    this.onClickShowProduct = function (productId, index) {
        jQuery("#top-voted-content .small-image-active").removeClass("small-image-active").addClass("small-image");
        jQuery("#top-voted-content .index-number-active").removeClass("index-number-active").addClass("index-number");
        previewProduct = listProduct.find(x => x.productId == productId);
        this.setPreviewProduct(previewProduct);
        let element = jQuery("#id-" + productId + " .small-image");
        let elementIndex = jQuery("#id-" + productId + " .index-number");
        elementIndex.removeClass("index-number").addClass("index-number-active");
        element.removeClass("small-image").addClass("small-image-active");
        jQuery("#top-voted-content .product-item2").removeClass("click-active");
        jQuery("#id-" + productId).addClass("click-active");
//        if (index >= number_top) {
//            jQuery("html, body").animate({scrollTop: jQuery(".block-vote").offset().top}, 200);
//        }
    };

    this.getProductVotedByCustomer = function () {
        $jq.ajax({
            url: 'event/index/votedHistory',
            method: 'post'
        }).then((response) => {
            let result = JSON.parse(response);
            if (result.success) {
                jQuery("#vote-history").empty();
                result.data.forEach(function (item, index) {
                    let history_item = `<div style="flex-direction: row; display: flex;">`
                            + `<div style="font-size: 1.4em; color: black;">${index + 1}.</div>`
                            + `<div style="margin-left: 8px;">`
                            + `<div style="font-size: 1.4em; color: black;">${item.name}</div>`
                            + `<div style="font-size: 1.3em; color: gray;">${item.author}</div>`
                            + `</div>`
                            + `</div>`;
                    jQuery("#vote-history").append(history_item);
                });
            }
        });
    };
    
    this.product_init = function(product_id){
	$this.product_id = product_id;
	$this.getVoteProduct();
	
	$jq('.fhs_addon_topvote').click(function(){
	    $this.voteProduct();
	});
    };
    
    this.getVoteProduct = function(){
	$jq.ajax({
	    url: '/event/index/checkVote',
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {productId: $this.product_id},
	    success: function (data) {
		if(!fhs_account.isEmpty(data['productId'])){
		    if(data['showVote']){
			if(data['hadVoted']){
			    $jq('.fhs_addon_topvote').addClass('active');
			    $jq('.fhs_addon_topvote_text').text('Đã bình chọn');
			}
			$jq('.fhs_addon_topvote').fadeIn(0);
			
			let vote_html = '';
			let percentVoted = 0;
			if(data['percentVoted']){
			    percentVoted = data['percentVoted'];
			}
			vote_html = '<div style="border: 1px solid #CDCFD0;height: 16px; margin: 0 16px 0 8px;"></div>'
			vote_html += '<div class="fhs_addon_topvote_number"><span class="fhs_addon_topvote_icon"></span><span class="fhs_addon_topvote_num">'+percentVoted+'</span><span class="fhs_addon_topvote_text">lượt bình chọn</span></div>';
			$jq('.view-rate .view-rate-left').append(vote_html);
		    }
		}
	    },
	    error: function(){}
	});
    };
    
    this.voteProduct = function(){
	if($jq('.fhs_addon_topvote').hasClass('active')){return;}
	if(!fhs_account.isLogin()){return;}
	
	fhs_account.showLoadingAnimation();
	$jq.ajax({
	    url: '/event/index/postVoteProduct',
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {productId: $this.product_id},
	    success: function (data) {
		if(data['success']){
		    $jq('.fhs_addon_topvote').addClass('active');
		    $jq('.fhs_addon_topvote_text').text('Đã bình chọn');
		    $jq('.fhs_addon_topvote').fadeIn(0);
		}
		fhs_account.hideLoadingAnimation();
	    },
	    error: function(){fhs_account.hideLoadingAnimation();}
	});
    };
};

