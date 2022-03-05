<?php

class Mage_Customer_Block_Adminhtml_System_BtnAction extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('searchsphinx/system/btn_action.phtml');
        }
        return $this;
    }

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(array(
            'button_label' => $this->_getBtnLabel($originalData),
            'html_id'      => $element->getHtmlId(),
            'ajax_url'     => Mage::getSingleton('adminhtml/url')->getUrl('customer/adminhtml_system_action/'.$originalData['button_action'])
        ));

        return $this->_toHtml();
    }

    protected function _getBtnLabel($originalData)
    {
        $label = $originalData['button_label'];

        switch ($originalData['button_action']) {
            case 'stopstart':
                break;
        }

        return Mage::helper('fahasa_customer')->__($label);
    }
}
