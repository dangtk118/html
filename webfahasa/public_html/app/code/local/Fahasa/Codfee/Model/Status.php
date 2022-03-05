<?php

// Trung: This class uses static method which makes it difficult to customize it by inheritance.
// In this case, I did not introduce the getModuleStr method like other classes.

class Fahasa_Codfee_Model_Status extends Magebuzz_Vietnamshipping_Model_Status {
	const STATUS_ENABLED	= 1;
	const STATUS_DISABLED	= 2;
     
	static public function getOptionArray() {
		return array(
			self::STATUS_ENABLED    => Mage::helper("codfee")->__('Enabled'),
			self::STATUS_DISABLED   => Mage::helper("codfee")->__('Disabled')
		);
	}
}