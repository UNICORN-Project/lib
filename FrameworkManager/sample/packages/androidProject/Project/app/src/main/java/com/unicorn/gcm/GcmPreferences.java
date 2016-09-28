package com.unicorn.gcm;

import android.content.Context;
import android.content.SharedPreferences;
import android.content.SharedPreferences.Editor;
import android.content.pm.PackageInfo;
import android.content.pm.PackageManager.NameNotFoundException;
import android.util.Log;

/**
 * GCM永続化
 * 
 * @author c1718
 *
 */
public class GcmPreferences {

	private static final String PROPERTY_REG_ID = "registration_id";
	private static final String PROPERTY_APP_VERSION = "appVersion";
	private SharedPreferences prefs;
	private int appVer;

	/**
	 * @param ctx コンテキスト
	 */
	public GcmPreferences(Context ctx) {
		this.prefs = ctx.getSharedPreferences("GCMPref", Context.MODE_PRIVATE);
		this.appVer = getAppVersion(ctx);
		Log.d(getClass().getName(), "appVersion:" + this.appVer);
	}

	/**
	 * RegistrationIDを取得する
	 * 
	 * @return RegistrationID
	 */
	public String getRegistrationId() {
		int registeredVersion = prefs.getInt(PROPERTY_APP_VERSION, Integer.MIN_VALUE);
		if (registeredVersion != appVer) {
			// バージョンが違う
			Log.i(getClass().getName(), "App version changed. current:" + appVer + " registered:"
					+ registeredVersion);
			return "";
		}
		return this.prefs.getString(PROPERTY_REG_ID, "");
	}

	/**
	 * RegistrationIDを保存する
	 * 
	 * @param registrationId RegistrationID
	 */
	public void setRegistrationId(String registrationId) {
		Editor editor = this.prefs.edit();
		editor.putString(PROPERTY_REG_ID, registrationId);
		editor.putInt(PROPERTY_APP_VERSION, this.appVer);
		editor.commit();
	}

	/**
	 * アプリのバージョンを取得する
	 * 
	 * @param context コンテキスト
	 * @return アプリのバージョン
	 */
	private int getAppVersion(Context context) {
		try {
			PackageInfo packageInfo = context.getPackageManager().getPackageInfo(
					context.getPackageName(), 0);
			return packageInfo.versionCode;
		} catch (NameNotFoundException e) {
			// should never happen
			throw new RuntimeException("Could not get package name: " + e.getMessage(), e);
		}
	}
}
