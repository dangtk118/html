var Popup = function () {
    this.init = function (_dataArray) {
     
        $jq("a.cursor-pointer").click(function (e) {
            
            let attr = $jq(this).attr('name');
            if (attr && _dataArray) {
                let html = '';
                $jq.each(_dataArray, function (index, item) {
                    $jq.each(item, function (key, value) {
                        if (key == attr) {
                            html += "<div class='popup-rules'>"
                                + "<div class='popup-title'>"+value.title+"</div>"
                                + "<div class='popup-icons' onclick='popup.clickClose()'>&nbsp;</div>"
                                + "<div class='popup-rules-content'>"
                                    + "<div id='popup-content-child-'>"
                                        + value.content
                                    + "</div>"
                                + "</div>"
                                + "<div style='padding:10px;'>"
                                    + "<div class='popup-buttons' onclick='popup.clickClose()' style='background:"+value.buttonBackground+";color:"+value.buttonColor+";'>"
                                        + "<p style='margin:auto;'>"+ value.button +"</p>"
                                    + "</div>"
                                + "</div>"
                        + "</div>";
                        }
                        
                    });
                });
                $jq(".popup-container").html(html).fadeIn();
                $jq("html, body, .offcanvas-container, .offcanvas-pusher, .offcanvas-content").addClass('body-scroll-hidden');
            }
        });
    }
    this.clickClose = function (e) {
        $jq(".popup-container").empty().fadeOut("slow");
        $jq("html, body, .offcanvas-container, .offcanvas-pusher, .offcanvas-content").removeClass('body-scroll-hidden');
    }
}