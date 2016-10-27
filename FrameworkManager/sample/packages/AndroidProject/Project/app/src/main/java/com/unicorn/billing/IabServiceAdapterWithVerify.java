package com.unicorn.billing;

/**
 * アプリ内で購入情報の検証を行う
 * 
 * @author c1718
 *
 */
public class IabServiceAdapterWithVerify extends IabServiceAdapter {

	private String base64PublicKey;

	/**
	 * @param apiVersion APIのバージョン
	 * @param packageName パッケージ名
	 * @param base64PublicKey 公開鍵
	 */
	public IabServiceAdapterWithVerify(int apiVersion, String packageName, String base64PublicKey) {
		super(apiVersion, packageName);
		this.base64PublicKey = base64PublicKey;
	}

	@Override
	protected boolean verifyPurchase(String jsonPurchaseInfo, String signature) {
		if (!Security.verifyPurchase(this.base64PublicKey, jsonPurchaseInfo, signature)) {
			logWarn("Purchase signature verification **FAILED**.");
			logDebug("   Purchase data: " + jsonPurchaseInfo);
			logDebug("   Signature: " + signature);
			return false;
		}
		return true;
	}

}
