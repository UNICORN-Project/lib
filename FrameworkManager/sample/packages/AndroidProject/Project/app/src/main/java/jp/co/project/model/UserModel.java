package jp.co.project.model;

import android.content.Context;

import com.unicorn.constant.Constant;

import jp.co.project.model.base.UserModelBase;
/**
 * UserModelはUserテーブルのデータを参照、保存するためのクラスです
 * 
 * @author　c1363
 */
public class UserModel extends UserModelBase {

	public UserModel(Context argContext) {
		super(argContext, Constant.PROTOCOL, Constant.DOMAIN_NAME, Constant.URL_BASE,
				Constant.COOKIE_TOKEN_NAME, Constant.SESSION_CRYPT_KEY, Constant.SESSION_CRYPT_IV);
	}
}