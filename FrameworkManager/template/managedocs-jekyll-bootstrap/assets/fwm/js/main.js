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

// 変数の初期化
var loadingCnt = 0;
var crudEnabled = false;
var permission = 9;
var projectName = getParameterByName("projectname");
var targetProject = getParameterByName("target_project");
var dispName = targetProject;
if (null != projectName && 0 < projectName.length){
	dispName = projectName;
}

// メイン処理
$(document).ready(function() {
	// 描画の初期化
	if ($('#loading-screen').size()){
		$('#loading-screen').hide();
		loadingCnt++;
		$('#loading-screen').fadeIn();
	}

	$("[permission]").hide();
	// ログインユーザーの名前を取得
	$.ajax({
		url: "xresouce/me/fwmuser.json",
		dataType: "json",
		cache: false,
	}).done(function(json) {
		if(0 == window.location.href.split('?')[0].split('/')[3].length || 'index.html' == window.location.href.split('?')[0].split('/')[3]){
			permission = parseInt(json[0].permission);
			// デフォルトターゲットプロジェクトにリダイレクトするかどうかの判定
			if (1 < permission && 0 < json[0].default_target_project.length){
				var target = $.parseJSON(json[0].default_target_project);
				if("undefined" !== typeof target.target_project && "undefined" !== typeof target.project_name && target.target_project != targetProject){
					window.location.href = "/?target_project=" + target.target_project + "&projectname=" + target.project_name;
					return true;
				}
			}
		}
		$("#username").text(json[0].name);
		// パーミッションの操作
		permission = parseInt(json[0].permission);
		$("[permission]").each(function(){
			$(this).hide();
			// ログインユーザーのパーミッションが小さければ見れる
			if (permission <= parseInt($(this).attr("permission"))){
				$(this).show();
			}
		});
	}).fail(function(xhr, status, error) {
		console.log(error);
		console.log(status);
		if (405 == xhr.status) {
			// refresh_token切れなので再読み込み
			location.reload();
		}
		else {
//			if (null !== unauthrized && typeof unauthrized != 'undefined') {
//				unauthrized();
//			}
//			return;
		}
	}).always(function(){
		if ($('#loading-screen').size()){
			loadingCnt--;
			if (0 >= loadingCnt){
				loadingCnt = 0;
				$('#loading-screen').fadeOut();
			}
		}
	});
	if(true != crudEnabled){
		// プロジェクトの一覧を取得
		$.ajax({
			url: "api/project.json",
			dataType: "json",
			cache: false,
		}).done(function(json) {
			var projectdombase = $("#projectlist .project").parent().html();
			var projectlist = "";
			for(var idx=0; idx < json.length; idx++){
				var dispname = json[idx].name;
				if (0 < json[idx].dispname.length){
					dispname = json[idx].dispname;
				}
				projectlist += projectdombase.replace("project name", dispname).replace("_project_", json[idx].name).replace("_projectname_", dispname).replace("class=\"project ", "class=\"project " + json[idx].name + " ");
			}
			$("#projectlist").html(projectlist);
			// ページのメニューセレクティング
			var dom = $("#menu-projectlist ul.nav li a[href^='./" + window.location.href.split('/').pop().split('&').shift() + "']").parent("li");
			dom.attr("class", dom.attr("class") + " selected");
		}).fail(function(xhr, status, error) {
			console.log(error);
			console.log(status);
			if (405 == xhr.status) {
				// refresh_token切れなので再読み込み
				location.reload();
			}
			else {
//				if (null !== unauthrized && typeof unauthrized != 'undefined') {
//					unauthrized();
//				}
//				return;
			}
		}).always(function(){
			if ($('#loading-screen').size()){
				loadingCnt--;
				if (0 >= loadingCnt){
					loadingCnt = 0;
					$('#loading-screen').fadeOut();
				}
			}
		});
	}
	if(0 < targetProject.length){
		// プロジェクトのメニュー一覧を取得
		$.ajax({
			url: "api/menu.json?target_project="+targetProject,
			dataType: "json",
			cache: false,
		}).done(function(json) {
			if (0 < json.length){
				$("#menu-projectmenu").attr("style", "");
				menuList = "<div id=\"menulist\"><b>" + dispName + " menu</b><ul>";
				for(var idx=0; idx < json.length; idx++){
					menuList += "<li><a href=\"./fwm-" + targetProject + "-" + json[idx].path.replace('.','_').replace('/','=') + ".html?target_project=" + targetProject + "&projectname=" + dispName + "\">" + json[idx].name + "</a></li>"
				}
				menuList += "</ul></div>";
				$("#menu-projectmenu").html(menuList);
			}
		}).always(function(){
			if ($('#loading-screen').size()){
				loadingCnt--;
				if (0 >= loadingCnt){
					loadingCnt = 0;
					$('#loading-screen').fadeOut();
				}
			}
		});
	}
});
