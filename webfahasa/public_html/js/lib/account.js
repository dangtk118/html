const FhsAccount = function () {
    let ACCOUNT_INFO = "/customer/account/edit/";
    let LOGIN_ACCOUNT_URL = "/ajaxlogin/ajax/index/";
    
    let CHECK_PHONE_OTP_ACCOUNT_URL = "/ajaxlogin/ajax/checkPhoneOTP";
    let CHECK_EMAIL_OTP_ACCOUNT_URL = "/ajaxlogin/ajax/checkEmailOTP";
    
    let SEND_PHONE_OTP_ACCOUNT_URL = "/ajaxlogin/ajax/checkPhone";
    let REGISTER_ACCOUNT_URL = "/ajaxlogin/ajax/registerAccount";
    let REGISTERQUICK_ACCOUNT_URL = "/ajaxlogin/ajax/registerAccountQuick";
    let ORDER_HISTORY_URL = "/sales/order/view/order_id/";
    
    let SEND_RECOVERY_PASSWORD_URL = "/ajaxlogin/ajax/checkRecoveryPassword";
    let RECOVERY_ACCOUNT_URL = "/ajaxlogin/ajax/recoveryAccount";
    
    let SEND_CHANGE_PHONE_URL = "/customer/account/checkChangePhone";
    let CHANGE_PHONE_ACCOUNT_URL = "/customer/account/changePhoneAccount";
    
    let SEND_CHANGE_EMAIL_URL = "/customer/account/checkChangeEmail";
    let CHANGE_EMAIL_ACCOUNT_URL = "/customer/account/changeEmailAccount";
    
    let LOGIN_FB_ACCOUNT_URL = "/ajaxlogin/ajax/loginfb/";
    let SEND_CONFIRM_PHONE_URL = "/ajaxlogin/ajax/checkPhoneConfirm";
    let REGISTER_FB_ACCOUNT_URL = "/ajaxlogin/ajax/registerFaccbookAccount";

    
    var languages = {};
    let is_loading = false;
    let is_redirect = '';
    let redirect_url = '';
    let sent_phone_otp = false;
    let phone = '';
    let phone_otp = '';
    
    let username = '';
    let password = '';
    
    let is_recovery_opening = false;
    let sent_username_recovery_otp = false;
    let username_recovery = '';
    let username_recovery_otp = '';
    
    let sent_phone_change_otp = false;
    let phone_change = '';
    let phone_change_otp = '';
    
    let sent_email_change_otp = false;
    let email_change = '';
    let email_change_otp = '';
    
    let is_login_facebook = false;
    let sent_phone_confirm_otp = false;
    let phone_confirm = '';
    let phone_confirm_otp = '';
    let accessToken = '';
    let facebookId = '';
    
    let orderId = '';
    let registerquick_sent_otp = false;
    let registerquick_phone = '';
    let registerquick_otp = '';
    
    let minLength = 6;
    
    let isShowLoginform = false;
    let coupon_bg_path = "M 110 144 h -98 a 12 12 0 0 1 -12 -12 v {{H}} a 12 12 0 0 1 12 -12 H 110 a 12.02 12 0 0 0 24 0 H {{W}} a 12 12 0 0 1 12 12 V 132 a 12 12 0 0 1 -12 12 H 134 v 0 a 12 12 0 0 0 -24 0 v 0 Z";
    let coupon_bg_mini_path = "M 98 144 h -86 a 12 12 0 0 1 -12 -12 v {{H}} a 12 12 0 0 1 12 -12 H 98 a 8 8 0 0 0 16 0 H {{W}} a 12 12 0 0 1 12 12 V 132 a 12 12 0 0 1 -12 12 H 114 v 0 a 8 8 0 0 0 -16 0 v 0 Z";
    
    let is_loading_block = false;
    let block_ids = {};
    
    let add_to_cart_data = {};
    
    var $this = this;
//Login-Register-Recovery--------------------------------- 
    this.initAccount = function (_is_redirect, _redirect_url, _languages, _minLength) {
	$this.is_redirect = _is_redirect;
	$this.redirect_url = _redirect_url;
	$this.languages = _languages;
	$this.is_recovery_opening = false;
	$this.isShowLoginform = false;
	$this.minLength = _minLength;
	
	$this.phone = '';
	$this.phone_otp = '';
	$this.sent_phone_otp = false;
	
	$this.username_recovery = '';
	$this.username_recovery_otp = '';
	$this.sent_username_recovery_otp = false;
	
	$this.is_login_facebook = false;
	$this.sent_phone_confirm_otp = false;
	$this.phone_confirm = '';
	$this.phone_confirm_otp = '';
	$this.accessToken = '';
	
	$this.username = 'thefirst';
	
	$this.openCloseWindowEvents();
	$this.eventButtonClick();
	$this.eventInputPress();
	
	$this.is_loading_block = false;
	$this.block_ids = {};
	
	$this.add_to_cart_data = {};
    };
    
    this.postLogin = function(username, password){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq('.fhs-login-msg').text('');
	$jq('#login_password').prop("type", "password");
	$jq.ajax({
	    url: LOGIN_ACCOUNT_URL,
	    method: 'post',
            dataType : "html",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {ajax : 'login',email: username, password: password},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
	    },
	    success: function (msg) {
//		if(msg != 'success'){
//		    $jq('.fhs-login-msg').text($this.languages['wrong']);
//		}else{
//		    $this.savePassword(username, password);
//		    // Redirect
//		    if ($this.is_redirect == '1') {
//			window.location = $this.redirect_url;
//		    } else {
//			window.location.reload();
//		    }
//		    $this.closeLoginPopup();
//		}
//		is_loading = false;
//		$this.animateLoader('stop');
		if(msg != 'success'){
		    $jq('.fhs-login-msg').text($this.languages['wrong']);
		}else{
		    $this.savePassword(username, password);
		    // Redirect
		    $this.addToCart();
		    
		    $this.closeLoginPopup();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    
    this.postSendPhoneOTP = function(phone){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq('.fhs-register-msg').text('');
	$jq.ajax({
	    url: SEND_PHONE_OTP_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {phone: phone},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
	    },
	    success: function (data) {
		let $phone = $jq('#register_phone');
		let $input_box = $phone.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		
		let $phone_otp = $jq('#register_phone_otp');
		$phone_otp.removeClass('checked-error');
		$phone_otp.removeClass('checked-pass');
		if(!$this.sent_phone_otp){
		    $phone_otp.val('');
		    $phone_otp.attr('disabled','disabled');
		}
		
		let $password = $jq('#register_password');
		$password.val('');
		$password.attr('disabled','disabled');
		$password.removeClass('checked-error');
		$password.removeClass('checked-pass');
		
		alert_message.text('');
		$this.phone = '';
		if(!data['success']){
		    $input_box.addClass('checked-error');
		    alert_message.text(data['message']);
		}else{
		    $this.phone = phone;
		    $input_box.addClass('checked-pass');
		    alert_message.text(data['message']);
		    $this.sent_phone_otp = true;
		    $phone_otp.removeAttr('disabled');
		    $phone_otp.focus();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    this.postCheckPhoneOTP = function(otp){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq.ajax({
	    url: CHECK_PHONE_OTP_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {phone: $this.phone, otp: otp},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
	    },
	    success: function (data) {
		let $phone_otp = $jq('#register_phone_otp');
		let $input_box = $phone_otp.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		
		let $password = $jq('#register_password');
		$password.val('');
		$password.attr('disabled','disabled');
		$password.removeClass('checked-error');
		$password.removeClass('checked-pass');
		
		alert_message.text('');
		$this.phone_otp = '';
		
		if(!data['success']){
		    $input_box.addClass('checked-error');
		    alert_message.text(data['message']);
		}else{
		    alert_message.text(data['message']);
		    $this.phone_otp = otp;
		    let $phone = $jq('#register_phone');
		    $phone.val($this.phone);
		    $phone.attr('disabled','disabled');
		    $phone_otp.attr('disabled','disabled');
		    $input_box.addClass('checked-pass');
		    
		    $password.removeAttr('disabled');
		    $password.focus();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    this.postRegister = function(password){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq.ajax({
	    url: REGISTER_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {username: $this.phone, otp: $this.phone_otp, password: password},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
		$jq('.fhs-register-msg').text($this.languages['tryagain']);
	    },
	    success: function (data) {
		let $phone_otp = $jq('#register_phone_otp');
		let $input_box = $phone_otp.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		alert_message.text('');
		$this.phone_otp = '';
		if(!data['success']){
		    $jq('.fhs-register-msg').text(data['message']);
		}else{
		    $this.savePassword($this.phone, password);
		    
		    // Redirect
		    $this.addToCart(true);
		    
		    //window.location = ACCOUNT_INFO;
		    $this.closeLoginPopup();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    
    this.postSendRecoveryPhoneOTP = function(username){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq('.fhs-register-msg').text('');
	$jq.ajax({
	    url: SEND_RECOVERY_PASSWORD_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {username: username},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
	    },
	    success: function (data) {
		let $phone = $jq('#recovery_phone');
		let $input_box = $phone.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		
		let $phone_otp = $jq('#recovery_phone_otp');
		    $phone_otp.removeClass('checked-error');
		    $phone_otp.removeClass('checked-pass');
		if(!$this.sent_username_recovery_otp){
		    $phone_otp.val('');
		    $phone_otp.attr('disabled','disabled');
		}
		
		let $password = $jq('#recovery_password');
		$password.val('');
		$password.attr('disabled','disabled');
		$password.removeClass('checked-error');
		$password.removeClass('checked-pass');
		
		alert_message.text('');
		$this.username_recovery = '';
		if(!data['success']){
		    $input_box.addClass('checked-error');
		    alert_message.text(data['message']);
		}else{
		    $this.username_recovery = username;
		    $input_box.addClass('checked-pass');
		    alert_message.text(data['message']);
		    $this.sent_username_recovery_otp = true;
		    $phone_otp.removeAttr('disabled');
		    $phone_otp.focus();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    this.postCheckRecoveryPhoneOTP = function(otp){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	if(!$jq.isNumeric($this.username_recovery)){
	    $jq.ajax({
		url: CHECK_EMAIL_OTP_ACCOUNT_URL,
		method: 'post',
		dataType : "json",
		contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
		data: {email: $this.username_recovery, otp: otp},
		error: function() {
		    is_loading = false;
		    $this.animateLoader('stop');
		},
		success: function (data) {
		    let $phone_otp = $jq('#recovery_phone_otp');
		    let $input_box = $phone_otp.parents('.fhs-input-box');
		    let alert_message = $input_box.children('.fhs-input-alert');
		    $input_box.removeClass('checked-error');
		    $input_box.removeClass('checked-pass');

		    let $password = $jq('#recovery_password');
		    $password.val('');
		    $password.attr('disabled','disabled');
		    $password.removeClass('checked-error');
		    $password.removeClass('checked-pass');

		    alert_message.text('');
		    $this.username_recovery_otp = '';

		    if(!data['success']){
			$input_box.addClass('checked-error');
			alert_message.text(data['message']);
		    }else{
			alert_message.text(data['message']);
			$this.username_recovery_otp = otp;

			$phone_otp.attr('disabled','disabled');
			$input_box.addClass('checked-pass');

			$password.removeAttr('disabled');
			$password.focus();
		    }
		    is_loading = false;
		    $this.animateLoader('stop');
		}
	    });
	}else{
	    $jq.ajax({
		url: CHECK_PHONE_OTP_ACCOUNT_URL,
		method: 'post',
		dataType : "json",
		contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
		data: {phone: $this.username_recovery, otp: otp},
		error: function() {
		    is_loading = false;
		    $this.animateLoader('stop');
		},
		success: function (data) {
		    let $phone_otp = $jq('#recovery_phone_otp');
		    let $input_box = $phone_otp.parents('.fhs-input-box');
		    let alert_message = $input_box.children('.fhs-input-alert');
		    $input_box.removeClass('checked-error');
		    $input_box.removeClass('checked-pass');

		    let $password = $jq('#recovery_password');
		    $password.val('');
		    $password.attr('disabled','disabled');
		    $password.removeClass('checked-error');
		    $password.removeClass('checked-pass');

		    alert_message.text('');
		    $this.username_recovery_otp = '';

		    if(!data['success']){
			$input_box.addClass('checked-error');
			alert_message.text(data['message']);
		    }else{
			alert_message.text(data['message']);
			$this.username_recovery_otp = otp;

			let $phone = $jq('#recovery_phone');
			$phone.val($this.username_recovery);
			$phone.attr('disabled','disabled');
			$phone_otp.attr('disabled','disabled');
			$input_box.addClass('checked-pass');

			$password.removeAttr('disabled');
			$password.focus();
		    }
		    is_loading = false;
		    $this.animateLoader('stop');
		}
	    });
	}
    };
    this.postRecovery = function(password){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq.ajax({
	    url: RECOVERY_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {username: $this.username_recovery, otp: $this.username_recovery_otp, password: password},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
		$jq('.fhs-recovery-msg').text($this.languages['tryagain']);
	    },
	    success: function (data) {
		let $phone_otp = $jq('#recovery_phone_otp');
		let $input_box = $phone_otp.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		alert_message.text('');
		$this.username_recovery_otp = '';
		if(!data['success']){
		    $jq('.fhs-recovery-msg').text(data['message']);
		}else{	  
		    $this.savePassword($this.username_recovery, password);  
		    // Redirect
		    if ($this.is_redirect == '1') {
			window.location = $this.redirect_url;
		    } else {
			window.location.reload();
		    }
		    $this.closeLoginPopup();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    
    //events
    this.removeOriginalJsLocations = function() {
	$jq('a[href*="customer/account/login"], ' +
	    '.customer-account-login .new-users button')
	    .attr('onclick', 'return false;');
	$jq('a[href*="customer/account/create"], ' +
	    '.customer-account-register .new-users button')
	    .attr('onclick', 'return false;');
    
    };
    this.openCloseWindowEvents = function() {
	$jq('body').on('click', 'a[href*="customer/account/login"]',function() {
	    if ($jq('.youama-login-window').css('display') == 'none') {
		$this.showLoginPopup('login');                
	    }
	    return false;
	});
	$jq('body').on('click', 'a[href*="customer/account/create"]',function() {
	    if ($jq('.youama-login-window').css('display') == 'none') {
		$this.showLoginPopup('register');        
	    }
	    return false;
	});
	$jq('.youama-login-window .fhs-btn-cancel').click(function() {
	    $this.closeLoginPopup();
	});
    };
    this.eventButtonClick = function(){
	$jq('.youama-ajaxlogin-cover').click(function(){
	    if($jq('.lg-close').is(":visible")){
		$jq('.lg-close').each(function(){
		    if($jq(this).is(":visible")){
			$jq(this).trigger("click");
		    }
		});
	    }
	});
	$jq('.btn-account-login').click(function(){
	    $this.showLoginPopup('login');
	});
	
	$jq('.btn-account-register').click(function(){
	    $this.showLoginPopup('register');
	});
	$jq('.popup-login-tab-item').click(function(){
	   let is_login_tab = $jq(this).hasClass('popup-login-tab-login');
	   if(is_login_tab){
	       $this.tab_change('login');
	   }else{
	       $this.tab_change('register');
	   }
	});
	$jq('.fhs-textbox-alert').click(function(){
	    let $alert_icon = $jq(this);
	    let $input_group = $alert_icon.parents('.fhs-input-group');
	    let $input_box = $alert_icon.parents('.fhs-input-box');
	    let $text_box = $input_group.children('.fhs-textbox');
	    let $alert_msg = $input_box.children('.fhs-input-alert');
	    if($input_box.hasClass('checked-error')){
		$alert_msg.empty();
		$text_box.val('');
		$input_box.removeClass('checked-error');
	    }
	    if($input_group.hasClass('checked-error')){
		$alert_msg.empty();
		$text_box.val('');
		$input_group.removeClass('checked-error');
	    }
	    $text_box.focus();
	});
	$jq('.fhs-textbox-showtext').click(function(){
	    let $show = $jq(this);
	    let $input_group = $show.parents('.fhs-input-group');
	    let $text_box = $input_group.children('.fhs-textbox');
	    if($text_box.attr('type') != 'text'){
		$text_box.prop("type", "text");
		$show.text($this.languages['hide']);
	    }else{
		$text_box.prop("type", "password");
		$show.text($this.languages['show']);
	    }
	    $text_box.focus();
	});
	$jq('.fhs-textbox-send').click(function(){
	    $this.sentPhoneOTP();
	});
	if(!$this.isMobile()){
	    $input_noautofill = $jq("input[autocomplete='off']");
	    $input_noautofill.attr('readonly', true);
	    $input_noautofill.focusin(function (){
		$jq(this).removeAttr('readonly')
	    });
	    $input_noautofill.focusout(function (){
		$jq(this).attr('readonly', true);
	    });
	}
	
	$jq('.fhs-btn-login').click(function(){
	    $this.login();
	});
	$jq('.fhs-btn-fb').click(function(){
	    $this.loginFB();
	});
	$jq('.fhs-forget-pass').click(function(){
	    $this.is_recovery_opening = true;
	    //$jq('.popup-login-tab').children('a').text($this.languages['recoverypassword']);
	    $jq('#popup-login-tab_list').fadeOut(0);
	    $jq('.popup-login-title').fadeIn(0);
	    $jq('.popup-login-content').fadeOut(0);
	    $jq('.popup-recovery-content').fadeIn(0);
	    $jq('#recovery_phone').focus();
	});
	$jq('.fhs-btn-register').click(function(){
	    $this.register();
	});
	
	$jq('.fhs-textbox-recoverysend').click(function(){
	    $this.sentPhoneRecoveryOTP();
	});
	$jq('.fhs-btn-backlogin').click(function(){
	    $this.is_recovery_opening = false;
	    //$jq('.popup-login-tab').children('a').text($this.languages['login']);
	    $jq('#popup-login-tab_list').fadeIn(0);
	    $jq('.popup-login-title').fadeOut(0);
	    $jq('.popup-recovery-content').fadeOut(0);
	    $jq('.popup-login-content').fadeIn(0);
	    $jq('#login_username').focus();
	});
	$jq('.fhs-btn-recovery').click(function(){
	    $this.recovery();
	});
	
	$jq('.fhs-textbox-confirmsend').click(function(){
	    $this.sentPhoneConfirmOTP();
	});
	$jq('.fhs-btn-confirmphone').click(function(){
	    $this.registerFB();
	});
    };
    this.eventInputPress = function(){
	window.onkeyup = function (event) {
	    if(event.keyCode == 27) {
		if($jq('.lg-close').is(":visible")){
		    $jq('.lg-close').each(function(){
			if($jq(this).is(":visible")){
			    $jq(this).trigger("click");
			}
		    });
		}
	    }
	    if(event.keyCode == 13) {
		if($jq('.lg-yes').is(":visible")){
		    $jq('.lg-yes').each(function(){
			if($jq(this).is(":visible")){
			    $jq(this).trigger("click");
			}
		    });
		}
	    }
	};
	//login
	$jq('#login_username').keyup(function(e){
	    let is_pass = false;
	    let username = $jq(this).val().trim();
	    let password = $jq('#login_password').val().trim();
	    
	    if($this.validateData('login_username',username) == '' && password.length >= $this.minLength){
		is_pass = true;
	    }
	    
	    $this.enableLoginButton(is_pass);
	    
	    let keycode = (e.keyCode ? e.keyCode : e.which);
	    if(keycode == '13'){
		$this.login();
	    }
	});
	$jq('#login_password').keyup(function(e){
	    let is_pass = false;
	    
	    let username = $jq('#login_username').val().trim();
	    let password = $jq(this).val().trim();
	    
	    
	    if($this.validateData('login_username',username) == '' && password.length >= $this.minLength){
		is_pass = true;
	    }
	    
	    $this.enableLoginButton(is_pass);
	    
	    let keycode = (e.keyCode ? e.keyCode : e.which);
	    if(keycode == '13'){
		$this.login();
	    }
	});
	
	//register account
	$jq('#register_phone').keydown(function(e){
	    let key = '';
	    let keyCode = 0;
	    if (e.type === 'paste') {
		key = e.clipboardData.getData('text/plain');
	    }else{
		keyCode = (e.keyCode ? e.keyCode : e.which);
		key = String.fromCharCode(keyCode);
	    }
	    
	    if(!e.ctrlKey && !e.altKey){
		if(!((keyCode >= 37) && (keyCode <= 40))  && (keyCode != 17) && !((keyCode >= 8)  && (keyCode <= 9)) && !((keyCode >= 46)  && (keyCode <= 47)) && (keyCode != 49) && (keyCode != 116)){
		    if(!((keyCode >= 48 && keyCode <= 57) || (keyCode >= 96 && keyCode <= 105))){
			let regex = /[0-9]|\./;
			if(!regex.test(key)) {
			  e.returnValue = false;
			  if(e.preventDefault) e.preventDefault();
			}  
		    }
		}
	    }
	});
	$jq('#register_phone').keyup(function(e){
	    let is_pass = false;
	    
	    let phone = $jq(this).val().trim();
	    let phone_otp = $jq('#register_phone_otp').val().trim();
	    let password = $jq('#register_password').val().trim();
	    
	    if(phone.length >= 10&& phone_otp.length == 6  && password.length >= $this.minLength){
		is_pass = true;
	    }
	    $this.enableRegisterButton(is_pass);
	    
	    let keycode = (e.keyCode ? e.keyCode : e.which);
	    if(keycode == '13'){
		$this.sentPhoneOTP();
	    }
	});
	$jq('#register_phone_otp').keyup(function(e){
	    let is_pass = false;
	    
	    let phone = $jq('#register_phone').val().trim();
	    let phone_otp = $jq(this).val().trim();
	    let password = $jq('#register_password').val().trim();
	    
	    if(phone.length >= 10&& phone_otp.length == 6  && password.length >= $this.minLength && $this.sent_phone_otp){
		is_pass = true;
	    }
	    $this.enableRegisterButton(is_pass);
	    
	    if(!$this.validateData('register_phone_otp',phone_otp) && !$this.validateData('register_phone',phone) && $this.sent_phone_otp){
		$this.postCheckPhoneOTP(phone_otp);
	    }
	})
	.on('paste', function(e) {
	     setTimeout(function () {
                let is_pass = false;
	    
		let phone = $jq('#register_phone').val().trim();
		let phone_otp = $jq('#register_phone_otp').val().trim();
		let password = $jq('#register_password').val().trim();

		if(phone.length >= 10&& phone_otp.length == 6  && password.length >= $this.minLength && $this.sent_phone_otp){
		    is_pass = true;
		}
		$this.enableRegisterButton(is_pass);

		if(!$this.validateData('register_phone_otp',phone_otp) && !$this.validateData('register_phone',phone) && $this.sent_phone_otp){
		    $this.postCheckPhoneOTP(phone_otp);
		}
	    }, 100);
	});
	$jq('#register_password').keyup(function(e){
	    let is_pass = false;
	    
	    let phone = $jq('#register_phone').val().trim();
	    let phone_otp = $jq('#register_phone_otp').val().trim();
	    let password = $jq(this).val().trim();
	    
	    if(phone.length >= 10&& phone_otp.length == 6  && password.length >= $this.minLength){
		is_pass = true;
	    }
	    $this.enableRegisterButton(is_pass);
	    
	    let keycode = (e.keyCode ? e.keyCode : e.which);
	    if(keycode == '13'){
		$this.register();
	    }
	});
	
	//recovery account
	$jq('#recovery_phone').keyup(function(e){
	    let is_pass = false;
	    
	    let phone = $jq(this).val().trim();
	    let phone_otp = $jq('#recovery_phone_otp').val().trim();
	    let password = $jq('#recovery_password').val().trim();
	    
	    if(!$this.validateData('login_username',phone) && password.length >= $this.minLength){
		is_pass = true;
	    }
	    $this.enableRecoveryButton(is_pass);
	    
	    let keycode = (e.keyCode ? e.keyCode : e.which);
	    if(keycode == '13'){
		$this.sentPhoneRecoveryOTP();
	    }
	});
	$jq('#recovery_phone_otp').keyup(function(e){
	    let is_pass = false;
	    
	    let phone = $jq('#recovery_phone').val().trim();
	    let phone_otp = $jq(this).val().trim();
	    let password = $jq('#recovery_password').val().trim();
	    
	    if(!$this.validateData('login_username',phone) && password.length >= $this.minLength && $this.sent_username_recovery_otp){
		is_pass = true;
	    }
	    $this.enableRecoveryButton(is_pass);
	    
	    if(!$this.validateData('recovery_phone_otp',phone_otp) && !$this.validateData('login_username',phone) && $this.sent_username_recovery_otp){
		$this.postCheckRecoveryPhoneOTP(phone_otp);
	    }
	})
	.on('paste', function(e) {
	     setTimeout(function () {
                let is_pass = false;

		let phone = $jq('#recovery_phone').val().trim();
		let phone_otp = $jq('#recovery_phone_otp').val().trim();
		let password = $jq('#recovery_password').val().trim();

		if(!$this.validateData('login_username',phone) && password.length >= $this.minLength && $this.sent_username_recovery_otp){
		    is_pass = true;
		}
		$this.enableRecoveryButton(is_pass);

		if(!$this.validateData('recovery_phone_otp',phone_otp) && !$this.validateData('login_username',phone) && $this.sent_username_recovery_otp){
		    $this.postCheckRecoveryPhoneOTP(phone_otp);
		}
	    }, 100);
	});
	$jq('#recovery_password').keyup(function(e){
	    let is_pass = false;
	    
	    let phone = $jq('#recovery_phone').val().trim();
	    let phone_otp = $jq('#recovery_phone_otp').val().trim();
	    let password = $jq(this).val().trim();
	    
	    if(!$this.validateData('login_username',phone) && phone_otp.length == 6  && password.length >= $this.minLength){
		is_pass = true;
	    }
	    $this.enableRecoveryButton(is_pass);
	    
	    let keycode = (e.keyCode ? e.keyCode : e.which);
	    if(keycode == '13'){
		$this.recovery();
	    }
	});
	
		
	//confirm Phone fb
	$jq('#confirm_phone').keydown(function(e){
	    let key = '';
	    let keyCode = 0;
	    if (e.type === 'paste') {
		key = e.clipboardData.getData('text/plain');
	    }else{
		keyCode = (e.keyCode ? e.keyCode : e.which);
		key = String.fromCharCode(keyCode);
	    }
	    
	    if(!e.ctrlKey && !e.altKey){
		if(!((keyCode >= 37) && (keyCode <= 40))  && (keyCode != 17) && !((keyCode >= 8)  && (keyCode <= 9)) && !((keyCode >= 46)  && (keyCode <= 47)) && (keyCode != 49) && (keyCode != 116)){
		    if(!((keyCode >= 48 && keyCode <= 57) || (keyCode >= 96 && keyCode <= 105))){
			let regex = /[0-9]|\./;
			if(!regex.test(key)) {
			  e.returnValue = false;
			  if(e.preventDefault) e.preventDefault();
			}  
		    }
		}
	    }
	});
	$jq('#confirm_phone').keyup(function(e){
	    let is_pass = false;
	    
	    let phone = $jq(this).val().trim();
	    let phone_otp = $jq('#confirm_phone_otp').val().trim();
	    
	    if(phone.length >= 10&& phone_otp.length == 6){
		is_pass = true;
	    }
	    $this.enableConfirmPhoneButton(is_pass);
	    
	    let keycode = (e.keyCode ? e.keyCode : e.which);
	    if(keycode == '13'){
		$this.sentPhoneConfirmOTP();
	    }
	});
	$jq('#confirm_phone_otp').keyup(function(e){
	    let is_pass = false;
	    
	    let phone = $jq('#confirm_phone').val().trim();
	    let phone_otp = $jq(this).val().trim();
	    
	    if(phone.length >= 10&& phone_otp.length == 6  && $this.sent_phone_confirm_otp && ($this.phone_confirm_otp != '')){
		is_pass = true;
	    }
	    $this.enableConfirmPhoneButton(is_pass);
	    
	    if(!$this.validateData('otp',phone_otp) && !$this.validateData('phone',phone) && $this.sent_phone_confirm_otp){
		$this.postCheckConfirmPhoneOTP(phone_otp);
	    }
	})
	.on('paste', function(e) {
	     setTimeout(function () {
                let is_pass = false;

		let phone = $jq('#confirm_phone').val().trim();
		let phone_otp = $jq('#confirm_phone_otp').val().trim();

		if(phone.length >= 10&& phone_otp.length == 6  && $this.sent_phone_confirm_otp && ($this.phone_confirm_otp != '')){
		    is_pass = true;
		}
		$this.enableConfirmPhoneButton(is_pass);

		if(!$this.validateData('otp',phone_otp) && !$this.validateData('phone',phone) && $this.sent_phone_confirm_otp){
		    $this.postCheckConfirmPhoneOTP(phone_otp);
		}
	    }, 100);
	});
    };
    
    //operation
    this.showLoginPopup = function(windowName) {
	if($this.is_recovery_opening){
	    $this.is_recovery_opening = false;
	    $jq('#popup-login-tab_list').fadeIn(0);
	    $jq('.popup-login-title').fadeOut(0);
	    $jq('.popup-recovery-content').fadeOut(0);
	    $jq('.popup-login-content').fadeIn(0);
	}
	    
	$jq('.youama-ajaxlogin-cover').fadeIn();
	if(!$this.isShowLoginform){
	    $jq('.youama-login-window').slideDown(500);
	}else{
	    $jq('.youama-login-window').addClass('fhs_popup_show');
	    $jq('.youama-login-window .fhs-btn-cancel').fadeIn(0);
	    $jq('.youama-login-window').slideDown(500);
	}
	if(windowName != 'register'){
	    $this.tab_change('login');
	}else{
	    $this.tab_change('register');
	}
    };
    this.closeLoginPopup = function() {
	if(!$this.isShowLoginform){
	    $jq('.youama-login-window').slideUp();
	}else{
	    $jq('.youama-login-window .fhs-btn-cancel').fadeOut(0);
	    $jq('.youama-login-window').removeClass('fhs_popup_show');
	}
	if($this.is_login_facebook){
	    $jq('.youama-confirm-window').fadeIn();
	    if($this.isShowLoginform){
		$jq('.youama-ajaxlogin-cover').fadeIn();
	    }
	}else{
	    $jq('.youama-ajaxlogin-cover').fadeOut();
	}
	$this.resetLoginPopup();
    };
    this.resetLoginPopup = function(){
	$jq('.youama-login-window .fhs-input-box').removeClass('checked-error');
	$jq('.youama-login-window .fhs-input-box').removeClass('checked-pass');
	$jq('.youama-login-window .fhs-input-alert').text('');
	
	//login tab
	$jq('.youama-login-window .fhs-login-msg').text('');
	
	$this.add_to_cart_data = {};
    };
    
    this.tab_change = function(tab_name){
	$jq('.popup-login-tab-item').removeClass('active');
	if(tab_name != 'register'){
	    $jq('.popup-login-tab-login').addClass('active');
	    $jq('.popup-register-content').fadeOut(0);
	    if(!$this.is_recovery_opening){
		$jq('.popup-recovery-content').fadeOut(0);
		$jq('.popup-login-content').fadeIn(0);
		$jq('#login_username').focus();
	    }else{
		$jq('.popup-login-content').fadeOut(0);
		$jq('.popup-recovery-content').fadeIn(0);
		$jq('#recovery_phone').focus();
	    }
	}else{
	    $jq('.popup-login-tab-register').addClass('active');
	    $jq('.popup-login-content').fadeOut(0);
	    $jq('.popup-recovery-content').fadeOut(0);
	    $jq('.popup-register-content').fadeIn(0);
	    $jq('#register_phone').focus();
	}
    };
    this.enableLoginButton = function(is_enable){
	if(is_enable){
	    $jq('.fhs-btn-login').removeAttr("disabled");
	}else{
	    $jq('.fhs-btn-login').attr("disabled", "disabled");
	}
    };
    this.enableRegisterButton = function(is_enable){
	if(is_enable){
	    $jq('.fhs-btn-register').removeAttr("disabled");
	}else{
	    $jq('.fhs-btn-register').attr("disabled", "disabled");
	}
    };
    this.enableRecoveryButton = function(is_enable){
	if(is_enable){
	    $jq('.fhs-btn-recovery').removeAttr("disabled");
	}else{
	    $jq('.fhs-btn-recovery').attr("disabled", "disabled");
	}
    };
    
    this.validateTextbox = function(name, text, $element){
	let result = false;
	let $input_box = $element.parents('.fhs-input-box');
	let $alert_message = $input_box.children('.fhs-input-alert');
	$input_box.removeClass('checked-error');
	$input_box.removeClass('checked-error-text');
	$input_box.removeClass('checked-pass');
	$input_box.removeClass('checked-warning');
	$input_box.removeClass('checked-msg');
	$alert_message.text('');
	let message = this.validateData(name, text);
	if(!$this.isEmpty(message)){
	    $input_box.addClass('checked-error');
	    $alert_message.text(message);
	}else{
	    result = true;
	}
	return result;
    };
    this.validateTextboxInGroup = function(name, text, $element){
	let result = '';
	let $input_box = $element.parents('.fhs-input-box');
	let $input_group = $element.parents('.fhs-input-group');
	let $alert_message = $input_box.children('.fhs-input-alert');
	$alert_message.text('');
	$input_box.removeClass('checked-group-error');
	$input_group.removeClass('checked-error');
	let message = this.validateData(name, text);
	if(!$this.isEmpty(message)){
	    $input_group.addClass('checked-error');
	    result = message;
	}
	return result;
    };
    this.validateTextboxGroup = function($element){
	let result = true;
	let $input_box = $element.parents('.fhs-input-box');
	let $textboxs = $input_box.find('.fhs-textbox');
	let $alert_message = $input_box.children('.fhs-input-alert');
	let message = '';
	$textboxs.each(function(){
	    if($jq(this).is(":visible")){
		if($jq(this).hasClass('require_group_check')){
		    let vailidated = $this.validateTextboxInGroup($jq(this).attr('validate_type'), $jq(this).val().trim(), $jq(this));
		    if(!$this.isEmpty(vailidated)){
			if($this.isEmpty(message)){
			    message = vailidated;
			    result = false;
			}
		    }
		}
	    }
	});
	if(!$this.isEmpty(message)){
	    $input_box.addClass('checked-group-error');
	    $alert_message.text(message);
	}
	return result;
    };
    this.removeAlert = function($element){
	let $input_box = $element.parents('.fhs-input-box');
	let $input_group = $input_box.find('.fhs-input-group');
	let alert_message = $input_box.children('.fhs-input-alert');
	$input_group.removeClass('checked-error');
	$input_box.removeClass('checked-error');
	$input_box.removeClass('checked-group-error');
	alert_message.text('');
    };
    this.validateData = function(name, text){
	let result = "";
	switch(name){
	    case 'date':
		if(text.length < 1){
		    result = $this.languages['notempty'];
		}else{
		    if(text.length != 10 || text == '0'){
			result = $this.languages['dateinvalid'];
		    }
		}
		break;
	    case 'text':
		if(text.length < 1){
		    result = $this.languages['notempty'];
		}else{
		    if(text.length > 200){
			result = $this.languages['over200char'];
		    }
		}
		break;
	    case 'login_username':
		if(text.length < 1){
		    result = $this.languages['notempty'];
		}else{
		    if(!this.validateEmail(text)){
			if($jq.isNumeric(text)){
			    if((text.length < 10 || text.length > 11)){
				result = $this.languages['phoneinvalid'];
			    }
			}else{
			    result = $this.languages['emailinvalid'];
			}
		    }
		}
		break;
	    case 'email':
	    case 'change_email':
		if(text.length < 1){
		    result = $this.languages['notempty'];
		}else{
		    if(!this.validateEmail(text)){
			result = $this.languages['emailinvalid'];
		    }
		}
		break;
	    case 'password':
	    case 'recovery_password':
	    case 'register_password':
	    case 'login_password':
		if(text.length < 1){
		    result = $this.languages['notempty'];
		}else{
		    if(text.length < $this.minLength){
			result = $this.languages['minLength'];
		    }
		    if(text.length > 30){
			result = $this.languages['30char'];
		    }
		}
		break;
	    case 'phone':
	    case 'change_phone':
	    case 'recovery_phone':
	    case 'register_phone':
		if(text.length < 1){
		    result = $this.languages['notempty'];
		}else{
		    if((text.length < 10 || text.length > 11)){
			result = $this.languages['phoneinvalid'];
		    }
		}
		break;
	    case 'shipping_telephone':
		if(text.length < 1){
		    result = $this.languages['notempty'];
		}else{
		    if((text.length <= 9 || text.length > 11)){
			result = $this.languages['phoneinvalid10'];
		    }
		}
		break;
	    case 'otp':
	    case 'change_email_otp':
	    case 'change_phone_otp':
	    case 'recovery_phone_otp':
	    case 'register_phone_otp':
		if(text.length < 1){
		    result = $this.languages['notempty'];
		}else{
		    if(text.length != 6){
			result = $this.languages['otpinvalid'];
		    }
		}
		break;
	    case 'fullname':
		if(text.length < 1){
		    result = $this.languages['notempty'];
		}else{
		    if((/^\S+$/g.test(text))){
			result = $this.languages['2word'];
		    }
		}
		break;
	    case 'taxcode':
		if(text.length < 1){
		    result = $this.languages['notempty'];
		}else{
		    if(text.length > 200){
			result = $this.languages['over200char'];
		    }else{
			if(!$this.validateTaxcode(text)){
			    result = $this.languages['taxcodeinvalid'];
			}
		    }
		}
		break;
	    case 'address':
		if(text.length < 1){
		    result = $this.languages['notempty'];
		}else{
		    if(text.length < 10){
			result = $this.languages['minLength_address'].replace($this.minLength, "10");
		    }else if(text.length > 200){
			result = $this.languages['over200char'];
		    }
		}
		break;
	}
	return result;
    };
    this.isInputTexted = function($element){
	let result = false;
	let $input_box = $element.parents('.fhs-input-box');
	let $input_group = $element.parents('.fhs-input-group');
	let text_value = $element.val();

	if($input_group.length){
	    if(!fhs_account.isEmpty(text_value)){
		$input_group.addClass('texting');
		result = true;
	    }
	}else if($input_box.length){
	    if(!fhs_account.isEmpty(text_value)){
		$input_box.addClass('texting');
		result = true;
	    }
	}
	return result;
    };
    
    this.login = function(){
	let username = $jq('#login_username');
	let password = $jq('#login_password');
	
	let username_validated = $this.validateTextbox('login_username',username.val().trim(), username);
	let password_validated = $this.validateTextbox('login_password',password.val().trim(), password);
	
	if(username_validated && password_validated){
	    $this.postLogin(username.val().trim(),password.val().trim());
	}
    };
    
    //register
    this.sentPhoneOTP = function(){
	if($this.phone_otp != ''){return;}
	
	let phone = $jq('#register_phone');
	let phone_validated = $this.validateTextbox('register_phone',phone.val().trim(), phone);
	
	if(!$this.sent_phone_otp){
	    let $phone_otp = $jq('#register_phone_otp');
	    $phone_otp.val('');
	    $phone_otp.attr('disabled','disabled');
	}
	
	if(phone_validated){
	    $this.postSendPhoneOTP(phone.val().trim());
	}
    };
    this.register = function(){
	let password = $jq('#register_password');
	
	let password_validated = $this.validateTextbox('register_password',password.val().trim(), password);
	
	if(password_validated){
	    $this.postRegister(password.val().trim());
	}
    };
    
    //recovery
    this.sentPhoneRecoveryOTP = function(){
	if($this.username_recovery_otp != ''){return;}
	
	let phone = $jq('#recovery_phone');
	let phone_validated = $this.validateTextbox('login_username',phone.val().trim(), phone);
	
	if(!$this.sent_username_recovery_otp){
	    let $phone_otp = $jq('#recovery_phone_otp');
	    $phone_otp.val('');
	    $phone_otp.attr('disabled','disabled');
	}
	
	if(phone_validated){
	    $this.postSendRecoveryPhoneOTP(phone.val().trim());
	}
    };
    this.recovery = function(){
	let password = $jq('#recovery_password');
	
	let password_validated = $this.validateTextbox('recovery_password',password.val().trim(), password);
	
	if(password_validated){
	    $this.postRecovery(password.val().trim());
	}
    };
    
//Account info----------------------------------
    this.initAccountInfo = function(){
	$this.phone_change = '';
	$this.phone_change_otp = '';
	$this.sent_phone_change_otp = false;
    
	$this.email_change = '';
	$this.email_change_otp = '';
	$this.sent_email_change_otp = false;
	
	$this.eventBtnAccount();
	$this.eventPressAccount();
    };
    
    //post
    this.postSendChangePhoneOTP = function(phone){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq('.fhs-changephone-msg').text('');
	$jq.ajax({
	    url: SEND_CHANGE_PHONE_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {phone: phone},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
	    },
	    success: function (data) {
		let $phone = $jq('#change_phone');
		let $input_box = $phone.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		
		let $phone_otp = $jq('#change_phone_otp');
		$phone_otp.removeClass('checked-error');
		$phone_otp.removeClass('checked-pass');
		if(!$this.sent_phone_change_otp){
		    $phone_otp.val('');
		    $phone_otp.attr('disabled','disabled');
		}
		
		alert_message.text('');
		$this.phone_change = '';
		if(!data['success']){
		    $input_box.addClass('checked-error');
		    alert_message.text(data['message']);
		}else{
		    $this.phone_change = phone;
		    $input_box.addClass('checked-pass');
		    alert_message.text(data['message']);
		    $this.sent_phone_change_otp = true;
		    $phone_otp.removeAttr('disabled');
		    $phone_otp.focus();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    this.postCheckChangePhoneOTP = function(otp){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq.ajax({
	    url: CHECK_PHONE_OTP_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {phone: $this.phone_change, otp: otp},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
	    },
	    success: function (data) {
		let $phone_otp = $jq('#change_phone_otp');
		let $input_box = $phone_otp.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		
		alert_message.text('');
		$this.phone_change_otp = '';
		
		if(!data['success']){
		    $input_box.addClass('checked-error');
		    alert_message.text(data['message']);
		}else{
		    alert_message.text(data['message']);
		    $this.phone_change_otp = otp;
		    
		    let $phone = $jq('#change_phone');
		    $phone.val($this.phone_change);
		    $phone.attr('disabled','disabled');
		    $phone_otp.attr('disabled','disabled');
		    $input_box.addClass('checked-pass');
		    
		    $this.enableChangePhoneButton(true);
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    this.postChangePhone = function(){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq.ajax({
	    url: CHANGE_PHONE_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {username: $this.phone_change, otp: $this.phone_change_otp},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
		$jq('.fhs-changephone-msg').text($this.languages['tryagain']);
	    },
	    success: function (data) {
		let $phone_otp = $jq('#change_phone_otp');
		let $input_box = $phone_otp.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		alert_message.text('');
		$this.username_recovery_otp = '';
		if(!data['success']){
		    $jq('.fhs-changephone-msg').text(data['message']);
		}else{	
		    let $phone = $jq('#telephone');
		    if(!$this.isEmpty($phone.attr('noti'))){
			let $phone_input_box = $phone.parents('.fhs-input-box');
			let $phone_input_group = $phone.parents('.fhs-input-group');
			let $phone_description = $phone_input_box.children('.fhs-input-description');
			$phone_description.text('');
			let $phone_changephone_text = $phone_input_group.children('.fhs-textbox-changephone');
			$phone_changephone_text.text($this.languages['change']);
			setTimeout(loadNoticationTop,100);
		    }
		    $phone.val($this.phone_change);
		    $this.clearChangePhone();
		    $this.changePhoneClose();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    
    this.postSendChangeEmailOTP = function(email){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq('.fhs-changeemail-msg').text('');
	$jq.ajax({
	    url: SEND_CHANGE_EMAIL_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {email: email},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
	    },
	    success: function (data) {
		let $email = $jq('#change_email');
		let $input_box = $email.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		
		let $email_otp = $jq('#change_email_otp');
		$email_otp.removeClass('checked-error');
		$email_otp.removeClass('checked-pass');
		if(!$this.sent_email_change_otp){
		    $email_otp.val('');
		    $email_otp.attr('disabled','disabled');
		}
		alert_message.text('');
		$this.email_change = '';
		if(!data['success']){
		    $input_box.addClass('checked-error');
		    alert_message.text(data['message']);
		}else{
		    $this.email_change = email;
		    $input_box.addClass('checked-pass');
		    alert_message.text(data['message']);
		    $this.sent_email_change_otp = true;
		    $email_otp.removeAttr('disabled');
		    $email_otp.focus();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    this.postCheckChangeEmailOTP = function(otp){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq.ajax({
	    url: CHECK_EMAIL_OTP_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {email: $this.email_change, otp: otp},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
	    },
	    success: function (data) {
		let $email_otp = $jq('#change_email_otp');
		let $input_box = $email_otp.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		
		alert_message.text('');
		$this.email_change_otp = '';
		
		if(!data['success']){
		    $input_box.addClass('checked-error');
		    alert_message.text(data['message']);
		}else{
		    alert_message.text(data['message']);
		    $this.email_change_otp = otp;
		    
		    let $email = $jq('#change_email');
		    $email.val($this.email_change);
		    $email.attr('disabled','disabled');
		    $email_otp.attr('disabled','disabled');
		    $input_box.addClass('checked-pass');
		    
		    $this.enableChangeEmailButton(true);
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    this.postChangeEmail = function(){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq.ajax({
	    url: CHANGE_EMAIL_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {email: $this.email_change, otp: $this.email_change_otp},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
		$jq('.fhs-changeemail-msg').text($this.languages['tryagain']);
	    },
	    success: function (data) {
		let $email_otp = $jq('#change_email_otp');
		let $input_box = $email_otp.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		alert_message.text('');
		$this.email_change_otp = '';
		if(!data['success']){
		    $jq('.fhs-changeemail-msg').text(data['message']);
		}else{	
		    let $email = $jq('#email');
		    if(!$this.isEmpty($email.attr('noti'))){
			let $email_input_box = $email.parents('.fhs-input-box');
			let $email_input_group = $email.parents('.fhs-input-group');
			let $email_description = $email_input_box.children('.fhs-input-description');
			$email_description.text('');
			let $email_changeemail_text = $email_input_group.children('.fhs-textbox-changeemail');
			$email_changeemail_text.text($this.languages['change']);
			setTimeout(loadNoticationTop,100);
		    }
		    setTimeout(function(){$this.execute(data['netcore_contact']);},100);
		    $email.val($this.email_change);
		    $this.clearChangeEmail();
		    $this.changeEmailClose();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    
    //process
    this.clearChangePhone = function(){
	$this.phone_change = '';
	$this.phone_change_otp = '';
	$this.sent_phone_change_otp = false;
	
	let $phone_change = $jq('#change_phone');
	$phone_change.val('');
	$phone_change.removeAttr('disabled');
	let $phone_change_input_box = $phone_change.parents('.fhs-input-box');
	$phone_change_input_box.removeClass('checked-error');
	$phone_change_input_box.removeClass('checked-pass');
	let $phone_change_alert_message = $phone_change_input_box.children('.fhs-input-alert');
	$phone_change_alert_message.text('');
		
	let $phone_otp = $jq('#change_phone_otp');
	$phone_otp.val('');
	$phone_otp.attr('disabled','disabled');
	let $phone_otp_input_box = $phone_otp.parents('.fhs-input-box');
	$phone_otp_input_box.removeClass('checked-error');
	$phone_otp_input_box.removeClass('checked-pass');
	let $phone_otp_alert_message = $phone_otp_input_box.children('.fhs-input-alert');
	$phone_otp_alert_message.text('');
	
	$jq('.fhs-changephone-msg').text('');
    }
    this.enableChangePhoneButton = function(is_enable){
	if(is_enable){
	    $jq('.fhs-btn-changephone').removeAttr("disabled");
	}else{
	    $jq('.fhs-btn-changephone').attr("disabled", "disabled");
	}
    };
    this.changePhoneShow = function(){
	$jq('.youama-ajaxlogin-cover').fadeIn();
	$jq('.youama-changePhone-window').fadeIn();
	$jq('#change_phone').focus();
    };
    this.changePhoneClose = function(){
	$jq('.youama-changePhone-window').fadeOut();
	$jq('.youama-ajaxlogin-cover').fadeOut();
    };
    
    this.clearChangeEmail = function(){
	$this.email_change = '';
	$this.email_change_otp = '';
	$this.sent_email_change_otp = false;
	
	let $email_change = $jq('#change_email');
	$email_change.val('');
	$email_change.removeAttr('disabled');
	let $email_change_input_box = $email_change.parents('.fhs-input-box');
	$email_change_input_box.removeClass('checked-error');
	$email_change_input_box.removeClass('checked-pass');
	let $email_change_alert_message = $email_change_input_box.children('.fhs-input-alert');
	$email_change_alert_message.text('');
	
	let $email_otp = $jq('#change_email_otp');
	$email_otp.val('');
	$email_otp.attr('disabled','disabled');
	let $email_otp_input_box = $email_change.parents('.fhs-input-box');
	$email_otp_input_box.removeClass('checked-error');
	$email_otp_input_box.removeClass('checked-pass');
	let $email_otp_alert_message = $email_otp_input_box.children('.fhs-input-alert');
	$email_otp_alert_message.text('');
	
	$jq('.fhs-changeemail-msg').text('');
    };
    this.enableChangeEmailButton = function(is_enable){
	if(is_enable){
	    $jq('.fhs-btn-changeemail').removeAttr("disabled");
	}else{
	    $jq('.fhs-btn-changeemail').attr("disabled", "disabled");
	}
    };
    this.changeEmailShow = function(){
	$jq('.youama-ajaxlogin-cover').fadeIn();
	$jq('.youama-changeEmail-window').fadeIn();
	$jq('#change_email').focus();
    };
    this.changeEmailClose = function(){
	$jq('.youama-changeEmail-window').fadeOut();
	$jq('.youama-ajaxlogin-cover').fadeOut();
    };
    
    //event
    this.eventBtnAccount = function(){
	if(!$this.isMobile()){
	    $input_noautofill = $jq("input[autocomplete='off']");
	    $input_noautofill.attr('readonly', true);
	    $input_noautofill.focusin(function (){
		$jq(this).removeAttr('readonly')
	    });
	    $input_noautofill.focusout(function (){
		$jq(this).attr('readonly', true);
	    });
	}
	
	$jq('.fhs-textbox-alert').click(function(){
	    let $alert_icon = $jq(this);
	    let $input_group = $alert_icon.parents('.fhs-input-group');
	    let $input_box = $alert_icon.parents('.fhs-input-box');
	    let $text_box = $input_group.children('.fhs-textbox');
	    if($input_box.hasClass('checked-error')){
		let $input_box = $input_group.parents('.fhs-input-box');
		let $alert_msg = $input_box.children('.fhs-input-alert');
		$alert_msg.empty();
		$text_box.val('');
		$input_box.removeClass('checked-error');
	    }
	    $text_box.focus();
	});
	
	//phone
	$jq('.fhs-textbox-changephone').click(function(){
	    $this.changePhoneShow();
	});
	$jq('.fhs-btn-backPhone').click(function(){
	    $this.changePhoneClose();
	});
	$jq('.fhs-textbox-phonesend').click(function(){
	    $this.sentPhoneChangeOTP();
	});
	$jq('.fhs-btn-changephone').click(function(){
	    $this.changePhone();
	});
	
	//email
	$jq('.fhs-textbox-changeemail').click(function(){
	    $this.changeEmailShow();
	});
	$jq('.fhs-btn-backemail').click(function(){
	    $this.changeEmailClose();
	});
	$jq('.fhs-textbox-emailsend').click(function(){
	    $this.sentEmailChangeOTP();
	});
	$jq('.fhs-btn-changeemail').click(function(){
	    $this.changeEmail();
	});
    };
    this.eventPressAccount = function(){
	//Change Phone
	$jq('#change_phone').keydown(function(e){
	    let key = '';
	    let keyCode = 0;
	    if (e.type === 'paste') {
		key = e.clipboardData.getData('text/plain');
	    }else{
		keyCode = (e.keyCode ? e.keyCode : e.which);
		key = String.fromCharCode(keyCode);
	    }
	    
	    if(!e.ctrlKey && !e.altKey){
		if(!((keyCode >= 37) && (keyCode <= 40))  && (keyCode != 17) && !((keyCode >= 8)  && (keyCode <= 9)) && !((keyCode >= 46)  && (keyCode <= 47)) && (keyCode != 49) && (keyCode != 116)){
		    if(!((keyCode >= 48 && keyCode <= 57) || (keyCode >= 96 && keyCode <= 105))){
			let regex = /[0-9]|\./;
			if(!regex.test(key)) {
			  e.returnValue = false;
			  if(e.preventDefault) e.preventDefault();
			}  
		    }
		}
	    }
	});
	$jq('#change_phone').keyup(function(e){
	    let is_pass = false;
	    
	    let phone = $jq(this).val().trim();
	    let phone_otp = $jq('#change_phone_otp').val().trim();
	    
	    if(phone.length >= 10&& phone_otp.length == 6){
		is_pass = true;
	    }
	    $this.enableChangePhoneButton(is_pass);
	    
	    let keycode = (e.keyCode ? e.keyCode : e.which);
	    if(keycode == '13'){
		$this.sentPhoneChangeOTP();
	    }
	});
	$jq('#change_phone_otp').keyup(function(e){
	    let is_pass = false;
	    
	    let phone = $jq('#change_phone').val().trim();
	    let phone_otp = $jq(this).val().trim();
	    
	    if(phone.length >= 10&& phone_otp.length == 6  && $this.sent_phone_change_otp && ($this.phone_change_otp != '')){
		is_pass = true;
	    }
	    $this.enableChangePhoneButton(is_pass);
	    
	    if(!$this.validateData('change_phone_otp',phone_otp) && !$this.validateData('change_phone',phone) && $this.sent_phone_change_otp){
		$this.postCheckChangePhoneOTP(phone_otp);
	    }
	})
	.on('paste', function(e) {
	     setTimeout(function () {
                let is_pass = false;
	    
		let phone = $jq('#change_phone').val().trim();
		let phone_otp = $jq('#change_phone_otp').val().trim();

		if(phone.length >= 10&& phone_otp.length == 6  && $this.sent_phone_change_otp && ($this.phone_change_otp != '')){
		    is_pass = true;
		}
		$this.enableChangePhoneButton(is_pass);

		if(!$this.validateData('change_phone_otp',phone_otp) && !$this.validateData('change_phone',phone) && $this.sent_phone_change_otp){
		    $this.postCheckChangePhoneOTP(phone_otp);
		}
	    }, 100);
	});
	
	//Press Email
	$jq('#change_email').keyup(function(e){
	    let is_pass = false;
	    
	    let email = $jq(this).val().trim();
	    let email_otp = $jq('#change_email_otp').val().trim();
	    
	    if(email.length >= 10&& email_otp.length == 6){
		is_pass = true;
	    }
	    $this.enableChangeEmailButton(is_pass);
	    
	    let keycode = (e.keyCode ? e.keyCode : e.which);
	    if(keycode == '13'){
		$this.sentEmailChangeOTP();
	    }
	});
	$jq('#change_email_otp').keyup(function(e){
	    let is_pass = false;
	    
	    let email = $jq('#change_email').val().trim();
	    let email_otp = $jq(this).val().trim();
	    
	    if(email.length >= 10&& email_otp.length == 6  && $this.sent_email_change_otp && ($this.email_change_otp != '')){
		is_pass = true;
	    }
	    $this.enableChangeEmailButton(is_pass);
	    
	    if(!$this.validateData('change_email_otp',email_otp) && !$this.validateData('change_email',email) && $this.sent_email_change_otp){
		$this.postCheckChangeEmailOTP(email_otp);
	    }
	})
	.on('paste', function(e) {
	     setTimeout(function () {
                let is_pass = false;
	    
		let email = $jq('#change_email').val().trim();
		let email_otp = $jq('#change_email_otp').val().trim();

		if(email.length >= 10&& email_otp.length == 6  && $this.sent_email_change_otp && ($this.email_change_otp != '')){
		    is_pass = true;
		}
		$this.enableChangeEmailButton(is_pass);

		if(!$this.validateData('change_email_otp',email_otp) && !$this.validateData('change_email',email) && $this.sent_email_change_otp){
		    $this.postCheckChangeEmailOTP(email_otp);
		}
	    }, 100);
	});
    };
    
    this.setDateBox = function(date_box){
	let $day = $jq(date_box+' .fhs_input_date_group_day');
	let $month = $jq(date_box+' .fhs_input_date_group_month');
	let $year = $jq(date_box+' .fhs_input_date_group_year');
	let $full = $jq(date_box+' .fhs_input_date_group_full');
	$day.keydown(function(e){
	    let key = '';
	    let keyCode = 0;
	    if (e.type === 'paste') {
		key = e.clipboardData.getData('text/plain');
	    }else{
		keyCode = (e.keyCode ? e.keyCode : e.which);
		key = String.fromCharCode(keyCode);
	    }
	    if(!((keyCode >= 37) && (keyCode <= 40))  && (keyCode != 17) && !((keyCode >= 8)  && (keyCode <= 9)) && !((keyCode >= 46)  && (keyCode <= 47)) && (keyCode != 49) && (keyCode != 116)){
		if(!((keyCode >= 48 && keyCode <= 57) || (keyCode >= 96 && keyCode <= 105))){
		    let regex = /[0-9]|\./;
		    if(!regex.test(key)) {
		      e.returnValue = false;
		      if(e.preventDefault) e.preventDefault();
		    }  
		}
	    }
	});
	$month.keydown(function(e){
	    let key = '';
	    let keyCode = 0;
	    if (e.type === 'paste') {
		key = e.clipboardData.getData('text/plain');
	    }else{
		keyCode = (e.keyCode ? e.keyCode : e.which);
		key = String.fromCharCode(keyCode);
	    }
	    if(!((keyCode >= 37) && (keyCode <= 40))  && (keyCode != 17) && !((keyCode >= 8)  && (keyCode <= 9)) && !((keyCode >= 46)  && (keyCode <= 47)) && (keyCode != 49) && (keyCode != 116)){
		if(!((keyCode >= 48 && keyCode <= 57) || (keyCode >= 96 && keyCode <= 105))){
		    let regex = /[0-9]|\./;
		    if(!regex.test(key)) {
		      e.returnValue = false;
		      if(e.preventDefault) e.preventDefault();
		    }  
		}
	    }
	});
	$year.keydown(function(e){
	    let key = '';
	    let keyCode = 0;
	    if (e.type === 'paste') {
		key = e.clipboardData.getData('text/plain');
	    }else{
		keyCode = (e.keyCode ? e.keyCode : e.which);
		key = String.fromCharCode(keyCode);
	    }
	    if(!((keyCode >= 37) && (keyCode <= 40))  && (keyCode != 17) && !((keyCode >= 8)  && (keyCode <= 9)) && !((keyCode >= 46)  && (keyCode <= 47)) && (keyCode != 49) && (keyCode != 116)){
		if(!((keyCode >= 48 && keyCode <= 57) || (keyCode >= 96 && keyCode <= 105))){
		    let regex = /[0-9]|\./;
		    if(!regex.test(key)) {
		      e.returnValue = false;
		      if(e.preventDefault) e.preventDefault();
		    }  
		}
	    }
	});
	$day.keyup(function(e){
	    if($day.val().length == 2){
		if($day.val() <= 0){
		    $day.val(1);
		}else if($day.val() > 31){
		    $day.val(31);
		}
	    }
	    if($year.val().length == 4 && $month.val().length == 2 && $day.val().length == 2){
		let datefull = $year.val()+"-"+$month.val()+"-"+$day.val();
		$full.val($this.formatDate(datefull));
	    }else{
		$full.val(0000);
	    }
	});
	$month.keyup(function(e){
	    if($month.val().length == 2){
		if($month.val() <= 0){
		    $month.val(1);
		}else if($month.val() > 12){
		    $month.val(12);
		}
	    }
	    if($year.val().length == 4 && $month.val().length == 2 && $day.val().length == 2){
		let datefull = $year.val()+"-"+$month.val()+"-"+$day.val();
		$full.val($this.formatDate(datefull));
	    }else{
		$full.val(0000);
	    }
	});
	$year.keyup(function(e){
	    var d = new Date();
	    var nowyear = d.getFullYear();
	    if($year.val().length == 4){
		if($year.val() <= 1900){
		    $year.val(1900);
		}else if($year.val() > nowyear){
		    $year.val(nowyear);
		}
	    }
	    if($year.val().length == 4 && $month.val().length == 2 && $day.val().length == 2){
		let datefull = $year.val()+"-"+$month.val()+"-"+$day.val();
		$full.val($this.formatDate(datefull));
	    }else{
		$full.val(0000);
	    }
	});
    };
    
    //Phone
    this.sentPhoneChangeOTP = function(){
	if($this.phone_change_otp != ''){return;}
	
	let phone = $jq('#change_phone');
	let phone_validated = $this.validateTextbox('change_phone',phone.val().trim(), phone);
	
	if(!$this.sent_phone_change_otp){
	    let $phone_otp = $jq('#change_phone_otp');
	    $phone_otp.val('');
	    $phone_otp.attr('disabled','disabled');
	}
	
	if(phone_validated){
	    $this.postSendChangePhoneOTP(phone.val().trim());
	}
    };
    this.changePhone = function(){
	$this.postChangePhone();
    };
    //Email
    this.sentEmailChangeOTP = function(){
	if($this.email_change_otp != ''){return;}
	
	let email = $jq('#change_email');
	let email_validated = $this.validateTextbox('change_email',email.val().trim(), email);
	
	
	if(!$this.sent_email_change_otp){
	    let $email_otp = $jq('#change_email_otp');
	    $email_otp.val('');
	    $email_otp.attr('disabled','disabled');
	}
	
	if(email_validated){
	    $this.postSendChangeEmailOTP(email.val().trim());
	}
    };
    this.changeEmail = function(){
	$this.postChangeEmail();
    };
    
//login form
    this.initLoginForm = function(){
	$this.tab_change('login');
	$jq('.youama-login-window').prependTo('.fhs_login_form_content');
	$jq('.youama-login-window').removeClass('fhs_popup_show');
	$jq('.youama-login-window .fhs-btn-cancel').fadeOut(0);
	$this.isShowLoginform = true;
    };

//register form
    this.initRegisterForm = function(){
	$this.tab_change('register');
	$jq('.youama-login-window').prependTo('.fhs_login_form_content');
	$jq('.youama-login-window').removeClass('fhs_popup_show');
	$jq('.youama-login-window .fhs-btn-cancel').fadeOut(0);
	$this.isShowLoginform = true;
    };

//login with facebook
    this.postloginfb = function(accessToken){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq.ajax({
	    url: LOGIN_FB_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {accessToken: accessToken},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
		$jq('.fhs-register-msg').text($this.languages['tryagain']);
	    },
	    success: function (data) {
		if(!data['success']){
		    $jq('.fhs-login-msg').text(data['message']);
		}else{
		    if(data['logined']){
			if ($this.is_redirect == '1') {
			    window.location = $this.redirect_url;
			} else {
			    window.location.reload();
			}
		    }else{
			$this.is_login_facebook = true;
		    }
		    $this.closeLoginPopup();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    
    this.postSendConfirmPhoneOTP = function(phone){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq('.fhs-confirmphone-msg').text('');
	$jq.ajax({
	    url: SEND_CONFIRM_PHONE_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {phone: phone},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
	    },
	    success: function (data) {
		let $phone = $jq('#confirm_phone');
		let $input_box = $phone.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		
		let $phone_otp = $jq('#confirm_phone_otp');
		$phone_otp.removeClass('checked-error');
		$phone_otp.removeClass('checked-pass');
		if(!$this.sent_phone_confirm_otp){
		    $phone_otp.val('');
		    $phone_otp.attr('disabled','disabled');
		}
		
		alert_message.text('');
		$this.phone_confirm = '';
		if(!data['success']){
		    $input_box.addClass('checked-error');
		    alert_message.text(data['message']);
		}else{
		    $phone.attr('disabled','disabled');
		    $this.phone_confirm = phone;
		    $input_box.addClass('checked-pass');
		    alert_message.text(data['message']);
		    $this.sent_phone_confirm_otp = true;
		    $phone_otp.removeAttr('disabled');
		    $phone_otp.focus();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    this.postCheckConfirmPhoneOTP = function(otp){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq.ajax({
	    url: CHECK_PHONE_OTP_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {phone: $this.phone_confirm, otp: otp},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
	    },
	    success: function (data) {
		let $phone_otp = $jq('#confirm_phone_otp');
		let $input_box = $phone_otp.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		
		alert_message.text('');
		$this.phone_confirm_otp = '';
		
		if(!data['success']){
		    $input_box.addClass('checked-error');
		    alert_message.text(data['message']);
		}else{
		    alert_message.text(data['message']);
		    $this.phone_confirm_otp = otp;
		    
		    $phone_otp.attr('disabled','disabled');
		    $input_box.addClass('checked-pass');
		    
		    $this.enableConfirmPhoneButton(true);
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    this.postRegisterFB = function(){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq.ajax({
	    url: REGISTER_FB_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {accessToken: $this.accessToken, phone: $this.phone_confirm, otp: $this.phone_confirm_otp},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
		$jq('.fhs-register-msg').text($this.languages['tryagain']);
	    },
	    success: function (data) {
		let $phone_otp = $jq('#confirm_phone_otp');
		let $input_box = $phone_otp.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		alert_message.text('');
		$this.phone_otp = '';
		if(!data['success']){
		    if(data['message'] == $this.languages['loginfail']){
			$jq('.fhs-login-msg').text(data['message']);
			$this.is_login_facebook = false;
			$jq('.youama-confirm-window').fadeOut();
			$this.showLoginPopup();
		    }else{
			$jq('.fhs-confirmphone-msg').text(data['message']);
		    }
		}else{
		    window.location = ACCOUNT_INFO;
		    $this.is_login_facebook = false;
		    $jq('.youama-confirm-window').fadeOut();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    
    //process
    this.loginFB = function(){
	FB.login(function(response) {
	    if (response.status === 'connected') {
		$this.accessToken = response.authResponse.accessToken;
		$this.postloginfb($this.accessToken);
	    } else {}
	}, {scope: 'public_profile,email'});
    };
    this.sentPhoneConfirmOTP = function(){
	if($this.phone_confirm_otp != ''){return;}
	
	let phone = $jq('#confirm_phone');
	let phone_validated = $this.validateTextbox('phone',phone.val().trim(), phone);
	
	if(!$this.sent_phone_confirm_otp){
	    let $phone_otp = $jq('#confirm_phone_otp');
	    $phone_otp.val('');
	    $phone_otp.attr('disabled','disabled');
	}
	
	if(phone_validated){
	    $this.postSendConfirmPhoneOTP(phone.val().trim());
	}
    };
    this.registerFB = function(){
	$this.postRegisterFB();
    };
    
    this.enableConfirmPhoneButton = function(is_enable){
	if(is_enable){
	    $jq('.fhs-btn-confirmphone').removeAttr("disabled");
	}else{
	    $jq('.fhs-btn-confirmphone').attr("disabled", "disabled");
	}
    };
    this.eventBtnFB = function(){
    };

//register quick----------------------------------
    this.initRegisterQuick = function(_order_id){
	$this.orderId = _order_id;
	
	$this.registerquick_phone = '';
	$this.registerquick_otp = '';
	$this.registerquick_sent_otp = false;
	
	$this.eventBtnRegisterQuick();
	$this.eventPressRegisterQuick();
    };
    //post
    this.postSendRegisterQuickPhoneOTP = function(phone){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq('.fhs-registerquick-msg').text('');
	$jq.ajax({
	    url: SEND_PHONE_OTP_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {phone: phone},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
	    },
	    success: function (data) {
		let $phone = $jq('#registerquick_phone');
		let $input_box = $phone.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		
		let $phone_otp = $jq('#registerquick_phone_otp');
		$phone_otp.removeClass('checked-error');
		$phone_otp.removeClass('checked-pass');
		if(!$this.registerquick_sent_otp){
		    $phone_otp.val('');
		    $phone_otp.attr('disabled','disabled');
		}
		
		let $password = $jq('#registerquick_password');
		$password.val('');
		$password.attr('disabled','disabled');
		$password.removeClass('checked-error');
		$password.removeClass('checked-pass');
		
		alert_message.text('');
		$this.registerquick_phone = '';
		if(!data['success']){
		    $input_box.addClass('checked-error');
		    alert_message.text(data['message']);
		}else{
		    $this.registerquick_phone = phone;
		    $input_box.addClass('checked-pass');
		    alert_message.text(data['message']);
		    $this.registerquick_sent_otp = true;
		    $phone_otp.removeAttr('disabled');
		    $phone_otp.focus();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    this.postCheckRegisterQuickPhoneOTP = function(otp){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq.ajax({
	    url: CHECK_PHONE_OTP_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {phone: $this.registerquick_phone, otp: otp},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
	    },
	    success: function (data) {
		let $phone_otp = $jq('#registerquick_phone_otp');
		let $input_box = $phone_otp.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		
		let $password = $jq('#registerquick_password');
		$password.val('');
		$password.attr('disabled','disabled');
		$password.removeClass('checked-error');
		$password.removeClass('checked-pass');
		
		alert_message.text('');
		$this.registerquick_otp = '';
		
		if(!data['success']){
		    $input_box.addClass('checked-error');
		    alert_message.text(data['message']);
		}else{
		    alert_message.text(data['message']);
		    $this.registerquick_otp = otp;
		    let $phone = $jq('#registerquick_phone');
		    $phone.val($this.registerquick_phone);
		    $phone.attr('disabled','disabled');
		    $phone_otp.attr('disabled','disabled');
		    $input_box.addClass('checked-pass');
		    
		    $password.removeAttr('disabled');
		    $password.focus();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    this.postRegisterQuick = function(password){
	if(is_loading){return;}
	is_loading = true;
	$this.animateLoader('start');
	$jq.ajax({
	    url: REGISTERQUICK_ACCOUNT_URL,
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {order_id: $this.orderId, username: $this.registerquick_phone, otp: $this.registerquick_otp, password: password},
	    error: function() {
		is_loading = false;
		$this.animateLoader('stop');
		$jq('.fhs-registerquick-msg').text($this.languages['tryagain']);
	    },
	    success: function (data) {
		let $phone_otp = $jq('#registerquick_phone_otp');
		let $input_box = $phone_otp.parents('.fhs-input-box');
		let alert_message = $input_box.children('.fhs-input-alert');
		$input_box.removeClass('checked-error');
		$input_box.removeClass('checked-pass');
		alert_message.text('');
		$this.registerquick_otp = '';
		if(!data['success']){
		    $jq('.fhs-registerquick-msg').text(data['message']);
		}else{
		    $this.savePassword($this.registerquick_phone, password);
		    window.location = ORDER_HISTORY_URL + $this.orderId;
		    $this.RegisterQuickClose();
		}
		is_loading = false;
		$this.animateLoader('stop');
	    }
	});
    };
    //event
    this.eventBtnRegisterQuick = function(){
	if(!$this.isMobile()){
	    $input_noautofill = $jq("input[autocomplete='off']");
	    $input_noautofill.attr('readonly', true);
	    $input_noautofill.focusin(function (){
		$jq(this).removeAttr('readonly')
	    });
	    $input_noautofill.focusout(function (){
		$jq(this).attr('readonly', true);
	    });
	}
	
	$jq('.fhs-textbox-alert').click(function(){
	    let $alert_icon = $jq(this);
	    let $input_group = $alert_icon.parents('.fhs-input-group');
	    let $input_box = $alert_icon.parents('.fhs-input-box');
	    let $text_box = $input_group.children('.fhs-textbox');
	    if($input_box.hasClass('checked-error')){
		let $input_box = $input_group.parents('.fhs-input-box');
		let $alert_msg = $input_box.children('.fhs-input-alert');
		$alert_msg.empty();
		$text_box.val('');
		$input_box.removeClass('checked-error');
	    }
	    $text_box.focus();
	});
	$jq('.fhs-textbox-showtext').click(function(){
	    let $show = $jq(this);
	    let $input_group = $show.parents('.fhs-input-group');
	    let $text_box = $input_group.children('.fhs-textbox');
	    if($text_box.attr('type') != 'text'){
		$text_box.prop("type", "text");
		$show.text($this.languages['hide']);
	    }else{
		$text_box.prop("type", "password");
		$show.text($this.languages['show']);
	    }
	    $text_box.focus();
	});
	$jq('.fhs-textbox-registerquick-send').click(function(){
	    $this.sentRegisterQuickPhoneOTP();
	});
	$jq('.fhs-btn-registerquick-cancel').click(function(){
	    $this.RegisterQuickClose();
	});
	$jq('.fhs-btn-registerquick').click(function(){
	    $this.RegisterQuick();
	});
    };
    this.eventPressRegisterQuick = function(){
	
	$jq('#registerquick_phone').keydown(function(e){
	    let key = '';
	    let keyCode = 0;
	    if (e.type === 'paste') {
		key = e.clipboardData.getData('text/plain');
	    }else{
		keyCode = (e.keyCode ? e.keyCode : e.which);
		key = String.fromCharCode(keyCode);
	    }
	    
	    if(!e.ctrlKey && !e.altKey){
		if(!((keyCode >= 37) && (keyCode <= 40))  && (keyCode != 17) && !((keyCode >= 8)  && (keyCode <= 9)) && !((keyCode >= 46)  && (keyCode <= 47)) && (keyCode != 49) && (keyCode != 116)){
		    if(!((keyCode >= 48 && keyCode <= 57) || (keyCode >= 96 && keyCode <= 105))){
			let regex = /[0-9]|\./;
			if(!regex.test(key)) {
			  e.returnValue = false;
			  if(e.preventDefault) e.preventDefault();
			}  
		    }
		}
	    }
	});
	$jq('#registerquick_phone').keyup(function(e){
	    let is_pass = false;
	    
	    let phone = $jq(this).val().trim();
	    let phone_otp = $jq('#registerquick_phone_otp').val().trim();
	    let password = $jq('#registerquick_password').val().trim();
	    
	    if(phone.length >= 10&& phone_otp.length == 6  && password.length >= $this.minLength){
		is_pass = true;
	    }
	    $this.enableRegisterQuickButton(is_pass);
	    
	    let keycode = (e.keyCode ? e.keyCode : e.which);
	    if(keycode == '13'){
		$this.sentRegisterQuickPhoneOTP();
	    }
	});
	$jq('#registerquick_phone_otp').keyup(function(e){
	    let is_pass = false;
	    
	    let phone = $jq('#registerquick_phone').val().trim();
	    let phone_otp = $jq(this).val().trim();
	    let password = $jq('#registerquick_password').val().trim();
	    
	    if(phone.length >= 10&& phone_otp.length == 6  && password.length >= $this.minLength && $this.registerquick_sent_otp){
		is_pass = true;
	    }
	    $this.enableRegisterQuickButton(is_pass);
	    
	    if(!$this.validateData('otp',phone_otp) && !$this.validateData('phone',phone) && $this.registerquick_sent_otp){
		$this.postCheckRegisterQuickPhoneOTP(phone_otp);
	    }
	})
	.on('paste', function(e) {
	     setTimeout(function () {
                let is_pass = false;
	    
		let phone = $jq('#registerquick_phone').val().trim();
		let phone_otp = $jq('#registerquick_phone_otp').val().trim();
		let password = $jq('#registerquick_password').val().trim();

		if(phone.length >= 10&& phone_otp.length == 6  && password.length >= $this.minLength && $this.registerquick_sent_otp){
		    is_pass = true;
		}
		$this.enableRegisterQuickButton(is_pass);

		if(!$this.validateData('otp',phone_otp) && !$this.validateData('phone',phone) && $this.registerquick_sent_otp){
		    $this.postCheckRegisterQuickPhoneOTP(phone_otp);
		}
	    }, 100);
	});
	$jq('#registerquick_password').keyup(function(e){
	    let is_pass = false;
	    
	    let phone = $jq('#registerquick_phone').val().trim();
	    let phone_otp = $jq('#registerquick_phone_otp').val().trim();
	    let password = $jq(this).val().trim();
	    
	    if(phone.length >= 10&& phone_otp.length == 6  && password.length >= $this.minLength){
		is_pass = true;
	    }
	    $this.enableRegisterQuickButton(is_pass);
	    
	    let keycode = (e.keyCode ? e.keyCode : e.which);
	    if(keycode == '13'){
		$this.RegisterQuick();
	    }
	});
    };

    this.sentRegisterQuickPhoneOTP = function(){
	if($this.registerquick_otp != ''){return;}
	
	let phone = $jq('#registerquick_phone');
	let phone_validated = $this.validateTextbox('registerquick_phone',phone.val().trim(), phone);
	
	if(!$this.sent_phone_otp){
	    let $phone_otp = $jq('#registerquick_phone_otp');
	    $phone_otp.val('');
	    $phone_otp.attr('disabled','disabled');
	}
	
	if(phone_validated){
	    $this.postSendRegisterQuickPhoneOTP(phone.val().trim());
	}
    };
    this.RegisterQuick = function(){
	let password = $jq('#registerquick_password');
	
	let password_validated = $this.validateTextbox('registerquick_password',password.val().trim(), password);
	
	if(password_validated){
	    $this.postRegisterQuick(password.val().trim());
	}
    };
    this.enableRegisterQuickButton = function(is_enable){
	if(is_enable){
	    $jq('.fhs-btn-registerquick').removeAttr("disabled");
	}else{
	    $jq('.fhs-btn-registerquick').attr("disabled", "disabled");
	}
    };
    this.RegisterQuickClose = function(){
	$jq('.youama-registerquick-window').fadeOut();
	$jq('.youama-ajaxlogin-cover').fadeOut();
    };
    
//Common
    this.savePassword = function(username, password){
	console.log('username: '+username+', password: '+password);
	if (window.PasswordCredential) {
	    var cr = new PasswordCredential({ id: username, password: password});
	    navigator.credentials.store(cr);
	}else {
	    Promise.resolve();
	}
    };
    this.validateEmail = function(emailAddress) {
	var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;

	if (filter.test(emailAddress)) {
	    return true;
	} else {
	    return false;
	}
    };
    this.validateTaxcode = function(taxCode){
	if($this.isEmpty(taxCode)) {
            return false;
        }
        
        try {
            let taxCodeSplit = taxCode.split("-");
            if (taxCodeSplit.length > 2) {
                return false;
            }
            if (taxCodeSplit.length == 2) {
                let branchId = parseInt(taxCodeSplit[1]);
                if (branchId < 1 || branchId > 999) {
                    return false;
                }
            }
            let taxId = taxCodeSplit[0];
            if (taxId.length != 10) {
                return false;
            }
            let nArr = [];
            for (i = 0; i < 10; i++) {
                nArr[i] = parseInt(taxId.substring(i, i + 1));
            }
            // MOD(10-(S1*31+ S2*29 + S3*23 + S4*19 + S5*17 + S6*13 + S7*7 + S8*5 + S9*3),11) = S10
            let t = nArr[0] * 31
                    + nArr[1] * 29
                    + nArr[2] * 23
                    + nArr[3] * 19
                    + nArr[4] * 17
                    + nArr[5] * 13
                    + nArr[6] * 7
                    + nArr[7] * 5
                    + nArr[8] * 3;
            t = 10 - t;
            t = $this.mod(t, 11);
            if (t != nArr[9]) {
                return false;
            }
            return true;
        } catch (ex) {}
        return false;
    };
    this.mod = function(n, m){
	var remain = n % m;
	return Math.floor(remain >= 0 ? remain : remain + m);
    };
    this.isEmpty = function (obj){
	try{
	    if(typeof obj == 'undefined' || obj == null || obj == ''){
		return true;
	    }
	    for(var key in obj) {
		if(obj.hasOwnProperty(key))
		    return false;
	    }
	}catch(ex){}
	return true;
    };
    this.hideLoadingAnimation = function () {
	$jq('.loadding_ajaxcart,#wraper_ajax,.wrapper_box').remove();
    };
    this.showLoadingAnimation = function (){
	var loading_bg = $jq('#ajaxconfig_info button').attr('name');
	var opacity = $jq('#ajaxconfig_info button').attr('value');
	var style_wrapper =  "position: fixed;top:0;left:0;filter: alpha(opacity=70); z-index:99999;background-color:"+loading_bg+"; width:100%;height:100%;opacity:"+opacity+"";
	var loading = '<div id ="wraper_ajax" style ="'+style_wrapper+'" ><div class ="loadding_ajaxcart" ><img class="default-icon-loading" src="'+$this.languages['img_loading']+'"/></div></div>';
	if($jq('#wraper_ajax').length==0) {
	    $jq('body').append(loading);
	}
    };
    this.showAlert = function (text){
	if($jq('.fhs_alert_box').length==0) {
	    $jq('body').append("<div class='fhs_alert_box'><div class='fhs_alert_box_text'>"+text+"</div></div>");
	}else{
	    $jq('.fhs_alert_box').html("<div class='fhs_alert_box_text'>"+text+"</div>");
	}
	$jq('.fhs_alert_box_text').slideDown(500);
	setTimeout(function(){$jq('.fhs_alert_box_text').slideUp();},1000);
    };
    this.animateLoader = function(step) {
	// Start
	if (step == 'start') {
	    $jq('.youama-ajaxlogin-loader').fadeIn();
	    $jq('.youama-login-window')
		.animate({opacity : '0.5'});
	// Stop
	} else {
	    $jq('.youama-ajaxlogin-loader').fadeOut('normal', function() {
	    $jq('.youama-login-window')
		.animate({opacity : '1'});
	    });
	}
    };
    this.isMobile = function() {
	try{ document.createEvent("TouchEvent"); return true; }
	catch(e){ return false; }
    };
    this.formatDate = function(date) {
	var d = new Date(date),
	    day = '' + d.getDate(),
	    month = '' + (d.getMonth() + 1),
	    year = d.getFullYear();

	if (month.length < 2) month = '0' + month;
	if (day.length < 2) day = '0' + day;

	return [day, month, year].join('/');
    };
    this.execute = function(_script = ''){
	try{
	    if(!$this.isEmpty(_script)){
		$jq.globalEval(_script);
	    }
	}catch(ex){}
    };
    this.sleep = function(milliseconds) {
	var start = new Date().getTime();
	for (var i = 0; i < 1e7; i++) {
	  if ((new Date().getTime() - start) > milliseconds){
	    break;
	  }
	}
    };
    this.copyCouponCode = function(text){
	if(this.copyToClipboard(text)){
	    this.showAlert($this.languages['copied']);
	}
    };
    this.copyToClipboard = function(text) {
	let result = false;
	try{
	    var $temp = $jq("<input>");
	    $jq("body").append($temp);
	    $temp.val(text).select();
	    document.execCommand("copy");
	    $temp.remove();
	    result = true;
	}catch(ex){}
	return result;
    };
    this.shareFB = function(event, url, login_require = false) {
	if(login_require){
	    if(!$this.isLogin()){return;}
	}
        FB.ui({
            method: 'share',
            href: url,
        }, function (response) {
            if (response && !response.error_message) {
		$this.getGilftIRS(url);
            }
        });
    };
    //IRS = Image render share
    this.getGilftIRS = function(sharedLink){
	$this.showLoadingAnimation();
	$jq.ajax({
	    url: '/event/index/getGilftIRS',
	    method: 'post',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {sharedLink: sharedLink},
	    error: function() {
		$this.hideLoadingAnimation();
		    alert("Bạn đã chia sẻ thành công !");
	    },
	    success: function (data) {
		if(data['success']){
		    if(!$this.isEmpty(data['img_background']) && !$this.isEmpty(data['msg'])){
			$this.showAlertMsg(data['img_background'],data['img_backgroud_width'],data['img_backgroud_height'],data['msg']);
			if(data['result']){
			    setTimeout(loadNoticationTop,100);
			}
		    }else{
			alert("Bạn đã chia sẻ thành công !");
		    }
		}else{
		    alert("Bạn đã chia sẻ thành công !");
		}
		$this.hideLoadingAnimation();
	    }
	});
    };
    this.showAlertMsg = function(background_img = '', width = '', height = '', msg, background_color = '#fff', ico_img = '', question_msg = '', btn_confirm_title = '', btn_confirm_script = ''){
	let cover = "<div class='youama-ajaxlogin-cover'></div>";
	let backgroud_img_srt = "";
	let background_color_srt = '';
	let ico_img_srt = "";
	if(!$this.isEmpty(background_img)){
	    backgroud_img_srt = "background: url("+background_img+") no-repeat center center; "
	}
	if(!$this.isEmpty(background_color)){
	    background_color_srt = 'background-color: '+background_color+"; ";
	}
	if(!$this.isEmpty(ico_img)){
	    ico_img_srt = "<div style='position: absolute; top: 90px;left: 50%;-webkit-transform: translate(-50%, -45%);-ms-transform: translate(-50%, -45%);-moz-transform: translate(-50%, -45%);transform: translate(-50%, -45%);'><img src='"+ico_img+"'/></div>";
	}
	if(!$this.isEmpty(width) || Number.isInteger(width)){
	    width = "width: "+width+"px;";
	}else{width = '';}
	
	if(!$this.isEmpty(height) || Number.isInteger(height)){
	    height = "height: "+height+"px;";
	}else{height = '';}
	
	let popup_template = '';
	if(!$this.isEmpty(question_msg) && !$this.isEmpty(btn_confirm_title) && !$this.isEmpty(btn_confirm_script)){
	    popup_template = "<div id='fhs-popup-event-alert' style='"+width+height+background_color_srt + "'>"
				+"<div style='color:#212121;text-transform: uppercase;margin:24px 0 0 0; font-size: 1.23em; font-weight: 700;letter-spacing: 0px;'>"+ question_msg + "</div>"
				+"<div class='fhs_center_top' style='color:#212121;margin:8px 24px 24px 24px;text-align: center; font-size: 1.23em;letter-spacing: 0px;'>"+ msg + "</div>"
				+"<div class='fhs-btn-box-confirm'>"
				    +"<button class='lg-close' type='button' title='"+$this.languages['cancel']+"' onclick='fhs_account.closeAlertMsg();'><span>"+$this.languages['cancel']+"</span></button>"
				    +"<button class='lg-yes' type='button' title='"+btn_confirm_title+"' onclick='"+btn_confirm_script+"'><span>"+btn_confirm_title+"</span></button>"
				+"</div><div></div>"
			    +"</div>";
	}else{
	    popup_template = "<div id='fhs-popup-event-alert' style='"+width +height +backgroud_img_srt + background_color_srt + "'><div>"+ msg + ico_img_srt + "</div>"
		+"<div style='padding-bottom:8px;'><button type='button' title='"+$this.languages['close']+"' class='fhs_btn_default lg-close' onclick='fhs_account.closeAlertMsg();'><span>"+$this.languages['close']+"</span></button></div>"
	    +"</div>";
	}
	    
	    $jq("body").append(popup_template);
	$jq('.youama-ajaxlogin-cover').fadeIn(0);
    };
    this.closeAlertMsg = function(){
	$jq('#fhs-popup-event-alert').remove();
	$jq('.youama-ajaxlogin-cover').fadeOut(0);
    };
    this.getBlockId = function(block_id, title = '', width = null, height = null){
	if($this.isEmpty(block_id)){return;}
	
	if($this.block_ids[block_id]){$this.showPopup(title, $this.block_ids[block_id], width, height);return;}
	if($this.is_loading_block){return;}
	$this.is_loading_block = true;
	$this.showLoadingAnimation();
	$jq.ajax({
	    url: "/cmsjson/index/getBlock",
	    method: 'post',
	    data: {block_id: block_id},
	    dataType : "json",
	    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    $this.block_ids[block_id] = data['content'];
		    $this.showPopup(title, $this.block_ids[block_id], width, height);
		}
		$this.hideLoadingAnimation();
		$this.is_loading_block = false;
	    },
	    error: function(){
		$this.hideLoadingAnimation();
		$this.is_loading_block = false;
	    }
	});
    };
    this.showPopup = function(title, content, width = null, height = null){
	let block_size_style = '';
	if(!this.isEmpty(width)){
	    block_size_style = "width:"+width+";";
	}
	if(!this.isEmpty(height)){
	    block_size_style += "height:"+height+";";
	}
	if(!this.isEmpty(block_size_style)){
	    block_size_style = "style='"+block_size_style+"'";
	}
	let popup_template = "<div id='fhs_popup-default-info' "+block_size_style+">"
	    +"<div class='fhs_popup-default-info-detail'>"
		+"<div class='fhs_popup-default-info-detail-title'>"
		    +"<div class='fhs_popup-default-info-detail-title-text'>"
			+"<div class='fhs_popup-default-info-detail-title-left'></div>"
			+"<div class='fhs_popup-default-info-detail-title-center'>"+title+"</div>"
			+"<div class='fhs_popup-default-info-detail-title-right lg-close' onclick='fhs_account.closePopup();'>"
			    +"<span class='icon_close_gray'></span>"
			+"</div>"
		    +"</div>"
		+"</div>"
		+"<div class='fhs_popup-default-info-detail-content'>"
		    +content
		+"</div>"
	    "</div>"
	+"</div>";
	$jq("body").append(popup_template);
	$jq('.youama-ajaxlogin-cover').fadeIn(0);
    };
    this.closePopup = function(){
	$jq('#fhs_popup-default-info').remove();
	$jq('.youama-ajaxlogin-cover').fadeOut(0);
    };
    this.getAddToCartButton = function(cart_info){
	let result = "";
	let form_id = Math.floor(Math.random() * 1000000) + 1;
	if(cart_info){
	    if(cart_info['can_buy']){
		let action_script = '';
		if(cart_info['action_script']){
		    action_script = cart_info['action_script'];
		}
		let my_script = "<script type='text/javascript'>"
				+"var productAddToCartForm"+form_id+" = new VarienForm('product_addtocart_form_"+form_id+"');"
				+"productAddToCartForm"+form_id+".submit = function(button) {"
				    +"if(this.validator && this.validator.validate()){"
					+"let is_buyNow = 'open_box';let this_button = \$jq(button);"
					+"if(this_button.attr('is_buyNow')){"
					    +"is_buyNow = this_button.attr('is_buyNow');"
					+"}"
				    +"try {"
					+"ajaxToCart(this.form.action,\$jq(this.form).serialize(),'view',is_buyNow);"
					+action_script
				    +"}catch(e){"
					+"this.form.submit();}}return false;"
				    +"}.bind(productAddToCartForm"+form_id+");"
			    +"</script>";
		    
		result = "<form action='"+cart_info['action_form']+"' method='post' id='product_addtocart_form_"+form_id+"'>"
			    +"<button type='button' onclick='event.stopPropagation(); productAddToCartForm"+form_id+".submit(this); return false; ' title='"+$this.languages['add_to_cart']+"' class='btn_add_cart'><div class='btn_add_cart_icon'></div><span>"+$this.languages['add_to_cart']+"</span></button>"
			+"</form>"
			+my_script;
	    }else{
		result = "<button type='button' onclick=\"event.stopPropagation(); (function(){ fhs_account.showAlertMsg('',350,275,'"+$this.languages['out_of_stock']+"', 'white','"+$this.languages['fail_icon']+"');})(); \" title='"+$this.languages['add_to_cart']+"' class='btn_add_cart'><div class='btn_add_cart_icon'></div><span>"+$this.languages['add_to_cart']+"</span></button>"
	    }
	}
	return result;
	
    };
    this.hasEmojiIconInTextbox = function(str){
	let new_str = $this.removeEmojiIcon(str);
	if(new_str != str){
	    return true;
	}else{
	    return false;
	}
    };
    this.removeEmojiIcon = function(str){
	let ranges = [
	    '\ud83c[\udf00-\udfff]', // U+1F300 to U+1F3FF
	    '\ud83d[\udc00-\ude4f]', // U+1F400 to U+1F64F
	    '\ud83d[\ude80-\udeff]'  // U+1F680 to U+1F6FF
	];
	try{
	    str = str.replace(new RegExp(ranges.join('|'), 'g'), '');
	}catch(ex){str = '';}
	return str;
    };
    this.keepOnlyNumber = function(str){
	return str.replace(/[^\d]/g, '')
    };
    this.updateQueryStringParam = function (key, value) {
	var baseUrl = [location.protocol, '//', location.host, location.pathname].join(''),
	    urlQueryString = document.location.search,
	    newParam = key + '=' + value,
	    params = '?' + newParam;

	// If the "search" string exists, then build params from it
	if (urlQueryString) {
	    var updateRegex = new RegExp('([\?&])' + key + '[^&]*');
	    var removeRegex = new RegExp('([\?&])' + key + '=[^&;]+[&;]?');

	    if( typeof value == 'undefined' || value == null || value == '' ) { // Remove param if value is empty
		params = urlQueryString.replace(removeRegex, "$1");
		params = params.replace( /[&;]$/, "" );

	    } else if (urlQueryString.match(updateRegex) !== null) { // If param exists already, update it
		params = urlQueryString.replace(updateRegex, "$1" + newParam);

	    } else { // Otherwise, add it to end of query string
		params = urlQueryString + '&' + newParam;
	    }
	}

	// no parameter was set so we don't need the question mark
	params = params == '?' ? '' : params;

	window.history.replaceState({}, "", baseUrl + params);
    };
    this.encodeHTML = function(str) {
	str = $this.decodeHTML(str);
	
	return str.replace(/([\u00A0-\u9999<>&])(.|$)/g, function(full, char, next) {
	    if(char !== '&' || next !== '#'){
	      if(/[\u00A0-\u9999<>&]/.test(next))
		next = '&#' + next.charCodeAt(0) + ';';

	      return '&#' + char.charCodeAt(0) + ';' + next;
	    }

	    return full;
	  });
    };
    this.decodeHTML = function(str) {
	return $jq("<textarea/>").html(str).text();
    };
    this.animateLoaderBlock = function(step, element) {
	if($this.isEmpty(element)){return;}
	// Start
	let $content = element.parents('.fhs_checkout_block_content');
	if (step == 'start') {
	    is_loading = true;
	    $content.addClass('loading');
	// Stop
	} else {
	    $content.removeClass('loading');
	    is_loading = false;
	}
    };
    this.formatDateTime = function(date){
	let datetime = new Date(date);
	let day = datetime.getDay();
	let dayofweek = $this.tranlateDayofweek(day);
	return dayofweek + " - " + $this.formatDateToDayAndMonth(datetime);
    };
    this.tranlateDayofweek = function(index){
	let dayofweek = '';
	if($this.languages['locale'] == 'vi_VN'){
	    dayofweek = ["Chủ Nhật", "Thứ Hai", "Thứ Ba", "Thứ Tư", "Thứ Năm", "Thứ Sáu", "Thứ Bảy"];
	}else{
	    dayofweek = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
	}
	return dayofweek[index];
    };
    this.formatDateToDayAndMonth = function(date){
	var d = new Date(date),
	month = '' + (d.getMonth() + 1),
	day = '' + d.getDate(),
	year = d.getFullYear();
	if (month.length < 2) {
	  month = '0' + month;
	}
	if (day.length < 2) {
	  day = '0' + day;
	}
	return [day, month].join('/');
    };
    this.sizeCouponBg = function(e = null){
	if(e != null){
	    if($jq(e).length > 0){
		let $path = $jq(e).find('path');
		if($path.length > 0){
		    let H = $jq(e).height();
		    let W = ($jq(e).width() - 15);
		    let viewbox = '7.5 '+(((H+2) - 146)*-1) + ' ' + (W-2) + ' ' + (H+2);
		    H = ((H - 24) * -1);
		    let path = '';
		    if($jq(document).width() > 500){
			path = coupon_bg_path.replace('{{H}}',H).replace('{{W}}',W);
		    }else{
			path = coupon_bg_mini_path.replace('{{H}}',H).replace('{{W}}',W);
		    }
		    $jq(e).get(0).setAttribute("viewBox",viewbox);
		    $path.attr('d',path);
		}
	    }
	}else{
	    $jq('.coupon_bg').each(function() {
		let $path = $jq(this).find('path');
		if($path.length > 0){
		    let H = $jq(this).height();
		    let W = ($jq(this).width() - 15);
		    let viewbox = '7.5 '+(((H+2) - 146)*-1) + ' ' + (W-2) + ' ' + (H+2);
		    H = ((H - 24) * -1);
		    let path = '';
		    if($jq(document).width() > 500){
			path = coupon_bg_path.replace('{{H}}',H).replace('{{W}}',W);
		    }else{
			path = coupon_bg_mini_path.replace('{{H}}',H).replace('{{W}}',W);
		    }
		    $jq(this).get(0).setAttribute("viewBox",viewbox);
		    $path.attr('d',path);
		}
	    });
	}
	
    };
    this.hoverCouponBg = function(){
	$jq('.fhs-event-promo-list-item').hover(
	    function(){
		let $coupon_bg = $jq(this).find('.coupon_bg');
		if($coupon_bg.length > 0){
		    let $path = $coupon_bg.find('path');
		    if($path.length > 0){
			$path.attr('stroke','rgba(47, 128, 237, 1)');
		    }
		    //$coupon_bg.css('filter','drop-shadow(rgba(47, 128, 237, 1) 0px 1px 3px)');
		}
	    },
	    function(){
		let $coupon_bg = $jq(this).find('.coupon_bg');
		if($coupon_bg.length > 0){
		    let $path = $coupon_bg.find('path');
		    if($path.length > 0){
			$path.attr('stroke','rgba(0,0,0,0)');
		    }
		    $coupon_bg.css('filter','drop-shadow(rgba(0, 0, 0, 0.15) 0px 1px 3px)');
		}
	    }
	);
    };
    this.goto = function(e){
	if(IS_MOBILE){
	    $jq('html, body').stop().animate({
		scrollTop: $jq(e).offset().top - 60
	    }, 1000);
	}else{
	    $jq('html, body').stop().animate({
		scrollTop: $jq(e).offset().top
	    }, 1000);
	}
    };
    this.showBorderNeon = function(e, time_left = 3000){
	if(!$jq(e).hasClass('animate_parent')){
	    $jq(e).addClass('animate_parent');
	}
	$jq(e).append('<span class="border_neon"></span>');
	setTimeout(function(){$jq(e).find('.border_neon').remove();}, time_left);
    };
    this.clickActive = function(e){
	$element = $jq(e);
	if($element.hasClass('active')){
	    $element.removeClass('active');
	}else{
	    $element.addClass('active');
	}
    };
    this.isLogin = function(add_to_cart_data = {}){
	if($this.isEmpty(CUSTOMER_ID)){
	    $this.add_to_cart_data = add_to_cart_data;
	    $this.showLoginPopup('login');
	    $jq('#login_username').focus();
	    return false;
	}
	return true;
    };
    this.formatCurrency = function (value) {
        value = Math.round(value); /// Example: 123000.000 -> 123000
        value = String(value).replace(/(.)(?=(\d{3})+$)/g,'$1.'); /// -> 123.000
        
        return value + " đ";
    };
    this.getProduct = function (item, slider_class = 'swiper-slide'){
	let episode = '';
	let subscribes = '';
	let name = '';
	let body = '';

	if(item['product_name']){
	    name = fhs_account.encodeHTML(item['product_name']);
	}else if(item['name_a_label']){
	    name = fhs_account.encodeHTML(item['name_a_label']);
	}
	
	if(item['type_id'] != 'series'){
	    let rating_html = "<div class='ratings'><div class='rating-box'><div class='rating' style='width:0'></div></div><div class='amount'>(0)</div></div>";
	    let btn_add_to_cart = '';
	    let comingsoon = '';
	    let price = '';
	    let discount = '';
	    let bar_html = '';
	    
	    
	    if(item['price'] != item['final_price']){
		price += "<p class='old-price'><span class='price'>"+$this.formatCurrency(item['price'])+"</span></p>";
	    }
	    if(item['discount_percent']){
		if(item['discount_percent'] > 0){
		    if(item['discount_percent']> 100){
			item['discount_percent'] = 100;
		    }
		    discount = "<span class='discount-percent fhs_center_left'>-"+Math.floor(item['discount_percent'])+"%</span>"
		}
	    }
	    if(item['episode']){
		episode = "<div class='episode-label'>"+item['episode']+"</div>";
	    }
	    if(item['add_to_cart_info']){
		btn_add_to_cart = $this.getAddToCartButton(item['add_to_cart_info']);
	    }else if(item['submitUrl']){
		let add_to_cart_info = [];
		if(item['stock_available'] == 'out_of_stock'){
		    add_to_cart_info['can_buy'] = false;
		}else{
		    add_to_cart_info['can_buy'] = true;
		    add_to_cart_info['action_form'] = item['submitUrl'];
		}
		btn_add_to_cart = $this.getAddToCartButton(add_to_cart_info);
	    }
	    if(item['bar_html']){
		bar_html = item['bar_html'];
	    }
	    if(!$this.isEmpty(item['rating_html'])){
		rating_html = item['rating_html'];
	    }
//	    if(item['soon_release'] == 1){
//		comingsoon = "<div><div class='hethang product-hh'><span><span>"+$this.languages['comingsoon']+"</span></span></div><div>"
//	    }
	    body = "<h2 class='product-name-no-ellipsis'>"
				+"<a href='"+item['product_url']+"' title='"+name+"'>"+name+"</a>"
			    +"</h2>"
			    +"<div class='price-label'>"
				+"<p class='special-price'>"
				    +"<span class='price m-price-font fhs_center_left'>"+$this.formatCurrency(item['final_price'])+"</span>"
				    +discount
				+"</p>"
				+price
			    +"</div>"
			    +"<div class='fhs-rating-container'>"
				+rating_html
			    +"</div>"
			    +"<div class='clear'></div>"
			    +comingsoon
			    +bar_html
			    +btn_add_to_cart;
	}else{
	    let episode_series = '';
	    if(item['episode']){
		episode_series = "<div class='fhs-series-episode-label'>"+item['episode']+"</div>";
	    }
	    if(item['subscribes']){
		subscribes = "<div class='fhs-series-subscribes'>"+item['subscribes']+" lượt theo dõi"+"</div>"
	    }
	     body = "<h2 class='product-name-no-ellipsis fhs-series'>"
			    +"<a href='"+url +"' title='"+name+"' ><span class='fhs-series-label'><i></i></span>"+name+"</a>"
			+"</h2>"
			+episode_series
			+subscribes;
	}
	
	
	return "<li class='fhs_product_basic "+slider_class+"'>"
		+"<div class='item-inner'>"
		    +"<div class='ma-box-content'>"
			+"<div class='products clear'>"
			    +"<div class='product images-container'>"
				+"<a href='"+item['product_url']+"' title='"+name+"' class='product-image'>"
				    +"<div class='product-image'>"
					+"<img class='lazyload' src='"+loading_icon_url+"' data-src='"+item['image_src']+"' alt='"+name+"'>"
				    +"</div>"
				    +episode
				+"</a>"
			    +"</div>"
			+"</div>"
			+"<div>"
			    +body
			+"</div>"
		    +"</div>"
		+"</div>"
	    +"</li>";
    };
    
    this.showSlider = function(block_id, is_grid, data_lenght){
	$jq('#'+block_id+' .swiper-button-next').hide();
	$jq('#'+block_id+' .swiper-button-prev').hide();
	
	if ($jq(window).width() < 992) {
	    eval("var mySwiperAsidebar"+block_id+" = new Swiper($jq('#"+block_id+"_slider'), {"
		    +"slidesPerView: 'auto',"
		    +"freeMode: true,"
		    +"direction: 'horizontal',"
		    +"simulateTouch: true,"
		    +"spaceBetween: 8,"
		    +"});");
	}else{
		if(!is_grid && data_lenght && data_lenght > 5){
		    $jq('#'+block_id+' .swiper-button-next').show();
		}
		let row_param = "";
		if(is_grid){
		    row_param = "slidesPerColumnFill: 'row',slidesPerColumn: 2,";
		}
		eval("var mySwiperAsidebar"+block_id+" = new Swiper($jq('#"+block_id+"_slider'), {"
		    +"slidesPerView: 5,"
		    +"slidesPerGroup: 5,"
		    +"spaceBetween: 8,"
		    +row_param
		    +"direction: 'horizontal',"
		    +"simulateTouch: true,"
		    +"navigation: {"
			+"nextEl: '#"+block_id+" .swiper-button-next',"
			+"prevEl: '#"+block_id+" .swiper-button-prev'"
		    +"},"
			+"on: {"
			    +"slideChange: function() {"
				+"if ("+data_lenght+") { "
				    // on the first slide
				    +"let demSo =  mySwiperAsidebar"+block_id+".activeIndex + 5;"
				    +"if (mySwiperAsidebar"+block_id+".activeIndex == 0) {"
					+"$jq('#"+block_id+" .swiper-button-next').show();"
					+"$jq('#"+block_id+" .swiper-button-prev').hide();"
				    +"}"
				    // most right postion
				    +"else if (demSo == "+data_lenght+") {"
					+"$jq('#"+block_id+" .swiper-button-next').show();"
					+"$jq('#"+block_id+" .swiper-button-prev').show();"
				    +"}"
				    // middle positions
				    +"else {"
					+"$jq('#"+block_id+" .swiper-button-next').hide();"
					+"$jq('#"+block_id+" .swiper-button-prev').show();"
				    +"}"
				    // --- end-swpier
				+"}"
			    +"}"
			+"},"
		+"});");
	}
    };
    this.addToCart = function(is_register = false){
	if ($this.is_redirect == '1') {
	    if($this.add_to_cart_data['url']){
		let url = $this.add_to_cart_data['url'];
		let data = $this.add_to_cart_data['data'];
		let mine = $this.add_to_cart_data['mine'];
		let is_buyNow = $this.add_to_cart_data['is_buyNow'];

		ajaxToCart(url,data,mine,is_buyNow, true);
	    }else{
		window.location = $this.redirect_url;
	    }
	} else {
	    if($this.add_to_cart_data['url']){
		let url = $this.add_to_cart_data['url'];
		let data = $this.add_to_cart_data['data'];
		let mine = $this.add_to_cart_data['mine'];
		let is_buyNow = $this.add_to_cart_data['is_buyNow'];

		ajaxToCart(url,data,mine,is_buyNow, true);
	    }else{
		if(is_register){
		    window.location = ACCOUNT_INFO;
		}else{
		    window.location.reload();
		}
	    }
	}
    };
}