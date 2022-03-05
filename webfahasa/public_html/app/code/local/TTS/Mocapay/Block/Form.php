<?php

class TTS_Mocapay_Block_Form extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('mocapay/form.phtml');
    }

}
