<?php

/*
 * Copyright (c) 2014 www.magebuzz.com 
 */

class Magebuzz_Vietnamshipping_Helper_Adminhtml_AreaController extends Mage_Adminhtml_Controller_Action {

    protected function getModuleStr() {
        return "vietnamshipping";
    }

    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu($this->getModuleStr() . '/location')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Area Manager'), Mage::helper('adminhtml')->__('Area Manager'));

        return $this;
    }

    public function indexAction() {
        $this->_initAction()
                ->renderLayout();
    }

    public function editAction() {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel($this->getModuleStr() . '/area')->load($id);

        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }

            Mage::register('area_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu($this->getModuleStr() . '/location');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Area Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Area News'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_area_edit'))
                    ->_addLeft($this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_area_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Area does not exist'));
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
            //check if existing area code
            if ((!$this->getRequest()->getParam('id') && Mage::helper($this->getModuleStr())->isExistedAreaCode($data['area_code'])) || Mage::helper($this->getModuleStr())->isExistedAreaCodeEditData($this->getRequest()->getParam('id'), $data['area_code'])) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Duplicate area code.'));
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }

            $model = Mage::getModel($this->getModuleStr() . '/area');
            $model->setData($data);

            $provinceIds = '';
            $newProvinceIds = array();
            $districtIds = '';
            $newDistrictIds = array();
            if (isset($data['selected_provinces'])) {
                parse_str($data['selected_provinces'], $newProvinceIds);
                $newProvinceIds = array_keys($newProvinceIds);
                $provinceIds = Mage::helper($this->getModuleStr())->formatDistrictIds($newProvinceIds);
                $model->setProvinceIds($provinceIds);
            }
            if (isset($data['selected_districts'])) {
                parse_str($data['selected_districts'], $newDistrictIds);
                $newDistrictIds = array_keys($newDistrictIds);
                $districtIds = Mage::helper($this->getModuleStr())->formatDistrictIds($newDistrictIds);
                $model->setDistrictIds($districtIds);
            }



            try {
                $model->setId($this->getRequest()->getParam('id'))
                        ->save();

                /* update area for province */
                $areaId = $model->getId();
                $model->load($this->getRequest()->getParam('id'));
                $provinceCollection = Mage::getModel($this->getModuleStr() . '/province')->getCollection()->addFieldToFilter('area_id', $areaId)->getData();
                $provinceIdsOld = array();
                foreach ($provinceCollection as $_pro) {
                    $provinceIdsOld[] = $_pro['province_id'];
                }
                $newProvinceIds = array();
                if ($model->getProvinceIds() != '') {
                    $newProvinceIds = explode(',', $model->getProvinceIds());
                }
                $model->compareProvinceList($newProvinceIds, $provinceIdsOld, $areaId);

                // /* update area for district */
                $districtCollection = Mage::getModel($this->getModuleStr() . '/district')->getCollection()
                        ->addFieldToFilter('area_id', $areaId)
                        ->getData();
                $districtIdsOld = array();
                foreach ($districtCollection as $_dist) {
                    $districtIdsOld[] = $_dist['district_id'];
                }
                $newDistrictIds = array();
                if ($model->getDistrictIds() != '') {
                    $newDistrictIds = explode(',', $model->getDistrictIds());
                }
                $model->compareDistrictList($newDistrictIds, $districtIdsOld, $areaId);


                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper($this->getModuleStr())->__('Area was successfully saved'));
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
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Unable to find Area to save'));
        $this->_redirect('*/*/');
    }

    public function districtlistAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('area.edit.tab.district')
                ->setDistricts($this->getRequest()->getPost('odistrict', null));
        $this->renderLayout();
    }

    public function districtlistGridAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('area.edit.tab.district')
                ->setDistricts($this->getRequest()->getPost('odistrict', null));
        $this->renderLayout();
    }

    public function provincelistAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('area.edit.tab.province')
                ->setProvinces($this->getRequest()->getPost('oprovince', null));
        $this->renderLayout();
    }

    public function provincelistGridAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('area.edit.tab.province')
                ->setProvinces($this->getRequest()->getPost('oprovince', null));
        $this->renderLayout();
    }

    public function deleteAction() {
        if (!Mage::getStoreConfig($this->getModuleStr() . '/general/enable_module')) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Please enable module'));
            $this->_redirect('*/*/');
            return;
        }
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel($this->getModuleStr() . '/area');

                $model->setId($this->getRequest()->getParam('id'))
                        ->delete();
                /* remove area in district */
                $districts = Mage::getModel($this->getModuleStr() . '/district')->getCollection();
                $districts->addFieldToFilter('area_id', $this->getRequest()->getParam('id'));
                if (count($districts)) {
                    foreach ($districts as $_district) {
                        $_district->setAreaId('0')->save();
                    }
                }
                /* remove area in province */
                $provinces = Mage::getModel($this->getModuleStr() . '/province')->getCollection();
                $provinces->addFieldToFilter('area_id', $this->getRequest()->getParam('id'));
                if (count($provinces)) {
                    foreach ($provinces as $_province) {
                        $_province->setAreaId('0')->save();
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Area was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
        return;
    }

    protected function _isAllowed() {
        switch ($this->getRequest()->getActionName()) {
            case 'new':
            case 'save':
                return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_area/save');
                break;
            case 'delete':
                return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_area/delete');
                break;
            default:
                return Mage::getSingleton('admin/session')->isAllowed($this->getModuleStr() . '/adminhtml_area/');
                break;
        }
    }

    public function massDeleteAction() {
        if (!Mage::getStoreConfig($this->getModuleStr() . '/general/enable_module')) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper($this->getModuleStr())->__('Please enable module'));
            $this->_redirect('*/*/');
            return;
        }
        $vietnamshippingIds = $this->getRequest()->getParam('area');
        if (!is_array($vietnamshippingIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select Area(s)'));
        } else {
            try {
                foreach ($vietnamshippingIds as $vietnamshippingId) {
                    $vietnamshipping = Mage::getModel($this->getModuleStr() . '/area')->load($vietnamshippingId);
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
        $vietnamshippingIds = $this->getRequest()->getParam($this->getModuleStr());
        if (!is_array($vietnamshippingIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select Area(s)'));
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
        $fileName = 'area.csv';
        $content = $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_area_grid')
                ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction() {
        $fileName = 'area.xml';
        $content = $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_area_grid')
                ->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

    protected function _sendUploadResponse($fileName, $content, $contentType = 'application/octet-stream') {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK', '');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }

}
