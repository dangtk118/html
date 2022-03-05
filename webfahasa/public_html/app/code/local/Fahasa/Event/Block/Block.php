<?php   
class Fahasa_Event_Block_Block extends Mage_Core_Block_Template{   
    
    public function checkProductVote($productId){
        $helper = Mage::helper('event');
        $result = $helper->checkProductVote($productId);
        return $result;
    }
    
    public function getEventDetail($eventId){
        return Mage::helper("event")->getEventDetail($eventId);
    }
    
    public function getTopVotedStaticAction($productIds){
        return Mage::helper('event')->getTopVotedStatic($productIds);
    }

    public function getCategoryNameByCatIds(){
       return Mage::helper('event')->getCategoryNameByCatIds();
    }
}