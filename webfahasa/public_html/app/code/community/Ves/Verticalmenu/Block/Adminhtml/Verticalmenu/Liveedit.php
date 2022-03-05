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
class Ves_Verticalmenu_Block_Adminhtml_Verticalmenu_Liveedit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    var $params = null;
    public function __construct()
    {
	    $this->_blockGroup  = 'ves_verticalmenu';
        $this->_objectId    = 'ves_verticalmenu_id';
        $this->_controller  = 'adminhtml_verticalmenu';
        $this->_mode        = 'liveedit';

        $this->_updateButton('save', 'label', Mage::helper('ves_verticalmenu')->__('Save Theme'));
        $this->_updateButton('delete', 'label', Mage::helper('ves_verticalmenu')->__('Delete Theme'));

        $this->setTemplate('ves_verticalmenu/verticalmenu/liveedit.phtml');
        
        $mediaHelper = Mage::helper('ves_verticalmenu/media');
        $mediaHelper->loadMedia();
        $mediaHelper->loadMediaLiveEdit();

        $this->params = array();
    }

    protected function _prepareLayout() {
        return parent::_prepareLayout();
    }

    public function getParam($name, $default = ""){
        return isset($this->params[$name])?$this->params['name']: $default;
    }

    public function getParams(){
        return $this->params;
    }
    public function getWidgets(){
        $widgets = Mage::getModel('ves_verticalmenu/widget')->getCollection();
        /*
        $store_id = $this->getRequest()->getParam('store_id');

        if($store_id){
            $widgets->addFieldToFilter('store_id', $store_id);
        }*/
        return $widgets;
        
    }

    public function getHeaderText()
    {
        return Mage::helper('ves_verticalmenu')->__("Venus Verticalmenu");
    }

    public function getLiveSiteUrl(){
        $live_site_url = Mage::getBaseUrl();
        $live_site_url = str_replace("index.php/", "", $live_site_url);
        return $live_site_url;

    }

    public function getBackLink(){
        $store_id = Mage::helper("ves_verticalmenu")->getStoreId();
        if($store_id) {
            return $this->getUrl('*/adminhtml_verticalmenu/index', array("store_id"=>$store_id));
        } else {
            return $this->getUrl('*/adminhtml_verticalmenu/index'); 
        }

    }
    public function getLiveEditLink(){
        $store_id = Mage::helper("ves_verticalmenu")->getStoreId();
        if($store_id) {
            return $this->getUrl('*/adminhtml_verticalmenu/livesave', array("store_id"=>$store_id));
        } else {
            return $this->getUrl('*/adminhtml_verticalmenu/livesave'); 
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
    public function getRenderWidgetLink(){
       return $this->getUrl('*/adminhtml_verticalmenu/renderwidget');
    }
    public function getAjaxGenmenuLink(){
        $store_id = Mage::helper("ves_verticalmenu")->getStoreId();
        if($store_id) {
            return $this->getUrl('*/adminhtml_verticalmenu/ajxgenmenu', array("store_id"=>$store_id));
        } else {
            return $this->getUrl('*/adminhtml_verticalmenu/ajxgenmenu'); 
        }
        
    }
    public function getAjaxMenuinfoLink(){
        $store_id = Mage::helper("ves_verticalmenu")->getStoreId();
        if($store_id) {
            return $this->getUrl('*/adminhtml_verticalmenu/ajxmenuinfo', array("store_id"=>$store_id));
        } else {
            return $this->getUrl('*/adminhtml_verticalmenu/ajxmenuinfo'); 
        }
    }
    public function getAjaxSaveLink(){
        $store_id = Mage::helper("ves_verticalmenu")->getStoreId();
        if($store_id) {
            return $this->getUrl('*/adminhtml_verticalmenu/ajaxsave', array("store_id"=>$store_id));
        } else {
            return $this->getUrl('*/adminhtml_verticalmenu/ajaxsave'); 
        }
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