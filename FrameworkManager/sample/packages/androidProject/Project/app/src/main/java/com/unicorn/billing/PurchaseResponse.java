package com.unicorn.billing;

import java.util.ArrayList;
import java.util.List;

/**
 * 購入情報のレスポンス
 * 
 * @author c1718
 *
 */
public class PurchaseResponse extends Response {

	private List<Purchase> purchases = new ArrayList<Purchase>();
	private List<String> productIds = new ArrayList<String>();

	/**
	 * @param status ステータス
	 */
	public PurchaseResponse(ResponseStatus status) {
		super(status);
	}

	/**
	 * 購入情報を追加する
	 * 
	 * @param purchase 購入情報
	 */
	public void addPurchase(Purchase purchase) {
		this.purchases.add(purchase);
		// アイテム情報を取得するとき使うために保持する
		this.productIds.add(purchase.getProductId());
	}

	/**
	 * すべての購入情報を取得する
	 * 
	 * @return 購入情報
	 */
	public List<Purchase> getPurchases() {
		return purchases;
	}

	/**
	 * すべての購入情報内の商品IDのリストを取得する
	 * 
	 * <p>
	 * アイテム情報を付加するために用意したメソッド
	 * </p>
	 * 
	 * @return 商品IDのリスト
	 */
	public List<String> getProductIds() {
		return productIds;
	}

	/**
	 * 購入情報を1件でも所有しているか調べる
	 * 
	 * @return 1件でも所有している場合は{@code true}
	 */
	public boolean hasPurchases() {
		if (purchases == null) {
			return false;
		}
		return !purchases.isEmpty();
	}

	@Override
	public String toString() {
		if (purchases == null) {
			getStatus().toString();
		}
		return getStatus().toString() + purchases.toString();
	}
}
