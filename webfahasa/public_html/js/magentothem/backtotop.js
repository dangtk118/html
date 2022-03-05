
$jq(document).ready(function(){	
    // scroll body to 0px on click
    $jq('#back-top').on('click touchend' ,function () {
            $jq('body,html').animate({
                    scrollTop: 0
            }, 800);
            return false;
    });    
});
