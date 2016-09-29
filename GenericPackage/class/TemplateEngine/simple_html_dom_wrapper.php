<?php

class simple_html_dom extends simple_html_dom_org {}

class simple_html_dom_node extends simple_html_dom_node_org {

	public $dom = NULL;

	/**
	 * domを上位クラスでも参照出来るようにコンストラクタをオーバーライド
	 */
	function __construct($argDOM) {
		parent::__construct($argDOM);
		$this->dom = & $argDOM;
	}

	/**
	 * #0001の為にメソッドをオーバーライド
	 */
	function innertext() {
		// XXX getDOMメソッドはCOREでaddmethodされている事に注意！
		if (isset($this->_[HDOM_INFO_INNER])) return $this->_[HDOM_INFO_INNER];
		if (isset($this->_[HDOM_INFO_TEXT])) return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);

		$ret = '';
		foreach($this->nodes as $n){
			// XXX #0001 S.Ohno modifyed
			if(is_object($n->dom)){
				$ret .= $n->outertext();
			}
		}
		return $ret;
	}

	/**
	 * タグでくくられていない、テキストノードだけを抽出する
	 */
	function textNodes(){
		$textNodes = array();
		for($nodeIndex = 0; count($this->nodes) > $nodeIndex; $nodeIndex++){
			if('text' === strtolower($this->nodes[$nodeIndex]->tag) && strlen(trim($this->nodes[$nodeIndex]->innertext())) > 0){
				$textNodes[] = &$this->nodes[$nodeIndex];
			}
		}
		if(count($textNodes) == 0){
			return NULL;
		}
		return $textNodes;
	}

	function innerHtml($argHtmlText = NULL){
		if(NULL === $argHtmlText){
			// get
			return $this->text();
		}else{
			// set
			$this->setAttribute('innertext', $argHtmlText);
			$this->dom->load($this->dom->flush());
			return TRUE;
		}
	}

	/**
	 * jQueryを真似したアクセサ兼セッター
	 */
	function text($argText = NULL){
		if(NULL === $argText){
			// get
			return $this->innertext();
		}else{
			// set
			$this->setAttribute('innertext', $argText);
			return TRUE;
		}
	}

	/**
	 * jQueryを真似したアクセサ兼セッター
	 */
	function html($argHtmlText = NULL){
		if(NULL === $argHtmlText){
			// get
			return $this->outertext();
		}else{
			// set
			$this->setAttribute('outertext', $argHtmlText);
			$this->dom->load($this->dom->flush());
			return TRUE;
		}
	}

	/**
	 * カラにする
	 */
	public function remove(){
		return $this->__set('outertext', '');
	}

	/**
	 * html文字列にして返却
	 */
	public function flush(){
		return $this->__toString();
	}
	

	/**
	 * @orverlide
	 */
	protected function seek($selector, &$ret) {
        list($tag, $key, $val, $exp) = $selector;

        $end = (!empty($this->_[HDOM_INFO_END])) ? $this->_[HDOM_INFO_END] : 0;
        if ($end==0) {
            $parent = $this->parent;
            while (!isset($parent->_[HDOM_INFO_END]) && $parent!==null) {
                $end -= 1;
                $parent = $parent->parent;
            }
            $end += $parent->_[HDOM_INFO_END];
        }

        for($i=$this->_[HDOM_INFO_BEGIN]+1; $i<$end; ++$i) {
            $node = $this->dom->nodes[$i];
            $pass = true;
            $check = false;

            if ($tag==='*') {
                if (in_array($node, $this->children, true))
                    $ret[$i] = 1;
                continue;
            }

            // compare tag
            if ($tag && $tag!=$node->tag) {$pass=false;}
            if (is_array($key) && is_array($val)){
            		$checkNum = 0;
            		for($skIdx=0; $skIdx < count($key); $skIdx++){
            			$skey = $key[$skIdx];
            			$sexp = $exp[$skIdx];
            			$sval = $val[$skIdx];
            			// compare key
            			if ($pass && $skey && !(isset($node->attr[$skey]))) {$pass=false;}
            			// compare value
            			if ($pass && $skey && 0 < strlen($sval)) {
           				if($this->match($sexp, $sval, $node->attr[$skey])){
            					$checkNum++;
	            			}
	            			// handle multiple class
		            		elseif (strcasecmp($skey, 'class')===0) {
		            			foreach(explode(' ',$node->attr[$skey]) as $k) {
		            				if($this->match($sexp, $sval, $k)){
		            					$checkNum++;
		            					break;
		            				}
		            			}
		            		}
            			}
            			elseif($pass && $skey) {
            				$checkNum++;
            			}
            		}
            		if ($checkNum > 0 && $checkNum == $skIdx){
	            		$check = true;
            		}
            }
            else {
            	// compare key
            	if ($pass && $key && !(isset($node->attr[$key]))) {$pass=false;}
            	// compare value
            	if ($pass && $key && $val) {
            		$check = $this->match($exp, $val, $node->attr[$key]);
            		// handle multiple class
	                if (!$check && strcasecmp($key, 'class')===0) {
	                    foreach(explode(' ',$node->attr[$key]) as $k) {
	                        $check = $this->match($exp, $val, $k);
	                        if ($check) break;
	                    }
	                }
            	}
            }
            if (!$check) $pass = false;
            if ($pass) $ret[$i] = 1;
            unset($node);
        }
    }

	/**
	 * @orverlide
	 */
	protected function parse_selector($selector_string) {
		// pattern of CSS selectors, modified from mootools
		$pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[(\w+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])*?([, ]+)/is";
		preg_match_all($pattern, trim($selector_string).' ', $matches, PREG_SET_ORDER);
		$selectors = array();
		$result = array();
		foreach ($matches as $m) {
			if (trim($m[0])==='') continue;
	
			list($tag, $key, $val, $exp) = array($m[1], null, null, '=');
			if(!empty($m[2])) {$key='id'; $val=$m[2];}
			if(!empty($m[3])) {$key='class'; $val=$m[3];}
			if(isset($m[4]) && TRUE === (!empty($m[4]) || 0 < strlen($m[4]))) {$key=$m[4];}
			if(isset($m[5]) && TRUE === (!empty($m[5]) || 0 < strlen($m[5]))) {$exp=$m[5];}
			if(isset($m[6]) && TRUE === (!empty($m[6]) || 0 < strlen($m[6]))) {$val=$m[6];}

			// convert to lowercase
			if ($this->dom->lowercase) {$tag=strtolower($tag); $key=strtolower($key);}
	
			$spattern = "/(?:\[(\w+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])*?/is";
			preg_match_all($spattern, trim($m[0]).' ', $smatches, PREG_SET_ORDER);
			if (4 < count($smatches)){
				$smatchCnt=0;
				foreach ($smatches as $sm) {
					if (trim($sm[0])==='') continue;
					$smatchCnt++;
					if (1 > $smatchCnt) continue;
					if(!is_array($key)) {$key = array($key);}
					if(!is_array($val)) {$val = array($val);}
					if(!is_array($exp)) {$exp = array($exp);}
					list($skey, $sval, $sexp) = array(null, null, '=');
					if(isset($sm[1]) && TRUE === (!empty($sm[1]) || 0 < strlen($sm[1]))) {$skey=$sm[1];}
					if(isset($sm[2]) && TRUE === (!empty($sm[2]) || 0 < strlen($sm[2]))) {$sexp=$sm[2];}
					if(isset($sm[3]) && TRUE === (!empty($sm[3]) || 0 < strlen($sm[3]))) {$sval=$sm[3];}
					$key[] = $skey;
					$val[] = $sval;
					$exp[] = $sexp;
				}
			}
			$result[] = array($tag, $key, $val, $exp);
	
			if (trim($m[7])===',') {
				$selectors[] = $result;
				$result = array();
			}
		}
		if (count($result)>0)
			$selectors[] = $result;
	
		return $selectors;
	}
}

?>