package com.unicorn.billing;

import java.util.List;

/**
 * 所有している購入情報取得の結果を通知するリスナー
 * 
 * @author c1718
 *
 */
public interface InventoryListener {

	/**
	 * 所有している購入情報の取得に成功したときに呼び出される
	 * 
	 * @param purchases 所有している購入情報のリスト
	 */
	public void onSuccess(List<Purchase> purchases);

	/**
	 * 所有している購入情報の取得に失敗したときに呼び出される
	 * 
	 * @param error エラー
	 */
	public void onFailure(IabError error);
}
