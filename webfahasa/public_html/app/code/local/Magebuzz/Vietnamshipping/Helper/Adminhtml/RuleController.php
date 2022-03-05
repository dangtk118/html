<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Helper_Adminhtml_RuleController extends Mage_Adminhtml_Controller_Action {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        }  
        
	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu($this->getModuleStr() . '/shipping_rule')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Rule Manager'), Mage::helper('adminhtml')->__('Rule Manager'));
		
		return $this;
	}   
 
	public function indexAction() {
		$this->_initAction()
			->renderLayout();
	}

	public function editAction() {
		$id     = $this->getRequest()->getParam('id');
		$model  = Mage::getModel($this->getModuleStr() . '/rule')->load($id);

		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('rule_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu($this->getModuleStr() . '/shipping_rule');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Rule Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Rule News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_rule_edit'))
				->_addLeft($this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_rule_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Rule does not exist'));
			$this->_redirect('*/*/');
		}
	}
 
	public function newAction() {
		$this->_forward('edit');
	}
 
	public function saveAction() {
	 if(!Mage::getStoreConfig($this->getModuleStr() . '/general/enable_module')) {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Please enable module'));
		  $this->_redirect('*/*/');
      return;
    }
		if ($data = $this->getRequest()->getPost()) {			
			$model = Mage::getModel($this->getModuleStr() . '/rule');
      // Remove Area old
      if (isset($data['rule']['conditions'])) {
        $data['conditions'] = $data['rule']['conditions'];
      }
      if (isset($data['rule']['actions'])) {
        $data['actions'] = $data['rule']['actions'];
      }
      unset($data['rule']);  
			$model->setData($data)
				->setId($this->getRequest()->getParam('id'));
			 $model->loadPost($data); 
			try {
				if ($model->getCreatedTime() == NULL || $model->getUpdateTime() == NULL) {
					$model->setCreatedTime(now())
						->setUpdateTime(now());
				} else {
					$model->setUpdateTime(now());
				}	
				$customerGroups = serialize($data['customer_groups']);
				$areaId = '';
				if (isset($data['area_id'])) 
					$areaId =  serialize($data['area_id']);
        $model->setCustomerGroups($customerGroups);  
        $model->setAreaId($areaId);  
				$model->save();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper($this->getModuleStr())->__('Rule was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('id' => $model->getId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
      } catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
				return;
			}
    }
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Unable to find Rule to save'));
		$this->_redirect('*/*/');
	}
 
	public function deleteAction() {
	 if(!Mage::getStoreConfig($this->getModuleStr() . '/general/enable_module')) {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Please enable module'));
		  $this->_redirect('*/*/');
      return;
    }
		if( $this->getRequest()->getParam('id') > 0 ) {
			try {
				$model = Mage::getModel($this->getModuleStr() . '/rule');
				 
				$model->setId($this->getRequest()->getParam('id'))
					->delete();
					 
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Rule was successfully deleted'));
				$this->_redirect('*/*/');
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
			}
		}
		$this->_redirect('*/*/');
	}
	
	protected function _isAllowed() {
		switch ($this->getRequest()->getActionName()) {
				case 'new':
				case 'save':
						return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_rule/save');
						break;
				case 'delete':
						return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_rule/delete');
						break;
				default:
						return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_rule/');
						break;
		}
	}

	public function massDeleteAction() {
	 if(!Mage::getStoreConfig($this->getModuleStr() . '/general/enable_module')) {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Please enable module'));
  	  $this->_redirect('*/*/');
      return;
    }
	 
		$vietnamshippingIds = $this->getRequest()->getParam('rule');
		if(!is_array($vietnamshippingIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select Rule(s)'));
		} else {
			try {
				foreach ($vietnamshippingIds as $vietnamshippingId) {
					$vietnamshipping = Mage::getModel($this->getModuleStr() . '/rule')->load($vietnamshippingId);
					$vietnamshipping->delete();
				}
				Mage::getSingleton('adminhtml/session')->addSuccess(
					Mage::helper('adminhtml')->__(
						'Total of %d record(s) were successfully deleted', count($vietnamshippingIds)
					)
				);
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
		}
		$this->_redirect('*/*/index');
	}
	
	public function massStatusAction() {
	 if(!Mage::getStoreConfig($this->getModuleStr() . '/general/enable_module')) {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Please enable module'));
		  $this->_redirect('*/*/');
      return;
    }
		$vietnamshippingIds = $this->getRequest()->getParam('rule');
		if(!is_array($vietnamshippingIds)) {
			Mage::getSingleton('adminhtml/session')->addError($this->__('Please select Rule(s)'));
		} else {
			try {
				foreach ($vietnamshippingIds as $vietnamshippingId) {
					$vietnamshipping = Mage::getSingleton($this->getModuleStr() . '/rule')
						->load($vietnamshippingId)
						->setStatus($this->getRequest()->getParam('status'))
						->setIsMassupdate(true)
						->save();
				}
				$this->_getSession()->addSuccess(
					$this->__('Total of %d record(s) were successfully updated', count($vietnamshippingIds))
				);
			} catch (Exception $e) {
					$this->_getSession()->addError($e->getMessage());
			}
		}
		$this->_redirect('*/*/index');
	}
  
	public function exportCsvAction() {
		$fileName   = 'rule.csv';
		$content    = $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_rule_grid')
			->getCsv();

		$this->_sendUploadResponse($fileName, $content);
	}

	public function exportXmlAction() {
		$fileName   = 'rule.xml';
		$content    = $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_rule_grid')
			->getXml();

		$this->_sendUploadResponse($fileName, $content);
	}

	protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream') {
		$response = $this->getResponse();
		$response->setHeader('HTTP/1.1 200 OK','');
		$response->setHeader('Pragma', 'public', true);
		$response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
		$response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
		$response->setHeader('Last-Modified', date('r'));
		$response->setHeader('Accept-Ranges', 'bytes');
		$response->setHeader('Content-Length', strlen($content));
		$response->setHeader('Content-type', $contentType);
		$response->setBody($content);
		$response->sendResponse();
		die;
	}
}