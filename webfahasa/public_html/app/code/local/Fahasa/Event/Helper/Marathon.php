<?php

class Fahasa_Event_Helper_Marathon extends Mage_Core_Helper_Abstract {
    
    const MARATHON_MAX_CUSTOMERS = 10000;
    const MARATHON_LIST_KEY_NAME = "event:marathon:list";
    const MARATHON_DATA_KEY_NAME = "event:marathon:data";
    
    const MARATHON_2_RANK_KEY_NAME = "event:marathon:rank";
    const MARATHON_2_DATA_KEY_NAME = "event:marathon:data";
    const MARATHON_2_RANK_MAX_LIST = 100;
    
    /*
     *  Marathon 1: Get Top Customers with most orders
     */
    function calculateTopCustomers(){
        
        $from_date = Mage::getStoreConfig('event_marathon/config/from_date');
        $to_date = Mage::getStoreConfig('event_marathon/config/to_date');
        $complete_date = Mage::getStoreConfig('event_marathon/config/complete_date');
        
        // Query Top Customers from mysql
        $query_sql = "SELECT all_orders.customer_id, all_orders.customer_firstname, all_orders.customer_lastname, all_orders.telephone,
            count(all_orders.increment_id) AS 'total_orders'
            FROM (
            SELECT fo.customer_id, fo.customer_firstname, fo.customer_lastname, ce.telephone, fo.increment_id, fo.created_at
            FROM fhs_sales_flat_order fo
            JOIN fahasa_suborder so ON so.order_id = fo.increment_id
            JOIN fhs_customer_entity ce ON ce.entity_id = fo.customer_id
            WHERE convert_tz(fo.created_at, '+0:00', '+7:00') between :from_date and :to_date 
            AND fo.customer_id IS NOT NULL
            AND fo.status = 'complete' AND so.status = 'complete' AND fo.don_si IS NULL
            GROUP BY fo.entity_id
            ) all_orders
            GROUP BY all_orders.customer_id 
            ORDER BY total_orders DESC, all_orders.created_at ASC LIMIT 10000;";
        
        $query_binding = array(
            'from_date' => $from_date,
            'to_date' => $to_date
        );
        
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $top_customers = $connection->fetchAll($query_sql, $query_binding);
        
        // Copy To Redis
        $helper_redis = Mage::helper("flashsale/redis");
        $redis_client = $helper_redis->createRedisClient();
        if (!$redis_client->isConnected()) {
            return array(
                "result" => false,
                "msg" => "Can't connect to Redis."
            );
        }
        
        /// Sql Result to array
        $customer_list = array();
        $count = 0;
        $top_ten = array();
        foreach($top_customers as $customer){
            $masked_phone = substr($customer['telephone'], 0,6) . "*****";
            
            $item = array(
                'name' => $customer['customer_lastname'] . " " . $customer['customer_firstname'],
                'phone' => $masked_phone,
                'total_orders' => $customer['total_orders'],
                'rank' => $count
            );
            
            $customer_list[$customer['customer_id']] = json_encode($item);
            
            if($count < 10){
                $top_ten[] = $item;
            }
            
            $count++;
        }
        
        $redis_client->delete($redis_client->keys("event:marathon:*"));
        $redis_client->hMSet(self::MARATHON_LIST_KEY_NAME, $customer_list);
        
        $time_zone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $now_date_time = new DateTime();
        $now_date_time->setTimezone($time_zone);
        $to_date_time = new DateTime($to_date);
        $complete_date_time = new DateTime($complete_date);
        
        $top_customers_json = json_encode($top_ten);
        $data = array(
            'new_list' => $top_customers_json,
            'update_at' => $now_date_time->format('H:i d/m'),
            'end_date' => $to_date_time->format('Y/m/d H:i:s'),
            'complete_date' => $complete_date_time->format('Y/m/d H:i:s'),
            //// 2020/10/10 12:34:56
        );
        
        $redis_client->hMSet(self::MARATHON_DATA_KEY_NAME, $data);
        $redis_client->close();
        
        return array(
            "result" => true,
            "msg" => "Done ! ". $count . " Customers"
        );
    }

    /*
     *  Event Marathon 2: 
     */
    function updateMarathon2(){
        
        $ype = Mage::getStoreConfig('event_marathon_2/config/is_active_type');
        $listRuleRefer = Mage::getStoreConfig("customerregister/refer/listrule");
        $rank_json = Mage::getStoreConfig('event_marathon_2/config/ranks');
        $rank_limit_json = Mage::getStoreConfig('event_marathon_2/config/ranks_limit');
        $max_ordertotal = Mage::getStoreConfig('event_marathon_2/config/max_ordertotal');
        $from_date = Mage::getStoreConfig('event_marathon_2/config/from_date');
        $to_date = Mage::getStoreConfig('event_marathon_2/config/to_date');
        $exlude = Mage::getStoreConfig('event_marathon_2/config/exlude');
        $dem = 0;
	if(!$exlude){
	    $exlude  = "''";
	}
	$max_customers = self::MARATHON_MAX_CUSTOMERS;
	if($rank_limit_json){
	    $max_customers = 0;
	    $ranks_limit = json_decode($rank_limit_json);
	    foreach($ranks_limit as $rank_limit){
		$max_customers += $rank_limit;
	    } 
	}
        $ranks = json_decode($rank_json);
	if($ranks[0]){
	    $min_ordertotal = $ranks[0];
	}
        /*
         * Get all orders between a period.
         */
        $query_sql = "select order_result.*
	    from (
		    SELECT all_orders.customer_id, CONCAT(all_orders.customer_lastname,' ', all_orders.customer_firstname) as 'name' 
		, sum(all_orders.grand_total) AS 'sum_grand_total', count(all_orders.entity_id) as 'order_count', all_orders.customer_email
		FROM (
			SELECT fo.entity_id, fo.customer_id, fo.customer_lastname, fo.customer_firstname, fo.grand_total, fo.customer_email
			FROM fhs_sales_flat_order fo
			JOIN fahasa_suborder so ON so.order_id = fo.increment_id
			WHERE convert_tz(fo.created_at, '+0:00', '+7:00') between :from_date and :to_date
			AND fo.customer_id IS NOT NULL 
			AND fo.customer_id NOT IN (".$exlude.")
			AND fo.status = 'complete' AND so.status = 'complete' AND fo.don_si IS NULL
			GROUP BY fo.entity_id
		) all_orders
		GROUP BY all_orders.customer_id 
	    ) order_result
	    WHERE order_result.sum_grand_total <= ".$max_ordertotal."
	    AND order_result.sum_grand_total >= ".$min_ordertotal."
	    ORDER BY order_result.sum_grand_total DESC 
	    LIMIT ". $max_customers .";";
        
        $query_sql2 = "select order_result.*
            from (
                    select all_orders_refer_code.customer_id_of_refer_code as customer_id,
                        CONCAT(all_orders_refer_code.last_name,
                        ' ',
                        all_orders_refer_code.first_name) as 'name' ,
                        sum(all_orders_refer_code.grand_total) AS 'sum_grand_total',
                        count(all_orders_refer_code.entity_id) as 'order_count',
                        all_orders_refer_code.customer_email
                    from (
			select fo.grand_total, fo.status, fo.entity_id, rule.rule_id, rule.code, rule.email as customer_email, rule.last_name, rule.first_name,rule.customer_id_of_refer_code
			from fhs_sales_flat_order as fo
			JOIN fahasa_suborder on fahasa_suborder.order_id = fo.increment_id
			join (
				select cp.rule_id, cp.code, cus.email, cus.refer_code, cus.entity_id as 'customer_id_of_refer_code', last_name.value as 'last_name', first_name.value as 'first_name'
				from fhs_customer_entity as cus
				left join fhs_customer_entity_varchar last_name on last_name.entity_id = cus.entity_id and last_name.attribute_id = 7
				left join fhs_customer_entity_varchar first_name on first_name.entity_id = cus.entity_id and first_name.attribute_id = 5
				join fhs_salesrule_coupon cp on cp.code = cus.refer_code
			) rule on fo.coupon_code = rule.code
			where rule.rule_id in (".$listRuleRefer.")
                        AND convert_tz(fo.created_at,
				'+0:00',
				'+7:00') between :from_date and :to_date
			AND fo.customer_id IS NOT NULL
			AND fo.status = 'complete'
			AND fahasa_suborder.status = 'complete'
			AND fo.don_si IS NULL
                        AND rule.customer_id_of_refer_code NOT IN (".$exlude.")
			GROUP by fo.entity_id
		) all_orders_refer_code
                    GROUP by all_orders_refer_code.customer_id_of_refer_code
            ) order_result
                WHERE order_result.sum_grand_total <= ".$max_ordertotal." 
                AND order_result.sum_grand_total >= ".$min_ordertotal."
                ORDER BY order_result.sum_grand_total DESC	
                LIMIT ". $max_customers .";";
       
        $query_binding = array(
            'from_date' => $from_date,
            'to_date' => $to_date
        );
        
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        
        switch ($ype) {
            case "order":
                $all_customers = $connection->fetchAll($query_sql, $query_binding);
                break;
            case "orderByReferCode":
                $all_customers = $connection->fetchAll($query_sql2, $query_binding);
                break;
            default:
                break;
        }
        
        $customers_by_rank = array();
        $rank_counters = array();
        $customer_rank_count = array();
	foreach($ranks as $rank){
	    array_push($customer_rank_count, 0);
	}
        
        /// $map_id_to_customers store is a map of (customer_id => customer data);
        $map_id_to_customers = array();
        
        $sym = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
        rsort($ranks);
        foreach($all_customers as $customer){
            // Find customer rank ( Ex: rank 1 - 1M VND , rank 2 - 2M VND, rank 3 - 3,000,000 VND )
            $temp_rank_index = -1;
            $i = sizeof($ranks) - 1;
	    
	    if($ranks_limit){
		foreach($ranks as $rank){
		    if($temp_rank_index == -1){
			if($customer['sum_grand_total'] >= $rank){
			    $x = $i;
			    while($x >= 0){
				if($customer_rank_count[$x] < $ranks_limit[$x]){
				    $temp_rank_index = $x;
				    $customer_rank_count[$x]++;
				    $x = -1;
				}
				else{
				    $x--;
				}
			    }
			}
			$i--;
		    }
		}
	    }
	    else{
		foreach($ranks as $rank){
		    if($customer['sum_grand_total'] >= $rank){
			$temp_rank_index = $i;
		    }
		    $i--;
		}
	    }
            
            $customer_data = array(
              'customer_id' => $customer['customer_id'],
              'name' => $customer['name'],
              'rank' => $temp_rank_index,
              'sum_grand_total' => $customer['sum_grand_total'],
              'order_count' => $customer['order_count']
            );
            
            /// This array contains a map of id <-> $customer_data
            /// It's used to show indivisual customer data
            $map_id_to_customers[$customer['customer_id']] = json_encode($customer_data);
            
            /*
             *  Calculate Marathon List, putting customers into an appropriate list
             */
            if($temp_rank_index >= 0){
                if(!$customers_by_rank[$temp_rank_index]){
                    $customers_by_rank[$temp_rank_index] = array();
                }
                
                if(!$rank_counters[$temp_rank_index]){
                    $rank_counters[$temp_rank_index] = 0;
                }
                
		if(!$ranks_limit){
		    if($rank_counters[$temp_rank_index] <= self::MARATHON_2_RANK_MAX_LIST){
			$sum_grand_total = number_format((int)$customer['sum_grand_total'],0, ",", "."). " ". $sym;
                        if ((strlen((string) $sum_grand_total) - 10) > -1) { // hang don vi tram nghin thi` 1.XXX.000
                            $sum_grand_total = substr_replace($sum_grand_total, "xxx", strlen((string) $sum_grand_total) - 10, 3);
                            $customer_data['sum_grand_total'] = $sum_grand_total;
                        }else{
                            if ((strlen((string) $sum_grand_total) - 10) == -1) {// hang chuc nghin xx.000
                                $sum_grand_total = substr_replace($sum_grand_total, "xx", strlen((string) $sum_grand_total) - 9, 2);
                                $customer_data['sum_grand_total'] = $sum_grand_total;
                            }else{
                                $customer_data['sum_grand_total'] = $sum_grand_total;
                            }
                        }
                        $customers_by_rank[$temp_rank_index][$customer['customer_id']] = json_encode($customer_data);
			$rank_counters[$temp_rank_index] += 1;
		    }
		}
		else{
		    if($rank_counters[$temp_rank_index] <= $ranks_limit[0]){
			$sum_grand_total = number_format((int)$customer['sum_grand_total'],0, ",", "."). " ". $sym;
			if ((strlen((string) $sum_grand_total) - 10) > -1) { // hang don vi tram nghin thi` 1.XXX.000
                            $sum_grand_total = substr_replace($sum_grand_total, "xxx", strlen((string) $sum_grand_total) - 10, 3);
                            $customer_data['sum_grand_total'] = $sum_grand_total;
                        }else{
                            if ((strlen((string) $sum_grand_total) - 10) == -1) {// hang chuc nghin xx.000
                                $sum_grand_total = substr_replace($sum_grand_total, "xx", strlen((string) $sum_grand_total) - 9, 2);
                                $customer_data['sum_grand_total'] = $sum_grand_total;
                            } else {
                                $customer_data['sum_grand_total'] = $sum_grand_total;
                            }
                        }
			$customers_by_rank[$temp_rank_index][$customer['customer_id']] = json_encode($customer_data);
			$rank_counters[$temp_rank_index] += 1;
		    }
		}
            }
        }
        
        /*
         *  Store ranking data
         */
        $helper_redis = Mage::helper("flashsale/redis");
        $redis_client = $helper_redis->createRedisClient();
        if (!$redis_client->isConnected()) {
            return array(
                "result" => false,
                "msg" => "Can't connect to Redis."
            );
        }
        
        $redis_client->delete($redis_client->keys("event:marathon:*"));
        
        $i = 1;
        ksort($customers_by_rank);
        foreach($customers_by_rank as $rank_list){
            $a = $rank_list;
            $redis_client->hMSet(self::MARATHON_2_RANK_KEY_NAME . ":" . $i, $rank_list);
            $i++;
        }
        
        $redis_client->hMSet(self::MARATHON_2_DATA_KEY_NAME, $map_id_to_customers);
        $redis_client->close();
        
        $count = count($map_id_to_customers);
	if($ranks_limit){
	    $msg = "Done ! There are ". $count . " customers in marathon event. (MAX: ". $max_customers. " customers to be considered) - Max sum order total: ".$max_ordertotal.", Min sum order total: ".$min_ordertotal;
	}else{
	    $msg = "Done ! There are ". $count . " customers in marathon event. (MAX: ". self::MARATHON_MAX_CUSTOMERS. " customers to be considered)";
	}
        return array(
            "result" => true,
            "msg" => $msg
        );
    }
}
