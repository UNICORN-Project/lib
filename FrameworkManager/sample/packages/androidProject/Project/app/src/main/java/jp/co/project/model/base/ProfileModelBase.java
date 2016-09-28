package jp.co.project.model.base;

import java.io.ByteArrayOutputStream;
import java.util.HashMap;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.os.Handler;

import com.unicorn.model.ModelBase;
import com.unicorn.utilities.Base64;
import com.unicorn.utilities.Log;

/**
 * プロフィール情報を扱う
 *
 * @author c1718
 */
public class ProfileModelBase extends ModelBase {

	public static String TAG = "ProfileModel";
	/**
	 * ネーム
	 */
	private String name;
	private boolean name_replaced;
	/**
	 * イメージ
	 */
	private String image;
	private boolean image_replaced;
	/**
	 * 作成日時
	 */
	private String created;
	private boolean created_replaced;
	/**
	 * 更新日時
	 */
	private String modified;
	private boolean modified_replaced;
	/**
	 * 有効・無効
	 */
	private String available;
	private boolean available_replaced;

	/**
	 * @param argContext      コンテキスト
	 * @param argProtocol     プロトコル
	 * @param argDomain       ドメイン
	 * @param argURLBase      ドメイン以下のディレクトリ名
	 * @param argTokenKeyName Cookieに保存するトークンのキー名
	 * @param argTimeout      タイムアウトの時間
	 */
	public ProfileModelBase(Context argContext, String argProtocol, String argDomain,
							String argURLBase, String argTokenKeyName, int argTimeout) {
		super(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName, argTimeout);
		modelName = "profile";
	}

	/**
	 * @param argContext      コンテキスト
	 * @param argProtocol     プロトコル
	 * @param argDomain       ドメイン
	 * @param argURLBase      ドメイン以下のディレクトリ名
	 * @param argTokenKeyName Cookieに保存するトークンのキー名
	 * @param argCryptKey     暗号化キー
	 * @param argCryptIV      初期化ベクトル
	 * @param argTimeout      タイムアウトの時間
	 */
	public ProfileModelBase(Context argContext, String argProtocol, String argDomain,
							String argURLBase, String argTokenKeyName, String argCryptKey, String argCryptIV,
							int argTimeout) {
		super(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName, argCryptKey,
				argCryptIV, argTimeout);
		modelName = "profile";
	}

	/**
	 * @param argContext      コンテキスト
	 * @param argProtocol     プロトコル
	 * @param argDomain       ドメイン
	 * @param argURLBase      ドメイン以下のディレクトリ名
	 * @param argTokenKeyName Cookieに保存するトークンのキー名
	 * @param argCryptKey     暗号化キー
	 * @param argCryptIV      初期化ベクトル
	 */
	public ProfileModelBase(Context argContext, String argProtocol, String argDomain,
							String argURLBase, String argTokenKeyName, String argCryptKey, String argCryptIV) {
		super(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName, argCryptKey,
				argCryptIV);
		modelName = "profile";
	}

	/**
	 * @param argContext      コンテキスト
	 * @param argProtocol     プロトコル
	 * @param argDomain       ドメイン
	 * @param argURLBase      ドメイン以下のディレクトリ名
	 * @param argTokenKeyName Cookieに保存するトークンのキー名
	 */
	public ProfileModelBase(Context argContext, String argProtocol, String argDomain,
							String argURLBase, String argTokenKeyName) {
		super(argContext, argProtocol, argDomain, argURLBase, argTokenKeyName);
		modelName = "profile";
	}

	/**
	 * @param argContext コンテキスト
	 */
	public ProfileModelBase(Context argContext) {
		super(argContext);
		modelName = "profile";
	}

	@Override
	public void resetReplaceFlagment() {
		// replacedは親クラスでやった方がいいかも
		this.replaced = false;

		this.name_replaced = false;
		this.image_replaced = false;
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
		if (this.name_replaced) {
			argsaveParams.put("name", this.name);
		}
		if (this.image_replaced) {
			argsaveParams.put("image", this.image);
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
		this.name = String.valueOf(map.get("name"));
		this.image = String.valueOf(map.get("image"));
		this.created = String.valueOf(map.get("created"));
		this.modified = String.valueOf(map.get("modified"));
		this.available = String.valueOf(map.get("available"));
	}

	@Override
	public HashMap<String, Object> convertModelData() {
		HashMap<String, Object> newMap = new HashMap<String, Object>();
		newMap.put("id", ID);
		newMap.put("name", this.name);
		newMap.put("image", this.image);
		newMap.put("created", this.created);
		newMap.put("modified", this.modified);
		newMap.put("available", this.available);
		return newMap;
	}

	/**
	 * @return ネーム
	 */
	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
		this.name_replaced = true;
		this.replaced = true;
	}

	public void setImage(String image) {
		this.image = image;
	}

	public String getImage() {
		return this.image;
	}

	public void setImageBitmap(Bitmap bitmap) {
		ByteArrayOutputStream baos = new ByteArrayOutputStream();
		bitmap.compress(Bitmap.CompressFormat.JPEG, 100, baos);
		byte[] b = baos.toByteArray();

		try {
			this.image = Base64.encode(b);
			this.image_replaced = true;
			this.replaced = true;
		} catch (Exception e) {
			e.printStackTrace();
		}
	}

	public Bitmap getImageBitmap() {
		Bitmap bmp = null;
		if (image != null && image.length() != 0) {
			try {
				byte[] b = Base64.decode(image);
				if (b != null) {
					bmp = BitmapFactory.decodeByteArray(b, 0, b.length);
				}
			} catch (Exception e) {
				e.printStackTrace();
			}
		}
		return bmp;
	}

	public boolean list(Handler argCompletionHandler, int offset, int limit) {
		completionHandler = argCompletionHandler;
		HashMap<String, Object> argWhereParams = new HashMap<String, Object>();
		argWhereParams.put("OFFSET", String.valueOf(offset));
		argWhereParams.put("LIMIT", String.valueOf(limit));
		return load(loadResourceMode.listedResource, argWhereParams);
	}

}
