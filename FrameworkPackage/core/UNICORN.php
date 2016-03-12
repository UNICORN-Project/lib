<?php

/*
 * UNICORN Framework
 *
 * @author saimushi
 * @website http://unicorn-project.github.io
 * @copyright (C) 2014 saimushi All Rights Reserved.
 */


// このファイルのファイル名を持ってしてフレームワークの名称と位置づける
// 名前は変えてOKです
function corefilename(){
	return strtoupper(substr(basename(__FILE__), 0, strpos(basename(__FILE__), '.')));
}

// この手続上でコアファイル名(フレームワーク名)へのアクセスが何度も出てくるので変数に入れておく
$corefilename = corefilename();

// 定数定義
define('PHP_TAB', "\t");
define('PHP_CR', "\r");
define('PHP_LF', "\n");

// XXX config.xmlを別パスに設置したい場合は以下の定数を定義すればOK(絶対パスでの定義)
//define($corefilename . '_CONFIG_XML_PATH', dirname(__FILE__).'/' . $corefilename . '.config.xml');
// XXX 強制終了時に閉鎖処理を入れたい場合は以下の定数に関数名を指定する事！引数は渡せないので工夫する事！
//define($corefilename . '_ERROR_FINALIS', 'finalize');

// XXX 各種フラグファイルを別パスに設置したい場合は以下の定数を定義すればOK(絶対パスでの定義)
// 開発環境自動判別フラグのセット(高速化用静的ファイル変換)のセット
//define($corefilename . '_AUTO_STAGE_CHECK_ENABLED', dirname(dirname(__FILE__)).'/.autostagecheck');
// 自動ジェネレート(高速化用静的ファイル変換)のセット
//define($corefilename . '_AUTO_GENERATE_ENABLED', dirname(dirname(__FILE__)).'/.autogenerate');
// 自動ジェネレート(高速化用静的ファイル変換)のセット
//define($corefilename . '_AUTO_MIGRATE_ENABLED', dirname(dirname(__FILE__)).'/.automigrate');
// ローカル環境フラグのセット
//define($corefilename . '_WORKSPACE_LOCAL_ENABLED', dirname(dirname(__FILE__)).'/.local');
// 開発環境フラグのセット
//define($corefilename . '_WORKSPACE_DEV_ENABLED', dirname(dirname(__FILE__)).'/.dev');
// テスト環境(テスト用凍結環境)フラグのセット
//define($corefilename . '_WORKSPACE_TEST_ENABLED', dirname(dirname(__FILE__)).'/.test');
// ステージング環境フラグのセット
//define($corefilename . '_WORKSPACE_STAGING_ENABLED', dirname(dirname(__FILE__)).'/.staging');
// デバッグモードのセット
//define($corefilename . '_DEBUG_MODE_ENABLED', dirname(dirname(__FILE__)).'/.debug');
// エラーレポートのセット
//define($corefilename . '_ERROR_REPORT_ENABLED', dirname(dirname(__FILE__)).'/.error_report');

/*------------------------------ 根幹関数定義 ココから ------------------------------*/

/**
 * クラス使用時、ロードされてないと実行される
 * @param string $className
*/
function _autoloadFramework($className, $argSetAutoloadEnabled=NULL){
	static $_autoloaderEnabled = TRUE;
	if (NULL !== $argSetAutoloadEnabled){
		$_autoloaderEnabled = $argSetAutoloadEnabled;
		// オートロードじゃないのでセットして終わり
		return;
	}
	if (TRUE !== $_autoloaderEnabled){
		return;
	}
	// namespaceだったらフレームワークのpackeageロードで無いことが確定なので無視する
	// XXX PMA_(PHPMyAdmin)も無視！
	if(FALSE === strpos($className, '\\') && FALSE === strpos($className, 'PMA_') && FALSE === strpos($className, 'PHPExcel')){
		// クラスが既に利用かのうかどうか
		if(!class_exists($className, FALSE)){
			// class_existsから呼びだされたのかの判定
			$class_exists_called = FALSE;
			$dbg = debug_backtrace();
			if(isset($dbg[2]) && isset($dbg[2]['function']) && 'class_exists' == $dbg[2]['function']){
				$class_exists_called = TRUE;
			}
			// バックトレースは重いのでとっとと捨てる
			unset($dbg);
			loadModule($className, $class_exists_called);
		}
	}
}

function autoloadUnregisterFramework(){
	_autoloadFramework(NULL, FALSE);
	// オートローダへの登録を解除(他のフレームワーク向け)
	spl_autoload_unregister('_autoloadFramework');
}
function autoloadRegisterFramework(){
	_autoloadFramework(NULL, TRUE);
	// オートローダへ登録
	spl_autoload_register('_autoloadFramework');
}
// オートローダへ登録
autoloadRegisterFramework();

$functions = <<<_METHODS_
if (!function_exists('filemtime_ip')){
	/**
	 * filemtimeでinclude_pathを走査する
	 * ※フレームワーク内で使用しているので注意！
	 */
	function filemtime_ip(\$argFilePath){
		\$time = (int)@filemtime(\$argFilePath);
		if(0 === \$time){
			\$includePaths = explode(PATH_SEPARATOR, get_include_path());
			for(\$includePathNum=0; \$includePathNum < count(\$includePaths); \$includePathNum++){
				\$time = (int)@filemtime(\$includePaths[\$includePathNum].\$argFilePath);
				if(0 < \$time){
					BREAK;
				}
			}
		}
		return \$time;
	}
}

if (!function_exists('file_exists_ip')){
	/**
	 * file_existsでinclude_pathを走査する
	 * ※フレームワーク内で使用しているので注意！
	 */
	function file_exists_ip(\$argFilePath){
		\$exists = @file_exists(\$argFilePath);
		if(TRUE !== \$exists){
			\$includePaths = explode(PATH_SEPARATOR, get_include_path());
			for(\$includePathNum=0; \$includePathNum < count(\$includePaths); \$includePathNum++){
				\$exists = @file_exists(\$includePaths[\$includePathNum].\$argFilePath);
				if(TRUE === \$exists){
					BREAK;
				}
			}
		}
		return \$exists;
	}
}
_METHODS_;

// オートジェネレートキャッシュ用に関数定義を文字列として取っておく
define('FILE_CHECK_GENERIC_FUNCTIONS', $functions);
eval($functions);

/**
 * HAPPYBORN OOP/AOP向けフレームワークのほぼ本体
 * クラス・関数の自動走査・自動読み込み・自動生成を行う
 * @param string モジュール本体を探すヒント文字列(URI) or .区切り文字 (混在はNG /がある場合、URIとして優先して処理される）
 * @param bool クラスの存在チェックをからめたコールの場合は、sysエラーで終了せず、エラーをretrunしてあげる
 * @return return instance名を返す
*/
function loadModule($argHint, $argClassExistsCalled = FALSE){

	// オートジェネレートフラグの取得
	$autoGenerateFlag = getAutoGenerateEnabled();
	// パッケージヒント文字列
	$hintPath = str_replace('/', '.', $argHint);
	// パッケージ定義の初期化
	$pkConfXMLs = _initFramework(TRUE);

	// オートジェネレートチェック
	if(TRUE === $autoGenerateFlag){
		$generatedIncFileName = getAutoGeneratedPath().$hintPath.'.generated.inc.php';
		if(FALSE === resolveUnlinkAutoGeneratedFile($generatedIncFileName)){
			// ジェネレートされたファイルを読み込んで終了
			return TRUE;
		}
	}

	// パッケージ名に該当するノードが格納されたパッケージXML格納用
	$pkConfXML = NULL;
	// パッケージ名
	$packageName = NULL;
	// 代表クラス名 本来は必要ないので空
	// ※パッケージ名と実際にパッケージ読み込み後に利用するクラス名が違う場合に利用される
	// ※一つのリンクされたファイルの中に複数のクラス定義がある場合等
	$className = NULL;

	// defaultパッケージの走査は最後 or 明示の時だけに絞る
	$defaultPackageFlag = FALSE;

	// defaultパッケージの明示指定があるかどうか
	$matches = NULL;
	if(preg_match('/^default\.(.+)/', $argHint, $matches)){
		// defaultパッケージを走査対象のパッケージとする
		$defaultPackageFlag = TRUE;
		$packageName = $matches[1];
		$pkConfXML = $pkConfXMLs[0]['dom'];
	}
	else{
		// Hintパスからパッケージを当てる
		if(!is_file($argHint)){
			// 読み込み済みの$pkConfXMLの数分だけ処理
			for($pkConfXMLCnt = 0; count($pkConfXMLs) > $pkConfXMLCnt; $pkConfXMLCnt++){
				$pkConfXML = $pkConfXMLs[$pkConfXMLCnt]['dom'];
				if(isset($pkConfXML->{$argHint})){
					// ※見つかった！
					// Hintのパス情報そのままの定義があればそれを使う
					$packageName = $argHint;
					BREAK;
				}
				// ※まだ見つからない！
				// パッケージXMLの子ノード総当りでパターンマッチ検索
				foreach(get_object_vars($pkConfXML) as $key => $ChildNode){
					if(isset($ChildNode->pattern) && preg_match('/'.$ChildNode->pattern.'/', $argHint)){
						// ※見つかった！
						$packageName = $key;
						// ルートのループも含めてスキップ
						BREAK 2;
					}
				}
				// ※まだ見つからない！
				// Hintのパス情報からたどる
				$pathHints = explode('/', $argHint);
				if(count($pathHints) == 0){
					$pathHints = explode('.', $argHint);
				}
				$pathHintMaxCnt = count($pathHints);
				// XXX コレなーんか処理おかしい気がする・・・後ろから回すべきじゃね？？
				// hintの長い状態から上を徐々に削って短くし、完全一致する場所を探す
				for($pathHintCnt = 0; $pathHintMaxCnt > $pathHintCnt; $pathHintCnt++){
					$packageName = implode('.', $pathHints);
					if(isset($pkConfXML->{$packageName})){
						// ※見つかった！
						// ルートのループも含めてスキップ
						BREAK 2;
					}
					// 見つからないのでやり直し
					$packageName = NULL;
					unset($pathHints[$pathHintCnt]);
				}
			}
		}
		// ※まだ見つからない！
		if(NULL === $packageName){
			// ここまできてなかったら仮でdefaultパッケージを走査対象のパッケージとする
			$defaultPackageFlag = TRUE;
			$packageName = $argHint;
			$pkConfXML = $pkConfXMLs[0]['dom'];
		}
	}

	// 実際にモジュールを設定したパッケージから設定内容を取得して読み込みを行う
	if(TRUE === $defaultPackageFlag){
		// defaultパッケージを捜査
		_loadDefaultModule($argHint, $argClassExistsCalled, $packageName);
		$className = NULL;
	}
	else{
		// オートジェネレートされるファイルを初期化
		if(TRUE === $autoGenerateFlag){
			// 空でジェネレートファイルを生成
			@file_put_contents($generatedIncFileName, '');
		}
		// 明示的な指定がある場合の捜査
		// パッケージ定義の中に、複数のlinkが設定されていたら、そのlink数分処理をループ
		// linkを全て読み込む
		for($packagePathCnt = 0, $errorCnt = 0; count($pkConfXML->{$packageName}->link) > $packagePathCnt; $packagePathCnt++){
			$fileget = FALSE;
			$addmethod = FALSE;
			$rename = FALSE;
			// メソッドを追加する処理
			if(0 < @strlen($pkConfXML->{$packageName}->link[$packagePathCnt]->attributes()->addmethod)){
				$fileget = TRUE;
				$addmethod = TRUE;
			}
			// クラス名をリネームする処理
			if(0 < @strlen($pkConfXML->{$packageName}->link[$packagePathCnt]->attributes()->renameto) && 0 < @strlen($pkConfXML->{$packageName}->link[$packagePathCnt]->attributes()->renamefrom)){
				$fileget = TRUE;
				$rename = TRUE;
			}
			// ファイルを変数に読み込むかどうか
			if(TRUE === $fileget){
				// 変数に読み込む(addmethodかrenmae処理が予定されている)
				$classdef = @file_get_contents($pkConfXML->{$packageName}->link[$packagePathCnt], TRUE);
				if(strlen($classdef) == 0){
					// 読み込みに失敗した場合、パッケージがハズレだったので_loadDefaultModuleでdefault定義パッケージを走査して貰ってみる事にする
					$subPackageName = $pkConfXML->{$packageName}->link[$packagePathCnt];
					if(preg_match('/^default\.(.+)/', $subPackageName, $matches)){
						$subPackageName = $matches[1];
					}
					// _loadDefaultModuleの再帰処理による自動解決を試みる
					$classdefs = _loadDefaultModule($argHint, $argClassExistsCalled, $subPackageName, TRUE);
					// 読み込みに成功したクラス定義を変数に詰め直す
					// 成功していない場合、ここまで処理が到達しない
					$classdef = $classdefs['classdef'];
					$classPath = $classdefs['classpath'];
				}
				else{
					// 読み込みに成功したクラス定義を変数に詰め直す
					$classPath = $pkConfXML->{$packageName}->link[$packagePathCnt];
				}
			}
			else{
				// ファイルはインクルードで処理する
				if(FALSE === @include_once($pkConfXML->{$packageName}->link[$packagePathCnt])){
					// includeに場合、パッケージがハズレだったので_loadDefaultModuleでdefault定義パッケージを走査して貰ってみる事にする
					$subPackageName = $pkConfXML->{$packageName}->link[$packagePathCnt];
					if(preg_match('/^default\.(.+)/', $subPackageName, $matches)){
						$subPackageName = $matches[1];
					}
					// loadModuleの再帰処理による自動解決を試みる
					_loadDefaultModule($argHint, $argClassExistsCalled, $subPackageName);
				}
				else{
					// ジェネレート処理
					if(TRUE === $autoGenerateFlag){
						generateIncCache($generatedIncFileName, $pkConfXML->{$packageName}->link[$packagePathCnt]);
					}
				}
			}
			// methodの動的追加を実行
			if(TRUE === $addmethod){
				if(!isset($classBuffer)){
					ob_start();
					echo $classdef;
					$classBuffer = ob_get_clean();
				}
				// 追加するメソッド定義を探す
				$addmethoddef = $pkConfXML->{$packageName}->link[$packagePathCnt]->attributes()->addmethod;
				if(FALSE !== strpos($addmethoddef, ',')){
					$addmethoddefs = explode(',', $addmethoddef);
					for($addmethoddefIndex=0; count($addmethoddefs) > $addmethoddefIndex; $addmethoddefIndex++){
						if(isset($pkConfXML->{$packageName}->{trim($addmethoddefs[$addmethoddefIndex])}) && isset($pkConfXML->{$packageName}->{trim($addmethoddefs[$addmethoddefIndex])}->attributes()->targetclass) && strlen($pkConfXML->{$packageName}->{trim($addmethoddefs[$addmethoddefIndex])}->attributes()->targetclass) > 0){
							$targetClassName = $pkConfXML->{$packageName}->{trim($addmethoddefs[$addmethoddefIndex])}->attributes()->targetclass;
							$addmethod = (string)$pkConfXML->{$packageName}->{trim($addmethoddefs[$addmethoddefIndex])};
							$classBuffer = preg_replace('/(class|abstract|interface)\s+?'.trim($targetClassName).'(.*)?\{/', '$1 '. trim($targetClassName) . '\2 { '.$addmethod, $classBuffer);
						}
						else{
							_systemError('class method add notfound node \''.$packageName.'.'.trim($addmethoddefs[$addmethoddefIndex]).' or undefined attribute \'targetclass\'');
						}
					}
				}
				else{
					if(isset($pkConfXML->{$packageName}->{$addmethoddef}) && isset($pkConfXML->{$packageName}->{trim($addmethoddef)}->attributes()->targetclass) && strlen($pkConfXML->{$packageName}->{trim($addmethoddef)}->attributes()->targetclass) > 0){
						$targetClassName = $pkConfXML->{$packageName}->{trim($addmethoddef)}->attributes()->targetclass;
						$addmethod = (string)$pkConfXML->{$packageName}->{trim($addmethoddef)};
						$classBuffer = preg_replace('/(class|abstract|interface)\s+?'.trim($targetClassName).'(.*)?\{/', '$1 '. trim($targetClassName) . '\2 { '.$addmethod, $classBuffer);
					}
					else{
						_systemError('class method add notfound node \''.$packageName.'.'.trim($addmethoddef).' or undefined attribute \'targetclass\'');
					}
				}
			}
			// クラス名リネームの実行
			// XXX 処理の順番に注意！！先にaddmethodを処理。renameした後だとクラス名が変わっていてaddにしくじるので
			if(TRUE === $rename){
				if(!isset($classBuffer)){
					ob_start();
					echo $classdef;
					$classBuffer = ob_get_clean();
				}
				// リネーム
				$renametoClassName = $pkConfXML->{$packageName}->link[$packagePathCnt]->attributes()->renameto;
				$renamefromClassName = $pkConfXML->{$packageName}->link[$packagePathCnt]->attributes()->renamefrom;
				if(FALSE !== strpos($renametoClassName, ',')){
					$renametoClassName = explode(',', $renametoClassName);
					$renamefromClassName = explode(',', $renamefromClassName);
					if(!(is_array($renametoClassName) && is_array($renamefromClassName) && count($renametoClassName) == count($renamefromClassName))){
						_systemError('class rename error! renameto-from count missmatch renameto-count='.count($renametoClassName).' renamefrom-count='.count($renamefromClassName));
					}
					for($renameIndex=0; count($renamefromClassName)>$renameIndex; $renameIndex++){
						$classBuffer = preg_replace('/(class|abstract|interface)\s+?'.trim($renamefromClassName[$renameIndex]).'(\s|\{|\r|\n)/', '\1 '. trim($renametoClassName[$renameIndex]), $classBuffer);
					}
				}
				else{
					$classBuffer = preg_replace('/(class|abstract|interface)\s+?'.trim($renamefromClassName).'/', '\1 '. $renametoClassName, $classBuffer);
				}
			}
			// 定義の動的変更を実行
			if(isset($classBuffer)){
				// PHPの開始タグがあるとコケるので消す
				$classBuffer = preg_replace('/^<\?(php){0,1}(\s|\t)*?(\r\n|\r|\n)/s', '', $classBuffer);
				// PHPの終了タグがあるとコケるので消す
				$classBuffer = preg_replace('/(\r\n|\r|\n)\?>(\r\n|\r|\n){0,1}$/s', '', $classBuffer);
				eval($classBuffer);
				$classCheck = '';
				$matches = NULL;
				if(preg_match('/(class|abstract|interface)\s+?([^\s\t\r\n\{]+)/', $classBuffer, $matches) && is_array($matches) && isset($matches[2]) && strlen($matches[2]) > 0){
					$classCheck = $matches[2];
				}
				// ジェネレート処理
				if(TRUE === $autoGenerateFlag){
					generateClassCache($generatedIncFileName, $classPath, $classBuffer, $classCheck);
				}
				unset($classdef);
				unset($classBuffer);
			}
			// クラス名をマッピングする処理
			if(0 < @strlen($pkConfXML->{$packageName}->link[$packagePathCnt]->attributes()->mapto) && 0 < @strlen($pkConfXML->{$packageName}->link[$packagePathCnt]->attributes()->mapfrom)){
				$classCheck = '';
				$maptoClassName = $pkConfXML->{$packageName}->link[$packagePathCnt]->attributes()->mapto;
				$mapfromClassName = $pkConfXML->{$packageName}->link[$packagePathCnt]->attributes()->mapfrom;
				if(FALSE !== strpos($maptoClassName, ',')){
					$maptoClassName = explode(',', $maptoClassName);
					$mapfromClassName = explode(',', $mapfromClassName);
					if(!(is_array($maptoClassName) && is_array($mapfromClassName) && count($maptoClassName) == count($mapfromClassName))){
						_systemError('class map error! mapto-from count missmatch mapto-count=' . count($maptoClassName) . ' mapfrom-count=' . count($mapfromClassName));
					}
					$mapClass = array();
					for($mapIndex=0;count($maptoClassName)>$mapIndex; $mapIndex++){
						$mapClass[] = 'class ' . $maptoClassName[$mapIndex] . ' extends ' . $mapfromClassName[$mapIndex].'{}';
					}
					$mapClass = implode('', $mapClass);
					$classCheck = ' && !class_exists(\'' . $maptoClassName[0] . '\', FALSE)';
				}
				else{
					$mapClass = 'class ' . $maptoClassName . ' extends ' . $mapfromClassName . '{}';
					$classCheck = ' && !class_exists(\'' . $maptoClassName . '\', FALSE)';
				}
				// マップクラス生成
				eval($mapClass);
				// ジェネレート処理
				if(TRUE !== $fileget && TRUE === $autoGenerateFlag){
					@file_put_contents_e($generatedIncFileName, '<?php' . PHP_EOL . 'if(FALSE === $unlink' . $classCheck . '){ ' . $mapClass . ' }' . PHP_EOL . '?>', FILE_APPEND);
					@chmod($generatedIncFileName, 0666);
				}
				unset($mapClass);
			}
		}
		// 代表クラス名が定義されているかどうか
		// パッケージ名と実際に利用しようとしているクラス名が異なる場合の定義はココを通る
		if(isset($pkConfXML->{$packageName}->class)){
			$className = $pkConfXML->{$packageName}->class;
			// そのパッケージの代表クラスの読み込みが成功しているかどうかをチェック
			if(!class_exists($className, FALSE)){
				if(FALSE === $argClassExistsCalled){
					// クラスが存在しないエラー
					_systemError('not found class ' . $className . ' on ' . $pkConfXML->{$packageName}->link[$packagePathCnt] . '!! Please check default path config.' . PHP_EOL . str_replace(PATH_SEPARATOR, PHP_EOL, get_include_path()));
				}
				return FALSE;
			}
		}
	}
	// 代表クラス名を返して終了
	return (string) $className;
}

/**
 * デフォルト定義されたパッケージ走査に従ってパッケージを探し読み込みをする(内部関数)
 */
function _loadDefaultModule($argHint, $argClassExistsCalled = FALSE, $argPackageName='', $argFileGetContentsEnabled = FALSE){

	if(!function_exists('_loadMatchDefaultNodeModule')){
		/**
		 * ローカル関数:指定されたデフォルトノードの該当のパスからパッケージを探してロードを試みる
		 * ※ただ手続きだと見難いので関数化しただけ
		 */
		function _loadDefaultNodeModule($argNode, $argHint, $argClassExistsCalled = FALSE, $argPackageName='', $argFileGetContentsEnabled = FALSE){
			$loaded = FALSE;
			// パッケージヒント文字列
			$hintPath = str_replace('/', '.', $argHint);
			// 自動ジェネレートフラグの取得
			$autoGenerateFlag = getAutoGenerateEnabled();
			if(TRUE === $autoGenerateFlag){
				$generatedIncFileName = getAutoGeneratedPath().$hintPath.'.generated.inc.php';
			}
			// パッケージ定義の初期化
			$pkConfXMLs = _initFramework(TRUE);
			for($packageCnt = 0; count($pkConfXMLs) > $packageCnt; $packageCnt++){
				$pkConfXML = $pkConfXMLs[$packageCnt]['dom'];
				if(isset($pkConfXML->default) && isset($pkConfXML->default->{$argNode})){
					for($packagePathCnt = 0, $errorCnt = 0; count($pkConfXML->default->{$argNode}) > $packagePathCnt; $packagePathCnt++){
						if(TRUE === $argFileGetContentsEnabled){
							$file = @file_get_contents($pkConfXML->default->{$argNode}[$packagePathCnt].'/'.$argPackageName.$pkConfXML->default->{$argNode}[$packagePathCnt]->attributes()->suffix, TRUE);
							if(strlen($file) > 0){
								$path = $pkConfXML->default->{$argNode}[$packagePathCnt].'/'.$argPackageName.$pkConfXML->default->{$argNode}[$packagePathCnt]->attributes()->suffix;
								$loaded = TRUE;
								BREAK 2;
							}
							$file = @file_get_contents($pkConfXML->default->{$argNode}[$packagePathCnt].'/'.$argPackageName, TRUE);
							if(strlen($file) > 0){
								$path = $pkConfXML->default->{$argNode}[$packagePathCnt].'/'.$argPackageName;
								$loaded = TRUE;
								BREAK 2;
							}
						}
						else{
							if(FALSE !== @include_once($pkConfXML->default->{$argNode}[$packagePathCnt].'/'.$argPackageName.$pkConfXML->default->{$argNode}[$packagePathCnt]->attributes()->suffix)){
								$loaded = TRUE;
								// ジェネレート処理
								if(TRUE === $autoGenerateFlag){
									generateIncCache($generatedIncFileName, $pkConfXML->default->{$argNode}[$packagePathCnt].'/'.$argPackageName.$pkConfXML->default->{$argNode}[$packagePathCnt]->attributes()->suffix);
								}
								BREAK 2;
							}
							if(FALSE !== @include_once($pkConfXML->default->{$argNode}[$packagePathCnt].'/'.$argPackageName)){
								$loaded = TRUE;
								// ジェネレート処理
								if(TRUE === $autoGenerateFlag){
									generateIncCache($generatedIncFileName, $pkConfXML->default->{$argNode}[$packagePathCnt].'/'.$argPackageName);
								}
								BREAK 2;
							}
						}
					}
				}
			}
			if(isset($file) && isset($path)){
				// 読み込み結果を返して正常終了とする
				return array('classdef' => $file, 'classpath' => $path);
			}
			return $loaded;
		};

		/**
		 * ローカル関数:指定されたデフォルトノードにマッチしたパッケージヒントがマッチしていればそのノードの該当のパスからパッケージを探してロードを試みる
		 * ※ただ手続きだと見難いので関数化しただけ
		 */
		function _loadMatchDefaultNodeModule($argNode, $argHint, $argClassExistsCalled = FALSE, $argPackageName='', $argFileGetContentsEnabled = FALSE){
			$loaded = NULL;
			$matches = NULL;
			if(preg_match('/^' . $argNode . '\.(.+)/', $argPackageName, $matches)){
				$argPackageName = $matches[1];
				$loaded = _loadDefaultNodeModule($argNode, $argHint, $argClassExistsCalled, $argPackageName, $argFileGetContentsEnabled);
				if(FALSE === $loaded && FALSE === $argClassExistsCalled){
					_systemError('not found ' . $argNode . ' ' . $argPackageName . '!! Please check default path config.' . PHP_EOL . str_replace(PATH_SEPARATOR, PHP_EOL, get_include_path()));
				}
			}
			return $loaded;
		};
	}

	// resがNULLの間は走査が続く
	$res = _loadMatchDefaultNodeModule('controlmain', $argHint, $argClassExistsCalled, $argPackageName, $argFileGetContentsEnabled);
	if(NULL !== $res){
		// ※マッチしたのに無かったと言う場合もココを通って異常終了する！
		return $res;
	}
	$res = _loadMatchDefaultNodeModule('modelmain', $argHint, $argClassExistsCalled, $argPackageName, $argFileGetContentsEnabled);
	if(NULL !== $res){
		// ※マッチしたのに無かったと言う場合もココを通って異常終了する！
		return $res;
	}
	$res = _loadMatchDefaultNodeModule('consolemain', $argHint, $argClassExistsCalled, $argPackageName, $argFileGetContentsEnabled);
	if(NULL !== $res){
		// ※マッチしたのに無かったと言う場合もココを通って異常終了する！
		return $res;
	}
	$res = _loadMatchDefaultNodeModule('abstract', $argHint, $argClassExistsCalled, $argPackageName, $argFileGetContentsEnabled);
	if(NULL !== $res){
		// ※マッチしたのに無かったと言う場合もココを通って異常終了する！
		return $res;
	}
	$res = _loadMatchDefaultNodeModule('interface', $argHint, $argClassExistsCalled, $argPackageName, $argFileGetContentsEnabled);
	if(NULL !== $res){
		// ※マッチしたのに無かったと言う場合もココを通って異常終了する！
		return $res;
	}
	$res = _loadMatchDefaultNodeModule('implement', $argHint, $argClassExistsCalled, $argPackageName, $argFileGetContentsEnabled);
	if(NULL !== $res){
		// ※マッチしたのに無かったと言う場合もココを通って異常終了する！
		return $res;
	}
	// ※ここまで来てしまったらdefault定義パスを全走査
	$res = _loadDefaultNodeModule('implement', $argHint, $argClassExistsCalled, $argPackageName, $argFileGetContentsEnabled);
	if(FALSE !== $res){
		// ※無事に見つかって正常終了！
		return $res;
	}
	$res = _loadDefaultNodeModule('link', $argHint, $argClassExistsCalled, $argPackageName, $argFileGetContentsEnabled);
	if(FALSE !== $res){
		// ※無事に見つかって正常終了！
		return $res;
	}
	$res = _loadDefaultNodeModule('abstract', $argHint, $argClassExistsCalled, $argPackageName, $argFileGetContentsEnabled);
	if(FALSE !== $res){
		// ※無事に見つかって正常終了！
		return $res;
	}
	$res = _loadDefaultNodeModule('interface', $argHint, $argClassExistsCalled, $argPackageName, $argFileGetContentsEnabled);
	if(FALSE !== $res){
		// ※無事に見つかって正常終了！
		return $res;
	}

	// ※それでもなければインクルードパスを信じてみる！！
	if(TRUE === $argFileGetContentsEnabled){
		$file = @file_get_contents($argPackageName, TRUE);
		if(strlen($file) == 0){
			// 拡張子足して試してみる
			$file = @file_get_contents($argPackageName.'.php', TRUE);
			if(strlen($file) == 0){
				// 何をやってもDefaultパッケージからは見つけられなかった
				if(FALSE === $argClassExistsCalled){
					_systemError('not found package ' . $argPackageName . '!! Please check default path config.' . PHP_EOL . str_replace(PATH_SEPARATOR, PHP_EOL, get_include_path()));
				}
				return FALSE;
			}
			// パッケージ名を決定したものに変更
			$argPackageName = $argPackageName.'.php';
		}
		$path = $argPackageName;
	}
	else{
		if(FALSE === @include_once($argPackageName)){
			// 拡張子足して試してみる
			if(FALSE === @include_once($argPackageName.'.php')){
				if(FALSE === $argClassExistsCalled){
					// 何をやってもDefaultパッケージからは見つけられなかった
					_systemError('not found package ' . $argPackageName . '!! Please check default path config.' . PHP_EOL . str_replace(PATH_SEPARATOR, PHP_EOL, get_include_path()));
				}
				return FALSE;
			}
			// パッケージ名を決定したものに変更
			$argPackageName = $argPackageName.'.php';
		}
		// インクルードに成功したので、ジェネレート処理
		if(TRUE === getAutoGenerateEnabled()){
			generateIncCache(getAutoGeneratedPath().str_replace('.php', '.generated.inc.php', $argPackageName), $argPackageName);
		}
	}

	if(isset($file) && isset($path)){
		// 読み込み結果を返して正常終了とする
		return array('classdef' => $file, 'classpath' => $path);
	}
	return TRUE;
}


/**
 * configの読み込みとconfigureクラスの定義を実行する
 */
function loadConfig($argConfigPath){

	// 自動ジェネレートフラグの取得
	$autoGenerateFlag = getAutoGenerateEnabled();

	static $autoMigrationFlag = NULL;
	static $localFlag = NULL;
	static $devFlag = NULL;
	static $testFlag = NULL;
	static $stagingFlag = NULL;
	static $debugFlag = NULL;
	static $errorReportFlag = NULL;
	static $loggingFlag = NULL;
	static $regenerateFlag = NULL;

	if(NULL === $errorReportFlag){
		// 自動マイグレート設定フラグのセット
		$autoMigrationFlag = getAutoMigrationEnabled();
		// ローカル環境フラグのセット
		$localFlag = getLocalEnabled();
		// 開発環境フラグのセット
		$devFlag = getDevelopmentEnabled();
		// テスト環境(テスト用凍結環境)フラグのセット
		$testFlag = getTestEnabled();
		// ステージング環境フラグのセット
		$stagingFlag = getStagingEnabled();
		// デバッグモードフラグのセット
		$debugFlag = getDebugEnabled();
		// エラーレポートフラグのセット
		$errorReportFlag = getErrorReportEnabled();
		// ロギングフラグのセット
		$loggingFlag = getLoggingEnabled();
	}

	if(TRUE === $autoGenerateFlag){
		if(is_file($argConfigPath)){
			// フラグメントキャッシュの更新が有れば、コンフィグファイルを強制再読み込みする為のチェック
			if(NULL === $regenerateFlag){
				$flagcacheFileName = getAutoGeneratedPath().'flagcache.php';
				if(file_exists($flagcacheFileName)){
					require_once $flagcacheFileName;
					if(TRUE !== (TRUE === isset($flagchaces['autoMigrationFlag']) && (int)$autoMigrationFlag == $flagchaces['autoMigrationFlag'])){
						$regenerateFlag = TRUE;
					}
					if(!isset($flagchaces['localFlag']) || (int)$localFlag != $flagchaces['localFlag']){
						$regenerateFlag = TRUE;
					}
					if(!isset($flagchaces['devFlag']) || (int)$devFlag != $flagchaces['devFlag']){
						$regenerateFlag = TRUE;
					}
					if(!isset($flagchaces['testFlag']) || (int)$testFlag != $flagchaces['testFlag']){
						$regenerateFlag = TRUE;
					}
					if(!isset($flagchaces['stagingFlag']) || (int)$stagingFlag != $flagchaces['stagingFlag']){
						$regenerateFlag = TRUE;
					}
					if(!isset($flagchaces['debugFlag']) || (int)$debugFlag != $flagchaces['debugFlag']){
						$regenerateFlag = TRUE;
					}
					if(!isset($flagchaces['errorReportFlag']) || (int)$errorReportFlag != $flagchaces['errorReportFlag']){
						$regenerateFlag = TRUE;
					}
					if(!isset($flagchaces['loggingFlag']) || (int)$loggingFlag != $flagchaces['loggingFlag']){
						$regenerateFlag = TRUE;
					}
				}
				else{
					$regenerateFlag = TRUE;
				}
				if(TRUE === $regenerateFlag){
					$flagchaceBody = '$flagchaces = array(\'autoMigrationFlag\'=>' . (int)$autoMigrationFlag . ', \'localFlag\'=>' . (int)$localFlag . ', \'devFlag\'=>' . (int)$devFlag . ', \'testFlag\'=>' . (int)$testFlag . ', \'stagingFlag\'=>' . (int)$stagingFlag . ', \'debugFlag\'=>' . (int)$debugFlag . ', \'errorReportFlag\'=>' . (int)$errorReportFlag . ', \'loggingFlag\'=>' . (int)$loggingFlag . ');';
					// フラグメントキャッシュを更新
					file_put_contents($flagcacheFileName, '<?php' . PHP_EOL . $flagchaceBody . PHP_EOL . '?>');
					@chmod($flagcacheFileName,0666);
				}
			}
			if(TRUE !== $regenerateFlag){
				$configFileName = basename($argConfigPath);
				$generatedConfigFileName = getAutoGeneratedPath().$configFileName.'.generated.php';
				if(file_exists($generatedConfigFileName) && filemtime($generatedConfigFileName) >= filemtime($argConfigPath)){
					// 静的ファイル化されたコンフィグクラスファイルを読み込んで終了
					// fatal errorがいいのでrequireする
					require_once $generatedConfigFileName;
					// リプレースは不要
					$regenerateFlag = FALSE;
					return TRUE;
				}
			}
		}
	}
	if(!is_file($argConfigPath)){
		return FALSE;
	}

	// configureの初期化
	$configs = array();
	$configure = simplexml_load_file($argConfigPath, NULL, LIBXML_NOCDATA);

	// 環境フラグをセット
	if(!class_exists('Configure', FALSE)){
		$configure->addChild('AUTO_GENERATE_ENABLED', $autoGenerateFlag);
		$configure->addChild('AUTO_MIGRATE_ENABLED', $autoMigrationFlag);
		$configure->addChild('LOCAL_ENABLED', $localFlag);
		$configure->addChild('DEV_ENABLED', $devFlag);
		$configure->addChild('TEST_ENABLED', $testFlag);
		$configure->addChild('STAGING_ENABLED', $stagingFlag);
		$configure->addChild('DEBUG_ENABLED', $debugFlag);
		$configure->addChild('ERROR_REPORT_ENABLED', $errorReportFlag);
		$configure->addChild('LOGGING_ENABLED', $loggingFlag);
	}

	foreach(get_object_vars($configure) as $key => $val){
		if('comment' != $key){
			if(count($configure->{$key}->children()) > 0){
				if(!isset($configs[$key.'Configure'])){
					$configs[$key.'Configure'] = '';
				}
				$configs[$key.'Configure'] .= PHP_TAB.'const NAME = \''.$key.'\';'.PHP_EOL;
				foreach(get_object_vars($val) as $key2 => $val2){
					if('comment' != $key2){
						$evalFlag = FALSE;
						if(count($val2) > 1){
							$skip = TRUE;
							for($attrCnt=0;count($val2)>$attrCnt;$attrCnt++){
								if(isset($configure->{$key}->{$key2}[$attrCnt]->attributes()->stage)){
									$stage = $configure->{$key}->{$key2}[$attrCnt]->attributes()->stage;
									if('local' == $stage && 1 === (int)$localFlag){
										$skip = FALSE;
										BREAK;
									}
									elseif('dev' == $stage && 1 === (int)$devFlag){
										$skip = FALSE;
										BREAK;
									}
									elseif('test' == $stage && 1 === (int)$testFlag){
										$skip = FALSE;
										BREAK;
									}
									elseif('staging' == $stage && 1 === (int)$stagingFlag){
										$skip = FALSE;
										BREAK;
									}
								}
								else{
									$defAttrCnt = $attrCnt;
								}
							}
							if(TRUE === $skip){
								$attrCnt = $defAttrCnt;
							}
							$val2 = $val2[$attrCnt];
							if(isset($configure->{$key}->{$key2}[$attrCnt]->attributes()->code)){
								$evalFlag = TRUE;
							}
						}
						elseif(isset($configure->{$key}->{$key2}) && isset($configure->{$key}->{$key2}->attributes()->code)){
							$evalFlag = TRUE;
						}
						$val2 = trim($val2);
						$matches = NULL;
						if(preg_match_all('/\%(.+)\%/',$val2,$matches) > 0){
							for($matchCnt=0; count($matches[0]) > $matchCnt; $matchCnt++){
								$matchKey = $matches[0][$matchCnt];
								$matchStr = $matches[1][$matchCnt];
								$val2 = substr_replace($val2,$configure->{$key}->{$matchStr},strpos($val2,$matchKey),strlen($matchKey));
							}
						}
						if(TRUE === $evalFlag){
							if(FALSE !== strpos($val2, '__FILE__')){
								$val2 = str_replace('__FILE__', '\'' . realpath($argConfigPath) .'\'', $val2);
							}
							@eval('$val2 = '.$val2.';');
							//$configure->{$key}->{$key2} = $val2;
							$configs[$key.'Configure'] .= PHP_TAB.'const '.$key2.' = \''.$val2.'\';'.PHP_EOL;
						}
						else{
							if(strlen($val2) == 0){
								$configs[$key.'Configure'] .= PHP_TAB.'const '.$key2.' = \'\';'.PHP_EOL;
							}elseif('TRUE' == strtoupper($val2) || 'FALSE' == strtoupper($val2) || 'NULL' == strtoupper($val2) || is_numeric($val2)){
								$configs[$key.'Configure'] .= PHP_TAB.'const '.$key2.' = '.$val2.';'.PHP_EOL;
							}else{
								$configs[$key.'Configure'] .= PHP_TAB.'const '.$key2.' = \''.addslashes($val2).'\';'.PHP_EOL;
							}
						}
					}
				}
			}
			else{
				$evalFlag = FALSE;
				if(count($val) > 1){
					$skip = TRUE;
					for($attrCnt=0;count($val)>$attrCnt;$attrCnt++){
						if(isset($configure->{$key}[$attrCnt]->attributes()->stage)){
							$stage = $configure->{$key}[$attrCnt]->attributes()->stage;
							if('local' == $stage && 1 === (int)$localFlag){
								$skip = FALSE;
								BREAK;
							}
							elseif('dev' == $stage && 1 === (int)$devFlag){
								$skip = FALSE;
								BREAK;
							}
							elseif('test' == $stage && 1 === (int)$testFlag){
								$skip = FALSE;
								BREAK;
							}
							elseif('staging' == $stage && 1 === (int)$stagingFlag){
								$skip = FALSE;
								BREAK;
							}
						}
						else{
							$defAttr = $attrCnt;
						}
					}
					if(TRUE === $skip){
						$attrCnt = $defAttr;
					}
					$val = $val[$attrCnt];
					if(isset($configure->{$key}[$attrCnt]->attributes()->code)){
						$evalFlag = TRUE;
					}
				}
				elseif(isset($configure->{$key}->attributes()->code)){
					$evalFlag = TRUE;
				}
				$val = trim($val);
				$matches = NULL;
				if(preg_match_all('/\%(.+)\%/',$val,$matches) > 0){
					for($matchCnt=0; count($matches[0]) > $matchCnt; $matchCnt++){
						$matchKey = $matches[0][$matchCnt];
						$matchStr = $matches[1][$matchCnt];
						$val = substr_replace($val,$configure->{$matchStr},strpos($val,$matchKey),strlen($matchKey));
					}
				}

				if(TRUE === $evalFlag){
					if(FALSE !== strpos($val, '__FILE__')){
						$val = str_replace('__FILE__', '\'' . realpath($argConfigPath) .'\'', $val);
					}
					eval('$val = '.$val.';');
					if(!isset($configs['Configure'])){
						$configs['Configure'] = '';
					}
					$configs['Configure'] .= PHP_TAB.'const '.$key.' = \''.$val.'\';'.PHP_EOL;
				}
				else{
					if(!isset($configs['Configure'])){
						$configs['Configure'] = '';
					}
					if(strlen($val) == 0){
						$configs['Configure'] .= PHP_TAB.'const '.$key.' = \'\';'.PHP_EOL;
					}
					elseif('TRUE' == strtoupper($val) || 'FALSE' == strtoupper($val) || 'NULL' == strtoupper($val) || is_numeric($val)){
						$configs['Configure'] .= PHP_TAB.'const '.$key.' = '.$val.';'.PHP_EOL;
					}
					else{
						// XXX ココ危険！！！addslashesしないと行けないシチュエーションが出てくるかも
						$configs['Configure'] .= PHP_TAB.'const '.$key.' = \''.addslashes($val).'\';'.PHP_EOL;
					}
				}
			}
		}
	}

	static $baseConfigureClassDefine = NULL;
	if(NULL === $baseConfigureClassDefine){



		/*------------------------------ Configureクラス定義のスケルトン ココから ------------------------------*/

		$baseConfigureClassDefine = <<<_CLASSDEF_
class %class% {

	%consts%

	private static function _search(\$argVal,\$argKey,\$argHints){
		if(preg_match('/'.\$argHints['hint'].'/',\$argKey)){
			\$argHints['data'][\$argKey] = \$argVal;
		}
	}

	public static function constant(\$argHint,\$argSearchFlag = FALSE){
		static \$myConsts = NULL;
		if(FALSE !== \$argSearchFlag){
			if(NULL === \$myConsts){
				\$ref = new ReflectionClass(__CLASS__);
				\$myConsts = \$ref->getConstants();
			}
			\$tmpArr = array();
			foreach(\$myConsts as \$key => \$val){
				if(preg_match('/'.\$argHint.'/',\$key)){
					\$tmpArr[\$key] = \$val;
				}
			}
			if(count(\$tmpArr)>0){
				return \$tmpArr;
			}
		}
		elseif(TRUE === defined('self::'.\$argHint)){
			return constant('self::'.\$argHint);
		}
		return NULL;
	}
}

\$paths = %class%::constant(".+_PATH$", TRUE);
foreach(\$paths as \$key => \$val){
	set_include_path(get_include_path().PATH_SEPARATOR.\$val);
}

_CLASSDEF_;

		/*------------------------------ Configureクラス定義のスケルトン ココまで ------------------------------*/



	}

	// Configureクラスを宣言する
	$configGeneratClassDefine = NULL;
	foreach($configs as $key => $val){
		$configClassDefine = $baseConfigureClassDefine;
		if('Configure' !== $key){
			$configClassDefine .= "\r\n/*requireに成功しているので、initFrameworkでコンフィグを追加する*/\r\n_initFramework('%class%');";
		}
		$configClassDefine = str_replace('%class%',ucwords($key),$configClassDefine);
		$configClassDefine = str_replace('%consts%', 'const PATH = \''.$argConfigPath.'\'; '.$val, $configClassDefine);
		if(TRUE === $autoGenerateFlag){
			$configGeneratClassDefine .= $configClassDefine;
		}
		else{
			eval($configClassDefine);
		}
	}

	// ジェネレート処理
	if(TRUE === $autoGenerateFlag){
		$configFileName = basename($argConfigPath);
		$generatedConfigFileName = getAutoGeneratedPath().$configFileName.'.generated.php';
		// タブ文字削除
		$configGeneratClassDefine = str_replace(PHP_TAB, '', $configGeneratClassDefine);
		// 改行文字削除
		$configGeneratClassDefine = str_replace(array(PHP_CR, PHP_LF), '', $configGeneratClassDefine);
		file_put_contents($generatedConfigFileName, '<?php' . PHP_EOL . $configGeneratClassDefine . PHP_EOL . '?>');
		@chmod($generatedConfigFileName,0666);
		// 静的ファイル化されたコンフィグクラスファイルを読み込む
		require_once $generatedConfigFileName;
	}

	// インクルードパスの追加処理
	foreach($configs as $key => $val){
		$ConfigClassName = ucwords($key);
		$paths = $ConfigClassName::constant(".+_PATH$", TRUE);
		foreach($paths as $key => $val){
			set_include_path(get_include_path().PATH_SEPARATOR.$val);
		}
	}

	return TRUE;
}

/**
 * プロジェクト名からconfigファイルのパスを自動走査して取得する
 */
function getConfigPathForConfigName($argConfigName){
	$projectconfPath = dirname(dirname(dirname(__FILE__))).'/' . $argConfigName . '/core/' . $argConfigName . '.config.xml';
	if(TRUE !== is_file($projectconfPath)){
		$projectconfPath = dirname(dirname(dirname(__FILE__))).'/' . $argConfigName . '/core/config.xml';
	}
	if(TRUE !== is_file($projectconfPath)){
		$projectconfPath = dirname(dirname(dirname(__FILE__))).'/' . $argConfigName . '/core/' . str_replace('Package', '', $argConfigName) . '.config.xml';
	}
	if(TRUE !== is_file($projectconfPath)){
		$projectconfPath = dirname(dirname(dirname(__FILE__))).'/' . $argConfigName . 'Package/core/config.xml';
	}
	if(TRUE !== is_file($projectconfPath)){
		$projectconfPath = dirname(dirname(dirname(__FILE__))).'/' . $argConfigName . 'Package/core/' . $argConfigName . '.config.xml';
	}
	if(TRUE !== is_file($projectconfPath) && 'Project' === $argConfigName){
		$projectconfPath = dirname(dirname(dirname(__FILE__))).'/FrameworkManager/sample/packages/' . $argConfigName . 'Package/core/' . $argConfigName . '.config.xml';
	}
	if(TRUE === is_file($projectconfPath)){
		return $projectconfPath;
	}
	return FALSE;
}

/**
 * configの読み込みとconfigureクラスの定義を実行する
 */
function loadConfigForConfigName($argConfigName){
	$projectconfPath = getConfigPathForConfigName($argConfigName);
	if(FALSE !== $projectconfPath){
		return loadConfig($projectconfPath);
	}
	return FALSE;
}

/**
 * フレームワークの初期化処理(内部関数)
 * @param mixed TRUEの時は、読み込み済みのパッケージ情報を返す stringの時はConfigureクラス名としてConfigureクラスに対してのinitを処理する
 */
function _initFramework($argment = NULL){

	// 読み込み済みのパッケージ定義格納用
	static $pkgConfXML = NULL;
	// 処理済みのConfigureクラス名格納用
	static $argmentKeys = NULL;

	if(TRUE === $argment && NULL !== $pkgConfXML){
		// 敢えて直ぐ返す
		return $pkgConfXML;
	}

	if(!function_exists('_loadPackage')){
		/**
		 * ローカル関数:指定されたパッケージのロード処理
		 * ※ただ手続きだと見難いので関数化しただけ
		 */
		function _loadPackage($pkgConfXMLPath, &$pkgConfXML){
			if(is_array($pkgConfXMLPath)){
				if(count($pkgConfXMLPath) > 0) {
					// 再帰処理
					foreach($pkgConfXMLPath as $key => $path){
						_loadPackage($path, $pkgConfXML);
					}
				}
			}
			else{
				if(file_exists($pkgConfXMLPath)){
					if(NULL === $pkgConfXML){
						// 配列に初期化
						$pkgConfXML = array();
					}
					// XXX 新しいパッケージは常に配列の先頭に！
					array_unshift($pkgConfXML, array('time' => filemtime($pkgConfXMLPath), 'dom' => simplexml_load_string(file_get_contents($pkgConfXMLPath), NULL, LIBXML_NOCDATA)));
					// defaulのauto節を処理する
					if(count($pkgConfXML[0]['dom']->default->auto) > 0){
						foreach($pkgConfXML[0]['dom']->default->auto->children() as $autoLoadModule){
							loadModule($autoLoadModule);
						}
					}
				}
			}
		};
	}

	// デフォルトのフレームワークのpackageXMLを探して全て読み込む
	if(NULL === $pkgConfXML){
		// 「package.xml」と言うファイルがこのファイルと同じ階層にあったら読み込む
		_loadPackage(dirname(__FILE__).'/package.xml', $pkgConfXML);
		// このファイルと同じ名前で拡張子が「.package.xml」のファイルがこのファイルと同じ階層にあったら読み込む
		_loadPackage(dirname(__FILE__).'/' . strtoupper(substr(basename(__FILE__), 0, strpos(basename(__FILE__), '.'))) . '.package.xml', $pkgConfXML);
		// 「.*PACKAGE_CONFIG_XML_PATH.*」に該当するConfigureクラス定数定義されたパスのファイルがあったら全て読み込む
		_loadPackage(Configure::constant('.*PACKAGE_CONFIG_XML_PATH.*', TRUE), $pkgConfXML);
		// Configureクラス定義のパッケージは全て読み込み済みとする
		$argmentKeys[] = 'Configure';
		// 「.*PACKAGE_CONFIG_XML_PATH.*」に該当する通常定数定義されたパスのファイルがあったら全て読み込む
		_loadPackage(constants('.*PACKAGE_CONFIG_XML_PATH.*',TRUE), $pkgConfXML);
		// デフォルトでパッケージが一個も見つからなかったらエラー
		if(NULL === $pkgConfXML){
			_systemError('not found package.xml  !! Please check default path config.' . PHP_EOL . str_replace(PATH_SEPARATOR, PHP_EOL, get_include_path()));
		}
	}

	// 渡されたConfigureクラス名のConfigureクラスをinit処理して$pkgConfXMLのリフレッシュする
	if(NULL !== $argment && TRUE !== $argment && is_string($argment)){
		// 処理済みでは無いかどうか
		// 更に、クラスが存在するかどうか
		if(!in_array($argment, $argmentKeys) && class_exists($argment, FALSE)){
			// 「.*PACKAGE_CONFIG_XML_PATH.*」に該当するConfigureクラス定数定義されたパスのファイルがあったら全て読み込む
			_loadPackage($argment::constant('.*PACKAGE_CONFIG_XML_PATH.*', TRUE), $pkgConfXML);
			// 2度処理しないように追加しておく
			$argmentKeys[] = $argment;
		}
	}

	if(TRUE === $argment){
		// TRUEの時は処理後のパッケージ定義を全て返す
		return $pkgConfXML;
	}
}

/**
 * 外部からのフレームワークの明示的初期化処理
 */
eval('function init' . $corefilename . '($argment = NULL){ return _initFramework($argment); }');

/**
 * フレームワーク内のエラー処理
*/
function _systemError($argMsg, $argStatusCode=500, $argHtml='', $argTrace=NULL){
	debug(">>>>>>>>>>> system error >>>>>>>>>>");
	debug("argMsg:{$argMsg}, argStatusCode:{$argStatusCode}, argHtml:{$argHtml}");
	$corefilename = strtoupper(substr(basename(__FILE__), 0, strpos(basename(__FILE__), '.')));
	$errorHtml = $argHtml;
	// ココを通るのは相当なイレギュラー
	if(defined($corefilename . '_ERROR_FINALIS')){
		eval(constant($corefilename . '_ERROR_FINALIS').'();');
	}
	else{
		header('HTTP/1.0 '.$argStatusCode.' Internal Server Error');
		if(!strlen($errorHtml) > 0){
			echo '<h1>Internal Server Error</h1>'.PHP_EOL;
			echo '<br/>'.PHP_EOL;
			echo 'Please check exception\'s log'.PHP_EOL;
		}
	}
	// 開発状態のみエラー表示をする
	if(isTest()) {
		if(strlen($errorHtml) > 0){
			$errorName = 'Internal Server Error';
			if (400 === (int)$argStatusCode){
				$errorName = 'Bad Request';
			}
			if (401 === (int)$argStatusCode){
				$errorName = 'Unauthorized';
			}
			if (403 === (int)$argStatusCode){
				$errorName = 'Forbidden';
			}
			if (404 === (int)$argStatusCode){
				$errorName = 'Not Found';
			}
			if (405 === (int)$argStatusCode){
				$errorName = 'Access Denied';
			}
			if (503 === (int)$argStatusCode){
				$errorName = 'Service Unavailable';
			}
			$errorTitle = '<h1>'.$argStatusCode.' '.$errorName.'</h1>';
			$errorTitle .= '<h2>'.$argMsg.'</h2>';
			$errorTitle .= '<h3>Please check exception\'s log</h3>';
			$errorHtml = str_replace('%error_code%', $argStatusCode, $errorHtml);
			$errorHtml = str_replace('%error_name%', $errorName, $errorHtml);
			$errorHtml = str_replace('%error_title%', $errorTitle, $errorHtml);
			// バックトレースの0番目をコードとして表示
			$errorMsg = '<p><strong>error code:%error_file% - %error_line%行目</strong></p>'.PHP_EOL;
			$errorMsg .= '<pre class="prettyprint lang-html linenums error_code brush: php first-line:%error_code_startline% highlight:[%error_code_highlightline%]">%error_code%</pre>'.PHP_EOL;
			$errorMsg .= '<p><strong>error ditail:</strong></p>'.PHP_EOL;
			$errorMsg .= '<pre class="error_detail">%error_detail%</pre>'.PHP_EOL;
			$errorCode = '';
			$errorDetail = '';
			$tracecs = $argTrace;
			if (NULL === $tracecs){
				$tracecs = debug_backtrace();
			}
			if(isset($tracecs[0])){
				$errorFileInfo = $tracecs[0];
				if(isset($errorFileInfo['file']) && isset($errorFileInfo['line']) && is_file($errorFileInfo['file']) && is_numeric($errorFileInfo['line'])){
					$handle = fopen($errorFileInfo['file'], 'r');
					if($handle){
						$targetStartLine = (int)$errorFileInfo['line'] - 10;
						if(0 > $targetStartLine){
							$targetStartLine = 0;
						}
						$targetEndLine = (int)$errorFileInfo['line'] + 10;
						$readLine = 0;
						$file="";
						while (($buffer = fgets($handle, 4096)) !== false) {
							if($readLine >= $targetStartLine && $readLine < $targetEndLine){
								$errorCode .= $buffer;
							}
							$readLine++;
							if($readLine >= $targetEndLine){
								break;
							}
						}
						fclose($handle);
						$errorMsg = str_replace('%error_file%', $errorFileInfo['file'], $errorMsg);
						$errorMsg = str_replace('%error_line%', $errorFileInfo['line'], $errorMsg);
						$errorMsg = str_replace('%error_code_startline%', (int)$targetStartLine+1, $errorMsg);
						$errorMsg = str_replace('%error_code_highlightline%', $errorFileInfo['line'], $errorMsg);
					}
				}
			}
			$errorMsg = str_replace('%error_file%', '', $errorMsg);
			$errorMsg = str_replace('%error_line%', '', $errorMsg);
			$errorMsg = str_replace('%error_code_startline%', '', $errorMsg);
			$errorMsg = str_replace('%error_code_highlightline%', '', $errorMsg);
			$errorMsg = str_replace('%error_code%', htmlentities($errorCode, ENT_QUOTES, mb_internal_encoding()), $errorMsg);
			// PHPUnitTestではdebugtraceを取らない・・・(traceが長すぎてパンクする・・・)
			if(!class_exists('PHPUnit_Framework_TestCase', FALSE)){
				$errorDetail = str_replace(array(PHP_TAB, ' '), array('&nbsp;&nbsp;', '&nbsp;'), htmlspecialchars(var_export($tracecs, TRUE)));
			}
			$errorMsg = str_replace('%error_detail%', $errorDetail, $errorMsg);
			// メッセージをディスパッチ
			$errorHtml = str_replace('%error_msg%', $errorMsg, $errorHtml);
			echo $errorHtml;
		}
		else {
			echo $argMsg;
			// PHPUnitTestではdebugtraceを取らない・・・(traceが長すぎてパンクする・・・)
			if(!class_exists('PHPUnit_Framework_TestCase', FALSE)){
				echo PHP_EOL.'<br>'.PHP_EOL.str_replace(array(' ', PHP_EOL), array('&nbsp;', PHP_EOL.'<br>'.PHP_EOL), var_export(debug_backtrace(), TRUE));
			}
		}
	}
	else if(strlen($errorHtml) > 0){
		$errorName = 'Internal Server Error';
		if (400 === (int)$argStatusCode){
			$errorName = 'Bad Request';
		}
		if (401 === (int)$argStatusCode){
			$errorName = 'Unauthorized';
		}
		if (403 === (int)$argStatusCode){
			$errorName = 'Forbidden';
		}
		if (404 === (int)$argStatusCode){
			$errorName = 'Not Found';
		}
		if (405 === (int)$argStatusCode){
			$errorName = 'Access Denied';
		}
		if (503 === (int)$argStatusCode){
			$errorName = 'Service Unavailable';
		}
		// XXX 本番環境では詳細なエラーの画面表示はしない！(exception_logには吐かれます)
		$errorTitle = '<h1>'.$argStatusCode.' '.$errorName.'</h1>';
		$errorTitle .= '<h2>&nbsp;</h2>';
		$errorTitle .= '<h3>Please check exception\'s log</h3>';
		$errorHtml = str_replace('%error_code%', $argStatusCode, $errorHtml);
		$errorHtml = str_replace('%error_name%', $errorName, $errorHtml);
		$errorHtml = str_replace('%error_title%', $errorTitle, $errorHtml);
		$errorHtml = str_replace('%error_msg%', '', $errorHtml);
		echo $errorHtml;
	}
	// PHPUnitTestではdebugtraceを取らない・・・(traceが長すぎてパンクする・・・)
	if(!class_exists('PHPUnit_Framework_TestCase', FALSE)){
		logging($argMsg, 'exception');
		logging($argMsg.PATH_SEPARATOR.var_export(debug_backtrace(),TRUE), 'backtrace');
	}
	exit();
}

/**
 * 外部からのフレームワークの明示的エラー処理
 */
function errorExit($argMsg, $argStatusCode=500, $argHtml=''){
	_systemError($argMsg, $argStatusCode, $argHtml);
}

/**
 * フレームワークの終了処理
 * ob_startを仕掛けたプログラムの終了時にコールされる
 * @param バッファストリング
 */
function _callbackAndFinalize($argBuffer){
	// return ってすると出力されるのよ。
	return $argBuffer;
}

/**
 * 単純なsessionIDのストア
 */
function _sessionIDStroe($argAction,$argSID = NULL){
	static $sessionID = NULL;
	if('get' === strtolower($argAction)){
		if(NULL == $sessionID){
			if(session_status()!=PHP_SESSION_ACTIVE)session_start();
			$sessionID = session_id();
		}
		return $sessionID;
	}
	elseif('set' === strtolower($argAction)){
		$sessionID = $argSID;
	}
}

/**
 * sessionIDのアクセサ
 */
function setSessionID($argSessionID){
	_sessionIDStroe('set',$argSessionID);
}

/**
 * sessionIDのアクセサ
 */
function getSessionID(){
	return _sessionIDStroe('get');
}

/**
 * 単純なUIDのストア
 */
function _uniqueuserIDStroe($argAction,$argUID = NULL){
	static $UID = NULL;
	if('get' === strtolower($argAction)){
		if(NULL == $UID){
			$UID = uniqid();
		}
		return $UID;
	}
	elseif('set' === strtolower($argAction)){
		$UID = $argUID;
	}
}

/**
 * UIDのセットアクセサ
 */
function setUID($argUID){
	_uniqueuserIDStroe('set',$argUID);
}

/**
 * UIDのゲットアクセサ
 */
function getUID(){
	return _uniqueuserIDStroe('get');
}

/**
 * pathを一気に上まで駆け上がる！
 */
function path($argFilePath, $argDepth = 1){
	for($pathUpcnt=0; $argDepth > $pathUpcnt; $pathUpcnt++){
		$argFilePath = dirname($argFilePath);
	}
	return $argFilePath;
}

define('FILE_PREPEND', 'FILE_PREPEND');
/**
 * file_put_contentsのフラグ指定を拡張
 * $argMode=FILE_APPEND=a+
 * $argMode=FOPENMODE(rとかt+とか)
*/
function file_put_contents_e($argFilePath, $argData, $argMode=0){
	$mode = $argMode;
	if(FILE_APPEND === $argMode){
		$mode = 'a+';
	}
	if(FILE_PREPEND === $argMode){
		$mode = 'w';
		$argData .= file_get_contents($argFilePath);
	}
	$handle = @fopen($argFilePath, $mode);
	if($handle){
		fwrite($handle, $argData);
		fclose($handle);
	}
	else{
		return FALSE;
	}
	return TRUE;
}

/**
 * ディレクトリごとコピーする
 */
function dir_copy($dir_name, $new_dir, $permission = 0755) {
	if (!is_dir($new_dir)) {
		$res = mkdir($new_dir, $permission, TRUE);
		if(!$res){
			return FALSE;
		}
		@exec('chmod -R '.sprintf('%04d', $permission).' ' .$new_dir);
	}
	if (is_dir($dir_name)) {
		if ($dh = opendir($dir_name)) {
			while (($file = readdir($dh)) !== FALSE) {
				if ($file == "." || $file == "..") {
					continue;
				}
				if (is_dir($dir_name . "/" . $file)) {
					dir_copy($dir_name . "/" . $file, $new_dir . "/" . $file, $permission);
				}
				else {
					copy($dir_name . "/" . $file, $new_dir . "/" . $file);
				}
			}
			closedir($dh);
		}
	}
	return TRUE;
}

/**
 * ディレクトリごと削除する
 */
function dir_delete($dir_name) {
	if (is_dir($dir_name)) {
		if ($dh = opendir($dir_name)) {
			while (($file = readdir($dh)) !== FALSE) {
				if ($file == "." || $file == "..") {
					continue;
				}
				if (is_dir($dir_name . "/" . $file)) {
					dir_delete($dir_name . "/" . $file);
				}
				else {
					unlink($dir_name . "/" . $file);
				}
			}
			closedir($dh);
		}
		rmdir($dir_name);
	}
	return TRUE;
}

/**
 * ディレクトリごと移動(コピーして削除)する
 */
function dir_move($dir_name, $new_dir, $permission = 0755) {
	if(TRUE === dir_copy($dir_name, $new_dir, $permission)){
		// コピーに成功してから削除する
		// XXX 冗長だが敢えて
		return dir_delete($dir_name);
		return TRUE;
	}
	return FALSE;
}

/**
 * logging出力
 */
function logging($arglog, $argLogName = NULL, $argConsolEchoFlag = FALSE){

	static $beforeDate = NULL;
	static $date = NULL;
	static $pdate = NULL;
	static $phour = NULL;
	static $loggingLineNum = 1;

	$logpath = dirname(dirname(dirname(dirname(__FILE__)))).'/logs/';
	if(NULL !== defined('PROJECT_NAME')){
		$logpath = dirname(dirname(dirname(dirname(__FILE__)))).'/' . PROJECT_NAME . '/logs/';
	}
	if(class_exists('Configure', FALSE) && NULL !== constant('Configure::LOG_PATH')){
		$logpath = Configure::LOG_PATH;
	}

	$loggingFlag = FALSE;
	if(class_exists('Configure', FALSE) && NULL !== constant('Configure::LOGGING_ENABLED')){
		$loggingFlag = Configure::LOGGING_ENABLED;
	}

	$debugFlag = FALSE;
	if(class_exists('Configure', FALSE) && NULL !== constant('Configure::DEBUG_ENABLED')){
		$debugFlag = Configure::DEBUG_ENABLED;
		$loggingFlag = 1;
	}

	if(NULL === $argLogName){
		$argLogName = 'process';
	}

	if(NULL === $pdate){
		$deftimezone = @date_default_timezone_get();
		date_default_timezone_set('Asia/Tokyo');
		$dateins = new DateTime();
		$date = $dateins->format('Y/m/d');
		$pdate = $dateins->format('Y-m-d H:i:s') . ' [UDate:'. microtime(TRUE).']';
		$phour = $dateins->format('H');
		date_default_timezone_set($deftimezone);
	}

	// ログローテートの実行
	if (1 === (int)$loggingFlag){
		// 最終ロギング時間の記録
		if(!is_file($logpath.'date')){
			@touch($logpath.'date');
			@chmod($logpath.'date', 0666);
			@file_put_contents($logpath.'date', $date);
		}
		else {
			$beforeDate = @file_get_contents($logpath.'date');
		}
		// 日が変わったかどうか
		if (NULL !== $beforeDate && $beforeDate != $date){
			@file_put_contents($logpath.'date', $date);
			// ログローテートの実行
			if (is_dir($logpath) && $dh = @opendir($logpath)) {
				while (($file = @readdir($dh)) !== false) {
					if(is_file($logpath.$file) && 'date' !== $file){
						if (!is_dir($logpath.'backup/'.$beforeDate)){
							@mkdir($logpath.'backup/'.$beforeDate, 0777, true);
							// XXX root実行されている場合用
						}
						@rename($logpath.$file, $logpath.'backup/'.$beforeDate.'/'.$file);
						@exec('chmod -R 0777 ' . $logpath.'backup');
					}
				}
				@closedir($dh);
				// LOG_ROTATE_CYCLEヶ月以上前のファイルは全て削除
				$beforeDates = explode('/', $beforeDate);
				$beforeYear = (int)$beforeDates[0];
				$beforeMonth = (int)$beforeDates[1]-getConfig('LOG_ROTATE_CYCLE');
				if (1 > $beforeMonth){
					$beforeYear -= 1;
					$beforeMonth = 12 + $beforeMonth;
				}
				$maxLoop = 0;
				while (is_dir($logpath.'backup/'.$beforeYear.'/'.sprintf('%02d', $beforeMonth))){
					dir_delete($logpath.'backup/'.$beforeYear.'/'.sprintf('%02d', $beforeMonth));
					// 1ヶ月減算
					$beforeMonth -= 1;
					if (1 > $beforeMonth){
						$beforeYear -= 1;
						$beforeMonth = 12 + $beforeMonth;
					}
					$maxLoop++;
					if ($maxLoop > 1000){
						// 1000ヶ月以上前には遡れない！無限ループ対策
						break;
					}
				}
			}
		}
	}

	if(is_array($arglog) || is_object($arglog)){
		$arglog = var_export($arglog, TRUE);
	}
	if(isset($_SERVER['REQUEST_URI'])){
		$arglog = '[URI:'.$_SERVER['REQUEST_URI'].']'.$arglog;
	}
	$logstr = $pdate.'[logging'.$loggingLineNum.'][SID:'.getSessionID().'][UID:'.getUID().']'.$arglog;

	// 改行コードは\rだけにして、一行で表現出来るようにする
	$logstr = str_replace(PHP_CR,'[EOL]',$logstr);
	$logstr = str_replace(PHP_LF,'[EOL]',$logstr);

	// ログ出力
	if (1 === (int)$loggingFlag){
		if (!is_dir($logpath)){
			@mkdir($logpath, 0777, true);
			@exec('chmod -R 0777 ' .$logpath);
		}
		if('process' !== $argLogName){
			// process_logは常に出す
			if(!is_file($logpath.'process.log')){
				@touch($logpath.'process.log');
				@chmod($logpath.'process.log', 0666);
			}
			if(!is_file($logpath.'process'.$phour.'.log')){
				@touch($logpath.'process'.$phour.'.log');
				@chmod($logpath.'process'.$phour.'.log', 0666);
			}
			@file_put_contents($logpath.'process.log', $logstr.PHP_EOL, FILE_APPEND);
			@file_put_contents($logpath.'process'.$phour.'.log', $logstr.PHP_EOL, FILE_APPEND);
		}
		if(!is_file($logpath.$argLogName.'.log')){
			@touch($logpath.$argLogName.'.log');
			@chmod($logpath.$argLogName.'.log', 0666);
		}
		if(!is_file($logpath.$argLogName.$phour.'.log')){
			@touch($logpath.$argLogName.$phour.'.log');
			@chmod($logpath.$argLogName.$phour.'.log', 0666);
		}
		@file_put_contents($logpath.$argLogName.'.log', $logstr.PHP_EOL, FILE_APPEND);
		@file_put_contents($logpath.$argLogName.$phour.'.log', $logstr.PHP_EOL, FILE_APPEND);
	}

	// $debugFlagが有効だったらdebugログに必ず出力
	if('debug' != $argLogName && 'backtrace' != $argLogName && 1 === (int)$debugFlag && isset($_SERVER['REQUEST_URI'])){
		debug($arglog);
	}

	$loggingLineNum++;
}

/**
 * debugログ出力
 */
function debug($arglog){
	if(class_exists('Configure', FALSE) && NULL !== constant('Configure::DEBUG_ENABLED')){
		$debugFlag = Configure::DEBUG_ENABLED;
	}
	if(isset($debugFlag) && 1 === (int)$debugFlag){
		logging($arglog, 'debug', TRUE);
	}
}

/**
 * 定数を正規表現で検索可能にする
 */
function constants($argKey, $argSearchFlag = FALSE){
	if(FALSE !== $argSearchFlag){
		$datas = array();
		foreach(get_defined_constants() as $constKey => $val){
			if(preg_match('/'.$argKey.'/',$constKey)){
				$datas[$constKey] = $val;
			}
		}
		if(count($datas)>0){
			return $datas;
		}
	}
	elseif(TRUE === defined($argKey)){
		return constant($argKey);
	}
	return NULL;
}

define('CHAKE_STAGE_LOCAL', 'local');
define('CHAKE_STAGE_DEV', 'dev');
define('CHAKE_STAGE_TEST', 'test');
define('CHAKE_STAGE_STAGING', 'stage');
define('CHAKE_STAGE_RELEASE', 'release');

/**
 * ドメインベースでステージを判定する
 */
function getStage($argHost=NULL){
	static $stage = NULL;
	if(NULL === $stage){
		$host = NULL;
		if(isset($_SERVER['HTTP_HOST'])){
			$host = $_SERVER['HTTP_HOST'];
		}
		if(isset($_SERVER['SERVER_NAME']) && strlen($_SERVER['SERVER_NAME'])){
			$host = $_SERVER['SERVER_NAME'];
		}
		if(NULL !== $argHost){
			$host = $argHost;
		}
		$stage = CHAKE_STAGE_RELEASE;
		if(NULL === $host){
			$stage = CHAKE_STAGE_TEST;
		}
		if(FALSE !== strpos($host, 'test.')){
			$stage = CHAKE_STAGE_TEST;
		}
		if(FALSE !== strpos($host, 'stage.')){
			$stage = CHAKE_STAGE_STAGING;
		}
		if(FALSE !== strpos($host, 'staging.')){
			$stage = CHAKE_STAGE_STAGING;
		}
		if(FALSE !== strpos($host, 'dev.')){
			$stage = CHAKE_STAGE_DEV;
		}
		if(FALSE !== strpos($host, 'develop.')){
			$stage = CHAKE_STAGE_DEV;
		}
		if(FALSE !== strpos($host, 'development.')){
			$stage = CHAKE_STAGE_DEV;
		}
		if(FALSE !== strpos($host, 'localhost')){
			$stage = CHAKE_STAGE_LOCAL;
		}
		if(FALSE !== strpos($host, 'localhost')){
			$stage = CHAKE_STAGE_LOCAL;
		}
		if(FALSE !== strpos($host, '127.0.0.1')){
			$stage = CHAKE_STAGE_LOCAL;
		}
		if(FALSE !== strpos($host, 'exsample.com')){
			$stage = CHAKE_STAGE_LOCAL;
		}
	}
	return $stage;
}

/**
 * 指定されたステージなのかどうかをチェックする
 * @param $argStage string local dev test stage release
 */
function checkStage($argStage, $argHost=NULL){
	if($argStage === getStage($argHost)){
		return TRUE;
	}
	return FALSE;
}

/**
 * テスト環境チェック
 * @param $argStagingEnabled bool TRUE=ステージングをテスト環境とみなさない FALSE=ステージングをテスト環境とみなす(デフォルト)
 */
function isTest($argStagingEnabled=FALSE, $argProjectName=NULL, $argHost=NULL){
	$isTest = FALSE;
	$host = NULL;
	if(isset($_SERVER['SERVER_NAME'])){
		$host = $_SERVER['SERVER_NAME'];
	}
	if(NULL !== $argHost){
		$host = $argHost;
	}
	if(NULL !== $host && 1 === getAutoStageCheckEnabled($argProjectName) && FALSE === checkStage(CHAKE_STAGE_RELEASE, $host)){
		// ステージング環境をテスト環境とみなすかどうか
		if(FALSE === $argStagingEnabled){
			// みなすので、テスト確定
			$isTest = TRUE;
		}
		// ステージング環境かどうか
		elseif(TRUE !== checkStage(CHAKE_STAGE_STAGING, $host)){
			// ステージング環境をテスト環境とみなさないので、ステージング環境以外なのでテスト環境であると返却する
			$isTest = TRUE;
		}
	}
	elseif(class_exists('Configure', FALSE)){
		if(getLocalEnabled($argProjectName, $host) || getDevelopmentEnabled($argProjectName, $host) || getTestEnabled($argProjectName, $host)|| getStagingEnabled($argProjectName, $host)) {
			// ステージング環境をテスト環境とみなすかどうか
			if(FALSE === $argStagingEnabled){
				// みなすので、テスト確定
				$isTest = TRUE;
			}
			// ステージング環境かどうか
			elseif(0 === getStagingEnabled($argProjectName, $host)){
				// ステージング環境をテスト環境とみなさないので、ステージング環境以外なのでテスト環境であると返却する
				$isTest = TRUE;
			}
		}
	}
	return $isTest;
}

/**
 * 現在設定されている開発環境自動判別フラグを返す
 */
function getAutoStageCheckEnabled($argProjectName=NULL){
	static $enabled = NULL;
	if(NULL === $enabled || !isset($enabled[$argProjectName])){
		$autoStagecheckEnabled = NULL;
		$enabled = array();
		if(NULL !== $argProjectName){
			$autoStagecheckEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'/.autostagecheck';
			if(TRUE !== is_file($autoStagecheckEnabledFilepath)){
				$autoStagecheckEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'Package/.autostagecheck';
			}
		}
		elseif(NULL !== defined('PROJECT_NAME')){
			// 併設されている事を前提とする！
			$autoStagecheckEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'/.autostagecheck';
			if(TRUE !== is_file($autoStagecheckEnabledFilepath)){
				$autoStagecheckEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'Package/.autostagecheck';
			}
			if(TRUE !== is_file($autoStagecheckEnabledFilepath) && 'Project' === PROJECT_NAME){
				$autoStagecheckEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/FrameworkManager/sample/packages/'.PROJECT_NAME.'Package/.autostagecheck';
			}
		}
		else{
			$corefilename = corefilename();
			$autoStagecheckEnabledFilepath = dirname(dirname(__FILE__)).'/.autostagecheck';
			if(defined($corefilename . '_AUTO_STAGE_CHECK_ENABLED')){
				$autoStagecheckEnabledFilepath = constant($corefilename . '_AUTO_STAGE_CHECK_ENABLED');
			}
		}
		if(isset($autoStagecheckEnabledFilepath)){
			// フラグファイルからフラグをセット
			$autoStagecheckEnabled = 0;
			if(file_exists($autoStagecheckEnabledFilepath)){
				$autoStagecheckEnabled = 1;
			}
		}
// 		if(NULL === $argProjectName){
			// 一応$_SERVERを探す
			if(isset($_SERVER['autostagecheck']) && 1 === (int)strtolower($_SERVER['autostagecheck'])){
				// $_SERVERが最強 $_SERVERがあれば$_SERVERに必ず従う
				$autoStagecheckEnabled = 1;
			}
			else if(isset($_SERVER['autostagecheck'])){
				// $_SERVERが最強 $_SERVERがあれば$_SERVERに必ず従う
				$autoStagecheckEnabled = 0;
			}
			// XXX Fuel向け拡張
			if(isset($_SERVER['FUEL_ENV']) && 'local' === strtolower($_SERVER['FUEL_ENV'])){
				$autoStagecheckEnabled = 1;
			}
			// 		}
		if(NULL === $autoStagecheckEnabled){
			// フラグ設定が見つからなかったのでdisabledで設定
			$autoStagecheckEnabled = 0;
		}
		$enabled[$argProjectName] = $autoStagecheckEnabled;
	}
	return $enabled[$argProjectName];
}

/**
 * 現在設定されているロ−カル開発環境フラグを返す
 */
function getLocalEnabled($argProjectName=NULL, $argHost=NULL){
	static $enabled = NULL;
	if(NULL === $enabled || !isset($enabled[$argProjectName])){
		$localEnabled = NULL;
		$enabled = array();
		if(1 === getAutoStageCheckEnabled($argProjectName) && TRUE === checkStage(CHAKE_STAGE_LOCAL, $argHost)){
			$localEnabled = 1;
		}
		else {
			if(NULL !== $argProjectName){
				$localEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'/.local';
				if(TRUE !== is_file($localEnabledFilepath)){
					$localEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'Package/.local';
				}
			}
			elseif(NULL !== defined('PROJECT_NAME')){
				// 併設されている事を前提とする！
				$localEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'/.local';
				if(TRUE !== is_file($localEnabledFilepath)){
					$localEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'Package/.local';
				}
			}
			else{
				$corefilename = corefilename();
				$localEnabledFilepath = dirname(dirname(__FILE__)).'/.local';
				if(defined($corefilename . '_WORKSPACE_LOCAL_ENABLED')){
					$localEnabledFilepath = constant($corefilename . '_WORKSPACE_LOCAL_ENABLED');
				}
			}
			if(isset($localEnabledFilepath)){
				// フラグファイルからフラグをセット
				$localEnabled = 0;
				if(file_exists($localEnabledFilepath)){
					$localEnabled = 1;
				}
			}
		}
		// 一応$_SERVERを探す
		if(isset($_SERVER['workspace']) && 'local' === strtolower($_SERVER['workspace'])){
			// $_SERVERが最強 $_SERVERがあれば$_SERVERに必ず従う
			$localEnabled = 1;
		}
		else if(isset($_SERVER['workspace'])){
			$localEnabled = 0;
		}
		// XXX Fuel向け拡張
		if(isset($_SERVER['FUEL_ENV']) && 'local' === strtolower($_SERVER['FUEL_ENV'])){
			$localEnabled = 1;
		}
		if(NULL === $localEnabled){
			// フラグ設定が見つからなかったのでdisabledで設定
			$localEnabled = 0;
		}
		$enabled[$argProjectName] = $localEnabled;
	}
	return $enabled[$argProjectName];
}

/**
 * 現在設定されている開発開発環境フラグを返す
 */
function getDevelopmentEnabled($argProjectName=NULL, $argHost=NULL){
	static $enabled = NULL;
	if(NULL === $enabled || !isset($enabled[$argProjectName])){
		$devlopmentEnabled = NULL;
		$enabled = array();
		if(1 === getAutoStageCheckEnabled($argProjectName) && TRUE === checkStage(CHAKE_STAGE_DEV, $argHost)){
			$devlopmentEnabled = 1;
		}
		else {
			if(NULL !== $argProjectName){
				$devlopmentEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'/.dev';
				if(TRUE !== is_file($devlopmentEnabledFilepath)){
					$devlopmentEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'Package/.dev';
				}
			}
			elseif(NULL !== defined('PROJECT_NAME')){
				// 併設されている事を前提とする！
				$devlopmentEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'/.dev';
				if(TRUE !== is_file($devlopmentEnabledFilepath)){
					$devlopmentEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'Package/.dev';
				}
			}
			else{
				$corefilename = corefilename();
				$devlopmentEnabledFilepath = dirname(dirname(__FILE__)).'/.dev';
				if(defined($corefilename . '_WORKSPACE_DEV_ENABLED')){
					$devlopmentEnabledFilepath = constant($corefilename . '_WORKSPACE_DEV_ENABLED');
				}
				elseif(defined($corefilename . '_WORKSPACE_DEVELOPMENT_ENABLED')){
					$devlopmentEnabledFilepath = constant($corefilename . '_WORKSPACE_DEVELOPMENT_ENABLED');
				}
			}
			if(isset($devlopmentEnabledFilepath)){
				// フラグファイルからフラグをセット
				$devlopmentEnabled = 0;
				if(file_exists($devlopmentEnabledFilepath)){
					$devlopmentEnabled = 1;
				}
				// 一応ファイル名をdevelopmentで探してもみる
				if(0 === $devlopmentEnabled){
					if(file_exists(str_replace('.dev', '.development', $devlopmentEnabledFilepath))){
						$devlopmentEnabled = 1;
					}
				}
			}
		}
		// 一応$_SERVERを探す
		if(isset($_SERVER['workspace']) && TRUE === ('development' === strtolower($_SERVER['workspace']) || 'dev' === strtolower($_SERVER['workspace']))){
			// $_SERVERが最強 $_SERVERがあれば$_SERVERに必ず従う
			$devlopmentEnabled = 1;
		}
		else if (isset($_SERVER['workspace'])){
			$devlopmentEnabled = 0;
		}
		// XXX Fuel向け拡張
		if(isset($_SERVER['FUEL_ENV']) && TRUE === ('development' === strtolower($_SERVER['FUEL_ENV']) || 'dev' === strtolower($_SERVER['FUEL_ENV']))){
			$devlopmentEnabled = 1;
		}
		if(NULL === $devlopmentEnabled){
			// フラグ設定が見つからなかったのでdisabledで設定
			$devlopmentEnabled = 0;
		}
		$enabled[$argProjectName] = $devlopmentEnabled;
	}
	return $enabled[$argProjectName];
}

/**
 * 現在設定されているテスト開発環境フラグを返す
 */
function getTestEnabled($argProjectName=NULL, $argHost=NULL){
	static $enabled = NULL;
	if(NULL === $enabled || !isset($enabled[$argProjectName])){
		$testEnabled = NULL;
		$enabled = array();
		if(1 === getAutoStageCheckEnabled($argProjectName) && TRUE === checkStage(CHAKE_STAGE_TEST, $argHost)){
			$testEnabled = 1;
		}
		else {
			if(NULL !== $argProjectName){
				$testEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'/.test';
				if(TRUE !== is_file($testEnabledFilepath)){
					$testEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'Package/.test';
				}
			}
			elseif(NULL !== defined('PROJECT_NAME')){
				// 併設されている事を前提とする！
				$testEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'/.test';
				if(TRUE !== is_file($testEnabledFilepath)){
					$testEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'Package/.test';
				}
			}
			else{
				$corefilename = corefilename();
				$testEnabledFilepath = dirname(dirname(__FILE__)).'/.test';
				if(defined($corefilename . '_WORKSPACE_TEST_ENABLED')){
					$testEnabledFilepath = constant($corefilename . '_WORKSPACE_TEST_ENABLED');
				}
			}
			if(isset($testEnabledFilepath)){
				// フラグファイルからフラグをセット
				$testEnabled = 0;
				if(file_exists($testEnabledFilepath)){
					$testEnabled = 1;
				}
			}
		}
		// 一応$_SERVERを探す
		if(isset($_SERVER['workspace']) && 'test' === strtolower($_SERVER['workspace'])){
			// $_SERVERが最強 $_SERVERがあれば$_SERVERに必ず従う
			$testEnabled = 1;
		}
		else if (isset($_SERVER['workspace'])){
			$testEnabled = 0;
		}
		// XXX Fuel向け拡張
		if(isset($_SERVER['FUEL_ENV']) && 'test' === strtolower($_SERVER['FUEL_ENV'])){
			$testEnabled = 1;
		}
		if(NULL === $testEnabled){
			// フラグ設定が見つからなかったのでdisabledで設定
			$testEnabled = 0;
		}
		$enabled[$argProjectName] = $testEnabled;
	}
	return $enabled[$argProjectName];
}

/**
 * 現在設定されているステージング開発環境フラグを返す
 */
function getStagingEnabled($argProjectName=NULL, $argHost=NULL){
	static $enabled = NULL;
	if(NULL === $enabled || !isset($enabled[$argProjectName])){
		$stagingEnabled = NULL;
		$enabled = array();
		if(1 === getAutoStageCheckEnabled($argProjectName) && TRUE === checkStage(CHAKE_STAGE_STAGING, $argHost)){
			$stagingEnabled = 1;
		}
		else {
			if(NULL !== $argProjectName){
				$stagingEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'/.staging';
				if(TRUE !== is_file($stagingEnabledFilepath)){
					$stagingEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'Package/.staging';
				}
			}
			elseif(NULL !== defined('PROJECT_NAME')){
				// 併設されている事を前提とする！
				$stagingEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'/.staging';
				if(TRUE !== is_file($stagingEnabledFilepath)){
					$stagingEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'Package/.staging';
				}
			}
			else{
				$corefilename = corefilename();
				$stagingEnabledFilepath = dirname(dirname(__FILE__)).'/.staging';
				if(defined($corefilename . '_WORKSPACE_STAGING_ENABLED')){
					$stagingEnabledFilepath = constant($corefilename . '_WORKSPACE_STAGING_ENABLED');
				}
			}
			if(isset($stagingEnabledFilepath)){
				// フラグファイルからフラグをセット
				$stagingEnabled = 0;
				if(file_exists($stagingEnabledFilepath)){
					$stagingEnabled = 1;
				}
			}
		}
		// 一応$_SERVERを探す
		if(isset($_SERVER['workspace']) && 'staging' === strtolower($_SERVER['workspace'])){
			// $_SERVERが最強 $_SERVERがあれば$_SERVERに必ず従う
			$stagingEnabled = 1;
		}
		else if (isset($_SERVER['workspace'])){
			$stagingEnabled = 0;
		}
		// XXX Fuel向け拡張
		if(isset($_SERVER['FUEL_ENV']) && TRUE === ('staging' === strtolower($_SERVER['FUEL_ENV']) || 'stage' === strtolower($_SERVER['FUEL_ENV']))){
			$stagingEnabled = 1;
		}
		if(NULL === $stagingEnabled){
			// フラグ設定が見つからなかったのでdisabledで設定
			$stagingEnabled = 0;
		}
		$enabled[$argProjectName] = $stagingEnabled;
	}
	return $enabled[$argProjectName];
}

/**
 * 現在設定されているデバッグモードフラグを返す
 */
function getDebugEnabled($argProjectName=NULL, $argHost=NULL){
	static $enabled = NULL;
	if(NULL === $enabled || !isset($enabled[$argProjectName])){
		$debugEnabled = 0;
		$enabled = array();
		if(TRUE === isTest(FALSE, $argProjectName, $argHost)){
			$debugEnabled = 1;
		}
		else {
			if(NULL !== $argProjectName){
				$debugEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'/.debug';
				if(TRUE !== is_file($debugEnabledFilepath)){
					$debugEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'Package/.debug';
				}
			}
			elseif(NULL !== defined('PROJECT_NAME')){
				// 併設されている事を前提とする！
				$debugEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'/.debug';
				if(TRUE !== is_file($debugEnabledFilepath)){
					$debugEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'Package/.debug';
				}
				if(TRUE !== is_file($autoStagecheckEnabledFilepath) && 'Project' === PROJECT_NAME){
					$autoStagecheckEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/FrameworkManager/sample/packages/'.PROJECT_NAME.'Package/.debug';
				}
			}
			else{
				$corefilename = corefilename();
				$debugEnabledFilepath = dirname(dirname(__FILE__)).'/.debug';
				if(defined($corefilename . '_DEBUG_MODE_ENABLED')){
					$debugEnabledFilepath = constant($corefilename . '_DEBUG_MODE_ENABLED');
				}
			}
			if(isset($debugEnabledFilepath)){
				// フラグファイルからフラグをセット
				$debugEnabled = 0;
				if(file_exists($debugEnabledFilepath)){
					$debugEnabled = 1;
				}
			}
			// 一応$_SERVERを探す
			if(isset($_SERVER['debug_mode']) && 1 === (int)$_SERVER['debug_mode']){
				// $_SERVERが最強 $_SERVERがあれば$_SERVERに必ず従う
				$debugEnabled = 1;
			}
			else if (isset($_SERVER['debug_mode'])){
				$debugEnabled = 0;
			}
			else if(isset($_SERVER['debugmode']) && 1 === (int)$_SERVER['debugmode']){
				$debugEnabled = 1;
			}
			if(NULL === $debugEnabled){
				// フラグ設定が見つからなかったのでdisabledで設定
				$debugEnabled = 0;
			}
		}
		$enabled[$argProjectName] = $debugEnabled;
	}
	return $enabled[$argProjectName];
}

/**
 * 現在設定されているエラーレポーティングフラグを返す
 */
function getErrorReportEnabled($argProjectName=NULL){
	static $enabled = NULL;
	if(NULL === $enabled || !isset($enabled[$argProjectName])){
		$errorReportEnabled = NULL;
		$enabled = array();
		if(NULL !== $argProjectName){
			$errorReportEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'/.error_report';
			if(TRUE !== is_file($errorReportEnabledFilepath)){
				$errorReportEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'Package/.error_report';
			}
		}
		elseif(NULL !== defined('PROJECT_NAME')){
			// 併設されている事を前提とする！
			$errorReportEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'/.error_report';
			if(TRUE !== is_file($errorReportEnabledFilepath)){
				$errorReportEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'Package/.error_report';
			}
		}
		else{
			$corefilename = corefilename();
			$errorReportEnabledFilepath = dirname(dirname(__FILE__)).'/.error_report';
			if(defined($corefilename . '_ERROR_REPORT_ENABLED')){
				$errorReportEnabledFilepath = constant($corefilename . '_ERROR_REPORT_ENABLED');
			}
		}
		if(isset($errorReportEnabledFilepath)){
			// フラグファイルからフラグをセット
			$errorReportEnabled = 0;
			if(file_exists($errorReportEnabledFilepath)){
				$errorReportEnabled = 1;
			}
			// 一応ファイル名をerror_reportで探してもみる
			if(0 === $errorReportEnabled){
				if(file_exists(str_replace('.error_report', '.errorreport', $errorReportEnabledFilepath))){
					$errorReportEnabled = 1;
				}
			}
		}
		// 一応$_SERVERを探す
		if(isset($_SERVER['error_report']) && 1 === (int)$_SERVER['error_report']){
			// $_SERVERが最強 $_SERVERがあれば$_SERVERに必ず従う
			$errorReportEnabled = 1;
		}
		else if(isset($_SERVER['errorreport']) && 1 === (int)$_SERVER['errorreport']){
			$errorReportEnabled = 1;
		}
		else if(isset($_SERVER['error_report']) || isset($_SERVER['errorreport'])){
			$errorReportEnabled = 0;
		}
		if(NULL === $errorReportEnabled){
			// フラグ設定が見つからなかったのでdisabledで設定
			$errorReportEnabled = 0;
		}
		$enabled[$argProjectName] = $errorReportEnabled;
	}
	return $enabled[$argProjectName];
}

/**
 * 現在設定されているロギングフラグを返す
 */
function getLoggingEnabled($argProjectName=NULL){
	static $enabled = NULL;
	if(NULL === $enabled || !isset($enabled[$argProjectName])){
		$loggingEnabled = NULL;
		$enabled = array();
		if(NULL !== $argProjectName){
			$loggingEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'/.logging';
			if(TRUE !== is_file($loggingEnabledFilepath)){
				$loggingEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'Package/.logging';
			}
		}
		elseif(NULL !== defined('PROJECT_NAME')){
			// 併設されている事を前提とする！
			$loggingEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'/.logging';
			if(TRUE !== is_file($loggingEnabledFilepath)){
				$loggingEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'Package/.logging';
			}
		}
		else{
			$corefilename = corefilename();
			$loggingEnabledFilepath = dirname(dirname(__FILE__)).'/.logging';
			if(defined($corefilename . '_LOGGING_ENABLED')){
				$loggingEnabledFilepath = constant($corefilename . '_LOGGING_ENABLED');
			}
		}
		if(isset($loggingEnabledFilepath)){
			// フラグファイルからフラグをセット
			$loggingEnabled = 0;
			if(file_exists($loggingEnabledFilepath)){
				$loggingEnabled = 1;
			}
			// 一応ファイル名をloggingで探してもみる
			if(0 === $loggingEnabled){
				if(file_exists(str_replace('.logging', '.errorreport', $loggingEnabledFilepath))){
					$loggingEnabled = 1;
				}
			}
		}
		// 一応$_SERVERを探す
		if(isset($_SERVER['logging']) && 1 === (int)$_SERVER['logging']){
			// $_SERVERが最強 $_SERVERがあれば$_SERVERに必ず従う
			$loggingEnabled = 1;
		}
		else if(isset($_SERVER['logging']) && 1 === (int)$_SERVER['logging']){
			$loggingEnabled = 1;
		}
		else if(isset($_SERVER['logging']) || isset($_SERVER['logging'])){
			$loggingEnabled = 0;
		}
		if(NULL === $loggingEnabled){
			// フラグ設定が見つからなかったのでdisabledで設定
			$loggingEnabled = 0;
		}
		$enabled[$argProjectName] = $loggingEnabled;
	}
	return $enabled[$argProjectName];
}

/**
 * 現在設定されている自動最適化キャッシュ生成フラグを返す
 */
function getAutoGenerateEnabled($argProjectName=NULL){
	static $enabled = NULL;
	if(NULL === $enabled || !isset($enabled[$argProjectName])){
		$autoGenerateEnabled = NULL;
		$enabled = array();
		if(NULL !== $argProjectName){
			$autoGenerateEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'/.autogenerate';
			if(TRUE !== is_file($autoGenerateEnabledFilepath)){
				$autoGenerateEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'Package/.autogenerate';
			}
		}
		elseif(FALSE !== defined('PROJECT_NAME')){
			// 併設されている事を前提とする！
			$autoGenerateEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'/.autogenerate';
			if(TRUE !== is_file($autoGenerateEnabledFilepath)){
				$autoGenerateEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'Package/.autogenerate';
			}
		}
		else{
			$corefilename = corefilename();
			$autoGenerateEnabledFilepath = dirname(dirname(__FILE__)).'/.autogenerate';
			if(defined($corefilename . '_AUTO_GENERATE_ENABLED')){
				$autoGenerateEnabledFilepath = constant($corefilename . '_AUTO_GENERATE_ENABLED');
			}
		}
		if(isset($autoGenerateEnabledFilepath)){
			// 自動ジェネレートフラグのセット
			$autoGenerateEnabled = FALSE;
			if(file_exists($autoGenerateEnabledFilepath)){
				$autoGenerateEnabled = TRUE;
			}
		}
		// 一応ENVを探す
		if(isset($_SERVER['autogenerate']) && 1 === (int)$_SERVER['autogenerate']){
			// ENVが最強 ENVがあればENVに必ず従う
			$autoGenerateEnabled = TRUE;
		}
		else if(isset($_SERVER['autogenerate'])){
			$autoGenerateEnabled = FALSE;
		}
		if(NULL === $autoGenerateEnabled){
			// フラグ設定が見つからなかったのでdisabledで設定
			$autoGenerateEnabled = FALSE;
		}
		$enabled[$argProjectName] = $autoGenerateEnabled;
	}
	return $enabled[$argProjectName];
}

/**
 * 現在設定されている自動最適化キャッシュ生成フラグを返す
 */
function getAutoMigrationEnabled($argProjectName=NULL){
	static $enabled = NULL;
	if(NULL === $enabled || !isset($enabled[$argProjectName])){
		$autoMigrationEnabled = NULL;
		$enabled = array();
		if(NULL !== $argProjectName){
			$autoMigrationEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'/.automigration';
			if(TRUE !== is_file($autoMigrationEnabledFilepath)){
				$autoMigrationEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.$argProjectName.'Package/.automigration';
			}
		}
		elseif(NULL !== defined('PROJECT_NAME')){
			// 併設されている事を前提とする！
			$autoMigrationEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'/.automigration';
			if(TRUE !== is_file($autoMigrationEnabledFilepath)){
				$autoMigrationEnabledFilepath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'Package/.automigration';
			}
		}
		else{
			$corefilename = corefilename();
			$autoMigrationEnabledFilepath = dirname(dirname(__FILE__)).'/.automigration';
			if(defined($corefilename . '_AUTO_MIGRATION_ENABLED')){
				$autoMigrationEnabledFilepath = constant($corefilename . '_AUTO_MIGRATION_ENABLED');
			}
		}
		if(isset($autoMigrationEnabledFilepath)){
			// 自動マイグレーションフラグのセット
			$autoMigrationEnabled = FALSE;
			if(file_exists($autoMigrationEnabledFilepath)){
				$autoMigrationEnabled = TRUE;
			}
		}
		// 一応ENVを探す
		if(isset($_SERVER['automigration']) && 1 === (int)$_SERVER['automigration']){
			// ENVが最強 ENVがあればENVに必ず従う
			$autoMigrationEnabled = TRUE;
		}
		else if(isset($_SERVER['automigration'])){
			$autoMigrationEnabled = FALSE;
		}
		if(NULL === $autoMigrationEnabled){
			// フラグ設定が見つからなかったのでdisabledで設定
			$autoMigrationEnabled = FALSE;
		}
		$enabled[$argProjectName] = $autoMigrationEnabled;
	}
	return $enabled[$argProjectName];
}

/**
 * 現在設定されている自動最適化キャッシュファイル保存先パス情報を返す
 */
function getAutoGeneratedPath(){
	static $generatedPath = NULL;
	if(NULL === $generatedPath){
		$generatedPath = dirname(dirname(__FILE__)).'/autogenerate/';
		if(FALSE !== defined('PROJECT_NAME')){
			// 併設されている事を前提とする！
			$tmpGeneratedPath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'/autogenerate';
			if(TRUE === is_dir($tmpGeneratedPath)){
				// パスとして認める
				$generatedPath = $tmpGeneratedPath.'/';
			}
			else{
				$tmpGeneratedPath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'Package/autogenerate';
				if(TRUE === is_dir($tmpGeneratedPath)){
					// パスとして認める
					$generatedPath = $tmpGeneratedPath.'/';
				}
			}
		}
	}
	return $generatedPath;
}

/**
 * ジェネレートチェックと自動読み込みを解決する
 */
function resolveUnlinkAutoGeneratedFile($generatedIncFileName){
	$unlink=TRUE;
	if(is_file($generatedIncFileName)){
		if(filemtime($generatedIncFileName) >= filemtime(__FILE__)){
			// フレームワークコアの変更が見当たらない場合は、コンフィグと比較
			$pkConfXMLs = _initFramework(TRUE);
			for($pkConfXMLCnt = 0, $timecheckNum = 0; count($pkConfXMLs) > $pkConfXMLCnt; $pkConfXMLCnt++){
				// XXX 時間チェック(タイムゾーン変えてもちゃんと動く？？)
				if(filemtime($generatedIncFileName) >= $pkConfXMLs[$pkConfXMLCnt]['time']){
					$timecheckNum++;
				}
			}
			if($timecheckNum === $pkConfXMLCnt){
				$unlink=FALSE;
				// 静的ファイル化されたrequire群ファイルを読み込んで終了
				// fatal errorがいいのでrequireする
				require_once $generatedIncFileName;
			}
		}
		if(FALSE !== $unlink){
			// ここまで来たら再ジェネレートが走るのでジェネレート済みの古いファイルを削除しておく
			@file_put_contents($generatedIncFileName, '');
			@unlink($generatedIncFileName);
		}
	}
	return $unlink;
}

/**
 * オートジェネレートされるファイルにデフォルトで書き込む処理のベースを文字列で返す(内部関数)
 */
function _getAutoGenerateIncPHPMainBase(){
	static $autoGenerateIncPHPMainBase = NULL;
	if(NULL === $autoGenerateIncPHPMainBase){
		// 1行で返す
		$autoGenerateIncPHPMainBase = "";
		$autoGenerateIncPHPMainBase .= PHP_EOL;
		$autoGenerateIncPHPMainBase .= FILE_CHECK_GENERIC_FUNCTIONS;
		$autoGenerateIncPHPMainBase .= PHP_EOL;
		$autoGenerateIncPHPMainBase .= '$linkFilePath=\'%s\'; ';
		$autoGenerateIncPHPMainBase .= 'if(!isset($unlink)){ $unlink=FALSE; }; ';
		$autoGenerateIncPHPMainBase .= 'if(!(FALSE === $unlink && FALSE !== @file_exists_ip($linkFilePath) && (int)filemtime(__FILE__) >= (int)filemtime_ip($linkFilePath))){ $unlink=TRUE; }; ';
		$autoGenerateIncPHPMainBase .= PHP_EOL;
	}
	return $autoGenerateIncPHPMainBase;
}

/**
 * インクルードキャッシュのジェネレート
 */
function generateIncCache($argGeneratedPath, $argIncludePath){
	// 先ず先頭に条件文を追加
	@file_put_contents_e($argGeneratedPath, '<?php' . sprintf(_getAutoGenerateIncPHPMainBase(), $argIncludePath) . '?>', FILE_PREPEND);
	// ファイルの終端に処理を追加
	@file_put_contents_e($argGeneratedPath, '<?php if(FALSE === $unlink){ @include_once(\'' . $argIncludePath . '\'); } ?>', FILE_APPEND);
	@chmod($argGeneratedPath, 0666);
}

/**
 * クラス自動生成のキャッシュ
 */
function generateClassCache($argGeneratedPath, $argIncludePath, $argClassBuffer, $argClassName=''){
	$classCheck = $argClassName;
	if('' !== $argClassName){
		$classCheck = ' && !class_exists(\'' . $argClassName . '\', FALSE)';
	}
	// 先ず先頭に条件文を追加
	@file_put_contents_e($argGeneratedPath, '<?php' . sprintf(_getAutoGenerateIncPHPMainBase(), $argIncludePath) . '?>', FILE_PREPEND);
	// ファイルの終端に処理を追加
	@file_put_contents_e($argGeneratedPath, '<?php' . PHP_EOL . 'if(FALSE === $unlink' . $classCheck . '){ ' . PHP_EOL . $argClassBuffer . PHP_EOL . '}' . PHP_EOL . '?>', FILE_APPEND);
	@chmod($argGeneratedPath, 0666);
}

/**
 * 現在設定されている自動最適化キャッシュファイル保存先パス情報を返す
 */
function getAutoMigrationPath(){
	static $migrationPath = NULL;
	if(NULL === $migrationPath){
		$migrationPath = dirname(dirname(__FILE__)).'/automigration/';
		if(NULL !== defined('PROJECT_NAME')){
			// 併設されている事を前提とする！
			$tmpMigrationPath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'/automigration';
			if(TRUE === is_dir($tmpMigrationPath)){
				// パスとして認める
				$migrationPath = $tmpMigrationPath.'/';
			}
			else {
				$tmpMigrationPath = dirname(dirname(dirname(__FILE__))).'/'.PROJECT_NAME.'Package/automigration';
				if(TRUE === is_dir($tmpMigrationPath)){
					// パスとして認める
					$migrationPath = $tmpMigrationPath.'/';
				}
			}
		}
	}
	return $migrationPath;
}

/**
 * コンフィグレーションされている値を返す
 */
function getConfig($argKey, $argConfigName=''){
	static $values = array();
	$value = NULL;
	if (NULL === $argConfigName){
		$argConfigName = '';
	}
	if (0 === strlen($argConfigName) && defined('PROJECT_NAME') && strlen(PROJECT_NAME) > 0){
		$argConfigName = PROJECT_NAME;
	}
	if(!isset($values[$argConfigName])){
		$values[$argConfigName] = array();
	}
	if(!isset($values[$argConfigName][$argKey])){
		if(class_exists('Configure') && TRUE === defined('Configure::'.$argKey)){
			$value = Configure::constant($argKey);
		}
		if(defined('PROJECT_NAME') && strlen(PROJECT_NAME) > 0 && class_exists(PROJECT_NAME . 'Configure')){
			$ProjectConfigure = PROJECT_NAME . 'Configure';
			if(TRUE === defined($ProjectConfigure.'::'.$argKey)){
				$value = $ProjectConfigure::constant($argKey);
			}
		}
		if(!class_exists($argConfigName . 'Configure') && !class_exists($argConfigName) && !class_exists(str_replace('Package', '', $argConfigName))){
			loadConfigForConfigName($argConfigName);
		}
		if('' !== $argConfigName && strlen($argConfigName) > 0 && class_exists($argConfigName . 'Configure')){
			$ArgConfigure = $argConfigName . 'Configure';
			if(TRUE === defined($ArgConfigure.'::'.$argKey)){
				$value = $ArgConfigure::constant($argKey);
			}
		}
		if('' !== $argConfigName && strlen($argConfigName) > 0 && class_exists($argConfigName)){
			$ArgConfigure = $argConfigName;
			if(TRUE === defined($ArgConfigure.'::'.$argKey)){
				$value = $ArgConfigure::constant($argKey);
			}
		}
		$argConfigName = str_replace('Package', '', $argConfigName);
		if('' !== $argConfigName && strlen($argConfigName) > 0 && class_exists($argConfigName . 'Configure')){
			$ArgConfigure = $argConfigName . 'Configure';
			if(TRUE === defined($ArgConfigure.'::'.$argKey)){
				$value = $ArgConfigure::constant($argKey);
			}
		}
		if('' !== $argConfigName && strlen($argConfigName) > 0 && class_exists($argConfigName)){
			$ArgConfigure = $argConfigName;
			if(TRUE === defined($ArgConfigure.'::'.$argKey)){
				$value = $ArgConfigure::constant($argKey);
			}
		}
		$values[$argConfigName][$argKey] = $value;
	}
	else {
		$value = $values[$argConfigName][$argKey];
	}
	return $value;
}

function modifiyConfig($argKey, $argValue, $argConfigName=''){
	$confName = getConfig('NAME', $argConfigName);
	$confPath = getConfig('PATH', $argConfigName);
	if (is_file($confPath)){
		$confXML = simplexml_load_file($confPath);
		if(isset($confXML->$confName)){
			// CDATAで追加
			// XXX 空にしとく
			$confXML->$confName->$argKey = "";
			$node = dom_import_simplexml($confXML->$confName->$argKey);
			$node->appendChild($node->ownerDocument->createCDATASection($argValue));
			// XML文字列を再生成
			$confXML->asXML($confPath);
			$confFileName = basename($confPath);
			// キャッシュは削除
			if (is_file(getAutoGeneratedPath().$confFileName.'.generated.php')){
				@unlink(getAutoGeneratedPath().$confFileName.'.generated.php');
			}
		}
	}
}

/*------------------------------ 根幹関数定義 ココから ------------------------------*/



/*------------------------------ 以下手続き型処理 ココから ------------------------------*/

// output buffaringを開始する
ob_start('_callbackAndFinalize');

// エラーレポートの設定
$errorReportEnabled = dirname(dirname(__FILE__)).'/.error_report';
if(defined($corefilename . '_ERROR_REPORT_ENABLED')){
	$errorReportEnabled = constant($corefilename . '_ERROR_REPORT_ENABLED');
}
if(file_exists($errorReportEnabled)){
	// errorReportEnabledが有効な時は全てのエラーがエラーログに吐出される！
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 0);
}

// 共通configurationの読み込み
loadConfig(dirname(__FILE__).'/config.xml');

if(defined($corefilename . '_CONFIG_XML_PATH')){
	// 定義されたconfigureの読み込み
	loadConfig(constant($corefilename . '_CONFIG_XML_PATH'));
}
else {
	// 併設されているconfigureの読み込み
	loadConfig(dirname(__FILE__).'/' . $corefilename . '.config.xml');
}

// PROJECT_NAME定数があったらプロジェクト専用のconfigureを探してみて読み込む
if(defined('PROJECT_NAME')){
	loadConfigForConfigName(PROJECT_NAME);
}

/*------------------------------ 手続き型処理 ココまで ------------------------------*/



/*------------------------------ 以下コンソールモード処理 ココから ------------------------------*/

// コマンド一覧
define('HELP', '-h:--help:');
// インストーラーの初期化
define('INSTALL', 'install:initialize:init:NT-D:NEWTYPE-DRIVE:');
// フレームワーク管理ツールの起動
define('MANAGEMENT', 'management:start-manager:LA+:');
// マイグレーション
define('MIGRATION', 'migration:psycho:');

// コマンドオプション定数を一覧化
define('options','HELP:INSTALL:INSTALLER:MIGRATION:');

// フレームワークの起動モードを調べる
// XXX デフォルトはWebブラウザアクセスモードとする
$_consoled = FALSE;
if(isset($_SERVER['SHELL']) && isset($_SERVER['SCRIPT_FILENAME']) && strlen($_SERVER['SCRIPT_FILENAME']) - strlen(basename(__FILE__, '.php')) === strrpos($_SERVER['SCRIPT_FILENAME'], basename(__FILE__, '.php'))){
	// フレームワークのConsolモードを起動する
	$_consoled = TRUE;
}

if(TRUE === $_consoled){
	// コンソールの場合、アウトプットバッファリングを中止し、しないようにする。
	@ob_end_flush();
	echo PHP_EOL;
	echo _SYSTEM_LOGO_;
	echo PHP_EOL;
	echo ' Wellcom ' . corefilename() . '.';
	echo PHP_EOL;
	echo PHP_EOL;
	// コンソールコマンドを確定させる
	// デフォルトはヘルプ扱い
	$_command = HELP;
	if(isset($_SERVER['argv']) && isset($_SERVER['argv'][1])){
		if(FALSE !== strpos(INSTALL, $_SERVER['argv'][1].':')){
			$_command = INSTALL;
			// インストーラーを初期化する処理
			if(!is_file(dirname(dirname(__FILE__)).'/installer/index.php')){
				// インストーラーPHPが何故か存在しない
				echo ' (!)エラー : installer.phpが見つかりませんでした！' . PHP_EOL;
				echo PHP_TAB . ' -> コレは致命的なエラーです' . PHP_EOL;
				echo PHP_TAB . ' installer.phpをこのファイルと同一のディレクトリ配下に置いて下さい' . PHP_EOL;
				echo PHP_TAB . ' 言っている事が分からない場合は、フレームワークのダウンロードからやり直してみて下さい' . PHP_EOL;
			}
			elseif(!isset($_SERVER['argv'][2]) || !isset($_SERVER['argv'][3])){
				// インストールを始めるにはパラメータが足らないエラー
				echo ' (!)エラー : installerの公開ディレクトリ または installerの公開URL を指定して下さい' . PHP_EOL;
				echo PHP_TAB . ' -> php UNICORN install installerの公開ディレクトリ installerの公開URL [or] php UNICORN NT-D  installerの公開ディレクトリ installerの公開URL' . PHP_EOL;
				echo PHP_TAB . ' 例) php UNICORN NT-D '.dirname(dirname(dirname(dirname(__FILE__)))).'/htdocs http://mydomian.com/unicorn/' . PHP_EOL;
			}
			else{
				// バリデーションチェック
				$valid = TRUE;
				if ('psycho' === strtolower($_SERVER['argv'][2])) {
					$_SERVER['argv'][2] = 'MAC';
				}
				if ('burst' === strtolower($_SERVER['argv'][3])) {
					$_SERVER['argv'][3] = 'MAMP';
				}
				if ('mac' === strtolower($_SERVER['argv'][2]) && 'mamp' === strtolower($_SERVER['argv'][3])) {
					// $linkWorkspaceにlnを貼ってしまう
					$workspace = dirname(dirname(realpath($_SERVER['argv'][0])));
					$pjdirname = basename($workspace);
					$linkWorkspace = '/Applications/MAMP/htdocs/workspace';
					// ディレクトリ生成
					if (!is_dir($linkWorkspace)){
						echo 'mkdir -m 777 '.$workspace.PHP_EOL;
						@mkdir($linkWorkspace, 0777, TRUE);
					}
					// リンクを貼る
					if (!file_exists($linkWorkspace.'/'.$pjdirname)){
						echo 'ln -s '.$workspace.' '.$linkWorkspace.PHP_EOL;
						@exec('ln -s '.$workspace.' '.$linkWorkspace);
					}
					// ディレクトリコピー
					$_SERVER['argv'][2] = $workspace;
					$isPHPProcOutput = array();
					exec('ps aux|grep php', $isPHPProcOutput);
					$isPHPProcOutput = implode('<>', $isPHPProcOutput);
					// Mac&MAMP環境専用の特殊インストール
					if ('apache' === strtolower($_SERVER['argv'][4])){
						if (FALSE !== strpos($isPHPProcOutput, 'MAMP/bin/php')){
							// XXX nginxで動いている！？
							echo ' (!)エラー : MAMP-Apacheの自動設定を指定する場合は、Apacheを起動して下さい。' . PHP_EOL;
							exit;
						}
						// Apache用の設定
						// XXX Apacheはパスさえ合っていれば動くのconfなどのコピーはしない
						$_SERVER['argv'][3] = 'http://localhost/'.basename($linkWorkspace).'/'.$pjdirname.'/';
					}
					else if ('nginx' === strtolower($_SERVER['argv'][4])){
						if (FALSE === strpos($isPHPProcOutput, 'MAMP/bin/php')){
							// XXX nginxが動いていない！？
							echo ' (!)エラー : MAMP-Nginxの自動設定を指定する場合は、Nginxを起動して下さい。' . PHP_EOL;
							exit;
						}
						// Nginx用の設定
						if (!is_dir('/Applications/MAMP/conf/nginx/conf.d')){
							echo 'mkdir -m 777 /Applications/MAMP/conf/nginx/conf.d'.PHP_EOL;
							@mkdir('/Applications/MAMP/conf/nginx/conf.d', 0777, TRUE);
						}
						if (!is_file('/Applications/MAMP/conf/nginx/conf.d/nginx-mamp-'.$pjdirname.'.conf')){
							// 設定を書き換えてリンクを貼る
							@copy($linkWorkspace.'/'.$pjdirname.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-fwm.conf', $linkWorkspace.'/'.$pjdirname.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$pjdirname.'.conf');
							file_put_contents($linkWorkspace.'/'.$pjdirname.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$pjdirname.'.conf', str_replace(corefilename(), strtolower($pjdirname), file_get_contents($linkWorkspace.'/'.$pjdirname.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$pjdirname.'.conf')));
							@exec('ln -s '.$linkWorkspace.'/'.$pjdirname.'/supple/setting/NginxWithMAMP/conf.d/nginx-mamp-'.$pjdirname.'.conf /Applications/MAMP/conf/nginx/conf.d/nginx-mamp-'.$pjdirname.'.conf');
						}
						// hostsにローカルドメイン追加
						if (FALSE === strpos(@file_get_contents('/etc/hosts'), strtolower($pjdirname).'api.localhost')){
							@exec('sudo sed -i "" -e $\'1s/^/127.0.0.1       '.strtolower($pjdirname).'api.localhost \\\\\\n/\' /etc/hosts');
							echo 'sudo sed -i "" -e $\'1s/^/127.0.0.1       '.strtolower($pjdirname).'api.localhost \\\\\\n/\' /etc/hosts'.PHP_EOL;
						}
						if (FALSE === strpos(@file_get_contents('/etc/hosts'), strtolower($pjdirname).'.localhost')){
							@exec('sudo sed -i "" -e $\'1s/^/127.0.0.1       '.strtolower($pjdirname).'.localhost \\\\\\n/\' /etc/hosts');
							echo 'sudo sed -i "" -e $\'1s/^/127.0.0.1       '.strtolower($pjdirname).'.localhost \\\\\\n/\' /etc/hosts'.PHP_EOL;
						}
						if (FALSE === strpos(@file_get_contents('/etc/hosts'), 'fwm'.strtolower($pjdirname).'.localhost')){
							@exec('sudo sed -i "" -e $\'1s/^/127.0.0.1       fwm'.strtolower($pjdirname).'.localhost \\\\\\n/\' /etc/hosts');
							echo 'sudo sed -i "" -e $\'1s/^/127.0.0.1       fwm'.strtolower($pjdirname).'.localhost \\\\\\n/\' /etc/hosts'.PHP_EOL;
						}
						// ローカル開発用に自己証明書を設置
						if (!is_dir('/Applications/MAMP/.ssl')){
							@mkdir('/Applications/MAMP/.ssl', 0777, TRUE);
						}
						if (!is_file('/Applications/MAMP/.ssl/self-server.crt')){
							echo $linkWorkspace.'/'.$pjdirname.'/supple/setting/NginxWithMAMP/.ssl/self-server.crt';
							@copy($linkWorkspace.'/'.$pjdirname.'/supple/setting/NginxWithMAMP/.ssl/self-server.crt', '/Applications/MAMP/.ssl/self-server.crt');
							@copy($linkWorkspace.'/'.$pjdirname.'/supple/setting/NginxWithMAMP/.ssl/self-server.key', '/Applications/MAMP/.ssl/self-server.key');
						}
						if (FALSE === strpos(@file_get_contents('/Applications/MAMP/conf/nginx/nginx.conf'), 'conf.d/*.conf')){
							// 元のconf移動
							@rename('/Applications/MAMP/conf/nginx/nginx.conf', '/Applications/MAMP/conf/nginx/nginx.conf.org');
							// conf入れ替え
							@copy($linkWorkspace.'/'.$pjdirname.'/supple/setting/NginxWithMAMP/nginx.conf', '/Applications/MAMP/conf/nginx/nginx.conf');
						}
						// Nginx再起動
						echo 'sudo /Applications/MAMP/Library/bin/nginxctl -s reload'.PHP_EOL;
						flush();
						@exec('sudo /Applications/MAMP/Library/bin/nginxctl -s reload');
// 						sleep(10);
// 						@exec('sudo sh /Applications/MAMP/bin/startNginx.sh');
// 						flush();
// 						echo 'sudo sh /Applications/MAMP/bin/startNginx.sh'.PHP_EOL;
						sleep(5);
						// 設定完了
						echo 'MAC MAMP Nginx用の自動設定が完了しました。'.PHP_EOL;
						echo 'ブラウザが自動的に開き、フレームワーク管理ツールのログイン画面が表示されます。'.PHP_EOL.PHP_EOL;
						echo 'MAC MAMP 自動インストール時の初期ログインIDとパスワードは以下になります。'.PHP_EOL.PHP_EOL;
						echo 'ID: root@super.user'.PHP_EOL;
						echo 'PASS: R00t@sup3r'.PHP_EOL.PHP_EOL;
						echo 'ログイン後にCRUD機能で即時変更する事をオススメします。'.PHP_EOL;
						echo '※既にセットアップ済みの場合や、既にログインユーザーが何かしら存在する場合は初期ユーザーは自動生成されません。'.PHP_EOL.PHP_EOL;
						flush();
						sleep(2);
						// フレームワーク管理ツールを直接起動(DBマイグレーションを実行する)
						exec('open https://fwm'.strtolower($pjdirname).'.localhost/migration.php');
						exit;
					}
					else {
						// XXX それ以外のWebサーバ
					}
					// 
				}
				// 通常のインストール処理
				if (!is_dir($_SERVER['argv'][2])) {
					// 作成出来るか試みる
					if (!mkdir($_SERVER['argv'][2], 0777, true)){
						echo ' (!)エラー : installerの公開ディレクトリには、存在するディレクトリを指定して下さい' . PHP_EOL;
						echo PHP_TAB . ' -> php UNICORN install installerの公開ディレクトリ installerの公開URL [or] php UNICORN NT-D  installerの公開ディレクトリ installerの公開URL' . PHP_EOL;
						echo PHP_TAB . ' 例) php UNICORN NT-D '.dirname(dirname(dirname(dirname(__FILE__)))).'/htdocs/ http://mydomian.com/unicorn/' . PHP_EOL;
						$valid = FALSE;
					}
					@exec('chmod -R 0777 ' .$_SERVER['argv'][2]);
				}
				elseif(FALSE === strpos($_SERVER['argv'][3], 'http')){
					echo ' (!)エラー : installerの公開URLは、「http」から指定して下さい'.PHP_EOL;
					echo PHP_TAB . ' -> php UNICORN install installerの公開ディレクトリ installerの公開URL [or] php UNICORN NT-D  installerの公開ディレクトリ installerの公開URL' . PHP_EOL;
					echo PHP_TAB . ' 例) php UNICORN NT-D '.dirname(dirname(dirname(dirname(__FILE__)))).'/htdocs/ http://mydomian.com/unicorn/' . PHP_EOL;
					$valid = FALSE;
				}
				if (TRUE === $valid){
					// installer.phpをコピー
					if(!dir_copy(dirname(dirname(__FILE__)).'/installer', $_SERVER['argv'][2] . '/setupunicorn')){
						echo ' (!)エラー : installerのコピーに失敗しました！' . PHP_EOL;
						echo PHP_TAB . ' -> コレは致命的なエラーです' . PHP_EOL;
						echo PHP_TAB . ' 指定されたディレクトリ 「'.$_SERVER['argv'][2].'」 に対しての書込権限がないかも知れません' . PHP_EOL;
						echo PHP_TAB . ' 「'.$_SERVER['argv'][2].'」 に適切な書込権限を設定し、再度実行して下さい' . PHP_EOL;
						echo PHP_TAB . PHP_TAB . ' -> 参考) sudo chmod -R 0755 ' . $_SERVER['argv'][2] . PHP_EOL;
					}
					else{
						// インストーラーからみたフレームワークのパスを書き換える
						$baseFrameworkPath = dirname(dirname(__FILE__));
						$installerPath = str_replace('//', '/', $_SERVER['argv'][2] . '/setupunicorn');
						$paths = explode('/', $installerPath);
						// パスが一致するところまでさかのぼり、それを新たなルートパスとし、そこを基準にFrameworkのパスを設定しなおす
						$tmpPath = "/";
						for($tmpPathIdx=0, $pathIdx=1; $pathIdx <= count($paths); $pathIdx++){
							// 空文字は無視
							if(isset($paths[$pathIdx]) && strlen($paths[$pathIdx]) > 0){
								if(0 === strpos($baseFrameworkPath, $tmpPath.$paths[$pathIdx])){
									$tmpPath .= $paths[$pathIdx]."/";
									$tmpPathIdx++;
									// パスが一致したので次へ
									continue;
								}
								else{
									// 一致しなかったので、この前までが一致パスとする
									break;
								}
							}
						}
						$depth = count($paths) - $tmpPathIdx;
						$dirnameStr = '__FILE__';
						for($dirnameIdx=0; $dirnameIdx < $depth; $dirnameIdx++){
							$dirnameStr = 'dirname('.$dirnameStr.')';
						}
						$frameworkPathStr = '$frameworkPath = '.str_replace($tmpPath, $dirnameStr.'."/', $baseFrameworkPath).'";';
						$fwmgrPathStr = '$fwmgrPath = '.str_replace($tmpPath, $dirnameStr.'."/', dirname(dirname(dirname(__FILE__))).'/FrameworkManager').'";';

						// インストーラーのプロダクト名をコアファイルの名前で書き換える
						$peoductNameDefinedLine = 'define("PROJECT_NAME", "'.corefilename().'");';
						$handle = fopen($_SERVER['argv'][2] . '/setupunicorn/index.php', 'r');
						if(FALSE === $handle){
							echo ' (!)エラー : installerのコピーに失敗しました！' . PHP_EOL;
							echo PHP_TAB . ' -> コレは致命的なエラーです' . PHP_EOL;
							echo PHP_TAB . ' 指定されたディレクトリ 「'.$_SERVER['argv'][2].'」 に対しての書込権限がないかも知れません' . PHP_EOL;
							echo PHP_TAB . ' 「'.$_SERVER['argv'][2].'」 に適切な書込権限を設定し、再度実行して下さい' . PHP_EOL;
							echo PHP_TAB . PHP_TAB . ' -> 参考) sudo chmod -R 0755 ' . $_SERVER['argv'][2] . PHP_EOL;
						}
						$targetLine1Num = 15;
						$targetLine2Num = 20;
						$targetLine3Num = 26;
						$readLine = 0;
						$file='';
						while (($buffer = fgets($handle, 4096)) !== false) {
							$readLine++;
							if($targetLine1Num === $readLine){
								// 置換処理
								$file .= $peoductNameDefinedLine . PHP_EOL;
							}
							elseif($targetLine2Num === $readLine){
								// 置換処理
								$file .= $frameworkPathStr . PHP_EOL;
							}
							elseif($targetLine3Num === $readLine){
								// 置換処理
								$file .= $fwmgrPathStr . PHP_EOL;
							}
							else {
								$file .= $buffer;
							}
						}
						fclose($handle);
						file_put_contents($_SERVER['argv'][2] . '/setupunicorn/index.php', $file);
						// インストーラーが標準の位置では無い場合は印をつける
						if(str_replace('//', '/', $_SERVER['argv'][2] . '/setupunicorn/index.php') !== str_replace('//', '/', dirname(dirname(__FLE__)).'/installer/index.php')){
							@touch($_SERVER['argv'][2] . '/setupunicorn/.copy');
						}
						$installerURL = str_replace('//setupunicorn/', '/setupunicorn/', $_SERVER['argv'][3].'/setupunicorn/');
						if(isset($_SERVER['argv'][4]) && 'debug' === $_SERVER['argv'][4]){
							$installerURL .= '?debug=1';
						}
						// ブラウザを起動して、インストーラーを表示
						echo ' macの場合、ブラウザが自動で起動します。少しお待ち下さい' . PHP_EOL;
						echo ' mac以外をお使いの方は、以下のURLをコピーしてブラウザで実行して下さい' . PHP_EOL;
						echo PHP_EOL;
						echo PHP_TAB . ' ' . $installerURL . PHP_EOL;
						exec('open ' . $installerURL);
					}
				}
			}
		}
	}
	if(HELP === $_command){
		// 説明を表示
		echo PHP_EOL;
		echo PHP_EOL;
		echo ' フレームワークのインストールを提供します。' . PHP_EOL;
		echo ' 以下のコマンドを実行して下さい。' . PHP_EOL;
		echo ' Webベースナビゲーションのインストーラー"NT-D"が起動します。' . PHP_EOL;
		echo PHP_EOL;
		echo PHP_EOL;
		echo ' (!)お使いのPCがMACで、且つローカル開発環境"MAMP"にインストールする場合は、全てを自動で設定するコマンドとモードがお使い頂けます。' . PHP_EOL;
		echo PHP_EOL;
		echo ' MAC MAMPで、WebサーバにNginxを利用する場合'.PHP_EOL;
		echo ' php '.dirname(dirname(dirname(__FILE__))).'/'.basename(__FILE__, '.php').' NT-D Psycho Burst Apache'.PHP_EOL;
		echo PHP_EOL;
		echo ' MAC MAMPで、WebサーバにApacheを利用する場合'.PHP_EOL;
		echo ' php '.dirname(dirname(dirname(__FILE__))).'/'.basename(__FILE__, '.php').' NT-D Psycho Burst Nginx'.PHP_EOL;
		echo PHP_EOL;
		echo PHP_EOL;
		echo ' それ以外の場合'.PHP_EOL;
		// 		echo ' php '.dirname(dirname(dirname(__FILE__))).'/'.basename(__FILE__, '.php').' NT-D' . PHP_EOL;
// 		echo ' OR'.PHP_EOL;
// 		echo ' php '.dirname(dirname(dirname(__FILE__))).'/'.basename(__FILE__, '.php').' install' . PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_EOL;
// 		echo ' ■フレームワークのインストーラーのWeb公開ディレクトリを指定する場合は以下のコマンドを実行して下さい。' . PHP_EOL;
		echo ' php '.dirname(dirname(dirname(__FILE__))).'/'.basename(__FILE__, '.php').' install [インストーラーのWeb公開ディレクトリ] [インストーラーのWeb公開URL]'.PHP_EOL;
		echo ' OR'.PHP_EOL;
		echo ' php '.dirname(dirname(dirname(__FILE__))).'/'.basename(__FILE__, '.php').' NT-D  [インストーラーのWeb公開ディレクトリ] [インストーラーのWeb公開URL]' . PHP_EOL;
		echo PHP_EOL;
		echo ' 例) php '.dirname(dirname(dirname(__FILE__))).'/'.basename(__FILE__, '.php').' NT-D '.dirname(dirname(dirname(dirname(__FILE__)))).'/htdocs/ http://mydomian.com/unicorn/' . PHP_EOL;
		echo PHP_EOL;
		echo PHP_EOL;
		echo ' ※フレームワークのインストーラーをデバッグモードで実行する場合は「 debug」をコマンドの末尾に付加して下さい。' . PHP_EOL;
		echo ' 例) php '.dirname(dirname(dirname(__FILE__))).'/'.basename(__FILE__, '.php').' NT-D '.dirname(dirname(dirname(dirname(__FILE__)))).'/htdocs/ http://mydomian.com/unicorn/ debug' . PHP_EOL;
		echo PHP_EOL;
		echo PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_EOL;
// 		echo ' フレームワークのコマンドラインツールは以下の事を提供します' . PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_EOL;
// 		echo ' 1.(NT-Dシステムによる)フレームワークインストールナビゲーション' . PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_TAB . ' ■フレームワークのインストーラーをWeb公開ディレクトリに配置し、インストーラーのURLを払い出す' . PHP_EOL;
// 		echo PHP_TAB . ' -> php UNICORN install installerの公開ディレクトリ installerの公開URL [or] php UNICORN NT-D  installerの公開ディレクトリ installerの公開URL' . PHP_EOL;
// 		echo PHP_TAB . ' 例) php UNICORN NT-D '.dirname(dirname(dirname(dirname(__FILE__)))).'/htdocs/ http://mydomian.com/unicorn/' . PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_TAB . ' ■フレームワークのインストーラーのURLをデバッグモードフラグ付きで払い出す' . PHP_EOL;
// 		echo PHP_TAB . ' -> php UNICORN NT-D installerの公開ディレクトリ installerの公開URL debug' . PHP_EOL;
// 		echo PHP_TAB . ' 例) php UNICORN NT-D '.dirname(dirname(dirname(dirname(__FILE__)))).'/htdocs/ http://mydomian.com/unicorn/ debug' . PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_EOL;
// 		// 		echo ' 2.(LA+システムによる)フレームワークマネージメント' . PHP_EOL;
// 		// 		echo PHP_EOL;
// 		// 		echo PHP_TAB . ' -> php UNICORN management [or] php UNICORN LA+' . PHP_EOL;
// 		// 		echo PHP_EOL;
// 		// 		echo PHP_EOL;
// 		echo ' 2.(ORMapper「PsychoFrame」を利用した)データベースマイグレーション(※準備中)' . PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_TAB . ' ※ 前提条件 : フレームワークのインストールを完了し、データベース接続設定が完了している事' . PHP_EOL;
// 		echo PHP_TAB . ' 分からない場合はフレームワーク管理機能を先ず利用してみて下さい' . PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_TAB . ' ■全てのテーブルをマイグレーションする' . PHP_EOL;
// 		echo PHP_TAB . ' -> php UNICORN migration [or] php UNICORN PsychoJack' . PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_TAB . ' ■指定のテーブルをマイグレーションする' . PHP_EOL;
// 		echo PHP_TAB . ' -> php UNICORN migration テーブル名 [or] php UNICORN PsychoJack テーブル名' . PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_TAB . ' ■全てのマイグレーションを適用する' . PHP_EOL;
// 		echo PHP_TAB . ' -> php UNICORN migration up [or] php UNICORN PsychoBurst' . PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_TAB . ' ■指定のテーブルにマイグレーションを適用する' . PHP_EOL;
// 		echo PHP_TAB . ' -> UNICORN migration up テーブル名 [or] php UNICORN PsychoBurst テーブル名' . PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_TAB . ' ■マイグレーションの一覧を表示する' . PHP_EOL;
// 		echo PHP_TAB . ' -> UNICORN migration list [or] php UNICORN PsychoFrame' . PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_EOL;
// 		echo PHP_EOL .' 必要なコマンドを上記から見つけ出し、再度コマンドを入力して実行して下さい' . PHP_EOL;
// 		echo PHP_EOL;
	}
	exit(PHP_EOL.PHP_EOL);
}

/*------------------------------ コンソールモード処理 ココまで ------------------------------*/



?>