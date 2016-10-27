package com.unicorn.utilities;

import android.content.Context;
import android.content.SharedPreferences;
import android.content.SharedPreferences.Editor;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Calendar;

/**
 * 
 * @author c1718
 *
 */
public class Preferences {

	private SharedPreferences pref;

	/**
	 * 
	 * @param con
	 */
	public Preferences(Context con) {
		this.pref = con.getSharedPreferences("Project_Pref", Context.MODE_PRIVATE);
	}

	/**
	 * 保存しているすべての値を削除する
	 */
	public void clear() {
		Editor editor = pref.edit();
		editor.clear();
		editor.commit();
	}

	/**
	 * 値を取得する
	 * 
	 * @param key キー
	 * @return デフォルトは0
	 */
	public int getIntValue(String key) {
		return pref.getInt(key, 0);
	}

	/**
	 * 値を設定する
	 * 
	 * @param key キー
	 * @param value 値
	 */
	public void setIntValue(String key, int value) {
		Editor editor = pref.edit();
		editor.putInt(key, value);
		editor.commit();
	}

	/**
	 * 値を取得する
	 * 
	 * @param key キー
	 * @return デフォルトは空文字
	 */
	public String getStringValue(String key) {
		return pref.getString(key, "");
	}

	/**
	 * 値を設定する
	 * 
	 * @param key キー
	 * @param value 値
	 */
	public void setStringValue(String key, String value) {
		Editor editor = pref.edit();
		editor.putString(key, value);
		editor.commit();
	}
	
	/**
	 * 値を取得する
	 * 
	 * @param key キー
	 * @return デフォルトは空文字
	 */
	public boolean getBooleanValue(String key) {
		return pref.getBoolean(key, true);
	}
	
	/**
	 * 値を設定する
	 * 
	 * @param key キー
	 * @param value 値
	 */
	public void setBooleanValue(String key, boolean value) {
		Editor editor = pref.edit();
		editor.putBoolean(key, value);
		editor.commit();
	}

	/**
	 * スタンプのIDを履歴に追加する
	 *
	 * @param add_history スタンプのID
	 */
	public void saveStampHistory(String add_history) {

		ArrayList<String> array_history = getStampHistory();

		array_history.remove(add_history);

		String saveText = "";
		saveText += add_history;

		for (int i = 0; i < array_history.size(); i++) {
			if (i < 7) {
				saveText += ",";
				saveText += array_history.get(i);
			} else {
				break;
			}
		}

		Editor editor = pref.edit();
		editor.putString("stamp_history", saveText);
		editor.commit();
	}

	/**
	 * スタンプ履歴をArrayLisで取得する
	 *
	 * @return ArrayList<String> スタンプID（文字列）のList
	 */
	public ArrayList<String> getStampHistory() {

		String[] history = pref.getString("stamp_history", "").split(",");
		ArrayList<String> array_history = new ArrayList<String>();

		for (int i = 0; i < history.length; i++) {
			if (!"".equals(history[i])) {
				array_history.add(history[i]);
			}
		}

		return array_history;
	}

	/**
	 * 今日はもう表示しないAnnounceのIDを追加する
	 *
	 * @param add_ID AnnounceのID
	 */
	public void saveAnnounceID(String add_ID) {

		ArrayList<String> array_history = getAnnounceID();

		array_history.remove(add_ID);

		String saveText = "";
		saveText += add_ID;

		for (int i = 0; i < array_history.size(); i++) {
			saveText += ",";
			saveText += array_history.get(i);
		}

		Editor editor = pref.edit();
		editor.putString("announceID", saveText);
		editor.commit();

		saveAnnouncedDate();
	}

	/**
	 * 今日はもう表示しないAnnounceのIDを追加する
	 *
	 * @param add_ID AnnounceのID
	 */
	public void saveOneTimeAnnounceID(String add_ID) {

		ArrayList<String> array_history = getAnnounceID();

		array_history.remove(add_ID);

		String saveText = "";
		saveText += add_ID;

		for (int i = 0; i < array_history.size(); i++) {
			saveText += ",";
			saveText += array_history.get(i);
		}

		Editor editor = pref.edit();
		editor.putString("onetimeannounceID", saveText);
		editor.commit();

		saveAnnouncedDate();
	}

	public void saveAnnouncedDate() {

		Calendar cal = Calendar.getInstance();
		SimpleDateFormat sdf = new SimpleDateFormat("yyyyMMdd");
		String dateStr = sdf.format(cal.getTime());

		Editor editor = pref.edit();
		editor.putString("announcedDate", dateStr);
		editor.commit();
	}

	public boolean isEqualAnnouncedDate() {

		Calendar cal = Calendar.getInstance();
		SimpleDateFormat sdf = new SimpleDateFormat("yyyyMMdd");
		String dateStr = sdf.format(cal.getTime());

		String lastDate = pref.getString("announcedDate", "");

		if(dateStr.equals(lastDate)){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 今日はもう表示しないAnnouceIDをArrayLisで取得する
	 *
	 * @return ArrayList<String> AnnounceID（文字列）のList
	 */
	public ArrayList<String> getAnnounceID() {

		String[] history = pref.getString("announceID", "").split(",");
		String[] onetime = pref.getString("onetimeannounceID", "").split(",");
		ArrayList<String> array_announced = new ArrayList<String>();

		for (int i = 0; i < history.length; i++) {
			if (!"".equals(history[i])) {
				array_announced.add(history[i]);
			}
		}

		for(int i = 0;i < onetime.length ; i++){
			if(!"".equals(onetime[i])){
				array_announced.add(onetime[i]);
			}
		}

		return array_announced;
	}

	public void resetAnnounceID(){
		Editor editor = pref.edit();
		editor.remove("announceID");
		editor.commit();
	}
}
