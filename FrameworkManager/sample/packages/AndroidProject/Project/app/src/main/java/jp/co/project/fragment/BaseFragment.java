package jp.co.project.fragment;

import android.content.Context;
import android.os.Bundle;
import android.support.v4.app.Fragment;
import android.view.inputmethod.InputMethodManager;

import com.unicorn.app.App;

import java.util.Map;

import jp.co.project.activity.MainActivity;

public abstract class BaseFragment extends Fragment {

	protected Map<String, Object> backArgsMap;

	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
	}

	@Override
	public void onResume() {
		super.onResume();
		try {
			// ソフトキーボードを閉じる
			InputMethodManager inputMethodManager = (InputMethodManager) getMain().getSystemService(Context.INPUT_METHOD_SERVICE);
			inputMethodManager.hideSoftInputFromWindow(getMain().getRootLayout().getWindowToken(), 0);
		} catch (Exception e) {
			e.printStackTrace();
		}
		// 縦横サイズが取れていない場合
		if (getMain().getFullHeight() == 0 || getMain().getFullWidth() == 0) {
			getMain().setSize();
		}
		App.activity.isChangingFragment = false;
	}

	@Override
	public void onDestroyView() {
		super.onDestroyView();
	}

	protected MainActivity getMain() {
		MainActivity activity = (MainActivity)getActivity();
		if (activity == null) {
			return App.activity;
		}
		return activity;
	}

	public void setBackArgsMap(Map<String, Object> backArgsMap) {
		this.backArgsMap = backArgsMap;
	}

}