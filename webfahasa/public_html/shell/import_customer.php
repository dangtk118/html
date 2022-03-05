<?php
    echo "---------------------------------------------\n";
    echo "READY  \n";
    echo "---------------------------------------------\n";


require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();

$media_customer_path = Mage::getBaseDir('media')."/customer/";

require_once $media_customer_path.'Excel/reader.php';

$excel_path = $media_customer_path."customer_list.xls";

echo "loading file: ".$excel_path."  \n";
echo "---------------------\n";
$data = new Spreadsheet_Excel_Reader();
$data->setOutputEncoding('utf-8');
$data->read($excel_path);

echo "readed file excel  \n";
echo "---------------------\n";
$excel_data = [];
foreach($data->sheets[0] as $index=>$rows){
    if($index == "cells"){
	$excel_data = $rows;
    }
}
echo "Excel array size:".sizeof($excel_data)."\n";
echo "---------------------\n";
$helper_customer = Mage::helper("fahasa_customer");
$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
foreach($excel_data as $index=>$row){
    if($index > 1){
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	$first_name = trim($row[1]);
	$last_name = trim($row[2]);
	$email = trim($row[3]);
	$telephone = trim($row[4]);
	
	$sql = "select entity_id from fhs_customer_entity where email = '".$email."' limit 1;";
	$sql_telephone = "select entity_id from fhs_customer_entity where telephone = '".$telephone."' limit 1;";
	$customer_entity = $reader->fetchRow($sql);
	$customer_phone = $reader->fetchRow($sql_telephone);
	
	if(!$customer_phone['entity_id']){
	    try{
		if(!$customer_entity['entity_id']){
		    $customer = Mage::getModel('customer/customer');
		    $customer->setFirstname(trim($first_name))
				->setLastname($last_name)
				->setEmail($email)
				->save();
		}
		else{
		    $customer = Mage::getModel('customer/customer')->load($customer_entity['entity_id']);
		}
		
		$helper_customer->createCustomerByExcel($customer, $telephone);
		
		echo "[".$index."/".(sizeof($excel_data)-1)."][Success]:created customer:- first_name: ".$first_name.", last_name: ".$last_name.", email: ".$email.", phone: ".$telephone." \n";
	    } catch (Exception $ex) {
		echo "[".$index."/".(sizeof($excel_data)-1)."][ERROR]: ".$ex->getMessage()." \n";
	    }
	}else{
	    echo "[".($index-1)."/".(sizeof($excel_data)-1)."][DUPLICATE]:can't create customer because duplicate:- first_name: ".$first_name.", last_name: ".$last_name.", email: ".$email.", phone: ".$telephone." \n";
	}
    }
}
echo "----------DONE-----------\n";
return;
