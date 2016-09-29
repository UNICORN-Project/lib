<?php

/**
 * GMO Payment Gatewayを利用してSBかんたん決済を行う
 * 
 * @author saimushi
 * @see <a href="https://www.gmo-pg.com">GMO Payment Gateway</a>
 */
class GenericGMOPaymentGatewaySBAgent extends GenericGMOPaymentGatewayAgent
{
	/**
	 * SB決済
	 */
	public static $servicePrefix = 'Sb';
	public static $orderID = NULL;
	public static $cancelAmount = 0;

	/**
	 * SB決済の取消
	 */
	public static function cancelPurchase($argAccessKey, $argAccessSecret, $argAccessID, $argAccessPass, $argRETURN=TRUE){
		// XXX SB決済の取り消しはオーダーIDが必須
		if (NULL === self::$orderID){
			// キャンル不要(不能)
			return TRUE;
		}
		// XXX SB決済の取り消しはオーダーIDが必須
		if (0 === self::$cancelAmount){
			// キャンル不要(不能)
			return TRUE;
		}
		// バリデート
		if (FALSE === strpos($argAccessSecret, '.')){
			// アクセスシークレットにはSiteIdとSitePassを連結する事が必須！
			return FALSE;
		}
		$accessSecrets = explode('.', $argAccessSecret);
		if (!isset($accessSecrets[0])){
			return FALSE;
		}
		if (!isset($accessSecrets[1])){
			return FALSE;
		}
		if (!isset($accessSecrets[2])){
			return FALSE;
		}
		$accessSecret = $accessSecrets[0];
		$siteID = $accessSecrets[1];
		$sitePass = $accessSecrets[2];

		// GMOライブラリの読み込み
		if (function_exists('getConfig') && 0 < strlen(getConfig('VENDOR_PATH'))){
			set_include_path(get_include_path().PATH_SEPARATOR.getConfig('VENDOR_PATH').'GMOPay/');
		}
		require_once( 'com/gmo_pg/client/input/SbCancelInput.php');
		require_once( 'com/gmo_pg/client/tran/SbCancel.php');
		
		//入力パラメータクラスをインスタンス化します
		$input = new SbCancelInput();
		$input->setShopID($argAccessKey);
		$input->setShopPass($accessSecret);
		$input->setAccessID($argAccessID);
		$input->setAccessPass($argAccessPass);
		$input->setOrderID(self::$orderID);
		$input->setCancelAmount(self::$cancelAmount);

		//API通信クラスをインスタンス化します
		$exe = new SbCancel();

		//パラメータオブジェクトを引数に、実行メソッドを呼び、結果を受け取ります。
		$output = $exe->exec( $input );
		//実行後、その結果を確認します。
		if( $exe->isExceptionOccured() ){//取引の処理そのものがうまくいかない（通信エラー等）場合、例外が発生します。
			throw $exception;
		}
		else{
			//例外が発生していない場合、出力パラメータオブジェクトが戻ります。
			if( $output->isErrorOccurred() ){//出力パラメータにエラーコードが含まれていないか、チェックしています。
				require_once('common/ErrorMessageHandler.php');
				$errorHandle = new ErrorHandler();
				$errorList = $output->getErrList() ;
				$errorCode = '999';
				$errorMsg = '致命的なエラー';
				if (0 < count($errorList)){
					foreach( $errorList as  $errorInfo ){
						$errorCode = $errorInfo->getErrCode();
						$errorMsg = $errorHandle->getMessage( $errorInfo->getErrInfo() );
						break;
					}
				}
				if (FALSE !== strpos($errorMsg, 'エラーコード表')){
					$errorMsg = '致命的なエラー';
				}
				throw new Exception($errorCode.':'.$errorMsg, (int)$errorCode);
			}
		}
		// 正常終了
		return TRUE;
	}
}

?>