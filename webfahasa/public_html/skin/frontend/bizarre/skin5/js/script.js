/* Login ajax */
function ajaxLogin(ajaxUrl, clear){
	if(clear == true){
		clearHolder();
		jQuery(".ajax-box-overlay").removeClass('loaded');
	}
	jQuery("body").append("<div id='login-holder' />");
	if(!jQuery(".ajax-box-overlay").length){
		jQuery("#login-holder").after('<div class="ajax-box-overlay"><i class="load" /></div>');
		jQuery(".ajax-box-overlay").fadeIn('medium');
	}
	function overlayResizer(){
		jQuery(".ajax-box-overlay").css('height', jQuery(window).height());
	}
	overlayResizer();
	jQuery(window).resize(function(){overlayResizer()});
	
	jQuery.ajax({
		url: ajaxUrl,
		cache: false
	}).done(function(html){
		setTimeout(function(){
			jQuery("#login-holder").html(html).animate({
				opacity: 1,
				top: '100px'
			}, 500 );
			jQuery(".ajax-box-overlay").addClass('loaded');
			clearAll();
		}, 500);
	});
	
	var clearAll = function(){
		jQuery("#login-holder .close-button").on('click', function(){
			jQuery(".ajax-box-overlay").fadeOut('medium', function(){
				jQuery(this).remove();
			});
			clearHolder();
		});
	}
	function clearHolder(){
		jQuery("#login-holder").animate({
			opacity: 0,
			top: 0
		  }, 500, function() {
			jQuery(this).remove();
		});
	}
}

/* isotop */
function isotopInit(){
	if(!jQuery('body').hasClass('rtl')){
		jQuery('.products-grid').isotope({
			itemSelector: '.item',
			resizable: true,
			layoutMode : 'fitRows'
		});
	} else {
		jQuery('.products-grid').isotope({
			itemSelector: '.item',
			resizable: true,
			layoutMode : 'fitRows',
			transformsEnabled: false
		});
	}
}
function isotopDestroy(){
	jQuery('.products-grid').isotope('destroy');
	if(jQuery('body').hasClass('rtl')){
		jQuery('.products-grid').find('li.item').attr('style', '');
	}
}
function isotopLoader(imgCount, callback){
	images = jQuery('.products-grid .product-image img');
	if(!imgCount){
		imgCount = images.size();
	}
	currentIndex = 0;
	images.load(function(){
		currentIndex++;
		if(currentIndex == imgCount){
			try{
				callback();
			}catch(err){}
			labelsHeight();
			setTimeout(function(){
				isotopInit();
			}, 100);
		}
	});
}

var thisHeader;

/* Top Cart */
function topCartListener(e){
	var touch = e.touches[0];
	if(jQuery(touch.target).parents('.topCartContent').length == 0 && jQuery(touch.target).parents('.cart-button').length == 0 && !jQuery(touch.target).hasClass('cart-button')){
		jQuery('.top-cart .block-title').removeClass('active');
		jQuery('.topCartContent').slideUp(500).removeClass('active');
		document.removeEventListener('touchstart', topCartListener, false);
	}
}
function topCart(isOnHover){
	jQuery('header.header').each(function(){
		var thisHeader = jQuery(this);
		function standardMode(){
			thisHeader.find('.top-cart .block-title').off().on('click', function(event){
				event.stopPropagation();
				jQuery(this).toggleClass('active');
				jQuery(this).next('.topCartContent').slideToggle(500).toggleClass('active');
				document.addEventListener('touchstart', topCartListener, false);
				jQuery(document).on('click.cartEvent', function(e) {
					if (jQuery(e.target).parents('.topCartContent').length == 0) {
						thisHeader.find('.top-cart .block-title').removeClass('active');
						thisHeader.find('.topCartContent').slideUp(500).removeClass('active');
						jQuery(document).off('click.cartEvent');
					}
				});
			});
		}
		if(isOnHover){
			if((navigator.userAgent.match(/iPhone/i)) || (navigator.userAgent.match(/iPod/i)) || (navigator.userAgent.match(/iPad/i)) || (navigator.userAgent.match(/Android/i))){
				standardMode();
			}else{
				thisHeader.find('.top-cart').off().on('mouseenter mouseleave', function(event){
					event.stopPropagation();
					jQuery(this).find('.block-title').toggleClass('active');
					thisHeader.find('.topCartContent').stop().slideToggle(500).toggleClass('active');
				});
			}
		}else{
			standardMode();
		}
	});
}
/* Top Cart */

/* Wishlist Block Slider */
function wishlist_slider(){
  jQuery('#wishlist-slider .es-carousel').iosSlider({
	responsiveSlideWidth: true,
	snapToChildren: true,
	desktopClickDrag: true,
	infiniteSlider: false,
	navNextSelector: '#wishlist-slider .next',
	navPrevSelector: '#wishlist-slider .prev'
  });
}
 
function wishlist_set_height(){
	var wishlist_height = 0;
	jQuery('#wishlist-slider .es-carousel li').each(function(){
	 if(jQuery(this).height() > wishlist_height){
	  wishlist_height = jQuery(this).height();
	 }
	})
	jQuery('#wishlist-slider .es-carousel').css('min-height', wishlist_height+2);
}
if(jQuery('#wishlist-slider').length){
  whs_first_set = true;
  wishlist_slider();
}
/* Wishlist Block Slider */

/* Labels height */
function labelsHeight(){
	jQuery('.label-type-1 .label-new, .label-type-3 .label-new, .label-type-1 .label-sale, .label-type-3 .label-sale').each(function(){
		labelNewWidth = jQuery(this).outerWidth();
		if(jQuery(this).parents('.label-type-1').length){
			if(jQuery(this).hasClass('percentage')){
				lineHeight = labelNewWidth - labelNewWidth*0.22;
			}else{
				lineHeight = labelNewWidth;
			}
		}else if(jQuery(this).parents('.label-type-3').length){
			if(jQuery(this).hasClass('percentage')){
				lineHeight = labelNewWidth - labelNewWidth*0.2;
			}else{
				lineHeight = labelNewWidth - labelNewWidth*0.1;
			}
		}else{
			lineHeight = labelNewWidth;
		}
		jQuery(this).css({
			'height' : labelNewWidth,
			'line-height' : lineHeight + 'px'
		});
	});
}

/* Product Fancy */
function productFancy(){
	jQuery(function(){
		jQuery('.more-views a.cloud-zoom-gallery').on('click.productFancy', function(){
			thisHref = jQuery(this).attr('href');
			jQuery('.product-view .product-img-box a.fancybox-product').removeClass('active').each(function(){
				if(jQuery(this).attr('href') == thisHref){
					jQuery(this).addClass('active');
				}
			});
		});
		jQuery('.fancybox-product').fancybox();
	});
}

/* Top Link Wishlist Quantity */
function getWishlistCount(){
	toopLink = jQuery('header#header a.top-link-wishlist');
	url = toopLink.attr('href').replace("wishlist/","meigeeactions/wishlist/count");
	jQuery.ajax({
		url : url,
		success : function(data) {
			toopLink.find('.wishlist-items').html(data);
		}
	});
}

/* Header Customer Block */
function headerCustomer(reset){
	if(jQuery('.header-right.simple-list').length){
		custName = jQuery('#header .customer-name');
		links = custName.parent().next('.links');
		links.hide();
		if(reset){
			custName.off('click.moblinks');
			links.css('display', 'inline-block');
		}else{
			custName.off('click.moblinks').on('click.moblinks', function(){
				links.slideToggle();
			});
		}
	}
}

/* Retina logo resizer */
function stickyLogoResize(){
	if (pixelRatio > 1) {
		jQuery('header#sticky-header h2.small-logo').each(function(){
			var thisLogo = jQuery(this);
			setTimeout(function(){
				thisLogo.attr('style', '');
				thisLogo.find('img').attr('style', '');
				if(thisLogo.hasClass('logo')){
					thisLogo.css({
						'position': 'absolute',
						'opacity': '0'
					});
				}
				thisLogo.find('img').css('width', (thisLogo.find('img').width()/2));
			}, 100);
		});
	}
}

function contentTop() {
	if(jQuery('body').hasClass('hitched-header') && jQuery('header#header').length){
		jQuery('header#header').each(function(){
			header = jQuery(this);
			var headerHeight = header.outerHeight();
			if(jQuery('.top-container').length) {
				content = jQuery('.top-container');
			} else {
				content = jQuery('.content-wrapper');
			}
			if(!header.hasClass('floating')){
				jQuery('.header-wrapper').addClass('positioned');
				if (jQuery(document.body).width() > 767) {
					content.css({
						'top' : -headerHeight,
						'margin-bottom' : -headerHeight
					});
				} else {
					jQuery('body').removeClass('hitched-header');
					content.css({
						'top' : 'auto',
						'margin-bottom' : 0
					});
				}
			}
		});
	}
}
  
  /* Header Search */
function headerSearch() {
	if(jQuery('header.header .search_mini_form').length && jQuery('.page-no-route').length == 0){
		setTimeout(function(){
			jQuery('header.header').each(function(){
				header = jQuery(this);
				var search = header.find('.search_mini_form');
				if(jQuery(document.body).width() > 767) {
					var menuHeight = jQuery('.menu-button').offset().top;
					var searchHeight = search.outerHeight();
					var searchTop = menuHeight - (searchHeight/2);
					var searchTop = header.find('.header-right').outerHeight(true);
					search.removeClass('positioned');
					search.addClass('floating');
					header.find('.search_mini_form.floating').css({
						'top' : searchTop - 1
					});
					if(!header.find('.search_mini_form').hasClass('visible')){
						header.find('.search_mini_form.floating').css({
							'display' : 'none'
						});
						if(header.hasClass('floating')){
							jQuery('header#sticky-header .search-button').off().on('click', function() {
								jQuery('header#sticky-header .search-button').toggleClass('open');
								jQuery('header#sticky-header .search_mini_form.floating').toggleClass('visible').slideToggle();
							});
						} else {
							jQuery('header#header .search-button').off().on('click', function() {
								jQuery('header#header .search-button').toggleClass('open');
								jQuery('header#header .search_mini_form.floating').toggleClass('visible').slideToggle();
							});
						}
					}
					if(!jQuery('body').hasClass('rtl')){
						header.find('.top-cart .topCartContent').css('left', 'auto');
					} else {
						header.find('.top-cart .topCartContent').css('right', 'auto');
					}
				} else {
					if(header.hasClass('floating')){
						headerHeight = header.find('.header-right').position().top + header.find('.header-right').outerHeight()+ parseFloat(header.find('.header-right').css('margin-top'));
						if(jQuery('header.header.floating .top-cart').length){
							cartLeft = jQuery('header.header.floating .top-cart').offset().left - jQuery('header.header .header-top .container_12').position().left - parseFloat(jQuery('header.header .header-top .container_12').css('margin-left'));
							jQuery('header.header.floating .top-cart .topCartContent').css('left', -cartLeft);
						}
					} else {
						headerHeight = header.find('.header-top').height() - header.find('.menu-button').outerHeight();
						header.find('.top-cart .topCartContent').css('left', 0);
					}
					var searchHeight = search.height();
					header.find('.search-button').off().on('click', function() {
						jQuery(this).toggleClass('open');
						search.toggleClass('visible').slideToggle();
					});
					search.css({
						'top' : headerHeight
					});
				}
			});
		}, 300);
	}
}

jQuery(window).load(function() {
	
	/* Fix for IE */
    	if(navigator.userAgent.indexOf('IE')!=-1 && jQuery.support.noCloneEvent){
			jQuery.support.noCloneEvent = true;
		}
	/* End fix for IE */

	/* More Views Slider */
	if(jQuery('#more-views-slider').length){
		jQuery('#more-views-slider').iosSlider({
		   responsiveSlideWidth: true,
		   snapToChildren: true,
		   desktopClickDrag: true,
		   infiniteSlider: false,
		   navSlideSelector: '.sliderNavi .naviItem',
		   navNextSelector: '.more-views .next',
		   navPrevSelector: '.more-views .prev'
		 });
		
	 }
	 function more_view_set_height(){
		if(jQuery('#more-views-slider').length){
			var more_view_height = 0;
			jQuery('#more-views-slider li a').each(function(){
			 if(jQuery(this).height() > more_view_height){
			  more_view_height = jQuery(this).height();
			 }
			})
			jQuery('#more-views-slider').css('min-height', more_view_height+2);
		}
	 }
	 /* More Views Slider */

	 /* Related Block Slider */
	  if(jQuery('#block-related-slider').length) {
	  jQuery('#block-related-slider').iosSlider({
		   responsiveSlideWidth: true,
		   snapToChildren: true,
		   desktopClickDrag: true,
		   infiniteSlider: false,
		   navSlideSelector: '.sliderNavi .naviItem',
		   navNextSelector: '.block-related .next',
		   navPrevSelector: '.block-related .prev'
		 });
	 } 
	 
	 function related_set_height(){
		var related_height = 0;
		jQuery('#block-related-slider li.item').each(function(){
		 if(jQuery(this).height() > related_height){
		  related_height = jQuery(this).height();
		 }
		})
		jQuery('#block-related-slider').css('min-height', related_height+2);
	}
	 /* Related Block Slider */
	 
   /* Layered Navigation Accorion */
  if (jQuery('#layered_navigation_accordion').length) {
    jQuery('.filter-label').each(function(){
        jQuery(this).toggle(function(){
            jQuery(this).addClass('closed').next().slideToggle(200);
        },function(){
            jQuery(this).removeClass('closed').next().slideToggle(200);
        })
    });
  }
  /* Layered Navigation Accorion */
   
   var menuItem;
  
  /* Sticky Wide Menu Position */
  function wideMenuPos() {
	jQuery('.nav-container li.level-top').each(function(){
		var menuItem = jQuery(this);
		if(jQuery(document.body).width() > 977){
			itemHeight = jQuery(this).position().top + jQuery(this).outerHeight();
			menuItem.find('.menu-wrapper').css('top', itemHeight);
		}
		menuItem.children('a.level-top').hover(
			function() {
				jQuery(this).attr('style', '');
				borderWidth = parseFloat(jQuery(this).css('border-bottom-width'));
				paddingBottom = parseFloat(jQuery(this).css('padding-bottom'));
				jQuery(this).css('padding-bottom', paddingBottom - borderWidth);
			}, function() {
				paddingBottom = parseFloat(jQuery(this).css('padding-bottom'));
				jQuery(this).attr('style', '');
				if(jQuery(this).parent().hasClass('over') || jQuery(this).parent().hasClass('active')){
					jQuery(this).css('padding-bottom', paddingBottom);
				} else {
					jQuery(this).attr('style', '');
				}
			}
		);

		if(menuItem.hasClass('active')){
			menuItem.children('a.level-top').attr('style', '');
			borderWidth = parseFloat(menuItem.children('a.level-top').css('border-bottom-width'));
			paddingBottom = parseFloat(menuItem.children('a.level-top').css('padding-bottom'));
			menuItem.children('a.level-top').css('padding-bottom', paddingBottom - borderWidth);
		}
	});
  }
	
	/* Footer Links */
	function footerLinksAnimation() {
		if(jQuery('.footer-links').length){
			if (jQuery(document.body).width() > 767){
				jQuery('.footer-links > span').on('click', function() {
					if(!jQuery('.animation-progress').length) {
						footerLinksButton = jQuery(this);
						footerLinksButton.animate({'opacity': '0', 'z-index': '-1'}, 500);
						jQuery('.footer-links ul').addClass('active').css('top', 'auto');
						jQuery('.footer-links ul li i').animate({'opacity': '1'}, 400);
						jQuery('#footer .footer-links li:not(".remover")').each(function(i) {
							i ++;
							jQuery(this).addClass('animation-progress').animate({'top' : '0'}, i*100, function(){
								jQuery(this).removeClass('animation-progress');
							});
						});
					}
				});
				jQuery('.footer-links ul li i').on('click', function() {
					if(!jQuery('.animation-progress').length) {
						jQuery('#footer .footer-links li:not(".remover")').each(function(i) {
							i ++;
							jQuery(this).addClass('animation-progress').animate({'top' : '-100px'}, i*200, function(){
								jQuery(this).removeClass('animation-progress');
							});
						});
						jQuery(this).parent().parent().prev().animate({'opacity': '1'}, 500);
						jQuery(this).animate({'opacity': '0'}, 300);
						jQuery('.footer-links > span').animate({'opacity': '1', 'z-index': '9999'}, 400);
						jQuery('.footer-links ul').removeClass('active').css('top', 'auto');
					}
				});
			} else {
				var headerHeight = jQuery('header#sticky-header').outerHeight();
				jQuery('.footer-links > span').off().show().css('opacity', 1);
				jQuery('.footer-links > span').on('click', function() {
					jQuery(this).next().slideToggle().css('top', headerHeight);
				});
				jQuery('.footer-links ul li i').css('opacity', 1).on('click', function() {
					jQuery(this).parent().parent().slideUp().css('top', headerHeight);
				});
			}
		}
	}
  
	
 
  jQuery(window).scroll(function(){headerSearch(); wideMenuPos(); footerLinksAnimation(); contentTop()});
  
  /* Product Collateral Accordion */
  if (jQuery('#collateral-accordion').length) {
	  jQuery('#collateral-accordion > div.box-collateral').not(':first').hide();  
	  jQuery('#collateral-accordion > h2').click(function() {
		jQuery(this).parent().find('h2').removeClass('active');
		jQuery(this).addClass('active');
		
	    var nextDiv = jQuery(this).next();
	    var visibleSiblings = nextDiv.siblings('div:visible');
	 
	    if (visibleSiblings.length ) {
	      visibleSiblings.slideUp(300, function() {
	        nextDiv.slideToggle(500);
	      });
	    } else {
	       nextDiv.slideToggle(300, function(){
				if(!nextDiv.is(":visible")){
					jQuery(this).prev().removeClass('active');
				}
		   });
	    }
	  });
  }
  /* Product Collateral Accordion */

  /* My Cart Accordion */
  if (jQuery('#cart-accordion').length) {
	  jQuery('#cart-accordion > div.accordion-content').hide();	  
	  
	  jQuery('#cart-accordion > h3.accordion-title').wrapInner('<span/>').click(function(){
	  
		var accordion_title_check_flag = false;
		if(jQuery(this).hasClass('active')){accordion_title_check_flag = true;}
		jQuery('#cart-accordion > h3.accordion-title').removeClass('active');
		if(accordion_title_check_flag == false){
			jQuery(this).toggleClass('active');
	    }
		
		var nextDiv = jQuery(this).next();
	    var visibleSiblings = nextDiv.siblings('div:visible');
	 
	    if (visibleSiblings.length ) {
	      visibleSiblings.slideUp(300, function() {
	        nextDiv.slideToggle(500);
	      });
	    } else {
	       nextDiv.slideToggle(300);
	    }
	  });
  }
  /* My Cart Accordion */
  
  /* Coin Slider */

	/* Fancybox */
	if (jQuery.fn.fancybox) {
		jQuery('.fancybox').fancybox();
	}
	/* Fancybox */

	/* Zoom */
	if (jQuery('#zoom').length) {
		function imgChecker(){
			jQuery('#image').css('width', 'auto');
			imgHolder = jQuery('.product-view .img-holder');
			if(jQuery('#image').width() < imgHolder.width()){
				imgHolder.addClass('no-zoom');
				jQuery('#zoom').on('click.zoom', function(event){
					event.preventDefault();
				});
			}else{
				jQuery('#zoom').off('click.zoom');
				imgHolder.removeClass('no-zoom');
			}
			jQuery('#image').css('width', '100%');
		}
		imgChecker();
		jQuery(window).resize(function(){imgChecker()});
		jQuery('.product-view .more-views a').on('click.moreviews', function(){
			setTimeout(function(){
				imgChecker();
			}, 200);
		});
		jQuery('.cloud-zoom, .cloud-zoom-gallery').CloudZoom();
  	}
	/* Zoom */
	
	/* Responsive */
	var responsiveflag = false;
	var topSelectFlag = false;
	var menu_type = jQuery('.nav').attr('class');
	
	/* Menu */
	function mobileDevicesMenu(action){
		if(action == 'reset'){
			jQuery(".nav-container .nav li a, .nav-container .nav-wide li a").off();
		}else{
			function topMenuListener(e){
				var touch = e.touches[0];
				if(jQuery(touch.target).parents('.nav, .nav-wide').length == 0){
					jQuery(".nav-container:not('.mobile') .nav li, .nav-container:not('.mobile') .nav-wide li").each(function(){
						jQuery(this).removeClass('touched over').children('ul').removeClass('shown-sub');
					});
					document.removeEventListener('touchstart', topMenuListener, false);
				}
			}
			jQuery(".nav-container:not('.mobile') .nav li a, .nav-container:not('.mobile') .nav-wide li.level-top > a").on({
				click: function (e){
					if (jQuery(this).parent().children('ul, .menu-wrapper').length ){
						if(jQuery(this).parent().hasClass('touched')){
							isActive = true;
						}else{
							isActive = false;
						}
						jQuery(this).parent().addClass('touched over');
						document.addEventListener('touchstart', topMenuListener, false);
						if(!isActive){
							return false;
						}
					}
				}
			});
		}
	}
	
	function mobile_menu(mode){
		switch(mode)
		{
		case 'animate':
		   if(!jQuery('.nav-container').hasClass('mobile')){
				if(mobileDevice == true){
					mobileDevicesMenu('reset');
				}
				jQuery(".nav-container").addClass('mobile');
				jQuery('.nav-container > ul').slideUp('fast');
				jQuery('.menu-button').removeClass('active');
				function mobileMenuListener(e){
					var touch = e.touches[0];
					if(jQuery(touch.target).parents('.nav-container.mobile').length == 0 && jQuery(touch.target).parents('.menu-button').length == 0 && !jQuery(touch.target).hasClass('menu-button') && jQuery(touch.target).parents('.search_mini_form').length == 0 && !jQuery(touch.target).hasClass('quick-access')){
						jQuery('.nav-container.mobile > ul').slideUp('medium');
						document.removeEventListener('touchstart', mobileMenuListener, false);
					}
				}
				
				jQuery('.menu-button').on('click', function(event){
					event.stopPropagation();
					jQuery(this).toggleClass('active');
					jQuery(this).parent().find('.nav-container > ul').slideToggle('medium');
					document.addEventListener('touchstart', mobileMenuListener, false);
					jQuery(document).on('click.mobileMenuEvent', function(e){
						if (jQuery(e.target).parents('.nav-container.mobile').length == 0 && jQuery(e.target).parents('.search_mini_form').length == 0 && !jQuery(e.target).hasClass('search_mini_form')) {
							jQuery('.nav-container.mobile > ul').slideUp('medium');
							jQuery(document).off('click.mobileMenuEvent');
						}
					});
				});
				
			   jQuery('.nav-container > ul a').each(function(){
					if(jQuery(this).next('ul').length || jQuery(this).next('div.menu-wrapper').length){
						jQuery(this).before('<span class="menu-item-button"><i class="fa fa-plus"></i><i class="fa fa-minus"></i></span>')
						jQuery(this).next('ul').slideUp('fast');
						jQuery(this).prev('.menu-item-button').on('click', function(){
							jQuery(this).toggleClass('active');
							jQuery(this).nextAll('ul, div.menu-wrapper').slideToggle('medium');
						});
					}
				});
			
		   }
		break;
		default:
				jQuery(".nav-container").removeClass('mobile');
				jQuery('.menu-button').off();
				jQuery(document).off('click.mobileMenuEvent');
				jQuery('.nav-container > ul').slideDown('fast');
				jQuery('.nav-container .menu-item-button').each(function(){
					jQuery(this).nextAll('ul').slideDown('fast');
					jQuery(this).remove();
				});
				jQuery('.nav-container .menu-wrapper').slideUp('fast');
				if(mobileDevice == true){
					mobileDevicesMenu();
				}
		 }
	}
	
	/* Product tabs */
 	if(jQuery('.product-tabs-widget').length){
		function productTabs(){
			jQuery('ul.product-tabs').on('click', 'li:not(.current)', function() {
				jQuery(this).addClass('current').siblings().removeClass('current')
				.parents('div.product-tabs-wrapper').find('div.product-tabs-box').eq(jQuery(this).index()).fadeIn(800).addClass('visible').siblings('div.product-tabs-box').hide().removeClass('visible');
				labelsHeight();
			});
		}
		function productTabsBg(){
			if(jQuery('.product-tabs-wrapper').length){
				jQuery('.product-tabs-wrapper').each(function(){
					if(jQuery(this).find('.product-tabs-box').length && jQuery(this).find('.product-tabs-bg').length){
						maxHeight = 0;
						jQuery(this).find('.product-tabs-box').each(function(){
							tabContent = jQuery(this).outerHeight(true);
							if(tabContent > maxHeight) {
								maxHeight = tabContent;
							}
						});
						blockIndents = parseFloat(jQuery(this).css('padding-top')) + parseFloat(jQuery(this).css('padding-bottom'));
						if(jQuery(this).find('.widget-title').length){
							listHeight = jQuery(this).find('.product-tabs').outerHeight(true) + jQuery(this).find('.widget-title').outerHeight(true);
						} else {
							listHeight = jQuery(this).find('.product-tabs').outerHeight(true);
						}
						blockHeight = maxHeight + listHeight + blockIndents;
						jQuery(this).children('.product-tabs-box:not(".visible")').css({
							'position' : 'static',
							'opacity' : 1,
							'display' : 'none'
						});
						if(jQuery('body').hasClass('boxed-layout')) {
							blockWidth = jQuery(this).width()+81;
						} else {
							blockWidth = jQuery(this).width()+30;
						}
						siteWidth = jQuery(window).width();
						bg = jQuery(this).find('.product-tabs-bg');
						bg.attr('style', '');
						bgIndent = bg.offset().left;
						if(jQuery('body').hasClass('boxed-layout')){
							if(jQuery(document.body).width() < 479){
								bg.css({
									'left': '-'+25+'px',
									'width': siteWidth+'px',
									'height': blockHeight
								}).parent().css('height', blockHeight - blockIndents);
							} else if(jQuery(document.body).width() > 479 && jQuery(document.body).width() < 767){
								bg.css({
									'left': '-'+bgIndent+'px',
									'width': siteWidth+'px',
									'height': blockHeight
								}).parent().css('height', blockHeight - blockIndents);;
							} else if(jQuery(document.body).width() > 767 && jQuery(document.body).width() < 978){
								bg.css({
									'width': blockWidth - 20,
									'left': '-10px',
									'height': blockHeight
								}).parent().css('height', blockHeight - blockIndents);
							} else {
								bg.css({
									'width': blockWidth,
									'left': '-40px',
									'height': blockHeight
								}).parent().css('height', blockHeight - blockIndents);
							}
						}else{
							bg.css({
								'left': '-'+bgIndent+'px',
								'width': siteWidth+'px',
								'height': blockHeight
							}).parent().css('height', blockHeight - blockIndents);;
						}
						if(jQuery(this).find('.top-buttons').length && jQuery(this).find('.widget-title').length){
							if(jQuery(document.body).width() < 767){
								titleHeight =  jQuery(this).find('.widget-title').innerHeight();
								blockTopIndent = parseFloat(jQuery(this).css('padding-top'));
								listHeight = jQuery(this).find('.product-tabs').outerHeight(true);
								jQuery(this).find('.product-tabs').css('top', titleHeight + blockTopIndent + listHeight/2 + 5)
							} else {
								jQuery(this).find('.product-tabs').attr('style', '');
							}
						}
					}
				});
			}
		}
		productTabs();
		productTabsBg();
		jQuery(window).resize(function(){productTabsBg()});
	}
	
	/* Mobile Cart Remove Link */
	function MCRemoveLink() {
		if(jQuery(document.body).width() < 767) {
			jQuery('.cart-table .remove .btn-remove2').each(function(){
				var cartButtons = jQuery(this).parent().parent().find('.product-box .c_actions');
				jQuery(this).appendTo(cartButtons);
			});
		}
	}
	
	/* Mobile Sidebar on Product Page */
	function mobileSidebar() {
		if(jQuery('.product-view .sidebar-left').length){
			sidebar = jQuery('.product-view .sidebar-left');
			content = jQuery('.product-view .product-essential');
			setTimeout(function(){
				if(jQuery(document.body).width() < 767) {
					content.parent().css('padding-bottom', sidebar.outerHeight(true));
					sidebar.css({
						'top' : content.outerHeight(),
						'width' : content.width()
					});
				} else {
					content.parent().attr('style', '');
					sidebar.attr('style', '');
				}
			}, 1000);
		} 
	}
	
	
	function toDo(){
		if (jQuery(document.body).width() < 767 && responsiveflag == false){
			/* Top Menu Select */
			if(topSelectFlag == false){
				jQuery('.nav-container .sbSelector').wrapInner('<span />').prepend('<span />');
				topSelectFlag = true;
			}
			jQuery('.nav-container .sbOptions li a').on('click', function(){
				if(!jQuery('.nav-container .sbSelector span').length){
					jQuery('.nav-container .sbSelector').wrapInner('<span />').prepend('<span />');
				}
			});
			/* //Top Menu Select */
			responsiveflag = true;
		}
		else if (jQuery(document.body).width() > 767){
			responsiveflag = false;
		}
	}
	
	function pageNotFound() {
		if(jQuery('.not-found-bg').data('bgimg')){
			var bgImg = jQuery('.not-found-bg').data('bgimg');
			jQuery('.not-found-bg').attr('style', bgImg);
		}
	}
	
	/* Header Customer Block */
	
	function headerCustomer2() {
		if(jQuery('#header .customer-name').length && !jQuery('#header .header-right.simple-list').length){
			var custName = jQuery('#header .customer-name');
			var linksTop = jQuery('#header .header-right').outerHeight();
			var WishlistLink = jQuery('.top-link-wishlist');
			jQuery('#header .customer-name').before(WishlistLink);
			jQuery('#header .links').hide();
			jQuery('header#header .customer-name').removeClass('open');
			jQuery('header#header .customer-name + .links').slideUp(500);
			function headerCustomerListener(e){
				var touch = e.touches[0];
				if(jQuery(touch.target).parents('header#header .customer-name + .links').length == 0 && !jQuery(touch.target).hasClass('customer-name') && !jQuery(touch.target).parents('.customer-name').length){
					jQuery('header#header .customer-name').removeClass('open');
					jQuery('header#header .customer-name + .links').slideUp(500);
					document.removeEventListener('touchstart', headerCustomerListener, false);
				}
			}
			custName.off().on('click', function(event){
				event.stopPropagation();
				jQuery(this).toggleClass('open');
				jQuery('#header .links').slideToggle().css('top', linksTop);
				document.addEventListener('touchstart', headerCustomerListener, false);
				jQuery(document).on('click.headerCustomerEvent', function(e) {
					if (jQuery(e.target).parents('header#header ul.links').length == 0) {
						jQuery('header#header .customer-name').removeClass('open');
						jQuery('header#header .customer-name + .links').slideUp(500);
						jQuery(document).off('click.headerCustomerEvent');
					}
				});
			});
		}
		if(!jQuery('#header .header-right.simple-list').length){
			jQuery('.wishlist-items').appendTo('header#header .top-link-wishlist');
		}
	}

	if(jQuery('.twitter-timeline').length){
		jQuery('.twitter-timeline').contents().find('head').append('<style>body{color: #888} body .p-author .profile .p-name{color: #fff}</style>');
	}

	if(jQuery('#footer iframe#contact-map').length){
		jQuery('#footer iframe#contact-map').contents().find('.map-container').addClass('no-contacts');
	}
	
	function backgroundWrapper(){
		if(jQuery('.background-wrapper').length){
			jQuery('.background-wrapper').each(function(){
				if(jQuery('body').hasClass('boxed-layout')) {
					blockWidth = jQuery(this).parent().width()+40;
				} else {
					blockWidth = jQuery(this).parent().width()+30;
				}
				siteWidth = jQuery(window).width();
				var bg = jQuery(this);
				bg.parent().css('position', 'relative');
				bgIndent = jQuery(this).parent().offset().left;
				if(jQuery('body').hasClass('boxed-layout')){
					if(jQuery(document.body).width() < 767){
						bg.css({
							'left': '-'+bgIndent+'px',
							'width': siteWidth+'px'
						});
					} else if(jQuery(document.body).width() > 767 && jQuery(document.body).width() < 977){
						bg.css({
							'width': blockWidth - 20,
							'left': -10
						});
					} else {
						if(jQuery(this).parent('.container_12').length == 0){
							
						} else {
							blockWidth = jQuery(this).parent().width()+20;
						}
						bg.css({
							'width': blockWidth + 40,
							'left': -40
						});
					}
				}else{
					if(jQuery(document.body).width() > 767 && jQuery(document.body).width() < 977){
						bg.css({
							'width': siteWidth+'px',
							'left': '-'+bgIndent+'px'
						});
					} else {
						bg.css({
							'left': '-'+bgIndent+'px',
							'width': siteWidth+'px'
						});
					}
				}
				if(bg.children('.text-banner').length){
				 	if(bg.parent().hasClass('parallax-banners-wrapper')) {
						jQuery('.parallax-banners-wrapper').each(function(){
							var wrapper = jQuery(this);
							headerHeight = jQuery('header#header').outerHeight();
							jQuery('.parallax-content').css({
								'top' : -headerHeight,
								'margin-bottom' : -headerHeight
							});
							block = jQuery(this).find('.text-banner');
							var fullHeight = 0;
							var imgCount = block.size();
							var currentIndex = 0;
							block.each(function(){
								if(jQuery(this).children('.banner-content').data('colors')){
									jQuery(this).children('.banner-content').addClass(jQuery(this).children('.banner-content').data('colors'));
								}
								imgUrl = jQuery(this).find('.background').css('background-image').replace(/url\(|\)|\"/ig, '');
								if(imgUrl.indexOf('none')==-1){
									img = new Image;
									img.src = imgUrl;
									img.setAttribute("name", jQuery(this).attr('id'));
									img.onload = function(){
										imgName = '#' + jQuery(this).attr('name');
										if(jQuery('.parallax-banners-wrapper').data('fullscreen')){
											windowHeight = document.compatMode=='CSS1Compat' && !window.opera?document.documentElement.clientHeight:document.body.clientHeight;
											jQuery(imgName).find('.background').css({
												'height' : windowHeight+'px',
												'background-size' : '100% 100%'
											});
											jQuery(imgName).css('height', (windowHeight)+'px');
											fullHeight += windowHeight;
										} else {
											jQuery(imgName).find('.background').css('height', this.height+'px');
											jQuery(imgName).css('height', (this.height - 100)+'px');
											fullHeight += this.height - 100;
											if (pixelRatio > 1) {
												jQuery(imgName).find('.background').css('background-size', this.width+'px' + ' ' + this.height+'px');
											}
										}
										wrapper.css('height', fullHeight);
										currentIndex++;
										if(!jQuery('body').hasClass('mobile-device')){
											if(currentIndex == imgCount){
												if(jQuery(document.body).width() > 1278) {
													jQuery('#parallax-banner-1').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-1').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-2').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-3').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-4').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-5').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-6').parallax("60%", 0.4, false);
													jQuery('#parallax-banner-7').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-8').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-9').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-10').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-11').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-12').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-13').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-14').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-15').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-16').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-17').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-18').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-19').parallax("60%", 0.7, false);
													jQuery('#parallax-banner-20').parallax("60%", 0.7, false);
												} else if(jQuery(document.body).width() > 977) {
													jQuery('#parallax-banner-1').parallax("60%", 0.2, false);
													jQuery('#parallax-banner-2').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-3').parallax("60%", 0.9, false);
													jQuery('#parallax-banner-4').parallax("60%", 0.85, false);
													jQuery('#parallax-banner-5').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-6').parallax("60%", 0.4, false);
													jQuery('#parallax-banner-7').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-8').parallax("60%", 0.9, false);
													jQuery('#parallax-banner-9').parallax("60%", 0.85, false);
													jQuery('#parallax-banner-10').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-11').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-12').parallax("60%", 0.9, false);
													jQuery('#parallax-banner-13').parallax("60%", 0.85, false);
													jQuery('#parallax-banner-14').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-15').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-16').parallax("60%", 0.9, false);
													jQuery('#parallax-banner-17').parallax("60%", 0.85, false);
													jQuery('#parallax-banner-18').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-19').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-20').parallax("60%", 0.9, false);
												} else if(jQuery(document.body).width() > 767) {
													jQuery('#parallax-banner-1').parallax("60%", 0.3, false);
													jQuery('#parallax-banner-2').parallax("60%", 0.3, false);
													jQuery('#parallax-banner-3').parallax("60%", 0.3, false);
													jQuery('#parallax-banner-4').parallax("60%", 0.3, false);
													jQuery('#parallax-banner-5').parallax("60%", 0.3, false);
													jQuery('#parallax-banner-6').parallax("60%", 0.3, false);
													jQuery('#parallax-banner-7').parallax("60%", 0.3, false);
													jQuery('#parallax-banner-8').parallax("60%", 0.1, false);
													jQuery('#parallax-banner-9').parallax("60%", 0.1, false);
													jQuery('#parallax-banner-10').parallax("60%", 0.1, false);
													jQuery('#parallax-banner-11').parallax("60%", 0.1, false);
													jQuery('#parallax-banner-12').parallax("60%", 0.1, false);
													jQuery('#parallax-banner-13').parallax("60%", 0.1, false);
													jQuery('#parallax-banner-14').parallax("60%", 0.1, false);
													jQuery('#parallax-banner-15').parallax("60%", 0.1, false);
													jQuery('#parallax-banner-16').parallax("60%", 0.1, false);
													jQuery('#parallax-banner-17').parallax("60%", 0.1, false);
													jQuery('#parallax-banner-18').parallax("60%", 0.1, false);
													jQuery('#parallax-banner-19').parallax("60%", 0.1, false);
													jQuery('#parallax-banner-20').parallax("60%", 0.1, false);
												} else if(jQuery(document.body).width() < 767) {
													jQuery('#parallax-banner-1').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-2').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-3').parallax("60%", 0.9, false);
													jQuery('#parallax-banner-4').parallax("60%", 0.85, false);
													jQuery('#parallax-banner-5').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-6').parallax("60%", 0.4, false);
													jQuery('#parallax-banner-7').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-8').parallax("60%", 0.9, false);
													jQuery('#parallax-banner-9').parallax("60%", 0.85, false);
													jQuery('#parallax-banner-10').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-11').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-12').parallax("60%", 0.9, false);
													jQuery('#parallax-banner-13').parallax("60%", 0.85, false);
													jQuery('#parallax-banner-14').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-15').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-16').parallax("60%", 0.9, false);
													jQuery('#parallax-banner-17').parallax("60%", 0.85, false);
													jQuery('#parallax-banner-18').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-19').parallax("60%", 0.8, false);
													jQuery('#parallax-banner-20').parallax("60%", 0.9, false);
												}
											}
										}
									}
								}
								bannerText = jQuery(this).find('.banner-content');
								if(bannerText.data('top')){
									bannerText.css('top', bannerText.data('top'));
								}
								if(bannerText.data('left')){
									if(!bannerText.data('right')){
										bannerText.css({
											'left': bannerText.data('left'),
											'right' : 'auto'
										});
									} else {
										bannerText.css('left', bannerText.data('left'));
									}
								}
								if(bannerText.data('right')){
									if(!bannerText.data('left')){
										bannerText.css({
											'right': bannerText.data('right'),
											'left' : 'auto'
										});
									} else {
										bannerText.css('right', bannerText.data('right'));
									}
								}
							});
						});
						jQuery(window).scroll(function() {
							jQuery('.parallax-banners-wrapper').each(function(){
								block = jQuery(this).find('.text-banner');
								block.each(function(){
									var imagePos = jQuery(this).offset().top;
									var topOfWindow = jQuery(window).scrollTop();
									if (imagePos < topOfWindow+400) {
										jQuery(this).addClass("slideup");
									} else {
										jQuery(this).removeClass("slideup");
									}
								});
							});
						});
						setTimeout(function(){
							jQuery('.parallax-page #parallax-loading').fadeOut(200);
						}, 500);
					} else {
						blockHeight = bg.children('img').outerHeight();
						block.css('height', blockHeight);
						bg.css('height', blockHeight);
					}
				}
				
				if(bg.parents('.map-wrapper').length) {
					mapHeight = parseFloat(bg.find('#contact-map').css('height'));
					bg.css('height', mapHeight);
					bg.parents('.map-wrapper').css('height', mapHeight);
				}
				
			});
		}
	}
	
	/* Products grid titles */
	function productTitlesHeight(){
		function titleSet(group){
			maxTitleHeight = 0;
			group.each(function(){
				if(jQuery(this).find('.product-name').length){
					title = jQuery(this).find('.product-name').height();
					if(title > maxTitleHeight){
						maxTitleHeight = title;
					}
				}
			});
			group.find('.product-name').css('min-height', maxTitleHeight);
		}
		jQuery('.products-grid:not(.carousel-ul, #upsell-product-table)').each(function(){
			gridWidth = jQuery(this).width();
			itemWidth = jQuery(this).find('li.item:first').width();
			columnsCount = Math.round(gridWidth/itemWidth);
			items = jQuery(this).find('li.item');
			if(items.find('.product-name').length){
				items.find('.product-name').css('min-height', 0); //clear title height
				groupsCount = items.size()/columnsCount;
				ratio = 1;
				for(i=0; i<groupsCount; i++){
					currentGroupe = items.slice((i*columnsCount), (columnsCount*ratio));
					/* set title height */
					titleSet(currentGroupe);
					/* ==== */
					ratio++;
				}
			}
		});
		jQuery('.products-grid.carousel-ul').each(function(){
			titleSet(jQuery(this).find('li.item'));
		});
	}
	
	function replacingClass () {
		if (jQuery(document.body).width() < 480) { //Mobile
			mobile_menu('animate');
			headerCustomer();
		}
		if (jQuery(document.body).width() > 479 && jQuery(document.body).width() < 768) { //iPhone
			mobile_menu('animate');
			headerCustomer();
		}  
		if (jQuery(document.body).width() > 767 && jQuery(document.body).width() < 977){ //Tablet
			mobile_menu('animate');
			headerCustomer(true);
		}
		if (jQuery(document.body).width() > 977 && jQuery(document.body).width() < 1279){ //Tablet
			mobile_menu('reset');
			headerCustomer(true);
		}
		if (jQuery(document.body).width() > 1279){ //Extra Large
			mobile_menu('reset');
			headerCustomer(true);
		}
	}
	
	replacingClass();
	toDo();
	more_view_set_height();
	wishlist_set_height();
	related_set_height();
	MCRemoveLink();
	headerSearch();
	footerLinksAnimation();
	labelsHeight();
	mobileSidebar();
	headerCustomer2();
	productTitlesHeight();
	backgroundWrapper();
	wideMenuPos();
	contentTop();
	pageNotFound();
	jQuery(window).resize(function(){toDo(); replacingClass(); more_view_set_height(); wishlist_set_height(); related_set_height(); MCRemoveLink(); headerSearch(); footerLinksAnimation(); mobileSidebar(); headerCustomer2(); productTitlesHeight(); backgroundWrapper(); wideMenuPos(); contentTop();});
	/* Responsive */

	/* Top Menu */
	function menuHeight2 () {
		var menu_min_height = 0;
		jQuery('.nav li.tech').css('height', 'auto');
		jQuery('.nav li.tech').each(function(){
			if(jQuery(this).height() > menu_min_height){
				menu_min_height = jQuery(this).height();
			}
		});		
		jQuery('.nav li.tech').each(function(){
			jQuery(this).css('height', menu_min_height + 'px');
		});
	}
	
	/* Top Selects */
	function option_class_add(items, is_selector){
		jQuery(items).each(function(){
			if(is_selector){
				jQuery(this).removeAttr('class'); 
				jQuery(this).addClass('sbSelector');
			}			
			stripped_string = jQuery(this).html().replace(/(<([^>]+)>)/ig,"");
			RegEx=/\s/g;
			stripped_string=stripped_string.replace(RegEx,"");
			jQuery(this).addClass(stripped_string.toLowerCase());
			if(is_selector){
				tags_add();
			}
		});
	}
	option_class_add('.sbOptions li a, .sbSelector', false);
	jQuery('.sbOptions li a').on('click', function(){
		option_class_add('sbSelector', true);
	});	
	function tags_add(){
		jQuery('.sbSelector').each(function(){
			if(!jQuery(this).find('span.text').length){
				jQuery(this).wrapInner('<span class="text" />').append('<span />').find('span:last').wrapInner('<span />');
			}
		});
	}
	tags_add();
	/* //Top Selects */
	
	if (jQuery('body').hasClass('retina-ready')) {
		/* Mobile Devices */
		if((navigator.userAgent.match(/iPhone/i)) || (navigator.userAgent.match(/iPod/i)) || (navigator.userAgent.match(/iPad/i)) || (navigator.userAgent.match(/Android/i))){
			
			/* Mobile Devices Class */
			jQuery('body').addClass('mobile-device');
			
			/* Menu */
			jQuery(".nav-container:not('.mobile') .nav li").on({
	            click: function (){
	                if ( !jQuery(this).hasClass('touched') && jQuery(this).children('ul').length ){
						jQuery(this).addClass('touched');
						clearTouch(jQuery(this));
						return false;
	                }
	            }
	        });
			
			/* Clear Touch Function */
			function clearTouch(handlerObject){
				jQuery('body').on('click', function(){
					handlerObject.removeClass('touched closed');
					if(handlerObject.parent().attr('id') == 'categories-accordion'){
						handlerObject.children('ul').slideToggle(200);
					}
					jQuery('body').off();
				});
				handlerObject.click(function(event){
					event.stopPropagation();
				});
				handlerObject.parent().click(function(){
					handlerObject.removeClass('touched');
				});
				handlerObject.siblings().click(function(){
					handlerObject.removeClass('touched');
				});
			}
			
			var mobileDevice = true;
		}else{
			var mobileDevice = false;
		}

		//images with custom attributes
		
		if (pixelRatio > 1) {
			function brandsWidget(){
				brands = jQuery('ul.brands li a img');
				brands.each(function(){
					jQuery(this).css('width', jQuery(this).width()/2);
				});
			}
			function textBannersImg(){
				bannerImages = jQuery('.home-text-banners .text-banner-image img');
				bannerImages.each(function(){
					jQuery(this).css('width', jQuery(this).width()/2);
				});
			}
			function logoResize(){
				jQuery('header#header h2.logo, footer#footer .footer-logo').each(function(){
					var logo = jQuery(this);
					setTimeout(function(){
						logo.attr('style', '');
						logo.find('img').attr('style', '');
						if(logo.hasClass('logo')){
							logo.css({
								'position': 'absolute',
								'opacity': '0'
							});
						}
						defaultStart = true;
						if(logo.hasClass('footer-logo')){
							logo.css('position', 'absolute');
							if(logo.parent().width() < logo.width()){
								logo.find('img').css('width', logo.parent().width() - (logo.parent().width()*0.15));
								defaultStart = false;
							}
						}
						if(defaultStart){
							logo.find('img').css('width', (logo.find('img').width()/2));
						}
						logo.css({
							'position': 'static',
							'opacity': '1'
						});
					}, 100);
				});
			}
			if(jQuery('.product-view .product-brand .brand-img img').length){
				productBrand = jQuery('.product-view .product-brand .brand-img img');
				productBrand.css('width', productBrand.width()/2);
			}
			logoResize();
			stickyLogoResize();
			brandsWidget();
			textBannersImg();
			headerSearch();
			jQuery(window).resize(function(){
				logoResize();
				stickyLogoResize();
			});
		}
    }
	
	/* Categories Accorion */
	if (jQuery('#categories-accordion').length){
		jQuery('#categories-accordion li.parent ul').before('<div class="btn-cat"><i class="fa fa-plus-square-o"></i><i class="fa fa-minus-square-o"></i></div>');
		jQuery('#categories-accordion li.level-top:not(.parent) > a').before('<i class="fa fa-square-o"></i>');
		if(mobileDevice == true){
			jQuery('#categories-accordion li.parent').each(function(){
				jQuery(this).on({
					click: function (){
						if(!jQuery(this).hasClass('touched')){
							jQuery(this).addClass('touched closed').children('ul').slideToggle(200);
							clearTouch(jQuery(this));
							return false;
						}
					}
				});
			});
		}else{
			jQuery('#categories-accordion li.parent .btn-cat').each(function(){
				jQuery(this).toggle(function(){
					jQuery(this).addClass('closed').next().slideToggle(200);
					jQuery(this).prev().addClass('closed');
				},function(){
					jQuery(this).removeClass('closed').next().slideToggle(200);
					jQuery(this).prev().removeClass('closed');
				})
			});
		}
	}
	/* Categories Accorion */
	
	/* Menu Wide */
	if(jQuery('.nav-wide').length){
		jQuery('.nav-wide li.level-top').mouseenter(function(){
			jQuery(this).addClass('over');
			if (pixelRatio > 1) {
				if(jQuery('.menu-wrapper .menu-banners').length){
					jQuery('.nav-wide li.level0').each(function(){
						jQuery(this).find('.menu-wrapper .menu-banners .menu-banner').each(function(){
							jQuery(this).find('img').css('width', jQuery(this).find('img').width()/2);
						});
					});
				}
			}
		});
		jQuery('.nav-wide li.level-top').mouseleave(function(){
			jQuery(this).removeClass('over');
			if (pixelRatio > 1) {
				if(jQuery('.menu-wrapper .menu-banners').length){
					jQuery('.nav-wide li.level0').each(function(){
						jQuery(this).find('.menu-wrapper .menu-banners .menu-banner').each(function(){
							jQuery(this).find('img').css('width', 'auto');
						});
					});
				}
			}
		});
		jQuery('.nav-wide .menu-wrapper > div.alpha.omega:first').addClass('first');
		
		columnsWidth = function(columnsCount, currentGroupe){
			if(currentGroupe.size() > 1){
				currentGroupe.each(function(){
					jQuery(this).css('width', (100/currentGroupe.size())+'%');
				});
			}else{
				currentGroupe.css('width', (100/columnsCount)+'%');
			}
		}
		jQuery('.nav-wide .menu-wrapper').each(function(){
			columnsCount = jQuery(this).attr('columns');
			items = jQuery(this).find('ul.level0 > li');
			groupsCount = items.size()/columnsCount;
			ratio = 1;
			for(i=0; i<groupsCount; i++){
				currentGroupe = items.slice((i*columnsCount), (columnsCount*ratio));
				/* set columns width */
				columnsWidth(columnsCount, currentGroupe);
				/* ==== */
				ratio++;
			}
		});
		
		/* Default Sub Menu in Wide Mode */
		elements = jQuery('.nav-wide .menu-wrapper.default-menu ul.level0 li');
		if(elements.length){
			elements.on('mouseenter mouseleave', function(){
				if(!jQuery('.nav-container').hasClass('mobile')){
					jQuery(this).children('ul').toggle();
				}
			});
			jQuery(window).resize(function(){
				if(!jQuery('.nav-container').hasClass('mobile')){
					elements.find('ul').hide();
				}
			});
			elements.each(function(){
				if(jQuery(this).children('ul').length){
					jQuery(this).addClass('parent');
				}
			});
			
			
			/* Default dropdown menu */
			items = [];
			jQuery('.nav-wide li.level0').each(function(){
				if(jQuery(this).children('.default-menu').length){
					items.push(jQuery(this));
				}
			});
			jQuery(items).each(function(){
				jQuery(this).on('mouseenter mouseleave', function(){
					if(jQuery(this).hasClass('over')){
						if(!jQuery('body').hasClass('rtl')){
							jQuery(this).children('.default-menu').css({
								'top': jQuery(this).position().top + jQuery(this).height(),
								'left': jQuery(this).position().left
							});
						}else{
							jQuery(this).children('.default-menu').css({
								'top': jQuery(this).position().top + jQuery(this).height(),
								'left': jQuery(this).position().left - (jQuery(this).children('.default-menu').width() - jQuery(this).width())
							});
						}
						
					}else{
						jQuery(this).children('.default-menu').css('left', '-10000px');
					}
				});
			});
		}
	}
	
});
var pixelRatio = !!window.devicePixelRatio ? window.devicePixelRatio : 1;
jQuery(document).ready(function(){

	if (jQuery('body').hasClass('retina-ready')) {
		if (pixelRatio > 1) {
			jQuery('body').addClass('retina-display');
			jQuery('img[data-srcX2]').each(function(){
				jQuery(this).attr('src',jQuery(this).attr('data-srcX2'));
			});
			jQuery('.newsletter-line').each(function(){
				var newsletterBg = jQuery(this).find('.grid_12');
				newsletterBg.attr('style',newsletterBg.attr('data-srcx2'));
			});
		}
	}
	
	var isApple = false;
	/* apple position fixed fix */
	if((navigator.userAgent.match(/iPhone/i)) || (navigator.userAgent.match(/iPod/i)) || (navigator.userAgent.match(/iPad/i))){
		isApple = true;
		
		function stickyPosition(clear){
			items = jQuery('header.header, .backstretch');
			if(clear == false){
				topIndent = jQuery(window).scrollTop();
				items.css({
					'position': 'absolute',
					'top': topIndent
				});
			}else{
				items.css({
					'position': 'fixed',
					'top': '0'
				});
			}
		}
		
		jQuery('.sticky-search header.header .form-search input').on('focusin focusout', function(){
			jQuery(this).toggleClass('focus');
			if(jQuery('header.header').hasClass('floating')){
				if(jQuery(this).hasClass('focus')){
					setTimeout(function(){
						stickyPosition(false);
					}, 500);
				}else{
					stickyPosition(true);
				}
			}
		});
	}
	
	/* sticky header */
	if(jQuery('body').hasClass('floating-header')){
		var headerHeight = jQuery('#header').height();
		sticky = jQuery('#sticky-header');
		jQuery(window).on('scroll', function(){
			if(!isApple){
				heightParam = headerHeight;
			}else{
				heightParam = headerHeight*2;
			}
			if(jQuery(this).scrollTop() >= heightParam){
				sticky.slideDown(100);
				stickyLogoResize();
			}
			if(jQuery(this).scrollTop() < headerHeight ){
				sticky.hide();
				stickyLogoResize();
			}
		});
	}	
	
	/* Selects */
	jQuery(".form-language select, .form-currency select, .store-switcher  select").selectbox();
	
	
/* Messages button */
	if(jQuery('ul.messages').length){
		jQuery('ul.messages > li').each(function(){
			switch (jQuery(this).attr('class')){
				case 'success-msg':
				messageIcon = '<i class="fa fa-check" />';
				break;
				case 'error-msg':
				messageIcon = '<i class="fa fa-times" />';
				break;
				case 'note-msg':
				messageIcon = '<i class="fa fa-exclamation" />';
				break;
				case 'notice-msg':
				messageIcon = '<i class="fa fa-exclamation" />';
				break;
			}
			jQuery(this).prepend('<div class="messages-close-btn" />', messageIcon);
			jQuery('ul.messages .messages-close-btn').on('click', function(){
				jQuery('ul.messages').remove();
			});
		});
	}

	if(jQuery('span.hover-image').length){
		jQuery('span.hover-image').parent().addClass('hover-exists');
	}
	
	if(jQuery('#footer .form-currency .sbHolder').length) {
		jQuery('#footer .form-language').addClass('select');
	}

	if(jQuery('.map-wrapper').length){
		if(!jQuery('.map-wrapper-bg').parents('.map-wrapper').hasClass('active')){
			jQuery('.map-wrapper-bg button').on('click', function(){
				jQuery('.map-wrapper-bg').animate({
					'opacity' : 0,
					'z-index' : '-1'
				})
				setTimeout(function(){
					jQuery('.map-wrapper-bg').parents('.map-wrapper').addClass('active');
				}, 250);
			});
			jQuery('.map-wrapper .btn-close').on('click', function(){
				jQuery('.map-wrapper-bg').css({
					'opacity' : 1,
					'z-index' : 1
				})
				setTimeout(function(){
					jQuery('.map-wrapper-bg').parents('.map-wrapper').removeClass('active');
				}, 400);
			});
		}
	}
	
	/***  ***/
	if(jQuery('.header .form-search').length){
		jQuery('.header .form-search input').focusin(function(){
			jQuery(this).parent().addClass('focus');
		});
		jQuery('.header .form-search input').focusout(function(){
			jQuery(this).parent().removeClass('focus');
		});
	}
	
	if(jQuery('.text-banner, .banner, .slider-banner, .home-banners-wrapper').length) {
		jQuery('.text-banner:not(.background), .banner, .slider-banner, .home-banners-wrapper .item').each(function(){
			img = jQuery(this).find('img');
			var xPos;
			var yPos;
			var x2Pos;
			var y2Pos;
			img.css('left',0).css('top', 0);
			if(jQuery(this).parents('.parallax-banners-wrapper').length == 0){
				jQuery(this).mouseenter(function(e){
					img = jQuery(this).find('img');
					var xPos = e.pageX;
					var yPos = e.pageY;
					jQuery(this).mousemove(function(b){
						var x2Pos = b.pageX;
						var y2Pos = b.pageY;
						var hPos = x2Pos-xPos;
						var vPos = y2Pos-yPos;
						img.css('left',hPos/20).css('top',vPos/20);
					});
				});
				jQuery(this).mouseleave(function(e){
					img = jQuery(this).find('img');
					img.css('left',0).css('top',0);
				});
			}
		});
	}
	
	if(jQuery('.toolbar-bottom .pager .pages').length == 0){
		jQuery('.toolbar-bottom').addClass('no-border');
	}
	
	/* Run Top Menus */
	$$(".nav").each(function(this_nav){
		new mainNav(this_nav, {"show_delay":"100","hide_delay":"100"});
	});
	
	/* Content to top */
	function imagesOnLoad(images){
		imgCount = images.size(); 
		currentIndex = 0;
		images.load(function(){
		   currentIndex++;
		   if(currentIndex == imgCount){
			contentTop();
		   }
		});
	}
	imagesOnLoad(jQuery('header#header img'));
	
	contentTop();
	
});