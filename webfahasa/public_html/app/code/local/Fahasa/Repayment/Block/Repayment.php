 <?php
 
class Fahasa_Repayment_Block_Repayment extends Mage_Core_Block_Template{
    
    public function getPaymentMethods($is_mobile = false) {
        $payments = \Mage::getSingleton('payment/config')->getActiveMethods();
        foreach ($payments as $paymentCode => $paymentModel) {
            if ($paymentModel->canUseCheckout()) {
                $paymentTitle = \Mage::getStoreConfig('payment/' . $paymentCode . '/title');
                // comment out webmoney, bug #49500 http://app.fahasa.com/redmine/issues/49500
                if ($paymentCode == "webmoney") {
                    continue;
                }
                if ($is_mobile){
//                    if ($paymentCode == "airpay") {
//                        continue;
//                    }
                }
                $methods[] = array(
                    'label' => $paymentTitle,
                    'value' => $paymentCode,
                );
            }
        }
        return $methods;
    }

}
