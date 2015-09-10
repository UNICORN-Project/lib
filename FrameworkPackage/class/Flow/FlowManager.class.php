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

	public static function reverseRewriteURL($argAction, $argQuery=''){
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
			foreach($_GET as $key => $val){
				if('_c_' !== $key && '_a_' !== $key && '_o_' !== $key){
					if(strlen($query) > 0){
						$query .= '&';
					}
					$query .= $key.'='.$val;
				}
			}
		}
		if('' !== $query){
			if(FALSE === strpos($action, '.'.$_GET['_o_'].'?')){
				$query = '?'.$query;
			}
			else {
				$query = '&'.$query;
			}
		}
		return $action.$query;
	}

	/**
	 * 次のFlowを特定し、ロードし、そのクラス名を返却する
	 * @param string クラス名
	 * @param string ターゲットファイルパスのヒント
	 * @return mixed 成功時は対象のクラス名 失敗した場合はFALSEを返す
	 */
	public static function loadNextFlow($argClassName = NULL, $argTargetPath = ''){
		// 先ずbackflowなのかどうか
		if('backflow' === strtolower($argClassName)){
			// backflowが特定出来無かった時ように強制的にIndexを指定しておく
			$argClassName = 'index';
			if(strlen($argTargetPath) > 0){
				$argClassName = $argTargetPath.'/'.$argClassName;
			}
			// PostパラメータからBackflowを特定する
			if(isset($_POST['flowpostformsection-backflow-section'])){
				$argClassName = $_POST['flowpostformsection-backflow-section'];
			}
			// backflowはリダイレクトポスト(307リダイレクト)
			$query = '';
			if(isset($_POST['flowpostformsection-backflow-section-query']) && strlen($_POST['flowpostformsection-backflow-section-query']) > 0){
				$query = $_POST['flowpostformsection-backflow-section-query'];
			}
			$argClassName = str_replace('//', '/', str_replace('//', '/', $argClassName));
			header('Location: ./'.self::reverseRewriteURL('?_c_=' . $argClassName . '&_o_='.$_GET['_o_'], $query), TRUE, 307);
			exit();
		}
		$className = MVCCore::loadMVCModule($argClassName, FALSE, $argTargetPath);
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
					elseif('api' == $typeStr){
						$extends = ' extends APIControllerBase';
					}
					elseif('image' == $typeStr){
						$extends = ' extends ImageControllerBase';
					}
				}
				$methods = array();
				// メソッド定義
				foreach ($firstNode->children() as $methodNode) {
					// 2次元目はメソッド定義
					$methodName = $methodNode->getName();
					// constructとdestructだけをマジックメソッドのマップとしてサポートする
					if('construct' == $methodName || 'destruct' == $methodName){
						$methodName = '__' . $methodName;
					}
					$method = PHP_TAB . 'function ' . $methodName . '(%s){' . PHP_EOL . '%s' . PHP_EOL . PHP_TAB . PHP_TAB . 'return TRUE;' . PHP_EOL . PHP_TAB . '}' . PHP_EOL;
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
					if(isset($extends) && strlen($extends) > 0 && ' extends WebFlowControllerBase' == $extends){
						// WebFlowBaseのinitをメソッドの頭で必ず呼ぶ
						$code .= PHP_TAB . PHP_TAB . '$autoValidated = parent::_initWebFlow();' . PHP_EOL;
					}
					foreach ($methodNode->children() as $codeNode) {
						$code .= self::_generateCode($codeNode, $argBasePathTarget);
					}
					$method = sprintf($method, $methodArg, $code);
					$methods[] = $method;
				}
				// クラス定義つなぎ合わせ
				$classDef .= PHP_EOL . 'class ' . $sectionClassName . $extends . PHP_EOL;
				$classDef .= '{' . PHP_EOL . PHP_EOL;
				$classDef .= PHP_TAB . 'public $section=\''.basename($argSection).'\';' . PHP_EOL;
				$classDef .= PHP_TAB . 'public $target=\''.$argBasePathTarget.'\';' . PHP_EOL . PHP_EOL;
				for($methodIdx=0; count($methods) > $methodIdx; $methodIdx++){
					$classDef .= $methods[$methodIdx];
				}
				$classDef .= '}' . PHP_EOL;
			}
			// オートジェネレートチェック
			if(TRUE === $filepathUsed && TRUE === $autoGenerateFlag){
				// 空でジェネレートファイルを生成
				@file_put_contents($generatedClassPath, '');
				// ジェネレート
				generateClassCache($generatedClassPath, $argTarget, $classDef, $className);
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
		$targetPath = str_replace('..','.', str_replace('/','.', substr($argTarget, strlen(MVCCore::$flowXMLBasePath))));
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
			$tab = PHP_TAB;
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
					$code .= '$className = Flow::loadNextFlow(\'' . str_replace('-', '_', ucfirst($sectionID)) . '\', \'' . $target . '\');' . PHP_EOL;
					$code .= $tab . '$instance = new $className();' . PHP_EOL;
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
			elseif('flowpostformsectionerror' === $codeType){
				$code .= 'if(NULL === Flow::$params[\'view\']){' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'Flow::$params[\'view\'] = array();' . PHP_EOL;
				$code .= $tab . '}' . PHP_EOL;
				$code .= $tab . 'Flow::$params[\'view\'][] = array(\'div[flowpostformsectionerror]\' => \'' . $argCodeNode . '\');';
			}
			elseif('cancelthisbackflow' === $codeType){
				$code .= 'if(count(Flow::$params[\'backflow\']) > 0){' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'unset(Flow::$params[\'backflow\'][count(Flow::$params[\'backflow\']) -1]);' . PHP_EOL;
				$code .= $tab. '}' . PHP_EOL;
			}
			elseif('flowviewparam' === $codeType){
				$code .= 'if(NULL === Flow::$params[\'view\']){' . PHP_EOL;
				$code .= $tab . PHP_TAB . 'Flow::$params[\'view\'] = array();' . PHP_EOL;
				$code .= $tab . '}' . PHP_EOL;
				$code .= $tab . 'Flow::$params[\'view\'][] = array(\'' . $tmpAttr['selector'] . '\' => ' . self::_resolveValue($tmpAttr['val']) . ');';
			}
			elseif('exception' === $codeType){
				$msg = '';
				$code .= 'throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);';
			}
			elseif('view' === $codeType){
				$section = 'str_replace(\'_\', \'-\', $this->controlerClassName)';
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
				if(isset($tmpAttr['flowpostformsection']) && strlen($tmpAttr['flowpostformsection']) > 0){
					$code .= 'if(NULL === Flow::$params[\'view\']){' . PHP_EOL;
					$code .= $tab . PHP_TAB . 'Flow::$params[\'view\'] = array();' . PHP_EOL;
					$code .= $tab . '}' . PHP_EOL;
					$action = '?_c_=' . $tmpAttr['flowpostformsection'] . '&_o_='.$_GET['_o_'];
					$code .= $tab . '$this->action = \'?_c_=' . $tmpAttr['flowpostformsection'] . '&_o_='.$_GET['_o_'].'\';' . PHP_EOL;
					$code .= $tab . 'Flow::$params[\'view\'][] = array(\'form[flowpostformsection=' . $tmpAttr['flowpostformsection'] . ']\' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array(\'method\'=>\'post\', \'action\'=>$this->_reverseRewriteURL())));' . PHP_EOL;
					$code .= $tab . 'Flow::$params[\'view\'][] = array(\'form[flowpostformsection=' . $tmpAttr['flowpostformsection'] . ']\' => array(HtmlViewAssignor::APPEND_NODE_KEY => \'<input type="hidden" name="flowpostformsection" value="'.$tmpAttr['flowpostformsection'].'"/>\'));' . PHP_EOL;
					$code .= $tab;
				}
				// Viewを表示する処理を生成
				$code .= '$HtmlView = Core::loadView(' . $section . ', FALSE, ' . $target . ');' . PHP_EOL;
				if(NULL !== $base){
					// baseになるhtmlをViewクラスに渡す
					$code .= $tab . '$HtmlView->addTemplate(Core::loadTemplate(\'' . $base . '\', FALSE, \'\', \'.html\', \'HtmlTemplate\'), \'base\');' . PHP_EOL;
				}
				$code .= $tab . '$html = $HtmlView->execute(NULL, Flow::$params[\'view\']);' . PHP_EOL;
				$code .= $tab . 'return $html;';
			}
			else{
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
				elseif('else' === $codeType || 'for' === $codeType || 'foreach' === $codeType){
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
				if('if' === $codeType || 'elseif' === $codeType || 'for' === $codeType || 'foreach' === $codeType){
					$code .= '){' . PHP_EOL;
				}
				elseif('else' === $codeType){
					$code .= '{' . PHP_EOL;
				}
				// ネスト構造を再帰的に処理して、コードに繋げる
				if(count($argCodeNode->children()) > 0){
					foreach($argCodeNode->children() as $codeNode){
						$code .= self::_generateCode($codeNode, $argBasePathTarget, ($argDepth+1));
					}
				}
				// 終了子判定
				if('if' === $codeType || 'elseif' === $codeType || 'else' === $codeType || 'for' === $codeType || 'foreach' === $codeType){
					$code .= $tab . '}';
				}
				else{
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