package com.unicorn.billing;

import android.util.Log;

/**
 * 課金APIが管理するアイテムの種類
 * 
 * <p>
 * {@link ProductType}とは違う
 * </p>
 * 
 * @author c1718
 *
 */
public enum ItemType {

	/** アプリ内課金 */
	INAPP("inapp"),
	/** 定期購読 */
	SUBS("subs");

	private String key;

	private ItemType(String key) {
		this.key = key;
	}

	/**
	 * @return 課金APIで定義された文字列
	 */
	public String getKey() {
		return key;
	}

	/**
	 * 課金APIで定義された文字列から該当するItemTypeインスタンスを取得する
	 * 
	 * @param key 課金APIで定義された文字列
	 * @return アイテムの種類
	 */
	public static ItemType find(String key) {
		for (ItemType type : values()) {
			if (type.getKey().equals(key)) {
				return type;
			}
		}
		// 不明な種類の場合、各処理の振る舞いが決定できないのでRuntimeExceptionにする
		Log.e("ItemType", "Not found ItemType. key:" + key);
		throw new IllegalArgumentException("Not found ItemType. key:" + key);
	}
}
