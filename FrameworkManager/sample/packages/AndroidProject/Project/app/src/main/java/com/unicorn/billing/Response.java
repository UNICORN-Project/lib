package com.unicorn.billing;

/**
 * レスポンスの基底クラス
 * 
 * @author c1718
 *
 */
public abstract class Response {

	private ResponseStatus status;

	/**
	 * @param status ステータス
	 */
	public Response(ResponseStatus status) {
		this.status = status;
	}

	/**
	 * @return ステータス
	 */
	public ResponseStatus getStatus() {
		return status;
	}

}
