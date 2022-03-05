<?php
class TTS_Airpay_Block_Airpay extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getAirpay()     
     { 
        if (!$this->hasData('airpay')) {
            $this->setData('airpay', Mage::registry('airpay'));
        }
        return $this->getData('airpay');
        
    }
}