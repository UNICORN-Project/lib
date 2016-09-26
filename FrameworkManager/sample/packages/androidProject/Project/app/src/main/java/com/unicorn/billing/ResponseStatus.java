package com.unicorn.billing;

import android.util.Log;

/**
 * 課金APIで定義されたレスポンスステータス
 * 
 * @author c1718
 *
 */
public enum ResponseStatus {
	/** 成功 */
	OK(0),
	/** ユーザーが戻るを押したり、ダイアログでキャンセルを選択した */
	USER_CANCELED(1),
	/** ネットワークがダウンしている */
	SERVICE_UNAVAILABLE(2),
	/** 課金APIバージョンが要求されたアイテムの種類をサポートしていない {@link ItemType}を参照 */
	BILLING_UNAVAILABLE(3),
	/** 要求されたアイテムが購入できない */
	ITEM_UNAVAILABLE(4),
	/** APIに無効な引数が渡された。アプリケーションが正しく署名されていない、またはGooglePlayのアプリ内課金の設定が正しくない、 もしくはマニフェストに権限が設定されていない */
	DEVELOPER_ERROR(5),
	/** API動作中に致命的なエラーが発生した */
	ERROR(6),
	/** すでにアイテムを所有しているので購入できない */
	ITEM_ALREADY_OWNED(7),
	/** アイテムを所有していないので消費できない */
	ITEM_NOT_OWNED(8),
	/** 課金APIに定義されていない不明なステータス */
	UNKNOWN(99);

	private int code;

	private ResponseStatus(int code) {
		this.code = code;
	}

	/**
	 * @return コード
	 */
	public int getCode() {
		return code;
	}

	public static ResponseStatus find(int code) {
		for (ResponseStatus result : values()) {
			if (result.getCode() == code) {
				return result;
			}
		}
		// 課金APIの仕様変更でステータスが追加変更される可能性を考慮するとRuntimeExceptionではなく不明扱いとし、warningを出す
		Log.w("ResponseStatus", "Not exist ResponseStatus. code:" + code);
		return ResponseStatus.UNKNOWN;
	}
}
