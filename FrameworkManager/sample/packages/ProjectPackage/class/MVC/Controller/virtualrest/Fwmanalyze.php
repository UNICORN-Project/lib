<?php

class Fwmanalyze extends RestControllerBase
{
	public $virtualREST = TRUE;

	public function get($argRequestParams=NULL) {
		$request = $this->getRequestParams();
		if (!isset($request['target'])){
			$this->httpStatus = 400;
			throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
		}
		$timely = 10;
		$timelyKeys = array('10' => 'day', '7' => 'month', '4' => 'year');
		$timelyPad = array('10' => ' 00:00:00', '7' => '-01 00:00:00', '4' => '-01-01 00:00:00');
		if (isset($request['timely']) && is_numeric($request['timely'])){
			$timely -= 3 * (int)$request['timely'];
		}
		$join = '';
		if (isset($request['campaign_code']) && 0 < strlen($request['campaign_code'])){
			$join  = ' INNER JOIN `campaign_relay` cr ON cr.`campaign_code` = \''.$request['campaign_code'].'\' AND cr.`owner_id` = ';
			if ('user' === $request['target']){
				$join .= 'tg.`id`';
			}
			else {
				$join .= 'tg.`owner_id`';
			}
		}
		$mindate = Utilities::modifyDate('-1day', 'Y-m-d H:i:s', Utilities::date('Y-m-d 00:00:00', null, 'Asia/Tokyo', 'Asia/Tokyo'), 'Asia/Tokyo', 'GMT');
		$maxdate = Utilities::modifyDate('-1day', 'Y-m-d H:i:s', Utilities::date('Y-m-d 23:59:59', null, 'Asia/Tokyo', 'Asia/Tokyo'), 'Asia/Tokyo', 'Asia/Tokyo');
		if (isset($request['mindate'])){
			$request['mindate'] = substr($request['mindate'], 0, $timely);
			debug($request['mindate'].$timelyPad[(string)$timely]);
			$mindate = Utilities::date('Y-m-d H:i:s', $request['mindate'].$timelyPad[(string)$timely], 'Asia/Tokyo', 'GMT');
		}
		if (isset($request['maxdate'])){
			$request['maxdate'] = substr($request['maxdate'], 0, $timely);
			debug($request['maxdate'].$timelyPad[(string)$timely]);
			$maxdate = Utilities::modifyDate('-1second', 'Y-m-d H:i:s', Utilities::modifyDate('+1'.$timelyKeys[(string)$timely], 'Y-m-d H:i:s', $request['maxdate'].$timelyPad[(string)$timely], 'Asia/Tokyo', 'GMT'), 'GMT', 'GMT');
		}
		if ((int)Utilities::date('U', $mindate, 'GMT', 'GMT') > (int)Utilities::date('U', $maxdate, 'GMT', 'GMT')){
			// 大小逆エラー
			$this->httpStatus = 400;
			throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
		}
		debug($mindate);
		debug($maxdate);
		// 返却データの初期化 デフォルト0埋めしておく
		$loopIdx = 0;
		$dateKey = substr(Utilities::date('Y-m-d H:i:s', $mindate, 'GMT', 'Asia/Tokyo'), 0, $timely);
		$maxdateKey = substr(Utilities::date('Y-m-d H:i:s', $maxdate, 'GMT', 'Asia/Tokyo'), 0, $timely);
		$data = array($dateKey => array('date' => $dateKey, 'total' => '0', 'intotal' => '0', 'outtotal' => '0', 'in' => '0', 'out' => '0', 'oneout' => '0'));
		if ($request['target'] === 'purchase'){
			$data[$dateKey]['outtotal'] = '-';
			$data[$dateKey]['oneout'] = '-';
		}
		while ($dateKey !== $maxdateKey){
			if ($loopIdx > 1000){
				// 1000日(月)以上の差は処理させない！
				$this->httpStatus = 400;
				throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, $this->httpStatus);
				break;
			}
			$dateKey = substr(Utilities::modifyDate('+1'.$timelyKeys[(string)$timely], 'Y-m-d H:i:s', $dateKey.$timelyPad[(string)$timely], 'Asia/Tokyo', 'Asia/Tokyo'), 0, $timely);
			$data[$dateKey] = array('date' => $dateKey, 'total' => '0', 'intotal' => '0', 'outtotal' => '0', 'in' => '0', 'out' => '0', 'oneout' => '0');
			if ($request['target'] === 'purchase'){
				$data[$dateKey]['outtotal'] = '-';
				$data[$dateKey]['oneout'] = '-';
			}
			$loopIdx++;
		}
		// 入会(投稿)数
		$bind = array();
		$bind['mindate'] = $mindate;
		$bind['maxdate'] = $maxdate;
		$query = '';
		if ($request['target'] === 'purchase'){
			$query .= 'SELECT SUBSTRING((tg.`created` + INTERVAL 9 HOUR), 1, '.$timely.') as date, SUM(tg.`total_price`) AS cnt FROM `'.$request['target'].'` tg'.$join.' WHERE tg.`created` BETWEEN :mindate AND :maxdate GROUP BY date ORDER BY date ASC';
		}
		else {
			$query .= 'SELECT SUBSTRING((tg.`created` + INTERVAL 9 HOUR), 1, '.$timely.') as date, COUNT(tg.`id`) AS cnt FROM `'.$request['target'].'` tg'.$join.' WHERE tg.`created` BETWEEN :mindate AND :maxdate GROUP BY date ORDER BY date ASC';
		}
		$response = DBO::sharedInstance()->execute($query, $bind);
		if (FALSE !== $response){
			$analyzies = $response->GetAll();
			for ($anaIdx=0; $anaIdx < count($analyzies); $anaIdx++){
				$data[$analyzies[$anaIdx]['date']]['in'] = (string)$analyzies[$anaIdx]['cnt'];
			}
		}
		// 退会(公開)数
		$bind = array();
		$bind['mindate'] = $mindate;
		$bind['maxdate'] = $maxdate;
		$query = '';
		if ($request['target'] === 'purchase'){
			$query .= 'SELECT SUBSTRING((tg.`created` + INTERVAL 9 HOUR), 1, '.$timely.') as date, COUNT(tg.`id`) AS cnt FROM `'.$request['target'].'` tg'.$join.' WHERE tg.`created` BETWEEN :mindate AND :maxdate';
		}
		else if ($request['target'] === 'productitem'){
			// 公開日で公開数をサマリーする
			$query .= 'SELECT SUBSTRING((tg.`release_date` + INTERVAL 9 HOUR), 1, '.$timely.') as date, COUNT(tg.`id`) AS cnt FROM `'.$request['target'].'` tg'.$join.' WHERE tg.`release_date` BETWEEN :mindate AND :maxdate';
		}
		else {
			$bind['available'] = '0';
			$query .= 'SELECT SUBSTRING((tg.`modified` + INTERVAL 9 HOUR), 1, '.$timely.') as date, COUNT(tg.`id`) AS cnt FROM `'.$request['target'].'` tg'.$join.' WHERE tg.`modified` BETWEEN :mindate AND :maxdate AND tg.`available` = :available';
		}
		$query .= ' GROUP BY date ORDER BY date ASC';
		$response = DBO::sharedInstance()->execute($query, $bind);
		if (FALSE !== $response){
			$analyzies = $response->GetAll();
			for ($anaIdx=0; $anaIdx < count($analyzies); $anaIdx++){
				$data[$analyzies[$anaIdx]['date']]['out'] = (string)$analyzies[$anaIdx]['cnt'];
			}
		}
		if ($request['target'] !== 'productitem'){
			// 当日(月)退会数
			$bind = array();
			$query = 'SELECT SUBSTRING((tg.`modified` + INTERVAL 9 HOUR), 1, '.$timely.') as date, COUNT(tg.`id`) AS cnt FROM `'.$request['target'].'` tg'.$join.' WHERE';
			if ($request['target'] === 'productitem'){
				// 作品の場合は非公開数(削除 AND リジェクト)
				$bind['mindate'] = $mindate;
				$bind['maxdate'] = $maxdate;
				// 削除
				$bind['available'] = '0';
				// リジェクト
				$bind['reviewed'] = '9';
				$query .= ' tg.`modified` BETWEEN :mindate AND :maxdate AND (tg.`available` = :available OR tg.`reviewed` = :reviewed )';
			}
			else {
				$bind['mindate'] = $mindate;
				$bind['maxdate'] = $maxdate;
				$bind['available'] = '0';
				$query .= ' SUBSTRING((tg.`created` + INTERVAL 9 HOUR), 1, '.$timely.') = SUBSTRING((tg.`modified` + INTERVAL 9 HOUR), 1, '.$timely.') AND tg.`modified` BETWEEN :mindate AND :maxdate AND tg.`available` = :available';
			}
			$query .= ' GROUP BY date ORDER BY date ASC';
			$response = DBO::sharedInstance()->execute($query, $bind);
			if (FALSE !== $response){
				$analyzies = $response->GetAll();
				for ($anaIdx=0; $anaIdx < count($analyzies); $anaIdx++){
					$data[$analyzies[$anaIdx]['date']]['oneout'] = (string)$analyzies[$anaIdx]['cnt'];
				}
			}
		}
		// 最後に配列を成形しなおす
		$responce = array();
		foreach($data as $dateKey => $analyzed){
			// 累計推移
			$maxdate = Utilities::modifyDate('-1second', 'Y-m-d H:i:s', Utilities::modifyDate('+1'.$timelyKeys[(string)$timely], 'Y-m-d H:i:s', $dateKey.$timelyPad[(string)$timely], 'Asia/Tokyo', 'Asia/Tokyo'), 'Asia/Tokyo', 'GMT');
			// 指定日付時点の入会総数を先ず取る
			$bind = array();
			$bind['maxdate'] = $maxdate;
			$query = 'SELECT COUNT(tg.`id`) AS cnt FROM `'.$request['target'].'` tg'.$join.' WHERE tg.`created` <= :maxdate';
			$response = DBO::sharedInstance()->execute($query, $bind);
			if (FALSE !== $response){
				$analyzies = $response->GetAll();
				$analyzed['intotal'] = $analyzies[0]['cnt'];
			}
			if ($request['target'] !== 'purchase'){
				// 指定日付時点の退会総数も取る
				$bind = array();
				$bind['maxdate'] = $maxdate;
				$query = 'SELECT COUNT(tg.`id`) AS cnt FROM `'.$request['target'].'` tg'.$join.' WHERE';
				if ($request['target'] === 'productitem'){
					// 作品の場合は公開数
					$bind['mindate'] = '2014-12-31 15:00:00';
					$query .= ' tg.`release_date` BETWEEN :mindate AND :maxdate';
				}
				else {
					$bind['available'] = '0';
					$query .= ' tg.`modified` <= :maxdate AND tg.`available` = :available';
				}
				$response = DBO::sharedInstance()->execute($query, $bind);
				if (FALSE !== $response){
					$analyzies = $response->GetAll();
					$analyzed['outtotal'] = $analyzies[0]['cnt'];
				}
			}
			if ($request['target'] === 'purchase'){
				$analyzed['total'] = '0';
				// 売上の場合は総売上金額を取得する
				$bind = array();
				$bind['maxdate'] = $maxdate;
				// XXX INDEXが問題になる場合はQUERYを2回に分割出来るのでした方がよい
				$query = 'SELECT SUM(tg.`total_price`) AS num FROM `'.$request['target'].'` tg'.$join.' WHERE tg.`created` <= :maxdate';
				$response = DBO::sharedInstance()->execute($query, $bind);
				if (FALSE !== $response){
					$analyzies = $response->GetAll();
					$analyzed['total'] = (string)((int)$analyzies[0]['num']);
				}
			}
			else if ($request['target'] === 'productitem'){
				$analyzed['total'] = $analyzed['intotal'];
				// 作品の場合は有効作品数を取得する為に非公開作品数(削除 AND リジェクト)の累計を取得する
				$bind = array();
				$bind['maxdate'] = $maxdate;
				// 削除
				$bind['available'] = '0';
				// リジェクト
				$bind['reviewed'] = '9';
				// XXX INDEXが問題になる場合はQUERYを2回に分割出来るのでした方がよい
				$query = 'SELECT COUNT(tg.`id`) AS cnt FROM `'.$request['target'].'` tg'.$join.' WHERE tg.`modified` <= :maxdate AND (tg.`available` = :available OR tg.`reviewed` = :reviewed )';
				$response = DBO::sharedInstance()->execute($query, $bind);
				if (FALSE !== $response){
					$analyzies = $response->GetAll();
					$analyzed['total'] = (string)((int)$analyzed['intotal'] - (int)$analyzies[0]['cnt']);
				}
			}
			else {
				$analyzed['total'] = (string)((int)$analyzed['intotal'] - (int)$analyzed['outtotal']);
			}
			// 追加のキーを
			$responce[] = $analyzed;
		}
		return $responce;
	}
}
?>