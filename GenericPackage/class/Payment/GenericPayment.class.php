<?php

class GenericPayment
{
	const SERVICE_GMOPAY = 'GMOPaymentGatewayAgent';
	const SERVICE_WEBPAY = 'WebPayAgent';
	const SERVICE_BITCASH = 'BitCashAgent';
	const SERVICE_LINEPAY = 'GMOPaymentGatewayLinePayAgent';
	const SERVICE_SBPAY = 'GMOPaymentGatewaySBAgent';

	/**
	 * 決済処理
	 */
	public static function fixPurchase($argService, $argAccessKey, $argAccessSecret, $argToken, $argUserID, $argAmount, $argCallbackFunction, $argOptions=NULL, $argItemID=NULL, $argCapture=TRUE, $arg3DSecured=FALSE, $argTax=NULL, $argReturnURL=NULL, $argCancelURL=NULL, $argNotifyURL=NULL, $argExpireDays=NULL, $argDescription=NULL, $argUuid=NULL, $argShop=NULL){
		return $argService::fixPurchase($argAccessKey, $argAccessSecret, $argToken, $argUserID, $argAmount, $argCallbackFunction, $argOptions, $argItemID, $argCapture, $arg3DSecured, $argTax, $argReturnURL, $argCancelURL, $argNotifyURL, $argExpireDays, $argDescription, $argUuid, $argShop);
	}

	/**
	 * 決済取消
	 */
	public static function cancelPurchase($argService, $argAccessKey, $argAccessSecret, $argAccessID, $argAccessPass, $argRETURN=TRUE){
		return $argService::cancelPurchase($argAccessKey, $argAccessSecret, $argAccessID, $argAccessPass, $argRETURN);
	}
}

?>
