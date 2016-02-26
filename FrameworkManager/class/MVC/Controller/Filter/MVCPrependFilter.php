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
			if (FALSE !== strpos($_SERVER['REQUEST_URI'], '/xresouce/') || FALSE !== strpos($_SERVER['REQUEST_URI'], '/crud/') || FALSE !== strpos($_SERVER['REQUEST_URI'], '/api/')){
				if (TRUE !== (isset($_COOKIE['refresh_token']) && 0 < strlen($_COOKIE['refresh_token']))){
					// 不可
					debug ( 'FWM MVCPrependFilter filtered allow false' . __FILE__.':'.__LINE__);
					return  FALSE;
				}
				$permission = 9;
				if (FALSE !== strpos($_SERVER['REQUEST_URI'], '/xresouce/')){
					// FWMのCRUDへのアクセスは最高権限が必要
					$permission = 0;
				}
				if (FALSE !== strpos($_SERVER['REQUEST_URI'], '/xresouce/me/')){
					// ただし自分のリソースへは誰でもアクセス化
					$permission = 9;
				}
				if (FALSE !== strpos($_SERVER['REQUEST_URI'], '/crud/')){
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