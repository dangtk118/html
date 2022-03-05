<?php 
class Magebuzz_Vietnamshipping_Model_Salesrule_Rule_Condition_Address extends Mage_SalesRule_Model_Rule_Condition_Address {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        }     
        
	public function loadAttributeOptions() {
		if (Mage::registry('rule_data')) {
			$rule = Mage::registry('rule_data');
			if ($rule instanceof Magebuzz_Vietnamshipping_Model_Rule) {
				$attributes = array(
					'base_subtotal' => Mage::helper('salesrule')->__('Subtotal'),
					'total_qty' => Mage::helper('salesrule')->__('Total Items Quantity'),
					'weight' => Mage::helper('salesrule')->__('Total Weight'),	
				);
				$this->setAttributeOption($attributes);

				return $this;
			}
		}
			
		return parent::loadAttributeOptions();
	}

    /**
     * Override the validate method to make 'salesrule' use base subtotal with tax,
     * instead of just base subtotal when set rule for shipping. This is because the price
     * on the label is already include tax.
     */
    public function validate(Varien_Object $object, $all = false, $true = false)
    {
        $address = $object;
        if (!$address instanceof Mage_Sales_Model_Quote_Address) {
            if (!$object->getQuote()->isVirtual()) {
                $address = $object->getQuote()->getShippingAddress();
                $address->setBaseSubtotal($address->getBaseSubtotalInclTax());
                $object->getQuote()->setShippingAddress($address);
            }
        }
        return parent::validate($object, $all, $true);
    }
}
