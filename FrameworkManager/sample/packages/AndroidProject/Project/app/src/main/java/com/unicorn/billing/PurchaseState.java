package com.unicorn.billing;

/**
 * 課金APIで定義された購入ステータス
 * 
 * @author c1718
 *
 */
public enum PurchaseState {

	/** 購入 */
	PURCHASED(0),
	/** キャンセル */
	CANCELED(1),
	/** 払い戻し */
	REFUNDED(2);

	private int code;

	private PurchaseState(int code) {
		this.code = code;
	}

	/**
	 * @return コード
	 */
	public int getCode() {
		return code;
	}

	/**
	 * 課金APIで定義された数値から該当するPurchaseStateインスタンスを取得する
	 * 
	 * @param code コード
	 * @return 購入ステータス
	 */
	public static PurchaseState find(int code) {
		for (PurchaseState state : values()) {
			if (state.getCode() == code) {
				return state;
			}
		}
		// 不明の場合、処理できないので例外
		throw new IllegalArgumentException("Not found PurchaseState. code:" + code);
	}
}
