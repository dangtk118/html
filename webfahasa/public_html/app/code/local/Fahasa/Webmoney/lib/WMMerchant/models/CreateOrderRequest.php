<?php
/**
 * @Author: toan.nguyen
 * @Date:   2016-02-21 14:42:57
 * @Last Modified by:   hgiasac
 * @Last Modified time: 2016-02-25 15:06:32
 */
namespace WMMerchant\models;

use WMMerchant\base\RequestModel;

/**
 * Class contain data for sending POST request to webmoney server
 */
class CreateOrderRequest extends RequestModel {
	/**
	 * Merchant Transaction ID
	 *
	 * @var string
	 */
	public $mTransactionID;
	/**
	 * Customer name
	 *
	 * @var string
	 */
	public $custName;
	/**
	 * Customer address
	 * @var string
	 */
	public $custAddress;
	/**
	 * Customer email
	 *
	 * @var string
	 */
	public $custEmail;
	/**
	 * Customer gender
	 *
	 * @var string
	 */
	public $custGender;

	/**
	 * Customer Phone
	 *
	 * @var string
	 */
	public $custPhone;

	/**
	 * Order description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Total amount
	 *
	 * @var int
	 */
	public $totalAmount;

	/**
	 * Cancel URL
	 *
	 * @var string
	 */
	public $cancelURL;

	/**
	 * Result URL
	 *
	 * @var string
	 */
	public $resultURL;

	/**
	 * Error URL
	 *
	 * @var string
	 */
	public $errorURL;

	/**
	 * Addition Information
	 *
	 * @var array
	 */
	public $addInfo;

	/**
	 * Client IP address
	 *
	 * @var string
	 */
	public $clientIP;

	/**
	 * User agent of client machine
	 *
	 * @var string
	 */
	public $userAgent;

	/*
		     * Attributes labels
	*/
	public function attributeLabels() {
		return array(
			'mTransactionID' => 'Transaction ID',
			'custName' => 'Customer Name',
			'custAddress' => 'Customer Address',
			'custGender' => 'Customer Gender',
			'custEmail' => 'Customer Email',
			'custPhone' => 'Customer Phone',
			'cancelURL' => 'Cancel URL',
			'errorURL' => 'Error URL',
			'resultURL' => 'Result URL',
			'custPurse' => 'Customer Purse',
			'description' => 'Description',
			'addInfo' => 'Additional Information',
			'totalAmount' => 'Total Amount',
			'clientIP' => 'Client IP',
			'userAgent' => 'User Agent',
			'checksum' => 'Checksum',
		);
	}

	public function getAttributes() {
		return array(
			'mTransactionID' => $this->mTransactionID,
			'custName' => $this->custName,
			'custAddress' => $this->custAddress,
			'custGender' => $this->custGender,
			'custEmail' => $this->custEmail,
			'custPhone' => $this->custPhone,
			'cancelURL' => $this->cancelURL,
			'errorURL' => $this->errorURL,
			'resultURL' => $this->resultURL,
			'description' => $this->description,
			'addInfo' => $this->addInfo,
			'totalAmount' => $this->totalAmount,
		);
	}

	/**
	 * Create Order ID
	 * @param  string $prefix ID prefix
	 * @return string         Order ID at current time
	 */
	public static function createUniqueOrderId($prefix) {
		return $prefix . time();
	}

	/**
	 * Return attribute names that will be used for encrypting checksum
	 *
	 * @return array
	 */
	public function hashAttributes() {
		return array('mTransactionID', 'totalAmount', 'custName', 'custAddress', 'custGender', 'custEmail', 'custPhone', 'resultURL', 'description', 'clientIP', 'userAgent');
	}
}
