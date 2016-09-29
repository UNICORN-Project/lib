<?php

require Configure::LIB_PATH.'VendorPackage/WebPay/vendor/autoload.php';
use WebPay\WebPay;

/**
 * WebPay API を利用してクレジットカードの取引を行います。
 * 
 * @author atarun
 * @see <a href="https://webpay.jp/docs/api/php">WebPay API Docs</a>
 */
class GenericWebPayAgent
{
	/**
	 * @var WebPay インスタンス
	 */
	protected static $_WebPay = NULL;
	
	/**
	 * @var boolean 初期化フラグ
	 */
	protected static $_initialized = FALSE;
	
	/**
	 * @var string 通貨
	 * 2015/11/4時点で対応している通貨は"jpy"のみ
	 */
	public static $currency = 'jpy';
	
	public static $tokenKeyFields = array('card', 'customer');
	
	/**
	 * @var number WebPay最低課金額
	 */
	public static $minimumChargeAmount = 50;
	
	/**
	 * @var number WebPay最高課金額
	 */
	public static $maximumChargeAmount = 9999999;
	
	protected static function _init(){
		if(FALSE === self::$_initialized){
			$secretKey = NULL;
			
			if(class_exists('Configure') && NULL !== Configure::constant('WEB_PEY_SECRET_KEY')){
				$secretKey = Configure::WEB_PAY_SECRET_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('WEB_PEY_CURRENCY')){
				self::$currency = Configure::WEB_PAY_CURRENCY;
			}
			
			if(defined('PROJECT_NAME') && 0 < strlen(PROJECT_NAME) && class_exists(PROJECT_NAME.'Configure')){
				$ProjectConfigure = PROJECT_NAME . 'Configure';
				if(NULL !== $ProjectConfigure::constant('WEB_PAY_SECRET_KEY')){
					$secretKey = $ProjectConfigure::WEB_PAY_SECRET_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('WEB_PAY_CURRENCY')){
					self::$currency = $ProjectConfigure::WEB_PAY_CURRENCY;
				}
			}
			if(NULL === self::$_WebPay){
				self::$_WebPay = new WebPay($secretKey);
			}
			self::$_initialized = TRUE;
		}
	}
	
	/**
	 * 課金(Charge)の作成, $argCaptureがFALSEの場合は仮売上
	 * @param string $argTokenKeyField
	 * @param string $argChargeToken
	 * @param number $argChargeAmount
	 * @param string $argCapture
	 * @param number $argExpireDays
	 * @param string $argDescription
	 * @param string $argUuid
	 * @param string $argShop
	 */
	public static function chargeCreate($argTokenKeyField, $argChargeToken, $argChargeAmount, $argCapture=TRUE, $argExpireDays=NULL, $argDescription=NULL, $argUuid=NULL, $argShop=NULL){
		debug(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		if(FALSE === self::$_initialized){
			self::_init();
		}
		try {
			if (FALSE === in_array($argTokenKeyField, self::$tokenKeyFields)) {
				return FALSE;
			}
			if (NULL === $argChargeToken) {
				return FALSE;
			}
			if($argTokenKeyField === self::$tokenKeyFields[1]){
				if (1 !== preg_match('/^cus_[0-9a-zA-Z]+/', $argChargeToken)){
					return FALSE;
				}
			}
			else{
				if (1 !== preg_match('/^tok_[0-9a-zA-Z]+/', $argChargeToken)){
					return FALSE;
				}
			}
			if (NULL === $argChargeAmount || (int)$argChargeAmount < self::$minimumChargeAmount || self::$maximumChargeAmount < (int)$argChargeAmount || (int)$argChargeAmount < 0) {
				return FALSE;
			}
			
			$data = array(
				$argTokenKeyField => $argChargeToken,
				"amount"          => (int)$argChargeAmount,
				"currency"        => self::$currency,
				'capture'         => $argCapture,
				'expire_days'     => $argExpireDays,
				'uuid'            => $argUuid,
				'description'     => $argDescription,
				'shop'            => $argShop,
			);
			// token
			$result = self::$_WebPay->charge->create($data);
			return $result;
		} catch (\WebPay\ErrorResponse\ErrorResponseException $ErrorResponseException) {
			$error = $ErrorResponseException->data->error;
			debug($error);
			switch ($error->causedBy) {
				case 'buyer':
					// カードエラーなど、購入者に原因がある
					// エラーメッセージをそのまま表示するのがわかりやすい
					break;
				case 'insufficient':
					// 実装ミスに起因する
					break;
				case 'missing':
					// リクエスト対象のオブジェクトが存在しない
					break;
				case 'service':
					// WebPayに起因するエラー
					break;
				default:
					// 未知のエラー
					break;
			}
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		} catch (\WebPay\ApiException $ApiException) {
			$error = $ApiException->data->error;
			debug($error);
			// APIからのレスポンスが受け取れない場合。接続エラーなど
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		} catch (\Exception $Exception) {
			// WebPayとは関係ない例外の場合
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		}
	}
	
	/**
	 * 課金(Charge)の仮売上を実売上化
	 * @param string $argCustomerID
	 * @param number $argChargeAmount
	 * @throws Exception
	 * @return boolean|\WebPay\Data\ChargeRequestWithAmount
	 */
	public static function chargeCapture($argChargeID, $argChargeAmount=NULL){
		debug(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		if(FALSE === self::$_initialized){
			self::_init();
		}
		try {
			if(NULL === $argChargeID || 1 !== preg_match('/^ch_[0-9a-zA-Z]+/', $argChargeID)){
				return FALSE;
			}
			if(NULL !== $argChargeAmount && (int)$argChargeAmount < 0){
				return FALSE;
			}
			$data = array(
				'id'     => $argChargeID,
				'amount' => (int)$argChargeAmount,
			);
			$result = self::$_WebPay->charge->capture($data);
			return $result;
		} catch (\WebPay\ErrorResponse\InvalidRequestException $InvalidRequestException) {
			$error = $InvalidRequestException->data->error;
			debug($error->message, $error->type);
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		} catch (\Exception $Exception) {
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		}
	}
	
	/**
	 * 課金(Charge)の払戻, 仮売上の失効
	 * @param string $argCustomerID
	 * @param number $argChargeAmount
	 * @param string $argUuid
	 * @throws Exception
	 */
	public static function chargeRefund($argChargeID, $argChargeAmount=NULL, $argUuid=NULL){
		debug(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		if(FALSE === self::$_initialized){
			self::_init();
		}
		try {
			if(NULL === $argChargeID || 1 !== preg_match('/^ch_[0-9a-zA-Z]+/', $argChargeID)){
				return FALSE;
			}
			if(NULL !== $argChargeAmount && (int)$argChargeAmount < 0){
				return FALSE;
			}
			$data = array(
				'id'     => $argChargeID,
				'amount' => (int)$argChargeAmount,
				'uuid'   => $argUuid,
			);
			$result = self::$_WebPay->charge->refund($data);
			return $result;
		} catch (\WebPay\ErrorResponse\InvalidRequestException $InvalidRequestException) {
			$error = $InvalidRequestException->data->error;
			debug($error->message, $error->type);
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		} catch (\Exception $Exception) {
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		}
	}
	
	/**
	 * 課金(Charge)の取得
	 * @param string $argCustomerID
	 * @throws Exception
	 */
	public static function chargeRetrieve($argChargeID){
		debug(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		if(FALSE === self::$_initialized){
			self::_init();
		}
		try {
			if (NULL === $argChargeID || 1 !== preg_match('/^ch_[0-9a-zA-Z]+/', $argChargeID)){
				return FALSE;
			}
			$result = self::$_WebPay->charge->retrieve($argChargeID);
			return $result;
		} catch (\Exception $Exception) {
			// エラーが発生する場合はWebPay側に課金情報が存在しないと判断
			return FALSE;
// 			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		}
	}
	
	/**
	 * 顧客(Customer)の作成
	 * @param string $argCardToken
	 * @param string $argEmail
	 * @param string $argDescription
	 * @param string $argUuid
	 * @throws Exception
	 * @return boolean|\WebPay\Data\CustomerRequestCreate
	 */
	public static function customerCreate($argCardToken, $argEmail=NULL, $argDescription=NULL, $argUuid=NULL){
		debug(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		if(FALSE === self::$_initialized){
			self::_init();
		}
		try {
			if(NULL === $argCardToken){
				return FALSE;
			}
			$data = array(
				'card'        => $argCardToken,
				'email'       => $argEmail,
				'description' => $argDescription,
				'uuid'        => $argUuid,
			);
			
			$result = self::$_WebPay->customer->create($data);
			return $result;
		} catch (\WebPay\ErrorResponse\InvalidRequestException $InvalidRequestException) {
			$error = $InvalidRequestException->data->error;
			debug($error);
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		} catch (\Exception $Exception) {
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		}
	}
	
	/**
	 * 顧客(Customer)の取得
	 * @param string $argCustomerID
	 * @throws Exception
	 */
	public static function customerRetrieve($argCustomerID){
		debug(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		if(FALSE === self::$_initialized){
			self::_init();
		}
		try {
			if (NULL === $argCustomerID || 1 !== preg_match('/^cus_[0-9a-zA-Z]+/', $argCustomerID)){
				return FALSE;
			}
			$result = self::$_WebPay->customer->retrieve($argCustomerID);
			// 顧客情報が存在
			if(FALSE === $result->deleted){
				return $result;
			}else{
				return FALSE;
			}
		} catch (\Exception $Exception) {
			// エラーが発生する場合はWebPay側に顧客情報が存在しないと判断
			return FALSE;
// 			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		}
	}
	
	/**
	 * 顧客(Customer)の更新
	 * @param string $argCustomerID
	 * @param string $argCardToken
	 * @param string $argEmail
	 * @param string $argDescription
	 * @throws Exception
	 */
	public static function customerUpdate($argCustomerID, $argCardToken, $argEmail=NULL, $argDescription=NULL){
		debug(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		if(FALSE === self::$_initialized){
			self::_init();
		}
		try { 
			if (NULL === $argCustomerID || 1 !== preg_match('/^cus_[0-9a-zA-Z]+/', $argCustomerID)){
				return FALSE;
			}
			if(NULL === $argCardToken){
				return FALSE;
			}
			$data = array(
				'id'          => $argCustomerID,
				'card'        => $argCardToken,
				'email'       => $argEmail,
				'description' => $argDescription,
			);
			$result = self::$_WebPay->customer->update($data);
			return $result;
		} catch (\WebPay\ErrorResponse\InvalidRequestException $InvalidRequestException) {
			$error = $InvalidRequestException->data->error;
			debug($error->message, $error->type);
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		} catch (\Exception $Exception) {
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		}
	}
	
	/**
	 * 顧客(Customer)の永久削除
	 * @param string $argCustomerID
	 * @throws Exception
	 */
	public static function customerTerminate($argCustomerID){
		debug(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		if(FALSE === self::$_initialized){
			self::_init();
		}
		if(NULL === $argCustomerID){
			return FALSE;
		}
		try {
			$data = array(
				'id' => $argCustomerID,
			);
			$result = self::$_WebPay->customer->delete($data);
			return $result;
		} catch (\WebPay\ErrorResponse\InvalidRequestException $InvalidRequestException) {
			$error = $InvalidRequestException->data->error;
			debug($error);
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		} catch (\Exception $Exception) {
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		}
	}
	
	/**
	 * 顧客(Customer)の一覧取得
	 * @param number $argLimit
	 * @param number $argOffset
	 * @param datetime $argCreated
	 * @throws Exception
	 */
	public static function customerAll($argLimit=10, $argOffset=0, $argCreated=NULL){
		debug(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		if(FALSE === self::$_initialized){
			self::_init();
		}
		if(FALSE === is_numeric($argLimit)){
			return FALSE;
		}
		if(FALSE === is_numeric($argOffset)){
			return FALSE;
		}
		// ToDo: $argCreated TimeStamp Check
		try {
			$data = array(
				'count'   => $argLimit,
				'offset'  => $argOffset,
				'created' => $argCreated,
			);
			$result = self::$_WebPay->customer->all($data);
			return $result;
		} catch (\Exception $Exception) {
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		}
	}
	
	/**
	 * 顧客(Customer)のカード情報削除
	 * @param string $argCustomerID
	 * @throws Exception
	 */
	public static function customerDelete($argCustomerID){
		debug(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		if(FALSE === self::$_initialized){
			self::_init();
		}
		if(NULL === $argCustomerID){
			return FALSE;
		}
		try {
			$data = array(
				'id' => $argCustomerID,
			);
			$result = self::$_WebPay->customer->deleteActiveCard($data);
			return $result;
		} catch (\WebPay\ErrorResponse\InvalidRequestException $InvalidRequestException) {
			$error = $InvalidRequestException->data->error;
			debug($error);
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		} catch (\Exception $Exception) {
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		}
	}
}

?>