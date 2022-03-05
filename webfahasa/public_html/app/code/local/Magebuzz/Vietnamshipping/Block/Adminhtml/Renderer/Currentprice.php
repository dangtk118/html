<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Renderer_Currentprice extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
		$priceShippingNormal=$row->getPriceShippingNormal();
		return Mage::helper('core')->currency($priceShippingNormal, true, false);
    }

}