<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Observers
 *
 * @author theanh
 */
class Fahasa_Weblog_Model_Observer {

    public function AddProductToCart($observer) {
        $product = $observer->getEvent()->getData('product');
        Mage::helper("weblog")->AddProductToCart($product);
    }
    
    public function RemoveProductFromCart($observer) {
        $quoteItem = $observer->getEvent()->getQuoteItem();
        Mage::helper("weblog")->RemoveProductFromCart($quoteItem);
    }

    public function FinishCheckout($observer) {
        $quote = $observer->getEvent()->getData('quote');
        Mage::helper("weblog")->FinishCheckout($quote);
    }

    public function SaveShippingMethod($observer) {
        $shipping = $_POST['shipping_method'];
        Mage::helper("weblog")->SaveShippingMethod($shipping);
    }

    public function LogIn($observer) {
        Mage::helper("weblog")->LogIn();
    }

    public function SavePaymentMethod($observer) {
        $payment = $observer->getPayment()->getMethod();
        Mage::helper("weblog")->SavePaymentMethod($payment);
    }

    public function SaveBillingAddress($observer) {
        // not caught
    }
    
    public function Search($observer){                
        Mage::helper("weblog")->Search();
    }
    
    public function ViewProduct($observer) {//view product
        $product = $observer->getEvent()->getData('product');
        Mage::helper("weblog")->ViewProduct($product);
    }

    public function ViewCategory($observer) {
        $category = $observer->getEvent()->getData('category');
        Mage::helper("weblog")->ViewCategory($category);
    }
    
    public function ViewCmsPage($observer) {
        $page = $observer->getEvent()->getPage();
        Mage::helper("weblog")->ViewCmsPage($page);
    }
}
