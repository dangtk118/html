<?php
/**
 * @category    AM
 * @package     AM_Extensions
 * @author      FireGento Team <team@firegento.com>
 * @copyright   2013 FireGento Team (http://firegento.com). All rights served.
 * @license     http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */
class AM_Extensions_Block_Adminhtml_Diagnostic_CheckRewrites extends Mage_Adminhtml_Block_Widget_Grid_Container{
    public function __construct(){
        $this->_blockGroup = 'amext';
        $this->_controller = 'adminhtml_diagnostic_checkRewrites';
        $this->_headerText = $this->__('Check Rewrites');
        parent::__construct();
        $this->_removeButton('add');
    }
}