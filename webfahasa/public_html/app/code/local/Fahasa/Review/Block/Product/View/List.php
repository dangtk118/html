<?php
// the same core, need overdride  because overdride Fahasa_Review_Block_Product_View
class Fahasa_Review_Block_Product_View_List extends Fahasa_Review_Block_Product_View
{
    protected $_forceHasOptions = false;

    public function getProductId()
    {
        return Mage::registry('product')->getId();
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($toolbar = $this->getLayout()->getBlock('product_review_list.toolbar')) {
            $toolbar->setCollection($this->getReviewsCollection());
            $this->setChild('toolbar', $toolbar);
        }

        return $this;
    }

    protected function _beforeToHtml()
    {
        $this->getReviewsCollection()
            ->load()
            ->addRateVotes();
        return parent::_beforeToHtml();
    }

    public function getReviewUrl($id)
    {
        return Mage::getUrl('review/product/view', array('id' => $id));
    }
}
