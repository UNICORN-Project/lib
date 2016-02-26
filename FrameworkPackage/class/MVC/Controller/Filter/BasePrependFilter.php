<?php

/**
 * フィルター
 * @author saimushi
 */
class BasePrependFilter {
	public function execute($argRequestParams=NULL){
		$allow = NULL;
		$denyHTTP = FALSE;
		$allowIPFilter = FALSE;
		if(class_exists('Configure') && NULL !== Configure::constant('DENY_HTTP')){
			$denyHTTP = Configure::DENY_HTTP;
		}
		if(class_exists('Configure') && NULL !== Configure::constant('ALLOW_IP_FILTER')){
			$allowIPFilter = Configure::ALLOW_IP_FILTER;
		}
		if(defined('PROJECT_NAME') && strlen(PROJECT_NAME) > 0 && class_exists(PROJECT_NAME . 'Configure')){
			$ProjectConfigure = PROJECT_NAME . 'Configure';
			if(NULL !== $ProjectConfigure::constant('DENY_HTTP')){
				$denyHTTP = $ProjectConfigure::DENY_HTTP;
			}
			if(NULL !== $ProjectConfigure::constant('ALLOW_IP_FILTER')){
				$allowIPFilter = $ProjectConfigure::ALLOW_IP_FILTER;
			}
		}
		if(isset($_SERVER['DENY_HTTP']) && 1 === (int)strtolower($_SERVER['DENY_HTTP'])){
				// $_SERVERが最強 $_SERVERがあれば$_SERVERに必ず従う
				$denyHTTP = 1;
		}
		if(isset($_SERVER['ALLOW_IP_FILTER']) && 0 < strlen($_SERVER['ALLOW_IP_FILTER'])){
			// $_SERVERが最強 $_SERVERがあれば$_SERVERに必ず従う
			$allowIPFilter = $_SERVER['ALLOW_IP_FILTER'];
		}

		debug('MVCPrependFilter denyHTTP='.$denyHTTP);
		debug('MVCPrependFilter allowIPFilter='.$allowIPFilter);
		debug($_SERVER);

		// SSLチェック
		if(FALSE !== $denyHTTP && 0 !== $denyHTTP && "0" !== $denyHTTP){
			debug('MVCPrependFilter denyHTTP check');
			debug('MVCPrependFilter denyHTTP='.$denyHTTP);
			if(!(isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS'])){
				$allow = FALSE;
			}
			else {
				$allow = TRUE;
			}
			debug('MVCPrependFilter denyHTTP ALLOW='.$allow);
		}
		// IPアドレスチェック
		if(FALSE !== $allow && FALSE !== $allowIPFilter && 0 !== $allowIPFilter && "0" !== $allowIPFilter){
			debug('MVCPrependFilter allowIPFilter check');
			debug('MVCPrependFilter allowIPFilter='.$allowIPFilter);
			// ローカルIPは無条件許可 それ以外はCIDR表記によるマスクチェックまたはIPの完全一致をcheckIPで確認
			if('::1' !== $_SERVER['REMOTE_ADDR'] && '127.0.0.1' !== $_SERVER['REMOTE_ADDR'] && TRUE !== checkIP($_SERVER['REMOTE_ADDR'], $allowIPFilter)){
				$allow = FALSE;
			}
			else {
				$allow = TRUE;
			}
			debug('MVCPrependFilter allowIPFilter ALLOW='.$allow);
		}
		if(FALSE !== $allow){
			// モバイルIPは非固定なので、UAから判断してしまって、許可をする
			$serverUserAgent = $_SERVER ['HTTP_USER_AGENT'];
			if (false != strpos ( strtolower ( $serverUserAgent ), 'iphone' )) {
				$allow = TRUE;
			}
			elseif (false != strpos ( strtolower ( $serverUserAgent ), 'ipad' )) {
				$allow = TRUE;
			}
			elseif (false != strpos ( strtolower ( $serverUserAgent ), 'ipod' )) {
				$allow = TRUE;
			}
			elseif (false != strpos ( strtolower ( $serverUserAgent ), 'android' )) {
				$allow = TRUE;
			}
		}
		debug('MVCPrependFilter ALLOW='.$allow);
		return $allow;
	}
}

?>