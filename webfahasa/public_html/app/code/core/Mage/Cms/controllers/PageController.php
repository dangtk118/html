<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Cms
 * @copyright  Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * CMS Page controller
 *
 * @category   Mage
 * @package    Mage_Cms
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Cms_PageController extends Mage_Core_Controller_Front_Action
{
    /**
     * View CMS page action
     *
     */
    public function viewAction()
    {
	Mage::dispatchEvent('share_screen_image', array('event'=>($this->getRequest()->getParam('event', ''))
	    , 'id'=>($this->getRequest()->getParam('id', ''))
	    , 'text'=>($this->getRequest()->getParam('text', ''))
	    , 'x'=>($this->getRequest()->getParam('x', 20))
	    , 'y'=>($this->getRequest()->getParam('y', 20))
	    , 'size'=>($this->getRequest()->getParam('size', 10))
	    , 'bold'=>($this->getRequest()->getParam('bold', 0))
	    , 'cred'=>($this->getRequest()->getParam('cr', 0))
	    , 'cgreen'=>($this->getRequest()->getParam('cg', 0))
	    , 'cblue'=>($this->getRequest()->getParam('cb', 0))
	    ));
        $pageId = $this->getRequest()
            ->getParam('page_id', $this->getRequest()->getParam('id', false));
        if (!Mage::helper('cms/page')->renderPage($this, $pageId)) {
            $this->_forward('noRoute');
        }
    }
}
