package com.unicorn.utilities;

import java.io.IOException;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.support.v4.content.AsyncTaskLoader;

public class DownloadImageAsyncTaskLoaderHelper extends AsyncTaskLoader<Bitmap> {

	private String imageUrl = "";
	private Context context = null;

	public DownloadImageAsyncTaskLoaderHelper(Context context, String url) {
		super(context);

		this.imageUrl = url;
		this.context = context;
	}

	@Override
	public Bitmap loadInBackground() {
		Bitmap bitmap = ImageCache.getImage(imageUrl);
		if (bitmap == null) {
			try {
				bitmap = BitmapFactory.decodeStream(HttpClientAgent.getBufferedHttpEntity(context,
						imageUrl).getContent());
			} catch (IOException e) {
				// TODO Auto-generated catch block
				e.printStackTrace();
			}
			if (bitmap != null) {
				// 取得した画像データをキャッシュに保持
				ImageCache.setImage(imageUrl, bitmap);
			}
		}
		return bitmap;
	}

	@Override
	protected void onStartLoading() {
		forceLoad();
	}
}