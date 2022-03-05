    window.fbAsyncInit = function () {
        FB.init({
            appId: '331898836987489',
            //appId: '1780915398680453', //for test
            autoLogAppEvents: true,
            xfbml: true,
            version: 'v3.2'
        });
        
        $jq(document).trigger('fbInit'); // trigger event
    };

    (function (d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {
            return;
        }
        js = d.createElement(s);
        js.id = id;
        js.src = "https://connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));