<?php

// フレームワークのアクセス制限機能を実行
$useSupple = true;
require_once dirname(dirname(__FILE__)).'/index.php';
if (TRUE !== (isset($_COOKIE['refresh_token']) && 0 < strlen($_COOKIE['refresh_token']))){
	ob_end_clean();
	// アクセス禁止
	header ('HTTP', TRUE, '400');
	echo '400 Bad Request';
	exit;
}
if (FALSE === AccessTokenAuth::validate(0, $_COOKIE['refresh_token'])){
	ob_end_clean();
	// アクセス禁止
	header ('HTTP', TRUE, '400');
	echo '400 Bad Request';
	exit;
}
// UNICORNのオートローダを止めてしまう
autoloadUnregisterFramework();

// info表示
phpinfo();

?>