const Wishlist = function () {
    let product_id = 0;
    
    var $this = this;
    
    this.product_init = function(product_id){
	$this.product_id = product_id;
	$this.isWishlisted();
	
	$jq('.fhs_addon_wishlist').click(function(){
	    if(!$jq(this).hasClass('active')){
		$this.addWishlist();
	    }else{
		$this.removeWishlist();
	    }
	    
	});
    };
    
    this.isWishlisted = function(){
	$jq.ajax({
	    url: '/fahasa_catalog/product/isWishlisted',
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {product_id: $this.product_id},
	    success: function (data) {
		if(data['success']){
		    if(data['is_wished']){
			$jq('.fhs_addon_wishlist').addClass('active');
		    }
		}
		$jq('.fhs_addon_wishlist').fadeIn(0);
	    },
	    error: function(){}
	});
    };
    
    this.addWishlist = function(){
	if($jq('.fhs_addon_wishlist').hasClass('active')){return;}
	if(!fhs_account.isLogin()){return;}
	
	fhs_account.showLoadingAnimation();
	$jq.ajax({
	    url: '/fahasa_catalog/product/addWishlist',
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {product_id: $this.product_id},
	    success: function (data) {
		if(data['success']){
		    $jq('.fhs_addon_wishlist').addClass('active');
		    setTimeout(function(){$jq(window).trigger("netcore_event_add_to_wishlist");},1);
		}
		fhs_account.hideLoadingAnimation();
	    },
	    error: function(){fhs_account.hideLoadingAnimation();}
	});
    };
    
    this.removeWishlist = function(){
	if(!$jq('.fhs_addon_wishlist').hasClass('active')){return;}
	if(!fhs_account.isLogin()){return;}
	
	fhs_account.showLoadingAnimation();
	$jq.ajax({
	    url: '/fahasa_catalog/product/removeWishlist',
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {product_id: $this.product_id},
	    success: function (data) {
		if(data['success']){
		    $jq('.fhs_addon_wishlist').removeClass('active');
		    setTimeout(function(){$jq(window).trigger("netcore_event_remove_wishlist");},1);
		}
		fhs_account.hideLoadingAnimation();
	    },
	    error: function(){fhs_account.hideLoadingAnimation();}
	});
    };
};

