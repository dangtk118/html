<?php

/******************************************************
 * @package Ves Megamenu module for Magento 1.4.x.x and Magento 1.7.x.x
 * @version 1.0.0.1
 * @author http://landofcoder.com
 * @copyright   Copyright (C) December 2010 LandOfCoder.com <@emai:landofcoder@gmail.com>.All rights reserved.
 * @license     GNU General Public License version 2
*******************************************************/
class Ves_Verticalmenu_Install extends Mage_Catalog_Model_Resource_Eav_Mysql4_Setup {
    public function install(){
        $sqldir = Mage::getModuleDir('sql', 'Ves_Verticalmenu');
        $sqldir = $sqldir.DS."ves_verticalmenu_setup".DS;
        $files = glob($sqldir.'*');
        foreach( $files as $dir ){
            if( preg_match("#.php#", $dir)){
                include( $dir );
            }
        }
    }
}
class Ves_Verticalmenu_Adminhtml_VerticalmenuController extends Mage_Adminhtml_Controller_Action {

    private $_DEFAULT_ROOT_CATE_ID = 1;

    protected function _initAction() {
        $this->_title($this->__('Manage Verticalmenu Items'))
                ->_title($this->__('Verticalmenu'));

        $this->loadLayout()
                ->_setActiveMenu('ves/verticalmenu')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

        return $this;
    }

    public function indexAction() {

        $this->_forward('edit');
    }

    public function index2Action() {
        $this->_forward('edit', null, null, array("position_type" => "left"));
    }

    public function editAction($position_type = "top") {
        /*
        $installer = new Ves_verticalmenu_Install();
        $installer->install();
        */

        $position_type = $this->getRequest()->getParam('position_type');
        Mage::getSingleton('admin/session')
                ->setMegaPositionType($position_type);

        $id = $this->getRequest()->getParam('id');
        $verticalmenu = Mage::getModel('ves_verticalmenu/verticalmenu')->load($id);
        Mage::register('current_verticalmenu', $verticalmenu);
        
        $storeId = $this->getRequest()->getParam('store_id');
        $storeId = !empty($storeId) ? $storeId : 0;
        Mage::getSingleton('admin/session')
                ->setLastViewedStore($storeId);
        Mage::getSingleton('admin/session')
                ->setLastEditedVerticalmenu($verticalmenu->getId());

        
        if ( $verticalmenu->getId() ) {
            $this->_title( $this->__('Venus Verticalmenu - Edit '.$verticalmenu->getTitle() ));
        }

        $this->_initAction();
       
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
        if ($head = $this->getLayout()->getBlock('head')) {
            $head->addItem('js', 'prototype/window.js')
                ->addItem('js_css', 'prototype/windows/themes/default.css')
                ->addCss('lib/prototype/windows/themes/magento.css')
                ->addItem('js', 'mage/adminhtml/variables.js')
                ->addItem('js', 'mage/adminhtml/wysiwyg/widget.js')
                ->addItem('js', 'lib/flex.js')
                ->addItem('js', 'lib/FABridge.js')
                ->addItem('js', 'mage/adminhtml/flexuploader.js')
                ->addItem('js', 'mage/adminhtml/browser.js')
                ;
        }
        $this->_addContent($this->getLayout()->createBlock('ves_verticalmenu/adminhtml_verticalmenu_edit'));
        
        $this->renderLayout();
    }
    
    public function addwidgetAction(){

        Mage::getSingleton('admin/session')
                ->setMegaPositionType("top");
        $id = $this->getRequest()->getParam('id');

        $widget = Mage::getModel('ves_verticalmenu/widget')->load($id);
        Mage::register('current_widget', $widget);
        $form            = '';
        $widget_selected = '';
        $this->_title( $this->__('Add & Edit Widget'));
        
        $this->_initAction();
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
        if ($head = $this->getLayout()->getBlock('head')) {
            $head->addItem('js', 'prototype/window.js')
                ->addItem('js_css', 'prototype/windows/themes/default.css')
                ->addCss('lib/prototype/windows/themes/magento.css')
                ->addItem('js', 'mage/adminhtml/variables.js')
                ->addItem('js', 'mage/adminhtml/wysiwyg/widget.js')
                ->addItem('js', 'lib/flex.js')
                ->addItem('js', 'lib/FABridge.js')
                ->addItem('js', 'mage/adminhtml/flexuploader.js')
                ->addItem('js', 'mage/adminhtml/browser.js')
                ;
        }

        $this->_addContent($this->getLayout()->createBlock('ves_verticalmenu/adminhtml_widget_edit'));
        $this->renderLayout();

        return;
    }
    public function delwidgetAction(){

        Mage::getSingleton('admin/session')
                ->setMegaPositionType("top");
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('ves_verticalmenu/widget');
        $model->setId($id)
                ->delete();
        Mage::getSingleton('adminhtml/session')
            ->addSuccess(Mage::helper('adminhtml')
            ->__('Widget was deleted successfully'));
        $store_id = Mage::helper("ves_verticalmenu")->getStoreId();
        if($store_id) {
            $this->_redirect('*/*/store_id/'.$store_id."/");
        } else {
            $this->_redirect('*/*/');
        }
        
    }
    public function savewidgetAction(){
        $data =  $this->getRequest()->getPost();
        if( isset($data['widget']) && isset($data['params']) ){

            $data['widget']['params'] = $data['params'];
            $post = array(
                'id'     => '',
                'params' => '',
                'type'   => ''
            );
            
            $data['widget'] = array_merge( $post, $data['widget'] ); 

            $image = isset($data['widget']['params']['image_path'])?$data['widget']['params']['image_path']:"";

           
            $data['widget']['store_id'] = $this->getRequest()->getParam('store_id');
            $id = $data['widget']['id'];

            unset( $data['widget']['id'] );

            $widget = Mage::getModel('ves_verticalmenu/widget')->load($id);

            if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
                try {
                    $uploader = new Varien_File_Uploader('image');
                    $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png'));
                    $uploader->setAllowRenameFiles(false);
                    $uploader->setFilesDispersion(true);
                    $path = Mage::getBaseDir('media') . DS . 'ves_verticalmenu';
                    $uploader->save($path, $_FILES['image']['name']);
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    return;
                }
                $image = 'ves_verticalmenu' . $uploader->getUploadedFileName();

            }  
     
            if (isset($data['image']['delete']) && $data['image']['delete'] == 1) {
                $path = Mage::getBaseDir('media') . DS . $image;
                if( $image && file_exists($path) ){
                    @unlink($path);
                }
                $image = '';
            }
            $data['widget']['params']['image_path'] = $image;

            if( $data['widget']['params'] ){
                $data['widget']['params'] = base64_encode(serialize($data['widget']['params']));
            }

            $widget->setData( $data['widget'] );
            if($id){
                $widget->setId($id);
            }
            try {
                $widget->save();
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError('Could not save widget');
                Mage::throwException($e);
            }
            $this->_redirect('*/*/addwidget', array("done"=>1,"id"=>$widget->getId(),"wtype"=>$widget->getType()));
        }
        $this->_forward('addwidget');
    }
    
    private function _updateStore($menu_id = 0){
        if($menu_id > 1){
            $store_id = $this->getRequest()->getParam('store_id');
            $stores = !empty($store_id)?array($store_id):array(0);
            if($menu_id){
                Mage::getModel('ves_verticalmenu/verticalmenu')
                                    ->updateStores($stores, $menu_id);
            }
            return true;
        }
        
        return false;
    }

    private function _importCategory($category) {
        $category_id = $category->getId();
        if($category_id == $this->_DEFAULT_ROOT_CATE_ID ) {
          $parent = Mage::getModel('ves_verticalmenu/verticalmenu')->loadByTitle("ROOT");
          $p = $parent->getId()>0?$parent->getId():0;
          if($p)
            return; 
        }

        $store_id = $this->getRequest()->getParam('store_id');
        $store_id = !empty($store_id)?$store_id:0;

        $category = $category->setStoreId($store_id)->load($category->getId());

        $stores = !empty($store_id)?array($store_id):array(0);
        $parentCategoryId = $category->getParentId();
        $parentVerticalmenu = Mage::getModel('ves_verticalmenu/verticalmenu')->loadByCategoryId($parentCategoryId, $store_id, true);
        $p = $parentVerticalmenu->getId()>0?$parentVerticalmenu->getId():0;

        if(!$p){
            $parentVerticalmenu = Mage::getModel('ves_verticalmenu/verticalmenu')->loadByTitle("ROOT");
            $p = $parentVerticalmenu->getId()>0?$parentVerticalmenu->getId():0;
        }

        if($category->getParentId() == 0){
            if($p == 0){
               $category->setName("ROOT"); 
            }
            
        }

        if($p == 1 && $store_id) {
            $store_info = Mage::getModel('core/store')->load($store_id);
            $name = $category->getName()." (".$store_info->getName().")";
        } else {
            $name = $category->getName();
        }

        $verticalmenu = Mage::getModel('ves_verticalmenu/verticalmenu')->load(null);
        $data = array("title" => $name,
                      "description" => "",
                      "level" => ($category->getLevel() - 1),
                      "show_title" => 1,
                      "published" => 1,
                      "is_group" => 0,
                      "image" => "",
                      "widget_id" => 0,
                      "menu_icon_class" => "",
                      "type_submenu" => "menu",
                      "type" => "category",
                      "left" => 0,
                      "right" => 0,
                      "item" => $category->getId(),
                      "parent_id" => $p,
                      "position" => $category->getPosition(),
                      "store_id" => $store_id,
                      "stores" => $stores);

        $verticalmenu->setData($data);

        try {
            $verticalmenu->save();
            if($data['level'] < 0){
                $verticalmenu->updateId(1);
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError('Could not import categories');
            Mage::throwException($e);
        }
    }

    public function importCategoriesAction() {
        $existsIds = array();
        $store_id = $this->getRequest()->getParam('store_id');
        $store_id = !empty($store_id)?$store_id:0;

        $existsCategory = Mage::getModel('ves_verticalmenu/verticalmenu')
                ->getCollection()
                ->addFieldToFilter('type', 'category')
                ->addStoreFilter($store_id);

        $menuIds = array();
        foreach ($existsCategory as $menu) {
            $existsIds[] = $menu->getItem();
            $menuIds[ $menu->getItem() ] = $menu->getId();
        }

        $collection = Mage::getModel('catalog/category')
                ->getCollection()
                ->setOrder('level', 'ASC');
        try {
            foreach ($collection as $category) {
                if (!in_array($category->getId(), $existsIds)){
                    $this->_importCategory($category);
                }
                else
                    $this->_updateStore($menuIds[$category->getId()]);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('adminhtml/session')->addError('Could not import categories.');
            return false;
        }
    }


    public function deleteCategoriesAction(){
        $store_id = $this->getRequest()->getParam('store_id');
        $existsCategory = Mage::getModel('ves_verticalmenu/verticalmenu')
                ->getCollection()
                ->addFieldToFilter('type', 'category');
        if($store_id){
            $existsCategory =  $existsCategory->addStoreFilter($store_id);
        }

        foreach ($existsCategory as $menu) {
            if((int)$menu->getLevel() >=0 ) {
                $menu->delete();
            }
        }
    }

    public function deleteAllAction(){
        $store_id = $this->getRequest()->getParam('store_id');
        $menus = Mage::getModel('ves_verticalmenu/verticalmenu')
                ->getCollection();
        if($store_id){
            $menus =  $menus->addStoreFilter($store_id);
        }

        foreach ($menus as $menu) {
            $menu->delete();
        }
    }
    public function importMenusAction() {
        $result = array();
        $store_id = $this->getRequest()->getParam('store_id');
        $current_store_id = $this->getRequest()->getParam('current_store_id');
        if($store_id != $current_store_id) {

            $importMenus = Mage::getModel('ves_verticalmenu/verticalmenu')
                ->getCollection();

            //if($store_id){
            $importMenus =  $importMenus->addStoreFilter($store_id);
            //}
            $importMenus->addOrder('level','asc');

            $store_name = "";

            if($current_store_id) {
                $store_info = Mage::getModel('core/store')->load($current_store_id);
                $store_name = " (".$store_info->getName().")";
            }
            $parent_id = 1;
            $arr_parent_alias = array();
            $arr_menuitems_alias = array();

            foreach ($importMenus as $menu) {
                if($menu->getId() != 1) { /*Dont clone root menu item*/
                    $name = $menu->getTitle();

                    if($name == "" && 1== $menu->getLevel())
                        continue;
                    
                    if($menu->getParentId() == 1) {
                        $name = $name.$store_name;
                    }
                    if($menu->getParentId() > 1) {
                        $parent_id = isset($arr_parent_alias[$menu->getParentId()])?(int)$arr_parent_alias[$menu->getParentId()]:1;
                    }
                    $verticalmenu = Mage::getModel('ves_verticalmenu/verticalmenu')->load(null);
                    $data = array("title" => $name,
                                  "description" => $menu->getDescription(),
                                  "level" => $menu->getLevel(),
                                  "show_title" => $menu->getShowTitle(),
                                  "published" => $menu->getPublished(),
                                  "is_group" => $menu->getIsGroup(),
                                  "image" => $menu->getImage(),
                                  "widget_id" => $menu->getWidgetId(),
                                  "menu_icon_class" => $menu->getMenuIconClass(),
                                  "type_submenu" => $menu->getTypeSubmenu(),
                                  "type" => $menu->getType(),
                                  "left" => $menu->getLeft(),
                                  "right" => $menu->getRight(),
                                  "item" => $menu->getItem(),
                                  "parent_id" => $parent_id,
                                  "target" => $menu->getTarget(),
                                  "store_id" => $current_store_id,
                                  "stores" => array($current_store_id));
                    
                    $data = $menu->getData();
                    $data['title'] = $name;
                    $data['store_id'] = $current_store_id;
                    $data['stores'] = array($current_store_id);
                    $data['parent_id'] = $parent_id;
                    
                    if(isset($data['verticalmenu_id'])) {
                        unset($data['verticalmenu_id']);
                    }

                    $verticalmenu->setData($data);

                    try {
                        $verticalmenu->save();
                        if($data['level'] < 0){
                            $verticalmenu->delete();
                        }

                        if($menu->getParentId() >= 1 ) {
                            $arr_parent_alias[$menu->getId()] = $verticalmenu->getId();
                        }

                        $arr_menuitems_alias[$menu->getId()] = $verticalmenu->getId();

                    } catch (Exception $e) {
                        $result = array('error'=>$e->getMessage(), 'errorcode'=>$e->getCode());
                        Mage::throwException($e);
                    }
                }
            }

            if(!$result) {
                /*For each config field group to store data for fields*/
                $params = "";
                $title = "";

                if($store_id) {
                    $params = Mage::getStoreConfig('ves_verticalmenu/ves_verticalmenu/params', $store_id);
                    $title = Mage::getStoreConfig('ves_verticalmenu/ves_verticalmenu/title', $store_id);
                } else {
                    $params = Mage::getStoreConfig('ves_verticalmenu/ves_verticalmenu/params');
                    $title = Mage::getStoreConfig('ves_verticalmenu/ves_verticalmenu/title');
                }

                $array_params = Zend_Json::decode($params);
                $tmp_params = "";
                if($array_params) {
                    foreach($array_params as $key=>$item) {
                        $tmp = $item;
                        $alias_menu_id = isset($arr_menuitems_alias[$item['id']])?(int)$arr_menuitems_alias[$item['id']]:0;
                        if($alias_menu_id) {
                            $tmp['id'] = $alias_menu_id;
                            $tmp_params[] = $tmp;
                        }
                    }
                }

                $params = Zend_Json::encode($tmp_params);

                if($current_store_id) {
                    Mage::getConfig()->saveConfig('ves_verticalmenu/ves_verticalmenu/params', $params, 'stores', $current_store_id );
                    Mage::getConfig()->saveConfig('ves_verticalmenu/ves_verticalmenu/title', $title, 'stores', $current_store_id );
                } else {
                    Mage::getConfig()->saveConfig('ves_verticalmenu/ves_verticalmenu/params', $params );
                    Mage::getConfig()->saveConfig('ves_verticalmenu/ves_verticalmenu/title', $title );
                }
                        
                       
            }

            $result = array('success'=>Mage::helper("ves_verticalmenu")->__("Imported Menus Successfully!"));


        } else {
            $result = array('error'=>Mage::helper("ves_verticalmenu")->__("You can not import menus in same store view!"));
        }

        $this->getResponse()->setBody(Zend_Json::encode($result));
        
    }
    /*
    * Update position of menu
    */
    public function updateAction(){
        $data =  $this->getRequest()->getPost();
        $store_id = !isset($data['store_switcher'])?0:$data['store_switcher'];
        $dataString = isset($data['sortable'])?$data['sortable']:"";
        $data = Mage::helper("ves_verticalmenu")->stringtoURL($dataString);

        $root = $this->getRequest()->getParam('root');
        $child = array();
        foreach( $data as $id => $parentId ){
            if( $parentId <=0 ){
                $parentId = $root;
            }
            $child[$parentId][] = $id;
        }
        
        foreach( $child as $parentId => $menus ){
            $i = 1;
            foreach( $menus as $menuId ){
                $model = Mage::getModel('ves_verticalmenu/verticalmenu')->load($menuId);
                $model->setParentId((int)$parentId)
                        ->setPosition($i);

                $model->save();
                $i++;
            }
        }

       
        //Populate Resultarray
        $result = array("count"=>$i);

        Mage::getSingleton('adminhtml/session')
                    ->addSuccess(Mage::helper('adminhtml')
                    ->__('<strong>'.$i.'</strong> item(s) was stored successfully!'));
        if($store_id) {
            $this->_redirect('*/*/index', array('store_id' => $store_id));
        } else {
           $this->_redirect('*/*/index');  
        }
       
    }
    /**
     * save data menu
     */
    public function saveAction() {
        $result = array();
        if ($data = $this->getRequest()->getPost()) {
            $data['store_id'] = !isset($data['store_switcher'])?0:$data['store_switcher'];
            $store_id = $data['store_id'];
            $verticalmenu = isset($data['verticalmenu'])?$data['verticalmenu']:array();
            if(!$verticalmenu) {
                if($store_id) {
                   $result['redirect'] = $this->getUrl('*/*/index', array('store_id' => $store_id));
                } else {
                    $result['redirect'] = $this->getUrl('*/*/index');
                }
                Mage::getSingleton('adminhtml/session')
                    ->addWarning(Mage::helper('ves_verticalmenu')
                    ->__('Please input menu title and choose other menu parent (not ROOT)'));
                $this->getResponse()->setBody(
                        '<script type="text/javascript">parent.window.location.href = "' . $result['redirect'] . '";</script>'
                );
                return;
            }
            $check_exist = false;
            if(!$this->getRequest()->getParam('id') && (!isset($verticalmenu['title']) || !$verticalmenu['title']) && (!$verticalmenu['parent_id'] || $verticalmenu['parent_id'] == 1)) {
                $check_exist = Mage::getModel('ves_verticalmenu/verticalmenu')->checkExistMenuRoot(1, $store_id);
            }

            if($check_exist) {
               if($store_id) {
                   $result['redirect'] = $this->getUrl('*/*/index', array('store_id' => $store_id));
                } else {
                    $result['redirect'] = $this->getUrl('*/*/index');
                }
                Mage::getSingleton('adminhtml/session')
                    ->addWarning(Mage::helper('ves_verticalmenu')
                    ->__('Please input menu title and choose other menu parent (not ROOT)'));
                $this->getResponse()->setBody(
                        '<script type="text/javascript">parent.window.location.href = "' . $result['redirect'] . '";</script>'
                );
                return;
            }
              

            $save_mode = $this->getRequest()->getParam('save_mode');
            $id = 0;
            if ($id = $this->getRequest()->getParam('id')) {
                $model = Mage::getModel('ves_verticalmenu/verticalmenu')->load($id);
            }else{
                $model = Mage::getModel('ves_verticalmenu/verticalmenu')->load(null);
            }
            $image = $model->getImage();

            if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
                try {
                    $uploader = new Varien_File_Uploader('image');
                    $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png'));
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(true);
                    $path = Mage::getBaseDir('media') . DS . 'ves_verticalmenu';
                    $uploader->save($path, $_FILES['image']['name']);
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    return;
                }
                $image = 'ves_verticalmenu' . $uploader->getUploadedFileName();
            }  
     
            if (isset($data['image']['delete']) && $data['image']['delete'] == 1) {
                $path = Mage::getBaseDir('media') . DS . $image;
                if( $image && file_exists($path) ){
                    @unlink($path);
                }
                $image = '';
            }

            $data['verticalmenu']['image'] = $image;

            switch ($data['verticalmenu']['type']) {
                case 'category':
                    $data['verticalmenu']['item'] = isset($data['category'])?$data['category']:0;
                    break;
                case 'cms_page':
                    $data['verticalmenu']['item'] = isset($data['cms_page'])?$data['cms_page']:0;
                    break;
                case 'product':
                    $data['verticalmenu']['item'] = isset($data['verticalmenu']['product'])?$data['verticalmenu']['product']:0;
                    break;
                case 'static_block':
                    $data['verticalmenu']['item'] = isset($data['static_block'])?$data['static_block']:0;
                    break;
            }

            if( !isset($data['verticalmenu']['parent_id']) || empty($data['verticalmenu']['parent_id']) ){
                $data['verticalmenu']['parent_id'] = 1;
            }
            if ($data['verticalmenu']['parent_id']) {
                $level = Mage::getModel('ves_verticalmenu/verticalmenu')->load($data['verticalmenu']['parent_id'])->getLevel();
                $level += 1;
                $data['verticalmenu']['level'] = $level;
            } else {
                $root = Mage::getModel('ves_verticalmenu/verticalmenu')
                        ->getCollection()
                        ->addFieldToFilter('parent_id', 1)
                        ->addStoreFilter($data['store_id']);
                if (count($root)) {
                    $firstitem = $root->getFirstItem();
                    if ($firstitem->getId() != $id) {
                        Mage::getSingleton('adminhtml/session')->addError('Only one root Menu on Store');
                        if($save_mode == "save-edit"){
                            $result['redirect'] = $this->getUrl('*/*/edit', array('id' => $this->getRequest()->getParam('id'), 'store_id' => $store_id));
                            $this->getResponse()->setBody(
                                    '<script type="text/javascript">parent.window.location.href = "' . $result['redirect'] . '";</script>'
                            );  
                        }else{
                            $result['redirect'] = $this->getUrl('*/*/index', array('store_id' => $store_id));
                            $this->getResponse()->setBody(
                                    '<script type="text/javascript">parent.window.location.href = "' . $result['redirect'] . '";</script>'
                            );
                        }
                        
                        return;
                    }
                }
                $data['verticalmenu']['level'] = 0;
            }
            $data['verticalmenu']['stores'] = !empty($data['store_id'])?array($data['store_id']):array(0);
            $data['verticalmenu']['position'] = isset($data['verticalmenu']['position'])?$data['verticalmenu']['position']:0;
            $data['verticalmenu']['target'] = isset($data['megamenu']['target'])?$data['megamenu']['target']:"";

            $data_verticalmenu = array("title" => $data['verticalmenu']['title'],
                      "description" => $data['verticalmenu']['description'],
                      "level" => $data['verticalmenu']['level'],
                      "show_title" => $data['verticalmenu']['show_title'],
                      "published" => $data['verticalmenu']['published'],
                      "is_group" => $data['verticalmenu']['is_group'],
                      "image" => $data['verticalmenu']['image'],
                      "widget_id" => $data['verticalmenu']['widget_id'],
                      "submenu_content" => $data['verticalmenu']['submenu_content'],
                      "submenu_colum_width" => $data['verticalmenu']['submenu_colum_width'],
                      "colums" => $data['verticalmenu']['colums'],
                      "menu_class" => $data['verticalmenu']['menu_class'],
                      "menu_icon_class" => isset($data['verticalmenu']['menu_icon_class'])?$data['verticalmenu']['menu_icon_class']:"",
                      "type_submenu" => $data['verticalmenu']['type_submenu'],
                      "content_text" => $data['verticalmenu']['content_text'],
                      "type" => $data['verticalmenu']['type'],
                      "item" => $data['verticalmenu']['item'],
                      "url" => $data['verticalmenu']['url'],
                      "width" => $data['verticalmenu']['width'],
                      "position" => $data['verticalmenu']['position'],
                      "parent_id" => $data['verticalmenu']['parent_id'],
                      "store_id" => $data['store_id'],
                      "target" => $data['megamenu']['target'],
                      "stores" => $data['verticalmenu']['stores']);
            
            $model->setData($data_verticalmenu);

            if ($this->getRequest()->getParam('id'))
                $model->setId($this->getRequest()->getParam('id'));

            try {
                $model->save();
                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(Mage::helper('ves_verticalmenu')
                    ->__('verticalmenu was successfully saved'));

                if($save_mode == "save-edit"){
                    $result['redirect'] = $this->getUrl('*/*/edit', array('id' => $model->getId(), 'store_id' => $store_id));

                }else{
                    $result['redirect'] = $this->getUrl('*/*/index', array('store_id' => $store_id));
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $result['redirect'] = $this->getUrl('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                if($save_mode == "save-edit"){
                    $result['redirect'] = $this->getUrl('*/*/edit', array('id' => $model->getId(), 'store_id' => $store_id));
                }else{
                    $result['redirect'] = $this->getUrl('*/*/index', array('store_id' => $store_id));
                }
            }
        } else {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('ves_verticalmenu')
                ->__('Could not find anyverticalmenu to save'));
            if($save_mode == "save-edit"){
                $result['redirect'] = $this->getUrl('*/*/edit', array('id' => $model->getId(), 'store_id' => $store_id));
            }else{
                $result['redirect'] = $this->getUrl('*/*/index', array( 'store_id' => $store_id));
            }
        }
        
        $this->getResponse()->setBody(
                '<script type="text/javascript">parent.window.location.href = "' . $result['redirect'] . '";</script>'
        );
    }

    /**
     * Delete menu item and all of submenu if have
     */
    public function deleteAction($menuid = null) {
        $menuid = $menuid === null ? $this->getRequest()->getParam('id') : $menuid;
        $store_id = $this->getRequest()->getParam('store_id');
        $store_id = !empty($store_id)?$store_id:0;
        if ($menuid) {
            try {

                $model = Mage::getModel('ves_verticalmenu/verticalmenu');
                $model = $model->load($menuid);
                $test = $model->hasChild();
                $title = $model->getTitle();
                
                if ($model->hasChild()) {
                    $childs = $model->getChildItem();
                    foreach ($childs as $child) {
                        $this->deleteAction($child->getId());
                    }
                } else {
                    $model->delete();
                }
                $model->delete();

                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(Mage::helper('adminhtml')
                    ->__('Item was deleted successfully'));
                if($store_id) {
                   $this->_redirect('*/*/', array('store_id' => $store_id)); 
                } else {
                   $this->_redirect('*/*/');
                }
               
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                if($store_id) {
                   $this->_redirect('*/*/edit', array('id' => $menuid, 'store_id' => $store_id));
                } else {
                   $this->_redirect('*/*/edit', array('id' => $menuid));
                }
            }
        }
        if($store_id) {
           $this->_redirect('*/*/', array('store_id' => $store_id)); 
        } else {
           $this->_redirect('*/*/');
        }
    }

    /**
     * Live Edit Mega Menu Action
     */
    public function liveeditAction(){
        $position_type = $this->getRequest()->getParam('position_type');
        Mage::getSingleton('admin/session')
                ->setMegaPositionType($position_type);

        $this->_initAction();
       
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
        if ($head = $this->getLayout()->getBlock('head')) {
            $head->addItem('js', 'prototype/window.js')
                ->addItem('js_css', 'prototype/windows/themes/default.css')
                ->addCss('lib/prototype/windows/themes/magento.css')
                ->addItem('js', 'mage/adminhtml/variables.js')
                ->addItem('js', 'mage/adminhtml/wysiwyg/widget.js')
                ->addItem('js', 'lib/flex.js')
                ->addItem('js', 'lib/FABridge.js')
                ->addItem('js', 'mage/adminhtml/flexuploader.js')
                ->addItem('js', 'mage/adminhtml/browser.js')
                ;
        }

        $this->_addContent($this->getLayout()->createBlock('ves_verticalmenu/adminhtml_verticalmenu_liveedit'));
        $this->renderLayout();

        return;
    }
 

    /**
     *  Ajax Live Save Action.
     */
    public function livesaveAction(){
        $this->_forward('ajxgenmenu');
    }

    /**
     * Ajax Render List Tree Mega Menu Action
     */
    public function ajxgenmenuAction( ){ 
        $parent                 = '1';
        $store_id = $this->getRequest()->getParam('store_id');
        $store_id = !empty($store_id)?$store_id:0;

         /* unset mega menu configuration */
        $action_reset = $this->getRequest()->getParam("reset");
        if( $action_reset == "1" ){
           if($store_id) {
             Mage::getConfig()->saveConfig('ves_verticalmenu/ves_verticalmenu/params', "", 'stores', $store_id );
           } else {
             Mage::getConfig()->saveConfig('ves_verticalmenu/ves_verticalmenu/params', "" );
           }
          
           Mage::getConfig()->reinit();
           Mage::app()->reinitStores();
        }
        $ajaxmenu_block = $this->getLayout()->createBlock('ves_verticalmenu/adminhtml_verticalmenu_ajaxgenmenu');

        $this->getResponse()->setBody( $ajaxmenu_block->toHtml() );

        return;
    }
    /**
     * Ajax Menu Information Action
     */
    public function ajxmenuinfoAction(){
        $params = $this->getRequest()->getParam('params');
        if( $params ) {
           $store_id = $this->getRequest()->getParam('store_id');
           $store_id = !empty($store_id)?$store_id:0;
           $params = trim(html_entity_decode($params));

           if($store_id) {
                Mage::getConfig()->saveConfig('ves_verticalmenu/ves_verticalmenu/params', $params, 'stores', $store_id );
           } else {
                Mage::getConfig()->saveConfig('ves_verticalmenu/ves_verticalmenu/params', $params );
           }
           Mage::getConfig()->reinit();
           Mage::app()->reinitStores();
        }

        return $this->ajxgenmenuAction();
        
    }
    public function renderwidgetAction(){
         /* unset mega menu configuration */
        $widget_html = Mage::helper("ves_verticalmenu")->renderwidget();
        if($widget_html){
            $this->getResponse()->setBody( $widget_html );
        }else{
            exit();
        }
        return;
    }

    public function importJsonAction() {
         // get uploaded file
        $filepath = Mage::helper("ves_verticalmenu")->getUploadedFile();
       
        if ($filepath != null) {
            try {
                // import into model
                $file_content = "";
                if(file_exists($filepath)) {
                    $file_content = file_get_contents($filepath);
                }
                if($file_content) {
                    Mage::helper("ves_verticalmenu")->importSample($file_content, "ves_verticalmenu");
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('cms')->__('Sample Data Was Imported Successfully'));
                } else {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('cms')->__('An Error occured importing Sample Data. Sample data is NULL.'));
                }
                

            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('cms')->__('An Error occured importing Sample Data.'));
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } // end if
        }


        // redirect to grid page.
        $this->_redirect('*/*/index');
    }

    public function uploadJsonAction() {
        $this->loadLayout();

        $block = $this->getLayout()->createBlock('ves_verticalmenu/adminhtml_verticalmenu_upload');
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }
    /**
     * Exports a JSON file
     */
    public function exportsampleAction() {
        $type = $this->getRequest()->getParam('type');

        $fileName = "";
        $content = "";
        switch ($type) {
            case 'csv':
                $fileName = "ves_verticalmenu_sample_data.csv";
                $content    = Mage::helper("ves_verticalmenu")->exportSample("csv");
                break;
            case 'json':
            default:
                $fileName = "ves_verticalmenu_sample_data.json";
                $content    = Mage::helper("ves_verticalmenu")->exportSample("json");
                break;
        }

        $this->_prepareDownloadResponse($fileName, $content);

    }
    /**
     * Check the permission to run it
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        $action = strtolower($this->getRequest()->getActionName());

        switch ($action) {
            case 'new':
            case 'add':
            case 'edit':
                return Mage::getSingleton('admin/session')->isAllowed('vesextensions/ves_verticalmenu/index');
                break;
            case 'update':
            case 'save':
                return Mage::getSingleton('admin/session')->isAllowed('vesextensions/ves_verticalmenu/save');
                break;
            case 'savewidget':
                return Mage::getSingleton('admin/session')->isAllowed('vesextensions/ves_verticalmenu/savewidget');
                break;
            case 'liveedit':
                return Mage::getSingleton('admin/session')->isAllowed('vesextensions/ves_verticalmenu/liveedit');
                break;
            case 'importCategories':
                return Mage::getSingleton('admin/session')->isAllowed('vesextensions/ves_verticalmenu/importcategories');
                break;
            case 'importMenus':
                return Mage::getSingleton('admin/session')->isAllowed('vesextensions/ves_verticalmenu/importmenus');
                break;
            case 'delete':
                return Mage::getSingleton('admin/session')->isAllowed('vesextensions/ves_verticalmenu/delete');
                break;
            default:
                return Mage::getSingleton('admin/session')->isAllowed('vesextensions/ves_verticalmenu');
                break;
        }
    }
}