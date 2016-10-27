package com.unicorn.billing;

import java.util.List;

/**
 * 一度に複数の購入情報を消費した結果を通知するリスナー
 * 
 * @author c1718
 *
 */
public interface ConsumeMultiListener {

	/**
	 * すべての消費に成功したときに呼び出される
	 * 
	 * @param successPurchases 消費できた購入情報
	 */
	public void onSuccessAll(List<Purchase> successPurchases);

	/**
	 * 消費に失敗した購入情報がひとつでも存在したときに呼び出される
	 * 
	 * @param successPurchases 消費できた購入情報
	 * @param errors 消費できなかったエラー
	 * @param failurePurchase 消費できなかった購入情報
	 */
	public void onFailureExist(List<Purchase> successPurchases, List<IabError> errors,
			List<Purchase> failurePurchase);

}
