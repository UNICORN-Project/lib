package com.unicorn.dialog;

/**
 * Created by takashi_takeuchi on 2016/09/20.
 */
import java.util.EventListener;

public interface MessageDialogListener extends EventListener {

    /**
     * okボタンが押されたイベントを通知する
     */
    public void onPositiveClick();

    /**
     * cancelボタンが押されたイベントを通知する
     */
    public void onNegativeClick();
}