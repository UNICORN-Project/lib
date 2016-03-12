<?php

// 全てのRESTを開放
// XXX 代わりにrefreshTokenチェックを行う
$_SERVER['ALLOW_ALL_WHITE'] = true;



require_once dirname(dirname(__FILE__))."/index.php";

?>