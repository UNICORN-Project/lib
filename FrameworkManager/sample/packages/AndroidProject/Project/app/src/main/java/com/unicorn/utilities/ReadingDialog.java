package com.unicorn.utilities;

import android.app.ProgressDialog;
import android.content.Context;

/**
 * 読み込み中ダイアログ
 * 
 * @author c1718
 *
 */
public class ReadingDialog extends ProgressDialog {

	/**
	 * @param context
	 */
	public ReadingDialog(Context context) {
		super(context);
		setProgressStyle(ProgressDialog.STYLE_SPINNER);
		setMessage("読み込み中‥");
	}

}
