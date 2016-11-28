/* フレームワークが各種JSで利用している共通関数・クラス群の定義 */
var browserLanguage = function () {
	try {
		return (navigator.browserLanguage || navigator.language || navigator.userLanguage).substr(0,2)
	}
	catch(e) {
		return undefined;
	}
};

/* querystringから指定のkeyの値を取り出して返す */
var getParameter = function (argKey, argBaseURL) {
	var _URL = location.search;
	if(typeof argBaseURL != "undefined"){
		_URL = argBaseURL;
	}
	_key = argKey.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
	var _regex = new RegExp("[\\?&]" + _key + "=([^&#]*)"), _results = _regex.exec(_URL);
	return _results == null ? "" : decodeURIComponent(_results[1].replace(/\+/g," "));
};

/* querystringから全てのkeyを値を取り出して返す */
var getParameters = function (argBaseURL) {
	var _params = [];
	if (0 == Object.keys(parameters).length){
		var _request = location.search.substring(1).split('&');
		for(var i = 0; i < _request.length; i++) {
			var _keySearch = _request[i].search(/=/);
			var _key = '';
			if (_keySearch != -1) {
				_key = _request[i].slice(0, _keySearch);
			}
			if (_key != '') {
			}
			_params[_key] = getParameter(_key, argBaseURL);
		}
	}
	return _params; 
};

$(function() {
	// DatePickerを設定
	$.datetimepicker.setLocale(browserLanguage());
	if(0 < $(".datetimepicker").size()){
		$(".datetimepicker").each(function (){
			var format = "Y-m-d H:i:s";
			if ('undefined' != typeof $(this).attr("dateformat")){
				format = $(this).attr("dateformat");
			}
			$(this).datetimepicker({
				i18n:{
					ja:{
						months:['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'],
						dayOfWeek:["日", "月", "火", "水", "木", "金", "土"]
					}
				},
				format: format,
				autoclose: true,
			});
		});
	}
	if(0 < $(".datepicker").size()){
		$(".datepicker").each(function (){
			var format = "Y-m-d";
			if ('undefined' != typeof $(this).attr("dateformat")){
				format = $(this).attr("dateformat");
			}
			$(this).datetimepicker({
				i18n:{
					ja:{
						months:['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'],
						dayOfWeek:["日", "月", "火", "水", "木", "金", "土"]
					}
				},
				format: format,
				timepicker: false,
				autoclose: true,
			});
		});
	}
});
