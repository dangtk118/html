<?php
class Fahasa_Coreextended_Block_Product_List_Related extends Mage_Catalog_Block_Product_List_Related
{    
    protected $_reviewsHelperBlock;
    
    public function getFahasaSummaryHtml($product)
    {
        $this->_reviewsHelperBlock = $this->getLayout()->createBlock('review/helper');
        $html =  $this->_reviewsHelperBlock->getFhsReviewSummary($product);
        return $html;
    }
}
