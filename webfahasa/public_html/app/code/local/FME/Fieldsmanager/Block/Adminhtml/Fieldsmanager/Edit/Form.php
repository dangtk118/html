<?php 

class FME_Fieldsmanager_Block_Adminhtml_Fieldsmanager_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
                                     
                                           'id' => 'edit_form',
                                           'action' => $this->getUrl('*/*/save', array('attribute_id' => $this->getRequest()->getParam('id'))),
                                           'method' => 'post'
                                            //'enctype' => 'multipart/form-data'
                                    )
        );
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
    
     public function isAjax()
    {
        return Mage::app()->getRequest()->isXmlHttpRequest() || Mage::app()->getRequest()->getParam('isAjax');
    }
	
	 public function getProductsJson()
    {
        $products = $this->getCategory()->getProductsPosition();
        if (!empty($products)) {
            return Mage::helper('core')->jsonEncode($products);
        }
        return '{}';
    }
	
	 public function getRefreshPathUrl(array $args = array())
    {
        $params = array('_current'=>true);
        $params = array_merge($params, $args);
        return $this->getUrl('*/*/refreshPath', $params);
    }

} 