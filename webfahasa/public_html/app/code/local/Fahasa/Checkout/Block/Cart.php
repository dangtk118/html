<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Fahasa_Checkout_Block_Cart extends Mage_Checkout_Block_Cart {

    public function chooseTemplate()
    {

        $this->setTemplate($this->getCartTemplate());
    }

}
