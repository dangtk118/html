<?php

class Fahasa_Fpointstore_Helper_Data_Error {

    const DISABLED_FPOINTSTORE = "disabled_fpointstore";
    const NO_ACTIVE_FPOINTSTORE = "no_active_fpointstore";
    const NO_ACTIVE_PERIOD = "no_active_period";
    const NO_CONNECTION = "no_connection";
    const NO_PERIODS = "no_periods";
    const NO_PRODUCTS = "no_gifts";

}

class Fahasa_Fpointstore_Helper_Data extends Mage_Core_Helper_Abstract {

    const FPOINTSTORE_KEY_NAME = "fpointstore";
    const PERIOD_KEY_NAME = "fpointstore_period";
    const GIFT_KEY_NAME = "gift_entity";
    const SEPERATOR = ":";
    const FHS_FPOINTSTORE = "fhs_fpointstore";
    const FHS_FPOINTSTORE_PERIOD = "fhs_fpointstore_period";
    const FHS_FPOINTSTORE_PRODUCTS = "fhs_fpointstore_gift";
    const FHS_FPOINTSTORE_PRODUCT_CODE = "fhs_fpointstore_gift_code";
    
    //load for copy db to redis
    public function copyDataFromMysqlToRedis() {

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $fpointstore_id = (int) Mage::getStoreConfig('fpointstore_config/config/active_fpointstore_id');
	
        $query_fpointstore_binds = array('id' => $fpointstore_id);
        /// Query Fpointstore
        $query_fpointstore = "select * from " . Fahasa_Fpointstore_Helper_Data::FHS_FPOINTSTORE . " where id = :id";
        $fpointstore_result = $connection->fetchRow($query_fpointstore, $query_fpointstore_binds);
        if (empty($fpointstore_result)) {
            return array(
                "result" => false,
                "error_type" => Fahasa_Fpointstore_Helper_Data_Error::NO_ACTIVE_FPOINTSTORE
            );
        }

        /// Query Fpointstore Periods
        $query_period = "select * from " . Fahasa_Fpointstore_Helper_Data::FHS_FPOINTSTORE_PERIOD . " where fpointstore_id = :id";
        $periods_result = $connection->fetchAll($query_period, $query_fpointstore_binds);
        if (empty($periods_result)) {
            return array(
                "result" => false,
                "error_type" => Fahasa_Fpointstore_Helper_Data_Error::NO_PERIODS
            );
        }

        /// Query gifts
        $query_gifts = 
	"select m_result.gift_id, m_result.fpointstore_id, m_result.period_id, m_result.name, m_result.description,
	    m_result.image ,m_result.fpoint, m_result.discount, m_result.expire_date, m_result.partner, m_result.order_limit ,m_result.quatity_total ,ifnull(n_result.quatity_used,0) as 'quatity_used'
	from (
		select fgift.id as 'gift_id', f.id as 'fpointstore_id', fp.id as 'period_id', fgift.name, 
		fgift.Description, fgift.image, fgift.fpoint, fgift.discount, fpc.expire_date, fgift.partner, fgift.order_limit, count(fpc.gift_id) as 'quatity_total'
		from fhs_fpointstore f
		join fhs_fpointstore_period fp on fp.fpointstore_id = f.id and f.active = 1
		join fhs_fpointstore_gift_code fpc on fpc.period_id = fp.id and (fpc.expire_date - INTERVAL fpc.expire_before_day DAY) > now()
		join fhs_fpointstore_gift fgift on fgift.id = fpc.gift_id
		where f.id = :id
		group by fpc.gift_id, fpc.period_id
		order by fpc.period_id, fgift.sort_order
	) m_result
	left join (
		select fgift.id as 'gift_id', f.id as 'fpointstore_id', fp.id as 'period_id', count(fpc.gift_id) as 'quatity_used'
		from fhs_fpointstore f
		join fhs_fpointstore_period fp on fp.fpointstore_id = f.id and f.active = 1
		join fhs_fpointstore_gift_code fpc on fpc.period_id = fp.id and fpc.is_sent = 1 and (fpc.expire_date - INTERVAL fpc.expire_before_day DAY) > now()
		join fhs_fpointstore_gift fgift on fgift.id = fpc.gift_id
		where f.id = :id
		group by fpc.gift_id, fpc.period_id
	) n_result on n_result.fpointstore_id = m_result.fpointstore_id and n_result.gift_id = m_result.gift_id and n_result.period_id = m_result.period_id;";
	
        $gifts_result = $connection->fetchAll($query_gifts, $query_fpointstore_binds);
        if (empty($gifts_result)) {
            return array(
                "result" => false,
                "error_type" => Fahasa_Fpointstore_Helper_Data_Error::NO_GIFTS
            );
        }

        /// Start Redis Connection
        $helper_redis = Mage::helper("fpointstore/redis");
        $redis_client = $helper_redis->createRedisClient();
        if (!$redis_client->isConnected()) {
            return array(
                "result" => false,
                "error_type" => Fahasa_Fpointstore_Helper_Data_Error::NO_CONNECTION
            );
        }

        /*
         *  Delete fpointstore:*
         */
        ///$redis_client->delete($redis_client->keys(FPOINTSTORE_KEY_NAME. ":*"));
        ///$redis_client->delete($redis_client->keys(PERIOD_KEY_NAME. ":*"));
        $redis_client->delete($redis_client->keys("fpointstore:*"));
        $redis_client->delete($redis_client->keys("fpointstore_period:*"));

        /// Parses Results into key => value
        /// Fpointstore
        $fpointstore_key = Fahasa_Fpointstore_Helper_Data::FPOINTSTORE_KEY_NAME . Fahasa_Fpointstore_Helper_Data::SEPERATOR . $fpointstore_id;
        $fpointstore_periods = array();
        foreach ($periods_result as $period) {
            $fpointstore_periods[] = array(
                "period_id" => $period['id'],
                "start_date" => $period['start_date'],
                "end_date" => $period['end_date'],
            );
        }

        $fpointstore_result['periods'] = $fpointstore_periods;
        $fpointstore_json = json_encode($fpointstore_result);

        $redis_client->set($fpointstore_key, $fpointstore_json);

        /// Periods
        $period_key = Fahasa_Fpointstore_Helper_Data::PERIOD_KEY_NAME . Fahasa_Fpointstore_Helper_Data::SEPERATOR;
        $catalog_gift = Mage::helper('fahasa_catalog/product');
        
        foreach ($periods_result as $period) {
            $period_gift_ids = array();
            /*
             *  Store Gifts Data
             */
            foreach ($gifts_result as $gift) {
                if ($gift['period_id'] !== $period['id']) {
                    continue;
                }
		$gift['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$gift['image'];
		$gift['fpoint_str'] = number_format($gift['fpoint'], 0, ",", ".");
                $period_gift_ids[] = $gift['gift_id'];
                $gift_key = $period_key . $period['id'] . Fahasa_Fpointstore_Helper_Data::SEPERATOR
                        . Fahasa_Fpointstore_Helper_Data::GIFT_KEY_NAME
                        . Fahasa_Fpointstore_Helper_Data::SEPERATOR
                        . $gift['gift_id'];
                                
                $gift_json = json_encode($gift);
                $redis_client->set($gift_key, $gift_json);
            }

            $period['gift_ids'] = $period_gift_ids;
            $period['gift_count'] = count($period_gift_ids);
            $period_json = json_encode($period);
            $redis_client->set($period_key . $period['id'], $period_json);
        }

        $redis_client->close();
        return array(
            "result" => true,
            "msg" => "Success!"
        );
    }
    
    //Insert to Queue
    public function insertQueue($fpointstore_id, $period_id, $gift_id, $customer){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$fpointstore_id = $product_helper->cleanBug($fpointstore_id);
	$period_id = $product_helper->cleanBug($period_id);
	$gift_id = $product_helper->cleanBug($gift_id);
	try{
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $binds = array(
		'customer_id' => $customer->getEntityId(),
		'fpointstore_id' => $fpointstore_id,
		'period_id' => $period_id,
		'gift_id' => $gift_id);
	    $sql = "INSERT INTO fhs_fpointstore_queue
		(customer_id, fpointstore_id, period_id, gift_id, created_at)
		VALUES(:customer_id, :fpointstore_id, :period_id, :gift_id, now());
		";
	    $writer->query($sql, $binds);
	    $result = $writer->lastInsertId();
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] insertQueue: customer_id=". $customer->getEntityId().", customer_email=".$customer->getEmail().", fpointstore_id=".$fpointstore_id.", period_id=".$period_id . ", gift_id=".$gift_id.", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
	return $result;
    }
    
    //get Queue
    public function getQueue($gift_queue_id, $customer){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$gift_queue_id = $product_helper->cleanBug($gift_queue_id);
	
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
        $binds = array('id' => $gift_queue_id, 'customer_id' => $customer->getEntityId());
	$sql = "select * from fhs_fpointstore_queue where customer_id = :customer_id and id = :id and status = 1;";
        return $reader->fetchRow($sql, $binds);
    }
    
    //for web and mobile
    public function getGiftByID($fpointstore_id, $period_id, $gift_id){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$fpointstore_id = $product_helper->cleanBug($fpointstore_id);
	$period_id = $product_helper->cleanBug($period_id);
	$gift_id = $product_helper->cleanBug($gift_id);
	
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
        $binds = array('id' => $fpointstore_id, 'period_id' => $period_id, 'fgift' => $gift_id);
	$sql = "select m_result.gift_id, m_result.fpointstore_id, m_result.period_id, m_result.name ,m_result.fpoint ,m_result.quatity ,ifnull(n_result.quatity_used,0) as 'quatity_used'
	from (
		select fgift.id as 'gift_id', f.id as 'fpointstore_id', fp.id as 'period_id', fgift.name, fgift.fpoint , count(fpc.gift_id) as 'quatity'
		from fhs_fpointstore f
		join fhs_fpointstore_period fp on fp.fpointstore_id = f.id and f.active = 1 and fp.start_date < now() and now() < fp.end_date and fp.id = :period_id
		join fhs_fpointstore_gift_code fpc on fpc.period_id = fp.id and (fpc.expire_date - INTERVAL fpc.expire_before_day DAY) > now()
		join fhs_fpointstore_gift fgift on fgift.id = fpc.gift_id and fgift.id = :fgift
		where f.id = :id
		group by fpc.gift_id, fpc.period_id
	) m_result
	left join (
		select fgift.id as 'gift_id', f.id as 'fpointstore_id', fp.id as 'period_id', fgift.name, fgift.fpoint , count(fpc.gift_id) as 'quatity_used'
		from fhs_fpointstore f
		join fhs_fpointstore_period fp on fp.fpointstore_id = f.id and f.active = 1 and fp.start_date < now() and now() < fp.end_date and fp.id = :period_id
		join fhs_fpointstore_gift_code fpc on fpc.period_id = fp.id and fpc.is_sent = 1 and (fpc.expire_date - INTERVAL fpc.expire_before_day DAY) > now()
		join fhs_fpointstore_gift fgift on fgift.id = fpc.gift_id and fgift.id = :fgift
		where f.id = :id
		group by fpc.gift_id, fpc.period_id
	) n_result on n_result.fpointstore_id = m_result.fpointstore_id and n_result.gift_id = m_result.gift_id and n_result.period_id = m_result.period_id";
        return $reader->fetchRow($sql, $binds);
    }
    public function getGiftCodeByID($gift_code_id){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$gift_code_id = $product_helper->cleanBug($gift_code_id);
	
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
        $binds = array('id' => $gift_code_id);
	$sql = "select code from fhs_fpointstore_gift_code where id = :id limit 1";
        return $reader->fetchRow($sql, $binds);
    }
    public function exchangeGift($fpointstore_id, $period_id, $gift_id, $customer){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$fpointstore_id = $product_helper->cleanBug($fpointstore_id);
	$period_id = $product_helper->cleanBug($period_id);
	$gift_id = $product_helper->cleanBug($gift_id);
	
	try{
	    $gift = $this->getGiftCode($fpointstore_id, $period_id, $gift_id);
	    if($gift){
		if($this->setGiftCodeUsed($gift)){
		    $result = $gift;
		}
	    }
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] exchangeGift: gift_id=". $gift_id . ", customer id=".$customer->getEntityId().", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
	return $result;
    }
    
    //protected in local
    protected function getGiftCode($fpointstore_id, $period_id, $gift_id){
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
        $binds = array('id' => $fpointstore_id, 'period_id' => $period_id, 'fgift' => $gift_id);
	$sql = "select fgift.id as 'gift_id', f.id as 'fpointstore_id', fp.id as 'period_id', fgift.name, fgift.fpoint, fgift.discount, fgift.order_limit , fpc.id as 'code_id', fpc.code, fpc.expire_date, fpc.is_sent  
	from fhs_fpointstore f
	join fhs_fpointstore_period fp on fp.fpointstore_id = f.id and f.active = 1 and fp.start_date < now() and now() < fp.end_date  and fp.id = :period_id
	join fhs_fpointstore_gift_code fpc on fpc.period_id = fp.id and fpc.is_sent = 0 and (fpc.expire_date - INTERVAL fpc.expire_before_day DAY) > now()
	join fhs_fpointstore_gift fgift on fgift.id = fpc.gift_id and fgift.id = :fgift
	where f.id = :id
	limit 1";
        return $reader->fetchRow($sql, $binds);
    }
    protected function setGiftCodeUsed($gift){
	$result = false;
	try {
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $binds = array(
		'used' => 1,
		'id' => $gift['code_id']
	    );
	    $sql = "Update fhs_fpointstore_gift_code SET is_sent = :used where id = :id";
	    $writer->query($sql, $binds);
	    
	    if($this->checkGiftCodeIsUsed($gift['code_id'])){
		return false;
	    }
	    //customer fpoint update
	    $customer = $this->_getCustomerSession()->getCustomer();
	    $fpoint_before = Mage::helper('tryout')->determinetryout();
	    $fpoint_after = $fpoint_before - floatval($gift['fpoint']);
	    if($this->updateCustomerFpoint($fpoint_after, $customer)){
		$this->updateQuatityOnRedis($gift['period_id'],$gift['gift_id']);
		$this->insertGiftCustomerLog($gift, $customer);
		$this->insertCustomerLog($fpoint_before, $fpoint_after, $gift, $customer);
		$this->insertCustomeNotification($fpoint_before, $fpoint_after, $gift, $customer);
		$result = true;
	    }
	    else{
		$binds = array(
		    'used' => 0,
		    'id' => $gift['code_id']
		);
		$writer->query($sql, $binds);
		$result = false;
	    }
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] setGiftCodeUsed: code_id=". $code_id . ", set used=".$result.", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
	return $result;
    }
    protected function checkGiftCodeIsUsed($gift_code_id){
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
        $binds = array('code_id' => $gift_code_id);
	$sql = "select * from fhs_fpointstore_customer_log where gift_code_id = :code_id";
        return $reader->fetchRow($sql, $binds);
    }
    protected function insertCustomerLog($fpoint_before, $fpoint_after, $gift, $customer){
	$result = false;
	try{
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $binds = array(
		'account' => $customer->getEmail(),
		'customer_id' => $customer->getEntityId(),
		'value' => $gift['fpoint'],
		'amountAfter' => $fpoint_after,
		'amountBefore' => $fpoint_before,
		'product_id' => $gift['code_id']);
	    $sql = "INSERT INTO fhs_purchase_action_log (account, customer_id, `action`, value, amountAfter, updateBy, lastUpdated, amountBefore, product_id, `type`) 
		    VALUES(:account, :customer_id, 'Exchange Voucher with F-point', :value, :amountAfter, 'magento', now(), :amountBefore, :product_id, 'fpoint')";
	    $writer->query($sql, $binds);
	    $result = true;
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] insertCustomerLog: customer_id=". $customer->getEntityId().", customer_email=".$customer->getEmail().", fpoint_before=".$fpoint_before.", fpoint_after=".$fpoint_after . ", gift_code_id=".$gift_code_id.", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
	return $result;
    }
    protected function insertGiftCustomerLog($gift, $customer){
	$result = false;
	try{
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $binds = array(
		'customer_id' => $customer->getEntityId(),
		'gift_code_id' => $gift['code_id']);
	    $sql = "INSERT INTO fhs_fpointstore_customer_log (customer_id, gift_code_id, created_at) 
		    VALUES(:customer_id, :gift_code_id, now())";
	    $writer->query($sql, $binds);
	    $result = true;
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] insertGiftCustomerLog: customer_id=". $customer->getEntityId().", customer_email=".$customer->getEmail().", gift_code_id=".$gift_code_id.", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
	return $result;
    }
    protected function insertCustomeNotification($fpoint_before, $fpoint_after, $gift, $customer){
	$result = false;
	try{
	    $symbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
	    $title = "Bạn vừa đổi voucher thành công!";
	    $content = $gift['name']." có mã là ".$gift['code']." giảm thêm ".$gift['discount']." cho đơn hàng từ ".number_format($gift['order_limit'], 0, ",", ".").$symbol.". Hạn sử dụng đến hết ngày ".date('d/m/Y',strtotime($gift['expire_date']));
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $binds = array(
		'title' => $title,
		'content' => $content,
		'customer_id' => $customer->getEntityId());
	    $sql = "INSERT INTO fhs_mobile_notification (title, content, customer_id, created_by, created_at, seen_status, page_type, page_value) 
		    VALUES(:title, :content, :customer_id, 'magento', now(), 0, 'event', 'fpointstore')";
	    $writer->query($sql, $binds);
	    $result = true;
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] insertCustomeNotification: customer_id=". $customer->getEntityId().", customer_email=".$customer->getEmail().", fpoint_before=".$fpoint_before.", fpoint_after=".$fpoint_after . ", gift_code_id=".$gift_code_id.", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
	return $result;
    }
    protected function updateCustomerFpoint($fpoint_after, $customer){
	$result = false;
	try{
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $binds = array('entity_id' => $customer->getEntityId(), 'fpoint'=> $fpoint_after);
	    $sql = "Update fhs_customer_entity SET fpoint = :fpoint where entity_id = :entity_id";
	    $writer->query($sql, $binds);
	    $result = true;
	} catch (Exception $ex) {
	    Mage::log("***[ERROR] updateCustomerFpoint: customer_id=". $customer->getEntityId() .", customer_email=".$customer->getEmail().", fpoint_final=".$fpoint_final.", message:".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
	return $result;
    }

    //update redis
    protected function updateQuatityOnRedis($period_id, $gift_id){
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
        $binds = array('period_id' => $period_id, 'fgift' => $gift_id);
	$sql = "select fgift.id as 'gift_id', fp.id as 'period_id', fgift.Name, fgift.fpoint , count(fpc.gift_id) as 'quatity_used'
	    from fhs_fpointstore_period fp
	    join fhs_fpointstore_gift_code fpc on fpc.period_id = fp.id and fpc.is_sent = 1 and (fpc.expire_date - INTERVAL fpc.expire_before_day DAY) > now()
	    join fhs_fpointstore_gift fgift on fgift.id = fpc.gift_id and fgift.id = :fgift
	    where fp.id = :period_id
	    group by fpc.gift_id";
        $gift = $reader->fetchRow($sql, $binds);
	if($gift){
	    $this->setGiftExChanged($period_id, $gift_id, $gift['quatity_used']);
	}
    }
    protected function setGiftExChanged($period_id, $gift_id, $quatity_used) {
        $redis_client = Mage::helper("fpointstore/redis")->createRedisClient();

	try{
	    $gift_key = Fahasa_Fpointstore_Helper_Data::PERIOD_KEY_NAME
		    . Fahasa_Fpointstore_Helper_Data::SEPERATOR
		    . $period_id
		    . Fahasa_Fpointstore_Helper_Data::SEPERATOR
		    . Fahasa_Fpointstore_Helper_Data::GIFT_KEY_NAME . Fahasa_Fpointstore_Helper_Data::SEPERATOR;

	    $gift_str = $redis_client->get($gift_key . $gift_id);
	    $gift = json_decode($gift_str, true);
	    if($gift){
		$gift['quatity_used'] = $quatity_used;
		$gift_json = json_encode($gift);
		$redis_client->set($gift_key . $gift_id, $gift_json);
	    }
	} catch (Exception $ex) {
	    Mage::log("***[ERROR][REDIS] setGiftExChanged: gift_id=" . $gift_id. ", quatity_used=".$quatity_used. ", message=".$ex->getMessage(), Zend_Log::ERR, "fpointstore.log");
	}
        $redis_client->close();
    }
    
    //common
    protected function _getCustomerSession() {
        return Mage::getSingleton('customer/session');
    }
}
