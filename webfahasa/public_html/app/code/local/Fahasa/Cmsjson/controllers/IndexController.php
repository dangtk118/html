<?php

class Fahasa_Cmsjson_IndexController extends Mage_Core_Controller_Front_Action
{
    public function getBlockAction()
    {
	$block_id = $this->getRequest()->getParam('block_id', 0);
        $data = Mage::helper('cmsjson')->getBlock($block_id);
	
        return $this->getResponse()->setBody(json_encode($data))
                ->setHeader('Content-Type', 'application/json');
    }
}
