<?php

/**
 * Given the purchase amount, convert this into user score, and log this tich diem
 * transaction
 *
 * @author Thang Phan
 */
class Fahasa_Tichdiem_Model_Score_Scorerule {
    
    const TICHDIEM_ADD = 0;
    const TICHDIEM_REMOVE = 1;
    const TICHDIEM_REDEEM = 2;
    const TICHDIEM_UPGRADE = 3;
    
    /**
     * 
     * @param type $amount
     * @param type $customer_email
     */
    public function tichdiem($amount, $customer_email, $incrementId){
        $userTichdiem = Mage::getModel('tichdiem/totalscore')->load($customer_email);
        $currentScore = 0;
        $member_level = 0;
        if($userTichdiem->getTotalScore() != null){           
            $currentScore = $userTichdiem->getTotalScore();
            $member_level = $userTichdiem->getMembershipLevel();
            $updateScore = $this->getNewTichdiemScoreAfterSuccessPurchase($amount, $currentScore);
            $userTichdiem->setTotalScore($updateScore);        
            $userTichdiem->setLastUpdated($this->getCurrentTime());
            $userTichdiem->setLastUpdatedBy($this->getCurAdminLoggedInUser());
            $userTichdiem->save();
        }else{
            $updateScore = $this->getNewTichdiemScoreAfterSuccessPurchase($amount, $currentScore);
            //Membership level are by default 0, which is the basic membership
            $newUserTichdiem = Mage::getModel('tichdiem/totalscore');
            $newUserTichdiem->setCustomerEmail($customer_email);
            $newUserTichdiem->setTotalScore($updateScore);        
            $newUserTichdiem->setLastUpdated($this->getCurrentTime());
            $newUserTichdiem->setLastUpdatedBy($this->getCurAdminLoggedInUser());
            $newUserTichdiem->save();            
        }
        $this->insertTichDiemTransaction(self::TICHDIEM_ADD, $member_level, $customer_email,
                $incrementId, $amount, $currentScore, $updateScore);
    }
    
    /**
     * Given the customer email, and score to subtract, update user total score.
     * This will also insert score transaction with "Remove" action
     * @param type $cust_email
     */
    private function handleUpdateTruDiem($cust_email, $scoreDelta, $incrementId, $amount){        
        $td_user = Mage::getModel('tichdiem/totalscore')->load($cust_email);
        if($td_user->getTotalScore() != null){
            $curScore = $td_user->getTotalScore();
            $newscore = $curScore - $scoreDelta;
            $td_user->setTotalScore($newscore);
            $td_user->save();
            $this->insertTichDiemTransaction(self::TICHDIEM_REMOVE, 
                        $td_user->getMembershipLevel(), 
                        $cust_email, 
                        $incrementId, 
                        $amount, $curScore, $newscore);
        } else{
            Mage::log("Score for user '" . $cust_email . "' is not existed. Increment id: '" . $incrementId . "'", 2);
        } 
    }
    
    /**
     * Given the increment_id of the order, subtract point for this order.
     * This is only happen, when the admin click "Fahasa Refund"     
     */
    public function truDiem($incrementId){
        //Get the order
        $td_trans_collection = Mage::getModel('tichdiem/scoretransaction')->getCollection();        
        $td_trans_collection->addFieldToFilter('increment_id', $incrementId)
                            ->addFieldToFilter('action', self::TICHDIEM_ADD)                            
                            ->load();        
        $results = $td_trans_collection->getData();        
        if(count($results) == 1){                        
            $td_transaction = $results[0];            
            if($td_transaction['trans_id'] != null){
                $score_before = $td_transaction['score_before'];
                $score_after = $td_transaction['score_after'];
                $scoreDelta = $score_after - $score_before;
                $amount = $td_transaction['amount'];
                if($scoreDelta >= 0){
                    $cust_email = $td_transaction['customer_email'];
                    $this->handleUpdateTruDiem($cust_email, $scoreDelta, $incrementId, $amount);                
                }else{
                    Mage::log("Score is negative. Increment Id : '" . $incrementId . 
                            "'. Score Before: '" . $score_before . "'. Score After: '" . $score_after . "'", 2);
                }                
            }else{
                Mage::log("Cannot find transaction with increment id '" . $incrementId . "' when tru diem. This must be look at immediately", 1);
            }
        }else{
            $numResult = count($results);
            Mage::log("There are $numResult item with incrementId '" . $incrementId . "' and action 'add'", 2);
        }                        
    }
    
    function getCurAdminLoggedInUser(){        
        return Mage::getSingleton('admin/session')->getUser()->getUsername();
    }
    
    /**
     * Every action related to tichdiem need to be recorded
     */
    function insertTichDiemTransaction($action, $membership, $customer_email, $incrementId,
                                    $amount, $scoreBefore, $scoreAfter){
        $scoretrans = Mage::getModel('tichdiem/scoretransaction');
        $scoretrans->setCustomerEmail($customer_email);
        $scoretrans->setMembershipLevel($membership);
        $scoretrans->setAction($action);
        $scoretrans->setIncrementId($incrementId);
        $scoretrans->setAmount($amount);
        $scoretrans->setInsertTime($this->getCurrentTime());
        $scoretrans->setInsertBy($this->getCurAdminLoggedInUser());
        $scoretrans->setScoreBefore($scoreBefore);
        $scoretrans->setScoreAfter($scoreAfter);
        $scoretrans->save();
    }
    
    /**
     * Return the current time
     * @return type
     */
    function getCurrentTime(){
        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone("Asia/Ho_Chi_Minh"));
        return $dt->format('Y-m-d H:i:s');        
    }

    /**
     * Every 50000 VND equal 1 point, rounding up.
     * @param type $currentPurchase
     * @param type $currentScore
     */
    public function getNewTichdiemScoreAfterSuccessPurchase($currentPurchase, $currentScore){
        $purchase_score = ceil($currentPurchase / 50000);
        return $currentScore + $purchase_score;
    }
}
