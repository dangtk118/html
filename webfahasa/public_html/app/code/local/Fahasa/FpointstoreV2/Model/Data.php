<?php

class Fahasa_FpointstoreV2_Model_Data extends Mage_Eav_Model_Entity_Attribute
{
    public function getValidateRuleMessage($event){
	$rule_id = $event->getRuleId();
	$result = $event->getResult();
	$all = $event->getAll();
	$true = $event->getTrue();
	$attribute = $event->getAttribute();
	$op = $event->getOp();
	$value = $event->getValue();
	$validatedValue = $event->getValidatedValue();
		
	$found = $all;
	if(($all && !$result) || (!$all && $result)){
	    $found = $result;
	}
	
	if(!empty($rule_id) && !(($found && $true) || (!$found && !$true))){
	    $msg = Mage::getSingleton('customer/session')->getRuleMsg();
	    if(empty($msg)){
		return $result;
	    }
	    if(!empty($rule_id)){
		$msg_rule = $msg[$rule_id];
	    }
	    if(empty($msg_rule)){
		$msg_rule = [];
	    }
	    $validate_item = [];
	    $validate_item['attribute'] = $attribute;
	    $validate_item['opt'] = $op;
	    $validate_item['r_value'] = $value;
	    $validate_item['v_value'] = $validatedValue;
	    $op_text = '';
	    switch ($op) {
		case '==':
		    $op_text = 'bằng';
		    break;
		case '!=':
		    $op_text = 'khác';
		    break;
		case '<':
		    $op_text = 'bé hơn';
		    break;
		case '<=':
		    $op_text = 'nhỏ hơn hoặc bằng';
		    break;
		case '>':
		    $op_text = 'lớn hơn';
		    break;
		case '>=':
		    $op_text = 'lớn hơn hoặc bằng';
		    break;
		case '{}': 
		    $op_text = 'có chứa';
		    break;
		case '!{}':
		    $op_text = 'không được có chứa';
		    break;
		case '()':
		    $op_text = 'áp dụng cho';
		    break;
		case '!()':
		    $op_text = 'không áp dụng cho';
		    break;
	    }
	    
	    switch ($attribute) {
		case 'base_subtotal':
		    $validate_item['msg'] = 'Thành tiền phải '.((!$true && !($op == "!=" || $op == "!()" || $op == "!()"))?'Không ':'').$op_text." ".number_format($value, 0, ",", ".");
		    break;
		case 'category_ids':
		    $cat_str = '';
		    $value_found = [];
		    if(is_array($validatedValue)){
			$value_found = array_intersect($validatedValue, (array)$value);
		    }else{
			$value = (array)$value;
			foreach ($value as $item) {
			    if ($this->_compareValues($validatedValue, $item)) {
				array_push($value_found, $item);
				break;
			    }
			}
		    }
		    foreach ($value_found as $item) {
			$_category = Mage::getModel('catalog/category')->load($item);
			if(empty($cat_str)){
			    $cat_str = $_category->getName();
			}else{
			    $cat_str = $cat_str.", ".$_category->getName();
			}
		    }
		    
		    if(!empty($cat_str)){
			$validate_item['msg'] = ((!$true && !($op == "!=" || $op == "!()" || $op == "!()"))?'Không ':'Chỉ')." áp dụng cho ".$cat_str;
		    }else{
			$validate_item['msg'] = 'Danh mục sản phẩm yêu cầu không thỏa';
		    }
		break;
		case 'sku':
		    //$product_name_str = '';
		    $value_found = [];
		    if(is_array($validatedValue)){
			$value_found = array_intersect($validatedValue, (array)$value);
		    }else{
			$value = (array)$value;
			foreach ($value as $item) {
			    if ($this->_compareValues($validatedValue, $item)) {
				array_push($value_found, $item);
				break;
			    }
			}
		    }
//                    foreach ($value_found as $item) {
//			$_product = Mage::getModel('catalog/product')->load($item, 'sku');
//			if(empty($cat_str)){
//			    $product_name_str = $_product->getName();
//			}else{
//			    $product_name_str = $cat_str.", ".$_product->getName();
//			}
//                    }
		    $sku_str = implode(', ', $value_found);
		    if(!empty($sku_str)){
			$validate_item['msg'] = ((!$true && !($op == "!=" || $op == "!()" || $op == "!()"))?'Không ':'Chỉ')." áp dụng cho mã ".$sku_str;
		    }else{
			$validate_item['msg'] = 'Mã sản phẩm yêu cầu không thỏa';
		    }
		    break;
		case 'supplier':
		    $value_found = [];
		    if(is_array($validatedValue)){
			$value_found = array_intersect($validatedValue, (array)$value);
		    }else{
			$value = (array)$value;
			foreach ($value as $item) {
			    if ($this->_compareValues($validatedValue, $item)) {
				array_push($value_found, $item);
				break;
			    }
			}
		    }
		    $ncc_str = implode(', ', $value_found);
		    if(!empty($ncc_str)){
			$validate_item['msg'] = ((!$true && !($op == "!=" || $op == "!()" || $op == "!()"))?'Không ':'Chỉ')." áp dụng cho mã NCC ".$ncc_str;
		    }else{
			$validate_item['msg'] = 'Nhà cung cấp yêu cầu không thỏa';
		    }
		    break;
		case 'storeView':
		    if($value == 4){
			switch($op){
			    case "==":
				$validate_item['msg'] = 'Chỉ áp dụng cho app mobile';
				break;
			    case "!=":
				$validate_item['msg'] = 'Chỉ áp dụng cho web';
				break;
			}
		    }
		break;
		case 'payment_method':
		    $paymentTitle = \Mage::getStoreConfig('payment/' . $value . '/title');
		    switch($op){
			case "==":
			    $validate_item['msg'] = 'Chỉ áp dụng cho '.strtolower($paymentTitle);
			    break;
			case "!=":
			    $validate_item['msg'] = 'Không áp dụng cho '.strtolower($paymentTitle);
			    break;
		    }
		break;
		case 'country_id':
		    switch($op){
			case "==":
			    $validate_item['msg'] = 'Chỉ áp dụng cho '.$value;
			    break;
			case "!=":
			    $validate_item['msg'] = 'Không áp dụng cho '.$value;
			    break;
		    }
		break;
		case 'qty':
		    $validate_item['msg'] = 'Số lượng các sản phẩm yêu cầu không thỏa';
		break;
		case 'base_row_total':
		    if($value > 1){
			$validate_item['msg'] = 'Tổng tiền các sản phẩm yêu cầu phải '.((!$true && !($op == "!=" || $op == "!()" || $op == "!()"))?'Không ':'').$op_text." ".number_format($value, 0, ",", ".");
		    }
		break;
	    }
	    if(!empty($validate_item['msg'])){
		$msg_rule[$attribute.$op] = $validate_item;
		$msg[$rule_id] = $msg_rule;
		Mage::getSingleton('customer/session')->setRuleMsg($msg);
	    }
	}else{
	    if(($attribute == "base_subtotal" || $attribute == "base_row_total") && $value > 1 &&  ($op == ">" || $op == ">=")){
		$msg = Mage::getSingleton('customer/session')->getRuleMsg();
		if(empty($msg)){
		    return $result;
		}
		if(!empty($rule_id)){
		    $msg_rule = $msg[$rule_id];
		}
		if(empty($msg_rule)){
		    $msg_rule = [];
		}
		$validate_item = [];
		$validate_item['attribute'] = $attribute;
		$validate_item['opt'] = $op;
		$validate_item['r_value'] = $value;
		$validate_item['v_value'] = $validatedValue;
		
		$msg_rule[$attribute.$op] = $validate_item;
		$msg[$rule_id] = $msg_rule;
		Mage::getSingleton('customer/session')->setRuleMsg($msg);
	    }
	}
	return;
    }
    
    protected function _compareValues($validatedValue, $value, $strict = true)
    {
        if ($strict && is_numeric($validatedValue) && is_numeric($value)) {
            return $validatedValue == $value;
        } else {
            $validatePattern = preg_quote($validatedValue, '~');
            if ($strict) {
                $validatePattern = '^' . $validatePattern . '$';
            }
            return (bool)preg_match('~' . $validatePattern . '~iu', $value);
        }
    }
}
