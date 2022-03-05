<?php
class Fahasa_Tryout_Block_Index extends Mage_Core_Block_Template
{
    protected function _beforeToHtml()
    {
        $toolbar = $this->getToolbarBlock();
        $collection = $this->_getPostsCollection();
        $toolbar->setCollection($collection);     /*(Add toolbar to collection)*/
        return parent::_beforeToHtml();
    }
}
