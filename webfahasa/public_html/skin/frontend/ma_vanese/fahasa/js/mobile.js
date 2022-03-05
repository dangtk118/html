$jq(function() {
    //Remove banner that have class no-fhs-mobile
    $jq(".fhs-no-mobile-block").remove();
    $jq(".cd-b-l").addClass("mobile-no-pl");
});