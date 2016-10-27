package com.unicorn.billing;

/**
 * 課金エラーの種類
 * 
 * @author c1718
 *
 */
public enum IabErrorType {
	/** 不明な結果コード */
	UNKOWN_RESULT_CODE(100),
	/** OK以外のレスポンス */
	RESPONSE_STATUS_NG(200),
	/** 不正なレスポンス(想定外の形式) */
	BAD_RESPONSE(300),
	/** リモートエラー */
	REMOTE_ERROR(400),
	/** 課金サービスが存在しない */
	NOT_EXIST_IAB_SERVICE(500),
	/** 課金サービスがサポートされていない */
	NOT_SUPPORTED(600),
	/**  */
	SEND_INTENT_ERROR(700);

	private int code;

	private IabErrorType(int code) {
		this.code = code;
	}

	/**
	 * @return コード
	 */
	public int getCode() {
		return code;
	}
}
