package com.unicorn.billing;

import android.util.Log;

/**
 * アプリケーションが管理する商品の種類
 * 
 * <p>
 * {@link ItemType}とは違う
 * </p>
 * 
 * @author c1718
 *
 */
public enum ProductType {

	/** 消費型 */
	CONSUMABLE("consumable"),
	/** 非消費型 */
	NON_CONSUMABLE("non_consumable"),
	/** 定期購読 */
	SUBS("subs");

	private String key;

	private ProductType(String key) {
		this.key = key;
	}

	/**
	 * @return アプリケーション内で定義された文字列
	 */
	public String getKey() {
		return key;
	}

	/**
	 * アプリケーション内で定義された文字列から該当するProductTypeインスタンスを取得する
	 * 
	 * @param key アプリケーション内で定義された文字列
	 * @return 商品の種類
	 */
	public static ProductType find(String key) {
		for (ProductType type : values()) {
			if (type.getKey().equals(key)) {
				return type;
			}
		}
		// 不明な種類の場合、各処理の振る舞いを決定することができない(どう扱っていいか分からない)のでRuntimeExceptionとする
		Log.e("ProductType", "Not found ProductType. key:" + key);
		throw new IllegalArgumentException("Not found ProductType. key:" + key);
	}

	@Override
	public String toString() {
		return "ProductType{key:" + key + "}";
	}

}
