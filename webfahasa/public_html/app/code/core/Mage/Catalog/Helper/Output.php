<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Catalog_Helper_Output extends Mage_Core_Helper_Abstract
{
    /**
     * Array of existing handlers
     *
     * @var array
     */
    protected $_handlers;

    /**
     * Template processor instance
     *
     * @var Varien_Filter_Template
     */
    protected $_templateProcessor = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        Mage::dispatchEvent('catalog_helper_output_construct', array('helper'=>$this));
    }

    protected function _getTemplateProcessor()
    {
        if (null === $this->_templateProcessor) {
            $this->_templateProcessor = Mage::helper('catalog')->getPageTemplateProcessor();
        }

        return $this->_templateProcessor;
    }

    /**
     * Adding method handler
     *
     * @param   string $method
     * @param   object $handler
     * @return  Mage_Catalog_Helper_Output
     */
    public function addHandler($method, $handler)
    {
        if (!is_object($handler)) {
            return $this;
        }
        $method = strtolower($method);

        if (!isset($this->_handlers[$method])) {
            $this->_handlers[$method] = array();
        }

        $this->_handlers[$method][] = $handler;
        return $this;
    }

    /**
     * Get all handlers for some method
     *
     * @param   string $method
     * @return  array
     */
    public function getHandlers($method)
    {
        $method = strtolower($method);
        return isset($this->_handlers[$method]) ? $this->_handlers[$method] : array();
    }

    /**
     * Process all method handlers
     *
     * @param   string $method
     * @param   mixed $result
     * @param   array $params
     * @return unknown
     */
    public function process($method, $result, $params)
    {
        foreach ($this->getHandlers($method) as $handler) {
            if (method_exists($handler, $method)) {
                $result = $handler->$method($this, $result, $params);
            }
        }
        return $result;
    }

    /**
     * Prepare product attribute html output
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   string $attributeHtml
     * @param   string $attributeName
     * @return  string
     */
    public function productAttribute($product, $attributeHtml, $attributeName)
    {
        $attribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeName);
        if ($attribute && $attribute->getId() && ($attribute->getFrontendInput() != 'media_image')
            && (!$attribute->getIsHtmlAllowedOnFront() && !$attribute->getIsWysiwygEnabled())) {
                if ($attribute->getFrontendInput() != 'price') {
                    $attributeHtml = $this->escapeHtml($attributeHtml);
                }
                if ($attribute->getFrontendInput() == 'textarea') {
                    $attributeHtml = nl2br($attributeHtml);
                }
        }
        if ($attribute->getIsHtmlAllowedOnFront() && $attribute->getIsWysiwygEnabled()) {
            if (Mage::helper('catalog')->isUrlDirectivesParsingAllowed()) {
                $attributeHtml = $this->_getTemplateProcessor()->filter($attributeHtml);
            }
        }

        $attributeHtml = $this->process('productAttribute', $attributeHtml, array(
            'product'   => $product,
            'attribute' => $attributeName
        ));

        return $attributeHtml;
    }

    /**
     * Prepare category attribute html output
     *
     * @param   Mage_Catalog_Model_Category $category
     * @param   string $attributeHtml
     * @param   string $attributeName
     * @return  string
     */
    public function categoryAttribute($category, $attributeHtml, $attributeName)
    {
        $attribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Category::ENTITY, $attributeName);

        if ($attribute && ($attribute->getFrontendInput() != 'image')
            && (!$attribute->getIsHtmlAllowedOnFront() && !$attribute->getIsWysiwygEnabled())) {
            $attributeHtml = $this->escapeHtml($attributeHtml);
        }
        if ($attribute->getIsHtmlAllowedOnFront() && $attribute->getIsWysiwygEnabled()) {
            if (Mage::helper('catalog')->isUrlDirectivesParsingAllowed()) {
                $attributeHtml = $this->_getTemplateProcessor()->filter($attributeHtml);
            }
        }
        $attributeHtml = $this->process('categoryAttribute', $attributeHtml, array(
            'category'  => $category,
            'attribute' => $attributeName
        ));
        return $attributeHtml;
    }
    
    public function getAdditionHtml($attribute_data){
	$result = "";
	$block_1 ="";
	$block_2 ="";
	$block_3 ="";
	
	$supplier = $attribute_data['supplier'];
	$author = $attribute_data['author'];
	$manufacturer = $attribute_data['manufacturer'];
	$origin = $attribute_data['origin'];
	$madein = $attribute_data['madein'];
	$publisher = $attribute_data['publisher'];
	$book_layout = $attribute_data['book_layout'];
	$series_data = $attribute_data['series'];
	
	//block 1-1
	if(!empty($supplier['name']) 
	    ||!empty($origin['value']) 
	    ||!empty($madein['value']) 
	    ||!empty($publisher['value']) 
	    ||!empty($book_layout['value']) 
	    ||!empty($author['value']) 
	    ||!empty($manufacturer['value'])){
	    $block_1 = "<div class='product-view-sa-supplier'>";
		if(!empty($supplier['name'])){
		    $block_1 .= "<span>". $this->__("Supplier"). ":</span>";
		    if (!empty($supplier['pageUrl'])){
			$block_1 .= "<a href='". $supplier['pageUrl'] ."'>". $supplier['name'] ."</a>";
		    }else{
			$block_1 .= "<span>". $supplier['name'] ."</span>";
		    }
		    $supplier = null;
		}elseif(!empty($origin['value'])){
		    $block_1 .= "<span>". $this->__("Origin"). ":</span>"
			    . "<span>". $origin['value'] ."</span>";
		    $origin = null;
		}elseif(!empty($publisher['value'])){
		    $block_1 .= "<span>". $this->__("Publisher"). ":</span>"
			    . "<span>". $publisher['value'] ."</span>";
		    $publisher = null;
		}elseif(!empty($book_layout['value'])){
		    $block_1 .= "<span>". $this->__("Book layout"). ":</span>"
			    . "<span>". $book_layout['value'] ."</span>";
		    $book_layout = null;
		}elseif(!empty($author['value']) || !empty($manufacturer['value'])){
		    if(!empty($author['value'])){
			$block_1 .= "<span>". $this->__("Author"). ":</span>"
				. "<span>". $author['value'] ."</span>";
			$author = null;
		    }else{
			$block_1 .= "<span>". $this->__("Manufacture"). ":</span>"
				. "<span>". $manufacturer['value'] ."</span>";
			$manufacturer = null;
		    }
		}elseif(!empty($madein['value'])){
		    $block_1 .= "<span>". $this->__("Made in"). ":</span>"
			    . "<span>". $madein['value'] ."</span>";
		    $madein = null;
		}
	    $block_1 .= "</div>";
	}
	//block 1-2
	if(!empty($origin['value']) 
	    ||!empty($madein['value']) 
	    ||!empty($publisher['value']) 
	    ||!empty($book_layout['value']) 
	    ||!empty($author['value']) 
	    ||!empty($manufacturer['value'])){
	    $block_1 .= "<div class='product-view-sa-author'>";
		if(!empty($author['value']) || !empty($manufacturer['value'])){
		    if(!empty($author['value'])){
			$block_1 .= "<span>". $this->__("Author"). ":</span>"
				. "<span>". $author['value'] ."</span>";
			$author = null;
		    }else{
			$block_1 .= "<span>". $this->__("Manufacture"). ":</span>"
				. "<span>". $manufacturer['value'] ."</span>";
			$manufacturer = null;
		    }
		}elseif(!empty($madein['value'])){
		    $block_1 .= "<span>". $this->__("Made in"). ":</span>"
			    . "<span>". $madein['value'] ."</span>";
		    $madein = null;
		}elseif(!empty($origin['value'])){
		    $block_1 .= "<span>". $this->__("Origin"). ":</span>"
			    . "<span>". $origin['value'] ."</span>";
		    $origin = null;
		}elseif(!empty($publisher['value'])){
		    $block_1 .= "<span>". $this->__("Publisher"). ":</span>"
			    . "<span>". $publisher['value'] ."</span>";
		    $publisher = null;
		}elseif(!empty($book_layout['value'])){
		    $block_1 .= "<span>". $this->__("Book layout"). ":</span>"
			    . "<span>". $book_layout['value'] ."</span>";
		    $book_layout = null;
		}
	    $block_1 .= "</div>";
	}
	
	//----------------------------
	//block 2-1
	if(!empty($origin['value']) 
	    ||!empty($madein['value']) 
	    ||!empty($publisher['value']) 
	    ||!empty($book_layout['value']) 
	    ||!empty($author['value']) 
	    ||!empty($manufacturer['value'])){
	    $block_2 = "<div class='product-view-sa-supplier'>";
		if(!empty($origin['value'])){
		    $block_2 .= "<span>". $this->__("Origin"). ":</span>"
			    . "<span>". $origin['value'] ."</span>";
		    $origin = null;
		}elseif(!empty($publisher['value'])){
		    $block_2 .= "<span>". $this->__("Publisher"). ":</span>"
			    . "<span>". $publisher['value'] ."</span>";
		    $publisher = null;
		}elseif(!empty($book_layout['value'])){
		    $block_2 .= "<span>". $this->__("Book layout"). ":</span>"
			    . "<span>". $book_layout['value'] ."</span>";
		    $book_layout = null;
		}elseif(!empty($madein['value'])){
		    $block_2 .= "<span>". $this->__("Made in"). ":</span>"
			    . "<span>". $madein['value'] ."</span>";
		    $madein = null;
		}elseif(!empty($author['value']) || !empty($manufacturer['value'])){
		    if(!empty($author['value'])){
			$block_2 .= "<span>". $this->__("Author"). ":</span>"
				. "<span>". $author['value'] ."</span>";
			$author = null;
		    }else{
			$block_2 .= "<span>". $this->__("Manufacture"). ":</span>"
				. "<span>". $manufacturer['value'] ."</span>";
			$manufacturer = null;
		    }
		}
	    $block_2 .= "</div>";
	}
	//block 2-2
	if(!empty($madein['value']) 
	    ||!empty($publisher['value']) 
	    ||!empty($book_layout['value']) 
	    ||!empty($author['value']) 
	    ||!empty($manufacturer['value'])){
	    $block_2 .= "<div class='product-view-sa-author'>";
		if(!empty($madein['value'])){
		    $block_2 .= "<span>". $this->__("Made in"). ":</span>"
			    . "<span>". $madein['value'] ."</span>";
		    $madein = null;
		}elseif(!empty($publisher['value'])){
		    $block_2 .= "<span>". $this->__("Publisher"). ":</span>"
			    . "<span>". $publisher['value'] ."</span>";
		    $publisher = null;
		}elseif(!empty($book_layout['value'])){
		    $block_2 .= "<span>". $this->__("Book layout"). ":</span>"
			    . "<span>". $book_layout['value'] ."</span>";
		    $book_layout = null;
		}elseif(!empty($author['value']) || !empty($manufacturer['value'])){
		    if(!empty($author['value'])){
			$block_2 .= "<span>". $this->__("Author"). ":</span>"
				. "<span>". $author['value'] ."</span>";
			$author = null;
		    }else{
			$block_2 .= "<span>". $this->__("Manufacture"). ":</span>"
				. "<span>". $manufacturer['value'] ."</span>";
			$manufacturer = null;
		    }
		}
		$block_2 .= "</div>";
	}
	//----------------------------
	//block 3-1
	if(!empty($publisher['value']) 
	    ||!empty($book_layout['value']) 
	    ||!empty($author['value']) 
	    ||!empty($manufacturer['value'])){
	    $block_3 = "<div class='product-view-sa-supplier'>";
		if(!empty($publisher['value'])){
		    $block_3 .= "<span>". $this->__("Publisher"). ":</span>"
			    . "<span>". $publisher['value'] ."</span>";
		    $publisher = null;
		}elseif(!empty($book_layout['value'])){
		    $block_3 .= "<span>". $this->__("Book layout"). ":</span>"
			    . "<span>". $book_layout['value'] ."</span>";
		    $book_layout = null;
		}elseif(!empty($author['value']) || !empty($manufacturer['value'])){
		    if(!empty($author['value'])){
			$block_3 .= "<span>". $this->__("Author"). ":</span>"
				. "<span>". $author['value'] ."</span>";
			$author = null;
		    }else{
			$block_3 .= "<span>". $this->__("Manufacture"). ":</span>"
				. "<span>". $manufacturer['value'] ."</span>";
			$manufacturer = null;
		    }
		}
	    $block_3 .= "</div>";
	}
	//block 3-2
	if(!empty($book_layout['value']) 
	    ||!empty($author['value']) 
	    ||!empty($manufacturer['value'])){
	    $block_3 .= "<div class='product-view-sa-author'>";
		if(!empty($book_layout['value'])){
		    $block_3 .= "<span>". $this->__("Book layout"). ":</span>"
			    . "<span>". $book_layout['value'] ."</span>";
		    $book_layout = null;
		}elseif(!empty($author['value']) || !empty($manufacturer['value'])){
		    if(!empty($author['value'])){
			$block_3 .= "<span>". $this->__("Author"). ":</span>"
				. "<span>". $author['value'] ."</span>";
			$author = null;
		    }else{
			$block_3 .= "<span>". $this->__("Manufacture"). ":</span>"
				. "<span>". $manufacturer['value'] ."</span>";
			$manufacturer = null;
		    }
		}
	    $block_3 .= "</div>";
	}
	
	if(!empty($series_data)){
	    $campain = Mage::helper('seriesbook')->getFhsCampaignSeriProduct();
	    $block_series = "<div class='product-view-sa-series'>"
		    . "<span>".$this->__('Series').": </span>"
		    . "<span>"
			. "<a href='".$series_data['url']."?fhs_campaign=".Mage::helper('seriesbook')->getFhsCampaignSeriProduct()."'>"
			    . "<span>".(!empty($series_data['name'])?$series_data['name']:$this->__('Detail'))."</span>"
			    . "<span class='icon_seemore_blue'></span>"
			. "</a>"
		    . "</span>"
		    . "</div>";
	}
	
	if(!empty($block_1)){
	    $result = "<div class='product-view-sa_one'>". $block_1 ."</div>";
	}
	if(!empty($block_2)){
	    $result .= "<div class='product-view-sa_two'>". $block_2 ."</div>";
	}
	if(!empty($block_3)){
	    $result .= "<div class='product-view-sa_one'>". $block_1 ."</div>";
	}
	if(!empty($block_series)){
	    $result .= "<div class='product-view-sa_four'>". $block_series ."</div>";
	}
	if(!empty($result)){
	    $result = "<div class='product-view-sa'>".$result."</div>";
	}
	return $result;
    }
}
