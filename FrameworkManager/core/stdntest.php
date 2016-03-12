<?php

function getSTDNNextLine(){
	$stdin = trim(fgets(STDIN));
	if ($stdin === '') {
		return FALSE;
	}
	return $stdin;
}

$_line = getSTDNNextLine();
list($parsons, $memos) = explode(' ', $_line);

// validate
if ((int)$parsons < 1){
	echo 'input parsons error.';
}

if ((int)$parsons > 1000){
	echo 'input parsons error.';
}

if ((int)$memos < 0){
	echo 'input memos error.';
}
if ((int)$memos > 100000){
	echo 'input memos error.';
}

if ((int)$memos === 0){
	// 計測不能
	echo '-1'.PHP_EOL;
	exit;
}

$cnt = 1;
$liars = array();
$honests = array();
$memoLines = array();
for ($idx=0; $idx < (int)$memos; $idx++){
	$_line = getSTDNNextLine();
	$_tmp = explode(' ', $_line);
	// validate
	if (FALSE !== strpos($_line, 'liar')){
		if ($_tmp[0] === $_tmp[2]){
			// 嘘つきのパラドクス発生により計測不能
			echo '-1'.PHP_EOL;
			exit;
		}
		if (in_array($_tmp[0].' said '.$_tmp[2].' was an honest person.', $memoLines)){
			// 矛盾により計測不能
			echo '-1'.PHP_EOL;
			exit;
		}
		if (!in_array($_tmp[0], $liars)){
			$liars[] = $_tmp[0];
// 			if (in_array($_tmp[0], $honests)){
// 				unsst($honests[array_search($_tmp[0], $honests)]);
// 			}
		}
		if (!in_array($_tmp[2], $liars)){
			$liars[] = $_tmp[2];
// 			if (in_array($_tmp[2], $honests)){
// 				unset($honests[array_search($_tmp[2], $honests)]);
// 			}
		}
	}
	else if (FALSE !== strpos($_line, 'honest')){
		if (in_array($_tmp[0].' said '.$_tmp[2].' was a liar.', $memoLines)){
			// 矛盾により計測不能
			echo '-1'.PHP_EOL;
			exit;
		}
		$cnt = $cnt * 2;
// 		if (!in_array($_tmp[0], $honests) && !in_array($_tmp[0], $liars)){
// 			$honests[] = $_tmp[0];
// 		}
// 		if (!in_array($_tmp[2], $honests) && !in_array($_tmp[2], $liars)){
// 			$honests[] = $_tmp[2];
// 		}
	}
	if (!in_array($_line, $memoLines)){
		// 重複は不要
		$memoLines[] = $_line;
	}
}

// $bunshi = gmp_fact($parsons);
// $bunbo = gmp_fact(count($honests));

// $cnt = $bunshi / $bunbo - count($liars);

// $cnt = count($honests);
// for ($idx=1; $idx < count($liars); $idx++){
// 	$cnt = $cnt * count($liars);
// }

echo strlen(''.decbin($cnt/count($liars))).PHP_EOL;
exit;

// echo 'dise='.PHP_EOL;
// echo var_export($dise, true).PHP_EOL;
// echo PHP_EOL;
// echo 'board='.PHP_EOL;
// echo var_export($board, true).PHP_EOL;
// exit;
/////