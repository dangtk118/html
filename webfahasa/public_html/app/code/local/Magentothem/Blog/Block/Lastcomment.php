<?php

class Magentothem_Blog_Block_Lastcomment extends Magentothem_Blog_Block_Abstract {

    public function getComment() {
        $collection = Mage::getModel('blog/comment')
                ->getCollection()
                ->setOrder('created_time', 'DESC');
        $collection->getSelect()
                ->joinLeft(
                        array('blog_main' => $collection->getTable('blog/blog')), 'main_table.post_id=blog_main.post_id', array('blog_main.title', 'blog_main.identifier', 'blog_main.title')
        );
        $collection->getSelect()
                ->where('main_table.status = 2')
                ->limit(10);
        foreach ($collection as $item) {
            $item->setAddress($this->getBlogUrl($item->getIdentifier()));
        }
        return $collection;
    }

}
