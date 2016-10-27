package com.unicorn.billing;

/**
 * 課金エラー
 * 
 * @author c1718
 *
 */
public class IabError {

	private IabErrorType errorType;
	private String message;
	private int detailCode;

	/**
	 * @param errorType エラーの種類
	 * @param message メッセージ
	 */
	public IabError(IabErrorType errorType, String message) {
		this(errorType, message, 0);
	}

	/**
	 * @param errorType エラーの種類
	 * @param message メッセージ
	 * @param status ステータス
	 */
	public IabError(IabErrorType errorType, String message, ResponseStatus status) {
		this(errorType, message, status.getCode());
	}

	/**
	 * @param errorType エラーの種類
	 * @param message メッセージ
	 * @param detailCode 詳細コード
	 */
	private IabError(IabErrorType errorType, String message, int detailCode) {
		this.errorType = errorType;
		this.message = message;
		this.detailCode = detailCode;
	}

	/**
	 * @return エラーコード
	 */
	public int getCode() {
		return errorType.getCode() + detailCode;
	}

	/**
	 * @return メッセージ
	 */
	public String getMessage() {
		return message;
	}

	@Override
	public String toString() {
		return "IabError{errorType:" + errorType.toString() + " detailCode:" + detailCode
				+ " message:" + message + "}";
	}

}
