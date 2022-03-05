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
 * Invoice view  comments form
 *
 * @category    Mage
 * @package     Mage_Sales
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Sales_Block_Order_Info extends Mage_Core_Block_Template
{
    protected $_links = array();

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('sales/order/info.phtml');
    }

    protected function _prepareLayout()
    {
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setTitle($this->__('Order # %s', $this->getOrder()->getRealOrderId()));
        }
        $this->setChild(
            'payment_info',
            $this->helper('payment')->getInfoBlock($this->getOrder()->getPayment())
        );
    }

    public function getPaymentInfoHtml()
    {
        return $this->getChildHtml('payment_info');
    }

    /**
     * Retrieve current order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    public function addLink($name, $path, $label)
    {
        $this->_links[$name] = new Varien_Object(array(
            'name' => $name,
            'label' => $label,
            'url' => empty($path) ? '' : Mage::getUrl($path, array('order_id' => $this->getOrder()->getId()))
        ));
        return $this;
    }

    public function getLinks()
    {
        $this->checkLinks();
        return $this->_links;
    }

    private function checkLinks()
    {
        $order = $this->getOrder();
        if (!$order->hasInvoices()) {
            unset($this->_links['invoice']);
        }
        if (!$order->hasShipments()) {
            unset($this->_links['shipment']);
        }
        if (!$order->hasCreditmemos()) {
            unset($this->_links['creditmemo']);
        }
    }

    /**
     * Get url for reorder action
     *
     * @deprecated after 1.6.0.0, logic moved to new block
     * @param Mage_Sales_Order $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return $this->getUrl('sales/guest/reorder', array('order_id' => $order->getId()));
        }
        return $this->getUrl('sales/order/reorder', array('order_id' => $order->getId()));
    }

    /**
     * Get url for printing order
     *
     * @deprecated after 1.6.0.0, logic moved to new block
     * @param Mage_Sales_Order $order
     * @return string
     */
    public function getPrintUrl($order)
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return $this->getUrl('sales/guest/print', array('order_id' => $order->getId()));
        }
        return $this->getUrl('sales/order/print', array('order_id' => $order->getId()));
    }
    
    public function  getProgressStep($_order){
        
        $result = "";
        $dot_waiting = "next-step-item-waiting";
        $dot_process = "next-step-item-process";
        $dot_finish = "next-step-item-finish";
        $dot_process_cancel = "next-step-item-process-cancel";
        $dot_cancel = "next-step-item-cancel";
        $dot_last = "next-step-item-last";
        $template = "<div class='next-step-item {dot}' style='width: auto;'>
                        <div class='next-step-item-tail'>
                            <div class='next-step-item-tail-underlay'>
                                <div class='next-step-item-tail-overlay' style='width: {line_percent}%;'></div>
                            </div>
                        </div>
                        <div class='next-step-item-container'>
                            <div class='next-step-item-node'><span class='next-step-item-node-dot'></span></div>
                            <div class='next-step-item-title'>{status_text}</div>
                        </div>
                    </div>";
        $CancelStep = -1;
        $step = -1;
        if ($_order->getStatus() == 'canceled'){
            $CancelStep = 0;
        }
        if($_order->getStatus() == 'pending' || $_order->getStatus() == 'pending_payment' || $_order->getStatus() == 'paid'){
            $step = 0;
        }else if($_order->getStatus() == 'customer_confirmed'){
            $step = 1;
        }else if($_order->getStatus() == 'processing'){
            $step = 2;
        }else if($_order->getStatus() == 'complete'){
            $step = 3;
        }
        for($i = 0; $i < 4; $i++){
            if($i == $step && $step == 0){
                $replace_text = str_replace('{dot}',($CancelStep < 0?$dot_waiting.' '.$dot_process:$dot_process_cancel) ,$template);
                $replace_text = str_replace('{status_text}',$this->__('New Orders'),$replace_text);
                $replace_text = str_replace('{line_percent}',100,$replace_text);
                $result .= $replace_text;
                 continue;
            }
            if($i == $step && $step == 1){
                $replace_text = str_replace('{dot}',($CancelStep < 0?$dot_waiting.' '.$dot_process:$dot_process_cancel) ,$template);
                $replace_text = str_replace('{status_text}',$this->__('Confirm'),$replace_text);
                $replace_text = str_replace('{line_percent}',100,$replace_text);
                $result .= $replace_text;
                 continue;
            }
            if($i == $step && $step == 2){
                $replace_text = str_replace('{dot}',($CancelStep < 0?$dot_waiting.' '.$dot_process:$dot_process_cancel) ,$template);
                $replace_text = str_replace('{status_text}',$this->__('processing'),$replace_text);
                $replace_text = str_replace('{line_percent}',100,$replace_text);
                $result .= $replace_text;
                continue;
            }
            if(($i == $step && $step == 3) ||($i==3 && $CancelStep > -1)){
                $replace_text = str_replace('{dot}',($CancelStep < 0?$dot_process." ".$dot_last:$dot_process_cancel),$template);
                $replace_text = str_replace('{status_text}',$CancelStep < 0?$this->__('complete'):$this->__('_canceled'),$replace_text);
                $replace_text = str_replace('{line_percent}',0,$replace_text);
                $result .= $replace_text;
                 continue;
            }
            
            //default step
            if($i == 0){
                $replace_text = str_replace('{dot}',($CancelStep >$step?$dot_cancel:($step > $i?$dot_finish:$dot_waiting)),$template);
                $replace_text = str_replace('{status_text}',$this->__('New Orders'),$replace_text);
                $replace_text = str_replace('{line_percent}',100,$replace_text);
                if($CancelStep >$step){$replace_text = str_replace('next-step-item-title','next-step-item-title-light',$replace_text);}
                $result .= $replace_text;
                 continue;
            }
            if($i == 1){
                $replace_text = str_replace('{dot}',($CancelStep >$step?$dot_cancel:($step > $i?$dot_finish:$dot_waiting)),$template);
                $replace_text = str_replace('{status_text}',$this->__('Confirm'),$replace_text);
                $replace_text = str_replace('{line_percent}',100,$replace_text);
                if($CancelStep >$step){$replace_text = str_replace('next-step-item-title','next-step-item-title-light',$replace_text);}
                $result .= $replace_text;
                 continue;
            }
            if($i == 2){
                $replace_text = str_replace('{dot}',($CancelStep >$step?$dot_cancel:($step > $i?$dot_finish:$dot_waiting)),$template);
                $replace_text = str_replace('{status_text}',$this->__('processing'),$replace_text);
                $replace_text = str_replace('{line_percent}',100,$replace_text);
                if($CancelStep >$step){$replace_text = str_replace('next-step-item-title','next-step-item-title-light',$replace_text);}
                $result .= $replace_text;
                 continue;
            }
            if($i == 3){
                $replace_text = str_replace('{dot}',($CancelStep >$step?$dot_cancel:""),$template);
                $replace_text = str_replace('{status_text}',$CancelStep < 0?$this->__('complete'):$this->__('_canceled'),$replace_text);
                $replace_text = str_replace('{line_percent}',0,$replace_text);
                if($CancelStep >$step){$replace_text = str_replace('next-step-item-title','next-step-item-title-light',$replace_text);}
                $result .= $replace_text;
                 continue;
            }
        }
        return $result;
    }

    public function  getOrderLog($_order){
        $result = array();
        
        if($_order->getStatus() == "complete" && $_order->getState() == "complete"){
            if(($_order->getCompleteTimestamp() != null) && ($_order->getCompleteTimestamp() != "0000-00-00 00:00:00")){
                $order_log_item0->date = date('d/m/Y - H:i',strtotime($_order->getCompleteTimestamp())); 
                $order_log_item0->status = $this->__('Order completed.');
                $result[] = $order_log_item0;
            }
        }
        
        if ($_order->getStatus() == 'canceled'){
            if(($_order->getCompleteTimestamp() != null) && ($_order->getCompleteTimestamp() != "0000-00-00 00:00:00")){
                $order_log_item_cancel->date = date('d/m/Y - H:i',strtotime($_order->getCompleteTimestamp())); 
                $order_log_item_cancel->status = $this->__('Order canceled.');
                $result[] = $order_log_item_cancel;
            }
        }
        
        if(($_order->getConfirmedTimestamp() != null) && ($_order->getConfirmedTimestamp() != "0000-00-00 00:00:00")){
            $order_log_item1->date = date('d/m/Y - H:i',strtotime($_order->getConfirmedTimestamp())); 
            $order_log_item1->status = $this->__('Order confirmed and processing.');
            $result[] = $order_log_item1;
        }
        
        $order_log_item2->date = date('d/m/Y - H:i',strtotime('+7 hour',strtotime($_order->getCreatedAt()))); 
        $order_log_item2->status = $this->__('Created new Order.');
        $result[] = $order_log_item2;
        return json_encode($result);
    }
    
    public function getVAT($_order){
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
            if($rows[0]){
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
                $result = "(".$this->__("Don't have infomation").")";
        } catch (Exception $ex) {}
        return $result;
    }
    
    public function getNote($_order){
        $result = "";
        try{
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $connection->query("set character_set_results=utf8"); 
            $sql = "SELECT value as 'note'
                    From fhs_fieldsmanager_orders 
                    where entity_id = '".$_order->getEntityId()."' AND attribute_id = '172';";
            $rows = $connection->fetchAll($sql);
            if($rows[0])
                $result = json_decode($rows[0]['note']);
            else
                $result = "<i>(".$this->__("Don't have infomation").")</i>";
        } catch (Exception $ex) {}
        return $result;
    }  
}
