var EventSource = function () {
    var GET_EVENT_SOURCE_URL = '/event/index/getEventSourceOptions';
    var GET_EVENT_SOURCE_PAYMENT_URL = '/event/index/getEventSourceOptionsPayment';
    var SAVE_EVENT_SOURCE_URL = '/event/index/saveEventSourceOption';
    var is_loading = false;
    var block_id = '';
    var title_choose_option = '';
    var $block = null;
    var $block_static = null;
    var $title_list = null;
    var $view_more = null;
    var $title = null;
    var $header = null;
    var $block_select = null;
    var $selectbox = null;
    var $product_list = null;
    var $product_slider = null;
    
    var $title_block = null;
    var $title_area = null;
    var $title_level = null;
    var $title_checkout = null;
    
    var $selectbox_area = null;
    var $selectbox_level = null;
    var $selectbox_checkout = null;
    
    var title_choose_option_area = '';
    var title_choose_option_level = '';
    var title_choose_option_checkout = '';
    
    var affId = '';
    var areaId = '';
    var levelId = '';
    
    var tabslider_script = '';
    
    let is_first = true;
    
    var $this = this;
    this.init = function(_block_id, _affId){
	$this.block_id = _block_id;
	$this.affId = _affId;
	
	$this.is_first = true;
	
	$block = $jq('#'+$this.block_id);
	$block_select = $jq('#'+$this.block_id+"_select");
	$title_list = $jq('#'+$this.block_id+"_title_list");
	$view_more = $jq('#'+$this.block_id+"_view_more");
	
	$header = $jq('#'+$this.block_id+"_header");
	$title = $jq('#'+$this.block_id+"_title");
	$selectbox = $jq('#'+$this.block_id+"_selectbox");
	$product_list = $jq('#'+$this.block_id+"_list");
	$product_slider = $jq('#'+$this.block_id+"_slider");
	
	$selectbox.change(function(){
	    $this.saveEventSouceOption($jq(this).val());
	});
	$this.loadEventSource();
    };
    this.initSelectBox = function(_block_id){
	$this.is_first = true;
	
	$block_select = $jq('#'+_block_id+"_select");
	$title_list = $jq('#'+_block_id+"_title_list");
	$selectbox = $jq('#'+_block_id+"_selectbox");
	
	$selectbox.change(function(){
	    $this.saveEventSouceOption($jq(this).val());
	});
	$this.loadEventSourcePayment();
    };
    this.loadEventSource = function(){
	if($this.is_loading){return;}
	$this.is_loading = true;
	$jq.ajax({
	    url: GET_EVENT_SOURCE_URL,
	    method: 'post',
	    data: {affId: $this.affId},
	    dataType : "json",
	    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    if($this.is_first){
			if(data['form_ui']){
			    $this.is_first = false;
			    
			    let form_ui = data['form_ui'];
			    if(form_ui['background_color']){
				$block_select.css('background-color', form_ui['background_color']);
			    }
			    if(form_ui['color']){
				$block_select.css('color', form_ui['color']);
			    }
			    if(form_ui['title_list']){
				$title_list.text(form_ui['title_choose_option']);
			    }
			    if(form_ui['title_choose_option']){
				$this.title_choose_option = form_ui['title_list'];
			    }
			    if(data['seeAllLink']){
				$view_more.attr('href','/'+data['seeAllLink']);
			    }
			    if(data['product_title']){
				$title.text(data['product_title']);
				$header.show();
			    }

			    $block_select.show();
			    $selectbox.select2({
				    placeholder: $this.title_choose_option,
				    allowClear: true
				    });

			    $jq(window).on('resize scroll', function() {
				$selectbox.select2({
					placeholder: $this.title_choose_option,
					allowClear: true
					});
			    });
			}
		    }
		    
		    $this.renderEventSource(data['options']);
		    if(data['products']){
			if(!fhs_account.isEmpty($this.block_id)){
			    $this.renderProducts(data['products']);
			}
		    }
		}
	    },
	    error: function(){
	    }
	});
    };
    this.loadEventSourcePayment = function(){
	if($this.is_loading){return;}
	$this.is_loading = true;
	$jq.ajax({
	    url: GET_EVENT_SOURCE_PAYMENT_URL,
	    method: 'post',
	    dataType : "json",
	    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    if(!data['matched']){return;}
		    
		    if($this.is_first){
			if(data['form_ui'] !== null){
			    $this.is_first = false;
			    
			    let form_ui = data['form_ui'];

			    if(form_ui['title_checkout']){
				$title_list.text(form_ui['title_checkout']);
			    }
			    if(form_ui['title_choose_option']){
				$this.title_choose_option = form_ui['title_list'];
			    }

			    $block_select.show();
			    $selectbox.select2({
				    placeholder: $this.title_choose_option,
				    allowClear: true
				    });
			}
		    }
		    
		    $this.renderEventSource(data['options']);
		}
		$this.is_loading = false;
	    },
	    error: function(){
		$this.is_loading = false;
	    }
	});
    };
    this.renderEventSource = function(data){
	let select_item = '<option value="">'+$this.title_choose_option+'</option>';
	Object.keys(data).forEach(function(key){
	    let item = data[key];
	    let selected = '';
	    if(item['active']){
		selected = 'selected';
	    }
	    select_item += "<option value='"+item['id']+"' "+selected+">"+item['name']+"</option>";
	});
	$selectbox.empty().html(select_item);
    };
    this.renderProducts = function(data){
	let items = '';
	if(data){
	    Object.keys(data).forEach(function(key){
		items += fhs_account.getProduct(data[key]);
	    });
	}
	if(!fhs_account.isEmpty(items)){
	    $product_list.empty().html(items);
	    $block.show();
	    fhs_account.showSlider($this.block_id, false, data.length);
	}else{
	    $product_list.empty();
	    $block.hide();;
	}
    };
    this.saveEventSouceOption = function(option_id){
	if($this.is_loading){return;}
	$this.is_loading = true;
	$jq.ajax({
	    url: SAVE_EVENT_SOURCE_URL,
	    method: 'post',
	    dataType : "json",
	    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {option_id: option_id},
	    success: function (data) {
		if(data['success']){
		    if(!fhs_account.isEmpty($this.block_id)){
			if(!fhs_account.isEmpty(data['product_title'])){
			    $title.text(data['product_title']);
			    $header.show();
			}else{
			    $header.hide();
			}
                        if(data['seeAllLink']){
			    $view_more.attr('href','/'+data['seeAllLink']);
			}
			$this.renderProducts(data['products']);
		    }
		}
		$this.is_loading = false;
	    },
	    error: function(){
		$this.is_loading = false;
	    }
	});
    };
    
    this.init_V2 = function(_block_id, _affId, _areaId, _levelId, _tabslider_script){
	$this.block_id = _block_id;
	$this.affId = _affId;
	$this.areaId = _areaId;
	$this.levelId = _levelId;
	$this.tabslider_script = _tabslider_script;
	
	$this.is_first = true;
	
	$block = $jq('#'+$this.block_id);
        $block_static = $jq('#'+$this.block_id + "_static_block");
	$block_select = $jq('#'+$this.block_id+"_select");
        
        $product_list = $jq('#'+$this.block_id+"_list");
	$product_slider = $jq('#'+$this.block_id+"_slider");
	
        
        $header = $jq('#'+$this.block_id+"_header");
        $title = $jq('#'+$this.block_id+"_title");
        $view_more = $jq('#'+$this.block_id+"_view_more");
        
        $title_block = $jq('#'+$this.block_id+"_title_selection_block");
	$title_area = $jq('#'+$this.block_id+"_title_area");
	$title_level = $jq('#'+$this.block_id+"_title_level");
	$title_checkout = $jq('#'+$this.block_id+"_title_checkout");
	
	$selectbox_area = $jq('#'+_block_id+"_selectbox_area");
	$selectbox_level = $jq('#'+_block_id+"_selectbox_level");
	$selectbox_checkout = $jq('#'+_block_id+"_selectbox_checkout");
	
	$selectbox_area.change(function(){
	    $this.areaId = $jq(this).val();
	    $this.loadEventSource_V2('area');
	});
	$selectbox_level.change(function(){
	    $this.levelId = $jq(this).val();
	    $this.loadEventSource_V2('level');
	});
	$selectbox_checkout.change(function(){
	    $this.saveEventSouceOption_V2($jq(this).val());
	});
	$this.loadEventSource_V2();
    };
    this.loadEventSource_V2 = function(skip_selectbox = ''){
	if($this.is_loading){return;}
	$this.is_loading = true;
	$jq.ajax({
	    url: GET_EVENT_SOURCE_URL,
	    method: 'post',
	    data: {affId: $this.affId, areaId: $this.areaId, levelId: $this.levelId},
	    dataType : "json",
	    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    success: function (data) {
		if(data['success']){
		    if($this.is_first){
			if(data['form_ui']){
			    $this.is_first = false;
			    
			    let form_ui = data['form_ui'];
			    if(form_ui['background_color']){
				$block_select.css('background-color', form_ui['background_color']);
			    }
			    if(form_ui['color']){
				$block_select.css('color', form_ui['color']);
			    }
                            
                            if (form_ui['title_block']){
                                $title_block.text(form_ui['title_block']);
                            }

			    if(form_ui['area_list']){
				$title_area.text(form_ui['area_list']);
			    }
			    if(form_ui['level_list']){
				$title_level.text(form_ui['level_list']);
			    }
			    if(form_ui['title_checkout']){
				$title_checkout.text(form_ui['title_checkout']);
			    }

			    if(form_ui['choose_area']){
				$this.title_choose_option_area = form_ui['choose_area'];
			    }
			    if(form_ui['choose_level']){
				$this.title_choose_option_level = form_ui['choose_level'];
			    }
			    if(form_ui['title_choose_option']){
				$this.title_choose_option_checkout = form_ui['title_choose_option'];
			    }
                            
			    $block_select.show();
			    $selectbox_area.select2({
				    placeholder: $this.title_choose_option_area,
				    allowClear: true
				    });
			    $selectbox_level.select2({
				    placeholder: $this.title_choose_option_level,
				    allowClear: true
				    });
			    $selectbox_checkout.select2({
				    placeholder: $this.title_choose_option_checkout,
				    allowClear: true
				    });
			}
		    }
		    
		    if(data['selection']){
			$this.renderEventSource_V2(data['selection'], skip_selectbox);
		    }
		    
		    if(data['related_data']){
			if(!$this.is_first){
			    data['related_data'] = data['related_data'].replace($this.tabslider_script, '');
			}
			
			$block_static.html(data['related_data']);
			$block_static.show();
		    }else{
			$block_static.hide();
			$block_static.empty();
		    }
                    
                    if (data['product_title']) {
                        $title.text(data['product_title']);
                        $header.show();
                    }
                    
                    if (data['seeAllLink']) {
                        $view_more.attr('href', '/' + data['seeAllLink']);
                    }
                    
                    if (data['products']) {
                        $this.renderProducts(data['products']);
                    }
		}
		$this.is_loading = false;
	    },
	    error: function(){
		$this.is_loading = false;
	    }
	});
    };
    this.saveEventSouceOption_V2 = function(option_id){
	$jq.ajax({
	    url: SAVE_EVENT_SOURCE_URL,
	    method: 'post',
	    dataType : "json",
	    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
	    data: {option_id: option_id, areaId: $this.areaId, levelId: $this.levelId},
	    success: function (data) {
		if(data['success']){
		    if(!fhs_account.isEmpty($this.block_id)){
			if(data['related_data']){
			    $block_static.html(data['related_data']);
			    $block_static.show();
			}else{
			    $block_static.hide();
			    $block_static.empty();
			}
                        
                        if (data['product_title']) {
                            $title.text(data['product_title']);
                            $header.show();
                        }

                        if (data['seeAllLink']) {
                            $view_more.attr('href', '/' + data['seeAllLink']);
                        }
                        if (data['products']) {
                            $this.renderProducts(data['products']);
                        }
                    
		    }
		}
	    },
	    error: function(){
	    }
	});
    };
    this.renderEventSource_V2 = function(data, skip_selectbox = ''){
	if(data['area'] && skip_selectbox != 'area'){
	    let select_item = '<option value="">'+$this.title_choose_option_area+'</option>';
	    Object.keys(data['area']).forEach(function(key){
		let item = data['area'][key];
		let selected = '';
		if(item['active']){
		    selected = 'selected';
		}
	    select_item += "<option value='"+item['id']+"' "+selected+">"+item['name']+"</option>";
	    });
	    $selectbox_area.empty().html(select_item);
	}
	if(data['level'] && skip_selectbox != 'level'){
	    let select_item = '<option value="">'+$this.title_choose_option_level+'</option>';
	    Object.keys(data['level']).forEach(function(key){
		let item = data['level'][key];
		let selected = '';
		if(item['active']){
		    selected = 'selected';
		}
	    select_item += "<option value='"+item['id']+"' "+selected+">"+item['name']+"</option>";
	    });
	    $selectbox_level.empty().html(select_item);
	}
	if(data['source'] && skip_selectbox != 'source'){
	    let select_item = '<option value="">'+$this.title_choose_option_checkout+'</option>';
	    Object.keys(data['source']).forEach(function(key){
		let item = data['source'][key];
		let selected = '';
		if(item['active']){
		    selected = 'selected';
		}
	    select_item += "<option value='"+item['id']+"' "+selected+">"+item['name']+"</option>";
	    });
	    $selectbox_checkout.empty().html(select_item);
	}
    };
}