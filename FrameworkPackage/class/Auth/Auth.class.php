<?php

class Auth
{
	protected static $_sessionCryptKey = NULL;
	protected static $_sessionCryptIV = NULL;
	protected static $_authCryptKey = NULL;
	protected static $_authCryptIV = NULL;
	protected static $_DBO = NULL;
	protected static $_initialized = FALSE;

	public static $authTable = 'user_table';
	public static $authPKeyField = 'id';
	public static $authIDField = 'mailaddress';
	public static $authPassField = 'password';
	public static $authCreatedField = 'create_date';
	public static $authModifiedField = 'modify_date';
	public static $authIDEncrypted = 'AES128CBC';
	public static $authPassEncrypted = 'SHA256';
	public static $authAutoRefreshKey = 'autoauthorize';

	protected static function _init($argDSN=NULL){
		if(FALSE === self::$_initialized){

			$DSN = NULL;

			if(class_exists('Configure') && NULL !== Configure::constant('AUTH_DB_DSN')){
				// 定義からセッションDBの接続情報を特定
				$DSN = Configure::AUTH_DB_DSN;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AUTH_TBL_NAME')){
				// 定義からuserTable名を特定
				self::$authTable = Configure::AUTH_TBL_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AUTH_PKEY_FIELD_NAME')){
				// 定義からuserTable名を特定
				self::$authPKeyField = Configure::AUTH_PKEY_FIELD_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AUTH_ID_FIELD_NAME')){
				// 定義からuserTable名を特定
				self::$authIDField = Configure::AUTH_ID_FIELD_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AUTH_PASS_FIELD_NAME')){
				// 定義からuserTable名を特定
				self::$authPassField = Configure::AUTH_PASS_FIELD_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AUTH_CREATE_DATE_KEY_NAME')){
				// 定義からuserTable名を特定
				self::$authCreatedField = Configure::AUTH_CREATE_DATE_KEY_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AUTH_MODIFY_DATE_KEY_NAME')){
				// 定義からuserTable名を特定
				self::$authModifiedField = Configure::AUTH_MODIFY_DATE_KEY_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AUTH_ID_ENCRYPTED')){
				// 定義からuserTable名を特定
				self::$authIDEncrypted = Configure::AUTH_ID_ENCRYPTED;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AUTH_PASS_ENCRYPTED')){
				// 定義からuserTable名を特定
				self::$authPassEncrypted = Configure::AUTH_PASS_ENCRYPTED;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('CRYPT_KEY')){
				// 定義から暗号化キーを設定
				self::$_sessionCryptKey = Configure::CRYPT_KEY;
				self::$_authCryptKey = Configure::CRYPT_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('NETWORK_CRYPT_KEY')){
				// 定義から暗号化キーを設定
				self::$_sessionCryptKey = Configure::NETWORK_CRYPT_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SESSION_CRYPT_KEY')){
				// 定義から暗号化キーを設定
				self::$_sessionCryptKey = Configure::SESSION_CRYPT_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('DB_CRYPT_KEY')){
				// 定義から暗号化キーを設定
				self::$_authCryptKey = Configure::DB_CRYPT_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AUTH_CRYPT_KEY')){
				// 定義から暗号化キーを設定
				self::$_authCryptKey = Configure::AUTH_CRYPT_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('CRYPT_IV')){
				// 定義から暗号化IVを設定
				self::$_sessionCryptIV = Configure::CRYPT_IV;
				self::$_authCryptIV = Configure::CRYPT_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('NETWORK_CRYPT_IV')){
				// 定義から暗号化IVを設定
				self::$_sessionCryptIV = Configure::NETWORK_CRYPT_IV;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SESSION_CRYPT_IV')){
				// 定義から暗号化IVを設定
				self::$_sessionCryptIV = Configure::SESSION_CRYPT_IV;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('DB_CRYPT_IV')){
				// 定義から暗号化キーを設定
				self::$_authCryptKIV = Configure::DB_CRYPT_IV;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AUTH_CRYPT_IV')){
				// 定義から暗号化キーを設定
				self::$_authCryptIV = Configure::AUTH_CRYPT_IV;
			}
			if(defined('PROJECT_NAME') && strlen(PROJECT_NAME) > 0 && class_exists(PROJECT_NAME . 'Configure')){
				$ProjectConfigure = PROJECT_NAME . 'Configure';
				if(NULL !== $ProjectConfigure::constant('AUTH_DB_DSN')){
					// 定義からセッションDBの接続情報を特定
					$DSN = $ProjectConfigure::AUTH_DB_DSN;
				}
				if(NULL !== $ProjectConfigure::constant('AUTH_TBL_NAME')){
					// 定義からuserTable名を特定
					self::$authTable = $ProjectConfigure::AUTH_TBL_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('AUTH_PKEY_FIELD_NAME')){
					// 定義からuserTable名を特定
					self::$authPKeyField = $ProjectConfigure::AUTH_PKEY_FIELD_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('AUTH_ID_FIELD_NAME')){
					// 定義からuserTable名を特定
					self::$authIDField = $ProjectConfigure::AUTH_ID_FIELD_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('AUTH_PASS_FIELD_NAME')){
					// 定義からuserTable名を特定
					self::$authPassField = $ProjectConfigure::AUTH_PASS_FIELD_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('AUTH_CREATE_DATE_KEY_NAME')){
					// 定義からuserTable名を特定
					self::$authCreatedField = $ProjectConfigure::AUTH_CREATE_DATE_KEY_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('AUTH_MODIFY_DATE_KEY_NAME')){
					// 定義からuserTable名を特定
					self::$authModifiedField = $ProjectConfigure::AUTH_MODIFY_DATE_KEY_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('AUTH_ID_ENCRYPTED')){
					// 定義からuserTable名を特定
					self::$authIDEncrypted = $ProjectConfigure::AUTH_ID_ENCRYPTED;
				}
				if(NULL !== $ProjectConfigure::constant('AUTH_PASS_ENCRYPTED')){
					// 定義からuserTable名を特定
					self::$authPassEncrypted = $ProjectConfigure::AUTH_PASS_ENCRYPTED;
				}
				if(NULL !== $ProjectConfigure::constant('CRYPT_KEY')){
					// 定義から暗号化キーを設定
					self::$_sessionCryptKey = $ProjectConfigure::CRYPT_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('NETWORK_CRYPT_KEY')){
					// 定義から暗号化キーを設定
					self::$_sessionCryptKey = $ProjectConfigure::NETWORK_CRYPT_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('SESSION_CRYPT_KEY')){
					// 定義から暗号化キーを設定
					self::$_sessionCryptKey = $ProjectConfigure::SESSION_CRYPT_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('DB_CRYPT_KEY')){
					// 定義から暗号化キーを設定
					self::$_authCryptKey = $ProjectConfigure::DB_CRYPT_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('AUTH_CRYPT_KEY')){
					// 定義から暗号化キーを設定
					self::$_authCryptKey = $ProjectConfigure::AUTH_CRYPT_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('CRYPT_IV')){
					// 定義から暗号化IVを設定
					self::$_sessionCryptIV = $ProjectConfigure::CRYPT_IV;
					self::$_authCryptIV = $ProjectConfigure::CRYPT_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('NETWORK_CRYPT_IV')){
					// 定義から暗号化IVを設定
					self::$_sessionCryptIV = $ProjectConfigure::NETWORK_CRYPT_IV;
				}
				if(NULL !== $ProjectConfigure::constant('SESSION_CRYPT_IV')){
					// 定義から暗号化IVを設定
					self::$_sessionCryptIV = $ProjectConfigure::SESSION_CRYPT_IV;
				}
				if(NULL !== $ProjectConfigure::constant('DB_CRYPT_IV')){
					// 定義から暗号化キーを設定
					self::$_authCryptKIV = $ProjectConfigure::DB_CRYPT_IV;
				}
				if(NULL !== $ProjectConfigure::constant('AUTH_CRYPT_IV')){
					// 定義から暗号化キーを設定
					self::$_authCryptIV = $ProjectConfigure::AUTH_CRYPT_IV;
				}
			}
			$authAutoRefreshKey = getConfig('AUTH_AUTOREFRESH_KEY');
			if(NULL !== $authAutoRefreshKey && 0 < strlen($authAutoRefreshKey)){
				// 定義から暗号化キーを設定
				self::$authAutoRefreshKey = $authAutoRefreshKey;
			}

			// DBOを初期化
			if(NULL === self::$_DBO){
				if(NULL !== $argDSN){
					// セッションDBの接続情報を直指定
					$DSN = $argDSN;
				}
				self::$_DBO = DBO::sharedInstance($DSN);
			}

			// 初期化済み
			self::$_initialized = TRUE;
		}
	}

	public static function init($argDSN=NULL){
		if(FALSE === self::$_initialized){
			self::_init($argDSN);
		}
	}

	/**
	 */
	public static function getEncryptedAuthIdentifier($argIdentifier=NULL){
		if(NULL === $argIdentifier){
			$argIdentifier = Session::sessionID();
		}
		return Utilities::doHexEncryptAES($argIdentifier, self::$_sessionCryptKey, self::$_sessionCryptIV);

	}

	/**
	 */
	public static function getDecryptedAuthIdentifier($argIdentifier=NULL){
		if(FALSE === self::$_initialized){
			self::_init($argDSN);
		}
		if(NULL === $argIdentifier){
			$argIdentifier = Session::sessionID();
		}
		return Utilities::doHexDecryptAES($argIdentifier, self::$_sessionCryptKey, self::$_sessionCryptIV);
	}

	/**
	 * 認証が証明済みのユーザーモデルを返す
	 * @param string DB接続情報
	 */
	public static function getCertifiedUser($argDSN = NULL){
		if(FALSE === self::$_initialized){
			self::_init($argDSN);
		}
		Session::start();
		$sessionIdentifier = Session::sessionID();
		debug( self::$_sessionCryptKey . ':' . self::$_sessionCryptIV);
		debug("session identifier=".$sessionIdentifier);
		$userID = self::getDecryptedAuthIdentifier($sessionIdentifier);
		debug("decrypted userID=".$userID);
		if(strlen($userID) > 0){
			$User = ORMapper::getModel(self::$_DBO, self::$authTable, $userID, NULL, FALSE);
			debug("decrypted DBGet userID=".$User->{self::$authPKeyField});
			try {
				if(isset($User->{self::$authPKeyField}) && NULL !== $User->{self::$authPKeyField} && FALSE === is_object($User->{self::$authPKeyField}) && strlen((string)$User->{self::$authPKeyField}) > 0 && (string)$userID === (string)$User->{self::$authPKeyField}){
					// 型チェック
					// XXX セッションIDが偶然数値スタートのハッシュ値の時に、暗黙型変換でIDセレクトされてデータが返って来てしまっているバグが発生！
					$User->validateType(self::$authPKeyField, $userID);
					// UserIDが特定出来た
					debug("Authlized");
					return $User;
				}
			}
			catch (Exception $Exception) {
				// おそらくIDのバリデートエラーで落ちた
			}
		}
		// 認証出来ない！
		debug("Auth failed");
		return FALSE;
	}

	/**
	 * 認証が証明済みかどうか(セッションが既にあるかどうか)
	 * @param string DB接続情報
	 */
	public static function isCertification($argDSN = NULL){
		static $cookieReplaced = FALSE;
		if(FALSE === self::$_initialized){
			self::_init($argDSN);
		}
		if(FALSE === self::getCertifiedUser()){
			Session::clear();
			return FALSE;
		}
		// 自動ログインリフレッシュカウンターを上げてセッションIDを更新する
		if (1 === (int)Session::get('autoRefreshed')){
			$autoRefreshedCount = 1+(int)Session::get('autoRefreshedCount');
			Session::set('autoRefreshedCount', $autoRefreshedCount);
			if(FALSE === $cookieReplaced){
				// 少なくとも最初の一回目は明示的にコミット
				self::$_DBO->commit();
				$cookieReplaced = TRUE;
			}
		}
		return TRUE;
	}

	/**
	 * 認証を証明する(ログインしてセッションを発行する)
	 * @param string 認証ID
	 * @param string 認証パスワード
	 * @param string DB接続情報
	 * @param string 強制再認証
	 */
	public static function certify($argID = NULL, $argPass = NULL, $argDSN = NULL, $argExecut = FALSE){
		debug('start certify auth');
		if(TRUE === $argExecut || FALSE === self::isCertification($argDSN)){
			$usePost = FALSE;
			$autoRefreshed = FALSE;
			if (is_object($argID) && property_exists($argID, 'id') && 0 < (int)$argID->id){
				$User = $argID;
				if(FALSE === $autoRefreshed){
					if(isset($_REQUEST) && isset($_REQUEST[self::$authAutoRefreshKey]) && 1 === (int)$_REQUEST[self::$authAutoRefreshKey]){
						// リクエストパラメータから直接受け取る
						$autoRefreshed = TRUE;
					}
					if(TRUE === class_exists('Flow', FALSE) && isset(Flow::$params) && isset(Flow::$params['post']) && TRUE === is_array(Flow::$params['post']) && isset(Flow::$params['post'][self::$authAutoRefreshKey]) && 1 === (int)Flow::$params['post'][self::$authAutoRefreshKey]){
						// Flowに格納されているPOSTパラメータを自動で使う
						$autoRefreshed = TRUE;
					}
				}
			}
			else {
				// ログインセッションが無かった場合に処理を実行
				$id = $argID;
				$pass = $argPass;
				if(NULL === $id){
					if(isset($_REQUEST) && isset($_REQUEST[self::$authIDField])){
						// リクエストパラメータから直接受け取る
						$id = $_REQUEST[self::$authIDField];
						$usePost = TRUE;
					}
					if(TRUE === class_exists('Flow', FALSE) && isset(Flow::$params) && isset(Flow::$params['post']) && TRUE === is_array(Flow::$params['post']) && isset(Flow::$params['post'][self::$authIDField])){
						// Flowに格納されているPOSTパラメータを自動で使う
						$id = Flow::$params['post'][self::$authIDField];
						$usePost = TRUE;
					}
				}
				if(NULL === $pass){
					if(isset($_REQUEST) && isset($_REQUEST[self::$authPassField])){
						// リクエストパラメータから直接受け取る
						$pass = $_REQUEST[self::$authPassField];
					}
					if(TRUE === class_exists('Flow', FALSE) && isset(Flow::$params) && isset(Flow::$params['post']) && TRUE === is_array(Flow::$params['post']) && isset(Flow::$params['post'][self::$authPassField])){
						// Flowに格納されているPOSTパラメータを自動で使う
						$pass = Flow::$params['post'][self::$authPassField];
					}
				}
				if(FALSE === $autoRefreshed){
					if(isset($_REQUEST) && isset($_REQUEST[self::$authAutoRefreshKey]) && 1 === (int)$_REQUEST[self::$authAutoRefreshKey]){
						// リクエストパラメータから直接受け取る
						$autoRefreshed = TRUE;
					}
					if(TRUE === class_exists('Flow', FALSE) && isset(Flow::$params) && isset(Flow::$params['post']) && TRUE === is_array(Flow::$params['post']) && isset(Flow::$params['post'][self::$authAutoRefreshKey]) && 1 === (int)Flow::$params['post'][self::$authAutoRefreshKey]){
						// Flowに格納されているPOSTパラメータを自動で使う
						$autoRefreshed = TRUE;
					}
				}
				// ユーザーモデルを取得
				$User = self::getRegisteredUser($id, $pass);
			}
			if(FALSE === $User){
				// 証明失敗
				return FALSE;
			}
			// 認証に成功したら、ログインパラメータは消してしまう
			if (TRUE === $usePost){
				if(TRUE === class_exists('Flow', FALSE) && isset(Flow::$params) && isset(Flow::$params['post']) && TRUE === is_array(Flow::$params['post']) && isset(Flow::$params['post'][self::$authIDField])){
					// Flowに格納されているPOSTパラメータを自動で使う
					unset(Flow::$params['post'][self::$authIDField]);
				}
				if(isset($_REQUEST) && isset($_REQUEST[self::$authIDField])){
					// リクエストパラメータから直接受け取る
					unset($_REQUEST[self::$authIDField]);
				}
			}
			// セッションを発行
			Session::start();
			debug('auth self::$authPKeyField='.self::$authPKeyField);
			$sessionIdentifier = self::getEncryptedAuthIdentifier($User->{self::$authPKeyField});
			debug('auth new identifier='.$sessionIdentifier);
			Session::sessionID($sessionIdentifier);
			// ログインした固有識別子をSessionに保存して、Cookieの発行を行う
			Session::set('identifier', $User->{self::$authPKeyField});
			// 自動ログインリフレッシュを有効にするかどうかのフラグを取っておく
			Session::set('autoRefreshed', $autoRefreshed);
			self::$_DBO->commit();
		}
		debug('auth end certify auth');
		return TRUE;
	}

	/**
	 * 認証を非証明する(ログアウトする)
	 * @param string DB接続情報
	 */
	public static function unCertify($argDSN = NULL){
		if(FALSE === self::$_initialized){
			self::_init($argDSN);
		}
		debug('is logout??');
		Session::clear();
		debug('is logout!!');
		return TRUE;
	}

	/**
	 * 登録済みかどうか
	 * @param string $argDSN
	 */
	public static function getRegisteredUser($argID = NULL, $argPass = NULL, $argDSN = NULL){
		if(FALSE === self::$_initialized){
			self::_init($argDSN);
		}
		$id = $argID;
		$pass = $argPass;
		if(NULL === $id){
			if(isset($_REQUEST) && isset($_REQUEST[self::$authIDField])){
				// リクエストパラメータから直接受け取る
				$id = $_REQUEST[self::$authIDField];
			}
			if(TRUE === class_exists('Flow', FALSE) && isset(Flow::$params) && isset(Flow::$params['post']) && TRUE === is_array(Flow::$params['post']) && isset(Flow::$params['post'][self::$authIDField])){
				// Flowに格納されているPOSTパラメータを自動で使う
				$id = Flow::$params['post'][self::$authIDField];
			}
		}
		if(NULL === $pass){
			if(isset($_REQUEST) && isset($_REQUEST[self::$authPassField])){
				// リクエストパラメータから直接受け取る
				$pass = $_REQUEST[self::$authPassField];
			}
			if(TRUE === class_exists('Flow', FALSE) && isset(Flow::$params) && isset(Flow::$params['post']) && TRUE === is_array(Flow::$params['post']) && isset(Flow::$params['post'][self::$authPassField])){
				// Flowに格納されているPOSTパラメータを自動で使う
				$pass = Flow::$params['post'][self::$authPassField];
			}
		}
		debug($id.':'.$pass);
		$query = '`' . self::$authIDField . '` = :' . self::$authIDField . ' AND `' . self::$authPassField . '` = :' . self::$authPassField . ' ';
		$binds = array(self::$authIDField => self::resolveEncrypted($id, self::$authIDEncrypted), self::$authPassField => self::resolveEncrypted($pass, self::$authPassEncrypted));
		$User = ORMapper::getModel(self::$_DBO, self::$authTable, $query, $binds, FALSE);
		if(isset($User->{self::$authPKeyField}) && NULL !== $User->{self::$authPKeyField} && FALSE === is_object($User->{self::$authPKeyField}) && strlen((string)$User->{self::$authPKeyField}) > 0){
			// 登録済みのユーザーIDを返す
			return $User;
		}
		// ユーザー未登録
		return FALSE;
	}

	/**
	 * 登録済みかどうか
	 * @param string $argDSN
	 */
	public static function isRegistered($argID = NULL, $argPass = NULL, $argDSN = NULL){
		if(FALSE === self::$_initialized){
			self::_init($argDSN);
		}
		if(FALSE === self::getRegisteredUser($argID, $argPass)){
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * 登録する
	 * @param string DB接続情報
	 */
	public static function registration($argID = NULL, $argPass = NULL, $argDate = NULL, $argDSN = NULL){
		if(FALSE === self::$_initialized){
			self::_init($argDSN);
		}
		$id = $argID;
		$pass = $argPass;
		if(NULL === $id){
			if(TRUE === class_exists('Flow', FALSE) && isset(Flow::$params) && isset(Flow::$params['post']) && TRUE === is_array(Flow::$params['post']) && isset(Flow::$params['post'][self::$authIDField])){
				// Flowに格納されているPOSTパラメータを自動で使う
				$id = Flow::$params['post'][self::$authIDField];
			}
			if(isset($_REQUEST) && isset($_REQUEST[self::$authIDField])){
				// リクエストパラメータから直接受け取る
				$id = $_REQUEST[self::$authIDField];
			}
		}
		if(NULL === $pass){
			if(TRUE === class_exists('Flow', FALSE) && isset(Flow::$params) && isset(Flow::$params['post']) && TRUE === is_array(Flow::$params['post']) && isset(Flow::$params['post'][self::$authPassField])){
				// Flowに格納されているPOSTパラメータを自動で使う
				$pass = Flow::$params['post'][self::$authPassField];
			}
			if(isset($_REQUEST) && isset($_REQUEST[self::$authPassField])){
				// リクエストパラメータから直接受け取る
				$pass = $_REQUEST[self::$authPassField];
			}
		}
		$id = self::resolveEncrypted($id, self::$authIDEncrypted);
		$pass = self::resolveEncrypted($pass, self::$authPassEncrypted);
		if(NULL === $argDate){
			$argDate = Utilities::date('Y-m-d H:i:s', NULL, NULL, 'GMT');
		}
		$query = '`' . self::$authIDField . '` = :' . self::$authIDField . ' AND `' . self::$authPassField . '` = :' . self::$authPassField . ' ';
		$binds = array(self::$authIDField => $id, self::$authPassField => $pass);
		$User = ORMapper::getModel(self::$_DBO, self::$authTable, $query, $binds, FALSE);
		$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', self::$authIDField)))}($id);
		$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', self::$authPassField)))}($pass);
		$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', self::$authCreatedField)))}($argDate);
		$User->{'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', self::$authModifiedField)))}($argDate);
		if(TRUE === $User->save()){
			// ユーザーの登録は完了とみなし、コミットを行う！
			self::$_DBO->commit();
		}
		return $User;
	}

	/**
	 * @param string $argDSN
	 */
	public static function resolveEncrypted($argString, $argAlgorism = NULL){
		debug('EncryptAlg='.$argAlgorism);
		$string = $argString;
		if('sha1' === strtolower($argAlgorism)){
			$string = sha1($argString);
		}
		elseif('sha256' === strtolower($argAlgorism)){
			$string = sha256($argString);
		}
		elseif(FALSE !== strpos(strtolower($argAlgorism), 'aes')){
			$string = Utilities::doHexEncryptAES($argString, self::$_authCryptKey, self::$_authCryptIV);
		}
		return $string;
	}

	/**
	 * @param string $argDSN
	 */
	public static function resolveDecrypted($argString, $argAlgorism = NULL){
		debug('DecryptAlg='.$argAlgorism);
		$string = $argString;
		if(FALSE !== strpos(strtolower($argAlgorism), 'aes')){
			$string = Utilities::doHexDecryptAES($argString, self::$_authCryptKey, self::$_authCryptIV);
		}
		return $string;
	}
}
?>