package com.unicorn.utilities;

import java.io.FileOutputStream;

import android.content.AsyncTaskLoader;
import android.content.Context;

public class AsyncFileWriteTaskLoader extends AsyncTaskLoader<FileOutputStream> {

	public AsyncFileWriteTaskLoader(Context context) {
		super(context);
		// TODO Auto-generated constructor stub
	}

	@Override
	public FileOutputStream loadInBackground() {
		// TODO Auto-generated method stub
		return null;
	}

}
