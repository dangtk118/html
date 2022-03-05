<?php
class Fahasa_Personalization_IndexController extends Mage_Core_Controller_Front_Action{
     
    public function indexAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function likedprodutsAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function bestsellerAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
}