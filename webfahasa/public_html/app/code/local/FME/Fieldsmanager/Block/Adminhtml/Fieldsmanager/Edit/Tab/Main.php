<?php 
/*////////////////////////////////////////////////////////////////////////////////
 \\\\\\\\\\\\\\\\\\\\\\\\\  FME Fieldsmanager extension  \\\\\\\\\\\\\\\\\\\\\\\\\
 /////////////////////////////////////////////////////////////////////////////////
 \\\\\\\\\\\\\\\\\\\\\\\\\ NOTICE OF LICENSE\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 ///////                                                                   ///////
 \\\\\\\ This source file is subject to the Open Software License (OSL 3.0)\\\\\\\
 ///////   that is bundled with this package in the file LICENSE.txt.      ///////
 \\\\\\\   It is also available through the world-wide-web at this URL:    \\\\\\\
 ///////          http://opensource.org/licenses/osl-3.0.php               ///////
 \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 ///////                      * @category   FME                            ///////
 \\\\\\\                      * @package    FME_Fieldsmanager              \\\\\\\
 ///////    * @author     Malik Tahir Mehmood <malik.tahir786@gmail.com>   ///////
 \\\\\\\                                                                   \\\\\\\
 /////////////////////////////////////////////////////////////////////////////////
 \\* @copyright  Copyright 2010 © free-magentoextensions.com All right reserved\\\
 /////////////////////////////////////////////////////////////////////////////////
 */

class FME_Fieldsmanager_Block_Adminhtml_Fieldsmanager_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form
{
     public function __construct()
    {
      parent::__construct();
	
      $this->setUseAjax(true);
     
    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('fieldsmanager_form', array('legend'=>Mage::helper('fieldsmanager')->__('Fields Properties')));
        $data=Mage::registry('fieldsmanager_data')->getData();
        if (Mage::registry('fieldsmanager_data')->getId()) {
            $fieldset->addField('attribute_id', 'hidden', array(
                'name' => 'attribute_id',
            ));
        }

        $this->_addElementTypes($fieldset);

        $yesno = Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray();

        $fieldset->addField('attribute_code', 'text', array(
            'name'  => 'attribute_code',
            'label' => Mage::helper('catalog')->__('Attribute Code'),
            'title' => Mage::helper('catalog')->__('Attribute Code'),
            'note'  => Mage::helper('catalog')->__('Must be unique and without spaces'),
            'class' => 'validate-code',
            'required' => true,
        ));

        
        $response = new Varien_Object();
        $response->setTypes(array());
        $additionalTypes=array();
        $additionalTypes=FME_Fieldsmanager_Model_Type::toOptionArray();
        //Mage::dispatchEvent('adminhtml_product_attribute_types', array('response'=>$response));
        $_disabledTypes = array();
        $_hiddenFields = array();
        foreach ($response->getTypes() as $type) {
            $additionalTypes[] = $type;
            if (isset($type['hide_fields'])) {
                $_hiddenFields[$type['value']] = $type['hide_fields'];
            }
            if (isset($type['disabled_types'])) {
                $_disabledTypes[$type['value']] = $type['disabled_types'];
            }
        }
        Mage::register('attribute_type_hidden_fields', $_hiddenFields);
        Mage::register('attribute_type_disabled_types', $_disabledTypes);


        $fieldset->addField('frontend_input', 'select', array(
            'name' => 'frontend_input',
            'label' => Mage::helper('catalog')->__('Input Type'),
            'title' => Mage::helper('catalog')->__('Input Type'),
            'value' => 'text',
            'values'=> $additionalTypes,
        ));
        
        $fieldset->addField('frontend_class', 'select', array(
            'name'  => 'frontend_class',
            'label' => Mage::helper('catalog')->__('Input Validation'),
            'title' => Mage::helper('catalog')->__('Input Validation'),
            'values'=> FME_Fieldsmanager_Model_Type::toValidateArray(),
        ));

        $fieldset->addField('is_filterable', 'select', array(
            'name'  => 'is_filterable',
            'label' => Mage::helper('catalog')->__('Field Placement'),
            'title' => Mage::helper('catalog')->__('Field Placement'),
            'values'=> FME_Fieldsmanager_Model_Type::toPositionArray()
        ));

        $fieldset->addField('position', 'text', array(
            'name'  => 'position',
            'label' => Mage::helper('catalog')->__('Position'),
            'title' => Mage::helper('catalog')->__('Position'),
            'note' => Mage::helper('catalog')->__('Will Use this value to sort the fields'),
            'class' => 'validate-digits',
        ));
        
        
        $fieldset->addField('entity_type_id', 'hidden', array(
            'name' => 'entity_type_id',
            'value' => Mage::getModel('eav/entity')->setType('fme_fieldsmanager')->getTypeId()
        ));
        
        

        
        $fieldset->addField('default_value_text', 'text', array(
            'name' => 'default_value_text',
            'label' => Mage::helper('catalog')->__('Default value'),
            'title' => Mage::helper('catalog')->__('Default value'),
            'value' => Mage::registry('fieldsmanager_data')->getDefaultValue(),
        ));

        $fieldset->addField('default_value_yesno', 'select', array(
            'name' => 'default_value_yesno',
            'label' => Mage::helper('catalog')->__('Default value'),
            'title' => Mage::helper('catalog')->__('Default value'),
            'values' => $yesno,
            'value' => Mage::registry('fieldsmanager_data')->getDefaultValue(),
        ));

        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $dateElement = $fieldset->addField('default_value_date', 'date', array(
            'name'   => 'default_value_date',
            'label'  => Mage::helper('catalog')->__('Default value'),
            'title'  => Mage::helper('catalog')->__('Default value'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'value'  => Mage::registry('fieldsmanager_data')->getDefaultValue(),
            'class' => 'validate-date',
            'format' => $dateFormatIso
        ));
        $dateElement->setValue(Mage::registry('fieldsmanager_data')->getDefaultValue(),$dateFormatIso);

        $fieldset->addField('default_value_textarea', 'textarea', array(
            'name' => 'default_value_textarea',
            'label' => Mage::helper('catalog')->__('Default value'),
            'title' => Mage::helper('catalog')->__('Default value'),
            'value' => Mage::registry('fieldsmanager_data')->getDefaultValue(),
        ));
        
        $fieldset->addField('default_value_message', 'textarea', array(
            'name' => 'default_value_message',
            'label' => Mage::helper('catalog')->__('Enter Your Message'),
            'title' => Mage::helper('catalog')->__('Enter Your Message'),
            'value' => Mage::registry('fieldsmanager_data')->getDefaultValue(),
        ));
      
        $fieldset->addField('is_used_for_price_rules', 'select', array(
            'name'  => 'is_used_for_price_rules',
            'label' => Mage::helper('catalog')->__('Add default Empty option'),
            'title' => Mage::helper('catalog')->__('Add default Empty option'),
            'note'  => Mage::helper('catalog')->__('If enabled then the drop down will add an empty option at the top'),
            'value' => 0,
            'values' => $yesno,
        ));
        
     
        
        $fieldset->addField('is_searchable', 'select', array(
            'name'  => 'is_searchable',
           'label' => Mage::helper('catalog')->__('Select for the Step'),
            'title' => Mage::helper('catalog')->__('Select for the Step'),
            'note'  => Mage::helper('catalog')->__('Add the Field to Checkout process or Customer Registration Form'),
            'values'=> FME_Fieldsmanager_Model_Type::toPlacementArray(),
        ));
        

 
        $fieldset->addField('is_required', 'select', array(
            'name' => 'is_required',
            'label' => Mage::helper('catalog')->__('Values Required'),
            'title' => Mage::helper('catalog')->__('Values Required'),
            'values' => $yesno,
        ));

        $fieldset->addField('fme_customer_account', 'select', array(
            'name' => 'fme_customer_account',
            'label' => Mage::helper('catalog')->__("Add to Customer's Account"),
            'title' => Mage::helper('catalog')->__("Add to Customer's Account"),
            'values'=> FME_Fieldsmanager_Model_Type::toCustomerArray(),
        ));
	  $fieldset->addField('fme_email', 'select', array(
            'name' => 'fme_email',
            'label' => Mage::helper('catalog')->__("Add to Emails"),
            'title' => Mage::helper('catalog')->__("Add to Emails"),
            'values' => $yesno,
        ));

	  $fieldset->addField('fme_pdf', 'select', array(
            'name' => 'fme_pdf',
            'label' => Mage::helper('catalog')->__('Add To Pdf'),
            'title' => Mage::helper('catalog')->__('Add To Pdf'),
            'values'=> FME_Fieldsmanager_Model_Type::toPdfArray(),
	   ));
	  
	  $fieldset->addField('store_ids', 'multiselect', array(
                'name'      => 'store_ids[]',
                'label'     => $this->__('Store View'),
                'title'     => $this->__('Store View'),
                'required'  => true,
                'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
            ));
	  $groups = Mage::getResourceModel('customer/group_collection')
            //->addFieldToSelect('customer_group_id')
            ->load()
            ->toOptionArray();
	  
         $fieldset->addField('custmoer_group', 'multiselect', array(
                'name'      => 'custmoer_group[]',
                'label'     => $this->__('Customer Group'),
                'title'     => $this->__('Customer Group'),
                'required'  => true,
                'values'    => $groups,
            ));
        
        $fieldset->addField('is_global', 'hidden', array(
            'name'  => 'is_global',
            'values'=> 0
        ), 'attribute_code');
        
        $fieldset->addField('is_user_defined', 'hidden', array(
            'name' => 'is_user_defined',
            'value' => 1
        ));

        $fieldset->addField('is_visible_in_advanced_search', 'hidden', array(
            'name' => 'is_visible_in_advanced_search',
            'value' => 0
        ));

        $fieldset->addField('is_comparable', 'hidden', array(
            'name' => 'is_comparable',
            'value' => 0
        ));


        $fieldset->addField('is_used_for_promo_rules', 'hidden', array(
            'name' => 'is_used_for_promo_rules',
            'value' => 0,
        ));

        $fieldset->addField('is_wysiwyg_enabled', 'hidden', array(
            'name' => 'is_wysiwyg_enabled',
            'values' => 0,
        ));

        $htmlAllowed = $fieldset->addField('is_html_allowed_on_front', 'hidden', array(
            'name' => 'is_html_allowed_on_front',
            'values' => 0,
        ));

        $fieldset->addField('is_visible_on_front', 'hidden', array(
            'name'      => 'is_visible_on_front',
            'values'    => 0,
        ));

        $fieldset->addField('used_in_product_listing', 'hidden', array(
            'name'      => 'used_in_product_listing',
            'value'    => 0,
        ));
        $fieldset->addField('used_for_sort_by', 'hidden', array(
            'name'      => 'used_for_sort_by',
            'values'    => 0,
        ));

        
        if (Mage::registry('fieldsmanager_data')->getId()) {
            $form->getElement('attribute_code')->setDisabled(1);
            $form->getElement('frontend_input')->setDisabled(1);

            if (isset($disableAttributeFields[Mage::registry('fieldsmanager_data')->getAttributeCode()])) {
                foreach ($disableAttributeFields[Mage::registry('fieldsmanager_data')->getAttributeCode()] as $field) {
                    $form->getElement($field)->setDisabled(1);
                }
            }
        }

        $form->addValues(Mage::registry('fieldsmanager_data')->getData());
        
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _getAdditionalElementTypes()
    {
        return array(
            'apply' => Mage::getConfig()->getBlockClassName('adminhtml/catalog_product_helper_form_apply')
        );
    }

} 