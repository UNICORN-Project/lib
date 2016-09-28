package com.unicorn.utilities;

import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.UnsupportedEncodingException;

import android.content.Context;
import android.os.Message;

import com.loopj.android.http.AsyncHttpResponseHandler;

/**
 * 非同期でファイルをダウンロードする
 * 
 * @author c1718
 * 
 */
public class AsyncFileDownLoader {

	private Context context;
	private AsyncFileDownLoadHandler handler;

	/**
	 * ダウンロード結果の通知を受け取る必要がないときに使用する
	 * 
	 * @param context コンテキスト
	 */
	public AsyncFileDownLoader(Context context) {
		this(context, null);
	}

	/**
	 * ダウンロード結果の通知を受け取る必要があるときに使用する
	 * 
	 * @param context コンテキスト
	 * @param handler ダウンロード結果の通知を受け取るためのハンドラ
	 */
	public AsyncFileDownLoader(Context context, AsyncFileDownLoadHandler handler) {
		this.context = context;
		this.handler = handler;
	}

	public static void download(Context context, String url, String[] contentTypes,
			final String filename) {
		AsyncFileDownLoader.download(context, url, contentTypes, filename, null);
	}

	/**
	 * ファイルをダウンロードする
	 * 
	 * @param context コンテキスト
	 * @param handler ダウンロード結果の通知を受け取るためのハンドラ
	 * @param url ダウンロードするファイルが存在するURL
	 * @param contentTypes コンテントタイプ
	 * @param filename 保存ファイル名
	 */
	public static void download(Context context, String url, String[] contentTypes,
			final String filename, AsyncFileDownLoadHandler handler) {
		AsyncFileDownLoader downloader = new AsyncFileDownLoader(context,handler);
		downloader.download(url, contentTypes, filename);
	}

	/**
	 * ファイルをダウンロードする
	 * 
	 * @param url ダウンロードするファイルが存在するURL
	 * @param contentTypes コンテントタイプ
	 * @param filename 保存ファイル名
	 */
	public void download(String url, String[] contentTypes, final String filename) {
		AsyncHttpResponseHandler responseHandler = new AsyncHttpResponseHandler() {

			protected void handleMessage(Message msg) {
				Object[] response;

				switch (msg.what) {
				case SUCCESS_MESSAGE:
					response = (Object[]) msg.obj;
					onSuccess(((Integer) response[0]).intValue(), (String) response[1]);
					break;
				case FAILURE_MESSAGE:
					response = (Object[]) msg.obj;
					onFailure((Throwable) response[0]);
					break;
				case START_MESSAGE:
					onStart();
					break;
				case FINISH_MESSAGE:
					onFinish();
					break;
				}
			}

			public void onSuccess(int statusCode, String content) {
				Log.d("AsyncFileDownLoader-file download onSuccess");
				try {
					byte[] strByte = content.getBytes("UTF-8");
					write(strByte, filename);
				} catch (UnsupportedEncodingException e) {
					Log.d(e.getMessage());
					sendFail();
				}

			}

			public void onFailure(Throwable error) {
				Log.d("AsyncFileDownLoader-file download onFailure");
				sendFail();
			}
		};
		// 取りに行く
		AsyncHttpClientAgent.get(context, url, null, responseHandler);
	}

	/**
	 * ダウンロードファイルのパスを取得する
	 * 
	 * @param filename ファイル名
	 * @return ダウンロードファイルのフルパス
	 */
	public String getDownloadPath(String filename) {
		return "file://" + context.getFilesDir().getPath() + "/" + filename;
	}

	/**
	 * ダウンロードファイルのパスを取得する
	 * 
	 * @param filename ファイル名
	 * @return ダウンロードファイルのフルパス
	 */
	public static String getDownloadPath(Context _context, String filename) {
		return "file://" + _context.getFilesDir().getPath() + "/" + filename;
	}

	/**
	 * 書き込む
	 * 
	 * @param bytes バイト
	 * @param filename 保存するファイル名
	 */
	private void write(byte[] bytes, String filename) {
		FileOutputStream stream;
		try {
			stream = context.openFileOutput(filename, Context.MODE_PRIVATE);
		} catch (FileNotFoundException e) {
			Log.e(getClass().toString(), "filename:" + filename, e);
			// 書き込み失敗
			sendFail();
			return;
		} finally {
			// 開けなかったからcloseしなくてOK
		}
		AsyncFileWriteTask writer = new AsyncFileWriteTask(stream, bytes) {

			@Override
			protected void onPostExecute(Boolean result) {
				if (result) {
					Log.d("AsyncFileDownLoader-AsyncFileWriteTask Success");
					// 書き込み成功
					sendComplete();
				} else {
					Log.d("AsyncFileDownLoader-AsyncFileWriteTask Failure");
					// 書き込み失敗
					sendFail();
				}
			}

		};
		writer.execute();
	}

	private void sendComplete() {
		if (handler != null) {
			handler.sendComplete();
		}
	}

	private void sendFail() {
		if (handler != null) {
			handler.sendFail();
		}
	}
}
