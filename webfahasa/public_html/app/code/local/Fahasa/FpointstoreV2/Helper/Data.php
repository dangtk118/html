<?php

class Fahasa_FpointstoreV2_Helper_Data_Error {

    const DISABLED_FPOINTSTORE = "disabled_fpointstore";
    const NO_ACTIVE_FPOINTSTORE = "no_active_fpointstore";
    const NO_ACTIVE_PERIOD = "no_active_period";
    const NO_CONNECTION = "no_connection";
    const NO_PERIODS = "no_periods";
    const NO_PRODUCTS = "no_gifts";

}

class Fahasa_FpointstoreV2_Helper_Data extends Mage_Core_Helper_Abstract {

    const FPOINTSTORE_KEY_NAME = "fpointstore";
    const PERIOD_KEY_NAME = "fpointstore_period";
    const GIFT_KEY_NAME = "gift_entity";
    const SEPERATOR = ":";
    const FHS_FPOINTSTORE = "fhs_fpointstore";
    const FHS_FPOINTSTORE_PERIOD = "fhs_fpointstore_period";
    const FHS_FPOINTSTORE_PRODUCTS = "fhs_fpointstore_gift";
    const FHS_FPOINTSTORE_PRODUCT_CODE = "fhs_fpointstore_gift_code";
    
    public function getComboList($customer_id, $vip_id, $order_times, $combo_id = 0){
	$result = [];
	try {
	    $result = $this->getCombos($customer_id, $vip_id, $order_times, $combo_id, true);
	    if(!$result){
		if(!$result){
		    $nextOrder = $this->getVipNextOrder($vip_id, $order_times);
		    if($nextOrder){
			$result = $this->getCombos($customer_id, $vip_id, $nextOrder['order_times'], $combo_id, false);
		    }
		}
	    }
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] getComboList: vip_id=". $vip_id. ", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
	return $result;
    }
    
    public function getCombos($customer_id, $vip_id, $order_times, $combo_id = 0, $is_combo = false){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$combo_id = $product_helper->cleanBug($combo_id);
	
	$result = [];
	try {
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    if(!$combo_id){
		$binds = array('customer_id' => $customer_id, 'vip_id' => $vip_id, 'order_times' => $order_times);
		    $sql = "select r.id, r.name, r.fpoint, r.image, r.image_banner, r.banner_url, r.description, r.expire_date, ".($is_combo?'true':'false')." as 'is_combo', IF(excl.id IS NULL,0,1) as 'is_over' 
			    from(
				select c.id, c.name, c.fpoint, c.image, c.image_banner, c.banner_url, c.description, c.expire_date, c.sort_order
				from fhs_fpointstore_combo c 
				join fhs_fpointstore_combo_group cg on cg.id = c.combo_group_id and cg.show_by_ordertime_start <= :order_times and cg.show_by_ordertime_end >= :order_times
				left join fhs_fpointstore_combo_log cl on cl.customer_id = :customer_id and cl.vip_id = :vip_id and cl.combo_id = c.id 
				where c.vip_id = :vip_id and cg.bought_limit > ifnull(cl.combo_bought_times,0) 
				and (c.expire_date - INTERVAL c.expire_before_day DAY) >= now() 
			    ) r
			    left join 
			    (
				select c.id
				from fhs_fpointstore_combo c
				join fhs_fpointstore_combo_group cg on cg.id = c.combo_group_id and cg.show_by_ordertime_start <= :order_times and cg.show_by_ordertime_end >= :order_times 
				left join fhs_fpointstore_combo_log cl on cl.customer_id = :customer_id and cl.vip_id = :vip_id and cl.combo_id = c.id 
				join fhs_fpointstore_combo_item ci on c.id = ci.combo_id 
				join fhs_fpointstore_gift g on g.id = ci.gift_id 
				left join fhs_fpointstore_gift_code gc on gc.gift_id = g.id and gc.period_id = 0 and gc.is_sent = 0 and (gc.expire_date - INTERVAL gc.expire_before_day DAY) > now() 
				where c.vip_id = :vip_id and gc.gift_id is null and cg.bought_limit > ifnull(cl.combo_bought_times,0) 
				and (c.expire_date - INTERVAL c.expire_before_day DAY) >= now() 
				group by c.id 
			    ) excl on r.id = excl.id
			    order by r.sort_order desc;";
	    }else{
		$binds = array('customer_id' => $customer_id, 'vip_id' => $vip_id, 'order_times' => $order_times , 'combo_id' => $combo_id);
		$sql = "select r.id, r.name, r.fpoint, r.image, r.image_banner, r.banner_url, r.description, r.expire_date, ".($is_combo?'true':'false')." as 'is_combo', IF(excl.id IS NULL,0,1) as 'is_over' 
			from(
			    select c.id, c.name, c.fpoint, c.image, c.image_banner, c.banner_url, c.description, c.expire_date
			    from fhs_fpointstore_combo c 
			    join fhs_fpointstore_combo_group cg on cg.id = c.combo_group_id and cg.show_by_ordertime_start <= :order_times and cg.show_by_ordertime_end >= :order_times
			    left join fhs_fpointstore_combo_log cl on cl.customer_id = :customer_id and cl.vip_id = :vip_id and cl.combo_id = c.id 
			    where c.vip_id = :vip_id and cg.bought_limit > ifnull(cl.combo_bought_times,0) and c.id = :combo_id 
			    and (c.expire_date - INTERVAL c.expire_before_day DAY) >= now() 
			) r
			left join 
			(
			    select c.id
			    from fhs_fpointstore_combo c
			    join fhs_fpointstore_combo_group cg on cg.id = c.combo_group_id and cg.show_by_ordertime_start <= :order_times and cg.show_by_ordertime_end >= :order_times 
			    left join fhs_fpointstore_combo_log cl on cl.customer_id = :customer_id and cl.vip_id = :vip_id and cl.combo_id = c.id 
			    join fhs_fpointstore_combo_item ci on c.id = ci.combo_id 
			    join fhs_fpointstore_gift g on g.id = ci.gift_id 
			    left join fhs_fpointstore_gift_code gc on gc.gift_id = g.id and gc.period_id = 0 and gc.is_sent = 0 and (gc.expire_date - INTERVAL gc.expire_before_day DAY) > now() 
			    where c.vip_id = :vip_id and gc.gift_id is null and c.id = :combo_id and cg.bought_limit > ifnull(cl.combo_bought_times,0) 
			    and (c.expire_date - INTERVAL c.expire_before_day DAY) >= now() 
			    group by c.id 
			) excl on r.id = excl.id;";
	    }
	    
	    $result = $reader->fetchAll($sql, $binds);
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] getComboNextList: vip_id=". $vip_id.", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
	return $result;
    }
    
    public function getVipNextOrder($vip_id, $order_times){
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	$binds = array('order_times' => $order_times, 'vip_id' => $vip_id);
	$sql = "select order_times, combo_buy_limit from fhs_fpointstore_vip_rule where vip_id = :vip_id and order_times > :order_times order by order_times limit 1;";
        return $reader->fetchRow($sql, $binds);
    }

    public function getCategories(){
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	$sql = "select * from fhs_fpointstore_category where is_show = 1 order by sort_order;";
        return $reader->fetchAll($sql);
    }
    
    public function getGiftList($category_id, $page_current, $limit, $customer_id = 0){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$category_id = $product_helper->cleanBug($category_id);
	$page_current = $product_helper->cleanBug($page_current);
	$limit = $product_helper->cleanBug($limit);
	
	if(!$limit){$limit = 12;};
	if(!$page_current){$page_current = 0;};
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	$category_str = '';
	if($category_id){
	    $category_str = " and category_id = ".$category_id; 
	}
	
	$sql = "select g.id, g.name, g.fpoint, g.image, g.image_logo, g.description, g.partner, g.expire_date, g.order_limit, g.discount, g.category_id, g.sort_order ,g.limit, ifnull(gb.bought, 0) as bought
		from 
		(
			select g.id, g.name, g.fpoint, g.image, g.image_logo, g.description, g.partner, gc.expire_date, g.order_limit, g.discount, g.category_id, g.sort_order ,g.uses_per_customer as 'limit' 
			from fhs_fpointstore_gift g 
			join fhs_fpointstore_gift_code gc on gc.gift_id = g.id and gc.period_id = 0 and gc.is_sent = 0 and (gc.expire_date - INTERVAL gc.expire_before_day DAY) > now() 
			where g.is_show = 1 ".$category_str." 
			group by g.id
		) g
		left join (
			select g.id, count(cl.customer_id) as 'bought'
			from fhs_fpointstore_gift g
			join fhs_fpointstore_gift_code gc on gc.gift_id = g.id
			join fhs_fpointstore_customer_log cl on cl.gift_code_id = gc.id and cl.customer_id = ".$customer_id." 
			group by g.id, cl.customer_id
		) gb on g.id = gb.id
		where g.limit = 0 OR gb.bought is null OR  g.limit > gb.bought
		order by g.sort_order
		limit ".($limit*($page_current-1))." , ".$limit." ;";
	
	return $reader->fetchAll($sql);
    }
    
    public function getVipInfo($customer_id, $company_id, $create = true){
	$result = [];
	if(!$company_id){return $result;}
	try {
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $binds = array('customer_id' => $customer_id ,'company_id' => trim($company_id));
	    $sql = "select v.id, v.company_id, cv.customer_id, ifnull(cv.registered_at, now()) as 'registered_at', ifnull(cv.combo_bought_times, 0) as 'combo_bought_times', ifnull(cv.order_complete_times,0) as 'order_times' , ifnull(cv.combo_buy_limit,1) as 'combo_buy_limit' 
		    from fhs_fpointstore_vip v 
		    left join fhs_fpointstore_customer_vip cv on cv.vip_id = v.id and cv.customer_id = :customer_id 
		    where v.company_id = :company_id and v.is_active = 1;";
	    $result = $reader->fetchRow($sql, $binds);
	    if($result['id']){
		if(!$result['customer_id'] && $create){
		    $this->updateCustomerVIP($customer_id, $company_id, true, $result['id'], 0);
		}
	    }
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] getVipInfo: customer_id=". $customer_id.", company_id=".$company_id . ", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	    
	}
        return $result;
    }
    
    public function updateCustomerVIP($customer_id, $company_id, $is_new = true, $vip_id, $combo_bought_time){
	try{
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    if($is_new){
		$binds = array(
		    'customer_id' => $customer_id,
		    'vip_id' => $vip_id);
		$sql = "INSERT INTO fhs_fpointstore_customer_vip(customer_id, vip_id, registered_at) 
			VALUES (:customer_id, :vip_id, now())
			ON DUPLICATE KEY UPDATE 
			vip_id=:vip_id;";
		
		//donateFpoint
		$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
		$is_exist = $reader->fetchRow("select customer_id from fhs_fpointstore_customer_vip where customer_id = ".$customer_id.";");
		if(!$is_exist){
		    $fpoint = 1000;
		    $noti_title = "Bạn có 1.000 điểm F-Point Để Đối Combo Voucher dành riêng cho bạn";
		    $noti_content = "Chúc mừng bạn được tặng 1.000 F-Point, đồng thời bạn có 1 cơ hội mua Combo Voucher dành riêng cho bạn chỉ với 1.000 F-Point. Nhấn vào thông báo này để Vào ngay F-POINT STORE ( https://www.fahasa.com/fpointstore/ ) đổi Combo Voucher. ";
		    $noti_page_value = "fpointstore";
		    $noti_url = "/fpointstore";
		    $action_purchase = "Donate_fpoint";
		    $description_purchase = "FStore: donate for company VIP";
		    $this->donateFpoint($customer_id, $fpoint, $noti_title, $noti_content, $noti_page_value, $noti_url, $action_purchase, $description_purchase);
		}
	    }else{
		$binds = array(
		    'customer_id' => $customer_id,
		    'vip_id' => $vip_id,
		    'combo_bought_time' =>$combo_bought_time);
		$sql = "update fhs_fpointstore_customer_vip set vip_id = :vip_id, combo_bought_time = :combo_bought_time where customer_id = :customer_id;";
		
	    }
	    $writer->query($sql, $binds);
	    $result = $writer->lastInsertId();
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] updateCustomerVIP: customer_id=". $customer_id.", company_id=".$company_id.", is_new=".$is_new.", vip_id=".$vip_id . ", combo_bought_time=".$combo_bought_time.", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
	return $result;
    }
    
    public function getComboGiftList($customer_id, $combo_id, $vip_id){
	$result = [];
	try {
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $binds = array('customer_id' => $customer_id, 'id' => $combo_id, 'vip_id' => $vip_id);
	    $sql = "select c.id, ci.gift_id, g.name, g.fpoint, g.image, g.image_logo, g.image_banner, g.banner_url, g.description, g.partner, gc.expire_date, g.block_id_detail, b.content, g.order_limit, g.discount, g.category_id 
		    from fhs_fpointstore_combo c
		    join fhs_fpointstore_combo_group cg on cg.id = c.combo_group_id
		    left join fhs_fpointstore_combo_log cl on cl.customer_id = :customer_id and cl.vip_id = :vip_id and cl.combo_id = c.id  
		    join fhs_fpointstore_combo_item ci on c.id = ci.combo_id
		    join fhs_fpointstore_gift g on g.id = ci.gift_id
		    join fhs_fpointstore_gift_code gc on gc.gift_id = g.id and gc.period_id = 0 and gc.is_sent = 0 and (gc.expire_date - INTERVAL gc.expire_before_day DAY) > now()  and cg.bought_limit > ifnull(cl.combo_bought_times,0)
		    left join fhs_cms_block b on b.identifier = g.block_id_detail
		    where c.id = :id
		    group by g.id 
		    order by g.sort_order;";
	    
	    $result = $reader->fetchAll($sql, $binds);
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] getComboGiftList: combo_id=". $combo_id . ", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
        return $result;
    }
    
    public function getGiftInfo($gift_id, $customer_id = 0){
	$result = [];
	try {
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $binds = array('id' => $gift_id);
	    $sql = "select g.id, g.name, g.fpoint, g.image, g.image_logo, g.image_banner, g.banner_url, g.description, g.partner, gc.expire_date, g.block_id_detail, b.content, g.order_limit, g.discount, g.category_id ,g.is_show ,g.uses_per_customer as 'limit' 
		    from fhs_fpointstore_gift g 
		    left join fhs_fpointstore_gift_code gc on gc.gift_id = g.id and gc.period_id = 0 and gc.is_sent = 0 and (gc.expire_date - INTERVAL gc.expire_before_day DAY) > now() 
		    left join fhs_cms_block b on b.identifier = g.block_id_detail 
		    where g.id = :id 
		    group by g.id;";
	    
	    $result = $reader->fetchRow($sql, $binds);
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] getGiftInfo: gift_id=". $gift_id . ", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
        return $result;
    }
    
    public function getCustomerOrdered($customer_id){
	$result = false;
	try {
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $binds = array('company_id' => trim($company_id));
	    $sql = "select id from fhs_fpointstore_vip where company_id = ':company_id' and is_active = 1;";
	    $data = $reader->fetchRow($sql, $binds);
	    if($data){
		$result = true;
	    }
	} catch (Exception $ex) {mage::log("isVIP:".$ex->getMessage(), null, "fpointstore.log");}
        return $result;
    }
    
    public function getGiftLimit($gift_id, $customer_id){
	$result = [];
	try {
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select count(cl.customer_id) as 'bought'
		    from fhs_fpointstore_gift g
		    join fhs_fpointstore_gift_code gc on gc.gift_id = g.id
		    left join fhs_fpointstore_customer_log cl on cl.gift_code_id = gc.id and cl.customer_id = ".$customer_id.
		    " where g.id = ".$gift_id.";";
	    
	    $result = $reader->fetchRow($sql);
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] getGiftLimit: gift_id=". $gift_id . ", customer_id=". $customer_id . ", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
        return $result;
    }
    
    //Insert to Queue
    public function insertQueue($is_combo, $id, $customer){
	try{
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $binds = array(
		'customer_id' => $customer->getEntityId(),
		'is_combo' => $is_combo,
		'gift_id' => $id);
	    $sql = "INSERT INTO fhs_fpointstore_queue
		(customer_id, fpointstore_id, period_id, gift_id, version, is_combo)
		VALUES(:customer_id, 0, 0, :gift_id, 2, :is_combo);
		";
	    $writer->query($sql, $binds);
	    $result = $writer->lastInsertId();
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] insertQueue V2: customer_id=". $customer->getEntityId().", customer_email=".$customer->getEmail().", is_combo=".$is_combo.", id=".$id.", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
	return $result;
    }
    
    //get Queue
    public function getQueue($gift_queue_id, $customer){
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
        $binds = array('id' => $gift_queue_id, 'customer_id' => $customer->getEntityId());
	$sql = "select * from fhs_fpointstore_queue where customer_id = :customer_id and id = :id and status = 1;";
        return $reader->fetchRow($sql, $binds);
    }
    
    //for web and mobile
    public function getGiftCodeByIds($gift_code_ids){
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	$sql = "select gc.code,g.name, g.partner, g.discount
		from fhs_fpointstore_gift_code gc
		join fhs_fpointstore_gift g on g.id = gc.gift_id
		where gc.id in (".$gift_code_ids.");";
        return $reader->fetchAll($sql);
    }
    
    //history
    public function getVoucherHistoryList($customer_id, $is_fhs_voucher = true, $is_active_wallet_voucher = false, $check_applied, $check_cart = false, $page_current = 1, $limit = 0, $is_get_expired = true){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$page_current = $product_helper->cleanBug($page_current);
	$limit = $product_helper->cleanBug($limit);
	
	if(empty($customer_id)){return null;}
	
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	if($is_fhs_voucher){
	    if($is_active_wallet_voucher){
		$sql = "select r.*
			from (
				select cl.id, gc.gift_id, g.name, g.description, sc.code as 'coupon_code', sc.rule_id, '' as 'page_detail', gc.expire_date, ifnull(r.content,'') as 'rule_content', cl.created_at, sr.simple_free_shipping,
				if((sc.times_used < sc.usage_limit or sc.usage_limit = 0 or sc.usage_limit is null),
				    if((ifnull(scus.times_used, 0) < sc.usage_per_customer or sc.usage_per_customer = 0 or sc.usage_per_customer is null),
					if(date(gc.expire_date) >= CURRENT_DATE() or gc.expire_date is null,
					    0
					,1)
				    ,1)
				,1) as 'is_expired' 
				from fhs_fpointstore_customer_log cl 
				join fhs_fpointstore_gift_code gc on gc.id = cl.gift_code_id and gc.is_sent = 1 and (date(gc.expire_date) >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) or gc.expire_date is null)
				join fhs_fpointstore_gift g on g.id = gc.gift_id and g.partner = ''
				join fhs_salesrule_coupon sc on sc.code = gc.code and (date(sc.expiration_date) >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) or sc.expiration_date is null)
				join fhs_salesrule sr on sr.rule_id = sc.rule_id and sr.is_active = 1
				left join fhs_salesrule_customer scus on scus.customer_id = cl.customer_id and scus.rule_id = sc.rule_id 
				left join fhs_cms_block b on b.identifier = g.block_id_detail
				left join fhs_cms_block r on r.identifier = g.block_id_rule
				where cl.customer_id = ".$customer_id."
				    UNION ALL 
				select v.id, 0 as 'gift_id', v.name, v.description, sc.code as 'coupon_code', sc.rule_id, ifnull(v.page_detail,'') as 'buy_now_link', v.expire_date, ifnull(r.content,'') as 'rule_content', v.created_at, sr.simple_free_shipping,
				if((sc.times_used < sc.usage_limit or sc.usage_limit = 0 or sc.usage_limit is null),
				    if((ifnull(scus.times_used, 0) < sc.usage_per_customer or sc.usage_per_customer = 0 or sc.usage_per_customer is null),
					if(date(v.expire_date) >= CURRENT_DATE(),
					    0
					,1)
				    ,1)
				,1) as 'is_expired'
				from fhs_wallet_voucher v 
				join fhs_salesrule_coupon sc on sc.coupon_id = v.coupon_id and (date(sc.expiration_date) >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) or sc.expiration_date is null)
				join fhs_salesrule sr on sr.rule_id = sc.rule_id and sr.is_active = 1
				left join fhs_salesrule_customer scus on scus.customer_id = ".$customer_id." and scus.rule_id = sc.rule_id 
				left join fhs_cms_block r on r.identifier = v.block_id_rule
				where (v.customer_id = ".$customer_id."  or v.customer_id = 0) and v.is_show = 1 and date(v.expire_date) >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
			) r
			order by r.is_expired desc, r.created_at desc";
	    }else{
		$sql = "select cl.id, gc.gift_id, g.name, g.description, sc.code as 'coupon_code', sc.rule_id, '' as 'page_detail', gc.expire_date, ifnull(r.content,'') as 'rule_content', cl.created_at, sr.simple_free_shipping,
			if((sc.times_used < sc.usage_limit or sc.usage_limit = 0 or sc.usage_limit is null),
			    if((ifnull(scus.times_used, 0) < sc.usage_per_customer or sc.usage_per_customer = 0 or sc.usage_per_customer is null),
				if(date(gc.expire_date) >= CURRENT_DATE() or gc.expire_date is null,
				    0
				,1)
			    ,1)
			,1) as 'is_expired' 
			from fhs_fpointstore_customer_log cl 
			join fhs_fpointstore_gift_code gc on gc.id = cl.gift_code_id and gc.is_sent = 1 and (date(gc.expire_date) >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) or gc.expire_date is null)
			join fhs_fpointstore_gift g on g.id = gc.gift_id and g.partner = ''
			join fhs_salesrule_coupon sc on sc.code = gc.code and (date(sc.expiration_date) >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) or sc.expiration_date is null)
			join fhs_salesrule sr on sr.rule_id = sc.rule_id and sr.is_active = 1
			left join fhs_salesrule_customer scus on scus.customer_id = cl.customer_id and scus.rule_id = sc.rule_id 
			left join fhs_cms_block b on b.identifier = g.block_id_detail
			left join fhs_cms_block r on r.identifier = g.block_id_rule
			where cl.customer_id = ".$customer_id." and (ifnull(scus.times_used, 0) < sc.usage_per_customer or sc.usage_per_customer = 0 or sc.usage_per_customer is null)
			order by cl.created_at desc";
	    }
	}else{
	    $sql = "select cl.id, gc.gift_id, g.name, g.fpoint, g.image, g.image_logo, g.description, g.partner, g.order_limit, g.discount, g.category_id, gc.code as 'coupon_code', gc.expire_date, b.content, ifnull(r.content,'') as 'rule_content'
		    from fhs_fpointstore_customer_log cl 
		    join fhs_fpointstore_gift_code gc on gc.id = cl.gift_code_id and gc.is_sent = 1 and date(gc.expire_date) >= CURRENT_DATE()
		    join fhs_fpointstore_gift g on g.id = gc.gift_id and g.partner != ''
		    left join fhs_cms_block b on b.identifier = g.block_id_detail
		    left join fhs_cms_block r on r.identifier = g.block_id_rule
		    where cl.customer_id = ".$customer_id."
		    order by cl.created_at desc";
	}
	if($limit != 0){
	    $sql = $sql." limit ".($limit*($page_current-1))." , ".$limit.";";
	}else{
	    $sql = $sql.";";
	}
	
	$result = $reader->fetchAll($sql);
	
	//replace Variable
	//{{NAME}}
	//{{DESC}}
	//{{COUPON_CODE}}
	//{{EXP}}
	//{{ICON_LINK}}
	//{{IMAGE}}
	//{{PARTNER}}
	//{{FPOINT}}
	//{{ORDER_LIMIT}}
	//{{DISCOUNT}}
	if(!empty($result)){
	    if($is_fhs_voucher && $check_applied){
		$coupon_applied = Mage::getSingleton('checkout/session')->getQuote()->getCouponCode();
		$coupon_applied = strtoupper(trim($coupon_applied));
	    }
	    $media_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA, true);
	    $queryfier = Mage::getStoreConfig('bubble_queryfier/suffix_js_css/suffix');
	    if($check_cart){
		$quote = Mage::getSingleton('checkout/session')->getQuote();
		$quote = $this->includeTax($quote);
		$total_default = ($quote->getSubtotal()>25000)?25000:$quote->getSubtotal();
	    }
	    foreach($result as $key=>$item){
		$is_use_item = true;
		$item['expire_date'] = date('d/m/Y',strtotime($item['expire_date']));
		if(!empty($item['image_logo'])){
		    $item['image_logo'] = $media_url.$item['image_logo'] . '?q='.$queryfier;
		}
		if(!empty($item['image'])){
		    $item['image'] = $media_url.$item['image'] . '?q='.$queryfier;
		}
		$item['title'] = $item['name'];
		$item['title_2'] = $item['description'];
		$item['sub_total'] = '';
		$item['reach_percent'] = 0;
		$item['applied'] = false;
		$item['matched'] = false;
                $item['event_type'] = $item['simple_free_shipping'] ? 4 : 1;
                $item['is_expired'] = $item['is_expired'] == 1 ? true : false;
		if($item['is_expired'] && !$is_get_expired){
		    unset($result[$key]);
		    continue;
		}
		 
		if(!empty($item['page_detail'])){
		    if(substr($item['page_detail'],0,1) == "/"){
			$item['page_detail'] = substr($item['page_detail'],1, strlen($item['page_detail']));
		    }
		}else{
		    $item['page_detail'] = '';
		}
		if(!empty($item['rule_content'])){
		    $item['rule_content'] = str_replace("{{NAME}}",$item['name'],$item['rule_content']);
		    $item['rule_content'] = str_replace("{{DESC}}",$item['description'],$item['rule_content']);
		    $item['rule_content'] = str_replace("{{COUPON_CODE}}",$item['coupon_code'],$item['rule_content']);
		    $item['rule_content'] = str_replace("{{EXP}}",$item['expire_date'],$item['rule_content']);
		    if(!empty($item['image_logo'])){
			$item['rule_content'] = str_replace("{{ICON_LINK}}",$item['image_logo'],$item['rule_content']);
		    }
		    if(!empty($item['image'])){
			$item['rule_content'] = str_replace("{{IMAGE}}",$item['image'],$item['rule_content']);
		    }
		    if(!empty($item['partner'])){
			$item['rule_content'] = str_replace("{{PARTNER}}",$item['partner'],$item['rule_content']);
		    }
		    if(!empty($item['fpoint'])){
			$item['rule_content'] = str_replace("{{FPOINT}}",$item['fpoint'],$item['rule_content']);
		    }
		    if(!empty($item['order_limit'])){
			$item['rule_content'] = str_replace("{{ORDER_LIMIT}}",$item['order_limit'],$item['rule_content']);
		    }
		    if(!empty($item['discount'])){
			$item['rule_content'] = str_replace("{{DISCOUNT}}",$item['discount'],$item['rule_content']);
		    }
		}
		if(!empty($coupon_applied)){
		    if(strtoupper($item['coupon_code']) == $coupon_applied){
			$item['applied'] = true;
		    }
		}
		if(!$item['is_expired']){
		    Mage::getSingleton('customer/session')->setRuleMsg(array(1=>true));
		    if($check_cart && !empty($item['rule_id'])){
			$rule = Mage::getModel('salesrule/rule')->load($item['rule_id']);

			$item['matched'] = $this->getValidateRuleId($quote, $rule, $item['coupon_code'], false);

			$item['sub_total'] = number_format($total_default, 0, ",", ".")." đ";;
			$item['max_total'] = '25.000 đ';
			$item['min_total'] = '0 đ';
			try{$item['reach_percent'] = round($total_default/25000 * 100, 0);} catch (Exception $ex) {};

			$msg_store = Mage::getSingleton('customer/session')->getRuleMsg();
			Mage::getSingleton('customer/session')->unsRuleMsg();
			if(!empty($msg_store)){
			    $msg_array = [];
			    foreach($msg_store[$item['rule_id']] as $msg_store_item){
				if($msg_store_item['attribute'] == 'base_subtotal' || $msg_store_item['attribute'] == 'base_row_total'){
				    if($msg_store_item['opt'] == ">"|| $msg_store_item['opt'] == ">=") {
					$item['min_total'] = "0 đ";
					$item['max_total'] = number_format($msg_store_item['r_value'], 0, ",", ".")." đ";
					try{$item['reach_percent'] = round($msg_store_item['v_value']/$msg_store_item['r_value'] * 100, 0);} catch (Exception $ex) {}
					$need_total = $msg_store_item['r_value'] - $msg_store_item['v_value'];
					if ($need_total > 0){
						$item['need_total'] = Mage::helper('core')->formatPrice($need_total, false);
					}
					$item['sub_total'] = number_format($msg_store_item['r_value'], 0, ",", ".")." đ";
				    }else{
					$msg_array_item = [];
					$msg_array_item['type'] = $msg_store_item['attribute'];
					$msg_array_item['message'] = $msg_store_item['msg'];
					array_push($msg_array, $msg_array_item);
				    }
				}elseif($msg_store_item['attribute'] == 'over_limit' 
					|| $msg_store_item['attribute'] == 'over_limit_customer'){
				    $is_use_item = false;
				}else{
				    $msg_array_item = [];
				    $msg_array_item['type'] = $msg_store_item['attribute'];
				    $msg_array_item['message'] = $msg_store_item['msg'];
				    array_push($msg_array, $msg_array_item);
				}
			    }
			    if(sizeof($msg_array) > 0){
				$item['error'] = $msg_array;
			    }
			}

			//check customer group
			$is_pass_customer_group = false;
			foreach ($rule->getCustomerGroupIds() as $item_group){
			    if($quote->getCustomerGroupId() == $item_group){
				$is_pass_customer_group = true;
				break;
			    }
			}
			if(!$is_pass_customer_group){
			    if(empty($msg_array)){
				$msg_array = [];
			    }
			    $msg_array_item = [];
			    $msg_array_item['type'] = 'customer_group';
			    $msg_array_item['message'] = "Không thuộc nhóm áp dụng";
			    array_push($msg_array, $msg_array_item);

			    $item['matched'] = false;
			    $item['error'] = $msg_array;
			}

			if($item['reach_percent'] > 100){$item['reach_percent'] = 100;}
			if($item['reach_percent'] < 0){$item['reach_percent'] = 0;}
		    }
		}

		//dynamic rule_content 
        if (empty($item['rule_content'])){
	    	$item['rule_content'] = Mage::helper('eventcart')->createDynamicRuleContent(null, $item['title'], $item['title_2'], $coupon['expire_date']);
		}
		
		if($is_use_item){
		    $result[$key] = $item;
		}
	    }
	}
	return $result;
    }
    
    //validate apply cart with rule id
    public function getValidateRuleId($quote, $rule, $couponCode, $is_check_limit = false){
	foreach ($quote->getAllAddresses() as $quote_address){
	    $items = $quote_address->getAllNonNominalItems();
	    if(!$items){
		continue;
	    }
		
	    foreach ($items as $item) {
		if ($item->getNoDiscount()) {
		    continue;
		}
		if ($item->getParentItemId()){
                    continue;
                }
		
		$address = $this->_getAddress($item);
		if(!$this->_canProcessRule($rule,$address, $is_check_limit)){
		    return false;
		}
		
                if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                    foreach ($item->getChildren() as $child) {
                        $address = $this->_getAddress($child);
                        if(!$this->_canProcessRule($rule,$address, $is_check_limit)){
			    return false;
			}
                    }
                }
	    }
	}
	
	Mage::getSingleton('customer/session')->unsRuleMsg();
        $rule->setIsValidForAddress($address, true);
	return true;
    }
    
    protected function _canProcessRule($rule, $address, $is_check_limit){
	if ($rule->hasIsValidForAddress($address) && !$address->isObjectNew()) {
	    return $rule->getIsValidForAddress($address);
	}
	
	$msg = Mage::getSingleton('customer/session')->getRuleMsg();
	$msg_rule = [];
	$rule_id = $rule->getId();
	if(!empty($msg[$rule_id])){
	    $msg_rule = $msg[$rule_id];
	}
	
	$current_date = date("Y-m-d", Mage::getModel('core/date')->timestamp(time()));
	if(!empty($rule->getFromDate())){
	    if(date('Y-m-d',strtotime($rule->getFromDate())) > $current_date){
		$msg_item['attribute'] = 'from_date';
		$msg_item['msg'] = "Có hiệu lực từ ngày ".date('d/m/Y', strtotime($rule->getFromDate()));
		array_push($msg_rule, $msg_item);
		$msg[$rule_id] = $msg_rule;
		Mage::getSingleton('customer/session')->setRuleMsg($msg);
		return false;
	    }
	}
	
	// check from created account
	if($rule->getFromCreatedAccount()){
	    if(Mage::getSingleton('customer/session')->isLoggedIn()){
		$from_created_account = date('Y-m-d', strtotime($rule->getFromCreatedAccount())) . " 00:00:00";
		$customer_created_at = date('Y-m-d H:i:s', strtotime('+7 hour',strtotime(Mage::getSingleton('customer/session')->getCustomer()->getCreatedAt())));
		if($customer_created_at < $from_created_account){
		    $msg_item['attribute'] = 'from_created_account';
		    $msg_item['msg'] = "Chỉ áp dụng cho thành viên fahasa, đăng ký tù ngày ".date('d/m/Y', strtotime($rule->getFromCreatedAccount()));
		    array_push($msg_rule, $msg_item);
		    $msg[$rule_id] = $msg_rule;
		    Mage::getSingleton('customer/session')->setRuleMsg($msg);
		    return false;
		}
	    }else{
		$msg_item['attribute'] = 'from_created_account';
		$msg_item['msg'] = "Chỉ áp dụng cho thành viên fahasa";
		array_push($msg_rule, $msg_item);
		$msg[$rule_id] = $msg_rule;
		Mage::getSingleton('customer/session')->setRuleMsg($msg);
		return false;
	    }
	}
		
	if($is_check_limit){
	    //check per coupon usage limit
	    if ($rule->getCouponType() != Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON) {
		if (strlen($couponCode)) {
		    $coupon = Mage::getModel('salesrule/coupon');
		    $coupon->load($couponCode, 'code');
		    if ($coupon->getId()) {
			// check entire usage limit
			if ($coupon->getUsageLimit() && $coupon->getTimesUsed() >= $coupon->getUsageLimit()) {
			    $rule->setIsValidForAddress($address, false);

			    $msg_item['attribute'] = 'over_limit';
			    $msg_item['msg'] = "Hết lượt sử dụng";
			    array_push($msg_rule, $msg_item);
			    $msg[$rule_id] = $msg_rule;
			    Mage::getSingleton('customer/session')->setRuleMsg($msg);
			    return false;
			}

			// check per customer usage limit
			$customerId = $address->getQuote()->getCustomerId();
			if ($customerId && $coupon->getUsagePerCustomer()) {
			    $couponUsage = new Varien_Object();
			    Mage::getResourceModel('salesrule/coupon_usage')->loadByCustomerCoupon(
				$couponUsage, $customerId, $coupon->getId());
			    if ($couponUsage->getCouponId() &&
				$couponUsage->getTimesUsed() >= $coupon->getUsagePerCustomer()
			    ) {
				$rule->setIsValidForAddress($address, false);

				$msg_item['attribute'] = 'over_limit_customer';
				$msg_item['msg'] = "Bạn đã hết lượt sử dụng";
				array_push($msg_rule, $msg_item);
				$msg[$rule_id] = $msg_rule;
				Mage::getSingleton('customer/session')->setRuleMsg($msg);
				return false;
			    }
			}
		    }
		}
	    }

	    if ($rule->getId() && $rule->getUsesPerCustomer()) {
		$customerId     = $address->getQuote()->getCustomerId();
		$ruleCustomer   = Mage::getModel('salesrule/rule_customer');
		$ruleCustomer->loadByCustomerRule($customerId, $ruleId);
		if ($ruleCustomer->getId()) {
		    if ($ruleCustomer->getTimesUsed() >= $rule->getUsesPerCustomer()) {
			$rule->setIsValidForAddress($address, false);

			$msg_item['attribute'] = 'over_limit_customer';
			$msg_item['msg'] = "Bạn đã hết lượt sử dụng";
			array_push($msg_rule, $msg_item);
			$msg[$rule_id] = $msg_rule;
			Mage::getSingleton('customer/session')->setRuleMsg($msg);
			return false;
		    }
		}
	    }
	}

	$SubtotalInclTax = $address->getSubtotalInclTax();
	$address->setBaseSubtotal($SubtotalInclTax);
	
	if (!$rule->validate($address)) {
	    $rule->setIsValidForAddress($address, false);
	    return false;
	}
        return true;
    }

    public function getBuffetCoupon(){
	$result = [];

	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	$query = "select p.id, p.discountoriginal_id, s.description, c.rule_id, c.code, s.simple_free_shipping, p.stop_time as expire_date
		from fhs_event_discountoriginal_period p
		join fhs_salesrule_coupon c on c.code = p.coupon_code and (date(c.expiration_date) >= CURRENT_DATE() or c.expiration_date is null) and (c.times_used < c.usage_limit or c.usage_limit = 0 or c.usage_limit is null)
		join fhs_salesrule s on s.rule_id = c.rule_id and s.is_active = 1
		where p.start_time < now() and p.stop_time > now();";
	$data = $reader->fetchAll($query);
	if(!empty($data)){
	    $quote = Mage::getSingleton('checkout/session')->getQuote();
	    $quote = $this->includeTax($quote);
	    
	    $coupon_applied = $quote->getCouponCode();
	    $coupon_applied = strtoupper(trim($coupon_applied));
	    $total_default = ($quote->getSubtotal()>25000)?25000:$quote->getSubtotal();
	    foreach($data as $key=>$coupon){
		$is_use_item = true;
		$item = [];
		$item['action_id'] = 0;
		$item['action_type'] = "salesrule";
		$item['almost_run_out'] = false;
		$item['applied'] = false;
		$item['coupon_code'] = strtoupper($coupon['code']);
		$item['error'] = [];
		$item['event_type'] = $coupon['simple_free_shipping'] ? 4 : 1;
		$item['is_auto'] = true;
		$item['matched'] = false;
		$item['matched_items'] = null;
		$item['max_total'] = '25.000 đ';
		$item['min_total'] = '0 đ';
		$item['order_index'] = 999;
		$item['payment_method'] = null;
		$item['rule_content'] = null;
		$item['sub_total'] = number_format($total_default, 0, ",", ".")." đ";
		$item['title'] = '';
		$item['title_2'] = '';
		
		try{$item['reach_percent'] = round($total_default/25000 * 100, 0);} catch (Exception $ex) {};
		    
		if(!empty($coupon['description'])){
		    $info = explode("-",$coupon['description']);
		    if(!empty($info[0])){
			$item['title_2'] = trim($info[0]);
		    }
		    if(!empty($info[1])){
			if(!empty($item['title_2'])){
			    $item['title_2'] .= " - ";
			}
			$item['title_2'] .= trim($info[1]);
		    }
		    if(!empty($info[2])){
			$item['title'] = trim($info[2]);
		    }
		}
		
		if(!empty($coupon_applied)){
		    if(strtoupper($coupon['code']) == $coupon_applied){
			$item['applied'] = true;
		    }
		}
		
		if(!empty($item['title'])){
		    Mage::getSingleton('customer/session')->setRuleMsg(array(1=>true));
		    $rule = Mage::getModel('salesrule/rule')->load($coupon['rule_id']);
		    $item['matched'] = $this->getValidateRuleId($quote, $rule, $coupon['code'], true);
		    
		    $msg_store = Mage::getSingleton('customer/session')->getRuleMsg();
		    Mage::getSingleton('customer/session')->unsRuleMsg();
		    if(!empty($msg_store)){
			$msg_array = [];
			foreach($msg_store[$coupon['rule_id']] as $msg_store_item){
			    if($msg_store_item['attribute'] == 'base_subtotal' || $msg_store_item['attribute'] == 'base_row_total'){
				if($msg_store_item['opt'] == ">"|| $msg_store_item['opt'] == ">=") {
				    $item['min_total'] = "0 đ";
				    $item['max_total'] = number_format($msg_store_item['r_value'], 0, ",", ".")." đ";
				    try{
                                        $item['reach_percent'] = round($msg_store_item['v_value']/$msg_store_item['r_value'] * 100, 0);
                                        $need_total = $msg_store_item['r_value'] - $msg_store_item['v_value'];
                                        if ($need_total > 0){
                                            $item['need_total'] = Mage::helper('core')->formatPrice($need_total, false);
                                        }
                                    } catch (Exception $ex) {}
				    $item['sub_total'] = number_format($msg_store_item['r_value'], 0, ",", ".")." đ";
				}else{
				    $msg_array_item = [];
				    $msg_array_item['type'] = $msg_store_item['attribute'];
				    $msg_array_item['message'] = $msg_store_item['msg'];
				    array_push($msg_array, $msg_array_item);
				}
			    }elseif($msg_store_item['attribute'] == 'over_limit' 
				    || $msg_store_item['attribute'] == 'over_limit_customer'){
				$is_use_item = false;
			    }else{
				$msg_array_item = [];
				$msg_array_item['type'] = $msg_store_item['attribute'];
				$msg_array_item['message'] = $msg_store_item['msg'];
				array_push($msg_array, $msg_array_item);
			    }
			}
			if(sizeof($msg_array) > 0){
			    $item['error'] = $msg_array;
			}
		    }
		    
		    //check customer group
		    $is_pass_customer_group = false;
		    foreach ($rule->getCustomerGroupIds() as $item_group){
			if($quote->getCustomerGroupId() == $item_group){
			    $is_pass_customer_group = true;
			    break;
			}
		    }
		    if(!$is_pass_customer_group){
			if(empty($msg_array)){
			    $msg_array = [];
			}
			$msg_array_item = [];
			$msg_array_item['type'] = 'customer_group';
			$msg_array_item['message'] = "Không thuộc nhóm áp dụng";
			array_push($msg_array, $msg_array_item);
			
			$item['matched'] = false;
			$item['error'] = $msg_array;
		    }
		    
		    if($item['reach_percent'] > 100){$item['reach_percent'] = 100;}
		    if($item['reach_percent'] < 0){$item['reach_percent'] = 0;}
                    
                    //dynamic rule_content 
                    if (empty($item['rule_content'])){
                        $item['rule_content'] = Mage::helper('eventcart')->createDynamicRuleContent(null, $item['title'], $item['title_2'], $coupon['expire_date']);
                    }
		    
		    if($is_use_item){
			array_push($result, $item);
		    }
		}
	    }
	}
	return $result;
    }
    //includeTax
    protected function includeTax($quote) {
	foreach ($quote->getAllVisibleItems() as $quote_item) {
	    $quote_item->setPrice($quote_item->getPriceInclTax());
	    $quote_item->setBasePrice($quote_item->getBasePriceInclTax());
	    $quote_item->setBaseRowTotal($quote_item->getBaseRowTotalInclTax());
	}
	return $quote;
    }
    
    //VIP
    public function setVIP($customer_id, $company_id){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$company_id = $product_helper->cleanBug($company_id);
	
	$result = false;
	try {
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select * from fhs_customer_entity_varchar where attribute_id = 178 and entity_id = ".$customer_id.";";
	    $info = $reader->fetchRow($sql, $binds);
	    
	    if(!$info){
		$sql = "INSERT INTO fhs_customer_entity_varchar (entity_type_id, attribute_id, entity_id, value) VALUES(1, 178, ".$customer_id.", '".$company_id."');";
	    }else{
		$sql = "update fhs_customer_entity_varchar set value = '".$company_id."' where attribute_id = 178 and entity_id = ".$customer_id.";";
	    }
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $writer->query($sql);
	    $result = true;
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] setVIP: customer_id=". $customer_id .", company_id:".$company_id .", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
        return $result;
    }
    
    //donate Fpoint single customer
    public function donateFpoint($customer_id, $fpoint, $noti_title, $noti_content, $noti_page_value, $noti_url, $action_purchase, $description_purchase){
	$result = false;
	try {
	    if(empty($customer_id)){
		return $result;
	    }
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    if(Mage::helper("fahasa_customer/fpoint")->transationFpoint($customer_id, $fpoint, 'fpoint', $action_purchase, $description_purchase)){
		$sql = "INSERT INTO fhs_mobile_notification (title, content, customer_id, created_by, created_at, seen_status, page_type, page_value, url) 
		VALUES('".$noti_title."', '".$noti_content."', ".$customer_id.", 'magento', now(), 0, 'event', '".$noti_page_value."','".$noti_url."'); ";
		$writer->query($sql);
		$result = true;
	    }
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] updateOrderCompleteTime, message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
        return $result;
    }

    //donate Fpoint multi customer where order complete (only donate one time)
    public function donateFpointWhereOrderComplete(){
	$result = [];
	$result['result'] = false;
	$result['message'] = ""; 
	try {
	    $fpoint = 1000;
	    $noti_title = "Bạn có 1.000 điểm F-Point Để Đối Combo Voucher dành riêng cho bạn";
	    $noti_content = "Chúc mừng bạn được tặng 1.000 F-Point, đồng thời bạn có thêm 1 cơ hội mua Combo Voucher dành riêng cho bạn chỉ với 1.000 F-Point. Nhấn vào thông báo này để Vào ngay F-POINT STORE ( https://www.fahasa.com/fpointstore/ ) đổi Combo Voucher. ";
	    $noti_page_value = "fpointstore";
	    $noti_url = "/fpointstore";
	    $action_purchase = "Donate Fpoint";
	    $description_purchase = "FStore: donate for customer VIP orders complete";
	    
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select cv.customer_id, e.email, e.fpoint 
		    from fhs_fpointstore_customer_vip cv
		    join fhs_fpointstore_vip_rule vr on vr.vip_id = cv.vip_id and vr.order_times = 1
		    join fhs_customer_entity e on e.entity_id = cv.customer_id 
		    where donated_order_complete = 0 and order_complete_times > 0
		    group by cv.customer_id;";
	    $customer_list = $reader->fetchAll($sql);
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    if($customer_list){
		foreach ($customer_list as $customer){
		    try{
			if($this->donateFpoint($customer['customer_id'], $fpoint, $noti_title, $noti_content, $noti_page_value, $noti_url, $action_purchase, $description_purchase)){
			    $result['message'] .= "[SUCCESS] donated customer_id: ".$customer['customer_id']. ", fpoint_before: ".$fpoint_before.", fpoint_after: ".$fpoint_after." \n";
			    $sql = "UPDATE fhs_fpointstore_customer_vip set donated_order_complete = 1 where customer_id = ".$customer['customer_id']."; ";
			    $writer->query($sql);
			}else{
			    $result['message'] .= "[ERROR] customer_id: ".$customer['customer_id']." msg: post to REST fail \n";
			}
		    } catch (Exception $ex) {
			$result['message'] .= "[ERROR] customer_id: ".$customer['customer_id'].", msg:".$ex->getMessage()." \n";
			Mage::log("[ERROR] donateFpointWhereOrderComplete customer_id: ".$customer['customer_id'].", msg:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
		    }
		}
	    }
	    $result['result'] = true;
	} catch (Exception $ex) {
	    $result['message'] = $ex->getMessage();
	    Mage::log("***[ERROR] donateFpointWhereOrderComplete, message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
        return $result;
    }
      
    //update order complete time
    public function updateOrderCompleteTime(){
	$result = [];
	$result['result'] = false;
	try {
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $sql = "UPDATE fhs_fpointstore_customer_vip cv 
		    join (
			    select cv.customer_id, count(o.status) as 'order_times', cv.order_complete_times 
			    from fhs_fpointstore_customer_vip cv 
			    join fhs_sales_flat_order o on o.customer_id = cv.customer_id and (o.created_at + INTERVAL 7 HOUR) >= cv.registered_at and o.status = 'complete' 
			    group by cv.customer_id 
			    HAVING COUNT(o.status) > cv.order_complete_times 
		    ) rcv on rcv.customer_id = cv.customer_id 
		    set cv.order_complete_times = rcv.order_times;";
	    $writer->query($sql);
	    $sql = "update fhs_fpointstore_customer_vip cv 
		    join(
			select cv.customer_id, max(vr.combo_buy_limit) as combo_buy_limit 
			from fhs_fpointstore_customer_vip cv 
			join fhs_fpointstore_vip_rule vr on vr.vip_id = cv.vip_id and vr.order_times <= cv.order_complete_times and vr.combo_buy_limit > cv.combo_buy_limit 
		    ) r on cv.customer_id = r.customer_id 
		    set cv.combo_buy_limit = r.combo_buy_limit;";
	    $writer->query($sql);
	    $donate_fpoint_result =  $this->donateFpointWhereOrderComplete();
	    $result['message_donate'] = $donate_fpoint_result['message'];
	    $result['result'] = true;
	} catch (Exception $ex) {
	    $result['message'] = $ex->getMessage();
	    Mage::log("***[ERROR] updateOrderCompleteTime, message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
        return $result;
    }
    
    //common
    protected function _getCustomerSession() {
        return Mage::getSingleton('customer/session');
    }
    
    public function getCache($key){
	if ($data = Mage::app()->getCache()->load($key)) {
	    $data = unserialize($data);
	}
	return $data;
    }

    public function setCache($key, $data){
	Mage::app()->getCache()->save(serialize($data), $key);
    }
    
    protected function _getAddress(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        if ($item instanceof Mage_Sales_Model_Quote_Address_Item) {
            $address = $item->getAddress();
        } elseif ($this->_address) {
            $address = $this->_address;
        } elseif ($item->getQuote()->getItemVirtualQty() > 0) {
            $address = $item->getQuote()->getBillingAddress();
        } else {
            $address = $item->getQuote()->getShippingAddress();
        }
        return $address;
    }
    
    
    public function getWalletVoucherHtml($coupons, $event_cart_limit, $languages){
	$result = '';
	try{
	    $matched_list = '';
	    $matched_title = '<div class="fhs-event-promo-list-title">'.$languages['matched_voucher_title'].'</div>';
	    $matched = '';
	    $matched_more = '';

	    $not_matched_list = '';
	    $not_matched_title = '<div class="fhs-event-promo-list-title">'.$languages['notmatched_voucher_title'].'</div>';
	    $not_matched = '';
	    $not_matched_more = '';
	    $matched_viewmore_btn = '<div class="fhs-event-promo-list-viewmore">'
			.'<a class="collapse" data-toggle="collapse" href="#collapse_walletvoucher_list_matched"><span class="text-viewmore">'.$languages['viewmore'].'</span><span class="text-viewless">'.$languages['viewless'].'</span><img src="'.$languages['ico_down_orange'].'"/></a>'
		    .'</div>';
	    $line = '<div class="fhs-event-promo-list-line"></div>';
	    $not_matched_viewmore_btn = '<div class="fhs-event-promo-list-viewmore">'
			.'<a class="collapse" data-toggle="collapse" href="#collapse_walletvoucher_list_not_matched"><span class="text-viewmore">'.$languages['viewmore'].'</span><span class="text-viewless">'.$languages['viewless'].'</span><img src="'.$languages['ico_down_orange'].'"/></a>'
		    .'</div>';

	    $coupons_match = array();
	    $coupons_not_match = array();

	    foreach($coupons as $key=>$item){
		if($item['matched']){
		    $coupons_match[$key] = $item;
		}else{
		    $coupons_not_match[$key] = $item;
		}
	    }

	    if(!empty($coupons_match)){
		$count = 0;

		foreach($coupons_match as $key=>$item){
		    if($count < $event_cart_limit){
			$matched .= $this->getWalletVoucherItem($key, 'matched', $item, $languages);
		    }else{
			$matched_more .= $this->getWalletVoucherItem($key, 'matched', $item, $languages);
		    }
		    $count++;
		}
		$matched_list = '<div class="fhs-event-promo-list">';
		    $matched_list .= $matched_title;
		    $matched_list .= $matched;
		    if(!empty($matched_more)){
			$matched_list .= '<div id="collapse_walletvoucher_list_matched" class="panel-collapse collapse in">';
			$matched_list .= $matched_more;
			$matched_list .= '</div>';
			$matched_list .= $matched_viewmore_btn;
		    }
		$matched_list .= '</div>';
	    }

	    if(!empty($coupons_not_match)){
		$count = 0;
		foreach($coupons_not_match as $key=>$item){
		    if($count < $event_cart_limit){
			$not_matched .= $this->getWalletVoucherItem($key, 'not_matched', $item, $languages);
		    }else{
			$not_matched_more .= $this->getWalletVoucherItem($key, 'not_matched', $item, $languages);
		    }
		    $count++;
		}
		$not_matched_list = '<div class="fhs-event-promo-list">';
		    $not_matched_list .= $not_matched_title;
		    $not_matched_list .= $not_matched;
		    if(!empty($not_matched_more)){
			$not_matched_list .= '<div id="collapse_walletvoucher_list_not_matched" class="panel-collapse collapse in">';
			$not_matched_list .= $not_matched_more;
			$not_matched_list .= '</div>';
			$not_matched_list .= $not_matched_viewmore_btn;
		    }
		$not_matched_list .= '</div>';
	    }

	    if(!empty($matched_list)){
		$result = $matched_list;
	    }
	    if(!empty($matched_list) && !empty($not_matched_list)){
		$result .= $line;
	    }
	    if(!empty($not_matched_list)){
		$result .= $not_matched_list;
	    }

	    if(empty($matched_list) && empty($not_matched_list)){
		$result = $icon_empty;
	    }
	} catch (Exception $ex) {$result = '';}
	return $result;
    }
    public function getWalletVoucherItem($index, $_type, $coupon, $languages){
	$class_color = "fhs-event-promo-list-item-blue";
	$img_coupon = $languages['ico_couponblue'];
	$description = '';
	$coupon_code = '';
	$expire_date = '';
	$error_total = '';
	$errors = '';
	$rule_content = '';
	$class_content_detail = '';
	$btn_apply = '';
	$btn_detail = '';
	$almost_over = '';
	$progress_bar = '';
	$progress_bar_class = '';
	$total = '';
	$class_button_more = '';
	
	if($coupon['almost_run_out']){
//	    if(_type == 'matched'){
//		almost_over = "<span class=\"fhs-event-promo-almost-over-red\">".$languages['selling_out']+"</span>";
//	    }else{
		$almost_over = "<span class=\"fhs-event-promo-almost-over-red\">".$languages['selling_out']."</span>";
//	    }
	}
	
	if(!empty($coupon['min_total']) && !empty($coupon['max_total'])){
	    $total = "<div class=\"fhs-event-promo-item-minmax\"><span>".$coupon['min_total']."</span><span>".$coupon['max_total']."</span></div>";
	}
	if($coupon['matched']){
	    $class_color = "fhs-event-promo-list-item-green";
	    $img_coupon = $languages['ico_coupongreen'];
	    $progress_bar_class = "class=\"progress-success\"";
	}else{
	    if(!empty($coupon['sub_total'])){
		$progress_bar = "<div class=\"fhs-event-promo-item-progress-bar\">"
			    ."<div class=\"fhs-event-promo-item-progress\"><hr ".$progress_bar_class." style=\"width:". $coupon['reach_percent'] . "%;\"/><div>".$coupon['sub_total']."</div><img class='progress-cheat' src='".$languages['progress_cheat_img']."'/></div>"
			    .$total
			."</div>";
	    }
	}
	
	if($coupon['description']){
	    $description = "<div>".$coupon['description']."</div>";
	}
	if($coupon['coupon_code']){
	    $coupon_code = "<div class='fhs_voucher_code'>".$languages['Voucher_code']." - ".$coupon['coupon_code']."</div>";
	}
	if($coupon['expire_date']){
	    $expire_date = "<div class='fhs_voucher_expiry'>HSD: ".$coupon['expire_date']."</div>";
	}
	if(!empty($coupon['error'])){
	    if(sizeof($coupon['error']) > 1){
		$error_total = "<div class=\"fhs-event-promo-error\" onclick='showEventCartErrorBlock(this)'>* ".sizeof($coupon['error'])." điều kiện không thỏa "."<img src='".$languages['ico_viewmore']."' /></div>";
		$errors = "<div class='fhs-event-promo-error-block'>";
		foreach($coupon['error'] as $key=>$item){
		    $errors .= "<div class=\"fhs-event-promo-error\">* ".$item['message']."</div>";
		}
		$errors .= "</div>";
	    }else{
		foreach($coupon['error'] as $key=>$item){
		    $errors .= "<div class=\"fhs-event-promo-error\">* ".$item['message']."</div>";
		}
	    }
	    
	}
	if($coupon['applied']){
	    $btn_apply = "<button type='button' onclick='applyCoupon(this);' title='".$languages['cancel_apply']."' coupon='".$coupon['coupon_code']."' apply='0' class='fhs-btn-view-promo-coupon' ><span>".$languages['cancel_apply']."</span></button>";
	    $btn_detail = "<button type=\"button\" title=\"".$languages['cancel_apply']."\" onclick=\"applyCoupon(this);\" coupon=\"".$coupon['coupon_code']."\" apply=\"0\" class=\"btn-close-popup-event\"><span>".$languages['cancel_apply']."</span></button>";
	}else{
	    if($coupon['matched']){
		$btn_apply = "<button type=\"button\" onclick=\"applyCoupon(this);\" title=\"".$languages['apply']."\" coupon=\"".$coupon['coupon_code']."\" apply=\"1\" class=\"fhs-btn-view-promo-coupon\"><span>".$languages['apply']."</span></button>";
		$btn_detail = "<button type=\"button\" title=\"".$languages['apply']."\" onclick=\"applyCoupon(this);\" coupon=\"".$coupon['coupon_code']."\" apply=\"1\" class=\"btn-close-popup-event fhs-btn-view-promo-detail-coupon\"><span>".$languages['apply']."</span></button>";
	    }else{
		if($item['reach_percent'] >= 100){
		    $btn_apply = "<button type=\"button\" onclick=\"applyCoupon(this);\" title=\"".$languages['apply']."\" coupon=\"".$coupon['coupon_code']."\" apply=\"1\" class=\"fhs-btn-view-promo-coupon\" disabled><span>".$languages['apply']."</span></button>";
		    $btn_detail= "<button type=\"button\" title=\"".$languages['apply']."\" class=\"btn-close-popup-event\" disabled><span>".$languages['apply']."</span></button>";
		}else{
		    $btn_apply = "<a href=\"\\".$coupon['page_detail']."\"><button type=\"button\" title=\"".$languages['buy_more']."\" class=\"fhs-btn-view-promo\"><span>".$languages['buy_more']."</span></button></a>";
		    $btn_detail = "<a href=\"\\".$coupon['page_detail']."\"><button type=\"button\" title=\"".$languages['buy_more']."\" class=\"btn-close-popup-event fhs-btn-view-promo-detail-gift\"><span>".$languages['buy_more']."</span></button></a>";
		}
		//$class_button_more = "class='no-more-button'";
	    }
	}
	if($coupon['rule_content']){
	    $class_content_detail = 'class="fhs-event-promo-list-item-content" onclick="showVoucherDetail(this, '.$index.');"';
	    $rule_content = "<div class=\"fhs-event-promo-list-item-detail\" onclick=\"showVoucherDetail(this, ".$index.")\">".$languages['detail']
		    ."<div class=\"fhs-event-promo-list-item-btndata\">".$btn_detail."</div>"
		    ."</div>";
	}
	return "<div class=\"fhs-event-promo-list-item ".$class_color."\">"
		    ."<div>"
			."<img src=\"".$img_coupon."\"/>"
		    ."</div>"
		    ."<div ".$class_button_more.">"
			."<div>"
			    ."<div ".$class_content_detail.">"
				."<div>".$almost_over . $coupon['name']."</div>"
				.$description
				.$coupon_code
				.$expire_date
				.$error_total
				.$errors
			    ."</div>"
			."<div>"
			.$rule_content
			."<div class=\"fhs-event-promo-list-item-button\">"
			    .$btn_apply
			."</div>"
			."</div>"
			."</div>"
			.$progress_bar
		    ."</div>"
		."</div>";
    }
}
//explode(',',$item);
//implode(",", $item);