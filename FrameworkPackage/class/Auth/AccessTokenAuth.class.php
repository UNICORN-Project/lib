<?php

class AccessTokenAuth {

	public static $permission = NULL;
	public static $expired = 300;// 秒指定

	public static function getAccessKey($argAccessKey=NULL, $argTargetProject=NULL){
		if (NULL === $argAccessKey){
			$argAccessKey = @getConfig('FWM_ACCESS_KEY', $argTargetProject);
		}
		if (NULL === $argAccessKey){
			$argAccessKey = @getConfig('ACCESS_KEY', $argTargetProject);
		}
		return $argAccessKey;
	}

	public static function getAccessSecret($argAccessKey=NULL, $argTargetProject=NULL){
		return @substr(Utilities::doHexEncryptAES(sha256(self::getAccessKey($argAccessKey)), getConfig('AUTH_CRYPT_KEY', $argTargetProject), getConfig('AUTH_CRYPT_IV', $argTargetProject)), -16);
	}

	public static function generate($argPermission=NULL, $argRefresh=TRUE, $argAccessKey=NULL, $argAccessSecret=NULL, $argTargetProject=NULL){
		debug('AccessTokenAuth Permission='.var_export($argPermission, TRUE));
		if (TRUE !== (10 > (int)$argPermission && NULL !== $argPermission)){
			// パーミッション無指定は無視
			return NULL;
		}
		$key = $argAccessKey;
		if (NULL === $key){
			$key = self::getAccessKey($key, $argTargetProject);
		}
		$iv = $argAccessSecret;
		if (NULL === $iv){
			$iv = self::getAccessSecret($key, $argTargetProject);
		}
		$accessToken = @Utilities::doHexEncryptAES(json_encode(array('access_key' => $key, 'permission' => (int)$argPermission, 'time' => Utilities::date('U', NULL, NULL, 'GMT'))), $key, $iv);
		debug('AccessTokenAuth accessToken='.$accessToken);
		if (0 >= strlen($accessToken)){
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
		}
		if (TRUE === $argRefresh){
			// refresh_tokenとして処理する
			$_COOKIE['refresh_token'] = $accessToken;
			@setcookie('refresh_token', $accessToken, 0, '/');
		}
		return $accessToken;
	}

	public static function validate($argPermission=9, $argAccessToken=NULL, $argRefresh=TRUE, $argAccessKey=NULL, $argAccessSecret=NULL, $argTargetProject=NULL, $argExpried=NULL){
		if (NULL === $argAccessToken){
			// Cookie refresh_token認証
			if (isset($_COOKIE['refresh_token']) && 0 < strlen($_COOKIE['refresh_token'])){
				$accessToken = $_COOKIE['refresh_token'];
				unset($_COOKIE['refresh_token']);
				if(FALSE !== self::validate($argPermission, $accessToken, $argRefresh, $argAccessKey, $argAccessSecret, $argTargetProject, $argExpried)){
					return TRUE;
				}
			}
		}
		if (NULL === $argAccessToken){
			// GET access_token認証
			if (isset($_GET['access_token']) && 0 < strlen($_GET['access_token'])){
				$accessToken = $_GET['access_token'];
				unset($_GET['access_token']);
				if(FALSE !== self::validate($argPermission, $accessToken, $argRefresh, $argAccessKey, $argAccessSecret, $argTargetProject, $argExpried)){
					return TRUE;
				}
			}
		}
		// 以下からValidateの実処理
		debug('AccessTokenAuth argAccessToken='.var_export($argAccessToken, TRUE));
		if (NULL === $argAccessToken){
			// アクセストークンが無い
			@setcookie('refresh_token', '', time() - 3600, '/');
			return FALSE;
		}
		debug('whitelistcheck $argAccessKey='.$argAccessKey);
		$key = $argAccessKey;
		if (NULL === $key){
			$key = self::getAccessKey($key, $argTargetProject);
		}
		$iv = $argAccessSecret;
		if (NULL === $iv){
			$iv = self::getAccessSecret($key, $argTargetProject);
		}
		$accessTokens = @json_decode(Utilities::doHexDecryptAES($argAccessToken, $key, $iv), TRUE);
		debug('AccessTokenAuth accessTokens='.var_export($accessTokens, TRUE));
		if (TRUE != (isset($accessTokens['access_key']) && isset($accessTokens['permission']) && isset($accessTokens['time']))){
			// Validate Error
			@setcookie('refresh_token', '', time() - 3600, '/');
			return FALSE;
		}
		// アクセスキーの不一致は認めない
		if ($key !== $accessTokens['access_key']){
			// Validate Error
			@setcookie('refresh_token', '', time() - 3600, '/');
			return FALSE;
		}
		if (NULL === $argExpried){
			$argExpried = self::$expired;
		}
		// 5分以内のアクセストークンしか認めない
		if (Utilities::modifyDate('-'.$argExpried.'second', 'U', NULL, NULL, 'GMT') > (int)$accessTokens['time']){
			// Validate Error
			@setcookie('refresh_token', '', time() - 3600, '/');
			return FALSE;
		}
		// パーミッションの不一致は認めない
		if (TRUE !== (is_numeric($accessTokens['permission']) && (int)$argPermission >= (int)$accessTokens['permission'])){
			@setcookie('refresh_token', '', time() - 3600, '/');
			return FALSE;
		}
		if (10 > (int)$accessTokens['permission']){
			// 保持するパーミッションとして認める
			self::$permission = (int)$accessTokens['permission'];
			if (TRUE === $argRefresh){
				debug('AccessTokenAuth auto refresh');
				return self::generate($accessTokens['permission'], $argRefresh, $argAccessKey, $argAccessSecret, $argTargetProject);
			}
		}
		return TRUE;
	}
}

?>