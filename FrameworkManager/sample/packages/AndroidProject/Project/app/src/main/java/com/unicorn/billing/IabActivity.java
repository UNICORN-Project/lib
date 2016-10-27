package com.unicorn.billing;

import android.app.Activity;
import android.app.AlertDialog;
import android.content.DialogInterface;
import android.content.Intent;
import android.os.Bundle;
import android.util.Log;

/**
 * 課金サポートのアクティビティ基底クラス
 * 
 * @author c1718
 *
 */
public abstract class IabActivity extends Activity {

	private IabHelper helper;

	/**
	 * 購入要求のコード
	 * 
	 * <p>
	 * このアプリケーションでの購入要求のコードを指定して下さい。
	 * このクラスの{@link IabActivity#onActivityResult(int, int, Intent)}の第一引数に渡ってきます。
	 * </p>
	 * 
	 * @return 購入要求のコード
	 */
	protected abstract int getPurchaseRequestCode();

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		helper = new IabHelper(this, getPurchaseRequestCode());
		helper.setup(new SetupListener() {

			@Override
			public void onSuccess() {
				Log.d(getClass().getName(), "startSetup success.");
			}

			@Override
			public void onFailure(IabError error) {
				Log.w(getClass().getName(), "error:" + error.toString());
				onSetupFailue(error);
			}
		});
	}

	/**
	 * 課金サービスのセットアップに失敗したときに呼ばれます。
	 * 
	 * <p>
	 * {@link IabActivity#showSetupErrorDialog(String, String)}を実行します。
	 * 挙動を変えたい場合はこのメソッドをオーバーライドして下さい。
	 * </p>
	 * 
	 * @param error エラー
	 */
	protected void onSetupFailue(IabError error) {
		showDialogAfterClose("購入初期化エラー", "購入手続きが利用できません。エラーコード：" + error.getCode());
	}

	@Override
	protected void onDestroy() {
		helper.dispose();
		super.onDestroy();
	}

	/**
	 * 購入フローを起動する
	 * 
	 * <p>
	 * GooglePlayの購入画面が起動する
	 * <ul>
	 * <li>起動に成功したときには{@link IabActivity#onLaunchBuyFlowSuccess()}が呼ばれます。
	 * <li>起動に失敗したときには{@link IabActivity#onLaunchBuyFlowFailure(IabError)}が呼ばれます。
	 * </ul>
	 * <p>
	 * 起動が成功した後
	 * <ul>
	 * <li>購入処理が成功したときには{@link IabActivity#onPurchaseResultSuccess(Purchase)}が呼ばれます。
	 * <li>購入処理が失敗したときには{@link IabActivity#onPurchaseResultFailure(IabError)}が呼ばれます。
	 * <li>購入処理がキャンセルされたときには{@link IabActivity#onPurchaseResultCanceled()}が呼ばれます。
	 * </ul>
	 * 
	 * @param productId 商品コード
	 * @param productType 商品の種類
	 * @param extraData 付加情報(無い場合は{@code null})
	 * @return 開始に成功した場合はtrue
	 */
	protected void launchBuyFlow(String productId, ProductType productType, String extraData) {
		IabResult result = helper.launchBuyFlow(this, productId, productType, extraData);
		if (result.isSuccess()) {
			Log.d(getClass().getName(), "LaunchBuyFlow Success.");
			onLaunchBuyFlowSuccess();
			return;
		}
		IabError error = result.getError();
		Log.w(getClass().getName(), "error:" + error.toString());
		onLaunchBuyFlowFailure(error);
	}

	/**
	 * 購入フロー開始に成功したときに呼ばれます
	 * 
	 * @see IabActivity#launchBuyFlow(String, ProductType, String)
	 */
	protected void onLaunchBuyFlowSuccess() {
	}

	/**
	 * 購入フロー開始に失敗したときに呼ばれます
	 * 
	 * @param error エラー
	 * @see IabActivity#launchBuyFlow(String, ProductType, String)
	 */
	protected void onLaunchBuyFlowFailure(IabError error) {
		showDialog("購入エラー", "購入できません。エラーコード：" + error.getCode());
	}

	@Override
	protected void onActivityResult(int requestCode, int resultCode, Intent data) {
		// 購入処理の結果かどうか
		if (!helper.isPurchaseRequestCode(requestCode)) {
			// 購入結果ではない
			super.onActivityResult(requestCode, resultCode, data);
			return;
		}
		helper.handlePurchaseResult(resultCode, data, new PurchaseResultListener() {

			@Override
			public void onFailure(IabError error) {
				Log.w(getClass().getName(), "PurchaseResult error. " + error.toString());
				onPurchaseResultFailure(error);
			}

			@Override
			public void onSuccess(Purchase purchase) {
				Log.d(getClass().getName(), "PurchaseResult success.");
				onPurchaseResultSuccess(purchase);
			}

			@Override
			public void onCanceled() {
				Log.d(getClass().getName(), "PurchaseResult canceled.");
				onPurchaseResultCanceled();
			}
		});
	}

	/**
	 * 購入処理が成功したときに呼ばれる
	 * 
	 * @param purchase 購入情報
	 */
	protected abstract void onPurchaseResultSuccess(Purchase purchase);

	/**
	 * 購入処理が失敗したときに呼ばれる
	 * 
	 * @param error エラー
	 */
	protected void onPurchaseResultFailure(IabError error) {
		showDialog("購入エラー", "購入に失敗しました。エラーコード：" + error.getCode());
	}

	/**
	 * 購入処理がキャンセルされたときに呼ばれる
	 */
	protected void onPurchaseResultCanceled() {
	}

	/**
	 * <p>
	 * {@link IabActivity#showDialogAfterClose(String, String)}で閉じるが選択されたとき呼ばれる。
	 * 後始末の処理やアニメーションを実装して下さい。
	 * </p>
	 */
	protected void close() {
		finish();
	}

	/**
	 * ダイアログを表示する
	 * 
	 * @param title タイトル
	 * @param message メッセージ
	 */
	protected void showDialog(String title, String message) {
		AlertDialog.Builder alertDialog = new AlertDialog.Builder(this);
		alertDialog.setTitle(title);
		alertDialog.setMessage(message);
		alertDialog.setPositiveButton("閉じる", new DialogInterface.OnClickListener() {
			public void onClick(DialogInterface dialog, int which) {
			}
		});
		alertDialog.create();
		alertDialog.show();
	}

	/**
	 * ダイアログを表示する
	 * 
	 * <p>
	 * 「閉じる」を押すと{@link IabActivity#close()}が呼ばれます。
	 * </p>
	 * 
	 * @param title タイトル
	 * @param message メッセージ
	 */
	protected void showDialogAfterClose(String title, String message) {
		AlertDialog.Builder alertDialog = new AlertDialog.Builder(this);
		alertDialog.setTitle(title);
		alertDialog.setMessage(message);
		alertDialog.setPositiveButton("閉じる", new DialogInterface.OnClickListener() {
			public void onClick(DialogInterface dialog, int which) {
				close();
			}
		});
		alertDialog.create();
		alertDialog.show();
	}
}
