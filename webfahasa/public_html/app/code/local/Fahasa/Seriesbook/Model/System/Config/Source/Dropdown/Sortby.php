<?php

class Fahasa_Seriesbook_Model_System_Config_Source_Dropdown_Sortby
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => '',
                'label' => Mage::helper('seriesbook')->__('Episode Newest'),
            ),
            array(
                'value' => 'num_orders',
                'label' => Mage::helper('seriesbook')->__('Weekly BestSeller'),
            ),
            array(
                'value' => 'num_orders_month',
                'label' => Mage::helper('seriesbook')->__('Monthly BestSeller'),
            ),
            array(
                'value' => 'num_orders_year',
                'label' => Mage::helper('seriesbook')->__('Yearly BestSeller'),
            ),
            array(
                'value' => 'product_view',
                'label' => Mage::helper('seriesbook')->__('Weekly Trending'),
            ),
            array(
                'value' => 'product_view_month',
                'label' => Mage::helper('seriesbook')->__('Monthly Trending'),
            ),
            array(
                'value' => 'product_view_year',
                'label' => Mage::helper('seriesbook')->__('Yearly Trending'),
            ),
            array(
                'value' => 'discount_percent',
                'label' => Mage::helper('seriesbook')->__('Discount'),
            ),
            array(
                'value' => 'min_price',
                'label' => Mage::helper('seriesbook')->__('Sale Price'),
            ),
            array(
                'value' => 'created_at',
                'label' => Mage::helper('seriesbook')->__('Created At'),
            ),
        );
    }
}