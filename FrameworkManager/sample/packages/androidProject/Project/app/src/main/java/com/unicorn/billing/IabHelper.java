package com.unicorn.billing;

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.IntentSender.SendIntentException;
import android.os.Handler;
import android.os.RemoteException;

import org.json.JSONException;

import java.util.ArrayList;
import java.util.List;

/**
 * 課金処理をサポートする
 *
 * @author c1718
 */
public class IabHelper extends LogSupport {

	private boolean asyncInProgress = false;
	private String asyncOperation = "";
	private IabManager iabManager;
	private int purchaseRequestCode;

	/**
	 * @param ctx                 コンテキスト
	 * @param purchaseRequestCode 購入処理のRequestCode
	 */
	public IabHelper(Context ctx, int purchaseRequestCode) {
		this.iabManager = new IabManager(ctx.getApplicationContext());
		this.purchaseRequestCode = purchaseRequestCode;
		logDebug("IabHelper created.");
		logDebug("purchaseRequestCode:" + purchaseRequestCode);
	}

	/**
	 * セットアップを行う
	 *
	 * @param listener セットアップ結果を通知するリスナー
	 */
	public void setup(final SetupListener listener) {
		logDebug("Starting setupWithRefresh.");
		if (!iabManager.existsIabService()) {
			listener.onFailure(new IabError(IabErrorType.NOT_EXIST_IAB_SERVICE,
					"Not exists IabService"));
			return;
		}
		iabManager.setup(listener);
	}

	/**
	 * 内部のデータを破棄する
	 * <p/>
	 * <p>
	 * {@link Activity#onDestroy()}するときに必ず呼び出して下さい。
	 * </p>
	 */
	public void dispose() {
		logDebug("Starting dispose.");
		if (iabManager == null) {
			logDebug("IabManager is already null.");
			return;
		}
		iabManager.dispose();
	}

	/**
	 * 購入フローを起動する
	 * <p/>
	 * <p>
	 * GooglePlayの購入画面が起動します。
	 * 引数として渡した{@code activity}に実装された{@link Activity#onActivityResult(int, int, Intent)}で購入結果を受け取ることができます。
	 * </p>
	 *
	 * @param activity    購入結果を受け取るアクティビティ
	 * @param productId   商品ID
	 * @param productType 商品の種類
	 * @param extraData   付加情報(無い場合は{@code null})
	 * @return 結果
	 */
	public IabResult launchBuyFlow(Activity activity, String productId, ProductType productType,
								   String extraData) {
		flagStartAsync("launchBuyFlow");
		try {
			OrderResponse response = iabManager.order(productId, productType, extraData);
			logDebug("OrderResponse:" + response.toString());
			if (response.getStatus() != ResponseStatus.OK) {
				flagEndAsync();
				IabError error = new IabError(IabErrorType.RESPONSE_STATUS_NG, "response status:"
						+ response.getStatus().toString(), response.getStatus());
				return new IabResult(error);
			}
			activity.startIntentSenderForResult(response.getOrderIntent().getIntentSender(),
					purchaseRequestCode, new Intent(), Integer.valueOf(0), Integer.valueOf(0),
					Integer.valueOf(0));
			return new IabResult();
		} catch (RemoteException e) {
			logError("RemoteException while launching buy flow for productId " + productId);
			flagEndAsync();
			IabError error = new IabError(IabErrorType.REMOTE_ERROR, "RemoteException:"
					+ e.getMessage());
			return new IabResult(error);
		} catch (SendIntentException e) {
			logError("SendIntentException while launching buy flow for productId " + productId);
			flagEndAsync();
			IabError error = new IabError(IabErrorType.SEND_INTENT_ERROR,
					"failed startIntentSenderForResult");
			return new IabResult(error);
		}
	}

	/**
	 * 購入処理のリクエストコードか調べる
	 *
	 * @param reauestCode リクエストコード
	 * @return 購入処理のリクエストコードの場合は{@code true}
	 */
	public boolean isPurchaseRequestCode(int reauestCode) {
		logDebug("requestCode:" + reauestCode + " purchaseRequestCode:" + purchaseRequestCode);
		return reauestCode == purchaseRequestCode;
	}

	/**
	 * 購入結果を処理する
	 * <p/>
	 * <p>
	 * 実行する前に必ず{@link IabHelper#isPurchaseRequestCode(int)}でチェックを行って下さい。
	 * </p>
	 *
	 * @param resultCode 結果コード
	 * @param data       購入情報
	 * @param listener   購入結果を通知するリスナー
	 */
	public void handlePurchaseResult(int resultCode, Intent data,
									 final PurchaseResultListener listener) {
		flagEndAsync();
		if (Activity.RESULT_CANCELED == resultCode) {
			logDebug("PurchaseResult canceled.");
			listener.onCanceled();
			return;
		}
		if (Activity.RESULT_OK != resultCode) {
			// 想定外
			logError("PurchaseResult unknown. resultCode:" + resultCode);
			listener.onFailure(new IabError(IabErrorType.UNKOWN_RESULT_CODE, "resultCode:"
					+ resultCode));
			return;
		}
		// RESULT_OK
		Purchase purchase = null;
		try {
			PurchaseResponse response = iabManager.makePurchase(data);
			logDebug("PurchaseResponse:" + response.toString());
			if (response.getStatus() != ResponseStatus.OK) {
				listener.onFailure(new IabError(IabErrorType.RESPONSE_STATUS_NG, "responseStatus:"
						+ response.getStatus().toString(), response.getStatus()));
				return;
			}
			purchase = response.getPurchases().get(0);
		} catch (JSONException e) {
			logError("Failed to parse purchase data.");
			listener.onFailure(new IabError(IabErrorType.BAD_RESPONSE, "JSONException:"
					+ e.getMessage()));
			return;
		}
		if (purchase == null) {
			// ありえないけれども
			logError("Purchase is null.");
			listener.onFailure(new IabError(IabErrorType.BAD_RESPONSE, "Purchase is null"));
			return;
		}

		listener.onSuccess(purchase);
	}

	/**
	 * 非同期でユーザーの所有している購入情報を取得する
	 *
	 * @param listener 所有している購入情報を取得した結果を通知するリスナー
	 */
	public void getInventoryAllAsync(final InventoryListener listener) {
		final Handler handler = new Handler();
		flagStartAsync("refresh inventory");
		(new Thread(new Runnable() {
			public void run() {
				try {
					final PurchaseResponse pRes = iabManager.getInventoryAll();
					logDebug("PurchaseResponse:" + pRes.toString());
					flagEndAsync();
					if (pRes.getStatus() != ResponseStatus.OK) {
						handler.post(new Runnable() {
							public void run() {
								listener.onFailure(new IabError(IabErrorType.RESPONSE_STATUS_NG,
										"response status:" + pRes.getStatus().toString(), pRes
										.getStatus()));
							}
						});
						return;
					}
					// 購入情報取得成功
					final List<Purchase> purchases = pRes.getPurchases();
					handler.post(new Runnable() {
						public void run() {
							listener.onSuccess(purchases);
						}
					});
				} catch (final RemoteException e) {
					logError("GetInventoryAll failure.", e);
					flagEndAsync();
					handler.post(new Runnable() {
						public void run() {
							listener.onFailure(new IabError(IabErrorType.REMOTE_ERROR,
									"RemoteException:" + e.getMessage()));
						}
					});
				} catch (final JSONException e) {
					logError("GetInventoryAll failure.", e);
					flagEndAsync();
					handler.post(new Runnable() {
						public void run() {
							listener.onFailure(new IabError(IabErrorType.BAD_RESPONSE,
									"JSONException:" + e.getMessage()));
						}
					});
				}
			}
		})).start();
	}

	/**
	 * 非同期で所有している購入情報を1件だけ消費(削除)する
	 *
	 * @param purchase 購入情報
	 * @param listener 消費結果を通知するリスナー
	 */
	public void consumeAsync(final Purchase purchase, final ConsumeListener listener) {
		List<Purchase> targets = new ArrayList<Purchase>();
		targets.add(purchase);
		// 複数件のメソッドに委譲する
		consumeListAsync(targets, new ConsumeMultiListener() {

			@Override
			public void onSuccessAll(List<Purchase> successPurchases) {
				// 1件だけ渡しているので1件
				logDebug("ConsumeAsync success. " + successPurchases.toString());
				listener.onSuccess(successPurchases.get(0));
			}

			@Override
			public void onFailureExist(List<Purchase> successPurchases, List<IabError> errors,
									   List<Purchase> failurePurchase) {
				// 1件だけ渡しているので失敗の方に1件
				logDebug("ConsumeAsync failure." + " error:" + errors.toString() + " failure:"
						+ failurePurchase.toString());
				listener.onFailure(errors.get(0), failurePurchase.get(0));
			}
		});
	}

	/**
	 * 非同期で所有している購入情報を複数件消費(削除)する
	 *
	 * @param purchases 購入情報のリスト
	 * @param listener  消費結果を通知するリスナー
	 */
	public void consumeListAsync(final List<Purchase> purchases, final ConsumeMultiListener listener) {
		final Handler handler = new Handler();
		flagStartAsync("consumeListAsync");
		(new Thread(new Runnable() {
			public void run() {
				final List<Purchase> successPurchase = new ArrayList<Purchase>();
				final List<Purchase> failurePurchase = new ArrayList<Purchase>();
				final List<IabError> errors = new ArrayList<IabError>();
				for (Purchase p : purchases) {
					try {
						ResponseStatus code = iabManager.consume(p);
						if (code != ResponseStatus.OK) {
							failurePurchase.add(p);
							errors.add(new IabError(IabErrorType.RESPONSE_STATUS_NG,
									"response status:" + code.toString(), code));
						} else {
							successPurchase.add(p);
						}
					} catch (RemoteException e) {
						failurePurchase.add(p);
					}
				}
				flagEndAsync();
				if (failurePurchase.isEmpty()) {
					handler.post(new Runnable() {
						public void run() {
							listener.onSuccessAll(successPurchase);
						}
					});
				} else {
					handler.post(new Runnable() {
						public void run() {
							listener.onFailureExist(successPurchase, errors, failurePurchase);
						}
					});
				}
			}
		})).start();
	}

	/**
	 * 非同期処理開始
	 * <p/>
	 * <p>
	 * 非同期処理は同時に複数の操作を実行できない。
	 * これは必須なのだろうか？(サンプルを踏襲)
	 * </p>
	 *
	 * @param operation 操作内容
	 */
	protected void flagStartAsync(String operation) {
		// 例外ではなくブロッキングはダメ？
		if (asyncInProgress)
			throw new IllegalStateException("Can't start async operation (" + operation
					+ ") because another async operation(" + asyncOperation + ") is in progress.");
		asyncOperation = operation;
		asyncInProgress = true;
		logDebug("Starting async operation: " + operation);
	}

	/**
	 * 非同期処理終了
	 * <p/>
	 * <p>
	 * 非同期処理は同時に複数の操作を実行できない。
	 * これは必須なのだろうか？(サンプルを踏襲)
	 * </p>
	 */
	protected void flagEndAsync() {
		// 例外ではなくブロッキングはダメ？
		logDebug("Ending async operation: " + asyncOperation);
		asyncOperation = "";
		asyncInProgress = false;
	}
}
