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

class FME_Fieldsmanager_FieldsController extends Mage_Adminhtml_Controller_Action
{

    protected $_entityTypeId;
    protected $_entityType='fme_fieldsmanager';
       
    public function preDispatch()
    {
        parent::preDispatch();
        $this->_entityTypeId = Mage::getModel('eav/entity')->setType($this->_entityType)->getTypeId();
    }
    public function categoriesAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('fieldsmanager/adminhtml_fieldsmanager_edit_tab_categories')->toHtml()
        );   
    }
	
	public function categoriesJsonAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('fieldsmanager/adminhtml_fieldsmanager_edit_tab_categories')
                ->getCategoryChildrenJson($this->getRequest()->getParam('category'))
        );
    }
    
	/**
     * Get related products grid and serializer block
     */
    public function productsAction()
    {
		$this->_initFieldsmanagerProducts();
		$this->loadLayout();
        $this->getLayout()->getBlock('fieldsmanager.edit.tab.products')
		 				  ->setFieldsmanagerProductsRelated($this->getRequest()->getPost('products_related', null));
        $this->renderLayout();
    }
	
	/**
     * Get related products grid
     */
    public function productsGridAction()
    {
        $this->_initFieldsmanagerProducts();
		//Push Existing Values in Array
		$productsarray = array();
		$fieldsmanagerId  = (int) $this->getRequest()->getParam('attribute_id');
		foreach (Mage::registry('current_fieldsmanager_products')->getFieldsmanagerRelatedProducts($fieldsmanagerId) as $products) {
           $productsarray = $products["product_id"];
        }
		array_push($_POST["products_related"],$productsarray);
		Mage::registry('current_fieldsmanager_products')->setFieldsmanagerProductsRelated($productsarray);
		
		$this->loadLayout();
        $this->getLayout()->getBlock('fieldsmanager.edit.tab.products')
            			  ->setFieldsmanagerProductsRelated($this->getRequest()->getPost('products_related', null));
        $this->renderLayout();
    }
	protected function _initFieldsmanagerProducts() {
		
		$fieldsmanager = Mage::getModel('fieldsmanager/fieldsmanager');
        $fieldsmanagerId  = (int) $this->getRequest()->getParam('attribute_id');
//		if ($fieldsmanagerId) {
//        	$fieldsmanager->load($fieldsmanagerId);
//		}
		Mage::register('current_fieldsmanager_products', $fieldsmanager);
		return $fieldsmanager;
		
	}

	protected function _initAction() {
            $this->_title($this->__('FME Extensions'))
             ->_title($this->__('Field Manager'))
             ->_title($this->__('Manage Fields'));
		
            //if($this->getRequest()->getParam('popup')) {
            //    $this->loadLayout('popup');
            //} else {
                $this->loadLayout()
                    ->_setActiveMenu('fme_extensions/fieldsmanager')
                    ->_addBreadcrumb(Mage::helper('catalog')->__('FME Extensions'), Mage::helper('catalog')->__('Field Manager'))
                    ->_addBreadcrumb(
                        Mage::helper('catalog')->__('Manage Field Attributes'),
                        Mage::helper('catalog')->__('Manage Field Attributes'))
                ;
            //}
            return $this;
	}
	
	public function indexAction()
	{
		$this->_initAction()
			->_addContent($this->getLayout()->createBlock('fieldsmanager/adminhtml_fieldsmanager_grid'));
		$this->renderLayout();
	}

    public function editAction() {
        
	$id = (int)$this->getRequest()->getParam('attribute_id');
		
        $model = Mage::getModel('catalog/resource_eav_attribute');
        $collection = Mage::getResourceModel('eav/entity_attribute_collection');
        $data= $collection=Mage::helper('fieldsmanager')->getEavAttribute($collection, false ,$id);
	if($id && $id != 0){
	    $data = Mage::getModel('fieldsmanager/fieldsmanager')->getAfterLoad($data,$id);
	}
       
        if ($data and !empty($data))
        {
       	    $model->load($id);
            $model->addData($data);
        }
            
        if ($model->getId() || $id == 0) {
                $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
                if (!empty($data)) {
                        $model->setData($data);
                }
                Mage::register('fieldsmanager_data', $model);
                $this->_title($this->__('FME Extensions'))
                    ->_title($this->__('Field Manager'))
                    ->_title($this->__('Manage Fields'));
                $this->_title($id ? $model->getFrontendLabel() : $this->__('New Field')); 
                
                $this->loadLayout();
                $this->_setActiveMenu('fme_extensions/fieldsmanager');
                $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
                $this->_addContent($this->getLayout()->createBlock('fieldsmanager/adminhtml_fieldsmanager_edit'))
                     ->_addLeft($this->getLayout()->createBlock('fieldsmanager/adminhtml_fieldsmanager_edit_tabs'));

                $this->renderLayout();
        } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('fieldsmanager')->__('Field does not exist'));
                $this->_redirect('*/*/');
        }
    }
 
	public function newAction() {
		$this->_forward('edit');
	}
	
	public function validateAction()
    {
        $response = new Varien_Object();
        $response->setError(false);

        $attributeCode  = $this->getRequest()->getParam('attribute_code');
        $attributeId    = $this->getRequest()->getParam('attribute_id');
        $attribute = Mage::getModel('catalog/resource_eav_attribute')
            ->loadByCode($this->_entityTypeId, $attributeCode);

        if ($attribute->getId() && !$attributeId) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('catalog')->__('Attribute with the same code already exists'));
            $this->_initLayoutMessages('adminhtml/session');
            $response->setError(true);
            $response->setMessage($this->getLayout()->getMessagesBlock()->getGroupedHtml());
        }

        $this->getResponse()->setBody($response->toJson());
        
    }
     protected function _filterPostData($data)
    {
        if ($data) {
            /** @var $helperCatalog Mage_Catalog_Helper_Data */
            $helperCatalog = Mage::helper('catalog');
            //labels
            foreach ($data['frontend_label'] as & $value) {
                if ($value) {
                    $value = $helperCatalog->escapeHtml($value);
                }
            }
            //options
            if (!empty($data['option']['value'])) {
                foreach ($data['option']['value'] as &$options) {
                    foreach ($options as &$label) {
                        $label = $helperCatalog->escapeHtml($label);
                    }
                }
            }
            if (!empty($data['default_value_message'])) {
                $data['default_value'] = $helperCatalog->escapeHtml($data['default_value_message']);
            }
            if (!empty($data['default_value'])) {
                $data['default_value'] = $helperCatalog->escapeHtml($data['default_value']);
            }
            if (!empty($data['default_value_text'])) {
                $data['default_value_text'] = $helperCatalog->escapeHtml($data['default_value_text']);
            }
            if (!empty($data['default_value_textarea'])) {
                $data['default_value_textarea'] = $helperCatalog->escapeHtml($data['default_value_textarea']);
            }
        }
        return $data;
    }
 
	public function saveAction() {
            if ($data = $this->getRequest()->getPost()) {
              Mage::getSingleton('adminhtml/session')->setIsForFME(true);
	    
             /** @var $session Mage_Admin_Model_Session */
            $session = Mage::getSingleton('adminhtml/session');

            $redirectBack   = $this->getRequest()->getParam('back', false);
            /* @var $model Mage_Catalog_Model_Entity_Attribute */
            $model = Mage::getModel('catalog/resource_eav_attribute');
            /* @var $helper Mage_Catalog_Helper_Product */
            $helper = Mage::helper('catalog/product');

            $id = $this->getRequest()->getParam('attribute_id');
	    //Mage::getModel('fieldsmanager/fieldsmanager')->updaterelatedtables($id,$data);

            //validate attribute_code
           $version = Mage::getVersion();
            //validate attribute_code
            if (isset($data['attribute_code'])) {
		
		if(version_compare($version,'1.4.2.0','<')){
		   $validatorAttrCode = new Zend_Validate_Regex('/^[a-z][a-z_0-9]{1,254}$/');
		}else{
		   $validatorAttrCode = new Zend_Validate_Regex(array('pattern' => '/^[a-z][a-z_0-9]{1,254}$/'));
		}
                
                if (!$validatorAttrCode->isValid($data['attribute_code'])) {
                    $session->addError(
                        $helper->__('Attribute code is invalid. Please use only letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a letter.'));
                    $this->_redirect('*/*/edit', array('attribute_id' => $id, '_current' => true));
                    return;
                }
            }
	    
            //validate frontend_input
            //if (isset($data['frontend_input'])) {
            //    /** @var $validatorInputType Mage_Eav_Model_Adminhtml_System_Config_Source_Inputtype_Validator */
            //    $validatorInputType = Mage::getModel('eav/adminhtml_system_config_source_inputtype_validator');
            //    if (!$validatorInputType->isValid($data['frontend_input'])) {
            //        foreach ($validatorInputType->getMessages() as $message) {
            //            $session->addError($message);
            //        }
            //        $this->_redirect('*/*/edit', array('attribute_id' => $id, '_current' => true));
            //        return;
            //    }
            //}

	     if ($id) {
                $model->load($id);

                if (!$model->getId()) {
                    $session->addError(
                        Mage::helper('catalog')->__('This Attribute no longer exists'));
                    $this->_redirect('*/*/');
                    return;
                }

                // entity type check
                if ($model->getEntityTypeId() != $this->_entityTypeId) {
                    $session->addError(
                        Mage::helper('catalog')->__('This attribute cannot be updated.'));
                    $session->setAttributeData($data);
                    $this->_redirect('*/*/');
                    return;
                }

                $data['attribute_code'] = $model->getAttributeCode();
                $data['is_user_defined'] = $model->getIsUserDefined();
                $data['frontend_input'] = $model->getFrontendInput();
            } else {
                /**
                * @todo add to helper and specify all relations for properties
                */
                if(version_compare($version,'1.4.2.0','<')){
		    if (isset($data['frontend_input']) && $data['frontend_input'] == 'multiselect') {
			$data['backend_model'] = 'eav/entity_attribute_backend_array';
		    }
		}else{
		    $data['source_model'] = $helper->getAttributeSourceModelByInputType($data['frontend_input']);
		    $data['backend_model'] = $helper->getAttributeBackendModelByInputType($data['frontend_input']);
		}
            }
	    
	    if (!isset($data['is_configurable'])) {
                $data['is_configurable'] = 0;
            }
            if (!isset($data['is_filterable'])) {
                $data['is_filterable'] = 0;
            }
            if (!isset($data['is_filterable_in_search'])) {
                $data['is_filterable_in_search'] = 0;
            }
			
            $sRealInput = $data['frontend_input'];
            $model->setEntityType('fme_fieldsmanager');
           
           
	    
            if($data['frontend_input']=='message'){
                $defaultValueField = 'default_value_message';
            }
	    elseif($data['frontend_input'] == 'checkbox'){
                $defaultValueField = null;
            }
            elseif($data['frontend_input'] == 'radio'){
                $defaultValueField ='';
            }else{
		 $defaultValueField = $model->getDefaultValueByInput($data['frontend_input']);
	    }
            //if (is_null($model->getIsUserDefined()) || $model->getIsUserDefined() != 0) {
                $data['backend_type'] = $model->getBackendTypeByInput($data['frontend_input']);
           // }

            
            if ($defaultValueField) {
                $data['default_value'] = $this->getRequest()->getParam($defaultValueField);
            }
	    
	    
            if(!isset($data['apply_to'])) {
                $data['apply_to'] = array();
            }

            //filter
            $data = $this->_filterPostData($data);
            $model->addData($data);
	    $data = Mage::helper('fieldsmanager')->getSource($data);
            
            $model->addData($data);
            
	    $DefaultOptionsValue = Mage::helper('fieldsmanager')->setDefault($data, $model);
               
	    try {
		
		Mage::getModel('fieldsmanager/fieldsmanager')->saveEAVData($model, $DefaultOptionsValue , $data);
		Mage::getSingleton('adminhtml/session')->setIsForFME(false);
		
                Mage::getSingleton('adminhtml/session')->setAttributeData(false);
		Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('fieldsmanager')->__('Field was successfully saved'));
		Mage::getSingleton('adminhtml/session')->setFormData(false);

		if ($this->getRequest()->getParam('back')) {
			$this->_redirect('*/*/edit', array('attribute_id' => $model->getId()));
			return;
		}
		
		$this->_redirect('*/*/index/filter//');
		return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('attribute_id' => $this->getRequest()->getParam('attribute_id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('fieldsmanager')->__('Unable to find item to save'));
        $this->_redirect('*/*/index/filter//');
	}
 
	public function deleteAction() {
		if( $this->getRequest()->getParam('attribute_id') > 0 ) {
			try {
				$model = Mage::getModel('eav/entity_attribute');
				 
				$model->setId($this->getRequest()->getParam('attribute_id'))
					->delete();
					 
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Field was successfully deleted'));
				$this->_redirect('*/*/index/filter//');
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('attribute_id' => $this->getRequest()->getParam('attribute_id')));
			}
		}
		$this->_redirect('*/*/index/filter//');
	}

//    public function massDeleteAction() {
//        $categoriesattributesIds = $this->getRequest()->getParam('fieldsmanager');
//        if(!is_array($categoriesattributesIds)) {
//			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
//        } else {
//            try {
//                foreach ($categoriesattributesIds as $categoriesattributesId) {
//                    $categoriesattributes = Mage::getModel('eav/entity_attribute')->load($categoriesattributesId);
//                    $categoriesattributes->delete();
//                }
//                Mage::getSingleton('adminhtml/session')->addSuccess(
//                    Mage::helper('adminhtml')->__(
//                        'Total of %d record(s) were successfully deleted', count($categoriesattributesIds)
//                    )
//                );
//            } catch (Exception $e) {
//                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
//            }
//        }
//        $this->_redirect('*/*/index/filter//');
//    }
    
    /**
     * Check the permission to run it
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('fme_extensions/fieldsmanager/fieldsmanager');
    }
	
    
}
