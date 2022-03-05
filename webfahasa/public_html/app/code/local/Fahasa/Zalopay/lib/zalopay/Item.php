<?php

class Item implements BaseEntity {

    private $itemId;
    private $itemName;
    private $itemQuantity;
    private $itemPrice;

    public function __construct() {
        
    }

    public function getItemId() {
        return $this->itemId;
    }

    public function getItemName() {
        return $this->itemName;
    }

    public function getItemQuantity() {
        return $this->itemQuantity;
    }

    public function getItemPrice() {
        return $this->itemPrice;
    }

    public function setItemId($itemId) {
        $this->itemId = $itemId;
    }

    public function setItemName($itemName) {
        $this->itemName = $itemName;
    }

    public function setItemQuantity($itemQuantity) {
        $this->itemQuantity = $itemQuantity;
    }

    public function setItemPrice($itemPrice) {
        $this->itemPrice = $itemPrice;
    }
    
    public function toArray() {
        $arr = array(
            'itemid' => $this->itemId,
            'itemname' => $this->itemName,
            'itemquantity' => $this->itemQuantity,
            'itemprice' => $this->itemPrice
        );

        return $arr;
    }

    public function toJson() {
        return JsonUtil::toJson($this);
    }

}
