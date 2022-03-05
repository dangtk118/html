const ProdComment = function () {
    let LOAD_COMMENT_URL = "/fahasa_catalog/product/loadComment";
    let ADD_COMMENT_URL = "/fahasa_catalog/product/addComment";
    let REVIEW_COMMENT_URL = "/fahasa_catalog/product/reviewComment";
    
    let product_id = 4;
    let page = 1;
    let page_size = 12;
    let order_by = "";
    let page_total = 0;
    let toolbar_limit = 5;
    let star_vote = 0;
    let languages = {};
    let is_loading = false;
    var is_first = true;
    let is_scroll = true;
    let is_incognito = false;
    
    var $this = this;
    
    this.initComment = function (_product_id, _page, _page_size, _toolbar_limit, _order_by, _languages) {
	$this.product_id = _product_id;
	$this.page = _page;
	$this.page_size = _page_size;
	$this.toolbar_limit = _toolbar_limit;
	$this.order_by = _order_by;
	$this.languages = _languages;
	$this.is_first = true;
	$this.is_scroll = false;
	$this.is_incognito = false;
	$this.star_vote = 5;
	
	$jq('.rating_item').hover(function(){
	    let star = $jq(this).attr('data')
	    $this.vote_change(star,true);
	});
	$jq('#review_field').keyup(function(){
	    let count = $jq(this).val().replace(/ /g, "").replace(/\r\t/g, "").replace(/\n/g, "").length;
	    if(count > 0){
		$jq('.fhs_top_space > div:first-of-type').css('width','calc(100% - 40px)');
		$jq("#count-message").fadeIn(0);
		$jq("#count-message").html(count); 
	    }else{
		$jq('.fhs_top_space > div:first-of-type').css('width','100%');
		$jq("#count-message").fadeOut(0);
	    }
	});
	$jq(window).on('resize scroll', function() {
	    var hT = $jq('#product_view_review').offset().top,
		hH = $jq('#product_view_review').outerHeight(),
		wH = $jq(window).height(),
		wS = $jq(this).scrollTop();
		if (wS > (hT+hH-wH) && $this.is_first){
		    $this.loadComment(true);
		}
	});
	setTimeout(function(){$this.checkBlockInViewport();},1000);
	
	let w = window.innerWidth || document.body.clientWidth;
	new Swiper('.review_comment_tabs_swiper_container', {
            slidesPerView: 'auto',
            allowTouchMove: (w <= 1230) ? true : false
        });
    };
    
    this.checkBlockInViewport = function(){
	if($jq('#product_view_review')){
	    if(Helper.isElementInViewport($jq('#product_view_review'))){
		if($this.is_first){
		    $this.loadComment(true);
		}
	    }
	}
    };
    this.loadComment = function(is_first = false){
	if(is_loading){return;}
	is_loading = true;
	if(!$this.is_first){fhs_account.showLoadingAnimation();}
	$jq.ajax({
	    url: LOAD_COMMENT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: { product_id: $this.product_id, page: $this.page, page_size: $this.limit, sort: $this.order_by},
	    fail: function() {
		is_loading = false;
		fhs_account.hideLoadingAnimation();
	    },
	    success: function (data) {
		if(data['success']){
		    setTimeout(function(){$this.commentDataProcess(data);}, 1);
		}
		else{
		    if(!$this.is_first){fhs_account.hideLoadingAnimation();}
		}
		is_loading = false;
	    }
	});
    };
    
    this.addComment = function(nickname, comment){
	if(is_loading){return;}
	is_loading = true;
	fhs_account.showLoadingAnimation();
	$jq.ajax({
	    url: ADD_COMMENT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: { product_id: $this.product_id, star: $this.star_vote, nickname: nickname, comment: comment},
	    fail: function() {
		is_loading = false;
	    },
	    success: function (data) {
		if(data['success']){
		    $jq('#popup-fahasa-default-content-text').text($this.languages['add_comment_complete']);
		    $jq('#popup_write_review').fadeOut(0);
		    $jq('#popup-notification-msg').fadeIn(0);
		    $this.vote_change('5', true);
		    $jq('#nickname_field').val('');
		    $jq('#review_field').val('');
		    $jq("#count-message").html("");
		    fhs_account.hideLoadingAnimation();
		}
		else{
		    fhs_account.hideLoadingAnimation();
		}
		is_loading = false;
	    }
	});
    };
    
    this.reviewComment = function(review_id, type, count_like = 0){
	if(!CUSTOMER_ID){
	    $jq('.youama-ajaxlogin-cover').fadeIn();
	    $jq('.youama-login-window').fadeIn();
	    return;
	}
	if(is_loading){return;}
	is_loading = true;
	fhs_account.showLoadingAnimation();
	$jq.ajax({
	    url: REVIEW_COMMENT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: { review_id: review_id, type: type},
	    fail: function() {
		is_loading = false;
		fhs_account.hideLoadingAnimation();
	    },
	    success: function (data) {
		if(data['success']){
		    setTimeout(function(){$this.reviewed(review_id, type, count_like);}, 1);
		}
		else{
		    fhs_account.hideLoadingAnimation();
		}
		is_loading = false;
	    }
	});
    };
    
    this.commentDataProcess = function(data){
	if(data['comment_list']){
	    if(data['comment_list'].length > 0){
		if(!$this.is_first){fhs_account.hideLoadingAnimation();}else{$jq('.product-view-tab-content-review-comment').fadeIn(0);}
	    }
	}
	$this.page_total = Math.ceil(data['total_comments']/$this.page_size);
	$this.displayComment(data['comment_list']);
	let is_more = false;
	if($jq('#product_view_tab_content_review').hasClass('desc_viewmore_showmore')){
	    is_more = true;
	}
	try{
	    //personalization.redisplayVertically(is_more);
	}catch(ex){}
	    if($this.is_scroll){
		$jq('html, body').stop().animate({
		scrollTop: $jq('.product-view-tab-content-review-comment_sort').offset().top
	    }, 1000);
	}
	
	$this.is_scroll = false;
	$this.is_first = false;
    }
    
    this.displayComment = function(data){
	var comment_str = "";
	var pages_str =  "";
	Object.keys(data).forEach(function(key){
	    comment_str += $this.getCommentItem(data[key]);
	});
	
	if(comment_str){
	    $jq('.comment_content .comment_list').html(comment_str);
	    pages_str = $this.calPages()
	    $jq('.comment_content').fadeIn();
	}else{
	    $jq('.comment_content').fadeOut();
	}
	
	$jq('.pages').html("<ol>"+pages_str+"</ol>");
    }
    
    this.pagesize_change = function (pagesize){
	if($this.limit != pagesize){
	    $this.limit = pagesize;
	    $this.page = 1;
	    $this.is_scroll = true;
	    $this.loadComment();
	}
    };
    this.sort_change = function (e, sort_val){
	if($this.order_by != sort_val){
	    $this.order_by = sort_val;
	    $this.page = 1;
	    $jq('.review_comment_tabs li').removeClass('active');
	    $jq(e).addClass('active');
	    $this.loadComment(true);
	}
    };
    this.Page_change = function (page_no){
	if(page_no == 'previous'){
	    --$this.page;
	}else if(page_no == 'next'){
	    ++$this.page;
	}else{
	    $this.page = page_no;
	}
	$this.is_scroll = true;
	$this.loadComment();
    };
    this.showReview = function(){
	$jq('.youama-ajaxlogin-cover').fadeIn(0);
	$jq('#popup_write_review').fadeIn(0);
    }
    this.closeReview = function(){
	$jq('#popup_write_review').fadeOut(0);
	$jq('.youama-ajaxlogin-cover').fadeOut(0);
    }
    this.post_review_click = function(){
	let nickname = $jq('#nickname_field');
	let message = $jq('#review_field');
	let can_post = true;
	$jq('#popup_write_review .validation-advice').fadeOut(0);
	if(!Validation.validate($('nickname_field'))){can_post = false;}
	if(!Validation.validate($('review_field'))){can_post = false;}
	
	if(can_post){
	    let nickname_val = nickname.val();
	    let message_val = message.val();
	    if($this.is_incognito){
		if(nickname_val.length > 2){
		    nickname_val = nickname_val.substring(0, 2)+"******";
		}
	    }
	    $this.addComment(nickname_val, message_val);
	}
    }
    this.closeAlert = function(){
	$jq('#popup-notification-msg').fadeOut();
	$jq('.youama-ajaxlogin-cover').fadeOut(0);
    }
    this.reviewed = function(review_id, type, _count_like){
	let $review = $jq(".review_"+review_id);
	let review_html = '';
	if(type == 'ubuse'){
	    review_html = "<div class='review_report active fhs_center_left'>"
			+"<span class='icon_review_report' style='margin-right:4px;'></span>"
			+"<span>"+ $this.languages['reported']+"</span>"
		    +"</div>";
	}else{
	    _count_like++;
	    review_html = "<div class='review_like active fhs_center_left'>"
			+"<span class='icon_review_like' style='margin-right:4px;'></span>"
			+"<span>"+ $this.languages['like']+"</span>"
			+"<span>("+_count_like+")</span>"
		    +"</div>";
	}
	$review.html(review_html);
	fhs_account.hideLoadingAnimation();
    }
    this.vote_change = function(vote, is_save = false){
	let item_1 = $jq('.rating_item_1');
	let item_2 = $jq('.rating_item_2');
	let item_3 = $jq('.rating_item_3');
	let item_4 = $jq('.rating_item_4');
	let item_5 = $jq('.rating_item_5');
	switch(vote){
	    case "1":
		item_1.addClass('active');
		item_2.removeClass('active');
		item_3.removeClass('active');
		item_4.removeClass('active');
		item_5.removeClass('active');
		if(is_save){$this.star_vote = 1;}
		break;
	    case "2":
		item_1.addClass('active');
		item_2.addClass('active');
		item_3.removeClass('active');
		item_4.removeClass('active');
		item_5.removeClass('active');
		if(is_save){$this.star_vote = 2;}
		break;
	    case "3":
		item_1.addClass('active');
		item_2.addClass('active');
		item_3.addClass('active');
		item_4.removeClass('active');
		item_5.removeClass('active');
		if(is_save){$this.star_vote = 3;}
		break;
	    case "4":
		item_1.addClass('active');
		item_2.addClass('active');
		item_3.addClass('active');
		item_4.addClass('active');
		item_5.removeClass('active');
		if(is_save){$this.star_vote = 4;}
		break;
	    case "5":
		item_1.addClass('active');
		item_2.addClass('active');
		item_3.addClass('active');
		item_4.addClass('active');
		item_5.addClass('active');
		if(is_save){$this.star_vote = 5;}
		break;
	    default:
		item_1.removeClass('active');
		item_2.removeClass('active');
		item_3.removeClass('active');
		item_4.removeClass('active');
		item_5.removeClass('active');
		$this.star_vote = 0;
	}
    };
    this.getCommentItem = function (item){
	let Verified_Purchase = "";
	let review = '';
	let like = "";
	let report = "";
	if(fhs_account.isEmpty(item['suborder_id'])){
	    Verified_Purchase = "style='display:none'";
	}
	if(fhs_account.isEmpty(item['review'])){
	    like = "<div class='review_like fhs_center_left fhs_mouse_point' style='margin-right:20px;' onclick=\"prodComment.reviewComment("+item['id']+",'like',"+item['countLike']+");\">"
				+"<span class='icon_review_like' style='margin-right:4px;'></span>"
				+"<span class='review_like_txt'>"+ $this.languages['like']+"</span>"
				+"<span class='review_like_count'>("+item['countLike']+")</span>"
			    +"</div>";
	    report = "<div class='review_report fhs_center_left fhs_mouse_point' onclick=\"prodComment.reviewComment("+item['id']+",'ubuse');\">"
				+"<span class='icon_review_report' style='margin-right:4px;'></span>"
				+"<span class='review_like_txt'>"+ $this.languages['report']+"</span>"
			    +"</div>";
	}else{
	    if(item['review'] == "ubuse"){
		report = "<div class='review_report active fhs_center_left'>"
			    +"<span class='icon_review_report' style='margin-right:4px;'></span>"
			    +"<span>"+ $this.languages['reported']+"</span>"
			+"</div>";
	    }else{
		like = "<div class='review_like active fhs_center_left'>"
			    +"<span class='icon_review_like' style='margin-right:4px;'></span>"
			    +"<span>"+ $this.languages['like']+"</span>"
			    +"<span>("+item['countLike']+")</span>"
			+"</div>";
	    }
	    
	}
	return "<li>"
		    +"<div>"
			+"<div>"+fhs_account.encodeHTML(item['nickname'])+"</div>"
			+"<div>"+item['created_at']+"</div>"
			+"<div class='fhs_center_left Verified_Purchase desktop_only' "+Verified_Purchase+">"+"<span class='icon_Verified_Purchase' style='margin-right:4px;'></span><span>"+$this.languages['Verified_Purchase']+"</span></div>"	
		    +"</div>"
		    +"<div>"
			+"<div class='fhs_center_left'>"
			    +"<div class='rating-box'>"
				+"<div class='rating' style='width:"+item['rating']+"%'></div>"
			    +"</div>"
			    +"<div class='clear'></div>"
			    +"<div class='fhs_center_left Verified_Purchase mobile_only' "+Verified_Purchase+" style='margin-left:24px;'>"+"<span class='icon_Verified_Purchase' style='margin-right:4px;'></span><span>"+$this.languages['Verified_Purchase']+"</span></div>"
			+"</div>"
			+"<div>"+fhs_account.encodeHTML(item['detail'])+"</div>"
			+"<div class='fhs_center_left review_"+item['id']+"'>"
			    +like
			    +report
			+"</div>"
		    +"</div>"
		+"</li>";
    };
    this.calPages = function (){
	var pages_str = "";
	if($this.page_total <= 1){
	    return pages_str;
	}
	var start = 0;
	var stop = 5;
	if($this.page > 1){
	    pages_str = "<li title='Previous'><a onclick=\"prodComment.Page_change('previous')\"><i class='fa fa-chevron-left'></i></a></li>";
	}

	if($this.page < ($this.toolbar_limit/2)){
	    start = 0;
	}
	else if(($this.page_total - $this.page) < ($this.toolbar_limit/2)){
	    start = $this.page_total - $this.toolbar_limit;
	}
	else{
	    start = $this.page - Math.ceil($this.toolbar_limit/2);
	}
	
	if(start < 0){start = 0;}
	
	stop = (start+$this.toolbar_limit);

	for(var i = start; i < stop; i++){
	    if(i < $this.page_total){
		if($this.page == (i+1)){
		    pages_str += "<li class='current'><a>"+(i+1)+"</a></li>";
		}
		else{
		    pages_str += "<li><a onclick='prodComment.Page_change("+(i+1)+")'>"+(i+1)+"</a></li>";
		}
	    }
	}

	if($this.page < $this.page_total){
	    pages_str += "<li title='Next'><a onclick=\"prodComment.Page_change('next')\"><i class='fa fa-chevron-right'></i></a></li>";
	}
	return pages_str;
    };

    //common
    this.choiceTab = function(tab_name){
	let block_id = '';
	switch(tab_name){
	    case "description":
		//personalization.tabVertically_choice('product-view-tab-info-item');
		block_id = '#product_view_info';
		break;
	    case "review":
		if($this.is_first){
		    $this.loadComment();
		}
		//personalization.tabVertically_choice('product-view-tab-review-item');
		block_id = '#product_view_review';
		break;
	}
	if(block_id){
	    $jq('html, body').stop().animate({
		scrollTop: $jq(block_id).offset().top
	    }, 1000);
	}
    }
    this.getQueryStringValue = function(param) {  
	var url = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');  
	for (var i = 0; i < url.length; i++) {  
	    var urlparam = url[i].split('=');  
	    if (urlparam[0] == param) {  
		return urlparam[1];  
	    }  
	}  
    }; 
    this.gotoQueryString = function(){
	let review = $this.getQueryStringValue('review');
	if(!fhs_account.isEmpty(review)){
	    if(review == "open"){
		$this.choiceTab('review');
	    }
	}
    };
    this.IncognitoClick = function(e){
	if($jq(e).hasClass('active')){
	    $jq('.fhs_btn_io').removeClass('active');
	    $this.is_incognito = false;
	}else{
	    $jq('.fhs_btn_io').addClass('active');
	    $this.is_incognito = true;
	}
    }
}