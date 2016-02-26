<?php

ini_set("max_execution_time",60);

class HtmlViewAssignor {

	const REMOVE_NODE_KEY = 'remove-node';
	const REMOVE_NODE = array(self::REMOVE_NODE_KEY => 'remove');
	const REPLACE_ATTR_KEY = 'replace-attribute';
	const PART_REPLACE_ATTR_KEY = 'part-replace-attribute';
	const LOOP_NODE_KEY = 'loop-node';
	const PART_REPLACE_NODE_KEY = 'part-replace-node';
	const APPEND_NODE_KEY = 'append-node';
	const PREPEND_NODE_KEY = 'prepend-node';
	const ASSIGN_RESET = 'initialize and reset';

	protected $_orgHtmlHint;
	protected $_orgHtmlKey;
	public $Templates = array();
	public $TemplateCaches = array();

	public static function buildRemoveNode($argSelector = NULL){
		if (NULL === $argSelector){
			return self::REMOVE_NODE;
		}
		return array($argSelector => self::REMOVE_NODE);
	}

	public static function buildSetNode($argValue, $argSelector = NULL){
		if (NULL === $argSelector){
			return $argValue;
		}
		return array($argSelector => $argValue);
	}

	public static function buildSetAttribute($argTargetAttribute, $argAttributeValue, $argSelector = NULL){
		if (NULL === $argSelector){
			return array(self::REPLACE_ATTR_KEY => array($argTargetAttribute => $argAttributeValue));
		}
		return array($argSelector => array(self::REPLACE_ATTR_KEY => array($argTargetAttribute => $argAttributeValue)));
	}
	public static function buildReplaceNode($argTargetParttern, $argNewValue, $argSelector = NULL){
		if (NULL === $argSelector){
			return array(self::PART_REPLACE_NODE_KEY => array($argTargetParttern => $argNewValue));
		}
		return array($argSelector => array(self::PART_REPLACE_NODE_KEY => array($argTargetParttern => $argNewValue)));
	}

	public static function buildReplaceAttribute($argTargetAttribute, $argTargetParttern, $argNewValue, $argSelector = NULL){
		if (NULL === $argSelector){
			return array(self::PART_REPLACE_ATTR_KEY => array($argTargetAttribute => array($argTargetParttern => $argNewValue)));
		}
		return array($argSelector => array(self::PART_REPLACE_ATTR_KEY => array($argTargetAttribute => array($argTargetParttern => $argNewValue))));
	}

	public static function buildLoopNode($argItems, $argSelector = NULL){
		if (NULL === $argSelector){
			return array(self::LOOP_NODE_KEY => $argItems);
		}
		return array($argSelector, array(self::LOOP_NODE_KEY => $argItems));
	}

	public function __construct($argHtmlHint=NULL, $argKey='main'){
		// コンストラクタですっ飛ばさない為に一旦しまってそれでコンストラクタは終わり
		$this->_orgHtmlHint = $argHtmlHint;
		$this->_orgHtmlKey = $argKey;
	}

	public function addTemplate($argHtmlHinst, $argKey='main'){
		if(is_object($argHtmlHinst)){
			// テンプレートエンジンインスタンスが渡ってきていると判定
			$this->Templates[$argKey] = $argHtmlHinst;
			$this->TemplateCaches[$argKey] = array();
		}
		else {
			// テンプレートファイルパスないし、html文字列が渡ってきていると判定し
			// テンプレートエンジンインスタンス生成
			$this->Templates[$argKey] = new HtmlTemplate($argHtmlHinst);
			$this->TemplateCaches[$argKey] = array();
		}
	}

	public function execute($argHtmlHint=NULL, $argParams=NULL, $argKey=NULL){

		if(NULL === $argKey){
			$argKey = $this->_orgHtmlKey;
			// 一度使ったら不要
			$this->_orgHtmlKey = NULL;
		}

		// ベースとなるテンプレートエンジンインスタンスを生成
		if(NULL !== $this->_orgHtmlHint){
			$this->addTemplate($this->_orgHtmlHint, $argKey);
			// 一度使ったら不要
			$this->_orgHtmlHint = NULL;
		}
		if(NULL !== $argHtmlHint){
			$this->addTemplate($argHtmlHint, $argKey);
		}

		// assignの実行
		$html = '<html templatepartsid="main"></html>';
		$htmls = array();
		if(count($this->Templates) > 0){
			$templates = array_reverse($this->Templates);
		}
		else{
			$templates = $this->Templates;
		}
		foreach($templates as $key => $val){
			$tmpHtml = self::assign($val, $argParams, NULL, 0, 0, $key);
			if(is_array($tmpHtml)){
				if(isset($tmpHtml['param'])){
					$argParams = $tmpHtml['param'];
				}
				if(isset($tmpHtml['html'])){
					$tmpHtml = $tmpHtml['html'];
				}
			}
			if('base' === $key){
				$html = $tmpHtml;
			}
			else {
				$htmls[$key] = $tmpHtml;
			}
			// リセットしておく
			self::assign(self::ASSIGN_RESET);
		}
		unset($templates);

		// 単一のテンプレートhtmlの場合はガッチャンコ不要
		if(count($htmls) === 1){
			$keys = array_keys($htmls);
			$html = $htmls[$keys[0]];
			unset($htmls);
		}
		// 複数のテンプレートhtmlをガッチャンコ
		elseif(count($htmls) >0){
			$BaseTemplate = new HtmlTemplate($html);
			foreach($htmls as $key => $val){
				$key = '[templatepartsid=' . $key . ']';
				// XXX アウターで置き換える！！
				// TODO 文字コードの変換指定はそのうちちゃんとやる
				$BaseTemplate->addSource($key, $val, NULL, NULL, TRUE);
			}
			// 書き戻し
			$html = $BaseTemplate->flush();
			unset($htmls);
		}

		return $html;
	}

	private  static function _searchMatchParam($argParams, $argSearchKey, $argSearchDepth, $argDepth=0, $argKeyMatch=FALSE){
		$params = NULL;
		if (is_array($argParams)){
			foreach ($argParams as $key => $val){
// 				echo $argSearchDepth;
// 				echo ':';
// 				echo $argDepth;
// 				echo '+';
// 				echo $argSearchKey;
// 				echo '+';
// 				echo $key;
// 				echo ':';
// 				echo '<br/>';
// 				var_dump($val);
// 				echo '<br/>';
// 				echo '<br/>';
				// 深度判定
				// マッチ判定
				if($key === $argSearchKey){
					//echo 'keymatch!<br/>';
					$argKeyMatch = TRUE;
				}
				if ($argDepth === $argSearchDepth && TRUE === $argKeyMatch){
					//echo 'match!!<br/>';
					if (NULL === $params){
						$params = array();
					}
					$params[] = $val;
				}
				// 再帰処理判定
				elseif (is_array($val)){
					$res = self::_searchMatchParam($val, $argSearchKey, $argSearchDepth, $argDepth+1, $argKeyMatch);
					if (is_array($res)){
						for ($resIdx=0; $resIdx < count($res); $resIdx++){
							if (NULL === $params){
								$params = array();
							}
							$params[] = $res[$resIdx];
						}
					}
				}
			}
		}
		return $params;
	}

	public static function assign($argTemplateHint, $argParams=NULL, $argKey=NULL, $argDepth = 0, $arrayDepth=0, $argTplName=NULL){
		static $Template = array();
		static $caches = array();
		static $cache = NULL;
		static $varGetten = FALSE;
		if(self::ASSIGN_RESET === $argTemplateHint){
			// staticを初期化
			$Template = array();
			$varGetten = FALSE;
			return;
		}
		if (NULL !== $argTemplateHint && TRUE === (!isset($Template[$argDepth]) || $argTemplateHint != $Template[$argDepth])){
			if(is_object($argTemplateHint)){
				// テンプレートエンジンインスタンスが渡ってきていると判定
				$Template[$argDepth] = $argTemplateHint;
			}
			else {
				// テンプレートファイルパスないし、html文字列が渡ってきていると判定し
				// テンプレートエンジンインスタンス生成
				$Template[$argDepth] = new HtmlTemplate($argTemplateHint);
			}
		}

		// 何はともあれ、テンプレートでの変数宣言があったら持ってくる
		$newParamGetten = FALSE;
		if(0 === $argDepth && FALSE === $varGetten){
			$varGetten = TRUE;
			$varNodes = $Template[$argDepth]->find('var[selector]');
			if(is_array($varNodes) && count($varNodes) > 0){
				for($varNodesIdx=0; $varNodesIdx < count($varNodes); $varNodesIdx++){
					if(NULL === $argParams){
						$argParams = array();
					}
					eval('$argParams[count($argParams)] = array(\'' . $varNodes[$varNodesIdx]->getAttribute('selector') . '\' => ' . $varNodes[$varNodesIdx]->getAttribute('value') . ');');
					$newParamGetten = TRUE;
					$varNodes[$varNodesIdx]->remove();
				}
			}
		}

		// アサイン処理の実行
		$useCache = FALSE;
		if(NULL !== $argParams && is_array($argParams)){
			// キャッシュの存在チェック
			$useCache = FALSE;
			// XXX キャッシュ化一旦諦め・・・
// 			if (NULL !== $argTplName){
// 				$cachePath = getAutoGeneratedPath().'/cache/tpl/'.str_replace('/', '_', str_replace('//', '/', $argTplName)).'.'.sha1(serialize(array_keys_recursive($argParams))).'.tplcache.php';
// 				if (is_file($cachePath)){
// 					$useCache = TRUE;
// 					$tmpCache = file_get_contents($cachePath);
// 					if (0< strlen($tmpCache)){
// 						// キャッシュの復帰
// 						$caches = unserialize($tmpCache);
// 						// キャッシュを逆(深い位置)から処理する為に逆順にする
// 						//$caches = array_reverse($caches);
// 					}
// 				}
// 			}
			// キャッシュからhtmlを生成する処理
			if (TRUE === $useCache && 0 < count($caches)){
				// サブテンプレート用
				$depths = NULL;
				$htmls = array();
				foreach ($caches as $tplDepth => $_caches){
// 					var_dump($tplDepth);
// 					echo '<br/>';
// 					var_dump($_caches);
// 					echo '<br/><br/>';
					// キャッシュ処理はhtmllのままのデータで処理をする
					$html = $Template[$tplDepth]->flush();
					for ($cacheIdx=0; $cacheIdx < count($_caches); $cacheIdx++){
						$cache = $_caches[$cacheIdx];
						$selector = $cache['findSelector'];
						if (NULL === $selector){
							$selector = $cache['key'];
						}
						$params = NULL;
						if ('remove-node' !== $cache['action'] && 'remove-attr' !== $cache['action']){
							$params = self::_searchMatchParam($argParams, $selector, $cache['arrayDepth']);
						}
						// 設定されている深度に到達したら値のセット処理をする
						if ('set-attr' === $cache['action']){
							// 属性の差し替え
							$node = new HtmlTemplate($cache['node']);
							$dom = $node->find($selector);
							if (isset($dom[0])){
								if (is_array($params[0])){
									if (isset($params[0][$cache['attr']])){
										$dom[0]->setAttribute($cache['attr'], $params[0][$cache['attr']]);
									}
								}
								else {
									$dom[0]->setAttribute($cache['attr'], $params[0]);
								}
							}
							$html = str_replace($cache['node'], $node->flush(), $html);
							unset($dom);
							unset($node);
						}
						elseif ('remove-attr' === $cache['action']){
							// 属性の削除
							$node = new HtmlTemplate($cache['node']);
							$dom = $node->find($selector);
							if (isset($dom[0])){
								if (is_array($params[0])){
									if (isset($params[0][$cache['attr']])){
										$dom[0]->removeAttribute($params[0][$cache['attr']]);
									}
								}
								else {
									$dom[0]->removeAttribute($cache['attr']);
								}
							}
							$html = str_replace($cache['node'], $node->flush(), $html);
							unset($dom);
							unset($node);
						}
						elseif ('replace-attr' === $cache['action']){
							// 属性の部分置換
							$node = new HtmlTemplate($cache['node']);
							$dom = $node->find($selector);
							if (isset($dom[0])){
								if (is_array($params[0])){
									if (isset($params[0][$cache['attr']])){
										$replace = $params[0][$cache['attr']];
										$attrVal = $dom[0]->getAttribute($cache['attr']);
										foreach($replace as $partKey => $partVal){
											$attrVal = str_replace($partKey, $partVal, $attrVal);
										}
										$dom[0]->setAttribute($cache['attr'], $attrVal);
									}
								}
							}
							$html = str_replace($cache['node'], $node->flush(), $html);
							unset($dom);
							unset($node);
						}
						elseif ('set-node' === $cache['action']){
							// 要素の差し替え
							$node = new HtmlTemplate($cache['node']);
							$dom = $node->find($selector);
							if (isset($dom[0])){
								if (is_array($params) && isset($params[0])){
									$dom[0]->innertext($params[0]);
								}
								else {
									$dom[0]->innertext($params);
								}
							}
							$html = str_replace($cache['node'], $node->flush(), $html);
							unset($dom);
							unset($node);
						}
						elseif ('append-node' === $cache['action']){
							// 後ろに要素追加
							$node = new HtmlTemplate($cache['node']);
							$dom = $node->find($selector);
							if (isset($dom[0])){
								if (is_array($params) && isset($params[0])){
									$dom[0]->innerHtml($dom[0]->innerHtml().$params[0]);
								}
								else {
									$dom[0]->innerHtml($dom[0]->innerHtml().$params);
								}
							}
							$html = str_replace($cache['node'], $node->flush(), $html);
							unset($dom);
							unset($node);
						}
						elseif ('prepend-node' === $cache['action']){
							// 前に要素追加
							$node = new HtmlTemplate($cache['node']);
							$dom = $node->find($selector);
							if (isset($dom[0])){
								if (is_array($params) && isset($params[0])){
									$dom[0]->innerHtml($params[0].$dom[0]->innerinnerHtml());
								}
								else {
									$dom[0]->innerHtml($params.$dom[0]->innerHtml());
								}
							}
							$html = str_replace($cache['node'], $node->flush(), $html);
							unset($dom);
							unset($node);
						}
						elseif ('remove-node' === $cache['action']){
							// 要素の削除
							$html = str_replace($cache['node'], '', $html);
						}
						elseif ('replace-node' === $cache['action']){
							// 要素の部分置換
							$node = new HtmlTemplate($cache['node']);
							$dom = $node->find($selector);
							if (isset($dom[0])){
								if (is_array($params[0])){
									$nodeVal = $dom[$domIdx]->innertext();
									foreach($params[0] as $partKey => $partVal){
										$nodeVal = str_replace($partKey, $partVal, $nodeVal);
									}
									$dom[0]->innertext($nodeVal);
								}
							}
							$html = str_replace($cache['node'], $node->flush(), $html);
							unset($dom);
							unset($node);
						}
						elseif ('loop-node' === $cache['action']){
							// 繰り返し用のテンプレートの追加
							$Template[$cache['target-depth']] = new HtmlTemplate($cache['node']);
							if (NULL === $depths){
								$depths = array();
							}
							$depths[] = array('target-depth' => $cache['target-depth'], 'node' => $cache['node']);
						}
					}
					$htmls[$tplDepth] = $html;
				}
				// サブテンプレートを適用
				if (is_array($depths) && 0 < count($depths)){
					$html = $htmls[0];
					for ($depthsIdx=0; $depthsIdx < count($depths); $depthsIdx++){
// 						var_dump($depths[$depthsIdx]['target-depth']);
// 						echo '<br>';
// 						var_dump($depths[$depthsIdx]['node']);
// 						echo '<br>';
// 						var_dump($htmls[$depths[$depthsIdx]['target-depth']]);
// 						echo '<br>';
// 						echo '<br>';
						$html = str_replace($depths[$depthsIdx]['node'], $htmls[$depths[$depthsIdx]['target-depth']], $html);
					}
// 					exit;
				}
				if (NULL !== $argTemplateHint){
					// 処理結果html文字列を返却
					if(TRUE === $newParamGetten){
						return array('html' => $html, 'param' => $argParams);
					}
					return $html;
				}
			}
			// キャッシュではない場合、htmlや値が変わっている場合
			else {
				foreach($argParams as $key => $val){
					if(is_numeric($key)){
						// 単純な再帰処理
						self::assign(NULL, $val, NULL, $argDepth, $arrayDepth+1);
					}
					else{
						// ノードの削除を処理
						if(NULL !== $argKey && self::REMOVE_NODE_KEY === $key || self::REMOVE_NODE_KEY === $val){
							$dom = $Template[$argDepth]->find($argKey);
							if(isset($dom) && is_array($dom) && isset($dom[0])){
								for ($domIdx = 0; count($dom) > $domIdx; $domIdx++) {
									// キャッシュを取っておく
									if (!isset($caches[$argDepth])){
										$caches[$argDepth] = array();
									}
									//$caches[$argDepth][] = array('arrayDepth' => $arrayDepth, 'key' => $key, 'findSelector' => $argKey, 'node' => (string)$dom[$domIdx]->outertext(), 'action' => 'remove-node');
									// 削除
									$dom[$domIdx]->remove();
								}
							}
							unset($dom);
						}
						// 属性の置換を処理
						elseif(NULL !== $argKey && self::REPLACE_ATTR_KEY === $key){
							$dom = $Template[$argDepth]->find($argKey);
							if(isset($dom) && is_array($dom) && isset($dom[0])){
								for ($domIdx = 0; count($dom) > $domIdx; $domIdx++) {
									foreach($val as $attrKey => $attrVal){
										if (0 < strlen($attrVal)){
											// キャッシュを取っておく
											if (!isset($caches[$argDepth])){
												$caches[$argDepth] = array();
											}
											//$caches[$argDepth][] = array('arrayDepth' => $arrayDepth, 'key' => $key, 'findSelector' => $argKey, 'node' => (string)$dom[$domIdx]->outertext(), 'action' => 'set-attr', 'attr' => $attrKey);
											// 置き換え
											$dom[$domIdx]->setAttribute($attrKey, $attrVal);
										}
										else {
											// キャッシュを取っておく
											if (!isset($caches[$argDepth])){
												$caches[$argDepth] = array();
											}
											//$caches[$argDepth][] = array('arrayDepth' => $arrayDepth, 'key' => $key, 'findSelector' => $argKey, 'node' => (string)$dom[$domIdx]->outertext(), 'action' => 'remove-attr', 'attr' => $attrKey);
											// 属性の削除
											$dom[$domIdx]->removeAttribute($attrKey);
										}
									}
								}
							}
							unset($dom);
						}
						// 属性の部分置換を処理
						elseif(NULL !== $argKey && self::PART_REPLACE_ATTR_KEY === $key){
							$dom = $Template[$argDepth]->find($argKey);
							if(isset($dom) && is_array($dom) && isset($dom[0])){
								for ($domIdx = 0; count($dom) > $domIdx; $domIdx++) {
									foreach($val as $attrKey => $part){
										// 部分置換
										// キャッシュを取っておく
										if (!isset($caches[$argDepth])){
											$caches[$argDepth] = array();
										}
										//$caches[$argDepth][] = array('arrayDepth' => $arrayDepth, 'key' => $key, 'findSelector' => $argKey, 'node' => (string)$dom[$domIdx]->outertext(), 'action' => 'replace-attr', 'attr' => $attrKey);
										$attrVal = $dom[$domIdx]->getAttribute($attrKey);
										foreach($part as $partKey => $partVal){
											if (0< strlen($partKey)){
												// replace
												$attrVal = str_replace($partKey, $partVal, $attrVal);
											}
											else {
												// add
												$attrVal = $attrVal.$partVal;
											}
										}
										// 置き換え
										$dom[$domIdx]->setAttribute($attrKey, $attrVal);
									}
								}
							}
							unset($dom);
						}
						// NODEの部分置換を処理
						elseif(NULL !== $argKey && self::PART_REPLACE_NODE_KEY === $key){
							$dom = $Template[$argDepth]->find($argKey);
							if(isset($dom) && is_array($dom) && isset($dom[0])){
								for ($domIdx = 0; count($dom) > $domIdx; $domIdx++) {
									// 部分置換
									// キャッシュを取っておく
									if (!isset($caches[$argDepth])){
										$caches[$argDepth] = array();
									}
									//$caches[$argDepth][] = array('arrayDepth' => $arrayDepth, 'key' => $key, 'findSelector' => $argKey, 'node' => (string)$dom[$domIdx]->outertext(), 'action' => 'replace-node');
									$nodeVal = $dom[$domIdx]->innertext();
									foreach($val as $partKey => $partVal){
										$nodeVal = str_replace($partKey, $partVal, $nodeVal);
									}
									// 置き換え
									$dom[$domIdx]->text($nodeVal);
								}
							}
							unset($dom);
						}
						// NODEの最後にNODEを追加する処理
						elseif(NULL !== $argKey && self::APPEND_NODE_KEY === $key){
							$dom = $Template[$argDepth]->find($argKey);
							if(isset($dom) && is_array($dom) && isset($dom[0])){
								for ($domIdx = 0; count($dom) > $domIdx; $domIdx++) {
									// キャッシュを取っておく
									if (!isset($caches[$argDepth])){
										$caches[$argDepth] = array();
									}
									//$caches[$argDepth][] = array('arrayDepth' => $arrayDepth, 'key' => $key, 'findSelector' => $argKey, 'node' => (string)$dom[$domIdx]->outertext(), 'action' => 'append-node');
									// 置き換え
									$dom[$domIdx]->innerHtml($dom[$domIdx]->innerHtml().$val);
								}
							}
							unset($dom);
						}
						// NODEの最初にNODEを追加する処理
						elseif(NULL !== $argKey && self::PREPEND_NODE_KEY === $key){
							$dom = $Template[$argDepth]->find($argKey);
							if(isset($dom) && is_array($dom) && isset($dom[0])){
								for ($domIdx = 0; count($dom) > $domIdx; $domIdx++) {
									// キャッシュを取っておく
									if (!isset($caches[$argDepth])){
										$caches[$argDepth] = array();
									}
									//$caches[$argDepth][] = array('arrayDepth' => $arrayDepth, 'key' => $key, 'findSelector' => $argKey, 'node' => (string)$dom[$domIdx]->outertext(), 'action' => 'prepend-node');
									// 置き換え
									$dom[$domIdx]->innerHtml($val.$dom[$domIdx]->innerHtml());
								}
							}
							unset($dom);
						}
						// 同じタグを繰り返し処理して描画する
						elseif(NULL !== $argKey && self::LOOP_NODE_KEY === $key){
							if(is_array($val)){
								$newDomHtml = '';
								$dom = $Template[$argDepth]->find($argKey);
								if(isset($dom) && is_array($dom) && isset($dom[0])){
									// キャッシュを取っておく
									if (!isset($caches[$argDepth])){
										$caches[$argDepth] = array();
									}
									//$caches[$argDepth][] = array('arrayDepth' => $arrayDepth, 'key' => $key, 'findSelector' => $argKey, 'node' => (string)$dom[0]->outertext(), 'action' => 'loop-node', 'target-depth' => $argDepth+1);
									$outerhtml = $dom[0]->outertext();
									foreach($val as $lKey => $lval){
										if(is_numeric($lKey)){
											$lKey = NULL;
										}
										$newDomHtml .= self::assign($outerhtml, $lval, $lKey, $argDepth+1, $arrayDepth+1);
									}
									// LOOPして出来たhtmlに置き換え
									// XXX ここは敢えて「setAttribute」メソッドで値を書き換えている！
									$dom[0]->setAttribute('outertext', $newDomHtml);
								}
							}
						}
						// 再帰処理
						elseif(is_array($val)){
							if(NULL !== $argKey){
								$key = $argKey . '-' . $key;
							}
							self::assign(NULL, $val, $key, $argDepth, $arrayDepth+1);
						}
						// ノード内のテキスト(html)の単純置換
						else {
							// ただのキーに紐づく値(innerHTML)の置換
							if(NULL !== $argKey){
								$key = $argKey . '-' . $key;
							}
							// ループの時用のkey自動走査対象の追加処理
							if(0 < $argDepth && FALSE === strpos($key, '#') && FALSE === strpos($key, '.') && !is_object($Template[$argDepth]->find($key))){
								// 対応のキーに値が無い時、自動でclass扱いしてみる
								// XXX class以外は対象外！理由は書くのが面倒くさい
								$key = '.' . $key;
							}
							$dom = $Template[$argDepth]->find($key);
							if(isset($dom) && is_array($dom) && isset($dom[0])){
								for ($domIdx = 0; count($dom) > $domIdx; $domIdx++) {
									// キャッシュを取っておく
									if (!isset($caches[$argDepth])){
										$caches[$argDepth] = array();
									}
									//$caches[$argDepth][] = array('arrayDepth' => $arrayDepth, 'key' => $key, 'findSelector' => $argKey, 'node' => (string)$dom[0]->outertext(), 'action' => 'set-node');
									// XXX メソッドは変えてはならない！
									$dom[$domIdx]->text($val);
								}
							}
						}
					}
				}
			}
		}

		if (FALSE === $useCache && NULL !== $argTplName){
			// テンプレートのキャッシュ化
// 			$cacheDir = getAutoGeneratedPath().'/cache/tpl/';
// 			if (!is_dir($cacheDir)){
// 				@mkdir($cacheDir, 0777, true);
// 				@exec('chmod -R 0777 ' .getAutoGeneratedPath().'/cache');
// 			}
// 			//$cachePath = $cacheDir.str_replace('/', '_', str_replace('//', '/', $argTplName)).'.'.sha1(serialize(array_keys_recursive($argParams))).'.tplcache.php';
// 			file_put_contents($cachePath, serialize($caches));
// 			@exec('chmod -R 0777 ' .$cachePath);
		}

		if (NULL !== $argTemplateHint){
			// 処理結果html文字列を返却
			if(TRUE === $newParamGetten){
				return array('html' => $Template[$argDepth]->flush(), 'param' => $argParams);
			}
			return $Template[$argDepth]->flush();
		}
	}
}

?>