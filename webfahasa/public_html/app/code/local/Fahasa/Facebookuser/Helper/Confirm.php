<?php

class Fahasa_Facebookuser_Helper_Confirm extends Mage_Core_Helper_Abstract{
    
    public function generateRandomKey(){
        return md5(uniqid());
    }
    
    public function generateKeyConfirm($facebookId) {
        try{
            $insert = Mage::getModel("facebookuser/facebookconfirm");
            $key = $this->generateRandomKey();
            $data = array (
                "facebook_id" => $facebookId,
                "facebook_key" => $key
            );
            $insert->setData($data);
            $insert->save();
            return $key;
        } catch (Exception $ex) {
            return false;
        }
    }
    
    public function getFacebookId($facebookKey){
        $model = Mage::getModel("facebookuser/facebookconfirm");
        $facebookConfirm = $model->load($facebookKey,'facebook_key');
        if ($facebookConfirm->getFacebookId() != null){
            return $facebookConfirm->getFacebookId();
        }
        return false;
    }
    
    public function generateKeyConfirmWithConfirmEmail($facebookId, $confirmEmail) {
        try{
            $insert = Mage::getModel("facebookuser/facebookconfirm");
            $key = $this->generateRandomKey();
            $data = array (
                "facebook_id" => $facebookId,
                "facebook_key" => $key,
                "confirm_email" => $confirmEmail
            );
            $insert->setData($data);
            $insert->save();
            return $key;
        } catch (Exception $ex) {
            return false;
        }
    }
    
    public function getConfirmEmail($facebookKey) {
        $model = Mage::getModel("facebookuser/facebookconfirm");
        $facebookConfirm = $model->load($facebookKey, 'facebook_key');
        if ($facebookConfirm->getConfirmEmail() != null)
        {
            return $facebookConfirm->getConfirmEmail();
        }
        return false;
    }

}