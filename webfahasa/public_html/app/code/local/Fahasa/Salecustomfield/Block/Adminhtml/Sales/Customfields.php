<?php
class Fahasa_Salecustomfield_Block_Adminhtml_Sales_Customfields extends Mage_Adminhtml_Block_Template{        
    
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('salecustomfield/salecustomfield_form.phtml');
    }

    public function getOrder(){
        return Mage::registry('current_order');
    }

    public function getDonViBanHang(){
        return Mage::helper('Fahasa_Salecustomfield')->getDonViBanHangGiaoHang($this->getOrder(), 'don_vi_ban_hang');
    }
    
    public function getDonViGiaoHang(){
        return Mage::helper('Fahasa_Salecustomfield')->getDonViBanHangGiaoHang($this->getOrder(), 'don_vi_giao_hang');
    }
    
    public function getFormUrl(){
        return Mage::helper("adminhtml")->getUrl('*/sales_order/customfield', array('order_id'=>$this->getOrder()->getId()) );
    }
}
