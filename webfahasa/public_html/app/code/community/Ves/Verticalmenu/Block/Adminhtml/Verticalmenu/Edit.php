<?php
/******************************************************
 * @package Ves Megamenu module for Magento 1.4.x.x and Magento 1.7.x.x
 * @version 1.0.0.1
 * @author http://landofcoder.com
 * @copyright	Copyright (C) December 2010 LandOfCoder.com <@emai:landofcoder@gmail.com>.All rights reserved.
 * @license		GNU General Public License version 2
*******************************************************/
?>
<?php
class Ves_Verticalmenu_Block_Adminhtml_Verticalmenu_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    var $data = null;
    public function __construct()
    {
	    $this->_blockGroup  = 'ves_verticalmenu';
        $this->_objectId    = 'ves_verticalmenu_id';
        $this->_controller  = 'adminhtml_verticalmenu';
        $this->_mode        = 'edit';

        $this->_updateButton('save', 'label', Mage::helper('ves_verticalmenu')->__('Save Theme'));
        $this->_updateButton('delete', 'label', Mage::helper('ves_verticalmenu')->__('Delete Theme'));

        $this->setTemplate('ves_verticalmenu/verticalmenu/edit.phtml');
        
        $mediaHelper = Mage::helper('ves_verticalmenu/media');
        $mediaHelper->loadMedia();

        $this->data = Mage::registry('current_verticalmenu');
    }

     protected function _prepareLayout() {
         /**
         * Display store switcher if system has more one store
         */
        if (!Mage::app()->isSingleStoreMode()) {
            $this->setChild('store_switcher',
                   $this->getLayout()->createBlock('adminhtml/store_switcher')
                   ->setUseConfirm(false)
                   ->setSwitchUrl($this->getUrl('*/*/*/id/'.Mage::registry('current_verticalmenu')->getId(), array('store'=>null)))
           );
        }

        $this->setChild('verticalmenu_tree',
                $this->getLayout()->createBlock('ves_verticalmenu/adminhtml_verticalmenu_tree'));

        $this->setChild('verticalmenu_form',
                $this->getLayout()->createBlock('ves_verticalmenu/adminhtml_verticalmenu_edit_form'));

        return parent::_prepareLayout();
    }
    public function getMenuData(){
        return $this->data;
    }
    public function getWidgets(){
        $widgets = Mage::getModel('ves_verticalmenu/widget')->getCollection();
        $store_id = $this->getRequest()->getParam('store_id');

        if($store_id){
            $widgets->addFieldToFilter('store_id', array('in'=>array(0, $store_id)));
        }
        return $widgets;
        
    }
    public function getHeaderText()
    {
        return Mage::helper('ves_verticalmenu')->__("Venus Verticalmenu");
    }
    public function getCancelLink(){
        return $this->getUrl('*/adminhtml_verticalmenu/index');
    }


    public function getUpdateLink() {
        $store_id = Mage::helper('ves_verticalmenu')->getStoreId();
        if($store_id) {
            return $this->getUrl('*/*/update', array('root'=>1, 'store_id'=> $store_id));
        } else {
            return $this->getUrl('*/*/update', array('root'=>1));
        }
        
    }

    public function getExportLink(){
        return $this->getUrl('*/adminhtml_verticalmenu/exportsample', array('type' => 'json'));
    }

    public function getUploadSampleLink(){
        return $this->getUrl('*/adminhtml_verticalmenu/uploadJson');
    }

    public function getLiveEditLink(){
        $store_id = Mage::helper('ves_verticalmenu')->getStoreId();
        if($store_id) {
            return $this->getUrl('*/adminhtml_verticalmenu/liveedit' , array('store_id' => $store_id));
        } else {
            return $this->getUrl('*/adminhtml_verticalmenu/liveedit');
        }
        
    }
    public function getCreateWidgetLink($widget_id = 0, $widget_type = ""){
        $store_id = Mage::helper("ves_verticalmenu")->getStoreId();
        if($store_id) {
            return $this->getUrl('*/adminhtml_verticalmenu/addwidget', array("id"=>$widget_id,"wtype"=>$widget_type, "store_id"=>$store_id));
        } else {
            return $this->getUrl('*/adminhtml_verticalmenu/addwidget', array("id"=>$widget_id,"wtype"=>$widget_type)); 
        }

    }
    public function getDelWidgetLink($widget_id = 0, $widget_type = ""){
        return $this->getUrl('*/adminhtml_verticalmenu/delwidget', array("id"=>$widget_id,"wtype"=>$widget_type));
    }
    public function getAjaxSaveLink(){
        return $this->getUrl('*/adminhtml_verticalmenu/ajaxsave');
    }
    public function getStoreSwitcherHtml() {
       return $this->getChildHtml('store_switcher');
    }
    protected function getCustomLink($route , $params = array()){
        $link =  Mage::helper("adminhtml")->getUrl($route, $params);
        $link = str_replace("/adminhtml/","/", $link);
        $link = str_replace("/adminhtml_verticalmenu/","/", $link);
        $link = str_replace("//admin","/admin", $link);
        return $link;
    }
    public function getDirectivesLink($params = array()){
       return $this->getCustomLink("*/adminhtml/admin/cms_wysiwyg/directive", $params);
    }
    public function getVariablesLink($params = array()){
       return $this->getCustomLink("*/adminhtml/admin/system_variable/wysiwygPlugin", $params);
    }
    public function getImagesLink($params = array()){
       return $this->getCustomLink("*/adminhtml/admin/cms_wysiwyg_images/index", $params);
    }
    public function getWidgetLink($params = array()){
        return $this->getCustomLink("*/adminhtml/admin/widget/index", $params);
    }
   
}