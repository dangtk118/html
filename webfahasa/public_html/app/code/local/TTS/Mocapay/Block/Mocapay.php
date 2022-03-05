<?php
class TTS_Mocapay_Block_Mocapay extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getMocapay()     
     { 
        if (!$this->hasData('mocapay')) {
            $this->setData('mocapay', Mage::registry('mocapay'));
        }
        return $this->getData('mocapay');
        
    }
}