package com.unicorn.billing;

/**
 * 単一の購入情報を消費した結果を通知するリスナー
 * 
 * @author c1718
 *
 */
public interface ConsumeListener {

	/**
	 * 消費に成功したときに呼び出される
	 * 
	 * @param purchase 消費できた購入情報
	 */
	public void onSuccess(Purchase purchase);

	/**
	 * 消費に失敗したときに呼び出される
	 * 
	 * @param error エラー
	 * @param purchase 消費できなかった購入情報
	 */
	public void onFailure(IabError error, Purchase purchase);
}
