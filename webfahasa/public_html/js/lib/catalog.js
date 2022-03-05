const CatalogAjax = function () {
    var CATALOG_AJAX_URL = "/fahasa_catalog/product/loadCatalog";
    var PRODUCTS_AJAX_URL = "/fahasa_catalog/product/loadproducts";
    
    var filters = {};
    var category_id = 4;
    var is_series_type = false;
    var display_count = false;
    var page_current = 1;
    var limit = 12;
    var toolbar_limit = 5;
    var order_by = "";
    var page_total = 0;
    var default_show_limit = 8;
    var language = {};
    var is_loading = false;
    var price_from = 1;
    var price_to = 10000000;
    
    var attributes = "";
    $filter_current = $jq("#filter_current");
    $parent_category = $jq("#parent-category");
    $current_category = $jq("#current-category");
    $children_category = $jq("#children-categories");
    $menu_attributes = $jq("#menu-attributes");
    $money_from = $jq('.m-from');
    $money_to = $jq('.m-to');
    $dislay_fillter_current = $jq('#dislay_fillter_current');
    
    var $this = this;
    var arrayPrice = []; // lay data price ben phtml product
    var mobile = 0; // check xem co phai mobile 
    var arrCodeToDelete = new Array(); // chua tat ca code category de xoa all;
    
    $jq(window).on('catalog_price_change', function(){
	$this.price_change();
    });
    
    this.initCatalog = function (_cat_id, _filters, _page_current, _limit, _toolbar_limit, _order_by, _attributes, _language, _is_series_type = false, _display_count = true) {
	$this.category_id = _cat_id;
	if(!$this.isEmpty(_filters)){
	    $this.filters = _filters;
	}else{
	    $this.filters = {};
	}
	$this.page_current = _page_current;
	$this.limit = _limit;
	$this.toolbar_limit = _toolbar_limit;
	$this.order_by = _order_by;
	$this.attributes = _attributes;
	$this.language = _language;
	$this.price_from = $money_from.val();
	$this.price_to = $money_to.val();
	$this.is_series_type = _is_series_type;
	$this.display_count = _display_count;
	try{$jq('#parent-category li a').click(function(e){e.preventDefault();return false;});}catch(ex){}
	try{$jq('#current-category li a').click(function(e){e.preventDefault();return false;});}catch(ex){}
	try{$jq('#children-categories li a').click(function(e){e.preventDefault();return false;});}catch(ex){}
    };
    
    this.loadCatalog = function(is_scroll_to = ''){
	if(is_loading){return;}
	if(is_scroll_to == 'catalog'){
	    if($jq('.breadcrumb').length) {
		try{event.preventDefault();}catch(ex){}
		$jq('html, body').stop().animate({
		    scrollTop: $jq('.breadcrumb').offset().top
		}, 1000);
	    }
	}else if(is_scroll_to == 'product'){
	    if($jq('.filter-ajax').length && $jq('.filter-ajax').is(":visible")) {
		try{event.preventDefault();}catch(ex){}
		$jq('html, body').stop().animate({
		    scrollTop: $jq('.filter-ajax').offset().top
		}, 1000);
	    }
	}
	
	is_loading = true;
	$this.showLoadingAnimation();
	$this.page_current = 1;
	$jq.ajax({
	    url: CATALOG_AJAX_URL,
	    method: 'get',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: { category_id: $this.category_id, filters: $this.filters, currentPage: $this.page_current, limit: $this.limit, order: $this.order_by, series_type: ($this.is_series_type?1:0)},
	    success: function (data) {
		if(data['status']){
		    if(data['message'] == "Success"){
			setTimeout(function(){$this.CatalogDataProcess(data);}, 100);
		    }
		}
		else{
		    $this.hideLoadingAnimation();
		}
		is_loading = false;
	    },
	    error: function(){
		$this.hideLoadingAnimation();
		is_loading = false;
	    }
	});
    };
    this.loadProducts = function(){
	if(is_loading){return;}
	if($jq('.category-products').length) {
	    try{event.preventDefault();}catch(ex){}
	    $jq('html, body').stop().animate({
		scrollTop: $jq('.category-products').offset().top
	    }, 1000);
	}
	is_loading = true;
	$this.showLoadingAnimation();
	$jq.ajax({
	    url: PRODUCTS_AJAX_URL,
	    method: 'get',
            dataType : "json",
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: { category_id: $this.category_id, filters: $this.filters, currentPage: $this.page_current, limit: $this.limit, order: $this.order_by, series_type: ($this.is_series_type?1:0)},
	    success: function (data) {
		if(data['status']){
		    if(data['message'] == "Success"){
			setTimeout(function(){$this.ProductDataProcess(data);}, 100);
		    }else{
			$this.hideLoadingAnimation();
		    }
		}
		else{
		    $this.hideLoadingAnimation();
		}
		is_loading = false;
	    },
	    error: function(){
		$this.hideLoadingAnimation();
		is_loading = false;
	    }
	});
    };
    
    this.CatalogDataProcess = function(data){
	$this.displayCategory(data['category'], data['parent_categories'], data['children_categories']);
	$this.displayPrice(data['price_range']);
        if(data['total_products'] > 0){
             $this.displayAttributes(data['attributes']);
             $jq(".button-open-category-mobile.clone-filter").hide();
        }else{
            if(mobile == 1)
            {
                $jq(".button-open-category-mobile.clone-filter").show();
            }
            $this.displayFilter();
        }
	$this.page_total = Math.ceil(data['total_products']/$this.limit);
	$this.displayProduct(data['product_list']);
	try{$jq('#parent-category li a').click(function(e){e.preventDefault();return false;});}catch(ex){}
	try{$jq('#current-category li a').click(function(e){e.preventDefault();return false;});}catch(ex){}
	try{$jq('#children-categories li a').click(function(e){e.preventDefault();return false;});}catch(ex){}
	$this.hideLoadingAnimation();
        // neu co show thi tat modal
//        if($jq('#modal-filter-mobile').hasClass('in')){
//            $jq('#modal-filter-mobile').modal('hide');
//        }
    }
    this.ProductDataProcess = function(data){
	$this.page_total = Math.ceil(data['total_products']/$this.limit);
	$this.displayProduct(data['product_list']);
	$this.hideLoadingAnimation();
    }
    this.displayCategory = function (current_categories, parent_categories, childrent_categories){
	var parrent_cat = "";
	var current_cat = "";
	var childrent_cat = "";
	var last_parrent_index = 0;
	var current_is_in_parrent = false;
        
        
        if($current_category.hasClass("parent-current-category")){
            $current_category.removeClass("parent-current-category")
        }
        if(parent_categories.length == 1){ // vi chi = 0 thi` no la cha cua tat ca
            if(current_categories['name'] == parent_categories[0]['name']){
                $current_category.addClass("parent-current-category");
                $current_category.removeAttr('style');
            }
        }
	    if(parent_categories[parent_categories.length - 1]['id'] == current_categories['id']){
		// phan loai cap trong current_categories moi con cua no margin left +10 px;
		$current_category.removeAttr('style');
		let num = ((parent_categories.length - 1)*10 )+'px' ;
		$current_category.css("margin-left",num);
             
	    }
	Object.keys(parent_categories).forEach(function(key){
	    if(parent_categories[key].id == current_categories.id){
		current_is_in_parrent = true;
	    }else{
		last_parrent_index = key;
	    }
	});
        // phan loai cap trong parent_categories moi con cua no margin left +10 px;
	var demParent = 0;
        var numberMarLeft = 0;
	Object.keys(parent_categories).forEach(function(key){
	    if(parent_categories[key].id != current_categories.id){
		if(!current_is_in_parrent && (last_parrent_index == key)){
		    return;
		}
                if(demParent > 0){
                    // tu index 1 tro len moi margin-left vi 0 la cha cua tat ca
                    numberMarLeft +=10;
                    parrent_cat += "<li style='margin-left:"+numberMarLeft+"px;'><a href='"+parent_categories[key].url+"' cat_id='"+parent_categories[key].id+"' onclick='catalog_ajax.category_click(this)' title='"+parent_categories[key].name+"'>"+parent_categories[key].name+"</a></li>";
                }else{
                    parrent_cat += "<li><a href='"+parent_categories[key].url+"' cat_id='"+parent_categories[key].id+"' onclick='catalog_ajax.category_click(this)' title='"+parent_categories[key].name+"'>"+parent_categories[key].name+"</a></li>";
                }
	    }
            demParent++;
	});
	
	if(!current_is_in_parrent){
	    current_cat = "<a href='"+parent_categories[last_parrent_index].url+"' cat_id='"+parent_categories[last_parrent_index].id+"' onclick='catalog_ajax.category_click(this)' title='"+parent_categories[last_parrent_index].name+"'>"+parent_categories[last_parrent_index].name+"</a>";
	}else{
	    current_cat = "<span class='m-selected-filter-item'>"+current_categories.name+"</span>";
	}
	
	var is_expand_category = false;
	var current_cat_index = 0;
	Object.keys(childrent_categories).forEach(function(key){
	    if(childrent_categories[key].count >0){
		let item_count = '';
		if($this.display_count){
		    item_count = "("+childrent_categories[key].count+")";
		}
		if(childrent_categories[key].id != current_categories.id){
		    current_cat_index++;
		    childrent_cat += "<li><a href='"+childrent_categories[key].url+"' cat_id='"+childrent_categories[key].id+"' onclick='catalog_ajax.category_click(this)' title='"+childrent_categories[key].name+"'>"+childrent_categories[key].name+"</a> "+item_count+"</li>";
		}else{
		    if(!is_expand_category && (current_cat_index >= default_show_limit)){is_expand_category = true;}
		    current_cat_index++;
    		    childrent_cat += "<li><span class='m-selected-filter-item'>"+childrent_categories[key].name+"</span> "+item_count;
		}
	    }
	});
	let numLeft = (numberMarLeft+10) + 'px';
	$this.displaySEO(current_categories);
	$this.displayBreadcrumb(current_categories, parent_categories);
	$parent_category.html(parrent_cat);
	$current_category.html(current_cat);
	$children_category.html(childrent_cat);
        $children_category.css('margin-left',numLeft);
	(function ($) {$(document).trigger('m-show-more-reset', ['left_category',default_show_limit,false,200,0]);})(jQuery);
	(function ($) {$(document).trigger('m-show-more-reset', ['left_category',default_show_limit,is_expand_category,200,0]);})(jQuery);
    };
    this.displayPrice = function (price_range){
	if($this._is_series_type){return;}
	
	if((price_range['min'] != price_range['price_range']['min']) || (price_range['max'] != price_range['price_range']['max'])){
	    $this.filters['price'] = price_range['min'] + "," + price_range['max'];
	}else{
	    if($this.filters['price']){
		delete $this.filters['price'];
	    }
	}
       // display lai price
        for (var prices of arrayPrice) {
            if ((prices['min'] == price_range['min']) && (price_range['max'] == prices['max'])) {
                let key = arrayPrice.indexOf(prices);
                let name = $jq('#price-m-' + key);
                name.removeClass('m-checkbox-unchecked').addClass("m-checkbox-checked");
            }else{
                let key = arrayPrice.indexOf(prices);
                let name = $jq('#price-m-' + key);
                if(!name.hasClass('checkbox-unchecked'))
                {
                    name.addClass("m-checkbox-unchecked");
                }
                    name.removeClass("m-checkbox-checked");
            }
        }
	$this.price_from = price_range['min'];
	$this.price_to = price_range['max'];
//	$money_from.val(price_range['min']);
//	$money_to.val(price_range['max']);
    };
    this.displayAttributes = function (attributes_data){
	var attributes = "";
	var price_range = $this.filters['price'];
	$this.filters = {};
	if(typeof price_range !== "undefined"){
	    $this.filters['price'] = price_range;
	}
	Object.keys(attributes_data).forEach(function(key){
	    var attribute = attributes_data[key];
	    if(!$this.isEmpty(attribute['options'])){
		attributes += "<dt class='odd' data-id='m_left_"+attribute['code']+"_filter'>"+attribute['label']+"</dt><dd class='odd'><ol class='m-filter-css-checkboxes '>";
		
		Object.keys(attribute['options']).forEach(function(option_key){
		    var option = attribute['options'][option_key];
		    var option_select = "m-checkbox-unchecked";
		    let item_count = '';
		    if($this.display_count){
			item_count = "("+option['count']+")";
		    }
		    if(option['selected']){
			option_select = "m-checkbox-checked";
			if(!$this.filters[attribute['code']]){
			    $this.filters[attribute['code']] = option['id'];
			}else{
			    $this.filters[attribute['code']] += "_"+option['id'];
			}
		    }
		    
		    attributes += "<li><a class='"+option_select+"' id='m-left-attr-"+attribute['code'] + option['id'] + "' onclick=\"catalog_ajax.attribute_click('"+attribute['code']+"',"+option['id']+")\"" 
				+" title='"+option['label']+"'>"+option['label']+"</a> "+item_count+"</li>";
		});
		
		attributes += "</ol><div class='m-more-less' id='m-more-less-left_"+attribute['code']+"'>"
				+"<a class='m-show-less-action' style='display: none;'>"+$this.language.showless+"</a>"
				+"<a class='m-show-more-action' style='display: inline;'>"+$this.language.showmore+"</a>"
			    +"</div></dd>";
	    }
	});
	
	$menu_attributes.html(attributes);
	
	Object.keys(attributes_data).forEach(function(key){
	    var attribute = attributes_data[key];
	    if(!$this.isEmpty(attribute['options'])){
		(function ($) {$(document).trigger('m-show-more-reset', ['left_'+attribute['code'],default_show_limit,false,200,0]);})(jQuery);
	    }
	});
	$this.attributes = attributes_data;
	$this.displayFilter();
    };
    this.displayFilter = function (){
        arrCodeToDelete = [];
	var filter_str = "";
	var demFilters = 0;
	Object.keys($this.filters).forEach(function(key){
	    if(key == "price"){
		var price = $this.filters[key].split(',');
                var high = $this.formatCurrency(price[1])
                if(high == '9,999,999'){
                     high = $this.language['more-price'];
                }
		filter_str += "<li><a onclick=\"catalog_ajax.removeFilter_click('price','')\" title='"+$this.language['removethisitem']+"' class='btn-remove'>"+$this.language['removethisitem']+"</a><span class='label'>"+$this.language['price']+":  "+$this.formatCurrency(price[0])+" - "+high+"</span></li>";
                demFilters++;
                var objCodeToDelete = {
                    "code": key,
                    "attr": ''
                }
                arrCodeToDelete.push(objCodeToDelete);
	    }else{
		Object.keys($this.attributes).forEach(function(attr_key){
		    var attribute = $this.attributes[attr_key];
		    if(attribute['code'] == key){
			var filter_attr_ids = $this.filters[key].toString().split("_");
			Object.keys(filter_attr_ids).forEach(function(filter_attr_id_index){
			    Object.keys(attribute['options']).forEach(function(option_key){
				var option = attribute['options'][option_key];
				if(option['id'] == filter_attr_ids[filter_attr_id_index]){
				    filter_str += "<li><a onclick=\"catalog_ajax.removeFilter_click('"+key+"','"+filter_attr_ids[filter_attr_id_index]+"')\" title='"+$this.language['removethisitem']+"' class='btn-remove'>"+$this.language['removethisitem']+"</a><span class='label'>"+attribute['label']+": "+option['label']+"</span></li>";
                                    demFilters++;
                                    var objCodeToDelete = {
                                        "code" : key,
                                        "attr" : filter_attr_ids[filter_attr_id_index]
                                    }
                                    arrCodeToDelete.push(objCodeToDelete);
                                }
			    });
			});
		    }
		});
	    }
	});
            if (demFilters > 1) {
            filter_str += "<li class='delete-all-category' onclick=\"catalog_ajax.removeAllFilter_click()\"><a><div>"+$this.language['delete_all']+"</div></a></li>";
        }
            if (demFilters == 1) {
                $jq('.filter-text-header').show();
        }
            if (demFilters == 0) {
                $jq('.filter-text-header').hide();

        }
        // display filer cua 2 phien ban PC va Mobile
        if($jq('.filter-ajax ol').is('#dislay_fillter_current') && mobile == 0)
        {
            $jq('ol#dislay_fillter_current').html(filter_str);
        }else{
            // lay desgin cua PC len Mobile
            //$filter_current.html(filter_str);
            $jq('ol#dislay_fillter_current').html(filter_str);
        }
	$this.setQueryString();
    };
    this.displayProduct = function(products_data){
	var product_str = "";
	var pages_str =  "";
        if (mobile == 1) {
            Object.keys(products_data).forEach(function (key) {
                product_str += $this.getProductMobile(products_data[key]);
            });
        } else {
            Object.keys(products_data).forEach(function (key) {
                product_str += $this.getProduct(products_data[key]);
            });
        }
	
	if(product_str){
	    $jq('.note-msg').fadeOut();
	    $jq('.category-products').show();
	    $jq('#products_grid').html(product_str);
	    pages_str = $this.calPages()
	}else{
	    $jq('.note-msg').fadeIn();
	    $jq('.category-products').fadeOut();
	}
	
	$jq('.pages').html("<ol>"+pages_str+"</ol>");

	
    }
    this.displayBreadcrumb = function(current_categories, parent_categories){
	var breadcrumb_str = "<li class='home'><a href='/' title='"+$this.language['homepage_title']+"'>"+$this.language['homepage']+"</a><span>/</span></li>";
	
	Object.keys(parent_categories).forEach(function(key){
	    if(parent_categories[key].id != current_categories.id){
		breadcrumb_str += "<li class='category"+parent_categories[key].id+"'><a href='"+parent_categories[key].url+"' title=''>"+parent_categories[key].name+"</a><span>/ </span></li>";
	    }
	});
	if(typeof current_categories !== "undefined"){
	    breadcrumb_str += "<li class='category"+current_categories.id+"'><strong>"+current_categories.name+"</strong><span>/ </span></li>";
	}
	$jq('.breadcrumb').html(breadcrumb_str);
    }
    this.displaySEO = function(current_categories){
	if(current_categories){
	    document.title = current_categories['title'];
	    $jq("meta[name='description']").attr("content", current_categories['description']);
	    $jq("meta[name='keywords']").attr("content", current_categories['keywords']);
	}
    }
    
    this.category_click = function (obj){
	if($this.category_id != $jq(obj).attr('cat_id')){
	    $this.category_id = $jq(obj).attr('cat_id');
	    window.history.pushState("object or string", "Title", $jq(obj).attr('href'));
	    $this.setQueryString();
	    $this.loadCatalog('catalog');
	}
	return false;
    };
    this.attribute_click = function (code, attr_id){
	var ck_attr = $jq('#m-left-attr-'+code+attr_id);
	if(!$this.filters[code]){
	    $this.filters[code] = attr_id;
	    ck_attr.removeClass('m-checkbox-unchecked').addClass("m-checkbox-checked");
	}else{
	    var attr_ids = $this.changeAttributeId($this.filters[code], attr_id);
	    if(attr_ids.isRemove){
		ck_attr.removeClass('m-checkbox-checked').addClass("m-checkbox-unchecked");
	    }else{
		ck_attr.removeClass('m-checkbox-unchecked').addClass("m-checkbox-checked");
	    }

	    if(attr_ids.result != ""){
		$this.filters[code] = attr_ids.result;
	    }else{
		delete $this.filters[code];
	    }
	}
	$this.displayFilter();
	$this.loadCatalog('product');
    };
    this.pagesize_change = function (pagesize){
	if($this.limit != pagesize){
	    $this.limit = pagesize;
	    $this.page_current = 1;
	    $this.setQueryString();
	    $this.loadProducts();
	}
    };
    this.removeFilter_click = function (code, attr_id){
	if(code == 'price'){
	    if($this.filters['price']){
		delete $this.filters['price'];
	    }
	}
	else{
	    var ck_attr = $jq('#m-left-attr-'+code+attr_id);
	    var attr_ids = $this.changeAttributeId($this.filters[code], attr_id);
	    if(attr_ids.isRemove){
		ck_attr.removeClass('m-checkbox-checked').addClass("m-checkbox-unchecked");
	    }else{
		ck_attr.removeClass('m-checkbox-unchecked').addClass("m-checkbox-checked");
	    }

	    if(attr_ids.result != ""){
		$this.filters[code] = attr_ids.result;
	    }else{
		delete $this.filters[code];
	    }
	}
	$this.displayFilter();
	$this.loadCatalog('product');
    };
    this.removeAllFilter_click = function (){
        // nut xoa tat ca filter 
        if (arrCodeToDelete.length > 0) {
            $jq.each(arrCodeToDelete, function (index, value) {
                if (value['code'] == 'price') {
                    if ($this.filters['price']) {
                        delete $this.filters['price'];
                    }
                } else {
                    var ck_attr = $jq('#m-left-attr-' + value['code'] + value['attr']);
                    var attr_ids = $this.changeAttributeId($this.filters[value['code']], value['attr']);
                    if (attr_ids.isRemove) {
                        ck_attr.removeClass('m-checkbox-checked').addClass("m-checkbox-unchecked");
                    } else {
                        ck_attr.removeClass('m-checkbox-unchecked').addClass("m-checkbox-checked");
                    }

                    if (attr_ids.result != "") {
                        $this.filters[value['code']] = attr_ids.result;
                    } else {
                        delete $this.filters[value['code']];
                    }
                }
            });
        }

        $this.displayFilter();
        $this.loadCatalog('product');
    };
    this.sort_change = function (sort_val){
	if($this.order_by != sort_val){
	    $this.order_by = sort_val;
	    $this.page_current = 1;
	    $this.setQueryString();
	    $this.loadProducts();
	}
    };
    this.Page_change = function (page_no){
	if(page_no == 'previous'){
	    --$this.page_current;
	}else if(page_no == 'next'){
	    ++$this.page_current;
	}else{
	    $this.page_current = page_no;
	}
	$this.setQueryString();
	$this.loadProducts();
    };
    this.price_change = function (price_min = null,price_max = null){
        var i = 0;
        var min = price_min;
        var max = price_max.toString();
	if($this.price_from == min && $this.price_to == max){
	    delete $this.filters['price'];
            $this.displayFilter();
	    $this.loadCatalog('product');
	}
	if(min.match(/\D/) == null && max.match(/\D/) == null){
	    var price_range = min + "," + max;
	    if(!$this.filters['price']){
		if($this.filters['price'] == price_range){
		    return;
		}
	    }
	    $this.price_from = min;
	    $this.price_to = max;
	    $this.filters['price'] = price_range;
	    $this.displayFilter();
	    $this.loadCatalog('product');
	}
        
    };

    this.getProduct = function (item){
	let discount = '';
	let price = '';
	let comingsoon = '';
	let episode = '';
	let subscribes = "<div class='fhs-series-subscribes'>0 lượt theo dõi</div>";
	let body = '';
	if(item['type_id'] != 'series'){
	    if(item['discount_label_html']){
		discount = item['discount_label_html'];
	    }
	    if(item['product_price'] != item['product_finalprice']){
		price = "<p class='old-price bg-white'><span class='price-label'>Giá bìa: </span><span id='old-price-175744' class='price'>"+item['product_price']+"&nbsp;đ</span></p>";
	    }
	    if(item['soon_release'] == 1){
		comingsoon = "<div><div class='hethang product-hh'><span><span>"+$this.language['comingsoon']+"</span></span></div><div>"
	    }
	    if(item['episode']){
		episode = "<div class='episode-label'>"+item['episode']+"</div>";
	    }
	    body = "<h2 class='product-name-no-ellipsis p-name-list'><a href='"+item['product_url']+"' title='"+item['product_name']+"'>"+(item['product_name'])+"</a></h2>                                    "
			+"<div class='price-label'>"
			    +"<div class='price-box'>"
				+"<div class='price-box'>"
				    +"<span id='product-price-"+item['product_id']+"' class=''>"
					+"<span class='price'>"
					    +"<div class='price-box'>"
						+"<p class='special-price'>"
						    +"<span class='price'>"+item['product_finalprice']+"&nbsp;đ</span>"
						+"</p>"
						+price
					    +"</div>"
					+"</span>"                              
				    +"</span>"                                          
				+"</div>"
			    +"</div>"
			    +episode
			+"</div>"
		    +"<div class='rating-container' style=''>"
			+item['rating_html']
			+comingsoon
		    +"</div>";
	}else{
	    if(item['episode']){
		episode = "<div class='fhs-series-episode-label'>"+item['episode']+"</div>"
	    }
	    if(item['subscribes']){
		subscribes = "<div class='fhs-series-subscribes'>"+item['subscribes']+" lượt theo dõi</div>";
	    }
	    body = "<h2 class='product-name-no-ellipsis p-name-list fhs-series'><a href='"+item['product_url']+"' title='"+item['product_name']+"'><span class='fhs-series-label'><i></i></span>"+(item['product_name'])+"</a></h2>                                    "
		    +episode
		    +subscribes;
	}
	
	return "<li>"
		+"<div class='item-inner'>"
                +discount
		   + "<div class='ma-box-content'>"
			+"<div class='products clearfix'>"
			    +"<div class='product images-container'>"
				+"<a href='"+item['product_url']+"' class='product-image'>"
				    +"<span class='product-image'>"
					+"<img class='lazyload' src='"+loading_icon_url+"' data-src='"+item['image_src']+"' alt=''>"
				    +"</span>"							   
				+"</a>"
			    +"</div>"
			    
			+"</div>"
			+body
		    +"</div>"
		+"</div>"
	    +"</li>";
    };
    this.getProductMobile = function(item){
	let discount = '';
	let price = '';
        let comingsoon = '';
        let rating = ''
	let episode = '';
	let subscribes = "<div class='fhs-series-subscribes'>0 lượt theo dõi</div>";
	let body = '';
	if(item['type_id'] != 'series'){
	    if(item['discount_label_html']){
		discount = item['discount_label_html'];
	    }
	    if(item['product_price'] != item['product_finalprice']){
		price = "<div class='old-price'><span class='price'>"+item['product_price']+"&nbsp;đ</span></div>";
	    }
	    if(item['soon_release'] == 1){
		comingsoon = "<div><div class='hethang product-hh'><span><span>"+$this.language['comingsoon']+"</span></span></div><div>"
	    }
	    if(item['episode']){
		episode = "<div class='episode-label'>"+item['episode']+"</div>";
	    }
	    body = "<h2 class='product-name-no-ellipsis p-name-list'><a href='"+item['product_url']+"' title='"+item['product_name']+"'>"+(item['product_name'])+"</a></h2>                                    "
		    +"<div class='price-label'>"
			+"<div class='special-price'>"
			    +"<span class='price'>"+item['product_finalprice']+"&nbsp;đ</span>"
			+"</div>"
			+price
			+episode
		    +"</div>"
		    +"<div class='rating-container' style=''>"
			+item['rating_html']
			+comingsoon
		    +"</div>";
	}else{
	    if(item['episode']){
		episode = "<div class='fhs-series-episode-label'>"+item['episode']+"</div>"
	    }
	    if(item['subscribes']){
		subscribes = "<div class='fhs-series-subscribes'>"+item['subscribes']+" lượt theo dõi</div>";
	    }
	    body = "<h2 class='product-name-no-ellipsis p-name-list fhs-series'><a href='"+item['product_url']+"' title='"+item['product_name']+"'><span class='fhs-series-label'><i></i></span>"+(item['product_name'])+"</a></h2>                                    "
		    +episode
		    +subscribes;
	}
	
	return "<li>"
		+"<div class='item-inner'>"
                +discount
		   + "<div class='ma-box-content'>"
			+"<div class='products clearfix'>"
			    +"<div class='product images-container'>"
				+"<a href='"+item['product_url']+"' class='product-image'>"
				    +"<span class='product-image'>"
					+"<img class='lazyload' src='"+loading_icon_url+"' data-src='"+item['image_src']+"' alt=''>"
				    +"</span>"							   
				+"</a>"
			    +"</div>"
			    
			+"</div>"
			+body
		    +"</div>"
		+"</div>"
	    +"</li>";
    
    };
    this.calPages = function (){
	var pages_str = "";
	if($this.page_total <= 1){
	    return pages_str;
	}
	var start = 0;
	var stop = 5;
	if($this.page_current > 1){
	    //pages_str = "<li title='Previous'><a onclick=\"catalog_ajax.Page_change('previous')\"><i class='fa fa-chevron-left'></i></a></li>";
            pages_str = "<li title='Previous'><a  onclick=\"catalog_ajax.Page_change('previous')\"><div class='icon-turn-left'>&nbsp;</div></a></li>";
            if (($this.page_current + 1) >= $this.toolbar_limit) {
                pages_str += "<li><a onclick='catalog_ajax.Page_change(1)'>1</a></li>";
                if (($this.page_current + 1) != $this.toolbar_limit) {
                    pages_str += "<li class='disable-li'><span>...</span</li>";
                }
            }
	}

	if($this.page_current < ($this.toolbar_limit/2)){
	    start = 0;
	}
	else if(($this.page_total - $this.page_current) < ($this.toolbar_limit/2)){
	    start = $this.page_total - $this.toolbar_limit;
	}
	else{
	    start = $this.page_current - Math.ceil($this.toolbar_limit/2);
	}
	
	if(start < 0){start = 0;}
	
	stop = (start+$this.toolbar_limit);

	for(var i = start; i < stop; i++){
	    if(i < $this.page_total){
		if($this.page_current == (i+1)){
		    pages_str += "<li class='current'><a>"+(i+1)+"</a></li>";
		}
		else{
                    if ($this.page_total != (i + 1)) {
                        pages_str += "<li><a onclick='catalog_ajax.Page_change(" + (i + 1) + ")'>" + (i + 1) + "</a></li>";
                    }
		}
	    }
	}

	if($this.page_current < $this.page_total){
            if(( $this.page_total - $this.page_current + 1) >= $this.toolbar_limit ){
               pages_str += "<li class='disable-li'><span>...</span</li>";
            }
            pages_str += "<li><a onclick='catalog_ajax.Page_change("+ $this.page_total +")'>"+ $this.page_total +"</a></li>";
	    //pages_str += "<li title='Next'><a onclick=\"catalog_ajax.Page_change('next')\"><i class='fa fa-chevron-right'></i></a></li>";
            pages_str += "<li title='Next'><a onclick=\"catalog_ajax.Page_change('next')\"><div class='icon-turn-right'>&nbsp;</div></a></li>";
	}
	return pages_str;
    };

    this.hideLoadingAnimation = function () {
	$jq('#m-wait').fadeOut();
	$jq('.m-overlay').fadeOut();
    };
    this.showLoadingAnimation = function (){
	$jq('#m-wait').fadeIn();
	$jq('.m-overlay').fadeIn();
    };
    this.formatCurrency = function (num) {
	return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')
      };
    this.changeAttributeId = function (attr, attr_id){
	var result = {"isRemove":false, "result":""};
	var filter_attr_ids = attr.toString().split("_");
	Object.keys(filter_attr_ids).forEach(function(key){
	    if(filter_attr_ids[key] == attr_id){
		result.isRemove = true;
	    }else{
		if(result.result == ""){
		    result.result = filter_attr_ids[key];
		}else{
		    result.result += "_"+filter_attr_ids[key];
		}
	    }
	});
	if(!result.isRemove){
	    if(result.result == ""){
		result.result = attr_id;
	    }else{
		result.result += "_"+attr_id;
	    }
	}
	return result;
    };
    this.isEmpty = function (obj){
	for(var key in obj) {
	    if(obj.hasOwnProperty(key))
		return false;
	}
	return true;
    };
    this.getArrayPrice = function (array){
        arrayPrice = array;
    };
    this.getDataFirstFilter = function (data,checkMobile,price,objCodeToDeletePhtml = null){
        mobile = checkMobile;
        if(data.length > 0){
            if (checkMobile == 1) {
                //$filter_current.html(data[0]);
                // lay desgin cua PC len Mobile
                $jq('ol#dislay_fillter_current').html(data[0]);
            } else {
                $jq('ol#dislay_fillter_current').html(data[0]);
            }
        }
        if (objCodeToDeletePhtml != null) {
            if (objCodeToDeletePhtml.length > 0) {
                arrCodeToDelete = objCodeToDeletePhtml;
            }
        }
        if (price) {
            let priceMinMax = price.split(",");
            $this.price_from = priceMinMax[0];
            $this.price_to = priceMinMax[1];
        }
    };
    this.enableSelectBoxes = function (button){
        // show option selected
        $jq('span.selectOption-' + button).each(function (index, value) {
            var selected = $jq(this).attr('selected');
            if (selected) {
                $jq('span.selected-' + button).html($jq(value).text());
            }
        });

        $jq('div.selectBox-' + button).each(function () {
            $jq(this).children('span.selected,span.selectArrow-' + button).click(function () {
                // remove all class if it still has
                $jq('span.selectOption-' + button).each(function (index, value) {
                    $jq(value).removeClass('hightlight');
                    //hightlight when selected 
                    if ($jq('span.selected-' + button).text() == $jq(value).text()) {
                        $jq(value).addClass("hightlight");
                    }
                });
                // show/hide option fields
                if ($jq(this).parent().children('div.selectOptions-' + button).css('display') == 'none') {
                    $jq(this).parent().children('div.selectOptions-' + button).css('display', 'block');
                } else
                {
                    $jq(this).parent().children('div.selectOptions-' + button).css('display', 'none');
                }
            });

            // action change sort
            $jq(this).find('span.selectOption-' + button).click(function () {
                $jq(this).parent().css('display', 'none');
                $jq(this).closest('div.selectBox-' + button).attr('value', $jq(this).attr('value'));
                $jq(this).parent().siblings('span.selected-' + button).html($jq(this).html());
                if (button == 'order') {
                    catalog_ajax.sort_change($jq(this).attr('value'));
                }
                if (button == 'limit') {
                    catalog_ajax.pagesize_change($jq(this).attr('value'));
                }

            });
            // tat option fields khi re chuot ra ngoai fields 
            $jq("div.selectOptions-" + button).mouseleave(function () {
                $jq(this).parent().children('div.selectOptions-' + button).css('display', 'none');
            });

        });
    };
    this.displayModalFilter = function(){
        // hien thi modal filter cho mobile
        $jq('.mb-mana-catalog-leftnav').appendTo('.modal-body');
        $jq('#modal-filter-mobile').modal('show');
    };
    this.setQueryString = function(){
	$this.clearAllQueryStringPram();
	$this.updateQueryStringParam('order',$this.order_by);
	$this.updateQueryStringParam('limit',$this.limit);
	$this.updateQueryStringParam('p',$this.page_current);
	if($this.is_series_type){
	    $this.updateQueryStringParam('series_type',1);
	}
	Object.keys($this.filters).forEach(function(key){
	    $this.updateQueryStringParam(key,$this.filters[key]);
	});
    }
    this.clearAllQueryStringPram = function (){
	var uri = window.location.toString();
	if (uri.indexOf("?") > 0) {
	    var clean_uri = uri.substring(0, uri.indexOf("?"));
	    window.history.replaceState({}, document.title, clean_uri);
	}
    };
    this.updateQueryStringParam = function (key, value) {
	var baseUrl = [location.protocol, '//', location.host, location.pathname].join(''),
	    urlQueryString = document.location.search,
	    newParam = key + '=' + value,
	    params = '?' + newParam;

	// If the "search" string exists, then build params from it
	if (urlQueryString) {
	    var updateRegex = new RegExp('([\?&])' + key + '[^&]*');
	    var removeRegex = new RegExp('([\?&])' + key + '=[^&;]+[&;]?');

	    if( typeof value == 'undefined' || value == null || value == '' ) { // Remove param if value is empty
		params = urlQueryString.replace(removeRegex, "$1");
		params = params.replace( /[&;]$/, "" );

	    } else if (urlQueryString.match(updateRegex) !== null) { // If param exists already, update it
		params = urlQueryString.replace(updateRegex, "$1" + newParam);

	    } else { // Otherwise, add it to end of query string
		params = urlQueryString + '&' + newParam;
	    }
	}

	// no parameter was set so we don't need the question mark
	params = params == '?' ? '' : params;

	window.history.replaceState({}, "", baseUrl + params);
    };
    
//    this.getParamLayoutProduct = function(_order){
//        orderParam = _order;
//    }
}
