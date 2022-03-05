function Suggestion(_session_id, _attribute, _data){
    $jq.ajax({
            url: "/personal/api/personalization/suggestion",
            method : 'post',
            dataType : "json",
            contentType: "application/json; charset=utf-8",
            data: JSON.stringify({
                session: _session_id,
                attribute: _attribute,
                data: _data,
                c_id: CUSTOMER_ID
            }),
            success: function(data)
            {}
        });
}
