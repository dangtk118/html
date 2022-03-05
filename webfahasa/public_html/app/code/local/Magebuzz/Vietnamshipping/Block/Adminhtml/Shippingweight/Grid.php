<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Shippingweight_Grid extends Mage_Adminhtml_Block_Widget_Grid {
  public function __construct() {
		parent::__construct();
		$this->setId('shippingweightGrid');
		$this->setDefaultSort('shippingweight_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection() {
		$collection = Mage::getModel('vietnamshipping/shippingweight')->getCollection();
		$this->setCollection($collection);
		return parent::_prepareCollection();
  }

  protected function _prepareColumns() {
		$this->addColumn('shippingweight_id', array(
			'header'    => Mage::helper('vietnamshipping')->__('ID'),
			'align'     =>'right',
			'width'     => '50px',
			'index'     => 'shippingweight_id',
		));

		$this->addColumn('rule_name', array(
			'header'    => Mage::helper('vietnamshipping')->__('Rule Name'),
			'align'     =>'left',
			'width'     => '350px',
			'index'     => 'rule_name',
		));
    $this->addColumn('from_weight', array(
			'header'    => Mage::helper('vietnamshipping')->__('From Weight (g)'),
			'align'     =>'left',
			'index'     => 'from_weight',
		));
   $this->addColumn('to_weight', array(
			'header'    => Mage::helper('vietnamshipping')->__('To Weight (g)'),
			'align'     =>'left', 
			'index'     => 'to_weight',
		)); 
    $this->addColumn('weight_step', array(
			'header'    => Mage::helper('vietnamshipping')->__('Weight Step (g)'),
			'align'     =>'left',
			'index'     => 'weight_step',
		));
    $this->addColumn('price_step', array(
			'header'    => Mage::helper('vietnamshipping')->__('Price Step'),
			'align'     =>'left',
			'index'     => 'price_step',
		)); 
    $this->addColumn('price', array(
			'header'    => Mage::helper('vietnamshipping')->__('Price'),
			'align'     =>'left',
			'index'     => 'price',
		));
		
	  $this->addColumn('status', array(
			'header'    => Mage::helper('vietnamshipping')->__('Status'),
			'align'     =>'left',
			'index'     => 'status',
			'type'      => 'options',
			'options'   => array(
				0 => Mage::helper('vietnamshipping')->__('Disabled'),
				1 => Mage::helper('vietnamshipping')->__('Enabled'),
			),
		));
	  
    return parent::_prepareColumns();
  }

	protected function _prepareMassaction() {
		$this->setMassactionIdField('shippingweight_id');
		$this->getMassactionBlock()->setFormFieldName('shippingweight');

		$this->getMassactionBlock()->addItem('delete', array(
			'label'    => Mage::helper('vietnamshipping')->__('Delete'),
			'url'      => $this->getUrl('*/*/massDelete'),
			'confirm'  => Mage::helper('vietnamshipping')->__('Are you sure?')
		));

		$statuses = array(
			'0' => Mage::helper('vietnamshipping')->__('Disabled'),
			'1' => Mage::helper('vietnamshipping')->__('Enabled'),
		);
		
		$this->getMassactionBlock()->addItem('status', array(
			'label'=> Mage::helper('vietnamshipping')->__('Change status'),
			'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
			'additional' => array(
				'visibility' => array(
					'name' => 'status',
					'type' => 'select',
					'class' => 'required-entry',
					'label' => Mage::helper('vietnamshipping')->__('Status'),
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