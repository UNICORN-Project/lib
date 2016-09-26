package jp.co.project.fragment;

import android.app.Activity;
import android.content.ContentValues;
import android.content.Intent;
import android.graphics.Matrix;
import android.graphics.Point;
import android.graphics.drawable.BitmapDrawable;
import android.media.MediaScannerConnection;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.provider.MediaStore;
import android.view.Display;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.view.WindowManager;
import android.widget.Button;
import android.widget.EdgeEffect;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.TextView;

import com.unicorn.app.App;
import com.unicorn.handler.ModelHandler;
import com.unicorn.manager.DataManager;
import com.unicorn.view.Toast;

import java.io.File;

import jp.co.project.R;
import jp.co.project.activity.ImageEditActivity;
import jp.co.project.constant.Constant;

/**
 * 設定
 */
public class MyPageFragment extends BaseFragment {

	private View rootView;
	private Uri m_uri;
	private static final int REQUEST_CHOOSER = 1000;

	@Override
	public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
		if (rootView != null) {
			return rootView;
		}
		return inflater.inflate(R.layout.layout_mypage, container, false);
	}

	@Override
	public void onViewCreated(View view, Bundle savedInstanceState) {
		this.rootView = view;

		initView();

	}

	@Override
	public void onResume() {
		super.onResume();
	}

	private void initView() {
		((TextView) rootView.findViewById(R.id.navigation_actionbar_title)).setText("マイページ");
		rootView.findViewById(R.id.navigation_actionbar_left_btn).setVisibility(View.VISIBLE);
		((Button) rootView.findViewById(R.id.navigation_actionbar_left_btn)).setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View view) {
				App.activity.backFragment();
			}
		});
		((Button) rootView.findViewById(R.id.navigation_actionbar_right_btn)).setText("保存");

		if (DataManager.getInstance().myModel.getImage() == null || DataManager.getInstance().myModel.getImage().length() == 0) {
			((ImageView) rootView.findViewById(R.id.mypage_image)).setImageResource(R.drawable.blank_img_photolist);
		} else {
			((ImageView) rootView.findViewById(R.id.mypage_image)).setImageBitmap(DataManager.getInstance().myModel.getImageBitmap());
		}
		rootView.findViewById((R.id.mypage_image)).setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View view) {
				showGallery();
			}
		});

		rootView.findViewById(R.id.navigation_actionbar_right_btn).setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View view) {
				String name = ((EditText) rootView.findViewById(R.id.mypage_name)).getText().toString();

				DataManager.getInstance().myModel.setName(name);
				DataManager.getInstance().myModel.setImageBitmap(((BitmapDrawable) ((ImageView) rootView.findViewById(R.id.mypage_image)).getDrawable()).getBitmap());

				ModelHandler handelr = new ModelHandler(getActivity()) {
					@Override
					public void success() {
						Toast.makeText(getActivity(), "保存しました。", Toast.LENGTH_SHORT).show();
					}

					@Override
					public void disconnect() {

					}

					@Override
					public void failure() {

					}
				};
				handelr.showProgressDialog();
				DataManager.getInstance().myModel.save(handelr);
			}
		});

		((EditText) rootView.findViewById(R.id.mypage_name)).setText(DataManager.getInstance().myModel.getName());
	}

	private void showGallery() {

		//カメラの起動Intentの用意
		String photoName = System.currentTimeMillis() + ".jpg";
		ContentValues contentValues = new ContentValues();
		contentValues.put(MediaStore.Images.Media.TITLE, photoName);
		contentValues.put(MediaStore.Images.Media.MIME_TYPE, "image/jpeg");
		m_uri = getActivity().getContentResolver()
				.insert(MediaStore.Images.Media.EXTERNAL_CONTENT_URI, contentValues);

		Intent intentCamera = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
		intentCamera.putExtra(MediaStore.EXTRA_OUTPUT, m_uri);

		// ギャラリー用のIntent作成
		Intent intentGallery;
		if (Build.VERSION.SDK_INT < 19) {
			intentGallery = new Intent(Intent.ACTION_GET_CONTENT);
			intentGallery.setType("image/*");
		} else {
			intentGallery = new Intent(Intent.ACTION_OPEN_DOCUMENT);
			intentGallery.addCategory(Intent.CATEGORY_OPENABLE);
			intentGallery.setType("image/jpeg");
		}
		Intent intent = Intent.createChooser(intentCamera, "画像の選択");
		intent.putExtra(Intent.EXTRA_INITIAL_INTENTS, new Intent[] {intentGallery});
		startActivityForResult(intent, REQUEST_CHOOSER);
	}

	@Override
	public void onActivityResult(int requestCode, int resultCode, Intent data) {
		super.onActivityResult(requestCode, resultCode, data);

		if(requestCode == REQUEST_CHOOSER) {

			if(resultCode != Activity.RESULT_OK) {
				// キャンセル時
				return ;
			}

			Uri resultUri = (data != null ? data.getData() : m_uri);

			if(resultUri == null) {
				// 取得失敗
				return;
			}

			// ギャラリーへスキャンを促す
			MediaScannerConnection.scanFile(
					getActivity(),
					new String[]{resultUri.getPath()},
					new String[]{"image/jpeg"},
					null
			);

			Intent intent = new Intent(getActivity(), ImageEditActivity.class);
			intent.putExtra("image_url", "");
			intent.putExtra("image_uri", resultUri);
			startActivityForResult(intent, Constant.REQUEST_IMAGE_EDIT);
			getActivity().overridePendingTransition(0, 0);

		}else if(requestCode == Constant.REQUEST_IMAGE_EDIT) {

			System.gc();
			if (resultCode == Activity.RESULT_OK) {
				if (data != null) {
					Uri resultUri = (Uri) data.getExtras().get("image_uri");
					if (resultUri != null) {
						// 画像を設定
						ImageView imageView = (ImageView)rootView.findViewById(R.id.mypage_image);
						imageView.setImageURI(resultUri);

						File file = new File(resultUri.getPath());
						if (file != null) {
							file.delete();
						}
					}
				}
			}

		}
	}
}