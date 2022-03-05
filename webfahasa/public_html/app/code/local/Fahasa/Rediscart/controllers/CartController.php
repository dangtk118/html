<?php

require_once 'Mage/Checkout/controllers/CartController.php';

//class Fahasa_Rediscart_CartController extends Mage_Core_Controller_Front_Action {
class Fahasa_Rediscart_CartController extends Mage_Checkout_CartController {

//      public function preDispatch()
//    {
//        parent::preDispatch();
//        $cart = $this->_getCart();
//        if ($cart->getQuote()->getIsMultiShipping()) {
//            $cart->getQuote()->setIsMultiShipping(false);
//        }
//        Mage::dispatchEvent(
//                'controller_action_predispatch_' . $this->getFullActionName(), array('controller_action' => $this)
//        );

        //return the original result in case another method is relying on it
//        return $this;
//        Mage_Core_Controller_Front_Action::preDispatch();
//    }
      public function indexAction()
    {
       
        $this->_getSession()->setCartWasUpdated(true);

        Varien_Profiler::start(__METHOD__ . 'cart_display');
        $this
                ->loadLayout()
//                ->_initLayoutMessages('checkout/session')
//                ->_initLayoutMessages('catalog/session')
                ->getLayout()->getBlock('head')->setTitle($this->__('Shopping Cart'));
        $this->renderLayout();
        Varien_Profiler::stop(__METHOD__ . 'cart_display');
    }

    
    /**
     * override Add product to shopping cart action
     */

    public function addAction()
    {
        $time_start = microtime(true);
        Mage::log("\n\nTIME GET ADD CART BEGIN *********  --- " . $time_start, null, "rediscart.log");
        header("Content-type: application/json");
        if ($this->getRequest()->getParam('callback'))
        {
            $ajaxData = array();
            $params = $this->getRequest()->getParams();

            try {
		if(empty($params['qty'])){$params['qty'] = 1;}
		
                if (isset($params['qty']))
                {
                    $filter = new Zend_Filter_LocalizedToNormalized(
                            array('locale' => Mage::app()->getLocale()->getLocaleCode())
                    );
                    $params['qty'] = $filter->filter($params['qty']);
                }
                $productId = (int) $this->getRequest()->getParam('product');

                $time_end = microtime(true);
                $time = $time_end - $time_start;

                Mage::log("TIME GET ADD CART BEFORE CALL HELPER *********  --- " . $time_end . " - " . $time, null, "rediscart.log");
                $redis_cart_result = Mage::helper('rediscart/cart')->addProduct($productId, $params);

                $time_end = microtime(true);
                $time = $time_end - $time_start;

                Mage::log("TIME GET ADD CART CONTROLLER FINISH  *********  --- " . $time_end . " - " . $time, null, "rediscart.log");

                if (!$this->_getSession()->getNoCartRedirect(true))
                {
                    if ($redis_cart_result['success'])
                    {
                        $product = $redis_cart_result['product'];
                        $message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
                        // $this->_getSession()->addSuccess($message);
                        $ajaxData['status'] = 1;
//                        $this->loadLayout();
                        $sidebarCart = "";
                        $mini_cart = "";
                        $toplink = "";

                        $mini_cart = Mage::app()->getLayout()->createBlock('checkout/cart_sidebar')->setTemplate('magentothem/ajaxcartsuper/checkout/cart/topcart.phtml')->toHtml();

                        $time_end = microtime(true);
                        $time = $time_end - $time_start;



                        $time_end = microtime(true);
                        $time = $time_end - $time_start;

                        Mage::log("TIME GET CART MINI  *********  --- " . $time . " -- ", null, "rediscart.log");

                        $time_end = microtime(true);
                        $time = $time_end - $time_start;

                        Mage::log("TIME GET LAYOUTE  *********  --- " . $time, null, "rediscart.log");
                        $pimage = Mage::helper('catalog/image')->init($product, 'image')->resize(155);
                        $ajaxData['sidebar_cart'] = $sidebarCart;
                        $ajaxData['mini_cart'] = $mini_cart;
                        //show or hide cofirmbox when add product to cart
                        if (Mage::getStoreConfig('ajaxcartsuper/ajaxcartsuper_config/show_confirm'))
                        {
                            $ajaxData['product_info'] = Mage::helper('ajaxcartsuper/data')->productHtml($product->getName(), $product->getProductUrl(), $pimage);
                        }
                    }
                    else
                    {
                        $messages = $redis_cart_result['message'];
                        $ajaxData['status'] = 0;
                        $ajaxData['message'] = $messages;
                        $ajaxData['type_product_ajax'] = 0;
                    }
                }
            } catch (Mage_Core_Exception $e) {
                $msg = "";
                if ($this->_getSession()->getUseNotice(true))
                {
                    $msg = $e->getMessage();
                }
                else
                {
                    $messages = array_unique(explode("\n", $e->getMessage()));
                    foreach ($messages as $message)
                    {
                        $msg .= $message . '<br/>';
                    }
                }
                $ajaxData['status'] = 0;
                $ajaxData['message'] = $msg;
                $ajaxData['type_product_ajax'] = 0;
            } catch (Exception $e) {
                $ajaxData['status'] = 0;
                $ajaxData['message'] = $this->__('Cannot add the this product to shopping cart.');
            }

            if ($ajaxData['status'] == 0 && !$ajaxData['message'])
            {
                $ajaxData['message'] = $this->__('Cannot add the this product to shopping cart.');
            }


            $time_end = microtime(true);
            $time = $time_end - $time_start;

            Mage::log("DEBUG TIME RETURN ADD TO CART IN PRODUCT VIEW *********  --- " . $time, null, "rediscart.log");
            $this->getResponse()->setBody($this->getRequest()->getParam('callback') . '(' . Mage::helper('core')->jsonEncode($ajaxData) . ')');
            return;
        }
    }

    public function deleteAllItemsAction()
    {
        $result = Mage::helper('rediscart/cart')->deleteAllItemsInQuote();
        $data = array(
            'success' => $result,
        );

        return $this->getResponse()->setBody(json_encode($data))
                        ->setHeader('Content-Type', 'application/json');
    }

    public function getCartAction()
    {
        $result = Mage::helper('rediscart/cart')->getCartFromRedisWithTotalsInDb();

        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }

    public function deleteCartAction()
    {
        $rq = (array) json_decode($this->getRequest()->getRawBody());
        $product_id = $rq['product_id'];
        $result = Mage::helper('rediscart/cart')->deleteProduct($product_id);

        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }

    public function updateCartAction()
    {
        $rq = (array) json_decode($this->getRequest()->getRawBody());
        $product_id = $rq['product_id'];
        
        $qty = $rq['qty'];
        $result = Mage::helper('rediscart/cart')->updateCartItems($product_id, $qty);
        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }

    public function addCheckedProductAction()
    {
        $rq = (array) json_decode($this->getRequest()->getRawBody());

        $product_id = $rq['product_id'];
        $checked = $rq['is_checked'];
        
        $result = Mage::helper('rediscart/cart')->addCheckedProductIntoQuote($product_id, $checked);


        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }

    public function checkAllProductAction()
    {
        $rq = (array) json_decode($this->getRequest()->getRawBody());

        $checked = $rq['is_checked'];

        $result = Mage::helper('rediscart/cart')->checkAllProductsIntoQuote($checked);


        return $this->getResponse()->setBody(json_encode($result))
                        ->setHeader('Content-Type', 'application/json');
    }

//    public function addWishlistToCartAction()
//    {
//        if (!$this->_validateFormKey())
//        {
//            return $this->_redirect('*/*');
//        }
//        $itemId = (int) $this->getRequest()->getParam('item');
//
//        /* @var $item Mage_Wishlist_Model_Item */
//        $item = Mage::getModel('wishlist/item')->load($itemId);
//        if (!$item->getId())
//        {
//            return $this->_redirect('checkout/cart');
//        }
//
//        $product = Mage::getModel('catalog/product')
//                ->setStoreId(Mage::app()->getStore()->getId())
//                ->load($item->getProductId());
//        $wishlist = Mage::getModel('wishlist/wishlist')->load($item->getWishlistId());
//
//        $session = Mage::getSingleton('wishlist/session');
//
//        $redirectUrl = Mage::getUrl('*/*');
//        try {
//            $params = array(
//                'qty' => 1
//            );
//
//            $redis_cart_result = Mage::helper('rediscart/cart')->addProduct($product, $params);
//
//            if ($redis_cart_result['success'])
//            {
//                $item->delete();
//                $wishlist->save();
//            }
//
//            if (Mage::helper('checkout/cart')->getShouldRedirectToCart())
//            {
//                $redirectUrl = Mage::helper('checkout/cart')->getCartUrl();
//            }
//            else if ($this->_getRefererUrl())
//            {
//                $redirectUrl = $this->_getRefererUrl();
//            }
//        } catch (Mage_Core_Exception $e) {
//            if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_NOT_SALABLE)
//            {
//                $session->addError($this->__('This product(s) is currently out of stock'));
//            }
//            else if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS)
//            {
//                Mage::getSingleton('catalog/session')->addNotice($e->getMessage());
//                $redirectUrl = Mage::getUrl('*/*/configure/', array('id' => $item->getId()));
//            }
//            else
//            {
//                Mage::getSingleton('catalog/session')->addNotice($e->getMessage());
//                $redirectUrl = Mage::getUrl('*/*/configure/', array('id' => $item->getId()));
//            }
//        } catch (Exception $e) {
//            Mage::logException($e);
//            $session->addException($e, $this->__('Cannot add item to shopping cart'));
//        }
//
//        return $this->_redirectUrl($redirectUrl);
//    }

    public function addWishlistToCartAction()
    {
        if (!$this->_validateFormKey())
        {
            return $this->_redirect('*/*');
        }
        $itemId = (int) $this->getRequest()->getParam('item');

        $redis_cart_result = Mage::helper('rediscart/cart')->addWishlistToCart($itemId);

        return $this->_redirect('checkout/cart');
    }
    
    public function addAllWishlistToCartAction()
    {
        if (!$this->_validateFormKey()){
            return $this->_redirect('*/*');
        }

        $redis_cart_result = Mage::helper('rediscart/cart')->addAllWishlistToCart();

	$redirectUrl = 'checkout/cart';
	if(!empty($redis_cart_result['redirectUrl'])){
	    $redirectUrl = $redis_cart_result['redirectUrl'];
	}
	
	return $this->_redirect($redirectUrl);
    }
}
