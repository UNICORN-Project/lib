package com.unicorn.dialog;

/**
 * Created by takashi_takeuchi on 2016/09/20.
 */
import android.app.AlertDialog;
import android.app.Dialog;
import android.content.DialogInterface;
import android.os.Bundle;
import android.support.v4.app.DialogFragment;

public class CommonMessageDialogFragment extends DialogFragment {
    private MessageDialogListener listener = null;

    public static CommonMessageDialogFragment newInstance(String title, String message) {
        CommonMessageDialogFragment frag = new CommonMessageDialogFragment();
        Bundle bundle = new Bundle();
        bundle.putString("title", title);
        bundle.putString("message", message);
        frag.setArguments(bundle);
        return frag;
    }

    @Override
    public Dialog onCreateDialog(Bundle savedInstanceState) {

        String title = getArguments().getString("title");
        String message = getArguments().getString("message");

        return new AlertDialog.Builder(getActivity())
                .setTitle(title)
                .setMessage(message)
                .setPositiveButton(android.R.string.ok,
                        new DialogInterface.OnClickListener() {
                            @Override
                            public void onClick(DialogInterface dialog, int whichButton) {
                                listener.onPositiveClick();
                                dismiss();
                            }
                        }
                )
                .setNegativeButton(android.R.string.cancel,
                        new DialogInterface.OnClickListener() {
                            @Override
                            public void onClick(DialogInterface dialog, int whichButton) {
                                listener.onNegativeClick();
                                dismiss();
                            }
                        }
                )
                .create();
    }

    /**
     * リスナーを追加する
     *
     * @param listener
     */
    public void setDialogListener(MessageDialogListener listener) {
        this.listener = listener;
    }

    /**
     * リスナーを削除する
     */
    public void removeDialogListener() {
        this.listener = null;
    }
}