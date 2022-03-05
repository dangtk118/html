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
 * @category    Fahasa
 * @package     Fahasa_Sales
 * @copyright  Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales order view items block
 *
 * @category   Fahasa
 * @package    Fahasa_Sales
 * @author     khuonglt
 */
class Fahasa_Sales_Block_Order_Items extends Mage_Sales_Block_Order_Items
{
//    public function getTrackingSubOrderLogFhs($_suborder){
//        if($_suborder["status"] == "complete" && $_suborder["completeSubOrder_timestamp"] != null){
//            $complete_timestamp = $_suborder["completeSubOrder_timestamp"];
//        }
//        else if($_suborder["status"] == "complete" && $_suborder["completeSubOrder_timestamp"] == null){
//            $complete_timestamp = $_suborder["status_timestamp"];
//        }
//        else if($_suborder["status"] == "canceled"){
//            $complete_timestamp = $_suborder["status_timestamp"];
//        }
//        if($_suborder["deliverSubOrder_timestamp"] != null){
//            $deliverSubOrder_timestamp = $_suborder["deliverSubOrder_timestamp"];
//        }
//        if($_suborder["packSubOrder_timestamp"] != null){
//            $packSubOrder_timestamp = $_suborder["packSubOrder_timestamp"];
//        }
//        if($_suborder["confirmSubOrder_timestamp"] != null){
//            $confirmSubOrder_timestamp = $_suborder["confirmSubOrder_timestamp"];
//        }else{
//            $confirmSubOrder_timestamp = $_suborder["created_at"];
//        }
//        //Add TimeZone
//        if($complete_timestamp != "")$complete_timestamp = date('d/m/Y - H:i',strtotime($complete_timestamp)); 
//        if($deliverSubOrder_timestamp != "")$deliverSubOrder_timestamp = date('d/m/Y - H:i',strtotime($deliverSubOrder_timestamp)); 
//        if($packSubOrder_timestamp != "")$packSubOrder_timestamp = date('d/m/Y - H:i',strtotime($packSubOrder_timestamp)); 
//        if($confirmSubOrder_timestamp != "")$confirmSubOrder_timestamp = date('d/m/Y - H:i',strtotime($confirmSubOrder_timestamp)); 
//        
//        $dataTrackLog['processing'] = $confirmSubOrder_timestamp;
//        $dataTrackLog['pack'] = $packSubOrder_timestamp;
//        $dataTrackLog['deliver'] = $deliverSubOrder_timestamp;
//        //TH : compelete van khong co data => giao hang that bai;
//        if ($complete_timestamp) {
//            $dataTrackLog['complete'] = $complete_timestamp;
//        }else{
////            if($_suborder["status"] == "canceled"
////           ||  $_suborder["status"] == "ebiz_returned" ||
////               $_suborder["status"] == "delivery_failed" ||
////               $_suborder["status"] == "delivery_returned" ||
////               $_suborder["status"] == "permanent_no_stock" ||
////               $_suborder["status"] == "returning" ||
////               $_suborder["status"] == "returned"
////             ){
////                $dataTrackLog['failure'] = $this->__('Failure');
////            }else{
//                $dataTrackLog['notshow'] = "";
////            }
//        }
//        return $dataTrackLog;
//    }
}
