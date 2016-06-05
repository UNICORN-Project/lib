<?php

class FwmuserMigration_1_0e42947aea2b70f2acb3845b868d871d7abddb7c extends MigrationBase {

	public $migrationIdx = "1";

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
		$alter = array();
		$alter["permission"] = array();
		$alter["permission"]["type"] = "string";
		$alter["permission"]["default"] = "9";
		$alter["permission"]["null"] = FALSE;
		$alter["permission"]["pkey"] = FALSE;
		$alter["permission"]["length"] = "1";
		$alter["permission"]["autoincrement"] = FALSE;
		$alter["permission"]["comment"] = "パーミッション(0:マスター〜9:スタッフ)";
		$alter["permission"]["alter"] = "ADD";
		$alter["permission"]["after"] = "pass";
		$alter["default_target_project"] = array();
		$alter["default_target_project"]["type"] = "string";
		$alter["default_target_project"]["null"] = TRUE;
		$alter["default_target_project"]["pkey"] = FALSE;
		$alter["default_target_project"]["length"] = "1024";
		$alter["default_target_project"]["min-length"] = 1;
		$alter["default_target_project"]["autoincrement"] = FALSE;
		$alter["default_target_project"]["comment"] = "デフォルトのターゲットプロジェクト名";
		$alter["default_target_project"]["alter"] = "ADD";
		$alter["default_target_project"]["after"] = "permission";
		
		$index = array();
		
		$res = $this->alter($argDBO, $alter, $index);

		// 初期レコードを追加
		$argDBO->execute("INSERT INTO `fwmuser` (`name`, `mail`, `pass`, `permission`) VALUES('SUPER USER', 'root@super.user', '9d13814473e7d0316260089f089be6e723aecf883be151a48592952d6ac1d98d', '0')");
		$argDBO->commit();
		return $res;
	}

	public function down($argDBO){
		$alter = array();
		$alter["permission"] = array();
		$alter["permission"]["alter"] = "DROP";
		$alter["default_target_project"] = array();
		$alter["default_target_project"]["alter"] = "DROP";
		
		$index = array();
		
		return $this->alter($argDBO, $alter, $index);
	}
}

?>