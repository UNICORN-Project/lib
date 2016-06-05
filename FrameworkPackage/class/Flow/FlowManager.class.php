<?php

class FlowManager
{
	public static $params = array('post'=>NULL, 'get'=>NULL, 'request'=>NULL, 'view'=>NULL, 'backflow'=>NULL);

	/**
	 * 定義情報をチェックする
	 * @param unknown $argContainerXML
	*/
	public static function validate($argContainerXML){
		// frowにはsection指定は必須
		return TRUE;
	}

	public static function getURIFlowTarget(){
		$targetPath = '';
		// 現在アクセスされているURIベースのターゲットパスを特定する
		if(@isset(Core::$CurrentController) && @isset(Core::$CurrentController->section)){
			$uris = explode('?', $_SERVER['REQUEST_URI']);
			$uri = $uris[0];
			$nowURI = strtolower($uri);
			$searchURI = str_replace('_', '-', strtolower(Core::$CurrentController->section));
			if (1 < strpos($nowURI, $searchURI)){
				// 上位階層がある状態でリクエストされているので、その階層を全部取り出す
				$targetPath = substr($nowURI, 0, strpos($nowURI, $searchURI));
				if (0 === strpos($targetPath, '/')){
					$targetPath = substr($targetPath, 1);
				}
			}
		}
		return $targetPath;
	}

	public static function reverseRewriteURL($argAction, $argQuery='', $argSSLRequired=FALSE){
		$action= $argAction;
		if(isset($_SERVER['ReverseRewriteRule'])){
			$reverseRules = explode(' ', str_replace('＄', '$', $_SERVER['ReverseRewriteRule']));
			if(count($reverseRules) == 2){
				debug("flow reverseRules=".$reverseRules[0]);
				$reverseAction = preg_replace('/' . $reverseRules[0] . '/', $reverseRules[1], $action);
				debug("flow reverseAction=".$action);
				debug("flow reverseAction=".$reverseAction);
				if(NULL !== $reverseAction && strlen($reverseAction) > 0){
					$action = strtolower($reverseAction);
				}
			}
		}
		$query = '';
		if('' !== $argQuery){
			$query = $argQuery;
		}
		else {
			if (isset($_GET) && 0 < count($_GET)){
				foreach($_GET as $key => $val){
					if('_c_' !== $key && '_a_' !== $key && '_o_' !== $key){
						if(strlen($query) > 0){
							$query .= '&';
						}
						$query .= $key.'='.$val;
					}
				}
			}
		}
		if (!(isset($_GET['_o_']) && 0 < strlen($_GET['_o_']))){
			$_GET['_o_'] = 'html';
		}
		if('' !== $query){
			if(FALSE === strpos($action, '.'.$_GET['_o_'].'?')){
				$query = '?'.$query;
			}
			else {
				$query = '&'.$query;
			}
		}
		if ('?' === trim($query)){
			$query = '';
		}
		if (NULL !== $argSSLRequired && FALSE !== $argSSLRequired){
			if (0 === strpos($action, './')){
				$action = substr($action, 2);
			}
			if (0 === strpos($action, '/')){
				$action = substr($action, 1);
			}
			$protocol = '';
			if (1 !== getLocalEnabled() && TRUE === $argSSLRequired){
				$protocol = 'https:';
			}
			// 現在アクセスされているURLと同じ階層にターゲットする
			$target = self::getURIFlowTarget();
			if (0 === strlen($target) || false === strpos($action, $target)){
				$action = $target.$action;
			}
			$action = $protocol.'//'.str_replace('//', '/', $_SERVER["HTTP_HOST"].'/'.$action);
		}
		else {
			$action = str_replace('//', '/', $action);
		}
		return $action.$query;
	}

	/**
	 * 登録されているbackFlowをクリアする
	 */
	public static function clearBackFlow(){
		if(isset($_POST['flowpostformsection-backflow-section'])){
			unset($_POST['flowpostformsection-backflow-section']);
		}
		if(isset($_POST['flowpostformsection-backflow-section-query'])){
			unset($_POST['flowpostformsection-backflow-section-query']);
		}
		if(isset(Flow::$params['post']['flowpostformsection-backflow-section'])){
			unset(Flow::$params['post']['flowpostformsection-backflow-section']);
		}
		if(isset(Flow::$params['post']['flowpostformsection-backflow-section-query'])){
			unset(Flow::$params['post']['flowpostformsection-backflow-section-query']);
		}
		if(isset($_COOKIE['flowpostformsection-backflow-section'])){
			unset($_COOKIE['flowpostformsection-backflow-section']);
			setcookie('flowpostformsection-backflow-section', '', time() - 3600, '/');
		}
		if(isset($_COOKIE['flowpostformsection-backflow-section-query'])){
			unset($_COOKIE['flowpostformsection-backflow-section-query']);
			setcookie('flowpostformsection-backflow-section-query', '', time() - 3600, '/');
		}
	}

	/**
	 * 次のFlowを特定し、ロードし、そのクラス名を返却する
	 * @param string クラス名
	 * @param string ターゲットファイルパスのヒント
	 * @return mixed 成功時は対象のクラス名 失敗した場合はFALSEを返す
	 */
	public static function loadNextFlow($argClassName = NULL, $argTargetPath = '', $argRedirect=FALSE){
		$query = '';
		// 先ずbackflowなのかどうか
		if('backflow' === strtolower($argClassName)){
			// backflowが特定出来無かった時ように強制的にIndexを指定しておく
			$argClassName = 'index';
			if (defined('PROJECT_NAME') && 0 < strlen(PROJECT_NAME)){
				$defaultBackFlow = getConfig('DEFAULT_BACKFLOW', PROJECT_NAME);
				if (0 < strlen($defaultBackFlow)){
					$argClassName = $defaultBackFlow;
				} 
			}
			if(strlen($argTargetPath) > 0){
				$argClassName = $argTargetPath.'/'.$argClassName;
			}
			// PostパラメータからBackflowを特定する
			if(isset($_POST['flowpostformsection-backflow-section'])){
				$argClassName = $_POST['flowpostformsection-backflow-section'];
			}
			else if(isset($_COOKIE['flowpostformsection-backflow-section'])){
				$argClassName = $_COOKIE['flowpostformsection-backflow-section'];
				setcookie('flowpostformsection-backflow-section', '', time() - 3600, '/');
			}
			// backflowはリダイレクトポスト(307リダイレクト)
			if(isset($_POST['flowpostformsection-backflow-section-query']) && strlen($_POST['flowpostformsection-backflow-section-query']) > 0){
				$query = $_POST['flowpostformsection-backflow-section-query'];
			}
			else if(isset($_COOKIE['flowpostformsection-backflow-section-query']) && strlen($_COOKIE['flowpostformsection-backflow-section-query']) > 0){
				$query = $_COOKIE['flowpostformsection-backflow-section-query'];
				setcookie('flowpostformsection-backflow-section-query', '', time() - 3600, '/');
			}
			if (isset($_SERVER['__loginID__'])){
				setcookie('__loginID__', $_SERVER['__loginID__'], time() + 60, '/');
			}
			// XXX Redirectを今のところ強制
			$argRedirect = TRUE;
		}
		if (TRUE === $argRedirect){
			$locationStatus = 302;
			$action = $argClassName;
			if (FALSE === (0 === strpos($argClassName, 'http://') || 0 === strpos($argClassName, 'https://')) && FALSE === strpos($argClassName, '.html')){
				$argClassName = str_replace('//', '/', str_replace('//', '/', $argClassName));
				if (isset($_POST['backflow']) && is_array($_POST['backflow']) && 0 < count($_POST['backflow'])){
					$locationStatus = 307;
				}
				$output = $_GET['_o_'];
				if ('shtml' !== $output && 'html' !== $output && 'php' !== $output && '' !== $output){
					$output = 'html';
				}
				$action = self::reverseRewriteURL('?_c_=' . str_replace('_', '-', ucfirst($argClassName)) . '&_o_='.$output, $query);
			}
			if (FALSE !== strpos($action, '://')){
				$action = str_replace('//', '/', str_replace('//', '/', $action));
			}
			else if (false === strpos($action, '/')){
				// ターゲット指定が無いので相対パスにしてあげる
				$action = str_replace('.//', './', './'.$action);
			}
			header('Location: '.$action, TRUE, $locationStatus);
			return TRUE;
		}
		$className = Core::loadMVCModule(str_replace('-', '_', ucfirst($argClassName)), FALSE, $argTargetPath);
		debug('backflowClass='.var_export($className,true));
		return $className;
	}

	/**
	 * 定義情報をチェックする
	 * @param string XMLファイルのパス文字列 or XML文字列
	 */
	public static function generate($argTarget, $argSection, $argBasePathTarget=''){
		$filepathUsed = FALSE;
		$targetXML = $argTarget;
		if(1024 >= strlen($argTarget) && TRUE === file_exists_ip($argTarget)){
			// ファイルパス指定の場合はファイルの中身を文字列として一旦取得
			$filepathUsed = TRUE;
			$targetXML = file_get_contents($argTarget);
		}
		// クラス名を確定する
		$className = $argSection;
		if(TRUE === $filepathUsed){
			// オートジェネレートフラグの取得(フレームワークのコアから貰う)
			$autoGenerateFlag = getAutoGenerateEnabled();
			if(TRUE === $autoGenerateFlag){
				$generatedClassPath = self::_getAutogenerateFilePath($argTarget);
				// unlinkされたか
				if(FALSE === resolveUnlinkAutoGeneratedFile($generatedClassPath)){
					// クラス名を返して終了
					return $className;
				}
			}
		}
		// XML文字列を、XMLオブジェクト化
		libxml_use_internal_errors(TRUE);
		$FlowXML = simplexml_load_string($targetXML);
		if(FALSE === $FlowXML){
			throw new LibXMLException(libxml_get_errors());
		}
		// FlowXMLとして正しいかどうか
		if(TRUE === self::validate($FlowXML)){
			// $argSectionがIndex_authedだったとして、index-authedに変換される
			//$targetSection = str_replace('_', '-', ucfirst($argSection));
			// FlowXMLに基いてコントローラクラスを自動生成する
			$classDef = '';
			foreach ($FlowXML->children() as $firstNode) {
				// firstNodeのid属性から暮らす名を決める
				// idがindex-authedだったとして、Index_authedに変換される
				$tmpAttr = $firstNode->attributes();
				$sectionClassName = str_replace('-', '_', ucfirst($tmpAttr['id']));
				// 上位クラスは暫定でWebコントローラと言う事にする
				$extends = ' extends WebFlowControllerBase';
				// 上位クラスが指定されているかどうか
				if(isset($tmpAttr['extends']) && strlen($tmpAttr['extends']) > 0){
					// そのまま採用
					$extends = ' extends ' . $tmpAttr['extends'];
				}
				// コントローラセクションのタイプ属性が指定されているかどうか
				elseif(isset($tmpAttr['type']) && strlen($tmpAttr['type']) > 0){
					$typeStr = $tmpAttr['type'];
					// XXX $typeStrはSimpleXMLElementなので===の完全一致では動作しない！
					if('web' == $typeStr){
						$extends = ' extends WebFlowControllerBase';
					}
					elseif('fwm' == $typeStr){
						$extends = ' extends FwmFlowBase';
					}
					elseif('api' == $typeStr){
						$extends = ' extends APIControllerBase';
					}
					elseif('rest' == $typeStr){
						$extends = ' extends RestControllerBase';
					}
					elseif('image' == $typeStr){
						$extends = ' extends ImageControllerBase';
					}
				}
				if(isset($tmpAttr['permission']) && strlen($tmpAttr['permission']) > 0){
					// そのまま採用
					$permission = $tmpAttr['permission'];
				}
				$prepends = array();
				$methods = array();
				$appends = array();
				$exceptions = array();
				// メソッド定義
				foreach ($firstNode->children() as $methodNode) {
					// 2次元目はメソッド定義
					$methodName = $methodNode->getName();
					// constructとdestructだけをマジックメソッドのマップとしてサポートする
					if('construct' == $methodName || 'destruct' == $methodName){
						$methodName = '__' . $methodName;
					}
					if ($methodName === 'exception' || $methodName === 'append' || $methodName === 'prepend'){
						$method = '';
					}
					else {
						$method = PHP_TAB . 'function ' . $methodName . '(%s){' . PHP_EOL;
						$method .= PHP_TAB . PHP_TAB . 'try' . PHP_EOL;
						$method .= PHP_TAB . PHP_TAB . '{' . PHP_EOL;
						$method .= '[[[:prepend:]]]';
						$method .= '%s';
						$method .= '[[[:append:]]]';
						$method .= PHP_TAB . PHP_TAB . '}' . PHP_EOL;
						$method .= PHP_TAB . PHP_TAB . 'catch (Exception $Exception){' . PHP_EOL;
						$method .= '[[[:exception:]]]';
						$method .= PHP_TAB . PHP_TAB . '}' . PHP_EOL;
						$method .= PHP_TAB . PHP_TAB . 'return TRUE;' . PHP_EOL;
						$method .= PHP_TAB . '}' . PHP_EOL;
					}
					$methodArg = '';
					$methodArgs = $methodNode->attributes();
					if(count($methodArgs) > 0){
						$methodArg = array();
						foreach($methodArgs as $key => $val){
							$arg = '';
							$arg .= '$' . $key . ' = ' . self::_resolveValue($val);
							$methodArg[] = $arg;
						}
						$methodArg = implode(', ', $methodArg);
					}
					// 3次元目は以降はネスト構造のソースコード定義
					$code = '';
					if($methodName !== 'exception' && $methodName !== 'append' && $methodName !== 'prepend' && isset($extends) && strlen($extends) > 0 && TRUE === (' extends WebFlowControllerBase' == $extends || ' extends FwmFlowBase' == $extends)){
						// パミッションの補完
						if (' extends FwmFlowBase' == $extends && isset($permission) && 0 < strlen($permission)){
							$code .= PHP_TAB . PHP_TAB . PHP_TAB . '$this->permission = '.$permission.';' . PHP_EOL;
						}
						// WebFlowBaseのinitをメソッドの頭で必ず呼ぶ
						$code .= PHP_TAB . PHP_TAB . PHP_TAB . '$autoValidated = $this->_initWebFlow();' . PHP_EOL;
					}
					foreach ($methodNode->children() as $codeNode) {
						$code .= self::_generateCode($codeNode, $argBasePathTarget);
					}
					if ($methodName === 'exception' && 0 < count($methods)){
						$exceptions[count($methods) -1] = $code;
					}
					elseif ($methodName === 'append' && 0 < count($methods)){
						$appends[count($methods) -1] = $code;
					}
					elseif ($methodName === 'prepend'){
						$prepends[count($methods)] = $code;
					}
					else {
						$method = sprintf($method, $methodArg, $code);
						$methods[] = $method;
						$appends[] = '';
						if (!isset($prepends[count($methods) - 1])){
							$prepends[count($methods) - 1] = '';
						}
						$exceptions[] = PHP_TAB . PHP_TAB . PHP_TAB . 'throw $Exception;' . PHP_EOL;
					}
				}
				// クラス定義つなぎ合わせ
				$classDef .= PHP_EOL . 'class ' . $sectionClassName.'Flow' . $extends . PHP_EOL;
				$classDef .= '{' . PHP_EOL . PHP_EOL;
				$classDef .= PHP_TAB . 'public $section=\''.$sectionClassName.'\';' . PHP_EOL;
				$classDef .= PHP_TAB . 'public $target=\''.$argBasePathTarget.'\';' . PHP_EOL . PHP_EOL;
				for($methodIdx=0; count($methods) > $methodIdx; $methodIdx++){
					$methods[$methodIdx] = str_replace('[[[:prepend:]]]', $prepends[$methodIdx], $methods[$methodIdx]);
					$methods[$methodIdx] = str_replace('[[[:append:]]]', $appends[$methodIdx], $methods[$methodIdx]);
					$methods[$methodIdx] = str_replace('[[[:exception:]]]', $exceptions[$methodIdx], $methods[$methodIdx]);
					$classDef .= $methods[$methodIdx];
				}
				$classDef .= '}' . PHP_EOL;
			}
			// オートジェネレートチェック
			if(TRUE === $filepathUsed && TRUE === $autoGenerateFlag){
				// 空でジェネレートファイルを生成
				@file_put_contents($generatedClassPath, '');
				// ジェネレート
				generateClassCache($generatedClassPath, $argTarget, $classDef, $className.'Flow');
				// 静的ファイル化されたクラスファイルを読み込んで終了
				// fatal errorがいいのでrequireする
				//require_once $generatedClassPath;
			}
			// オートジェネレートが有効だろうが無効だろうがココまで来たらクラス定義の実体化
			eval($classDef);
			return $className;
		}
		return FALSE;
	}

	/**
	 * ジェネレートファイルのターゲットパスを取得する
	 * @param unknown $argNode
	 */
	public static function _getAutogenerateTargetPath($argTarget){
		$targetPath = str_replace('.flow.xml', 'Flow', str_replace('..','.', str_replace('/','.', substr($argTarget, strlen(Core::$flowXMLBasePath)))));
		if(0 === strpos($targetPath, '.')){
			$targetPath = substr($targetPath, 1);
		}
		return $targetPath;
	}

	/**
	 * ジェネレートファイルのパスを取得する
	 * @param unknown $argNode
	 */
	public static function _getAutogenerateFilePath($argTarget){
		return getAutoGeneratedPath() . self::_getAutogenerateTargetPath($argTarget) . '.generated.inc.php';
	}

	/**
	 * ノードを処理に分解する
	 * @param unknown $argNode
	 */
	public static function _generateCode($argCodeNode, $argBasePathTarget='', $argDepth=1){
		if(isset($argCodeNode) && NULL !== $argCodeNode){
			// TAB
			$tab = PHP_TAB.PHP_TAB;
			for ($depthIdx=0; $depthIdx < $argDepth; $depthIdx++){
				$tab .= PHP_TAB;
			}
			$code = $tab;
			$codeType = $argCodeNode->getName();
			$tmpAttr = $argCodeNode->attributes();
			// コードの種類に応じて処理を分岐
			if('flow' === $codeType){
				// 次のフローを実行する処理を生成
				if(isset($tmpAttr['section']) && strlen($tmpAttr['section']) > 0){
					$sectionID = $tmpAttr['section'];
					$target = $argBasePathTarget;
					$redirect = 'FALSE';
					if(FALSE !== strpos($sectionID, '/')){
						// sectionとtargetを分割する
						$targetTmp = explode('/', $sectionID);
						// 最後だけをsectionIDとして使う
						$sectionID = $targetTmp[count($targetTmp)-1];
						unset($targetTmp[count($targetTmp)-1]);
						$target = implode('/', $targetTmp) . '/';
					}
					elseif(isset($tmpAttr['target']) && strlen($tmpAttr['target']) > 0){
						$target = $tmpAttr['target'] . '/';
					}
					if(isset($tmpAttr['redirect']) && strlen($tmpAttr['redirect']) > 0 && TRUE === ('true' === strtolower($tmpAttr['redirect']) || '1' === strtolower($tmpAttr['redirect']))){
						$redirect = 'TRUE';
					}
					$code .= '$className = Flow::loadNextFlow(\'' . $sectionID . '\', \'' . $target . '\', ' . $redirect . ');' . PHP_EOL;
					$code .= $tab . 'if (TRUE === $className){' . PHP_EOL;
					$code .= $tab . PHP_TAB . 'return TRUE;' . PHP_EOL;
					$code .= $tab . '}' . PHP_EOL;
					$code .= $tab . '$instance = new $className();' . PHP_EOL;
					$code .= $tab . 'if (isset($_POST[\'flowpostformsection\']) && str_replace(\'_\', \'-\', ucfirst($_POST[\'flowpostformsection\'])) != $instance->section){' . PHP_EOL;
					// POSTパラメータを分離するため、今ポストされているものはバックフロー用のパラメータにしておく
					$code .= $tab . PHP_TAB . '$this->_convertBackflowForm($this->section, $_POST);' . PHP_EOL;
					$code .= $tab . '}' . PHP_EOL;
					// Flowなので、処理の移譲先のコントローラに自身のクラス変数を適用し直す
					$code .= $tab . '$instance->controlerClassName = $className;' . PHP_EOL;
				}

				$code .= $tab . '$instance->httpStatus = $this->httpStatus;' . PHP_EOL;
				$code .= $tab . '$instance->outputType = $this->outputType;' . PHP_EOL;
				$code .= $tab . '$instance->jsonUnescapedUnicode = $this->jsonUnescapedUnicode;' . PHP_EOL;
				$code .= $tab . '$instance->deviceType = $this->deviceType;' . PHP_EOL;
				$code .= $tab . '$instance->appVersion = $this->appVersion;' . PHP_EOL;
				$code .= $tab . '$instance->appleReviewd = $this->appleReviewd;' . PHP_EOL;
				$code .= $tab . '$instance->mustAppVersioned = $this->mustAppVersioned;' . PHP_EOL;
				// Flowクラスのメソッド実行の追記
				$method = 'execute';
				if(isset($tmpAttr['method']) && strlen($tmpAttr['method']) > 0){
					$method = $tmpAttr['method'];
				}
				$code .= $tab . '$res = $instance->' . $method . '(' . self::_resolveArgs($tmpAttr) .  ');' . PHP_EOL;
				// Flowなので、処理を移譲した先のコントローラの最終結果クラス変数を自分自身に適用し直す
				$code .= $tab . '$this->httpStatus = $instance->httpStatus;' . PHP_EOL;
				$code .= $tab . '$this->outputType = $instance->outputType;' . PHP_EOL;
				$code .= $tab . '$this->jsonUnescapedUnicode = $instance->jsonUnescapedUnicode;' . PHP_EOL;
				$code .= $tab . '$this->deviceType = $instance->deviceType;' . PHP_EOL;
				$code .= $tab . '$this->appVersion = $instance->appVersion;' . PHP_EOL;
				$code .= $tab . '$this->appleReviewd = $instance->appleReviewd;' . PHP_EOL;
				$code .= $tab . '$this->mustAppVersioned = $instance->mustAppVersioned;' . PHP_EOL;
				$code .= $tab . 'return $res;';
			}
			elseif('rest' === $codeType){
				// 内部のrestに処理を回す
				$executor = 'execute';
				$resourceBox = '$resource';
				$targetResource = '';
				$method = 'GET';
				if(isset($tmpAttr['assign']) && strlen($tmpAttr['assign']) > 0){
					$resourceBox = '$'.$tmpAttr['assign'];
				}
				if(isset($tmpAttr['execute']) && strlen($tmpAttr['execute']) > 0){
					$executor = $tmpAttr['execute'];
				}
				if(isset($tmpAttr['resource']) && strlen($tmpAttr['resource']) > 0){
					$targetResource = $tmpAttr['resource'];
				}
				if(isset($tmpAttr['method']) && strlen($tmpAttr['method']) > 0){
					$method = $tmpAttr['method'];
				}
				$code .= '$params = NULL;' . PHP_EOL;
				if(isset($tmpAttr['params']) && strlen($tmpAttr['params']) > 0){
					$code .= $tab . '$params = @json_decode(\''.$tmpAttr['params'].'\', TRUE);' . PHP_EOL;
					$code .= $tab . 'if (FALSE === (is_array($params) && 0 < count($params))){' . PHP_EOL;
					$code .= $tab . PHP_TAB . '$params = NULL;' . PHP_EOL;
					$code .= $tab . '}' . PHP_EOL;
				}
				$code .= $tab . '$controlerClassName = Core::loadMVCModule (\'Rest\', FALSE, \'\', TRUE);' . PHP_EOL;
				$code .= $tab . '$_SERVER[\'ALLOW_ALL_WHITE\'] = TRUE;' . PHP_EOL;
				$code .= $tab . '$Rest = new $controlerClassName ();' . PHP_EOL;
				$code .= $tab . '$targetResource = '.self::_resolveValue($targetResource).';' . PHP_EOL;
				$code .= $tab . 'if (isset(Flow::$params) && isset(Flow::$params[\''.strtolower($method).'\'])) {' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'if (NULL === $params) {' . PHP_EOL;
				$code .= $tab . PHP_TAB . PHP_TAB . '$params = Flow::$params[\''.strtolower($method).'\'];' . PHP_EOL;
				$code .= $tab . PHP_TAB . '}' . PHP_EOL;
				if(isset($tmpAttr['margeparam']) && strlen($tmpAttr['margeparam']) > 0 && TRUE === ('true' === strtolower($tmpAttr['margeparam']) || '1' === strtolower($tmpAttr['margeparam']))){
					$code .= $tab . PHP_TAB . 'else {' . PHP_EOL;
					$code .= $tab . PHP_TAB . PHP_TAB . '$params = array_merge($params, Flow::$params[\''.strtolower($method).'\']);' . PHP_EOL;
					$code .= $tab . PHP_TAB . '}' . PHP_EOL;
				}
				$code .= $tab . '}' . PHP_EOL;
				if ('PUT' === strtoupper($method) || 'POST' === strtoupper($method)){
					$code .= $tab . $resourceBox.' = $Rest->'.$executor.' ($targetResource, $params, \''.strtoupper($method).'\');' . PHP_EOL;
				}
				else {
					$code .= $tab . $resourceBox.' = $Rest->'.$executor.' ($targetResource, NULL, \''.strtoupper($method).'\', $params);' . PHP_EOL;
				}
				$code .= $tab . 'if (FALSE === '.$resourceBox.') {' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'throw new Exception (__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);' . PHP_EOL;
				$code .= $tab . '}' . PHP_EOL;
			}
			elseif('flowpostformsectionerror' === $codeType){
				$code .= 'if(NULL === Flow::$params[\'view\']){' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'Flow::$params[\'view\'] = array();' . PHP_EOL;
				$code .= $tab . '}' . PHP_EOL;
				$code .= $tab . 'Flow::$params[\'view\'][] = array(\'div[flowpostformsectionerror]\' => \'' . $argCodeNode . '\');';
			}
			elseif('cancelthisbackflow' === $codeType){
				$code .= 'if(count(Flow::$params[\'backflow\']) > 0){' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'unset(Flow::$params[\'backflow\'][count(Flow::$params[\'backflow\']) -1]);' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'if (isset($_POST[\'backflow\']) && isset($_POST[\'backflow\'][count(Flow::$params[\'backflow\'])])) {' . PHP_EOL;
				$code .= $tab . PHP_TAB . PHP_TAB . 'unset($_POST[\'backflow\'][count(Flow::$params[\'backflow\']) -1]);' . PHP_EOL;
				$code .= $tab . PHP_TAB . '}' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'if (isset($_POST[\'mail\'])) {' . PHP_EOL;
				$code .= $tab . PHP_TAB . PHP_TAB . 'unset($_POST[\'mail\']);' . PHP_EOL;
				$code .= $tab . PHP_TAB . '}' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'if (isset($_POST[\'pass\'])) {' . PHP_EOL;
				$code .= $tab . PHP_TAB . PHP_TAB . 'unset($_POST[\'pass\']);' . PHP_EOL;
				$code .= $tab . PHP_TAB . '}' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'if (isset($_POST[\'passwd\'])) {' . PHP_EOL;
				$code .= $tab . PHP_TAB . PHP_TAB . 'unset($_POST[\'passwd\']);' . PHP_EOL;
				$code .= $tab . PHP_TAB . '}' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'if (isset($_POST[\'password\'])) {' . PHP_EOL;
				$code .= $tab . PHP_TAB . PHP_TAB . 'unset($_POST[\'password\']);' . PHP_EOL;
				$code .= $tab . PHP_TAB . '}' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'if (isset(Flow::$params[\'post\'][\'mail\'])) {' . PHP_EOL;
				$code .= $tab . PHP_TAB . PHP_TAB . 'unset(Flow::$params[\'post\'][\'mail\']);' . PHP_EOL;
				$code .= $tab . PHP_TAB . '}' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'if (isset(Flow::$params[\'post\'][\'pass\'])) {' . PHP_EOL;
				$code .= $tab . PHP_TAB . PHP_TAB . 'unset(Flow::$params[\'post\'][\'pass\']);' . PHP_EOL;
				$code .= $tab . PHP_TAB . '}' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'if (isset(Flow::$params[\'post\'][\'passwd\'])) {' . PHP_EOL;
				$code .= $tab . PHP_TAB . PHP_TAB . 'unset(Flow::$params[\'post\'][\'passwd\']);' . PHP_EOL;
				$code .= $tab . PHP_TAB . '}' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'if (isset(Flow::$params[\'post\'][\'password\'])) {' . PHP_EOL;
				$code .= $tab . PHP_TAB . PHP_TAB . 'unset(Flow::$params[\'post\'][\'password\']);' . PHP_EOL;
				$code .= $tab . PHP_TAB . '}' . PHP_EOL;
				$code .= $tab. '}' . PHP_EOL;
			}
			elseif('clearbackflow' === $codeType){
				$code .= 'Flow::clearBackFlow();' . PHP_EOL;
			}
			elseif('flowviewparam' === $codeType){
				$code .= 'if(NULL === Flow::$params[\'view\']){' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'Flow::$params[\'view\'] = array();' . PHP_EOL;
				$code .= $tab . '}' . PHP_EOL;
				$code .= $tab . 'Flow::$params[\'view\'][] = array(\'' . $tmpAttr['selector'] . '\' => ' . self::_resolveValue($tmpAttr['val']) . ');';
			}
			elseif('flowformparam' === $codeType){
				$code .= '$this->_convertWebFlowForm(\'' . $tmpAttr['section'] . '\', ' . self::_resolveValue($tmpAttr['val']) . ');';
			}
			elseif('exception' === $codeType){
				$msg = '';
				$code .= 'throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);';
			}
			elseif('view' === $codeType){
				$section = '$this->section';
				if(isset($tmpAttr['section']) && strlen($tmpAttr['section']) > 0){
					$section = '\'' . $tmpAttr['section'] . '\'';
				}
				$target = '\'\'';
				if(isset($tmpAttr['target']) && strlen($tmpAttr['target']) > 0){
					$target = '\'' . $tmpAttr['target'] . '\'';
				}
				$base = NULL;
				if(isset($tmpAttr['baseview']) && strlen($tmpAttr['baseview']) > 0){
					$base = $tmpAttr['baseview'];
				}
				$output = $_GET['_o_'];
				if ('shtml' !== $output && 'html' !== $output && 'php' !== $output && '' !== $output){
					$output = 'html';
				}
				// 入力フォーム用
				if(isset($tmpAttr['flowpostformsection']) && strlen($tmpAttr['flowpostformsection']) > 0){
					$code .= 'if(NULL === Flow::$params[\'view\']){' . PHP_EOL;
					$code .= $tab . PHP_TAB . 'Flow::$params[\'view\'] = array();' . PHP_EOL;
					$code .= $tab . '}' . PHP_EOL;
					$action = '?_c_=' . $tmpAttr['flowpostformsection'] . '&_o_='.$output;
					if(isset($tmpAttr['sslrequired']) && strlen($tmpAttr['sslrequired']) > 0){
						if('TRUE' === strtoupper($tmpAttr['sslrequired']) || 1 === (int)$tmpAttr['sslrequired'] || TRUE === $tmpAttr['sslrequired']){
							$code .= $tab . '$this->isSSLRequired = TRUE;' . PHP_EOL;
						}
						else {
							$code .= $tab . '$this->isSSLRequired = FALSE;' . PHP_EOL;
						}
					}
					else {
						$code .= $tab . '$this->isSSLRequired = NULL;' . PHP_EOL;
					}
					$code .= $tab . '$this->action = \'?_c_=' . $tmpAttr['flowpostformsection'] . '&_o_='.$output.'\';' . PHP_EOL;
					$code .= $tab . 'Flow::$params[\'view\'][] = array(\'form[flowpostformsection=' . $tmpAttr['flowpostformsection'] . ']\' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array(\'method\'=>\'post\', \'action\'=>$this->_reverseRewriteURL())));' . PHP_EOL;
					if ($tmpAttr['section'] !== $tmpAttr['flowpostformsection']){
						$code .= $tab . 'Flow::$params[\'view\'][] = array(\'form[flowpostformsection=' . $tmpAttr['flowpostformsection'] . ']\' => array(HtmlViewAssignor::APPEND_NODE_KEY => \'<input class="generate-flow" type="hidden" name="flowpostformsection" value="'.$tmpAttr['flowpostformsection'].'"/>\'));' . PHP_EOL;
					}
					$code .= $tab;
				}
				// 入浴確認、完了フォーム用
				if(isset($tmpAttr['confirmflowpostformsection']) && strlen($tmpAttr['confirmflowpostformsection']) > 0){
					$code .= 'if(NULL === Flow::$params[\'view\']){' . PHP_EOL;
					$code .= $tab . PHP_TAB . 'Flow::$params[\'view\'] = array();' . PHP_EOL;
					$code .= $tab . '}' . PHP_EOL;
					$action = '?_c_=' . $tmpAttr['confirmflowpostformsection'] . '&_o_='.$output;
					// XXX 確認画面から完了画面へのフローは今のところSSL非強制には非対応
					$code .= $tab . '$this->isSSLRequired = NULL;' . PHP_EOL;
					$code .= $tab . '$this->action = \'?_c_=' . $tmpAttr['confirmflowpostformsection'] . '&_o_='.$output.'\';' . PHP_EOL;
					$code .= $tab . 'Flow::$params[\'view\'][] = array(\'form[confirmflowpostformsection=' . $tmpAttr['confirmflowpostformsection'] . ']\' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array(\'method\'=>\'post\', \'action\'=>$this->_reverseRewriteURL())));' . PHP_EOL;
					if ($tmpAttr['section'] !== $tmpAttr['confirmflowpostformsection']){
						$code .= $tab . 'Flow::$params[\'view\'][] = array(\'form[confirmflowpostformsection=' . $tmpAttr['confirmflowpostformsection'] . ']\' => array(HtmlViewAssignor::APPEND_NODE_KEY => \'<input class="generate-flow" type="hidden" name="flowpostformsection" value="'.$tmpAttr['confirmflowpostformsection'].'"/>\'));' . PHP_EOL;
					}
					$code .= $tab;
				}
				// 戻るフォーム用
				if(isset($tmpAttr['backflowpostformsection']) && strlen($tmpAttr['backflowpostformsection']) > 0){
					$code .= 'if(NULL === Flow::$params[\'view\']){' . PHP_EOL;
					$code .= $tab . PHP_TAB . 'Flow::$params[\'view\'] = array();' . PHP_EOL;
					$code .= $tab . '}' . PHP_EOL;
					$action = '?_c_=' . $tmpAttr['backflowpostformsection'] . '&_o_='.$output;
					if(isset($tmpAttr['sslrequired']) && strlen($tmpAttr['sslrequired']) > 0){
						if('TRUE' === strtoupper($tmpAttr['sslrequired']) || 1 === (int)$tmpAttr['sslrequired'] || TRUE === $tmpAttr['sslrequired']){
							$code .= $tab . '$this->isSSLRequired = TRUE;' . PHP_EOL;
						}
						else {
							$code .= $tab . '$this->isSSLRequired = FALSE;' . PHP_EOL;
						}
					}
					else {
						$code .= $tab . '$this->isSSLRequired = NULL;' . PHP_EOL;
					}
					$code .= $tab . '$this->action = \'?_c_=' . $tmpAttr['backflowpostformsection'] . '&_o_='.$output.'\';' . PHP_EOL;
					$code .= $tab . 'Flow::$params[\'view\'][] = array(\'form[backflowpostformsection=' . $tmpAttr['backflowpostformsection'] . ']\' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array(\'method\'=>\'post\', \'action\'=>$this->_reverseRewriteURL())));' . PHP_EOL;
					$code .= $tab;
				}
				// Viewを表示する処理を生成
				$code .= '$HtmlView = Core::loadView(str_replace(\'_\', \'-\', strtolower(' . $section . ')), FALSE, ' . $target . ');' . PHP_EOL;
				$code .= $tab .'if (FALSE === $HtmlView){' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'throw new Exception (__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);' . PHP_EOL;
				$code .= $tab .'}' . PHP_EOL;
				if(NULL !== $base){
					// baseになるhtmlをViewクラスに渡す
					$code .= $tab . '$HtmlView->addTemplate(Core::loadTemplate(\'' . $base . '\', FALSE, \'\', \'.html\', \'HtmlTemplate\'), \'base\');' . PHP_EOL;
				}
				$code .= $tab . '$html = $HtmlView->execute(NULL, Flow::$params[\'view\']);' . PHP_EOL;
				$code .= $tab . 'return $html;';
			}
			else{
				if('lambda' === $codeType){
					$lambdaFunction = '$lambda';
					if(isset($tmpAttr['execute'])){
						// 匿名関数を実行処理
						if(strlen($tmpAttr['execute']) > 0){
							$lambdaFunction = '$'.$tmpAttr['execute'].'Lambda';
						}
						$lambdaArgment = '';
						if(isset($tmpAttr['argments']) && strlen($tmpAttr['argments']) > 0){
							$lambdaArgment = $tmpAttr['argments'];
						}
						$code .= $lambdaFunction.'('.$lambdaArgment.')';
					}
					else {
						// 匿名関数の作成処理
						$heredoc = 'LAMBDA';
						$lambdaFunctions = $lambdaFunction;
						$lambdaArgments = '$_lambdaArgments = \'\'';
						if(isset($tmpAttr['section']) && strlen($tmpAttr['section']) > 0){
							$heredoc = strtoupper($tmpAttr['section']);
							$lambdaFunctions = '$'.$tmpAttr['section'];
						}
						$lambdaFunction = $lambdaFunctions.'Lambda';
						if(isset($tmpAttr['argments']) && strlen($tmpAttr['argments']) > 0){
							$lambdaArgments = '$_lambdaArgments = \''.$tmpAttr['argments'].'\'';
						}
						$code .= $lambdaArgments . ';' . PHP_EOL;
						$code .= $tab . $lambdaFunctions.' = <<<__'.$heredoc.'__' . PHP_EOL;
					}
				}
				// それ以外はXML化されたただのPHPコード扱い
				// XXX 状態遷移して生成して行くので、処理の順番に注意！
				// if文
				if('if' === $codeType){
					$code .= 'if(';
				}
				elseif('elseif' === $codeType){
					$code .= 'elseif(';
				}
				elseif('else' === $codeType){
					$code .= 'else';
				}
				elseif('return' === $codeType){
					$code .= 'return ';
				}
				if(TRUE === ('if' === $codeType || 'elseif' === $codeType) && isset($tmpAttr['condition']) && strlen($tmpAttr['condition']) > 0){
					$code .= self::_resolveValue($tmpAttr['condition']);
				}
				// conditionとは排他
				elseif(isset($tmpAttr['var']) && strlen($tmpAttr['var']) > 0){
					if (FALSE !== strpos($tmpAttr['var'], '::')){
						// クラス変数への代入としてふるまう
						$code .= $tmpAttr['var'];
					}
					else {
						$code .= '$' . $tmpAttr['var'];
					}
				}
				// for文
				if('for' === $codeType){
					$code .= 'for('.$tmpAttr['iterate'].'; '.$tmpAttr['iterator'].'; '.$tmpAttr['iteration'];
				}
				// foreach文
				if('foreach' === $codeType){
					$code .= 'foreach($'.$tmpAttr['eachas'].' AS $'.$tmpAttr['eachas'].'key => $'.$tmpAttr['eachas'].'val';
				}
				// while文
				if('while' === $codeType){
					$code .= 'while(';
				}
				// 式の評価文
				// if文 elseif文
				if('if' === $codeType || 'elseif' === $codeType){
					// 評価方法判定
					if(isset($tmpAttr['style'])){
						$code .= ' ' . $tmpAttr['style'] . ' ';
					}
					// それ以外で、且つ左辺と右辺の指定が在る場合は強制最等価判定
					elseif(TRUE === (isset($tmpAttr['condition']) || isset($tmpAttr['var'])) && $tmpAttr['val']){
						$code .= ' === ';
					}
				}
				// else文 for文 foreach文
				elseif('else' === $codeType || 'for' === $codeType || 'foreach' === $codeType || 'while' === $codeType || 'lambda' === $codeType){
					// 何もナシ
				}
				// return文
				elseif('return' === $codeType){
					// returnは何もナシ
				}
				// execute文
				elseif('execute' === $codeType){
					// executeは何もナシ
				}
				// それ以外は代入扱い
				else {
					$code .= ' = ';
				}
				// 式の右辺属性文
				if('new' === $codeType){
					$code .= 'new ';
				}
				// 右辺が変数の場合
				if(isset($tmpAttr['val']) && strlen($tmpAttr['val']) > 0){
					$code .= self::_resolveValue($tmpAttr['val']);
				}
				// 右辺がクラスの場合
				if(isset($tmpAttr['class']) && strlen($tmpAttr['class']) > 0){
					$code .= $tmpAttr['class'];
					if('new' === $codeType){
						$code .= '(' . self::_resolveArgs($tmpAttr) . ')';
					}
					else{
						$code .= '::';
					}
				}
				// 右辺がインスタンス化されたクラスの場合
				if(isset($tmpAttr['instance']) && strlen($tmpAttr['instance']) > 0){
					$code .= '$' . $tmpAttr['instance'] . '->';
				}
				// メソッド定義を繋げる
				if(isset($tmpAttr['method']) && strlen($tmpAttr['method']) > 0){
					$code .= $tmpAttr['method'] . '(' . self::_resolveArgs($tmpAttr) . ')';
				}
				// property定義を繋げる
				if(isset($tmpAttr['property']) && strlen($tmpAttr['property']) > 0){
					$code .= $tmpAttr['property'];
				}
				// 式の終端文
				if('if' === $codeType || 'elseif' === $codeType || 'for' === $codeType || 'foreach' === $codeType || 'while' === $codeType){
					$code .= '){' . PHP_EOL;
				}
				elseif('else' === $codeType){
					$code .= '{' . PHP_EOL;
				}
				// ネスト構造を再帰的に処理して、コードに繋げる
				if(count($argCodeNode->children()) > 0){
					$_code = '';
					foreach($argCodeNode->children() as $codeNode){
						$_code .= self::_generateCode($codeNode, $argBasePathTarget, ($argDepth+1));
					}
					if('lambda' === $codeType && isset($heredoc) && isset($lambdaFunction) && isset($lambdaFunctions) && isset($lambdaArgments)){
						$_code = str_replace('$', '\$', $_code);
						$projectName = '';
						if (defined('PROJECT_NAME') && 0 < strlen(PROJECT_NAME)){
							$projectName = PROJECT_NAME;
						}
						$_code = $tab . PHP_TAB . 'if (!defined(\'PROJECT_NAME\')) {' . PHP_EOL
						. $tab . PHP_TAB . PHP_TAB . 'define(\'PROJECT_NAME\', \'' . $projectName . '\');' . PHP_EOL
						. $tab . PHP_TAB . '}' . PHP_EOL
						. $tab . PHP_TAB . 'require_once realpath(\$_SERVER[\'DOCUMENT_ROOT\'].\'/' . getFrameworkCoreFilePath(TRUE) . '\');' . PHP_EOL.$_code;
					}
					$code .= $_code;
				}
				// 終了子判定
				if('if' === $codeType || 'elseif' === $codeType || 'else' === $codeType || 'for' === $codeType || 'foreach' === $codeType || 'while' === $codeType){
					$code .= $tab . '}';
				}
				else{
					if('lambda' === $codeType && isset($heredoc) && isset($lambdaFunction) && isset($lambdaFunctions) && isset($lambdaArgments)){
						$code .=  '__'.$heredoc.'__;'.PHP_EOL;
						$code .= $tab . $lambdaFunction.' = create_lambdafunction('.$lambdaFunctions.', $_lambdaArgments)';
					}
					$code .= ';';
				}
			}
			// 生成されたコードを返す
			return $code . PHP_EOL;
		}
		// 何も生成してないので、0バイト文字を返す
		return '';
	}

	/**
	 * 引数の値の解決処理
	 * @param unknown $argNode
	 */
	public static function _resolveArgs($argAttr){
		$argCnt = 1;
		$code = '';
		while(isset($argAttr['arg' . $argCnt])){
			if($argCnt > 1){
				$code .= ', ';
			}
			$code .= self::_resolveValue($argAttr['arg' . $argCnt]);
			$argCnt++;
		}
		return $code;
	}

	/**
	 * 属性の値の解決処理
	 * @param unknown $argNode
	 */
	public static function _resolveValue($argValue){
		$val = $argValue;
		if(0 === strpos($val, 'assign:')){
			$val = substr($val, 7);
			$val = '$' . $val;
		}
		elseif('true' === strtolower($val)){
			$val = 'TRUE';
		}
		elseif('false' === strtolower($val)){
			$val = 'FALSE';
		}
		elseif('null' === strtolower($val)){
			$val = 'NULL';
		}
		elseif(TRUE === is_numeric($val)){
			$val = (string)$val;
		}
		elseif(0 === strpos($val, 'string:')){
			$val = substr($val, 7);
			$val = '\'' . $val . '\'';
		}
		elseif(0 === strpos($val, 'str:')){
			$val = substr($val, 4);
			$val = '\'' . $val . '\'';
		}
		return $val;
	}
}
?>