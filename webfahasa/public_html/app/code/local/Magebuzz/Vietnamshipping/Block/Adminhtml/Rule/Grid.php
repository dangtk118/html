<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Rule_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  }
        
  public function __construct() {
		parent::__construct();
		$this->setId('ruleGrid');
		$this->setDefaultSort('rule_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection() {
		$collection = Mage::getModel($this->getModuleStr() . '/rule')->getCollection();
    // $collection->getSelect()
			// ->join(array('area' => Mage::getSingleton('core/resource')->getTableName($this->getModuleStr() . '_area')), 'main_table.area_id=area.area_id', 'area.area_name')
			// ->order('main_table.rule_id', 'ASC'); 
		$this->setCollection($collection);
		return parent::_prepareCollection();
  }

  protected function _prepareColumns() {
		$this->addColumn('rule_id', array(
			'header'    => Mage::helper($this->getModuleStr())->__('ID'),
			'align'     =>'right',
			'width'     => '50px',
			'index'     => 'rule_id',
		));

		$this->addColumn('rule_name', array(
			'header'    => Mage::helper($this->getModuleStr())->__('Rule Name'),
			'align'     =>'left',
			'index'     => 'rule_name',
		));
    
    $this->addColumn('apply_to_shipping', array(
			'header'    => Mage::helper($this->getModuleStr())->__('Apply to Shipping'),
			'align'     => 'left',
			'index'     => 'apply_to_shipping',
			'type'      => 'options',
			'options'   => array(
				'free' => Mage::helper($this->getModuleStr())->__('Free Shipping'),
				'discount_shipping_normal' => Mage::helper($this->getModuleStr())->__('Discount Shipping Normal'),
				'discount_shipping_sameday' => Mage::helper($this->getModuleStr())->__('Discount Shipping Sameday'),
        'discount_shipping_express' => Mage::helper($this->getModuleStr())->__('Discount Shipping Express'),
			),
		)); 
    $this->addColumn('discount_amount', array(
			'header'    => Mage::helper($this->getModuleStr())->__('Discount Amount'),
			'align'     =>'left',
			'index'     => 'discount_amount',
		));
   /*$this->addColumn('area_name', array(
			'header'    => Mage::helper($this->getModuleStr())->__('Area'),
			'align'     =>'left',
			'index'     => 'area_name',
		));  */
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
		$this->setMassactionIdField('rule_id');
		$this->getMassactionBlock()->setFormFieldName('rule');

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