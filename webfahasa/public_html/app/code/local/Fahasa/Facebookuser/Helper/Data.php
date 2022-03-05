<?php

class Fahasa_Facebookuser_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getFacebookEmail($facebookId) {
        $facebookUser = Mage::getModel("facebookuser/facebookuser")->load($facebookId);
        if ($facebookUser->getEmail() != null) {
            return $facebookUser->getEmail();
        }
        return false;
    }

    public function insertFacebookUser($facebookId, $email) {
        try {
            $insert = Mage::getModel("facebookuser/facebookuser");
            $data = array(
                "facebook_id" => $facebookId,
                "email" => $email,
                "created_at" => now()
            );
            $insert->setData($data);
            $insert->save();
            return true;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    public function updateFacebookUser($facebookId, $email, $facebookEmail = null) {
        try {
            $update = Mage::getModel("facebookuser/facebookuser")->load($facebookId);
            $update->setEmail($email);
            //only update facebookEmail in case: login facebook with facebookEmail != fhsEmail
            if ($facebookEmail && $facebookEmail != $email){
                $update->setFacebookEmail($facebookEmail);
            }
            else{
                $update->setFacebookEmail(null);
            }
            $update->setUpdateAt(now());
            $update->save();
            return true;
        } catch (Exception $e) {
            return FALSE;
        }
    }
    
    public function checkFacebookEmailExist($email){
        $facebookUser = Mage::getModel("facebookuser/facebookuser")->load($email,'email');
        if ($facebookUser != null && $facebookUser->getEmail() != null){
            return true;
        }
        return false;
    }
    
    public function checkNewFacebookEmailIsUpdated($facebookId, $newFbEmail){
        $facebookUser = Mage::getModel("facebookuser/facebookuser")->load($facebookId);
        $curFhsEmail = $facebookUser->getEmail();
        $curFbEmail = $facebookUser->getFacebookEmail();
        
        if (($curFbEmail && $newFbEmail == $curFbEmail) || (!$curFbEmail && $newFbEmail == $curFhsEmail)){
            return true;
        }
        return false;
    }
    
    public function mappingFacebookUser($facebookId, $email) {
        $model = Mage::getModel("facebookuser/facebookuser");
        $facebookUser = $model->load($facebookId);
        if ($facebookUser->getEmail() != null) {
            try{
                $facebookUser->setEmail($email);
                $facebookUser->setUpdateAt(now());
                $facebookUser->save();
                return true;
            } catch (Exception $ex) {
                Mage::log("FAIL TO UPDATE EMAIL = " .$email . ", facebookId = " . $facebookId, null, "restapi.log");
                return false;
            }
        } else {
            try {
                $data = array(
                    "facebook_id" => $facebookId,
                    "email" => $email,
                    "created_at" => now()
                );
                $model->setData($data);
                $model->save();
                return true;
            } catch (Exception $ex) {
                Mage::log("FAIL TO insert EMAIL = " .$email . ", facebookId = " . $facebookId, null, "restapi.log");
                return false;
            }
        }
    }

}
