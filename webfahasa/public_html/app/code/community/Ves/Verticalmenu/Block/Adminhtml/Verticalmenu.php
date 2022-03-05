<?php
/******************************************************
 * @package Ves Megamenu module for Magento 1.4.x.x and Magento 1.7.x.x
 * @version 1.0.0.1
 * @author http://landofcoder.com
 * @copyright	Copyright (C) December 2010 LandOfCoder.com <@emai:landofcoder@gmail.com>.All rights reserved.
 * @license		GNU General Public License version 2
*******************************************************/
?>
<?php
class Ves_Verticalmenu_Block_Adminhtml_Verticalmenu extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_verticalmenu';
        $this->_blockGroup = 'ves_verticalmenu';
        $this->_headerText = Mage::helper('ves_verticalmenu')->__('Verticalmenu Manager');
        $this->_addButtonLabel = Mage::helper('ves_verticalmenu')->__('Add Verticalmenu');
        parent::__construct();
    }

    protected function _prepareLayout() {
        $this->setChild('add_new_button',
                $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                'label'     => Mage::helper('ves_verticalmenu')->__('Add Verticalmenu'),
                'onclick'   => "setLocation('".$this->getUrl('*/*/add')."')",
                'class'   => 'add'
                ))
        );
        $this->setChild('importcsv',
                $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                'label'     => Mage::helper('ves_verticalmenu')->__('Import CSV'),
                'onclick'   => 'setLocation(\'' . $this->getImportUrl() .'\')',
                'class'   => 'import'
                ))
        );
        /**
         * Display store switcher if system has more one store
         */
        if (!Mage::app()->isSingleStoreMode()) {
            $this->setChild('store_switcher',
                   $this->getLayout()->createBlock('adminhtml/store_switcher')
                   ->setUseConfirm(false)
                   ->setSwitchUrl($this->getUrl('*/*/*', array('store'=>null)))
           );
       }
        $this->setChild('grid', $this->getLayout()->createBlock('ves_verticalmenu/adminhtml_verticalmenu_grid', 'verticalmenu.grid'));
        return parent::_prepareLayout();
    }

    private function getImportUrl() {
        return $this->getUrl('*/*/uploadCsv');
    } // end

    public function getAddNewButtonHtml() {
        return $this->getChildHtml('add_new_button');
    }

    public function getImportButtonHtml() {
        return $this->getChildHtml('importcsv');
    }

    public function getGridHtml() {
        return $this->getChildHtml('grid');
    }

    public function getStoreSwitcherHtml() {
       return $this->getChildHtml('store_switcher');
    }
}