<?php

function getSTDNNextLine(){
	$stdin = trim(fgets(STDIN));
	if ($stdin === '') {
		return FALSE;
	}
	return $stdin;
}

class SimpleBoardGame
{
	function getDise(){
		$_dise = getSTDNNextLine();
		if (FALSE === $_dise){
			echo 'input dise error. no data.';
			exit;
		}
		list($t, $b, $u, $d, $l, $r) = explode(' ', $_dise);
		if (!0 < (int)$t || !0 < (int)$b || !0 < (int)$u || !0 < (int)$d || !0 < (int)$l || !0 < (int)$r){
			echo 'input dise error. mismatch dise format.';
			exit;
		}
		if (!7 > (int)$t || !7 > (int)$b || !7 > (int)$u || !7 > (int)$d || !7 > (int)$l || !7 > (int)$r){
			echo 'input dise error. mismatch dise format.';
			exit;
		}
		if (!($t !== $b && $t !== $u && $t !== $d && $t !== $l && $t !== $r)){
			echo 'input dise error. duplicate dise.';
			exit;
		}
		if (!($b !== $t && $b !== $u && $b !== $d && $b !== $l && $b !== $r)){
			echo 'input dise error. duplicate dise.';
			exit;
		}
		if (!($u !== $t && $u !== $b && $u !== $d && $u !== $l && $u !== $r)){
			echo 'input dise error. duplicate dise.';
			exit;
		}
		if (!($d !== $t && $d !== $b && $d !== $u && $d !== $l && $d !== $r)){
			echo 'input dise error. duplicate dise.';
			exit;
		}
		if (!($l !== $t && $l !== $b && $l !== $u && $l !== $d && $l !== $r)){
			echo 'input dise error. duplicate dise.';
			exit;
		}
		if (!($r !== $t && $r !== $b && $r !== $u && $r !== $d && $r !== $l)){
			echo 'input dise error. duplicate dise.';
			exit;
		}
		$dise = array();
		$dise['t'] = (int)$t;
		$dise['b'] = (int)$b;
		$dise['u'] = (int)$u;
		$dise['d'] = (int)$d;
		$dise['l'] = (int)$l;
		$dise['r'] = (int)$r;
		return $dise;
	}
	function getBoard(){
		$_boardMax = getSTDNNextLine();
		if (FALSE === $_boardMax){
			echo 'input boardMax error. no data.';
			exit;
		}
		$boardMax = (int)$_boardMax;
		if (!(2 <= $boardMax && 1000 >= $boardMax)){
			echo 'input boardMax error. mismatch boardMax format.';
			exit;
		}
		$board = array();
		for ($idx=0; $idx < $boardMax; $idx++){
			$_board = getSTDNNextLine();
			if (FALSE === $_board){
				echo 'input board error. no data.';
				exit;
			}
			if (!(0 < $_board && 7 > $_board)){
				echo 'input board error. mismatch board format.';
				exit;
			}
			$board[$idx] = (int)$_board;
		}
		if (count($board) !== $boardMax){
			echo 'input board error. mismatch board format.';
			exit;
		}
		return $board;
	}
}

$game = new SimpleBoardGame();
$dise = $game->getDise();
$diseKeys = array_keys($dise);
$board = $game->getBoard();

// スタート位置のバリデート
if ($dise['t'] !== $board[0]){
	echo 'input game error. mismatch game format.';
	exit;
}

// 何回でクリア出来るかを計算する
$rotateCount = 0;
for ($idx=1; $idx < count($board); $idx++){
	$rotateCount++;
	// 現在表示しているダイス面から、真下(dise配列上隣の値)への回転は１回転多くなる
	$nowPos = array_search(array_search($board[$idx-1], $dise), $diseKeys);
	$nextPos = array_search(array_search($board[$idx], $dise), $diseKeys);
	if ($nextPos === $nowPos){
		$rotateCount = $rotateCount-1;
	}
	elseif (0 === ($nowPos % 2) && 1 === (($nextPos+1) - ($nowPos+1))){
		// もう一回転必要
		$rotateCount++;
	}
	elseif (1 === ($nowPos % 2) && 1 === (($nowPos+1) - ($nextPos+1))){
		// もう一回転必要
		$rotateCount++;
	}
}

echo $rotateCount.PHP_EOL;
exit;

// echo 'dise='.PHP_EOL;
// echo var_export($dise, true).PHP_EOL;
// echo PHP_EOL;
// echo 'board='.PHP_EOL;
// echo var_export($board, true).PHP_EOL;
// exit;
/////