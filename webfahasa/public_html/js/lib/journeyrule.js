var JourneyRule = function (_session_id) {

    let action = "";
    let content = {};

    return;
    $jq.ajax({
        url: "/personal/api/journeyrule",
        method: 'post',
        dataType: "json",
        contentType: "application/json; charset=utf-8",
        data: JSON.stringify({
            session: _session_id,
            current_url: window.location.href
        }),
        success: function (data) {

            if (!data.result || data.data == null) {
                return;
            }
            action = data.data.action;
            content = data.data.content;
                       
            if ("popup" == action) {
                $jq(".journeyrule-image").html(content);
                $jq("#journeyrule-popup-background").css('display', 'flex');
             }
        },
        timeout: 2000
    });

}

var clickCloseJourneyRule = function () {
    $jq("#journeyrule-popup-background").fadeOut();
}
