<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Adminhtml_ShippingweightController extends Mage_Adminhtml_Controller_Action {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        }      
        
	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu($this->getModuleStr() . '/shipping_weight')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Rule Manager'));
		
		return $this;
	}   
 
	public function indexAction() {
		$this->_initAction()
			->renderLayout();
	}

	public function editAction() {
		$id     = $this->getRequest()->getParam('id');
		$model  = Mage::getModel($this->getModuleStr() . '/shippingweight')->load($id);

		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('shippingweight_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu($this->getModuleStr() . '/shipping_weight');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Rule Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Rule News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_shippingweight_edit'))
				->_addLeft($this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_shippingweight_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('vietnamshipping')->__('Rule does not exist'));
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
			$model = Mage::getModel($this->getModuleStr() . '/shippingweight');
      // Remove Area old
			$model->setData($data)
				->setId($this->getRequest()->getParam('id'));
			try {
				if ($model->getCreatedTime() == NULL || $model->getUpdateTime() == NULL) {
					$model->setCreatedTime(now())
						->setUpdateTime(now());
				} else {
					$model->setUpdateTime(now());
				}	
			
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
				$model = Mage::getModel($this->getModuleStr() . '/shippingweight');
				 
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
						return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_shippingweight/save');
						break;
				case 'delete':
						return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_shippingweight/delete');
						break;
				default:
						return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_shippingweight/');
						break;
		}
	}

	public function massDeleteAction() {
	 if(!Mage::getStoreConfig($this->getModuleStr() . '/general/enable_module')) {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Please enable module'));
  	  $this->_redirect('*/*/');
      return;
    }
		$vietnamshippingIds = $this->getRequest()->getParam('shippingweight');
		if(!is_array($vietnamshippingIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select Rule(s)'));
		} else {
			try {
				foreach ($vietnamshippingIds as $vietnamshippingId) {
					$vietnamshipping = Mage::getModel($this->getModuleStr() . '/shippingweight')->load($vietnamshippingId);
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
		$vietnamshippingIds = $this->getRequest()->getParam('shippingweight');
		if(!is_array($vietnamshippingIds)) {
			Mage::getSingleton('adminhtml/session')->addError($this->__('Please select Rule(s)'));
		} else {
			try {
				foreach ($vietnamshippingIds as $vietnamshippingId) {
					$vietnamshipping = Mage::getSingleton($this->getModuleStr() . '/shippingweight')
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
		$fileName   = 'shippingweight.csv';
		$content    = $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_shippingweight_grid')
			->getCsv();

		$this->_sendUploadResponse($fileName, $content);
	}

	public function exportXmlAction() {
		$fileName   = 'shippingweight.xml';
		$content    = $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_shippingweight_grid')
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