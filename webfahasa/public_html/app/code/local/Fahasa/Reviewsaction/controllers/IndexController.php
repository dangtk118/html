<?php	
class Fahasa_Reviewsaction_IndexController extends Mage_Core_Controller_Front_Action {
    public function insertAction() {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return;
        }
        $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        $review_id = $_POST['review_id'];
        $type = $_POST['type'];
        $review_count = $this->checkExist($review_id, $email, $type);
        if($review_count == 0){
            $model = Mage::getModel('reviewsaction/reviewsaction');
            $model->setCustomerEmail($email);
                    $model->setType($type);
                    $model->setCreatedAt(now());
                    $model->setReviewId($review_id);
                    $model->save();
        }
        $this->renderBlockLayout();
    }
    
    public function checkExist($review_id,$email,$type) {
        $model = Mage::getModel('reviewsaction/reviewsaction')
                ->getCollection()
                ->addFilter('review_id',$review_id)
                ->addFilter('customer_email',$email)
                ->addFilter('type',$type)
                ->getSize();
        return $model;
    }
    
    public function sorterAction(){
        $this->renderBlockLayout();
    }
    
    public function renderBlockLayout(){
        $productId = $_POST['product_id'];
        $numPager = $_POST['numPager'];
        $product = Mage::getModel('catalog/product')->load($productId);
        Mage::register('product', $product);
        $layout = $this->getLayout();        
        $layout->getUpdate()->load('catalog_product_view');
        $layout->generateXml();
        $layout->generateBlocks();
        $block = $layout->getBlock('product.info.product_additional_data');
        if($numPager){
            $block->getReviewsCollection()->setCurPage($numPager);
        }
        $html = $block->toHtml();        
        $this->getResponse()->setBody($html);
    }
}
