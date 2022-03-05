<?php
class Fahasa_PhieuBinhChon_IndexController extends Mage_Core_Controller_Front_Action{
    public function IndexAction() {
        $this->loadLayout();   
        $this->getLayout()->getBlock("head")->setTitle($this->__("Phiếu Bình Chọn"));
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        $breadcrumbs->addCrumb("home", array(
              "label" => $this->__("Home Page"),
              "title" => $this->__("Home Page"),
              "link"  => Mage::getBaseUrl()
                 ));

        $breadcrumbs->addCrumb("phiếu bình chọn", array(
              "label" => $this->__("Phiếu Bình Chọn"),
              "title" => $this->__("Phiếu Bình Chọn")
                 ));

        $this->renderLayout(); 
    }
    public function postAction()
    {
        $post = $this->getRequest();
        $name = $post->getPost("name");
        $cmnd = $post->getPost("cmnd");
        $job = $post->getPost("job");
        $address = $post->getPost("address");
        $phone = $post->getPost("phone");
        $email = $post->getPost("email");
        $bookName = $post->getPost("q");
        $author = $post->getPost("author");
        $publisher = $post->getPost("publisher");
        $number = $post->getPost("number");
        
        $write = Mage::getSingleton("core/resource")->getConnection("core_write");
        $query = "insert into fhs_phieubinhchon "
       . "(name, cmnd_id, job, address, phone, email, book_name, author, publisher,number,date) values "
       . "(:name, :cmnd_id, :job, :address, :phone, :email, :book_name, :author, :publisher,:number, NOW())";
        $binds = array(
            'name'          => $name,
            'cmnd_id'       => $cmnd,
            'job'           => $job,
            'address'       => $address,
            'phone'         => $phone,
            'email'         => $email,
            'book_name'     => $bookName,
            'author'        => $author,
            'publisher'     => $publisher,
            'number'        => $number
                );
        $write->query($query, $binds); 
        echo "Bình chọn thành công. Bạn sẽ được chuyển về trang chủ trong vòng 2s nữa.";
//        $this->_redirectReferer();
        header('Refresh: 2;url='.Mage::getBaseUrl());
    }
}