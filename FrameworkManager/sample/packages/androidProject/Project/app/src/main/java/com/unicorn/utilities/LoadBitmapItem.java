package com.unicorn.utilities;

import android.graphics.Bitmap;
import android.widget.ImageView;
import android.widget.ProgressBar;

/**
 * ダウンロード画像を管理する
 * 
 * @author c1718
 *
 */
public class LoadBitmapItem {

	private ImageView imgView;
	private ProgressBar progress;

	private Bitmap bitmap;
	private String url;
	private boolean isMask;

	private int resizedWidth = 0;
	private int resizedHeight = 0;
	private int resizeIvMaxWidth = 0;
	private int resizeIvMaxHeight = 0;
	private int noImageResId;

	/**
	 * @return 画像
	 */
	public Bitmap getBitmap() {
		return bitmap;
	}

	/**
	 * @param bitmap 画像
	 */
	public void setBitmap(Bitmap bitmap) {
		this.bitmap = bitmap;
	}

	/**
	 * @return 画像のURL
	 */
	public String getUrl() {
		return url;
	}

	/**
	 * @param url 画像のURL
	 */
	public void setUrl(String url) {
		this.url = url;
	}

	/**
	 * @return 画像View
	 */
	public ImageView getImgView() {
		return imgView;
	}

	/**
	 * @param imgView 画像View
	 */
	public void setImgView(ImageView imgView) {
		this.imgView = imgView;
	}

	/**
	 * @return プログレスバー
	 */
	public ProgressBar getProgress() {
		return progress;
	}

	/**
	 * @param progress プログレスバー
	 */
	public void setProgress(ProgressBar progress) {
		this.progress = progress;
	}

	/**
	 * @return マスクする場合はtrue
	 */
	public boolean isMask() {
		return isMask;
	}

	/**
	 * @param isMask マスクする場合はtrue
	 */
	public void setMask(boolean isMask) {
		this.isMask = isMask;
	}

	/**
	 * @param noImageResId 画像がダウンロードできなかったときの代替画像のID
	 */
	public void setNoImageResId(int noImageResId) {
		this.noImageResId = noImageResId;
	}

	/**
	 * @return 画像がダウンロードできなかったときの代替画像のID
	 */
	public int getNoImageResId() {
		return noImageResId;
	}

	/**
	 * @return ダウンロードした画像をリサイズする場合の幅(0はリサイズしない)
	 */
	public int getResizedWidth() {
		return resizedWidth;
	}

	/**
	 * 0の場合はリサイズしない
	 * リサイズする場合は高さと幅の両方を必ず指定する
	 * 
	 * @param resizedWidth ダウンロードした画像をリサイズする場合の幅
	 */
	public void setResizedWidth(int resizedWidth) {
		this.resizedWidth = resizedWidth;
	}

	/**
	 * @return ダウンロードした画像をリサイズする場合の高さ(0はリサイズしない)
	 */
	public int getResizedHeight() {
		return resizedHeight;
	}

	/**
	 * 0の場合はリサイズしない
	 * リサイズする場合は高さと幅の両方を必ず指定する
	 * 
	 * @param resizedHeight ダウンロードした画像をリサイズする場合の高さ
	 */
	public void setResizedHeight(int resizedHeight) {
		this.resizedHeight = resizedHeight;
	}
	
	/**
	 * 0の場合はリサイズしない
	 * リサイズする場合は高さと幅の両方を必ず指定する
	 * 
	 * @param resizedWidth ダウンロードした画像をリサイズする場合の幅
	 */
	public void setResizeIvMaxWidth(int resizeIvMaxWidth) {
		this.resizeIvMaxWidth = resizeIvMaxWidth;
	}

	/**
	 * @return ダウンロードした画像をリサイズする場合の高さ(0はリサイズしない)
	 */
	public int getResizeIvMaxWidth() {
		return resizeIvMaxWidth;
	}

	/**
	 * 0の場合はリサイズしない
	 * リサイズする場合は高さと幅の両方を必ず指定する
	 * 
	 * @param resizedHeight ダウンロードした画像をリサイズする場合の高さ
	 */
	public void setResizeIvMaxHeight(int resizeIvMaxHeight) {
		this.resizeIvMaxHeight = resizeIvMaxHeight;
	}
	
	/**
	 * @return ダウンロードした画像をリサイズする場合の高さ(0はリサイズしない)
	 */
	public int getResizeIvMaxHeight() {
		return resizeIvMaxHeight;
	}

}