<?php

use Abraham\TwitterOAuth\TwitterOAuth;

class GenericTwitterOAuthAgent
{
	public static function startAuth($argAccessKey, $argAccessSecret, $argCollbackURL, $argAutoExit=TRUE, $argOptionParams=NULL) {
		// Twitterセッションを開始する
		TwitterSession::start();
		//TwitterOAuth をインスタンス化
		$connection = new TwitterOAuth($argAccessKey, $argAccessSecret);
		//コールバックURLをここでセット
		$request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $argCollbackURL));
		//callback.phpで使うのでセッションに入れる
		TwitterSession::set('request_token', json_encode($request_token));
		if (NULL !== $argOptionParams && is_array($argOptionParams) && 0 < count($argOptionParams)){
			TwitterSession::set('option_params', json_encode($argOptionParams));
		}
		// セッションを保存
		TwitterSession::flush();
		//Twitter.com 上の認証画面のURLを取得( この行についてはコメント欄も参照 )
		$url = $connection->url('oauth/authenticate', array('oauth_token' => $request_token['oauth_token']));
		//Twitter.com の認証画面へリダイレクト
		if (TRUE === $argAutoExit){
			header( 'location: '. $url );
			exit;
		}
		return $url;
	}

	public static function callbackAuth($argAccessKey, $argAccessSecret) {
		// Twitterセッションを開始する
		TwitterSession::start();
		$request_token = TwitterSession::get('request_token');
		// バリデート
		if (!(0 < strlen($request_token))){
			return FALSE;
		}
		$request_token = @json_decode($request_token, TRUE);
		if (TRUE !== (isset($request_token['oauth_token']) && isset($request_token['oauth_token_secret']) && 0 < strlen($request_token['oauth_token']) && 0 < strlen($request_token['oauth_token_secret']))){
			return FALSE;
		}
		//Twitterから返されたOAuthトークンと、あらかじめlogin.phpで入れておいたセッション上のものと一致するかをチェック
		if (TRUE !== (isset($_GET['oauth_token']) && $request_token['oauth_token'] == $_GET['oauth_token'])) {
			return FALSE;
		}
		if (TRUE !== (isset($_GET['oauth_verifier']) && 0 < strlen($_GET['oauth_verifier']))) {
			return FALSE;
		}
		// アクセストークンを取得する
		$connection = new TwitterOAuth($argAccessKey, $argAccessSecret, $request_token['oauth_token'], $request_token['oauth_token_secret']);
		$access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_GET['oauth_verifier']));
		if (TRUE !== (is_array($access_token) && isset($access_token['user_id']) && 0 < strlen($access_token['user_id']) && is_numeric($access_token['user_id']))) {
			return FALSE;
		}
		// オプションパラメータの復帰処理
		$access_token['option_params'] = NULL;
		$optionParam = TwitterSession::get('option_params');
		// バリデート
		if (0 < strlen($optionParam)){
			$optionParams = @json_decode($optionParam, TRUE);
			if (is_array($optionParams) && 0 < count($optionParams)){
				$access_token['option_params'] = $optionParams;
			}
		}
		TwitterSession::set('option_params', '');
		TwitterSession::clear();
		return $access_token;
	}
}

?>