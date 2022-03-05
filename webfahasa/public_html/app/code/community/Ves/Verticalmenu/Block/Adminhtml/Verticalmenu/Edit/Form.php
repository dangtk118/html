<?php
/******************************************************
 * @package Ves Megamenu module for Magento 1.4.x.x and Magento 1.7.x.x
 * @version 1.0.0.1
 * @author http://landofcoder.com
 * @copyright   Copyright (C) December 2010 LandOfCoder.com <@emai:landofcoder@gmail.com>.All rights reserved.
 * @license     GNU General Public License version 2
*******************************************************/
?>
<?php
class Ves_Verticalmenu_Block_Adminhtml_Verticalmenu_Edit_Form extends Ves_Verticalmenu_Block_Adminhtml_Verticalmenu_Tree
{
  protected $_additionalButtons = array();
    
    protected $itemTypes = array();
    protected $types = array();
    public function __construct()
    {
        $this->types = array(
            'category'      => array('model'=>'catalog/category','name'=>"Category",'field'=>'name'),
            'product'       =>  array('model'=>'product','name'=>"Product",'field'=>'name'),
            'cms_page'      =>  array('model'=>'cms/page','name'=>"CMS Page",'field'=>'title'),
            'static_block'  =>  array('model'=>'cms/block','name'=>"Static Block",'field'=>'title')
        );
        
        foreach( $this->types as $type => $m ){
            $data = new Varien_Object();
            $data->setTitle($m['field'])
                ->setName($type)
                    ->setModel($m['model'])
                        ->setTitleField($m['name']);
            $this->itemTypes[$type] = $data;
        }
        
//   echo '<pre>'.print_r($this->articleTypes,1); die;
        parent::__construct();
        $this->setTemplate('ves_verticalmenu/edit/form.phtml');
        
    }
    
    function getFieldTitle( $type){
        return $this->types[$type]['field'];
    }

    public function getVerticalmenu() {
      return Mage::registry('current_verticalmenu');
    }
    
    public function getMenuTypes(){
        return array(
            'url' => 'URL',
            'category' => 'Category',
            'cms_page' => 'CMS Page',
            'product' => 'Product',
            'ves_blog' => 'Ves Blog',
            'ves_deal' => 'Ves Deals',
            'ves_brand' => 'Ves Brands',
            'html'  => "HTML"
        );
    }
    function getGroups(){
        $customer_group = new Mage_Customer_Model_Group();
        $allGroups  = $customer_group->getCollection()->toOptionHash();
        $customerGroup[99]=array('value'=>"99",'label'=>"User Logined");
        foreach($allGroups as $key=>$allGroup){
              $customerGroup[$key]=array('value'=>$key,'label'=>$allGroup);
        }

        return $customerGroup;
    }
     public function getWidgets(){
        $widgets = Mage::getModel('ves_verticalmenu/widget')->getCollection();
        $store_id = $this->getRequest()->getParam('store_id');

        if($store_id){
            $widgets->addFieldToFilter('store_id', $store_id);
        }
        return $widgets;
        
    }
    protected function _prepareLayout()
    {
        $verticalmenu = $this->getVerticalmenu();
        $verticalmenuId = (int) $verticalmenu->getId();

            // Save button
        $this->setChild('save_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label'     => Mage::helper('catalog')->__('Save Verticalmenu'),
                'onclick'   => "verticalmenuForm.submit('" . $this->getUrl('*/*/save', array('id' => $verticalmenuId)) . "', true)",
                'class' => 'save'
            ))
        );

            // Delete button
        $this->setChild('delete_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label'     => Mage::helper('catalog')->__('Delete Verticalmenu'),
                'onclick'   => "verticalmenuDelete('" . $this->getUrl('*/*/delete') . "', {$verticalmenuId})",
                'class' => 'delete'
            ))
        );

            // Reset button
        $resetPath = $verticalmenuId ? '*/*/edit' : '*/*/add';
        $this->setChild('reset_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label'     => Mage::helper('catalog')->__('Reset'),
                'onclick'   => "categoryReset('".$this->getUrl($resetPath, array('_current'=>true))."',true)"
            ))
        );

        return parent::_prepareLayout();
    }
    
  
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_button');
    }

    public function getSaveButtonHtml()
    {
      return $this->getChildHtml('save_button');
    }

    public function getResetButtonHtml()
    {
      return $this->getChildHtml('reset_button');
    }

  
    public function getAdditionalButtonsHtml()
    {
        $html = '';
        foreach ($this->_additionalButtons as $childName) {
            $html .= $this->getChildHtml($childName);
        }
        return $html;
    }

 
    public function addAdditionalButton($alias, $config)
    {
        if (isset($config['name'])) {
            $config['element_name'] = $config['name'];
        }
        $this->setChild($alias . '_button',
                        $this->getLayout()->createBlock('adminhtml/widget_button')->addData($config));
        $this->_additionalButtons[$alias] = $alias . '_button';
        return $this;
    }
 
    
    public function getHeader()  {
        if ($this->getVerticalmenu()->getId()) {
            return $this->getVerticalmenu()->getTitle(). ' (ID : ' .$this->getVerticalmenu()->getId(). ')';
        } else {
          
        $parentId = (int) $this->getRequest()->getParam('parent');
      
          if ($parentId) {
                $verticalmenu = Mage::getModel('ves_verticalmenu/verticalmenu')->load($parentId);
              $output = Mage::helper('catalog')->__('Create A Sub Verticalmenu In %s', $verticalmenu->getTitle()." ( ID: ".$verticalmenu->getId() . " )" ) ;        
              return $output;
          } else {
              return Mage::helper('catalog')->__('Create A Root Verticalmenu');
          }
      }
    }

    public function getDeleteUrl(array $args = array())
    {
        $params = array('_current'=>true);
        $params = array_merge($params, $args);
        return $this->getUrl('*/*/delete', $params);
    }
    
    public function getSaveUrl()
    {
      return $this->getUrl('*/*/save', array('id' => $this->getVerticalmenu()->getId()));
    }
    
    public function getItemHtml($type, $disabled) {
        $html = '';
        if($type) {
            if($this->getVerticalmenu()->getId() && $type == $this->getVerticalmenu()->getType()) {
                $html .= $this->nodeToSelectHtml($type, $this->getVerticalmenu()->getItem(), $disabled);
            } else {
                $html .= $this->nodeToSelectHtml($type, 0, $disabled);
            }
        }
        return $html;
    }

    public function getTreecategoriesHtml($type, $disabled) {
        $html = '';
        if($type) {
            if($this->getVerticalmenu()->getId() && $type == $this->getVerticalmenu()->getType()) {
                $html .= $this->nodeToTreeHtml($type, $this->getVerticalmenu()->getItem(), $disabled);
            } else {
                $html .= $this->nodeToTreeHtml($type, 0, $disabled);
            }
        }
        return $html;
    }

    public function removeAdditionalButton($alias)
    {
        if (isset($this->_additionalButtons[$alias])) {
            $this->unsetChild($this->_additionalButtons[$alias]);
            unset($this->_additionalButtons[$alias]);
        }

        return $this;
    }
    
    public function getStoreConfigurationUrl()
    {
        $storeId = (int) $this->getRequest()->getParam('store');
        $params = array();
        if ($storeId) {
            $store = Mage::app()->getStore($storeId);
            $params['website'] = $store->getWebsite()->getCode();
            $params['store']   = $store->getCode();
        }
        return $this->getUrl('*/system_store', $params);
    }
    public function nodeToTreeHtml($name, $select = 0, $disabled = false, $multiple = false) {
        $html = '';
        $nodeData = $this->itemTypes[$name];
        /*Get Root Category Id*/
        $root_parent_id = 1;
        $root_parent_collection = Mage::getModel('catalog/category')->getCollection()
                    ->addAttributeToSelect('*')
                    ->addFieldToFilter('is_active', array('eq'=>'1'))
                    //->addAttributeToSort('level', '0')
                    ->addAttributeToFilter('parent_id',array('eq' => "0"));
        
        if(0 < $root_parent_collection->getSize()) {
            $root_parent_id = $root_parent_collection->getFirstItem()->getId();
        }

        $collection = Mage::helper("ves_verticalmenu/treecategories")->getTreeCategories($root_parent_id, 0);//get all categories as tree 
        
        $multiple_html = "";
        if($multiple){
            $multiple_html = ' multiple="multiple" size="10" ';
        }
        $html .= '<select name="'.$name.'" id="verticalmenu['.$name.']" class="input-text required-entry"'.$multiple_html;

        $html .= '>';
        $html .= '<option value="">'.$this->__('--------------------------------').'</option>';
        foreach($collection as $option) {
            if($select) {
                $selectOption = '';
                if($select == $option['value'])
                    $selectOption = 'selected="selected"';
                $html .= '<option value="'.$option['value'].'" '.$selectOption.'>'.$option['label'].'</option>';
            } else {
                $html .= '<option value="'.$option['value'].'">'.$option['label'].'</option>';
            }
        }
        $html .= '</select>';
        return $html;
    }

     public function nodeToSelectHtml($name, $select = 0, $disabled = false, $multiple = false) {
        $html = '';
        $nodeData = $this->itemTypes[$name];
        $collection = Mage::getModel($nodeData->getModel())->getCollection();
        
        $field = $this->getFieldTitle( $name );
        
        $multiple_html = "";
        if($multiple){
            $multiple_html = ' multiple="multiple" size="10" ';
        }
        $html .= '<select name="'.$name.'" id="verticalmenu['.$name.']" class="input-text required-entry"'.$multiple_html;

        $html .= '>';
        $html .= '<option value="">'.$this->__('--------------------------------').'</option>';
        foreach($collection as $option) {
            $type = Mage::getModel($nodeData->getModel())->load($option->getId());
            if($type->getData( $field )) {
                if($select) {
                    $selectOption = '';
                    if($option->getId() == $select)
                        $selectOption = 'selected="selected"';
                    $html .= '<option value="'.$option->getId().'" '.$selectOption.'>'.$type->getData( $field ).'</option>';
                } else {
                    $html .= '<option value="'.$option->getId().'">'.$type->getData( $field ).'</option>';
                }
            }
        }
        $html .= '</select>';
        return $html;
    }
    
    public function nodeToSelectHtml2($name, $select = 0, $disabled = false, $multiple = false) {
       $html = '';
        $nodeData = $this->articleTypes[$name];
        $collection = Mage::getModel($nodeData->getModel())->getCollection();
        $field = $this->getFieldTitle( $name );
        $select = is_array($select)?$select:array($select);
        $collection = Mage::getModel($nodeData->getModel())->getCollection();
        $multiple_html = "";
        $field_name = "verticalmenu[submenu_content]";
        if($multiple){
            $multiple_html = ' multiple="multiple" size="10" ';
            $field_name = "verticalmenu[submenu_content][]";
        }
        $html .= '<select name="'.$field_name.'" id="verticalmenu[submenu_content]" class="input-text"'.$multiple_html;
        if($disabled)
            $html .= 'disabled="disabled"';
        $html .= '>';
        $html .= '<option value="">'.$this->__('--------------------------------').'</option>';
        foreach($collection as $option) {
            $type = Mage::getModel($nodeData->getModel())->load($option->getId());
            if($type->getData($field)) {
                if($select) {
                    $selectOption = '';
                    if( in_array($option->getId(), $select) )
                        $selectOption = 'selected="selected"';
                    $html .= '<option value="'.$option->getId().'" '.$selectOption.'>'.$type->getData($field).'</option>';
                } else {
                    $html .= '<option value="'.$option->getId().'">'.$type->getData($field).'</option>';
                }
            }
        }
        $html .= '</select>';
        return $html;
    }
     
    public function getMenus( $id ){
        $collection = Mage::getModel("ves_verticalmenu/verticalmenu")->renderDropdownMenu(null, 0, $id);
        
        $select  = '<select name="verticalmenu[parent_id]">';
            $select .=$collection;
        $select .= '</select>';
        
        return $select;
    }
    
    public function isAjax()
    {
        return Mage::app()->getRequest()->isXmlHttpRequest() || Mage::app()->getRequest()->getParam('isAjax');
    }
    
    public function getSubmenutypeHtml($type, $disabled, $multiple=false){
        $html = '';
        if($type) {
            if($this->getVerticalmenu()->getId()) {
                $submenucontent = $this->getVerticalmenu()->getSubmenuContent();
                $tmp = explode(":", $submenucontent);
                $article_str = isset($tmp[0])?$tmp[0]:"";
                $article_arr = explode(",", $article_str);
                $html .= $this->nodeToSelectHtml2($type, $article_arr, $disabled, $multiple);
            } else {
                $html .= $this->nodeToSelectHtml2($type, 0, $disabled, $multiple);
            }
        }
        return $html;
    }
    
}