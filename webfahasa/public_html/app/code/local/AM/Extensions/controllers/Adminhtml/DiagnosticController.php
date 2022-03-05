<?php
/**
 * @category    AM
 * @package     AM_Extensions
 * @author      FireGento Team <team@firegento.com>
 * @copyright   2013 FireGento Team (http://firegento.com). All rights served.
 * @license     http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */
class AM_Extensions_Adminhtml_DiagnosticController extends Mage_Adminhtml_Controller_Action{
    protected function _initAction(){
        $this->loadLayout();
        $this->_setActiveMenu('am');
        $this->_title(Mage::helper('amext')->__('AM Extensions'));
    }

    public function checkRewritesAction(){
        $this->_initAction();
        $this->_title(Mage::helper('amext')->__('Check Rewrites'));
        $this->renderLayout();
    }
}