<?php

// 全てのRESTを開放
// XXX 代わりにrefreshTokenチェックを行う
$_SERVER['ALLOW_ALL_WHITE'] = TRUE;
$_SERVER['__SUPER_USER__'] = TRUE;
// 管理機能のAPIを開放する手続き
$useAPI = TRUE;
$pkgName = str_replace('ProjectPackage', 'Project', $_GET['_p_']);
require_once dirname(dirname(__FILE__))."/index.php";

?>