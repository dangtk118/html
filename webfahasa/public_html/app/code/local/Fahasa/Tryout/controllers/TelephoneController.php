<?php

class Fahasa_Tryout_TelephoneController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {

        $this->loadLayout();
        $this->getLayout()->getBlock('root')->setTemplate('page/1column.phtml');
        $block = $this->getLayout()->createBlock(
                'Mage_Core_Block_Template', 'telephone_block_confirm', array('template' => 'tryout/telephone.phtml')
        );

        $this->getLayout()->getBlock('content')->append($block);
        $this->getLayout()->getBlock("head")->setTitle($this->__("Confirm telephone"));
        $this->renderLayout();
    }
    
    public function checkTelephoneAction() {
        $post = $this->getRequest("post");
        $channel = "web";
        $telephone = $post->get("telephone");
        $customerId = $post->get("customerId");

        $helper = Mage::helper("fahasa_customer");
        $data = $helper->checkTelephoneInvalid($telephone, $channel, $customerId);

        return $this->getResponse()->setBody($data);
    }

    public function compareOtpAction() {
        $post = $this->getRequest("post");
        $telephone = $post->get("telephone");
        $otp = $post->get("otp");
        $customerId = $post->get("customerId");
        $facebookId = $post->get("facebookId");
        
        $helper = Mage::helper("fahasa_customer");
        $data = $helper->compareOTP($telephone, $otp, $customerId, $facebookId);

        return $this->getResponse()->setBody($data);
    }

}
