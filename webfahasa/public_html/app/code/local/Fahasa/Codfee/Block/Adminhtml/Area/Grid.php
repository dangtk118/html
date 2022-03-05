<?php

class Fahasa_Codfee_Block_Adminhtml_Area_Grid extends Magebuzz_Vietnamshipping_Block_Adminhtml_Area_Grid {

    protected function getModuleStr() {
        return "codfee";
    }
    
    protected function _prepareColumns() {
        parent::_prepareColumns();
        $col = $this->getColumn("shipping_express");    
        $col->setData("column_css_class", "no-display");
        $col->setData("header_css_class", "no-display");
    }
}