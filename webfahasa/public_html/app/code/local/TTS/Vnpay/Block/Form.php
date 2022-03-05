<?php

class TTS_Vnpay_Block_Form extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('vnpay/form.phtml');
    }

}
