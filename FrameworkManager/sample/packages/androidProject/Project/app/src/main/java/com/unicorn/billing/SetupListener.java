package com.unicorn.billing;

/**
 * セットアップ結果を通知するリスナー
 */
public interface SetupListener {

	/**
	 * セットアップに成功したときに呼び出される
	 */
	public void onSuccess();

	/**
	 * セットアップに失敗したときに呼び出される
	 * 
	 * @param error エラー
	 */
	public void onFailure(IabError error);
}
