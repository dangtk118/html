<?php
/******************************************************
 * @package Ves Megamenu module for Magento 1.4.x.x and Magento 1.7.x.x
 * @version 1.0.0.1
 * @author http://landofcoder.com
 * @copyright	Copyright (C) December 2010 LandOfCoder.com <@emai:landofcoder@gmail.com>.All rights reserved.
 * @license		GNU General Public License version 2
*******************************************************/
?>
<?php
class Ves_Verticalmenu_Block_Adminhtml_Widget_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    var $data = null;
    var $form = null;
    var $widget_selected = "";
    public function __construct()
    {
	    $this->_blockGroup  = 'ves_verticalmenu';
        $this->_objectId    = 'ves_verticalmenu_id';
        $this->_controller  = 'adminhtml_widget';

        $this->setTemplate('ves_verticalmenu/widget/edit.phtml');
        $mediaHelper = Mage::helper('ves_verticalmenu/media');
        $mediaHelper->loadMedia();
        
        $wtype = $this->getRequest()->getParam('wtype');
        $widget_params = null; 
        
        $this->data = Mage::registry('current_widget');
        if( $this->data->getId() ){
            $widget_params =  $this->data->getParams();
            $widget_params =  unserialize(base64_decode($widget_params));

        }

        if( $wtype ) {
            $this->widget_selected =  trim(strtolower($wtype));
            $this->form = Mage::helper('ves_verticalmenu')->getForm( $this->widget_selected, $widget_params);
        }
    }
    public function getWidgetSelected(){
        return $this->widget_selected;
    }
    public function getDataForm(){
        return $this->form;
    }
    public function getSampleData(){
        return $this->data;
    }
    public function getWidgetAction(){
        return $this->getUrl('*/adminhtml_verticalmenu/savewidget');
    }
    /**
     * get list of supported widget types.
     */
    public function getTypes(){

        return array(
            'html'              => Mage::helper("ves_verticalmenu")->__( 'HTML' ),
            'category_list'     => Mage::helper("ves_verticalmenu")->__( 'Categories list' ),
            'product_category'  => Mage::helper("ves_verticalmenu")->__( 'Products category' ),
            'product_list'      => Mage::helper("ves_verticalmenu")->__( 'Products list' ),
            'product'           => Mage::helper("ves_verticalmenu")->__( 'Product' ),
            'static_block'      => Mage::helper("ves_verticalmenu")->__( 'Static block' ),
            'video_code'        => Mage::helper("ves_verticalmenu")->__( 'Video code' ),
            'image'             => Mage::helper("ves_verticalmenu")->__( 'Image' ),
            'feed'              => Mage::helper("ves_verticalmenu")->__( 'Feed' ),
            'ves_blog'          => Mage::helper("ves_verticalmenu")->__( 'Last Venus Blog' ),
            'ves_brand'         => Mage::helper("ves_verticalmenu")->__( 'Shop By Brands' )
        );
    }
}
?>