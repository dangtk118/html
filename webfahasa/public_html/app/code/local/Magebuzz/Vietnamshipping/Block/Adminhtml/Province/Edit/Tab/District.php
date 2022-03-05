<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Province_Edit_Tab_District extends Mage_Adminhtml_Block_Widget_Grid {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  } 
  
  public function __construct() {
    parent::__construct();
    $this->setId('district_id');
    $this->setDefaultSort('district_id');
    $this->setDefaultDir('DESC');
    $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection() {
    $collection = Mage::getModel($this->getModuleStr() . '/district')->getCollection();
    $collection->setOrder('district_id', 'DESC');
    $this->setCollection($collection);
    return parent::_prepareCollection();
  }


  protected function _prepareColumns() {    

    $this->addColumn('district_id', array(
    'header_css_class' => 'a-center',       
    'header'    => Mage::helper($this->getModuleStr())->__('ID'),
    'field_name' => 'district_ids[]',        
    'align'     =>'left',
    'type'      =>'checkbox',
    'width'     => '50px', 
    'index'     => 'district_id',
    'values'    => $this->getDistrictIds()
    ));

    $this->addColumn('district_name', array(
    'header'    => Mage::helper($this->getModuleStr())->__('District Name'),
    'align'     => 'left',    
    'index'     => 'district_name',
    'type'      => 'text',			
    ));
    
     $this->addColumn('status_district', array(
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
    

    return parent::_prepareColumns();
  }
  public function getDistrictIds(){
    $collection = Mage::getModel($this->getModuleStr() . '/province')->getCollection();
    $collection->getSelect()
	    ->join(array('district' => Mage::getSingleton('core/resource')->getTableName($this->getModuleStr() . '_district')), 'main_table.province_id=district.province_id', 'district.district_id')
	    ->order('main_table.province_id', 'ASC');
	
		$districtIds = array();
		foreach($collection->getData() as $district){
			if($district['province_id' ]==$this->getRequest()->getParam('id')) {
				 $districtIds[]= $district['district_id' ];
			}
		}
		return  $districtIds;
  }
}