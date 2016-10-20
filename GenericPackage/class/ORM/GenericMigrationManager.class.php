<?php

class GenericMigrationManager {

	private static $_lastMigrationHash;

	/**
	 * データベースをマイグレーションする
	 * @return boolean
	 */
	public static function dispatchDatabase(){
		logging('DB Migration:is init? '.getConfig('PROJECT_ROOT_PATH').'.dbinitialized'.'='.(is_file(getConfig('PROJECT_ROOT_PATH').'.dbinitialized')), 'migration');
		// DBマイグレーションを実行
		if (!is_file(getConfig('PROJECT_ROOT_PATH').'.dbinitialized')){
			$connect = FALSE;
			$host = '';
			$port = '';
			$user = '';
			$pass = '';
			if (getLocalEnabled()){
				// ローカルの場合
				$host = 'mysqld';
				$port = '';
				$user = 'root';
				$pass = 'root';
			}
			else {
				$dsn = getConfig('DB_DSN');
				if (0 >= strlen($dsn) && defined("DB_DSN")){
					// 定数を使う
					$dsn = DB_DSN;
				}
				logging('DB Migration:DB Connect '.parse_url($dsn, PHP_URL_HOST).' '. parse_url($dsn, PHP_URL_USER).' '. parse_url($dsn, PHP_URL_PASS).' '. 'mysql'.' '. parse_url($dsn, PHP_URL_PORT).'.', 'migration');
				// コンフィグから接続先を特定
				$host = parse_url($dsn, PHP_URL_HOST);
				$port = parse_url($dsn, PHP_URL_PORT);
				$user = parse_url($dsn, PHP_URL_USER);
				$pass = parse_url($dsn, PHP_URL_PASS);
				$path = parse_url($dsn, PHP_URL_PATH);
				if (0 === strpos($path, '/')){
					$path = substr($path, 1);
				}
			}
			if (0 >= strlen($port)){
				$port = '3306';
			}
			$ok = false;
			if (isset($path)){
				logging('DB Migration:DB Connect '.$host.', '.$user.', '.$pass.', '.$path.', '.$port.'.', 'migration');
				// 先ず、そもそもDBマイグレーションする必要があるかどうかを確認
				$connect = @mysqli_connect($host, $user, $pass, $path, $port);
				if (FALSE !== $connect){
					// マイグレーション済みとして、処理を即正常終了させる
					logging('DB Migration:DB Connect ok. '.$host.' '.$user.' '.$pass.' '.$path.' '.$port, 'migration');
					$ok = true;
				}
			}
			if (false === $ok){
				logging('DB Migration:DB Connect '.$host.', '.$user.', '.$pass.', '.$port.'.', 'migration');
				$connect = @mysqli_connect($host, $user, $pass, 'mysql', $port);
				$tryroot = false;
				if (FALSE === $connect){
					logging('DB Migration:DB Connect Error.', 'migration');
					$tryroot = true;
				}
				// rootを試す
				if (true === $tryroot){
					$connect = @mysqli_connect($host, 'root', 'root', 'mysql', $port);
					if (FALSE === $connect){
						logging('DB Migration:DB Root:Root Connect Error.', 'migration');
						$tryroot = true;
					}
				}
				// passなしも試す
				if (true === $tryroot){
					$connect = @mysqli_connect($host, 'root', 'root', 'mysql', $port);
					if (FALSE === $connect){
						logging('DB Migration:DB Root: Connect Error.', 'migration');
						exit;
					}
				}
			}
			// 初期テーブルマイグレーション
			$createdb = file_get_contents(getConfig('PROJECT_ROOT_PATH').'core/createdb.sql');
			$matchies = NULL;
			if (!preg_match('/`(.+)?`/', $createdb, $matchies)){
				logging('DB Migration:DB Name Resolve Error.', 'migration');
				exit;
			}
			$dbname = $matchies[1];
			$res = mysqli_set_charset($connect, 'utf8');
			$res = mysqli_query($connect, 'set names utf8');
			$res = mysqli_multi_query($connect, $createdb);
			if(FALSE === $res){
				logging('DB Migration:DB Create Error.', 'migration');
				exit;
			}
			logging('DB Migration:DB '.$dbname.' Created.', 'migration');
			mysqli_commit($connect);
			mysqli_close($connect);
		
			$connect = @mysqli_connect($host, $user, $pass, $dbname, $port);
			$res = mysqli_set_charset($connect, 'utf8');
			logging('DB Migration:Connect Created db '.$dbname.'.', 'migration');
			$createTableTmp = file_get_contents(getConfig('PROJECT_ROOT_PATH').'core/createtable.sql');
			$createTables = explode("\n", $createTableTmp);
			logging('DB Migration:Create tables='.var_export($createTables, TRUE), 'migration');
			for ($tidx=0; $tidx < count($createTables); $tidx++) {
				if (0 < strlen($createTables[$tidx]) && 0 !== strpos($createTables[$tidx], '--')){
					logging('DB Migration:Create table='.$createTables[$tidx], 'migration');
					$res = mysqli_query($connect, $createTables[$tidx]);
					if(FALSE === $res){
						logging('DB Migration:Table Create Error.', 'migration');
						exit;
					}
					mysqli_commit($connect);
				}
			}
			mysqli_close($connect);
			logging('DB Migration:Table Created.', 'migration');
			touch(getConfig('PROJECT_ROOT_PATH').'.dbinitialized');
			@chmod(getConfig('PROJECT_ROOT_PATH').'.dbinitialized', 0666);
			logging('DB Migration:DB migrated.', 'migration');
		}
		return TRUE;
	}

	/**
	 * 適用されていないマイグレーションを探して、あれば実行する。なければそのまま終了する
	 * @param instance $argDBO
	 * @return boolean
	 */
	public static function dispatchAll($argDBO, $argTblName=NULL){
		static $executed = FALSE;
		// 1プロセス内で2度も処理しない
		if(FALSE === $executed){
			// データベースのマイグレーションをまずは実行
			if (TRUE !== self::dispatchDatabase()){
				return FALSE;
			}
			// 適用差分を見つける
			self::$_lastMigrationHash = NULL;
			$diff = self::_getDiff($argDBO, $argTblName);
			logging('diff=', 'migration');
			logging($diff, 'migration');
			if(count($diff) > 0){
				// 差分の数だけマイグレーションを適用
				for($diffIdx=0; $diffIdx < count($diff); $diffIdx++){
					$migrationFilePath = getAutoMigrationPath().$argDBO->dbidentifykey.'.'.$diff[$diffIdx].'.migration.php';
					if(TRUE === file_exists($migrationFilePath) && TRUE === is_file($migrationFilePath)){
						@include_once $migrationFilePath;
						// migrationの実行
						$migration = new $diff[$diffIdx]();
						if(TRUE === $migration->up($argDBO)){
							logging('migration up! '.$diff[$diffIdx], 'migration');
							// マイグレーション済みに追加
							@file_put_contents_e(getAutoMigrationPath().$argDBO->dbidentifykey.'.dispatched.migrations', $diff[$diffIdx].PHP_EOL, FILE_APPEND);
							@chmod(getAutoMigrationPath().$argDBO->dbidentifykey.'.dispatched.migrations', 0666);
						}
					}
				}
			}
			$executed = TRUE;
			if(NULL !== self::$_lastMigrationHash){
				return self::$_lastMigrationHash;
			}
		}
		return TRUE;
	}

	/**
	 * テーブルマイグレートを自動解決する
	 * @param unknown $argDBO
	 * @param unknown $argTable
	 * @return boolean
	 */
	public static function resolve($argDBO, $argTblName, $argLastMigrationHash=NULL){
		static $executed = array();
		// 1プロセス内で同じテーブルに対してのマイグレーションを2度も処理しない
		if(FALSE === (isset($executed[$argTblName]) && TRUE === $executed[$argTblName])){
			$firstMigration = TRUE;
			if(!isset(ORMapper::$modelHashs[$argTblName])){
				// コンソールから強制マイグレーションされる時に恐らくココを通る
				$nowModel = ORMapper::getModel($argDBO, $argTblName);
			}
			// XXX ORMapperとMigrationManagerは循環しているのでいじる時は気をつけて！
			$modelHash = ORMapper::$modelHashs[$argTblName];
			// modelハッシュがmigrationハッシュに含まれていないかどうか
			$migrationHash = $argLastMigrationHash;
			if(NULL === $migrationHash){
				// 既に見つけているマイグレーションハッシュから定義を取得する
				$diff = self::_getDiff($argDBO, $argTblName);
				if(NULL !== self::$_lastMigrationHash){
					$migrationHash = self::$_lastMigrationHash;
				}
			}
			logging('$migrationHash='.$migrationHash, 'migration');
			logging('$modelHash='.$modelHash, 'migration');
			// マイグレーションハッシュがある場合は
			if(NULL !== $migrationHash){
				if(FALSE !== strpos($migrationHash, $modelHash)){
					// このテーブルはマイグレーション済み
					$executed[$argTblName] = TRUE;
					// 現在のテーブル定義と最新のマイグレーションファイル上のテーブルハッシュに差分が無いので何もしない
					logging('exists migration! '.$migrationHash, 'migration');
					return TRUE;
				}
				// 最後に適用している該当テーブルに対してのマイグレーションクラスを読み込んでmodelハッシュを比較する
				$migrationFilePath = getAutoMigrationPath().$argDBO->dbidentifykey.'.'.$migrationHash.'.migration.php';
				if(TRUE === file_exists($migrationFilePath) && TRUE === is_file($migrationFilePath)){
					// 既にテーブルはあるとココで断定
					$firstMigration = FALSE;
					// 直前のマイグレーションクラスをインスタンス化
					@include_once $migrationFilePath;
					// モデルハッシュが変わっているかどうかを比較
					if($modelHash == $migrationHash::$migrationHash){
						// このテーブルはマイグレーション済み
						$executed[$argTblName] = TRUE;
						// 現在のテーブル定義と最新のマイグレーションファイル上のテーブルハッシュに差分が無いので何もしない
						return TRUE;
					}
				}
			}

			// テーブル定義を取得
			$tableDefs = ORMapper::getModelPropertyDefs($argDBO, $argTblName);
			$varDef = $tableDefs['varDef'];
			eval(str_replace('public', '', $varDef));
			$describeDef = $tableDefs['describeDef'];
			$indexDef = $tableDefs['indexDef'];
			$migrationIdx = 0;

			$migrationClassDef = PHP_EOL;
			$migrationClassDef .= PHP_EOL . PHP_TAB . 'public function __construct(){' . PHP_EOL . PHP_TAB . PHP_TAB . str_replace('; ', ';' . PHP_EOL . PHP_TAB . PHP_TAB, $describeDef) . PHP_EOL . PHP_TAB . PHP_TAB . str_replace('; ', ';' . PHP_EOL . PHP_TAB . PHP_TAB, $indexDef) . 'return;' . PHP_EOL . PHP_TAB . '}'. PHP_EOL;
			if(TRUE === $firstMigration){
				// create指示を生成
				$migrationClassDef .= PHP_EOL . PHP_TAB . 'public function up($argDBO){' . PHP_EOL . PHP_TAB . PHP_TAB . 'return $this->create($argDBO);' . PHP_EOL . PHP_TAB . '}'. PHP_EOL;
				// drop指示を生成
				$migrationClassDef .= PHP_EOL . PHP_TAB . 'public function down($argDBO){' . PHP_EOL . PHP_TAB . PHP_TAB . 'return $this->drop($argDBO);' . PHP_EOL . PHP_TAB . '}'. PHP_EOL;
			}
			else {
				// ALTERかDROP指示を生成
				// 差分をフィールドを走査して特定する
				$lastModel = new $migrationHash();
				$beforeDescribes = $lastModel->describes;
				$beforeIndexes = $lastModel->indexes;
				$beforeComment = $lastModel->tableComment;
				$beforeEngine = $lastModel->tableEngine;
				$migrationIdx = (int)$lastModel->migrationIdx;
				$migrationIdx++;
				// フィールドマイグレーション
				$upAlterDef = '$alter = array(); ';
				$downAlterDef = '$alter = array(); ';
				$describes = array();
				$beforeFieldKey = NULL;
				eval(str_replace('$this->', '$', $describeDef));
				// フィールドが増えている もしくは数は変わらないが定義が変わっている
				foreach($describes as $feldKey => $propary){
					// 最新のテーブル定義に合わせて
					$alter = NULL;
					if(!array_key_exists($feldKey, $beforeDescribes)){
						// 増えてるフィールドを単純に増やす
						$alter = 'ADD';
						$downAlterDef .= '$alter["'.$feldKey.'"] = array(); ';
						$downAlterDef .= '$alter["'.$feldKey.'"]["alter"] = "DROP"; ';
					}
					// 新旧フィールドのハッシュ値比較
					elseif(isset($beforeDescribes[$feldKey]) && sha1(serialize($propary)) != sha1(serialize($beforeDescribes[$feldKey]))){
						// ハッシュ値が違うので新しいフィールド情報でAlterする
						$alter = 'MODIFY';
						// 元に戻すMODYFI
						$alterDefs = ORMapper::getModelPropertyDefs($argDBO, $argTblName, array($feldKey=>$beforeDescribes[$feldKey]));
						$downAlterDef .= str_replace('$this->describes = array(); ', '', $alterDefs['describeDef']);
						$downAlterDef .= '$alter["'.$feldKey.'"]["alter"] = "' . $alter . '"; ';
					}
					if(NULL === $alter){
						// 処理をスキップして次のループへ
						$beforeFieldKey = $feldKey;
						continue;
					}
					// up生成
					$alterDefs = ORMapper::getModelPropertyDefs($argDBO, $argTblName, array($feldKey=>$propary));
					$upAlterDef .= str_replace('$this->describes = array(); ', '', $alterDefs['describeDef']);
					$upAlterDef .= '$alter["'.$feldKey.'"]["alter"] = "' . $alter . '"; ';
					if('ADD' === $alter){
						if(NULL === $beforeFieldKey){
							// 先頭にフィールドが増えている
							$upAlterDef .= '$alter["'.$feldKey.'"]["first"] = TRUE; ';
						}
						else {
							// ADDする箇所の指定
							$upAlterDef .= '$alter["'.$feldKey.'"]["after"] = "' . $beforeFieldKey . '"; ';
						}
					}
					$beforeFieldKey = $feldKey;
				}
				// フィールドが減っている
				$beforeFieldKey = NULL;
				// XXX upとdownがただ増えている時と逆なだけ
				foreach($beforeDescribes as $feldKey => $propary){
					// 前のテーブル定義に合わせて
					$alter = NULL;
					if(!array_key_exists($feldKey, $describes)){
						// 減ってるフィールドを単純にARTER DROPする
						$alter = 'ADD';
						$upAlterDef .= '$alter["'.$feldKey.'"] = array(); ';
						$upAlterDef .= '$alter["'.$feldKey.'"]["alter"] = "DROP"; ';
					}
					if(NULL === $alter){
						// 処理をスキップして次のループへ
						$beforeFieldKey = $feldKey;
						continue;
					}
					// down生成
					$alterDefs = ORMapper::getModelPropertyDefs($argDBO, $argTblName, array($feldKey=>$propary));
					$downAlterDef .= str_replace('$this->describes = array(); ', '', $alterDefs['describeDef']);
					$downAlterDef .= '$alter["'.$feldKey.'"]["alter"] = "' . $alter . '"; ';
					if('ADD' === $alter){
						if(NULL === $beforeFieldKey){
							// 先頭にフィールドが増えている
							$downAlterDef .= '$alter["'.$feldKey.'"]["first"] = TRUE; ';
						}
						else {
							// ADDする箇所の指定
							$downAlterDef .= '$alter["'.$feldKey.'"]["after"] = "' . $beforeFieldKey . '"; ';
						}
					}
					$beforeFieldKey = $feldKey;
				}
				// インデックスマイグレーション
				$upIndexDef = '$index = array(); ';
				$downIndexDef = '$index = array(); ';
				$indexes = array();
				eval(str_replace('$this->', '$', $indexDef));
				// フィールドが増えている もしくは数は変わらない
				foreach($indexes as $feldKey => $propary){
					// 最新のテーブル定義に合わせて
					$alter = NULL;
					if(!array_key_exists($feldKey, $beforeIndexes)){
						// 増えてるフィールドを単純に増やす
						$alter = 'ADD';
						$downIndexDef .= '$index["'.$feldKey.'"] = array(); ';
						$downIndexDef .= '$index["'.$feldKey.'"]["alter"] = "DROP"; ';
					}
					// 新旧フィールドのハッシュ値比較
					elseif(isset($beforeIndexes[$feldKey]) && sha1(serialize($propary)) != sha1(serialize($beforeIndexes[$feldKey]))){
						// ハッシュ値が違うので新しいMODIFY(DROP CREATE)でAlterする
						$alter = 'MODIFY';
						$downIndexDef .= '$index["'.$feldKey.'"] = array(); ';
						$downIndexDef .= '$index["'.$feldKey.'"]["alter"] = "' . $alter . '"; ';
						$downIndexDef .= '$index["'.$feldKey.'"]["Colums"] = array(); ';
						for ($colIdx=0; $colIdx < count($beforeIndexes[$feldKey]["Colums"]); $colIdx++){
							$downIndexDef .= '$index["'.$feldKey.'"]["Colums"][] = "' . $beforeIndexes[$feldKey]["Colums"][$colIdx] . '"; ';
						}
						if (isset($beforeIndexes[$feldKey]["Unique"]) && 1 === (int)$beforeIndexes[$feldKey]["Unique"]){
							$downIndexDef .= '$index["'.$feldKey.'"]["Unique"] = 1; ';
						}
						$downIndexDef .= '$index["'.$feldKey.'"]["Index_comment"] = "' . $beforeIndexes[$feldKey]["Index_comment"] . '"; ';
					}
					if(NULL === $alter){
						// 処理をスキップして次のループへ
						continue;
					}
					// up生成
					$upIndexDef .= '$index["'.$feldKey.'"] = unserialize(\'' . serialize($propary) . '\'); ';
					$upIndexDef .= '$index["'.$feldKey.'"]["alter"] = "' . $alter . '"; ';
				}
				// フィールドが減っているかチェック
				// XXX upとdownがただ増えている時と逆なだけ
				foreach($beforeIndexes as $feldKey => $propary){
					// 前のテーブル定義に合わせて
					$alter = NULL;
					if(!array_key_exists($feldKey, $indexes)){
						// 減ってるフィールドを単純にARTER DROPする
						$alter = 'ADD';
						$upIndexDef .= '$index["'.$feldKey.'"] = array(); ';
						$upIndexDef .= '$index["'.$feldKey.'"]["alter"] = "DROP"; ';
					}
					if(NULL === $alter){
						// 処理をスキップして次のループへ
						continue;
					}
					// down生成
					$downIndexDef .= '$index["'.$feldKey.'"] = unserialize(\'' . serialize($propary) . '\'); ';
					$downIndexDef .= '$index["'.$feldKey.'"]["alter"] = "' . $alter . '"; ';
				}
				// テーブルコメントマイグレーション
				if ($beforeComment != $tableComment){
					$upAlterDef .= '$alter["__Comment__"]["alter"] = "MODIFY"; ';
					$downAlterDef .= '$alter["__Comment__"]["before"] = "'.$beforeComment.'"; ';
					$downAlterDef .= '$alter["__Comment__"]["alter"] = "MODIFY"; ';
				}
				// DBエンジンマイグレーション
				if ($beforeEngine != $tableEngine){
					$upAlterDef .= '$alter["__Engine__"]["alter"] = "MODIFY"; ';
					$downAlterDef .= '$alter["__Engine__"]["before"] = "'.$beforeEngine.'"; ';
					$downAlterDef .= '$alter["__Engine__"]["alter"] = "MODIFY"; ';
				}
				// alter指示を生成
				$migrationClassDef .= PHP_EOL . PHP_TAB . 'public function up($argDBO){' . PHP_EOL . PHP_TAB . PHP_TAB . str_replace('$this->describes', '$alter', str_replace('; ', ';' . PHP_EOL . PHP_TAB . PHP_TAB, $upAlterDef)) . PHP_EOL . PHP_TAB . PHP_TAB . str_replace('; ', ';' . PHP_EOL . PHP_TAB . PHP_TAB, $upIndexDef) . PHP_EOL . PHP_TAB . PHP_TAB . 'return $this->alter($argDBO, $alter, $index);' . PHP_EOL . PHP_TAB . '}'. PHP_EOL;
				$migrationClassDef .= PHP_EOL . PHP_TAB . 'public function down($argDBO){' . PHP_EOL . PHP_TAB . PHP_TAB . str_replace('$this->describes', '$alter', str_replace('; ', ';' . PHP_EOL . PHP_TAB . PHP_TAB, $downAlterDef)) . PHP_EOL . PHP_TAB . PHP_TAB . str_replace('; ', ';' . PHP_EOL . PHP_TAB . PHP_TAB, $downIndexDef) . PHP_EOL . PHP_TAB . PHP_TAB . 'return $this->alter($argDBO, $alter, $index);' . PHP_EOL . PHP_TAB . '}'. PHP_EOL;
			}

			// 現在の定義でマイグレーションファイルを生成する
			$migrationClassName = self::_createMigrationClassName($argTblName).'_'.$migrationIdx.'_'.$modelHash;
			$migrationClassDef = 'class '.$migrationClassName.' extends MigrationBase {' . PHP_EOL . PHP_EOL . PHP_TAB . 'public $migrationIdx = "' . $migrationIdx . '";' . PHP_EOL . PHP_EOL . PHP_TAB . 'public $tableName = "' . strtolower($argTblName) . '";' . PHP_EOL . PHP_TAB . 'public $tableComment = "' . $tableComment . '";' . PHP_EOL . PHP_TAB . 'public $tableEngine = "' . $tableEngine . '";' . PHP_EOL . PHP_EOL . PHP_TAB . 'public static $migrationHash = "' . $modelHash . '";' . $migrationClassDef . '}';
			$path = getAutoMigrationPath().$argDBO->dbidentifykey.'.'.$migrationClassName.'.migration.php';
			@file_put_contents($path, '<?php' . PHP_EOL . PHP_EOL . $migrationClassDef . PHP_EOL . PHP_EOL . '?>');
			@chmod($path, 0666);

			// 生成した場合は、生成環境のマイグレーションが最新で、適用済みと言う事になるので
			// マイグレーション済みファイルを生成し、新たにマイグレーション一覧に追記する
			@file_put_contents_e(getAutoMigrationPath().$argDBO->dbidentifykey.'.all.migrations', $migrationClassName.PHP_EOL, FILE_APPEND);
			@file_put_contents_e(getAutoMigrationPath().$argDBO->dbidentifykey.'.dispatched.migrations', $migrationClassName.PHP_EOL, FILE_APPEND);
			@chmod(getAutoMigrationPath().$argDBO->dbidentifykey.'.all.migrations', 0666);
			@chmod(getAutoMigrationPath().$argDBO->dbidentifykey.'.dispatched.migrations', 0666);
			$executed[$argTblName] = TRUE;
			logging('migration! '.$migrationClassName, 'migration');
		}
		return TRUE;
	}

	private static function _getDiff($argDBO, $argTblName){
		// 実行可能なmigrationの一覧を取得
		$migrationes = array();
		$migrationesFilePath = getAutoMigrationPath().$argDBO->dbidentifykey.'.all.migrations';
		if(TRUE === file_exists($migrationesFilePath) && TRUE === is_file($migrationesFilePath)){
			// 適用済みのmigratione一覧を取得
			$handle = fopen($migrationesFilePath, 'r');
			while(($line = fgets($handle, 4096)) !== FALSE){
				$migrationes[] = trim($line);
			}
		}
		logging('dispatche all migrations=', 'migration');
		logging($migrationesFilePath, 'migration');
		logging($migrationes, 'migration');
		$dispatchedMigrationesFilePath = getAutoMigrationPath().$argDBO->dbidentifykey.'.dispatched.migrations';
		$dispatchedMigrationes = array();
		if(TRUE === file_exists($dispatchedMigrationesFilePath) && TRUE === is_file($dispatchedMigrationesFilePath)){
			// 適用済みのmigratione一覧を取得
			$handle = fopen($dispatchedMigrationesFilePath, 'r');
			while(($line = fgets($handle, 4096)) !== FALSE){
				$dispatchedMigrationes[] = trim($line);
			}
		}
		$dispatchedMigrationesStr = implode(':', $dispatchedMigrationes);
		logging('dispatched migrations='.$dispatchedMigrationesStr, 'migration');
		self::$_lastMigrationHash = NULL;
		$diff = array();
		// 未適用の差分を探す
		for($migIdx=0; $migIdx < count($migrationes); $migIdx++){
			if(strlen($migrationes[$migIdx]) > 0){
				if('' === $dispatchedMigrationesStr){
					$diff[] = $migrationes[$migIdx];
				}
				elseif(FALSE === strpos($dispatchedMigrationesStr, $migrationes[$migIdx])){
					// 数が足りていないので、実行対象
					$diff[] = $migrationes[$migIdx];
				}
				// テーブル指定があった場合は、最後の該当テーブルに対するマイグレーションファイルを特定しておく
				if(NULL !== $argTblName){
					$migrationName = strtolower(ORMapper::getGeneratedModelName($argTblName));
					logging('check exists migration='. strtolower($migrationes[$migIdx]) . ' & ' . $migrationName.'migration_', 'migration');
					if(FALSE !== strpos(strtolower($migrationes[$migIdx]), $migrationName.'migration_')){
						self::$_lastMigrationHash = $migrationes[$migIdx];
						logging('self::$_lastMigrationHash='.$migrationes[$migIdx], 'migration');
					}
				}
			}
		}
		return $diff;
	}

	private static function _createMigrationClassName($argTblName){
		$migrationName = ORMapper::getGeneratedModelName($argTblName);
		if((strlen($migrationName) - (strlen('migration'))) === strpos(strtolower($migrationName), 'migration')){
			// 何もしない
		}
		else{
			$migrationName = $migrationName."Migration";
		}
		return $migrationName;
	}
}

?>
