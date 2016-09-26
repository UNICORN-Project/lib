package com.unicorn.utilities;

/**
 * Created by c1363 on 15/06/09.
 */

import android.app.Activity;
import android.content.Context;
import android.os.Handler;
import android.view.View;
import android.view.WindowManager.LayoutParams;
import android.view.inputmethod.InputMethodManager;
import android.widget.EditText;

public class KeyboardUtil {

	public static void hide(Context context, View view) {
		if (view != null) {
			InputMethodManager imm = (InputMethodManager)context.getSystemService(
					Context.INPUT_METHOD_SERVICE);
			imm.hideSoftInputFromWindow(view.getWindowToken(), 0);
			view.clearFocus();
		}
	}

	public static void hide(Activity activity) {
		hide(activity, activity.getCurrentFocus());
	}

	public static void initHidden(Activity activity) {
		activity.getWindow().setSoftInputMode(LayoutParams.SOFT_INPUT_STATE_ALWAYS_HIDDEN);
	}

	public static void show(Context context, EditText edit) {
		show(context, edit, 0);
	}

	public static void show(final Context context, final EditText edit, int delayTime) {
		final Runnable showKeyboardDelay = new Runnable() {
			@Override
			public void run() {
				if (context != null) {
					InputMethodManager imm = (InputMethodManager)context
							.getSystemService(Context.INPUT_METHOD_SERVICE);
					imm.showSoftInput(edit, InputMethodManager.SHOW_IMPLICIT);
				}
			}
		};
		new Handler().postDelayed(showKeyboardDelay, delayTime);
	}
}