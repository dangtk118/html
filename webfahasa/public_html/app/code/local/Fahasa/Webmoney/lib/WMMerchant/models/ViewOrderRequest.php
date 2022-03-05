<?php
/**
 * @Author: toan.nguyen
 * @Date:   2016-02-21 14:42:57
 * @Last Modified by:   hgiasac
 * @Last Modified time: 2016-02-26 23:00:52
 */
namespace WMMerchant\models;

use WMMerchant\base\RequestModel;

/**
 * Class contain data for sending POST request to webmoney server
 */
class ViewOrderRequest extends RequestModel {
	/**
	 * Transaction ID
	 *
	 * @var string
	 */
	public $mTransactionID;


	/*
	 * Attributes labels
	 */
	public function attributeLabels() {
		return array(
			'mTransactionID' => 'Transaction ID',
		);
	}

	public function getAttributes() {
		return array(
			'mTransactionID' => $this->mTransactionID,
		);
	}

	/**
	 * Return attribute names that will be used for encrypting checksum
	 *
	 * @return array
	 */
	public function hashAttributes() {
		return array('mTransactionID');
	}
}
