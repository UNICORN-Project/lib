<?php

$useSupple = TRUE;
require_once dirname(__FILE__).'/index.php';

// マイグレーションを実行
MigrationManager::dispatchDatabase();
MigrationManager::dispatchAll ( DBO::sharedInstance () );

if (FALSE === $_consoled){
	// トップへ移動
	header('Location: ./');
}

?>