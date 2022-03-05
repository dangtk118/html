<?php
class Fahasa_Availablestock_Helper_Data extends Mage_Core_Helper_Abstract{  
    public function exist($customer_email, $isbn) {
        $availablestock = Mage::getModel("availablestock/availablestock")->loadByMultiple($customer_email, $isbn);
        return $availablestock;
    }

    function checkNotify($model) {
        $notify = 1;
        foreach ($model->getItems() as $item) {
            // neu 1 dong nao do co notify = 0 
            // => insert dong moi (khach dang doi gui mail)
            // => khong can insert nua
            if ($item->getNotify() == 0) {
                $notify = 0;
                break;
            }
        }
        return $notify;
    }

    function insertAvailablestock($email, $sku) {
        $insert = Mage::getModel('availablestock/availablestock');
        $insert->setCustomerEmail($email)
                ->setIsbn($sku)
                ->setInsertTime(now())
                ->setNotify(0)
                ->save();
    }

}
