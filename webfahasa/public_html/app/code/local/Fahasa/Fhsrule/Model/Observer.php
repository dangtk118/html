<?php

class Fahasa_Fhsrule_Model_Observer {
    
    // Discount Original Action
    const DISCOUNT_ORIGINAL_ACTION = 'by_percent_original_price';
    
    public function addNewActionDiscountOriginal($observer){
        
        // Extract the form field
        $field = $observer->getForm()->getElement('simple_action');
        // Extract the field values
        $options = $field->getValues();
        // Add the new value
        $options[] = array(
            'value' => self::DISCOUNT_ORIGINAL_ACTION,
            'label' => 'Custom: percent of original price discount'
        );
        // Set the field
        $field->setValues($options);
    }
    
    public function salesruleValidatorProcess($observer){
        // $rule typeof Mage_SalesRule_Model_Rule
        $rule = $observer->getEvent()->getRule();
        
        if($rule->getSimpleAction() == self::DISCOUNT_ORIGINAL_ACTION) {
            // $item typeof Mage_Sales_Model_Quote_Item
            $item = $observer->getEvent()->getItem();
            // Number of products
            $qty = $item->getQty();
            if($item->getParentItem()){
                $qty = $item->getParentItem()->getQty();
            }
            
            //Mage::log("Item Qty: ". $qty, null, "buffet.log");
            
            $rulePercent = min(100, $rule->getDiscountAmount());
            $_rulePct = $rulePercent/100;
            
            //$discountAmount    = ($qty * $itemPrice - $item->getDiscountAmount()) * $_rulePct;
            //$baseDiscountAmount = ($qty * $baseItemPrice - $item->getBaseDiscountAmount()) * $_rulePct;
            //get discount for original price
            
            $itemOriginalPrice = $item->getProduct()->getPrice(); /// Mage::helper('tax')->getPrice($item, $item->getOriginalPrice(), true);
            //Mage::log("Original Price: " . $itemOriginalPrice, null, "buffet.log");
            
            ///$baseItemOriginalPrice  = $item->getProduct()->getBasePrice();
            $originalDiscountAmount    = ($qty * $itemOriginalPrice - $item->getDiscountAmount()) * $_rulePct;
            //Mage::log("Original Price: ". $item->getPriceInclTax(), null, "buffet.log");
            //Mage::log("Original Discount: ". $originalDiscountAmount, null, "buffet.log");
            
            //Mage::log("Real Discount Amount: " . $originalDiscountAmount, null, "buffet.log");
            
            $originalDiscountAmount = $qty*$item->getPriceInclTax() - $originalDiscountAmount;
            //Mage::log("Re-calculate Discount Amount: " . $originalDiscountAmount, null, "buffet.log");
            
            if (!$rule->getDiscountQty() || $rule->getDiscountQty()>$qty) {
                $discountPercent = min(100, $item->getDiscountPercent()+$rulePercent);
                //Mage::log("Discount: ". $discountPercent, null, "buffet.log");
                $item->setDiscountPercent($discountPercent);
            }
            
            // Setting up the effective discount, basically this is the discount value
            $result = $observer->getResult();
            $result->setDiscountAmount($originalDiscountAmount);
            $result->setBaseDiscountAmount($originalDiscountAmount);
        }
    }
}
