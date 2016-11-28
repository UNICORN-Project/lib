var modelLoadEnd = {};
var modelRules = {};
var errorPlacement = function(error, element){
	if (typeof element.next() != 'undefined' && 0 < element.next().size() && element.next().hasClass('inputclear')){
		// inputの直後のエレメントがclearボタンの時の処理
		element.parent().addClass('haserror');
		error.appendTo(element.parent());
		return;
	}
	// 通常のエラー表示処理
	element.addClass('haserror');
	error.insertAfter(element);
};

var initValidate = function(argFormName, argModelName) {
	$(function(){
		if (typeof modelRules[argModelName] != 'undefined') {
			if (typeof modelRules[argModelName].rules != 'undefined'){
				setValidate(argFormName, argModelName);
			}
			else {
				if (typeof modelLoadEnd[argModelName] == 'undefined'){
					// 初期化
					modelLoadEnd[argModelName] = [];
				}
				modelLoadEnd[argModelName].push(argFormName);
			}
		}
		else {
			modelRules[argModelName] = {};
			// JSバリデートの初期化
			apiclient.head(argModelName, function (data, xhr) {
				headers = $.parseJSON(xhr.getResponseHeader("Head"));
				rules = $.parseJSON(xhr.getResponseHeader("Rules"));
				records = xhr.getResponseHeader("Records");
				comment = $.parseJSON(xhr.getResponseHeader("Comment"));
				// デフォルトメッセージの変更
				if (typeof rules.default_messages != 'undefiled' && 0 < Object.keys(rules.default_messages).length){
					$.extend($.validator.messages, rules.default_messages);
				}
				// カスタムメソッドの追加
				if (typeof rules.custom_methods != 'undefiled' && 0 < Object.keys(rules.custom_methods).length){
					var _methodKeys = Object.keys(rules.custom_methods);
					for (var mkidx=0; mkidx < _methodKeys.length; mkidx++){
						var _methodName = _methodKeys[mkidx];
						var _methodPreg = rules.custom_methods[_methodName];
						_methodPreg = _methodPreg.replace('\\A',"^");
						_methodPreg = _methodPreg.replace('+\\z',"$");
						var _method;
						eval('_method = function (value, element){ return this.optional(element) || ' + _methodPreg + '.test(value); };');
						$.validator.addMethod(_methodName, _method);
					}
				}
				// 固定長をチェックするメソッドは標準では無いので追加しておく
				$.validator.addMethod('length', function(value, element, maxlength) {
					return this.optional(element) || ((maxlength == this.getLength(value, element))? true : false);
				});
				// title属性からエラー表示はしない
				rules.ignoreTitle = true;
				//エラーメッセージ出力箇所調整
				rules.errorPlacement = errorPlacement;
				// ベースとなるルールの永続化
				modelRules[argModelName] = rules;
				// validateの設定待ちのform全てに適用
				setValidate(argFormName, argModelName);
				if (typeof modelLoadEnd[argModelName] != 'undefined' && 0 < modelLoadEnd[argModelName].length){
					for (var mlidx=0; mlidx < modelLoadEnd[argModelName].length; mlidx++){
						var _callbackForm = modelLoadEnd[argModelName][mlidx];
						setValidate(_callbackForm, argModelName);
					}
				}
			}, function (error) {
			});
		}
	});
}

var setValidate = function(argFormName, argModelName) {
	var _rules = modelRules[argModelName];
	// ルールの拡張
	var _ruleKeys = Object.keys(rules.rules);
	for (var kidx=0; kidx < _ruleKeys.length; kidx++){
		var _ruleKey = _ruleKeys[kidx];
		var _targetInputSelector = "form[name*='"+argFormName+"'] input[name='"+_ruleKey+"']";
		var _targetInput = $(_targetInputSelector);
		var _targetInputName = _targetInput.attr('name');
		if (!(typeof _targetInput != 'undefined' && 0 < _targetInput.size())){
			continue;
		}
		// 必須指定されているかどうか
		if (_targetInput.hasClass('required')) {
			// ルールを画面のカスタムルールに置き換える
			rules.rules[_ruleKey].required = true;
		}
		// unique指定さているかどうか
		if (_targetInput.hasClass('unique')) {
			// 重複チェックをサーバーに問い合わせするカスタム
			var _data = { validate : 'unique' };
			eval('_data[_ruleKey] = function (){ return $("'+_targetInputSelector+'").val(); };');
			$.validator.addMethod('unique', function( value, element, param, method ) {
				if ( this.optional( element ) ) {
					return "dependency-mismatch";
				}

				method = "unique";

				var previous = this.previousValue( element, method ),
					validator, data, optionDataString;

				if ( !this.settings.messages[ element.name ] ) {
					this.settings.messages[ element.name ] = {};
				}
				previous.originalMessage = previous.originalMessage || this.settings.messages[ element.name ][ method ];
				this.settings.messages[ element.name ][ method ] = previous.message;

				param = typeof param === "string" && { url: param } || param;
				optionDataString = $.param( $.extend( { data: value }, param.data ) );
				if ( previous.old === optionDataString ) {
					return previous.valid;
				}

				previous.old = optionDataString;
				validator = this;
				this.startRequest( element );
				data = {};
				data[ element.name ] = value;
				var _element = $(element);
				var _loaderClass = 'loader loader-5';
				var _loader = $('#loading-screen .loader');
				if (0 < _loader.size()){
					_loaderClass = _loader.attr('class');
				}
				var _loading = $('<label id="'+element.name+'-loading" class="loading '+_loaderClass+'"></label>');
				if (typeof _element.next() != 'undefined' && 0 < _element.next().size() && _element.next().hasClass('inputclear')){
					// inputの直後のエレメントがclearボタンの時の処理
					_loading.appendTo(_element.parent());
				}
				else {
					// 通常のローディング表示処理
					_loading.insertAfter(_element);
				}
				$.ajax( $.extend( true, {
					mode: "abort",
					port: "validate" + element.name,
					dataType: "json",
					data: data,
					context: validator.currentForm,
					complete: function( xhr, status ) {
						var valid,errors, message, submitted;
						if (200 == xhr.status){
							valid = true;
						}
						validator.settings.messages[ element.name ][ method ] = previous.originalMessage;
						if ( valid ) {
							submitted = validator.formSubmitted;
							validator.resetInternals();
							validator.toHide = validator.errorsFor( element );
							validator.formSubmitted = submitted;
							validator.successList.push( element );
							validator.invalid[ element.name ] = false;
							validator.showErrors();
						} else {
							errors = {};
							message = validator.defaultMessage( element, { method: method, parameters: value } );
							errors[ element.name ] = previous.message = message;
							validator.invalid[ element.name ] = true;
							validator.showErrors( errors );
						}
						previous.valid = valid;
						validator.stopRequest( element, valid );
					},
					error: function( xhr, status ) {
						var valid,errors, message, submitted;
						if (409 != xhr.status){
							// 通信エラー
						}
					},
				}, param ) )
				.always(function (){
					$('#'+element.name+'-loading').remove();
				});
				return "pending";
			});
			rules.rules[_ruleKey].unique = {
				type:'head',
				url:apiclient.baseUrl + argModelName + "." + apiclient.dataType,
				dataType: "text",
				data: _data,
			};
			// メッセージの追加
			if (typeof rules.messages == 'undefined'){
				rules.messages = {};
			}
			if (typeof rules.messages[_targetInputName] == 'undefined'){
				rules.messages[_targetInputName] = {};
			}
			rules.messages[_targetInputName].unique = '※この'+_targetInput.attr('title')+'は使用出来ません';
			// キーを打つ度にAjaxは重いのでfalseにする
			rules.onkeyup = false;
		}
		// パスワードポリシーチェック指定されているかどうか
		if (_targetInput.hasClass('passwordPoricy') && typeof _rules.custom_methods != 'undefiled' && 0 < Object.keys(_rules.custom_methods).length && typeof _rules.custom_methods.password != 'undefiled'){
			// ルールを画面のカスタムルールに置き換える
			rules.rules[_ruleKey].password = true;
			// ポリシー内に文字数があるので文字数チェック系は全て外す
			rules.rules[_ruleKey].length = false;
			rules.rules[_ruleKey].minlength = false;
			rules.rules[_ruleKey].maxlength = false;
			rules.rules[_ruleKey].ranglength = false;
		}
		// 確認項目指定さていいるかどうか
		if (_targetInput.hasClass('confirm')){
			var _targetConfirmSelector = "form[name*='"+argFormName+"'] input[name='"+_ruleKey+"-confirm']";
			var _targetConfirm = $(_targetConfirmSelector);
			if (1 > _targetConfirm.size()){
				_targetConfirmSelector = "form[name*='"+argFormName+"'] input[name='"+_ruleKey+"_confirm']";
				_targetConfirm = $(_targetConfirmSelector);
			}
			if (1 > _targetConfirm.size()){
				_targetConfirmSelector = "form[name*='"+argFormName+"'] input[name='"+_ruleKey+"Confirm']";
				_targetConfirm = $(_targetConfirmSelector);
			}
			if (0 < _targetConfirm.size()){
				// 確認項目用のRuleを生成
				var _targetConfirmName = _targetConfirm.attr('name');
				var _confirmMethodName = _ruleKey+'Confirm';
				if (typeof rules.rules[_targetConfirmName] == 'undefined'){
					rules.rules[_targetConfirmName] = {};
				}
				rules.rules[_targetConfirmName].equalTo = _targetInputSelector;
				// メッセージの追加
				if (typeof rules.messages == 'undefined'){
					rules.messages = {};
				}
				if (typeof rules.messages[_targetConfirmName] == 'undefined'){
					rules.messages[_targetConfirmName] = {};
				}
				rules.messages[_targetConfirmName].equalTo = '※'+_targetInput.attr('title')+'と一致していません';
			}
		}
	}
	// ルールの適用
	$("form[name*='"+argFormName+"']").validate(_rules);
	// サブミットをフックしたvalidateの発火
	$("form[name*='"+argFormName+"'] input[name*='-form-submit']").click(function (event) {
		if (true == $("form[name*='"+argFormName+"']").valid()) {
			// validate成功なのでサブミット
			$("form[name*='"+argFormName+"']").submit();
		}
		// validate失敗
		return false;
	});
};

var autoInitValidate = function (){
	$("form[models]").each(function(){
		var _attrName = $(this).attr('name');
		var _attrModel = $(this).attr('models');
		var _models = _attrModel.split(/,/);
		for (var _modelIdx=0; _modelIdx < _models.length; _modelIdx++){
			var _model = _models[_modelIdx];
			initValidate(_attrName, _model)
		}
	});
}
ntploadEnd.push(autoInitValidate);
