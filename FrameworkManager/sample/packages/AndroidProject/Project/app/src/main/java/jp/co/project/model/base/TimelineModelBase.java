package jp.co.project.model.base;

import android.content.Context;
import android.os.Handler;
import android.provider.ContactsContract;

import com.unicorn.manager.DataManager;
import com.unicorn.model.ModelBase;

import java.util.HashMap;

/**
 * プロフィール情報を扱う
 * 
 * @author c1718
 * 
 */
public class TimelineModelBase extends ModelBase {

	public static String TAG = "TimelineModel";
	/** ネーム */
	private String text;
	private boolean text_replaced;
	/** 作成日時 */
	private String created;
	private boolean created_replaced;
	/** 更新日時 */
	private String modified;
	private boolean modified_replaced;
	/** 有効・無効 */
	private String available;
	private boolean available_replaced;

	/**
	 *
	 * @param argContext コンテキスト
	 * @param argProtocol プロトコル
	 * @param argDomain ドメイン
	 * @param argURLBase ドメイン以下のディレクトリ名
	 * @param argTokenKeyName Cookieに保存するトークンのキー名
	 * @param argTimeout タイムアウトの時間
	 */
	public TimelineModelBase(Context argContext, String argProtocol, String argDomain,
							 String argURLBase, String argTokenKeyName, int argTimeout) {
		super(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName, argTimeout);
		modelName = "timeline";
	}

	/**
	 *
	 * @param argContext コンテキスト
	 * @param argProtocol プロトコル
	 * @param argDomain ドメイン
	 * @param argURLBase ドメイン以下のディレクトリ名
	 * @param argTokenKeyName Cookieに保存するトークンのキー名
	 * @param argCryptKey 暗号化キー
	 * @param argCryptIV 初期化ベクトル
	 * @param argTimeout タイムアウトの時間
	 */
	public TimelineModelBase(Context argContext, String argProtocol, String argDomain,
							 String argURLBase, String argTokenKeyName, String argCryptKey, String argCryptIV,
							 int argTimeout) {
		super(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName, argCryptKey,
				argCryptIV, argTimeout);
		modelName = "timeline";
	}

	/**
	 *
	 * @param argContext コンテキスト
	 * @param argProtocol プロトコル
	 * @param argDomain ドメイン
	 * @param argURLBase ドメイン以下のディレクトリ名
	 * @param argTokenKeyName Cookieに保存するトークンのキー名
	 * @param argCryptKey 暗号化キー
	 * @param argCryptIV 初期化ベクトル
	 */
	public TimelineModelBase(Context argContext, String argProtocol, String argDomain,
							 String argURLBase, String argTokenKeyName, String argCryptKey, String argCryptIV) {
		super(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName, argCryptKey,
				argCryptIV);
		modelName = "timeline";
	}

	/**
	 *
	 * @param argContext コンテキスト
	 * @param argProtocol プロトコル
	 * @param argDomain ドメイン
	 * @param argURLBase ドメイン以下のディレクトリ名
	 * @param argTokenKeyName Cookieに保存するトークンのキー名
	 */
	public TimelineModelBase(Context argContext, String argProtocol, String argDomain,
							 String argURLBase, String argTokenKeyName) {
		super(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName);
		modelName = "timeline";
	}

	/**
	 *
	 * @param argContext コンテキスト
	 */
	public TimelineModelBase(Context argContext) {
		super(argContext);
		modelName = "timeline";
	}

	@Override
	public void resetReplaceFlagment() {
		// replacedは親クラスでやった方がいいかも
		this.replaced = false;

		this.text_replaced = false;
		this.created_replaced = false;
		this.modified_replaced = false;
		this.available_replaced = false;
	}

	@Override
	public boolean save() {
		if (!this.replaced) {
			// 更新したことにする
			return true;
		}
		HashMap<String, Object> argsaveParams = new HashMap<String, Object>();
		argsaveParams.put("profile_id",DataManager.getInstance().getUserModel().ID);
		argsaveParams.put("owner_id",DataManager.getInstance().getUserModel().ID);

		if (this.text_replaced) {
			argsaveParams.put("text", this.text);
		}
		if (this.created_replaced) {
			argsaveParams.put("created", this.created);
		}
		if (this.modified_replaced) {
			argsaveParams.put("modified", this.modified);
		}
		if (this.available_replaced) {
			argsaveParams.put("available", this.available);
		}
		return super.save(argsaveParams);
	}

	public boolean save(Handler completeHandler) {
		completionHandler = completeHandler;
		return save();
	}

	@Override
	public void _setModelData(HashMap<String, Object> map) {

		if (!map.containsKey("id")) {
			// プロフィール情報が存在しないと判断
			return;
		}
		this.ID = String.valueOf(map.get("id"));
		this.text = String.valueOf(map.get("text"));
		this.created = String.valueOf(map.get("created"));
		this.modified = String.valueOf(map.get("modified"));
		this.available = String.valueOf(map.get("available"));
	}

	@Override
	public HashMap<String, Object> convertModelData() {
		HashMap<String, Object> newMap = new HashMap<String, Object>();
		newMap.put("id", ID);
		newMap.put("text", this.text);
		newMap.put("created", this.created);
		newMap.put("modified", this.modified);
		newMap.put("available", this.available);
		return newMap;
	}

	/**
	 * @return ネーム
	 */
	public String getText() {
		return text;
	}

	public void setText(String text) {
		this.text = text;
		this.text_replaced = true;
		this.replaced = true;
	}

	/**
	 * @return 更新日時
     */
	public String getModified() {
		return modified;
	}

}
