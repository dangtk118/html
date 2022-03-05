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

require_once BP.DS.'app'.DS.'code'.DS.'core'.DS.'Mage'.DS.'Catalog'.DS.'Model'.DS.'Entity'.DS.'Attribute.php';
class FME_Fieldsmanager_Model_Entity_Attribute extends Mage_Catalog_Model_Entity_Attribute
{
    protected function _beforeSave()
    {
        if (!Mage::getSingleton('adminhtml/session')->getIsForFME() || Mage::getSingleton('adminhtml/session')->getIsForFME()==false){
            if ($this->_getResource()->isUsedBySuperProducts($this)) {
               throw Mage::exception('Mage_Eav', Mage::helper('eav')->__('This attribute is used in configurable products'));
            }
            $this->setData('modulePrefix', self::MODULE_NAME);
            return parent::_beforeSave();
            
        }
    }
}
