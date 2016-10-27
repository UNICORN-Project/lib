package com.unicorn.dialog;

/**
 * Created by takashi_takeuchi on 2016/09/20.
 */
import java.util.EventListener;

public interface InputDialogListener extends EventListener {

    /**
     * okボタンが押されたイベントを通知する
     */
    public void onPositiveClick(String text);

    /**
     * cancelボタンが押されたイベントを通知する
     */
    public void onNegativeClick();
}