<?php

class TTS_Airpay_Block_Form extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('airpay/form.phtml');
    }

}
