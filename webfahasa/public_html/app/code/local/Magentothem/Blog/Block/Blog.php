<?php

class Magentothem_Blog_Block_Blog extends Magentothem_Blog_Block_Abstract {

    public function getPosts() {
        $collection = parent::_prepareCollection();
        $tag = $this->getRequest()->getParam('tag');
        if ($tag) {
            $collection->addTagFilter(urldecode($tag));
        }
        parent::_processCollection($collection);
        return $collection;
    }

    public function getCatIds() {
        return getTopCatIds();
    }

    public function getCategoryTitle($cat_id) {
        if ($cat_id !== null && $cat_id !== "" && $cat_id) {
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $query = "select cat_id, title, identifier from fhs_magentothem_blog_cat where cat_id = :cat_id";
            $query_blog_binds = array('cat_id' => $cat_id);
            $catResult = $connection->fetchAll($query, $query_blog_binds);
            return $catResult;
        }
    }

    public function getCategoryPosts($cat_id) {
        if ($cat_id !== null && $cat_id !== "" && $cat_id) {
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $query = "select mb.title as blogTitle, mb.short_content, mb.identifier as url,mb.post_content, mb.thumbnailimage, bc.title as categoryTitle, mb.update_time
                from fhs_magentothem_blog_post_cat pc
                       join fhs_magentothem_blog_cat bc on bc.cat_id = pc.cat_id
                       join fhs_magentothem_blog mb on mb.post_id = pc.post_id
                where pc.cat_id = :cat_id
                order by pc.cat_id, mb.created_time desc
                limit 3";
            $query_blog_binds = array('cat_id' => $cat_id);
            $blog_result = $connection->fetchAll($query, $query_blog_binds);
            return $blog_result;
        }
    }

    public function getLatestPost($post_id) {
        if ($post_id !== null && $post_id !== "" && $post_id) {
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $query = "select mb.title as blogTitle, mb.short_content,
                    concat('blog/', mb.identifier) as url,
                    mb.thumbnailimage as imageUrl,
                    bc.title as categoryTitle, mb.update_time
                    from fhs_magentothem_blog_post_cat pc
                    join fhs_magentothem_blog_cat bc on bc.cat_id = pc.cat_id
                    join fhs_magentothem_blog mb on mb.post_id = pc.post_id
                    where mb.post_id in (" . $post_id . ");";
            $latest_post_result = $connection->fetchAll($query);
            return $latest_post_result;
        }
    }

    protected function _prepareLayout() {
        if ($this->isBlogPage() && ($breadcrumbs = $this->getCrumbs())) {
            parent::_prepareMetaData(self::$_helper);
            $tag = $this->getRequest()->getParam('tag', false);
            if ($tag) {
                $tag = urldecode($tag);
                $breadcrumbs->addCrumb(
                        'blog', array(
                    'label' => self::$_helper->getTitle(),
                    'title' => $this->__('Return to ' . self::$_helper->getTitle()),
                    'link' => $this->getBlogUrl(),
                        )
                );
                $breadcrumbs->addCrumb(
                        'blog_tag', array(
                    'label' => $this->__('Tagged with "%s"', self::$_helper->convertSlashes($tag)),
                    'title' => $this->__('Tagged with "%s"', $tag),
                        )
                );
            } else {
                $breadcrumbs->addCrumb('blog', array('label' => self::$_helper->getTitle()));
            }
        }
    }

    public function getTopPostSlider() {
        $postIdList = Mage::getStoreConfig('blog/blog/postIds');
        $arr = explode(",", $postIdList);

        $postList = $this->getLatestPost($postIdList);

        return $postList;
    }

}
