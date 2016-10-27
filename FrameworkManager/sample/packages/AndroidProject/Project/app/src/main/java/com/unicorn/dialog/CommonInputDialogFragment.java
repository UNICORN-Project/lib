package com.unicorn.dialog;

/**
 * Created by takashi_takeuchi on 2016/09/20.
 */
import android.app.AlertDialog;
import android.app.Dialog;
import android.content.DialogInterface;
import android.os.Bundle;
import android.support.v4.app.DialogFragment;
import android.text.InputFilter;
import android.view.LayoutInflater;
import android.view.View;
import android.widget.EditText;

import jp.co.project.R;

public class CommonInputDialogFragment extends DialogFragment {
    private InputDialogListener listener = null;

    public static CommonInputDialogFragment newInstance(String title, int maxLength) {
        CommonInputDialogFragment frag = new CommonInputDialogFragment();
        Bundle bundle = new Bundle();
        bundle.putString("title", title);
        bundle.putInt("maxLength", maxLength);
        frag.setArguments(bundle);
        return frag;
    }

    @Override
    public Dialog onCreateDialog(Bundle savedInstanceState) {

        String title = getArguments().getString("title");
        int maxLength = getArguments().getInt("maxLength");

        AlertDialog.Builder builder = new AlertDialog.Builder(getActivity())
                .setTitle(title);

        LayoutInflater factory = LayoutInflater.from(getContext());
        final View inputView = factory.inflate(R.layout.layout_common_input_dialog, null);

        final EditText editText = (EditText)inputView.findViewById(R.id.input_dialog_edit_text);
        InputFilter[] _inputFilter = new InputFilter[1];
        _inputFilter[0] = new InputFilter.LengthFilter(maxLength);
        editText.setFilters(_inputFilter);

        builder.setView(inputView);
        builder.setPositiveButton(android.R.string.ok,
                new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialog, int whichButton) {
                        listener.onPositiveClick(editText.getText().toString());
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
                );
        return builder.create();
    }

    /**
     * リスナーを追加する
     *
     * @param listener
     */
    public void setDialogListener(InputDialogListener listener) {
        this.listener = listener;
    }

    /**
     * リスナーを削除する
     */
    public void removeDialogListener() {
        this.listener = null;
    }
}