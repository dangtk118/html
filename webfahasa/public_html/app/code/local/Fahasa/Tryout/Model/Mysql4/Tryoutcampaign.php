<?php
class Fahasa_Tryout_Model_Mysql4_Tryoutcampaign extends Mage_Core_Model_Mysql4_Abstract{
    
    public function _construct() {
        $this->_init('tryout/tryoutcampaign', 'campaign_id');
    }        
}
