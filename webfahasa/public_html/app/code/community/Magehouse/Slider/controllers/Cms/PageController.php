<?php

require_once('app/code/core/Mage/Cms/controllers/PageController.php');

class Magehouse_Slider_Cms_PageController extends Mage_Cms_PageController {

    public function viewAction() {

        if ($this->getRequest()->isXmlHttpRequest() and ( !(int) $this->getRequest()->getParam('infParam'))) {
            $pageId = $this->getRequest()
                    ->getParam('page_id', $this->getRequest()->getParam('id', false));
            $page = Mage::getSingleton('cms/page');
            $page->load($pageId);
            $helper = Mage::helper('cms');
            $processor = $helper->getPageTemplateProcessor();
            $html = $processor->filter($page->getData()['content']);
            $blocks = $this->getLayout()->getAllBlocks();
            foreach ($blocks as $k => $block) {
                if (strcmp($block->getType(), 'custom_listing/products') == 0 ||
                        strcmp($block->getType(), 'custom_listing/attribute') == 0) {
                    $productList = $block->toHtml();
                    break;
                }
            }

            $response['status'] = 'SUCCESS';
            $response['productlist'] = $productList;
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
            return;
        } else {
            parent::viewAction();
        }
    }

}
