<?php
require_once 'abstract.php';
class Mage_Shell_Pricerules extends Mage_Shell_Abstract
{
    public function run()
    {
        //query all categories, only care the "CK "
        $categories = Mage::getResourceModel('catalog/category_collection')
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('parent_id', 2);
        $ids = array();
	foreach ($categories as $cat) {
            $catName = $cat->getName();
            if (substr(strtoupper($catName), 0, strlen("CK")) === "CK") {
                $prodInCatIds = $cat->getProductCollection()->getAllIds();  
                $ids = array_merge($ids, $prodInCatIds);
            }
	}

        $collection = Mage::getResourceModel('catalog/product_collection')
                            ->addAttributeToSelect('sku')
                            ->addFieldToFilter('entity_id', $ids);

        $count = count($collection);
        $i = 0;
          
        foreach($collection as $product) {
            $i++;
            $productWebsiteIds = $product->getWebsiteIds();
            $rules = Mage::getModel('catalogrule/rule')->getCollection()
                ->addFieldToFilter('is_active', 1);
            foreach ($rules as $rule) {
                $websiteIds = array_intersect($productWebsiteIds, $rule->getWebsiteIds());
                $rule->applyToProduct($product, $websiteIds);
                $rule->clearInstance();
            }
            echo "Applied rules to " . $product->getSku() . " (" . number_format(memory_get_usage() / 1024 / 1024 / 1024, 2) . "G, " . ($count - $i) . " products left)\n";
            $product->clearInstance();
        }
        $resource = Mage::getResourceSingleton('catalogrule/rule');
        $resource->applyAllRulesForDateRange();
    }
}

require_once str_replace('shell','',getcwd()) . 'app/Mage.php';
$shell = new Mage_Shell_Pricerules();
$shell->run();
