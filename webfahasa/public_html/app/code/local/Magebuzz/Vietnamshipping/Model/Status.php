<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/


// Trung: This class uses static method which makes it difficult to customize it by inheritance.
// In this case, I did not introduce the getModuleStr method like other classes.

class Magebuzz_Vietnamshipping_Model_Status extends Varien_Object {
	const STATUS_ENABLED	= 1;
	const STATUS_DISABLED	= 2;
     
	static public function getOptionArray() {
		return array(
			self::STATUS_ENABLED    => Mage::helper("vietnamshipping")->__('Enabled'),
			self::STATUS_DISABLED   => Mage::helper("vietnamshipping")->__('Disabled')
		);
	}
}