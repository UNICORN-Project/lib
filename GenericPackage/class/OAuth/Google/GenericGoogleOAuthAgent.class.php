<?php

class GenericGoogleOAuthAgent {
	
	public static function startAuth($argClientId, $argAccessSecret, $argCallbackURL, $argAutoExit=TRUE, $argOptionParams=NULL) {
		// Googleセッションを開始する
		GoogleSession::start();

		//Google_Client をインスタンス化
		$googleClient = new Google_Client();

		//ローカルのURLが怒られるので置換(コーディング完了後削除)
		if ( preg_match("/anlimited.localhost/",$argCallbackURL)===1){
			$argCallbackURL = preg_replace("/anlimited.localhost/", "localhost", $argCallbackURL);
		}
		
		$googleClient->setClientID($argClientId);
		$googleClient->setClientSecret($argAccessSecret);
		$googleClient->setScopes('email profile');
		$googleClient->setRedirectUri($argCallbackURL);
		
		$access_token = $googleClient->getAccessToken();

		//callback.phpで使うのでセッションに入れる
		GoogleSession::set('access_token', json_encode($access_token));
		GoogleSession::set('callback_url', $argCallbackURL);

		if (NULL !== $argOptionParams && is_array($argOptionParams) && 0 < count($argOptionParams)){
			GoogleSession::set('option_params', json_encode($argOptionParams));
		}
		// セッションを保存
		GoogleSession::flush();
		
		//google上の認証画面のURLを取得
		$url = $googleClient->createAuthUrl();
		
		//Google の認証画面へリダイレクト
		if (TRUE === $argAutoExit){
			header( 'location: '. $url );
			exit;
		}
		return $url;
	}

	public static function callbackAuth($argClientId, $argClientSecret) {
		// Twitterセッションを開始する
		GoogleSession::start();
		$access_token = GoogleSession::get('access_token');
		$callback_url = GoogleSession::get('callback_url');
		// バリデート
		if (!(0 < strlen($access_token))){
			return FALSE;
		}
		$access_token = @json_decode($access_token, TRUE);
		if (TRUE !== (isset($access_token['access_token']) && isset($access_token['token_type']) && 0 < strlen($access_token['expires_in']) && 0 < strlen($access_token['id_token']))){
			//googleからリダイレクトされてきた?
			$access_token['access_token'] = $_GET['code'];
		}
		//Googleから返されたOAuthトークンと、あらかじめlogin.phpで入れておいたセッション上のものと一致するかをチェック
		if (TRUE !== (isset($_GET['code']) && $access_token['access_token'] == $_GET['code'])) {
			return FALSE;
		}

		// アクセストークンを取得する
		$googleClient = new Google_Client();
		$googleClient->setClientID($argClientId);
		$googleClient->setClientSecret($argClientSecret);
		$googleClient->setConfig('grant_type', 'authorization_code');
		$googleClient->setScopes('email profile');
		$googleClient->setRedirectUri($callback_url);
		
		$googleClient->authenticate($_GET['code']);
		$access_token = $googleClient->getAccessToken();
		$id_token = $googleClient->verifyIdToken();
		
		if (TRUE !== (isset($access_token['access_token']) && isset($access_token['token_type']) && 0 < strlen($access_token['expires_in']) && 0 < strlen($access_token['id_token']))){
			return FALSE;
		}
		$access_token['id_token'] = $id_token;

		// オプションパラメータの復帰処理
		$access_token['option_params'] = NULL;
		$optionParam = GoogleSession::get('option_params');
		// バリデート
		if (0 < strlen($optionParam)){
			$optionParams = @json_decode($optionParam, TRUE);
			if (is_array($optionParams) && 0 < count($optionParams)){
				$access_token['option_params'] = $optionParams;
			}
		}
		// セッションを削除
		GoogleSession::set('option_params', '');
		//GoogleSession::clear();
		return $access_token;
	}
}

?>