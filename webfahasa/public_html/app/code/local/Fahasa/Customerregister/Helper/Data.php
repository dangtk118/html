<?php
class Fahasa_Customerregister_Helper_Data extends Mage_Core_Helper_Abstract{
    
    /**
     * Determine if the current email is a fahasa tmdt fake email. This include
     * @fahasatmdt.com
     * @fahasa.tmdt.com
     * @tmdt.com
     * @tmdtfahasa.com
     * @tmdt.fahasa.com
     */
    public static function isInternalFakeFHSEmail($email){
        if(strpos($email, "@fahasatmdt.com") ||
                strpos($email, "@fahasa.tmdt.com") ||
                strpos($email, "@tmdt.com") ||
                strpos($email, "@tmdtfahasa.com") ||
                strpos($email, "@tmdt.fahasa.com")){
            return true;
        }
        
        if (preg_match("/^notverify.+@fahasa.com$/i", $email)){
            return true;
        }
        
        return false;
    }    
}
