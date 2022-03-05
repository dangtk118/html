<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Fahasa_Review_Helper_Data extends Mage_Page_Block_Html_Breadcrumbs {
    public function getCategoryPath($_product) {

        $_category = Mage::getModel('catalog/category')->load(end($_product->getCategoryIds()));
        $catids = str_replace("1/2/", "", $_category->getPath());
        $cats = explode("/", $catids);
        $productCrumb = array();
        $itemsCrumb = array();
        $i = 1;

        // home Crumb
        $homeCrumb = (object) [
                    "@type" => "ListItem",
                    "position" => $i ++,
                    "item" => (object) [
                        "@id" => Mage::getBaseUrl(),
                        "name" => "Fahasa"
                    ]
        ];
        array_push($itemsCrumb, $homeCrumb);

        // category Crumb
        foreach ($cats as $category_id) {
            $_cat = Mage::getModel('catalog/category')->load($category_id);
            $item = array('label' => $_cat->getName(),
                'title' => $_cat->getName(),
                'link' => $_cat->getUrl(),
                'first' => false,
                'last' => false,
                'readonly' => false);
            array_push($productCrumb, $item);

            $itemCrumb = (object) [
                        "@type" => "ListItem",
                        "position" => $i ++,
                        "item" => (object) [
                            "@id" => $_cat->getUrl(),
                            "name" => $_cat->getName()
                        ]
            ];
            array_push($itemsCrumb, $itemCrumb);
        }
        
        foreach($productCrumb as $_crumbName=>$_crumbInfo) {
            $_cateLink = $_crumbInfo['link'];
        } 
    }
}