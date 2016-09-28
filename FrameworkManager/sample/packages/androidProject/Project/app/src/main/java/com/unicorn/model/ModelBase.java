package com.unicorn.model;

import java.io.UnsupportedEncodingException;
import java.lang.reflect.Constructor;
import java.lang.reflect.InvocationTargetException;
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map.Entry;

import org.apache.http.Header;
import org.apache.http.client.HttpResponseException;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import android.app.Activity;
import android.content.Context;
import android.os.Handler;
import android.os.Message;

import jp.co.project.R;
import com.loopj.android.http.JsonHttpResponseHandler;
import com.loopj.android.http.RequestParams;
import com.unicorn.constant.Constant;
import com.unicorn.gcm.GcmPreferences;
import com.unicorn.utilities.AsyncHttpClientAgent;
import com.unicorn.utilities.Log;
import com.unicorn.utilities.Utilitis;

/**
 * ModelBaseはUnicornとRest通信を行う為のベースクラスです。
 * 
 * @author　c1363
 */
public abstract class ModelBase {

	public enum loadResourceMode {
		IDResource,myIDResource, listedResource, myListedResource,
	};

	public static String TAG = "ModelBase";
	public static final String DEVICE_TOKEN_KEY = "device_token";
	public Context context;

	public String protocol;
	public String domain;
	public String urlbase;
	public String cryptKey;
	public String cryptIV;
	public int timeout;
	public String tokenKeyName;
	public String modelName;
	public String ID;
	public String myResourcePrefix;
	public String requestMethod;
	public int index;
	public int total;
	private int recordCount;
	public ArrayList<HashMap<String, Object>> responseList;
	// 通信に関する変数
	public boolean replaced;
	public boolean requested;
	public int statusCode;
	// Blockでハンドラを受け取るバージョンの為に用意
	public Handler completionHandler;
	public Handler modelBaseHandler;

	public Header[] responseHeaders;

	public boolean isDeep = true;

	/**
	 * コンストラクタです
	 * 
	 * @param argContext Contextが入っています
	 */
	public ModelBase(Context argContext) {
		context = argContext;
		protocol = "";
		domain = "";
		urlbase = "";
		cryptKey = "";
		cryptIV = "";
		timeout = 10;
		tokenKeyName = "";
		modelName = "";
		ID = null;
		myResourcePrefix = "me/";
		requestMethod = "";
		index = 0;
		total = 0;
		responseHeaders = null;
		responseList = new ArrayList<HashMap<String, Object>>();
		replaced = false;
		isDeep = true;
		requested = false;
		statusCode = 0;
		// Blockでハンドラを受け取るバージョンの為に用意
		completionHandler = null;
		recordCount = 0;
	}

	/**
	 * オーバーロードされたコンストラクタです
	 * 
	 * @param argContext Contextが入っています
	 * @param argProtocol プロトコルが入っています
	 * @param argDomain ドメインが入っています
	 * @param argURLBase ドメイン以下のディレクトリ名が入っています
	 * @param argTokenKeyName Cookieに保存するトークンのkey名が入っています
	 */
	public ModelBase(Context argContext, String argProtocol, String argDomain, String argURLBase,
			String argTokenKeyName) {
		this(argContext);
		protocol = argProtocol;
		domain = argDomain;
		urlbase = argURLBase;
		tokenKeyName = argTokenKeyName;
	}

	/**
	 * オーバーロードされたコンストラクタです
	 * 
	 * @param argContext Contextが入っています
	 * @param argProtocol プロトコルが入っています
	 * @param argDomain ドメインが入っています
	 * @param argURLBase ドメイン以下のディレクトリ名が入っています
	 * @param argTokenKeyName Cookieに保存するトークンのkey名が入っています
	 * @param argTimeout Timeoutまでの時間が入っています
	 */
	public ModelBase(Context argContext, String argProtocol, String argDomain, String argURLBase,
			String argTokenKeyName, int argTimeout) {
		this(argContext);
		protocol = argProtocol;
		domain = argDomain;
		urlbase = argURLBase;
		tokenKeyName = argTokenKeyName;
		timeout = argTimeout;
	}

	/**
	 * オーバーロードされたコンストラクタです
	 * 
	 * @param argContext Contextが入っています
	 * @param argProtocol プロトコルが入っています
	 * @param argDomain ドメインが入っています
	 * @param argURLBase ドメイン以下のディレクトリ名が入っています
	 * @param argTokenKeyName Cookieに保存するトークンのkey名が入っています
	 * @param argCryptKey トークンの暗号化に使うKEYが入っています
	 * @param argCryptIV トークンの暗号化に使うIVが入っています
	 */
	public ModelBase(Context argContext, String argProtocol, String argDomain, String argURLBase,
			String argTokenKeyName, String argCryptKey, String argCryptIV) {
		this(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName);
		cryptKey = argCryptKey;
		cryptIV = argCryptIV;
	}

	/**
	 * オーバーロードされたコンストラクタです
	 * 
	 * @param argContext Contextが入っています
	 * @param argProtocol プロトコルが入っています
	 * @param argDomain ドメインが入っています
	 * @param argURLBase ドメイン以下のディレクトリ名が入っています
	 * @param argTokenKeyName Cookieに保存するトークンのkey名が入っています
	 * @param argCryptKey トークンの暗号化に使うKEYが入っています
	 * @param argCryptIV トークンの暗号化に使うIVが入っています
	 * @param argTimeout Timeoutまでの時間が入っています
	 */
	public ModelBase(Context argContext, String argProtocol, String argDomain, String argURLBase,
			String argTokenKeyName, String argCryptKey, String argCryptIV, int argTimeout) {
		this(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName, argCryptKey,
				argCryptIV);
		timeout = argTimeout;
	}

	/**
	 * REST通信を行う為のURLの生成を行うメソッドです
	 * 
	 * @param resourceId IDが入っています
	 * @return 生成したURLが入っています。
	 */
	public String createURLString(String myResourcePrefix,String resourceId) {
		String url = "";
		if (null != resourceId) {
			// 更新(Put)
			url = String.format("%s://%s%s%s%s/%s.json", protocol, domain, urlbase,
					myResourcePrefix, modelName, resourceId);
		} else {
			// 新規(POST)
			url = String.format("%s://%s%s%s%s.json", protocol, domain, urlbase, myResourcePrefix,
					modelName);
		}
		return url;
	}

	/**
	 * モデルを参照するメソッドです
	 * 通信結果を元に処理を行う場合はload(Hanler argCompletionHandler)を使用し、
	 * Hanler内で処理を分岐して下さい。
	 * 
	 * @return loadの戻り値
	 */
	public boolean load() {
		return load(loadResourceMode.myIDResource, null);
	}

	/**
	 * モデルを参照するメソッドです
	 * 通信結果は引数として渡されたHandlerに渡されます。
	 * 
	 * @param argCompletionHandler 通信後に呼び出すhandlerが入っています
	 * @return loadの戻り値
	 */
	public boolean load(Handler argCompletionHandler) {
		completionHandler = argCompletionHandler;
		return load(loadResourceMode.myIDResource, null);
	}

//	/**
//	 * モデルをlist参照するメソッドです
//	 * 通信結果を元に処理を行う場合はlist(Hanler argCompletionHandler)を使用し、 Hanler内で処理を分岐して下さい。
//	 *
//	 * @return trueを返却します。
//	 */
//	public boolean list() {
//		return load(loadResourceMode.listedResource, null);
//	}
//
//	/**
//	 * モデルを開始位置、件数を指定してlist参照するメソッドです
//	 * 通信結果を元に処理を行う場合はlist(Hanler argCompletionHandler)を使用し、 Hanler内で処理を分岐して下さい。
//	 *
//	 * @param offset データの取得開始位置
//	 * @param limit 取得件数
//	 * @return trueを返却します。
//	 */
//	public boolean list(int offset, int limit) {
//		HashMap<String, Object> argWhereParams = new HashMap<String, Object>();
//		argWhereParams.put("OFFSET", String.valueOf(offset));
//		argWhereParams.put("LIMIT", String.valueOf(limit));
//		return load(loadResourceMode.listedResource, null);
//	}
//
//	/**
//	 * モデルをlist参照するメソッドです 通信結果は引数として渡されたHandlerに渡されます。
//	 *
//	 * @param argCompletionHandler 通信後に呼び出すhandlerが入っています
//	 * @return trueを返却します。
//	 */
//	public boolean list(Handler argCompletionHandler) {
//		completionHandler = argCompletionHandler;
//		return load(loadResourceMode.listedResource, null);
//	}
//
//	/**
//	 * モデルを開始位置、件数を指定してlist参照するメソッドです
//	 * 通信結果を元に処理を行う場合はlist(Hanler argCompletionHandler)を使用し、 Hanler内で処理を分岐して下さい。
//	 *
//	 * @param offset データの取得開始位置
//	 * @param limit 取得件数
//	 * @return trueを返却します。
//	 */
//	public boolean list(Handler argCompletionHandler, int offset, int limit) {
//		completionHandler = argCompletionHandler;
//		HashMap<String, Object> argWhereParams = new HashMap<String, Object>();
//		argWhereParams.put("OFFSET", String.valueOf(offset));
//		argWhereParams.put("LIMIT", String.valueOf(limit));
//		return load(loadResourceMode.listedResource, argWhereParams);
//	}
//
//	/**
//	 * モデルを開始位置、件数を指定してlist参照するメソッドです
//	 * 通信結果を元に処理を行う場合はlist(Hanler argCompletionHandler)を使用し、 Hanler内で処理を分岐して下さい。
//	 *
//	 * @param offset データの取得開始位置
//	 * @param limit 取得件数
//	 * @return trueを返却します。
//	 */
//	public boolean list(Handler argCompletionHandler, int offset, int limit, String queryJson) {
//		completionHandler = argCompletionHandler;
//		HashMap<String, Object> argWhereParams = new HashMap<String, Object>();
//		argWhereParams.put("OFFSET", String.valueOf(offset));
//		argWhereParams.put("LIMIT", String.valueOf(limit));
//		argWhereParams.put("Q", queryJson);
//		return load(loadResourceMode.listedResource, argWhereParams);
//	}

	/**
	 * モデルを条件付きで参照するメソッドです
	 * 通信結果を元に処理を行う場合はquery(HashMap<String, Object> argWhereParams, Handler argCompletionHandler)
	 * を使用し、Hanler内で処理を分岐して下さい。
	 * 
	 * @param argWhereParams 通信時に付与するパラメータがMapで入っています
	 * @return trueを返却します。
	 */
	public boolean query(HashMap<String, Object> argWhereParams) {
		return load(loadResourceMode.myListedResource, argWhereParams);
	}

	/**
	 * モデルを条件付きで参照するメソッドです
	 * 通信結果は引数として渡されたHandlerに渡されます。
	 * 
	 * @param argWhereParams 通信時に付与するパラメータがMapで入っています
	 * @param argCompletionHandler 通信後に呼び出すhandlerが入っています
	 * @return trueを返却します。
	 */
	public boolean query(HashMap<String, Object> argWhereParams, Handler argCompletionHandler) {
		completionHandler = argCompletionHandler;
		return load(loadResourceMode.myListedResource, argWhereParams);
	}

	/**
	 * モデルを保存するメソッドです
	 * 
	 * @return trueを返却します。
	 */
	abstract public boolean save();

	/**
	 * モデルを保存するメソッドです
	 * 
	 * @param argSaveParams テキストデータ
	 * @param argUploadData アップロードするデータ
	 * @param argUploadDataName アップロードするデータの名称
	 * @param argUploadDataContentType アップロードするデータのコンテントタイプ
	 * @param argUploadDataKey アップロードするデータのキー
	 * @return trueを返却します。
	 */
	public boolean save(HashMap<String, Object> argSaveParams, byte[] argUploadData,
			String argUploadDataName, String argUploadDataContentType, String argUploadDataKey) {

		String url = createURLString(myResourcePrefix,ID);
		Log.d(url);

		if (ID != null) {
			// XXX 更新(Put)するところだがpostBinaryの実装が不完全なのでpostBinaryを使用する
			AsyncHttpClientAgent.postBinary(context, url, argSaveParams, argUploadData,
					argUploadDataName, argUploadDataContentType, argUploadDataKey,
					new ModelHttpResponseHandler("postBinary"));
		} else {
			// 更新(Post)
			AsyncHttpClientAgent.postBinary(context, url, argSaveParams, argUploadData,
					argUploadDataName, argUploadDataContentType, argUploadDataKey,
					new ModelHttpResponseHandler("postBinary"));
		}
		return true;
	}

	/**
	 * ファイルを一つのモデルリソースと見立ててアップロードする。
	 * ID無しでのアップロードは許可しない為強制PUT
	 * 
	 * @param argUploadData アップロードデータのbyte[]
	 * @return ID無しの場合はfalse,それ以外はtrueを返却
	 */
	public boolean _save(byte[] argUploadData) {
		String url = createURLString(myResourcePrefix,ID);

		if (null != ID) {
			// 更新(Put)

			AsyncHttpClientAgent.putBinary(context, url, argUploadData,
					new ModelHttpResponseHandler("putBinary"));
		} else {
			// XXX ID無しのファイルアップロードは出来ない！
			return false;
		}
		return false;
	}

	/**
	 * ファイルを一つのモデルリソースと見立ててアップロードする。
	 * ID無しでのアップロードは許可しない為強制PUT
	 * 
	 * @param argsaveParams postするデータ
	 * @return trueを返却
	 */
	public boolean save(HashMap<String, Object> argsaveParams) {

		String url = createURLString(myResourcePrefix,ID);

		if (ID != null) {
			// 更新(Put)
			RequestParams requestParam = new RequestParams();

			for (Iterator<Entry<String, Object>> it = argsaveParams.entrySet().iterator(); it
					.hasNext();) {
				HashMap.Entry<String, Object> entry = (HashMap.Entry<String, Object>) it.next();
				Object key = entry.getKey();
				Object value = entry.getValue();
				if (value instanceof String) {
					requestParam.put((String) key, (String) value);
				}
			}
			GcmPreferences prefs = new GcmPreferences(context);
			String id = prefs.getRegistrationId();
			if (!id.isEmpty()) {
				requestParam.put(DEVICE_TOKEN_KEY, id);
			}

			AsyncHttpClientAgent.put(context, url, requestParam, new ModelHttpResponseHandler(
					"put"));
		} else {
			// 新規(POST)
			RequestParams requestParam = new RequestParams();

			for (Iterator<Entry<String, Object>> it = argsaveParams.entrySet().iterator(); it
					.hasNext();) {
				HashMap.Entry<String, Object> entry = (HashMap.Entry<String, Object>) it.next();
				Object key = entry.getKey();
				Object value = entry.getValue();
				if (value instanceof String) {
					requestParam.put((String) key, (String) value);
				}
			}
			GcmPreferences prefs = new GcmPreferences(context);
			String id = prefs.getRegistrationId();
			if (!id.isEmpty()) {
				requestParam.put(DEVICE_TOKEN_KEY, id);
			}

			AsyncHttpClientAgent.post(context, url, requestParam, new ModelHttpResponseHandler(
					"post"));
		}
		return true;
	}

	/**
	 * ファイルを一つのモデルリソースと見立ててアップロードする。
	 * ID無しでのアップロードは許可しない為強制PUT
	 * 
	 * @param argsaveParams postするデータ
	 * @return trueを返却
	 */
	public boolean save(HashMap<String, Object> argsaveParams, Handler handler) {
		completionHandler = handler;
		String url = createURLString(myResourcePrefix,ID);

		if (ID != null) {
			// 更新(Put)
			RequestParams requestParam = new RequestParams();

			for (Iterator<Entry<String, Object>> it = argsaveParams.entrySet().iterator(); it
					.hasNext();) {
				HashMap.Entry<String, Object> entry = (HashMap.Entry<String, Object>) it.next();
				Object key = entry.getKey();
				Object value = entry.getValue();
				if (value instanceof String) {
					requestParam.put((String) key, (String) value);
				}
			}
			GcmPreferences prefs = new GcmPreferences(context);
			String id = prefs.getRegistrationId();
			if (!id.isEmpty()) {
				requestParam.put(DEVICE_TOKEN_KEY, id);
			}

			AsyncHttpClientAgent.post(context, url, requestParam, new ModelHttpResponseHandler(
					"post"));
		} else {
			// 新規(POST)
			RequestParams requestParam = new RequestParams();

			for (Iterator<Entry<String, Object>> it = argsaveParams.entrySet().iterator(); it
					.hasNext();) {
				HashMap.Entry<String, Object> entry = (HashMap.Entry<String, Object>) it.next();
				Object key = entry.getKey();
				Object value = entry.getValue();
				if (value instanceof String) {
					requestParam.put((String) key, (String) value);
				}
			}
			GcmPreferences prefs = new GcmPreferences(context);
			String id = prefs.getRegistrationId();
			if (!id.isEmpty()) {
				requestParam.put(DEVICE_TOKEN_KEY, id);
			}

			AsyncHttpClientAgent.post(context, url, requestParam, new ModelHttpResponseHandler(
					"post"));
		}
		return true;
	}

	/**
	 * GET通信をする際にパラメータをURLにつける
	 * 
	 * @param url 元のURLが入っています
	 * @param argsaveParams getパラメータのmap
	 * @return 生成されたurlが返却されます
	 */
	public String createGetURl(String url, HashMap<String, Object> argsaveParams) {
		if (argsaveParams.size() != 0) {
			url = url + "?";
		}
		for (Iterator<Entry<String, Object>> it = argsaveParams.entrySet().iterator(); it.hasNext();) {
			HashMap.Entry<String, Object> entry = (HashMap.Entry<String, Object>) it.next();
			Object key = entry.getKey();
			Object value = entry.getValue();
			if (value instanceof String) {
				try {
					url = url + (String) key + "=" + URLEncoder.encode((String) value, "UTF-8");
					if (it.hasNext()) {
						url = url + "&";
					}
				} catch (UnsupportedEncodingException e) {
					// TODO Auto-generated catch block
					e.printStackTrace();
				}
			}
		}
		return url;
	}

	/**
	 * IDを元にモデル参照を行います
	 * ただしloadResourceModeがautomaticResourceの場合のみargWhereParamsのデータをもとに
	 * 条件検索を行う事ができます。
	 * 
	 * @param loadResourceMode モデル参照するタイプ
	 *            <ul>
	 *            <li>myResource　自分のデータ</li>
	 *            <li>listedResource　リストデータ</li>
	 *            <li>myListedResource　自動判別</li>
	 *            </ul>
	 * @param argWhereParams　条件つきで参照する場合のパラメータのmap
	 * @return trueを返却します
	 */
	public boolean load(loadResourceMode argLoadResourceMode, HashMap<String, Object> argWhereParams) {

		String url = "";
		GcmPreferences prefs;
		String id;
		RequestParams params;

		switch (argLoadResourceMode) {

			case IDResource:
				url = createURLString("",ID);

				prefs = new GcmPreferences(context);
				id = prefs.getRegistrationId();
				params = null;
				if (!id.isEmpty()) {
					params = new RequestParams();
					params.put(DEVICE_TOKEN_KEY, id);
				}

				if("POST".equals(requestMethod)){
					for (Iterator<Entry<String, Object>> it = argWhereParams.entrySet().iterator(); it.hasNext();) {
						HashMap.Entry<String, Object> entry = (HashMap.Entry<String, Object>) it.next();
						Object key = entry.getKey();
						Object value = entry.getValue();
						if (value instanceof String) {
							params.put((String) key,(String)value);
						}
					}
					AsyncHttpClientAgent.post(context, url, params, new ModelHttpResponseHandler("post"));
				}else if("POST".equals(requestMethod)){
					for (Iterator<Entry<String, Object>> it = argWhereParams.entrySet().iterator(); it.hasNext();) {
						HashMap.Entry<String, Object> entry = (HashMap.Entry<String, Object>) it.next();
						Object key = entry.getKey();
						Object value = entry.getValue();
						if (value instanceof String) {
							params.put((String) key,(String)value);
						}
					}
					AsyncHttpClientAgent.put(context, url, params, new ModelHttpResponseHandler("put"));
				}else {
					if (argWhereParams != null) {
						url = createGetURl(url, argWhereParams);
					}
					AsyncHttpClientAgent.get(context, url, params, new ModelHttpResponseHandler("get"));
				}

				break;
			case myIDResource:
				url = createURLString(myResourcePrefix,ID);

				prefs = new GcmPreferences(context);
				id = prefs.getRegistrationId();
				params = null;
				if (!id.isEmpty()) {
					params = new RequestParams();
					params.put(DEVICE_TOKEN_KEY, id);
				}

				if("POST".equals(requestMethod)){
					for (Iterator<Entry<String, Object>> it = argWhereParams.entrySet().iterator(); it.hasNext();) {
						HashMap.Entry<String, Object> entry = (HashMap.Entry<String, Object>) it.next();
						Object key = entry.getKey();
						Object value = entry.getValue();
						if (value instanceof String) {
							params.put((String) key,(String)value);
						}
					}
					AsyncHttpClientAgent.post(context, url, params, new ModelHttpResponseHandler("post"));
				}else if("POST".equals(requestMethod)){
					for (Iterator<Entry<String, Object>> it = argWhereParams.entrySet().iterator(); it.hasNext();) {
						HashMap.Entry<String, Object> entry = (HashMap.Entry<String, Object>) it.next();
						Object key = entry.getKey();
						Object value = entry.getValue();
						if (value instanceof String) {
							params.put((String) key,(String)value);
						}
					}
					AsyncHttpClientAgent.put(context, url, params, new ModelHttpResponseHandler("put"));
				}else {
					if (argWhereParams != null) {
						url = createGetURl(url, argWhereParams);
					}
					AsyncHttpClientAgent.get(context, url, params, new ModelHttpResponseHandler("get"));
				}

				break;
			case listedResource:
				url = createURLString("","");

				prefs = new GcmPreferences(context);
				id = prefs.getRegistrationId();
				params = null;
				if (!id.isEmpty()) {
					params = new RequestParams();
					params.put(DEVICE_TOKEN_KEY, id);
				}

				if("POST".equals(requestMethod)){
					for (Iterator<Entry<String, Object>> it = argWhereParams.entrySet().iterator(); it.hasNext();) {
						HashMap.Entry<String, Object> entry = (HashMap.Entry<String, Object>) it.next();
						Object key = entry.getKey();
						Object value = entry.getValue();
						if (value instanceof String) {
							params.put((String) key,(String)value);
						}
					}
					AsyncHttpClientAgent.post(context, url, params, new ModelHttpResponseHandler("post"));
				}else if("POST".equals(requestMethod)){
					for (Iterator<Entry<String, Object>> it = argWhereParams.entrySet().iterator(); it.hasNext();) {
						HashMap.Entry<String, Object> entry = (HashMap.Entry<String, Object>) it.next();
						Object key = entry.getKey();
						Object value = entry.getValue();
						if (value instanceof String) {
							params.put((String) key,(String)value);
						}
					}
					AsyncHttpClientAgent.put(context, url, params, new ModelHttpResponseHandler("put"));
				}else {
					if (argWhereParams != null) {
						url = createGetURl(url, argWhereParams);
					}
					AsyncHttpClientAgent.get(context, url, params, new ModelHttpResponseHandler("get"));
				}
				break;
			case myListedResource:
				url = createURLString(myResourcePrefix,"");

				prefs = new GcmPreferences(context);
				id = prefs.getRegistrationId();
				params = null;
				if (!id.isEmpty()) {
					params = new RequestParams();
					params.put(DEVICE_TOKEN_KEY, id);
				}

				if("POST".equals(requestMethod)){
					for (Iterator<Entry<String, Object>> it = argWhereParams.entrySet().iterator(); it.hasNext();) {
						HashMap.Entry<String, Object> entry = (HashMap.Entry<String, Object>) it.next();
						Object key = entry.getKey();
						Object value = entry.getValue();
						if (value instanceof String) {
							params.put((String) key,(String)value);
						}
					}
					AsyncHttpClientAgent.post(context, url, params, new ModelHttpResponseHandler("post"));
				}else if("POST".equals(requestMethod)){
					for (Iterator<Entry<String, Object>> it = argWhereParams.entrySet().iterator(); it.hasNext();) {
						HashMap.Entry<String, Object> entry = (HashMap.Entry<String, Object>) it.next();
						Object key = entry.getKey();
						Object value = entry.getValue();
						if (value instanceof String) {
							params.put((String) key,(String)value);
						}
					}
					AsyncHttpClientAgent.put(context, url, params, new ModelHttpResponseHandler("put"));
				}else {
					if (argWhereParams != null) {
						url = createGetURl(url, argWhereParams);
					}
					AsyncHttpClientAgent.get(context, url, params, new ModelHttpResponseHandler("get"));
				}
		}
		return true;
	}

	/**
	 * 通信レスポンスデータを元にmodelにデータをセットする。
	 * リスト参照など、複数データが返却された場合は先頭のデータがセットされます。
	 */
	public void setModelData() {
		if (0 < responseList.size()) {
			HashMap<String, Object> map = responseList.get(0);
			if (map.get("list") == null) {
				total = responseList.size();
				if (0 < total) {
					_setModelData(responseList.get(0));
				}
			} else {
				@SuppressWarnings("unchecked")
				ArrayList<HashMap<String, Object>> array = (ArrayList<HashMap<String, Object>>) map
						.get("list");
				total = array.size();
				if (0 < total) {
					_setModelData(map,index);
				}
			}
		}
	}

	/**
	 * listの0番目のHashMapを元にmodelにデータをセットする。
	 * セット部分は各モデルで_setModelDataをOverrideして実装して下さい
	 * 
	 * @param list モデルにセットする元データ(jsonのArray)
	 */
	public void setModelData(ArrayList<HashMap<String, Object>> list) {
		
		responseList = list;
		if (0 < responseList.size()) {
			HashMap<String, Object> map = responseList.get(0);
			if (map.get("list") == null) {
				total = responseList.size();
				if (0 < total) {
					_setModelData(responseList.get(0));
				}
			} else {
				@SuppressWarnings("unchecked")
				ArrayList<HashMap<String, Object>> array = (ArrayList<HashMap<String, Object>>) map
						.get("list");
				total = array.size();
				if (0 < total) {
					_setModelData(map,index);
				}
			}
		}
	}

	/**
	 * listの0番目のHashMapを元にmodelにデータをセットする。
	 * セット部分は各モデルで_setModelDataをOverrideして実装して下さい
	 * 
	 * @param list モデルにセットする元データ(jsonのArray)
	 */
	public void setModelData(ArrayList<HashMap<String, Object>> list, Header[] responseHeader) {
		responseList = list;
		responseHeaders = responseHeader;
		if (0 < responseList.size()) {
			HashMap<String, Object> map = responseList.get(0);
			if (map.get("list") == null) {
				total = responseList.size();
				if (0 < total) {
					_setModelData(responseList.get(0));
				}
			} else {
				@SuppressWarnings("unchecked")
				ArrayList<HashMap<String, Object>> array = (ArrayList<HashMap<String, Object>>) map
						.get("list");
				total = array.size();
				if (0 < total) {
					_setModelData(map,index);
				}
			}
		}
		if (responseHeaders == null) {
			return;
		}
		for (int i = 0; i < responseHeaders.length; i++) {
			Header header = responseHeaders[i];
			if ( false == "Records".equals(header.getName())) {
				continue;
			}
			String headerValue = header.getValue();
			recordCount = Integer.parseInt(headerValue);
		}
	}

	/**
	 * listのargIndex番目のHashMapを元にmodelにデータをセットする。
	 * セット部分は各モデルで_setModelDataをOverrideして実装して下さい
	 * 
	 * @param list モデルにセットする元データ(jsonのArray)
	 */
	public void setModelData(ArrayList<HashMap<String, Object>> list, int argIndex) {
		responseList = list;
		if (0 < responseList.size()) {
			HashMap<String, Object> map = responseList.get(0);
			if (map.get("list") == null) {
				total = responseList.size();
				if (0 < total) {
					index = argIndex;
					_setModelData(responseList.get(index));
				}
			} else {
				@SuppressWarnings("unchecked")
				ArrayList<HashMap<String, Object>> array = (ArrayList<HashMap<String, Object>>) map
						.get("list");
				total = array.size();
				if (0 < total) {
					index = argIndex;
					_setModelData(map,index);
				}
			}
		}
	}

	/**
	 * listのargIndex番目のHashMapを元にmodelにデータをセットする。
	 * セット部分は各モデルで_setModelDataをOverrideして実装して下さい
	 * 
	 * @param list モデルにセットする元データ(jsonのArray)
	 */
	public void setModelData(ArrayList<HashMap<String, Object>> list, Header[] responseHeader,
			int argIndex) {
		responseList = list;
		responseHeaders = responseHeader;
		if (0 < responseList.size()) {
			HashMap<String, Object> map = responseList.get(0);
			if (map.get("list") == null) {
				total = responseList.size();
				if (0 < total) {
					index = argIndex;
					_setModelData(responseList.get(index));
				}
			} else {
				@SuppressWarnings("unchecked")
				ArrayList<HashMap<String, Object>> array = (ArrayList<HashMap<String, Object>>) map
						.get("list");
				total = array.size();
				if (0 < total) {
					index = argIndex;
					_setModelData(map,index);
				}
			}
		}
		if (responseHeaders == null) {
			return;
		}
		for (int i = 0; i < responseHeaders.length; i++) {
			Header header = responseHeaders[i];
			if ( false == "Records".equals(header.getName())) {
				continue;
			}
			String headerValue = header.getValue();
			recordCount = Integer.parseInt(headerValue);
		}
	}

	/**
	 * setModelDataから呼ばれるメソッド
	 * 各モデルでOverrideして実装。モデル毎の専用変数にデータを入れて下さい
	 * 
	 * @param map モデルにセットする元データ(jsonのMap)
	 */
	abstract protected void _setModelData(HashMap<String, Object> map);

	/**
	 * setModelDataから呼ばれるメソッド
	 * 各モデルでOverrideして実装。モデル毎の専用変数にデータを入れて下さい
	 * 
	 * @param map モデルにセットする元データ(jsonのMap)
	 */
	protected void _setModelData(HashMap<String, Object> map,int index){
		
	}
	/**
	 * モデルからモデル生成の元データとなるMapを生成する
	 * 各モデルでOverrideして実装。
	 * 
	 * @return trueを返却します
	 */
	abstract public HashMap<String, Object> convertModelData();

	/**
	 * このモデルの専用変数の更新フラグを全てリセットするメソッド
	 */
	abstract protected void resetReplaceFlagment();

	/**
	 * handlerがnullで無い場合、通信結果をhandlerに渡す
	 * 
	 * @param msg　通信結果が格納されています。
	 */
	public void returnMainTheread(Message msg) {
		if (completionHandler != null) {
			completionHandler.sendMessage(msg);
			completionHandler = null;
		}
	}

	/**
	 * JSONArrayをパースしてArrayListに変換します
	 * JSONArray内のJSONObjectはcreateMapFromJSONObjectでHashMap<String,Object>に変換されます
	 * 
	 * @param data JSONArrayが格納されています
	 * @throws JSONException パースに失敗した場合throwされます
	 * @return JsonArrayをパースした結果
	 */
	public ArrayList<HashMap<String, Object>> createArrayFromJSONArray(JSONArray data)
			throws JSONException {
		ArrayList<HashMap<String, Object>> array = new ArrayList<HashMap<String, Object>>();
		if (array != null) {
			for (int i = 0; i < data.length(); i++) {
				JSONObject jsonObject = data.getJSONObject(i);
				array.add(createMapFromJSONObject(jsonObject));
			}
		}
		return array;
	}

	/**
	 * JSONObjectをパースしてHashMap<String,Object>に変換します
	 * JSONObject内にJSONArrayがあった場合はcreateArrayFromJSONArrayでArrayListへ変換されます
	 * 
	 * @param data JSONObjectが格納されています
	 * @throws JSONException パースに失敗した場合throwされます
	 * @return JsonObjectをパースした結果
	 */
	public HashMap<String, Object> createMapFromJSONObject(JSONObject data) throws JSONException {
		HashMap<String, Object> map = new HashMap<String, Object>();
		if (data != null) {
			Iterator<?> keys = data.keys();

			while (keys.hasNext()) {
				String key = (String) keys.next();
				if (data.get(key) instanceof JSONObject) {
					map.put(key, createMapFromJSONObject((JSONObject) data.get(key)));
				} else if (data.get(key) instanceof JSONArray) {
					map.put(key, createArrayFromJSONArray((JSONArray) data.get(key)));
				} else if (data.get(key) instanceof String) {
					map.put(key, data.get(key));
				}
			}
		}
		return map;
	}

	/**
	 * model内のあるデータを1加算する場合に使用する目的のメソッド
	 * 各モデルでorrverrideして使用
	 * 各モデルでの実装内容：目的の変数を加算してreplacedをtrueにして_incrementを呼び出します
	 * 
	 * @return _incrementメソッドをコールした戻り値が返ります
	 */
	public boolean increment() {
		return true;
	}

	/**
	 * modelbaseを継承した子クラスのincrementから呼ばれます。
	 * 
	 * @return _incrementメソッドをコールした戻り値が返ります
	 */
	public boolean _increment(HashMap<String, Object> argSaveParams) {
		if (null != ID) {
			return save(argSaveParams);
		}
		// インクリメントはID指定ナシはエラー！
		return false;
	}

	/**
	 * model内のあるデータを1減算する場合に使用する目的のメソッド
	 * 各モデルでorrverrideして使用
	 * 各モデルでの実装内容：目的の変数を減算してreplacedをtrueにして_decrementを呼び出します
	 * 
	 * @return _decrementメソッドをコールした戻り値が返ります
	 */
	public boolean decrement() {
		return true;
	}

	/**
	 * modelbaseを継承した子クラスのdecrementから呼ばれます。
	 * 
	 * @return _decrementメソッドをコールした戻り値が返ります
	 */
	public boolean _decrement(HashMap<String, Object> argSaveParams) {
		if (null != ID) {
			return save(argSaveParams);
		}
		// インクリメントはID指定ナシはエラー！
		return false;
	}

	/**
	 * total件数が現在のindexより多い場合次のモデルデータに変更する
	 * 
	 * @return 次のモデルが存在しない場合false、それ以外はtrueを返す
	 */
	public boolean next() {
		if (index < responseList.size() - 1) {
			index++;
			setModelData(responseList, responseHeaders, index);
			return true;
		}
		return false;
	}

	/**
	 * argIndex番目のモデルデータを取得します
	 * 
	 * @param argIndex 何番目のmodelを取得するか
	 * @return argIndex番目のモデル
	 */
	public ModelBase objectAtIndex(int argIndex) {
		ModelBase nextModel = null;
		if (0 < total && argIndex < total) {
			Class<?> cls;

			Constructor<?> constructor = null;
			// 引数の型を定義
			Class<Context> contextParam = Context.class;

			String className = this.getClass().getName();
			try {
				cls = Class.forName(className);
				constructor = cls.getConstructor(contextParam);
				// 引数を渡してオブジェクトを生成する
				nextModel = (ModelBase) constructor.newInstance(context);
				nextModel.setModelData(responseList, responseHeaders, argIndex);
			} catch (ClassNotFoundException e) {
				e.printStackTrace();
			} catch (NoSuchMethodException e) {
				e.printStackTrace();
			} catch (InstantiationException e) {
				e.printStackTrace();
			} catch (IllegalAccessException e) {
				e.printStackTrace();
			} catch (InvocationTargetException e) {
				e.printStackTrace();
			}
		}
		return nextModel;
	}

	/**
	 * argIndex番目にモデルを挿入します
	 * 
	 * @param model 挿入するモデル
	 * @param argIndex 何番目に挿入するか
	 */
	public void insertObject(ModelBase model, int argIndex) {
		HashMap<String, Object> response = model.convertModelData();
		responseList.add(argIndex, response);
		total = responseList.size();
	}

	/**
	 * argIndex番目のモデルを置き換えます
	 * 
	 * @param model 置き換えるモデル
	 * @param argIndex 何番目に挿入するか
	 */
	public void replaceObject(ModelBase model, int argIndex) {
		HashMap<String, Object> response = model.convertModelData();
		responseList.remove(argIndex);
		responseList.add(argIndex, response);
		total = responseList.size();
	}

	/**
	 * argIndex番目のモデルを削除します
	 * 
	 * @param argIndex 何番目のモデルを削除するか
	 */
	public void removeObject(int argIndex) {
		responseList.remove(argIndex);
		total = responseList.size();
	}

	/**
	 * モデルデータを全件ArrayListにして返します
	 * 
	 * @return　全件分のArrayList<ModelBase>
	 */
	public ArrayList<ModelBase> toArray() {
		ArrayList<ModelBase> array = new ArrayList<ModelBase>();
		for (int i = 0; i < total; i++) {
			array.add(objectAtIndex(i));
		}
		return array;
	}
	
	/**
	 * モデルデータを全件ArrayListにして返します
	 * 
	 * @return　全件分のArrayList<ModelBase>
	 */
	public ArrayList<ModelBase> toReverceArray() {
		ArrayList<ModelBase> array = new ArrayList<ModelBase>();
		for (int i = total -1; i >= 0; i--) {
			array.add(objectAtIndex(i));
		}
		return array;
	}

	/**
	 * エラーダイアログを表示します
	 * 
	 * @param argStatusCode エラーのステータスコード
	 * @param activity　このダイアログを管理するActivity（最前面のactivity)
	 */
	public void showRequestError(int argStatusCode, Activity activity,
			ArrayList<HashMap<String, Object>> response) {
		if (response.size() > 0) {
			showRequestError(argStatusCode, activity, response.get(0));
		} else {
			showRequestError(argStatusCode, activity, new HashMap<String, Object>());
		}
	}

	/**
	 * エラーダイアログを表示します
	 * 
	 * @param argStatusCode エラーのステータスコード
	 * @param activity　このダイアログを管理するActivity（最前面のactivity)
	 */
	public void showRequestError(int argStatusCode, Activity activity,
			HashMap<String, Object> response) {
		String errorMsg = context.getString(R.string.errorMsgTimeout);
		if (0 < argStatusCode) {
			errorMsg = context.getString(R.string.errorMsgServerError);
			if (400 == argStatusCode) {
				errorMsg = context.getString(R.string.errorMsg400);
			}
			if (401 == argStatusCode) {
				errorMsg = context.getString(R.string.errorMsg401);
			}
			if (404 == argStatusCode) {
				errorMsg = context.getString(R.string.errorMsg404);
			}
			if (503 == argStatusCode) {
				errorMsg = context.getString(R.string.errorMsg503);
			}
		}

		// responseにvalidate_errorが含まれていたらエラーメッセージを上書き
		if (response.containsKey("validate_error")) {
			Log.d("validate_error = " + response.get("validate_error"));
			errorMsg = "入力エラー\n\n" + (String) response.get("validate_error");
		}

		if (context != null) {
			Utilitis.showAlert(context, errorMsg, response, completionHandler, activity,
					argStatusCode);
		}
	}

	/**
	 * モデルレスポンスのハンドラ
	 * 
	 * @author c1718
	 * 
	 */
	private class ModelHttpResponseHandler extends JsonHttpResponseHandler {

		private String usedMethodName;

		/**
		 * 
		 * @param usedMethodName AsyncHttpClientAgentが使用したメソッド名
		 */
		private ModelHttpResponseHandler(String usedMethodName) {
			this.usedMethodName = usedMethodName;
		}

		@Override
		public void onSuccess(Header[] responseHeader, JSONObject response) {
			Log.v(TAG, this.usedMethodName + "->onSuccessJsonObject");
			responseHeaders = responseHeader;
			try {
				responseList.add(createMapFromJSONObject(response));
				Log.v(TAG, this.usedMethodName + "->onSuccessJsonObject->pauseSuccess");
				setModelData();
				resetReplaceFlagment();
				Message msg = new Message();
				msg.arg1 = Constant.RESULT_OK;
				msg.arg2 = 200;
				if (0 < responseList.size()) {
					responseList.get(0).put("header", responseHeader);
					msg.obj = responseList.get(0);
				}
				returnMainTheread(msg);
			} catch (JSONException e) {
				e.printStackTrace();
			}
		}

		@Override
		public void onSuccess(Header[] responseHeader, JSONArray response) {
			Log.v(TAG, this.usedMethodName + "->onSuccessJsonArray");
			responseHeaders = responseHeader;
			try {
				responseList = createArrayFromJSONArray(response);
				setModelData();
				Log.v(TAG, this.usedMethodName + "->onSuccessJsonArray->pauseSuccess");
				resetReplaceFlagment();
				Message msg = new Message();
				msg.arg1 = Constant.RESULT_OK;
				msg.arg2 = 200;
				if (0 < responseList.size()) {
					responseList.get(0).put("header", responseHeader);
					msg.obj = responseList.get(0);
				}
				returnMainTheread(msg);
			} catch (JSONException e) {
				e.printStackTrace();
			}
		}

		@Override
		public void onSuccess(int statusCode, Header[] responseHeader, JSONObject response) {
			Log.v(TAG, this.usedMethodName + "->onSuccessJsonObject");

			responseHeaders = responseHeader;
			try {
				responseList.add(createMapFromJSONObject(response));
				Log.v(TAG, this.usedMethodName + "->onSuccessJsonObject->pauseSuccess");
				setModelData();
				resetReplaceFlagment();
				Message msg = new Message();
				msg.arg1 = Constant.RESULT_OK;
				msg.arg2 = 200;
				if (0 < responseList.size()) {
					responseList.get(0).put("header", responseHeader);
					msg.obj = responseList.get(0);
				}
				returnMainTheread(msg);
			} catch (JSONException e) {
				e.printStackTrace();
			}

		}

		@Override
		public void onSuccess(int statusCode, Header[] responseHeader, JSONArray response) {

			Log.v(TAG, this.usedMethodName + "->onSuccessJsonArray");

			responseHeaders = responseHeader;
			try {
				responseList = createArrayFromJSONArray(response);
				setModelData();
				Log.v(TAG, this.usedMethodName + "->onSuccessJsonArray->pauseSuccess");
				resetReplaceFlagment();
				Message msg = new Message();
				msg.arg1 = Constant.RESULT_OK;
				msg.arg2 = 200;
				if (0 < responseList.size()) {
					responseList.get(0).put("header", responseHeader);
					msg.obj = responseList.get(0);
				}
				returnMainTheread(msg);
			} catch (JSONException e) {
				e.printStackTrace();
			}

		}

		@Override
		public void onFailure(Throwable e, JSONObject errorResponse) {
			int statusCode = 0;
			if (e instanceof HttpResponseException) {
				statusCode = ((HttpResponseException) e).getStatusCode();
				if (statusCode != 200) {
					try {
						Log.v(TAG, this.usedMethodName + "->onSuccessJsonObject->pauseSuccess");
						setModelData();
						resetReplaceFlagment();
						Message msg = new Message();
						msg.arg1 = Constant.RESULT_FAILED;
						msg.arg2 = statusCode;
						msg.obj = createMapFromJSONObject(errorResponse);
						returnMainTheread(msg);
					} catch (JSONException e1) {
						// TODO Auto-generated catch block
						e1.printStackTrace();
					}
				}
			} else {
				try {
					Message msg = new Message();
					msg.arg1 = Constant.RESULT_FAILED;
					msg.arg2 = statusCode;
					msg.obj = createMapFromJSONObject(null);
					returnMainTheread(msg);

				} catch (JSONException e1) {
					// TODO Auto-generated catch block
					e1.printStackTrace();
				}
			}
			String error = e.toString();
			Log.d(TAG + " error", error);
		}

		@Override
		public void onFailure(Throwable e, JSONArray errorResponse) {
			int statusCode = 0;
			if (e instanceof HttpResponseException) {
				statusCode = ((HttpResponseException) e).getStatusCode();
				if (statusCode != 200) {
					try {
						setModelData();
						Log.v(TAG, this.usedMethodName + "->onSuccessJsonArray->pauseSuccess");
						resetReplaceFlagment();
						Message msg = new Message();
						msg.arg1 = Constant.RESULT_FAILED;
						msg.arg2 = statusCode;
						ArrayList<HashMap<String, Object>> response = createArrayFromJSONArray(errorResponse);
						if (response.size() > 0) {
							msg.obj = response.get(0);
						} else {
							msg.obj = new HashMap<String, Object>();
						}
						returnMainTheread(msg);

					} catch (JSONException e1) {
						// TODO Auto-generated catch block
						e1.printStackTrace();
					}
				}
			} else {
				try {
					Message msg = new Message();
					msg.arg1 = Constant.RESULT_FAILED;
					msg.arg2 = statusCode;
					msg.obj = createMapFromJSONObject(null);
					returnMainTheread(msg);

				} catch (JSONException e1) {
					// TODO Auto-generated catch block
					e1.printStackTrace();
				}
			}

			String error = e.toString();
			Log.d(TAG + " error", error);
		}

		@Override
		public void onFailure(Throwable e, String errorResponse) {
			int statusCode = 0;
			if (e instanceof HttpResponseException) {
				statusCode = ((HttpResponseException) e).getStatusCode();
				if (statusCode != 200) {
					HashMap<String, Object> resMap = new HashMap<String, Object>();
					setModelData();
					resetReplaceFlagment();
					resMap.put("error", errorResponse);
					Message msg = new Message();
					msg.arg1 = Constant.RESULT_FAILED;
					msg.arg2 = statusCode;
					msg.obj = resMap;
					returnMainTheread(msg);

				}
			} else {
				try {
					Message msg = new Message();
					msg.arg1 = Constant.RESULT_FAILED;
					msg.arg2 = statusCode;
					msg.obj = createMapFromJSONObject(null);
					returnMainTheread(msg);
				} catch (JSONException e1) {
					// TODO Auto-generated catch block
					e1.printStackTrace();
				}
			}
			Log.d(TAG + " error", errorResponse);
		}
	}

	/**
	 * @return 検索件数
	 */
	public int getRecordCount() {
		return this.recordCount;
	}
}