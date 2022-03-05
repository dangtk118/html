<?php

class Fahasa_Codfee_Block_Adminhtml_Rule_Grid extends Magebuzz_Vietnamshipping_Block_Adminhtml_Rule_Grid {

    protected function getModuleStr() {
        return "codfee";
    }

    protected function _prepareColumns() {
        parent::_prepareColumns();
        $col = $this->getColumn("apply_to_shipping");        
        $col->setData("header", Mage::helper($this->getModuleStr())->__('Apply'));
        $col->setData("index", "simple_action");
        $col->setData("type", "options");
        $col->setData("options", array(
                "by_percent" => Mage::helper($this->getModuleStr())->__('Percent amount discount'),
                "cart_fixed" => Mage::helper($this->getModuleStr())->__('Fixed amount discount'),            
        ));
                
    }
    
}