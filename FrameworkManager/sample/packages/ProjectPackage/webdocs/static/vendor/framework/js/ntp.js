
// 時刻のズレ幅(秒数)
var offset = 0;
// 実際の送信の直前で現在時刻をしまう！(秒ズレを極力減らす為)
var sendTime = null;
// NTP時刻修正の完了した時刻を保存
var now = null;
// NTPの同期が完了した時に呼ばれるメソッドの定義用
var ntploadEnd = [];

// 正確な現在日付を返す用の関数を定義
if (!Date.nowDate) {
	Date.nowDate = function nowDate() {
		return new Date().getTime() + offset;
	};
}

/**
 * 受信用のjsont関数を定義
 * offsetを確定させるだけの処理
 */
window.jsont = function (data) {
	// 今現在の時間が通信終了時間
	var endTime = Date.nowDate();
	console.log("dateWithoutOffset: " + Date.nowDate());
	offset = (parseInt(data.st * 1000 + (endTime - sendTime) / 2, 10)) - endTime;
	console.log("offset: " + offset);
	console.log("dateWithOffset: " + Date.nowDate());
	now = Date.nowDate();
	// 最新のNTP同期日付をMETAタグしてしまっておく
	var metaE = document.createElement('meta');
	metaE.id = 'ntpsyncdate';
	metaE.content = now;
	document.head.appendChild(metaE);

	// jsont関数を削除
	if (!(delete window.jsont)) {
		window.jsont = undefined;
	}

	// NTP読み込み完了後の関数を実行(jQuery依存)
	$(function () {
		$('meta#ntpsyncdate').ready(function () {
			if (0 < ntploadEnd.length) {
				for (var midx = 0; midx < ntploadEnd.length; midx++) {
					var method = ntploadEnd[midx];
					method();
				}
			}
		});
	});
};

// NTP時刻修正処理開始
// XXX 若干aゾーンの比重を増やしている
var serverList = null;
if ("https:" == document.location.protocol) {
	serverList = [
		'https://ntp-a1.nict.go.jp/cgi-bin/jsont',
		'https://ntp-a1.nict.go.jp/cgi-bin/jsont',
		'https://ntp-a1.nict.go.jp/cgi-bin/jsont',
		'https://ntp-b1.nict.go.jp/cgi-bin/jsont',
	];
} else {
	serverList = [
		'http://ntp-a1.nict.go.jp/cgi-bin/jsont',
		'http://ntp-a1.nict.go.jp/cgi-bin/jsont',
		'http://ntp-a1.nict.go.jp/cgi-bin/jsont',
		'http://ntp-b1.nict.go.jp/cgi-bin/jsont',
	];
}
var sendTime = Date.nowDate();
var scriptE = document.createElement('script');
scriptE.src = serverList[Math.floor(Math.random() * serverList.length)] + '?' + (sendTime / 1000);
// ココから先のズレはNTPサーバとのレイテンシーに依存する！
document.head.appendChild(scriptE);
