<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if (!@class_exists('SphinxClient')) {
    include Mage::getBaseDir().DS.'lib'.DS.'Sphinx'.DS.'sphinxapi.php';
}

class Fahasa_PhieuBinhChon_AjaxController extends Mage_Core_Controller_Front_Action
{
    public function suggestAction()
    {
        if (!$this->getRequest()->getParam('q', false)) {
            $this->getResponse()->setRedirect(Mage::getSingleton('core/url')->getBaseUrl());
        }
        
        //Query sphinx for suggest
        $param = $this->getRequest()->getParam('q');
        $sphinxResult = $this->getSuggestion($param);
        
        if ($sphinxResult !== false && $sphinxResult["total"] > 0) {
            //We get the product names
            $matchedProductIds = array();
            foreach ($sphinxResult['matches'] as $data) {
                array_push($matchedProductIds, $data['attrs']["product_id"]);
            }
                        
            $collection = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToSelect('name')
                    ->addAttributeToFilter('entity_id', array('in' => $matchedProductIds))
                    ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
            $orderString = $this->getOrderStringForProductId($matchedProductIds);
            $collection->getSelect()
                   ->order(new Zend_Db_Expr($orderString));
        
            //Return suggestion result
            $html = '<ul><li style="display:none"></li>';
            $count = count($collection) - 1;
            $index = 0;
            foreach ($collection as $item) {               
                if ($index == 0) {
                    $item['row_class'] .= ' first';
                }

                if ($index == $count) {
                    $item['row_class'] .= ' last';
                }

                $html .= '<li title="' . htmlspecialchars($item['name']) . '" class="' . $item['row_class'] . '">'
                        . htmlspecialchars($item['name']) . '</li>';
                $index++;
            }

            $html.= '</ul>';
            $this->getResponse()->setBody($html);            
        }                              
    }
    
    function getSuggestion($input) {
        $client = new SphinxClient();
        $client->setMaxQueryTime(2000);
        $client->setLimits(0, 20);
        $client->setSortMode(SPH_SORT_RELEVANCE);
        //$client->setMatchMode($this->_matchMode);        
        $client->setMatchMode(SPH_MATCH_EXTENDED);
        //$client->setRankingMode(SPH_PROXIMITY_BM25);
	$client->setRankingMode(SPH_RANK_PROXIMITY_BM25);
        $sphinxServer = Mage::getStoreConfig('searchsphinx/general/external_host');
        $sphinxPort = Mage::getStoreConfig('searchsphinx/general/external_port');
        $client->setServer($sphinxServer, intval($sphinxPort));   
        $client->SetFilter('store_id', array(Mage::app()->getStore()->getId()));
        $sphinxResult = $client->query($input, "prod_name");
        
        if ($sphinxResult === false || $sphinxResult["total"] < 1) {
            $client->setMatchMode(SPH_MATCH_ANY);
            $sphinxResult = $client->query($input, "prod_name");
        }
        
        return $sphinxResult;
    }
    
    function getOrderStringForProductId($product_ids){
        $orderString = array('CASE e.entity_id');
        foreach($product_ids as $i => $productId) {
                $orderString[] = 'WHEN '.$productId.' THEN '.$i;
        }
        $orderString[] = 'END';
        $orderString = implode(' ', $orderString);
        return $orderString;
    }    
}