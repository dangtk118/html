<?php

class ProductInfo implements BaseEntity {

    private $manufactory;
    private $category;
    private $count;

    /**
     * 
     * @param string $manufactory
     * @param string $category
     * @param int $count
     */
    public function __construct($manufactory, $category, $count) {
        $this->manufactory = $manufactory;
        $this->category = $category;
        $this->count = $count;
    }

    public function getManufactory() {
        return $this->manufactory;
    }

    public function getCategory() {
        return $this->category;
    }

    public function getCount() {
        return $this->count;
    }
    
    public function toArray() {
        $arr = array(
            'manufactory' => $this->manufactory,
            'category' => $this->category,
            'count' => $this->count
        );

        return $arr;
    }

    public function toJson() {
        return JsonUtil::toJson($this);
    }

}
