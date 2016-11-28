/* api通信ライブラリ */
var apiclient = null;
// Authの同期が完了した時に呼ばれるメソッドの定義用
var authEnd = [];
var loadingCnt = 0;
var clientFactory = {
	create: function (baseUrl, authfunc, screenLoadingSelector) {
		if ($('.ajax-error').size()) {
			$('.ajax-error').hide();
		}
		var loadingSelector = '#loading-screen';
		if (typeof screenLoadingSelector != 'undefined' && 0 < screenLoadingSelector.length) {
			loadingSelector = screenLoadingSelector;
		}
		if ($(loadingSelector).size()) {
			$(loadingSelector).hide();
		}
		var auth = null;
		if (typeof authfunc == 'function') {
			auth = authfunc;
		}
		var client = {
			baseUrl: baseUrl,
			loading: loadingSelector,
			auth: auth,
			dataType: "json",
			deep: 0,
			/**
			 * @param path
			 * @param done 成功処理の関数(data, count)
			 */
			getAll: function (path, done) {
				var url = this.baseUrl + path + "." + this.dataType;
				this.get(url, {}, done);
			},
			/**
			 * @param path
			 * @param id 一意となるID
			 * @param done 成功処理の関数(data, count)
			 */
			getOne: function (path, id, done) {
				var url = this.baseUrl + path + "." + this.dataType;
				var data = {
					"id": id
				};
				this.get(url, data, done);
			},
			getByOwnerId: function (path, ownerId, done) {
				var url = this.baseUrl + path + "." + this.dataType;
				var data = {
					"owner_id": ownerId
				};
				console.log(url);
				console.log(data);
				console.log(done);
				this.get(url, data, done);
			},
			search: function (path, done, cond) {
				var url = this.baseUrl + path + "." + this.dataType;
				this.get(url, cond, done);
			},
			/**
			 * @param path
			 * @param no ページ番号
			 * @param size 1ページあたりの件数
			 * @param done 成功処理の関数(data, count, page)
			 * @param cond 検索条件(オプション)
			 * @param sort 並び順(オプション)
			 */
			searchPage: function (path, no, size, done, cond, sort) {
				var offset = (no - 1) * size;
				this.searchLimit(path, offset, size, function (data, count) {
					var page = Math.floor(count / size);
					if (count % size !== 0) {
						page++;
					}
					done(data, count, page);
				}, cond, sort);
			},
			/**
			 * @param path
			 * @param offset
			 * @param limit
			 * @param done 成功処理の関数(data, count)
			 * @param cond 検索条件(オプション)
			 * @param sort 並び順(オプション)
			 */
			searchLimit: function (path, offset, limit, done, cond, sort) {
				var url = this.baseUrl + path + "." + this.dataType;
				var data = {
					'OFFSET': offset,
					'LIMIT': limit
				};
				if (cond) {
					// dataにcondをマージ
					$.extend(data, cond);
				}
				if (sort) {
					data['ORDER'] = sort.join(",");
				}
				this.get(url, data, done);
			},
			/**
			 * @param url
			 * @param data
			 * @param done
			 */
			get: function (url, data, done, fail, always, useLoading) {
				if (false !== useLoading && $(loadingSelector).size() && 0 === loadingCnt) {
					$(loadingSelector).fadeIn();
					loadingCnt++;
				}
				$.ajax({
					type: "GET",
					url: url,
					data: data,
					cache: true,
					dataType: this.dataType,
				}).done(function (data, status, xhr) {
					var count = xhr.getResponseHeader("Records");
					done(data, count);
				}).fail(function (xhr, status, error) {
					console.log(error);
					console.log(xhr.status);
					if (404 == xhr.status) {
						// データが無い表示
						location.href = './404.html';
					}
					if (fail) {
						fail(xhr, status, error);
					} else {
						var isErrorArea = false;
						if ($('.ajax-error').size()) {
							isErrorArea = true;
						}
						if (isErrorArea && 400 == xhr.status) {
							$('.ajax-error').fadeIn();
							$('.ajax-error .alert_prefix').show();
							$('.ajax-error .alert_suffix').show();
							$('.ajax-error .alert_title').text('400:通信エラー');
							$('.ajax-error .alert_message').html('通信エラーが発生しました。アプリケーションに問題が起きているか、あるいはお使いの環境に問題があります。<br/>お使いのブラウザを一度起動し直すか、');
						} else if (isErrorArea && 405 == xhr.status) {
							$('.ajax-error').fadeIn();
							$('.ajax-error .alert_prefix').show();
							$('.ajax-error .alert_suffix').show();
							$('.ajax-error .alert_title').text('405:許可されていない操作の実行エラー');
							$('.ajax-error .alert_message').html('許可されていない操作が実行されました。アプリケーションに問題が起きているか、あるいはお使いの環境に問題があります。<br/>お使いのブラウザを一度起動し直すか、');
						} else if (isErrorArea && 500 == xhr.status) {
							$('.ajax-error').fadeIn();
							$('.ajax-error .alert_prefix').show();
							$('.ajax-error .alert_suffix').show();
							$('.ajax-error .alert_title').text('500:予期しないエラー');
							$('.ajax-error .alert_message').html('予期しないエラーが発生しました。アプリケーションにに問題が起きているか、あるいはお使いの環境に問題があります。<br/>お使いのブラウザを一度起動し直すか、');
						} else if (isErrorArea && 503 == xhr.status) {
							$('.ajax-error').fadeIn();
							$('.ajax-error .alert_prefix').show();
							$('.ajax-error .alert_suffix').show();
							$('.ajax-error .alert_title').text('503:利用制限エラー');
							$('.ajax-error .alert_message').html('アプリケーションが現在メンテナンス中か、あるいはお使いの環境に問題があります。<br/>お使いのブラウザを一度起動し直すか、');
						} else {
							if (401 == xhr.status) {
								if (null !== auth && typeof auth == 'function') {
									auth();
									return false;
								}
							}
							alert("エラー：" + error);
							// XXX エラー処理をなんかかっこよくする場合はココに到達しないように工夫する
						}
					}
				}).always(function () {
					loadingCnt--;
					if ($(loadingSelector).size() && 0 >= loadingCnt) {
						$(loadingSelector).fadeOut();
						loadingCnt = 0;
					}
					if (null !== always && typeof always != 'undefined') {
						always();
					}
				});
			},
			/**
			 * @param done
			 * @param unauthrized
			 */
			getAuth: function (done, unauthrized, always) {
				var url = this.baseUrl + "me/user" + "." + this.dataType;
				$.ajax({
					type: "GET",
					url: url,
					data: null,
					cache: true,
					dataType: this.dataType,
				}).done(function (data, status, xhr) {
					var count = xhr.getResponseHeader("Records");
					done(data, count);
				}).fail(function (xhr, status, error) {
					if (401 == xhr.status) {
						if (null !== unauthrized && typeof unauthrized != 'undefined') {
							unauthrized();
						}
						return;
					} else {
						//alert("エラー：" + error);
					}
				}).always(function () {
					if (null !== always && typeof always != 'undefined') {
						always();
					}
				});
			},
			/**
			 * @param done
			 * @param unauthrized
			 */
			entryUser: function (formData, done) {
				var url = this.baseUrl + "user" + "." + this.dataType;
				var authURL = this.baseUrl;
				if ($(loadingSelector).size() && 0 === loadingCnt) {
					$(loadingSelector).fadeIn();
					loadingCnt++;
				}
				$.ajax({
					type: "POST",
					url: url,
					data: formData,
					processData: false,
					contentType: false,
					cache: false,
					dataType: this.dataType,
				}).done(function (data, status, xhr) {
					done(data);
				}).fail(function (xhr, status, error) {
					alert("エラー：" + error);
				}).always(function () {
					loadingCnt--;
					if ($(loadingSelector).size() && 0 >= loadingCnt) {
						$(loadingSelector).fadeOut();
						loadingCnt = 0;
					}
				});
			},
			/**
			 * @param path
			 * @param formData
			 * @param done
			 */
			upload: function (path, formData, done) {
				var url = this.baseUrl + path + "." + this.dataType;
				if ($(loadingSelector).size() && 0 === loadingCnt) {
					$(loadingSelector).fadeIn();
					loadingCnt++;
				}
				$.ajax({
					type: "POST",
					url: url,
					data: formData,
					processData: false,
					contentType: false,
					cache: true,
					dataType: this.dataType,
				}).done(function (data, status, xhr) {
					done(data);
				}).fail(function (xhr, status, error) {
					console.log(error);
					console.log(xhr.status);
					alert("エラー：" + error);
				}).always(function () {
					loadingCnt--;
					if ($(loadingSelector).size() && 0 >= loadingCnt) {
						$(loadingSelector).fadeOut();
						loadingCnt = 0;
					}
				});
			},
			/**
			 * @param url
			 * @param data
			 * @param done
			 */
			post: function (path, data, done, fail) {
				var url = this.baseUrl + path + "." + this.dataType + "?_deep_=" + this.deep;
				console.log(url);
				if ($(loadingSelector).size() && 0 === loadingCnt) {
					$(loadingSelector).fadeIn();
					loadingCnt++;
				}
				$.ajax({
					type: "POST",
					url: url,
					data: data,
					cache: true,
					dataType: this.dataType,
				}).done(function (data, status, xhr) {
					done(data);
				}).fail(function (xhr, status, error) {
					if (!fail) {
						if (xhr.status === 401) {
							if (null !== auth && typeof auth == 'function') {
								auth();
							}
						}
						var isErrorArea = false;
						if ($('.ajax-error').size()) {
							isErrorArea = true;
						}
						if (isErrorArea && 400 == xhr.status) {
							$('.ajax-error').show();
							$('.ajax-error .alert_prefix').show();
							$('.ajax-error .alert_suffix').show();
							$('.ajax-error .alert_title').text('400:通信エラー');
							$('.ajax-error .alert_message').html('通信エラーが発生しました。アプリケーションに問題が起きているか、あるいはお使いの環境に問題があります。<br/>お使いのブラウザを一度起動し直すか、');
						} else if (isErrorArea && 405 == xhr.status) {
							$('.ajax-error').show();
							$('.ajax-error .alert_prefix').show();
							$('.ajax-error .alert_suffix').show();
							$('.ajax-error .alert_title').text('405:許可されていない操作の実行エラー');
							$('.ajax-error .alert_message').html('許可されていない操作が実行されました。アプリケーションに問題が起きているか、あるいはお使いの環境に問題があります。<br/>お使いのブラウザを一度起動し直すか、');
						} else if (isErrorArea && 500 == xhr.status) {
							$('.ajax-error').show();
							$('.ajax-error .alert_prefix').show();
							$('.ajax-error .alert_suffix').show();
							$('.ajax-error .alert_title').text('500:予期しないエラー');
							$('.ajax-error .alert_message').html('予期しないエラーが発生しました。アプリケーションにに問題が起きているか、あるいはお使いの環境に問題があります。<br/>お使いのブラウザを一度起動し直すか、');
						} else if (isErrorArea && 503 == xhr.status) {
							$('.ajax-error').show();
							$('.ajax-error .alert_prefix').show();
							$('.ajax-error .alert_suffix').show();
							$('.ajax-error .alert_title').text('503:利用制限エラー');
							$('.ajax-error .alert_message').html('アプリケーションが現在メンテナンス中か、あるいはお使いの環境に問題があります。<br/>お使いのブラウザを一度起動し直すか、');
						} else {
							alert("エラー：" + error);
						}
						return;
					} else {
						if (xhr.status === 400) {
							if ('validate_error' in xhr.responseJSON) {
								fail(xhr.responseJSON.validate_error);
							}
						} else if (xhr.status === 401) {
							if (null !== auth && typeof auth == 'function') {
								auth();
							}
						} else if (xhr.status === 404) {
							fail("該当データが存在しません");
						}
						fail(error);
					}
				}).always(function () {
					loadingCnt--;
					if ($(loadingSelector).size() && 0 >= loadingCnt) {
						$(loadingSelector).fadeOut();
						loadingCnt = 0;
					}
				});
			},
			/**
			 * @param url
			 * @param data
			 * @param done
			 */
			head: function (path, done, fail) {
				var url = this.baseUrl + path + "." + this.dataType;
				if ($(loadingSelector).size() && 0 === loadingCnt) {
					$(loadingSelector).fadeIn();
					loadingCnt++;
				}
				$.ajax({
					type: "HEAD",
					url: url,
					cache: false,
					error: function (data) {
						fail(data);
					},
					success: function (data, status, xhr) {
						done(data, xhr);
					}
				}).fail(function (xhr, status, error) {
					if (!fail) {
						if (xhr.status === 401) {
							if (null !== auth && typeof auth == 'function') {
								auth();
							}
						}
					} else {
						fail(xhr, status, error);
					}
				}).always(function () {
					loadingCnt--;
					if ($(loadingSelector).size() && 0 >= loadingCnt) {
						$(loadingSelector).fadeOut();
						loadingCnt = 0;
					}
				});
			}
		};
		return client;
	}
};

var authorize = function () {
	if('127.0.0.1' === document.domain || -1 < document.domain.indexOf('.github.io') || -1 < document.domain.indexOf('static.') || -1 < document.domain.indexOf('staticdev.')){
		// デザイン開発環境は自動ログイン制御をしない
		return;
	}
	if($('form[flowpostformsection]').size()){
		// formをPOSTしてしまって、ページの機能にログイン処理を委ねる
		var form = $('form[flowpostformsection]').get(0);
		form.submit();
	}
	else {
		// 単純なページの再読み込み
		location.reload(true);
	}
}

$(function() {
	if (typeof devapidomain == 'undefined' || 1 > devapidomain.length) {
		devapidomain = '';
	}
	if (typeof uribase == 'undefined' || 1 > uribase.length) {
		uribase = '/api/';
	}
	apiclient = (function() {
		if('127.0.0.1' === document.domain || -1 < document.domain.indexOf('.github.io') || -1 < document.domain.indexOf('static.') || -1 < document.domain.indexOf('staticdev.')){
			if (1 > devapidomain.length){
				alert('var.jsの「devapidomain」変数に開発サーバのドメインを指定することでデザイン開発中にapi通信エラーが起こらなくなります。');
			}
			// デザイン開発環境はAPIを開発サーバーに向ける
			uribase = location.protocol + '://' + devapidomain + uribase;
		}
		return clientFactory.create(uribase, authorize);
	})();
});
