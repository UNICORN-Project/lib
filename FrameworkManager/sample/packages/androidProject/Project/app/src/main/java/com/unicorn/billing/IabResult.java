package com.unicorn.billing;

/**
 * 課金結果
 * 
 * @author c1718
 *
 */
public class IabResult {

	private boolean isSuccess;
	private IabError error = null;

	/**
	 * 成功のとき使用する
	 */
	public IabResult() {
		this.isSuccess = true;
	}

	/**
	 * 失敗のとき使用する
	 * 
	 * @param error エラー
	 */
	public IabResult(IabError error) {
		this.isSuccess = false;
		this.error = error;
	}

	/**
	 * @return 成功の場合は{@code true}
	 */
	public boolean isSuccess() {
		return isSuccess;
	}

	/**
	 * @return エラー(成功の場合は{@code null})
	 */
	public IabError getError() {
		return error;
	}
}
