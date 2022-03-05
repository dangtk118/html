<?php
include_once Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'Sales' . DS . 'OrderController.php';
class Fahasa_Salecustomfield_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController{
    public function customfieldAction()
    {

        $postData = $this->getRequest()->getPost();

        Mage::log('Fahasa_Salecustomfield_Adminhtml_Sales_OrderController::customfieldAction'.print_r($postData, true) );

        $id = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($id);
        $order->setDonViBanHang($postData['don_vi_ban_hang']);
        $order->setDonViGiaoHang($postData['don_vi_giao_hang']);

        $order->save();

        // return to sales_order view
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id'=> $id)));

    }
}

