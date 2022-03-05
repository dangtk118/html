<?php
/**
 * Inchoo PDF rewrite for custom attribute
 * Attribute "inchoo_warehouse_location" has to be set manually
 * Original: Sales Order Invoice Pdf default items renderer
 *
 * @category   Inchoo
 * @package    Inhoo_Invoice
 * @author     Mladen Lotar - Inchoo <mladen.lotar@inchoo.net>
 */
 
class Fahasa_Invoice_Model_Order_Pdf_Items_Invoice_Default extends Mage_Sales_Model_Order_Pdf_Items_Invoice_Default
{
    /**
     * Draw item line
	 **/
    public function draw()
    {
        $order  = $this->getOrder();
        $item   = $this->getItem();
        $pdf    = $this->getPdf();
        $page   = $this->getPage();
        
        $itemQty = $item->getQty();
        $product = Mage::getModel('catalog/product')->load($item->getProductId());
        $item_don_gia = $product->getPrice();    //Regular price                    
        $item_thanh_tien = $item_don_gia * $itemQty;
        //Them vao Tong thanh tien
        $order->setTongThanhTien($order->getTongThanhTien() + $item_thanh_tien);
        $item_chiet_khau = ($item_don_gia - (($item->getRowTotal()+ $item->getTaxAmount())/$itemQty))/$item_don_gia * 100;
	$order->setTongChietKhauDuocTru($order->getTongChietKhauDuocTru() + ($item_thanh_tien - $item->getRowTotalInclTax()));
	
        $lines  = array();
 
        // draw Product name
        $lines[0] = array(array(
            'text' => Mage::helper('core/string')->str_split($item->getName(), 42, true, true),
            //'text' => 'abc',
            'feed' => 117,
        ));
 
        // draw SKU
        $lines[0][] = array(
                // get sku from $invoice
            //'text'  => Mage::helper('core/string')->str_split($this->getSku($item), 25),
                //get sku from $item->getProductId()
            'text'  => Mage::getModel('catalog/product')->load($item->getProductId())->getSku(),
            'feed'  => 52
        );
 
        // draw QTY
        $lines[0][] = array(
            'text'  => $item->getQty()*1,
            'feed'  => 345,
            'align' => 'right'
        );
 
	//draw Price bia
        $lines[0][] = array(
            'text'  =>  $order->getOrderCurrency()->format($item_don_gia, array(), false, false),
            'feed'  => 420,
            'font'  => 'bold',
            'align' => 'right'
        );
 
        // draw chiet khau
        $lines[0][] = array(
            'text'  => ceil($item_chiet_khau) . " %",//$order->formatPriceTxt($item->getTaxAmount()),
            'feed'  => 495,
            'font'  => 'bold',
            'align' => 'right'
        );
 
        // draw Subtotal
        $lines[0][] = array(
            'text'  =>  $order->getOrderCurrency()->format($item_thanh_tien, array(), false, false) ,//$order->formatPriceTxt($item->getRowTotal()),
            'feed'  => 565,
            'font'  => 'bold',
            'align' => 'right'
        );
 
        // custom options
        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                // draw options label
                $lines[][] = array(
                    'text' => Mage::helper('core/string')->str_split(strip_tags($option['label']), 70, true, true),
                    'font' => 'italic',
                    'feed' => 35
                );
 
                if ($option['value']) {
                    $_printValue = isset($option['print_value']) ? $option['print_value'] : strip_tags($option['value']);
                    $values = explode(', ', $_printValue);
                    foreach ($values as $value) {
                        $lines[][] = array(
                            'text' => Mage::helper('core/string')->str_split($value, 50, true, true),
                            'feed' => 40
                        );
                    }
                }
            }
        }
 
        $lineBlock = array(
            'lines'  => $lines,
            'height' => 15
        );
 
        $page = $pdf->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $this->setPage($page);
    }
}
