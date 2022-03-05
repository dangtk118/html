<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Helper_Adminhtml_ProvinceController extends Mage_Adminhtml_Controller_Action {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        }   
        
	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu($this->getModuleStr() . '/location')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Province Manager'), Mage::helper('adminhtml')->__('Province Manager'));
		
		return $this;
	}   
 
	public function indexAction() {
		$this->_initAction()
			->renderLayout();
	}

	public function editAction() {
		$id     = $this->getRequest()->getParam('id');
		$model  = Mage::getModel($this->getModuleStr() . '/province')->load($id);

		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('province_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu($this->getModuleStr() . '/location');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Province Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Province News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_province_edit'))
				->_addLeft($this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_province_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Province does not exist'));
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
			if ((!$this->getRequest()->getParam('id') && Mage::helper($this->getModuleStr())->isExistedProvinceCode($data['province_code'])) || Mage::helper('vietnamshipping')->isExistedProvinceCodeEditData($this->getRequest()->getParam('id'),$data['province_code'])) {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Duplicated province code.'));
				Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
				return;
			}
		
			$model = Mage::getModel($this->getModuleStr() . '/province');
      // Remove Area old
      $areaId = $model->load($this->getRequest()->getParam('id'))->getAreaId();
      
      $areaModel = Mage::getModel($this->getModuleStr() . '/area');
      if($areaId != $data['area_id'] && $areaId != null && $areaId!=0) {
        $areaModelBefore = $areaModel->load($areaId);
        if($areaModelBefore) {
          $arrayProvinceIds = explode(',',$areaModelBefore->getProvinceIds());
          foreach ($arrayProvinceIds as $key => $provinceId) {
            if($provinceId==$this->getRequest()->getParam('id')) {
            unset($arrayProvinceIds[$key]);
            continue;
            }
          }
          
          $areaModelBefore->setProvinceIds(Mage::helper($this->getModuleStr())->prepareCharacter($arrayProvinceIds));
          $areaModelBefore->save();
        }
      }	

			$model->setData($data)
				->setId($this->getRequest()->getParam('id'));
			
			try {
			
				$model->save();
        // add new province to area
        
        if ($data['area_id']) {
          $areaModelAfter = $areaModel->load($data['area_id']);
          if($areaModelAfter) {
            $_arrayProvinceIds = explode(',',$areaModelAfter->getProvinceIds());
            array_push($_arrayProvinceIds,$model->getProvinceId());
            $areaModelAfter->setProvinceIds(Mage::helper($this->getModuleStr())->prepareCharacter($_arrayProvinceIds));
            $areaModelAfter->save();
          }
        }
        //save district from province 
      
        $provinceId = $model->getId();
        $districtCollection = Mage::getModel($this->getModuleStr() . '/district')->getCollection()->addFieldToFilter('province_id', $provinceId)->getData() ;
        $districtIdsOld = array();
        foreach ($districtCollection as $_dist){
          $districtIdsOld[] =  $_dist['district_id'] ;
        }
			
				if (!isset($data['district_ids'])) {
					$data['district_ids'] = array();
				}
				
				$model->compareDistrictList($data['district_ids'], $districtIdsOld, $provinceId);				
         
        /* Save province to table Directoty_country_region */
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $country_code = 'VN';
        $locale = 'en_US';
        $directoryModel = Mage::getResourceModel('directory/region_collection');
        $directoryModel->addFieldToFilter('province_id',$model->getProvinceId());
        if(count($directoryModel)) {
          foreach ($directoryModel as $_directoryModel) {
            $_directoryModel->setCountryId($country_code);
            $_directoryModel->setCode($model->getProvinceCode());
            $_directoryModel->setDefaultName($model->getProvinceName());
            $_directoryModel->setProvinceId($model->getProvinceId());
            $_directoryModel->save();
            // update region name
            $region_id = $_directoryModel->getRegionId();
            $queryUpdate = "UPDATE ".Mage::getSingleton('core/resource')->getTableName('directory_country_region_name')." SET name='".$model->getProvinceName()."' WHERE region_id='".$region_id."'";
            $connection->query($queryUpdate);
          }
        } else {
          
         	$sql = "INSERT INTO ".Mage::getSingleton('core/resource')->getTableName('directory_country_region')." (`region_id`,`country_id`,`code`,`default_name`,`province_id`) VALUES (NULL,?,?,?,?)";
         	$connection->query($sql,array($country_code,$model->getProvinceCode(),$model->getProvinceName(),$model->getProvinceId()));
        	// get new region id for next query
        	$region_id = $connection->lastInsertId();
        	// insert region name
        	$sql = "INSERT INTO ".Mage::getSingleton('core/resource')->getTableName('directory_country_region_name')." (`locale`,`region_id`,`name`) VALUES (?,?,?)";
        	$connection->query($sql,array($locale,$region_id,$model->getProvinceName())); 
        }
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper($this->getModuleStr())->__('Province was successfully saved'));
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
		Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Unable to find Province to save'));
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
				$model = Mage::getModel($this->getModuleStr() . '/province');
				 
				$model->setId($this->getRequest()->getParam('id'))
				->delete();
        /* remove province in district*/
        $districts = Mage::getModel($this->getModuleStr() . '/district')->getCollection();
        $districts->addFieldToFilter('province_id',$this->getRequest()->getParam('id'));  
        if(count($districts)) {
          foreach ($districts as $_district) {
            $_district->setProvinceId('0')->save();
          }
        }    
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Province was successfully deleted'));
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
						return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_provide/save');
						break;
				case 'delete':
						return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_provide/delete');
						break;
				default:
						return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_provide/');
						break;
		}
	}

	public function massDeleteAction() {
	 if(!Mage::getStoreConfig($this->getModuleStr(). '/general/enable_module')) {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Please enable module'));
  	  $this->_redirect('*/*/');
      return;
    }
		$vietnamshippingIds = $this->getRequest()->getParam('province');
		if(!is_array($vietnamshippingIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select Province(s)'));
		} else {
			try {
				foreach ($vietnamshippingIds as $vietnamshippingId) {
					$vietnamshipping = Mage::getModel($this->getModuleStr() . '/province')->load($vietnamshippingId);
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
		$vietnamshippingIds = $this->getRequest()->getParam('province');
		if(!is_array($vietnamshippingIds)) {
			Mage::getSingleton('adminhtml/session')->addError($this->__('Please select Province(s)'));
		} else {
			try {
				foreach ($vietnamshippingIds as $vietnamshippingId) {
					$vietnamshipping = Mage::getSingleton($this->getModuleStr() . '/province')
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
		$fileName   = 'province.csv';
		$content    = $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_province_grid')
			->getCsv();

		$this->_sendUploadResponse($fileName, $content);
	}

	public function exportXmlAction() {
		$fileName   = 'province.xml';
		$content    = $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_province_grid')
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