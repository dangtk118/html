<?php

/**
 * Load Layout 
 */
class Fahasa_Phanloaivct_IndexController extends Mage_Adminhtml_Controller_Action {

    public function indexAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    /**
     * Luu du lieu tu table vietnamshiping_area vao tabble phanloaivct_khuvuc
     */
    public function clearText(String $str){
    $arrs = array("khuvuc_from_","khuvuc_to_","express_khuvuc_from_","express_khuvuc_to_");
        foreach($arr as $arrs){
            $str = str_replace($arr,"",$str);
        }
        return $str;
        
    }
    
    public function postAction() {
        $post = $this->getRequest()->getPost();
        $kvs = Mage::getModel('phanloaivct/khuvuc')->getCollection();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }
            
            foreach($kvs as $kv){
                $khuvuc_id = $kv->getKhuvucId();
                $kv_from = "khuvuc_from_" . $khuvuc_id;
                $kv_to = "khuvuc_to_" . $khuvuc_id;
                $ex_kv_from = "express_khuvuc_from_" . $khuvuc_id;
                $ex_kv_to = "express_khuvuc_to_" . $khuvuc_id;
                if($kv->getKhuvucId()){
                    $kv->setKhuvucFrom($post[$kv_from]);
                    $kv->setKhuvucTo($post[$kv_to]);
                    $kv->setExpressKhuvucFrom($post[$ex_kv_from]);
                    $kv->setExpressKhuvucTo($post[$ex_kv_to]);
                    $kv->save();
                }else{
                    $kv_insert = Mage::getModel('phanloaivct/khuvuc');
                    $kv_insert->setKhuvucId($post[$khuvuc_id]);
                    $kv_insert->setKhuvucFrom($post[$kv_from]);
                    $kv_insert->setKhuvucTo($post[$kv_to]);
                    $kv_insert->setExpressKhuvucFrom($post[$ex_kv_to]);
                    $kv_insert->setExpressKhuvucTo($post[$ex_kv_to]);
                    $kv_insert->save();
                }
            }
            $message = $this->__('Your form has been submitted successfully.');
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        // Redirect /index.php/phanloaivct/index/index
        $this->_redirect('*/*');
    }

}
