package com.unicorn.utilities;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.TimeZone;

/**
 * Dateのユーティリティクラス
 * 
 * @author c1718
 *
 */
public class DateUtilities {

	public static final TimeZone TIME_ZONE = TimeZone.getTimeZone("GMT");

	/**
	 * @return 現在日時
	 */
	public static Date now() {
		return new Date();
	}

	/**
	 * @return 現在日時の文字列(パターンはyyyyMMddHHmmss)
	 */
	public static String nowToYyyyMMddHHmmsss() {
		return format(now(), "yyyyMMddHHmmss");
	}

	/**
	 * @param pattern パターン
	 * @return フォーマットされた現在日時
	 * @see SimpleDateFormat#format(Date)
	 */
	public static String format(Date date, String pattern) {
		SimpleDateFormat sdf = new SimpleDateFormat(pattern);
		sdf.setTimeZone(TIME_ZONE);
		return sdf.format(date);
	}

	/**
	 * 文字列を解析してDateを生成する
	 * 
	 * @param str 対象文字列(パターンはyyyyMMddHHmmss)
	 * @return 現在日時
	 */
	public static Date parseFromYyyyMMddHHmmss(String str) {
		return parse(str, "yyyyMMddHHmmss");
	}

	/**
	 * 文字列を解析してDateを生成する
	 * 
	 * @param str 対象文字列
	 * @param pattern パターン
	 * @return 解析に失敗した場合は現在日時
	 * @see SimpleDateFormat#parse(String)
	 */
	public static Date parse(String str, String pattern) {
		SimpleDateFormat sdf = new SimpleDateFormat(pattern);
		sdf.setTimeZone(TIME_ZONE);
		try {
			return sdf.parse(str);
		} catch (ParseException e) {
			Log.w(e);
			return now();
		}
	}
}
