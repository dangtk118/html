<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Area_Edit_Tab_District extends Mage_Adminhtml_Block_Widget_Grid {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  }
  
  public function __construct() {
    parent::__construct();
    $this->setId('districtGrid');
    $this->setDefaultSort('district_id');
    //$this->setDefaultDir('DESC');
    //$this->setSaveParametersInSession(true);
		$this->setUseAjax(true);
  }
	
	protected function _addColumnFilterToCollection($column) {
			// Set custom filter for in product flag
			if ($column->getId() == 'in_districts') {
				$districtIds = $this->_getSelectedDistricts();
				if (empty($districtIds)) {
					$districtIds = 0;
				}
				if ($column->getFilter()->getValue()) {
					$this->getCollection()->addFieldToFilter('district_id', array('in' => $districtIds));
				} else {
					if($districtIds) {
						$this->getCollection()->addFieldToFilter('district_id', array('nin' => $districtIds));
					}
				}
			} else {
				parent::_addColumnFilterToCollection($column);
			}
			return $this;
	}

  protected function _prepareCollection() {
    $collection = Mage::getModel($this->getModuleStr() . '/district')->getCollection();
    $collection->getSelect()
			->joinLeft(array('province' => Mage::getSingleton('core/resource')->getTableName($this->getModuleStr() . '_province')), 'main_table.province_id=province.province_id', 'province.province_name');
    $collection->setOrder('district_id', 'DESC');
    $this->setCollection($collection);
    return parent::_prepareCollection();
  }
	
	public function getGridUrl() {
    return $this->getData('grid_url') ? $this->getData('grid_url') : $this->getUrl('*/*/districtlistGrid', array('_current'=>true));
  } 

  protected function _prepareColumns() {    
		$this->addColumn('in_districts', array(
			'header_css_class' => 'a-center',
			'type'      => 'checkbox',
			'name'      => 'in_districts',
			'align'     => 'center',
			'index'     => 'district_id',
			'values'    => $this->_getSelectedDistricts(),
		));
		
    $this->addColumn('district_id', array(
			'header'    => Mage::helper($this->getModuleStr())->__('ID'),
			'width'     => '50px',
			'index'     => 'district_id',
			'type'  => 'number',
		));

    $this->addColumn('district_name', array(
    'header'    => Mage::helper($this->getModuleStr())->__('District Name'),
    'align'     => 'left',    
    'index'     => 'district_name',
    'type'      => 'text',			
    ));
    $this->addColumn('province_name', array(
			'header'    => Mage::helper($this->getModuleStr())->__('Province Name'),
			'align'     =>'left',
			'index'     => 'province_name',
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
    
		$this->addColumn('position', array(
			'header'            => Mage::helper($this->getModuleStr())->__(''),
			'name'              => 'position',
			'index'             => 'position',
			'width'             => 0,
			'editable'          => true,//!$this->isReadonly(),
			'filter'			=> false,
		));
    return parent::_prepareColumns();
  }
	
	protected function _getSelectedDistricts() {
		$districts = $this->getDistricts();
		if(!is_array($districts)) {
			$districts = array_keys($this->getSelectedDistricts());
		}
		return $districts;
	}
	
	public function getSelectedDistricts() {
		$districts = array();
		$districtIds = $this->getDistrictIds();
		foreach($districtIds as $districtId) {
			$districts[$districtId] = array('position'=>0);
		}
		return $districts;
	}
	
  public function getDistrictIds(){
    $model = Mage::getModel($this->getModuleStr() . '/area')->load($this->getRequest()->getParam('id'));
    $_districtIds = $model->getDistrictIds();    
      foreach(explode(',',$_districtIds) as $districtId){
        $districtIds[]= $districtId;
      }
      return  $districtIds;
    
  }
}