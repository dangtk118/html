<?php
class Fahasa_Codfee_Model_Sales_Order_Total_Creditmemo_Fee extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
	public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
	{
		$order = $invoice->getOrder();
                
                $codfee = $order->getCodfee();
                if ($codfee != null) {
                    $invoice->setGrandTotal($invoice->getGrandTotal() + $codfee);
                    $invoice->setBaseGrantTotal($invoice->getBaseGrandTotal() + $codfee);
                }

		return $this;
	}
}
