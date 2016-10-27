package jp.co.project.fragment;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import com.unicorn.app.App;

import jp.co.project.R;

/**
 * 設定
 */
public class SettingFragment extends BaseFragment {

	private View rootView;

	@Override
	public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
		if (rootView != null) {
			return rootView;
		}
		return inflater.inflate(R.layout.layout_setting, container, false);
	}

	@Override
	public void onViewCreated(View view, Bundle savedInstanceState) {
		this.rootView = view;

		initViews();

	}

	@Override
	public void onResume() {
		super.onResume();
	}

	private void initViews() {
		((TextView)rootView.findViewById(R.id.navigation_actionbar_title)).setText("設定");
		rootView.findViewById(R.id.navigation_actionbar_left_btn).setVisibility(View.INVISIBLE);
		rootView.findViewById(R.id.navigation_actionbar_right_btn).setVisibility(View.INVISIBLE);

		rootView.findViewById(R.id.layout_myPage).setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View view) {
				MyPageFragment myPageFragment = new MyPageFragment();
				App.activity.nextFragment(myPageFragment,1);
			}
		});
		rootView.findViewById(R.id.layout_howAbout).setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View view) {
				WebViewFragment webViewFragment = new WebViewFragment();
				Bundle args = new Bundle();
				args.putSerializable("nav_title","このアプリについて");
				args.putSerializable("url","http://unicorn-project.github.io/licenses.html");
				webViewFragment.setArguments(args);
				App.activity.nextFragment(webViewFragment,1);
			}
		});
	}

}