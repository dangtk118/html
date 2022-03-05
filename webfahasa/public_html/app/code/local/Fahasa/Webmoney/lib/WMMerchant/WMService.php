<?php
namespace WMMerchant;

use WMMerchant\base\Curl;
use WMMerchant\base\Security;
use WMMerchant\base\NetHelper;
use WMMerchant\base\ResponseModel;
use WMMerchant\models\CreateOrderResponse;
use WMMerchant\models\ViewOrderResponse;

/**
 * Webmoney service helpers
 */
class WMService {

    const WMMERCHANT_HOST = 'https://apimerchant.webmoney.com.vn/';
    const WMMERCHANT_HOST_TEST = 'http://apimerchant.webmoney.prj/';

    const PRODUCTION_PATH = 'payment';
    const SANDBOX_PATH = 'sandbox';

    const SUCCESS_STATUS = 'WM_SUCCESS';
    const FAILED_STATUS = 'WM_FAILED';
    const WAITING_STATUS = 'WM_WAITING';
    const CANCELED_STATUS = 'WM_CANCELED';



    /**
     * Switch between production and test mode
     *
     * @var boolean
     */
    public $production_mode;

    /**
     * If this client using local debug
     *  This property is used by Webmoney developer only, so you don't need caring about this
     *
     * @var boolean
     */
    public $is_local_test;

    /**
     * Merchant code
     * Provided by Webmoney system
     *
     * @var string
     */
    public $merchant_code;

    /**
     * Merchant passcode
     * Provided by Webmoney system
     *
     * @var string
     */
    public $passcode;
    /**
     * Merchant secret key
     * Provided by Webmoney system
     * Used for encrypting and compare checksum
     *
     * @var string
     */
    public $secret_key;

    /**
     * Set passcode and secret while initialization
     *
     * @param string $passcode Merchant passcode
     * @param string $secret   Merchant secret key
     */
    public function __construct($settings) {
        if (empty($settings)) {
            throw new \Exception("Empty webmoney merchant settings data");
        }
        if (empty($settings['merchant_code'])) {
            throw new \Exception("merchant_code of WMService is empty");
        }
        if (empty($settings['passcode'])) {
            throw new \Exception("Passcode of WMService is empty");
        }

        if (empty($settings['secret_key'])) {
            throw new \Exception("Secret key of WMService is empty");
        }

        $this->passcode = $settings['passcode'];
        $this->merchant_code = $settings['merchant_code'];
        $this->secret_key = $settings['secret_key'];
        $this->production_mode = !empty($settings['production_mode']);
        $this->is_local_test = !empty($settings['is_local_test']);
    }

    /**
     * Construct new curl object with application/json header
     *
     * @return Curl curl object
     */
    public function getCurl() {
        $curl = new Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'Authorization:' . $this->passcode,
            'X-Forwarded-Host:' . $_SERVER['HTTP_HOST'],
            'X-Forwarded-For:' . $_SERVER['SERVER_ADDR'],
            'Referer:' . NetHelper::getCurrentURL(),
        ));
        $curl->setOption(CURLOPT_TIMEOUT, 200);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 200);
        // temporarily disables SSL verifying
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        return $curl;
    }

    /**
     * Validates passcode and secret key
     *
     */
    protected function validateCodes() {
        if (empty($this->passcode)) {
            throw new \Exception("Passcode has not been set.");
        }
        if (empty($this->merchant_code)) {
            throw new \Exception("merchant_code has not been set.");
        }

        if (empty($this->secret_key)) {
            throw new \Exception("secret_key has not been set.");
        }
    }

    /**
     * Get status codes
     *
     * @return array
     */
    public static function statusCodes() {
        return array(
            self::WAITING_STATUS => 'Waiting',
            self::SUCCESS_STATUS => 'Success',
            self::FAIL_STATUS => 'Failed',
            self::CANCELED_STATUS => 'Canceled',
        );
    }

    /**
     * Get status code from code list
     *
     * @param  int $code status code as integer
     * @return string       status code as string
     */
    public static function getStatusCode($code) {
        $codes = self::statusCodes();

        if (!empty($codes[$code])) {
            return $codes[$code];
        }

        throw new Exception("Invalid status code: " . $code);
    }
    /**
     * Create target API URL
     *
     * @param  string $action Action name
     *
     * @return string         REST URL
     */
    public function createURL($action) {
        $host = $this->is_local_test ? self::WMMERCHANT_HOST_TEST : self::WMMERCHANT_HOST;
        $mode = $this->production_mode ? self::PRODUCTION_PATH : self::SANDBOX_PATH;
        return $host . $mode . '/' . $action;
    }
    /**
     * Parse transaction redirect result URL
     *
     * @return mixed TRUE if no error, error string if validate data failed
     */
    public function validateResultURL($status) {
        $this->validateCodes();

        if (empty($_GET['transaction_id'])) {
            return 'Empty transaction id';
        }

        if (empty($_GET['checksum'])) {
            return 'Empty checksum';
        }
        $text = $_GET['transaction_id'] . $status . $this->merchant_code . $this->passcode;
        $hash = Security::hashChecksum($text, $this->secret_key);
        if ($hash !== $_GET['checksum']) {
            return 'Invalid checksum.';
        }

        return true;
    }

    /**
     * Parse transaction redirect success URL
     *
     * @return mixed TRUE if no error, error string if validate data failed
     */
    public function validateSuccessURL() {
        return $this->validateResultURL(self::SUCCESS_STATUS);
    }

    /**
     * Parse transaction redirect failed URL
     *
     * @return mixed TRUE if no error, error string if validate data failed
     */
    public function validateFailedURL() {
        return $this->validateResultURL(self::FAILED_STATUS);
    }


    /**
     * Parse transaction redirect canceled URL
     *
     * @return mixed TRUE if no error, error string if validate data failed
     */
    public function validateCanceledURL() {
        return $this->validateResultURL(self::CANCELED_STATUS);
    }


    /**
     * Create order request
     *
     * @param  WMMerchant\models\CreateOrderRequest $request Request data
     *
     * @return WMMerchant\base\ResponseMmodel           Response model
     */
    public function createOrder($request) {
        $url = $this->createURL('create-order');
        $this->validateCodes();

        $curl = $this->getCurl();
        $request->clientIP = NetHelper::getIPAddress();
        $request->userAgent = $_SERVER['HTTP_USER_AGENT'];
        $request->hashChecksum($this);
        $json_data = json_encode($request);
        $resp = $curl->setOption(CURLOPT_POSTFIELDS, $json_data)->post($url, true);

        $response = ResponseModel::load($resp, new CreateOrderResponse);

        return $response;
    }

    /**
     * View order request
     *
     * @param  WMMerchant\models\CreateOrderRequest $request Request data
     *
     * @return WMMerchant\base\ResponseMmodel           Response model
     */
    public function viewOrder($request) {

        $url = $this->createURL('view-order');
        $this->validateCodes();

        $curl = $this->getCurl();
        $request->hashChecksum($this);
        $json_data = json_encode($request);
        $resp = $curl->setOption(CURLOPT_POSTFIELDS, $json_data)->post($url, true);

        $response = ResponseModel::load($resp, new ViewOrderResponse);

        return $response;
    }
    
    public function callRestFahasa($url,$data) {
        $json_data = json_encode($request);
        $resp = $curl->setOption(CURLOPT_POSTFIELDS, $json_data)->post($url, true);
    }
}
