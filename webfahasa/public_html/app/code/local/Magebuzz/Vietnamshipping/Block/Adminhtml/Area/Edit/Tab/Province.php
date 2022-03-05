<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Area_Edit_Tab_Province extends Mage_Adminhtml_Block_Widget_Grid {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  }
  
  public function __construct() {
    parent::__construct();
    $this->setId('provinceGrid');
    $this->setDefaultSort('province_id');
    $this->setDefaultDir('DESC');
    $this->setUseAjax(true);
  }
	
	protected function _addColumnFilterToCollection($column) {
			// Set custom filter for in product flag
			if ($column->getId() == 'in_provinces') {
				$provinceIds = $this->_getSelectedProvinces();
				if (empty($provinceIds)) {
					$provinceIds = 0;
				}
				if ($column->getFilter()->getValue()) {
					$this->getCollection()->addFieldToFilter('province_id', array('in' => $provinceIds));
				} else {
					if ($provinceIds) {
						$this->getCollection()->addFieldToFilter('province_id', array('nin' => $provinceIds));
					}
				}
			} else {
				parent::_addColumnFilterToCollection($column);
			}
			return $this;
	}

  protected function _prepareCollection() {
    $collection = Mage::getModel($this->getModuleStr() . '/province')->getCollection();
    $collection->setOrder('province_id', 'DESC');
    $this->setCollection($collection);
    return parent::_prepareCollection();
  }

	public function getGridUrl() {
    return $this->getData('grid_url') ? $this->getData('grid_url') : $this->getUrl('*/*/provincelistGrid', array('_current'=>true));
  }
	
  protected function _prepareColumns() {    
		$this->addColumn('in_provinces', array(
			'header_css_class' => 'a-center',
			'type'      => 'checkbox',
			'name'      => 'in_provinces',
			'align'     => 'center',
			'index'     => 'province_id',
			'values'    => $this->_getSelectedProvinces(),
		));
		
		$this->addColumn('province_id', array(
			'header'    => Mage::helper($this->getModuleStr())->__('ID'),
			'width'     => '50px',
			'index'     => 'province_id',
			'type'  => 'number',
		));
   

    $this->addColumn('province_name', array(
    'header'    => Mage::helper($this->getModuleStr())->__('Province Name'),
    'align'     => 'left',    
    'index'     => 'province_name',
    'type'      => 'text',			
    ));
    
     $this->addColumn('province_code', array(
    'header'    => Mage::helper($this->getModuleStr())->__('Province Code'),
    'align'     => 'left',    
    'index'     => 'province_code',
    'type'      => 'text',			
    ));
    $this->addColumn('status_provice', array(
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
	
	protected function _getSelectedProvinces() {
		$provinces = $this->getProvinces();
		if(!is_array($provinces)) {
			$provinces = array_keys($this->getSelectedProvinces());
		}
		return $provinces;
	}
	
	public function getSelectedProvinces() {
		$provinces = array();
		$provinceIds = $this->getProvinceIds();
		foreach($provinceIds as $provinceId) {
			$provinces[$provinceId] = array('position'=>0);
		}
		return $provinces;
	}
	
  public function getProvinceIds(){
    $model = Mage::getModel($this->getModuleStr() . '/area')->load($this->getRequest()->getParam('id'));
    $_provinceIds = $model->getProvinceIds();    
      foreach(explode(',',$_provinceIds) as $provinceId){
        $provinceIds[]= $provinceId;
      }
      return  $provinceIds;
    
  }
}