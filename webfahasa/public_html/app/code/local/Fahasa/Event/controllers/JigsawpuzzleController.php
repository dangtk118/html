<?php

class Fahasa_Event_JigsawpuzzleController extends Mage_Core_Controller_Front_Action {
    
    public function loadPlayerAction() {
        $helper = Mage::helper('event/jigsawpuzzle');
        $result = $helper->loadPlayerData();
        
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function applyCodeAction(){
        $helper = Mage::helper('event/jigsawpuzzle');
        
        $code = $this->getRequest()->getPost("code");
        $result = $helper->applyCode($code);
        
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function loadHistoryAction(){
        $helper = Mage::helper('event/jigsawpuzzle');
        
        $result = $helper->loadHistory();
        
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function tradePieceAction(){
        $helper = Mage::helper('event/jigsawpuzzle');
        
        $count = $this->getRequest()->getPost("count");
        $row = $this->getRequest()->getPost("row");
        $col = $this->getRequest()->getPost("col");
        
        $result = $helper->tradePiece($count, $row, $col);
        
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function loadFpointAction(){
        $helper = Mage::helper('event/jigsawpuzzle');
        
        $result = $helper->loadFpoint();
        
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function tradeFpointAction(){
        $helper = Mage::helper('event/jigsawpuzzle');
        
        $num_pieces = $this->getRequest()->getPost("num_pieces");
        $result = $helper->tradeFpoint($num_pieces);
        
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function checkMissionsAction(){
        $helper = Mage::helper('event/jigsawpuzzle');
        
        try{
            $result = $helper->checkMissions();
        } catch (Exception $ex) {
            Mage::log("Error: " . $ex, null, "buffet.log");
        }
        
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
        
    }
    
    public function shareGameAction(){
        $helper = Mage::helper('event/jigsawpuzzle');
        
        $result = $helper->doMissionShareGame();
        
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
        
    }
    
    public function shareRegistrationAction(){
        $helper = Mage::helper('event/jigsawpuzzle');
        
        $result = $helper->doMissionShareRegistration();
        
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
}