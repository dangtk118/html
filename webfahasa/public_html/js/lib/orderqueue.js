
const OrderQueue = function () {
    const TIME_REQUEST_QUEUE = 2000;
    let time_loaded = 0;
    const QUEUE_TIME_LIMIT_TO_TRY = 5;
    let url_save_order = '';
    let url_check_order_status = '';
    var $this = this;
    
    this.initOrderQueue = function (_url_save_order, _url_check_order_status) {
	url_save_order = _url_save_order;
	url_check_order_status = _url_check_order_status;
    }
    this.submitPurchase = function (){
	$jq(".youama-ajaxlogin-cover").fadeIn();
	$jq('#popup-default-loading-confirm').hide();
	$jq('#popup-default-loading-context-text').html(text_processing+"...");
	$jq('#popup-default-loading-logo').hide();
	$jq('#popup-default-loading-icon').show();
	$jq('#popup-default-loading').fadeIn();
	sleep(200);
	new Ajax.Request(
	    url_save_order, {
	    method: 'post',
	    parameters: Form.serialize('one-step-checkout-form'),
	    onLoading: function () {
	    },
	    onFailure: function () {
	    },
	    onSuccess: function (result) {
		var result_purchare = JSON.parse(result.responseText);
		if(result_purchare.success){
		    time_loaded = 0;
		    $this.checkOrderStatus();
		}else{
		    if(result_purchare.url){
			window.location.href = result_purchare.url;
		    }else if(result_purchare.message){
			$jq('.popup-fahasa-default-alert-content .popup-fahasa-default-content-text').html(result_purchare.message);
			$jq('#popup-default-loading').fadeOut();
			$jq('#popup-fahasa-alert').show();
		    }else{
			$jq(".youama-ajaxlogin-cover").fadeOut();
			$jq('#popup-default-loading').fadeOut();
		    }
		}
	    }
	});
    }

    this.tryLoadOrderStatus = function (){
	$jq('#popup-default-loading-confirm').hide();
	$jq('#popup-default-loading-context-text').html(text_processing+"...");
	$jq('#popup-default-loading-logo').hide();
	$jq('#popup-default-loading-icon').show();
	time_loaded = 0;
	$this.checkOrderStatus();
    }
    
    this.checkOrderStatus = function () {
	new Ajax.Request(
	url_check_order_status, {
	    method: 'post',
	    onSuccess: function (result) {
		var order_queue = JSON.parse(result.responseText);
		time_loaded++;
		console.log("time to try="+time_loaded+", status="+order_queue.success+", process status="+order_queue.isProcessed);
		if (order_queue.success) {
		    if (order_queue.isProcessed) {
			if (order_queue.orderId) {
			    window.location.href = order_queue.redirectUrl;
			}else {
			    window.location.href = '/checkout/cart';
			}
			return;
		    }else{
			if(time_loaded < QUEUE_TIME_LIMIT_TO_TRY){
			    sleep(3000);
			    $this.checkOrderStatus();
			}else{
			    $jq('#popup-default-loading-icon').hide();
			    $jq('#popup-default-loading-logo').show();
			    $jq('#popup-default-loading-context-text').html(text_timeout);
			    $jq('#popup-default-loading-confirm').show();
			}
		    }
		}
	    },
	    onFailure: function () {
		time_loaded++;
		console.log("time to try="+time_loaded+", loading failured.");
		if(time_loaded < QUEUE_TIME_LIMIT_TO_TRY){
		    sleep(3000);
		    $this.checkOrderStatus();
		}else{
		    $jq('#popup-default-loading-icon').hide();
		    $jq('#popup-default-loading-logo').show();
		    $jq('#popup-default-loading-context-text').html(text_timeout);
		    $jq('#popup-default-loading-confirm').show();
		}
	    }
	});
    }
}
function sleep(milliseconds) {
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  }
}