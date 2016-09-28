package com.unicorn.billing;

import android.app.PendingIntent;

/**
 * 注文(購入フロー開始)レスポンス
 * 
 * @author c1718
 *
 */
public class OrderResponse extends Response {

	private PendingIntent orderIntent;

	/**
	 * @param status ステータス
	 */
	public OrderResponse(ResponseStatus status) {
		super(status);
	}

	/**
	 * 購入フローを起動するIntentを取得する
	 * 
	 * @return 購入フローを起動するIntent
	 */
	public PendingIntent getOrderIntent() {
		return orderIntent;
	}

	/**
	 * 購入フローを起動するIntentを設定する
	 * 
	 * @param orderIntent 購入フローを起動するIntent
	 */
	public void setOrderIntent(PendingIntent orderIntent) {
		this.orderIntent = orderIntent;
	}

	@Override
	public String toString() {
		if (orderIntent == null) {
			return getStatus().toString();
		}
		return getStatus().toString() + orderIntent.toString();
	}
}
