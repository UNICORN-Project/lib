package com.unicorn.utilities;

import android.app.Activity;
import android.app.AlertDialog;
import android.app.ProgressDialog;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.database.Cursor;
import android.graphics.Bitmap;
import android.graphics.Bitmap.CompressFormat;
import android.graphics.BitmapFactory;
import android.graphics.Matrix;
import android.media.ExifInterface;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.net.Uri;
import android.os.Handler;
import android.os.Message;

import com.unicorn.constant.Constant;
import com.unicorn.manager.DataManager;

import java.io.ByteArrayOutputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.InputStream;
import java.io.UnsupportedEncodingException;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Date;
import java.util.HashMap;

/**
 * どこからでも呼べる便利メソッドの集まりです
 * 
 * @author c1363
 */
public class Utilitis {

	/**
	 * １６進数文字列をbyte[]に変換するメソッド
	 * 
	 * @param s　文字列
	 * @return　変換後のbyte[]
	 */
	public static byte[] hexStringToByteArray(String s) {
		int len = s.length();
		byte[] data = new byte[len / 2];
		for (int i = 0; i < len; i += 2) {
			data[i / 2] = (byte) ((Character.digit(s.charAt(i), 16) << 4) + Character.digit(
					s.charAt(i + 1), 16));
		}
		return data;
	}

	/**
	 * 文字列をbyte[]に変換するメソッド
	 * 
	 * @param hex
	 * @return　変換後のbyte[]
	 */
	public static byte[] asByteArray(String hex) {
		byte[] bytes = null;
		try {
			bytes = hex.getBytes("UTF-8");
		} catch (UnsupportedEncodingException e) {
			e.printStackTrace();
		}

		// バイト配列を返す。
		return bytes;
	}

	// Converting a bytes array to string of hex character
	public static String byteArrayToHexString(byte[] b) {
		int len = b.length;
		String data = new String();

		for (int i = 0; i < len; i++) {
			data += Integer.toHexString((b[i] >> 4) & 0xf);
			data += Integer.toHexString(b[i] & 0xf);
		}
		return data;
	}

	// "YmdHis"の文字列からLineLikeな時間(Y/m/d H:i)を取得
	public static String getLineLikeDateString(String argDateString) {
		String year = argDateString.substring(0, 4);
		String month = argDateString.substring(4, 6);
		String date = argDateString.substring(6, 8);
		String hour = argDateString.substring(8, 10);
		String minute = argDateString.substring(10, 12);

		String dateString = "";

		// カレンダーオブジェクト
		Calendar cal = Calendar.getInstance();
		int nowYear = cal.get(Calendar.YEAR);
		int nowMonth = cal.get(Calendar.MONTH) + 1;
		int nowDate = cal.get(Calendar.DATE);

		if (!(Integer.valueOf(year).intValue() == nowYear)) {
			dateString = year + "/" + month + "/" + date;
		} else {
			if (!(Integer.valueOf(month).intValue() == nowMonth && Integer.valueOf(date).intValue() == nowDate)) {
				dateString = month + "/" + date;
			} else {
				dateString = hour + ":" + minute;
			}
		}

		return dateString;
	}

	// "YmdHis"の文字列から時間(Y/m/d H:i)を取得
	public static String getDateString(String argDateString) {
		String year = argDateString.substring(0, 4);
		String month = argDateString.substring(4, 6);
		String date = argDateString.substring(6, 8);
		String hour = argDateString.substring(8, 10);
		String minute = argDateString.substring(10, 12);

		String dateString = "";

		dateString = year + "/" + month + "/" + date + " " + hour + ":" + minute;

		return dateString;
	}

	public static boolean isNetworkConnected(Context context) {
		ConnectivityManager cm = (ConnectivityManager) context
				.getSystemService(Context.CONNECTIVITY_SERVICE);
		NetworkInfo ni = cm.getActiveNetworkInfo();
		if (ni != null) {
			return cm.getActiveNetworkInfo().isConnected();
		}
		return false;
	}

	public static ProgressDialog getProgressDialog(Context context, String text) {
		ProgressDialog dialog = new ProgressDialog(context);
		dialog.setProgressStyle(ProgressDialog.STYLE_SPINNER);
		dialog.setMessage(text);
		dialog.setCancelable(true);
		return dialog;
	}

	public static int getResoruceIdFromName(Context context, String name, String resourceType) {
		return context.getResources().getIdentifier(name, resourceType, context.getPackageName());
	}

	// ファイル→バイトデータ
	public static byte[] file2data(Context context, String fileName) throws Exception {
		int size;
		byte[] w = new byte[1024];
		InputStream in = null;
		ByteArrayOutputStream out = null;
		try {
			// ファイル入力ストリームのオープン
			in = context.openFileInput(fileName);

			// バイト配列の読み込み
			out = new ByteArrayOutputStream();
			while (true) {
				size = in.read(w);
				if (size <= 0)
					break;
				out.write(w, 0, size);
			}
			out.close();

			// ファイル入力ストリームのクローズ
			in.close();

			// ByteArrayOutputStreamオブジェクトのバイト配列化
			return out.toByteArray();
		} catch (Exception e) {
			try {
				if (in != null)
					in.close();
				if (out != null)
					out.close();
			} catch (Exception e2) {
			}
			throw e;
		}
	}

	// dialogをActivityに管理させる為にactivityを渡す必要あり
	public static void showDialog(Context context, String title, String msg) {
		AlertDialog.Builder alertDialogBuilder = new AlertDialog.Builder(context);
		alertDialogBuilder.setTitle(title);
		alertDialogBuilder.setMessage(msg);
		alertDialogBuilder.setPositiveButton("OK", new DialogInterface.OnClickListener() {
			@Override
			public void onClick(DialogInterface dialog, int which) {
				dialog.dismiss();
				DataManager.getInstance().closeAllDialog();
			}
		});
		alertDialogBuilder.setCancelable(true);
		AlertDialog alertDialog = alertDialogBuilder.create();
		alertDialog.setOwnerActivity((Activity) context);
		DataManager.getInstance().setDialog(alertDialog);
		alertDialog.show();
	}

	public static int calculateInSampleSize(BitmapFactory.Options options, int reqWidth,
			int reqHeight) {

		// 画像の元サイズ
		final int height = options.outHeight;
		final int width = options.outWidth;
		int inSampleSize = 1;

		if ((width * height) > 1048576) {
			// １Mピクセル超えてる
			double out_area = (double) (width * height) / 1048576.0;
			inSampleSize = (int) (Math.sqrt(out_area) + 1);
		} else {
			// 小さいのでそのまま
			inSampleSize = 1;
		}
		return inSampleSize;
	}

	public static int getRotateDegree(String filePath) {
		int degree = 0;
		try {
			ExifInterface exifInterface = new ExifInterface(filePath);
			int orientation = exifInterface.getAttributeInt(ExifInterface.TAG_ORIENTATION,
					ExifInterface.ORIENTATION_UNDEFINED);
			// Log.d("getRotateDegree", "orientation:"+orientation);

			if (orientation == ExifInterface.ORIENTATION_ROTATE_90) {
				degree = 90;
			} else if (orientation == ExifInterface.ORIENTATION_ROTATE_180) {
				degree = 180;
			} else if (orientation == ExifInterface.ORIENTATION_ROTATE_270) {
				degree = 270;
			}
		} catch (IOException e) {
			degree = -1;
			e.printStackTrace();
		}
		return degree;
	}

	public static String getPathFromUri(Context context, Uri uri) {

		String filePath = null;

		if (uri != null && "content".equals(uri.getScheme())) {

			Cursor cursor = context.getContentResolver().query(uri,
					new String[] { android.provider.MediaStore.Images.ImageColumns.DATA }, null,
					null, null);

			cursor.moveToFirst();

			filePath = cursor.getString(0);

			cursor.close();

		} else {

			filePath = uri.getPath();

		}

		return filePath;

	}

	public static Bitmap decodedBitmapFromInputStream(Activity activity, Uri uri, int reqWidth,
			int reqHeight) {
		InputStream in = null;
		Log.d("decodedBitmapFromInputStream", "uri:" + uri);
		// Log.d("decodedBitmapFromInputStream",
		// "data.getdata:"+data.getData());

		// 角度を取得
		int deg = Utilitis.getRotateDegree(Utilitis.getPathFromUri(activity, uri));

		try {
			in = activity.getContentResolver().openInputStream(uri);
		} catch (FileNotFoundException e) {
			e.printStackTrace();
		}
		// inJustDecodeBounds=true で画像のサイズをチェック
		final BitmapFactory.Options options = new BitmapFactory.Options();
		options.inJustDecodeBounds = true;
		BitmapFactory.decodeStream(in, null, options);

		// inSampleSize を計算
		options.inSampleSize = Utilitis.calculateInSampleSize(options, reqWidth, reqHeight);

		if (in != null) {
			try {
				in.close();
			} catch (IOException e) {
				e.printStackTrace();
			}
		}

		InputStream in2 = null;
		try {
			in2 = activity.getContentResolver().openInputStream(uri);
		} catch (FileNotFoundException e) {
			e.printStackTrace();
		}

		// inSampleSize をセットしてデコード
		options.inJustDecodeBounds = false;
		options.inPurgeable = true;
		Bitmap bitmap = BitmapFactory.decodeStream(in2, null, options);

		if (in2 != null) {
			try {
				in2.close();
			} catch (IOException e) {
				e.printStackTrace();
			}
		}

		// 角度が0ではないということは回転されているのでbitmapに角度を加える
		if (deg != 0) {
			Matrix mat = new Matrix();
			mat.setRotate(deg);
			bitmap = Bitmap.createBitmap(bitmap, 0, 0, bitmap.getWidth(), bitmap.getHeight(), mat,
					true);
		}

		return bitmap;
	}

	/*
	 * DatePickerDialogのonDateSetで受け取った日付を整形
	 */
	public static String getformatDate(int year, int monthOfYear, int dayOfMonth, String format) {
		Calendar calendar = Calendar.getInstance();
		calendar.set(year, monthOfYear, dayOfMonth);
		SimpleDateFormat sdf = new SimpleDateFormat(format);
		return sdf.format(calendar.getTime());
	}

	/*
	 * yyyy/mm/ddの文字列型の日付から日付型に変換
	 */
	public static Date getformatStringDate(String yyyymmdd) {
		SimpleDateFormat sdf = new SimpleDateFormat("yyyyMMdd");
		Date date = new Date();
		try {
			date = sdf.parse(yyyymmdd);
		} catch (ParseException e) {
			Log.d("ParseException:" + e.getMessage());
		}
		return date;
	}

	public static byte[] toByteArrayBody(Bitmap picture) {
		if (picture == null) {
			return null;
		}
		ByteArrayOutputStream bos = new ByteArrayOutputStream();
		picture.compress(CompressFormat.JPEG, 100, bos);
		return bos.toByteArray();
	}

	public static boolean isEmpty(String str){
		if(str == null) return true;
		return str.isEmpty();
	}

	public static boolean isNotEmpty(String str){
		if(str == null) return false;
		if(str.isEmpty()){
			return false;
		}else{
			return true;
		}
	}

	// dialogをActivityに管理させる為にactivityを渡す必要あり
	public static void showAlert(Context context, String msg,
								 final HashMap<String, Object> response, final Handler completionHandler,
								 final Activity activity, final int statusCode) {
		AlertDialog.Builder alertDialogBuilder = new AlertDialog.Builder(context);
		alertDialogBuilder.setMessage(msg);
		alertDialogBuilder.setPositiveButton("OK", new DialogInterface.OnClickListener() {
			@Override
			public void onClick(DialogInterface dialog, int which) {
				if (completionHandler != null) {
					Message msg = new Message();
					msg.arg1 = Constant.RESULT_FAILED;
					msg.arg2 = statusCode;
					msg.obj = response;
					completionHandler.sendMessage(msg);
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
}
