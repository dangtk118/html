<?php
class TTS_Vnpay_Block_Vnpay extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getVnpay()     
     { 
        if (!$this->hasData('vnpay')) {
            $this->setData('vnpay', Mage::registry('vnpay'));
        }
        return $this->getData('vnpay');
        
    }
}