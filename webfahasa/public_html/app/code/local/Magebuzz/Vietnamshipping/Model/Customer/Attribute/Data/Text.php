<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Vietnamshipping_Model_Customer_Attribute_Data_Text extends Mage_Eav_Model_Attribute_Data_Text {
	public function validateValue($value) {
		$attribute  = $this->getAttribute();	
		// if (!Mage::helper('vietnamshipping')->isVietnamShippingEnabled()) {
			// return parent::validateValue($value);
		// }
		
		if ($attribute->getAttributeCode() == 'postcode') {
			$countryId = $this->getExtractedData('country_id');
			$optionalZip = Mage::helper('directory')->getCountriesWithOptionalZip();
			if (!in_array($countryId, $optionalZip)) {
				return parent::validateValue($value);
			}
			return true;
		}
		else {
			return parent::validateValue($value);
		}
	}
}