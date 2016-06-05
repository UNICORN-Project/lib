<?php

/**
 * GMO Payment Gatewayを利用して決済を行う
 * 
 * @author saimushi
 * @see <a href="https://www.gmo-pg.com">GMO Payment Gateway</a>
 */
class GenericGMOPaymentGatewayAgent extends GenericPaymentBase implements GenericPaymentIO
{
	/**
	 * カード決済(無実し)
	 */
	public static $servicePrefix = '';

	/**
	 * @var number 最低課金額 1円以下の決済は出来ない
	 */
	private static $_minimumChargeAmount = 1;

	/**
	 * @var number 最高課金額 1000万以上の決済は出来ない
	 */
	private static $_maximumChargeAmount = 10000000;

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
		$EntryTranInput = 'EntryTran'.static::$servicePrefix.'Input';
		$ExecTranInput = 'ExecTran'.static::$servicePrefix.'Input';
		$EntryExecTranInput = 'EntryExecTran'.static::$servicePrefix.'Input';
		$EntryExecTran = 'EntryExecTran'.static::$servicePrefix.'';
		require_once( 'com/gmo_pg/client/input/'.$EntryTranInput.'.php');
		require_once( 'com/gmo_pg/client/input/'.$ExecTranInput.'.php');
		require_once( 'com/gmo_pg/client/input/'.$EntryExecTranInput.'.php');
		require_once( 'com/gmo_pg/client/tran/'.$EntryExecTran.'.php');
		//入力パラメータクラスをインスタンス化します
		//取引登録時に必要なパラメータ
		$entryInput = new $EntryTranInput();
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
		$execInput = new $ExecTranInput();

		// カード決済
		if ('' === static::$servicePrefix){
			//HTTP_ACCEPT,HTTP_USER_AGENTは、3Dセキュアサービスをご利用の場合のみ必要な項目です。
			//Entryで3D利用フラグをオンに設定した場合のみ、設定してください。	
			//設定する場合、カード所有者のブラウザから送信されたリクエストヘッダの値を、無加工で
			//設定してください。
			if (FALSE !== $arg3DSecured && isset($_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['HTTP_ACCEPT'])){
				$execInput->setHttpUserAgent($_SERVER['HTTP_USER_AGENT']);
				$execInput->setHttpAccept($_SERVER['HTTP_ACCEPT']);
			}
			// 支払方法に応じて、支払回数のセット要否が異なります。
			// XXX 一旦一括固定
			$execInput->setMethod(1);
// 			$method = $_POST['PayMethod'];
// 			$execInput->setMethod( $method );
// 			if( $method == '2' || $method == '4'){//支払方法が、分割またはボーナス分割の場合、支払回数を設定します。
// 				$execInput->setPayTimes( $_POST['PayTimes'] );
// 			}
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
			// オーダーIDを生成してセット
			$orderID = 'o'.substr(sha1($cardToken), 0, 11).'-'.Utilities::date('YmdHis', NULL, NULL, 'GMT');
		}
		else {
			$protocol = 'https://';
			if (true === (empty($_SERVER['HTTPS']))){
				$protocol = 'http://';
			}
			// 本当のコールバック定義(BitCashの仕様に寄せている)
			// fixPurchase → GMO → 外部決済 → GMO → NotifyURL → CallbackURL
			// の順にコールされる
			$returnURL = $argReturnURL;
			$cancelURL = $argCancelURL;
			if (NULL === $returnURL){
				$returnURL = $protocol.$_SERVER['HTTP_HOST'] . str_replace('//', '/', dirname($_SERVER['REQUEST_URI']).'/gmo'.strtolower(static::$servicePrefix).'callback-success.php');
			}
			else if (FALSE === strpos($returnURL, '//'.$_SERVER['HTTP_HOST'])){
				$returnURL = $protocol.$_SERVER['HTTP_HOST'] . str_replace('//', '/', dirname($_SERVER['REQUEST_URI']).'/'.$returnURL);
			}
			if (NULL === $cancelURL){
				$cancelURL = $protocol.$_SERVER['HTTP_HOST'] . str_replace('//', '/', dirname($_SERVER['REQUEST_URI']).'/gmo'.strtolower(static::$servicePrefix).'callback-cancel.php');
			}
			else if (FALSE === strpos($cancelURL, '//'.$_SERVER['HTTP_HOST'])){
				$cancelURL = $protocol.$_SERVER['HTTP_HOST'] . str_replace('//', '/', dirname($_SERVER['REQUEST_URI']).'/'.$cancelURL);
			}
			$useCode = FALSE;
			$uploadDir = NULL;
			if (NULL === $argNotifyURL){
				$useCode = TRUE;
				if (class_exists('WebStorage') && function_exists('getAutoGeneratedPath') && TRUE === (NULL !== getConfig('S3BUCKET') || NULL !== getConfig('FILE_UPLOAD_DIR'))){
					$uploadDir = getConfig('S3BUCKET');
					if (NULL === $uploadDir){
						$uploadDir = getConfig('FILE_UPLOAD_DIR');
					}
					if (0 === strpos($uploadDir, '/')){
						// 絶対パスはバケット指定の場合に使えないので却下
						$uploadDir = NULL;
					}
				}
			}
			$useBoot = FALSE;
			if (NULL !== $uploadDir){
				$useBoot = TRUE;
				$dir = 'gmo'.strtolower(static::$servicePrefix).'callbacks/';
				$notifyURL = $protocol.$_SERVER['HTTP_HOST'] . str_replace('//', '/', dirname($_SERVER['REQUEST_URI']).'/gmo'.strtolower(static::$servicePrefix).'callback.php?token='.sha256($argUserID));
				$fwCorePath = getFrameworkCoreFilePath(TRUE);
				$projectName = '';
				if (defined('PROJECT_NAME') && 0 < strlen(PROJECT_NAME)){
					$projectName = PROJECT_NAME;
				}
				// S3からファイルを取得してコールバックを実行するブートコードを自動生成する
				$bootCodePath = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['REQUEST_URI']).'/gmo'.strtolower(static::$servicePrefix).'callback.php');
				@file_put_contents($bootCodePath, self::_getCallbackBootCode($projectName, $fwCorePath, $dir));
				@chmod($bootCodePath, 0777);
			}
			else if (NULL === $argNotifyURL){
				// XXX 分散環境での動作は、自身で設計構築が必要です。
				$dir = 'gmo'.strtolower(static::$servicePrefix).'callbacks/'.Utilities::date('Y', NULL, NULL, 'GMT').'/'.Utilities::date('m', NULL, NULL, 'GMT').'/'.Utilities::date('d', NULL, NULL, 'GMT').'/';
				$notifyURL = $protocol.$_SERVER['HTTP_HOST'] . str_replace('//', '/', dirname($_SERVER['REQUEST_URI']).$dir.'/'.sha256($argUserID).'.php');
			}
			else {
				$notifyURL = $argNotifyURL;
			}
			$execInput->setShopId($argAccessKey);
			$execInput->setShopPass($accessSecret);
			$cardToken = 'cus_'.substr(sha256(str_pad(sha256($argUserID), 11, 0, STR_PAD_LEFT).''.Utilities::date('YmdHis', NULL, NULL, 'GMT').rand(0,99)), 2, 56);
			// LINE Pay
			if ('linepay' === strtolower(static::$servicePrefix)){
				// オーダーIDを生成してセット
				$orderID = 'olp'.substr(sha1($cardToken), 0, 9).'-'.Utilities::date('YmdHis', NULL, NULL, 'GMT');
				$execInput->setRetURL($notifyURL);
				$execInput->setErrorRcvURL($notifyURL);
				$displayName = 'Order: '.$orderID;
				if (function_exists('getProjectDisplayName')){
					$_displayName = getProjectDisplayName();
					if (NULL !== $_displayName){
						$displayName = $_displayName.PHP_EOL.$displayName;
					}
				}
				$execInput->setProductName(mb_convert_encoding( $displayName , 'SJIS' ));
				if (NULL !== $argDescription && 0 < strlen($argDescription) && FALSE !== strpos($argDescription, '//')){
					$execInput->setProductImageUrl($argDescription);
				}
			}
			// ソフトバンクまとめて決済
			else if ('sb' === strtolower(static::$servicePrefix)){
				// オーダーIDを生成してセット
				$orderID = 'osb'.substr(sha1($cardToken), 0, 9).'-'.Utilities::date('YmdHis', NULL, NULL, 'GMT');
				// SB決済は一旦
				$execInput->setRetURL($notifyURL);
			}
		}
		logging(static::$servicePrefix, 'gpayclient');
		logging($argAccessKey, 'gpayclient');
		logging($accessSecret, 'gpayclient');
		logging($siteID, 'gpayclient');
		logging($sitePass, 'gpayclient');
		logging($cardToken, 'gpayclient');

		if (function_exists('getLocalEnabled') && 1 === (int)getLocalEnabled()){
			// ローカル環境の場合は強制OKにしてしまう
			$function = create_lambdafunction($argCallbackFunction, '$results');
			return $function(array('status' => TRUE, 'userID' => $argUserID, 'amount' => $argAmount, 'orderID' => $orderID, 'accessID' => substr(sha256($orderID.time()), 0, 32), 'accessPass' => substr(sha256($orderID.time()), 0, 32), 'cardToken' => $cardToken, 'options' => $argOptions));
		}

		$entryInput->setOrderId($orderID);
		$execInput->setOrderId($orderID);

		//取引登録＋決済実行の入力パラメータクラスをインスタンス化します
		$input = new $EntryExecTranInput();
		$entryMethod = 'setEntryTran'.static::$servicePrefix.'Input';
		$execMethod = 'setExecTran'.static::$servicePrefix.'Input';
		$input->$entryMethod( $entryInput );
		$input->$execMethod( $execInput );

		//API通信クラスをインスタンス化します
		$exe = new $EntryExecTran();

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

		// 今回の決済用のIDを取っておく
		$accessID = $output->getAccessID();
		$accessPass = $output->getAccessPass();

		// 外部決済系(LINE SBまとめて)は、リダイレクトURLを持ってリダイレクトする
		if ('' !== static::$servicePrefix){
			$accessToken = $output->getToken();
			if (TRUE === $useCode) {
				// 通知URLを自動生成する
				// callbackFunctionをファイルに書き出す準備
				$results = array();
				$results['userID'] = $argUserID;
				$results['orderID'] = $orderID;
				$results['amount'] = $argAmount;
				$results['options'] = $argOptions;
				$results['accessID'] = $accessID;
				$results['accessPass'] = $accessPass;
				$callClassName = get_called_class();
				$sbCancelBlock = '';
				if ('sb' === strtolower(static::$servicePrefix)){
					// キャリア決済系のキャンセル処理はオーダーIDと金額指定が必須
					$sbCancelBlock = '	'.$callClassName.'::$cancelAmount = '.$argAmount.';'.PHP_EOL;
					$sbCancelBlock .= '	'.$callClassName.'::$orderID = \''.$orderID.'\';'.PHP_EOL;
				}
				$serializeResults = serialize($results);
				$callbackCode = <<<__CALLBACK__
<?php

\$results = unserialize('$serializeResults');

// 成功処理
function __gmoCallback(\$results){
$argCallbackFunction
}

// キャンル処理
function __gmoCancel(\$results){
$sbCancelBlock
	// キャンセル処理
	try
	{
		$callClassName::cancelPurchase('$argAccessKey', '$argAccessSecret', \$results['accessID'], \$results['accessPass']);
	}
	catch (Exception \$Exception){
		// XXX ロギング
	}
	// キャンセルコールバック画面へ遷移
	header('Location: $cancelURL');
	exit;
}

// validate
if (!isset(\$_POST['ShopID'])){
	__gmoCancel(\$results);
}
if (!isset(\$_POST['OrderID'])){
	__gmoCancel(\$results);
}
// 決済結果状態の判定
if (isset(\$_POST['Status']) && TRUE === ('PAYFAIL' == \$_POST['Status'] || 'PAYCANCEL' == \$_POST['Status'])){
	// エラー決済 キャンセル処理
	__gmoCancel(\$results);
}

if (isset(\$_POST['SbTrackingId'])){
	// XXX SbTrackingIdをロギング
}
if (isset(\$_POST['CheckString'])){
	// XXX LinePayのハッシュ値をロギング
}

// 決済エラー判定
if (isset(\$_POST['ErrCode']) && 0 < strlen(\$_POST['ErrCode'])){
	// ロギング
	//if (isset(\$_POST['ErrInfo'])){}
	// キャンセル処理
	__gmoCancel(\$results);
}

if( \$results['orderID'] !== \$_POST['OrderID'] ) {
	// オーダーIDが生成時と異なる不正なアクセス！
	// XXX ロギング
	__gmoCancel(\$results);
}

// ココに到達してる時点で決済APIは成功している
\$results['status'] = TRUE;

\$_results = __gmoCallback(\$results);

if (true !== (is_array(\$_results) && isset(\$_results['accessPass']) && 0 < strlen(\$_results['accessPass']))){
	__gmoCancel();
}

// 正常終了
// callbackへリダイレクト
header('Location: $returnURL');

?>
__CALLBACK__;
			}
			if (TRUE === $useBoot){
				// フレームワークのWebStorageが利用出来る場合は、WebStorageに生成スクリプトを置く(分散環境でも動く)
				$path = getAutoGeneratedPath();
				if (!is_dir($path.$dir)){
					@mkdir($path.$dir, 0777, true);
					@exec('chmod -R 0777 ' .$path.$dir);
				}
				if (!is_dir($path.$dir)){
					// ディレクトリ生成エラー
					throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
				}
				$genFilePath = $path.$dir.sha256($argUserID).'.php';
				@file_put_contents($genFilePath, str_replace(PHP_EOL."\t\t\t\t", PHP_EOL."\t", $callbackCode));
				@chmod($genFilePath, 0777);
				// WebStorageにソースをアップする
				$StorageEngine = new WebStorage();
				$fileName = $uploadDir.$dir.sha256($argUserID).'.php';
				$res = $StorageEngine->save($fileName, $genFilePath);
			}
			else if (TRUE === $useCode){
				// XXX 分散環境での動作は、自身で設計構築が必要です。
				$path = $_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['REQUEST_URI']);
				if (!is_dir($path.$dir)){
					@mkdir($path.$dir, 0777, true);
					@exec('chmod -R 0777 ' .$path);
				}
				if (!is_dir($path.$dir)){
					// ディレクトリ生成エラー
					throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
				}
				$genFilePath = $path.$dir.sha256($argUserID).'.php';
				@file_put_contents($genFilePath, str_replace(PHP_EOL."\t\t\t\t", PHP_EOL."\t", $callbackCode));
				@chmod($genFilePath, 0777);
			}
			$startURL = $output->getStartURL();
			// リダイレクトUTILを使ってリダイレクトする
			$redirectHtml = '';
			if (0 === strpos($argToken, '<') && FALSE !== strpos($argToken, 'html') && FALSE !== strpos($argToken, 'head') && FALSE !== strpos($argToken, 'body')){
				// トークンをリダイレクト用HTMLとして指定していると想定
				$redirectHtml = $argToken;
			}
			else {
				$redirectHtml .= '<html><head><title>Redirect Payment Service</title></head>';
				$redirectHtml .= '<body onload="document.forms[0].submit();"><form method="POST" action="'.$startURL.'">';
				$redirectHtml .= '<input type="hidden" name="AccessID" value="'.$accessID.'"/>';
				$redirectHtml .= '<input type="hidden" name="Token" value="'.$accessToken.'"/>';
				$redirectHtml .= '<input type="submit" name="go" value="Go Payment Service."/>';
				$redirectHtml .= '</form></body></html>';
			}
			echo $redirectHtml;
			exit;
		}

		// ココ以降ははカード決済用なので、今回の決済用のPASSを取っておく
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

		if (NULL !== $argToken && 0 !== strpos($argToken, 'cus_')){
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
		// GMOライブラリの読み込み
		if (function_exists('getConfig') && 0 < strlen(getConfig('VENDOR_PATH'))){
			set_include_path(get_include_path().PATH_SEPARATOR.getConfig('VENDOR_PATH').'GMOPay/');
		}
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