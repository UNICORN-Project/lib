package com.unicorn.billing;

import org.json.JSONException;
import org.json.JSONObject;

/**
 * 購入情報
 * 
 * @author c1718
 *
 */
public class PurchaseBase {
	private String orderId;
	private String packageName;
	private String productId;
	private long purchaseTime;
	private int purchaseState;
	private String developerPayload;
	private String purchaseToken;
	private String jsonPurchase;
	private String signature;

	/**
	 * @param jsonPurchaseInfo 購入情報のjson文字列
	 * @param signature シグニチャー
	 * @throws JSONException
	 */
	public PurchaseBase(String jsonPurchaseInfo, String signature) throws JSONException {
		this.jsonPurchase = jsonPurchaseInfo;
		JSONObject o = new JSONObject(jsonPurchaseInfo);
		this.orderId = o.optString("orderId");
		this.packageName = o.optString("packageName");
		this.productId = o.optString("productId");
		this.purchaseTime = o.optLong("purchaseTime");
		this.purchaseState = o.optInt("purchaseState");
		this.developerPayload = o.optString("developerPayload");
		this.purchaseToken = o.optString("purchaseToken");
		this.signature = signature;
	}

	/**
	 * @return 注文ID
	 */
	public String getOrderId() {
		return orderId;
	}

	/**
	 * @return パッケージ名
	 */
	public String getPackageName() {
		return packageName;
	}

	/**
	 * @return 商品コード
	 */
	public String getProductId() {
		return productId;
	}

	/**
	 * @return 購入時間(エポック[1970/1/1]からのミリ秒)
	 */
	public long getPurchaseTime() {
		return purchaseTime;
	}

	/**
	 * @see PurchaseState
	 * @return 購入ステータス
	 */
	protected int getPurchaseState() {
		return purchaseState;
	}

	/**
	 * @return 開発者用付加情報
	 */
	protected String getDeveloperPayload() {
		return developerPayload;
	}

	/**
	 * @return トークン
	 */
	public String getPurchaseToken() {
		return purchaseToken;
	}

	/**
	 * @return シグニチャー
	 */
	public String getSignature() {
		return signature;
	}

	/**
	 * @return 購入情報のjson文字列(signatureは含まれていません)
	 */
	public String getJsonPurchase() {
		return jsonPurchase;
	}

	@Override
	public String toString() {
		return "PurchaseBase:" + jsonPurchase + signature;
	}
}
