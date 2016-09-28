package com.unicorn.billing;

import android.util.Log;

/**
 * ログ出力を補助する
 * 
 * @author c1718
 *
 */
public abstract class LogSupport {

	private boolean debugLog = false;

	/**
	 * デバッグログの出力有無を切り替える
	 * 
	 * @param b {@code true}の場合はデバッグログを出力する
	 */
	public void changeDebugPrint(boolean b) {
		debugLog = b;
	}

	protected void logDebug(String msg) {
		if (debugLog) {
			Log.d(getClass().getName(), msg);
		}
	}

	protected void logWarn(String msg) {
		Log.w(getClass().getName(), msg);
	}

	protected void logWarn(String msg, Throwable e) {
		Log.w(getClass().getName(), msg, e);
	}

	protected void logError(String msg) {
		Log.e(getClass().getName(), msg);
	}

	protected void logError(String msg, Throwable e) {
		Log.e(getClass().getName(), msg, e);
	}
}
