<?php

class Fahasa_Tryout_Helper_Data extends Mage_Core_Helper_Abstract {

    const DELTA = 300; // time expired session fhs_coin 5 Minute = 300s

    public function determinetryoutHistory() {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return false;
        }
        $customer_email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        return $this->loadCustomerTryout($customer_email);
    }

    public function determinetryout() {
	$result = 0;
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return $result;
        }
        $customer = Mage::getSingleton('customer/session')->getCustomer();
	if(Mage::registry('is_gat_customer_info_rest')) {
	    $result = Mage::helper("fahasa_customer/fpoint")->getFpoint($customer);
	}else{
	    $result = Mage::helper("fahasa_customer/fpoint")->getFpoint($customer, true);
	}
	return $result;
    }

    public function loadCustomerTryout($customerEmail) {
        $tryout = Mage::getModel("tryout/tryout")->load($customerEmail);
        if ($tryout->getTryoutEmail() != null) {
            return $tryout;
        }
        return false;
    }

    /*
     * get value fhs_coin
     * $coinCode
     * $getDB = 1 : bat buoc query lai db khi can
     */

    public function checkCoin($coinCode, $getDB = 0) {
        Mage::log("checkCoin coinCode = " . $coinCode . " --- getDB=" . $getDB, null, "fhs_coin.log");
        $coinObj = Mage::getSingleton('core/session')->getFhsCoin();
        //Luu session: code , timestamp
        //Retrieve: currentTimestamp - savetimestamp > DELTA
        if (
                $getDB == 1 || // bat buoc query lai db
                !$coinObj || // khong co fhsCoin trong session 
                !$coinObj['code'] || // fhsCoin trong session nhung khong co code
                ((time() - $coinObj['savedTimeStamp']) > self::DELTA) // timeout TimeStamp
        ) {
            // clear session
            $coinObj = null;
            Mage::getSingleton('core/session')->setFhsCoin(null);

            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            //add is_one_time column in fhs_coin: to make whether coin code is used one time or more time.
            //if one_time: original_amount = current_amount => code has not been used before yet
            $query = 'select * from fhs_coin where code = "' . $coinCode . '" and active = 1 and expired_date > now() '
                    . 'and (is_one_time = 0 or (is_one_time = 1 and original_amount = current_amount ))';
            $results = $readConnection->fetchAll($query);
            if (!empty($results[0]['code'])) {
                $coinObj['code'] = $results[0]['code'];
                $coinObj['originalAmount'] = $results[0]['original_amount'];
                $coinObj['currentAmount'] = $results[0]['current_amount'];
                $coinObj['savedTimeStamp'] = time();
                Mage::getSingleton('core/session')->setFhsCoin($coinObj);
                Mage::log("checkCoin connect db ---- "
                        . "coinCode = " . $coinCode . ", "
                        . "originalAmount = " . $coinObj['originalAmount'] . ", "
                        . "currentAmount = " . $coinObj['currentAmount'] . ", "
                        . "savedTimeStamp = " . $coinObj['savedTimeStamp']
                        , null, "fhs_coin.log");
            }
        }
        return $coinObj;
    }
    
    // tong so tien khach tra trong nam
    public function getPayAccureYear() {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return false;
        }
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $customerId = $customer->getId();
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $date = date("Y");
        $sql = "select CAST(ifnull(sum(total), 0) AS INT) as 'totalPurchase'
		from (
		    select 
		    so.order_id, so.suborder_id, o.created_at ,
		    sum(if(sb.bundle_id is null, soi.price, (1-sb.saving) * soi.price) * if(sb.bundle_id is null, soi.qty, sb.qty * soi.qty)) + ifnull(so.cod_fee, 0) + ifnull(so.shipping_fee, 0) - ifnull(so.discount_amount, 0) + ifnull(so.giftwrap_fee, 0) - ifnull(so.tryout_discount, 0) as total
		    from fahasa_suborder so 
		    join fahasa_suborder_item soi on so.order_id = soi.order_id and so.suborder_id = soi.suborder_id 
		    left join fahasa_suborder_bundle sb on soi.bundle_id = sb.bundle_id and soi.suborder_id = sb.suborder_id and soi.bundle_type = sb.bundle_type         
		    join fhs_sales_flat_order o on o.increment_id = so.order_id
		    where so.parent_id is null
		    and so.parent_return_id is null and so.status = 'complete' 
		    and o.created_at >= convert_tz('" . $date . "-01-01', '+0:00', '-7:00')
		    and o.customer_id = " . $customerId . "
		    group by suborder_id
		) a;";
        $data = $readConnection->fetchRow($sql);
        $money = $data['totalPurchase'];
        return $money;
    }

    // tong don hang giao thanh cong trong nam (tinh don hang cha)
    public function getAllOrderInYear() {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return false;
        }
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $date = date("Y");
        $query = "select count(*) as num_orders_year from fhs_sales_flat_order "
                . "where status = 'complete' and customer_id = '" . $customer->getId() . "' and created_at >= '" . $date . "-01-01 00:00:00';";
        $data = $readConnection->fetchAll($query);
        $n_orders_year = $data[0]['num_orders_year'];
        return $n_orders_year;        
    }
    
    public function getListLevelMember() {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $date = date("Y");
        $query = "select * from fhs_fvip_level_rule;";
        $data = $readConnection->fetchAll($query);
        return $data;
    }
    
    /**
     * get image show line ffoint
     * **/
    public function handleLineFpoint($fpointAccureYear, $maxLevel){
        $percent = ($fpointAccureYear / $maxLevel) * 100;
        $img = 0;
        switch ($percent) {  
            case 0:
                $img = 0;
                break;
            case ($percent >= 100):
                $img = 16;
                break;
            case ($percent > 95):
                $img = 15;
                break;
            case ($percent > 90):
                $img = 14;
                break;
            case ($percent > 85):
                $img = 13;
                break;
            case ($percent > 80):
                $img = 12;
                break;
            case ($percent > 75):
                $img = 11;
                break;
            case ($percent > 70):
                $img = 10;
                break;
            case ($percent > 65):
                $img = 9;
                break;
            case ($percent > 60):
                $img = 8;
                break;
            case ($percent > 55):
                $img = 7;
                break;
            case ($percent > 50):
                $img = 6;
                break;
            case ($percent > 40):
                $img = 5;
                break;
            case ($percent > 35):
                $img = 4;
                break;
            case ($percent >= 20):
                $img = 3;
                break;
            case ($percent > 10):
                $img = 2;
                break;
            case ($percent > 0):
                $img = 1;
                break;
        }
        return $img;
    }
        
    // check cac san pham khong thanh toan bang fpoint
    public function getProductNotApplyFpoint() {
        $cart = Mage::getModel('checkout/cart')->getQuote();
        $no_apply_fpoint = Mage::getStoreConfig('fpoint_input/general/no_apply_fpoint');
        $arr_no_apply_fpoint = explode(",", str_replace(' ', '', $no_apply_fpoint));
        $product_list = array();
        foreach ($cart->getAllItems() as $item) {
            $productName = $item->getProduct()->getName();
            $sku = $item->getProduct()->getSKU();
            if (in_array($sku, $arr_no_apply_fpoint)) {
                $product = array();
                $product['sku'] = $sku;
                $product['name'] = $this->cutString($productName);
                $product = array_push($product_list, $product);
            }
        }
        if (count($product_list) > 0) {
            // remove fpoint rule
            Mage::getSingleton('checkout/session')->unsetData('onestepcheckout_tryout');
            return $product_list;
        } else {
            return FALSE;
        }
    }

    public function cutString($str, $numWords = 6) {
        $input = explode(" ", $str);
        $count = count($input);
        $input = array_slice($input, 0, $numWords);
        if ($count > 4) {
            return implode(" ", $input) . "...";
        } else {
            return implode(" ", $input);
        }
    }

}
