<?php
/**
 * @Author: toan.nguyen
 * @Date:   2016-02-21 14:42:57
 * @Last Modified by:   hgiasac
 * @Last Modified time: 2016-02-29 17:34:27
 */

namespace WMMerchant\base;

use WMMerchant\base\Model;

/**
 * Abstract model class
 * Implements some helper method, inspired by Yii framework
 *
 */
class RequestModel extends Model {

    /**
     * Checksum string for security check
     *
     * @var string
     */
    public $checksum;

    /**
     * Return attribute names that will be used for encrypting checksum
     *
     * @return array
     */
    public function hashAttributes() {
        throw new Exception("hashAttributes method is not implemented");
    }

    /**
     * Hash checksum data with sha1 algorithm
     *
     * @param  $secret Secret key for encryption
     *
     */
    public function hashChecksum($service) {
        $text = Security::joinHashText($this) . $service->merchant_code . $service->passcode;
        $this->checksum = Security::hashChecksum($text, $service->secret_key);
    }

}
