<?php
    echo "---------------------------------------------\n";
    echo "READY  \n";
    echo "---------------------------------------------\n";


require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();

$media_customer_path = Mage::getBaseDir('media')."/customer/";

require_once $media_customer_path.'Excel/reader.php';

$excel_path = $media_customer_path."import_country.xls";

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

$limit = 500;
$weight = 500;

echo "----------TRUNCATE-----------\n";
$writer = Mage::getSingleton('core/resource')->getConnection('core_write');
$truncate = "TRUNCATE TABLE fhs_shipping_matrixrate;";
$writer->query($truncate);

$truncate = "TRUNCATE TABLE fhs_directory_country;";
$writer->query($truncate);
echo "----------TRUNCATE DONE-----------\n";

echo "----------DATA PROCESSING-----------\n";
$countries_allow = [];
foreach($excel_data as $index=>$row){
    if($index > 1){
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	$name = trim($row[1]);
	$iso2 = trim($row[2]);
	$iso3 = trim($row[3]);
	$first_500g = trim($row[4]);
	$next_500g = trim($row[5]);
	$fee_first_2000g = trim($row[6]);
	$fee_next_500g = trim($row[7]);
	$allow = trim($row[8]);
	$other_fee = trim($row[9]);
	if($allow){
	    array_push($countries_allow, $iso2);
	    //echo "<territory type=\"".$iso2."\">".$name."</territory>"."\n";
	}
	if(empty($other_fee) || !is_numeric($other_fee)){
	    echo "[ERROR]Other fee is null or not numenic, name=".$name."\n";
	    echo "----------STOP-----------\n";
	    return;
	}
	echo "------------------\n";
	echo "Name = ".$name."\n";
	echo "First_500g = ".$first_500g."\n";
	echo "Next_500g = ".$next_500g."\n";
	echo "Fee_first = ".$fee_first_2000g."\n";
	echo "Fee_next = ".$fee_next_500g."\n";
	echo "Other_fee = ".$other_fee."\n";
	$index_count = 0;
	while ($index_count < $limit){
	    $weight_from = $weight*$index_count;
	    if($index_count > 0){
		++$weight_from;
	    }
	    $weight_to = $weight*($index_count + 1);
	    
	    //price calculator
	    $price = $first_500g + ($next_500g * $index_count) + $fee_first_2000g;
	    
	    if($weight_from > 2000){
		$price = $price + ($fee_next_500g * ($index_count-((2000/$weight) -1)));
	    }
	    
	    //add other fee 
	    $price = $price * $other_fee;
	    
	    $sql = "INSERT INTO fhs_shipping_matrixrate
		    (website_id, dest_country_id, dest_region_id, dest_city, dest_zip, dest_zip_to, condition_name, condition_from_value, condition_to_value, price, cost, delivery_type)
		    VALUES(1, '".$iso2."', 0, '', '', '', 'package_weight', ".$weight_from.", ".$weight_to.", ".$price.", 0.0000, 'Normal Shipping');";
	    $writer->query($sql);
	    
	    ++$index_count;
	    echo "[".($index-1)."/".(sizeof($excel_data)-1)."][".$index_count."/".($limit)."][Success]:inserted country price:- code: ".$code.", weight_from: ".$weight_from.", weight_to: ".$weight_to.", price: ".$price." \n";
	}
	
	$sql = "INSERT INTO fhs_directory_country
		(country_id, iso2_code, iso3_code)
		VALUES('".$iso2."', '".$iso2."', '".$iso3."');";
	$writer->query($sql);
    }
}


$sql = "INSERT INTO fhs_directory_country
	(country_id, iso2_code, iso3_code)
	VALUES('VN', 'VNM', '704');";
$writer->query($sql);

if(sizeof($countries_allow) > 0){
    array_push($countries_allow, 'VN');
    $sql = "update fhs_core_config_data set value = '".implode(',', $countries_allow)."' where `path` = 'general/country/allow';";
    $writer->query($sql);
}

echo "----------DONE-----------\n";
return;
