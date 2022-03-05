<?php
class Ves_Verticalmenu_Model_System_Config_Backend_Verticalmenu extends Mage_Core_Model_Config_Data {
    protected function _afterSave() {
	    // Code that flushes cache goes here
        Mage::app()->cleanCache( array(
	        Mage_Core_Model_Store::CACHE_TAG,
	        Mage_Cms_Model_Block::CACHE_TAG,
	        Ves_Verticalmenu_Model_Verticalmenu::CACHE_BLOCK_TAG
	    ) );

	     // Code that flushes cache goes here
        Mage::app()->cleanCache( array(
	        Mage_Core_Model_Store::CACHE_TAG,
	        Mage_Cms_Model_Block::CACHE_TAG,
	        Ves_Verticalmenu_Model_Verticalmenu::CACHE_WIDGET_TAG
	    ) );
	}
}