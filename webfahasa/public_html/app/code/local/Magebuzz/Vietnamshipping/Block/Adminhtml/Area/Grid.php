<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Area_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  }    
        
  public function __construct() {
		parent::__construct();
		$this->setId('areatGrid');
		$this->setDefaultSort('area_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection() {
		$collection = Mage::getModel($this->getModuleStr() . '/area')->getCollection();
    /*$collection->getSelect()
    ->join(array('province' => Mage::getSingleton('core/resource')->getTableName($this->getModuleStr() . '_province')), 'main_table.province_id=province.province_id', 'province.province_name')
    ->join(array('district' => Mage::getSingleton('core/resource')->getTableName($this->getModuleStr() . '_district')), 'main_table.district_id=district.district_id', 'district.district_name')
		->order('main_table.district_id', 'ASC'); */
		$this->setCollection($collection);
		return parent::_prepareCollection();
  }

  protected function _prepareColumns() {
		$this->addColumn('area_id', array(
			'header'    => Mage::helper($this->getModuleStr())->__('ID'),
			'align'     =>'right',
			'width'     => '50px',
			'index'     => 'area_id',
		));

		$this->addColumn('area_name', array(
			'header'    => Mage::helper($this->getModuleStr())->__('Area Name'),
			'align'     =>'left',
			'index'     => 'area_name',
		));
    $this->addColumn('area_code', array(
			'header'    => Mage::helper($this->getModuleStr())->__('Area Code'),
			'align'     =>'left',
			'index'     => 'area_code',
		));
    $this->addColumn('price_shipping_normal', array(
			'header'    => Mage::helper($this->getModuleStr())->__('Price for Shipping Normal'),
			'align'     =>'left',
			'index'     => 'price_shipping_normal',
      //'renderer'  => $this->getModuleStr() . '/adminhtml_renderer_currentprice'
		)); 
    
    $this->addColumn('shipping_express', array(
			'header'    => Mage::helper($this->getModuleStr())->__('Shipping Express'),
			'align'     => 'left',
			'width'     => '80px',
			'index'     => 'shipping_express',
			'type'      => 'options',
			'options'   => array(
				1 => 'Yes',
				0 => 'No',
			),
		)); 
    /*$this->addColumn('province_name', array(
			'header'    => Mage::helper($this->getModuleStr())->__('Province Name'),
			'align'     =>'left',
			'index'     => 'province_name',
		)); 
    $this->addColumn('district_name', array(
			'header'    => Mage::helper($this->getModuleStr())->__('District Name'),
			'align'     =>'left',
			'index'     => 'district_name',
		)); */
		$this->addColumn('status', array(
			'header'    => Mage::helper($this->getModuleStr())->__('Status'),
			'align'     => 'left',
			'width'     => '80px',
			'index'     => 'status',
			'type'      => 'options',
			'options'   => array(
				1 => 'Enabled',
				2 => 'Disabled',
			),
		));
	  
		$this->addColumn('action',
			array(
				'header'    =>  Mage::helper($this->getModuleStr())->__('Action'),
				'width'     => '100',
				'type'      => 'action',
				'getter'    => 'getId',
				'actions'   => array(
					array(
						'caption'   => Mage::helper($this->getModuleStr())->__('Edit'),
						'url'       => array('base'=> '*/*/edit'),
						'field'     => 'id'
					)
				),
				'filter'    => false,
				'sortable'  => false,
				'index'     => 'stores',
				'is_system' => true,
		));
		
		$this->addExportType('*/*/exportCsv', Mage::helper($this->getModuleStr())->__('CSV'));
		$this->addExportType('*/*/exportXml', Mage::helper($this->getModuleStr())->__('XML'));
	  
    return parent::_prepareColumns();
  }

	protected function _prepareMassaction() {
		$this->setMassactionIdField('area_id');
		$this->getMassactionBlock()->setFormFieldName('area');

		$this->getMassactionBlock()->addItem('delete', array(
			'label'    => Mage::helper($this->getModuleStr())->__('Delete'),
			'url'      => $this->getUrl('*/*/massDelete'),
			'confirm'  => Mage::helper($this->getModuleStr())->__('Are you sure?')
		));

		$statuses = Mage::getSingleton($this->getModuleStr() . '/status')->getOptionArray();

		array_unshift($statuses, array('label'=>'', 'value'=>''));
		$this->getMassactionBlock()->addItem('status', array(
			'label'=> Mage::helper($this->getModuleStr())->__('Change status'),
			'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
			'additional' => array(
				'visibility' => array(
					'name' => 'status',
					'type' => 'select',
					'class' => 'required-entry',
					'label' => Mage::helper($this->getModuleStr())->__('Status'),
					'values' => $statuses
				)
			)
		));
		return $this;
	}

  public function getRowUrl($row) {
    return $this->getUrl('*/*/edit', array('id' => $row->getId()));
  }
}