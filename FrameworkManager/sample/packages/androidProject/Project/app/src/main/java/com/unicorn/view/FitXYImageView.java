package com.unicorn.view;

import com.unicorn.app.App;

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.widget.ImageView;
import android.widget.LinearLayout;

public class FitXYImageView extends ImageView {

	public FitXYImageView(Context context) {
		super(context);
	}

	public void setImageResourceAndFitX(int resId) {
		this.setImageResource(resId);
		// 拡大縮小比率算出
		Bitmap image = BitmapFactory.decodeResource(App.getInstance().getApplicationContext()
				.getResources(), resId);

		// 画像サイズ取得
		int width = image.getWidth();
		int height = image.getHeight();
		
		float scale = (float)App.getInstance().getWidthPixels() / (float)width;
		// 表示画像のサイズ比率に応じてimageviewのサイズを調整
		int imgX = (int) (scale * width);
		int imgY = (int) (scale * height);

		// ImageViewのサイズ変更
		setLayoutParams(new LinearLayout.LayoutParams(imgX, imgY));
		// ImageViewのScaleType変更
		setScaleType(ImageView.ScaleType.FIT_XY);
	}
}
