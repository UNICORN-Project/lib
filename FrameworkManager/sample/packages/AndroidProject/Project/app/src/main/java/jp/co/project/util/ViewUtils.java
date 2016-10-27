package jp.co.project.util;

import android.annotation.SuppressLint;
import android.os.Build;
import android.view.ViewTreeObserver;
import android.view.ViewTreeObserver.OnGlobalLayoutListener;

public class ViewUtils {

	@SuppressLint("NewApi")
	@SuppressWarnings("deprecation")
	public static void removeGlobalOnLayoutListener(ViewTreeObserver obs, OnGlobalLayoutListener listener) {
		if (obs == null)
			return;
		if (Build.VERSION.SDK_INT < 16) {
			obs.removeGlobalOnLayoutListener(listener);
		} else {
			obs.removeOnGlobalLayoutListener(listener);
		}
	}

}