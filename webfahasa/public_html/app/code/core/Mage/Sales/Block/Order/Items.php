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
 * Sales order view items block
 *
 * @category   Mage
 * @package    Mage_Sales
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Sales_Block_Order_Items extends Mage_Sales_Block_Items_Abstract
{
    /**
     * Retrieve current order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }
    public function getTrackingSubOrderLog($_suborder){
        $template = "<tr>
                        <td>{processing}</td>
                        <td>{pack}</td>
                        <td>{deliver}</td>
                        <td>{complete}</td>
                    </tr> ";
        $complete_timestamp = "";
        $deliverSubOrder_timestamp = "";
        $packSubOrder_timestamp = "";
        $confirmSubOrder_timestamp = "";
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
        
        //Replace to template
        $template = str_replace('{complete}',$complete_timestamp,$template);
        $template = str_replace('{deliver}',$deliverSubOrder_timestamp,$template);
        $template = str_replace('{pack}',$packSubOrder_timestamp,$template);
        $template = str_replace('{processing}',$confirmSubOrder_timestamp,$template);
        return $template;
    }
    public function getExpectDateList($orderID){
        $result = Array();
        try{
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $sql = "SELECT oi.product_id, ed.value as 'expect_date'
                    FROM fhs_sales_flat_order o
                    JOIN fhs_sales_flat_order_item oi ON oi.order_id = o.entity_id
                    RIGHT JOIN fhs_catalog_product_entity_datetime ed 
                            on ed.entity_id = oi.product_id AND ed.attribute_id = 191 AND ed.value IS NOT NULL AND ed.value > now()
                    WHERE o.entity_id = '".$orderID."';";
            $result = $connection->fetchAll($sql);
        } catch (Exception $ex) {}
        return $result;
    }
    public function getExpectDate($product_id,$expect_date_list){
        $result = "";
        try{
            foreach($expect_date_list as $item){
                if($item['product_id'] == $product_id)
                    $result = $item['expect_date'];
            }
        } catch (Exception $ex) {}
        return $result;
    }
}
