<?php
require_once 'abstract.php';
class Mage_Shell_Pricerules_Part extends Mage_Shell_Abstract
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

        $ids = array_unique($ids);
        $collection = Mage::getResourceModel('catalog/product_collection')
                            ->addAttributeToSelect('sku')
                            ->addFieldToFilter('entity_id', $ids);

        $count = count($collection);
        
        $partIndex = $this->getArg('partIndex');
        $totalPart = $this->getArg('totalPart');
        $startIndex = $partIndex * floor($count / $totalPart);
        $endIndex = $startIndex + floor($count / $totalPart) - 1;
        if ($partIndex == $totalPart - 1) {
            $endIndex = $count- 1;
        }

        echo "Processing part " . $partIndex . " of " . $totalPart . " parts.     " . $startIndex . " -> " . $endIndex;
        $i = 0;
        foreach($collection as $product) {
            if ($i <= $endIndex && $i >= $startIndex) {    
		    $productWebsiteIds = $product->getWebsiteIds();
		    $rules = Mage::getModel('catalogrule/rule')->getCollection()
			->addFieldToFilter('is_active', 1);
		    foreach ($rules as $rule) {
			$websiteIds = array_intersect($productWebsiteIds, $rule->getWebsiteIds());
			$rule->applyToProduct($product, $websiteIds);
			$rule->clearInstance();
		    }
		    echo "Applied rules to " . $i . " /  " .$product->getSku() . " (" . number_format(memory_get_usage() / 1024 / 1024 / 1024, 2) . "G, " . ($endIndex - $i) . " products left)\n";
            }
            $product->clearInstance();
            $i++;
        }
    }
}

require_once str_replace('shell','',getcwd()) . 'app/Mage.php';
$shell = new Mage_Shell_Pricerules_Part();
$shell->run();
