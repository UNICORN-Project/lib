package jp.co.project.model;

import java.util.ArrayList;
import java.util.HashMap;

import android.content.Context;
import android.os.Handler;

import com.unicorn.constant.Constant;

import jp.co.project.model.base.ProfileModelBase;

/**
 * プロフィール情報を扱う
 *
 * @author c1718
 */

/**
 * @author c1718
 *
 */
public class ProfileModel extends ProfileModelBase {

    private UserModel userModel;

    /**
     *
     * @param argContext コンテキスト
     */
    public ProfileModel(Context argContext) {
        super(argContext, Constant.PROTOCOL, Constant.DOMAIN_NAME, Constant.URL_BASE,
                Constant.COOKIE_TOKEN_NAME, Constant.SESSION_CRYPT_KEY, Constant.SESSION_CRYPT_IV);
    }

    public UserModel getUserModel() {
        return this.userModel;
    }

    @SuppressWarnings({"unchecked", "rawtypes"})
    @Override
    public void _setModelData(HashMap<String, Object> map) {
        super._setModelData(map);
        if (map.containsKey("user")) {
            this.userModel = new UserModel(this.context);
            this.userModel.setModelData((ArrayList<HashMap<String, Object>>) map.get("user"));
        }

    }

    @Override
    public HashMap<String, Object> convertModelData() {
        HashMap<String, Object> map = super.convertModelData();
        if (this.userModel == null) {
            return map;
        }
        HashMap<String, Object> userMap = this.userModel.convertModelData();
        ArrayList<HashMap<String, Object>> list = new ArrayList<HashMap<String, Object>>();
        list.add(userMap);
        map.put("user", list);
        return map;
    }

    @Override
    public boolean load() {
        return super.load();
    }

    @Override
    public boolean load(Handler handler) {
        return super.load(handler);
    }
}
