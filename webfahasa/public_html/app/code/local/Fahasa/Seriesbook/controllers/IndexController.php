<?php

class Fahasa_Seriesbook_IndexController extends Mage_Core_Controller_Front_Action {
    
    public function seriesAction() {
	$this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle($this->__("Series book"));
	$breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
	$breadcrumbs->addCrumb('home', array('label'=>$this->__('Home'), 'title'=>$this->__('Go to Home Page'), 'link'=>Mage::getBaseUrl()));
	$breadcrumbs->addCrumb('all series', array('label'=>$this->__('All Series'), 'title'=>$this->__('All Series'), 'link'=>'/all-series.html'));
	$breadcrumbs->addCrumb('seriesbook', array('label'=>$this->__('Series book'), 'title'=>$this->__('Series book')));
        $this->renderLayout();
	
	if(Mage::getSingleton('customer/session')->isLoggedIn()){
	    $customer = Mage::getSingleton('customer/session')->getCustomer();
	}
	Mage::helper("weblog")->FpointStorePage($this->getLayout()->getBlock('head')->getTitle(), 'Series_Book_page', $customer?$customer->getEntityId():'guest');
    }
    
    public function getProductsBySeriesIdAction(){
	$series_id = $this->getRequest()->getParam('series_id', 0);
	$is_first = $this->getRequest()->getParam('is_first', 0);
	$sort_by = $this->getRequest()->getParam('sort_by', '');
	$page = $this->getRequest()->getParam('page', 1);
	$limit = $this->getRequest()->getParam('limit', 12);
	
	if(!is_numeric($series_id)){$series_id = 0;}
	if(!is_numeric($page)){$page = 1;}
	if(!is_numeric($limit)){$limit = 12;}
	$result = Mage::helper('seriesbook')->getProductsBySeriesId($series_id, $sort_by, $page, $limit, $is_first);
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function getSeriesSetAction(){
	$sort_by = $this->getRequest()->getParam('sort_by', '');
	$page = $this->getRequest()->getParam('page', 1);
	$limit = $this->getRequest()->getParam('limit', 12);
	
	if(!is_numeric($page)){$page = 1;}
	if(!is_numeric($limit)){$limit = 12;}
	$result = Mage::helper('seriesbook')->getSeriesSet($sort_by, $page, $limit);
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function getSeriesBookAction(){
	$is_follow = $this->getRequest()->getPost('is_follow', 0);
	$page = $this->getRequest()->getPost('page', 1);
	$limit = $this->getRequest()->getPost('limit', 12);
	
	if(!is_numeric($page)){$page = 1;}
	if(!is_numeric($limit)){$limit = 12;}
	$result = Mage::helper('seriesbook')->getSeriesBook($is_follow, $page, $limit);
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function setSeriesBookAction(){
	$series_id = $this->getRequest()->getPost('series_id', 0);
	$is_over= $this->getRequest()->getPost('is_over', false);
	if($is_over == 'false'){
	    $is_over = false;
	}
	$is_follow = $this->getRequest()->getPost('is_follow', false);
	if($is_follow == 'false'){
	    $is_follow = false;
	}
	$page = $this->getRequest()->getPost('page', 1);
	$limit = $this->getRequest()->getPost('limit', 12);
	
	if(!is_numeric($series_id)){$series_id = 0;}
	if(!is_numeric($page)){$page = 1;}
	if(!is_numeric($limit)){$limit = 12;}
	$result = Mage::helper('seriesbook')->setSeriesBook($series_id, $is_over, $is_follow, $page, $limit);
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
    
    public function setSeriesBookPageAction(){
	$series_id = $this->getRequest()->getPost('series_id', 0);
	$is_over= $this->getRequest()->getPost('is_over', false);
	if($is_over == 'false'){
	    $is_over = false;
	}
	$is_follow = $this->getRequest()->getPost('is_follow', false);
	if($is_follow == 'false'){
	    $is_follow = false;
	}
	
	if(!is_numeric($series_id)){$series_id = 0;}
	$result = Mage::helper('seriesbook')->setSeriesBookPage($series_id, $is_follow);
        return $this->getResponse()->setBody(json_encode($result))
                ->setHeader('Content-type', 'application/json');
    }
}
