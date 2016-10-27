package com.unicorn.utilities;

import android.content.Context;
import java.util.UUID;
import android.provider.Settings.System;

public class UUIDCreate {

	private static String KEY = "2b22c1d640eb5c82698422837f4f2950";

	public static String get(Context context) {
		String uuid = load(context);
		if (uuid == null || "".equals(uuid)) {
			uuid = createUUID();
			save(context, uuid);
		}
		return uuid;
	}

	private static String load(Context context) {
		String uuid = System.getString(context.getContentResolver(), KEY);
		if (uuid != null && !"".equals(uuid))
			uuid = uuid.toUpperCase();
		else
			uuid = null;
		return uuid;
	}

	private static void save(Context context, String uuid) {
		System.putString(context.getContentResolver(), KEY, uuid);
	}

	private static String createUUID() {
		return UUID.randomUUID().toString().toUpperCase();
	}
//	
//	 private static String load(Context context) {
//	 Preferences pref = new Preferences(context);
//	 String uuid = pref.getStringValue(KEY);
//	
//	 if (uuid != null && !"".equals(uuid))
//	 uuid = uuid.toUpperCase();
//	 else
//	 uuid = null;
//	 return uuid;
//	 }
//	
//	 private static void save(Context context, String uuid) {
//	 Preferences pref = new Preferences(context);
//	 pref.setStringValue(KEY, uuid);
//	 }
}