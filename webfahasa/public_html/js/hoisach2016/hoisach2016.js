
$jq(document).ready(function() {
    //$jq('.column-content').hide();
    $jq('.tabs .tab-banner a').on('click', function(e)  {
        
        var currentAttrValue = $jq(this).attr('href').split("#");
        // Show/Hide Tabs
        $jq('.tabs #' + currentAttrValue[1]).show().siblings().hide();
        
        // Change/remove current tab to active
        $jq(this).parent('div .tab-img').addClass('active').siblings().removeClass('active');
        
        $tabslider_blocks = $jq("#" + currentAttrValue[1]).find("div[id^='categorytab-']");
        $tabslider_blocks.trigger('query');
        
        var target = $jq("div#"+currentAttrValue[1]);
        if (target.length) {
            $jq('html,body').animate({
                scrollTop: target.offset().top
            }, 200);
            return false;
        }
        
        e.preventDefault();
    });
    //$jq('#column1-1-content').show();
    //$jq('.column a').on('click', function(e)  {
    //    var currentAttrValue = $jq(this).attr('href');
    //    $jq('.column-content').hide();
    //    $jq(currentAttrValue +'-content').fadeIn('slow');
    //});
});
