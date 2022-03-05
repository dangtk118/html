<?php

class TTS_Momopay_Block_Form extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('momopay/form.phtml');
    }

}
