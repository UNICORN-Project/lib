package com.unicorn.utilities;

import java.util.concurrent.BlockingQueue;
import java.util.concurrent.LinkedBlockingQueue;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.os.Handler;
import android.os.Message;
import android.view.View;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.RelativeLayout;

/**
 * 画像ダウンローダークラス ImageViewとurlを渡すとurlから画像を取得しImageViewにsetImageされます
 * ダウンロードスレッドはキューで管理されており、最大スレッド数はTHREAD_MAX_NUMで定義されます
 * 
 * @author　c1363
 */
public class LoadBitmapManager {

	private static final int THREAD_MAX_NUM = 7;

	private static BlockingQueue<LoadBitmapItem> downloadQueue;
	private static Handler handler;
	private static Context mContext;

	static {
		/*
		 * 画像情報を貯めるためのキュー
		 */
		downloadQueue = new LinkedBlockingQueue<LoadBitmapItem>();

		/*
		 * スレッド最大数まで画像ダウンロードスレッドを作成
		 */
		for (int i = 0; i < THREAD_MAX_NUM; i++) {
			new Thread(new DownloadWorker()).start();
		}

		/*
		 * 画像ダウンロード後にメッセージを受信するハンドラーを作成
		 */
		handler = new Handler() {
			@Override
			public void handleMessage(Message msg) {
				/*
				 * 取得したメッセージから画像情報を取得
				 */
				LoadBitmapItem item = (LoadBitmapItem) msg.obj;

				/*
				 * 画像ダウンロードがうまくいっていた場合はイメージビューに設定
				 */
				if (item.getBitmap() != null) {
					if (item.getResizeIvMaxWidth() != 0 && item.getResizeIvMaxHeight() != 0) {
						float imageWidth = (float) item.getBitmap().getWidth();
						float imageHeight = (float) item.getBitmap().getHeight();
						float resizeMaxWidth = (float) item.getResizeIvMaxWidth();
						float resizeMaxHeight = (float) item.getResizeIvMaxHeight();

						if (imageWidth / resizeMaxWidth > imageHeight / resizeMaxHeight) {
							// 横幅にあわせる
							if (imageWidth > resizeMaxWidth) {
								item.getImgView()
										.setLayoutParams(
												new RelativeLayout.LayoutParams(
														(int) resizeMaxWidth,
														(int) (imageHeight * (resizeMaxWidth / imageWidth))));
							} else {

							}
						} else {
							// 縦幅に合わせる
							if (imageHeight > resizeMaxHeight) {
								item.getImgView()
										.setLayoutParams(
												new RelativeLayout.LayoutParams(
														(int) (imageWidth * (resizeMaxHeight / imageHeight)),
														(int) resizeMaxHeight));
							} else {

							}
						}

					}
					item.getImgView().setImageBitmap(item.getBitmap());
					item.getImgView().setVisibility(View.VISIBLE);
				}

				// プログレスバーを隠し、取得した画像を表示
				if (null != item.getProgress()) {
					item.getProgress().setVisibility(View.GONE);
				}
			}
		};
	}

	/**
	 * キューにたまったダウンロードスレッドを空にする
	 */
	public static void clearQueue() {
		downloadQueue.clear();
	}

	/**
	 * 指定されたurlの画像をダウンロードしてImageViewに対して画像を設定する
	 * 
	 * @param context Activityのコンテキスト
	 * @param imgView ダウンロードした画像をセットするImageView
	 * @param progress ダウンロード中にprogressバーを表示する場合のprogressバー
	 * @param url ダウンロードする画像のurl
	 * @param isMask ダウンロードした画像をマスクする場合はtrue
	 * @param noImageResId ダウンロードする画像が取得できなかったときの代替画像のID
	 */
	public static void doDownloadBitmap(Context context, ImageView imgView, ProgressBar progress,
			String url, boolean isMask, int noImageResId) {
		doDownloadBitmap(context, imgView, progress, url, isMask, noImageResId, 0, 0);
	}

	/**
	 * 指定されたurlの画像をダウンロードしてImageViewに対して画像を設定する
	 * 
	 * @param context Activityのコンテキスト
	 * @param imgView ダウンロードした画像をセットするImageView
	 * @param progress ダウンロード中にprogressバーを表示する場合のprogressバー
	 * @param url ダウンロードする画像のurl
	 * @param isMask ダウンロードした画像をマスクする場合はtrue
	 * @param noImageResId ダウンロードする画像が取得できなかったときの代替画像のID
	 * @param resizedWidth リサイズする幅(0の場合はリサイズしない)
	 * @param resizedHeight リサイズする高さ(0の場合はリサイズしない)
	 */
	public static void doDownloadBitmap(Context context, ImageView imgView, ProgressBar progress,
			String url, boolean isMask, int noImageResId, int resizedWidth, int resizedHeight) {
		doDownloadBitmap(context, imgView, progress, url, isMask, noImageResId, 0, 0, 0, 0);
	}

	/**
	 * 指定されたurlの画像をダウンロードしてImageViewに対して画像を設定する
	 * 
	 * @param context Activityのコンテキスト
	 * @param imgView ダウンロードした画像をセットするImageView
	 * @param progress ダウンロード中にprogressバーを表示する場合のprogressバー
	 * @param url ダウンロードする画像のurl
	 * @param isMask ダウンロードした画像をマスクする場合はtrue
	 * @param noImageResId ダウンロードする画像が取得できなかったときの代替画像のID
	 * @param resizedWidth リサイズする幅(0の場合はリサイズしない)
	 * @param resizedHeight リサイズする高さ(0の場合はリサイズしない)
	 */
	public static void doDownloadBitmap(Context context, ImageView imgView, ProgressBar progress,
			String url, boolean isMask, int noImageResId, int resizedWidth, int resizedHeight,
			int resizeIvMaxWidth, int resizeIvMaxHeight) {

		mContext = context;
		/*
		 * ダウンロードキューに入れる
		 */
		LoadBitmapItem item = new LoadBitmapItem();
		item.setImgView(imgView);
		item.setProgress(progress);
		Log.d(url);
		item.setUrl(url);
		item.setMask(isMask);
		item.setResizedWidth(resizedWidth);
		item.setResizedHeight(resizedHeight);
		item.setResizeIvMaxWidth(resizeIvMaxWidth);
		item.setResizeIvMaxHeight(resizeIvMaxHeight);
		item.setNoImageResId(noImageResId);
		downloadQueue.offer(item);

		return;
	}

	/**
	 * 実際に画像をダウンロードするワーカー
	 */
	private static class DownloadWorker implements Runnable {

		@Override
		public void run() {

			/*
			 * 画像ダウンロードスレッドは常に動き続けるから無限ループ
			 */
			for (;;) {
				Bitmap bitmap = null;
				LoadBitmapItem item;

				try {
					/*
					 * キューに値が入ったら呼び出される nullの状態ではwaitしている
					 */
					item = downloadQueue.take();
				} catch (Exception ex) {
					Log.e("ERROR", "", ex);
					continue;
				}

				/*
				 * ダウンロード
				 */
				try {
					bitmap = ImageCache.getImage(item.getUrl());
					if (bitmap == null) {
						bitmap = BitmapFactory.decodeStream(HttpClientAgent.getBufferedHttpEntity(
								mContext, item.getUrl()).getContent());
						if (bitmap != null) {
							// 取得した画像データをキャッシュに保持
							ImageCache.setImage(item.getUrl(), bitmap);
						}
					}
				} catch (Exception e) {
					if (0 != item.getNoImageResId()) {
						bitmap = BitmapFactory.decodeResource(mContext.getResources(),
								item.getNoImageResId());
					}
				}
				// リサイズ
				int width = item.getResizedWidth();
				int height = item.getResizedHeight();
				if (width != 0 && height != 0) {
					bitmap = Bitmap.createScaledBitmap(bitmap, width, height, false);
				}
				item.setBitmap(bitmap);

				/*
				 * 取得した画像情報でメッセージを作って投げる
				 */
				Message msg = new Message();
				msg.obj = item;
				handler.sendMessage(msg);
			}
		}
	}
}
