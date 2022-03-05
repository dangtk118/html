<?php

class Fahasa_Event_IndexController extends Mage_Core_Controller_Front_Action {
    
    /// NOTE: hard-coded url , need to refactor code
    const NODE_SERVER_URL = "http://localhost:3000";
    
    public function IndexAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle($this->__("Event World cup"));
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        $breadcrumbs->addCrumb("home", array(
            "label" => $this->__("Home Page"),
            "title" => $this->__("Home Page"),
            "link" => Mage::getBaseUrl()
        ));
     
        $breadcrumbs->addCrumb("Event", array(
            "label" => $this->__("Event World cup"),
            "title" => $this->__("Event World cup")
        ));
        
        $this->renderLayout();
    }

    public function postAction() {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $email = $customer->getEmail();
            Mage::log("**Begin post action event with Email: $email", null, 'events.log');
            $helper = Mage::helper('event');
            $post = $this->getRequest('POST');
            $match = $post->getPost("match");
            $team = $post->getPost("team");
            $couponCode = $helper->couponCodeSurvey($email, $match, $team);

            if ($couponCode) {
                $insert = $helper->insertMatchTeam($match, $team, $email, $couponCode);
                if ($insert) {
                    $helper->sendSurveyCouponEmail($couponCode, $email, $match, $team);
                }
            } else {
                Mage::log("**Error -- couponCode: $couponCode => Post action event with Email: $email", null, 'events.log');
            }
        }
    }

    /*

     * get gift from event birthdaycake
     */
    public function postRandomAction() {
        $helper = Mage::helper('event');
        $eventId = $this->getRequest()->getPost("eventId");
        $result = $helper->randomGift($eventId,"web");
        $gift = null;
        if ($result["success"]){
            $gift = array(
                "success" => true,
                "attendCode" => $result["attendCode"],
                "giftImage" => $result["giftImage"],
                "sharedLink" => $result["sharedLink"],
                "giftDescription" => $result["giftDescription"],
                "giftIndex" => $result["giftIndex"]
            );
        }
        else{
            $gift = array();
            if (($result["giftImage"])){
                $gift = array(
                    "success" => false,
                    "message" => $result["message"],
                    "giftImage" => $result["giftImage"]
                );
            }
            else{
                $gift = array(
                    "success" => false,
                    "message" => $result["message"]
                );
            }
        }
        
        return $this->getResponse()->setBody(json_encode($gift));
    }
    
    
    /*

     * check user played
     */
    public function checkAction() {
        $event_id = "birthdaycake";
        $helper = Mage::helper('event');
        $gift = $helper->getDataPlayed($event_id);

        return $this->getResponse()->setBody($gift);
    }
    
    
    /*

     * check user played game with game active right now
     */

    public function checkGameAction() {
        $helper = Mage::helper('event');
        $event_id = $helper->getEventId("web");
        $params["eventId"] = $event_id;
        if (!$event_id) {
            return $this->getResponse()->setBody(json_encode($params));
        }

        $gift = "";
        $result = $helper->getDataPlayed($event_id);
        if ($result["success"] && $result["playLimit"] == 1 && $result["attended"]) {
            $gifts = $result["gifts"];
            $gift = $gifts[0];
        }

        $params["gift"] = $gift;

        return $this->getResponse()->setBody(json_encode($params));
    }

    public function checkVoteAction(){
        $helper = Mage::helper('event');
        $productId =  $this->getRequest()->getPost("productId");
        $result = $helper->checkProductVote($productId);
        
        return $this->getResponse()->setBody(json_encode($result));
    }
    
    public function postVoteProductAction(){
        $helper = Mage::helper('event');
        $productId = $this->getRequest()->getPost("productId");
        $result = $helper->voteProduct($productId, "web");
        return $this->getResponse()->setBody(json_encode($result));
    }

    public function getTopVotedAction(){
        $helper = Mage::helper('event');
        $catId = $this->getRequest()->getPost("catId");
        $result = $helper->getTopProductVoted($catId);
        $json = json_encode($result);
        return $this->getResponse()->setBody($json);
    }
    
    public function shareLogAction(){
        $helper = Mage::helper('event');
        $eventId = $this->getRequest()->getPost("eventId");
        $sharedLink = $this->getRequest()->getPost("sharedLink");
        $helper->insertShareLog($eventId, $sharedLink, "web");
    }
    
    public function checkGame1Action() {
        $eventId = $this->getRequest()->getPost("eventId");
        $result = null;
        if ($eventId) {
            $helper = Mage::helper("event");
            $result = $helper->getDataPlayed($eventId);
        } else {
            $result = array(
                "success" => false,
                "message" => "EVENT_INVALID"
            );
        }
        $session = Mage::getModel("customer/session");

        $result["isLogin"] = $session->isLoggedIn();
        if ($session->isLoggedIn()) {
            $result["currentFpoint"] = Mage::helper('tryout')->determinetryout();
        }
        return $this->getResponse()->setBody(json_encode($result));
    }

    public function buyTurnAction() {
        $eventId = $this->getRequest()->getPost("eventId");
        $quantity = $this->getRequest()->getPost("quantity");
        $result = Mage::helper("event")->buyEventTurnByFpoint($eventId, $quantity);
        return $this->getResponse()->setBody(json_encode($result));
    }
    
    /*
     *  Get Marathon Top Ten
     */
    public function marathonAction() {
        
        $node_post_url = self::NODE_SERVER_URL. "/event/marathon";
        $customer_id = null;
        
        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $customer_id = $customerData->getId();
        }
        
        $fields = json_encode(array(
            'customer_id' => $customer_id
        ));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $node_post_url);
        //curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $server_output = curl_exec($ch);
        curl_close ($ch);
        
        $this->getResponse()->setHeader('Content-type','application/json',true);
        return $this->getResponse()->setBody($server_output);
    }
    
    /*
     *  Get Marathon 2 - Personal Data
     */
    public function marathon2PersonalDataAction() {
        
        $node_post_url = self::NODE_SERVER_URL. "/event/marathon2/personal";
        $customer_id = null;
        
        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $customer_id = $customerData->getId();
        }
        
        $fields = json_encode(array(
            'customer_id' => $customer_id
        ));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $node_post_url);
        //curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $server_output = curl_exec($ch);
        curl_close ($ch);
        
        $this->getResponse()->setHeader('Content-type','application/json',true);
        return $this->getResponse()->setBody($server_output);
    }
    
    public function shareCmsAction(){
        $helper = Mage::helper('event');
        $eventId = $this->getRequest()->getPost("eventId");
        $sharedLink = $this->getRequest()->getPost("sharedLink");
        $helper->shareCmsPageLog($eventId, $sharedLink, "web");
    }
    
    public function getUsersAttendAction(){
        $helper = Mage::helper("event");
        $eventId = $this->getRequest()->getPost("eventId");
        $result = $helper->getListUserAttend($eventId);
        return $this->getResponse()->setBody(json_encode($result));
    }
    
    public function getGilftIRSAction(){
        $sharedLink = $this->getRequest()->getPost("sharedLink");
        $result = Mage::helper("event/data")->getGilftIRS($sharedLink);
        return $this->getResponse()->setBody(json_encode($result));
    }
    
    public function getEventSourceOptionsAction(){
        $affId = $this->getRequest()->getPost("affId");
        $areaId = $this->getRequest()->getPost("areaId");
        $levelId = $this->getRequest()->getPost("levelId");
        $result = Mage::helper("event/Eventsource")->getEventSourceInfo(true,$affId, false, $areaId, $levelId);
        return $this->getResponse()->setBody(json_encode($result));
    }
    
    public function getEventSourceOptionsPaymentAction(){
        $result = Mage::helper("event/Eventsource")->getEventSourceInfoInCheckout(true);
        return $this->getResponse()->setBody(json_encode($result));
    }
    
    public function saveEventSourceOptionAction(){
        $option_id = $this->getRequest()->getPost("option_id");
        $areaId = $this->getRequest()->getPost("areaId");
        $levelId = $this->getRequest()->getPost("levelId");
        $result = Mage::helper("event/Eventsource")->saveEventSourceOption($option_id, false, $areaId, $levelId);
        return $this->getResponse()->setBody(json_encode($result));
    }
}
