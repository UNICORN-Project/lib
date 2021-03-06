//
// フレームワークのREST-APIのhtml出力を利用してCRUDを実現する為のJSファイルです。
// ■制約事項：
// 	id="crudcontents"にRESTAPIから受け取ったコンテンツhtmlを出力します。
// 	id="tablelist"にRESTAPIから受け取ったテーブル一覧htmlを出力します。
// 	それ以外の制約は特にありません。
// ■参考html：
// 	<div id="tablelist"></div>
// 	<div id="crudcontents"></div>

var loadingCnt = 0;
var loadingSelector = '#loading-screen';

// 外部参照許可変数
var headers;
var rules;
var records = 0;
var comment;
var mode;

function crud(baseCRUDURL, baseQuery, callback) {
	if ($(loadingSelector).size()){
		loadingCnt++;
		$(loadingSelector).fadeIn();
	}
	var crudlinkbaseURL = "";
	var tablelinkbaseURL = location.protocol + "//" + location.hostname + location.pathname + "?" + baseQuery + "&mode=list";
	// テーブル一覧を取得
	$.ajax({
		type : "GET",
		url : baseCRUDURL + "/index.html",
		dataType : "html",
		cache : false,
	}).done(function(html) {
		$("#tablelist").html(html).ready(function(){
			$(".tablelink").each(function() {
				$(this).attr("href", tablelinkbaseURL+"&table="+$(this).find(".table-name").text()+"&limit=10&offset=0");
			}).ready(function(){
				if(typeof callback != "undefined"){
					callback("tablelist");
				}
			});
		});
	}).always(function(){
		if ($(loadingSelector).size()){
			loadingCnt--;
			if (0 >= loadingCnt){
				loadingCnt = 0;
				$(loadingSelector).fadeOut();
			}
		}
	});
	mode = getParameterByName("mode");
	var table = getParameterByName("table");
	var limit = getParameterByName("limit");
	var offset = getParameterByName("offset");
	var like = getParameterByName("LIKE");
	var order = getParameterByName("ORDER");
	if ("" != table) {
		if ($(loadingSelector).size()){
			loadingCnt++;
			$(loadingSelector).fadeIn();
		}
		// HEADリクエスト
		$.ajax({
			type : "HEAD",
			url : baseCRUDURL + "/" + table + ".html?LIKE="+like,
			cache : false,
			error : function (data){
				$("#crudcontents h2").text("Error :" + data.status + " " + data.statusText);
			},
			success : function (data, status, xhr) {
				headers = $.parseJSON(xhr.getResponseHeader("Head"));
				rules = $.parseJSON(xhr.getResponseHeader("Rules"));
				records = xhr.getResponseHeader("Records");
				comment = $.parseJSON(xhr.getResponseHeader("Comment"));
				crudlinkbaseURL = location.protocol + "//" + location.hostname + location.pathname + "?" + baseQuery + "&mode=detail";
				if ("list" == mode) {
					if ($(loadingSelector).size()){
						loadingCnt++;
						$(loadingSelector).fadeIn();
					}
					// 指定テーブルの一覧情報を取得
					$.ajax({
						type : "GET",
						url : baseCRUDURL + "/" + table + ".html?LIMIT="+limit+"&OFFSET="+offset+"&total="+records+"&ORDER="+encodeURIComponent(order)+"&LIKE="+encodeURIComponent(like),
						dataType : "html",
						cache : false,
					}).done(function(html) {
						// 一覧を描画
						$("#crudcontents").html(html).ready(function() {
							// DOM操作が終わったらhrefを書き換える
							$(".crudlink").each(function() {
								var link = $(this).attr("href");
								$(this).removeAttr("target");
								if(0 <= $(this).attr("id").indexOf("crud_order_"+table)){
									// 並び替え用リンク
									$(this).attr("href", tablelinkbaseURL+"&table="+table+"&limit="+limit+"&offset=0&ORDER="+encodeURIComponent(getParameterByName("ORDER", link))+"&LIKE="+encodeURIComponent(like));
								}
								else {
									// 詳細画面用リンク
									$(this).attr("href", crudlinkbaseURL+"&table="+table+"&limit="+limit+"&offset="+offset+"&ORDER="+encodeURIComponent(order)+"&LIKE="+encodeURIComponent(like)+"&url="+link);
								}
							}).ready(function() {
								// ページングリンクの修正
								$(".list-paginglink").each(function(){
									$(this).find("a").attr("href", tablelinkbaseURL+"&table="+table+"&limit="+limit+"&offset="+ getParameterByName("OFFSET", $(this).find("a").attr("href")) +"&ORDER="+encodeURIComponent(order)+"&LIKE="+encodeURIComponent(like));
								}).ready(function() {
									// 新規レコード作成リンク
									$("#crudcontents").append("<div class=\"create-new-record-link\"><a href=\"" + location.protocol + "//" + location.hostname + location.pathname + "?" + baseQuery + "&mode=new&table="+table+"&limit="+limit+"&offset="+offset+"&ORDER="+encodeURIComponent(order)+"&LIKE="+encodeURIComponent(like) + "\">create new record</a></div>").ready(function() {
										if(typeof callback != "undefined"){
											callback("list");
										}
									});
								});
							});
							$(this).find("h2").text($(this).find("h2").text() + ((0 < comment.length)? " \"" + comment + "\"" : "") + "(" + records + ")");
							$(".crudkey a").each(function() {
								$(this).text($(this).text() + " \"" + headers[$(this).text()].comment + "\"");
							});
							$(".tablelist-link").remove();
							$(".submit-button").each(function() {
								$(this).addClass("buttonarea");
							});
							$("#crud-form-search").append("<input type=\"hidden\" name=\"mode\" value=\"list\"/>");
							$("#crud-form-search").append("<input type=\"hidden\" name=\"table\" value=\"" + table +"\"/>");
							$("#crud-form-search").append("<input type=\"hidden\" name=\"limit\" value=\"" + limit +"\"/>");
							$("#crud-form-search").append("<input type=\"hidden\" name=\"offset\" value=\"0\"/>");
						});
					}).always(function(){
						if ($(loadingSelector).size()){
							loadingCnt--;
							if (0 >= loadingCnt){
								loadingCnt = 0;
								$(loadingSelector).fadeOut();
							}
						}
					});
				}
				if ("new" == mode) {
					var newRecordHtmlBase = "<h2>"+table + ((0 < comment.length)? " \"" + comment + "\"" : "") +"</h2>";
					newRecordHtmlBase += "<form id=\"crud-form-post\" class=\"crud-form\" method=\"POST\" action=\"" + baseCRUDURL + "/" + table + ".html\"><table class=\"detail\">";
					Object.keys(headers).forEach(function (key) {
						var fiendObj = headers[key];
						var required = ":必須";
						if(fiendObj["pkey"] || fiendObj["null"]){
							required = "";
						}
						newRecordHtmlBase += "<tr><th class=\"crudkey\">"+key+" \""+fiendObj.comment+required+"\"</th></tr><tr><td>";
						if (0 <= fiendObj["type"].indexOf("text")){
							newRecordHtmlBase += "<textarea class=\"form-input\" name=\""+key+"\">"+((typeof fiendObj["default"] != "undefined" && "NULL" != fiendObj["default"])? fiendObj["default"] : "")+"</textarea>";
						}
						else if (0 <= fiendObj["type"].indexOf("blob")){
							newRecordHtmlBase += "<input class=\"form-input\" type=\"file\" name=\""+key+"\"/>";
						}
						else {
							newRecordHtmlBase += "<input class=\"form-input\" type=\"text\" name=\""+key+"\" value=\""+((typeof fiendObj["default"] != "undefined" && "NULL" != fiendObj["default"])? fiendObj["default"] : "")+"\"/>";
						}
						newRecordHtmlBase += "</td></td>";
					});
					newRecordHtmlBase += "</table><div class=\"submit-button buttonarea\"><input type=\"submit\" value=\"POST\"/></div><input class=\"form-input\" type=\"hidden\" name=\"_method_\" value=\"POST\"/></form>";
					newRecordHtmlBase += "<div class=\"list-link\"><a href=\""+tablelinkbaseURL+"&table="+table+"&limit="+limit+"&offset="+getParameterByName("offset")+"&ORDER="+encodeURIComponent(order)+"&LIKE="+encodeURIComponent(like)+"\">"+table+" list</a></div>";
					// 新規作成画面を描画
					$("#crudcontents").html(newRecordHtmlBase).ready(function() {
						Object.keys(headers).forEach(function (key) {
							var fiendObj = headers[key];
							if(typeof fiendObj["calender"] != "undefined" && true == fiendObj["calender"]){
								// 日付をPickerで表示してあげる
								if (640 >= $(window).width()) {
									// モバイルの場合
								}
								else {
									// PCの場合のみ
									$("input[name='"+key+"']").datetimepicker({format: "Y-m-d H:i:s"});
								}
							}
						});
						// formのvalidateを設定
						$("#crud-form-post").validate(rules);
						// formのsubmitを設定
						$(".crud-form").submit(function(event) {
							crudSubmit(this, event, callback);
						});
						if(typeof callback != "undefined"){
							callback("new");
						}
					});
				}
				if ("detail" == mode) {
					if ($(loadingSelector).size()){
						loadingCnt++;
						$(loadingSelector).fadeIn();
					}
					var url = getParameterByName("url");
					// 詳細表示
					// 指定テーブルの指定のレコード情報を取得
					$.ajax({
						type : "GET",
						url : url,
						dataType : "html",
						cache : false,
					}).done(function(html) {
						// 詳細を描画
						$("#crudcontents").html(html).ready(function() {
							$(this).find("h2").text($(this).find("h2").text() + ((0 < comment.length)? " \"" + comment + "\"" : ""));
							// formのvalidateを設定
							$("#crud-form-put").validate(rules);
							// formのsubmitを設定
							$(".crud-form").attr("action", url);
							$(".crud-form").submit(function(event) {
								crudSubmit(this, event, callback);
							});
							$(".list-link a").attr("href", tablelinkbaseURL+"&table="+table+"&limit="+limit+"&offset="+getParameterByName("offset")+"&ORDER="+encodeURIComponent(order)+"&LIKE="+encodeURIComponent(like)).ready(function() {
								if(typeof callback != "undefined"){
									callback("ditail");
								}
							});
							$(".crudkey").each(function() {
								var required = ":必須";
								if(headers[$(this).text()]["pkey"] || headers[$(this).text()]["null"]){
									required = "";
								}
								if(typeof headers[$(this).text()]["calender"] != "undefined" && true == headers[$(this).text()]["calender"]){
									// 日付をPickerで表示してあげる
									if (640 >= $(window).width()) {
										// モバイルの場合
									}
									else {
										// PCの場合
										$("input[name='"+$(this).text()+"']").datetimepicker({format: "Y-m-d H:i:s"});
									}
								}
								$(this).text($(this).text() + " \"" + headers[$(this).text()].comment + required + "\"");
							});
							$(".submit-button").each(function() {
								$(this).addClass("buttonarea");
							});
						});
					}).always(function(){
						if ($(loadingSelector).size()){
							loadingCnt--;
							if (0 >= loadingCnt){
								loadingCnt = 0;
								$(loadingSelector).fadeOut();
							}
						}
					});
				}
			}
		});
	}
}

function crudSubmit(argFormInstance, argEvent, callback){
	if ($(loadingSelector).size()){
		loadingCnt++;
		$(loadingSelector).fadeIn();
	}
	// submitを先ずキャンセルします。
	argEvent.preventDefault();
	if(true == $(argFormInstance).valid()){
		// 未入力のformは送らない
		var data = new FormData();
//		$.each(context.prototype.fileData, function(i, obj) { data.append(i, obj.value.files[0]); });
		$(argFormInstance).find(".form-input").each(function(){
			if(typeof $(this).attr("name") != "undefined" && "" != $(this).val()){
				var name = $(this).attr("name");
				if ("file" == $(this).attr("type")){
					var files = this.files;
					$.each(files, function(i, file){
						data.append(name, file);
					});
				}
				else {
//					data.append(name, encodeURIComponent($(this).val()));
					data.append(name, $(this).val());
				}
				console.log(name + "&" + $(this).val());
			}
		}).ready(function(){
			console.log(data);
			$.ajax({
				url: $(argFormInstance).attr("action"),
				type: 'POST',
				//type: $(argFormInstance).find("input[name='_method_']").val(),
				processData: false,
				contentType: false,
				data: data,
				dataType: "html",
				// 送信前
				beforeSend: function(xhr, settings) {
					// ボタンを無効化し、二重送信を防止
					$(argFormInstance).find("input[type='submit']").attr("disabled", false);
				},
				success: function(html) {
					// 成功した場合は元の一覧に自動遷移する
					// 詳細を描画
					if(typeof callback != "undefined"){
						callback("submit");
					}
				},
				// 通信失敗時の処理
				error: function(xhr, textStatus, error) {
					alert("ERROR:" + textStatus);
				},
				// 応答後
				complete: function(xhr, textStatus) {
					// ボタンを有効化し、再送信を許可
					$(argFormInstance).find("input[type='submit']").attr("disabled", false);
					if ($(loadingSelector).size()){
						loadingCnt--;
						if (0 >= loadingCnt){
							loadingCnt = 0;
							$(loadingSelector).fadeOut();
						}
					}
				},
			});
		});
	}
}

// querystringから指定のkeyの値を返す
var getParameterByName = function (argKey, argBaseURL) {
	var URL = location.search;
	if(typeof argBaseURL != "undefined"){
		URL = argBaseURL;
	}
	key = argKey.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
	var regex = new RegExp("[\\?&]" + key + "=([^&#]*)"), results = regex.exec(URL);
	return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g," "));
}
