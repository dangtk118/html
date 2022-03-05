<?php
ini_set('memory_limit', '8192M');

class PT_Magento_Sitemap {

	protected $file;
	protected $filename;

	protected $urls;
	
	public function __construct($filename)
	{	
		$this->urls = array();
		$this->filename = $filename;
	}
	
	public function formatDate($datetime)
	{
		$timestamp = strtotime($datetime);
		return date('Y-m-d', $timestamp);
	}
	
	public function addUrl($loc, $priority = '1', $lastmod = NULL)
	{
		$this->urls[] = array(
			'loc' => $loc,
			'priority' => $priority,
			'lastmod' => ( $lastmod ? $this->formatDate($lastmod) : NULL ),
		);
		
		return true;
	}
	
	public function generate()
	{
        $sitemap_file = $this->filename;

		if ( ! $this->file ) {
			$this->openFile();
		}

		$counter = 0;
	
		if ( ! $this->urls ) {
			return false;
		}

		echo $counter .  $this->filename . "\n";
	
		foreach ( $this->urls as $url )  {
			$this->writeUrl($url);
			$counter++;

			if ($counter % 50000 == 0) {
 				$this->closeFile();
				$this->filename = preg_replace('/\.xml/', '-'.round($counter/50000).'.xml', $sitemap_file);
				echo $counter . $this->filename . "\n";
				$this->openFile();
			}
		}
		
		$this->closeFile();
		
		return true;
	}
	
	private function openFile()
	{
		$this->file = fopen($this->filename, 'w');
		
		if ( ! $this->file ) {
			throw new Exception('Sitemap file '.$file.' is not writable');
			return false;
		}
		
		fwrite($this->file, '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL);
		fwrite($this->file, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL);
		
		return true;
	}
	
	private function closeFile()
	{
		if ( $this->file ) {
			fwrite($this->file, "</urlset>");
			fclose($this->file);
		}
		 
		return true;
	}
	
	private function writeUrl($url)
	{
		fwrite($this->file,  "\t".'<url>'."\n".
			   "\t\t".'<loc>'.$url['loc'].'</loc>'."\n".
			   "\t\t".'<priority>'.$url['priority'].'</priority>'."\n".
			   ( $url['lastmod'] ? "\t\t".'<lastmod>'.$url['lastmod'].'</lastmod>'."\n" : '' ).
			   "\t".'</url>'."\n");
	}


}

	
// make sure we don't time out
error_reporting(E_ALL);
set_time_limit(0);	

require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();

$sitemap_file = '/var/www/html/static/fhsmap/sitemap.xml';

$page_priority = '1';
$category_priority = '0.5';
$product_priority = '0.5';
   	
try {

	$sitemap = new PT_Magento_Sitemap($sitemap_file);
	
    echo date("d-m-y H:i") . ": Loading cms/page collection. \n";
	$collection = Mage::getModel('cms/page')
						->getCollection()
						->addStoreFilter(Mage::app()->getStore()->getId())
                        ->addFieldToFilter('is_active',1);
    echo date("d-m-y H:i") . ": Finished load cms/page collection: " . $collection->getSize() . "\n";
						
	foreach ( $collection as $page ) {
		$sitemap->addUrl(Mage::getBaseUrl().$page->getIdentifier(), $page_priority, $page->getUpdateTime());
	}
	
	unset($collection);

	
    //echo date("d-m-y H:i") . ": Loading catalog/category collection.\n";
	//$collection = Mage::getModel('catalog/category')
	//			        ->getCollection()
	//			        ->addAttributeToSelect('*')
	//			        ->addIsActiveFilter();
    //echo date("d-m-y H:i") . ": Finished load catalog/category collection: " . $collection->getSize() . "\n";
				        
	//foreach ( $collection as $category ) {
	//	$sitemap->addUrl($category->getUrl(), $category_priority, $category->getUpdatedAt());
	//}
	
	//unset($collection);

    echo date("d-m-y H:i") . ": Loading catalog/product collection.\n";
	$collection = Mage::getModel('catalog/product')
					->getCollection()
					->addAttributeToSelect('*')
					->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
					->addAttributeToFilter('visibility', array(
                        Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH, Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
					));
					
    echo date("d-m-y H:i") . ": Finished load catalog/product collection: " . $collection->getSize() . "\n";
	foreach ( $collection as $product ) {
		$sitemap->addUrl($product->getProductUrl(), $product_priority, $product->getUpdatedAt());
	}
	
	unset($collection);
		
	// Generate and write the sitemap.
    echo date("d-m-y H:i") . ": Generating sitemap.xml \n";
	$sitemap->generate();
    echo date("d-m-y H:i") . ": Finished generate sitemap.xml\n";


} catch( Exception $e ) {

	die($e->getMessage());
	
}
