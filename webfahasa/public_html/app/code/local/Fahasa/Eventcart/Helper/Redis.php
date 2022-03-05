<?php


class Fahasa_Eventcart_Helper_Redis extends Mage_Core_Helper_Abstract {
    
    const SEPERATOR = ":";
    const EVENTCART_FILTER = "eventcart_filter";
    
    public function copyDataFromMysqlToRedis(){
        $helper = Mage::helper("eventcart");
        $events = $helper->getActiveEvents();
        
        if (count($events) == 0){
            return array(
                "success" => false,
                "message" => "NO_ACTIVE_EVENT"
            );
        }
        
        $filter_ids_data = [];
        foreach ($events as $event){
            $filters = $event["filters"];
            foreach ($filters as $filter){
                //only get filter_id which has in fhs_cart_event_filter_value
                $conditions = array_filter($filter['conditions'], function ($value, $key){
                    return $value["min_value"] == null && $value["max_value"] == null;
                });
                
                foreach ($conditions as $condition){
                    $filter_ids_data[] = $condition["filter_id"];
                }
            }
        }
        
        $filter_ids_data = array_unique($filter_ids_data);
        $filter_ids_string = implode(",", $filter_ids_data);
        $filter_value_query = "select fv.*, cf.type as filter_type, cf.error_message from fhs_event_cart_filter_value fv "
                . " join fhs_event_cart_filter cf on fv.filter_id = cf.id "
                . "where filter_id in ({$filter_ids_string}) "
                . "group by filter_id, value;";
                
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");

        $valueRs = $readConnection->fetchAll($filter_value_query);
        if (empty($valueRs)){
            return array(
                "success" => false,
                "message" => "NO_FILTER"
            );
        }
        
        $helper_redis = Mage::helper("flashsale/redis");
        $redis_client = $helper_redis->createRedisClientEventCart();
        
        if (!$redis_client->isConnected()){
            return array(
                "success" => false,
                "message" => "no_connection"
            );
        }
        
        //delete event_cart_filter
        $redis_client->delete($redis_client->keys(Fahasa_Eventcart_Helper_Redis::EVENTCART_FILTER . "*"));
        
        $filterArr = array();
        foreach($valueRs as $item){
            if ($filterArr[$item["filter_id"]] == null){
                $filterArr[$item["filter_id"]]["filter_id"] = $item["filter_id"];
                $filterArr[$item["filter_id"]]["filter_type"] = $item["filter_type"];
                $filterArr[$item["filter_id"]]["error_message"] = $item["error_message"];
            }
            $filterArr[$item["filter_id"]]["values"][] = $item["value"];
        }
        
        foreach($filterArr as $item){
            $filterKey = Fahasa_Eventcart_Helper_Redis::EVENTCART_FILTER . Fahasa_Eventcart_Helper_Redis::SEPERATOR . $item["filter_id"];
            $value = array(
                "filter_id" => $item["filter_id"],
                "filter_type" => $item["filter_type"],
                "error_message" => $item["error_message"],
                "values" => $item["values"]
            );
            $redis_client->set($filterKey, json_encode($value));
        }
        
        $redis_client->close();
        return array(
            "result" => true,
            "msg" => "Success!"
        );
    }
}