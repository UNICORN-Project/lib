package com.unicorn.billing;

/**
 * レシート送信結果通知するリスナー
 */
public interface SendReceiptListener {

	/**
	 * レシート送信に成功したときに呼び出される
	 */
	public void onSuccess(Purchase purchase);

	/**
	 * レシート送信に失敗したときに呼び出される
	 */
	public void onFailure(IabError error);
}
