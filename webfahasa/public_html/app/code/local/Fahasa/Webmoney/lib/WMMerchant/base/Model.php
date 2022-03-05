<?php
/**
 * @Author: toan.nguyen
 * @Date:   2016-02-21 14:42:57
 * @Last Modified by:   hgiasac
 * @Last Modified time: 2016-02-29 08:05:21
 */

namespace WMMerchant\base;

/**
 * Abstract model class
 * Implements some helper method, inspired by Yii framework
 *
 */
class Model {

    /**
     * Set attribute value into model
     *
     * @param array  $data    array data, eg $_POST
     * @param array  $excepts Excepts attribute names
     */
    public function setAttributes($data, $excepts = array()) {
        foreach ($data as $key => $value) {
            if (in_array($key, $excepts)) {
                continue;
            }
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /*
     * Attributes labels
     *
     * @return array $key => $label
     */
    public function attributeLabels() {
        throw new Exception("attributeLabels method is not implemented");

    }

    /**
     * Get all key => value attributes
     *
     * @return array
     */
    public function getAttributes() {
        throw new Exception("getAttributes method is not implemented");
    }
}
