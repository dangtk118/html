<?php
/**
 * @category    AM
 * @package     AM_Extensions
 * @author      FireGento Team <team@firegento.com>
 * @copyright   2013 FireGento Team (http://firegento.com). All rights served.
 * @license     http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */
class AM_Extensions_Block_Adminhtml_Diagnostic_CheckRewrites_Grid extends Mage_Adminhtml_Block_Widget_Grid{
    public function __construct(){
        parent::__construct();
        $this->setId('checkRewritesGrid');
        $this->_filterVisibility = false;
        $this->_pagerVisibility  = false;
    }

    protected function _prepareCollection(){
        $collection = Mage::helper('amext')->getRewriteCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns(){
        $this->addColumn('path', array(
            'header'   => Mage::helper('amext')->__('Path'),
            'align'    => 'left',
            'index'    => 'path',
            'sortable' => false,
        ));
        $this->addColumn('rewrite_class', array(
            'header'   => Mage::helper('amext')->__('Rewrite Class'),
            'width'    => '200',
            'align'    => 'left',
            'index'    => 'rewrite_class',
            'sortable' => false,
        ));
        $this->addColumn('active_class', array(
            'header'   => Mage::helper('amext')->__('Active Class'),
            'width'    => '200',
            'align'    => 'left',
            'index'    => 'active_class',
            'sortable' => false,
        ));
        $this->addColumn('status', array(
            'header'         => Mage::helper('amext')->__('Status'),
            'width'          => '120',
            'align'          => 'left',
            'index'          => 'status',
            'type'           => 'options',
            'options'        => array(0 => Mage::helper('amext')->__('Not Ok'), 1 => Mage::helper('amext')->__('Ok')),
            'frame_callback' => array($this, 'decorateStatus')
        ));

        return parent::_prepareColumns();
    }

    public function decorateStatus($value, $row){
        if ($row->getStatus()) {
            $cell = '<span class="grid-severity-notice"><span>'.$value.'</span></span>';
        }else{
            $cell = '<span class="grid-severity-critical"><span>'.$value.'</span></span>';
        }

        return $cell;
    }

    public function getRowUrl($row){
        return false;
    }
}