package com.unicorn.utilities;

import java.io.FileOutputStream;
import java.io.IOException;

import android.os.AsyncTask;

public class AsyncFileWriteTask extends AsyncTask<Void, Void, Boolean> {

	private FileOutputStream fileOutputStream;
	private byte[] bytes;

	public AsyncFileWriteTask(FileOutputStream fileOutputStream, byte[] bytes) {
		this.fileOutputStream = fileOutputStream;
		this.bytes = bytes;
	}

	@Override
	protected Boolean doInBackground(Void... params) {
		try {
			fileOutputStream.write(bytes);
			fileOutputStream.flush();
			return true;
		} catch (IOException e) {
			Log.e(getClass().toString(), "Failed write file.", e);
			return false;
		} finally {
			try {
				fileOutputStream.close();
			} catch (IOException e) {
				Log.e(getClass().toString(), "Failed close stream.", e);
			}
		}
	}
}
