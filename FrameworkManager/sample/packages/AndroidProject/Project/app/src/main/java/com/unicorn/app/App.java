package com.unicorn.app;

import java.io.File;

import android.os.Environment;
import android.view.ViewGroup;

import jp.co.project.activity.MainActivity;

/**
 * アプリ
 *
 * @author c1718
 *
 */
public class App extends android.app.Application {

	public static final String NAME = "YourAppName";
	public static final String TAG = "YourAppName";
	public static final int MP = ViewGroup.LayoutParams.MATCH_PARENT;
	public static final int WC = ViewGroup.LayoutParams.WRAP_CONTENT;
	public static MainActivity activity;

	// コンテキスト
	private static App instance;

	/**
	 * @deprecated {@link #getInstance()}を使用して下さい
	 */
	public App() {
		instance = this;
	}

	/**
	 * @return アプリ
	 */
	public static App getInstance() {
		return instance;
	}

	/**
	 * @return ストレージのディレクトリ
	 */
	public static File getStorageDir() {
		File file = Environment.getExternalStorageDirectory();
		return new File(file, App.NAME);
	}

	/**
	 * @return ストレージが使用可能な場合は{@code true}
	 */
	public static boolean isStorageAvailable() {
		return Environment.getExternalStorageState().equals(Environment.MEDIA_MOUNTED);
	}

	/**
	 * @param dp
	 * @return pixel数
	 */
	public int dp2px(int dp) {
		return (int) (dp * getResources().getDisplayMetrics().density);
	}

	/**
	 * @param px pixel数
	 * @return dp
	 */
	public int px2dp(int px) {
		// 小数点以下切り捨て
		return (int) (px / getResources().getDisplayMetrics().density);
	}

	/**
	 * @return ディスプレイの高さ(pixel)
	 */
	public int getHeightPixels() {
		return getResources().getDisplayMetrics().heightPixels;
	}

	/**
	 * @return ディスプレイの幅(pixel)
	 */
	public int getWidthPixels() {
		return getResources().getDisplayMetrics().widthPixels;
	}

	/**
	 * @return 密度
	 */
	public float getDensity() {
		return getResources().getDisplayMetrics().density;
	}

	/**
	 * @return 1インチあたりのドット数
	 */
	public int getDensityDpi() {
		return getResources().getDisplayMetrics().densityDpi;
	}
}