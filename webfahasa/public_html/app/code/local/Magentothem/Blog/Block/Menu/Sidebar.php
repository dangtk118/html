<?php

class Magentothem_Blog_Block_Menu_Sidebar extends Magentothem_Blog_Block_Abstract
{
    public function getRecent()
    {
        // widget declaration
        if ($this->getBlogWidgetRecentCount()) {
            $size = $this->getBlogWidgetRecentCount();
        } else {
            // standard output
            $size = self::$_helper->getRecentPage();
        }

        if ($size) {
            $collection = clone self::$_collection;
            $collection->setPageSize($size);

            foreach ($collection as $item) {
                $item->setAddress($this->getBlogUrl($item->getIdentifier()));
            }
            return $collection;
        }
        return false;
    }
    
    public function getLatestPost($post_id) {
        if ($post_id !== null && $post_id !== "" && $post_id) {
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $query = "select mb.title as blogTitle, mb.short_content as shortContent,
                    mb.identifier as url,
                    mb.thumbnailimage as imageUrl,
                    bc.title as categoryTitle, mb.update_time as updatedTime, mb.created_time as createdTime, mb.user
                    from fhs_magentothem_blog_post_cat pc
                    join fhs_magentothem_blog_cat bc on bc.cat_id = pc.cat_id
                    join fhs_magentothem_blog mb on mb.post_id = pc.post_id
                    where mb.post_id in (" . $post_id . ");";
            $latest_post_result = $connection->fetchAll($query);
            return $latest_post_result;
        }
    }

    public function getLeftPostSlider() {
        Mage::log("get left post slider", null, "blogUI.log");
        $postIdList = Mage::getStoreConfig('blog/blog/leftPostIds');
        $arr = explode(",", $postIdList);

        $postList = $this->getLatestPost($postIdList);

        return $postList;
    }

    public function getCategories()
    {
        $collection = Mage::getModel('blog/cat')
            ->getCollection()
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->setOrder('sort_order', 'asc')
        ;
        foreach ($collection as $item) {
            $item->setAddress($this->getBlogUrl(array(self::$_catUriParam, $item->getIdentifier())));
        } 
        return $collection;
    }
	
	public function getContentBlogSidebar($_description, $count) {
	   $short_desc = substr($_description, 0, $count);
	   if(substr($short_desc, 0, strrpos($short_desc, ' '))!='') {
			$short_desc = substr($short_desc, 0, strrpos($short_desc, ' '));
			$short_desc = $short_desc.'...';
		}
	   return $short_desc;
	}

    // protected function _beforeToHtml()
    // {
        // return $this;
    // }

    // protected function _toHtml()
    // {
        // if (self::$_helper->getEnabled()) {
            // $parent = $this->getParentBlock();
            // if (!$parent) {
                // return null;
            // }

            // $showLeft = Mage::getStoreConfig('blog/menu/left');
            // $showRight = Mage::getStoreConfig('blog/menu/right');

            // $isBlogPage = Mage::app()->getRequest()->getModuleName() == Magentothem_Blog_Helper_Data::DEFAULT_ROOT;

            // $leftAllowed = ($isBlogPage && ($showLeft == 2)) || ($showLeft == 1);
            // $rightAllowed = ($isBlogPage && ($showRight == 2)) || ($showRight == 1);

            // if (!$leftAllowed && ($parent->getNameInLayout() == 'left')) {
                // return null;
            // }
            // if (!$rightAllowed && ($parent->getNameInLayout() == 'right')) {
                // return null;
            // }

            // return parent::_toHtml();
        // }
    // }
}