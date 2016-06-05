<?php

class WebFlowControllerBase extends WebControllerBase {

	public $action='';
	public $isSSLRequired=NULL;
	public $section='';
	public $target='';
	public $validateError = FALSE;
	public static $flowpostformsectionUsed = FALSE;

	protected function _reverseRewriteURL($argAction=NULL, $argQuery='', $argSSLRequired=NULL){
		$action = $this->action;
		$sslRequired = $this->isSSLRequired;
		if(NULL !== $argAction){
			$action= $argAction;
		}
		if (NULL !== $argSSLRequired){
			$sslRequired = $argSSLRequired;
		}
		return Flow::reverseRewriteURL($action, $argQuery, $sslRequired);
	}

	protected function _convertBackflowForm($argBeforeSection, $argParams, $argParentParamKey=NULL){
		if (is_array($argParams) && 0 < count($argParams)){
			if (NULL === $argParentParamKey){
				$argParentParamKey = 'backflow['.$argBeforeSection.']';
			}
			foreach($argParams as $key => $val){
				if ($key !== $argBeforeSection){
					$inputName = $argParentParamKey.'['.$key.']';
					if (is_array($val)){
						$this->_convertBackflowForm($argBeforeSection, $val, $inputName);
					}
					else {
						Flow::$params['view'][] = array('form[flowpostformsection!=null]' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input class="'.$argBeforeSection.'-flow" type="hidden" name="'.$inputName.'" value="'.$val.'"/>'.PHP_EOL));
					}
				}
			}
		}
	}

	protected function _convertWebFlowForm($argFormSection, $argParams, $argParentParamInputKey=''){
		if (is_array($argParams) && 0 < count($argParams) && NULL !== $argFormSection && 0 < strlen($argFormSection)){
			if (isset($_POST['flowpostformsection']) && $_POST['flowpostformsection'] == $argFormSection){
				// 戻るの場合は無視
				return;
			}
			foreach($argParams as $key => $val){
				logging('key='.$key, 'flow');
				logging('val='.var_export($key, true), 'flow');
				// POSTパラメータを優先して利用する
				if (!isset($_POST[$key]) && !isset(Flow::$params['post'][$key])){
					$inputName = $key;
					if ('' !== $argParentParamInputKey){
						$inputName = $argParentParamInputKey.'['.$key.']';
					}
					if (is_array($val)){
						$this->_convertWebFlowForm($argFormSection, $val, $inputName);
					}
					else {
						// パスワード以外はREPLACE ATTRIBUTEを自動でして上げる
						if(0 !== strpos($key, 'pass') && $key !== 'flowpostformsection-backflow-section' && $key !== 'flowpostformsection-backflow-section-query' && is_string($val) && 0 < strlen($val)){
							if(NULL === Flow::$params['view']){
								Flow::$params['view'] = array();
							}
							Flow::$params['view'][] = array('form[flowpostformsection='.$argFormSection.'] input[class=flowpostformparam-' . $inputName . '][type!=radio][type!=checkbox]' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array('value'=>htmlspecialchars($val))));
							Flow::$params['view'][] = array('form[flowpostformsection='.$argFormSection.'] input[class=flowpostformparam-' . $inputName . '][type=radio][value!='.htmlspecialchars($val).']' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array('checked'=>'')));
							Flow::$params['view'][] = array('form[flowpostformsection='.$argFormSection.'] input[class=flowpostformparam-' . $inputName . '][type=radio][value='.htmlspecialchars($val).']' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array('checked'=>'checked')));
							Flow::$params['view'][] = array('form[flowpostformsection='.$argFormSection.'] input[class=flowpostformparam-' . $inputName . '][type=checkbox][value='.htmlspecialchars($val).']' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array('checked'=>'checked')));
							Flow::$params['view'][] = array('form[flowpostformsection='.$argFormSection.'] select[class=flowpostformparam-' . $inputName . '] option[value!='.htmlspecialchars($val).']' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array('selected'=>'')));
							Flow::$params['view'][] = array('form[flowpostformsection='.$argFormSection.'] select[class=flowpostformparam-' . $inputName . '] option[value='.htmlspecialchars($val).']' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array('selected'=>'selected')));
							Flow::$params['view'][] = array('form[flowpostformsection='.$argFormSection.'] textarea[class=flowpostformparam-' . $inputName . ']' => htmlspecialchars($val));
							Flow::$params['view'][] = array('[flowpostformsection='.$argFormSection.'] [class=confirmflowpostforminput-' . $inputName . ']' => nl2br(htmlspecialchars( ((is_numeric($val) && 0 !== strpos($val, '0') && FALSE === strpos($inputName, 'day') && FALSE === strpos($inputName, 'date'))? number_format($val) : $val) )));
							Flow::$params['view'][] = array('[flowpostformsection='.$argFormSection.'] [class=confirmflowpostformswitch-' . $inputName . '][value!='.htmlspecialchars($val).']' => HtmlViewAssignor::REMOVE_NODE);
							Flow::$params['view'][] = array('[flowpostformsection='.$argFormSection.'] [class=flowpostformhidden-' . $argParentParamInputKey . ']' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input type="hidden" name="'.$inputName.'" value="'.htmlspecialchars($val).'"/>'.PHP_EOL));
							// 入力の戻る用
							Flow::$params['view'][]=  array('form[flowpostformsection!='.$argFormSection.']' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input type="hidden" name="'.$inputName.'" value="' . htmlspecialchars($val) . '"/>'));
							Flow::$params['view'][]=  array('form[backflowpostformsection!=null]' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input type="hidden" name="'.$inputName.'" value="' . htmlspecialchars($val) . '"/>'));
						}
					}
				}
			}
		}
	}

	protected function _initWebFlowForm($argParams, $argParentParamKey='', $argParentParamInputKey=''){
		if (is_array($argParams) && 0 < count($argParams)){
			// switch要素を一旦一括でhidden化
			static $switched = FALSE;
			if (FALSE === $switched){
				$switched = TRUE;
				Flow::$params['view'][] = array('[flowpostformsection='.$_POST['flowpostformsection'].'] [class^=confirmflowpostformswitch-]' => array(HtmlViewAssignor::PART_REPLACE_ATTR_KEY => array('style' => array(''=>'display:none;'))));
			}
			foreach($argParams as $key => $val){
				$keyName = '[\''.$key.'\']';
				$inputName = $key;
				if ('' !== $argParentParamInputKey){
					$inputName = $argParentParamInputKey.'['.$key.']';
				}
				if ('' !== $argParentParamKey){
					$keyName = $argParentParamKey.$keyName;
				}
				if (is_array($val)){
					$this->_initWebFlowForm($val, $keyName, $inputName);
				}
				else {
					$isHidden = FALSE;
					$executed = FALSE;
					// Flow用としてPOSTパラメータをしまっておく
					eval('$isHidden = isset(Flow::$params[\'post\']'.$keyName.');');
					if (FALSE === $isHidden){
						// flowFormでPOSTされていたらbackfrowの処理をしておく
						eval('Flow::$params[\'post\']'.$keyName.' = $val;');
						if($_GET['_c_'] === $_POST['flowpostformsection']){
							// backflowがポストされてきたらそれをviewのformに自動APPEND
							if($key === 'flowpostformsection-backflow-section'){
								Flow::$params['view'][] = array('form[flowpostformsection]' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input type="hidden" name="flowpostformsection-backflow-section" value="' . $val . '"/>'));
								self::$flowpostformsectionUsed = TRUE;
								$executed = TRUE;
							}
							elseif($key === 'flowpostformsection-backflow-section-query'){
								Flow::$params['view'][] = array('form[flowpostformsection]' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input type="hidden" name="flowpostformsection-backflow-section-query" value="' . $val . '"/>'));
								$executed = TRUE;
							}
						}
						// パスワード以外はREPLACE ATTRIBUTEを自動でして上げる
						if($key !== 'flowpostformsection-backflow-section' && $key !== 'flowpostformsection-backflow-section-query'){
							if(NULL === Flow::$params['view']){
								Flow::$params['view'] = array();
							}
							//if (0 !== strpos($key, 'pass')){
								Flow::$params['view'][] = array('form[flowpostformsection='.$_POST['flowpostformsection'].'] input[class=flowpostformparam-' . $inputName . '][type!=radio][type!=checkbox]' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array('value'=>htmlspecialchars($val))));
								Flow::$params['view'][] = array('form[flowpostformsection='.$_POST['flowpostformsection'].'] input[class=flowpostformparam-' . $inputName . '][type=radio][value!='.htmlspecialchars($val).']' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array('checked'=>'')));
								Flow::$params['view'][] = array('form[flowpostformsection='.$_POST['flowpostformsection'].'] input[class=flowpostformparam-' . $inputName . '][type=radio][value='.htmlspecialchars($val).']' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array('checked'=>'checked')));
								Flow::$params['view'][] = array('form[flowpostformsection='.$_POST['flowpostformsection'].'] input[class=flowpostformparam-' . $inputName . '][type=checkbox][value='.htmlspecialchars($val).']' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array('checked'=>'checked')));
								Flow::$params['view'][] = array('form[flowpostformsection='.$_POST['flowpostformsection'].'] select[class=flowpostformparam-' . $inputName . '] option[value!='.htmlspecialchars($val).']' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array('selected'=>'')));
								Flow::$params['view'][] = array('form[flowpostformsection='.$_POST['flowpostformsection'].'] select[class=flowpostformparam-' . $inputName . '] option[value='.htmlspecialchars($val).']' => array(HtmlViewAssignor::REPLACE_ATTR_KEY => array('selected'=>'selected')));
								Flow::$params['view'][] = array('form[flowpostformsection='.$_POST['flowpostformsection'].'] textarea[class=flowpostformparam-' . $inputName . ']' => htmlspecialchars($val));
								Flow::$params['view'][] = array('[flowpostformsection='.$_POST['flowpostformsection'].'] [class=confirmflowpostforminput-' . $inputName . ']' => nl2br(htmlspecialchars( ((is_numeric($val) && 0 !== strpos($val, '0') && FALSE === strpos($inputName, 'day') && FALSE === strpos($inputName, 'date'))? number_format($val) : $val) )));
								Flow::$params['view'][] = array('[flowpostformsection='.$_POST['flowpostformsection'].'] [class=confirmflowpostformswitch-' . $inputName . '][value='.htmlspecialchars($val).']' => array(HtmlViewAssignor::PART_REPLACE_ATTR_KEY => array('style' => array('display:none;'=>''))));
								Flow::$params['view'][] = array('[flowpostformsection='.$_POST['flowpostformsection'].'] [class=flowpostformhidden-' . $argParentParamInputKey . ']' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input type="hidden" name="'.$inputName.'" value="'.htmlspecialchars($val).'"/>'.PHP_EOL));
								//Flow::$params['view'][] = array('[flowpostformsection='.$_POST['flowpostformsection'].'] [class=confirmflowpostformswitch-' . $inputName . '][value!='.htmlspecialchars($val).']' => HtmlViewAssignor::REMOVE_NODE);
							//}
							// 入力の確認用
							Flow::$params['view'][]=  array('form[confirmflowpostformsection!='.$_POST['flowpostformsection'].']' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input class="init-flow-confirmflow '.$inputName.'" type="hidden" name="'.$inputName.'" value="' . htmlspecialchars($val) . '"/>'.PHP_EOL));
							// 入力の戻る用
							Flow::$params['view'][]=  array('form[backflowpostformsection!='.$_POST['flowpostformsection'].']' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input class="init-flow-backflow '.$inputName.'" type="hidden" name="'.$inputName.'" value="' . htmlspecialchars($val) . '"/>'.PHP_EOL));
						}
						if(0 === strpos($inputName, 'backflow') && $this->target.str_replace('_', '-', strtolower(get_class($this))) !== $_POST['flowpostformsection'].'Flow' && FALSE === $executed && 0 !== strpos($key, 'pass')){
							Flow::$params['view'][]=  array('form[flowpostformsection]' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input class="init-flow '.$inputName.'" type="hidden" name="'.$inputName.'" value="' . htmlspecialchars($val) . '"/>'.PHP_EOL));
						}
						// auto validate
						// flowFormでPOSTされていたら自動的にバリデートする
						if($_GET['_c_'] === $_POST['flowpostformsection']){
							try{
								if(FALSE !== strpos($key, 'mail')){
									// 強制loginID認証モードの場合はmailにloginIDが入ってるのでメアドvalidateをしない
									if (TRUE !== (isset($_SERVER['__loginID__']) && $_SERVER['__loginID__'] == $val)){
										// メールアドレスのオートバリデート
										Validations::isEmail($val);
									}
								}
								if(FALSE !== strpos($key, '_must') && 0 === strlen($val)){
									debug('must exception');
									// 必須パラメータの存在チェック
									throw new Exception();
								}
							}
							catch (Exception $Exception){
								// 最後のエラーメッセージを取っておく
								$this->validateError = TRUE;
								if(NULL === Flow::$params['view']){
									Flow::$params['view'] = array();
								}
								// XXX メッセージの固定化いるか？？
								Flow::$params['view'][] = array('div[flowpostformsectionerror=' . $_POST['flowpostformsection'] . ']' => 'メールアドレスの形式が違います');
							}
						}
					}
				}
			}
		}
	}

	protected function _initWebFlow(){
		// Flowパラムの初期化
		if(NULL === Flow::$params){
			Flow::$params = array();
		}

		// GETパラメータの各種自動処理
		if(isset($_GET) && 0 < count($_GET)){
			if (TRUE !== (isset(Flow::$params['get']) && is_array(Flow::$params['get']))){
				Flow::$params['get'] = array();
			}
			foreach($_GET as $key => $val){
				// Flow用としてPOSTパラメータをしまっておく
				Flow::$params['get'][$key] = $val;
				if(NULL === Flow::$params['view']){
					Flow::$params['view'] = array();
				}
				Flow::$params['view'][] = array('[frowparamsection*=' . $key . ']' => array(HtmlViewAssignor::PART_REPLACE_NODE_KEY => array('_flow_'.$key.'_' => $val)));
				Flow::$params['view'][] = array('[frowparamsection*=' . $key . ']' => array(HtmlViewAssignor::PART_REPLACE_ATTR_KEY => array('href' => array('_flow_'.$key.'_' => $val), 'value' => array('_flow_'.$key.'_' => $val), 'src' => array('_flow_'.$key.'_' => $val))));
			}
		}

		self::$flowpostformsectionUsed = FALSE;

		if(isset($_POST['flowpostformsection']) && 0 < count($_POST)){
			if (!isset(Flow::$params)){
				Flow::$params['post'] = array();
			}
			// backflowで戻ってきた場合のPOSTの復帰処理を先にやっておく
			if (isset($_POST['flowpostformsection-backflow-section']) && $this->target.str_replace('_', '-', strtolower($this->section)) === str_replace('_', '-', strtolower($_POST['flowpostformsection-backflow-section'])) && isset($_POST['backflow']) && isset($_POST['backflow'][$this->section])){
				// backflowで戻ってきた
				$_POST = array_merge($_POST, $_POST['backflow'][$this->section]);
				unset($_POST['backflow'][$this->section]);
			}
			if (isset($_COOKIE['__loginID__'])){
				unset($_COOKIE['__loginID__']);
				setcookie('__loginID__', '', time() - 3600, '/');
				// loginのcancelthisbackflowを代行する
				if (isset($_POST['mail'])) {
					unset($_POST['mail']);
				}
				if (isset($_POST['pass'])) {
					unset($_POST['pass']);
				}
				if (isset($_POST['passwd'])) {
					unset($_POST['passwd']);
				}
				if (isset($_POST['password'])) {
					unset($_POST['password']);
				}
				if (isset(Flow::$params['post']['mail'])) {
					unset(Flow::$params['post']['mail']);
				}
				if (isset(Flow::$params['post']['pass'])) {
					unset(Flow::$params['post']['pass']);
				}
				if (isset(Flow::$params['post']['passwd'])) {
					unset(Flow::$params['post']['passwd']);
				}
				if (isset(Flow::$params['post']['password'])) {
					unset(Flow::$params['post']['password']);
				}
			}
			$this->_initWebFlowForm($_POST);
			if(isset($this->validateError) && TRUE === $this->validateError){
				// オートバリデートでエラー
				debug('$validateError');
				return FALSE;
			}
		}

		// Backflowの初期化
		if(NULL === Flow::$params['backflow']){
			Flow::$params['backflow'] = array();
		}

		// 一つ前の画面のbackflowをflowpostformsectionに自動で挿入
		if(count(Flow::$params['backflow']) > 0){
			$backFrowID = Flow::$params['backflow'][count(Flow::$params['backflow']) -1]['section'];
			if (isset(Flow::$params['backflow'][count(Flow::$params['backflow']) -1]['target']) && 0 < strlen(Flow::$params['backflow'][count(Flow::$params['backflow']) -1]['target'])){
				$backFrowID = Flow::$params['backflow'][count(Flow::$params['backflow']) -1]['target'] . '/' . $backFrowID;
			}
			if('' === Flow::$params['backflow'][count(Flow::$params['backflow']) -1]['section']){
				$backFrowID = $this->section;
			}
			else {
				$backFrowID = str_replace('//', '/', $backFrowID);
			}
			// Viewの初期化
			if(NULL === Flow::$params['view']){
				Flow::$params['view'] = array();
			}
			Flow::$params['view'][] = array('form[flowpostformsection]' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input type="hidden" name="flowpostformsection-backflow-section" value="' . $backFrowID . '"/>'));
			Flow::$params['view'][] = array('form[flowpostformsection]' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input type="hidden" name="flowpostformsection-backflow-section-query" value="' . Flow::$params['backflow'][count(Flow::$params['backflow']) -1]['query'] . '"/>'));
			self::$flowpostformsectionUsed = TRUE;
		}

		// 現在実行中のFlowをBackflowとして登録しておく
		$query = '';
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
		Flow::$params['backflow'][] = array('section' => $this->section, 'target' => $this->target, 'query' => htmlspecialchars($query));
		debug('backflows=');
		debug(Flow::$params['backflow']);

		// flowpostformsectionに現在の画面をBackFlowとして登録する
		if(NULL === Flow::$params['view'] && FALSE === self::$flowpostformsectionUsed){
			$backFrowID = Flow::$params['backflow'][count(Flow::$params['backflow']) -1]['target'] . '/' . Flow::$params['backflow'][count(Flow::$params['backflow']) -1]['section'];
			if('' === Flow::$params['backflow'][count(Flow::$params['backflow']) -1]['target']){
				$backFrowID = Flow::$params['backflow'][count(Flow::$params['backflow']) -1]['section'];
			}
			else {
				$backFrowID = str_replace('//', '/', $backFrowID);
			}
			Flow::$params['view'][] = array('form[flowpostformsection=]' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input type="hidden" name="flowpostformsection-backflow-section" value="' . $backFrowID . '"/>'));
			Flow::$params['view'][] = array('form[flowpostformsection]' => array(HtmlViewAssignor::APPEND_NODE_KEY => '<input type="hidden" name="flowpostformsection-backflow-section-query" value="' . Flow::$params['backflow'][count(Flow::$params['backflow']) -1]['query'] . '"/>'));
		}

		return TRUE;
	}
}

?>