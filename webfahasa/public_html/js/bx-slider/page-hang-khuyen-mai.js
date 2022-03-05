
$jq(document).ready(function() {
$jq('.slider1').bxSlider({
    pause: 0,			  
minSlides:1,
maxSlides: 6,
slideWidth: 270,
slideMargin: 45,

pager:false, 
                                                                            controls: true,
                                infiniteLoop:false,
touchEnabled: false,
hideControlOnEnd: true,
preloadImages: 'all'
  });
} );


