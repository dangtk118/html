<?php class Fahasa_Catalog_Model_Config extends Mage_Catalog_Model_Config
{
    public function getAttributeUsedForSortByArray()
    {
        return array_merge(
            array(
                '' => 'Sort By',
                'num_orders' => Mage::helper('catalog')->__('Weekly BestSeller'),
                'num_orders_month' => Mage::helper('catalog')->__('Monthly BestSeller'),
                'num_orders_year' => Mage::helper('catalog')->__('Yearly BestSeller'),
                'product_view' => Mage::helper('catalog')->__('Weekly Trending'),
                'product_view_month' => Mage::helper('catalog')->__('Monthly Trending'),
                'product_view_year' => Mage::helper('catalog')->__('Yearly Trending'),
                'discount_percent' => Mage::helper('catalog')->__('Discount'),
                'min_price' => Mage::helper('catalog')->__('final_price')
                ),
            parent::getAttributeUsedForSortByArray()
        );
    }
}