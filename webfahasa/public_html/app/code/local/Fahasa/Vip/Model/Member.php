<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Model holding data to determine whether it is an member
 *
 * @author phamtn8
 */
class Fahasa_Vip_Model_Member {
    
    const VIP_EMAIL_TYPE = "emailVIP";
    const VIP_ID_TYPE = "idVIP";    
    
    //where it is email, or id vip type
    public $type;
    
    public $isSSC;
    
    //Fahasa_Vip_Model_Customervip
    public $customerVip;
    
    public $customerEmail;
    public $vipId;
    public $companyId;
    public $groupId;
    public $level;
}
