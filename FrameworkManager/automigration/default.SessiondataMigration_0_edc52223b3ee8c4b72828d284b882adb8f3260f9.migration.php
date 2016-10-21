<?php

class SessiondataMigration_0_edc52223b3ee8c4b72828d284b882adb8f3260f9 extends MigrationBase {

	public $migrationIdx = "0";

	public $tableName = "sessiondata";
	public $tableComment = "セッションデータテーブル";
	public $tableEngine = "InnoDB";

	public static $migrationHash = "edc52223b3ee8c4b72828d284b882adb8f3260f9";

	public function __construct(){
		$this->describes = array();
		$this->describes["identifier"] = array();
		$this->describes["identifier"]["type"] = "string";
		$this->describes["identifier"]["null"] = FALSE;
		$this->describes["identifier"]["pkey"] = TRUE;
		$this->describes["identifier"]["length"] = "96";
		$this->describes["identifier"]["min-length"] = 1;
		$this->describes["identifier"]["autoincrement"] = FALSE;
		$this->describes["identifier"]["comment"] = "deviceテーブルのPkey";
		$this->describes["data"] = array();
		$this->describes["data"]["type"] = "text";
		$this->describes["data"]["null"] = TRUE;
		$this->describes["data"]["pkey"] = FALSE;
		$this->describes["data"]["length"] = "65535";
		$this->describes["data"]["min-length"] = 1;
		$this->describes["data"]["autoincrement"] = FALSE;
		$this->describes["data"]["comment"] = "jsonシリアライズされたセッションデータ";
		$this->describes["modified"] = array();
		$this->describes["modified"]["type"] = "date";
		$this->describes["modified"]["null"] = FALSE;
		$this->describes["modified"]["pkey"] = FALSE;
		$this->describes["modified"]["min-length"] = 1;
		$this->describes["modified"]["autoincrement"] = FALSE;
		$this->describes["modified"]["comment"] = "変更日時";
		
		return;
	}

	public function up($argDBO){
		return $this->create($argDBO);
	}

	public function down($argDBO){
		return $this->drop($argDBO);
	}
}

?>