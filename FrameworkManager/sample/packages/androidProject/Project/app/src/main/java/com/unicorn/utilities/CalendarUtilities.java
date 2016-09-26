package com.unicorn.utilities;

import java.util.Calendar;
import java.util.Date;
import java.util.TimeZone;

/**
 * @author c1718
 *
 */
public class CalendarUtilities {

	public static final TimeZone TIME_ZONE = TimeZone.getTimeZone("GMT");

	public static Calendar now() {
		return Calendar.getInstance(TIME_ZONE);
	}

	public static Calendar parse(String str, String pattern) {
		Date date = DateUtilities.parse(str, pattern);
		Calendar cal = Calendar.getInstance(TIME_ZONE);
		cal.setTime(date);
		return cal;
	}

	public static String format(Calendar calendar, String pattern) {
		return DateUtilities.format(calendar.getTime(), pattern);
	}

	public static boolean isToday(Calendar target) {
		Calendar now = now();
		if (target.get(Calendar.YEAR) != now.get(Calendar.YEAR)) {
			return false;
		}
		if (target.get(Calendar.DAY_OF_YEAR) != now.get(Calendar.DAY_OF_YEAR)) {
			return false;
		}
		return true;
	}
}
