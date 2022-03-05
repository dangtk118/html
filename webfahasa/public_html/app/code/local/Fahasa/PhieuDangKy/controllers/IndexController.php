<?php
class Fahasa_PhieuDangKy_IndexController extends Mage_Core_Controller_Front_Action{
    public function IndexAction() {
        $this->loadLayout();   
        $this->getLayout()->getBlock("head")->setTitle($this->__("Phiếu Đăng ký"));
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        $breadcrumbs->addCrumb("home", array(
              "label" => $this->__("Home Page"),
              "title" => $this->__("Home Page"),
              "link"  => Mage::getBaseUrl()
                 ));

        $breadcrumbs->addCrumb("Phiếu Đăng ký", array(
              "label" => $this->__("Phiếu Đăng ký"),
              "title" => $this->__("Phiếu Đăng ký")
                 ));

        $this->renderLayout(); 
    }
    public function postAction()
    {
        $post = $this->getRequest();
        $name = $post->getPost("name");
        $phone = $post->getPost("phone");
        $email = $post->getPost("email");
        $wherefrom = $post->getPost("wherefrom");
        if($post->getPost("note")){
            $note = $post->getPost("note");
        }  else {
            $note = 'null';
        }
        
        $write = Mage::getSingleton("core/resource")->getConnection("core_write");
        $query = "insert into fhs_phieudangky "
       . "(name, phone, email, wherefrom,note, date) values "
       . "(:name, :phone, :email,:wherefrom,:note, NOW())";
        $binds = array(
            'name'          => $name,
            'phone'         => $phone,
            'email'         => $email,
            'wherefrom'     => $wherefrom,
            'note'          => $note
                );
        $write->query($query, $binds); 
        echo "Đăng ký thành công. Bạn sẽ được chuyển về trang chủ trong vòng 2s nữa.";
//        $this->_redirectReferer();
        header('Refresh: 2;url='.Mage::getBaseUrl().'hoisach-online-2016');
    }
}