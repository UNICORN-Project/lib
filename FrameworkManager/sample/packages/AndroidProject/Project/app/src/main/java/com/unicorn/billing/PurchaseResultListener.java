package com.unicorn.billing;

/**
 * 購入結果を通知するリスナー
 * 
 * @author c1718
 *
 */
public interface PurchaseResultListener {

	/**
	 * 購入に成功したときに呼び出される
	 * 
	 * @param purchase 購入情報
	 */
	public void onSuccess(Purchase purchase);

	/**
	 * 購入に失敗したときに呼び出される
	 * 
	 * @param error エラー
	 */
	public void onFailure(IabError error);

	/**
	 * 購入をキャンセルされたときに呼び出される
	 */
	public void onCanceled();
}
