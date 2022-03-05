<?php
require 'app/Mage.php';
Mage::app('admin')->setUseSessionInUrl(false);                                                                                                                 
//These two orders id belong to a previous deleted store. Causing conflict when create new store under the same id.
$test_order_ids=array(
  '300000001',
  '300000002'
);
foreach($test_order_ids as $id){
    try{
        Mage::getModel('sales/order')->loadByIncrementId($id)->delete();
        echo "order #".$id." is removed".PHP_EOL;
    }catch(Exception $e){
        echo "order #".$id." could not be remvoved: ".$e->getMessage().PHP_EOL;
    }
}
echo "complete.";
