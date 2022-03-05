<?php

class Fahasa_Sales_Block_Order_History extends Mage_Sales_Block_Order_History
{
    protected $_statusVarName  = 'status';
    protected $_availableStatus = array('All'=>'All','pending_payment'=>'pending_payment','pending'=>'pending','processing'=>'processing','complete'=>'complete','canceled'=>'canceled');
    
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('sales/order/history.phtml');

        $orders = Mage::getResourceModel('sales/order_collection')
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', Mage::getSingleton('customer/session')->getCustomer()->getId())
            ->addFieldToFilter('state', array('in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()))
            ->setOrder('created_at', 'desc')
        ;
        $status_list = $this->getCurrentStatusFilter();
        if(count($status_list) > 0){
            $orders->addFieldToFilter('status',array('in' => $status_list));
        }
        $this->setOrders($orders);
        
        Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('root')->setHeaderTitle(Mage::helper('sales')->__('My Orders'));
    }
    
    public function getLimit()
    {
        return $this->getChild('pager')->getLimit();
    }
    
    //Handle order Status begin
    
    public function getCurrentStatus()
    {
        $_statusParam = (String) $this->getRequest()->getParam($this->getStatusVarName());
        if($_statusParam != "pending_payment" && $_statusParam != "pending" && $_statusParam != "canceled"
                && $_statusParam != "complete" && $_statusParam != "processing"){
            $_statusParam = 'All';
        }
        return $_statusParam;
    }

    public function getStatusUrl($status)
    {
        return $this->getChild('pager')->getPagerUrl(array($this->getStatusVarName()=>$status));
    }
    
     public function getStatusUrlWithOutLimit($status)
    {
        $urlParams = array();
//        $urlParams['_current']  = true;
//        $urlParams['_escape']   = true;
//        $urlParams['_use_rewrite']   = true;
        $urlParams['_query']    = array("status"=>$status);
        return $this->getUrl('*/*/*', $urlParams);
    }
    
    public function isStatusCurrent($status)
    {
        return $status == $this->getCurrentStatus();
    }
    
    public function getStatusVarName()
    {
        return $this->_statusVarName;
    }
    
    public function getAvailableStatus()
    {
        return $this->_availableStatus;
    }
    
    public function getCurrentStatusFilter()
    {
        $status_list = array();
        $_statusParam = $this->getCurrentStatus();
        switch ($_statusParam){
            case 'pending':
            case 'paid':
            case 'customer_confirmed':
                array_push($status_list,'pending','paid','customer_confirmed','pre_pending');
                break;
            case 'pending_payment':
                array_push($status_list,'pending_payment');
                break;
            case 'canceled':
                array_push($status_list,'canceled');
                break;
            case 'complete':
                array_push($status_list,'complete');
                break;
            case 'processing':
                array_push($status_list,'processing');
                break;
        }
        return $status_list;
    }
    //Handle order Status end
    
    public function getCountOrderByStatus($status){
        
        $orders = Mage::getResourceModel('sales/order_collection')
                ->addFieldToSelect('*')
                ->addFieldToFilter('customer_id', Mage::getSingleton('customer/session')->getCustomer()->getId())
                ->addFieldToFilter('state', array('in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()))
                ->setOrder('created_at', 'desc')
        ;
        if ($status != "All") {
            if($status == 'pending'){
                $orders->addFieldToFilter('status', array('in' =>array('pre_pending','pending','paid','customer_confirmed')));
            }else{
               $orders->addFieldToFilter('status', array('in' => $status));
             }
        }
        return $orders->count();
    }
    
    public function getPagerOrderHistoryHtml() {
        $pager = $this->getLayout()->createBlock('page/html_pager', 'sales.order.history.pager.v2')->setTemplate('sales/order/pager_orderhistory.phtml')
                ->setCollection($this->getOrders());
        $this->setChild('pagerOrderHistory', $pager);
        return $this->getChildHtml('pagerOrderHistory');
    }
    
    public function getLimitOrderHistoryHtml() {
        $limit = $this->getLayout()->createBlock('page/html_pager', 'sales.order.history.limit.v2')->setTemplate('sales/order/limit_orderhistory.phtml')
                ->setCollection($this->getOrders());
        $this->setChild('limitOrderHistory', $limit);
        return $this->getChildHtml('limitOrderHistory');
    }
}
