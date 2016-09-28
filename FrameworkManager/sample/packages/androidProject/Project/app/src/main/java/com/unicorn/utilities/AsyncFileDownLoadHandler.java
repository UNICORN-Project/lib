package com.unicorn.utilities;

import android.os.Handler;
import android.os.Message;

/**
 * 非同期でファイルをダウンロードしたときの結果を受け取るハンドラ
 * 
 * @author c1718
 *
 */
public class AsyncFileDownLoadHandler extends Handler {

	private final static int COMPLETE = 0;
	private final static int FAIL = 1;

	@Override
	public void handleMessage(Message msg) {
		if (msg.arg1 == COMPLETE) {
			completed();
		} else {
			failed();
		}
	}

	/**
	 * ダウンロード完了を通知する
	 */
	protected void sendComplete() {
		Message msg = new Message();
		msg.arg1 = COMPLETE;
		sendMessage(msg);
	}

	/**
	 * ダウンロード失敗を通知する
	 */
	protected void sendFail() {
		Message msg = new Message();
		msg.arg1 = FAIL;
		sendMessage(msg);
	}

	/**
	 * ダウンロードが完了したときに呼ばれる
	 * overrideして使用する
	 */
	public void completed() {

	}

	/**
	 * ダウンロードが失敗したときに呼ばれる
	 * overrideして使用する
	 */
	public void failed() {

	}

}
