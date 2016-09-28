package com.unicorn.utilities;

/**
 * 電話番号ユーティリティ
 * 
 * @author c1718
 *
 */
public class PhoneNumberUtil {

	public static String JAPAN = "+81";

	/**
	 * 国際表記の電話番号か調べる
	 * 
	 * @param number 電話番号
	 * @return 国際表記の電話番号の場合は{@code true}
	 */
	public static boolean isGlobal(String number) {
		if (number == null) {
			return false;
		}
		if (number.startsWith("+")) {
			// 厳密には調べない(手抜き)
			return true;
		}
		return false;
	}

	/**
	 * 国際表記された日本の電話番号か調べる
	 * 
	 * @param number 電話番号
	 * @return 国際表記の日本の電話番号の場合は{@code true}
	 */
	public static boolean isJapan(String number) {
		if (number == null) {
			return false;
		}
		if (number.startsWith(JAPAN)) {
			return true;
		}
		return false;
	}

	/**
	 * 国際表記された日本の電話番号を国内表記に変換する
	 * 
	 * @param globalNumber 国際表記された日本の電話番号
	 * @return 国内表記された電話番号
	 */
	public static String toJapaneseLocal(String globalNumber) {
		if (globalNumber == null) {
			throw new IllegalArgumentException("globalNumber is null.");
		}
		return globalNumber.replace(JAPAN, "0");
	}

	/**
	 * 日本の携帯番号(PHS含む)か調べる
	 * 
	 * @param localNumber 国内表記された日本の電話番号
	 * @return 携帯番号の場合は{@code true}
	 */
	public static boolean isJapaneseMobile(String localNumber) {
		if (localNumber == null) {
			throw new IllegalArgumentException("localNumber is null.");
		}
		if (localNumber.length() != 11) {
			return false;
		}
		if (localNumber.startsWith("070") || localNumber.startsWith("080")
				|| localNumber.startsWith("090")) {
			return true;
		}
		return false;
	}

	/**
	 * 日本のIP電話番号か調べる
	 * 
	 * @param localNumber 国内表記された日本の電話番号
	 * @return IP電話番号の場合は{@code true}
	 */
	public static boolean isJapaneseIP(String localNumber) {
		if (localNumber == null) {
			throw new IllegalArgumentException("localNumber is null.");
		}
		if (localNumber.length() != 11) {
			return false;
		}
		if (localNumber.startsWith("050")) {
			return true;
		}
		return false;
	}

	/**
	 * 先頭の＋と数字以外の文字列を取り除くことで正規化する
	 * 
	 * @param number 電話番号
	 * @return 正規化された電話番号
	 */
	public static String normalize(String number) {
		if (number == null) {
			throw new IllegalArgumentException("number is null.");
		}
		String digit = number.replaceAll("[\\D]", "");
		if (isGlobal(number)) {
			return "+" + digit;
		}
		return digit;
	}
}
