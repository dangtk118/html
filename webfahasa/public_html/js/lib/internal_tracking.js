const InternalTracking = function (config) {
    this.cookie_duration = 7;
    this.cookie_domain = null;
    
    this.init = function (config) {
        this.cookie_duration  = config.cookie_duration ? config.cookie_duration : 7;
    }

    this.tracking = function () {
        let affId = this.get_param("affId");
        if (!affId){
            affId = this.get_param("affid");
        }
        if (!affId){
            affId = this.get_param("affld");
        }
        if (affId){
            this.set_cookie("affId", affId, this.cookie_duration);
        }
    }

    this.set_cookie = function (key, value, day) {
        var cur_date = new Date();
        cur_date.setTime(cur_date.getTime() + day * 24 * 60 * 60 * 1000);
        var a = "expires=" + cur_date.toUTCString();
        let cookie_domain = this.cookie_domain || window.location.hostname;
        document.cookie = key + "=" + value + "; " + a + ";domain=" + cookie_domain + "; path=/";
    }

    this.get_cookie = function (key) {
        var b = key + "=";
        var cookies = document.cookie.split(";");
        for (var i = 0; i < cookies.length; i++) {
            var cookie = cookies[i];
            while (cookie.charAt(0) == " ") {
                cookie = cookie.substring(1);
            }
            if (cookie.indexOf(b) == 0) {
                return cookie.substring(b.length, cookie.length);
            }
        }
        return undefined;
    }
    
    this.get_param = function (param, b) {
        if (!b) {
            b = location.href;
        }
        param = param.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        let data = "[\\?&]" + param + "=([^&#]*)";
        let value = new RegExp(data);
        let result = value.exec(b);
        return result == null ? null : result[1];

    };
    
    this.init(config);
};