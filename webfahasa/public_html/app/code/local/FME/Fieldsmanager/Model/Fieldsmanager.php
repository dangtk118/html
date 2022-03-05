<?php 
/*////////////////////////////////////////////////////////////////////////////////
 \\\\\\\\\\\\\\\\\\\\\\\\\  FME Fieldsmanager extension  \\\\\\\\\\\\\\\\\\\\\\\\\
 /////////////////////////////////////////////////////////////////////////////////
 \\\\\\\\\\\\\\\\\\\\\\\\\ NOTICE OF LICENSE\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 ///////                                                                   ///////
 \\\\\\\ This source file is subject to the Open Software License (OSL 3.0)\\\\\\\
 ///////   that is bundled with this package in the file LICENSE.txt.      ///////
 \\\\\\\   It is also available through the world-wide-web at this URL:    \\\\\\\
 ///////          http://opensource.org/licenses/osl-3.0.php               ///////
 \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 ///////                      * @category   FME                            ///////
 \\\\\\\                      * @package    FME_Fieldsmanager              \\\\\\\
 ///////    * @author     Malik Tahir Mehmood <malik.tahir786@gmail.com>   ///////
 \\\\\\\                                                                   \\\\\\\
 /////////////////////////////////////////////////////////////////////////////////
 \\* @copyright  Copyright 2010 � free-magentoextensions.com All right reserved\\\
 /////////////////////////////////////////////////////////////////////////////////
 */

class FME_Fieldsmanager_Model_Fieldsmanager extends Mage_Eav_Model_Entity_Attribute
{

    protected $_FM_EntityCode = 'fme_fieldsmanager';
    protected $_Customers = false;
    protected $_step = false;
    protected $_locate = false;
    protected $_section = false;
    public function getFieldsList($step,$locate, $addTable)
    {
	$this->_step = $step;$this->_locate=$locate;
	$collection = $this->getFieldsCollecton($this->_Customers);
	if($addTable=='catalog' && $this->_Customers==false){
	    $collection->getSelect()->where('add_table_data.is_searchable ='. $step . '');
	    $collection->getSelect()->where('add_table_data.is_filterable ='. $locate . '');
	    $collection->getSelect()->order('add_table_data.position ASC');   
	}
	$mainIds = array();
	foreach($collection->getData() as $mainone){
	    $mainIds[$mainone['attribute_id']]=$mainone['attribute_id'];
	}
	if($mainIds && !empty($mainIds)){
	    $prod_ids = $this->getQuoteProductIds();
	    $main = $collection->getData();$pids = array(); $cat = array();
	    foreach($main as $key=>$forunique){
		$pids = $this->getFieldsmanagerRelatedProducts($forunique['attribute_id']);
		if( count($pids) > 0 && count($prod_ids['products']) !=0){
		    $result = array_intersect($pids, $prod_ids['products']);
		    if(count($result) == 0){
			unset($main[$key]);
		    }
		}
		$cat = $this->getCategoryId($forunique['attribute_id']);
		if(count($cat)>0 && count($prod_ids['category']) !=0){
		       $result = array_intersect($cat, $prod_ids['category']);
		    if(count($result) == 0){
			unset($main[$key]);
		    }
		}
		if(in_array($forunique['attribute_id'],$mainIds)){
		    unset($mainIds[$forunique['attribute_id']]);
		}else{
		    unset($main[$key]);
		}
	    }
	    return $main;
	}
	return $collection->getData();  
    }
    public function SaveFieldsdata($key,$value){//Mage::registry('fm_checkoutdata')
        if(!Mage::getSingleton('core/session')->getRegistry()){
	    Mage::getSingleton('core/session')->setRegistry('');
	}
	$data=Mage::getSingleton('core/session')->getRegistry();
	$data[$key]=$value;
	Mage::getSingleton('core/session')->setRegistry($data);
	return;
    }
    public function GetFMDb($id , $table ,$_Write){
	$allentries = $_Write->select()
            ->from(Mage::helper('fieldsmanager')->getFMTable($table), '*')
            ->where('entity_id=?', $id)
	    ->order('attribute_id','ASC');
        return $_Write->fetchAll($allentries);
    }
    public function GetFMData($orderid , $table , $for = false){
	if(!Mage::helper('fieldsmanager')->getStoredDatafor('enable')){
	    return false;
	}
        if(!$orderid or $orderid<=0){
	   return false;
	}
	$_Write = Mage::getSingleton('core/resource')->getConnection('core_read');
        $AllData = $this->GetFMDb($orderid , $table ,$_Write);
	$arrayvalues = array();
	if(count($AllData)!=0){
	    $Info='';
		$i=0;
		foreach($AllData as $data){
		    $arrayvalues = array();
		    $attribute=Mage::getResourceModel('eav/entity_attribute_collection')->addFieldToFilter('attribute_id',$data['attribute_id'])->getFirstItem();
		     $value = Zend_Json::decode($data['value']);
		    if(is_array($value)){
			$values=array();
			if($table=='orders'){
			    foreach($value as $key => $onebyone){
				$values[] = implode(',',$onebyone);
				if(is_array($onebyone)){
				    foreach($onebyone as $key2 =>$val2){
					$arrayvalues[]=$key2;
				    }
				}else{
				    $arrayvalues[]=$key;
				}
			    }
			    if(is_array($values)){$value = implode(',',$values);}
			}else{
			    foreach($value as $key=>$val){
				if(is_array($val)){
				    foreach($val as $key2 =>$val2){
					$values[]=$key2;
				    }
				}else{
				    $values[]=$key;
				}
			    }
			    $value=$values;
			}
		    }
		    if($for == 1){
			$attribute=Mage::getResourceModel('eav/entity_attribute_collection')->addFieldToFilter('attribute_id',$data['attribute_id'])->getFirstItem();
			if($attribute['fme_email'] == true){
			     $info[$attribute['attribute_code']]=$value;
			}
		    }elseif($for == 21 || $for == 22){
			$attribute=Mage::getResourceModel('eav/entity_attribute_collection')->addFieldToFilter('attribute_id',$data['attribute_id'])->getFirstItem();
			if($attribute['fme_pdf'] !=0 && ("2".$attribute['fme_pdf'] == $for || $attribute['fme_pdf'] == 3)){
			    $info[$i] = $this->getFieldStoreLabel($attribute) . ": " . $value;
			    $i++;
			}
		    }else{
			$info[$i]['code']=$attribute['attribute_code'];
			$info[$i]['label']=$this->getFieldStoreLabel($attribute);
			$info[$i]['value']=$value;
			if($arrayvalues){
			    $info[$i]['keys']= $arrayvalues;
			}else{
			    $info[$i]['keys']= '';
			}
			$i++;
		    }
		}
	    return $info;
	}
    }
    public function getCustomer(){
	$session = Mage::getSingleton('customer/session');
	if($session->isLoggedIn()) {
	   return $session->getCustomer();
	}
	if($backendcustomer = Mage::registry('current_customer')){
	    return $backendcustomer;
	}
	return 0;
    }
    public function SaveINDb($Entervalue,$id,$table,$attributeInfo,$_Write){
	$Exists = array();unset($Exists);
	$tables   = Mage::helper('fieldsmanager')->getFMTable($table);
	    $allentries = $_Write->select()
	    ->from($tables, '*')
	    ->where('entity_id=?', $id)
	    ->where('attribute_id=?', $attributeInfo)
	    ->order('attribute_id','ASC');
	    $Exists = $_Write->fetchRow($allentries);
	if(!empty($Exists) && !empty($Exists['fieldsmanager_id']) && $Exists['value']!=$Entervalue){
	    $_Write->update( $tables,
		array("value" => $Entervalue),
		array("fieldsmanager_id =?" => $Exists['fieldsmanager_id'])
	    );
	}elseif(empty($Exists)) {
	    $_Write->insert( $tables ,
	    array(
		"entity_id"	=> $id,
		"attribute_id" 	=> $attributeInfo,
		"value" 	=> $Entervalue,
	    ));
	}
	return;
    }
    public function AdminOrderEditBefore($observer){
	$data = Mage::app()->getRequest()->getParam('order_id');
	Mage::getSingleton('core/session')->setParentOrderId($data);
    }
    public function AdminOrderBeforeSaveToFM($observer){
	$data = Mage::app()->getRequest()->getPost('fm_fields');
	foreach($data as $key=>$value){
          if(substr($key,0,3)=='fm_'){
               Mage::getModel('fieldsmanager/fieldsmanager')->SaveFieldsdata(substr($key,3),$value);
            }
        }
	if(Mage::getSingleton('core/session')->getParentOrderId()){
	    Mage::getSingleton('core/session')->unsParentOrderId();
	}
    }
    public function AdminOrderSaveToFM(){
	$id= Mage::app()->getRequest()->getParam('order_id');
	$this->SaveToFM('core',$id,'orders',0);
	Mage::getSingleton('core/session')->unsRegistry();
	return;
    }
    public function SuccessOrderBeforeSaveToFM($observer){
	$order = new Mage_Sales_Model_Order();
	$incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
	$order->loadByIncrementId($incrementId);
	$this->SaveToFM('core',$order->getEntityId(),'orders',0);
	Mage::getSingleton('core/session')->unsRegistry();
	return;
    }
    public function OrderBeforeSaveToFM($observer){
	$order = $observer->getEvent()->getOrder();
	$this->SaveToFM('core',$order->getEntityId(),'orders',0);
	Mage::getSingleton('core/session')->unsRegistry();
	return;
    }
    public function CustomerBeforeSaveToFM($event){
	$customer = $event->getEvent();
	$this->SaveToFM('customer',0,'customers',$customer->getCustomer()->getEntityId());
	return;
    }
    public function SaveToFM($mode,$id,$table,$cId){
	if(!Mage::helper('fieldsmanager')->getStoredDatafor('enable')){
	    return;
	}
        if(!Mage::getSingleton($mode.'/session')->getRegistry()){
	   return;
	}
	$data=Mage::getSingleton($mode.'/session')->getRegistry();
	$customerId=$cId;
	if($customerId == 0 && $this->getCustomer()){
	    $customerId=$this->getCustomer()->getId();
	}
	$_Write = Mage::getSingleton('core/resource')->getConnection('core_write');
	$collection = $this->getFieldsCollecton('1');
	$main = $collection->getData();
	$mainIds = array();
	if(count($main) > 0){
	    foreach($main as $mainone){
		$mainIds[$mainone['attribute_id']]=$mainone['attribute_id'];
	    }
	}
	if($mainIds && !empty($mainIds)){
	    foreach($main as $key=>$forunique){
		    if(in_array($forunique['attribute_id'],$mainIds)){
			unset($mainIds[$forunique['attribute_id']]);
		    }else{
			unset($main[$key]);
		    }
		}
	}
	 $original = array();
	foreach($main as $each){
	    $original[$each['attribute_code']]=$each['attribute_code'];
	    $original_ids[$each['attribute_code']]['id'] = $each['attribute_id'];
	    $original_ids[$each['attribute_code']]['type'] = $each['frontend_input'];
	}
	foreach($data as $key=>$value){
	    if(in_array($key,$original)){
		unset($original[$key]);
	    }
	    if(!$key){continue;}
	    $Entervalue=null;unset($Entervalue);
	    $attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')
                        ->setCodeFilter($key)
			->addFieldToFilter('entity_type_id',$this->getEntityTypeId())
                        ->getFirstItem();
	    if(in_array($attributeInfo->getFrontendInput(), array('select', 'multiselect','checkbox','radio')))
	    {
		if($attributeInfo->getFrontendInput()=='multiselect'){
		    if(count($value)==1){
			$values=array();
			$values=explode(',',$value[0]);
			$value=$values;
		    }
		}
		elseif(!is_array($value)){
		    $values=array();
		    $values=explode(',',$value);
		    $value=$values;
		}
		foreach($attributeInfo->getSource()->getAllOptions(false) as $option){
		    if(in_array($option['value'],$value)){
			$Entervalue[$attributeInfo->getAttributeCode()][$option['value']]=$option['label'];
		    }
		}
	    }
	    elseif($attributeInfo->getFrontendInput()=='boolean')
	    {
		if($value==0){
		    $Entervalue = Mage::helper('catalog')->__('No');
		}else{
		    $Entervalue = Mage::helper('catalog')->__('Yes');
		}
	    }
	    else
	    {
		$Entervalue = strip_tags($value);
	    }
	    $Entervalue=Zend_Json::encode($Entervalue);
	    if($mode == 'customer'){
		$this->SaveINDb($Entervalue,$customerId,'customers',$attributeInfo->getAttributeId() ,$_Write);
	    }
	    else{
		if($attributeInfo->getFmeCustomerAccount()>0 && $customerId!=0){
		    $this->SaveINDb($Entervalue,$customerId,'customers',$attributeInfo->getAttributeId() ,$_Write);
		}
		if(!empty($Entervalue) && $mode == 'core'){
		    $this->SaveINDb($Entervalue,$id,$table,$attributeInfo->getAttributeId() ,$_Write);
		}
	    }
	}
	if(count($original)!=0){
	    foreach($original as $orgvalue){
		$value="";
		if(in_array($original_ids[$orgvalue]['type'], array('select', 'multiselect','checkbox','radio')))
		{
		    $value[$orgvalue]="";
		}
		$Entervalue=Zend_Json::encode($value);
		 $this->SaveINDb($Entervalue,$customerId,'customers',$original_ids[$orgvalue]['id'] ,$_Write);
	    }
	}
	return;
    }
    public function getFieldsCollecton($fromcustomers = false){
        if(!Mage::helper('fieldsmanager')->getStoredDatafor('enable')){
	    return false;
	}
        $collection = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter($this->getEntityTypeId());
	   if($fromcustomers != false){
		if($fromcustomers == 2){
		    $collection->getSelect()->where('main_table.fme_customer_account ='. $fromcustomers . '');
		}else{
		    $collection->getSelect()->where('main_table.fme_customer_account >="1"');
		}
	} 
        $collection->getSelect()->join(
            array('add_table_data' =>Mage::helper('fieldsmanager')->getTable('catalog')),
            'add_table_data.attribute_id=main_table.attribute_id'
        );
	if($this->_step && !$fromcustomers){$collection->getSelect()->where('add_table_data.is_searchable ='. $this->_step . '');}
	if($this->_locate && !$fromcustomers){$collection->getSelect()->where('add_table_data.is_filterable ='. $this->_locate . '');}
	$CurrentStoreId = Mage::app()->getStore()->getId();
	 $collection->getSelect()->join(
            array('store_table' =>Mage::helper('fieldsmanager')->getFMTable('store')),
            'store_table.attribute_id=main_table.attribute_id'
        );
	if(!Mage::getSingleton('adminhtml/session')->getIsAdmin() || Mage::getSingleton('adminhtml/session')->getIsAdmin()!=true){
	    $collection->getSelect()->where('store_table.store_id ="'. $CurrentStoreId . '" || store_table.store_id ="0"');
	}
	if($this->_section == 'fme_register'){
	    return $collection;
	}
	$collection->getSelect()->join(
            array('customer_group' =>Mage::helper('fieldsmanager')->getFMTable('customer_group')),
            'customer_group.attribute_id=main_table.attribute_id'
        );
	
	$GroupId = 0;
	if($this->getCustomer()){
	   $GroupId = $this->getCustomer()->getGroupId();
	}
	if(Mage::getSingleton('adminhtml/session')->getIsNew() && Mage::getSingleton('adminhtml/session')->getIsNew() == true){
	    $GroupId = 0;
	}
	$collection->AddFieldToFilter('customer_group.group_id',$GroupId);
        return $collection;
    }
    public function getQuoteProductIds(){
	$product_ids = array();$category_ids = array();
	$quote = Mage::getSingleton('checkout/session')->getQuote();
	$items = $quote->getAllVisibleItems();
	foreach ($items as $item) {
	    $product_ids[] = $item->getProductId();
	    $item = Mage::getModel('catalog/product')->load($item->getProductId());
	    $categories =  $item->getCategoryIds();
	    if(is_array($categories)){
		foreach($categories as $category){
		    $category_ids[] = $category;
		}
	    }else{
		$category_ids[] = $categories;
	    }
	}
	$return['products']=array_unique($product_ids);
	$return['category']=array_unique($category_ids);
	return $return;
    }
    public function getEntityTypeId(){
	return Mage::getModel('eav/entity')
            ->setType($this->_FM_EntityCode)->getTypeId();
    }
    public function getFieldLabel($NewFieldId)
    {
        if (!$NewFieldId){ return false;}
	$NewFieldData  = Mage::getModel('eav/entity_attribute')->load($NewFieldId);
	return $this->getFieldStoreLabel($NewFieldData);
    }
    public function getFieldStoreLabel($NewFieldData)
    {
	if (!$NewFieldData->getData()) return false;
	$CurrentStoreId = Mage::app()->getStore()->getId();
	$AllStoreLabels=array();
	$AllStoreLabels = $NewFieldData->getStoreLabels();
	if(!empty($AllStoreLabels) AND isset($AllStoreLabels[$CurrentStoreId]) AND $AllStoreLabels[$CurrentStoreId])
	{
	    return $AllStoreLabels[$CurrentStoreId];
	}
	return $NewFieldData->getFrontend()->getLabel();
    }
    public function getAllFieldsHtml($step , $locate ,$section, $addTable , $CS ,$CE)
    {	if(!Mage::helper('fieldsmanager')->getStoredDatafor('enable')){
	    return false;
	}
	$this->_section = $section;
	if($section == 'fme_register' || $section == 'fme_account'){
	    $this->_Customers=1;
	    if($section == 'fme_register'){
		$this->_Customers=2;
	    }
	}else{
	    $this->_Customers=false;
	}
	$html="";
	$list = $this->getFieldsList($step , $locate , $addTable);
	if(!empty($list) || count($list)>0){
	   $i=0;
	    foreach($list as $NewFieldData){
		switch ($NewFieldData['attribute_code']){
		    case "vat_yn":
		    case "vat_taxcode":
		    case "vat_company":
		    case "vat_address":
			$hidden = "style='display:none;'";
			break;
		    case "checkout_note":
			$hiddenlabel = "style='display:none;'";
			break;
		    default :
			$hiddenlabel = "";
			$hidden = "";
		}
		if($i++%2==0){ if(empty($hidden)) $html .= $CS; else  $html .= str_replace("class","style='display:none;' class", $CS);}
		if($i%2!=0 || $i==1){ $class = 'd_1';}else{$class = 'd_4';}
		
		$html .='<div class="'. $class .'"'.(!empty($hidden)?$hidden:'').'><label for="fm_'.$NewFieldData['attribute_code'] . '"'.$hiddenlabel;
		if($NewFieldData['is_required']){ $html .=" class='required'>";}else{ $html .=">";}
		$html .=$this->getFieldLabel($NewFieldData['attribute_id']) . '</label>';
		if($NewFieldData['is_required']){ $html .="<span class='required'>*</span>";}
		$html .='<div class="input-box">'.$this->getFieldHtml($NewFieldData , $section)  .'</div>';
		$html .='</div>';
		if($i%2==0 || $i== count($list)){ $html .= $CE;}
	    }
	}
	return $html; 
    }
     public function getOptionslist($NewFieldId)
    {
	$Options = Mage::getResourceModel('eav/entity_attribute_option_collection')
	     ->setAttributeFilter($NewFieldId)
	     ->setStoreFilter(Mage::app()->getStore()->getId())
	     ->setPositionOrder()
	     ->load();
	     return($Options->toOptionArray());
    }
    public function getSavedFieldData($id,$Field,$from)
    {	if(!Mage::helper('fieldsmanager')->getStoredDatafor('enable')){
	    return;
	}
	if($id){
	    $SavedData= $this->GetFMData($id , $from , false);
	    if($SavedData){
		foreach($SavedData as $saved){
		    if($saved['code']==$Field){
			if($from =="orders" && $saved['keys']!=''){
			   return $saved['keys']; 
			}else{
			    return $saved['value'];
			}
			break;
		    }
		}
	    }
	}
	return;
    }
    public function getFieldHtml($NewFieldData , $section)
    {
	if(!Mage::helper('fieldsmanager')->getStoredDatafor('enable')){
	    return false;
	}
        $StoreId = Mage::app()->getStore()->getId();
	$NewFieldId='fm_'.$NewFieldData['attribute_code'];
        $TextFields = new Zend_View();
	$Label = $this->getFieldLabel($NewFieldData['attribute_id']);
        $class=$NewFieldData['frontend_class'];
        $NewFieldName = $section . '[fm_' . $NewFieldData['attribute_code'] . ']';
	if(Mage::getSingleton('core/session')->getParentOrderId() && Mage::getSingleton('core/session')->getParentOrderId() !=0){
	    $BackEndValue = $this->getSavedFieldData(Mage::getSingleton('core/session')->getParentOrderId(),$NewFieldData['attribute_code'],'orders');
	}
	 elseif($NewFieldData['fme_customer_account']>0 && $this->getCustomer()){
	    $customerId = 0;
	    if($this->getCustomer()){
		$customerId=$this->getCustomer()->getId();
	    }
	    $BackEndValue = $this->getSavedFieldData($customerId,$NewFieldData['attribute_code'],'customers');
	 }
	$BackEndValue = !empty($BackEndValue)? $BackEndValue:$NewFieldData['default_value'];
        if ($NewFieldData['is_required'])
        {
	    if(in_array($NewFieldData['frontend_input'], array('text', 'textarea', 'date')))
		{ $class .= ' required-entry';}
	    elseif(in_array($NewFieldData['frontend_input'], array('select', 'multiselect','boolean')))
		{ $class .= ' validate-select'; }
	    else{$class .= ' validate-one-required-by-name';}
	}
        $TextInputExtra = array
        (
            'id' => $NewFieldId,
            'class' => $class,
	    'title' => $Label,
        );
	$OptionsList=array();
	$html='';
        switch ($NewFieldData['frontend_input'])
        {
            case 'text':
                $TextInputExtra['class'] .= ' input-text';
                $html .= $TextFields->formText($NewFieldName, $BackEndValue, $TextInputExtra);
            break;    
            case 'textarea':
                $TextInputExtra['class'] .= ' input-text';
                $TextInputExtra['style'] = 'height:50px !important; width: 90%';
                $TextInputExtra['maxlength'] = '100';
                $html .= $TextFields->formTextarea($NewFieldName, $BackEndValue, $TextInputExtra);
            break;    
            case 'select':
		$OptionsList1=array();
		if($NewFieldData['is_used_for_price_rules']==1){
		    $OptionsList1[-1]=array('value'=>'','label'=>' ');
		}
		$OptionsList = $this->getOptionslist($NewFieldData['attribute_id']);
		$OptionsList = array_merge($OptionsList1, $OptionsList);
		$select = Mage::getModel('core/layout')->createBlock('adminhtml/html_select')
		->setData(array(
		    'id'    => $NewFieldId,
		    'class' => $class,
		    'value' => $BackEndValue
		))
		->setName($NewFieldName)
		->setOptions($OptionsList);
		$html .= $select->getHtml();
            break;    
            case 'multiselect':
		 if(!is_array($BackEndValue))$BackEndValue = explode(',',$BackEndValue);
		 $OptionsList = $this->getOptionslist($NewFieldData['attribute_id']);
		 $select = Mage::getModel('core/layout')->createBlock('adminhtml/html_select')
		->setData(array(
		    'id'    => $NewFieldId,
		    'class' => $class,
		    'value'=>$BackEndValue
		))
		->setExtraParams('multiple')
		->setName($NewFieldName . '[]')
		->setOptions($OptionsList);
                    $html .=  $select->getHtml();
            break;    
            case 'checkbox':
                $OptionsList = $this->getOptionslist($NewFieldData['attribute_id']);
		if ($OptionsList){
		    $newOPtions =array();
		    foreach ($OptionsList as $option) {
			$newOPtions[$option['value']]=$option['label'];
		    }
		    $class .= ' checkbox';
		     if(!is_array($BackEndValue))$BackEndValue = explode(',',$BackEndValue);
		    $attribs = array(
			'id' => $NewFieldId,
			'class' => $class,
			'title' => $Label,
		    );
		    $html .= $TextFields->formMultiCheckbox($NewFieldName, $BackEndValue, $attribs, $newOPtions, "<br />\n");
		}
            break;    
            case 'radio':
		$OptionsList = $this->getOptionslist($NewFieldData['attribute_id']);
		if ($OptionsList)
		{
		    $OptionsList1=array();
		    if(!$NewFieldData['is_required']) {
			$OptionsList1[-1]=array('value'=>'','label'=>Mage::helper('catalog')->__('None'));
		    }
		    $OptionsList = array_merge($OptionsList1, $OptionsList);
		    $newOPtions =array();
		    foreach ($OptionsList as $option) {
			$newOPtions[$option['value']]=$option['label'];
		    }
		    $class .= ' radio';
		    if(!is_array($BackEndValue))$BackEndValue = explode(',',$BackEndValue);
		    $attribs = array(
			'id' 	=> $NewFieldId,
			'class' 	=> $class,
			'title' 	=> $Label
		    );
		    $html .=$TextFields->formRadio($NewFieldName, $BackEndValue, $attribs, $newOPtions);
		}
		break;    
            case 'boolean':
		if($BackEndValue=='No'){$BackEndValue='0';}elseif($BackEndValue=='Yes'){$BackEndValue='1';}
               	if(!is_array($BackEndValue))$BackEndValue = explode(',',$BackEndValue);
                $bool = array(
			array(
			    'value' => 0,
			    'label' => Mage::helper('catalog')->__('No')
			),
			array(
			    'value' => 1,
			    'label' => Mage::helper('catalog')->__('Yes')
			)
		);
                $bool_html = Mage::getModel('core/layout')->createBlock('adminhtml/html_select')
		->setData(array(
		    'id'    => $NewFieldId,
		    'class' => $class,
		    'value'=>$BackEndValue
		))
		->setName($NewFieldName)
		->setOptions($bool);
                $html .= $bool_html->getHtml();
            break;    
            case 'date':
                $dateFormat = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
		 $date = Mage::getModel('core/layout')->createBlock('adminhtml/html_date')
		->setData(array(
		    'id'    => $NewFieldId,
		    'class' => $class,
		    'title'=>$NewFieldName,
		    'format'=>$dateFormat,
		    'image'=>Mage::getDesign()->getSkinUrl('images/grid-cal.gif')
		))
		->setValue($BackEndValue, $dateFormat)
		->setName($NewFieldName);
                $html .= $date->getHtml();
            break;
	case 'message':
                $html .= '<label>'.$BackEndValue.'</label>';
            break;
        }
        return $html;        
    }
    public function saveEAVData($model, $DefaultOptionsValue , $data){
	$CatalogProduct = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();  
	$model->setEntityTypeId($CatalogProduct);
	$model->setIsUserDefined(1);
	$model->setEntityType($this->_FM_EntityCode);	
	$model->save();
	$EntityAttribute = Mage::getModel('catalog/entity_attribute');
	$EntityAttribute->load($model->getId());
	$EntityAttribute->setDefaultValue($DefaultOptionsValue);
	$EntityAttribute->setEntityTypeId($this->getEntityTypeId());
	$EntityAttribute->save();
	$this->updaterelatedtables($model->getId(), $data);
	return;
    }
    public function getAfterLoad($data,$id){
	$read = Mage::getSingleton('core/resource')->getConnection('core_read');
	$select =$read->select()
            ->from(Mage::helper('fieldsmanager')->getFMTable('store'))
            ->where('attribute_id = ?', $id);
        if ($datas = $read->fetchAll($select)) {
            $locationArray = array();
            foreach ($datas as $row) {
                $locationArray[] = $row['store_id'];
            }
          $data['store_ids']=$locationArray;
        }
	$select = $read->select()
            ->from(Mage::helper('fieldsmanager')->getFMTable('customer_group'))
            ->where('attribute_id = ?', $id);
        if ($datas = $read->fetchAll($select)) {
            $locationArray = array();
            foreach ($datas as $row) {
                $locationArray[] = $row['group_id'];
            }
          $data['custmoer_group']=$locationArray;
        }
	if ($categories = $this->getCategoryId($id)) { 
	    $data['category_id']=$categories;
        }else{
	    $data['category_id']="";
	}
	return $data;
    }
    public function getCategoryId($atr_Id)
    {
	$read = Mage::getSingleton('core/resource')->getConnection('core_read');
	$select = $read->select()
            ->from(Mage::helper('fieldsmanager')->getFMTable('category'))
            ->where('attribute_id = ?', $atr_Id);
        if ($datas = $read->fetchAll($select)) {
            $locationArray = array();
		foreach ($datas as $row) {
		    if($row['category_id'] > 0 ){
			$locationArray[] = $row['category_id'];
		    }
		}
	    return $locationArray;
        }
	return;
    }
     public function updaterelatedtables($atr_Id,$data)
    {
	$write = Mage::getSingleton('core/resource')->getConnection('core_write');
	$read = Mage::getSingleton('core/resource')->getConnection('core_read');
	$condition = $write->quoteInto('attribute_id = ?', $atr_Id);
	if(isset($data['store_ids']) && count($data['store_ids'])!=0){
	    $write->delete(Mage::helper('fieldsmanager')->getFMTable('store'), $condition);
	    foreach ($data['store_ids'] as $store) {
		$storeArray = array();
		$storeArray['attribute_id'] = $atr_Id;
		$storeArray['store_id'] = $store;
		$write->insert(Mage::helper('fieldsmanager')->getFMTable('store'), $storeArray);
	    }
	}
	if(isset($data['custmoer_group']) && count($data['custmoer_group'])!=0){
	    $write->delete(Mage::helper('fieldsmanager')->getFMTable('customer_group'), $condition);
	    foreach ($data['custmoer_group'] as $store) {
		$storeArray = array();
		$storeArray['attribute_id'] = $atr_Id;
		$storeArray['group_id'] = $store;
		$write->insert(Mage::helper('fieldsmanager')->getFMTable('customer_group'), $storeArray);
	    }
	}
	if(isset($data['links']) && count($data['links'])!=0){
	    $ProductIds = Mage::helper('adminhtml/js')->decodeGridSerializedInput($data['links']['related']);
	    $write->delete(Mage::helper('fieldsmanager')->getFMTable('products'), $condition);
		foreach ($ProductIds as $product) {
		$productArray = array();
		$productArray['attribute_id'] = $atr_Id;
		$productArray['products_id'] = $product;
		$write->insert(Mage::helper('fieldsmanager')->getFMTable('products'), $productArray);
	    }
	}
	if(isset($data['category_ids']) && count($data['category_ids'])!=0){
		$string = $_POST['category_ids'];
		$string = trim($string, ',');
		$catIds = explode(",", $string);	
		$result = array_unique($catIds);
		if(count($result)){
		    $write->delete(Mage::helper('fieldsmanager')->getFMTable('category'), $condition);
		    foreach ($result as $category) {
			if($category !=0){
			    $categoryArray = array();
			    $categoryArray['attribute_id'] = $atr_Id;
			    $categoryArray['category_id'] = $category;
			    $write->insert(Mage::helper('fieldsmanager')->getFMTable('category'), $categoryArray);
			}
		    }
		}
	}
    }
    public function getFieldsmanagerRelatedProducts($fieldsmanagerId)
    {
	$read = Mage::getSingleton('core/resource')->getConnection('core_read');
	$select = $read->select()
            ->from(Mage::helper('fieldsmanager')->getFMTable('products'))
            ->where('attribute_id = ?', $fieldsmanagerId);
        if ($datas = $read->fetchAll($select)) {
            $locationArray = array();
            foreach ($datas as $row) {
                $locationArray[] = $row['products_id'];
            }
         return $locationArray;
        }
	return;
    }
} 