<?php

class Fahasa_Event_Helper_Data extends Mage_Core_Helper_Abstract {

    function remove_sign($str = '') {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
        $str = preg_replace("/(đ)/", 'd', $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
        $str = preg_replace("/(Đ)/", 'D', $str);
        return $str;
    }

    function getDataPlayed($event_id) {
        
        $success = true;
        $message = null;
        $playLimit = null;
        $gifts = array();
        $attended = false;
        $remainQty = 0;
        $revertTime = null;
        $customerFpoint = 0;
        $multiDay = null; // so ngay lien tiep nhan dc 
        $indexDay = null; // lay so ngay trong tuan 1->7
        $totalFpointReward = 0;

        $eventDetail = $this->getEventDetail($event_id);
        $eventType = $eventDetail['eventType'];
        
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $email = $customer->getEmail();
            $customer_id = $customer->getId();
            $customerFpoint = Mage::helper('tryout')->determinetryout();
            $referCode = $customer->getReferCode();
            if ($referCode) {
                $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
            
               $query = "select ev.play_limit, ev.revert_turn_time, ev.cms_page, ev.data as game_data, ue.attend_code, ue.coupon_code, eg.description, eg.type as gift_type, eg.value, eg.link, "
                        . "ut.revert_qty, ut.revert_times_used, ut.buy_qty, ut.buy_times_used, ut.revert_time, ut.multi_day, ev.revert_turn_point "
                        . "from fahasa_events ev "
                        . "left join fahasa_user_event_log ue "
                        . "on ev.event_id = ue.event_id and ue.customer_id='{$customer_id}' "
                        . "left join  fahasa_event_gift eg on eg.event_id = ue.event_id and ue.attend_code = eg.name  and eg.type is not null "
                        . "left join fahasa_user_event_turn ut on ut.event_id = ev.event_id and ut.customer_id = '{$customer_id}' "
                        . "where ev.event_id='{$event_id}' "
                        . "order by ue.id;";
                $results = $readConnection->fetchAll($query);

                  if (count($results) == 0){
                      $success = false;
                      $message = "EVENT_INVALID";
                  }
                  else {
                    $success = true;
                    $playLimit = $results[0]['play_limit'];
                    $multiDay = $results[0]['multi_day'];

                    if ($results[0]["attend_code"]) {
                        if (count($results) == $playLimit) { 
                            $attended = true;
                        }
                        foreach ($results as $gift) {
                            $sharedLinkTemp = $gift["game_data"]["shareLink"];
                            if ($gift["game_data"]["shareGift"]){
                                $sharedLinkTemp .=  "?eventId=" . $event_id . "&giftId=" . $gift["attend_code"];
                            }
                            $sharedLink = $sharedLinkTemp;

                            $gameData = (array) json_decode($gift["game_data"]);
                            $gifts[] = array(
                                "attendCode" => $gift["attend_code"],
                                "giftImage" => Mage::getBaseUrl('media') . "wysiwyg/game/" . $gift["attend_code"] . ".png",
                                "sharedLink" => $sharedLink,
                                "giftName" => $gift["description"],
                                "giftDescription" => $gameData["receiveGiftMsg"] ?  preg_replace('/(.*)\%s(.*)\%s/i','${1}'. $gift["description"] . '${3}', 
                                    $gameData["receiveGiftMsg"]) : null,
                                "giftType" => $gift['gift_type'],
                                "couponCode" => $gift["coupon_code"],
                                "giftLink" => $gift["link"]
                            );
                            if ($gift['gift_type'] == 'fpoint') {
                                $totalFpointReward += (int) $gift['value'];
                            }
                        }
                        
                        $remainQty = $results[0]['revert_qty'] + $results[0]['buy_qty'] - $results[0]['revert_times_used'] - $results[0]['buy_times_used'];
                        $revertTime = $results[0]['revert_time'];
                        $revertTimeFormat = date("Y-m-d H:i:s", strtotime($revertTime));
                        $curdate =  date("Y-m-d H:i:s", strtotime('+7 hours'));
                      
                        $multiDay = $results[0]['multi_day'] == 0 ? null : $results[0]['multi_day'];
                        $indexDay = $multiDay == null ? null : $this->getIndexOfDay($multiDay,$eventDetail);
                        
                        //return null;
                        if ($revertTime) {
                            $resetFlag = 1; // reset multi_day neu bi miss check 1 ngay
                            $revertTimeFormat2 =  date("Y-m-d", strtotime($revertTime));
                            $curdate2 = date("Y-m-d", strtotime('+7 hours'));
                            $datetime1 = date_create($revertTimeFormat2);
                            $datetime2 = date_create($curdate2);
                            if ($eventType == 'get_fpoint' && ($datetime1 <= $datetime2)) {
                                // tinh khoang cach giua 2 ngay
                                $interval = date_diff($datetime1, $datetime2);
                                $days = $interval->days;
                                // miss check 1 ngay
                                if($days >= 1){
                                    $this->revertMultiDayAndTurn($event_id, $email, $playLimit,$resetFlag, $customer_id);
                                    // "0" => reset nen se bao popup la that bai phai string neu la int thi mobile se tuong la false
                                    $multiDay = "0";
                                    $indexDay = 0;
                                }else{
                                    $resetFlag = 0;
                                    $this->revertMultiDayAndTurn($event_id, $email, $playLimit,$resetFlag, $customer_id);
                                }
                                // ban dau la goi ham revertUserTurn() reset => revertUserTurn se null 
                                //  revertMultiDayAndTurn() reset van se giu lai revertUserTurn not null de so sanh neu user check nhung ko onPress
                                // => se giu ngay check cuoi cung de so sanh 
                                $remainQty = $remainQty >= $playLimit ? $playLimit : $remainQty + $playLimit ;
                                $revertTurnTime = $results[0]['revert_turn_time'];
                                $plusTime = 7 * 3600 + $revertTurnTime;
                                $revertTime = date("Y-m-d H:i:s", strtotime('+' . $plusTime . ' seconds'));
                                
                            } else if ($revertTimeFormat <= $curdate) {
                                // set lai revert turn  khi qua thoi gian revert
                                $this->revertUserTurn($event_id, $email, $playLimit, $customer_id);
                                $remainQty += $playLimit;
                                $revertTime = $this->calculateRevertTime($eventDetail);
                                
                            }
                        } 
                        else{
                            //set for second period because in the first time, we set revert_time is null
                            $revertTime = $this->calculateRevertTime($eventDetail);
                        }
                    }
                    else{
                        //never attend event before -> set data for display UI
                        $remainQty = $results[0]['play_limit'];
                        $revertTime = $this->calculateRevertTime($eventDetail);
                    }
                }
            } else {
                $success = false;
                $message = "REQUIRE_CONFIRM_TELEPHONE";
            }
        
        } else {
            // chua dang nhap
            $success = false;
            $message = "guest";
        }
        
        return array(
            "success" => $success,
            "message" => $message,
            "playLimit" => $playLimit,
            "gifts" => $gifts,
            "attended" => $attended,
            "remainQty" => $remainQty,
            "revertTime" => $revertTime,
            "customerFpoint" => $customerFpoint,
            "totalReward" => $totalFpointReward,
            'multiDay' => $multiDay,
            'indexDay' => $indexDay,
            
        );
    }

    function getFootballMatchs() {
        $data = array();

        for ($i == 1; $i < 5; $i ++) {
            // code system config
            $code = "event/match" . $i;
            if (Mage::getStoreConfig($code . '/enable')) {
                $match = array();
                $match["id"] = "match" . $i;
                $match['title'] = Mage::getStoreConfig($code . '/title');
                $match['team1'] = trim(Mage::getStoreConfig($code . '/team1'));
                $match['flagteam1'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "event/" . Mage::getStoreConfig($code . '/flagteam1');
                $match['team2'] = Mage::getStoreConfig($code . '/team2');
                $match['flagteam2'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "event/" . Mage::getStoreConfig($code . '/flagteam2');
                $data[] = $match;
            }
        }
        return $data;
    }

    function insertMatchTeam($match, $team, $email, $couponCode) {
        try {
            $teamName = $this->remove_sign(preg_replace("/\s+/", "", strtolower($team)));

            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $query = 'SELECT * FROM fahasa_user_event_log where email = :email and event_id = :event_id';
            $varsread = array(
                "event_id" => "worldcup_" . $match,
                "email" => $email
            );
            $results = $readConnection->fetchOne($query, $varsread);

            if ($results == FALSE) {
                // chua du doan
                $write = Mage::getSingleton("core/resource")->getConnection("core_write");
                $query = "insert into fahasa_user_event_log (event_id, email, created_at,created_by, attend_code, coupon_code) "
                        . "values(:event_id, :email, :created_at, :created_by, :attend_code, :coupon_code)";
                $varswrite = array(
                    "event_id" => "worldcup_" . $match,
                    "email" => $email,
                    "created_at" => now(),
                    "created_by" => "web",
                    "attend_code" => $teamName,
                    "coupon_code" => $couponCode
                );

                $write->query($query, $varswrite);
                return TRUE;
            } else {
                // co data// da du doan
                Mage::log("**error: du doan lan 2 - can't insert for Coupon Code: $couponCode when user join game with Email: $email, match:$match, team:$team", null, 'events.log');
                return FALSE;
            }
        } catch (Exception $exc) {
            Mage::log("**error: can't insert for Coupon Code: $couponCode when user join game with Email: $email, match:$match, team:$team", null, 'events.log');
            Mage::log("Email: $email ---- " . $exc->getMessage(), null, 'events.log');
            return FALSE;
        }
    }

    const SENDER_EMAIL_ADDRESS = 'services@fahasa.com.vn';
    const SENDER_EMAIL_NAME = 'FAHASA';

    function couponCodeSurvey($email, $match, $team) {
        $core_helper = Mage::helper('coreextended');
        $rule_Id = Mage::getStoreConfig("event/intro/ruleid");
        if ($rule_Id) {
            $couponCode = $core_helper->getAvailableCouponCode($rule_Id);
            Mage::log("**Rule Id : $rule_Id - Coupon Code: $couponCode when user join game with Email: $email, match:$match, team:$team", null, 'events.log');
            if ($couponCode) {
                //Code is good. Sending out email
                return $couponCode;
            }
        }
        return false;
    }

    function sendSurveyCouponEmail($couponCode, $customerEmail, $match, $team) {
        Mage::log("**Start send email : Coupon Code: $couponCode when user join game with Email: $customerEmail, match:$match, team:$team", null, 'events.log');
        $core_helper = Mage::helper('coreextended');
        $templateId = Mage::getStoreConfig("event/intro/templateid");
        $emailTemplate = Mage::getModel('core/email_template')->loadByCode($templateId);
        $customer = Mage::getModel("customer/customer")
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($customerEmail);
        $customername = $customer->getFirstname() . " " . $customer->getLastname();
        //set up variable coupon code for email template
        $rule = Mage::getModel('salesrule/rule')->load(Mage::getStoreConfig("event/intro/ruleid"));
        $vars = array(
            'couponcode' => $couponCode,
            'customername' => $customername,
            'match' => Mage::getStoreConfig('event/' . $match . '/title'),
            'team' => $team,
            'discount' => Mage::helper('core')->formatPrice(round($rule->getDiscountAmount())),
            'datestart' => date("d-m-Y", strtotime($rule->getFromDate())),
            'dateend' => date("d-m-Y", strtotime($rule->getToDate()))
        );
        //Set sender information
        $emailTemplate->setSenderEmail(self::SENDER_EMAIL_ADDRESS);
        $emailTemplate->setSenderName(self::SENDER_EMAIL_NAME);
        try {
            $emailTemplate->send($customerEmail, $customername, $vars);
            //Mark this coupon code as sent so it will not be used again
            $core_helper->markCouponCodeAsSent($couponCode);
            Mage::log("**Send email ok - Coupon Code: $couponCode when user join game with Email: $customerEmail, match:$match, team:$team", null, 'events.log');
        } catch (Exception $e) {
            Mage::log("**Send email error - Coupon Code: $couponCode when user join game with Email: $customerEmail, match:$match, team:$team", null, 'events.log');
            Mage::logException($e);
        }
    }

    /**
     * get all gift with event_id    
     */
    public function getGiftList($event_id) {
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "select eg.*, ev.cms_page from fahasa_event_gift eg join fahasa_events ev on ev.event_id = eg.event_id where ev.event_id = :event_id;";
        $vars = array(
            "event_id" => "$event_id"
        );
        $results = $readConnection->fetchAll($query, $vars);
        return $results;
    }
    
    public function getGiftByGiftCode($event_id, $gift_code)
    {
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "select eg.*, ev.cms_page, ue.coupon_code, (buy_qty + revert_qty - (revert_times_used + buy_times_used)) as remain_qty from fahasa_event_gift eg join fahasa_events ev on ev.event_id = eg.event_id "
                . " join fahasa_user_event_log ue on ue.event_id = eg.event_id and ue.attend_code = eg.name "
                . " join fahasa_user_event_turn ut on ut.event_id = ue.event_id and ut.customer_id = ue.customer_id "
                . "where ev.event_id = :event_id and eg.name = :gift_code order by ue.id desc limit 1;";
        $vars = array(
            "event_id" => $event_id,
            "gift_code" => $gift_code,
        );
        $results = $readConnection->fetchAll($query, $vars);
        if (count($results) > 0)
        {
            return $results[0];
        }

        return null;
    }

    /**
     * get info gift with event_id    
     */
    public function getGiftInfo($event_id, $name) {
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "select * from fahasa_event_gift where event_id = :event_id and name = :name;";
        $vars = array(
            "event_id" => "$event_id",
            "name" => "$name"
        );
        $results = $readConnection->fetchAll($query, $vars);
        return $results[0];
    }

    /**
     * get all user played with event_id     
     * use for event birthday cake
     */
    public function getAllPlayed($event_id) {
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "select count(attend_code) as gift_used,attend_code "
                . "from fahasa_user_event_log "
                . "where "
                . "event_id = :event_id "
                . "group by attend_code;";
        $vars = array(
            "event_id" => "$event_id"
        );
        $results = $readConnection->fetchAll($query, $vars);
        $data = array();
        foreach ($results as $gift) {
            $data[$gift["attend_code"]] = $gift["gift_used"];
        }
        return $data;
    }
    
    public function getAllPlayedOfCustomer($event_id, $customer_id) {
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "select count(attend_code) as gift_used,attend_code "
                . "from fahasa_user_event_log "
                . "where "
                . "event_id = :event_id  and customer_id = :customer_id "
                . "group by attend_code;";
        $vars = array(
            "event_id" => "$event_id",
            "customer_id" => $customer_id
        );
        $results = $readConnection->fetchAll($query, $vars);
        $data = array();
        foreach ($results as $gift) {
            $data[$gift["attend_code"]] = $gift["gift_used"];
        }
        return $data;
    }

    /**
     * use for event birthday cake
     * channel : web/mobile => track user use channel
     */
    // EventsType : 'random_gift and 'get_fpoint'
    public function randomGift($event_id, $channel) {
        $success = false;
        $message = null;
        $turnTimesLeft = 0;
        $queueId = null;
        
        // check lai lan nua ()
        $eventDetail = $this->getEventDetail($event_id);
        $eventType = $eventDetail['eventType'];
        $isQueue = $eventDetail["isQueue"] == "1" ? true : false;
        
        $date_now = date("Y-m-d H:i:s", strtotime('+7 hours'));
        if ($eventDetail['dateBegin'] <= $date_now && $eventDetail['dateEnd'] >= $date_now){
            if (in_array($eventDetail["channel"], array("all", $channel))){
                $session = Mage::getSingleton("customer/session");
                if ($session->isLoggedIn()){
                    $customer = $session->getCustomer();
                    $email = $customer->getEmail();
                    $customerId = $customer->getId();
                    $referCode = $customer->getReferCode();

                    if ($referCode){
                        $giftListTemp = $this->getGiftList($event_id);
//                        $giftListUsed = $this->getAllPlayed($event_id, $email);
                        $giftListUsedCustomer = $this->getAllPlayedOfCustomer($event_id, $customerId);
                        $giftList = $this->handleGiftBasedOnUsesPerCustomer($giftListTemp, $giftListUsedCustomer, $customer);
                        $allGift = array();
                        
                        foreach ($giftList as $gift) {
                            $timesUse = $gift["times_use"];
//                            $timesUsed = $giftListUsed[$gift["name"]];
                            $timesUsed = $gift['times_played'];
                            $numArr = $timesUse - $timesUsed;
                            // numArr <= 0 : da het loai qua nay
                            if ($numArr > 0) {
                                // create array with times_use alive
                                $gift_arr = array_fill(0, $numArr, $gift["name"]);
                                // merge all gift item
                                $allGift = array_merge($allGift, $gift_arr);
                            }
                        }
                        $gitfArr = null;
                        if($eventType == 'get_fpoint'){
                            $gitfArr = $this->getStepGift($giftList, $email, $eventDetail, $customerId);
                            $giftBouns = $gitfArr[2];
                        }
                            
                        $randomGift = ($eventType == 'random_gift') ? $this->array_random($allGift) : $gitfArr;
                        if (count($randomGift) > 0) {
                            $gift = $randomGift[0];
                            $insertRs = false;
                             
                            $gift_names = array_column($giftList, "name");
                            
                            $gift_index = array_search($gift, $gift_names);
                            
                            if ($gift_index !== false){
                                $giftInfo = $giftList[$gift_index];

                                if ($eventDetail["revertTurnTime"])
                                {
                                    if ($isQueue){
                                        $queueId = $this->insertPlayWithRevertInQueue($event_id, $gift, $email, $channel, $customerId, $eventDetail, $playLimit);
                                    } else {
                                        $insertRsArr = $this->insertPlayWithRevert($event_id, $gift, $email, $channel, $customerId, $eventDetail, $eventDetail['playLimit']);
                                        $insertRs = $insertRsArr["success"];
                                        $remainQty = $insertRsArr["remainQty"];
                                        $nextTimeRevert = $insertRsArr["nextTimeRevert"];
                                    }
                                    
                                }
                                else
                                {
                                    $insertRs = $this->insertGiftLog($event_id, $gift, $email, $channel, $customerId);
                                }
                            }

                            if (!$isQueue){
                                if ($insertRs == true) {
                                    if (!$giftInfo['type']) {
                                        $success = true;
                                        $message = "EMPTY_GIFT";
                                        $attendCode = $gift;
                                        $giftImage = Mage::getBaseUrl("media") . $eventDetail["gameData"]["emptyGift"];
                                        $giftDescription = $eventDetail["gameData"]["receiveEmptyMsg"];
                                    } else {
                                        if (isset($giftBouns) && $giftBouns != null) {
                                            $insertBouns = $this->insertLogBouns($event_id, $giftBouns, $email, $channel, $customerId);
                                            if ($insertBouns) {
                                                $this->giveGift($event_id, $giftBouns, $email, $customerId);
                                            }
                                        }

                                        $couponCodeRs = $this->giveGift($event_id, $gift, $email, $customerId);
                                        $success = true;
                                        $attendCode = $gift;
                                        $giftImage = Mage::getBaseUrl('media') . "wysiwyg/game/" . $attendCode . ".png";
                                        $giftIndex = $giftInfo['item_index'];
                                        $giftLink = $giftInfo['link'];
                                        $sharedLinkTemp = $eventDetail["gameData"]["shareLink"];
                                        if ($eventDetail["gameData"]["shareGift"]) {
                                            $sharedLinkTemp .= "?eventId=" . $event_id . "&giftId=" . $gift;
                                        }
                                        $sharedLink = $sharedLinkTemp;
                                        if ($couponCodeRs !== false && $couponCodeRs !== "other") {
                                            //update copuponCode for view coupon in game page directly
                                            $this->updateCouponCodeInUserLog($event_id, $email, $gift, $couponCodeRs, $customerId);
                                            $giftDescription = $eventDetail["gameData"]["receiveGiftMsg"] ? preg_replace('/(.*)\%s(.*)\%s/i', '${1}' . $giftInfo["description"] . '${2}' . strtoupper($couponCodeRs) . '${3}', $eventDetail["gameData"]["receiveGiftMsg"]) : null;
                                        } else {
                                            $giftDescription = $eventDetail["gameData"]["receiveGiftMsg"] ? preg_replace('/(.*)\%s(.*)\%s/i', '${1}' . $giftInfo["description"] . '${3}', $eventDetail["gameData"]["receiveGiftMsg"]) : null;
                                        }
                                        
                                        $giftBackground = Mage::getBaseUrl('media') . $eventDetail["gameData"]["backgroundGiftSuccess"];
                                        if ($giftInfo["item_index"])
                                        {
                                            $background_gift_key = "backgroundGiftSuccess_" . $giftInfo["item_index"];
                                            if ($eventDetail["gameData"][$background_gift_key])
                                            {
                                                $giftBackground = Mage::getBaseUrl('media') . $eventDetail["gameData"][$background_gift_key];
                                            }
                                        }
                                        else
                                        {
                                            $giftSubDesc = $this->getRandomGiftSubDesc($event_id);
                                        }
                                    }
                                } else {
                                    $message = "TURN_OVER";
                                    $giftImage = Mage::getBaseUrl("media") . $eventDetail["gameData"]["turnOverImage"];
                                }    
                            } else {
                                if ($queueId != -1){
                                    $success = true;
                                }
                            } 
                            
                        } else {
                            // het qua
                            $message = "NO_GIFT";
                            $giftImage = Mage::getBaseUrl('media') . Mage::getStoreConfig('game/config/endofgiftimg');
                        }
                    }
                    else{
                         $message = "REQUIRE_CONFIRM_TELEPHONE";
                    }
                }   
                else{
                     $message = "ERR_NEED_LOGIN";
                }
            }
        }
        else{
            $message = "GAME_TIME_OUT";
        }
        
        return array(
            "success" => $success,
            "message" => $message,
            "attendCode" => $attendCode,
            "giftImage" => $giftImage,
            "sharedLink" => $sharedLink,
            "giftDescription" => $giftDescription,
            "giftIndex" => $giftIndex,
            "remainQty" => $remainQty,
            "nextTimeRevert" => $nextTimeRevert,
            "giftLink" => $giftLink,
            "isQueue" => $isQueue,
            "queueId" => $queueId,
            "giftBackground" => $giftBackground,
//            "giftSubDesc" => $giftSubDesc
        );
    }
    
    public function getGiftInforByGift($attend_code, $event_id)
    {
        $eventDetail = $this->getEventDetail($event_id);
        $giftInfo = $this->getGiftByGiftCode($event_id, $attend_code);
        if (!$giftInfo['type'])
        {
            $message = "EMPTY_GIFT";
            $attendCode = $attend_code;
            $giftImage = Mage::getBaseUrl("media") . $eventDetail["gameData"]["emptyGift"];
            $giftDescription = $eventDetail["gameData"]["receiveEmptyMsg"];
        }
        else
        {
            $remainQty = $giftInfo["remain_qty"];
            $attendCode = $attend_code;
            $giftImage = Mage::getBaseUrl('media') . "wysiwyg/game/" . $attend_code . ".png";
            $giftIndex = $giftInfo['item_index'];
            $giftLink = $giftInfo['link'];
            $sharedLinkTemp = $eventDetail["gameData"]["shareLink"];
            if ($eventDetail["gameData"]["shareGift"])
            {
                $sharedLinkTemp .= "?eventId=" . $event_id . "&giftId=" . $attend_code;
            }
            $sharedLink = $sharedLinkTemp;
            if ($giftInfo["coupon_code"])
            {
                //update copuponCode for view coupon in game page directly
                $giftDescription = $eventDetail["gameData"]["receiveGiftMsg"] ? preg_replace('/(.*)\%s(.*)\%s/i', '${1}' . $giftInfo["description"] . '${2}' . strtoupper($giftInfo["coupon_code"]) . '${3}', $eventDetail["gameData"]["receiveGiftMsg"]) : null;
            }
            else
            {
                $giftDescription = $eventDetail["gameData"]["receiveGiftMsg"] ? preg_replace('/(.*)\%s(.*)\%s/i', '${1}' . $giftInfo["description"] . '${3}', $eventDetail["gameData"]["receiveGiftMsg"]) : null;
            }
            
            
            $giftBackground = Mage::getBaseUrl('media') . $eventDetail["gameData"]["backgroundGiftSuccess"];
            if ($giftInfo["item_index"])
            {
                $background_gift_key = "backgroundGiftSuccess_" . $giftInfo["item_index"];
                if ($eventDetail["gameData"][$background_gift_key]){
                    $giftBackground = Mage::getBaseUrl('media') . $eventDetail["gameData"][$background_gift_key];
                }
            } else {
                $giftSubDesc = $this->getRandomGiftSubDesc($event_id);
            }
            
        }
        return array(
            "message" => $message,
            "attendCode" => $attendCode,
            "giftImage" => $giftImage,
            "sharedLink" => $sharedLink,
            "giftDescription" => $giftDescription,
            "giftIndex" => $giftIndex,
            "giftLink" => $giftLink,
            "remainQty" => $remainQty,
            "giftSubDesc" => $giftSubDesc,
            "giftBackground" => $giftBackground
        );
    }
    
    public function getRandomGiftSubDesc($event_id){
        $giftSubDescList = $this->getGiftSubDesc($event_id);
            if (count($giftSubDescList) > 0){
                $rand_index = rand(0, count($giftSubDescList) - 1);
                 $randSubDesc = $giftSubDescList[$rand_index]['description'];
            }
            return $randSubDesc;
    }
    public function getGiftSubDesc($event_id){
        $query = "select description from fahasa_event_gift_desc where event_id = :event_id ";
        $binds = array(
            'event_id' => $event_id
        );

        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");

        $results = $readConnection->fetchAll($query, $binds);
        return $results;
    }

    public function checkTurnInQueue($queue_id)
    {
        $gift_info = array();
        if ($queue_id)
        {
            $session = Mage::getSingleton("customer/session");
            $customer = $session->getCustomer();
            $query = "select * from fhs_user_event_log_queue where id = :queue_id and customer_id = :customer_id ";
            $binds = array(
                "queue_id" => $queue_id,
                "customer_id" => $customer->getId()
            );
            $read = Mage::getSingleton("core/resource")->getConnection("core_read");
            $rs = $read->fetchAll($query, $binds);
            $is_processed = false;
            $attend_code = null;
            $is_accept = false;
            $event_id = null;
            foreach ($rs as $item)
            {
                $is_processed = $item["is_processed"];
                $is_accept = $item["is_accept"];
                $attend_code = $item["attend_code"];
                $event_id = $item["event_id"];
            }
            if ($is_processed)
            {
                if ($is_accept){
                    $gift_info = $this->getGiftInforByGift($attend_code, $event_id);
                } else {
                    $is_accept = true;
                }
                
            }
        }

        return array_merge(array(
            'success' => true,
            'isProcessed' => $is_processed,
            'isAccept' => $is_accept,
        ), $gift_info);
    }

    /**
     * get random item in array
     * * */
    function array_random($arr, $num = 1) {
        if (count($arr) > 0) {
            shuffle($arr);

            $r = array();
            for ($i = 0; $i < $num; $i++) {
                $r[] = $arr[$i];
            }
            return $r;
        } else {
            return NULL;
        }
    }
    
     public function handleGiftBasedOnUsesPerCustomer($giftListTemp, $giftListUsed, $customer){
        $customer_addresses = $customer->getAddresses();
        $customer_province_ids = array();
        if (count($customer_addresses) > 0)
        {
            foreach ($customer_addresses as $address)
            {
                $customer_province_ids[] = $address["region_id"];
            }
        }
        $result = array();
        foreach($giftListTemp as $gift){
            $uses_per_customer = intval($gift['uses_per_customer']);
            $applied_province_arr = array();
            if ($gift['apply_province'])
            {
                $applied_province_arr = explode(",", $gift['apply_province']);
            }
            
            //customer has at least 1 address which is in applied province rule
            if ((!$gift['uses_per_customer'] || $uses_per_customer > $giftListUsed[$gift['name']]) && 
                    (sizeof($applied_province_arr) == 0 || 
                    (count($customer_province_ids) > 0 && count(array_intersect($customer_province_ids, $applied_province_arr)) > 0
                            ))){
                $result[] = $gift;
            }  
        }
        return $result;
    }

    function insertGiftLog($event_id, $gift, $email, $channel, $customerId = null, $attendInt = null) {
        try {
            $write = Mage::getSingleton("core/resource")->getConnection("core_write");
           
            $query = "insert into fahasa_user_event_log(event_id, email, created_at, created_by, attend_code, customer_id) "
                    . "select * from (select '{$event_id}', '{$email}', now(), '{$channel}', '{$gift}', {$customerId}) as temp "
                    . "where ( "
                    . "select case when count(1) < ev.play_limit then 1 else 0 end from fahasa_user_event_log ue "
                    . "join fahasa_events ev on (ev.event_id = ue.event_id) "
                    . "where now() between ev.date_begin and ev.date_end and ue.customer_id = {$customerId} and ue.event_id='{$event_id}' "
                    . ") = 1";
            
            $rs = $write->exec($query);
            if ($rs == 0){
                return false;
            }
            else{
                return true;
            }
        } catch (Exception $exc) {
            Mage::log("**error: can't insert when user join game $event_id with customer_id: $customerId, gift: $gift, channel: $channel", null, 'events.log');
            Mage::log("Email: $customerId ---- " . $exc->getMessage(), null, 'events.log');
        }
    }

    function getVarsRuleForEmail($giftInfo, $customername, $core_helper) {
        $rule = Mage::getModel('salesrule/rule')->load($giftInfo['value']);
        $ruleId = $rule->getId();
        if ($ruleId) {
            $couponCode = $core_helper->getAvailableCouponCode($ruleId);
        }
        if ($couponCode) {
            $expiredDate = date("d-m-Y", strtotime($rule->getToDate()));
            $vars = array(
                'couponcode' => $couponCode,
                'customername' => $customername,
                'giftdescription' => $giftInfo['description'],
                'discount' => Mage::helper('core')->formatPrice(round($rule->getDiscountAmount())),
                'datestart' => date("d-m-Y", strtotime($rule->getFromDate())),
                'dateend' => $expiredDate
            );
            return array(
                "success" => true,
                "expiredDate" => $expiredDate,
                "couponCode" => $couponCode,
                "ruleId" => $ruleId,
                "vars" => $vars
            );
        } else {
            return array(
                "success" => false,
                "ruleId" => $ruleId
            );
        }
    }

    /**
     * send mail gift
     * * */
    function giveGift($event_id, $gift, $customerEmail, $customerId) {
        $giftInfo = $this->getGiftInfo($event_id, $gift);
        $customer = Mage::getModel('customer/customer')->load($customerId);
        
        $customername = $customer->getFirstname() . " " . $customer->getLastname();
        $expiredDate = null;
        $couponCode = null;
        $partnerVars = null;
        
        switch ($giftInfo['type']) {
            case "rule":
                $templateId = Mage::getStoreConfig('game/config/emailrule');
                $core_helper = Mage::helper('coreextended');
                $varsRule = $this->getVarsRuleForEmail($giftInfo, $customername, $core_helper);
                if ($varsRule['success'] == false){
                    Mage::log("**error: het ma giam gia cho $event_id with Email: $customerEmail, ruleId: ". $varsRule['ruleId'], null, 'events.log');
                    return FALSE;
                }
                else{
                    $expiredDate = $varsRule['expiredDate'];
                    $couponCode = $varsRule['couponCode'];
                    $vars = $varsRule['vars'];
                }
                break;
            case "fpoint":
                $label = "F-Point";
                $templateId = Mage::getStoreConfig('game/config/emailfpoint');
                $vars = array(
                    'customername' => $customername,
                    'value' => $giftInfo['value'] . " " . $label,
                    'gifttype' => $label,
                    'giftdescription' => $giftInfo['description']
                );
                $this->giveGiftCustomer($event_id, $customerEmail, $giftInfo['type'], $giftInfo['value'], $customer->getId());
                break;
            case "freeship":
                $label = "Freeship";
                $templateId = Mage::getStoreConfig('game/config/emailfreeship');
                $vars = array(
                    'customername' => $customername,
                    'value' => $giftInfo['value'] . " " . $label,
                    'gifttype' => $label,
                    'giftdescription' => $giftInfo['description']
                );
                $this->giveGiftCustomer($event_id, $customerEmail, $giftInfo['type'], $giftInfo['value'], $customer->getId());
                break;
            case "giftrule":
                $templateId = Mage::getStoreConfig('game/config/emailgiftrule');
                $core_helper = Mage::helper('coreextended');
                $varsRule = $this->getVarsRuleForEmail($giftInfo, $customername, $core_helper);
                if ($varsRule['success'] == false) {
                    Mage::log("**error: het ma qua tang cho $event_id with customer_id: $customerId, ruleId: " . $varsRule['ruleId'], null, 'events.log');
                    return FALSE;
                } else {
                    $expiredDate = $varsRule['expiredDate'];
                    $couponCode = $varsRule['couponCode'];
                    $vars = $varsRule['vars'];
                }
                break;
            case "partner_coupon":
                $varsRule = $this->getPartnerCouponForEmail($giftInfo, $customerEmail, $customername, $customerId);
                if ($varsRule){
                    $expiredDate = $varsRule['expired_date'];

                    $partnerVars = array(
                        "couponCode" => $varsRule['coupon_code'],
                        "companyName" => $varsRule['company_name'] 
                    );
                }else{
                     Mage::log("**error: het ma giam gia cho $event_id with Email: $customerEmail, giftId: ". $gift, null, 'events.log');
                    return FALSE;
                }
                break;    
            default :
                $templateId = FALSE;
                break;
        }
        //push notification
       $this->pushNotification($customerEmail, $giftInfo, $couponCode, $expiredDate, $partnerVars);

//        if ($templateId) {
//            $emailTemplate = Mage::getModel('core/email_template')->loadByCode($templateId);
            //Set sender information
//            $emailTemplate->setSenderEmail(self::SENDER_EMAIL_ADDRESS);
//            $emailTemplate->setSenderName(self::SENDER_EMAIL_NAME);
            try {
//                $emailTemplate->send($customerEmail, $customername, $vars);
                if ($couponCode) {
                    //Mark this coupon code as sent so it will not be used again
                    $core_helper->markCouponCodeAsSent($couponCode);
                    Mage::log("**Send email ok - user join game with Email: $customerEmail, gift: $gift", null, 'events.log');
                    return $couponCode;
                } else if($partnerVars){
                    return $partnerVars["couponCode"];
                } 
                else {
                    return "other";
                }
            } catch (Exception $e) {
                Mage::log("**Send email error - user join game with customer_id: $customerId, gift: $gift", null, 'events.log');
                Mage::logException($e);
                return FALSE;
            }
//        } else {
//            return FALSE;
//        }
    }

    /**
     * top up fpoint/freeship
     * * */
    public function giveGiftCustomer($eventId, $customerEmail, $type, $giftValue, $customerId) {
        Mage::log("**Topup fpoint/freeship event $eventId with customer_id: $customerId, type gift: $type, gift: $giftValue", null, 'events.log');
        try {
            if ($type == "freeship") {
                $typeId = "num_freeship";
            } else {
                $typeId = "fpoint";
            }
            $write = Mage::getSingleton("core/resource")->getConnection("core_write");
            $selectSql = "select " . $typeId . " from fhs_customer_entity where entity_id = " . $customerId . ";";
            $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
            $action_purchase = "Redeem_" . $typeId;
            $description_purchase = "Game: Redeem fpoint/freeship event $eventId with customer_id: $customerId, type gift: $type, gift: $giftValue";
            $amountBefore = $readConnection->fetchOne($selectSql);
            if (Mage::helper("fahasa_customer/fpoint")->transationFpoint($customerId, $giftValue, $type, $action_purchase, $description_purchase)) {
                $sql1 = "insert into fhs_purchase_action_log (account, customer_id, action, value, amountAfter, updateBy, lastUpdated, description, amountBefore, type) values "
                        . "('{$customerEmail}', {$customerId}, 'top up', {$giftValue}, {$giftValue} + {$amountBefore}, 'vannguyen', now(), "
                        . "'top up for event {$eventId}', {$amountBefore}, '{$type}');";
                //$sql2 = "update fhs_customer_entity set " . $typeId . " = $typeId + '" . $giftValue . "' where entity_id=" . $customerId . ";";

                Mage::log("**Sql1 : $sql1", null, 'events.log');
                Mage::log("**Sql2 : $sql2", null, 'events.log');
                $write->query($sql1 . $sql2);
                return TRUE;
            } else {
                Mage::log("** CALL API of $eventId with customer_id: $customerId, type gift: $type, gift: $giftValue ----- FAILED", null, 'events.log');
                return FALSE;
            }
        } catch (Exception $e) {
            Mage::log("*** event: update into fhs_purchase_action_log failed: eventId=" . $eventId . ", customer=" . $customerId . ", message=" . $ex->getMessage(), null, "events.log");
            return FALSE;
        }
    }

    public function getCheckoutEventAlmostcartBlock() {
        $cartTotal = Mage::helper('almostcart')->getCartTotal();
        $rs = Mage::getModel("almostcart/almostcart")
                ->getCollection()
                ->addFieldToFilter('status', '1')
                ->addFieldToFilter('view_page', 'checkout')
                ->getItems();
        $currentDate = date("Y-m-d 00:00:00");
        foreach ($rs as $item) {
            $min = $item["min_cart_value"];
            $max = $item["max_cart_value"];
            $cmsBlock = $item["cms_block"];
            $startDate = $item['start_date'];
            $endDate = $item['end_date'];
            $description = $item['description'];
            if ($currentDate >= $startDate && $currentDate <= $endDate) {
                Mage::log("description: $description", null, 'events.log');
                if ($cartTotal >= $min && ($cartTotal <= $max || $max == null)) {
                    Mage::log("cmsBlock: $cmsBlock", null, 'events.log');
                    return $cmsBlock;
                }
            }
        }
        return NULL;
    }

    function pushNotification($email, $giftInfo, $couponCode, $expiredDate, $partnerVars) {
        $typeGift = $giftInfo['type'];
        $titleCfg = $contentCfg = $pageType = $pageValue = null;
        $title = $message = "";
        $url = null;
        switch ($typeGift){
            case 'fpoint':
                $titleCfg = Mage::getStoreConfig('game/noti/titlefpoint');
                $contentCfg = Mage::getStoreConfig('game/noti/contentfpoint');
                $title = $titleCfg;
                $search = '/\%s/i';
                $replace = '${1}'.$giftInfo['description'] . '${2}';
                $message = preg_replace($search,$replace, $contentCfg);
                break;
            case 'freeship':
                $titleCfg = Mage::getStoreConfig('game/noti/titlefreeship');
                $contentCfg = Mage::getStoreConfig('game/noti/contentfreeship');
                $title = $titleCfg;
                $search = '/\%s/i';
                $replace = '${1}'.$giftInfo['description'] . '${2}';
                $message = preg_replace($search,$replace, $contentCfg);
                break;
            case 'rule':
                $titleCfg = Mage::getStoreConfig('game/noti/titlerule');
                $contentCfg = Mage::getStoreConfig('game/noti/contentrule');
                $pageType = "coupon";
                $pageValue = $couponCode;
                $title = $titleCfg;
                $search = '/(.*)\%s(.*)\%s(.*)\%s/i';
                $replace = '${1}'.$giftInfo['description'] . '${2}' . $couponCode .'${3}' . $expiredDate . '${4}';
                $message = preg_replace($search,$replace, $contentCfg);
                break;
            case 'giftrule':
                $titleCfg = Mage::getStoreConfig('game/noti/titlegiftrule');
                $contentCfg = Mage::getStoreConfig('game/noti/contentgiftrule');
                $pageType = "coupon";
                $pageValue = $couponCode;
                $title = $titleCfg;
                $search = '/(.*)\%s(.*)\%s(.*)\%s/i';
                $replace = '${1}'.$giftInfo['description'] . '${2}' . $couponCode .'${3}' . $expiredDate . '${4}';
                $message = preg_replace($search,$replace, $contentCfg);
                break;
            case 'partner_coupon':
                $titleCfg = Mage::getStoreConfig('game/noti/titlepartnercoupon');
                $contentCfg = Mage::getStoreConfig('game/noti/contentpartnercoupon');
                $pageType = null;
                $pageValue = $couponCode;
                $title = $titleCfg;
                $search = '/(.*)\%s(.*)\%s(.*)\%s/i';
                $replace = '${1}' . $giftInfo['description'] . '${2}' . $partnerVars["couponCode"] . '${3}' . $expiredDate . '${4}';
                $message = preg_replace($search,$replace, $contentCfg);
                $url = "/" . $giftInfo['link'];
                break;
            case 'physical_gift':
                $titleCfg = Mage::getStoreConfig('game/noti/titlephysicalgift');
                $contentCfg = Mage::getStoreConfig('game/noti/contentphysicalgift');
                $title = $titleCfg;
                $search = '/\%s/i';
                $replace = '${1}'.$giftInfo['description'] . '${2}';
                $message = preg_replace($search,$replace, $contentCfg);
                break;
            default:
                return;
        }

        $urlServer = "https://fahasa.com:88/pushNotificationMobile";
        $hashKey = "824b35b38e2e4fc0f9e88070cbcecd64ccaa592c8e11f9f80413aea36ea6ab84";
        $postHelper = Mage::helper('cancelorder');
        $curdate = date("Y-m-d H:i:s", strtotime('+7 hours + 3 minutes'));
        $scheduleTime = $curdate . " GMT+0700";
        $json = array(
            "email" => $email,
            "hashKey" => $hashKey,
            "title" => $title,
            "message" => $message,
            "pageType" => $pageType,
            "pageValue" => $pageValue,
            "scheduleTime" => $scheduleTime,
            "url" => $url
        );
        $postHelper->httpPost($urlServer, json_encode($json));
    }

    /**

     * get eventId active right now()
     */
    public function getEventId($channel) {
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "select event_id from fahasa_events where date_begin <= now() and date_end >= now() and active = 1 and channel in ('all', '{$channel}');";
        $results = $readConnection->fetchAll($query);

        $event_id = "NO_GAME";
        if ($results[0]["event_id"]){
            $event_id = $results[0]["event_id"];
        }

        return $event_id;
    }

    private function checkProductShouldShowVote($productId) {
        $mainCatId = Mage::getStoreConfig('game/voteproduct/mainCatId');
        $mainCatIdArr = explode(",", $mainCatId);
        $catIdsExcluded = Mage::getStoreConfig('game/voteproduct/catIdsExcluded');
        $catIdsExcludedArr = explode(",", $catIdsExcluded);
        $product = Mage::getModel('catalog/product')->load($productId);
        $mainCatIdProd = $product->getCategoryMainId();

        $midCatIdProd = $product->getCategoryMidId();
        
        if (in_array($mainCatIdProd, $mainCatIdArr) && !in_array($midCatIdProd, $catIdsExcludedArr)){
            return true;
        }
        return false;
    }

    public function checkProductVote($productId) {
        $hadVoted = false;
        $eventId = Mage::getStoreConfig('game/voteproduct/eventId');
        $enable = Mage::getStoreConfig('game/voteproduct/enable');

        if ($enable) {
            $showVote = $this->checkProductShouldShowVote($productId);
            $message = null;
            $percentVoted = null;

            if ($showVote) {
                $hadVotedRs = $this->getDataPlayedWithPercent($eventId, $productId);
                if ($hadVotedRs["message"] === "ERR_NEED_LOGIN") {
                    $message = "ERR_NEED_LOGIN";
                    $percentVoted = $hadVotedRs["percentVoted"];
                } else if ($hadVotedRs["attendInt"] == $productId) {
                    $hadVoted = true;
                    $percentVoted = $hadVotedRs["percentVoted"];
                } else {
                    $hadVoted = false;
                    $percentVoted = $hadVotedRs["percentVoted"];
                }
            }
        }

        return array(
            "eventId" => $eventId,
            "productId" => $productId,
            "showVote" => $showVote,
            "hadVoted" => $hadVoted,
            "message" => $message,
            "percentVoted" => $percentVoted
        );
    }

    public function voteProduct($productId, $channel) {
        $success = true;
        $message = null;
        
        $eventId = Mage::getStoreConfig('game/voteproduct/eventId');
        $enable = Mage::getStoreConfig('game/voteproduct/enable');
        $revertTime = Mage::getStoreConfig('game/voteproduct/revertTime');
        
        $showVote = $this->checkProductShouldShowVote($productId);
        
        if ($enable) {
            if ($showVote) {
                if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                    $customer = Mage::getSingleton('customer/session')->getCustomer();
                    $email = $customer->getEmail();
                    $customerId = $customer->getId();
                    try {
                        $rs = $this->insertUserVoteLog($eventId, $email, $channel, $customerId, $productId, $revertTime);
                        if ($rs) {
                            $message = "VOTE_PRODUCT_SUCCESS";
                        } else {
                            $success = false;
                            $message = "VOTE_PRODUCT_FAIL";
                        }
                    } catch (Exception $ex) {
                        $success = false;
                        $message = "VOTE_PRODUCT_FAIL";
                    }
                } else {
                    $success = false;
                    $message = "ERR_NEED_LOGIN";
                }
            } else {
                $success = false;
                $message = "NO_VOTE_PRODUCT";
            }
        }

        return array(
            "success" => $success,
            "message" => $message
        );
    }

    public function insertUserVoteLog($eventId, $email, $channel, $customerId, $attendInt, $revertTime) {
        $revertTimeCondition = $this->getPreviousRevertTime($revertTime);
        
        $writeConnection = Mage::getSingleton("core/resource")->getConnection("core_write");
        $insertNotExists = "insert into fahasa_user_event_log(event_id, email, created_at, created_by, attend_code, customer_id, attend_int) "
                . "select * from (select '{$eventId}', '{$email}', now(), '{$channel}', null, {$customerId}, {$attendInt}) as tmp "
                . "where not exists( "
                . "select customer_id from fahasa_user_event_log where customer_id={$customerId} and attend_int={$attendInt} and event_id='{$eventId}' "
                . $revertTimeCondition
                . ") and exists (select 1 from fahasa_events ev "
                . "where ev.event_id = '{$eventId}' and now() between ev.date_begin and ev.date_end) limit 1";
        try {
            $writeConnection->query($insertNotExists);
            return true;
        } catch (Exception $ex) {
            Mage::log("Insert user vote fail eventId=" . $eventId . ", customer_id=" . $customerId . ", attendInt=" . $attendInt . ", message=" . $ex->getMessage(), null, "events.log");
            return false;
        }
    }

    public function getTopProductVoted($catId, $page, $pageSize) {
        if (!isset($page)){
            $page = 1;
        }
        $topNumber = (int) Mage::getStoreConfig('game/voteproduct/topNumber');
        if (!isset($pageSize)){
            $pageSize = $topNumber;
        }
        
        $enable = Mage::getStoreConfig('game/voteproduct/enable');
        $result = array();
        if ($enable) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $customerId = $customer->getId();

            $eventId = Mage::getStoreConfig('game/voteproduct/eventId');
           
            $revertTime = (int) Mage::getStoreConfig('game/voteproduct/revertTime');

            $subquery = null;

            if ($customerId) {
                $revertTimeCondition = $this->getPreviousRevertTime($revertTime);
            
                $subquery = new Zend_Db_Expr("(select *, (case when t2.user_attend is not null then 1 else 0 end ) as had_voted from "
                        . "(select product_id as attend_int, total as total_voted, index_status as is_raise "
                        . "from fhs_vote_stat "
                        . "where event_id = '{$eventId}' "
                        . "order by total desc "
                        . ") as t left join ( "
                        . "select  ue2.attend_int as user_attend "
                        . "from fahasa_user_event_log ue2 "
                        . "where event_id='{$eventId}' and customer_id = {$customerId} "
                        . $revertTimeCondition
                        . ") as t2 on (t.attend_int = t2.user_attend ))");
            } else {
                $subquery = new Zend_Db_Expr("(select *, 0 as had_voted from "
                        . "(select product_id as attend_int, total as total_voted, index_status as is_raise "
                        . "from fhs_vote_stat "
                        . "where event_id = '{$eventId}' "
                        . "order by total desc "
                        . ") as t )");
            }

            $collection = Mage::getModel('catalog/product')->getCollection();

            $collection->addAttributeToSelect('*')
                    //->joinTable('amazonrating/amazonrating', 'sku = sku', array('amazonRatingURL' => 'ratingURL', 'amazonStarRating' => 'cssStarRating', 'amazonScore' => 'numericScore'), null, 'left')
                    ->joinTable('review/review_aggregate', 'entity_pk_value = entity_id', array('fhs_reviews_count' => 'reviews_count', 'fhs_rating_summary' => 'rating_summary'), '{{table}}.store_id=0', 'left')
                    ->joinTable('multistoreviewpricingpriceindexer/product_index_price', 'entity_id = entity_id', array('final_price' => 'final_price', 'min_price' => 'min_price', 'max_price' => 'max_price', 'price2' => 'price'), '{{table}}.store_id=' . Mage::app()->getStore()->getId() . ' and {{table}}.customer_group_id=0', 'left')
                    ->joinField('stock_status', 'cataloginventory/stock_status', 'stock_status', 'product_id=entity_id', null, 'left')
                    ->addStoreFilter()
                    ->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
                    ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
            if (Mage::getStoreConfig('game/voteproduct/show_in_stock')){
                $collection->addAttributeToFilter('stock_status', array('eq' => 1));
            }

            if ($catId == "rest"){
                $catIdsUsed = Mage::getStoreConfig('game/voteproduct/catIdsUsed');
                $catIdExcludedRest = Mage::getStoreConfig('game/voteproduct/catIdExcludedRest');
                if (empty($catIdExcludedRest)){
                   $collection->getSelect()->where('e.category_mid_id not in ('. $catIdsUsed . ')');
                }
                else{
                   $collection->getSelect()->where('e.category_main_id not in (' . $catIdExcludedRest .') and e.category_mid_id not in ('. $catIdsUsed . ')');  
                }
            }
            else{
                $mainCatId = Mage::getStoreConfig('game/voteproduct/mainCatId');
                $mainCatIdArr = explode(",", $mainCatId);
                $cat3IdsUsed = Mage::getStoreConfig('game/voteproduct/cat3IdsUsed');
                $cat3IdsUsedArr = explode(",", $cat3IdsUsed);
                if (in_array($catId, $mainCatIdArr)){
                    //catid is a main category
                    $collection->getSelect()->where('e.category_main_id='. $catId);
                }
                else if (in_array($catId, $cat3IdsUsedArr)){
                    $collection->getSelect()->where('e.category_1_id = '. $catId);
                }
                else{
                    if (empty($cat3IdsUsed)){
                        //catid is a mid category
                        $collection->getSelect()->where('e.category_mid_id='. $catId);
                    } else {
                        $collection->getSelect()->where('e.category_mid_id='. $catId . " and e.category_1_id not in (" . $cat3IdsUsed . ") " );
                    }
                }
            }
                    
            if ($pageSize * $page > $topNumber){
                $pageSize = $topNumber - $pageSize * ($page - 1);
            }
            $collection->getSelect()->join(array('user_event' => $subquery), 'e.entity_id = user_event.attend_int', array('total_voted' => 'total_voted',
                'is_raise' => 'is_raise',
                'had_voted' => 'had_voted'))
                    ->order(array("total_voted desc", "num_orders_month desc", "num_orders desc"))
                    ->limit($pageSize, ($page - 1) * $pageSize);

            $data = $collection->getItems();
            $handleDiscount = \Mage::helper('discountlabel/data');
            
            $index = 0;
            foreach ($data as $key => $product) {
                $p = array();
                $p["productId"] = $product["entity_id"];
                $p["image"] = (string) Mage::helper('catalog/image')->init($product, 'small_image')->resize(400, 400);
                $p["name"] = $product["name"];
                $p["typeId"] = $product["type_id"];
                $p["price"] = $product["price2"];
                $p["finalPrice"] = $product["final_price"];
                $p["percentVoted"] = $product["total_voted"];
                if ($index == 0){
                    $p["isRaise"] = 1; //because the top product is always up
                } else {
                    $p["isRaise"] = $product["is_raise"] == 1 ? 1 : 0;
                }
                $p["hadVoted"] = $product["had_voted"] == 1 ? 1 : 0;
                $p["discountPercent"] = $handleDiscount->handleDiscountPercent($product);
                $p["productUrl"] = $product["url_path"];
                $p["mainCat"] = $product["category_main_id"];
                $p["fhs_reviews_count"] = $product['fhs_reviews_count'];
                $p["fhs_rating_summary"] = $product['fhs_rating_summary'];
                $p["description"] = $product['description'];
                $p["author"] = $product['author'];
                $p["publisher"] = $product['publisher'];
                $result[] = $p;
                $index++;
            }
        }
        
        return $result;
    }
    
    
    function getDataPlayedWithPercent($event_id, $attend_int = null) {
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $customerId = $customer->getId();
            $revertTime = (int) Mage::getStoreConfig('game/voteproduct/revertTime');
            $revertTimeCondition = $this->getPreviousRevertTime($revertTime);
            
            $query = "select t1.customer_id, t1.attend_int, t2.percent_voted, t2.happening from "
                    . "(select ue1.customer_id, ue1.attend_int "
                    . "from fahasa_user_event_log ue1 "
                    . "where ue1.event_id='{$event_id}' and ue1.customer_id={$customerId} and ue1.attend_int={$attend_int} "
                    . $revertTimeCondition
                    . ") as t1 "
                    . "left join ( "
                    . "select vs.product_id as attend_int, vs.total as percent_voted, case when now() between ev.date_begin and ev.date_end  then 1 else 0 end as happening "
                    . "from fhs_vote_stat vs "
                    . "join fahasa_events ev on vs.event_id = ev.event_id "
                    . "where vs.event_id='{$event_id}' and vs.product_id={$attend_int} "
                    . ") as t2 on (t1.attend_int = t2.attend_int) "
                    . " union "
                    . "select t1.customer_id, t1.attend_int, t2.percent_voted, t2.happening from "
                    . "(select ue1.customer_id, ue1.attend_int "
                    . "from fahasa_user_event_log ue1 "
                    . "where ue1.event_id='{$event_id}' and ue1.customer_id={$customerId} and ue1.attend_int={$attend_int}"
                    . $revertTimeCondition
                    . ") as t1 "
                    . "right join ( "
                    . "select vs.product_id as attend_int, vs.total as percent_voted, case when now() between ev.date_begin and ev.date_end  then 1 else 0 end as happening "
                    . "from fhs_vote_stat vs "
                    . "join fahasa_events ev on vs.event_id = ev.event_id "
                    . "where vs.event_id='{$event_id}' and vs.product_id={$attend_int} "
                    . ") as t2 on (t1.attend_int = t2.attend_int);";
            $results = $readConnection->fetchAll($query);
            if (count($results) == 0) {
                 return array(
                        "attendInt" => null,
                        "percentVoted" =>  0
                    );
            } else {
                if ($results[0]["attend_int"] == null) {
                    return array(
                        "attendInt" => null,
                        "percentVoted" => $results[0]["percent_voted"] ? $results[0]["percent_voted"] : 0
                    );
                } else {
                    return array(
                        "attendInt" => $results[0]["attend_int"],
                        "percentVoted" => $results[0]["percent_voted"] ? $results[0]["percent_voted"] : 0
                    );
                }
            }
        } else {
            // chua dang nhap
            $query = "select vs.product_id as attend_int, vs.total as percent_voted, "
                    . "case when now() between ev.date_begin and ev.date_end  then 1 else 0 end as happening "
                    . "from fhs_vote_stat vs  join fahasa_events ev on vs.event_id = ev.event_id "
                    . "where vs.event_id='{$event_id}' and vs.product_id={$attend_int};";
            $results = $readConnection->fetchAll($query);
            if (count($results) == 0) {
                 return array(
                        "message" => "ERR_NEED_LOGIN",
                        "percentVoted" =>  0
                    );
            } else {
                return array(
                    "message" => "ERR_NEED_LOGIN",
                    "percentVoted" => $results[0]["percent_voted"] ? $results[0]["percent_voted"] : 0
                );
            }
        }
    }
    
    public function getSharedInforById($eventId, $giftId){
        $gameData = $this->getGameDataInfor($eventId);
        $image = "";
        if ($giftId){
            $image = Mage::getBaseUrl('media') . "wysiwyg/game/" . $giftId . ".png";
        }
        else{
            $image = Mage::getBaseUrl('media') . $gameData["sharedImage"];
        }
        
        return array(
            "image" => $image,
            "title" => $gameData["sharedTitle"],
            "description" => $gameData["sharedDescription"]
        );
    }
    
    public function getGameDataInfor($eventId){
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "select cms_page, data from fahasa_events where event_id = '{$eventId}';";
        $results = $readConnection->fetchAll($query);

        $rs = null;
        if (count($results) > 0 && $results[0]["data"]){
            $rs = (array) json_decode($results[0]["data"]);
        }
        return $rs;
    }
    
    public function insertShareLog($eventId, $sharedLink, $channel){
        $session = Mage::getSingleton('customer/session');
        if ($session->isLoggedIn()){
            $customer = $session->getCustomer();
            $email = $customer->getEmail();
            $customerId = $customer->getId();
            
            $writeConnection = Mage::getSingleton("core/resource")->getConnection("core_write");
            $insertQuery = "insert into fahasa_event_share_log (event_id, email, customer_id, created_at, created_by, channel, share_source, share_link) values "
                    . "('{$eventId}', '{$email}', '{$customerId}', now(), 'vannguyen', '{$channel}', 'facebook', '{$sharedLink}');";
            try {
                $writeConnection->query($insertQuery);
                Mage::log("*** event: insert share log success: eventId=". $eventId . ", customer_id=". $customerId . ", share_source=facebook, share_url" . $sharedLink, null, "events.log");
                $eventDetail = $this->getEventDetail($eventId);
                if ($eventDetail["gameData"]["shareBuyTurn"]) {
                    $this->buyEventTurnByShare($eventId, $email, $customerId);
                }
            } catch (Exception $ex) {
                Mage::log("*** event: insert share log failed: eventId=". $eventId . ", email=". $email . ", share_source=facebook, share_ur" . $sharedLink . ", message = " . $ex->getMessage(), null, "events.log");
            }
        }
        else{
            Mage::log("*** event: insert share log: customer is not login " . $eventId . ", shareUrl = " . $sharedLink, null, "events.log");
        }
    }
    
    public function buyEventTurnByShare($eventId, $email, $customerId){
        try{
            $read = Mage::getSingleton("core/resource")->getConnection("core_read");
            $curdate = date("Y-m-d", strtotime('+7 hours'));
            $fromDate = $curdate . " 00:00:00";
            $toDate = $curdate . " 23:59:59";
            $selectSql = "select id from fahasa_event_share_log where event_id = '{$eventId}' and customer_id={$customerId} and created_at between '{$fromDate}' and '{$toDate}';";
            $rs = $read->fetchAll($selectSql);
            //customer has just share page
            if (count($rs) == 1) {
                $write = Mage::getSingleton("core/resource")->getConnection("core_write");
                $quantity = 1;
                $insertTurn = "insert into fahasa_user_event_turn (event_id, email, customer_id, buy_qty, buy_times_used, created_by) values "
                    . "('{$eventId}', '{$email}', {$customerId}, {$quantity}, 0, 'vannguyen') on duplicate key update buy_qty=buy_qty+values(buy_qty);";
                Mage::log("*** event: buy event turn by share eventId=" . $eventId . ", customer_id=". $customerId . ", sql=" . $insertTurn, null, "events.log");
                $write->query($insertTurn);
            }
        } catch (Exception $ex) {
            Mage::log("*** event: exception buy event turn by share eventId=" . $eventId . ", customer_id=". $customerId . ", sql=" . $ex->getMessage(), null, "events.log");
        }
    }
    
    public function getEventDetail($eventId) {
        // cach 1 : order by item_index : sau do kiem tra item[0] => co index : event nay can sai index
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "select ev.date_begin, ev.date_end, ev.revert_turn_time, ev.revert_turn_point, ev.fpoint_turn_cost,ev.channel, ev.cms_page, "
                . "ev.type, ev.action_game, ev.play_limit, ev.data, ev.is_queue, eg.name, eg.description as gift_description, eg.item_index, eg.value as gift_value "
                . "from fahasa_events ev "
                . "left join fahasa_event_gift eg on ev.event_id = eg.event_id "
                . "where ev.event_id = '{$eventId}' order by -eg.item_index desc;";
        $result = $readConnection->fetchAll($query);
        if (count($result) > 0) {
            $giftsNumber = count(array_unique(array_column($result, 'item_index')));
            $giftsData = array();
            // danh cho event can item_index chua chinh dc event can null
            foreach ($result as $item) {
                if ($item['item_index'] != null) {
                    $gift['name'] = $item['name'];
                    $gift['description'] = $item['gift_description'];
                    $gift['item_index'] = $item['item_index'];
                    $gift['value'] = $item['gift_value'];
                    $giftsData[] = $gift;
                }
            }
            return array(
                "success" => true,
                "eventId" => $eventId,
                "eventType" => $result[0]['type'],
                "actionGame" => $result[0]['action_game'],
                "playLimit" => $result[0]['play_limit'],
                "gameData" => (array) json_decode($result[0]["data"]),
                "dateBegin" => $result[0]["date_begin"],
                "dateEnd" => $result[0]["date_end"],
                "channel" => $result[0]["channel"],
                "revertTurnTime" => $result[0]["revert_turn_time"],
                "revertTurnPoint" => $result[0]["revert_turn_point"],
                "fpointTurnCost" => $result[0]["fpoint_turn_cost"],
                "giftsNumber" => $giftsNumber,
                "giftsData" => $giftsData,
                "isQueue" => $result[0]["is_queue"] == "1" ? true : false,
            );
        }

        return array(
            "success" => false,
            "message" => "EVENT_INVALID"
        );
    }

    public function buyEventTurnByFpoint($eventId, $quantity) {
        $session = Mage::getSingleton('customer/session');
        $success = false;
       
        if ($eventId && $quantity && (string)(int)$quantity == $quantity && $quantity > 0) {
            if ($session->isLoggedIn()) {
                $customer = $session->getCustomer();

                $read = Mage::getSingleton("core/resource")->getConnection("core_read");
                $fpointBefore = Mage::helper('tryout')->determinetryout();
                $query = "select fpoint_turn_cost from fahasa_events where event_id='{$eventId}';";
                $fpointTurnCost = $read->fetchOne($query);
                if ($fpointTurnCost && $fpointBefore >= $fpointTurnCost * $quantity) {
                    $result = $this->updateCustomerBuyTurn($eventId, $customer, $quantity, $fpointTurnCost);
                    if ($result) {
                        $success = true;
                        $message = "BUY_TURN_SUCCESS";
                    } else {
                        $message = "BUY_TURN_FAIL";
                    }
                } else {

                    $message = "NOT_ENOUGH_FPOINT";
                }
            } else {
                $message = "ERR_NEED_LOGIN";
            }
        }

        return array(
            "success" => $success,
            "message" => $message
        );
    }

    public function updateCustomerBuyTurn($eventId, $customer, $quantity, $fpointTurnCost) {
        try {
            $fpointTotal = $quantity * $fpointTurnCost;
            $email = $customer->getEmail();
            $customerId = $customer->getId();
            $write = Mage::getSingleton("core/resource")->getConnection("core_write");

            try {
                $write->beginTransaction();
                $fpointValueLog = $fpointTotal * (-1);
		$action_purchase = "Buy_more_turn";
		$description_purchase = "Game: buy more turn, event_id: ".$eventId;
		if(Mage::helper("fahasa_customer/fpoint")->transationFpoint($customerId, $fpointValueLog, 'fpoint', $action_purchase, $description_purchase)){
		    $insertTurn = "insert into fahasa_user_event_turn (event_id, email, customer_id, buy_qty, buy_times_used, created_by) values "
                        . "('{$eventId}', '{$email}', {$customerId}, {$quantity}, 0, 'vannguyen') on duplicate key update buy_qty=buy_qty+values(buy_qty);";

		    Mage::log("*** event: update customer buy turn success: eventId=" . $eventId . ", email=" . $email . ", quantity=" . $quantity , null, "events.log");
		    $write->query($insertTurn);
		    $write->commit();
		    return true;
		}
            } catch (Exception $ex) {
                Mage::log("*** event: update customer buy turn exception for transaction eventId=" . $eventId . ", email=" . $email . ", quantity=" . $quantity . ", message=" . $ex->getMessage(), null, "events.log");
                $write->rollback();
            }
        } catch (Exception $ex) {
            Mage::log("*** event: update customer buy turn failed: eventId=" . $eventId . ", email=" . $email . ", quantity=" . $quantity . ", message=" . $ex->getMessage(), null, "events.log");
            return false;
        }
    }
    
    public function insertPlayWithRevertInQueue($event_id, $gift, $email, $channel, $customerId, $eventDetail, $playLimit)
    {
        try {
            $write = Mage::getSingleton("core/resource")->getConnection("core_write");
            $insert_query = "insert into fhs_user_event_log_queue (event_id, customer_id, attend_code, channel, created_at) values "
                    . "(:event_id, :customer_id, :attend_code, :channel, now()) ";
            $binds = array(
                'event_id' => $event_id,
                'customer_id' => $customerId,
                'attend_code' => $gift,
                'channel' => $channel,
            );
            $write->query($insert_query, $binds);
            $queueId = (int) $write->lastInsertId();
        } catch (Exception $ex) {
            Mage::log("Exception insert play queue " . $ex, null, "events.log");
            $queueId = -1;
        }

        return $queueId;
    }

    public function insertPlayWithRevert($event_id, $gift, $email, $channel, $customerId, $eventDetail, $playLimit) {
        try {
            $write = Mage::getSingleton("core/resource")->getConnection("core_write");

            $revertTurnTime = $eventDetail["revertTurnTime"];
            $insertTurn = "insert into fahasa_user_event_log (event_id, email, created_at, created_by, attend_code, customer_id) "
                    . "select * from ( "
                    . "select '{$event_id}', '{$email}', now(), '{$channel}', '{$gift}', {$customerId}) as temp "
                    . "where ( "
                    . "select revert_qty + buy_qty - revert_times_used - buy_times_used as remain_qty "
                    . "from fahasa_user_event_turn ut "
                    . "join fahasa_events ev on ev.event_id = ut.event_id "
                    . "where now() between ev.date_begin and ev.date_end and ut.email = '{$email}' and ut.event_id='{$event_id}' "
                    . ") > 0 "
                    . "or "
                    . " not exists ( "
                    . "select 1 from fahasa_user_event_turn ut "
                    . "join fahasa_events ev on ev.event_id = ut.event_id "
                    . "where now() between ev.date_begin and ev.date_end and ut.email = '{$email}' and ut.event_id='{$event_id}' ) "
                    . " ;";

            $read = Mage::getSingleton("core/resource")->getConnection("core_read");
            $selectTurn = "select email, revert_qty, revert_times_used, buy_qty, buy_times_used from fahasa_user_event_turn where event_id='{$event_id}' and email='{$email}';";

            $customerTurn = $read->fetchAll($selectTurn);
            $insertLogTurn = "";
            
            $remainQty = 0;
            $nextTimeRevert = null;
            
            if (count($customerTurn) > 0) {
                //only update revert_time when this is the first turn (means revert_times_used = 1)
//                $insertLogTurn = "update fahasa_user_event_turn as ut, "
//                        . " (select "
//                        . "if (revert_qty - revert_times_used > 0, revert_times_used + 1, revert_times_used) as revert_remain, "
//                        . "if (revert_qty - revert_times_used > 0, buy_times_used, if(buy_qty - buy_times_used > 0, buy_times_used + 1, buy_times_used)) as buy_remain "
//                        . "from fahasa_user_event_turn) as temp "
//                        . "set ut.buy_times_used = temp.buy_remain, ut.revert_times_used = temp.revert_remain, "
//                        . "ut.revert_time = case when ut.revert_times_used = 1 then date_add(now(), interval {$revertTurnTime} second) else ut.revert_time end "
//                        . "where event_id='{$event_id}' and email='{$email}';";
                $revertQty = (int)$customerTurn[0]["revert_qty"];
                $revertTimesUsed = (int) $customerTurn[0]["revert_times_used"];
                $buyQty = (int)$customerTurn[0]["buy_qty"];
                $buyTimesUsed = (int) $customerTurn[0]["buy_times_used"];
                
                $remainQty = $buyQty + $revertQty - ($revertTimesUsed + $buyTimesUsed + 1);
                
                $nextTimeValue = $this->calculateRevertTime($eventDetail);
                $nextTimeRevert = $nextTimeValue;
		
		//if customer buy turn before play game with revert turn
		if ($revertQty == 0){
                    $insertLogTurn = "update fahasa_user_event_turn set revert_qty={$playLimit}, revert_times_used=revert_times_used + 1, revert_time='{$nextTimeValue}' where event_id='{$event_id}' and email='{$email}';";
                } else {
            	    if ($revertQty - $revertTimesUsed > 0){
                        if ($revertTimesUsed == 0){
                            if ($eventDetail['eventType'] == 'get_fpoint') {
                                $insertLogTurn = "update fahasa_user_event_turn set revert_times_used=revert_times_used + 1, multi_day= multi_day + 1, revert_time='{$nextTimeValue}' where event_id='{$event_id}' and email='{$email}';";
                            } else {
                                $insertLogTurn = "update fahasa_user_event_turn set revert_times_used=revert_times_used + 1, revert_time='{$nextTimeValue}' where event_id='{$event_id}' and email='{$email}';";
                            }
                        }
                        else{
                            $insertLogTurn = "update fahasa_user_event_turn set revert_times_used=revert_times_used + 1 where event_id='{$event_id}' and email='{$email}';";
                        }
                    }
                    else{
                        if ($buyQty - $buyTimesUsed > 0){
                            $insertLogTurn = "update fahasa_user_event_turn set buy_times_used=buy_times_used + 1 where event_id='{$event_id}' and email='{$email}';";
                        }
                    }
      	        }
            } else {
                if ($eventDetail['eventType'] == 'get_fpoint') {
                    $multiDay = 1; // cho newplayer ko choi game tr day 
                    $insertLogTurn = "insert into fahasa_user_event_turn (event_id, email, customer_id, revert_qty, revert_times_used, revert_time, created_by, multi_day) values "
                            . "('{$event_id}', '{$email}', {$customerId}, {$playLimit}, 1, date_add(now(), interval {$revertTurnTime} second), 'vannguyen', {$multiDay});";
                } else {
//                    $nextTimeAmount = 7 * 3600 + $revertTurnTime;
//                    $nextTimeValue = date("Y-m-d H:i:s", strtotime('+' . $nextTimeAmount . ' seconds'));
//
//                    if ($eventDetail['revertTurnPoint'])
//                    {
//                        $nextTime =  strtotime($nextTimeValue);
//                        $revertTurnPoint = explode(",", $eventDetail['revertTurnPoint']);
//                        $cur_hour = date('H', $nextTime);
//                        if (in_array($cur_hour, $revertTurnPoint))
//                        {
//                           $cur_date = date('Y-m-d');
//                           $expected_time = $cur_date . " " . $cur_hour . ":00:00";
//                           $nextTimeValue = date("Y-m-d H:i:s", strtotime($expected_time)) ;
//                        }
//                    }
                    $nextTimeValue = $this->calculateRevertTime($eventDetail);
                    $nextTimeRevert = $nextTimeValue;
                    $insertLogTurn = "insert into fahasa_user_event_turn (event_id, email, customer_id, revert_qty, revert_times_used, revert_time, created_by) values "
                            . "('{$event_id}', '{$email}', {$customerId}, {$playLimit}, 1, '{$nextTimeValue}', 'vannguyen');";
                }
            }
            Mage::log("*** event: insert with revert time: eventId=" . $event_id . ", email=" . $email . ", sql=".$insertTurn . ", " . $insertLogTurn, null, "events.log");
            $rs1 = $write->exec($insertTurn);
            if ($rs1 > 0) {
                if (empty($insertLogTurn)){
                    return array(
                        "success" => false,
                        );
                }
                else{
                    $update_times_gift = "update fahasa_event_gift set times_played = times_played + 1 where event_id = :event_id and name = :gift_code ";
                    $bind_gift = array(
                        'event_id' => $event_id,
                        'gift_code' => $gift
                    );
                    $write->query($update_times_gift, $bind_gift);
                    
                    $rs2 = $write->exec($insertLogTurn);
                    if ($rs2 > 0) {
                        return array(
                            "success" => true,
                            "remainQty" => $remainQty,
                            "nextTimeRevert" => $nextTimeRevert
                        );
                    }    
                }
            }

            return array(
                "success" => false,
            );
        } catch (Exception $ex) {
            Mage::log("*** event: insert play with revert failed. email=" . $email . ",gift=" . $gift . ", message=" . $ex->getMessage(), null, "events.log");
        }
    }
    
    public function calculateRevertTime($eventDetail)
    {
        $revertTurnTime = $eventDetail['revertTurnTime'];
        $nextTimeAmount = 7 * 3600 + $revertTurnTime;
        $nextTimeValue = date("Y-m-d H:i:s", strtotime('+' . $nextTimeAmount . ' seconds'));

        if ($eventDetail['revertTurnPoint'])
        {
            $revertTurnPoint = explode(",", $eventDetail['revertTurnPoint']);
            if (sizeof($revertTurnPoint) > 0)
            {
                $cur_hour = date('H') + 7;
                $afterTimePointArr = array_filter($revertTurnPoint, function($e) use ($cur_hour){
                    if ($e > $cur_hour){
                        return $e;
                    }
                });
                if (sizeof($afterTimePointArr) > 0)
                {
                    $afterTimePointArr = array_values($afterTimePointArr);
                    //get the time is nearest
                    $nextTimePoint = $afterTimePointArr[0];
                    $cur_date = date('Y-m-d');
                    $expected_time = $cur_date . " " . $nextTimePoint . ":00:00";
                    $nextTimeValue = date("Y-m-d H:i:s", strtotime($expected_time));
                }
                else
                {
                    //get the time is the first revert point
                    $nextTimePoint = $revertTurnPoint[0];
                    $cur_date = date('Y-m-d');
                    $expected_time = $cur_date . " " . $nextTimePoint . ":00:00";
                    $nextTimeValue = date("Y-m-d H:i:s", strtotime($expected_time . ' + 1 days'));
                }
            }
        }
        return $nextTimeValue;
    }

    public function revertUserTurn($eventId, $email, $play_limit, $customer_id) {
        try {
            $write = Mage::getSingleton("core/resource")->getConnection("core_write");
            $revertSql = "update fahasa_user_event_turn set revert_qty={$play_limit}, revert_times_used=0, revert_time = null where event_id='{$eventId}' and customer_id='{$customer_id}';";
            Mage::log("*** event: revert user turn. eventId=" . $eventId . ", customer_id=" . $customer_id . ", sql = " . $revertSql, null, "events.log");
            $write->query($revertSql);
        } catch (Exception $ex) {
            Mage::log("*** event: revert user turn failed. eventId=" . $eventId . ", customer_id=" . $customer_id . ", error = " . $ex->getMessage(), null, "events.log");
        }
    }
    
    public function updateCouponCodeInUserLog($eventId, $email, $attendCode, $couponCode, $customerId) {
        try {
            $read = Mage::getSingleton("core/resource")->getConnection("core_read");
            $selectSql = "select id from fahasa_user_event_log where event_id='{$eventId}' and customer_id={$customerId} and attend_code='{$attendCode}' order by id desc limit 1;";
            $id = $read->fetchOne($selectSql);
            
            if ($id) {
                $write = Mage::getSingleton("core/resource")->getConnection("core_write");
                $updateSql = "update fahasa_user_event_log set coupon_code='{$couponCode}' where id = {$id};";
                
                Mage::log("*** event: update coupon success: eventId=". $eventId . ", customer_id=". $customerId . "attendCode=". $attendCode . ", couponCode=". $couponCode . ", sql=". $updateSql, null, "events.log");
                $write->query($updateSql);
            } else {
                Mage::log("*** event: no row in table for update coupon: eventId=". $eventId . ", customer_id=". $customerId . "attendCode=". $attendCode . ", couponCode=". $couponCode, null, "events.log");
            }
        } catch (Exception $ex) {
            Mage::log("*** event: Exception for update coupon: eventId=" . $eventId . ", customer_id=" . $customerId . "attendCode=" . $attendCode . ", couponCode=" . $couponCode . ", message=". $ex->getMessage(), null, "events.log");
        }
    }
    
    public function shareCmsPageLog($eventId, $sharedLink, $channel) {
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        $email = $customer->getEmail();
        $customerId = $customer->getId();

        $writeConnection = Mage::getSingleton("core/resource")->getConnection("core_write");
        $insertQuery = "insert into fahasa_event_share_log (event_id, email, customer_id, created_at, created_by, channel, share_source, share_link) values "
                . "('{$eventId}', '{$email}', {$customerId}, now(), 'vannguyen', '{$channel}', 'facebook', '{$sharedLink}');";
        try {
            $writeConnection->query($insertQuery);
            Mage::log("*** event: insert share cms_page log success: eventId=" . $eventId . ", customer_id=" . $customerId . ", share_source=facebook, share_url" . $sharedLink, null, "events.log");
        } catch (Exception $ex) {
            Mage::log("*** event: insert share cms_page log failed: eventId=" . $eventId . ", customer_id=" . $customerId . ", share_source=facebook, share_ur" . $sharedLink . ", message = " . $ex->getMessage(), null, "events.log");
        }
    }
    
    /**
     *  WARNING ! MUST add static variable to prevent infinite loop when use this function
     *  
     *  Apply a custom discount
     *  $quote : Mage_Sales_Model_Quote
     */
    public function applyCustomDiscount($quote, $discountAmount, $discount_description){
	///Mage::log("Apply custom", null, "a.log");
        	
        if ($discountAmount <= 0) {
            return;
        }
        
        $quote->setSubtotal(0);
        $quote->setBaseSubtotal(0);
        
        $quote->setSubtotalWithDiscount(0);
        $quote->setBaseSubtotalWithDiscount(0);

        $quote->setGrandTotal(0);
        $quote->setBaseGrandTotal(0);

        $canAddItems = $quote->isVirtual() ? ('billing') : ('shipping');

        foreach ($quote->getAllAddresses() as $address) {
            $address->setSubtotal(0);
            $address->setBaseSubtotal(0);

            $address->setGrandTotal(0);
            $address->setBaseGrandTotal(0);

            $address->collectTotals();

            $quote->setSubtotal((float) $quote->getSubtotal() + $address->getSubtotal());
            $quote->setBaseSubtotal((float) $quote->getBaseSubtotal() + $address->getBaseSubtotal());

            $quote->setSubtotalWithDiscount(
                    (float) $quote->getSubtotalWithDiscount() + $address->getSubtotalWithDiscount()
            );
            $quote->setBaseSubtotalWithDiscount(
                    (float) $quote->getBaseSubtotalWithDiscount() + $address->getBaseSubtotalWithDiscount()
            );

            $quote->setGrandTotal((float) $quote->getGrandTotal() + $address->getGrandTotal());
            $quote->setBaseGrandTotal((float) $quote->getBaseGrandTotal() + $address->getBaseGrandTotal());

            $address->setCustomDiscountAmount($discountAmount);
            $address->setCustomDiscountDesc($discount_description);
            
            $quote->setGrandTotal($quote->getBaseSubtotal() - $discountAmount)
                    ->setBaseGrandTotal($quote->getBaseSubtotal() - $discountAmount)
                    ->setSubtotalWithDiscount($quote->getBaseSubtotal() - $discountAmount)
                    ->setBaseSubtotalWithDiscount($quote->getBaseSubtotal() - $discountAmount);

            $quote->save();

            if ($address->getAddressType() == $canAddItems) {
                //echo $address->setDiscountAmount; exit;
                $address->setSubtotalWithDiscount((float) $address->getSubtotalWithDiscount() - $discountAmount);
                $address->setGrandTotal((float) $address->getGrandTotal() - $discountAmount);
                $address->setBaseSubtotalWithDiscount((float) $address->getBaseSubtotalWithDiscount() - $discountAmount);
                $address->setBaseGrandTotal((float) $address->getBaseGrandTotal() - $discountAmount);
                if ($address->getDiscountDescription()) {
                    $address->setDiscountAmount($address->getDiscountAmount() - $discountAmount);
                    $address->setDiscountDescription($address->getDiscountDescription() . ',' .$discount_description);
                    $address->setBaseDiscountAmount($address->getBaseDiscountAmount() - $discountAmount);
                } else {
                    $address->setDiscountAmount(-($discountAmount));
                    $address->setDiscountDescription($discount_description);
                    $address->setBaseDiscountAmount(-($discountAmount));
                }
                $address->save();
            }
        }
        
        /*
        $total = $quote->getBaseSubtotal();
        foreach ($quote->getAllItems() as $item) {
            //We apply discount amount based on the ratio between the GrandTotal and the RowTotal
            $rat = $item->getPriceInclTax() / $total;
            $ratdisc = $discountAmount * $rat;
            $item->setDiscountAmount(($item->getDiscountAmount() + $ratdisc) * $item->getQty());
            $item->setBaseDiscountAmount(($item->getBaseDiscountAmount() + $ratdisc) * $item->getQty())->save();
            }
        */
        }
        
    public function getListUserAttend($eventId){
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "select eg.description, concat(lastname.value, ' ', firstname.value) as name "
                . "from fahasa_user_event_log ue "
                . "join fahasa_event_gift eg on eg.event_id = ue.event_id and ue.attend_code = eg.name "
                . "join fhs_customer_entity ce on ce.entity_id = ue.customer_id "
                . "left join fhs_customer_entity_varchar firstname on firstname.entity_id = ce.entity_id and firstname.attribute_id = 5 "
                . "left join fhs_customer_entity_varchar lastname on lastname.entity_id = ce.entity_id and lastname.attribute_id = 7 "
                . "where ue.event_id='{$eventId}' "
                . "group by ue.customer_id "
                . "order by ue.created_at desc "
                . "limit 10;";
        $result = $readConnection->fetchAll($query);
        shuffle($result);
        return $result;
    }
    
    public function getPartnerCouponForEmail($giftInfo, $email, $customerName, $customerId) {
        try {
            $query = "select company_name,  coupon_code, coupon_action, discount_amount, expired_date from fhs_partner_coupon where rule_id = :ruleId "
                    . "and is_sent < if(max_send_time is null, 10000000, max_send_time) limit 1;";
            $binds = array(
                "ruleId" => $giftInfo["value"]
            );
            
            $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
            $result = $readConnection->fetchAll($query, $binds);
            if (count($result) > 0 && $result[0] != null) {
                $updateSql = "update fhs_partner_coupon set is_sent = is_sent + 1, last_updated = now() where rule_id=:ruleId and coupon_code = :couponCode;";
                $updateBinds = array(
                    "ruleId" => $giftInfo["value"],
                    "couponCode" => $result[0]["coupon_code"]
                );
                
                $writeConnection = Mage::getSingleton("core/resource")->getConnection("core_write");
                $writeConnection->query($updateSql, $updateBinds);
                
                $insertQuery = "insert into fhs_log_coupon_sent (customer_email, customer_name, coupon_code, sent_time, rule_id, campaign_id, coupon_type, order_id, customer_id) "
                        . "values (:customerEmail, :customerName, :couponCode, now(), :ruleId, null, :couponType, null, :customer_id);";
               
                $insertBinds = array(
                    "customerEmail" => $email,
                    "customerName" => $customerName,
                    "couponCode" => $result[0]['coupon_code'],
                    "ruleId" => $giftInfo["value"],
                    "couponType" => "partner",
                    "customer_id" => $customerId
                );
                $writeConnection->query($insertQuery, $insertBinds);
                return $result[0];
            }
            return false;
        } catch (Exception $ex) {
            Mage::log("*** Event - fail to get partner coupon: ruleId=" . $giftInfo["value"] . ", customer_id=" . $customerId . ", error " . $ex->getMessage(), null, "events.log");
            return false;
        }
    }
    
    public function getPreviousRevertTime($revertTime) {
        $revertTimeCondition = "";
        if ($revertTime && $revertTime > 0) {
            $curDate = strtotime(date("Y-m-d H:i:s"));
            $previousMiliSecond = $curDate - $revertTime;
            $previousDate = date("Y-m-d", $previousMiliSecond);
            $revertTimeCondition = " and created_at > '{$previousDate} 23:59:59' ";
        }
        return $revertTimeCondition;
    }
    
    public function insertLogBouns($event_id, $gift, $email, $channel, $customerId) {
        try {
            $writeConnection = Mage::getSingleton("core/resource")->getConnection("core_write");
            $insertLog = "insert into fahasa_user_event_log (event_id, email, created_at, created_by, attend_code, customer_id) "
                    . "select * from ( "
                    . "select '{$event_id}', '{$email}', now(), '{$channel}', '{$gift}', {$customerId} as temp "
                    . "where ( "
                    . "select ut.email "
                    . "from fahasa_user_event_turn ut "
                    . "join fahasa_events ev on ev.event_id = ut.event_id "
                    . "where now() between ev.date_begin and ev.date_end and ut.email = '{$email}' and ut.event_id='{$event_id}' "
                    . ") is not NULL) AS bouns";
            $writeConnection->query($insertLog);
            return true;
        } catch (Exception $ex) {
            Mage::log("Insert bouns log fail eventId=" . $event_id . ", email=" . $email . ", attendInt=" . $gift . ", message=" . $ex->getMessage(), null, "events.log");
            return false;
        }
    }

    public function revertMultiDayAndTurn($eventId, $customerEmail, $play_limit, $resetFlag, $customer_id) {
        Mage::log("**Topup Revert MultiDay and Turn when user miss check log  ".$eventId." with customer_id:". $customer_id, null, 'events.log');
        try {
            // ko update revert_time=date_add(now() vi khi ho check nhung ho ko get (play game)
            $write = Mage::getSingleton("core/resource")->getConnection("core_write");
            if ($resetFlag == 1) {
                $revertSql = "update fahasa_user_event_turn set revert_qty={$play_limit}, revert_times_used=0, multi_day = 0 where event_id='{$eventId}' and customer_id='{$customer_id}';";
            } else {
                $revertSql = "update fahasa_user_event_turn set revert_qty={$play_limit}, revert_times_used=0 where event_id='{$eventId}' and customer_id='{$customer_id}';";
            }
            Mage::log("**RevertSql MultiDay: " .$revertSql, null, 'events.log');
            $write->query($revertSql);
            return TRUE;
        } catch (Exception $e) {
            Mage::log("*** event: revert user turn failed. eventId=" . $eventId . ", customer_id=" . $customer_id . ", error = " . $e->getMessage(), null, "events.log");
            return FALSE;
        }
    }
    
    public function getStepGift($giftList, $email, $eventDetail, $customerId){
        $gift = null;
        $eventType = $eventDetail['eventType'];
        $event_id = $eventDetail['eventId'];
        $limitDay= $eventDetail['gameData']['bouns']->limitDay;
        
        $newGiftList = null;
        $listBouns = null;
        $getLastWord = null;
        $giftBouns = null;
        
        
        
        if ($eventType == 'get_fpoint') {
            foreach ($giftList as $giftListFormat) {
                if ($giftListFormat['item_index'] != null) {
                    $newGiftList[$giftListFormat['item_index']] = $giftListFormat;
                } else {
                    // lay list gift bouns
                    $stringNameArr = explode('_', $giftListFormat['name']);
                    $getLastWord = array_pop($stringNameArr);
                    $listBouns[$getLastWord] = $giftListFormat['name'];
                }
            }

            $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
            $sql = "select revert_time, multi_day from fahasa_user_event_turn where event_id = '{$event_id}' and customer_id = '{$customerId}';";
            $result = $readConnection->fetchAll($sql);
            // get_fpoint : check 2 type user : played or first
            if (count($result) > 0) {
                $multiDay = $result[0]['multi_day'];
                // played TH1 : miss check
                if ($multiDay == 0) {
                    $gift = $newGiftList[1]['name'];
                    $giftArr = array(
                        '0' => $gift,
                        '2' => $giftBouns
                    );
                    return $giftArr;
                }
                // played TH2: nhan gift hom nay va check bouns ??
                $index = $this->getIndexOfDay($multiDay, $eventDetail);
                if ($index < -1 || $index > $limitDay || $index == 0) {
                    return null;
                }
                // index == 7 , $index is previous gift index
                if ($index == $limitDay) {
                    $gift = $newGiftList[1]['name'];
                    $giftArr = array(
                        '0' => $gift,
                        '2' => $giftBouns
                    );
                    return $giftArr;
                }
                // check bouns
                $indexToday = $index + 1;
                if ($indexToday == $limitDay) {
                    $multiDayNext = $multiDay + 1;
                    $giftBouns = $listBouns[$multiDayNext] ? $listBouns[$multiDayNext] : null;
                }

                $gift = $newGiftList[$indexToday]['name'];

                $giftArr = array(
                    '0' => $gift,
                    '2' => $giftBouns
                );
                
                return $giftArr;
            }
            
            $gift = $newGiftList[1]['name'];
            $giftArr = array(
                '0' => $gift,
                '2' => $giftBouns
            );
            return $giftArr;
        } else {
            return null;
        }
    }
    
    public function getIndexOfDay($multiDay, $eventDetail){
        $dateBegin = date_create($eventDetail["dateBegin"]);
        $dateEnd = date_create($eventDetail["dateEnd"]);
        $interval = date_diff($dateBegin, $dateEnd);
        $totalActiveDate = $interval->days;

        // kiem tra stt ngay 1 den 7
            
        if (!isset($multiDay) || empty($multiDay) || $multiDay >= $totalActiveDate) {
            return -1;
        }

        $limitDate = $eventDetail['gameData']["bouns"]->limitDay;

        $stepIndex = $multiDay % $limitDate;

        $index = $stepIndex == 0 ? $limitDate : $stepIndex;

        return $index;
    }
    
    public function getCategoryNameByCatIds(){
        $result = array();
        $catIdsString = Mage::getStoreConfig('game/voteproduct/catIdsUsed'); 
        
        $query = "select c.entity_id, name.value as name "
                . "from fhs_catalog_category_entity c "
                . "join fhs_catalog_category_entity_varchar name on c.entity_id = name.entity_id and name.store_id = 0 and name.attribute_id = 41 "
                . "where c.entity_id in (" .$catIdsString . ") ";
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        
        $data = $readConnection->fetchAll($query);
        $temp = array();
        foreach($data as $cat){
            $temp[] = array(
                "catId" => $cat["entity_id"],
                "name" => $cat["name"],
            );
        }
        
        $catId_arr = explode(",", $catIdsString);
        foreach ($catId_arr as $catId)
        {
            $cur_cate = array_filter($temp, function($e) use ($catId) {
                if ($e["catId"] == $catId)
                {
                    return $e;
                }
            });
            if (count($cur_cate) > 0)
            {
                $cur_cate = array_values($cur_cate);
                $result[] = array(
                    "catId" => $catId,
                    "name" => $cur_cate[0]["name"],
                );
            }
        }

        if (Mage::getStoreConfig('game/voteproduct/enable_cat_rest'))
        {
            $result[] = array(
                "catId" => "rest", // it must be rest because the getTopVoteByCat handled based on this value
                "name" => "Thể loại khác"
            );
        }

        return $result;
    }
    
    public function getProductVotedByCustomer(){
        $result = array();
        $link_event = null;
        if (Mage::getSingleton('customer/session')->isLoggedIn())
        {
            $eventId = Mage::getStoreConfig('game/voteproduct/eventId');
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $customerId = $customer->getId();
            $game_data = $this->getEventDetail($eventId);
            $play_limit = $game_data["playLimit"];
            
            try {
                $query = "select ue.id, name.value as name, author.value as author from "
                        . "fahasa_user_event_log ue "
                        . "left join fhs_catalog_product_entity_varchar name on name.entity_id = ue.attend_int and name.attribute_id = 71 "
                        . "left join fhs_catalog_product_entity_varchar author on author.entity_id = ue.attend_int and author.attribute_id = 141 "
                        . "where ue.customer_id = :customer_id and event_id = :event_id "
                        . "order by ue.id "
                        . "limit " . $play_limit ;
                
                $params = array(
                    "customer_id" => $customerId,
                    "event_id" => $eventId,
                );
                $read = Mage::getSingleton('core/resource')->getConnection('core_read');
                $result = $read->fetchAll($query, $params);
                $remain_number = $play_limit - count($result);
                if ($remain_number > 0){
                    for ($i = 0; $i < $remain_number; $i++){
                        $result[] = new stdClass();
                    }
                }
                $link_other_event = Mage::getStoreConfig('game/voteproduct/link_other_event');
                if ($link_other_event){
                    $linked_event_id = Mage::getStoreConfig("game/voteproduct/linked_event_id");
                    $link_event["event_id"] = $linked_event_id;
                    
                    $query = "select * from fahasa_user_event_log where event_id = :event_id and customer_id = :customer_id";
                    $linked_params = array(
                        "event_id" => $linked_event_id,
                        "customer_id" => $customerId,
                    );
                   $link_rs = $read->fetchAll($query, $linked_params);
                   if (count($link_rs) > 0){
                       $link_event["attend_code"] = $link_rs[0]["attend_code"];
                   }
                }
            } catch (Exception $ex) {
                $success = false;
            }
        }
        return array(
            "success" => true,
            "data" => $result,
            "linked_event" => $link_event
        );
    }
    
    public function attendEventWithCode($eventId, $code, $channel){
        $success = false;
        if (Mage::getSingleton('customer/session')->isLoggedIn()){
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $customerId = $customer->getId();
              $insertRs = $this->insertGiftLog($eventId, $code, $customer->getEmail(), $channel, $customerId);
              if ($insertRs){
                  $success = true;
              }
        }
        return array(
            "success" => $success
        );
        
    }

    public function getMetaEventShareFB($event_name, $id){
	if(Mage::getStoreConfig('event_sharefacebook/share_render_image/is_active')){
	    if($event_name != Mage::getStoreConfig('event_sharefacebook/share_render_image/event_name')){
		return null;
	    }
	    $customer_id = 0;
	    if(!empty($id)){
		$customer_id = $this->encryptor('decrypt',$id);
		if($customer_id){
		    $customer = Mage::getModel('customer/customer')->load($customer_id);
		}
	    }
	    $background_image = Mage::getStoreConfig('event_sharefacebook/share_render_image/background_image');
	    if(empty($background_image)){
		return null;
	    }

	    $meta = [];
	    $meta['title'] = Mage::getStoreConfig('event_sharefacebook/share_render_image/title');
	    $meta['site'] = Mage::getStoreConfig('event_sharefacebook/share_render_image/site');
	    $meta['description'] = Mage::getStoreConfig('event_sharefacebook/share_render_image/description');

	    if(!empty($customer)){
		try{
		    $background_image = Mage::getBaseDir('media').'/event/'.$background_image;
		    // create background image layer
		    $img_path = pathinfo($background_image);
		    if($img_path['extension'] == 'png'){
			$image = imagecreatefrompng($background_image);
		    }else if($img_path['extension'] == 'jpep'){
			$image = imagecreatefromjpeg($background_image);
		    }
		    
		    //image size
		    $width = imagesx($image);
		    $height = imagesy($image);
		    $meta['width'] = $width;
		    $meta['height'] = $height;
		    $fontSize = 14;
		    
		    // Create text colours
//		    $black = imagecolorallocate($image, 0, 0, 0);
//		    $white = imagecolorallocate($image, 255, 255, 255);
//		    $blue = imagecolorallocate($image, 51, 204, 255);
		    $purple_light = imagecolorallocate($image, 157, 109, 255);
		    $purple_dark = imagecolorallocate($image, 82, 61, 128);
		    
		    // Text fonts
		    $FONT_REGULAR = Mage::getBaseDir('skin').'/frontend/ma_vanese/ma_vanesa2/fonts/opensans-regular.ttf';
		    $FONT_BOLD = Mage::getBaseDir('skin').'/frontend/ma_vanese/ma_vanesa2/fonts/opensans-bold.ttf';
		    if(!empty($font_regular = Mage::getStoreConfig('event_sharefacebook/share_render_image/font_regular'))){
			$FONT_REGULAR = Mage::getBaseDir('media').$font_regular;
		    }
		    if(!empty($font_bold = Mage::getStoreConfig('event_sharefacebook/share_render_image/font_bold'))){
			$FONT_BOLD = Mage::getBaseDir('media').$font_bold;
		    }
		    
		    //calc time
		    $time_ago = strtotime($customer->getCreatedAt().'+7 hour');
		    $cur_time = strtotime('+7 hour');
		    $time_elapsed = $cur_time - $time_ago;
		    $days = round($time_elapsed / 86400 );
		    
		    $full_name = $customer->getLastname().' '.$customer->getFirstname();
		    
		    //Customer bought total
		    $CustomerBoughtTotal = $this->getCustomerBoughtTotal($customer_id);
		    
		    // Write Customer Name
		    //$this->writeText($image, 50, 14, $blue, $FONT_BOLD, $customer_name);
		    imagettftext($image, $fontSize, 0, 358, 221, $purple_light, $FONT_BOLD, $full_name);

		    //Write date created account 
		    imagettftext($image, $fontSize, 0, 328, 265, $purple_dark, $FONT_BOLD, date('d/m/Y',strtotime('+7 hour')));

		    //Write days created account 
		    imagettftext($image, $fontSize, 0, 520, 265, $purple_light, $FONT_BOLD, $days." ngày");

		    //Write bought total
		    imagettftext($image, $fontSize, 0, 414, 335, $purple_light, $FONT_BOLD, number_format($CustomerBoughtTotal, 0, ",", "."). " đồng");
		    
		    if(Mage::getStoreConfig('event_sharefacebook/share_render_image/is_render_file')){
			$image_link = "wysiwyg/share/".$id.".png";
			imagepng($image, Mage::getBaseDir('media')."/".$image_link);
			imagedestroy($image);
			$meta['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$image_link."?q=".Mage::getStoreConfig('bubble_queryfier/suffix_js_css/suffix');
		    }else{
			ob_start();
			imagepng($image);
			$meta['image'] = "data:image/png;base64,".base64_encode(ob_get_clean());
		    }
		}catch (Exception $ex) {}
	    }
	    if(empty($meta['image'])){
		$meta['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).Mage::getStoreConfig('event_sharefacebook/share_render_image/image_default');
		$meta['width'] = Mage::getStoreConfig('event_sharefacebook/share_render_image/image_default_width');
		$meta['height'] = Mage::getStoreConfig('event_sharefacebook/share_render_image/image_default_height');
	    }

	    return $meta;
	}
    }
    
    public function encryptor($action, $string) {
	$output = false;

	$encrypt_method = "AES-256-CBC";
	//pls set your unique hashing key
	$secret_key = 'fahasa.com';
	$secret_iv = 'fahasa389';

	// hash
	$key = hash('sha256', $secret_key);

	// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	$iv = substr(hash('sha256', $secret_iv), 0, 16);

	//do the encyption given text/string/number
	if( $action == 'encrypt' ) {
	    $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
	    $output = base64_encode($output);
	}
	else if( $action == 'decrypt' ){
	    //decrypt the given text/string/number
	    $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	}

	return $output;
    }
    
    public function writeText($image, $y, $fontSize, $fontColour, $fontPath, $text){
        $x = $this->getXcoordinate($image, $fontSize, $fontPath, $text);
 
        imagettftext($image, $fontSize, 0, $x, $y, $fontColour, $fontPath, $text);
    }
 
    public function getXcoordinate($image, $fontSize, $fontPath, $text){
        $dimensions = imagettfbbox($fontSize, 0, $fontPath, $text);
        $width = abs($dimensions[4] - $dimensions[0]);
 
        return round((imagesx($image) - $width) / 2);
    }
    
    public function getCustomerBoughtTotal($customer_id){
	$result = 0;
	if(!empty($customer_id)){
	    try{
		$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
		$sql = "select sum(grand_total) as 'total' from fhs_sales_flat_order where status = 'complete' and customer_id = '".$customer_id."';";
		$data = $reader->fetchRow($sql);
		if(!empty($data['total'])){
		    $result = round($data['total'], 0);
		}
	    }catch(Exception $ex){}
	}
	return $result;
    }
    
    //IRS = Image render share
    public function getGilftIRS($sharedLink, $channel = 'web'){
	$result = [];
	$result['success'] = true;
	$result['result'] = false;
	$result['img_background'] = '';
	$result['msg'] = '';
	try{
	    $customer = Mage::getSingleton('customer/session')->getCustomer();
	    if(empty($customer->getEntityId())){
		return $result;
	    }
	    
	    if(Mage::getStoreConfig('event_sharefacebook/share_render_image/is_active_gift')){
		$from_date = Mage::getStoreConfig('event_sharefacebook/share_render_image/from_date');
		$to_date = Mage::getStoreConfig('event_sharefacebook/share_render_image/to_date');
		if(date('Y-m-d H:i:s', strtotime('+7 hours')) < date('Y-m-d H:i:s',strtotime($from_date)) 
		|| date('Y-m-d H:i:s', strtotime('+7 hours')) > date('Y-m-d H:i:s',strtotime($to_date))){
		    return $result;
		}
		
		$customer_id = $customer->getEntityId();
		$email = $customer->getEmail();
		$event_id = Mage::getStoreConfig('event_sharefacebook/share_render_image/event_name');
		$shared_time = $this->getSharedTimeInToday($event_id, $customer_id);
		$limit_share = Mage::getStoreConfig('event_sharefacebook/share_render_image/limit_share');
		if($shared_time < $limit_share){
		    $this->shareCmsPageLog($event_id, $sharedLink, $channel);
		}
		
		//check time in list have gifts
		$shared_time_total = $this->getSharedTimeTotal($event_id, date('Y-m-d H:i:s',strtotime($from_date)), date('Y-m-d H:i:s',strtotime($to_date)));
		$turn_have_gift = Mage::getStoreConfig('event_sharefacebook/share_render_image/turn_have_gift');
		$turn_have_gift = explode(",", $turn_have_gift);
		
		$image_background_alert = Mage::getStoreConfig('event_sharefacebook/share_render_image/image_background_alert');
		$result['img_background'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$image_background_alert;
		
		if($channel == 'web'){
		    //get image size
		    $background_image = Mage::getBaseDir('media')."/".$image_background_alert;
		    // create background image layer
		    $img_path = pathinfo($background_image);
		    if($img_path['extension'] == 'png'){
			$image = imagecreatefrompng($background_image);
		    }else if($img_path['extension'] == 'jpep'){
			$image = imagecreatefromjpeg($background_image);
		    }
		    //image size
		    $result['img_backgroud_width'] = imagesx($image);
		    $result['img_backgroud_height'] = imagesy($image);
		}
		
		if(in_array($shared_time_total , $turn_have_gift) && $shared_time < $limit_share){
		    $rule_id = Mage::getStoreConfig('event_sharefacebook/share_render_image/ruleid');
		    $couponCode = Mage::helper('coreextended')->getAvailableCouponCode($rule_id);
		    if (!$couponCode) {
			$result['msg'] = Mage::getStoreConfig('event_sharefacebook/share_render_image/msg_no_gift');
			$result['msg'] = str_replace("%s", $shared_time_total, $result['msg']);
			return $result;
		    }
		    
		    if(!$this->setCouponSent($couponCode)){
			$result['msg'] = Mage::getStoreConfig('event_sharefacebook/share_render_image/msg_no_gift');
			$result['msg'] = str_replace("%s", $shared_time_total, $result['msg']);
			return $result;
		    }
		    
		    //push noti
		    $title = Mage::getStoreConfig('event_sharefacebook/share_render_image/msg_has_gift_noti_title');
		    $message = Mage::getStoreConfig('event_sharefacebook/share_render_image/msg_has_gift_noti_content');
		    $message = str_replace("%s", $couponCode, $message);

		    Mage::helper("fahasa_customer")->pushNotification($email, $title, $message, $couponCode, 'coupon', '', $customer_id);

		    $result['msg'] = Mage::getStoreConfig('event_sharefacebook/share_render_image/msg_has_gift');
		    $result['result'] = true; 
		}else{
		    $result['msg'] = Mage::getStoreConfig('event_sharefacebook/share_render_image/msg_no_gift');
		}
		$result['msg'] = str_replace("%s", $shared_time_total, $result['msg']);
	    }
	}catch (Exception $ex){}
	return $result;
    }
    public function getSharedTimeInToday($event_id, $customer){
	$result = 0;
	try {
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "SELECT count(*) as 'count' FROM fahasa_event_share_log where customer_id = ".$customer." and event_id = '".$event_id."' and DATE(created_at) = CURDATE();";
	    $data = $reader->fetchRow($sql);
	    if(!empty($data['count'])){
		$result = $data['count'];
	    }
	} catch (Exception $ex) {}
	return $result;
    }
    public function getSharedTimeTotal($event_id, $start_time, $stop_time){
	$result = 0;
	try {
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "SELECT count(*) as 'total' FROM fahasa_event_share_log where event_id = '".$event_id."' and created_at between '".$start_time."' and '".$stop_time."';";
	    $data = $reader->fetchRow($sql);
	    if(!empty($data['total'])){
		$result = $data['total'];
	    }
	} catch (Exception $ex) {}
	return $result;
    }
    public function setCouponSent($coupon_code){
	$result = false;
	try {
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $sql = "update fhs_salesrule_coupon set sent = 1 where code = '".$coupon_code."';";
	    $writer->query($sql);
	    $result = true;
	} catch (Exception $ex) {}
	return $result;
    }
}


