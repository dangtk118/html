<?php

class Fahasa_Event_BuffetcomboController extends Mage_Core_Controller_Front_Action {

    public function IndexAction() {
        $this->loadLayout();
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        $breadcrumbs->addCrumb("home", array(
            "label" => $this->__("Home Page"),
            "title" => $this->__("Home Page"),
            "link" => Mage::getBaseUrl()
        ));

        $breadcrumbs->addCrumb("Event", array(
            "label" => $this->__("Event Buffet Combo"),
            "title" => $this->__("Event Buffet Combo")
        ));
        $this->renderLayout();
    }

    public function addgiftAction() {
        $buffet_helper = Mage::helper("event/buffetcombo");
        $data = $buffet_helper->addBuffetComboGift();
        return $this->getResponse()->setBody(json_encode($data))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function checkgiftAction() {
        $buffet_helper = Mage::helper("event/buffetcombo");
        $data = $buffet_helper->checkBuffetCombo();
                
        return $this->getResponse()->setBody(json_encode($data))
                ->setHeader('Content-type', 'application/json');
    }

}
