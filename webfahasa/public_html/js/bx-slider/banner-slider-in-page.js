
$jq(document).ready(function () {
    $jq('.4-banner-slider-in-page').bxSlider({
        slideWidth: 290,
        minSlides: 2,
        maxSlides: 4,
        moveSlides: 1,
        slideMargin: 0,
        auto: true,
        randomStart: true
    });

    $jq('.3-banner-slider-in-page').bxSlider({
        slideWidth: 385,
        minSlides: 2,
        maxSlides: 3,
        moveSlides: 1,
        slideMargin: 0,
        auto: true,
        randomStart: true
    });
    $jq('.6-banner-slider-in-page').bxSlider({
        slideWidth: 190,
        minSlides: 4,
        maxSlides: 6,
        moveSlides: 3,
        slideMargin: 0,
        auto: true,
        randomStart: true
    });
    
    var swiper3 = new Swiper('.three-new-banner-slider-in-page', {
        slidesPerView: 3,
        slidesPerGroup: 3,
        spaceBetween: 10,
        loop: true,
        autoplay: {
            delay: 3500,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        longSwipesMs: 800 
    });

    var swiperAuto = new Swiper('.four-new-banner-slider-in-page', {
        slidesPerView: 4,
        slidesPerGroup: 4,
        spaceBetween: 20,
        loop: true,
        autoplay: {
            delay: 3500,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        longSwipesMs: 800 
    });
         
    var swiper1 = new Swiper('.one-new-banner-slider-in-page', {
        slidesPerView: 1,
        slidesPerGroup: 1,
        spaceBetween: 20,
        loop: true,
        autoplay: {
            delay: 3500,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        longSwipesMs: 800 
    });
    
    var swiper2 = new Swiper('.two-new-banner-slider-in-page', {
        slidesPerView: 2,
        slidesPerGroup: 2,
        spaceBetween: 20,
        loop: true,
        autoplay: {
            delay: 3500,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        longSwipesMs: 800 
    });
    
});
