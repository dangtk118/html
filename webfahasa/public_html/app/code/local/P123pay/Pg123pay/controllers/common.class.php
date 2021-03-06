<?php
/**
 * 123Pay Merchant Service
 * @package		miservice
 * @subpackage 	common.class.php
 * @copyright	Copyright (c) 2012 VNG
 * @version 	1.0
 * @author 		quannd3@vng.com.vn (live support; zingchat:kibac2001, yahoo:kibac2001, Tel:0904904402)
 * @created 	01/10/2012
 * @modified 	05/10/2012
 */
 ?>
<?php
include_once 'rest.client.class.php';
class Common{ 
	public static function toString($o)
	{
		echo '<pre>';
		print_r($o);
		exit;	
	}
	public static function callRest($aConfig, $aData)
	{
		try 
		{	
			$sRawDataSign = '';
			foreach($aData as $k =>$v)
			{
				if($k != 'checksum' && $k != 'addInfo' && $k != 'description')$sRawDataSign .= $v;
			}
			$sign = sha1($sRawDataSign.$aConfig['key']);
			$aData['checksum'] = $sign;
                        Mage::log("*** 123Pay ATM callRest - controller aConfig: " . print_r($aConfig, true) , null, "123pay.log");
                        Mage::log("*** 123Pay ATM callRest - controller aData: " . print_r($aData, true) , null, "123pay.log");
                        Mage::log("*** 123Pay ATM callRest - callRest controller checksum: " . $aData['checksum'], null, "123pay.log");
			$request = new RestRequest($aConfig['url'], 'POST');
			$request->buildPostBody($aData);
			$request->execute();
			
			$http_code = $request->getHTTPCode();
			$rs->return = array();
			$rs->return['httpcode'] = $http_code;
			
			if($http_code == '200'){
				$result = json_decode($request->getResponseBody(),true);
				$rs->return = $result;
				$rs->return['httpcode'] = $http_code;
			}else{
				$rs->return['message'] = $request->getResponseBody();
			}
			
			return $rs;
		}catch (Exception $fault) {
                        Mage::log("*** 123Pay ATM controllers - callRest Exception", null, "123pay.log");
                        Mage::log("*** 123Pay ATM controllers - callRest Exception1: " . $fault->getTraceAsString(), null, "123pay.log");
                        Mage::log("*** 123Pay ATM controllers - callRest Exception2: " . $fault, null, "123pay.log");
			throw $fault;
		}
	}
}
?>