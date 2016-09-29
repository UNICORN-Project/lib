<?php

/**
 * フィルター
 * @author saimushi
 */
class MVCPrependFilter extends BasePrependFilter {
	public function execute($argRequestParams=NULL){
		$allow = parent::execute($argRequestParams);
		if (FALSE !== $allow){
			// refreshTokenチェック対象の確認
			$URIs = explode('?', $_SERVER['REQUEST_URI']);
			$URI = $URIs[0];
			if (FALSE !== strpos($URI, '/xresouce/') || FALSE !== strpos($URI, '/crud/') || FALSE !== strpos($URI, '/api/')){
				if (TRUE !== (isset($_COOKIE['refresh_token']) && 0 < strlen($_COOKIE['refresh_token']))){
					if(TRUE !== Auth::isCertification()){
						// 不可
						debug ( 'FWM MVCPrependFilter filtered allow false' . __FILE__.':'.__LINE__);
						return  FALSE;
					}
					// トークンの自動再発行
					@AccessTokenAuth::generate(Auth::getCertifiedUser()->permission);
				}
				$permission = 9;
				if (FALSE !== strpos($URI, '/xresouce/')){
					// FWMのCRUDへのアクセスは最高権限が必要
					$permission = 0;
				}
				if (FALSE !== strpos($URI, '/xresouce/me/')){
					// ただし自分のリソースへは誰でもアクセス化
					$permission = 9;
				}
				if (FALSE !== strpos($URI, '/crud/')){
					// プロジェクト毎のCRUDへのアクセスは最高権限が必要
					$permission = 0;
				}
				debug ( 'FWM MVCPrependFilter allow check permission ' . var_export($permission, TRUE));
				// refreshTokenチェック
				$allow = AccessTokenAuth::validate($permission, $_COOKIE['refresh_token'], FALSE);
			}
			if(TRUE === Auth::isCertification()){
				@AccessTokenAuth::generate(Auth::getCertifiedUser()->permission);
			}
		}
		return $allow;
	}
}

?>