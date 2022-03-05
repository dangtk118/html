const RepaymentOrder = function () {
    let REPAYMENT_ORDER = '/repayment/index/rePaymentOrder';
    
    let languages = {};
    let order_id = 0;
    let is_login = false;
    let is_loading = false;
    let is_loading_repayment_order = false;
    
    
    var $this = this;
    this.init = function (_order_id, _is_login, _languages) {
	$this.order_id = _order_id;
	$this.is_login = _is_login;
	$this.languages = _languages;
	$jq('.fhs-btn-orderconfirm').click(function(){
	    $this.validateRepaymentOrder();
	});
	$jq(window).on('resize scroll', function () {
	    let height = Math.round($jq('.fhs-bsidebar-content').height());
	    $jq('body').css('margin-bottom', (height+16)+"px");
	});
    };
    
    this.repaymentOrder_post = function (data, try_time = 0){
	$jq(".youama-ajaxlogin-cover").fadeIn();
	$jq('#popup-default-loading-confirm').hide();
	$jq('#popup-default-loading-context-text').html($this.languages['processing']+"...");
	$jq('#popup-default-loading-logo').hide();
	$jq('#popup-default-loading-icon').show();
	$jq('#popup-default-loading').fadeIn();
	if(is_loading){
	    if(try_time >= 10){
		is_loading_repayment_order = false;
		$jq('.popup-fahasa-default-alert-content .popup-fahasa-default-content-text').text($this.languages['overload']);
		$jq('#popup-default-loading').fadeOut();
		$jq('#popup-fahasa-alert').show();
	    }
	    try_time++;
	    setTimeout(function(){$this.createOrder_post(data,try_time);}, 1000);
	    return;
	}
	if(is_loading_repayment_order){return;}
	is_loading_repayment_order = true;
	$jq.ajax({
	    url: REPAYMENT_ORDER,
	    method: 'post',
            dataType : "json",
	    data: JSON.stringify(data),
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    if(!fhs_account.isEmpty(data['redirect_url'])){
			window.location.href = data['redirect_url'];
		    }
		    return;
		}
		if(!fhs_account.isEmpty(data['redirect_url'])){
		    window.location.href = data['redirect_url'];
		}else if(!fhs_account.isEmpty(data['message'])){
		    $jq('.popup-fahasa-default-alert-content .popup-fahasa-default-content-text').html(data['message']);
		    $jq('#popup-default-loading').fadeOut();
		    $jq('#popup-fahasa-alert').show();
		}else{
		    $jq('.popup-fahasa-default-alert-content .popup-fahasa-default-content-text').text($this.languages['overload']);
		    $jq('#popup-default-loading').fadeOut();
		    $jq('#popup-fahasa-alert').show();
		}
		is_loading_repayment_order = false;
	    },
	    error: function () {
		is_loading_repayment_order = false;
		$jq('.popup-fahasa-default-alert-content .popup-fahasa-default-content-text').text($this.languages['overload']);
		$jq('#popup-default-loading').fadeOut();
		$jq('#popup-fahasa-alert').show();
	    }
	});
    };    
    
    //METHOD PROCESS
    this.validateRepaymentOrder = function(){
	let payment_method = $jq('.fhs_checkout_paymentmethod_option:checked').val();
	if(fhs_account.isEmpty(payment_method)){
	    $jq('#fhs_checkout_block_paymentmethod').addClass('block_checked_error');
	    $jq('html, body').stop().animate({
		scrollTop: $jq('#fhs_checkout_block_paymentmethod').offset().top
	    }, 1000);
	    return;
	}
	
	$this.repaymentOrder();
    };
    this.repaymentOrder = function(){
	let data = {orderId:0, paymentMethod:''};
	data.orderId = $this.order_id;
	data.paymentMethod = $jq('.fhs_checkout_paymentmethod_option:checked').val();
	
	$this.repaymentOrder_post(data);
    };
};
