<?php
class Mage_Wishlist_Block_Customer_Wishlist extends Mage_Wishlist_Block_Abstract
{
    protected function _prepareCollection($collection)
    {
        $collection->setInStockFilter(true)->setOrder('added_at', 'DESC');
        return $this;
    }
}
