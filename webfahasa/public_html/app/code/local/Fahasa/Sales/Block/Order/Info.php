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
 * Invoice view  comments form
 *
 * @category    Fahasa
 * @package     Fahasa_Sales
 * @author      khuonglt
 */
class Fahasa_Sales_Block_Order_Info extends Mage_Sales_Block_Order_Info
{
    /* 
     * using in template order/info
     * like function getNote but return null if not infomation
     */
    public function getNoteOrderInfo($_order){
        $result = "";
        try{
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $connection->query("set character_set_results=utf8"); 
            $sql = "SELECT value as 'note'
                    From fhs_fieldsmanager_orders 
                    where entity_id = '".$_order->getEntityId()."' AND attribute_id = '172';";
            $rows = $connection->fetchAll($sql);
            if(!empty($rows[0]) && !empty(rows[0]['note']))
                $result = json_decode($rows[0]['note']);
            else
                $result = null;
        } catch (Exception $ex) {}
        return $result;
    }
    /* 
     * using in template sales/order/info
     * like function getVAT but return null if not infomation
     */
    public function getVATOrderInfo($_order){
        $result = "";
        try{
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $connection->query("set character_set_results=utf8"); 
            $sql = "SELECT vatcom.value as 'vatcom', vatadd.value as 'vatadd', vatcode.value as 'vatcode', ifnull(vatname.value,'\"\"') as 'vatname', ifnull(vatemail.value,'\"\"') as 'vatemail'
            FROM fhs_fieldsmanager_orders vatcom
            LEFT JOIN fhs_fieldsmanager_orders vatadd ON vatadd.entity_id = vatcom.entity_id and vatadd.attribute_id = '148'
            LEFT JOIN fhs_fieldsmanager_orders vatcode ON vatcode.entity_id = vatcom.entity_id and vatcode.attribute_id = '149'
            LEFT JOIN fhs_fieldsmanager_orders vatname ON vatname.entity_id = vatcom.entity_id and vatname.attribute_id = '219'
            LEFT JOIN fhs_fieldsmanager_orders vatemail ON vatemail.entity_id = vatcom.entity_id and vatemail.attribute_id = '220'
            WHERE vatcom.attribute_id = '147' AND vatcom.entity_id = '".$_order->getEntityId()."';";
	    
            $rows = $connection->fetchAll($sql);
            if(!empty($rows[0])){
                if($rows[0]['vatname'] != "\"\""){
                    $result .= json_decode($rows[0]['vatname'])."</br>";
                } 
                if($rows[0]['vatcom'] != "\"\""){
                    $result .= json_decode($rows[0]['vatcom'])."</br>";
                }
                if($rows[0]['vatcom'] != "\"\""){
                    $result .= json_decode($rows[0]['vatadd'])."</br>";
                }
                if($rows[0]['vatcom'] != "\"\""){
                    $result .= json_decode($rows[0]['vatcode'])."</br>";
                } 
                if($rows[0]['vatemail'] != "\"\""){
                    $result .= json_decode($rows[0]['vatemail'])."</br>";
                } 
            }
            if($result == "")
                $result = null;
        } catch (Exception $ex) {}
        return $result;
    }
    
    /* 
     * using in template sales/order/info
     */
    public function  getProgressStepOrderInfo($_order){
        
        $result = "";
        $skin_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN, true);
        $template = '
        <div class="order-view-status-new-order">
            <div class="order-view-icon-container">
                <div style="height: 60px;width: 60px;align-self: center;"><div class="order-view-icon-img" {style}></div></div>
                <div class="order-view-icon-content"><p>{status_text}</p><p>{status_date}</p></div>
            </div>
            {html_bar}
        </div>';
        $htmlProgress = '<div class="order-view-progress-bar" style="background:{color_id};"></div>';
        
        $step = -1;
        $helper = $this->helper('sales/data');
        $timeUpdateStatus = $helper->getOrderLogOrderInfo($_order);
                
        if($_order->getStatus() == 'pending' || $_order->getStatus() == 'pre_pending' || $_order->getStatus() == 'pending_payment' || $_order->getStatus() == 'paid' || $_order->getStatus() == 'customer_confirmed'){
            $step = 0;
            $colorId= "#f7941f";
            $colorName = "orange";
        }else if($_order->getStatus() == 'processing'){
            $step = 1;
            $colorId= "#2F80ED";
            $colorName = "blue";
        }else if($_order->getStatus() == 'complete' || $_order->getStatus() == 'canceled'){
            $step = 2;
            $statusName = $_order->getStatus() == 'complete' ? "hoantat_green" : "huy_red";
            $colorName =  $_order->getStatus() == 'complete' ? "green" : "red";
            $colorId = $_order->getStatus() == 'complete' ? "#29a72a" : "#fa0001";
            $statusText = $_order->getStatus() == 'complete' ? $this->__('Complete') : $this->__('Canceled');
        }
        for($i = 0; $i < 3; $i++){
            if($i == $step && $step == 0){
                $style='style="background: url('.$skin_url.'frontend/ma_vanese/fahasa/images/order/ico_donhangmoi_{color_name}.svg) no-repeat center;border-color:{color_id};"';
                $time = $timeUpdateStatus['created']->date;
                $htmlProgress = '<div class="order-view-progress-bar" style="background:#E0E0E0;"></div>';
                $replace_text = str_replace('{html_bar}', $htmlProgress, $template);
                $replace_text = str_replace('{style}', $style, $replace_text);
                $replace_text = str_replace('{color_name}', $colorName, $replace_text);
                $replace_text = str_replace('{color_id}', $colorId, $replace_text);
                $replace_text = str_replace('{status_text}', $this->__('New Orders'), $replace_text);
                $replace_text = str_replace('{status_date}', $time, $replace_text);
                $result .= $replace_text;
                continue;
            }
            if($i == $step && $step == 1){
                $style='style="background: url('.$skin_url.'frontend/ma_vanese/fahasa/images/order/ico_dangxuly_{color_name}.svg) no-repeat center;border-color:{color_id};"';
                $time = $timeUpdateStatus['processing']->date;
                $replace_text = str_replace('{html_bar}', $htmlProgress, $template);
                $replace_text = str_replace('{style}', $style, $replace_text);
                $replace_text = str_replace('{color_name}', $colorName, $replace_text);
                $replace_text = str_replace('{color_id}', $colorId, $replace_text);
                $replace_text = str_replace('{status_text}', $this->__('Processing'), $replace_text);
                $replace_text = str_replace('{status_date}', $time, $replace_text);
                $result .= $replace_text;
                continue;
            }
            if($i == $step && $step == 2){
                $style='style="background: url('.$skin_url.'frontend/ma_vanese/fahasa/images/order/ico_{status_name}.svg) no-repeat center;border-color:{color_id};"';
                $time = $timeUpdateStatus[$_order->getStatus()]->date;
                $replace_text = str_replace('{style}',$style, $template);
                $replace_text = str_replace('{html_bar}','', $replace_text);
                $replace_text = str_replace('{color_id}',$colorId, $replace_text);
                $replace_text = str_replace('{status_name}',$statusName, $replace_text);
                $replace_text = str_replace('{status_text}', $statusText, $replace_text);
                $replace_text = str_replace('{status_date}', $time, $replace_text);
                $result .= $replace_text;
                continue;
            }
            
            //default step
            // color : ($i > step ? xam : color of step  ) 
            // icon :  ($i > step ? no : yes  )
            if($i == 0){
                $style='style="background: url('.$skin_url.'frontend/ma_vanese/fahasa/images/order/ico_donhangmoi_{color_name}.svg) no-repeat center;border-color:{color_id};"';
                $replace_text = str_replace('{html_bar}', $htmlProgress, $template);
                $time1 = $timeUpdateStatus['created']->date;
                $replace_text = str_replace('{style}', $style, $replace_text);
                $replace_text = str_replace('{color_name}', $colorName, $replace_text);
                $replace_text = str_replace('{status_date}', $time1, $replace_text);
                $replace_text = str_replace('{color_id}', $colorId, $replace_text);
                $replace_text = str_replace('{status_text}', $this->__('New Orders'), $replace_text);
                $result .= $replace_text;
                continue;
            }
            if($i == 1){
                $style='style="background: url('.$skin_url.'frontend/ma_vanese/fahasa/images/order/ico_dangxuly_{color_name}.svg) no-repeat center;border-color:{color_id};"';
                $time1 = $timeUpdateStatus['processing']->date;
                $replace_text = str_replace('{html_bar}', $htmlProgress, $template);
                if ($i < $step) {
                    $replace_text = str_replace('{style}', $style, $replace_text);
                    $replace_text = str_replace('{color_name}', $colorName, $replace_text);
                    $replace_text = str_replace('{status_date}', $time1, $replace_text);
                    $replace_text = str_replace('{status_text}', $this->__('Processing'), $replace_text);
                }else{
                    $colorId = "#E0E0E0";
                    $style1 = 'style="border-color:'.$colorId.';"'; 
                    $replace_text = str_replace('{style}', $style1, $replace_text);
                    $replace_text = str_replace('{color_name}', $colorName, $replace_text);
                    $replace_text = str_replace('{status_date}',"", $replace_text);
                    $replace_text = str_replace('{status_text}',"", $replace_text);
                }
                $replace_text = str_replace('{color_id}', $colorId, $replace_text);
                $result .= $replace_text;
                continue;
            }
            if($i == 2){
                $style='style="background: url('.$skin_url.'frontend/ma_vanese/fahasa/images/order/ico_{status_name}.svg) no-repeat center;border-color:{color_id};"';
                $time1 = $timeUpdateStatus[$_order->getStatus()]->date;
                if ($i < $step) {
                    $replace_text = str_replace('{style}',$style, $template);
                    $replace_text = str_replace('{status_date}', $time1, $replace_text);
                    $replace_text = str_replace('{status_text}', $statusText, $replace_text);
                    
                }else{
                    $colorId = "#E0E0E0";
                    $style1 = 'style="border-color:'.$colorId.';"'; 
                    $replace_text = str_replace('{style}', $style1, $template);
                    $replace_text = str_replace('{status_date}',"", $replace_text);
                    $replace_text = str_replace('{status_text}',"", $replace_text);
                }
                $replace_text = str_replace('{html_bar}','', $replace_text);
                $replace_text = str_replace('{color_id}',$colorId, $replace_text);
                $replace_text = str_replace('{status_name}',$statusName, $replace_text);
                $result .= $replace_text;
                continue;
            }
        }
        return $result;
    }
    
    public function getProgressStepStatusPaymentHtml($orderId) {
        $helperRepayment = Mage::helper("repayment");
        $data_trans_history = $helperRepayment->getDataRepaymentMethodsLog($orderId);
        $payment_status = $data_trans_history['payment_status'];
        $payment_status_text = array();
        $result = $data_trans_history['trans_history'];
        $html = "";
        if($payment_status){
            $payment_status_text['show'] = TRUE;
            if($payment_status == 'refund_success'){
                $payment_status_text['text'] = "Hoàn tiền thành công";
                $payment_status_text['color'] = "#2ED62E";
            }else{
                if ($payment_status == 'payment_failure') {
                    $payment_status_text['text'] = $this->__('Payment failed due to payment error');
                } else {
                    
                    $payment_status_text['text'] = $this->__('Refund time is 3-5 days');
                }
                $payment_status_text['color'] = "#dc3545";
            }
        }else{
            $payment_status_text['show'] = FALSE;
        }
        
        if (count($result) > 0) {
            $html .= "<div style='padding:15px;'><ul class='order-box-info-ul'>";
            foreach ($result as $item) {
                $styleHtmlcolor = "";

                if($item['color'] && $item['color'] != null){
                    $color = json_decode($item['color']);
                    $styleHtmlcolor = "style='background:". $color->number1
                            .";color:" . $color->number2
                            .";border-color:" . $color->number1 . ";'";
                }
                
                if($item['status'] && $item['status'] != 'empty'){
                    if($item['status'] == 'failed'){
                        $textStatusIco = $this->__('Payment failed due to payment error'); 
                    }else{
                        $textStatusIco = $this->__($item['status']); 
                    }
                    $statusIcoPayment = ""
                            . "<div class='order-box-info-li-status' " . $styleHtmlcolor. ">"
                                . $textStatusIco
                            . "</div>";
                    $statusIcoPaymentMobile = ""
                            . "<div class='order-box-info-li-status-mobile' " . $styleHtmlcolor. ">"
                                . $textStatusIco
                            . "</div>";
                }
                
                $htmlRefundText = "";
                if($item['status'] == 'refund_processing'){
                    $textRefund = $this->__('Refund time is 3-5 days');
                    $htmlRefundText = "<div class='order-box-info-li-created-at'>". $textRefund ."</div>";
                }
                
                $html .= ""
                        . "<li class='order-box-info-li-parent'>"
                        . "<div style='display:flex;'>"
                            . "<div class='order-box-info-cricle'>"
                                . "<div class='order-box-info-li'></div>"
                            . "</div>"
                            . "<div class='order-box-info-li-content'>"
                                . $statusIcoPaymentMobile
                                . "<div class='order-box-info-li-content-status'>"
                                    . "<div class='order-box-info-li-text'>" . $item['payment_method_text'] . "</div>"
                                    .   $statusIcoPayment
                                . "</div>"
                                . "<div class='order-box-info-li-created-at'>" . $item['created_at'] . "</div>"
                                . $htmlRefundText
                            . "</div>"
                        . "</div>"
                        . "</li>";
            }
            $html .= "</ul><div>";
        }
        $dataArray = array(
            "count" => count($result),
            "html" => $html,
            "payment_status_text" => $payment_status_text,
        ); 
        return $dataArray;
    }

}
