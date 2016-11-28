// TwitterWidgetのデザイン変更
var customTwitterWidget = function (argCustomCSSRef, retryCnt){
	if (typeof argCustomCSSRef != 'undefined' && 0 < argCustomCSSRef.length) {
		var $twitter_widget = $('iframe.twitter-timeline');
		if (0 < $twitter_widget.size()){
			var $twitter_widget_contents = $twitter_widget.contents();
			if ($twitter_widget.length > 0 && $twitter_widget[0].contentWindow.document.body.innerHTML !== ""){
				$twitter_widget_contents.find('head').append('<link href="'+argCustomCSSRef+'" rel="stylesheet" type="text/css">');
				// 完了
				return;
			}
		}
		setTimeout(function(){
			if (1 > retryCnt){
				retryCnt = 0;
			}
			else if (5 < retryCnt){
				// ツイッターウィジェットはきっとそもそもなかったんだよ・・・
				// これ以上リトライしない
				return;
			}
			retryCnt++;
			// TwitterWidgetの初期化を待ってリトライ
			customTwitterWidget(argCustomCSSRe, retryCnt);
		}, 350);
	}
}
