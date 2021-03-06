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
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Index
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Sonassi_FastSearchIndex_Block_Index_Adminhtml_Process extends Mage_Index_Block_Adminhtml_Process
{
    public function __construct()
    {
        $this->_blockGroup = 'index';
        $this->_controller = 'adminhtml_process';
        $this->_headerText = Mage::helper('index')->__('Index Management');

        $this->_addButton('refresh_catprod', array(
            'label'     => Mage::helper('index')->__('Sonassi Category Products'),
            'onclick'   => 'setLocation(\'' . $this->getRefreshCatProdUrl() . '\')',
            'class' => 'save'
        ), 10);
        
        $this->_addButton('refresh_search', array(
            'label'     => Mage::helper('index')->__('Sonassi Catalog Search Index'),
            'onclick'   => 'setLocation(\'' . $this->getRefreshUrl() . '\')',
            'class' => 'save'
        ), 10);
        
        Mage_Adminhtml_Block_Widget_Grid_Container::__construct();
        $this->_removeButton('add');
    }
    public function getRefreshUrl() {
      
      return $this->getUrl('fastsearchindex/admin/refreshSearch');
      
    }
    public function getRefreshCatProdUrl() {
      
      return $this->getUrl('fastsearchindex/admin/refreshCatProd');
      
    }
}
