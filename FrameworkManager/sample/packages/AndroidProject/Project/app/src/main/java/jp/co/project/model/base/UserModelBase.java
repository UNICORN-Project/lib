package jp.co.project.model.base;

import java.util.HashMap;

import android.content.Context;

import com.unicorn.model.ModelBase;

/**
 * UserModelはUserテーブルのデータを参照、保存するためのクラスです
 * 
 * @author　c1363
 */
public class UserModelBase extends ModelBase {

	/** 名前 */
	private String uniq_name;
	private boolean uniq_name_replaced;
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
	 * コンストラクタです
	 * ModelBaseのコンストラクタを呼び出しmodelNameに「user」をセットします
	 * 
	 * @param argContext Contextが入っています
	 */
	public UserModelBase(Context argContext) {
		super(argContext);
		modelName = "user";
	}

	/**
	 * オーバーロードされたコンストラクタです
	 * ModelBaseのコンストラクタを呼び出しmodelNameに「user」をセットします
	 * 
	 * @param argContext Contextが入っています
	 * @param argProtocol プロトコルが入っています
	 * @param argDomain ドメインが入っています
	 * @param argURLBase ドメイン以下のディレクトリ名が入っています
	 * @param argTokenKeyName Cookieに保存するトークンのkey名が入っています
	 */
	public UserModelBase(Context argContext, String argProtocol, String argDomain,
			String argURLBase, String argTokenKeyName) {
		super(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName);
		modelName = "user";
	}

	/**
	 * オーバーロードされたコンストラクタです
	 * ModelBaseのコンストラクタを呼び出しmodelNameに「user」をセットします
	 * 
	 * @param argContext Contextが入っています
	 * @param argProtocol プロトコルが入っています
	 * @param argDomain ドメインが入っています
	 * @param argURLBase ドメイン以下のディレクトリ名が入っています
	 * @param argTokenKeyName Cookieに保存するトークンのkey名が入っています
	 * @param argTimeout Timeoutまでの時間が入っています
	 */
	public UserModelBase(Context argContext, String argProtocol, String argDomain,
			String argURLBase, String argTokenKeyName, int argTimeout) {
		super(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName, argTimeout);
		modelName = "user";
	}

	/**
	 * オーバーロードされたコンストラクタです
	 * ModelBaseのコンストラクタを呼び出しmodelNameに「user」をセットします
	 * 
	 * @param argContext Contextが入っています
	 * @param argProtocol プロトコルが入っています
	 * @param argDomain ドメインが入っています
	 * @param argURLBase ドメイン以下のディレクトリ名が入っています
	 * @param argTokenKeyName Cookieに保存するトークンのkey名が入っています
	 * @param argCryptKey トークンの暗号化に使うKEYが入っています
	 * @param argCryptIV トークンの暗号化に使うIVが入っています
	 */
	public UserModelBase(Context argContext, String argProtocol, String argDomain,
			String argURLBase, String argTokenKeyName, String argCryptKey, String argCryptIV) {
		super(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName, argCryptKey,
				argCryptIV);
		modelName = "user";
	}

	/**
	 * オーバーロードされたコンストラクタです
	 * ModelBaseのコンストラクタを呼び出しmodelNameに「user」をセットします
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
	public UserModelBase(Context argContext, String argProtocol, String argDomain,
			String argURLBase, String argTokenKeyName, String argCryptKey, String argCryptIV,
			int argTimeout) {
		super(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName, argCryptKey,
				argCryptIV, argTimeout);
		modelName = "user";
	}

	@Override
	protected void _setModelData(HashMap<String, Object> map) {
		this.ID = String.valueOf(map.get("id"));
		this.uniq_name = String.valueOf(map.get("uniq_name"));
		this.created = String.valueOf(map.get("created"));
		this.modified = String.valueOf(map.get("modified"));
		this.available = String.valueOf(map.get("available"));
	}

	@Override
	public HashMap<String, Object> convertModelData() {
		HashMap<String, Object> newMap = new HashMap<String, Object>();
		newMap.put("uniq_name", this.uniq_name);
		newMap.put("created", this.created);
		newMap.put("modified", this.modified);
		newMap.put("available", this.available);
		return newMap;
	}

	@Override
	protected void resetReplaceFlagment() {
		// 忘れずに
		this.replaced = false;

		this.uniq_name_replaced = false;
		this.created_replaced = false;
		this.modified_replaced = false;
		this.available_replaced = false;
	}

	@Override
	public boolean save() {
		HashMap<String, Object> argsaveParams = new HashMap<String, Object>();
		if (this.replaced) {
			if (this.uniq_name_replaced){
				argsaveParams.put("uniq_name", this.uniq_name);
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
		}
		return super.save(argsaveParams);
	}

	/**
	 * @return 作成日時
	 */
	public String getUniq_name() {
		return uniq_name;
	}

	/**
	 * @param uniq_name 作成日時
	 */
	public void setUniq_name(String uniq_name) {
		this.uniq_name = created;
		this.uniq_name_replaced = true;
		this.replaced = true;
	}


	/**
	 * @return 作成日時
	 */
	public String getCreated() {
		return created;
	}

	/**
	 * @param created 作成日時
	 */
	public void setCreated(String created) {
		this.created = created;
		this.created_replaced = true;
		this.replaced = true;
	}

	/**
	 * @return 更新日時
	 */
	public String getModified() {
		return modified;
	}

	/**
	 * @param modified 更新日時
	 */
	public void setModified(String modified) {
		this.modified = modified;
		this.modified_replaced = true;
		this.replaced = true;
	}

	/**
	 * @return 有効・無効
	 */
	public String getAvailable() {
		return available;
	}

	/**
	 * @param available 有効・無効
	 */
	public void setAvailable(String available) {
		this.available = available;
		this.available_replaced = true;
		this.replaced = true;
	}

}