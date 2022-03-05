<?php

class Ves_Verticalmenu_Model_System_Config_Source_ListRootMenu
{
    public function toOptionArray()
    {

		$this->_options  = array( array("value"=>"0", "label"=>"-- Select A Root Menu Item --") );
        $collection = Mage::getModel( "ves_verticalmenu/verticalmenu" )->getCollection()
        				->addRootFilter();
		
		foreach( $collection as $menu ){
			$this->_options[] = array("value"=>$menu->getId(), "label"=>$menu->getTitle() ); 
		}			
        
        return $this->_options;
    }
}