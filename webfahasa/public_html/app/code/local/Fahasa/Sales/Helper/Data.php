<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Sales
 * @copyright  Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales module base helper
 *
 * @category    Fahasa
 * @package     Fahasa_Sales
 * @author      khuonglt
 */
class Fahasa_Sales_Helper_Data extends Mage_Sales_Helper_Data
{
    public function  getOrderLogOrderInfo($_order){
        /* 
        * function dang duoc su dung cho ca app va web 
        *          
        */
        $result = array();
        
        if($_order->getStatus() == "complete" && $_order->getState() == "complete"){
            if(($_order->getCompleteTimestamp() != null) && ($_order->getCompleteTimestamp() != "0000-00-00 00:00:00")){
                $order_log_item0->date = date('d/m/Y - H:i',strtotime($_order->getCompleteTimestamp())); 
                $order_log_item0->status = $this->__('Order completed.');
                $result["complete"] = $order_log_item0;
            }
        }
        
        if ($_order->getStatus() == 'canceled'){
            if(($_order->getCompleteTimestamp() != null) && ($_order->getCompleteTimestamp() != "0000-00-00 00:00:00")){
                $order_log_item_cancel->date = date('d/m/Y - H:i',strtotime($_order->getCompleteTimestamp())); 
                $order_log_item_cancel->status = $this->__('Order canceled.');
                $result["canceled"] = $order_log_item_cancel;
            }
        }
        
        if(($_order->getConfirmedTimestamp() != null) && ($_order->getConfirmedTimestamp() != "0000-00-00 00:00:00")){
            $order_log_item1->date = date('d/m/Y - H:i',strtotime($_order->getConfirmedTimestamp())); 
            $order_log_item1->status = $this->__('Order confirmed and processing.');
            $result["processing"] = $order_log_item1;
        }
        
        $order_log_item2->date = date('d/m/Y - H:i',strtotime('+7 hour',strtotime($_order->getCreatedAt()))); 
        $order_log_item2->status = $this->__('Created new Order.');
        $result["created"] = $order_log_item2;
        return $result;
    }
    
    public function getTrackingSubOrderLogFhs($_suborder){
        /* LUU Y ;
         * ----  function dang duoc su dung cho ca app va web 
         * --- Data param cua Desktop nhieu hon .
         * --- Data param cua Mobile chi co :
         * $dataArr = array(
            'created_at' => null,
            'confirmSubOrder_timestamp' => null,
            'packSubOrder_timestamp' => null,
            'deliverSubOrder_timestamp' => null,
            'completeSubOrder_timestamp' => null,
            'status_timestamp' => null,
            'status' => null,
        ); => khi handle o Desktop thi nen luu y
         */
        if($_suborder["status"] == "complete" && $_suborder["completeSubOrder_timestamp"] != null){
            $complete_timestamp = $_suborder["completeSubOrder_timestamp"];
        }
        else if($_suborder["status"] == "complete" && $_suborder["completeSubOrder_timestamp"] == null){
            $complete_timestamp = $_suborder["status_timestamp"];
        }
        else if($_suborder["status"] == "canceled"){
            $complete_timestamp = $_suborder["status_timestamp"];
        }
        if($_suborder["deliverSubOrder_timestamp"] != null){
            $deliverSubOrder_timestamp = $_suborder["deliverSubOrder_timestamp"];
        }
        if($_suborder["packSubOrder_timestamp"] != null){
            $packSubOrder_timestamp = $_suborder["packSubOrder_timestamp"];
        }
        if($_suborder["confirmSubOrder_timestamp"] != null){
            $confirmSubOrder_timestamp = $_suborder["confirmSubOrder_timestamp"];
        }else{
            $confirmSubOrder_timestamp = $_suborder["created_at"];
        }
        //Add TimeZone
        if($complete_timestamp != "")$complete_timestamp = date('d/m/Y - H:i',strtotime($complete_timestamp)); 
        if($deliverSubOrder_timestamp != "")$deliverSubOrder_timestamp = date('d/m/Y - H:i',strtotime($deliverSubOrder_timestamp)); 
        if($packSubOrder_timestamp != "")$packSubOrder_timestamp = date('d/m/Y - H:i',strtotime($packSubOrder_timestamp)); 
        if($confirmSubOrder_timestamp != "")$confirmSubOrder_timestamp = date('d/m/Y - H:i',strtotime($confirmSubOrder_timestamp)); 
        
        $dataTrackLog['processing'] = $confirmSubOrder_timestamp;
        $dataTrackLog['pack'] = $packSubOrder_timestamp;
        $dataTrackLog['deliver'] = $deliverSubOrder_timestamp;
        //TH : compelete van khong co data => giao hang that bai;
        if ($complete_timestamp) {
            $dataTrackLog['complete'] = $complete_timestamp;
        }else{
//            if($_suborder["status"] == "canceled"
//           ||  $_suborder["status"] == "ebiz_returned" ||
//               $_suborder["status"] == "delivery_failed" ||
//               $_suborder["status"] == "delivery_returned" ||
//               $_suborder["status"] == "permanent_no_stock" ||
//               $_suborder["status"] == "returning" ||
//               $_suborder["status"] == "returned"
//             ){
//                $dataTrackLog['failure'] = $this->__('Failure');
//            }else{
                $dataTrackLog['notshow'] = "";
//            }
        }
        return $dataTrackLog;
    }
    
    public function getTrackingSubOrderLogShowFhsV2($_suborder){
        
        if($_suborder["status"] == "complete" && $_suborder["completeSubOrder_timestamp"] != null){
            $complete_timestamp = $_suborder["completeSubOrder_timestamp"];
        }
        else if($_suborder["status"] == "complete" && $_suborder["completeSubOrder_timestamp"] == null){
            $complete_timestamp = $_suborder["status_timestamp"];
        }
        else if($_suborder["status"] == "canceled"){
            $complete_timestamp = $_suborder["status_timestamp"];
        }
        if($_suborder["deliverSubOrder_timestamp"] != null){
            $deliverSubOrder_timestamp = $_suborder["deliverSubOrder_timestamp"];
        }
        if($_suborder["packSubOrder_timestamp"] != null){
            $packSubOrder_timestamp = $_suborder["packSubOrder_timestamp"];
        }
        if($_suborder["confirmSubOrder_timestamp"] != null){
            $confirmSubOrder_timestamp = $_suborder["confirmSubOrder_timestamp"];
        }else{
            $confirmSubOrder_timestamp = $_suborder["created_at"];
        }
        
        $showProcessing = false;
        $showPack = false;
        $showDelivery = false;
        
        //Add TimeZone
        if($confirmSubOrder_timestamp != ""){$confirmSubOrder_timestamp = date('d/m/Y - H:i',strtotime($confirmSubOrder_timestamp));$showProcessing=true;$index = 1;}
        else if($_suborder["status"] == 'confirmed'){
            $showProcessing=true;
            $index = 1;
        }
        if($packSubOrder_timestamp != ""){$packSubOrder_timestamp = date('d/m/Y - H:i',strtotime($packSubOrder_timestamp));$showPack=true;$index = 2; }
        else if($_suborder["status"] == 'packed'){
            $showPack=true;
            $index = 2;
        }
        if($deliverSubOrder_timestamp != ""){
            $deliverSubOrder_timestamp = date('d/m/Y - H:i',strtotime($deliverSubOrder_timestamp));$showDelivery=true;$index = 3; 
        }else if($_suborder["status"] == 'delivering' || $_suborder["status"] == 'delivery_failed' || $_suborder["status"] == 'delivery_returned' || $_suborder["status"] == 'ebiz_returned' ){
            $showDelivery=true;
            $index = 3;
        }
        if($complete_timestamp != ""){$complete_timestamp = date('d/m/Y - H:i',strtotime($complete_timestamp));$index = 4;}
        
        $dataTrackLog['processing']['timestamp'] = $confirmSubOrder_timestamp;
        $dataTrackLog['pack']['timestamp'] = $packSubOrder_timestamp;
        $dataTrackLog['deliver']['timestamp'] = $deliverSubOrder_timestamp;
        
        $dataTrackLog['processing']['show'] = $showProcessing;
        $dataTrackLog['pack']['show'] = $showPack;
        $dataTrackLog['deliver']['show'] = $showDelivery;
        
        // set index 
        $dataTrackLog['processing']['index'] = $index;
        $dataTrackLog['pack']['index'] = $index;
        $dataTrackLog['deliver']['index'] = $index;

        //TH : compelete van khong co data => giao hang that bai;
        if ($complete_timestamp) {
            $dataTrackLog['complete']['timestamp'] = $complete_timestamp;
            $dataTrackLog['complete']['show'] = true;
            $dataTrackLog['complete']['index'] = $index;
        }else{
            $dataTrackLog['complete']['timestamp'] = null;
            $dataTrackLog['complete']['show'] = false;
            $dataTrackLog['complete']['index'] = $index;
        }
        return $dataTrackLog;
    }
    public function getOrdersOptionRule($order_ids){
	$result = array();
	$order_ids_str = '';
	
	if(is_array($order_ids)){
	    foreach ($order_ids as $order_id){
		$result[$order_id] = array('hide_total'=>false,'hide_shipping_fee'=>false);
		
		if(!empty($order_ids_str)){
		    $order_ids_str .= "','";
		}
		$order_ids_str .= $order_id;
	    }
	}else{
	    $result[$order_ids] = array('hide_total'=>false,'hide_shipping_fee'=>false);
	    $order_ids_str = $order_ids;
	}
	if(empty($order_ids_str)){return null;}
	
	if(!empty(Mage::registry('order_options_rule_'.$order_ids_str))) {
	    return Mage::registry('order_options_rule_'.$order_ids_str);
	}
	
	try{
	    $read = Mage::getSingleton('core/resource')->getConnection('core_read');
	    
	    if(Mage::getStoreConfig('fahasa_sales/sgk/is_check_rule')){
		$sql = "select o.increment_id , o.status, o.shipping_incl_tax, o.customer_id ,
			p.require_shipping_fee , p.require_item_total 
			from fahasa_wholesale_project p 
			join fahasa_wholesale_project_order po on po.project_code = p.project_code 
			join fhs_sales_flat_order o on o.increment_id = po.order_id 
			left join fahasa_suborder so on so.order_id = o.increment_id 
			where o.increment_id in ('".$order_ids_str."') 
			order by so.status;";

		$data_result = $read->fetchAll($sql);
		foreach($data_result as $item){
		    if(!$item['require_item_total']){
			$result[$item['increment_id']]['hide_total'] = true;
		    }
		    if(!$item['require_shipping_fee']){
			$result[$item['increment_id']]['hide_shipping_fee'] = true;
		    }
		}
	    }
	    
	    if(!is_array($order_ids)){
		if(Mage::getStoreConfig('fahasa_sales/delivery/is_show_tracking_link')){
		    $sql = "select o.increment_id, so.suborder_id, 
			    case dp.id
				when 2 then concat(dp.tracking_url, so.suborder_id, so.order_id) 
				when 3 then concat(dp.tracking_url, so.delivery_id)
				when 4 then concat(dp.tracking_url, so.delivery_id)
				when 5 then concat(dp.tracking_url, so.delivery_id)
				when 6 then concat(dp.tracking_url, so.delivery_id)
				when 7 then concat(dp.tracking_url, so.delivery_id)
				when 8 then concat(dp.tracking_url, so.delivery_id)
				when 10 then REPLACE(REPLACE(REPLACE(dp.tracking_url, '{trackingId}', concat('FHS',so.suborder_id)), '{endTime}', curdate()), '{startTime}', curdate() - interval 3 week)
				when 12 then concat(dp.tracking_url, so.delivery_id)
				when 16 then concat(dp.tracking_url, so.delivery_id)
				when 17 then concat(dp.tracking_url, so.delivery_id)
				when 18 then concat(dp.tracking_url, so.delivery_id)
				else null
			    end as trackingUrl
			    from fhs_sales_flat_order o 
			    join fahasa_suborder so on so.order_id = o.increment_id and so.parent_id is null and so.self_ship not in (1,13,14,15) and so.status in ('delivering','complete','delivery_failed','delivery_returned','ebiz_returned') 
			    join fahasa_delivery_partner dp on dp.id = so.self_ship 
			    where o.increment_id in ('".$order_ids_str."');";

		    //$sql = "select '".$order_ids_str."' as 'increment_id', '2928727' as 'suborder_id', 'https://fahasa.com' as 'trackingUrl';";
		    $data_result = $read->fetchAll($sql);
		    foreach($data_result as $item){
			if(!empty($item['trackingUrl'])){
			    $result[$item['increment_id']][$item['suborder_id']]['tracking_url'] = $item['trackingUrl'];
			}
		    }
	    }
	    }
	    
	    if(!is_array($order_ids)){
		if(Mage::getStoreConfig('fahasa_sales/config/is_show_btn_confirm_delivery')){
		    $sql = "select o.increment_id, so.suborder_id, ifnull(sd.is_complete, 0) as 'is_complete' 
			    from fhs_sales_flat_order o
			    join fahasa_suborder so on so.order_id = o.increment_id and so.status_timestamp > '2021-07-23' and so.status = 'delivering' and so.self_ship not in (1,13,14,15) and so.parent_id is null and DATEDIFF(now(), so.status_timestamp) > 7
			    left join fahasa_suborder_success_delivery sd on sd.suborder_id = so.suborder_id
			    where o.store_id in (1,2,3,4,12) and o.increment_id in ('".$order_ids_str."');";

		    $data_result = $read->fetchAll($sql);
		    foreach($data_result as $item){
			if(!empty($item['suborder_id'])){
			    $is_complete = false;
			    $is_show_btn = false;
			    if($item['is_complete']){
				$is_complete = true;
			    }else{
				$is_show_btn = true;
			    }
			    $result[$item['increment_id']][$item['suborder_id']]['is_delivery_complete'] = $is_complete;
			    $result[$item['increment_id']][$item['suborder_id']]['show_btn_delivery_confirm'] = $is_show_btn;
			}
		    }
		}
	    }
	}catch (Exception $ex) {}
	
	if(!is_array($order_ids)){
	    Mage::register('current_order_options_rule', $result[$order_ids]);
	}
	Mage::register('order_options_rule_'.$order_ids_str, $result);
	return $result;
    }
    public function updateSuborderDeliveryConfirmComplete($suborder_id){
	$result = array();
	$result['success'] = false;
	
	if(Mage::getSingleton('customer/session')->isLoggedIn()){
	    $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getEntityId();
	    if(empty($customer_id)){return $result;}
	    
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $sql = "insert into fahasa_suborder_success_delivery (suborder_id, is_complete)
		    (select so.suborder_id , 1
		    from fahasa_suborder so
		    join fhs_sales_flat_order o on o.increment_id = so.order_id and o.customer_id = ".$customer_id."
		    where so.status_timestamp > '2021-07-23'
		    and so.status = 'delivering'
		    and so.self_ship not in (1,13,14,15)
		    and so.parent_id is null
		    and o.store_id in (1,2,3,4,12)
		    and DATEDIFF(now(), so.status_timestamp) > 7
		    and so.suborder_id = ".$suborder_id.")
		    ON DUPLICATE KEY UPDATE is_complete = VALUES(is_complete);";

	    $data_result = $writer->query($sql);
	    $result['success'] = true;
	}
	
	return $result;
    }
}
