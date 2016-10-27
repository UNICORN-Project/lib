package jp.co.project.model;

import android.content.Context;
import android.os.Handler;

import com.unicorn.constant.Constant;
import com.unicorn.manager.DataManager;

import java.util.ArrayList;
import java.util.HashMap;

import jp.co.project.model.base.TimelineModelBase;

/**
 * プロフィール情報を扱う
 *
 * @author c1718
 */

/**
 * @author c1718
 *
 */
public class TimelineModel extends TimelineModelBase {

    private UserModel userModel;
    private ProfileModel profileModel;

    /**
     *
     * @param argContext コンテキスト
     */
    public TimelineModel(Context argContext) {
        super(argContext, Constant.PROTOCOL, Constant.DOMAIN_NAME, Constant.URL_BASE,
                Constant.COOKIE_TOKEN_NAME, Constant.SESSION_CRYPT_KEY, Constant.SESSION_CRYPT_IV);
    }

    public UserModel getUserModel() {
        return this.userModel;
    }

    public ProfileModel getProfileModel() {
        return this.profileModel;
    }

    @SuppressWarnings({"unchecked", "rawtypes"})
    @Override
    public void _setModelData(HashMap<String, Object> map) {
        super._setModelData(map);
        if (map.containsKey("user")) {
            this.userModel = new UserModel(this.context);
            this.userModel.setModelData((ArrayList<HashMap<String, Object>>) map.get("user"));
        }
        if (map.containsKey("profile")) {
            this.profileModel = new ProfileModel(this.context);
            this.profileModel.setModelData((ArrayList<HashMap<String, Object>>) map.get("profile"));
        }

    }

    @Override
    public HashMap<String, Object> convertModelData() {
        HashMap<String, Object> map = super.convertModelData();
        if (this.userModel != null) {
            HashMap<String, Object> userMap = this.userModel.convertModelData();
            ArrayList<HashMap<String, Object>> list = new ArrayList<HashMap<String, Object>>();
            list.add(userMap);
            map.put("user", list);
        }
        if (this.profileModel != null) {
            HashMap<String, Object> profileMap = this.profileModel.convertModelData();
            ArrayList<HashMap<String, Object>> list = new ArrayList<HashMap<String, Object>>();
            list.add(profileMap);
            map.put("profile", list);
        }
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

    public boolean myList(Handler argCompletionHandler){
        completionHandler = argCompletionHandler;
        HashMap<String, Object> argWhereParams = new HashMap<String, Object>();
        return load(loadResourceMode.myListedResource, argWhereParams);
    }

    public boolean list(Handler argCompletionHandler) {
        completionHandler = argCompletionHandler;
        HashMap<String, Object> argWhereParams = new HashMap<String, Object>();
        return load(loadResourceMode.listedResource, argWhereParams);
    }

    public boolean post(String text,Handler handler){
        this.setText(text);

        return save(handler);
    }
}
