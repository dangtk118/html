<?php

class Fahasa_Customerregister_Model_System_Config_Source_View {

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {
        return array(
            array('value' => 0, 'label' => Mage::helper('adminhtml')->__('Coupon code')),
            array('value' => 1, 'label' => Mage::helper('adminhtml')->__('Fpoint'))
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray() {
        return array(
            0 => Mage::helper('adminhtml')->__('Coupon code'),
            1 => Mage::helper('adminhtml')->__('Fpoint'),
        );
    }

}

?>