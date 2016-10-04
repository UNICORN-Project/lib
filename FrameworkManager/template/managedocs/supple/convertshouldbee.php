<?php

$responce = "responseView";

if(isset($_POST["basetext"]) && 0 < strlen($_POST["basetext"])){
	// 変換処理
	$textLines = array();
	$basetexts = explode("\n", $_POST["basetext"]);
	for ($lineIdx = 0; $lineIdx < count($basetexts); $lineIdx++){
		// 1行は基本捨てる
		if (0 === $lineIdx && FALSE !== strpos($basetexts[$lineIdx], 'VERSION BUILD')){
			continue;
		}
		// 2行目以降はさらに分解して評価する
		$parts = explode(' ', $basetexts[$lineIdx]);
		// 2行目は基本URL遷移だけ見る
		if (1 === $lineIdx && FALSE !== strpos($basetexts[$lineIdx], 'URL GOTO') && isset($parts[1])){
			// URL遷移アクション
			$linkParts = explode("=", $parts[1]);
			if (isset($linkParts[1]) && 0 < strlen($linkParts[1])){
				$textLines[] = '「'.trim(str_replace('<SP>', ' ', $linkParts[1])).'」に移動する';
			}
		}
		// 3行目以降は基本アクション評価
		if (0 === strpos($parts[2], 'TYPE=A') && isset($parts[3])){
			// リンククリックアクション
			$linkParts = explode(":", $parts[3]);
			if (isset($linkParts[1]) && 0 < strlen($linkParts[1])){
				$textLines[] = '「'.trim(str_replace('<SP>', ' ', $linkParts[1])).'」のリンク先へ移動する';
			}
		}
		elseif (0 === strpos($parts[2], 'TYPE=BUTTON') && isset($parts[4])){
			// ボタンクリックアクション
			$linkParts = explode(":", $parts[4]);
			if (isset($linkParts[1]) && 0 < strlen($linkParts[1])){
				$textLines[] = '「'.trim(str_replace('<SP>', ' ', $linkParts[1])).'」ボタンをクリックする';
			}
		}
		// インプットアクション
		elseif (0 === strpos($parts[2], 'TYPE=INPUT:')){
			// テキストの入力
			if (0 === strpos($parts[2], 'TYPE=INPUT:TEXT') && isset($parts[4]) && isset($parts[5])){
				$linkParts = explode(":", $parts[4]);
				if (isset($linkParts[1]) && 0 < strlen($linkParts[1])){
					$msg = '「'.trim(str_replace('<SP>', ' ', $linkParts[1])).'」フィールドに';
					$linkParts = explode("=", $parts[5]);
					if (isset($linkParts[1]) && 0 < strlen($linkParts[1])){
						$msg .= '「'.trim(str_replace('<SP>', ' ', $linkParts[1])).'」と入力する';
						$textLines[] = $msg;
					}
				}
			}
			// ラジオボタンの変更
			if (0 === strpos($parts[2], 'TYPE=INPUT:RADIO') && isset($parts[4])){
				$linkParts = explode(":", $parts[4]);
				if (isset($linkParts[1]) && 0 < strlen($linkParts[1])){
					$textLines[] = '「'.trim(str_replace('<SP>', ' ', $linkParts[1])).'」ボタンをクリックする';
				}
			}
			// チェックボックス
			if (0 === strpos($parts[2], 'TYPE=INPUT:CHECKBOX') && isset($parts[4])){
				$linkParts = explode(":", $parts[4]);
				if (isset($linkParts[1]) && 0 < strlen($linkParts[1])){
					$textLines[] = '「'.trim(str_replace('<SP>', ' ', $linkParts[1])).'」ボタンをクリックする';
				}
			}
		}
		elseif(0 === strpos($parts[0], 'BACK')){
			$textLines[] = '履歴の前のページに戻る';
		}
	}
	$responce = implode('<br/>', $textLines);
}
else {
	$_POST["basetext"] = 'iMacrosのマクロをココにコピペして下さい。';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Cache-Control" content="no-cache" />
<title>テストコード変換ツール for Shouldbee</title>
<style type="text/css">
* {
	margin: 0;
	padding: 0;
	font-size: 12px;
}

body {
	width: 100%;
	height: 100%;
}

h1 {
	padding: 10px;
	font-size: 20px;
}

button {
	padding: 3px;
}

div.clear {
	clear: both;
}

div.descriptionView {
	padding: 20px;
}

div#formView {
	padding: 20px;
	background-color: #eeeeee;
}

div#formView div.formLine {
	margin-bottom: 5px;
}

div#formView div.formLine>div.label {
	width: 200px;
	float: left;
}

div#formView div.formLine>button.switch {
	margin-left: 20px;
}

div#formView div#multiParam {
	margin-top: 20px;
}

div#formView div.formLine div.multiParamLine {
	float: left;
}

div#formView div.formLine div.multiParamLine div.label {
	width: 110px;
	height: 25px;
}

div#formView div.formLine div.min div.label {
	width: 100px;
	height: 25px;
}

div#formView div.formLine div.multiParamLine input {
	width: 100px;
	margin-right: 10px;
}

div#formView div.formLine div.multiParamFile {
	width: 200px;
	display: none;
}

div#formView div.formLine div.multiParamFile input {
	width: 190px;
}

div#formView div.formLine button.multiParamLine {
	margin-top: 25px;
}

div#formView div.formLine div.multiParamNum {
	display: none;
}

div#responseView {
	top: 220px;
	margin: 20px;
}

div#responseView button {
	margin: 20px;
}

div#responseView pre#responseMain {
	padding: 10px;
	height: 100%;
	border: solid 2px;
}
</style>
</head>
<body>
	<h1>テストコード変換ツール for Shouldbee Ver 1.0</h1>
	<div class="descriptionView">
		「<a href="http://imacros.net/download" taget="_blank">iMacros無料のプラグイン版</a>」のマクロコードを「<a href="https://shouldbee.at/" taget="_blank">Shouldbee</a>」のテストコード形式に単純な変換をします。
		<br/>
		そのコードを基準に、テストコードの開発を行いって下さい。
	</div>
	<form method="post">
		<div id="formView">
			<div class="formLine">
				<textarea name="basetext" rows="10" style="width: 100%;"><?php echo $_POST["basetext"]; ?></textarea>
			</div>
			<div class="formLine">
				<input type="submit" value="convert!" />
			</div>
		</div>
		<div id="responseView">
			<pre id="responseMain">```<br/><?php echo $responce; ?><br/>```</pre>
		</div>
	</form>
</body>
</html>