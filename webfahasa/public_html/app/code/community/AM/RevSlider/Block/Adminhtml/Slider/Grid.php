<?php
/**
 * @category    AM
 * @package     AM_RevSlider
 * @copyright   Copyright (C) 2008-2013 ArexMage.com. All Rights Reserved.
 * @license     GNU General Public License version 2 or later
 * @author      ArexMage.com
 * @email       support@arexmage.com
 */
class AM_RevSlider_Block_Adminhtml_Slider_Grid extends Mage_Adminhtml_Block_Widget_Grid{
    protected function _prepareCollection(){
        $collection = Mage::getModel('revslider/slider')->getCollection();
        $this->setCollection($collection);
        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
        return parent::_prepareCollection();
    }

    protected function _prepareColumns(){
        $this->addColumn('id', array(
            'header'    => Mage::helper('revslider')->__('ID'),
            'index'     => 'id',
            'sortable'  => true
        ));
        $this->addColumn('title', array(
            'header'    => Mage::helper('revslider')->__('Title'),
            'index'     => 'title',
            'sortable'  => true
        ));
        $this->addColumn('status', array(
            'header'    => Mage::helper('revslider')->__('Status'),
            'index'     => 'status',
            'type'      => 'options',
            'options'   => Mage::getModel('revslider/slider')->getStatuses()
        ));
        $this->addColumn('preview', array(
            'header'    => Mage::helper('revslider')->__('Preview'),
            'width'     => '80px',
            'renderer'  => 'revslider/adminhtml_widget_grid_column_renderer_slider_preview',
            'filter'    => false,
            'sortable'  => false
        ));
        $this->addColumn('export', array(
            'header'    => Mage::helper('revslider')->__('Export'),
            'type'      => 'action',
            'getter'    => 'getId',
            'width'     => '80px',
            'actions'   => array(
                array(
                    'caption'   => Mage::helper('revslider')->__('Export'),
                    'field'     => 'id',
                    'url'       => array(
                        'base'  => 'revslideradmin/slider/export'
                    )
                )
            ),
            'filter'    => false,
            'sortable'  => false
        ));
        return parent::_prepareColumns();
    }

    public function getRowUrl($row){
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    protected function _prepareMassaction(){
        $this->setMassactionIdField('slider');
        $this->getMassactionBlock()->setFormFieldName('ids');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'     => Mage::helper('revslider')->__('Delete'),
            'url'       => $this->getUrl('*/*/massDelete'),
            'confirm'   => Mage::helper('catalog')->__('Are you sure?')
        ));

        return $this;
    }
}