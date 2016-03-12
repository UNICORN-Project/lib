<?php

class FwmuserMigration_0_0e42947aea2b70f2acb3845b868d871d7abddb7c extends MigrationBase {

	public $migrationIdx = "0";

	public $tableName = "fwmuser";
	public $tableComment = "ユーザーテーブル";
	public $tableEngine = "InnoDB";

	public static $migrationHash = "0e42947aea2b70f2acb3845b868d871d7abddb7c";

	public function __construct(){
		$this->describes = array();
		$this->describes["id"] = array();
		$this->describes["id"]["type"] = "int";
		$this->describes["id"]["null"] = FALSE;
		$this->describes["id"]["pkey"] = TRUE;
		$this->describes["id"]["length"] = "11";
		$this->describes["id"]["min-length"] = 1;
		$this->describes["id"]["autoincrement"] = TRUE;
		$this->describes["id"]["comment"] = "pkey";
		$this->describes["name"] = array();
		$this->describes["name"]["type"] = "string";
		$this->describes["name"]["null"] = FALSE;
		$this->describes["name"]["pkey"] = FALSE;
		$this->describes["name"]["length"] = "1024";
		$this->describes["name"]["min-length"] = 1;
		$this->describes["name"]["autoincrement"] = FALSE;
		$this->describes["name"]["comment"] = "名前";
		$this->describes["mail"] = array();
		$this->describes["mail"]["type"] = "string";
		$this->describes["mail"]["null"] = FALSE;
		$this->describes["mail"]["pkey"] = FALSE;
		$this->describes["mail"]["length"] = "1024";
		$this->describes["mail"]["min-length"] = 1;
		$this->describes["mail"]["autoincrement"] = FALSE;
		$this->describes["mail"]["comment"] = "メールアドレス";
		$this->describes["pass"] = array();
		$this->describes["pass"]["type"] = "string";
		$this->describes["pass"]["null"] = FALSE;
		$this->describes["pass"]["pkey"] = FALSE;
		$this->describes["pass"]["length"] = "64";
		$this->describes["pass"]["min-length"] = 1;
		$this->describes["pass"]["autoincrement"] = FALSE;
		$this->describes["pass"]["comment"] = "パスワード(SHA256)";
		$this->describes["permission"] = array();
		$this->describes["permission"]["type"] = "string";
		$this->describes["permission"]["default"] = "9";
		$this->describes["permission"]["null"] = FALSE;
		$this->describes["permission"]["pkey"] = FALSE;
		$this->describes["permission"]["length"] = "1";
		$this->describes["permission"]["autoincrement"] = FALSE;
		$this->describes["permission"]["comment"] = "パーミッション(0:マスター〜9:スタッフ)";
		$this->describes["default_target_project"] = array();
		$this->describes["default_target_project"]["type"] = "string";
		$this->describes["default_target_project"]["null"] = TRUE;
		$this->describes["default_target_project"]["pkey"] = FALSE;
		$this->describes["default_target_project"]["length"] = "1024";
		$this->describes["default_target_project"]["min-length"] = 1;
		$this->describes["default_target_project"]["autoincrement"] = FALSE;
		$this->describes["default_target_project"]["comment"] = "デフォルトのターゲットプロジェクト名";
		
		return;
	}

	public function up($argDBO){
		$res = $this->create($argDBO);
		@$argDBO->execute('INSERT INTO `fwmuser` (`id`, `name`, `mail`, `pass`, `permission`) SELECT \'1\', \'SUPER USER\', \'root@super.user\', \'9d13814473e7d0316260089f089be6e723aecf883be151a48592952d6ac1d98d\', \'0\' FROM DUAL WHERE NOT EXISTS(SELECT `id` FROM `fwmuser` WHERE `id` = \'1\')');
		@$argDBO->commit();
		return $res;
	}

	public function down($argDBO){
		return $this->drop($argDBO);
	}
}

?>