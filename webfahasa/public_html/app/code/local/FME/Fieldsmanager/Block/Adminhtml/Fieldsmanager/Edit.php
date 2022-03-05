<?php 

class FME_Fieldsmanager_Block_Adminhtml_Fieldsmanager_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
    	
        $this->_objectId = 'attribute_id';
        $this->_controller = 'adminhtml_fieldsmanager';
        $this->_blockGroup = 'fieldsmanager';

        parent::__construct();

       

       //iif($this->getRequest()->getParam('popup')) {
       //     $this->_removeButton('back');
       //     $this->_addButton(
       //         'close',
       //         array(
       //             'label'     => Mage::helper('catalog')->__('Close Window'),
       //             'class'     => 'cancel',
       //             'onclick'   => 'window.close()',
       //             'level'     => -1
       //         )
       //     );
       // } else {
       //     $this->_addButton(
       //         'save_and_edit_button',
       //         array(
       //             'label'     => Mage::helper('catalog')->__('Save and Continue Edit'),
       //             'onclick'   => 'saveAndContinueEdit()',
       //             'class'     => 'save'
       //         ),
       //         100
       //     );
       // }
	$this->_updateButton('save', 'label', Mage::helper('catalog')->__('Save Field'));
        //$this->_updateButton('save', 'onclick', 'saveAttribute()');

        if (! Mage::registry('fieldsmanager_data')->getIsUserDefined()) {
            $this->_removeButton('delete');
        } else {
            $this->_updateButton('delete', 'label', Mage::helper('catalog')->__('Delete Field'));
	    $this->_updateButton('delete', 'onclick', 'confirmDelete()');
		$this->_formScripts[] = "
		    function confirmDelete(){
			if (confirm('Click Ok to Delete'))
			{
				editForm.submit('".$this->getUrl('*/*/delete/attribute_id/'.$this->getRequest()->getParam('attribute_id'))."');
			}
		    }
		";
        }
    }

    public function getHeaderText()
    {
    	
        if (Mage::registry('fieldsmanager_data')->getId()) {
		//$frontendLabel = Mage::registry('entity_attribute')->getFrontendLabel();
		//if (is_array($frontendLabel)) {
		//    $frontendLabel = $frontendLabel[0];
		//}
            return Mage::helper('fieldsmanager')->__('Edit Field "%s"', $this->htmlEscape(Mage::registry('fieldsmanager_data')->getFrontendLabel()));
        }
        else {
            return Mage::helper('fieldsmanager')->__('New Field');
        }
       
    }
	
    public function getValidationUrl()
    {
        return $this->getUrl('*/*/validate', array('_current'=>true));
    }

    public function getSaveUrl()
    {
        return $this->getUrl('*/'.$this->_controller.'/save', array('_current'=>true, 'back'=>null));
    }
} 