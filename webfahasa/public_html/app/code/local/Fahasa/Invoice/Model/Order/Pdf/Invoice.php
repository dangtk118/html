<?php

/**
 * xuat phieu giao hang cho admin sales
 * Original: Sales Order Invoice PDF model
 *
 * @category   Inchoo
 * @package    Inhoo_Invoice
 * @author     Mladen Lotar - Inchoo <mladen.lotar@inchoo.net>
 */
class Fahasa_Invoice_Model_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Invoice {

    const VAT_TEN_CTY = 'vat_company';
    const VAT_DIACHI = 'vat_address';
    const VAT_MA_SO_THUE = 'vat_taxcode';
    
    private $orig_y = 800;
    private $total_line_height = 15;

    public function insertLogo(&$page, $store = null) {        

        $this->y = $this->y ? $this->y : 815;                
        $image = Mage::getSingleton('core/design_package')->getSkinBaseDir(array('_area' => 'frontend', '_package' => 'ma_vanese')) . "/images/logo.png";
        if (is_file($image)) {
            $image = Zend_Pdf_Image::imageWithPath($image);
            $top = 830; //top border of the page
            $widthLimit = 170; //half of the page width
            $heightLimit = 270; //assuming the image is not a "skyscraper"
            $width = $image->getPixelWidth();
            $height = $image->getPixelHeight();

            //preserving aspect ratio (proportions)
            $ratio = $width / $height;
            if ($ratio > 1 && $width > $widthLimit) {
                $width = $widthLimit;
                $height = $width / $ratio;
            } elseif ($ratio < 1 && $height > $heightLimit) {
                $height = $heightLimit;
                $width = $height * $ratio;
            } elseif ($ratio == 1 && $height > $heightLimit) {
                $height = $heightLimit;
                $width = $widthLimit;
            }

            $y1 = $top - $height;
            $y2 = $top;
            $x1 = 25;
            $x2 = $x1 + $width;

            //coordinates after transformation are rounded by Zend
            $page->drawImage($image, $x1, $y1, $x2, $y2);

            $this->y = $y1 - 10;
        }        
    }

    /**
     * lay dia chi tu store va gan vao pdf
     * @param type $page
     * @param type $store
     */
    protected function insertAddress(&$page, $store = null) {
        //$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page, 9);
        $page->setLineWidth(0);
        $this->y = $this->y ? $this->y : 815;
        $top = 815;
        foreach (explode("\n", Mage::getStoreConfig('sales/identity/address', $store)) as $value) {
            if ($value !== '') {
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach (Mage::helper('core/string')->str_split($value, 70, true, true) as $_value) {
                    $page->drawText(trim(strip_tags($_value)), $page->getWidth() - 170, $top - 10, 'UTF-8');
                    $top -= 10;
                }
            }
        }
        $this->y = ($this->y > $top) ? $top : $this->y;
    }

    /**
     * Lay thong tin giao hang
     * @return boolean
     */
    public function getShippingAddress() {
        foreach ($this->getAddressesCollection() as $address) {
            if ($address->getAddressType() == 'shipping' && !$address->isDeleted()) {
                return $address;
            }
        }
        return false;
    }

    public function getPdf($invoices = array()) {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->emulate($invoice->getStoreId());
            }
            $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
            $pdf->pages[] = $page;

            $order = $invoice->getOrder();
            
            /* Add image */
            $this->insertLogo($page, $invoice->getStore());

            /* Add address */
            $this->insertAddress($page, $invoice->getStore());
            $page->drawText('Công ty Cổ Phần Phát Hành Sách TP.HCM – FAHASA', 25, 790, 'UTF-8');
            $page->drawText('Phòng TMĐT – 55 Trần Đình Xu Q1 TPHCM', 25, 780, 'UTF-8');
            $this->_setFontRegular($page, 12);
            $this->_setFontBold($style, 12);
            $page->drawText('HOTLINE: 1900.636467', $page->getWidth() - 170, 815, 'UTF-8');
            $page->drawText('Email: sales@fahasa.com', $page->getWidth() - 170, 800, 'UTF-8');
            $page->drawText('Website: www.fahasa.com', $page->getWidth() - 170, 785, 'UTF-8');
            
            /* Add Tittle */
            $this->_setFontBold($page, 25);
            $page->drawText(strtoupper(Mage::helper('fhsinvoice')->__('Invoice')), 215, 740, 'UTF-8'); // . $invoice->getIncrementId(), 100, 700, 'UTF-8');            


            /**
             * Add Information;
             */
            $this->drawHeaderinfo($page, $invoice, $order);
            
            /**
             * Draw Phieu giao hang item and total
             */
            $page = $this->drawBody($order, $page, $invoice);
            // footer co chu ky cua phong TMDT
            $page = $this->drawFooter($page);
            // footer khong co chu ky cua phong TMDT
            //$page = $this->drawFooter2($page);
            $page = $this->drawFooterPage2($page, $order, $invoice);
        }
        $this->_afterGetPdf();

        return $pdf;
    }
   
    /**
     * Ve body items + total cua phieu giao hang
     * @param type $order
     * @param type $page
     * @param type $invoice
     */
    private function drawBody($order, $page, $invoice){
        //Init tong thanh tien = 0
        $order->setTongThanhTien(0);            
        //Init tong chiet khau = 0
        $order->setTongChietKhauDuocTru(0);            

        $this->_setFontRegular($page, 9);
        //$this->_setFontBold($page, 9);

        $page->setLineWidth(0.5);

        //Start position for 'Chi tiet Don Hang'
        $x = 20;
        $this->y = $this->drawItemsHeader($x, $this->y, $page);

        $this->y -=30;

        // Add Item
        $page = $this->drawItemsBody($page, $invoice->getAllItems(), $order);

        /* Add totals */
        $page = $this->drawItemsTotal($page, $order, $invoice);
        //Might be new page, so need to return page here
        return $page;
    }
    
    /**
     * ve table price total
     */ 
    protected function drawpricetotal($page, $order, $invoice, $text, $congtru, $getthis ){          
        if($text == Mage::helper('fhsinvoice')->__('Total')){
            // ve cot tong so luong & tong cong
            $page->drawRectangle(25, $this->y+15, $page->getWidth()-25, $this->y, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
            $page->drawText( $order->getTotalQtyOrdered()*1, 337, $this->y + 5, 'UTF-8');
            $page->drawLine(320, $this->y + 15, 320, $this->y);
            $page->drawLine(360, $this->y + 15, 360, $this->y);
        }  else{
            $page->drawRectangle(25, $this->y, $page->getWidth()-25, $this->y -=15, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        }
        $page->drawLine(500, $this->y + 15, 500, $this->y);
        
        if($text == Mage::helper('fhsinvoice')->__('Grand Total')){
            $this->_setFontBold($page, 12);
            Mage::helper('fhsinvoice/pdf_draw')->drawText($page, $congtru . $order->getOrderCurrency()->format($getthis, array(), false, false), 565, $this->y + 5, null, Fahasa_Invoice_Helper_Pdf_Draw::TEXT_ALIGN_RIGHT, 'UTF-8');
            $page->drawText( $text, 135, $this->y + 4, 'UTF-8');
        }else{
            //Mage::helper('fhsinvoice/pdf_draw')->drawText will draw right align text
            Mage::helper('fhsinvoice/pdf_draw')->drawText($page, $congtru . $order->getOrderCurrency()->format($getthis, array(), false, false),
                    565, $this->y + 5, null, Fahasa_Invoice_Helper_Pdf_Draw::TEXT_ALIGN_RIGHT, 'UTF-8');
            $page->drawText( $text, 135, $this->y + 5, 'UTF-8');
        } 
    }        
    
   /**
    *   ve phan total tong thanh toan
    */
    protected function drawItemsTotal($page, $order, $invoice){
        /**
         * 75: 5 rows total, each height is 15
         */
        if($this->y - 75 < 15){
            $page = $this->newPage();            
        }
        $this->_setFontBold($page, 9);
        // price tong cong san pham
        $this->drawpricetotal($page, $order,$invoice, Mage::helper('fhsinvoice')->__('Total'), '', $order->getTongThanhTien());
        // price tong chiet khau toan don hang
        $this->drawpricetotal($page, $order,$invoice, Mage::helper('fhsinvoice')->__('Discount'), '- ', $order->getTongChietKhauDuocTru());
        // price chi phi van chuyen/goi qua
        $this->drawpricetotal($page, $order,$invoice, Mage::helper('fhsinvoice')->__('Shipping Fee/Wrapping Fee'), '+ ', $order->getShippingInclTax());
        // price phi thu ho
        $this->drawpricetotal($page, $order,$invoice, Mage::helper('fhsinvoice')->__('Cash On Delivery Fee'), '+ ', $order->getCodfee());
        // price tong toan don hang        
        $this->drawpricetotal($page, $order,$invoice, Mage::helper('fhsinvoice')->__('Grand Total'), '', $order->getBaseGrandTotal());
        //Might be new page, so need to return page here
        return $page;
    }

    /**
     *
     * @param type $page reference to the current pdf page
     */
    protected function drawItemsBody($page, $items, $order){
        //Loop through each items in this invoice
        $stt = 0;
        foreach ($items as $item) {
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }
            
            $this->orig_y = $this->y;
            $this->y +=5;
            /* Draw item */
            $page = $this->_drawItem($item, $page, $order);
            $this->y-= 5;
            
            $page->drawText( ++$stt, 30, $this->orig_y + 5, 'UTF-8');
            $len = $this->orig_y + (15 - (ceil(strlen(utf8_decode($item->getName())) /45) * 15));
            $page->drawRectangle(25, $this->orig_y + 15, $page->getWidth() - 25, $len, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
            $page->drawLine(45, $this->orig_y + 15, 45, $len);
            $page->drawLine(115, $this->orig_y + 15, 115, $len);
            $page->drawLine(320, $this->orig_y + 15, 320, $len);
            $page->drawLine(360, $this->orig_y + 15, 360, $len);
            $page->drawLine(425, $this->orig_y + 15, 425, $len);
            $page->drawLine(500, $this->orig_y + 15, 500, $len);
        }
        
        return $page;
    }
    
    /**
     * Draw header of detail items columns of Phieu Giao Hang
     * Header of chi tiet san pham
     * @param type $x x coordinate
     * @param type $y y coordinate
     * @param type $page reference to the current pdf page
     */
    protected function drawItemsHeader($x, $y, $page){
        $page->drawRectangle(25, $y, $page->getWidth() - 25, $y - 20, Zend_Pdf_Page::SHAPE_DRAW_STROKE);            
            
        $this->_setFontBold($page, 12);

        $page->drawText(Mage::helper('sales')->__('Order Details'), 260, $y - 14, 'UTF-8');
        $this->_setFontBold($page, 10);
        $y -= 20;
        $page->drawRectangle(25, $y, $page->getWidth() - 25, $y - 15, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        // draw colum
        $page->drawText('Stt', 27, $y - 10, 'UTF-8');

        $page->drawText(Mage::helper('sales')->__('Products'), 132, $y - 10, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('SKU'), 52, $y - 10, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('Price'), 380, $y - 10, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('Qty'), 322, $y - 10, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('Discount'), 445, $y - 10, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('Subtotal'), 510, $y - 10, 'UTF-8');

        $page->drawLine(45, $y, 45, $y - 15);
        $page->drawLine(115, $y, 115, $y - 15);
        $page->drawLine(320, $y, 320, $y - 15);
        $page->drawLine(360, $y, 360, $y - 15);
        $page->drawLine(425, $y, 425, $y - 15);
        $page->drawLine(500, $y, 500, $y - 15);
        
        return $y;
    }

    /**
     * Lay thong tin ma so thue cua don hang
     * @param type $attrId attribute_id inside table fieldsmanager_orders
     * @param type $order Current Invoice order
     */
    public function getThongTinXuatHoaDon($order) {
        $vat_info = Mage::getModel('fieldsmanager/fieldsmanager')->GetFMData($order->getEntityId(), 'orders' , false);
        return $vat_info;
    }

    /**
     * Given 'code', return back the VAT information. If wrong code, will return empty string
     * @param type $code
     * @param type $vat_info
     * @return string
     */
    public function getValueVATSpecificInfo($code, $vat_info){
        foreach($vat_info as $vat){
            if($vat['code'] == $code){
                return $vat['value'];
            }
        }
        return '';
    }
    
    /**
     * Ve Thong tin nguoi thanh toan va thong tin nguoi nhan hang
     * @param type $order
     * @param type $page
     */
    public function drawShippingAddress($order, $page){
        $billingAddress = $this->_formatAddress($order->getBillingAddress()->format('pdf'));
        $shippingAddress = $this->_formatAddress($order->getShippingAddress()->format('pdf'));
        $block_height = 16;
        /**
         * Tim chieu cao cua Shipping/billing address
         */
        if (count($billingAddress) < count($shippingAddress)){
            $addressHeigh = count($shippingAddress) * $block_height;
        }else{
            $addressHeigh = count($billingAddress) * $block_height;
        }
        ;
        $page->setLineWidth(0.5);
        // Ve bang
        $x = 20;
        //Label Nguoi thanh toan/nhan hang
        $page->drawRectangle(25, $this->y , $page->getWidth() - 25, $this->y - $x, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $this->_setFontBold($page, 12);
        $page->drawText(Mage::helper('fhsinvoice')->__('Payment Information'), 110, $this->y - 14, 'UTF-8');
        $page->drawText(Mage::helper('fhsinvoice')->__('Information recipient'), 380, $this->y - 14, 'UTF-8');
        
        // Thong tin chi tiet nguoi thanh toan/nhan hang
        $page->drawRectangle(25, $this->y - $x, $page->getWidth() - 25, $this->y - $x - $addressHeigh, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->drawLine(300, $this->y, 300, $this->y - $x - $addressHeigh); // Vertical Line split nguoi thanh toan/nhan hang
        
        $this->_setFontRegular($page, 12);
        
        //Text Thong tin nguoi thanh toan
        $y_temp = $this->y;
        foreach ($billingAddress as $value) {
            if ($value !== '') {
                $text = array();
                foreach (Mage::helper('core/string')->str_split($value, 50, true, true) as $_value) {
                    $text[] = $_value;
                }
                
                foreach ($text as $part) {
                    $page->drawText(str_replace('Tel:', '', strip_tags(ltrim($part))), 29, $y_temp - $x - 14, 'UTF-8');
                    $y_temp -= 15;
                }
            }
        }

        // Text Thong tin nguoi nhan hang
        $y_temp = $this->y;
        foreach ($shippingAddress as $value) {
            if ($value !== '') {
                $text = array();
                foreach (Mage::helper('core/string')->str_split($value, 50, true, true) as $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $page->drawText(str_replace('Tel:', '', strip_tags(ltrim($part))), 305, $y_temp - $x - 14, 'UTF-8');
                    $y_temp -= 15;
                }
            }
        }
        $this->y = $this->y - $x - $addressHeigh;
    }
    /**
     * Thong tin xuat hoa don
     */
    public function drawThongtinXuathd($page, $order){
        $page->setLineWidth(0.5);
        // Ve bang
        $x = 20;
        $page->drawRectangle(25, $this->y, $page->getWidth() - 25, $this->y - $x, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        
        $page->drawRectangle(25, $this->y - $x, $page->getWidth() - 25, $this->y - (2 * $x), Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->drawRectangle(25, $this->y - (2 * $x), $page->getWidth() - 25, $this->y - (3 * $x), Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->drawRectangle(25, $this->y - (3 * $x), $page->getWidth() - 25, $this->y - (4 * $x), Zend_Pdf_Page::SHAPE_DRAW_STROKE);
         // Cac duong ke dung
        $page->drawLine(160, $this->y - $x, 160, $this->y - (4 * $x));
        $this->_setFontBold($page, 12);
        $page->drawText(Mage::helper('fhsinvoice')->__('Information Outputting Invoice'), 240, $this->y -14, 'UTF-8');
        $this->_setFontRegular($page,12);
        //Label Thong tin xuat hoa don (Ten cong ty, Ma so thue, Dia chi)
        $page->drawText(Mage::helper('fhsinvoice')->__('VAT Company'), 29, $this->y - $x - 14, 'UTF-8');
        $page->drawText(Mage::helper('customer')->__('Tax/VAT number'), 29, $this->y - (2 * $x) - 14, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('Address'), 29, $this->y - (3 * $x) - 14, 'UTF-8');
        
        //Chi tiet Ten cong ty, Ma so thue, Dia chi
        $vat_info = $this->getThongTinXuatHoaDon($order);
        if (count($vat_info) > 0) {
            $page->drawText(str_replace('"', ' ', $this->getValueVATSpecificInfo(self::VAT_TEN_CTY, $vat_info)), 170, $this->y - $x -14, 'UTF-8');
            $page->drawText(str_replace('"', ' ', $this->getValueVATSpecificInfo(self::VAT_MA_SO_THUE, $vat_info)), 170, $this->y - (2 * $x) - 14, 'UTF-8');
            $page->drawText(str_replace('"', ' ', $this->getValueVATSpecificInfo(self::VAT_DIACHI, $vat_info)), 170, $this->y - (3 * $x) - 14, 'UTF-8');
        }
        $this->y = $this->y - (4 * $x);
    }
    
    /**
     * Thong tin don hang(5 dong dau tien Phieu Giao Hang)
     */
    public function drawMasoNgaygiaohang($page, $invoice, $order){
         // Ve bang
        $x = 20;
        $this->_setFontRegular($page, 12);
        $this->y = 706;
        $MaSoValueX = 160;
        // So Hoa don
        $page->drawText(Mage::helper('fhsinvoice')->__('Invoice number'), 380, $this->y, 'UTF-8');
        $page->drawText('.........................', 460, $this->y, 'UTF-8');
        
        // Don vi Ban hang
        $page->drawText(Mage::helper('fhsinvoice')->__('Sale unit'), 29, $this->y - $x, 'UTF-8');
        $page->drawText(Mage::helper('Fahasa_Salecustomfield')->getDonViBanHangGiaoHang($order, 'don_vi_ban_hang'), $MaSoValueX, $this->y - $x, 'UTF-8');

        //Don Vi Giao hang
        $page->drawText(Mage::helper('fhsinvoice')->__('Delivery unit'), 29, $this->y - (2 * $x), 'UTF-8');
        $page->drawText(Mage::helper('Fahasa_Salecustomfield')->getDonViBanHangGiaoHang($order, 'don_vi_giao_hang'), $MaSoValueX, $this->y - (2 * $x), 'UTF-8');
        
        // Hinh thuc thanh toan
        $page->drawText(Mage::helper('sales')->__('Payment Method'), 29, $this->y - (3 * $x), 'UTF-8');
        if (!$order->getIsVirtual()) {
            $paymentMethod = $order->getPayment()->getMethodInstance()->getTitle();
        }
        foreach (Mage::helper('core/string')->str_split($paymentMethod, 80, true, true) as $_value) {
            $page->drawText(strip_tags(trim($_value)), $MaSoValueX, $this->y - (3 * $x), 'UTF-8');
        }

        // Ma so Don hang
        $page->drawText(Mage::helper('fhsinvoice')->__('Order Code'), 29, $this->y, 'UTF-8');
        $this->_setFontBold($page, 18);
        $page->drawText($order->getIncrementId(), $MaSoValueX, $this->y, 'UTF-8');
        $this->_setFontRegular($page, 12);

        // Phuong thuc giao hang
        $page->drawText(Mage::helper('fhsinvoice')->__('Delivery method'), 29, $this->y - (4 * $x), 'UTF-8');
        if (!$order->getIsVirtual()) {
            $shippingMethod = $order->getShippingDescription();
        }
        foreach (Mage::helper('core/string')->str_split($shippingMethod, 80, true, true) as $_value) {
            $page->drawText(strip_tags(trim($_value)), $MaSoValueX, $this->y - (4 * $x), 'UTF-8');
        }

        // Ngay giao hang
        $page->drawText(Mage::helper('fhsinvoice')->__('Date Shipped'), 29, $this->y - (5 * $x), 'UTF-8');
        $page->drawText(Mage::helper('core')->formatDate($order->getDate(), 'medium', false), $MaSoValueX, $this->y - (5 * $x), 'UTF-8');
        
        $this->y = $this->y - (5 * $x) - 6;
    }
    
    /**
     * Phan thong tin nguoi thanh toan va nguoi nhan 
     * @param type $page
     * @param type $invoice
     * @param type $order
     */
    public function drawHeaderinfo($page, $invoice, $order) {
        $this->drawMasoNgaygiaohang($page, $invoice, $order);
        $this->drawShippingAddress($order, $page);
        $this->drawThongtinXuathd($page, $order);
    }

    /**
     * Phan chu ki cua khach hang va nguoi giao
     * @param type $page
     */
    public function drawFooter($page) {        
        /**
         * Khach hang va nguoi giao ky nhan
         * 140 la tong height cua footer
         */
        if($this->y - 200 < 15){
            $page = $this->newPage();            
        }
        $this->_setFontBold($page, 11);
        $page->drawRectangle(25, $this->y, $page->getWidth() - 25, $this->y -= 20, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->drawText(Mage::helper('fhsinvoice')->__('Signature customers'), 110, $this->y + 4, 'UTF-8');
        $page->drawText(Mage::helper('fhsinvoice')->__('E-Commerce Department'), 380, $this->y + 4, 'UTF-8');
        $page->drawText(Mage::helper('fhsinvoice')->__('Signature delivery'), 395, $this->y  -70, 'UTF-8');
        //Chu Ky        
        $page->drawRectangle(25, $this->y, $page->getWidth() - 25, $this->y -= 140, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        
        //Cam on        
        $page->drawRectangle(25, $this->y, $page->getWidth() - 25, $this->y-= 20, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->drawLine(300, $this->y + 180, 300, $this->y + 20);
        //$page->drawLine(300, $this->y + 85, 570, $this->y + 85);
        $page->drawLine(300, $this->y + 105, 570, $this->y + 105);

        //  Add logo        
        $image = Mage::getSingleton('core/design_package')->getSkinBaseDir(array('_area' => 'frontend', '_package' => 'ma_vanese')) . "/images/logo.png";
        $image = Zend_Pdf_Image::imageWithPath($image);
        $page->drawImage($image, 315, $this->y, 435, $this->y + 20);        
        $page->drawText(Mage::helper('fhsinvoice')->__('Thank you for your purchase at'), 130, $this->y + 4, 'UTF-8');

        /**
         * Luu y Buu dien
         */
        $this->_setFontBold($page, 13);        
        $page->drawText(Mage::helper('fhsinvoice')->__('Attention: Postman, please contact customer prior the delivery'), 100, $this->y - 20, 'UTF-8');
        
        return $page;
    }
    public function drawFooter2($page) {        
        /**
         * Khach hang va nguoi giao ky nhan
         * 140 la tong height cua footer
         */
        if($this->y - 140 < 15){
            $page = $this->newPage();            
        }
        $this->_setFontBold($page, 11);
        $page->drawRectangle(25, $this->y, $page->getWidth() - 25, $this->y -= 20, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->drawText(Mage::helper('fhsinvoice')->__('Signature customers'), 110, $this->y + 4, 'UTF-8');
        $page->drawText(Mage::helper('fhsinvoice')->__('Signature delivery'), 380, $this->y + 4, 'UTF-8');
        //Chu Ky        
        $page->drawRectangle(25, $this->y, $page->getWidth() - 25, $this->y -= 80, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        
        //Cam on        
        $page->drawRectangle(25, $this->y, $page->getWidth() - 25, $this->y-= 20, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->drawLine(300, $this->y + 120, 300, $this->y + 20);

        //  Add logo        
        $image = Mage::getSingleton('core/design_package')->getSkinBaseDir(array('_area' => 'frontend', '_package' => 'ma_vanese')) . "/images/logo.png";
        $image = Zend_Pdf_Image::imageWithPath($image);
        $page->drawImage($image, 315, $this->y, 435, $this->y + 20);        
        $page->drawText(Mage::helper('fhsinvoice')->__('Thank you for your purchase at'), 130, $this->y + 4, 'UTF-8');

        /**
         * Luu y Buu dien
         */
        $this->_setFontBold($page, 13);        
        $page->drawText(Mage::helper('fhsinvoice')->__('Attention: Postman, please contact customer prior the delivery'), 100, $this->y - 20, 'UTF-8');
        
        return $page;
    }
    /**
     * Phan dung cat ra dan vao goi hang
     * @param type $page
     */
    public function drawFooterPage2($page, $order, $invoice) {        
        /**
         * tao trang moi
         */
        $page = $this->newPage(); 
        
        $shippingAddress = $this->_formatAddress($order->getShippingAddress()->format('pdf'));
        $block_height = 16;
        $vat_info = $this->getThongTinXuatHoaDon($order);
        /**
         * Tim chieu cao cua Shipping/billing address
         */
        $addressHeigh = count($shippingAddress) * $block_height;
        $page->setLineWidth(0.5);
        // Ve bang
        $x = 20;
        //Label Nguoi thanh toan/nhan hang
        $page->drawRectangle(25, $this->y , $page->getWidth() - 25, $this->y - $x, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $this->_setFontBold($page, 11);
        $page->drawText(Mage::helper('fhsinvoice')->__('Order Code'), 30, $this->y - 14, 'UTF-8');
        $page->drawText($order->getIncrementId(), 135, $this->y -14, 'UTF-8');
        $page->drawText(Mage::helper('fhsinvoice')->__('Information recipient'), 380, $this->y - 14, 'UTF-8');
        
        // Thong tin chi tiet nguoi thanh toan/nhan hang
        $page->drawRectangle(25, $this->y - $x, $page->getWidth() - 25, $this->y - $x - $addressHeigh, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        
        $page->drawLine(340, $this->y, 340, $this->y - $x - $addressHeigh); // Vertical Line split nguoi thanh toan/nhan hang
        $page->drawLine(132, $this->y, 132, $this->y - $x - $addressHeigh);
        
        $this->_setFontRegular($page, 10);

        // Text Thong tin nguoi nhan hang
        $y_temp = $this->y;
        foreach ($shippingAddress as $value) {
            if ($value !== '') {
                $text = array();
                foreach (Mage::helper('core/string')->str_split($value, 50, true, true) as $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $page->drawText(str_replace('Tel:', '', strip_tags(ltrim($part))), 345, $y_temp - $x - 14, 'UTF-8');
                    $y_temp -= 15;
                }
            }
        }
                        
        //$page->drawRectangle(25, $y_temp -x -15, $page->getWidth() - 25, $y_temp - 45, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
                
        $this->_setFontRegular($page, 10);
        $page->drawText(Mage::helper('sales')->__('Payment Method'), 29, $this->y - $x - 12, 'UTF-8');
        if (!$order->getIsVirtual()) {
            $paymentMethod = $order->getPayment()->getMethodInstance()->getTitle();
        }
        foreach (Mage::helper('core/string')->str_split($paymentMethod, 80, true, true) as $_value) {
            $page->drawText(strip_tags(trim($_value)), 135, $this->y - $x - 12, 'UTF-8');
        }
        $page->drawText(Mage::helper('fhsinvoice')->__('Delivery method'), 29, $this->y - $x - (14*2), 'UTF-8');
        $page->drawLine(25, $this->y - $x - 18, 340, $this->y - $x - 18);
        if (!$order->getIsVirtual()) {
            $shippingMethod = $order->getShippingDescription();
        }
        $j = -13;
        foreach (Mage::helper('core/string')->str_split($shippingMethod, 40, true, true) as $_value) {
            $page->drawText($_value, 135, $y_temp -$j, 'UTF-8');
            $j += 13;
        }
        $page->drawText(Mage::helper('fhsinvoice')->__('Date Shipped'), 29, $this->y - $x - 60, 'UTF-8');
        $page->drawText(Mage::helper('core')->formatDate($order->getDate(), 'medium', false), 135, $this->y - $x -60, 'UTF-8');
        $page->drawLine(25, $this->y - $x - (18*2)-10, 340, $this->y - $x - (18*2) -10);
        
        //draw customer notes
        $page->drawRectangle(25, $y_temp - $x - 10, $page->getWidth() - 25, $y_temp - $x - 50, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->drawLine(132, $y_temp - $x -10, 132, $y_temp - $x - 50);
        $page->drawText(Mage::helper('fhsinvoice')->__('Customer Notes'), 30, $y_temp - $x - 24, 'UTF-8');
        $checkout_note = $this->getValueVATSpecificInfo("checkout_note", $vat_info);
        $padd = 0;
        foreach (Mage::helper('core/string')->str_split($checkout_note, 100, true, true) as $_note) {
            $page->drawText($_note, 138, $y_temp - $x - 25 - $padd, 'UTF-8');
            $padd += 13;
        }        
        $y_temp = $y_temp - $x - 50;
        $page->drawRectangle(25, $y_temp, $page->getWidth() - 25, $y_temp - 35, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        
        $page->drawLine(340, $y_temp, 340, $y_temp - 35);

        $this->_setFontRegular($page, 12);
        $this->_setFontBold($page, 12);
        $page->drawText(Mage::helper('sales')->__('Grand Total'), 50, $y_temp - 20, 'UTF-8');
        
        $this->_setFontRegular($page, 14);
        $this->y = $y_temp - $x -14;
        
        $page->drawText( $order->getOrderCurrency()->format($order->getBaseGrandTotal(), array(), false, false), 420, $y_temp - 20, 'UTF-8');
    }

    public function newPage(array $settings = array()) {
        /* Add new table head */
        $page = $this->_getPdf()->newPage(Zend_Pdf_Page::SIZE_A4);
        $this->_getPdf()->pages[] = $page;
        $this->y = 800;        
        $this->orig_y = $this->y - 5;
        return $page;
    }

}
