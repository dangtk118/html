<?php

class Magentothem_Blog_Model_Blog extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('blog/blog');
    }

    public function getShortContent()
    {
        $content = $this->getData('short_content');
        if (Mage::getStoreConfig(Magentothem_Blog_Helper_Config::XML_BLOG_PARSE_CMS)) {
            $processor = Mage::getModel('core/email_template_filter');
            $content = $processor->filter($content);
        }
        return $content;
    }

    public function getPostContent()
    {
        $content = $this->getData('post_content');
        if (Mage::getStoreConfig(Magentothem_Blog_Helper_Config::XML_BLOG_PARSE_CMS)) {
            $processor = Mage::getModel('core/email_template_filter');
            $content = $processor->filter($content);
        }
        return $content;
    }

    public function _beforeSave()
    {
        if (is_array($this->getData('tags'))) {
            $this->setData('tags', implode(",", $this->getData('tags')));
        }
        return parent::_beforeSave();
    }
    
    public function getCats()
    {
        $route = Mage::getStoreConfig('blog/blog/route');
        if ($route == "") {
            $route = "blog";
        }
        $route = Mage::getUrl($route);

        $cats = Mage::getModel('blog/cat')->getCollection()
            ->addPostFilter($this->getId())
            ->addStoreFilter(Mage::app()->getStore()->getId())
        ;

        $catUrls = array();
        foreach ($cats as $cat) {
            $catUrls[$cat->getTitle()] = $route . "cat/" . $cat->getIdentifier();
        }
        return $catUrls;
    }
}