<?php

class Fahasa_Redeem_IndexController extends Mage_Core_Controller_Front_Action {

    public function redeemAction() {
        $post = $this->getRequest("post");
        $redeemCode = $post->get("redeemCode");

        $helper = Mage::helper("redeem");
        $data = $helper->redeemFpoint($redeemCode);

        return $this->getResponse()->setBody($data);
    }

}
