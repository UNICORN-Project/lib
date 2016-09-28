package jp.co.project.adapter;

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

import java.util.List;

import jp.co.project.R;
import jp.co.project.model.TimelineModel;

public class MyTimelineAdapter extends ArrayAdapter<ModelBase>{

	private Context mContext;
	private LayoutInflater mInflater;
	private List<ModelBase> mList;

	public MyTimelineAdapter(Context context, int textViewResourceId,
							 List<ModelBase> list) {
		super(context, textViewResourceId, list);

		mContext = context;
		mInflater = (LayoutInflater) context.getSystemService(Context.LAYOUT_INFLATER_SERVICE);
		mList = list;
	}

	@Override
	public View getView(final int position, View convertView, final ViewGroup parent) {

		View view;
		if(convertView != null){
			view = convertView;
		}else {
			view = mInflater.inflate(R.layout.mytimeline_list_row, null);
		}

		// 行に対応するモデルを取得
		TimelineModel timelineModel = (TimelineModel) mList.get(position);

		((TextView)view.findViewById(R.id.comment)).setText(timelineModel.getText());
		((TextView)view.findViewById(R.id.modified)).setText(timelineModel.getModified());

		return view;
	}

}
