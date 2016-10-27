package com.unicorn.manager;

import android.app.AlertDialog;
import android.os.Handler;
import android.os.Message;

import com.unicorn.app.App;
import com.unicorn.constant.Constant;
import com.unicorn.handler.ModelHandler;

import java.util.ArrayList;

import jp.co.project.model.ProfileModel;
import jp.co.project.model.UserModel;

public class DataManager {
	private static final DataManager mDataManager = new DataManager();

	public UserModel myUserModel;
	public ProfileModel myModel;
	public ArrayList<AlertDialog> dialogArray;

	private DataManager() {
		myModel = null;
		dialogArray = new ArrayList<AlertDialog>();
	}

	public static DataManager getInstance() {
		return mDataManager;
	}

	public void clear() {
		myModel = null;
	}

	public UserModel getUserModel(){
		UserModel userModel = null;
		if(myModel != null){
			userModel = myModel.getUserModel();
		}
		if(userModel == null){
			userModel = myUserModel;
		}
		return userModel;
	}

	public void loadUserModel(final ModelHandler argHandler) {
		final UserModel userModel = new UserModel(App.getInstance()
				.getApplicationContext());
		Handler handler = new Handler() {
			public void handleMessage(Message msg) {
				if (msg.arg1 == Constant.RESULT_OK) {
					myUserModel = userModel;
				}
				Message message = new Message();
				message.arg1 = msg.arg1;
				message.arg2 = msg.arg2;
				message.obj = msg.obj;
				argHandler.sendMessage(message);
			}
		};
		userModel.load(handler);
	}

	public void load() {
		final ProfileModel profileModel = new ProfileModel(App.getInstance()
				.getApplicationContext());
		Handler handler = new Handler() {
			public void handleMessage(Message msg) {
				if (msg.arg1 == Constant.RESULT_OK) {
					myModel = profileModel;
				}
			}
		};
		profileModel.load(handler);
	}

	public void load(final ModelHandler argHandler) {
		final ProfileModel profileModel = new ProfileModel(App.getInstance()
				.getApplicationContext());
		Handler handler = new Handler() {
			public void handleMessage(Message msg) {
				if (msg.arg1 == Constant.RESULT_OK) {
					myModel = profileModel;
				}
				Message message = new Message();
				message.arg1 = msg.arg1;
				message.arg2 = msg.arg2;
				message.obj = msg.obj;
				argHandler.sendMessage(message);

			}
		};
		profileModel.load(handler);
	}

	public void setDialog(AlertDialog dialog) {
		dialogArray.add(dialog);
	}

	public void closeAllDialog(){
		for(int i=0;i<dialogArray.size();i++){
			AlertDialog dialog = dialogArray.get(i);
			if(dialog != null){
				if(dialog.isShowing()){
					dialog.dismiss();
				}
			}
		}
		dialogArray.clear();
	}
}
