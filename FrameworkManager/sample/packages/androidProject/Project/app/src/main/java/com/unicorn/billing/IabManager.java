package com.unicorn.billing;

import java.util.List;

import org.json.JSONException;

import android.content.ComponentName;
import android.content.Context;
import android.content.Intent;
import android.content.ServiceConnection;
import android.content.pm.ResolveInfo;
import android.os.IBinder;
import android.os.RemoteException;

/**
 * 課金処理を管理する
 * 
 * @author c1718
 *
 */
public class IabManager extends LogSupport {

	/** 利用する課金APIのバージョン */
	public final static int API_VERSION = 3;
	private IabServiceAdapter adapter;
	private Context context;
	private ServiceConnection conn;
	private boolean setupDone = false;

	/**
	 * @param ctx コンテキスト
	 */
	public IabManager(Context ctx) {
		this.context = ctx;
		this.adapter = new IabServiceAdapter(API_VERSION, ctx.getPackageName());
	}

	/**
	 * 課金サービスが存在するか調べる
	 * 
	 * @return 存在する場合は{@code true}
	 */
	public boolean existsIabService() {
		Intent serviceIntent = adapter.getServiceIntent();
		List<ResolveInfo> infos = context.getPackageManager().queryIntentServices(serviceIntent, 0);
		if (infos == null || infos.isEmpty()) {
			logError("Not exists IabServiceIntent.");
			return false;
		}
		logDebug("Exists IabServiceIntent.");
		return true;
	}

	/**
	 * 課金サービスのセットアップを開始する
	 * 
	 * @param listener セットアップの結果を通知するリスナー
	 * @return 課金サービスが利用できる場合は{@code true}
	 */
	public void setup(final SetupListener listener) {
		logDebug("Starting setup.");
		if (setupDone) {
			logError("IabManager is already set up.");
			throw new IllegalStateException("IabManager is already set up.");
		}
		conn = new ServiceConnection() {

			@Override
			public void onServiceConnected(ComponentName name, IBinder service) {
				logDebug("Service connected.");
				try {
					// 通信する
					adapter.init(service);
					if (!adapter.isInappSupported()) {
						logDebug("Not supported inapp.");
						listener.onFailure(new IabError(IabErrorType.NOT_SUPPORTED,
								"Not supported inapp"));
						return;
					}
					if (!adapter.isSubsSupported()) {
						logDebug("Not supported subs.");
						listener.onFailure(new IabError(IabErrorType.NOT_SUPPORTED,
								"Not supported subs"));
						return;
					}
					setupDone = true;
					logDebug("Set up done.");
					listener.onSuccess();
				} catch (RemoteException e) {
					logError("Set up failure in onServiceConnected()", e);
					setupDone = false;
					listener.onFailure(new IabError(IabErrorType.REMOTE_ERROR, "RemoteException:"
							+ e.getMessage()));
				}
			}

			@Override
			public void onServiceDisconnected(ComponentName name) {
				// 一般的にクラッシュしたかkillされたとき呼ばれる
				// unbindServiceでは呼ばれない
				logDebug("Service disconnected.");
				// 破棄しておく
				if (adapter != null) {
					adapter.dispose();
				}
				setupDone = false;
			}

		};
		Intent serviceIntent = adapter.getServiceIntent();
		// 課金サービスをバインドする
		logDebug("BindService.");
		context.bindService(serviceIntent, conn, Context.BIND_AUTO_CREATE);
	}

	/**
	 * @return 課金サービスのコネクション
	 */
	public ServiceConnection getConn() {
		return conn;
	}

	/**
	 * @return 破棄されている場合は{@code true}
	 */
	public boolean isDisposed() {
		// adapterがnullなら破棄済み扱いにする
		return adapter == null || conn == null;
	}

	/**
	 * 破棄する
	 */
	public void dispose() {
		logDebug("Starting dispose.");
		if (conn != null) {
			if (context != null) {
				// 課金サービスをアンバインドする
				logDebug("Unbinding from service.");
				context.unbindService(conn);
			}
			conn = null;
		}
		if (adapter != null) {
			adapter.dispose();
		}
	}

	/**
	 * 注文する
	 * 
	 * <p>
	 * 付加情報には制限があります。
	 * {@link Purchase#makeDeveloperPayload(ProductType, String)}を参照して下さい。
	 * </p>
	 * 
	 * @param productId 商品ID
	 * @param productType 商品の種類
	 * @param extraData 付加情報(無い場合は{@code null})
	 * @return 注文レスポンス
	 * @throws RemoteException
	 */
	public OrderResponse order(String productId, ProductType productType, String extraData)
			throws RemoteException {
		if (isDisposed()) {
			throw new IllegalStateException("IabManager is disposed.");
		}
		if (!setupDone) {
			throw new IllegalStateException("IabManager is not set up.");
		}
		// developerPayloadに消費型・非消費型等の情報を埋め込む
		String developerPayload = Purchase.makeDeveloperPayload(productType, extraData);
		if (ProductType.SUBS == productType) {
			return adapter.order(productId, ItemType.SUBS, developerPayload);
		}
		return adapter.order(productId, ItemType.INAPP, developerPayload);
	}

	/**
	 * 消費する
	 * 
	 * <p>
	 * 定期購読{@link ProductType#SUBS}は消費することができません。
	 * IllegalArgumentExceptionが発生します。
	 * </p>
	 * 
	 * @param purchase 購入情報
	 * @return レスポンスステータス
	 * @throws RemoteException
	 */
	public ResponseStatus consume(Purchase purchase) throws RemoteException {
		if (isDisposed()) {
			throw new IllegalStateException("IabManager is disposed.");
		}
		if (!setupDone) {
			throw new IllegalStateException("IabManager is not set up.");
		}
		if (purchase.getProductType() == ProductType.SUBS) {
			throw new IllegalArgumentException("Can not consume subs.");
		}
		return adapter.consume(purchase);
	}

	/**
	 * 指定された商品コードのアイテム情報を取得する
	 * 
	 * @param itemType アイテムの種類
	 * @param productIds 商品IDのリスト
	 * @return アイテム情報レスポンス
	 * @throws RemoteException
	 * @throws JSONException
	 */
	protected ItemResponse getItems(ItemType itemType, List<String> productIds)
			throws RemoteException, JSONException {
		if (isDisposed()) {
			throw new IllegalStateException("IabManager is disposed.");
		}
		if (!setupDone) {
			throw new IllegalStateException("IabManager is not set up.");
		}
		return adapter.getItems(itemType, productIds);
	}

	/**
	 * すべての所有している購入情報を取得する
	 * 
	 * @return 購入情報レスポンス
	 * @throws RemoteException
	 * @throws JSONException
	 */
	public PurchaseResponse getInventoryAll() throws RemoteException, JSONException {
		PurchaseResponse response = new PurchaseResponse(ResponseStatus.OK);
		for (ItemType type : ItemType.values()) {
			// アイテム情報は付加しない
			PurchaseResponse r = getInventory(type, false);
			if (r.getStatus() != ResponseStatus.OK) {
				// 中断してOKでないオブジェクトを返す
				return r;
			}
			for (Purchase p : r.getPurchases()) {
				response.addPurchase(p);
			}
		}
		return response;
	}

	/**
	 * 指定されたアイテムの種類の所有している購入情報を取得する
	 * 
	 * @param itemType アイテムの種類
	 * @param addItem アイテム情報を付加するなら{@code true}
	 * @return 購入情報レスポンス
	 * @throws RemoteException
	 * @throws JSONException
	 */
	protected PurchaseResponse getInventory(ItemType itemType, boolean addItem)
			throws RemoteException, JSONException {
		if (isDisposed()) {
			throw new IllegalStateException("IabManager is disposed.");
		}
		if (!setupDone) {
			throw new IllegalStateException("IabManager is not set up.");
		}
		PurchaseResponse pRes = adapter.getInventory(itemType);
		if (pRes.getStatus() != ResponseStatus.OK) {
			return pRes;
		}
		if (!addItem) {
			return pRes;
		}
		logDebug("Add item to purchase.");
		// アイテム情報を付加する
		ItemResponse iRes = adapter.getItems(itemType, pRes.getProductIds());
		if (iRes.getStatus() != ResponseStatus.OK) {
			return pRes;
		}
		List<Purchase> list = pRes.getPurchases();
		for (Purchase p : list) {
			if (iRes.existItem(p.getProductId())) {
				Item i = iRes.getItem(p.getProductId());
				p.setItem(i);
			}
		}
		return pRes;
	}

	/**
	 * 購入結果から購入情報を生成する
	 * 
	 * @param data 購入結果
	 * @return 購入情報
	 * @throws JSONException
	 */
	public PurchaseResponse makePurchase(Intent data) throws JSONException {
		if (isDisposed()) {
			throw new IllegalStateException("IabManager is disposed.");
		}
		if (!setupDone) {
			throw new IllegalStateException("IabManager is not set up.");
		}
		return adapter.makePurchase(data);
	}
}
