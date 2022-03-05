<?php
/*////////////////////////////////////////////////////////////////////////////////
 \\\\\\\\\\\\\\\\\\\\\\\\\  FME Fieldsmanager extension  \\\\\\\\\\\\\\\\\\\\\\\\\
 /////////////////////////////////////////////////////////////////////////////////
 \\\\\\\\\\\\\\\\\\\\\\\\\ NOTICE OF LICENSE\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 ///////                                                                   ///////
 \\\\\\\ This source file is subject to the Open Software License (OSL 3.0)\\\\\\\
 ///////   that is bundled with this package in the file LICENSE.txt.      ///////
 \\\\\\\   It is also available through the world-wide-web at this URL:    \\\\\\\
 ///////          http://opensource.org/licenses/osl-3.0.php               ///////
 \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 ///////                      * @category   FME                            ///////
 \\\\\\\                      * @package    FME_Fieldsmanager              \\\\\\\
 ///////    * @author     Malik Tahir Mehmood <malik.tahir786@gmail.com>   ///////
 \\\\\\\                                                                   \\\\\\\
 /////////////////////////////////////////////////////////////////////////////////
 \\* @copyright  Copyright 2010 © free-magentoextensions.com All right reserved\\\
 /////////////////////////////////////////////////////////////////////////////////
 */

class FME_Fieldsmanager_Block_Adminhtml_Fieldsmanager_Grid  extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
      parent::__construct();
      
      $this->setId('fieldsmanagergrid');
      $this->setDefaultSort('attribute_code');
      $this->setDefaultDir('ASC');
      $this->setSaveParametersInSession(true);
      $this->setTemplate('fieldsmanager/grid.phtml');
    }

    protected function _prepareCollection()
    {
        $type='fme_fieldsmanager';
        //$entityattribute = Mage::getResourceModel('eav/entity_attribute');
        $this->type=$type;
            $collection = Mage::getResourceModel('eav/entity_attribute_collection')
                ->setEntityTypeFilter( Mage::getModel('eav/entity')->setType($type)->getTypeId());
           
            //$collection->getSelect()->join(
            //    array('additional_table' => $entityattribute->getTable('catalog/eav_attribute')),
            //    'additional_table.attribute_id=main_table.attribute_id'
            //);
         
            $collection=Mage::helper('fieldsmanager')->getEavAttribute($collection, true);
        $this->setCollection($collection);
      return parent::_prepareCollection();
      
    }
    
    protected function _prepareColumns()
    {
      $this->addColumn('attribute_code', array(
            'header'=>Mage::helper('catalog')->__('Attribute Code'),
            'sortable'=>true,
            'index'=>'attribute_code'
        ));

        $this->addColumn('frontend_label', array(
            'header'=>Mage::helper('catalog')->__('Attribute Label'),
            'sortable'=>true,
            'index'=>'frontend_label'
        ));

        $this->addColumn('frontend_input', array(
            'header'=>Mage::helper('catalog')->__('Input Type'),
            'sortable'=>true,
            'index'=>'frontend_input',
            'type' => 'options',
            'options' =>Mage::getModel('fieldsmanager/type')->toOptionsArray()
        ));
        
        $this->addColumn('is_filterable', array(
            'header'=>Mage::helper('catalog')->__('Field Placement'),
            'sortable'=>true,
            'index'=>'is_filterable',
            'type' => 'options',
            'options' =>Mage::getModel('fieldsmanager/type')->toPositionOptionsArray(),
            'align' => 'left',
        ));
        
        $this->addColumn('is_required', array(
            'header'=>Mage::helper('catalog')->__('Required'),
            'sortable'=>true,
            'index'=>'is_required',
            'type' => 'options',
             'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center',
        ));
        
        $this->addColumn('is_searchable', array(
            'header'=>Mage::helper('catalog')->__('Steps'),
            'sortable'=>true,
            'index'=>'is_searchable',
            'type' => 'options',
            'options' => Mage::getModel('fieldsmanager/type')->toPlacementOptionsArray()
        ));
	$this->addColumn('fme_customer_account', array(
            'header'=>Mage::helper('catalog')->__("Add to Customer's Account"),
            'sortable'=>true,
            'index'=>'fme_customer_account',
            'type' => 'options',
            'options' => Mage::getModel('fieldsmanager/type')->toCustomerOptionsArray()
        ));
	$this->addColumn('fme_email', array(
            'header'=>Mage::helper('catalog')->__("Add to Emails"),
            'sortable'=>true,
            'index'=>'fme_email',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
        ));

	$this->addColumn('fme_pdf', array(
            'header'=>Mage::helper('catalog')->__('Add To Pdf'),
            'sortable'=>true,
            'index'=>'fme_pdf',
	    'type' => 'options',
            'options' => Mage::getModel('fieldsmanager/type')->toPdfOptionsArray(),
            'align' => 'center',
        ));
 
   
      return parent::_prepareColumns();
  }

  public function addNewButton(){
  	return $this->getButtonHtml(
  		Mage::helper('fieldsmanager')->__('Add New Field'), //label
  		"setLocation('".$this->getUrl('*/*/new', array('attribute_id'=>0))."')", //url
  		"scalable add" //classe css
  		);
  }
  public function getRowUrl($row)
  {
      return $this->getUrl('*/*/edit', array('attribute_id' => $row->getAttributeId()));
  }
}

