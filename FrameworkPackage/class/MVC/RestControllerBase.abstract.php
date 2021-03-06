<?php

abstract class RestControllerBase extends APIControllerBase implements RestControllerIO {

	protected $_initialized = FALSE;
	public $requestMethod = NULL;
	protected static $_requestMethod = NULL;
	public $restResource = '';
	public $restResourceModel = '';
	public $restResourceListed = NULL;
	public $restResourceAccessedDateKeyName = '';
	public $restResourceCreateDateKeyName = '';
	public $restResourceModifyDateKeyName = '';
	public $restResourceAvailableKeyName = '';
	public $restResourceUserTableName = NULL;
	public $restResourceRelaySuffix = '';
	public $restResourceRelayPrefix = '';
	public $AuthDevice = NULL;
	public $AuthUser = NULL;
	public $authUserID = NULL;
	public $authUserIDFieldName = NULL;
	public $authUserQuery = NULL;
	public $deepRESTMode = TRUE;
	public $rootREST = TRUE;
	public $virtualREST = FALSE;
	public $responceData = FALSE;
	public static $nowGMT = NULL;
	
	public function __construct(){
		if(NULL === self::$nowGMT){
			self::$nowGMT = Utilities::date('Y-m-d H:i:s', NULL, NULL, 'GMT');
		}
	}

	protected function _init(){
		if(FALSE === $this->_initialized){
			if(class_exists('Configure') && NULL !== Configure::constant('REST_RESOURCE_OWNER_PKEY_NAME')){
				$this->authUserIDFieldName = Configure::REST_RESOURCE_OWNER_PKEY_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('REST_RESOURCE_ACCESSED_DATE_KEY_NAME')){
				$this->restResourceAccessedDateKeyName = Configure::REST_RESOURCE_ACCESSED_DATE_KEY_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('REST_RESOURCE_CREATE_DATE_KEY_NAME')){
				$this->restResourceCreateDateKeyName = Configure::REST_RESOURCE_CREATE_DATE_KEY_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('REST_RESOURCE_MODIFY_DATE_KEY_NAME')){
				$this->restResourceModifyDateKeyName = Configure::REST_RESOURCE_MODIFY_DATE_KEY_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('REST_RESOURCE_AVAILABLE_KEY_NAME')){
				$this->restResourceAvailableKeyName = Configure::REST_RESOURCE_AVAILABLE_KEY_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('REST_UIDAUTH_USER_TBL_NAME')){
				$this->restResourceUserTableName = Configure::REST_UIDAUTH_USER_TBL_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('REST_RESOURCE_RELAY_SUFFIX')){
				$this->restResourceRelaySuffix = Configure::REST_RESOURCE_RELAY_SUFFIX;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('REST_RESOURCE_RELAY_PREFIX')){
				$this->restResourceRelayPrefix = Configure::REST_RESOURCE_RELAY_PREFIX;
			}
			elseif(defined('PROJECT_NAME') && strlen(PROJECT_NAME) > 0 && class_exists(PROJECT_NAME . 'Configure')){
				$ProjectConfigure = PROJECT_NAME . 'Configure';
				if(NULL !== $ProjectConfigure::constant('REST_RESOURCE_OWNER_PKEY_NAME')){
					$this->authUserIDFieldName = $ProjectConfigure::REST_RESOURCE_OWNER_PKEY_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('REST_RESOURCE_ACCESSED_DATE_KEY_NAME')){
					$this->restResourceAccessedDateKeyName = $ProjectConfigure::REST_RESOURCE_ACCESSED_DATE_KEY_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('REST_RESOURCE_CREATE_DATE_KEY_NAME')){
					$this->restResourceCreateDateKeyName = $ProjectConfigure::REST_RESOURCE_CREATE_DATE_KEY_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('REST_RESOURCE_MODIFY_DATE_KEY_NAME')){
					$this->restResourceModifyDateKeyName = $ProjectConfigure::REST_RESOURCE_MODIFY_DATE_KEY_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('REST_RESOURCE_AVAILABLE_KEY_NAME')){
					$this->restResourceAvailableKeyName = $ProjectConfigure::REST_RESOURCE_AVAILABLE_KEY_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('REST_UIDAUTH_USER_TBL_NAME')){
					$this->restResourceUserTableName = $ProjectConfigure::REST_UIDAUTH_USER_TBL_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('REST_RESOURCE_RELAY_SUFFIX')){
					$this->restResourceRelaySuffix = $ProjectConfigure::REST_RESOURCE_RELAY_SUFFIX;
				}
				if(NULL !== $ProjectConfigure::constant('REST_RESOURCE_RELAY_PREFIX')){
					$this->restResourceRelayPrefix = $ProjectConfigure::REST_RESOURCE_RELAY_PREFIX;
				}
			}
			// オートavailableの強制OFF判定
			if(isset($_SERVER['_availalefilter_']) && TRUE == ('0' === $_SERVER['_availalefilter_'] || 'false' === strtolower($_SERVER['_availalefilter_']))){
				$this->restResourceAvailableKeyName = '';
			}
			if(NULL === self::$nowGMT){
				self::$nowGMT = Utilities::date('Y-m-d H:i:s', NULL, NULL, 'GMT');
			}
			$this->_initialized = TRUE;
		}
	}

	public function getDBO($argDSN=NULL){
		return self::_getDBO($argDSN);
	}

	protected static function _getDBO($argDSN=NULL){
		// DBOを初期化
		static $defaultDSN = NULL;
		static $DBO = array();
		// DSNの自動判別
		$DSN = NULL;
		if (NULL !== $defaultDSN && 0 < strlen($defaultDSN) && $defaultDSN != 'default'){
			$DSN = $defaultDSN;
		}
		if(NULL === $argDSN && NULL === $defaultDSN){
			if(class_exists('Configure') && NULL !== Configure::constant('REST_DB_DSN')){
				// 定義からセッションDBの接続情報を特定
				$DSN = Configure::REST_DB_DSN;
			}
			if(defined('PROJECT_NAME') && strlen(PROJECT_NAME) > 0 && class_exists(PROJECT_NAME . 'Configure')){
				$ProjectConfigure = PROJECT_NAME . 'Configure';
				if(NULL !== $ProjectConfigure::constant('REST_DB_DSN')){
					// 定義からセッションDBの接続情報を特定
					$DSN = $ProjectConfigure::REST_DB_DSN;
				}
			}
			if (NULL !== $DSN && 0 < strlen($DSN)){
				$defaultDSN = $DSN;
			}
			else {
				// Default DSNを使う指示を取っておく
				$defaultDSN = 'default';
			}
		}
		// DSN指定があった場合はそれに従う
		elseif(NULL !== $argDSN){
			$DSN = $argDSN;
		}
		if(!isset($DBO[(string)$DSN])){
			// DBマイグレーション
			if(function_exists('getAutoMigrationEnabled') && TRUE === getAutoMigrationEnabled()){
				MigrationManager::dispatchDatabase();
			}
			$DBO[(string)$DSN] = DBO::sharedInstance($DSN);
		}
		return $DBO[(string)$DSN];
	}

	public function getModel($argModel, $argIdentifierORQuery=NULL, $argBinds=NULL, $argDSN=NULL, $argAutoReadable=TRUE){
		return self::_getModel($argModel, $argIdentifierORQuery, $argBinds, $argDSN, $argAutoReadable);
	}

	protected function _getModel($argModel, $argIdentifierORQuery=NULL, $argBinds=NULL, $argDSN=NULL, $argAutoReadable=TRUE){
		if(NULL !== $argIdentifierORQuery){
			return ORMapper::getModel(self::_getDBO($argDSN), $argModel, $argIdentifierORQuery, $argBinds, $argAutoReadable);
		}
		else{
			return ORMapper::getModel(self::_getDBO($argDSN), $argModel, $argIdentifierORQuery, NULL, $argAutoReadable);
		}
	}

	public function convertArrayFromModel($ArgModel, $argFields=NULL){
		return $this->_convertArrayFromModel($ArgModel, $argFields);
	}

	protected function _convertArrayFromModel($ArgModel, $argFields=NULL){
		if(is_object($ArgModel)){
			$arrayModel = array();
			$fields = $ArgModel->getFieldKeys();
			if(NULL !== $argFields){
				for($fieldsIdx=0; $fieldsIdx < count($argFields); $fieldsIdx++){
					if(isset($ArgModel->{$argFields[$fieldsIdx]})){
						$arrayModel[$argFields[$fieldsIdx]] = (string)$ArgModel->{$argFields[$fieldsIdx]};
					}
				}
			}
			else{
				for($fieldsIdx=0; $fieldsIdx < count($fields); $fieldsIdx++){
					$arrayModel[$fields[$fieldsIdx]] = (string)$ArgModel->{$fields[$fieldsIdx]};
				}
			}
			return $arrayModel;
		}
		return FALSE;
	}

	public static function resolveRequestParams($argRequestMethod=NULL){
		$requestParams = array();
		if (NULL === $argRequestMethod){
			$argRequestMethod = $_SERVER['REQUEST_METHOD'];
		}
		if (NULL !== self::$_requestMethod){
			$argRequestMethod = self::$_requestMethod;
		}
		if ('PUT' === $argRequestMethod || 'DELETE' === $argRequestMethod){
			static $putParam = NULL;
			if(NULL === $putParam){
				$putParam = parse_phpinput_str();
			}
			$requestParams = $putParam;
		}
		else if ('POST' === $argRequestMethod){
			// XXX multipart/form-dataもPOSTなので、PHPに任せます
			$requestParams = $_POST;
		}
		else if ('GET' === $argRequestMethod || 'HEAD' === $argRequestMethod){
			$requestParams = $_GET;
		}
		else {
			// 未知のメソッド
		}
		// 完全に永続的に不要なゴミリクエストパラメータの排除はココ
		if (is_array($requestParams)){
			if(isset($requestParams['_'])){
				unset($requestParams['_']);
			}
			if(isset($requestParams['_p_'])){
				unset($requestParams['_p_']);
			}
		}
		return $requestParams;
	}

	public function getRequestParams($argRequestMethod=NULL){
		return self::resolveRequestParams($argRequestMethod);
	}

	/**
	 * フレームワーク標準のAuth機能を利用した認証と登録を行って、RESTする(スマフォAPI向け)
	 * @return array 配列構造のリソースデータ
	 */
	public function UIDAuthAndExecute(){
		$this->_init();
		// UIDAuthREST用変数初期化
		$DBO = NULL;
		$User = FALSE;
		$userModelName = NULL;
		$UserModelAccessedFieldName = NULL;
		$UserModelCreatedFieldName = NULL;
		$UserModelModifiedFieldName = NULL;
		$deviceTypeFieldName = NULL;
		$deviceTokenFieldName = NULL;
		$deviceRegistrationIDFieldName = NULL;
		if(class_exists('Configure') && NULL !== Configure::constant('REST_UIDAUTH_USER_TBL_NAME')){
			$userModelName = Configure::REST_UIDAUTH_USER_TBL_NAME;
		}
		if(class_exists('Configure') && NULL !== Configure::constant('REST_UIDAUTH_USER_ACCESSED_DATE_KEY_NAME')){
			$UserModelAccessedFieldName = Configure::REST_UIDAUTH_USER_ACCESSED_DATE_KEY_NAME;
		}
		if(class_exists('Configure') && NULL !== Configure::constant('REST_UIDAUTH_USER_CREATE_DATE_KEY_NAME')){
			$UserModelCreatedFieldName = Configure::REST_UIDAUTH_USER_CREATE_DATE_KEY_NAME;
		}
		if(class_exists('Configure') && NULL !== Configure::constant('REST_UIDAUTH_USER_MODIFY_DATE_KEY_NAME')){
			$UserModelModifiedFieldName = Configure::REST_UIDAUTH_USER_MODIFY_DATE_KEY_NAME;
		}
		if(class_exists('Configure') && NULL !== Configure::constant('REST_UIDAUTH_DEVICE_TYPE_FIELD_NAME')){
			$deviceTypeFieldName = Configure::REST_UIDAUTH_DEVICE_TYPE_FIELD_NAME;
		}
		if(class_exists('Configure') && NULL !== Configure::constant('REST_UIDAUTH_DEVICE_TYPE_FIELD_NAME')){
			$deviceTypeFieldName = Configure::REST_UIDAUTH_DEVICE_TYPE_FIELD_NAME;
		}
		if(class_exists('Configure') && NULL !== Configure::constant('REST_UIDAUTH_DEVICE_TOKEN_FIELD_NAME')){
			$deviceTokenFieldName = Configure::REST_UIDAUTH_DEVICE_TOKEN_FIELD_NAME;
		}
		if(class_exists('Configure') && NULL !== Configure::constant('REST_UIDAUTH_DEVICE_REGISTRATIONID_FIELD_NAME')){
			$deviceRegistrationIDFieldName = Configure::REST_UIDAUTH_DEVICE_REGISTRATIONID_FIELD_NAME;
		}
		if(defined('PROJECT_NAME') && strlen(PROJECT_NAME) > 0 && class_exists(PROJECT_NAME . 'Configure')){
			$ProjectConfigure = PROJECT_NAME . 'Configure';
			if(NULL !== $ProjectConfigure::constant('REST_UIDAUTH_USER_TBL_NAME')){
				$userModelName = $ProjectConfigure::REST_UIDAUTH_USER_TBL_NAME;
			}
			if(NULL !== $ProjectConfigure::constant('REST_UIDAUTH_USER_ACCESSED_DATE_KEY_NAME')){
				$UserModelAccessedFieldName = $ProjectConfigure::REST_UIDAUTH_USER_ACCESSED_DATE_KEY_NAME;
			}
			if(NULL !== $ProjectConfigure::constant('REST_UIDAUTH_USER_CREATE_DATE_KEY_NAME')){
				$UserModelCreatedFieldName = $ProjectConfigure::REST_UIDAUTH_USER_CREATE_DATE_KEY_NAME;
			}
			if(NULL !== $ProjectConfigure::constant('REST_UIDAUTH_USER_MODIFY_DATE_KEY_NAME')){
				$UserModelModifiedFieldName = $ProjectConfigure::REST_UIDAUTH_USER_MODIFY_DATE_KEY_NAME;
			}
			if(NULL !== $ProjectConfigure::constant('REST_UIDAUTH_DEVICE_TYPE_FIELD_NAME')){
				$deviceTypeFieldName = $ProjectConfigure::REST_UIDAUTH_DEVICE_TYPE_FIELD_NAME;
			}
			if(NULL !== $ProjectConfigure::constant('REST_UIDAUTH_DEVICE_TOKEN_FIELD_NAME')){
				$deviceTokenFieldName = $ProjectConfigure::REST_UIDAUTH_DEVICE_TOKEN_FIELD_NAME;
			}
			if(NULL !== $ProjectConfigure::constant('REST_UIDAUTH_DEVICE_REGISTRATIONID_FIELD_NAME')){
				$deviceRegistrationIDFieldName = $ProjectConfigure::REST_UIDAUTH_DEVICE_REGISTRATIONID_FIELD_NAME;
			}
		}
		try{
			$DBO = self::_getDBO();
			// UIDAuth
			if (0 < strlen((string)getConfig('APP_AUTH_TBL_NAME'))){
				// アプリ用のAuth設定を適用する
				Auth::init();
				Auth::$authTable = getConfig('APP_AUTH_TBL_NAME');
				Auth::$authPKeyField = getConfig('APP_AUTH_PKEY_FIELD_NAME');
				Auth::$authIDField = getConfig('APP_AUTH_ID_FIELD_NAME');
				Auth::$authPassField = getConfig('APP_AUTH_PASS_FIELD_NAME');
				Auth::$authIDEncrypted = getConfig('APP_AUTH_ID_ENCRYPTED');
				Auth::$authPassEncrypted = getConfig('APP_AUTH_PASS_ENCRYPTED');
				Session::init();
				if(isset($ProjectConfigure) && NULL !== $ProjectConfigure::constant('APP_AUTH_CRYPT_KEY') && NULL !== $ProjectConfigure::constant('APP_AUTH_CRYPT_IV')){
					Auth::$sessionCryptKey = $ProjectConfigure::APP_AUTH_CRYPT_KEY;
					Auth::$sessionCryptIV = $ProjectConfigure::APP_AUTH_CRYPT_IV;
					Auth::$authCryptKey = $ProjectConfigure::APP_AUTH_CRYPT_KEY;
					Auth::$authCryptIV = $ProjectConfigure::APP_AUTH_CRYPT_IV;
					Session::$cryptKey = $ProjectConfigure::APP_AUTH_CRYPT_KEY;
					Session::$cryptIV = $ProjectConfigure::APP_AUTH_CRYPT_IV;
				}
				else if (0 < strlen(getConfig('APP_AUTH_CRYPT_KEY'))){
					Auth::$sessionCryptKey = getConfig('APP_AUTH_CRYPT_KEY');
					Auth::$sessionCryptIV = getConfig('APP_AUTH_CRYPT_IV');
					Auth::$authCryptKey = getConfig('APP_AUTH_CRYPT_KEY');
					Auth::$authCryptIV = getConfig('APP_AUTH_CRYPT_IV');
					Session::$cryptKey = getConfig('APP_AUTH_CRYPT_KEY');
					Session::$cryptIV = getConfig('APP_AUTH_CRYPT_IV');
				}
			}
			$Device = Auth::getCertifiedUser();
			if(FALSE === $Device){
				// 登録処理
				// SessionID=端末固有IDと言う決めに沿って登録を行う
				$deviceID = Auth::getDecryptedAuthIdentifier();
				debug('$deviceID='.$deviceID);
				if(30 >= strlen($deviceID)){
					// UIDエラー！ 認証エラー
					$this->httpStatus = 401;
					throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
				}
				$Device = Auth::registration($deviceID, $deviceID, self::$nowGMT);
				// 強制認証で証明を得る
				if(TRUE !== Auth::certify($Device->{Auth::$authIDField}, $Device->{Auth::$authPassField}, NULL, TRUE)){
					// 認証NG(401)
					$this->httpStatus = 401;
					throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
				}
				// user情報の更新
				$userAdded = FALSE;
				$deviceModified = FALSE;
				if(NULL !== $userModelName && $userModelName != $Device->tableName){
					$ownerIDField = $userModelName . '_id';
					if(isset($Device->{$this->authUserIDFieldName})){
						$ownerIDField = $this->authUserIDFieldName;
					}
					if(!(0 < strlen($Device->{$ownerIDField}) && '0' != $Device->{$ownerIDField})){
						// userテーブルとdeviceテーブルのテーブル名が違うので、userテーブルの保存を行う
						$User = self::_getModel($userModelName);
						if(NULL !== $UserModelAccessedFieldName && in_array($UserModelAccessedFieldName, $User->getFieldKeys())){
							$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelAccessedFieldName)))}(self::$nowGMT);
						}
						if(NULL !== $UserModelCreatedFieldName && in_array($UserModelCreatedFieldName, $User->getFieldKeys())){
							$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelCreatedFieldName)))}(self::$nowGMT);
						}
						if(NULL !== $UserModelModifiedFieldName && in_array($UserModelModifiedFieldName, $User->getFieldKeys())){
							$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelModifiedFieldName)))}(self::$nowGMT);
						}
						$User->save();
						// deviceテーブルにユーザーIDのセット(強制)
						$Device->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $ownerIDField)))}($User->pkey);
						$deviceModified = TRUE;
						$userAdded = TRUE;
					}
					else {
						$User = self::_getModel($userModelName, $Device->{$ownerIDField});
						if(!(0 < (int)$User->pkey)){
							// デバイスの持ち主が変わった可能性
							// userテーブルとdeviceテーブルのテーブル名が違うので、userテーブルの保存を行う
							if(NULL !== $UserModelAccessedFieldName && in_array($UserModelAccessedFieldName, $User->getFieldKeys())){
								$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelAccessedFieldName)))}(self::$nowGMT);
							}
							if(NULL !== $UserModelCreatedFieldName && in_array($UserModelCreatedFieldName, $User->getFieldKeys())){
								$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelCreatedFieldName)))}(self::$nowGMT);
							}
							if(NULL !== $UserModelModifiedFieldName && in_array($UserModelModifiedFieldName, $User->getFieldKeys())){
								$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelModifiedFieldName)))}(self::$nowGMT);
							}
							$User->save();
							// deviceテーブルにユーザーIDのセット(強制)
							$Device->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $ownerIDField)))}($User->pkey);
							$deviceModified = TRUE;
							$userAdded = TRUE;
						}
					}
				}
				else {
					$User = $Device;
				}
				// device情報の更新
				// デバイズのtype設定があるなら保存する
				if(NULL !== $deviceTypeFieldName && in_array($deviceTypeFieldName, $Device->getFieldKeys()) && isset($this->deviceType) && 0 < strlen($this->deviceType)){
					if($Device->{$deviceTypeFieldName} != $this->deviceType){
						$Device->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $deviceTypeFieldName)))}($this->deviceType);
						$deviceModified = TRUE;
					}
				}
				$request = $this->getRequestParams();
				// デバイズトークン(iOS)設定があるなら保存する
				if(NULL !== $deviceTokenFieldName && in_array($deviceTokenFieldName, $Device->getFieldKeys()) && isset($request[$deviceTokenFieldName]) && 0 < strlen($request[$deviceTokenFieldName])){
					// 上書きチェック
					if($Device->{$deviceTokenFieldName} != $request[$deviceTokenFieldName]){
						$Device->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $deviceTokenFieldName)))}($request[$deviceTokenFieldName]);
						$deviceModified = TRUE;
					}
				}
				// SANDBOX端末設定(iOS)があるなら保存する
				// XXX フィード名を定数化するかどうか
				if(in_array('sandbox_enabled', $Device->getFieldKeys()) && isset($request['sandbox_enabled']) && 0 < strlen($request['sandbox_enabled'])){
					// 上書きチェック
					if($Device->sandbox_enabled != $request['sandbox_enabled']){
						$Device->setSandboxEnabled($request['sandbox_enabled']);
						$deviceModified = TRUE;
					}
				}
				// アプリバージョンがあるなら保存する
				// XXX フィード名を定数化する！？
				if(in_array('version_code', $Device->getFieldKeys()) && isset(Core::$appVersion) && NULL !== Core::$appVersion && 0 < strlen(Core::$appVersion)){
					// 上書きチェック
					if($Device->version_code != Core::$appVersion){
						$Device->setVersionCode(Core::$appVersion);
						$deviceModified = TRUE;
					}
				}
				// アプリ表示バージョンがあるなら保存する
				// XXX フィード名を定数化する！？
				if(in_array('version_name', $Device->getFieldKeys()) && isset(Core::$appDispayVersion) && NULL !== Core::$appDispayVersion && 0 < strlen(Core::$appDispayVersion)){
					// 上書きチェック
					if($Device->version_name != Core::$appDispayVersion){
						$Device->setVersionName(Core::$appDispayVersion);
						$deviceModified = TRUE;
					}
				}
				// レジストレーションID(Android)設定があるなら保存する
				if(NULL !== $deviceRegistrationIDFieldName && in_array($deviceRegistrationIDFieldName, $Device->getFieldKeys()) && isset($request[$deviceRegistrationIDFieldName]) && 0 < strlen($request[$deviceRegistrationIDFieldName])){
					// 上書きチェック
					if($Device->{$deviceRegistrationIDFieldName} != $request[$deviceRegistrationIDFieldName]){
						$Device->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $deviceRegistrationIDFieldName)))}($request[$deviceRegistrationIDFieldName]);
						$deviceModified = TRUE;
					}
				}
				if(TRUE === $deviceModified){
					if(in_array(Auth::$authModifiedField, $Device->getFieldKeys())){
						$Device->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', Auth::$authModifiedField)))}(self::$nowGMT);
					}
					$Device->save();
				}
				if(TRUE === $deviceModified || TRUE === $userAdded){
					// 一旦コミット
					$DBO->commit();
				}
			}
			elseif(NULL !== $userModelName && $userModelName != $Device->tableName){
				$ownerIDField = $userModelName . '_id';
				if(isset($Device->{$this->authUserIDFieldName})){
					$ownerIDField = $this->authUserIDFieldName;
				}
				// user情報の更新
				$userModified = FALSE;
				$deviceModified = FALSE;
				debug('is owner?'.$Device->{$ownerIDField});
				if(!(0 < strlen($Device->{$ownerIDField}) && '0' != $Device->{$ownerIDField})){
					// user情報の更新
					// userテーブルとdeviceテーブルのテーブル名が違うので、userテーブルの保存を行う
					$User = self::_getModel($userModelName);
					if(NULL !== $UserModelAccessedFieldName && in_array($UserModelAccessedFieldName, $User->getFieldKeys())){
						$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelAccessedFieldName)))}(self::$nowGMT);
						$userModified = TRUE;
					}
					if(NULL !== $UserModelCreatedFieldName && in_array($UserModelCreatedFieldName, $User->getFieldKeys())){
						$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelCreatedFieldName)))}(self::$nowGMT);
						$userModified = TRUE;
					}
					// deviceテーブルにユーザーIDのセット(強制)
					$Device->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $ownerIDField)))}($User->pkey);
					if(NULL !== $deviceTypeFieldName && in_array($deviceTypeFieldName, $Device->getFieldKeys()) && isset($this->deviceType) && 0 < strlen($this->deviceType)){
						$Device->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $deviceTypeFieldName)))}($this->deviceType);
						$deviceModified = TRUE;
					}
				}
				else {
					$User = self::_getModel($userModelName, $Device->{$ownerIDField});
					if(!(0 < (int)$User->pkey)){
						// デバイスの持ち主が変わった可能性
						// userテーブルとdeviceテーブルのテーブル名が違うので、userテーブルの保存を行う
						if(NULL !== $UserModelAccessedFieldName && in_array($UserModelAccessedFieldName, $User->getFieldKeys())){
							$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelAccessedFieldName)))}(self::$nowGMT);
							$userModified = TRUE;
						}
						if(NULL !== $UserModelCreatedFieldName && in_array($UserModelCreatedFieldName, $User->getFieldKeys())){
							$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelCreatedFieldName)))}(self::$nowGMT);
							$userModified = TRUE;
						}
						// deviceテーブルにユーザーIDのセット(強制)
						$Device->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $ownerIDField)))}($User->pkey);
						if(NULL !== $deviceTypeFieldName && in_array($deviceTypeFieldName, $Device->getFieldKeys()) && isset($this->deviceType) && 0 < strlen($this->deviceType)){
							$Device->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $deviceTypeFieldName)))}($this->deviceType);
							$deviceModified = TRUE;
						}
					}
				}
				$request = $this->getRequestParams();
				// デバイズトークン(iOS)設定があるなら保存する
				if(NULL !== $deviceTokenFieldName && in_array($deviceTokenFieldName, $Device->getFieldKeys()) && isset($request[$deviceTokenFieldName]) && 0 < strlen($request[$deviceTokenFieldName])){
					// 上書きチェック
					if($Device->{$deviceTokenFieldName} != $request[$deviceTokenFieldName]){
						$Device->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $deviceTokenFieldName)))}($request[$deviceTokenFieldName]);
						$deviceModified = TRUE;
					}
				}
				// SANDBOX端末設定(iOS)があるなら保存する
				if(in_array('sandbox_enabled', $Device->getFieldKeys()) && isset($request['sandbox_enabled']) && 0 < strlen($request['sandbox_enabled'])){
					// 上書きチェック
					if($Device->sandbox_enabled != $request['sandbox_enabled']){
						$Device->setSandboxEnabled($request['sandbox_enabled']);
						$deviceModified = TRUE;
					}
				}
				// アプリバージョンがあるなら保存する
				// XXX フィード名を定数化する！？
				if(in_array('version_code', $Device->getFieldKeys()) && isset(Core::$appVersion) && NULL !== Core::$appVersion && 0 < strlen(Core::$appVersion)){
					// 上書きチェック
					if($Device->version_code != Core::$appVersion){
						$Device->setVersionCode(Core::$appVersion);
						$deviceModified = TRUE;
					}
				}
				// アプリ表示バージョンがあるなら保存する
				// XXX フィード名を定数化する！？
				if(in_array('version_name', $Device->getFieldKeys()) && isset(Core::$appDispayVersion) && NULL !== Core::$appDispayVersion && 0 < strlen(Core::$appDispayVersion)){
					// 上書きチェック
					if($Device->version_name != Core::$appDispayVersion){
						$Device->setVersionName(Core::$appDispayVersion);
						$deviceModified = TRUE;
					}
				}
				// レジストレーションID(Android)設定があるなら保存する
				if(NULL !== $deviceRegistrationIDFieldName && in_array($deviceRegistrationIDFieldName, $Device->getFieldKeys()) && isset($request[$deviceRegistrationIDFieldName]) && 0 < strlen($request[$deviceRegistrationIDFieldName])){
					// 上書きチェック
					if($Device->{$deviceRegistrationIDFieldName} != $request[$deviceRegistrationIDFieldName]){
						$Device->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $deviceRegistrationIDFieldName)))}($request[$deviceRegistrationIDFieldName]);
						$deviceModified = TRUE;
					}
				}
				if(TRUE === $userModified){
					if(NULL !== $UserModelAccessedFieldName && in_array($UserModelAccessedFieldName, $User->getFieldKeys())){
						$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelAccessedFieldName)))}(self::$nowGMT);
						$userModified = TRUE;
					}
					if(NULL !== $UserModelModifiedFieldName && in_array($UserModelModifiedFieldName, $User->getFieldKeys())){
						$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelModifiedFieldName)))}(self::$nowGMT);
						$userModified = TRUE;
					}
					$User->save();
				}
				if(TRUE === $deviceModified){
					if(in_array(Auth::$authModifiedField, $Device->getFieldKeys())){
						$Device->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', Auth::$authModifiedField)))}(self::$nowGMT);
					}
					$Device->save();
				}
				if(TRUE === $deviceModified || TRUE === $userModified){
					// 一旦コミット
					$DBO->commit();
				}
			}
			else{
				$User = $Device;
			}
		}
		catch (Exception $Exception){
			if(NULL !== $DBO && is_object($DBO)){
				// トランザクションを異常終了する
				$DBO->rollback();
			}
			// 実装の問題によるエラー
			$this->httpStatus = 500;
			if(400 === $Exception->getCode() || 401 === $Exception->getCode() || 404 === $Exception->getCode() || 405 === $Exception->getCode() || 503 === $Exception->getCode()){
				$this->httpStatus = $Exception->getCode();
			}
			throw new RESTException($Exception->getMessage(), $this->httpStatus);
		}

		$this->AuthDevice = $Device;
		if(FALSE !== $User){
			// 認証OK
			$this->AuthUser = $User;
			debug('accessed='.$User->$UserModelAccessedFieldName);
			if(NULL !== $UserModelAccessedFieldName && in_array($UserModelAccessedFieldName, $User->getFieldKeys()) && substr($User->$UserModelAccessedFieldName, 0, -3) != substr(self::$nowGMT, 0, -3)){
				$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelAccessedFieldName)))}(self::$nowGMT);
				if(NULL !== $UserModelModifiedFieldName && in_array($UserModelModifiedFieldName, $User->getFieldKeys())){
					$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelModifiedFieldName)))}(self::$nowGMT);
				}
				$User->save();
				// 一旦コミット
				$DBO->commit();
			}
			$this->authUserID = $User->pkey;
			if(NULL === $this->authUserIDFieldName){
				// XXX xxx_xxと言うAuthユーザー判定法は固定です！使用は任意になります。
				$this->authUserIDFieldName = strtolower($User->tableName) . '_' . $User->pkeyName;
			}
			$this->authUserQuery = ' `' . $this->authUserIDFieldName . '` = \'' . $User->pkey . '\'';
			return $this->execute();
		}

		// XXX ココを通るのは相当なイレギュラー！
		$this->httpStatus = 500;
		throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
	}

	/**
	 * フレームワーク標準のAuth機能を利用した認証を行って、RESTする
	 * @return array 配列構造のリソースデータ
	 */
	public function authAndExecute($argResourceHint=NULL, $argRequestParams=NULL, $argRequestMethod=NULL, $argGETParams=NULL){
		$this->_init();
		$DBO = NULL;

		// XXX HEADリクエストでAuthUserTableへの参照は強制的にAuthを外す！
		if (isset($_SERVER['REQUEST_METHOD']) && 'HEAD' === $_SERVER['REQUEST_METHOD']) {
			$resource = self::resolveRESTResource($_GET['_r_']);
			Auth::init();
			if (strtolower($resource['model']) == strtolower(Auth::$authTable)){
				return $this->execute($argResourceHint, $argRequestParams, $argRequestMethod, $argGETParams);
			}
		}

		try{
			// Auth
			$DBO = self::_getDBO();
			$User = Auth::getCertifiedUser();
			if(FALSE === $User){
				// 認証NG(401)
				$this->httpStatus = 401;
				throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
			}
		}
		catch (Exception $Exception){
			if(NULL !== $DBO && is_object($DBO)){
				// トランザクションを異常終了する
				$DBO->rollback();
			}
			// 実装の問題によるエラー
			$this->httpStatus = 500;
			if(400 === $Exception->getCode() || 401 === $Exception->getCode() || 404 === $Exception->getCode() || 405 === $Exception->getCode() || 503 === $Exception->getCode()){
				$this->httpStatus = $Exception->getCode();
			}
			throw new RESTException($Exception->getMessage(), $this->httpStatus);
		}

		if(FALSE !== $User){
			// 認証OK
			$this->AuthUser = $User;
			if(NULL !== $UserModelAccessedFieldName && in_array($UserModelAccessedFieldName, $User->getFieldKeys()) && substr($User->$UserModelAccessedFieldName, 0, -3) != substr(self::$nowGMT, 0, -3)){
				$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelAccessedFieldName)))}(self::$nowGMT);
				if(NULL !== $UserModelModifiedFieldName && in_array($UserModelModifiedFieldName, $User->getFieldKeys())){
					$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $UserModelModifiedFieldName)))}(self::$nowGMT);
				}
				$User->save();
				// 一旦コミット
				$DBO->commit();
			}
			$this->authUserID = $User->pkey;
			// XXX xxx_xxと言うAuthユーザー判定法は固定です！使用は任意になります。
			if(NULL === $this->authUserIDFieldName){
				$this->authUserIDFieldName = strtolower($User->tableName) . '_' . $User->pkeyName;
			}
			$this->authUserQuery = ' ' . $this->authUserIDFieldName . ' = \'' . $User->pkey . '\'';
			return $this->execute($argResourceHint, $argRequestParams, $argRequestMethod, $argGETParams);
		}

		// XXX ココを通るのは相当なイレギュラー！
		// 恐らく実装の問題
		$this->httpStatus = 500;
		throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
	}

	/**
	 * RESTする
	 * @return array 配列構造のリソースデータ
	 */
	public function execute($argResourceHint=NULL, $argRequestParams=NULL, $argRequestMethod=NULL, $argGETParams=NULL){
		static $prepend = FALSE;
		static $append = FALSE;
		$this->_init();
		// RESTアクセスされるリソースの特定
		$this->restResource = $argResourceHint;
		if(NULL === $argResourceHint){
			$this->restResource = $_GET['_r_'];
		}
		if(isset($_GET['_deep_']) && TRUE == ('0' === $_GET['_deep_'] || 'false' === strtolower($_GET['_deep_']))){
			// RESTのDEEPモードを無効にする
			// XXX DEEPモードがTRUEの場合、[model名]_idのフィールドがリソースに合った場合、そのリソースまで自動で参照・更新・作成を試みます
			$this->deepRESTMode = FALSE;
		}
		if(isset($_SERVER['REQUEST_METHOD'])){
			$this->requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);
		}
		if(isset($_POST['_method_']) && strlen($_POST['_method_']) > 0){
			$this->requestMethod = strtoupper($_POST['_method_']);
		}
		if (NULL !== $argRequestMethod){
			$this->requestMethod = $argRequestMethod;
			self::$_requestMethod = $this->requestMethod;
		}
		// 内部RESTのDEEP用
		if (NULL !== self::$_requestMethod){
			$this->requestMethod = self::$_requestMethod;
		}
		debug($this->restResource);
		debug('rest method='.strtolower($this->requestMethod));
		$resource = self::resolveRESTResource($this->restResource);
		debug($resource);
		if(NULL === $resource){
			// リソースの指定が無かったのでエラー終了
			$this->httpStatus = 400;
			throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
		}
		if('PUT' === $this->requestMethod && NULL === $resource['ids']){
			// PUTメソッドの場合はリソースID指定は必須
			$this->httpStatus = 400;
			throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
		}

		$res = FALSE;
		$DBO = NULL;
		try{
			$DBO = self::_getDBO();

			// RESTの実行
			$this->restResourceModel = $resource['model'];
			if(NULL === $this->restResourceListed){
				$this->restResourceListed = $resource['listed'];
			}
			$this->restResource = $resource;
			// アクセストークンチェック
			if (isset($_COOKIE['refresh_token']) && 0 < strlen($_COOKIE['refresh_token'])){
				debug('whitelistcheck refresh_token check '.$_COOKIE['refresh_token']);
				// アクセストークンでの認証
				if (AccessTokenAuth::validate('9', $_COOKIE['refresh_token'])){
					// 認証元がパーミッション9以上(実質9)の場合はエンドユーザーなのでホワイトリストフィルターでAPIのアクセス認証をさらにきつくする
					if (9 > (int)AccessTokenAuth::$permission){
						// 8以上は管理ユーザーのパーミッションなので、オールホワイトとする
						$_SERVER['ALLOW_ALL_WHITE'] = 1;
					}
					if (isset($_SERVER['ALLOW_ALL_WHITE']) && TRUE === (TRUE === $_SERVER['ALLOW_ALL_WHITE'] || 1 === (int)$_SERVER['ALLOW_ALL_WHITE']) && 0 === (int)AccessTokenAuth::$permission){
						// 0はスーパーユーザーのパーミッションなので、フィールドの網掛けも外す
						$_SERVER['__SUPER_USER__'] = TRUE;
					}
				}
				debug('whitelistcheck refresh_token checked');
			}
			// ホワイトリストフィルター
			$classHint = str_replace(' ', '', ucwords(str_replace(' ', '', $this->restResourceModel)));
			debug('$classHint='.$classHint);
			debug('whitelistcheck allowed='.var_export($this->allowed, TRUE));
			if (isset($_SERVER['ALLOW_ALL_WHITE']) && TRUE === (TRUE === $_SERVER['ALLOW_ALL_WHITE'] || 1 === (int)$_SERVER['ALLOW_ALL_WHITE'])){
				debug('whitelistcheck ALLOW_ALL_WHITE='.var_export($_SERVER['ALLOW_ALL_WHITE'],TRUE));
				$this->allowed = TRUE;
			}
			//if(TRUE !== isTest() && !isset($this->AuthUser) && TRUE !== $this->allowed){
			if(1 !== (int)getLocalEnabled() && TRUE !== (TRUE === $this->allowed || NULL === $this->allowed)){
				// アクセスエラー！
				// フィルターを通り抜けてしまったイレギュラーなので、ココを遠たらおそらくフレームワークバグです・・・
				$this->httpStatus = 405;
				throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
			}
			else{
				if (TRUE === $this->rootREST && TRUE !== (isset($_SERVER['ALLOW_ALL_WHITE']) && TRUE === ('1' === $_SERVER['ALLOW_ALL_WHITE'] || 1 === $_SERVER['ALLOW_ALL_WHITE'] || 'true' === $_SERVER['ALLOW_ALL_WHITE'] || true === $_SERVER['ALLOW_ALL_WHITE']))){
					// 現在のホワイトリストの一覧を取得
					$nowWhiteList = stripslashes(trim(getConfig('REST_RESOURCE_WHITE_LIST')));
					debug("whitelistcheck now whiteList=". $nowWhiteList);
					$whiteList = json_decode($nowWhiteList, TRUE);
					$resourcePath = $this->restResource["model"];
					if (TRUE === $this->restResource["me"]){
						$resourcePath = "me.".$resourcePath;
					}
					if (TRUE !== $this->restResource["me"] && TRUE === $this->restResource["listed"]){
						$resourcePath .= ".list";
					}
					debug("whitelistcheck resourcePath=". $resourcePath);
					if (NULL === $nowWhiteList && 0 === strlen($nowWhiteList)){
						// アクセスエラー！
						$this->httpStatus = 405;
						throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
					}
					else {
						debug("whitelistcheck isTest=". var_export(isTest(), true));
						debug("whitelistcheck whiteList=". var_export($whiteList, true));
						$paramKeys = array_keys($this->getRequestParams());
						$allowUser = $_SERVER['REMOTE_ADDR'];
						if (isset($this->AuthUser) && NULL !== $this->AuthUser && 0 < strlen($this->AuthUser->tableName)){
							$allowUser = $this->AuthUser->tableName;
						}
						// ローカル環境の場合は、ホワイトフィルターを育てる処理
						// XXX テストテスト環境でもホワイトフィルターを育てたい場合はコメントアウトを書き換える
						//if (isTest()){
						if (1 === (int)getLocalEnabled()){
							$updateWhiteList = FALSE;
							if (!isset($whiteList[$resourcePath])){
								$whiteList[$resourcePath] = array("Method ".$this->requestMethod => NULL);
							}
							if (!isset($whiteList[$resourcePath]["Method ".$this->requestMethod])){
								$whiteList[$resourcePath]["Method ".$this->requestMethod] = array();
							}
							// アクセスを許可するユーザー(IPフィルター又は認証ユーザー)のデフォルト値を設定
							if ($allowUser === $_SERVER['REMOTE_ADDR']){
								// デフォルトは全てのIP・ユーザーを許可する
								$allowUser = '*';
								$IPFilter = getConfig('ALLOW_IP_FILTER');
								if (FALSE !== $IPFilter && NULL !== $IPFilter){
									// ALLOW_IP_FILTERの値をIPフィルターのデフォルト値として採用する
									$allowUser = $IPFilter;
								}
								$IPFilter = getConfig('REST_ALLOW_IP_FILTER');
								if (FALSE !== $IPFilter && NULL !== $IPFilter){
									// REST_ALLOW_IP_FILTERの値をIPフィルターのデフォルト値として採用する
									$allowUser = $IPFilter;
								}
							}
							// ホワイトリストに追加
							if (isset($whiteList[$resourcePath]["Method ".$this->requestMethod][$allowUser])){
								$paramKeys = array_merge($whiteList[$resourcePath]["Method ".$this->requestMethod][$allowUser], array_diff($paramKeys, $whiteList[$resourcePath]["Method ".$this->requestMethod][$allowUser]));
								debug('whitelistcheckdiff '.var_export(array_diff($paramKeys, $whiteList[$resourcePath]["Method ".$this->requestMethod][$allowUser]), TRUE));
							}
							$whiteList[$resourcePath]["Method ".$this->requestMethod][$allowUser] = $paramKeys;
							$newWhiteList = json_encode($whiteList);
							debug("whitelistcheck new whiteList=". $newWhiteList);
							debug("whitelistcheck now whiteList=". $nowWhiteList);
							// Configに書き出す
							if ($nowWhiteList != $newWhiteList){
								debug("whitelistcheck modify whiteList=". $newWhiteList);
								modifiyConfig('REST_RESOURCE_WHITE_LIST', PHP_EOL.PHP_TAB.PHP_TAB.PHP_TAB.
								str_replace("\"],\"", "\"]".PHP_EOL.PHP_TAB.PHP_TAB.PHP_TAB.PHP_TAB.PHP_TAB.",\"",
								str_replace("\"]},\"Method ", "\"]}".PHP_EOL.PHP_TAB.PHP_TAB.PHP_TAB.PHP_TAB.",\"Method ",
								str_replace("\":{\"", "\":".PHP_EOL.PHP_TAB.PHP_TAB.PHP_TAB.PHP_TAB.PHP_TAB."{\"",
								str_replace(":{\"Method ", ":".PHP_EOL.PHP_TAB.PHP_TAB.PHP_TAB.PHP_TAB."{\"Method ",
								str_replace("}}", "}".PHP_EOL.PHP_TAB.PHP_TAB.PHP_TAB."}",
								str_replace("\"]}}", "\"]}".PHP_EOL.PHP_TAB.PHP_TAB.PHP_TAB.PHP_TAB."}".PHP_EOL.PHP_TAB.PHP_TAB.PHP_TAB, json_encode($whiteList))))))).PHP_EOL.PHP_TAB.PHP_TAB);
							}
							$this->allowed = TRUE;
						}
						else {
							debug("whitelistcheck new is??");
							// ホワイトリストによるリソースアクセスチェック！
							if (!(isset($whiteList[$resourcePath]) && isset($whiteList[$resourcePath]["Method ".$this->requestMethod]))){
								// アクセスエラー！
								$this->httpStatus = 405;
								throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
							}
							else if(is_array($whiteList[$resourcePath]["Method ".$this->requestMethod])){
								// リクエストユーザーの整合性チェック
								if ($_SERVER['REMOTE_ADDR'] === $allowUser){
									// IPアドレスによるアクセスチェック
									$IPAllowed = FALSE;
									$allowIPs = array_keys($whiteList[$resourcePath]["Method ".$this->requestMethod]);
									// 　逆から評価
									for ($IPIdx = count($allowIPs) - 1; $IPIdx >= 0; $IPIdx--){
										if (TRUE === (preg_match('/^\d\.\d\.\d\.\d/', $allowIPs[$IPIdx]) || '*' === $allowIPs[$IPIdx]) && TRUE === checkIP($_SERVER['REMOTE_ADDR'], $allowIPs[$IPIdx])){
											$IPAllowed = TRUE;
											$allowUser = $allowIPs[$IPIdx];
											break;
										}
									}
									if (FALSE === $IPAllowed){
										// IPアドレスが許可されていない！
										// アクセスエラー！
										$this->httpStatus = 405;
										throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
									}
								}
								// リクエストメソッドとリクエストパラメータの整合性チェック
								if(isset($whiteList[$resourcePath]["Method ".$this->requestMethod][$allowUser])){
									$diffs = array_diff($paramKeys, $whiteList[$resourcePath]["Method ".$this->requestMethod][$allowUser]);
									if (0 < count($diffs)){
										$notFoundDiffKey = FALSE;
										foreach ($diffs as $diffKey){
											if (!in_array($diffKey, $whiteList[$resourcePath]["Method ".$this->requestMethod][$allowUser])){
												// ホントに無かったので即エラー
												$notFoundDiffKey = TRUE;
												break;
											}
										}
										// ホントにキーが足らないのでエラー確定
										if (TRUE === $notFoundDiffKey){
											// 許可されたメソッド内でパラメータが多い(許可されていないパラメータをリクエストに含めている)
											// アクセスエラー！
											debug('whitelistcheck diffkey error'.var_export($diffs, TRUE));
											$this->httpStatus = 405;
											throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
										}
									}
								}
							}
							else if ("*" !== $whiteList[$resourcePath]["Method ".$this->requestMethod]){
								// 外部からのアクセスを完全に拒絶されているメソッドへのアクセス
								// アクセスエラー！
								$this->httpStatus = 405;
								throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
							}
							// 許可されたRESTへのアクセス
							debug("whitelistcheck new is ok!");
							$this->allowed = TRUE;
						}
					}
				}
			}

			// REST全体のPrepend処理
			if(TRUE === $this->rootREST && FALSE === $prepend){
				$prepend = TRUE;
// 				// 外部からのGETのQUERYとJOINは危険なので、FILTERで許可されていなければ削除する
// 				if (TRUE !== $this->allowed){
// 					$_GET['QUERY'] = '';
// 					$_GET['JOIN'] = '';
// 					unset($_GET['QUERY']);
// 					unset($_GET['JOIN']);
// 				}
				// 許可されてあっても、「; GRANT CREATE SHOW INSERT UPDATE DELETE ALTER DROP TRUNCATE」は削除
				if (isset($_GET['QUERY'])){
					$_GET['QUERY'] = str_ireplace('; ', '', $_GET['QUERY']);
					$_GET['QUERY'] = str_ireplace('GRANT ', '', $_GET['QUERY']);
					$_GET['QUERY'] = str_ireplace('CREATE ', '', $_GET['QUERY']);
					$_GET['QUERY'] = str_ireplace('SHOW ', '', $_GET['QUERY']);
					$_GET['QUERY'] = str_ireplace('INSERT ', '', $_GET['QUERY']);
					$_GET['QUERY'] = str_ireplace('UPDATE ', '', $_GET['QUERY']);
					$_GET['QUERY'] = str_ireplace('DELETE ', '', $_GET['QUERY']);
					$_GET['QUERY'] = str_ireplace('ALTER ', '', $_GET['QUERY']);
					$_GET['QUERY'] = str_ireplace('DROP ', '', $_GET['QUERY']);
					$_GET['QUERY'] = str_ireplace('TRUNCATE ', '', $_GET['QUERY']);
				}
				if (isset($_GET['JOIN'])){
					$_GET['JOIN'] = str_ireplace('; ', '', $_GET['JOIN']);
					$_GET['JOIN'] = str_ireplace('GRANT ', '', $_GET['JOIN']);
					$_GET['JOIN'] = str_ireplace('CREATE ', '', $_GET['JOIN']);
					$_GET['JOIN'] = str_ireplace('SHOW ', '', $_GET['JOIN']);
					$_GET['JOIN'] = str_ireplace('INSERT ', '', $_GET['JOIN']);
					$_GET['JOIN'] = str_ireplace('UPDATE ', '', $_GET['JOIN']);
					$_GET['JOIN'] = str_ireplace('DELETE ', '', $_GET['JOIN']);
					$_GET['JOIN'] = str_ireplace('ALTER ', '', $_GET['JOIN']);
					$_GET['JOIN'] = str_ireplace('DROP ', '', $_GET['JOIN']);
					$_GET['JOIN'] = str_ireplace('TRUNCATE ', '', $_GET['JOIN']);
				}
				// Filterがあったらフィルター処理をする
				$filerName = 'RestPrependFilter';
				debug('$filerName='.$filerName);
				if(FALSE !== Core::loadMVCFilter($filerName, TRUE)){
					$filterClass = Core::loadMVCFilter($filerName);
					$Filter = new $filterClass();
					$Filter->REST = $this;
					$Filter->execute($argRequestParams);
				}
			}

			// 指定リソースの絶対的なPrepend
			$filerName = $classHint . 'PrependFilter';
			debug('resoruce='.$filerName);
			debug('$argRequestMethod='.$argRequestMethod);
			debug('$this->requestMethod='.$this->requestMethod);
			if(FALSE !== Core::loadMVCFilter($filerName, TRUE)){
				$filterClass = Core::loadMVCFilter($filerName);
				if (TRUE === method_exists($filterClass, strtolower($this->requestMethod))){
					$Filter = new $filterClass();
					$Filter->REST = $this;
					// リクエストメソッドで分岐する
					if('POST' === $this->requestMethod || 'PUT' === $this->requestMethod){
						$Filter->{strtolower($this->requestMethod)}($argRequestParams);
					}
					else {
						$Filter->{strtolower($this->requestMethod)}($argGETParams);
					}
				}
			}

			if(FALSE !== Core::loadMVCModule($classHint, TRUE, '', TRUE)){
				debug('RestClassLoaded');
				// オーバーライドされたModelへのリソース操作クラスが在る場合は、それをnewして実行する
				$className = Core::loadMVCModule($classHint, FALSE, '', TRUE);
				debug('RestClassLoaded='.$className);
				$RestController = new $className();
				// 自分自身の持っているパブリックパラメータをブリッジ先のRestControllerに引き渡す
				$RestController->controlerClassName = $this->restResourceModel;
				$RestController->httpStatus = $this->httpStatus;
				$RestController->outputType = $this->outputType;
				$RestController->requestMethod = $this->requestMethod;
				$RestController->restResource = $this->restResource;
				$RestController->jsonUnescapedUnicode = $this->jsonUnescapedUnicode;
				$RestController->deviceType = $this->deviceType;
				$RestController->appVersion = $this->appVersion;
				$RestController->appleReviewd = $this->appleReviewd;
				$RestController->mustAppVersioned = $this->mustAppVersioned;
				$RestController->allowed = $this->allowed;
				$RestController->deepRESTMode = $this->deepRESTMode;
				if (!isset($RestController->virtualREST)){
					$RestController->virtualREST = $this->virtualREST;
				}
				debug("virtualREST=".var_export($RestController->virtualREST,true));
				$RestController->restResource = $this->restResource;
				$RestController->restResourceModel = $this->restResourceModel;
				$RestController->restResourceListed = $this->restResourceListed;
				$RestController->restResourceUserTableName = $this->restResourceUserTableName;
				$RestController->restResourceAccessedDateKeyName = $this->restResourceAccessedDateKeyName;
				$RestController->restResourceCreateDateKeyName = $this->restResourceCreateDateKeyName;
				$RestController->restResourceModifyDateKeyName = $this->restResourceModifyDateKeyName;
				$RestController->restResourceAvailableKeyName = $this->restResourceAvailableKeyName;
				$RestController->restResourceRelaySuffix = $this->restResourceRelaySuffix;
				$RestController->restResourceRelayPrefix = $this->restResourceRelayPrefix;
				$RestController->AuthUser = $this->AuthUser;
				$RestController->AuthDevice = $this->AuthDevice;
				$RestController->authUserID = $this->authUserID;
				$RestController->authUserIDFieldName = $this->authUserIDFieldName;
				$RestController->authUserQuery = $this->authUserQuery;
				// リクエストメソッドで分岐する
				if('POST' === $this->requestMethod || 'PUT' === $this->requestMethod){
					$res = $RestController->{strtolower($this->requestMethod)}($argRequestParams);
				}
				else {
					$res = $RestController->{strtolower($this->requestMethod)}($argGETParams);
				}
				$this->responceData = &$res;
				// 結果のパラメータを受け取り直す
				$this->httpStatus = $RestController->httpStatus;
				$this->outputType = $RestController->outputType;
				$this->requestMethod = $RestController->requestMethod;
				$this->restResource = $RestController->restResource;
				$this->jsonUnescapedUnicode = $RestController->jsonUnescapedUnicode;
				$this->deviceType = $RestController->deviceType;
				$this->appVersion = $RestController->appVersion;
				$this->appleReviewd = $RestController->appleReviewd;
				$this->mustAppVersioned = $RestController->mustAppVersioned;
				$this->allowed = $RestController->allowed;
				$this->deepRESTMode = $RestController->deepRESTMode;
				$this->virtualREST = $RestController->virtualREST;
				$this->restResource = $RestController->restResource;
				$this->restResourceModel = $RestController->restResourceModel;
				$this->restResourceListed = $RestController->restResourceListed;
				$this->restResourceUserTableName = $RestController->restResourceUserTableName;
				$this->restResourceAccessedDateKeyName = $RestController->restResourceAccessedDateKeyName;
				$this->restResourceCreateDateKeyName = $RestController->restResourceCreateDateKeyName;
				$this->restResourceModifyDateKeyName = $RestController->restResourceModifyDateKeyName;
				$this->restResourceAvailableKeyName = $RestController->restResourceAvailableKeyName;
				$this->restResourceRelaySuffix = $RestController->restResourceRelaySuffix;
				$this->restResourceRelayPrefix = $RestController->restResourceRelayPrefix;
				$this->AuthUser = $RestController->AuthUser;
				$this->AuthDevice = $RestController->AuthDevice;
				$this->authUserID = $RestController->authUserID;
				$this->authUserIDFieldName = $RestController->authUserIDFieldName;
				$this->authUserQuery = $RestController->authUserQuery;
			}
			else {
				// リクエストメソッドで分岐する
							// リクエストメソッドで分岐する
				if('POST' === $this->requestMethod || 'PUT' === $this->requestMethod){
					$res = $this->{strtolower($this->requestMethod)}($argRequestParams);
				}
				else {
					$res = $this->{strtolower($this->requestMethod)}($argGETParams);
				}
				$this->responceData = &$res;
			}

			// 指定リソースの絶対的なAppend
			$filerName = $classHint . 'AppendFilter';
			debug('resoruce='.$filerName);
			if(FALSE !== Core::loadMVCFilter($filerName, TRUE)){
				$filterClass = Core::loadMVCFilter($filerName);
				if (TRUE === method_exists($filterClass, strtolower($this->requestMethod))){
					$Filter = new $filterClass();
					$Filter->REST = $this;
					// リクエストメソッドで分岐する
					if('POST' === $this->requestMethod || 'PUT' === $this->requestMethod){
						$Filter->{strtolower($this->requestMethod)}($argRequestParams);
					}
					else {
						$Filter->{strtolower($this->requestMethod)}($argGETParams);
					}
				}
			}

			// REST全体のAppend処理
			if(TRUE === $this->rootREST && FALSE === $append){
				$append = TRUE;
				// Filterがあったらフィルター処理をする
				$filerName = 'RestAppendFilter';
				debug('$filerName='.$filerName);
				if(FALSE !== Core::loadMVCFilter($filerName, TRUE)){
					$filterClass = Core::loadMVCFilter($filerName);
					$Filter = new $filterClass();
					$Filter->REST = $this;
					$Filter->execute($argRequestParams);
				}
			}

			// トランザクションを正常終了する
			$DBO->commit();
		}
		catch (Exception $Exception){
			if(NULL !== $DBO && is_object($DBO)){
				// トランザクションを異常終了する
				$DBO->rollback();
			}
			// 実装の問題によるエラー
			$this->httpStatus = 500;
			if(400 === $Exception->getCode() || 401 === $Exception->getCode() || 404 === $Exception->getCode() || 405 === $Exception->getCode() || 503 === $Exception->getCode()){
				$this->httpStatus = $Exception->getCode();
			}
			throw new RESTException($Exception->getMessage(), $this->httpStatus);
		}

		debug('res=');
		debug($res);

		if(TRUE === $res){
			$res = array('success' => TRUE);
		}

		if(FALSE === $res){
			// XXX ココを通るのは相当なイレギュラー！
			// 恐らく実装の問題
			$this->httpStatus = 500;
			throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
		}

		Auth::init();
		if('HEAD' === $this->requestMethod && isset($res['describes'])){
			header('Head: ' . json_encode($res['describes']));
			header('Rules: ' . json_encode($res['rules']));
			if (strtolower($this->restResourceModel) != strtolower(Auth::$authTable)){
				header('Records: ' . $res['count']);
			}
			else if (isset($_COOKIE['refresh_token']) && 0 < strlen($_COOKIE['refresh_token'])){
				// 管理者は出力してもOK
				header('Records: ' . $res['count']);
			}
			else {
				header('Records: *');
			}
			header('Comment: ' . json_encode($res['comment']));
			if (isset($_GET['validate'] ) && $_GET['validate'] === 'unique' && 0 < (int)$res['count']){
				// 既にレコードがあるのでエラー
				$this->httpStatus = 409;
				throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
				$res = FALSE;
			}
			else {
				$res = TRUE;
			}
		}
		else if(TRUE === $this->rootREST && FALSE === $this->virtualREST && 'DELETE' !== $this->requestMethod && TRUE !== ('index' === strtolower($this->restResourceModel) && 'html' === $this->outputType)){
			// GETの時はHEADリクエストの結果を包括する為の処理
			try{
				$headRes = $this->head($argGETParams);
				@header('Head: ' . json_encode($headRes['describes']));
				if (isset($headRes['rules'])){
					@header('Rules: ' . json_encode($headRes['rules']));
				}
				if (strtolower($this->restResourceModel) != strtolower(Auth::$authTable)){
					@header('Records: ' . $headRes['count']);
				}
				else if (isset($_COOKIE['refresh_token']) && 0 < strlen($_COOKIE['refresh_token'])){
					// 管理者は出力してもOK
					header('Records: ' . $headRes['count']);
				}
				else {
					header('Records: *');
				}
				@header('Comment: ' . json_encode($headRes['comment']));
			}
			catch (Exception $Exception){
				// 何もしない
			}
		}

		if(TRUE === is_array($res) && 'HEAD' !== $this->requestMethod){
			if('html' === $this->outputType){
				debug('convert html');
				$basehtml = '';
				$URIs = explode('?', $_SERVER['REQUEST_URI']);
				if("index" === strtolower($this->restResourceModel)){
					$basehtml .= '<h2>Table List</h2>'.PHP_EOL;
					$basehtml .= '<ul>'.PHP_EOL;
					for($idx=0; $idx < count($res); $idx++){
						$basehtml .= '<li><a class="tablelink" id="table_'.$res[$idx]['name'].'" href="'.str_replace('/index.html', '/', $URIs[0]).$res[$idx]['name'].'.html"><span class="table-name">'.$res[$idx]['name'].'</span>'.((0 < strlen($res[$idx]['comment']) ? ' "<span class="table-comment">'.$res[$idx]['comment'].'</span>"' : '')).'(<span class="table-row">'.$res[$idx]['row'].'</span>)'.'</a></li>'.PHP_EOL;
					}
					$basehtml .= '</ul>'.PHP_EOL;
				}
				else {
					$requestParams = $this->getRequestParams();
					if (!isset($requestParams['LIKE'])){
						$requestParams['LIKE'] = '';
					}
					if (!isset($requestParams['ORDER'])){
						$requestParams['ORDER'] = '';
					}
					if (!isset($requestParams['LIMIT'])){
						$requestParams['LIMIT'] = '';
					}
					if (!isset($requestParams['total'])){
						$requestParams['total'] = '';
					}
					$basehtml .= '<h2>'.$this->restResourceModel.'</h2>'.PHP_EOL;
					if(TRUE === $this->restResourceListed){
						$basehtml .= '<h3><form id="crud-form-search" class="crud-form" method="GET"><input type="text" value="' . ((isset($requestParams['LIKE']))? $requestParams['LIKE'] : '') . '" name="LIKE"/><div class="submit-button search-button"><input type="submit" value="search"/></div></form></h3>'.PHP_EOL;
						if(isset($res[0])){
							$basehtml .= '<table class="list">'.PHP_EOL;
							// リストヘッダ
							$basehtml .= '<tr>'.PHP_EOL;
							foreach($res[0] as $key => $val){
								$order = $requestParams['ORDER'];
								if(strlen($order) > 0 && 0 === strpos($order, $key) && FALSE !== strpos($order, 'DESC')){
									$order = rawurlencode($key.' ASC');
								}
								else {
									$order = rawurlencode($key.' DESC');
								}
								$basehtml .= '<th class="crudkey"><a class="crudlink" id="crud_order_'.$this->restResourceModel.'_'.$key.'" href="'.$URIs[0].'?LIMIT='.$requestParams['LIMIT'].'&OFFSET=0&total='.$requestParams['total'].'&ORDER='.$order.'&LIKE='.rawurlencode($requestParams['LIKE']).'">'.$key.'</a></th>';
							}
							$basehtml .= '</tr>'.PHP_EOL;
							for($idx=0; $idx < count($res); $idx++){
								$basehtml .= '<tr>'.PHP_EOL;
								$id = '';
								foreach($res[$idx] as $key => $val){
									if('' === $id){
										$id = $val;
									}
									$basehtml .= '<td><a class="crudlink" id="crud_'.$this->restResourceModel.'_'.$id.'" target="_blank" href="'.str_replace('/'.$this->restResourceModel.'.html', '/'.$this->restResourceModel.'/'.$id.'.html', $URIs[0]).'">';
									if (isset($headRes) && isset($headRes['describes']) && isset($headRes['describes'][$key]['type']) && FALSE !== strpos($headRes['describes'][$key]['type'], 'blob')){
										// XXX 動画との判別を入れる！
										$basehtml .= '<img width="50"';
										if (0 < strlen($val)){
											if (base64_encode(base64_decode($val, true)) !== $val){
												// 非base64(多分バイナリ)
												$val = base64_encode($val);
											}
											$basehtml .= ' src="data:application/octet-stream;base64,'.$val.'"';
										}
										$basehtml .= '/>';
									}
									else {
										$basehtml .= nl2br($val);
									}
									$basehtml .= '</a></td>';
								}
								$basehtml .= '</tr>'.PHP_EOL;
							}
							$basehtml .= '</table>'.PHP_EOL;
							if(TRUE === $this->rootREST && isset($requestParams['LIMIT']) && is_numeric($requestParams['LIMIT']) && (int)$requestParams['LIMIT'] > 0 && isset($requestParams['OFFSET']) && is_numeric($requestParams['OFFSET']) && isset($requestParams['total']) && is_numeric($requestParams['total']) && (int)$requestParams['total'] > 0){
								// ページング
								$nowPage = (floor((int)$requestParams['OFFSET']/(int)$requestParams['LIMIT'])+1);
								if(1 < ceil((int)$requestParams['total']/(int)$requestParams['LIMIT'])){
									$basehtml .= '<table class="paging"><tr>'.PHP_EOL;
									if($nowPage > 1){
										// 先頭リンク
										$basehtml .= '<td class="list-paginglink"><a href="'.$URIs[0].'?LIMIT='.$requestParams['LIMIT'].'&OFFSET=0&total='.$requestParams['total'].'&ORDER='.rawurlencode($requestParams['ORDER']).'&LIKE='.rawurlencode($requestParams['LIKE']).'">&lt;&lt;</a></td>'.PHP_EOL;
										// 前へリンク
										$basehtml .= '<td class="list-paginglink"><a href="'.$URIs[0].'?LIMIT='.$requestParams['LIMIT'].'&OFFSET='.((int)$requestParams['OFFSET']-(int)$requestParams['LIMIT']).'&total='.$requestParams['total'].'&ORDER='.rawurlencode($requestParams['ORDER']).'&LIKE='.rawurlencode($requestParams['LIKE']).'">&lt;</a></td>'.PHP_EOL;
									}
									// 2つ前のページ
									if($nowPage-2 > 0){
										$basehtml .= '<td class="list-paginglink"><a href="'.$URIs[0].'?LIMIT='.$requestParams['LIMIT'].'&OFFSET='.((int)$requestParams['OFFSET']-(int)$requestParams['LIMIT']*2).'&total='.$requestParams['total'].'&ORDER='.rawurlencode($requestParams['ORDER']).'&LIKE='.rawurlencode($requestParams['LIKE']).'">'.($nowPage-2).'</a></td>'.PHP_EOL;
									}
									// 1つ前のページ
									if($nowPage-1 > 0){
										$basehtml .= '<td class="list-paginglink"><a href="'.$URIs[0].'?LIMIT='.$requestParams['LIMIT'].'&OFFSET='.((int)$requestParams['OFFSET']-(int)$requestParams['LIMIT']).'&total='.$requestParams['total'].'&ORDER='.rawurlencode($requestParams['ORDER']).'&LIKE='.rawurlencode($requestParams['LIKE']).'">'.($nowPage-1).'</a></td>'.PHP_EOL;
									}
									// 現在ページ
									$basehtml .= '<td class="list-paginglink"><a href="'.$URIs[0].'?LIMIT='.$requestParams['LIMIT'].'&OFFSET='.$requestParams['OFFSET'].'&total='.$requestParams['total'].'&ORDER='.rawurlencode($requestParams['ORDER']).'&LIKE='.rawurlencode($requestParams['LIKE']).'">'.$nowPage.'</a></td>'.PHP_EOL;
									// 1つ次のページ
									if($nowPage < ceil((int)$requestParams['total']/(int)$requestParams['LIMIT'])){
										$basehtml .= '<td class="list-paginglink"><a href="'.$URIs[0].'?LIMIT='.$requestParams['LIMIT'].'&OFFSET='.((int)$requestParams['OFFSET']+(int)$requestParams['LIMIT']).'&total='.$requestParams['total'].'&ORDER='.rawurlencode($requestParams['ORDER']).'&LIKE='.rawurlencode($requestParams['LIKE']).'">'.($nowPage+1).'</a></td>'.PHP_EOL;
									}
									// 2つ次のページ
									if($nowPage+1 < ceil((int)$requestParams['total']/(int)$requestParams['LIMIT'])){
										$basehtml .= '<td class="list-paginglink"><a href="'.$URIs[0].'?LIMIT='.$requestParams['LIMIT'].'&OFFSET='.((int)$requestParams['OFFSET']+(int)$requestParams['LIMIT']*2).'&total='.$requestParams['total'].'&ORDER='.rawurlencode($requestParams['ORDER']).'&LIKE='.rawurlencode($requestParams['LIKE']).'">'.($nowPage+2).'</a></td>'.PHP_EOL;
									}
									if($nowPage < (ceil((int)$requestParams['total']/(int)$requestParams['LIMIT']))){
										// 次へリンク
										$basehtml .= '<td class="list-paginglink"><a href="'.$URIs[0].'?LIMIT='.$requestParams['LIMIT'].'&OFFSET='.((int)$requestParams['OFFSET']+(int)$requestParams['LIMIT']).'&total='.$requestParams['total'].'&ORDER='.rawurlencode($requestParams['ORDER']).'&LIKE='.rawurlencode($requestParams['LIKE']).'">&gt;</a></td>'.PHP_EOL;
										// 終端リンク
										$basehtml .= '<td class="list-paginglink"><a href="'.$URIs[0].'?LIMIT='.$requestParams['LIMIT'].'&OFFSET='.((ceil((int)$requestParams['total']/(int)$requestParams['LIMIT'])-1)*(int)$requestParams['LIMIT']).'&total='.$requestParams['total'].'&ORDER='.rawurlencode($requestParams['ORDER']).'&LIKE='.rawurlencode($requestParams['LIKE']).'">&gt;&gt;</a></td>'.PHP_EOL;
									}
									$basehtml .= '</tr></table>'.PHP_EOL;
								}
							}
							$basehtml .= '<div class="csv-download-link"><a href="'.str_replace('.html', '.csv', $_SERVER['REQUEST_URI']).'">download csv</a></div>'.PHP_EOL;
							$basehtml .= '<div class="csv-all-download-link"><a href="'.str_replace('.html', '.csv', $URIs[0]).'?ORDER='.rawurlencode($requestParams['ORDER']).'">download csv all records</a></div>'.PHP_EOL;
							$basehtml .= '<div class="tsv-download-link"><a href="'.str_replace('.html', '.tsv', $_SERVER['REQUEST_URI']).'">download tsv</a></div>'.PHP_EOL;
							$basehtml .= '<div class="tsv-all-download-link"><a href="'.str_replace('.html', '.tsv', $URIs[0]).'?ORDER='.rawurlencode($requestParams['ORDER']).'">download tsv all records</a></div>'.PHP_EOL;
							$basehtml .= '<div class="tablelist-link"><a href="'.str_replace('/'.$this->restResourceModel.'.html', '/index.html', $URIs[0]).'">table list</a></div>'.PHP_EOL;
						}
					}
					else {
						if(isset($res[0])){
							$basehtml .= '<form id="crud-form-put" class="crud-form" method="POST" enctype="multipart/form-data">'.PHP_EOL;
							$basehtml .= '<table class="detail">'.PHP_EOL;
							$id = '';
							foreach($res[0] as $key => $val){
								if('' === $id){
									$id = $val;
								}
								$basehtml .= '<tr><th class="crudkey">'.$key.'</th></tr>'.PHP_EOL;
								if (isset($headRes) && isset($headRes['describes']) && isset($headRes['describes'][$key]['type']) && FALSE !== strpos($headRes['describes'][$key]['type'], 'text')){
									$basehtml .= '<tr><td><textarea class="form-input" name="'.$key.'">'.htmlspecialchars($val).'</textarea></td></tr>'.PHP_EOL;
								}
								elseif (isset($headRes) && isset($headRes['describes']) && isset($headRes['describes'][$key]['type']) && FALSE !== strpos($headRes['describes'][$key]['type'], 'blob')){
									// XXX 動画との判別を入れる！
									$basehtml .= '<tr><td>';
									$basehtml .= '<img width="200"';
									if (0 < strlen($val)){
										if (base64_encode(base64_decode($data, true)) !== $val){
											// 非base64(多分バイナリ)
											$val = base64_encode($val);
										}
										$basehtml .= ' src="data:octet-stream;base64,'.$val.'"';
									}
									$basehtml .= '/>';
									$basehtml .= '<br/><input class="form-input" type="file" name="'.$key.'"/></td></tr>'.PHP_EOL;
								}
								else {
									$basehtml .= '<tr><td><input class="form-input" type="text" name="'.$key.'" value="'.htmlspecialchars($val).'"/></td></tr>'.PHP_EOL;
								}
							}
							$basehtml .= '<tr><td><div class="submit-button put-button"><input type="submit" value="PUT"/></div></td></tr>'.PHP_EOL;
							$basehtml .= '<input class="form-input" type="hidden" name="_method_" value="PUT"/>'.PHP_EOL;
							$basehtml .= '</table>'.PHP_EOL;
							$basehtml .= '</form>'.PHP_EOL;
							$basehtml .= '<form id="crud-form-delete" class="crud-form" method="POST">'.PHP_EOL;
							$basehtml .= '<div class="submit-button delete-button"><input type="submit" value="DELETE"/></div>'.PHP_EOL;
							$basehtml .= '<input class="form-input" type="hidden" name="_method_" value="DELETE"/>'.PHP_EOL;
							$basehtml .= '</form>'.PHP_EOL;
							$basehtml .= '<div class="list-link"><a href="'.str_replace('/'.$this->restResourceModel.'/'.$id.'.html', '/'.$this->restResourceModel.'.html', $URIs[0]).'">'.$this->restResourceModel.' list</a></div>'.PHP_EOL;
						}
						else {
							$basehtml .= '<div class="list-link"><a href="javascript:history.back()">back</a></div>'.PHP_EOL;
						}
					}
				}
				$res = $basehtml;
			}
		}
		debug($res);
		// MVCCoreのアクセス時間をRESTが持っている現在時刻で差し替える
		Core::$accessed = self::$nowGMT;

		// 正常終了(のハズ！)
		return $res;
	}

	/**
	 * RESTAPIでリソースにアクセスする際の指定リソース情報を分解してパラメータ化します
	 * [me指定(任意)/][model指定(必須)/][pkey指定(,pkey指定,...)(GET以外は必須)/]
	 * 例)
	 * 自分の"model"の一覧を取得
	 * /me/model.json
	 * XXX システム毎にRESTのURIパラメータ分解法が違う場合はこのメソッドをオーバーライドして下さい
	 * @param string $argRESTResourceHint
	 * @return array model,listed,fields,idsの配列
	 */
	public static function resolveRESTResource($argRESTResourceHint){
		$resource = NULL;
		if(strlen($argRESTResourceHint) > 0){
			// /区切りのリソースヒントを分解する
			$hints = explode('/', $argRESTResourceHint);
			$hintIdx=0;
			// 少なくとも一つ以上のリソース指定条件を必要とする！
			if(count($hints) >= 1){
				$me = FALSE;
				// me指定があるかどうか
				if('me' === $hints[$hintIdx]){
					$me = TRUE;
					$hintIdx++;
					$model = $hints[$hintIdx];
				}
				else{
					$model = $hints[$hintIdx];
				}
				$hintIdx++;
				$ids = NULL;
				$listed = TRUE;
				// pkey指定があるかどうか
				if(isset($hints[$hintIdx]) && strlen($hints[$hintIdx]) > 0){
					$ids = array($hints[$hintIdx]);
					// XXX strposでいいのか？？
					if(FALSE !== strpos($hints[$hintIdx], ',')){
						$ids = explode(',', $hints[$hintIdx]);
					}
					if(count($ids) <= 1){
						// リース１件に対してのアクセスなのでlistでは無い
						$listed = FALSE;
					}
				}
				$resource = array('me' => $me, 'model' => $model, 'listed' => $listed, 'ids' => $ids);
			}
		}
		return $resource;
	}

	/**
	 * GETメソッド リソースの参照(冪等性を持ちます)
	 * XXX モデルの位置付けが、テーブルリソースで無い場合は、継承して、RESTの”冪等性”に従って実装して下さい
	 * @return mixed 成功時は最新のリソース配列 失敗時はFALSE
	 */
	public function get($argRequestParams=NULL){
		$this->_init();
		$resources = array();
		$requestParams = array();
		$baseQuery = ' 1=1 ';
		$baseBinds = NULL;
		$isDeepModel = FALSE;
		$deepModels = array();
		if("index" === strtolower($this->restResourceModel)){
			// IndexはCRUD出来るテーブル一覧を返す
			$DBO = self::_getDBO();
			// XXX MySQL専用になっている事に注意！
			$response = $DBO->getTables();
			if(FALSE === $response){
				throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
			}
			else{
				$resources = $response;
			}
			return $resources;
		}
		try{
			if(TRUE === $this->restResource['me'] && NULL !== $this->AuthUser && is_object($this->AuthUser) && strtolower($this->restResourceModel) == strtolower($this->AuthUser->tableName)){
				// 自分自身のAuthモデルに対しての処理とする
				$Model = $this->AuthUser;
				$fields = $Model->getFieldKeys();
				// REQUESTされているパラメータは条件分に利用する
				for($fieldIdx = 0; $fieldIdx < count($fields); $fieldIdx++){
					// DEEP-REST用のテーブルとフィールドの一覧をストックしておく
					if(TRUE === $this->deepRESTMode && TRUE === (TRUE === $this->restResource['me'] || $this->authUserIDFieldName != $fields[$fieldIdx])){
						$deepBaseResource = NULL;
						if(0 < strlen($this->restResourceRelayPrefix) && 0 === strpos($fields[$fieldIdx], $this->restResourceRelayPrefix) && (strlen($this->restResourceRelayPrefix.$fields[$fieldIdx]) -3) === strpos($this->restResourceRelayPrefix.$fields[$fieldIdx], '_id')){
							$deepBaseResource = substr(substr($fields[$fieldIdx], 0, -3), strlen($this->restResourceRelayPrefix));
						}
						else if((strlen($fields[$fieldIdx]) - (3 + strlen($this->restResourceRelaySuffix))) === strpos($fields[$fieldIdx], '_id'.$this->restResourceRelaySuffix)){
							$deepBaseResource = substr($fields[$fieldIdx], 0, - (3 + strlen($this->restResourceRelaySuffix)));
						}
						else if((strlen($fields[$fieldIdx]) -3) === strpos($fields[$fieldIdx], '_id')){
							$deepBaseResource = substr($fields[$fieldIdx], 0, -3);
						}
						if(NULL !== $this->restResourceUserTableName && $this->authUserIDFieldName == $fields[$fieldIdx]){
							$deepBaseResource = $this->restResourceUserTableName;
						}
						if(NULL !== $deepBaseResource && 0 < strlen($deepBaseResource)){
							debug(__LINE__.'deep??'.$deepBaseResource.'&'.$this->authUserIDFieldName.'&'.$fields[$fieldIdx]);
							$isDeepModel = TRUE;
							$deepModels[$fields[$fieldIdx]] = $deepBaseResource;
						}
					}
				}
				$resources[] = $this->_convertArrayFromModel($Model);
				// DEEP-REST IDに紐づく関連テーブルのレコード参照
				if(TRUE === $isDeepModel){
					foreach($deepModels as $key => $val){
						$id = $resources[count($resources)-1][$key];
						if(0 < (int)$id && 0 < strlen($id)){
							// DEEPは有効なIDの値の時だけ
							$deepResource = $val.'/'.$id;
							$field = str_replace('_id', '', $key);
							if($this->authUserIDFieldName == $key){
								$field = $val;
							}
							if(TRUE === $this->restResource['me']  && strtolower($val) != strtolower($this->AuthUser->tableName)){
								// meリソース参照を継承
								$deepResource = 'me/'.$deepResource;
							}
							debug(__LINE__.'deep???'.$deepResource.'&'.$key.'&field='.$field);
							// deepRESTを実行し、IDの取得をする
							$DeepREST = new REST();
							$DeepREST->AuthUser = $this->AuthUser;
							$DeepREST->AuthDevice = $this->AuthDevice;
							$DeepREST->authUserID = $this->authUserID;
							$DeepREST->authUserIDFieldName = $this->authUserIDFieldName;
							$DeepREST->authUserQuery = $this->authUserQuery;
							// リスト参照はDEEP-RESTに継承する
							$DeepREST->restResourceListed = $this->restResourceListed;
							$DeepREST->rootREST = FALSE;
							$resources[count($resources)-1][$field] = $DeepREST->execute($deepResource);
						}
					}
				}
				// 日付の自動変換
				if (isset($resources[count($resources)-1][$this->restResourceAccessedDateKeyName]) && isset($_SERVER['HTTP_ACCEPT_TIMEZONE'])){
					$resources[count($resources)-1][$this->restResourceAccessedDateKeyName] = Utilities::date("Y/m/d H:i", $resources[count($resources)-1][$this->restResourceAccessedDateKeyName], 'GMT', $_SERVER['HTTP_ACCEPT_TIMEZONE']);
				}
				if (isset($resources[count($resources)-1][$this->restResourceCreateDateKeyName]) && isset($_SERVER['HTTP_ACCEPT_TIMEZONE'])){
					$resources[count($resources)-1][$this->restResourceCreateDateKeyName] = Utilities::date("Y/m/d H:i", $resources[count($resources)-1][$this->restResourceCreateDateKeyName], 'GMT', $_SERVER['HTTP_ACCEPT_TIMEZONE']);
				}
				if (isset($resources[count($resources)-1][$this->restResourceModifyDateKeyName]) && isset($_SERVER['HTTP_ACCEPT_TIMEZONE'])){
					$resources[count($resources)-1][$this->restResourceModifyDateKeyName] = Utilities::date("Y/m/d H:i", $resources[count($resources)-1][$this->restResourceModifyDateKeyName], 'GMT', $_SERVER['HTTP_ACCEPT_TIMEZONE']);
				}
				if (TRUE !== (isset($_SERVER['__SUPER_USER__']) && TRUE === $_SERVER['__SUPER_USER__'])){
					Auth::init();
					// Auth設定されているフィールドへの参照の場合、暗号化・ハッシュ化・マスク化を自動処理してあげる
					if (strtolower($this->restResourceModel) === strtolower(Auth::$authTable)){
						// IDフィールド用
						if (isset($resources[count($resources)-1][Auth::$authIDField]) && 0 < strlen($resources[count($resources)-1][Auth::$authIDField])){
							$resources[count($resources)-1][Auth::$authIDField] = Auth::resolveDecrypted($resources[count($resources)-1][Auth::$authIDField], Auth::$authIDEncrypted);
							if (FALSE !== strpos(Auth::$authIDField, 'mail')){
								// mail とか mailaddr とか mailaddress とかっぽいフィールだったら強制的にマスクして一部を読めなく！
								$resources[count($resources)-1][Auth::$authIDField] = substr($resources[count($resources)-1][Auth::$authIDField], 0, 2) . '********@********' . substr($resources[count($resources)-1][Auth::$authIDField], -2);
							}
						}
						// パスフィールド用
						if (isset($resources[count($resources)-1][Auth::$authPassField])){
							$resources[count($resources)-1][Auth::$authPassField] = Auth::resolveDecrypted($resources[count($resources)-1][Auth::$authPassField], Auth::$authPassEncrypted);
							if (FALSE !== strpos(Auth::$authPassField, 'pass')){
								// pass とか passphrasee とか password とかっぽいフィールだったら強制的に見えない用に！
								unset($resources[count($resources)-1][Auth::$authPassField]);
							}
						}
					}
				}
				else {
					Auth::init();
					// Auth設定されているフィールドへの参照の場合、暗号化・ハッシュ化・マスク化を自動処理してあげる
					if (strtolower($this->restResourceModel) === strtolower(Auth::$authTable)){
						// IDフィールド用
						if (isset($resources[count($resources)-1][Auth::$authIDField]) && 0 < strlen($resources[count($resources)-1][Auth::$authIDField])){
							$resources[count($resources)-1][Auth::$authIDField] = Auth::resolveDecrypted($resources[count($resources)-1][Auth::$authIDField], Auth::$authIDEncrypted);
						}
					}
				}
			}
			else {
				debug($argRequestParams);
				$requestParams = array();
				if(NULL === $argRequestParams){
					$requestParams = $this->getRequestParams();
				}
				else {
					$requestParams = $argRequestParams;
				}
				$Model = $this->_getModel($this->restResourceModel);
				$fields = $Model->getFieldKeys();
				debug($fields);
				if(TRUE === $this->restResource['me'] && FALSE !== in_array($this->authUserIDFieldName, $fields)){
					// 認証ユーザーのリソース指定
					// bind使うので自力で組み立てる
					$baseQuery = ' `' . $Model->tableName . '`.`' . $this->authUserIDFieldName . '` = :' . $this->authUserIDFieldName . ' ';
					$baseBinds = array($this->authUserIDFieldName => $this->authUserID);
				}
				debug(array_keys($requestParams));
				// REQUESTされているパラメータは条件文に利用する
				for($fieldIdx = 0; $fieldIdx < count($fields); $fieldIdx++){
					if(isset($requestParams[$fields[$fieldIdx]])){
						// GETパラメータでbaseクエリを書き換える
						if(is_array($requestParams[$fields[$fieldIdx]]) && isset($requestParams[$fields[$fieldIdx]]['mark']) && isset($requestParams[$fields[$fieldIdx]]['value'])){
							// =以外の条件を指定したい場合の特殊処理
							$baseQuery .= ' AND `' . $Model->tableName . '`.`' . $fields[$fieldIdx] . '` ' . $requestParams[$fields[$fieldIdx]]['mark'] . ' :' . $fields[$fieldIdx] . ' ';
							$bindValue = $requestParams[$fields[$fieldIdx]]['value'];
						}
						else{
							$baseQuery .= ' AND `' . $Model->tableName . '`.`' . $fields[$fieldIdx] . '` = :' . $fields[$fieldIdx] . ' ';
							$bindValue = $requestParams[$fields[$fieldIdx]];
						}
						Auth::init();
						// Auth設定されているフィールドへの保存の場合、暗号化・ハッシュ化を自動処理してあげる
						if (strtolower($this->restResourceModel) === strtolower(Auth::$authTable) && 0 < strlen($requestParams[$fields[$fieldIdx]])){
							// IDフィールド用
							if ($fields[$fieldIdx] === Auth::$authIDField){
								$requestParams[$fields[$fieldIdx]] = Auth::resolveEncrypted($requestParams[$fields[$fieldIdx]], Auth::$authIDEncrypted);
							}
						}
						if(NULL === $baseBinds){
							$baseBinds = array();
						}
						$baseBinds[$fields[$fieldIdx]] = $bindValue;
					}
					else if(isset($requestParams['LIKE']) && strlen($requestParams['LIKE']) > 0 && isset($Model->describes[$fields[$fieldIdx]]) && isset($Model->describes[$fields[$fieldIdx]]['type']) && TRUE === ('string' === $Model->describes[$fields[$fieldIdx]]['type'] || FALSE !== strpos($Model->describes[$fields[$fieldIdx]]['type'], 'text'))){
						$baseQuery .= ' OR `' . $Model->tableName . '`.`' . $fields[$fieldIdx] . '` LIKE \'%'.addslashes($requestParams['LIKE']).'%\' ';
					}
					// 有効フラグの自動参照制御
					if($this->restResourceAvailableKeyName == $fields[$fieldIdx]){
						$baseQuery .= ' AND `' . $Model->tableName . '`.`' . $this->restResourceAvailableKeyName . '` = :' . $fields[$fieldIdx] . ' ';
						$baseBinds[$fields[$fieldIdx]] = '1';
					}
					// DEEP-REST用のテーブルとフィールドの一覧をストックしておく
					if(TRUE === $this->deepRESTMode && TRUE === (TRUE === $this->restResource['me'] || $this->authUserIDFieldName != $fields[$fieldIdx])){
						$deepBaseResource = NULL;
						if(0 < strlen($this->restResourceRelayPrefix) && 0 === strpos($fields[$fieldIdx], $this->restResourceRelayPrefix) && (strlen($this->restResourceRelayPrefix.$fields[$fieldIdx]) -3) === strpos($this->restResourceRelayPrefix.$fields[$fieldIdx], '_id')){
							$deepBaseResource = substr(substr($fields[$fieldIdx], 0, -3), strlen($this->restResourceRelayPrefix));
						}
						else if((strlen($fields[$fieldIdx]) - (3 + strlen($this->restResourceRelaySuffix))) === strpos($fields[$fieldIdx], '_id'.$this->restResourceRelaySuffix)){
							$deepBaseResource = substr($fields[$fieldIdx], 0, - (3 + strlen($this->restResourceRelaySuffix)));
						}
						else if((strlen($fields[$fieldIdx]) -3) === strpos($fields[$fieldIdx], '_id')){
							$deepBaseResource = substr($fields[$fieldIdx], 0, -3);
						}
						if(NULL !== $this->restResourceUserTableName && $this->authUserIDFieldName == $fields[$fieldIdx]){
							$deepBaseResource = $this->restResourceUserTableName;
						}
						if(NULL !== $deepBaseResource && 0 < strlen($deepBaseResource)){
							debug(__LINE__.'deep??'.$deepBaseResource.'&'.$this->authUserIDFieldName.'&'.$fields[$fieldIdx]);
							$isDeepModel = TRUE;
							$deepModels[$fields[$fieldIdx]] = $deepBaseResource;
						}
					}
				}
				if(TRUE === $this->rootREST && isset($requestParams['QUERY']) && strlen($requestParams['QUERY']) > 0){
					// XXX SQLインジェクション対策を後でTXに相談！
					$baseQuery .= ' AND ' . $requestParams['QUERY'] . ' ';
				}
				if(FALSE !== strpos($baseQuery, '1=1  OR ')){
					$baseQuery = str_replace('1=1  OR ', 'WHERE ', $baseQuery);
				}
				// ID指定による参照(単一参照含む)
				if(NULL !== $this->restResource['ids'] && count($this->restResource['ids']) >= 1){
					// id指定でループする
					for($IDIdx = 0; $IDIdx < count($this->restResource['ids']); $IDIdx++){
						$query = $baseQuery . ' AND `' . $Model->tableName . '`.`' . $Model->pkeyName . '` = :' . $Model->pkeyName . ' ';
						$binds = $baseBinds;
						if(NULL === $binds){
							$binds = array();
						}
						$binds[$Model->pkeyName] = $this->restResource['ids'][$IDIdx];
						// GROUP句指定があれば付け足す
						if(TRUE === $this->rootREST && isset($requestParams['GROUP']) && 0 < strlen($requestParams['GROUP'])){
							$query .= ' GROUP BY ' . $requestParams['GROUP'] . ' ';
						}
						// ORDER句指定があれば付け足す
						if(isset($requestParams['ORDER']) && 0 < strlen($requestParams['ORDER'])){
							$query .= ' ORDER BY ' . $requestParams['ORDER'] . ' ';
						}
						elseif(in_array($this->restResourceModifyDateKeyName, $fields)) {
							$query .= ' ORDER BY `' . $Model->tableName . '`.`' . $this->restResourceModifyDateKeyName . '` DESC ';
						}
						elseif(in_array($this->restResourceCreateDateKeyName, $fields)) {
							$query .= ' ORDER BY `' . $Model->tableName . '`.`' . $this->restResourceCreateDateKeyName . '` DESC ';
						}
						else {
							$query .= ' ORDER BY `' . $Model->tableName . '`.`' . $Model->pkeyName . '` DESC ';
						}
						// LIMIT句指定があれば付け足す
						if(TRUE === $this->rootREST){
							if(isset($requestParams['LIMIT']) && is_numeric($requestParams['LIMIT']) && 0 < (int)$requestParams['LIMIT']){
								$query .= ' LIMIT';
								// OFFSET句指定があれば付け足す
								if(isset($requestParams['OFFSET']) && is_numeric($requestParams['OFFSET']) && 0 < (int)$requestParams['OFFSET']){
									$query .= ' ' . $requestParams['OFFSET'] . ',';
								}
								else {
									$query .= ' 0,';
								}
								$query .= ' ' . $requestParams['LIMIT'] . ' ';
							}
							if (isset($requestParams['JOIN'])){
								$query = array('QUERY' => $query, 'JOIN' => $requestParams['JOIN']);
							}
						}
						debug('REST '.__LINE__.'load query='.var_export($query, TRUE));
						// 読み込み
						$Model->load($query, $binds);
						if($Model->count > 0){
							$resources[] = $this->_convertArrayFromModel($Model);
							// DEEP-REST IDに紐づく関連テーブルのレコード参照
							if(TRUE === $isDeepModel){
								foreach($deepModels as $key => $val){
									$id = $resources[count($resources)-1][$key];
									if(0 < (int)$id && 0 < strlen($id)){
										// DEEPは有効なIDの値の時だけ
										$deepResource = $val.'/'.$id;
										$field = str_replace('_id', '', $key);
										if($this->authUserIDFieldName == $key){
											$field = $val;
										}
										if(TRUE === $this->restResource['me']  && strtolower($val) != strtolower($this->AuthUser->tableName)){
											// meリソース参照を継承
											$deepResource = 'me/'.$deepResource;
										}
										debug(__LINE__.'deep???'.$deepResource.'&'.$key.'&field='.$field);
										// deepRESTを実行し、IDの取得をする
										$DeepREST = new REST();
										$DeepREST->AuthUser = $this->AuthUser;
										$DeepREST->AuthDevice = $this->AuthDevice;
										$DeepREST->authUserID = $this->authUserID;
										$DeepREST->authUserIDFieldName = $this->authUserIDFieldName;
										$DeepREST->authUserQuery = $this->authUserQuery;
										// リスト参照はDEEP-RESTに継承する
										$DeepREST->restResourceListed = $this->restResourceListed;
										$DeepREST->rootREST = FALSE;
										$resources[count($resources)-1][$field] = $DeepREST->execute($deepResource);
									}
								}
							}
							// 日付の自動変換
							if (isset($resources[count($resources)-1][$this->restResourceAccessedDateKeyName]) && isset($_SERVER['HTTP_ACCEPT_TIMEZONE'])){
								$resources[count($resources)-1][$this->restResourceAccessedDateKeyName] = Utilities::date("Y/m/d H:i", $resources[count($resources)-1][$this->restResourceAccessedDateKeyName], 'GMT', $_SERVER['HTTP_ACCEPT_TIMEZONE']);
							}
							if (isset($resources[count($resources)-1][$this->restResourceCreateDateKeyName]) && isset($_SERVER['HTTP_ACCEPT_TIMEZONE'])){
								$resources[count($resources)-1][$this->restResourceCreateDateKeyName] = Utilities::date("Y/m/d H:i", $resources[count($resources)-1][$this->restResourceCreateDateKeyName], 'GMT', $_SERVER['HTTP_ACCEPT_TIMEZONE']);
							}
							if (isset($resources[count($resources)-1][$this->restResourceModifyDateKeyName]) && isset($_SERVER['HTTP_ACCEPT_TIMEZONE'])){
								$resources[count($resources)-1][$this->restResourceModifyDateKeyName] = Utilities::date("Y/m/d H:i", $resources[count($resources)-1][$this->restResourceModifyDateKeyName], 'GMT', $_SERVER['HTTP_ACCEPT_TIMEZONE']);
							}
							if (TRUE !== (isset($_SERVER['__SUPER_USER__']) && TRUE === $_SERVER['__SUPER_USER__'])){
								Auth::init();
								// Auth設定されているフィールドへの保存の場合、暗号化・ハッシュ化を自動処理してあげる
								if (strtolower($this->restResourceModel) === strtolower(Auth::$authTable)){
									// IDフィールド用
									if (isset($resources[count($resources)-1][Auth::$authIDField]) && 0 < strlen($resources[count($resources)-1][Auth::$authIDField])){
										$resources[count($resources)-1][Auth::$authIDField] = Auth::resolveDecrypted($resources[count($resources)-1][Auth::$authIDField], Auth::$authIDEncrypted);
										if (FALSE !== strpos(Auth::$authIDField, 'mail')){
											// mail とか mailaddr とか mailaddress とかっぽいフィールだったら強制的にマスクして一部を読めなく！
											$resources[count($resources)-1][Auth::$authIDField] = substr($resources[count($resources)-1][Auth::$authIDField], 0, 2) . '********@********' . substr($resources[count($resources)-1][Auth::$authIDField], -2);
										}
									}
									// パスフィールド用
									if (isset($resources[count($resources)-1][Auth::$authPassField])){
										$resources[count($resources)-1][Auth::$authPassField] = Auth::resolveDecrypted($resources[count($resources)-1][Auth::$authPassField], Auth::$authPassEncrypted);
										if (FALSE !== strpos(Auth::$authPassField, 'pass')){
											// pass とか passphrasee とか password とかっぽいフィールだったら強制的に見えない用に！
											unset($resources[count($resources)-1][Auth::$authPassField]);
										}
									}
								}
							}
						}
						else if(TRUE === $this->rootREST && TRUE !== $this->restResourceListed){
							// ROOT-RESTで且つLIST指定で無い時は、素直にリソースが無かったエラー
							if(!isset($Model->{$Model->pkeyName}) || $Model->{$Model->pkeyName} != $this->restResource['ids'][$IDIdx]){
								// リソースが存在しない
								debug($this->restResourceModel);
								debug($query);
								debug($binds);
								debug($this->restResource);
								debug($Model->pkeyName . '=' . $Model->{$Model->pkeyName});
								throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
							}
						}
					}
				}
				// 配列の参照
				else if(TRUE === $this->restResourceListed){
					$query = $baseQuery;
					$binds = $baseBinds;
					// GROUP句指定があれば付け足す
					if(TRUE === $this->rootREST && isset($requestParams['GROUP']) && 0 < strlen($requestParams['GROUP'])){
						$query .= ' GROUP BY ' . $requestParams['GROUP'] . ' ';
					}
					// ORDER句指定があれば付け足す
					if(isset($requestParams['ORDER']) && 0 < strlen($requestParams['ORDER'])){
						$query .= ' ORDER BY ' . $requestParams['ORDER'] . ' ';
					}
					elseif(in_array($this->restResourceModifyDateKeyName, $fields)) {
						$query .= ' ORDER BY `' . $Model->tableName . '`.`' . $this->restResourceModifyDateKeyName . '` DESC ';
					}
					elseif(in_array($this->restResourceCreateDateKeyName, $fields)) {
						$query .= ' ORDER BY `' . $Model->tableName . '`.`' . $this->restResourceCreateDateKeyName . '` DESC ';
					}
					else {
						$query .= ' ORDER BY `' . $Model->tableName . '`.`' . $Model->pkeyName . '` DESC ';
					}
					// LIMIT句指定があれば付け足す
					if(TRUE === $this->rootREST){
						if(isset($requestParams['LIMIT']) && is_numeric($requestParams['LIMIT']) && 0 < (int)$requestParams['LIMIT']){
							$query .= ' LIMIT';
							// OFFSET句指定があれば付け足す
							if(isset($requestParams['OFFSET']) && is_numeric($requestParams['OFFSET']) && 0 < (int)$requestParams['OFFSET']){
								$query .= ' ' . $requestParams['OFFSET'] . ',';
							}
							else {
								$query .= ' 0,';
							}
							$query .= ' ' . $requestParams['LIMIT'] . ' ';
						}
						if (isset($requestParams['JOIN'])){
							$query = array('QUERY' => $query, 'JOIN' => $requestParams['JOIN']);
						}
					}

					// 読み込み
					debug('REST '.var_export($query, TRUE));
					debug('REST '.var_export($binds, TRUE));
					$Model->load($query, $binds);
					if($Model->count > 0){
						do {
							$resources[] = $this->_convertArrayFromModel($Model);
							// DEEP-REST IDに紐づく関連テーブルのレコード参照
							if(TRUE === $isDeepModel){
								foreach($deepModels as $key => $val){
									$id = $resources[count($resources)-1][$key];
									if(0 < (int)$id && 0 < strlen($id)){
										// DEEPは有効なIDの値の時だけ
										$deepResource = $val.'/'.$id;
										$field = str_replace('_id', '', $key);
										if($this->authUserIDFieldName == $key){
											$field = $val;
										}
										if(TRUE === $this->restResource['me']  && strtolower($val) != strtolower($this->AuthUser->tableName)){
											// meリソース参照を継承
											$deepResource = 'me/'.$deepResource;
										}
										debug(__LINE__.'deep???'.$deepResource.'&'.$key.'&field='.$field);
										// deepRESTを実行し、IDの取得をする
										$DeepREST = new REST();
										$DeepREST->AuthUser = $this->AuthUser;
										$DeepREST->AuthDevice = $this->AuthDevice;
										$DeepREST->authUserID = $this->authUserID;
										$DeepREST->authUserIDFieldName = $this->authUserIDFieldName;
										$DeepREST->authUserQuery = $this->authUserQuery;
										// リスト参照はDEEP-RESTに継承する
										$DeepREST->rootREST = FALSE;
										$DeepREST->restResourceListed = $this->restResourceListed;
										$resources[count($resources)-1][$field] = $DeepREST->execute($deepResource);
									}
								}
							}
							// 日付の自動変換
							if (isset($resources[count($resources)-1][$this->restResourceAccessedDateKeyName]) && isset($_SERVER['HTTP_ACCEPT_TIMEZONE'])){
								$resources[count($resources)-1][$this->restResourceAccessedDateKeyName] = Utilities::date("Y/m/d H:i", $resources[count($resources)-1][$this->restResourceAccessedDateKeyName], 'GMT', $_SERVER['HTTP_ACCEPT_TIMEZONE']);
							}
							if (isset($resources[count($resources)-1][$this->restResourceCreateDateKeyName]) && isset($_SERVER['HTTP_ACCEPT_TIMEZONE'])){
								$resources[count($resources)-1][$this->restResourceCreateDateKeyName] = Utilities::date("Y/m/d H:i", $resources[count($resources)-1][$this->restResourceCreateDateKeyName], 'GMT', $_SERVER['HTTP_ACCEPT_TIMEZONE']);
							}
							if (isset($resources[count($resources)-1][$this->restResourceModifyDateKeyName]) && isset($_SERVER['HTTP_ACCEPT_TIMEZONE'])){
								$resources[count($resources)-1][$this->restResourceModifyDateKeyName] = Utilities::date("Y/m/d H:i", $resources[count($resources)-1][$this->restResourceModifyDateKeyName], 'GMT', $_SERVER['HTTP_ACCEPT_TIMEZONE']);
							}
							if (TRUE !== (isset($_SERVER['__SUPER_USER__']) && TRUE === $_SERVER['__SUPER_USER__'])){
								Auth::init();
								// Auth設定されているフィールドへの保存の場合、暗号化・ハッシュ化を自動処理してあげる
								if (strtolower($this->restResourceModel) === strtolower(Auth::$authTable)){
									if (isset($resources[count($resources)-1][Auth::$authIDField]) && 0 < strlen($resources[count($resources)-1][Auth::$authIDField])){
										$resources[count($resources)-1][Auth::$authIDField] = Auth::resolveDecrypted($resources[count($resources)-1][Auth::$authIDField], Auth::$authIDEncrypted);
										if (FALSE !== strpos(Auth::$authIDField, 'mail')){
											// mail とか mailaddr とか mailaddress とかっぽいフィールだったら強制的にマスクして一部を読めなく！
											$resources[count($resources)-1][Auth::$authIDField] = substr($resources[count($resources)-1][Auth::$authIDField], 0, 2) . '********@********' . substr($resources[count($resources)-1][Auth::$authIDField], -2);
										}
									}
									// パスフィールド用
									if (isset($resources[count($resources)-1][Auth::$authPassField])){
										$resources[count($resources)-1][Auth::$authPassField] = Auth::resolveDecrypted($resources[count($resources)-1][Auth::$authPassField], Auth::$authPassEncrypted);
										if (FALSE !== strpos(Auth::$authPassField, 'pass')){
											// pass とか passphrasee とか password とかっぽいフィールだったら強制的に見えない用に！
											unset($resources[count($resources)-1][Auth::$authPassField]);
										}
									}
								}
							}
						} while (FALSE !== $Model->next());
					}
				}
			}
		}
		catch (Exception $Exception){
			// リソースが存在しない
			$this->httpStatus = 404;
			throw new RESTException($Exception->getMessage(), $this->httpStatus);
		}
		return $resources;
	}

	/**
	 * POSTメソッド リソースの新規作成、更新、インクリメント、デクリメント(冪等性を強制しません)
	 * XXX モデルの位置付けが、テーブルリソースで無い場合は、継承して、RESTの”冪等性”に従って実装して下さい
	 * @return mixed 成功時は最新のリソース配列 失敗時はFALSE
	 */
	public function post($argRequestParams = NULL){
		return $this->put($argRequestParams);
	}

	/**
	 * PUTメソッド リソースの新規作成、更新(冪等性を持ちます)
	 * XXX モデルの位置付けが、テーブルリソースで無い場合は、継承して、RESTの”冪等性”に従って実装して下さい
	 * @return mixed 成功時は最新のリソース配列 失敗時はFALSE
	 */
	public function put($argRequestParams = NULL){
		$this->_init();
		$requestParams = array();
		$resources = FALSE;
		if(NULL === $argRequestParams){
			$requestParams = $this->getRequestParams();
		}
		else {
			$requestParams = $argRequestParams;
		}
		debug($this->requestMethod . ' param=');
		debug(array_keys($requestParams));
		if(isset($requestParams['datas']) && isset($requestParams['datas'][0])){
			// 配列のPOSTはリカーシブルで処理をする
			for($requestIdx=0; $requestIdx < count($requestParams['datas']); $requestIdx++){
				$tmpRes = $this->put($requestParams['datas'][$requestIdx]);
				if(is_array($tmpRes) && isset($tmpRes[0])){
					$resources[$requestIdx] = $tmpRes[0];
				}
				else {
					return FALSE;
				}
			}
		}
		else{
			// 更新を行うリソースを特定する
			$baseQuery = ' 1=1 ';
			$baseBinds = NULL;
			if(TRUE === $this->restResource['me']){
				// 認証ユーザーのリソース指定
				// bind使うので自力で組み立てる
				$baseQuery = ' `' . $this->authUserIDFieldName . '` = :' . $this->authUserIDFieldName. ' ';
				$baseBinds = array($this->authUserIDFieldName => $this->authUserID);
			}
			// リソースの更新
			// XXX 因みに更新はDEEP指定されていてもDEEPしない！
			if(NULL !== $this->restResource['ids'] && count($this->restResource['ids']) >= 1){
				// id指定でループする
				for($IDIdx = 0; $IDIdx < count($this->restResource['ids']); $IDIdx++){
					// 空のモデルを先ず作る
					try{
						if(TRUE === $this->restResource['me'] && NULL !== $this->AuthUser && is_object($this->AuthUser) && strtolower($this->restResourceModel) == strtolower($this->AuthUser->tableName) && $this->restResource['ids'][$IDIdx] == $this->AuthUser->pkeyName){
							if (0 < $IDIdx){
								// Me Authは1件しかないのでbreakする
								break;
							}
							// 自分自身のAuthモデルに対しての処理とする
							$Model = $this->AuthUser;
							$fields = $Model->getFieldKeys();
							if(TRUE === $this->restResource['me'] && FALSE === in_array($this->authUserIDFieldName, $fields)){
								// フィールドが無いなら$baseQueryを再初期化
								$baseQuery = ' 1=1 ';
								$baseBinds = NULL;
							}
						}
						else {
							$Model = NULL;
							$Model = $this->_getModel($this->restResourceModel);
							$fields = $Model->getFieldKeys();
							if(TRUE === $this->restResource['me'] && FALSE === in_array($this->authUserIDFieldName, $fields)){
								// フィールドが無いなら$baseQueryを再初期化
								$baseQuery = ' 1=1 ';
								$baseBinds = NULL;
							}
							$query = $baseQuery . ' AND `' . $Model->pkeyName . '` = :' . $Model->pkeyName . ' ';
							$binds = $baseBinds;
							if(NULL === $binds){
								$binds = array();
							}
							$binds[$Model->pkeyName] = $this->restResource['ids'][$IDIdx];
							// 読み込み
							debug($query);
							debug($binds);
							$Model->load($query, $binds);
						}
					}
					catch (Exception $Exception){
						// リソースが存在しない
						$this->httpStatus = 404;
						throw new RESTException($Exception->getMessage(), $this->httpStatus);
						break;
					}
					// 最初の一回目はバリデーションを必ず実行
					//if(0 === $IDIdx){
						$datas = array();
						if(FALSE === in_array($this->authUserIDFieldName, $fields)){
							// フィールドが無いなら$baseQueryを再初期化
							$baseQuery = ' 1=1 ';
							$baseBinds = NULL;
						}
						// オートバリデート
						try{
							for($fieldIdx = 0; $fieldIdx < count($fields); $fieldIdx++){
								if(array_key_exists($fields[$fieldIdx], $requestParams)){
									try{
										// XXX intのincrementとdecrimentは許可する
										if(FALSE === ('int' === $Model->describes[$fields[$fieldIdx]]['type'] && TRUE === ('increment' === strtolower($requestParams[$fields[$fieldIdx]]) || 'decrement' === strtolower($requestParams[$fields[$fieldIdx]])))){
											// exec系以外はオートバリデート
											$Model->validate($fields[$fieldIdx], $requestParams[$fields[$fieldIdx]]);
											Auth::init();
											// Auth設定されているフィールドへの保存の場合、暗号化・ハッシュ化を自動処理してあげる
											if (strtolower($this->restResourceModel) === strtolower(Auth::$authTable) && 0 < strlen($requestParams[$fields[$fieldIdx]])){
												// IDフィールド用
												if ($fields[$fieldIdx] === Auth::$authIDField){
													$requestParams[$fields[$fieldIdx]] = Auth::resolveEncrypted($requestParams[$fields[$fieldIdx]], Auth::$authIDEncrypted);
												}
												// パスフィールド用
												else if ($fields[$fieldIdx] === Auth::$authPassField){
													// 空パスワードの上書きは許可しない！
													if (FALSE === (0 < strlen($requestParams[$fields[$fieldIdx]])) || '********' === $requestParams[$fields[$fieldIdx]]){
														$requestParams[$fields[$fieldIdx]] = $Model->{$fields[$fieldIdx]};
													}
													else if ($Model->{$fields[$fieldIdx]} !== $requestParams[$fields[$fieldIdx]]){
														// 値が変わっていた時だけ差し替える
														$requestParams[$fields[$fieldIdx]] = Auth::resolveEncrypted($requestParams[$fields[$fieldIdx]], Auth::$authPassEncrypted);
													}
												}
											}
										}
										// バリデートに成功したので更新値として認める
										$datas[$fields[$fieldIdx]] = $requestParams[$fields[$fieldIdx]];
									}
									catch (Exception $Exception){
										// バリデーションエラー(必須パラメータチェックエラー)
										$this->httpStatus = 400;
										throw new RESTException($Exception->getMessage(), $this->httpStatus);
										break;
									}
								}
								elseif($fields[$fieldIdx] == $this->restResourceCreateDateKeyName && TRUE !== (0 < strlen($Model->{$this->restResourceCreateDateKeyName}))){
									// データ作成日付の自動補完
									$datas[$fields[$fieldIdx]] = self::$nowGMT;
								}
								elseif($fields[$fieldIdx] == $this->restResourceAccessedDateKeyName){
									// データ作成日付の自動補完
									$datas[$fields[$fieldIdx]] = self::$nowGMT;
								}
								elseif($fields[$fieldIdx] == $this->restResourceModifyDateKeyName){
									// データ更新日付の自動補完
									$datas[$fields[$fieldIdx]] = self::$nowGMT;
								}
								elseif($fields[$fieldIdx] == $Model->pkeyName){
									// Pkeyも入れておく(複合キーの為の処理)
									$datas[$fields[$fieldIdx]] = $this->restResource['ids'][$IDIdx];
								}
								elseif($fields[$fieldIdx] == $this->authUserIDFieldName && 0 < (int)$this->authUserID && isset($datas[$fields[$fieldIdx]]) && 0 >= @(int)$datas[$fields[$fieldIdx]] && $this->authUserID !== $datas[$fields[$fieldIdx]]){
									// 自分自身のIDを入れる
									$datas[$fields[$fieldIdx]] = $this->authUserID;
								}
								elseif($fields[$fieldIdx] == $this->authUserIDFieldName && 0 < (int)$this->authUserID && !isset($datas[$fields[$fieldIdx]]) && 0 >= @(int)$Model->id){
									// 自分自身のIDを入れる
									$datas[$fields[$fieldIdx]] = $this->authUserID;
								}
								// blobの自動処理
								elseif(FALSE !== strpos($Model->describes[$fields[$fieldIdx]]['type'], 'blob')){
									// リアルなリクエストメソッドで処理を分岐
									if ('PUT' === $_SERVER['REQUEST_METHOD']){
										// PUTの場合
										if ($PUT[$fields[$fieldIdx]]){
											// ファイルが存在したので、更新データにセットする
											$datas[$fields[$fieldIdx]] = $PUT[$fields[$fieldIdx]];
											if (base64_encode(base64_decode($datas[$fields[$fieldIdx]], true)) !== $datas[$fields[$fieldIdx]]){
												// 非base64(多分バイナリ)
												$datas[$fields[$fieldIdx]] = base64_encode($datas[$fields[$fieldIdx]]);
											}
										}
									}
									elseif ('POST' === $_SERVER['REQUEST_METHOD']){
										// POSTの場合
										if(isset($_FILES) && isset($_FILES[$fields[$fieldIdx]]) && is_array($_FILES[$fields[$fieldIdx]]) && isset($_FILES[$fields[$fieldIdx]]['tmp_name']) && isset($_FILES[$fields[$fieldIdx]]['type']) && isset($_FILES[$fields[$fieldIdx]]['size']) && isset($_FILES[$fields[$fieldIdx]]['error']) && 0 === $_FILES[$fields[$fieldIdx]]['error']){
											// ファイルが存在したので、更新データにセットする
											$datas[$fields[$fieldIdx]] = file_get_contents($_FILES[$fields[$fieldIdx]]["tmp_name"]);
											if (base64_encode(base64_decode($datas[$fields[$fieldIdx]], true)) !== $datas[$fields[$fieldIdx]]){
												// 非base64(多分バイナリ)
												$datas[$fields[$fieldIdx]] = base64_encode($datas[$fields[$fieldIdx]]);
											}
										}
									}
								}
								// XXX S3オートアップロード処理はココに追加する予定
								// Filterがあったらフィルター処理をする
								$filerName = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->restResourceModel. ' '. $fields[$fieldIdx]))) . 'Filter';
								debug('$filerName='.$filerName);
								if(FALSE !== Core::loadMVCFilter($filerName, TRUE)){
									$filterClass = Core::loadMVCFilter($filerName);
									$filterMethod = 'filter'.ucfirst(strtolower($this->requestMethod));
									if (TRUE === method_exists($filterClass, $filterMethod)){
										$Filter = new $filterClass();
										$Filter->REST = $this;
										$Filter->Model = $Model;
										if(!isset($datas[$fields[$fieldIdx]]) ){
											// 初期化
											$datas[$fields[$fieldIdx]] = NULL;
											if(0 < strlen($Model->{$fields[$fieldIdx]})){
												$datas[$fields[$fieldIdx]] = $Model->{$fields[$fieldIdx]};
											}
										}
										debug('original value='.$datas[$fields[$fieldIdx]]);
										$datas[$fields[$fieldIdx]] = $Filter->$filterMethod($datas[$fields[$fieldIdx]]);
										debug('$filered value='.$datas[$fields[$fieldIdx]]);
									}
								}
							}
						}
						catch (Exception $Exception){
							throw new RESTException($Exception->getMessage(), $this->httpStatus);
							break;
						}
					//}
					// POSTに従ってModelを更新する
					$Model->save($datas);
					// 更新の完了した新しいモデルのデータをレスポンスにセット
					$resources[] = $this->_convertArrayFromModel($Model);
				}
			}
			// リソースの新規作成
			else{
				try{
					if(TRUE === $this->restResource['me'] && NULL !== $this->AuthUser && is_object($this->AuthUser) && strtolower($this->restResourceModel) == strtolower($this->AuthUser->tableName)){
						// 自分自身のAuthモデルに対しての処理とする
						$Model = $this->AuthUser;
					}
					else {
						$Model = $this->_getModel($this->restResourceModel);
					}
					$datas = array();
					$isDeepModel = FALSE;
					$deepDatas = array();
					$fields = $Model->getFieldKeys();
					if(TRUE === $this->restResource['me'] && FALSE === in_array($this->authUserIDFieldName, $fields)){
						// フィールドが無いなら$baseQueryを再初期化
						$baseQuery = ' 1=1 ';
						$baseBinds = NULL;
					}
				}
				catch (Exception $Exception){
					// リソースが存在しない
					$this->httpStatus = 404;
					throw new RESTException($Exception->getMessage(), $this->httpStatus);
				}
				// オートバリデート
				for($fieldIdx = 0; $fieldIdx < count($fields); $fieldIdx++){
					if(array_key_exists($fields[$fieldIdx], $requestParams)){
						try{
							// XXX intのincrementとdecrimentは許可する
							if(FALSE === ('int' === $Model->describes[$fields[$fieldIdx]]['type'] && TRUE === ('increment' === strtolower($requestParams[$fields[$fieldIdx]]) || 'decrement' === strtolower($requestParams[$fields[$fieldIdx]])))){
								// exec系以外はオートバリデート
								$Model->validate($fields[$fieldIdx], $requestParams[$fields[$fieldIdx]]);
								Auth::init();
								// Auth設定されているフィールドへの保存の場合、暗号化・ハッシュ化を自動処理してあげる
								if (strtolower($this->restResourceModel) === strtolower(Auth::$authTable) && 0 < strlen($requestParams[$fields[$fieldIdx]])){
									// IDフィールド用
									if ($fields[$fieldIdx] === Auth::$authIDField){
										$requestParams[$fields[$fieldIdx]] = Auth::resolveEncrypted($requestParams[$fields[$fieldIdx]], Auth::$authIDEncrypted);
										// ユニークバリデートの自動実行
										$query = $fields[$fieldIdx].' = :'.$fields[$fieldIdx].' ';
										$binds = array($fields[$fieldIdx] => $requestParams[$fields[$fieldIdx]]);
										// 有効フラグの自動参照制御
										if(0 < strlen($this->restResourceAvailableKeyName)){
											$query .= ' AND `' . $this->restResourceAvailableKeyName . '` = :' . $this->restResourceAvailableKeyName . ' ';
											$binds[$this->restResourceAvailableKeyName] = '1';
										}
										debug('authcheck:'.$query.' : '.var_export($binds, true));
										$Model->load($query, $binds);
										if (0 < $Model->count){
											// 重複エラー
											// バリデーションエラー(必須パラメータチェックエラー)
											$this->httpStatus = 400;
											throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
											break;
										}
									}
									// パスフィールド用
									else if ($fields[$fieldIdx] === Auth::$authPassField){
										$requestParams[$fields[$fieldIdx]] = Auth::resolveEncrypted($requestParams[$fields[$fieldIdx]], Auth::$authPassEncrypted);
									}
								}
							}
							// バリデートに成功したので更新値として認める
							$datas[$fields[$fieldIdx]] = $requestParams[$fields[$fieldIdx]];
						}
						catch (Exception $Exception){
							// バリデーションエラー(必須パラメータチェックエラー)
							$this->httpStatus = 400;
							throw new RESTException($Exception->getMessage(), $this->httpStatus);
							break;
						}
					}
					// DEEP-REST IDに紐づく関連テーブルのレコード作成・更新
					elseif(TRUE === $this->deepRESTMode && (strlen($fields[$fieldIdx]) -3) === strpos($fields[$fieldIdx], '_id') && $this->authUserIDFieldName != $fields[$fieldIdx]){
						$deepResource = substr($fields[$fieldIdx], 0, -3);
						$deepResourcePath = $deepResource;
						if(TRUE === $this->restResource['me']){
							$deepResourcePath = 'me/'.$deepResource;
						}
						debug(__LINE__.'deep??'.$deepResourcePath.' & '.$this->authUserIDFieldName.' & '.$fields[$fieldIdx].' = '.(strlen($fields[$fieldIdx]) -3).' & '.strpos($fields[$fieldIdx], '_id'));
						$isDeepModel = TRUE;
						try{
							$deepModel = $this->_getModel($deepResource);
						}
						catch(Exception $Exception){
							$isDeepModel = FALSE;
						}
						if(TRUE === $isDeepModel){
							// deepRESTを実行し、IDの取得をする
							$DeepREST = new REST();
							$DeepREST->AuthUser = $this->AuthUser;
							$DeepREST->AuthDevice = $this->AuthDevice;
							$DeepREST->authUserID = $this->authUserID;
							$DeepREST->authUserIDFieldName = $this->authUserIDFieldName;
							$DeepREST->authUserQuery = $this->authUserQuery;
							$DeepREST->rootREST = FALSE;
							$res = $DeepREST->execute($deepResourcePath, $requestParams);
							$datas[$fields[$fieldIdx]] = $res[0]['id'];
							$deepDatas[$deepResource] = $res;
						}
					}
					// DEEP-REST 自身のIDの自動補完
					elseif(TRUE === $this->deepRESTMode && $this->authUserIDFieldName == $fields[$fieldIdx]) {
						// ログインIDの自動補完
						$datas[$fields[$fieldIdx]] = $this->authUserID;
					}
					elseif($fields[$fieldIdx] == $this->restResourceCreateDateKeyName && TRUE !== (0 < strlen($Model->{$this->restResourceCreateDateKeyName}))){
						// データ作成日付の自動補完
						$datas[$fields[$fieldIdx]] = self::$nowGMT;
					}
					elseif($fields[$fieldIdx] == $this->restResourceModifyDateKeyName){
						// データ更新日付の自動補完
						$datas[$fields[$fieldIdx]] = self::$nowGMT;
					}
					elseif($fields[$fieldIdx] == $this->restResourceAccessedDateKeyName){
						// データ更新日付の自動補完
						$datas[$fields[$fieldIdx]] = self::$nowGMT;
					}
					// blobの自動処理
					elseif(FALSE !== strpos($Model->describes[$fields[$fieldIdx]]['type'], 'blob')){
						// リアルなリクエストメソッドで処理を分岐
						if ('PUT' === $_SERVER['REQUEST_METHOD']){
							// PUTの場合
							if ($PUT[$fields[$fieldIdx]]){
								// ファイルが存在したので、更新データにセットする
								$datas[$fields[$fieldIdx]] = $PUT[$fields[$fieldIdx]];
								if (base64_encode(base64_decode($datas[$fields[$fieldIdx]], true)) !== $datas[$fields[$fieldIdx]]){
									// 非base64(多分バイナリ)
									$datas[$fields[$fieldIdx]] = base64_encode($datas[$fields[$fieldIdx]]);
								}
							}
						}
						elseif ('POST' === $_SERVER['REQUEST_METHOD']){
							// POSTの場合
							if(isset($_FILES) && isset($_FILES[$fields[$fieldIdx]]) && is_array($_FILES[$fields[$fieldIdx]]) && isset($_FILES[$fields[$fieldIdx]]['tmp_name']) && isset($_FILES[$fields[$fieldIdx]]['type']) && isset($_FILES[$fields[$fieldIdx]]['size']) && isset($_FILES[$fields[$fieldIdx]]['error']) && 0 === $_FILES[$fields[$fieldIdx]]['error']){
								// ファイルが存在したので、更新データにセットする
								$datas[$fields[$fieldIdx]] = file_get_contents($_FILES[$fields[$fieldIdx]]["tmp_name"]);
								if (base64_encode(base64_decode($datas[$fields[$fieldIdx]], true)) !== $datas[$fields[$fieldIdx]]){
									// 非base64(多分バイナリ)
									$datas[$fields[$fieldIdx]] = base64_encode($datas[$fields[$fieldIdx]]);
								}
							}
						}
					}
					// XXX S3オートアップロード処理はココに追加する予定
					// Filterがあったらフィルター処理をする
					$filerName = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->restResourceModel. ' '. $fields[$fieldIdx]))) . 'Filter';
					debug('$filerName='.$filerName);
					try {
						if(FALSE !== Core::loadMVCFilter($filerName, TRUE)){
							$filterClass = Core::loadMVCFilter($filerName);
							debug($filterClass);
							$Filter = new $filterClass();
							$Filter->REST = $this;
							$Filter->Model = $Model;
							if(!isset($datas[$fields[$fieldIdx]]) ){
								// 初期化
								$datas[$fields[$fieldIdx]] = NULL;
								if(0 < strlen($Model->{$fields[$fieldIdx]})){
									$datas[$fields[$fieldIdx]] = $Model->{$fields[$fieldIdx]};
								}
							}
							debug('original value='.$datas[$fields[$fieldIdx]]);
							$filterMethod = 'filter'.ucfirst(strtolower($this->requestMethod));
							$datas[$fields[$fieldIdx]] = $Filter->$filterMethod($datas[$fields[$fieldIdx]]);
							debug('$filered value='.$datas[$fields[$fieldIdx]]);
						}
					}
					catch (Exception $Exception){
						throw new RESTException($Exception->getMessage(), $this->httpStatus);
						break;
					}
				}
				// POSTに従ってModelを作成する
				$Model->save($datas);
				// 更新の完了した新しいモデルのデータをレスポンスにセット
				$resources[] = $this->_convertArrayFromModel($Model);
				if(TRUE === $isDeepModel && 0 < count($deepDatas)){
					foreach($deepDatas as $key => $val){
						$resources[count($resources)-1][$key] = $val;
					}
				}
			}
		}
		if (isset($resources[count($resources)-1])){
			if (isset($resources[count($resources)-1][$this->restResourceAccessedDateKeyName]) && isset($_SERVER['HTTP_ACCEPT_TIMEZONE'])){
				$resources[count($resources)-1][$this->restResourceAccessedDateKeyName] = Utilities::date("Y/m/d H:i", $resources[count($resources)-1][$this->restResourceAccessedDateKeyName], 'GMT', $_SERVER['HTTP_ACCEPT_TIMEZONE']);
			}
			if (isset($resources[count($resources)-1][$this->restResourceCreateDateKeyName]) && isset($_SERVER['HTTP_ACCEPT_TIMEZONE'])){
				$resources[count($resources)-1][$this->restResourceCreateDateKeyName] = Utilities::date("Y/m/d H:i", $resources[count($resources)-1][$this->restResourceCreateDateKeyName], 'GMT', $_SERVER['HTTP_ACCEPT_TIMEZONE']);
			}
			if (isset($resources[count($resources)-1][$this->restResourceModifyDateKeyName]) && isset($_SERVER['HTTP_ACCEPT_TIMEZONE'])){
				$resources[count($resources)-1][$this->restResourceModifyDateKeyName] = Utilities::date("Y/m/d H:i", $resources[count($resources)-1][$this->restResourceModifyDateKeyName], 'GMT', $_SERVER['HTTP_ACCEPT_TIMEZONE']);
			}
		}
		return $resources;
	}

	/**
	 * DELETEメソッド
	 * XXX モデルの位置付けが、テーブルリソースで無い場合は、継承してRESTの”冪等性”に従って実装して下さい(冪等性を持ちます)
	 * @return boolean
	 */
	public function delete($argRequestParams=NULL){
		$this->_init();
		if(NULL === $argRequestParams){
			$requestParams = $this->getRequestParams();
		}
		else {
			$requestParams = $argRequestParams;
		}
		$baseQuery = ' 1=1 ';
		$baseBinds = NULL;
		$isDeepModel = FALSE;
		$deepModels = array();
		if($this->restResource['me'] && NULL !== $this->AuthUser && is_object($this->AuthUser)){
			// 認証ユーザーのリソース指定
			// bind使うので自力で組み立てる
			$baseQuery = ' `' . $this->authUserIDFieldName . '` = :' . $this->authUserIDFieldName. ' ';
			$baseBinds = array($this->authUserIDFieldName => $this->authUserID);
		}
		// モデルリソースと特定条件を決める
		try{
			// 空のモデルを先ず作る
			$Model = $this->_getModel($this->restResourceModel);
			$fields = $Model->getFieldKeys();
			// REQUESTされているパラメータは条件分に利用する
			for($fieldIdx = 0; $fieldIdx < count($fields); $fieldIdx++){
				if(isset($requestParams[$fields[$fieldIdx]])){
					// GETパラメータでbaseクエリを書き換える
					if(is_array($requestParams[$fields[$fieldIdx]]) && isset($requestParams[$fields[$fieldIdx]]['mark']) && isset($requestParams[$fields[$fieldIdx]]['value'])){
						// =以外の条件を指定したい場合の特殊処理
						$baseQuery .= ' AND `' . $fields[$fieldIdx] . '` ' . $requestParams[$fields[$fieldIdx]]['mark'] . ' :' . $fields[$fieldIdx] . ' ';
						$bindValue = $requestParams[$fields[$fieldIdx]]['value'];
					}
					else{
						$baseQuery .= ' AND `' . $fields[$fieldIdx] . '` = :' . $fields[$fieldIdx] . ' ';
						$bindValue = $requestParams[$fields[$fieldIdx]];
					}
					if(NULL === $baseBinds){
						$baseBinds = array();
					}
					$baseBinds[$fields[$fieldIdx]] = $bindValue;
				}
				// DEEP-REST用のテーブルとフィールドの一覧をストックしておく
				if(TRUE === $this->deepRESTMode && TRUE === (TRUE === $this->restResource['me'] || $this->authUserIDFieldName != $fields[$fieldIdx])){
					$deepBaseResource = NULL;
					if(0 < strlen($this->restResourceRelayPrefix) && 0 === strpos($fields[$fieldIdx], $this->restResourceRelayPrefix) && (strlen($this->restResourceRelayPrefix.$fields[$fieldIdx]) -3) === strpos($this->restResourceRelayPrefix.$fields[$fieldIdx], '_id')){
						$deepBaseResource = substr(substr($fields[$fieldIdx], 0, -3), strlen($this->restResourceRelayPrefix));
					}
					else if((strlen($fields[$fieldIdx]) - (3 + strlen($this->restResourceRelaySuffix))) === strpos($fields[$fieldIdx], '_id'.$this->restResourceRelaySuffix)){
						$deepBaseResource = substr($fields[$fieldIdx], 0, - (3 + strlen($this->restResourceRelaySuffix)));
					}
					else if((strlen($fields[$fieldIdx]) -3) === strpos($fields[$fieldIdx], '_id')){
						$deepBaseResource = substr($fields[$fieldIdx], 0, -3);
					}
					if(NULL !== $this->restResourceUserTableName && $this->authUserIDFieldName == $fields[$fieldIdx]){
						$deepBaseResource = $this->restResourceUserTableName;
					}
					if(NULL !== $deepBaseResource && 0 < strlen($deepBaseResource)){
						debug(__LINE__.'deep??'.$deepBaseResource.'&'.$this->authUserIDFieldName.'&'.$fields[$fieldIdx]);
						$isDeepModel = TRUE;
						$deepModels[$fields[$fieldIdx]] = $deepBaseResource;
					}
				}
				// GETパラメータでbaseクエリを書き換える
				if(isset($requestParams[$fields[$fieldIdx]]) && is_array($requestParams[$fields[$fieldIdx]]) && isset($requestParams[$fields[$fieldIdx]]['mark']) && isset($requestParams[$fields[$fieldIdx]]['value'])){
					// =以外の条件を指定したい場合の特殊処理
					$baseQuery .= ' AND `' . $fields[$fieldIdx] . '` ' . $requestParams[$fields[$fieldIdx]]['mark'] . ' :' . $fields[$fieldIdx] . ' ';
					$bindValue = $requestParams[$fields[$fieldIdx]]['value'];
				}
			}
			if(NULL === $baseBinds){
				$baseBinds = array();
			}
		}
		catch (Exception $Exception){
			// リソースが存在しない
			$this->httpStatus = 404;
			throw new RESTException($Exception->getMessage(), $this->httpStatus);
		}
		// リソースの削除
		if(NULL !== $this->restResource['ids'] && count($this->restResource['ids']) >= 1){
			$query = $baseQuery;
			$binds = $baseBinds;
			debug($query);
			// id指定でループする
			for($IDIdx = 0; $IDIdx < count($this->restResource['ids']); $IDIdx++){
				if(strlen($Model->pkeyName) > 1){
					if(FALSE === strpos($query, '`' . $Model->pkeyName . '` = :' . $Model->pkeyName . ' ')){
						$query = $query . ' AND `' . $Model->pkeyName . '` = :' . $Model->pkeyName . ' ';
					}
					$binds[$Model->pkeyName] = $this->restResource['ids'][$IDIdx];
				}
				// 読み込み
				$Model->load($query, $binds);
				if((int)$Model->count > 0){
					// リソースの削除を実行
					$Model->remove();
					debug('removed!');
				}
			}
		}
		else{
			// 条件一致した全てのリソースを削除する
			$query = $baseQuery;
			$binds = $baseBinds;
			// 読み込み
			$Model->load($query, $binds);
			if($Model->count > 0){
				// リソースの削除を実行
				do {
					$Model->remove();
				} while (false !== $Model->next());
			}
		}
		return TRUE;
	}

	/**
	 * HEADメソッド
	 * @return boolean
	 */
	public function head($argRequestParams=NULL){
		$this->_init();
		$count = '0';
		if(NULL === $argRequestParams){
			$requestParams = $this->getRequestParams();
		}
		else {
			$requestParams = $argRequestParams;
		}
		$baseQuery = ' 1=1 ';
		$baseBinds = NULL;

		$rules = array('rules'=>array());
		// XXX 後でマルチ言語対応
		$rules['default_messages'] = array(
				'required' => '*入力して下さい',
				'email' => '*正しいメールアドレスの形式で入力して下さい',
				'url' => '*正しいURLの形式で入力して下さい',
				'length' => '※{0}文字で入力して下さい',
				'minlength' => '※{0}文字以上で入力して下さい',
				'maxlength' => '※{0}文字以内で入力して下さい',
				'digits' => '*半角英数字で入力して下さい',
				'number' => '*半角数字で入力して下さい',
				'katakana' => '*全角カナ・A〜Zで入力してください',
				'hirakana' => '*全角ひらかなで入力してください',
				'phone' => '*正しい電話番号の形式で入力してください',
		);
		$rules['custom_methods'] = array();
		$rules['custom_methods']['hirakana'] = '/^([ぁ-んー〜\d]+)$/';
		$rules['custom_methods']['katakana'] = '/^([ァ-ヶー\d]+)$/';
		$rules['custom_methods']['phone'] = '/^\+*(\d{11}$|^\d{3}-\d{4}-\d{4})$/';
		$passwordPoricy = getConfig('PASSWORD_PORICY');
		if (0 < strlen($passwordPoricy)){
			$rules['custom_methods']['password'] = $passwordPoricy;
			$passwordPoricyText = getConfig('PASSWORD_PORICY_TEXT');
			if (0 < strlen($passwordPoricy)){
				$rules['default_messages']['password'] = '*'.$passwordPoricyText.'で入力してください';
			}
		}

		try{
			if(TRUE === $this->restResource['me'] && NULL !== $this->AuthUser && is_object($this->AuthUser) && strtolower($this->restResourceModel) == strtolower($this->AuthUser->tableName)){
				// 自分自身のAuthモデルに対しての処理とする
				$Model = $this->AuthUser;
				$fields = $Model->getFieldKeys();
				$count = '1';
			}
			else {
				$Model = $this->_getModel($this->restResourceModel);
				$fields = $Model->getFieldKeys();
				if(TRUE === $this->restResource['me']){
					// 認証ユーザーのリソース指定
					// bind使うので自力で組み立てる
					$baseQuery = ' `' . $Model->tableName . '`.`' . $this->authUserIDFieldName . '` = :' . $this->authUserIDFieldName . ' ';
					$baseBinds = array($this->authUserIDFieldName => $this->authUserID);
				}
				// REQUESTされているパラメータは条件文に利用する
				for($fieldIdx = 0; $fieldIdx < count($fields); $fieldIdx++){
					if(isset($requestParams[$fields[$fieldIdx]])){
						// GETパラメータでbaseクエリを書き換える
						if(is_array($requestParams[$fields[$fieldIdx]]) && isset($requestParams[$fields[$fieldIdx]]['mark']) && isset($requestParams[$fields[$fieldIdx]]['value'])){
							// =以外の条件を指定したい場合の特殊処理
							$baseQuery .= ' AND `' . $Model->tableName . '`.`' . $fields[$fieldIdx] . '` ' . $requestParams[$fields[$fieldIdx]]['mark'] . ' :' . $fields[$fieldIdx] . ' ';
							$bindValue = $requestParams[$fields[$fieldIdx]]['value'];
						}
						else{
							$baseQuery .= ' AND `' . $Model->tableName . '`.`' . $fields[$fieldIdx] . '` = :' . $fields[$fieldIdx] . ' ';
							$bindValue = $requestParams[$fields[$fieldIdx]];
						}
						if(NULL === $baseBinds){
							$baseBinds = array();
						}
						$baseBinds[$fields[$fieldIdx]] = $bindValue;
					}
					else if(isset($requestParams['LIKE']) && strlen($requestParams['LIKE']) > 0 && isset($Model->describes[$fields[$fieldIdx]]) && isset($Model->describes[$fields[$fieldIdx]]['type']) && TRUE === ('string' === $Model->describes[$fields[$fieldIdx]]['type'] || FALSE !== strpos($Model->describes[$fields[$fieldIdx]]['type'], 'text'))){
						$baseQuery .= ' OR `' . $Model->tableName . '`.`' . $fields[$fieldIdx] . '` LIKE \'%'.addslashes($requestParams['LIKE']).'%\' ';
					}
					// 有効フラグの自動参照制御
					if($this->restResourceAvailableKeyName == $fields[$fieldIdx]){
						$baseQuery .= ' AND `' . $Model->tableName . '`.`' . $this->restResourceAvailableKeyName . '` = :' . $fields[$fieldIdx] . ' ';
						$baseBinds[$fields[$fieldIdx]] = '1';
					}
				}
				if(TRUE === $this->rootREST && isset($requestParams['QUERY']) && strlen($requestParams['QUERY']) > 0){
					// XXX SQLインジェクション対策を後でTXに相談！
					$baseQuery .= ' AND ' . $requestParams['QUERY'] . ' ';
				}
				if(FALSE !== strpos($baseQuery, '1=1  OR ')){
					$baseQuery = str_replace('1=1  OR ', 'WHERE ', $baseQuery);
				}
				$query = $baseQuery;
				$binds = $baseBinds;
				// GROUP句指定があれば付け足す
				if(TRUE === $this->rootREST && isset($requestParams['GROUP']) && 0 < strlen($requestParams['GROUP'])){
					$query .= ' GROUP BY ' . $requestParams['GROUP'] . ' ';
				}
				// ORDER句指定があれば付け足す
				if(isset($requestParams['ORDER']) && 0 < strlen($requestParams['ORDER'])){
					$query .= ' ORDER BY ' . $requestParams['ORDER'] . ' ';
				}
				elseif(in_array($this->restResourceModifyDateKeyName, $fields)) {
					$query .= ' ORDER BY `' . $Model->tableName . '`.`' . $this->restResourceModifyDateKeyName . '` DESC ';
				}
				elseif(in_array($this->restResourceCreateDateKeyName, $fields)) {
					$query .= ' ORDER BY `' . $Model->tableName . '`.`' . $this->restResourceCreateDateKeyName . '` DESC ';
				}
				else {
					$query .= ' ORDER BY `' . $Model->tableName . '`.`' . $Model->pkeyName . '` DESC ';
				}
				if(TRUE === $this->rootREST && isset($requestParams['JOIN'])){
					$query = array('QUERY' => $query, 'JOIN' => $requestParams['JOIN']);
				}
				debug($query);
				$Model->load($query, $binds);
				$count = (string)$Model->count;
				if('' === $count){
					$count = '0';
				}
			}
			// JSバリデートで使えるようのRuleオブジェクトをおまけで作って上げる
			foreach($Model->describes as $key => $val){
				$rules['rules'][$key] = array('required'=>(((isset($val['pkey']) && $val['pkey']) || (isset($val['null']) && $val['null']))? FALSE : TRUE), 'email'=>((FALSE === strpos(strtolower($key),'mail')) ? FALSE : TRUE), 'url'=>((FALSE === strpos(strtolower($key),'url')) ? FALSE : TRUE));
				if (TRUE === $rules['rules'][$key]['required'] && isset($val['default'])){
					// デフォルト値がある場合は、JSValidate上は未入力を許可する
					$rules['rules'][$key]['required'] = FALSE;
				}
				$rules['rules'][$key]['digits'] = FALSE;
				if(isset($val['type']) && 'int' === $val['type']){
					$rules['rules'][$key]['digits'] = TRUE;
				}
				$minlength = false;
				if(isset($val['min-length'])){
					$rules['rules'][$key]['minlength']=(int)$val['min-length'];
					$minlength = true;
				}
				if(isset($val['length'])){
					if (true === $minlength){
						$rules['rules'][$key]['maxlength']=(int)$val['length'];
					}
					else {
						$rules['rules'][$key]['length'] = (int)$val['length'];
					}
				}
				$Model->describes[$key]['calender'] = FALSE;
				if($this->restResourceAccessedDateKeyName == $key || $this->restResourceCreateDateKeyName == $key || $this->restResourceModifyDateKeyName == $key || FALSE !== strpos(strtolower($key),'date')){
					// ヘッダーでは日付フィールドである事を明確にして置く
					$Model->describes[$key]['calender'] = TRUE;
				}
			}
		}
		catch (Exception $Exception){
			// リソースが存在しない
			$this->httpStatus = 404;
			throw new RESTException($Exception->getMessage(), $this->httpStatus);
		}

		// 定義一覧をヘッダに詰めて返す
		return array('describes' => $Model->describes, 'count'=>$count, 'rules'=>$rules, 'comment'=>$Model->tableComment);
	}

	/**
	 * Restコントローラであるかの確認メソッド
	 */
	public static function isRestController(){
		// Restコントローラである
		return TRUE;
	}
}

?>