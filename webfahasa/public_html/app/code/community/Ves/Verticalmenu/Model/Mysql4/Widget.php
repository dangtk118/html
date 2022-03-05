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
class Ves_Verticalmenu_Model_Mysql4_Widget extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the verticalmenu_id refers to the key field in your database table.
        $this->_init('ves_verticalmenu/verticalmenu_widget', 'id');
    }
}