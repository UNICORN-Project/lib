package jp.co.project.fragment;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.webkit.WebView;
import android.widget.Button;
import android.widget.TextView;

import com.unicorn.app.App;

import jp.co.project.R;

/**
 * 設定
 */
public class WebViewFragment extends BaseFragment {

	private View rootView;
	private WebView webView;

	private String nav_title = "";
	private String url = "";

	@Override
	public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
		if (rootView != null) {
			return rootView;
		}
		return inflater.inflate(R.layout.layout_webview, container, false);
	}

	@Override
	public void onViewCreated(View view, Bundle savedInstanceState) {
		this.rootView = view;

		nav_title = (String)getArguments().getSerializable("nav_title");
		url = (String)getArguments().getSerializable("url");
		initView();

	}

	@Override
	public void onResume() {
		super.onResume();
	}

	private void initView() {
		((TextView)rootView.findViewById(R.id.navigation_actionbar_title)).setText(nav_title);
		rootView.findViewById(R.id.navigation_actionbar_left_btn).setVisibility(View.VISIBLE);
		((Button)rootView.findViewById(R.id.navigation_actionbar_left_btn)).setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View view) {
				App.activity.backFragment();
			}
		});
		rootView.findViewById(R.id.navigation_actionbar_right_btn).setVisibility(View.INVISIBLE);

		webView = (WebView)rootView.findViewById(R.id.webView);
		webView.getSettings().setJavaScriptEnabled(true);
		webView.loadUrl(url);
	}

}