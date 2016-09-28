package com.unicorn.handler;

import java.util.HashMap;

import jp.co.project.R;
import jp.co.project.constant.Constant;

import org.apache.http.Header;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.DialogInterface;
import android.os.Handler;
import android.os.Message;

import com.unicorn.utilities.Log;
import com.unicorn.utilities.Preferences;
import com.unicorn.utilities.ReadingDialog;

/**
 * モデルの通信時のハンドラ
 * 
 * @author c1718
 * 
 */
public abstract class ModelHandler extends Handler {

	Activity activity;
	private ReadingDialog dialog;

	private boolean isAutoShowError = true;
	private String appMustUpdateURL;

	/**
	 */
	public ModelHandler(Activity activity) {
		this.activity = activity;
	}

	@SuppressWarnings("unchecked")
	@Override
	public void handleMessage(Message msg) {
		// 先に閉じる
		if (dialog != null && dialog.isShowing()) {
			dialog.dismiss();
		}
		checkHeader(msg);

		if (msg.arg1 == Constant.RESULT_OK) {
			success();
		} else {
			HashMap<String, Object> response;
			if (msg.obj != null) {
				response = (HashMap<String, Object>) msg.obj;
			} else {
				response = new HashMap<String, Object>();
			}
			if (isAutoShowError) {
				showAlert(response, msg.arg2);
			}
		}
	}

	/**
	 * 通信成功
	 */
	public abstract void success();

	/**
	 * 通信不可
	 */
	public abstract void disconnect();

	/**
	 * 通信失敗
	 */
	public abstract void failure();

	@SuppressWarnings("unchecked")
	private GMatchHeader getHeader(Message msg) {
		if (msg.obj == null) {
			return null;
		}
		HashMap<String, Object> response = (HashMap<String, Object>) msg.obj;
		if (!response.containsKey("header")) {
			return null;
		}
		Header[] h = (Header[]) response.get("header");
		return new GMatchHeader(h);
	}

	// dialogをActivityに管理させる為にactivityを渡す必要あり
	public void showAlert(final HashMap<String, Object> response, final int argStatusCode) {
		String errorMsg = activity.getString(R.string.errorMsgTimeout);
		if (0 < argStatusCode) {
			errorMsg = activity.getString(R.string.errorMsgServerError);
			if (400 == argStatusCode) {
				errorMsg = activity.getString(R.string.errorMsg400);
			}
			if (401 == argStatusCode) {
				errorMsg = activity.getString(R.string.errorMsg401);
			}
			if (404 == argStatusCode) {
				errorMsg = activity.getString(R.string.errorMsg404);
			}
			if (503 == argStatusCode) {
				errorMsg = activity.getString(R.string.errorMsg503);
			}
		}

		// responseにvalidate_errorが含まれていたらエラーメッセージを上書き
		if (response.containsKey("validate_error")) {
			Log.d("validate_error = " + response.get("validate_error"));
			errorMsg = "入力エラー\n\n" + (String) response.get("validate_error");
		}

		AlertDialog.Builder alertDialogBuilder = new AlertDialog.Builder(activity);
		alertDialogBuilder.setMessage(errorMsg);
		alertDialogBuilder.setPositiveButton("OK", new DialogInterface.OnClickListener() {
			@Override
			public void onClick(DialogInterface dialog, int which) {
				if (argStatusCode != 0) {
					failure();
				} else {
					disconnect();
				}
			}
		});
		alertDialogBuilder.setCancelable(true);
		AlertDialog alertDialog = alertDialogBuilder.create();
		if (activity != null) {
			alertDialog.setOwnerActivity(activity);
		}
		alertDialog.show();
	}

	public void showProgressDialog() {
		showProgressDialog(true);
	}

	public void showProgressDialog(boolean cancelable) {
		if (dialog != null && dialog.isShowing()) {
			dialog.dismiss();
		}

		dialog = new ReadingDialog(activity);
		dialog.setCancelable(cancelable);
		dialog.setOwnerActivity(activity);
		// すぐに表示
		dialog.show();
	}

	protected void checkHeader(Message msg) {
		GMatchHeader h = getHeader(msg);
		if (h != null) {
			// 優先度が高い順にチェック
			if (h.existsAppMustUpdate()) {
				showForceUpdate();
			} else if (h.existsAppNotifyMessage()) {
				// ローカル通知
				showLocalNotification(h.getAppNotifyMessage());
			}
		}
	}

	/**
	 * ローカル通知を表示する
	 * 
	 * @param msg 表示するメッセージ
	 */
	protected void showLocalNotification(String msg) {
		if (msg != null && !"".equals(msg)) {
			/**
			 * ローカルメッセージの表示
			 */
//			GmatchNotification.showLocalMessage(activity, msg);
		}
		Preferences pref = new Preferences(this.activity);
		pref.setBooleanValue("approachBadgeVisibility", true);
	}

	/**
	 * 強制アップデートを表示する
	 */
	protected void showForceUpdate() {
//		AlertDialog.Builder alertDialogBuilder = new AlertDialog.Builder(activity);
//		LayoutInflater inflater = (LayoutInflater) activity
//				.getSystemService(Activity.LAYOUT_INFLATER_SERVICE);
//		View layout = inflater.inflate(R.layout.force_update_dialog, null);
//		alertDialogBuilder.setView(layout);
//		// キャンセルさせない
//		alertDialogBuilder.setCancelable(false);
//		final AlertDialog alertDialog = alertDialogBuilder.create();
//		alertDialog.setOwnerActivity(activity);
//		DataManager.getInstance().setDialog(alertDialog);
//		final ImageButton button = (ImageButton) layout.findViewById(R.id.do_update);
//		button.setOnClickListener(new OnClickListener() {
//			@Override
//			public void onClick(View view) {
//				DataManager.getInstance().setDialog(dialog);
//				// GooglePlayを開く
//				Intent intent;
//				if (appMustUpdateURL == null || appMustUpdateURL.isEmpty()) {
//					intent = new Intent(Intent.ACTION_VIEW, Uri.parse("market://details?id="
//							+ activity.getPackageName()));
//				} else {
//					intent = new Intent(Intent.ACTION_VIEW, Uri.parse(appMustUpdateURL));
//				}
//				activity.startActivity(intent);
//				// ダイアログは閉じない
//			}
//		});
//		alertDialog.show();
	}

	class GMatchHeader {
		private String appNotifyMessage;
		private String appMustUpdate;

		private GMatchHeader(Header[] headers) {
			int len = headers.length;
			for (int i = 0; i < len; i++) {
				if (headers[i].getName().equals("AppMustUpdate")) {
					appMustUpdate = headers[i].getValue();
				} else if (headers[i].getName().equals("AppMustUpdateURL")) {
					appMustUpdateURL = headers[i].getValue();
				} else if (headers[i].getName().equals("AppNotifyMessage")) {
					appNotifyMessage = headers[i].getValue();
				}
			}
		}

		boolean existsAppMustUpdate() {
			if (appMustUpdate == null || appMustUpdate.isEmpty()) {
				return false;
			}
			return appMustUpdate.equals("1");
		}

		String getAppMustUpdate() {
			return appMustUpdate;
		}

		boolean existsAppNotifyMessage() {
			if (appNotifyMessage == null || appNotifyMessage.isEmpty()) {
				return false;
			}
			return true;
		}

		String getAppNotifyMessage() {
			return appNotifyMessage;
		}
	}

	public void setAutoShowError(boolean isAutoShowError) {
		this.isAutoShowError = isAutoShowError;
	}

}
