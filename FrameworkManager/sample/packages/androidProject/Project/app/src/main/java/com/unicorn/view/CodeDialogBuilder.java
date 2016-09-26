package com.unicorn.view;

import java.util.List;

import com.unicorn.manager.DataManager;
import com.unicorn.model.CodeDto;

import android.app.Activity;
import android.app.AlertDialog;
import android.app.AlertDialog.Builder;
import android.content.DialogInterface.OnClickListener;

public class CodeDialogBuilder<T extends CodeDto> extends Builder {

	public String title;
	public Activity mActivity;
	public OnClickListener listener;
	
	public CodeDialogBuilder(Activity activity,String title) {
		super(activity);
		
		this.mActivity = activity;
		this.title = title;
	}
	
	public void setListener(OnClickListener listener){
		this.listener = listener;
	}

	public void show(List<T> list) {
		String[] tmpdata = new String[list.size()];
		for (int i = 0; i < list.size(); i++) {
			tmpdata[i] = list.get(i).getLabel();
		}
		
		final String[] data = tmpdata;
		
		setTitle(title);
		setItems(data, listener);
		setCancelable(true);
		
		AlertDialog alertDialog = create();

		alertDialog.setOwnerActivity(mActivity);
		DataManager.getInstance().setDialog(alertDialog);
		alertDialog.show();
	}

}
