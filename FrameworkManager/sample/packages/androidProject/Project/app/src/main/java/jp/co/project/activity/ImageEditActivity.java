package jp.co.project.activity;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.text.SimpleDateFormat;
import java.util.Date;

import android.app.ActionBar;
import android.app.AlertDialog;
import android.content.ContentUris;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.database.Cursor;
import android.graphics.Bitmap;
import android.graphics.Bitmap.CompressFormat;
import android.graphics.BitmapFactory;
import android.graphics.Matrix;
import android.graphics.Point;
import android.graphics.Rect;
import android.graphics.drawable.BitmapDrawable;
import android.media.ExifInterface;
import android.net.Uri;
import android.os.Bundle;
import android.os.Environment;
import android.os.Handler;
import android.os.Message;
import android.provider.MediaStore;
import android.support.v4.app.FragmentActivity;
import android.support.v4.app.LoaderManager.LoaderCallbacks;
import android.support.v4.content.Loader;
import android.view.Display;
import android.view.KeyEvent;
import android.view.View;
import android.view.View.OnClickListener;
import android.view.WindowManager;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.LinearLayout;

import com.unicorn.app.App;
import com.unicorn.utilities.DownloadImageAsyncTaskLoaderHelper;
import com.unicorn.utilities.Log;
import com.unicorn.view.ScalableView;

import jp.co.project.R;

public class ImageEditActivity extends FragmentActivity implements LoaderCallbacks<Bitmap> {

	private ScalableView scalableView;
	private ImageView imgIv;

	private String type;

	/**
	 * Called when the activity is first created.
	 */
	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		final ActionBar actionBar = getActionBar();

		if (actionBar != null) {
			actionBar.hide();
		}

		setContentView(R.layout.imageeditactivity);

		imgIv = (ImageView) findViewById(R.id.img_profile_sub);

		type = getIntent().getStringExtra("image_type");
		String intent_url = getIntent().getStringExtra("image_url");

		Button btn_select = (Button) findViewById(R.id.btn_select);
		btn_select.setOnClickListener(new OnClickListener() {

			@Override
			public void onClick(View v) {
				Rect mRect = new Rect(imgIv.getLeft(), imgIv.getTop(),
						imgIv.getRight(), imgIv.getBottom());

				Bitmap trimBitmap = scalableView.getCurrentImage(mRect);

				Uri uri = saveBitmapToSd(trimBitmap);
				if (uri != null) {
					Intent resultIntent = new Intent();
					resultIntent.putExtra("image_uri", uri);
					setResult(RESULT_OK, resultIntent);
				} else {
					setResult(RESULT_CANCELED);
				}
				finish();
			}

		});

		Button btn_cancel = (Button) findViewById(R.id.btn_cancel);
		btn_cancel.setOnClickListener(new OnClickListener() {

			@Override
			public void onClick(View v) {
				setResult(RESULT_CANCELED);
				finish();
			}

		});

		scalableView = (ScalableView) findViewById(R.id.scalableView);

		if (!"".equals(intent_url)) {
			startAsyncLoadImage(intent_url);
		} else {
			Uri uri = (Uri) getIntent().getExtras().get("image_uri");
			if (uri != null) {
				// Cursorの取得
				Cursor cursor = managedQuery(
						MediaStore.Images.Media.EXTERNAL_CONTENT_URI, //The URI of the content provider to query
						null, //List of columns to return.
						null, //SQL WHERE clause.
						null, //The arguments to selection, if any's are present
						null //SQL ORDER BY clause.
				);
// Cursorを先頭へ移動
				cursor.moveToFirst();
				int fieldIndex = cursor.getColumnIndex(MediaStore.Images.Media._ID);
				Long id = cursor.getLong(fieldIndex);

				final Uri bmpUri = ContentUris.withAppendedId(
						MediaStore.Images.Media.EXTERNAL_CONTENT_URI, id);

				Bitmap image = null;
				try {
					image = MediaStore.Images.Media.getBitmap(getContentResolver(), bmpUri);
					scalableView.setImageBitmap(image);

					final Handler scaleHandler = new Handler() {
						@Override
						public void handleMessage(Message msg) {
							float scale;
							Bitmap image = ((BitmapDrawable) scalableView.getDrawable()).getBitmap();
							if (image.getWidth() > image.getHeight()) {
								scale = (float) App.getInstance().dp2px(300)
										/ (float) image.getHeight();
							} else {
								scale = (float) App.getInstance().dp2px(300) / (float) image.getWidth();
							}

							scalableView.setScale(scale);
							scalableView.setVisibility(View.VISIBLE);


						}
					};

					scaleHandler.sendEmptyMessageDelayed(0, 1000);
				} catch (Exception e) {
					e.printStackTrace();
					showNoImageAlert();
				}
			} else {
				showNoImageAlert();
			}
		}

	}

	// 画像の受け渡しに失敗した場合に表示
	public void showNoImageAlert() {
		AlertDialog.Builder alertDialogBuilder = new AlertDialog.Builder(this);
		alertDialogBuilder
				.setMessage("端末のメモリ不足等により画像の取得ができませんでした。\n使用していないアプリを終了するか端末の再起動を行って再度お試し下さい。\nカメラがうまくいかない方は撮影後ギャラリーから選択もお試し下さい。");
		alertDialogBuilder.setPositiveButton("OK", new DialogInterface.OnClickListener() {
			@Override
			public void onClick(DialogInterface dialog, int which) {
				setResult(RESULT_CANCELED);
				finish();
			}
		});
		alertDialogBuilder.setCancelable(true);
		AlertDialog alertDialog = alertDialogBuilder.create();
		alertDialog.setOwnerActivity(this);
		alertDialog.show();
	}

	public Uri saveBitmapToSd(Bitmap mBitmap) {

		Uri uri = null;
		String fileName = "";
		try {
			// sdcardフォルダを指定
			File root = Environment.getExternalStorageDirectory();

			// 日付でファイル名を作成　
			Date mDate = new Date();
			SimpleDateFormat date = new SimpleDateFormat("yyyyMMdd_HHmmss");

			fileName = date.format(mDate) + ".jpg";
			// 保存処理開始
			FileOutputStream fos = null;
			fos = new FileOutputStream(new File(root, fileName));

			// jpegで保存
			mBitmap.compress(CompressFormat.JPEG, 100, fos);

			// 保存処理終了
			fos.close();
		} catch (Exception e) {
			Log.e("Error", "" + e.toString());
			fileName = "";
		}

		if (!"".equals(fileName)) {
			File file = new File(Environment.getExternalStorageDirectory(), fileName); // 存在チェックのためのFile。
			if (file.exists()) { // 一応存在チェックでも。
				uri = Uri.fromFile(file);
			} else {
				uri = null;
			}
		}

		return uri;
	}

	public float getScale(float image_width) {

		WindowManager windowManager = getWindowManager();
		Display display = windowManager.getDefaultDisplay();
		Point size = new Point();
		display.getSize(size);

		return (float) size.x / image_width;

	}

	@Override
	public void onDestroy() {
		super.onDestroy();
	}

	// Loaderの初期化から起動までを行います
	public void startAsyncLoadImage(String url) {
		Bundle args = new Bundle();
		args.putString("url", url);
		getSupportLoaderManager().initLoader(0, args, this); // onCreateLoaderが呼ばれます

		// 複数のLoaderを同時に動かす場合は、第一引数を一意のIDにしてやる必要があります。
		// GridViewなどに表示する画像を非同期で一気に取得する場合とか
	}

	@Override
	public Loader<Bitmap> onCreateLoader(int arg0, Bundle arg1) {
		if (arg1 != null) {
			String url = arg1.getString("url");
			return new DownloadImageAsyncTaskLoaderHelper(this, url);
		}
		return null;
	}

	@Override
	public void onLoadFinished(Loader<Bitmap> arg0, final Bitmap image) {
		// 非同期処理が終了したら呼ばれます.
		// 今回はDownloadが完了した画像をImageViewに表示します.
		scalableView.setVisibility(View.INVISIBLE);
		scalableView.setImageBitmap(image);

		final Handler scaleHandler = new Handler() {
			@Override
			public void handleMessage(Message msg) {
				float scale = 1.0f;
				if (image.getWidth() > image.getHeight()) {

					scale = (float) App.getInstance().dp2px(300)
							/ (float) image.getHeight();

				} else {
					scale = (float) App.getInstance().dp2px(300) / (float) image.getWidth();
				}

				scalableView.setScale(scale);
				scalableView.setVisibility(View.VISIBLE);
			}
		};

		scaleHandler.sendEmptyMessageDelayed(0, 1000);
	}

	@Override
	public void onLoaderReset(Loader<Bitmap> arg0) {
		// TODO Auto-generated method stub

	}

	public boolean onKeyDown(int keyCode, KeyEvent event) {
		if (keyCode == KeyEvent.KEYCODE_BACK) {
			setResult(RESULT_CANCELED);
			finish();
			overridePendingTransition(0, 0);
			return true;
		}
		return false;
	}

	public Bitmap decodedBitmapFromInputStream(Uri uri, int reqWidth, int reqHeight) {

		InputStream in = null;
		Log.d("decodedBitmapFromInputStream", "uri:" + uri);
		// Log.d("decodedBitmapFromInputStream",
		// "data.getdata:"+data.getData());

		// 角度を取得
		int deg = getRotateDegree(getPathFromUri(this, uri));

		try {
			in = getContentResolver().openInputStream(uri);
		} catch (FileNotFoundException e) {
			e.printStackTrace();
		}
		// inJustDecodeBounds=true で画像のサイズをチェック
		final BitmapFactory.Options options = new BitmapFactory.Options();
		options.inJustDecodeBounds = true;
		BitmapFactory.decodeStream(in, null, options);

		// inSampleSize を計算
		options.inSampleSize = calculateInSampleSize(options, reqWidth, reqHeight);

		if (in != null) {
			try {
				in.close();
			} catch (IOException e) {
				e.printStackTrace();
			}
		}

		InputStream in2 = null;
		try {
			in2 = getContentResolver().openInputStream(uri);
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
			try {
				bitmap = Bitmap.createBitmap(bitmap, 0, 0, bitmap.getWidth(), bitmap.getHeight(),
						mat, true);
			} catch (OutOfMemoryError e) {
				System.gc();
				try {
					bitmap = Bitmap.createBitmap(bitmap, 0, 0, bitmap.getWidth(),
							bitmap.getHeight(), mat, true);
				} catch (OutOfMemoryError e2) {
					// 画像の受け渡しに失敗した場合に表示
					System.gc();
					showNoImageAlert();
				}
			}
		}

		return bitmap;
	}

	private int getRotateDegree(String filePath) {
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

			// if (degree != 0) {
			// exifInterface.setAttribute(ExifInterface.TAG_ORIENTATION, "0");
			// exifInterface.saveAttributes();
			// }
		} catch (IOException e) {
			degree = -1;
			e.printStackTrace();
		}
		return degree;
	}

	public static String getPathFromUri(Context context, Uri uri) {

		String filePath = null;

		// Log.d("getPathFromUri", "uri :" +uri);

		if (uri != null && "content".equals(uri.getScheme())) {

			Cursor cursor = context.getContentResolver().query(uri,
					new String[]{android.provider.MediaStore.Images.ImageColumns.DATA}, null,
					null, null);

			cursor.moveToFirst();

			filePath = cursor.getString(0);

			cursor.close();

		} else {

			filePath = uri.getPath();

		}

		return filePath;

	}

	public int calculateInSampleSize(BitmapFactory.Options options, int reqWidth, int reqHeight) {

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

}
