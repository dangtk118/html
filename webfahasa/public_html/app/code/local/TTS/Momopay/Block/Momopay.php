<?php
class TTS_Momopay_Block_Momopay extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getMomopay()     
     { 
        if (!$this->hasData('momopay')) {
            $this->setData('momopay', Mage::registry('momopay'));
        }
        return $this->getData('momopay');
        
    }
}