package jp.co.project.adapter;

import java.util.List;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.TextView;

import com.unicorn.model.ModelBase;
import com.unicorn.utilities.LoadBitmapManager;

import jp.co.project.R;
import jp.co.project.model.TimelineModel;

public class TimelineAdapter extends ArrayAdapter<ModelBase> {

	private Context mContext;
	private LayoutInflater mInflater;
	private List<ModelBase> mList;

	public TimelineAdapter(Context context, int textViewResourceId,
						   List<ModelBase> list) {
		super(context, textViewResourceId, list);

		mContext = context;
		mInflater = (LayoutInflater) context.getSystemService(Context.LAYOUT_INFLATER_SERVICE);
		mList = list;
	}

	@Override
	public View getView(final int position, View convertView, final ViewGroup parent) {

		View view;
		if (convertView != null) {
			view = convertView;
		} else {
			view = mInflater.inflate(R.layout.timeline_list_row, null);
		}

		// 行に対応するモデルを取得
		TimelineModel timelineModel = (TimelineModel) mList.get(position);
		if (timelineModel.getProfileModel().getName() != null && timelineModel.getProfileModel().getName().length() > 0) {
			((TextView) view.findViewById(R.id.name)).setText(timelineModel.getProfileModel().getName());
		} else {
			((TextView) view.findViewById(R.id.name)).setText("@名無し");
		}
		((TextView) view.findViewById(R.id.comment)).setText(timelineModel.getText());
		((TextView) view.findViewById(R.id.modified)).setText(timelineModel.getModified());

		ImageView profileIv = (ImageView) view.findViewById(R.id.icon);
		profileIv.setTag(String.valueOf(position));
		ProgressBar waitBar = (ProgressBar) view.findViewById(R.id.waitBar);

		waitBar.setVisibility(View.INVISIBLE);
		profileIv.setVisibility(View.VISIBLE);

		if (timelineModel.getProfileModel().getImage() == null || timelineModel.getProfileModel().getImage().length() == 0) {
			profileIv.setImageResource(R.drawable.blank_img_photolist);
		} else {
			profileIv.setImageBitmap(timelineModel.getProfileModel().getImageBitmap());
		}

		return view;
	}

}
