<?php

/**
 * GMO Payment Gatewayを利用して決済を行う
 * 
 * @author saimushi
 * @see <a href="https://www.gmo-pg.com">GMO Payment Gateway</a>
 */
class GenericGMOPaymentGatewayAgent implements GenericPaymentIO
{
	/**
	 * @var number 最低課金額 50円以下の決済は出来ない
	 */
	public static $_minimumChargeAmount = 50;

	/**
	 * @var number 最高課金額 1000万以上の決済は出来ない
	 */
	public static $_maximumChargeAmount = 10000000;

	/**
	 * 決済処理
	 */
	public static function fixPurchase($argAccessKey, $argAccessSecret, $argToken, $argUserID, $argAmount, $argCallbackFunction, $argOptions=NULL, $argItemID=NULL, $argCapture=TRUE, $arg3DSecured=FALSE, $argTax=NULL, $argReturnURL=NULL, $argCancelURL=NULL, $argNotifyURL=NULL, $argExpireDays=NULL, $argDescription=NULL, $argUuid=NULL, $argShop=NULL){
		$cardToken = NULL;
		// バリデート
		if (NULL === $argAmount || (int)$argAmount < self::$_minimumChargeAmount || self::$_maximumChargeAmount < (int)$argAmount || (int)$argAmount < 0) {
			// 金額エラー
			return FALSE;
		}
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
		require_once( 'com/gmo_pg/client/input/EntryTranInput.php');
		require_once( 'com/gmo_pg/client/input/ExecTranInput.php');
		require_once( 'com/gmo_pg/client/input/EntryExecTranInput.php');
		require_once( 'com/gmo_pg/client/tran/EntryExecTran.php');
		//入力パラメータクラスをインスタンス化します
		//取引登録時に必要なパラメータ
		$entryInput = new EntryTranInput();
		$entryInput->setShopId($argAccessKey);
		$entryInput->setShopPass($accessSecret);
		// 処理区分
		if (FALSE !== $argCapture){
			// 即時決済
			$entryInput->setJobCd('CAPTURE');
		}
		else {
			// 仮売上
			$entryInput->setJobCd('AUTH');
		}
		if (NULL !== $argItemID){
			if (is_numeric($argItemID) && 7 > strlen((string)$argItemID)){
				$argItemID = str_pad($argItemID, 7, 0, STR_PAD_LEFT);
			}
			$entryInput->setItemCode($argItemID);
		}
		$entryInput->setAmount($argAmount);
		if (NULL !== $argTax){
			$entryInput->setTax($argTax);
		}
		// デフォルトは無し
		if (TRUE === $arg3DSecured){
			// 3Dセキュアで処理
			$entryInput->setTdFlag(1);
		}
		if (NULL !== $argShop){
			$entryInput->setTdTenantName($argShop);
		}

		//決済実行のパラメータ
		$execInput = new ExecTranInput();
		// 支払方法に応じて、支払回数のセット要否が異なります。
		// XXX 一旦一括固定
		$execInput->setMethod(1);
// 		$method = $_POST['PayMethod'];
// 		$execInput->setMethod( $method );
// 		if( $method == '2' || $method == '4'){//支払方法が、分割またはボーナス分割の場合、支払回数を設定します。
// 			$execInput->setPayTimes( $_POST['PayTimes'] );
// 		}
		//HTTP_ACCEPT,HTTP_USER_AGENTは、3Dセキュアサービスをご利用の場合のみ必要な項目です。
		//Entryで3D利用フラグをオンに設定した場合のみ、設定してください。	
		//設定する場合、カード所有者のブラウザから送信されたリクエストヘッダの値を、無加工で
		//設定してください。
		if (FALSE !== $arg3DSecured && isset($_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['HTTP_ACCEPT'])){
			$execInput->setHttpUserAgent($_SERVER['HTTP_USER_AGENT']);
			$execInput->setHttpAccept($_SERVER['HTTP_ACCEPT']);
		}
		if (0 === strpos($argToken, 'cus_')){
			logging('exists card payment', 'gpayclient');
			// 登録カードでの決済
			$execInput->setSiteId($siteID);
			$execInput->setSitePass($sitePass);
			$execInput->setMemberId($argToken);
			$execInput->setCardSeq('0');
			$cardToken = $argToken;
		}
		elseif (0 === strpos($argToken, 'cdn_')){
			logging('new card payment', 'gpayclient');
			$tokenTmp = explode(':', $argToken);
			if (4 > count($tokenTmp)){
				// バリデートエラー扱い
				return FALSE;
			}
			// カード情報入力での決済
			$execInput->setCardNo($tokenTmp[1]);
			$execInput->setExpire($tokenTmp[2]);
			$execInput->setSecurityCode($tokenTmp[3]);
			// カードトークンを生成
			$cardToken = 'cus_'.substr(sha256(str_pad(sha256($argUserID), 11, 0, STR_PAD_LEFT).''.Utilities::date('YmdHis', NULL, NULL, 'GMT').rand(0,99)), 2, 56);
		}
		else {
			logging('new card onetimetoken payment', 'gpayclient');
			// カードトークンとして扱う
			$cardToken = $argToken;
			// ワンタイムトークンでの決済
			$execInput->setToken($argToken);
			// カードトークンを生成
			$cardToken = 'cus_'.substr(sha256(str_pad(sha256($argUserID), 11, 0, STR_PAD_LEFT).''.Utilities::date('YmdHis', NULL, NULL, 'GMT').rand(0,99)), 2, 56);
		}
		logging($argAccessKey, 'gpayclient');
		logging($accessSecret, 'gpayclient');
		logging($siteID, 'gpayclient');
		logging($sitePass, 'gpayclient');
		logging($cardToken, 'gpayclient');

		// オーダーIDを生成してセット
		$orderID = 'o'.substr(sha1($cardToken), 0, 11).'-'.Utilities::date('YmdHis', NULL, NULL, 'GMT');
		if (function_exists('getLocalEnabled') && 1 === (int)getLocalEnabled()){
			// ローカル環境の場合は強制OKにしてしまう
			$function = create_lambdafunction($argCallbackFunction, '$results');
			return $function(array('status' => TRUE, 'userID' => $argUserID, 'amount' => $argAmount, 'orderID' => $orderID, 'accessID' => substr(sha256($orderID.time()), 0, 32), 'accessPass' => substr(sha256($orderID.time()), 0, 32), 'cardToken' => $cardToken, 'options' => $argOptions));
		}

		$entryInput->setOrderId($orderID);
		$execInput->setOrderId($orderID);

		//取引登録＋決済実行の入力パラメータクラスをインスタンス化します
		$input = new EntryExecTranInput();
		$input->setEntryTranInput( $entryInput );
		$input->setExecTranInput( $execInput );

		//API通信クラスをインスタンス化します
		$exe = new EntryExecTran();

		//パラメータオブジェクトを引数に、実行メソッドを呼びます。
		//正常に終了した場合、結果オブジェクトが返るはずです。
		$output = $exe->exec( $input );
	
		//実行後、その結果を確認します。
		if( $exe->isExceptionOccured() ){
			//取引の処理そのものがうまくいかない（通信エラー等）場合、例外が発生します。
			logging('is?', 'gpayclient');
			throw $exe->exception;
		}
		//出力パラメータにエラーコードが含まれていないか、チェックしています。
		if( $output->isErrorOccurred() ){
			require_once('common/ErrorMessageHandler.php');
			$errorHandle = new ErrorHandler();
			$errorCode = NULL;
			$errorMsg = NULL;
			$errorList = $output->getEntryErrList();
			if (0 < count($errorList)){
				foreach( $errorList as  $errorInfo ){
					$errorCode = $errorInfo->getErrCode();
					$errorMsg = $errorHandle->getMessage( $errorInfo->getErrInfo() );
					break;
				}
			}
			if (NULL === $errorCode){
				$errorList = $output->getExecErrList();
				if (0 < count($errorList)){
					foreach( $errorList as  $errorInfo ){
						$errorCode = $errorInfo->getErrCode();
						$errorMsg = $errorHandle->getMessage( $errorInfo->getErrInfo() );
						break;
					}
				}
			}
			if (NULL === $errorCode){
				$errorCode = '999';
				$errorMsg = '致命的なエラー';
			}
			if (FALSE !== strpos($errorMsg, 'エラーコード表')){
					$errorMsg = '致命的なエラー';
			}
			logging('is??', 'gpayclient');
			throw new Exception($errorCode.':'.$errorMsg, (int)$errorCode);
		}
		// 今回の決済用のIDとPASSを取っておく
		$accessID = $output->getAccessID();
		$accessPass = $output->getAccessPass();
		// XXX 3Dセキュアは一旦封印
// 			else if( $output->isTdSecure() ){//決済実行の場合、3Dセキュアフラグをチェックします。
				
// 				//3Dセキュアフラグがオンである場合、リダイレクトページを表示する必要があります。
// 				//サンプルでは、モジュールタイプに標準添付されるリダイレクトユーティリティを利用しています。
				
// 				//リダイレクト用パラメータをインスタンス化して、パラメータを設定します
// 				require_once( 'com/gmo_pg/client/input/AcsParam.php');
// 				require_once( 'com/gmo_pg/client/common/RedirectUtil.php');
// 				$redirectInput = new AcsParam();
// 				$redirectInput->setAcsUrl( $output->getAcsUrl() );
// 				$redirectInput->setMd( $output->getAccessId() );
// 				$redirectInput->setPaReq( $output->getPaReq() );
// 				$redirectInput->setTermUrl( PGCARD_SAMPLE_URL . '/SecureTran.php');
				
// 				//リダイレクトページ表示クラスをインスタンス化して実行します。
// 				$redirectShow = new RedirectUtil();
// 				print ($redirectShow->createRedirectPage( PGCARD_SECURE_RIDIRECT_HTML , $redirectInput ) );
// 				exit();
				
// 			}

		if (0 !== strpos($argToken, 'cus_')){
			// ワンタイムトークンでの決済だったので、会員登録を実行
			require_once( 'com/gmo_pg/client/input/SaveMemberInput.php');
			require_once( 'com/gmo_pg/client/tran/SaveMember.php');
			//入力パラメータクラスをインスタンス化します
			$saveInput = new SaveMemberInput();
			//このサンプルでは、サイトID・パスワードはコンフィグファイルで
			//定数defineしています。
			$saveInput->setSiteId($siteID);
			$saveInput->setSitePass($sitePass);
			//会員IDは必須です
			$saveInput->setMemberId($cardToken);
			//API通信クラスをインスタンス化します
			$exe = new SaveMember();
			//パラメータオブジェクトを引数に、実行メソッドを呼びます。
			//正常に終了した場合、結果オブジェクトが返るはずです。
			$output = $exe->exec($saveInput);
			if( $exe->isExceptionOccured() ){
				//取引の処理そのものがうまくいかない（通信エラー等）場合、例外が発生します。
				throw $exception;
			}
			if( $output->isErrorOccurred() ){
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
				// 売上てしまっているのでthrowする前にキャンセルを行う!
				self::cancelPurchase($argAccessKey, $argAccessSecret, $accessID, $accessPass);
				throw new Exception ($errorCode.':'.$errorMsg, (int)$errorCode);
			}
			// 次にカード登録を実行
			require_once( 'com/gmo_pg/client/input/TradedCardInput.php');
			require_once( 'com/gmo_pg/client/tran/TradedCard.php');
			$tradedInput = new TradedCardInput();
			$tradedInput->setShopId($argAccessKey);
			$tradedInput->setShopPass($accessSecret);
			$tradedInput->setSiteId($siteID);
			$tradedInput->setSitePass($sitePass);
			$tradedInput->setMemberId($cardToken);
			$tradedInput->setOrderId($orderID);
			//API通信クラスをインスタンス化します
			$exe = new TradedCard();
			//パラメータオブジェクトを引数に、実行メソッドを呼びます。
			//正常に終了した場合、結果オブジェクトが返るはずです。
			$output = $exe->exec( $tradedInput );
			if( $exe->isExceptionOccured() ){
				// 売上てしまっているのでthrowする前にキャンセルを行う!
				self::cancelPurchase($argAccessKey, $argAccessSecret, $accessID, $accessPass);
				// 取引の処理そのものがうまくいかない（通信エラー等）場合、例外が発生します。
				throw $exception;
			}
			//出力パラメータにエラーコードが含まれていないか、チェックしています。
			if( $output->isErrorOccurred() ){
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
				// 売上てしまっているのでthrowする前にキャンセルを行う!
				self::cancelPurchase($argAccessKey, $argAccessSecret, $accessID, $accessPass);
				throw new Exception ($errorCode.':'.$errorMsg, (int)$errorCode);
			}
		}
		// カードトークンを返す
		$function = create_lambdafunction($argCallbackFunction, '$results');
		return $function(array('status' => TRUE, 'userID' => $argUserID, 'amount' => $argAmount, 'orderID' => $orderID, 'accessID' => $accessID, 'accessPass' => $accessPass, 'cardToken' => $cardToken, 'options' => $argOptions));
	}

	/**
	 * 決済取消
	 */
	public static function cancelPurchase($argAccessKey, $argAccessSecret, $argAccessID, $argAccessPass, $argRETURN=TRUE){
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
		require_once( 'com/gmo_pg/client/input/AlterTranMagstripeInput.php');
		require_once( 'com/gmo_pg/client/tran/AlterTranMagstripe.php');
		//入力パラメータクラスをインスタンス化します
		$input = new AlterTranMagstripeInput();
		$input->setShopId($argAccessKey);
		$input->setShopPass($accessSecret);
		$input->setAccessId($argAccessID);
		$input->setAccessPass($argAccessPass);
		if (TRUE === $argRETURN){
			// 返品
			$input->setJobCd('RETURN');
		}
		else {
			// 取り消し
			$input->setJobCd('VOID');
		}
		//API通信クラスをインスタンス化します
		$exe = new AlterTranMagstripe();
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