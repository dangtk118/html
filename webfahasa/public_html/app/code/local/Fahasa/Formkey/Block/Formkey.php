<?php
class Fahasa_Formkey_Block_Formkey extends Mage_Core_Block_Template{
    /**
     * Return form key
     * @return type
     */
    public function getFormKey(){
        return Mage::getSingleton('core/session')->real_getFormKey();
    }
    
    /**
     * Get the current base64 encode of the current url     
     */
    public function getEncodeCurrentUrl(){
        $currentUrl = Mage::helper('checkout/cart')->getCurrentUrl();
        return Mage::helper('core')->urlEncode($currentUrl);
    }
    
    /**
     * Return the URL for add to cart, wishlist, or compare
     * @return type
     */
    public function getAddUrl($partialUrl){
        return Mage::getUrl($partialUrl, array());
    }
}
?>
