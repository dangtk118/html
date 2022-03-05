<?php

class Fahasa_Fhsrule_IndexController extends Mage_Core_Controller_Front_Action {

    public function postRemainAction() {
        $post = $this->getRequest('POST');
        $ruleIds = $post->getPost("ruleIds");
        $rules = Mage::helper("fhsrule")->getRuleData($ruleIds);
        return $this->getResponse()->setBody(json_encode($rules));
    }

}
