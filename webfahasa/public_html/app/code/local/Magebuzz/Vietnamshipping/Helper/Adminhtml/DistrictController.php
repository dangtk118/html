<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Helper_Adminhtml_DistrictController extends Mage_Adminhtml_Controller_Action {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        }   
        
	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu($this->getModuleStr() . '/location')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Manager District'), Mage::helper('adminhtml')->__('Manager District'));
		
		return $this;
	}   
 
	public function indexAction() {
		$this->_initAction()
			->renderLayout();
	}

	public function editAction() {
		$id     = $this->getRequest()->getParam('id');
		$model  = Mage::getModel($this->getModuleStr() . '/district')->load($id);

		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('district_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu($this->getModuleStr() . '/location');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('District Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('District News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_district_edit'))
				->_addLeft($this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_district_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('District does not exist'));
			$this->_redirect('*/*/');
		}
	}
 
	public function newAction() {
		$this->_forward('edit');
	}
 
	public function saveAction() {
	  if (!Mage::getStoreConfig($this->getModuleStr() . '/general/enable_module')) {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Please enable module'));
		  $this->_redirect('*/*/');
      return;
    }				
			
		if ($data = $this->getRequest()->getPost()) {			
			// check if district code is existed
			if ((!$this->getRequest()->getParam('id') && Mage::helper($this->getModuleStr())->isExistedDistrictCode($data['district_code'])) || Mage::helper($this->getModuleStr())->isExistedDistrictCodeEditData($this->getRequest()->getParam('id'),$data['district_code'])) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Duplicated district code'));
				Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
				return;
			}
		
			$model = Mage::getModel($this->getModuleStr() . '/district');
      //remove district old
      $areaId = $model->load($this->getRequest()->getParam('id'))->getAreaId();
      $areaModel = Mage::getModel($this->getModuleStr() . '/area');
      if($areaId != $data['area_id'] && $areaId != null && $areaId !=0) {
        $areaModelBefore = $areaModel->load($areaId);
        if($areaModelBefore) {
          $arrayDistrictIds = explode(',',$areaModelBefore->getDistrictIds());
          foreach ($arrayDistrictIds as $key => $districtId) {
            if($districtId==$this->getRequest()->getParam('id')) {
            unset($arrayDistrictIds[$key]);
            continue;
            }
          }
          $areaModelBefore->setDistrictIds(Mage::helper($this->getModuleStr())->prepareCharacter($arrayDistrictIds));
          $areaModelBefore->save();
        }
       } 

			$model->setData($data)
				->setId($this->getRequest()->getParam('id'));
			
			try {
			
				$model->save();
        
        // add new district to area
        
        if($data['area_id']) {
          $areaModelAfter = $areaModel->load($data['area_id']);
          if($areaModelAfter) {
            $_arrayDistrictIds = explode(',',$areaModelAfter->getDistrictIds());
            array_push($_arrayDistrictIds,$model->getDistrictId());
            $areaModelAfter->setDistrictIds(Mage::helper($this->getModuleStr())->prepareCharacter($_arrayDistrictIds));
            $areaModelAfter->save();
          }
        }
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper($this->getModuleStr())->__('District was successfully saved'));
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
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Unable to find District to save'));
		$this->_redirect('*/*/');
	}
 
	public function deleteAction() {
	  if (!Mage::getStoreConfig($this->getModuleStr() . '/general/enable_module')) {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Please enable module'));
		  $this->_redirect('*/*/');
      return;
    }
		if( $this->getRequest()->getParam('id') > 0 ) {
			try {
				$model = Mage::getModel($this->getModuleStr() . '/district');
				 
				$model->setId($this->getRequest()->getParam('id'))
					->delete();
					 
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('District was successfully deleted'));
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
						return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_district/save');
						break;
				case 'delete':
						return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_district/delete');
						break;
				default:
						return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_district/');
						break;
		}
	}

	public function massDeleteAction() {
	  if (!Mage::getStoreConfig($this->getModuleStr() . '/general/enable_module')) {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Please enable module'));
  	  $this->_redirect('*/*/');
      return;
    }
		$vietnamshippingIds = $this->getRequest()->getParam('district');
		if (!is_array($vietnamshippingIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select District(s)'));
		} else {
			try {
				foreach ($vietnamshippingIds as $vietnamshippingId) {
					$vietnamshipping = Mage::getModel($this->getModuleStr() . '/district')->load($vietnamshippingId);
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
    if (!Mage::getStoreConfig($this->getModuleStr() . '/general/enable_module')) {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Please enable module'));
  	  $this->_redirect('*/*/');
      return;
    }
		$vietnamshippingIds = $this->getRequest()->getParam('district');
		if (!is_array($vietnamshippingIds)) {
			Mage::getSingleton('adminhtml/session')->addError($this->__('Please select District(s)'));
		} else {
			try {
				foreach ($vietnamshippingIds as $vietnamshippingId) {
					$vietnamshipping = Mage::getSingleton($this->getModuleStr() . '/district')
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
		$fileName   = 'district.csv';
		$content    = $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_district_grid')
			->getCsv();

		$this->_sendUploadResponse($fileName, $content);
	}

	public function exportXmlAction() {
		$fileName   = 'district.xml';
		$content    = $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_district_grid')
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