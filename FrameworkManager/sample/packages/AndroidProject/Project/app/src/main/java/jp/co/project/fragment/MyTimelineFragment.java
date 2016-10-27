package jp.co.project.fragment;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ListView;
import android.widget.TextView;

import com.handmark.pulltorefresh.library.PullToRefreshBase;
import com.handmark.pulltorefresh.library.PullToRefreshListView;
import com.unicorn.dialog.CommonInputDialogFragment;
import com.unicorn.dialog.InputDialogListener;
import com.unicorn.dialog.MessageDialogListener;
import com.unicorn.handler.ModelHandler;
import com.unicorn.utilities.Log;

import jp.co.project.R;
import jp.co.project.adapter.MyTimelineAdapter;
import jp.co.project.model.TimelineModel;

/**
 * 設定
 */
public class MyTimelineFragment extends BaseFragment {

	private View rootView;

	private TimelineModel timelineModel;
	private PullToRefreshListView listView;
	private MyTimelineAdapter adapter;

	@Override
	public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
		if (rootView != null) {
			return rootView;
		}
		return inflater.inflate(R.layout.layout_mytimeline, container, false);
	}

	@Override
	public void onViewCreated(View view, Bundle savedInstanceState) {
		this.rootView = view;

		initViews();

		if(adapter == null) {
			refreshList();
		}
	}

	@Override
	public void onResume() {
		super.onResume();
	}

	private void initViews() {
		rootView.findViewById(R.id.navigation_actionbar).setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View view) {
				if(listView != null && adapter != null){

				}
			}
		});
		((TextView)rootView.findViewById(R.id.navigation_actionbar_title)).setText("マイタイムライン");
		rootView.findViewById(R.id.navigation_actionbar_left_btn).setVisibility(View.INVISIBLE);
		rootView.findViewById(R.id.navigation_actionbar_right_btn).setVisibility(View.VISIBLE);
		rootView.findViewById(R.id.navigation_actionbar_right_btn).setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View view) {
				CommonInputDialogFragment newFragment = CommonInputDialogFragment.newInstance(
						"投稿",30);
				newFragment.setDialogListener(new InputDialogListener() {
					@Override
					public void onPositiveClick(final String text) {
						final TimelineModel timelineModel = new TimelineModel(getActivity());
						ModelHandler completeHandler = new ModelHandler(getActivity()) {
							@Override
							public void success() {
								refreshList();
							}

							@Override
							public void disconnect() {
								timelineModel.post(text,this);
							}

							@Override
							public void failure() {

							}
						};
						completeHandler.showProgressDialog();
						timelineModel.post(text,completeHandler);
					}

					@Override
					public void onNegativeClick() {

					}
				});
				newFragment.show(getActivity().getSupportFragmentManager(), "CommonMessageDialogFragment");

			}
		});
		listView = (PullToRefreshListView)rootView.findViewById(R.id.mytimeline_listView);
	}

	public void refreshList(){
		timelineModel = new TimelineModel(getActivity());

		final ModelHandler completeHandler = new ModelHandler(getActivity()) {
			@Override
			public void success() {
				adapter = new MyTimelineAdapter(getContext(),R.layout.mytimeline_list_row,timelineModel.toArray());
				listView.setAdapter(adapter);
				listView.setOnRefreshListener(new PullToRefreshBase.OnRefreshListener<ListView>() {
					@Override
					public void onRefresh(PullToRefreshBase<ListView> refreshView) {
						refreshList();
					}
				});
				listView.onRefreshComplete();
			}

			@Override
			public void disconnect() {
				timelineModel.list(this);
			}

			@Override
			public void failure() {

			}
		};
		completeHandler.showProgressDialog();
		timelineModel.myList(completeHandler);
	}
}