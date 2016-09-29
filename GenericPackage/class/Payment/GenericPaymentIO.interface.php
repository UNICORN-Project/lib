<?php

/**
 * 決済クラスインターフェース
 * @author saimushi
 */
interface GenericPaymentIO
{
	/**
	 * 決済処理
	 * @param string $argAccessKey
	 * @param string $argAccessSecret
	 * @param string $argToken
	 * @param mixid $argUserID
	 * @param number $argAmount
	 * @param function $argCallbackFunction 決済処理完了後のに実行する関数
	 * @param mixid $argOptions $argCallbackFunction関数への返却値に追加するオプション変数
	 * @param mixid $argItemID 課金システムの仕様に依存します。(デフォルトはNULL)
	 * @param boolean $argCapture 即時売上フラグ(デフォルトはTRUE) FALSEの場合は仮売上処理とします(課金システムに依存します。)
	 * @param string $arg3DSecured 3Dセキュア決済フラグ(デフォルトはFALSE)
	 * @param string $argTax
	 * @param string $argReturnURL Optional
	 * @param string $argCancelURL Optional
	 * @param string $argNotifyURL Optional
	 * @param string $argExpireDays Optional
	 * @param string $argDescription Optional
	 * @param string $argUuid Optional
	 * @param string $argShop Optional
	 * @throws Exception
	 * @return boolean
	 */
	public static function fixPurchase($argAccessKey, $argAccessSecret, $argToken, $argUserID, $argAmount, $argCallbackFunction, $argOptions=NULL, $argItemID=NULL, $argCapture=TRUE, $arg3DSecured=FALSE, $argTax=NULL, $argReturnURL=NULL, $argCancelURL=NULL, $argNotifyURL=NULL, $argExpireDays=NULL, $argDescription=NULL, $argUuid=NULL, $argShop=NULL);

	/**
	 * 決済取消
	 * @param string $argAccessKey
	 * @param string $argAccessSecret
	 * @param string $argAccessID
	 * @param string $argAccessPass
	 * @param string $argRETURN 返品フラグ(デフォルトはTRUE:返品する)
	 * @throws Exception
	 * @return boolean
	 */
	public static function cancelPurchase($argAccessKey, $argAccessSecret, $argAccessID, $argAccessPass, $argRETURN=TRUE);
}

?>