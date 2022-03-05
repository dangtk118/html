<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */
 
class Mageplace_Flashmagazine_Helper_Version extends Mage_Core_Helper_Abstract
{
	const ENTERPRISE_EDITION = 'EE';
	const PRO_EDITION = 'PE';
	const COMMUNITY_EDITION = 'CE';
	
	
	public function getEdition()
	{
		static $edition;
		
		if(is_null($edition)) {
			if(file_exists('LICENSE_EE.txt')) {
				$edition = self::ENTERPRISE_EDITION;
			} elseif(file_exists('LICENSE_PRO.html')) {
				$edition = self::PRO_EDITION;
			} else {
				try {
					if(class_exists('Enterprise_Cms_Helper_Data')) {
						$edition = self::ENTERPRISE_EDITION;
					}
				} catch(Exception $e) {	}
				
				$edition = self::COMMUNITY_EDITION;
			}			
		}
		
		
		return $edition;
	}
	
	public function isCE()
	{
		return $this->getEdition() == self::COMMUNITY_EDITION;
	}
	
	public function isPE()
	{
		return $this->getEdition() == self::PRO_EDITION;
	}
	
	public function isEE()
	{
		return $this->getEdition() == self::ENTERPRISE_EDITION;
	}
	
	public function isOld()
	{
		return ($this->getEdition()=='EE' && version_compare(Mage::getVersion(), '1.11.0.0.', '<')===true)
			|| ($this->getEdition()=='PE' && version_compare(Mage::getVersion(), '1.11.0.0.', '<')===true)
			|| ($this->getEdition()=='CE' && version_compare(Mage::getVersion(), '1.6.0.0.', '<')===true);
	}
}
