<?php

class SessionMigration_0_7ede4c7cfb72a886612f543f8d7456d27b0b4777 extends MigrationBase {

	public $migrationIdx = "0";

	public $tableName = "session";
	public $tableComment = "セッションテーブル";
	public $tableEngine = "InnoDB";

	public static $migrationHash = "7ede4c7cfb72a886612f543f8d7456d27b0b4777";

	public function __construct(){
		$this->describes = array();
		$this->describes["token"] = array();
		$this->describes["token"]["type"] = "string";
		$this->describes["token"]["null"] = FALSE;
		$this->describes["token"]["pkey"] = TRUE;
		$this->describes["token"]["length"] = "255";
		$this->describes["token"]["min-length"] = 1;
		$this->describes["token"]["autoincrement"] = FALSE;
		$this->describes["token"]["comment"] = "ワンタイムトークン";
		$this->describes["created"] = array();
		$this->describes["created"]["type"] = "date";
		$this->describes["created"]["null"] = FALSE;
		$this->describes["created"]["pkey"] = FALSE;
		$this->describes["created"]["min-length"] = 1;
		$this->describes["created"]["autoincrement"] = FALSE;
		$this->describes["created"]["comment"] = "トークン作成日時";
		
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