package com.unicorn.billing;

import android.app.PendingIntent;
import android.content.Intent;
import android.os.Bundle;
import android.os.IBinder;
import android.os.RemoteException;
import android.text.TextUtils;

import com.android.vending.billing.IInAppBillingService;

import org.json.JSONException;

import java.util.ArrayList;
import java.util.List;


/**
 * {@link com.android.vending.billing.IInAppBillingService}のアダプター
 * 
 * @see http://developer.android.com/google/play/billing/billing_reference.html
 * @author c1718
 *
 */
public class IabServiceAdapter extends LogSupport {

	// Keys for the responses from InAppBillingService
	public static final String RESPONSE_CODE = "RESPONSE_CODE";
	public static final String RESPONSE_GET_SKU_DETAILS_LIST = "DETAILS_LIST";
	public static final String RESPONSE_BUY_INTENT = "BUY_INTENT";
	public static final String RESPONSE_INAPP_PURCHASE_DATA = "INAPP_PURCHASE_DATA";
	public static final String RESPONSE_INAPP_SIGNATURE = "INAPP_DATA_SIGNATURE";
	public static final String RESPONSE_INAPP_ITEM_LIST = "INAPP_PURCHASE_ITEM_LIST";
	public static final String RESPONSE_INAPP_PURCHASE_DATA_LIST = "INAPP_PURCHASE_DATA_LIST";
	public static final String RESPONSE_INAPP_SIGNATURE_LIST = "INAPP_DATA_SIGNATURE_LIST";
	public static final String INAPP_CONTINUATION_TOKEN = "INAPP_CONTINUATION_TOKEN";

	// some fields on the getSkuDetails response bundle
	public static final String GET_SKU_DETAILS_ITEM_LIST = "ITEM_ID_LIST";
	public static final String GET_SKU_DETAILS_ITEM_TYPE_LIST = "ITEM_TYPE_LIST";

	private int apiVer;
	private String pkgName;
	private IInAppBillingService service;
	private Intent serviceIntent;
	private boolean isInappSupported = false;
	private boolean isSubsSupported = false;

	/**
	 * @param apiVersion APIのバージョン
	 * @param packageName パッケージ名
	 */
	public IabServiceAdapter(int apiVersion, String packageName) {
		apiVer = apiVersion;
		pkgName = packageName;
		serviceIntent = new Intent("com.android.vending.billing.InAppBillingService.BIND");
		serviceIntent.setPackage("com.android.vending");
	}

	/**
	 * 初期化処理
	 * 
	 * <p>
	 * {@link com.android.vending.billing.IInAppBillingService}の生成をする。
	 * 課金APIサポート状況を調べる。
	 * </p>
	 * 
	 * @param binder ServiceConnectionのIBinder
	 * @throws RemoteException
	 */
	public void init(IBinder binder) throws RemoteException {
		logDebug("Starting init.");
		service = IInAppBillingService.Stub.asInterface(binder);
		isInappSupported = isBillingSupported(ItemType.INAPP);
		isSubsSupported = isBillingSupported(ItemType.SUBS);
	}

	/**
	 * 破棄する
	 */
	public void dispose() {
		logDebug("Starting dispose.");
		service = null;
		isInappSupported = false;
		isSubsSupported = false;
	}

	/**
	 * {@link com.android.vending.billing.IInAppBillingService}をバインドするためのIntentを取得する
	 * 
	 * @return {@link com.android.vending.billing.IInAppBillingService}のIntent
	 */
	public Intent getServiceIntent() {
		return serviceIntent;
	}

	/**
	 * アプリ内課金がサポートされているか調べる
	 * 
	 * @return サポートされている場合は{@code true}
	 */
	public boolean isInappSupported() {
		return isInappSupported;
	}

	/**
	 * 定期購入がサポートされているか調べる
	 *
	 * @return サポートされている場合は{@code true}
	 */
	public boolean isSubsSupported() {
		return isSubsSupported;
	}

	/**
	 * 課金APIがサポートされているか調べる
	 * 
	 * @see com.android.vending.billing.IInAppBillingService#isBillingSupported(int, String, String)
	 * @param itemType アイテムの種類
	 * @return サポートされている場合は{@code true}
	 * @throws RemoteException
	 */
	protected boolean isBillingSupported(ItemType itemType) throws RemoteException {
		logDebug("Checking for in-app billing " + apiVer + " support for " + pkgName + "itemType:"
				+ itemType.getKey());
		int code = service.isBillingSupported(apiVer, pkgName, itemType.getKey());
		if (ResponseStatus.OK.getCode() == code) {
			logDebug("supported. ver:" + apiVer + " packageName:" + pkgName + " itemType:"
					+ itemType.getKey());
			return true;
		}
		logWarn("Not supported. ver:" + apiVer + " packageName:" + pkgName + " responseCode:"
				+ code);
		return false;
	}

	/**
	 * 購入フローを起動するIntentを取得する
	 * 
	 * <h1>引数developerPayloadについて</h1>
	 * 
	 * <p>
	 * オーダーに対する追加情報（空の文字列でもよい）を送るために使用できる。
	 * これは典型的には、購入リクエストをユニークに識別する文字列トークンを渡すのに使われる。
	 * string値を指定すると、GooglePlayはこの値を購入レスポンスと共に返す。
	 * 続いてあなたがこの購入の問合せを行うとき、Google Playはこの値と購入詳細を返す。
	 * </p>
	 * 
	 * <p>
	 * 実践的にはあなたのアプリケーションで購入を行ったユーザーを識別するのに役立つ文字列を渡すと良い。
	 * そうすることで、あなたは後からユーザーのこの購入が正当なものであることを確かめられる。
	 * 消耗するアイテムに対してはランダムな文字列を使うが、永続的なアイテムに対してはユーザーをユニークに識別する文字列を使うべきだろう。
	 * </p>
	 * 
	 * @see com.android.vending.billing.IInAppBillingService#getBuyIntent(int, String, String,
	 *      String, String)
	 * @param productId 商品ID
	 * @param itemType アイテムの種類
	 * @param developerPayload 開発者用の付加情報
	 * @return 注文(購入フロー開始)レスポンス
	 * @throws RemoteException
	 */
	public OrderResponse order(String productId, ItemType itemType, String developerPayload)
			throws RemoteException {
		logDebug("Parameter. productId:" + productId + " itemType:" + itemType.getKey()
				+ " developerPayload:" + developerPayload);
		Bundle bundle = service.getBuyIntent(apiVer, pkgName, productId, itemType.getKey(),
				developerPayload);
		ResponseStatus code = getResponseStatusFromBundle(bundle);
		if (code != ResponseStatus.OK) {
			return new OrderResponse(code);
		}
		PendingIntent pendingIntent = bundle.getParcelable(RESPONSE_BUY_INTENT);
		OrderResponse response = new OrderResponse(code);
		response.setOrderIntent(pendingIntent);
		return response;
	}

	/**
	 * 購入情報を消費(削除)する
	 * 
	 * @see com.android.vending.billing.IInAppBillingService#consumePurchase(int, String, String)
	 * @param purchase 購入情報
	 * @return レスポンスステータス
	 * @throws RemoteException
	 */
	public ResponseStatus consume(Purchase purchase) throws RemoteException {
		logDebug("Parameter. purchase:" + purchase.toString());
		String token = purchase.getPurchaseToken();
		if (token == null || token.isEmpty()) {
			throw new IllegalArgumentException("Not exists token in purchase. purchase:"
					+ purchase.toString());
		}
		int code = service.consumePurchase(apiVer, pkgName, token);
		return ResponseStatus.find(code);
	}

	/**
	 * アイテム情報を取得する
	 * 
	 * @see com.android.vending.billing.IInAppBillingService#getSkuDetails(int, String, String,
	 *      Bundle)
	 * @param itemType アイテムの種類
	 * @param productIds 対象となる商品IDのリスト
	 * @return アイテム情報レスポンス
	 * @throws RemoteException
	 * @throws JSONException
	 */
	public ItemResponse getItems(ItemType itemType, List<String> productIds)
			throws RemoteException, JSONException {
		logDebug("Parameter. itemType:" + itemType.toString() + " productIds:"
				+ productIds.toString());
		Bundle querySkus = new Bundle();
		ArrayList<String> list = new ArrayList<String>();
		list.addAll(productIds);
		querySkus.putStringArrayList(GET_SKU_DETAILS_ITEM_LIST, list);

		Bundle skuDetails = service.getSkuDetails(apiVer, pkgName, itemType.getKey(), querySkus);
		ResponseStatus code = getResponseStatusFromBundle(skuDetails);
		if (code != ResponseStatus.OK) {
			return new ItemResponse(code);
		}
		ItemResponse response = new ItemResponse(code);
		// 成功にも関わらず、期待するデータが存在しなかったら例外
		if (!skuDetails.containsKey(RESPONSE_GET_SKU_DETAILS_LIST)) {
			throw new IllegalStateException("Not exists " + RESPONSE_GET_SKU_DETAILS_LIST);
		}
		ArrayList<String> responseList = skuDetails
				.getStringArrayList(RESPONSE_GET_SKU_DETAILS_LIST);
		for (String thisResponse : responseList) {
			Item d = new Item(thisResponse);
			response.addItem(d);
		}
		return response;
	}

	/**
	 * 所有している購入情報を取得する
	 * 
	 * @see com.android.vending.billing.IInAppBillingService#getPurchases(int, String, String,
	 *      String)
	 * @param itemType アイテムの種類
	 * @return 購入情報レスポンス
	 * @throws RemoteException
	 * @throws JSONException
	 */
	public PurchaseResponse getInventory(ItemType itemType) throws RemoteException, JSONException {
		logDebug("Parameter. itemType:" + itemType.toString());
		// パフォーマンス改善のために、getPurchase が最初に呼ばれたとき、In-app Billing サービスは700個のプロダクトまでしか返さない。
		// ユーザーが大量のプロダクトを持っている場合、Google Play は "INAPP_CONTINUATION_TOKEN"
		// というキーに文字トークンを割り当て、さらに取得できるプロダクトがあることを示す。
		// 引数としてこのトークンを渡す事でアプリは続きの getPurcases を呼び出す事ができる。
		// Google Play はユーザーのすべてのプロダクトがアプリに送られるまでレスポンスの Bundle に続きのトークンを含めて返す。
		String continueToken = null;
		PurchaseResponse response = null;
		do {
			Bundle ownedItems = service.getPurchases(apiVer, pkgName, itemType.getKey(),
					continueToken);
			ResponseStatus code = getResponseStatusFromBundle(ownedItems);
			if (ResponseStatus.OK != code) {
				// 1回でも失敗したら中断する
				return new PurchaseResponse(code);
			}
			// 成功にも関わらず、期待するデータが存在しなかったら例外
			if (!ownedItems.containsKey(RESPONSE_INAPP_ITEM_LIST)) {
				throw new IllegalStateException("Not exists " + RESPONSE_INAPP_ITEM_LIST);
			}
			if (!ownedItems.containsKey(RESPONSE_INAPP_PURCHASE_DATA_LIST)) {
				throw new IllegalStateException("Not exists " + RESPONSE_INAPP_PURCHASE_DATA_LIST);
			}
			if (!ownedItems.containsKey(RESPONSE_INAPP_SIGNATURE_LIST)) {
				throw new IllegalStateException("Not exists " + RESPONSE_INAPP_SIGNATURE_LIST);
			}
			if (response == null) {
				// 1回目のループでインスタンス生成
				response = new PurchaseResponse(code);
			}
			// ArrayList<String> ownedSkus =
			// ownedItems.getStringArrayList(RESPONSE_INAPP_ITEM_LIST);
			ArrayList<String> purchaseDataList = ownedItems
					.getStringArrayList(RESPONSE_INAPP_PURCHASE_DATA_LIST);
			ArrayList<String> signatureList = ownedItems
					.getStringArrayList(RESPONSE_INAPP_SIGNATURE_LIST);

			for (int i = 0; i < purchaseDataList.size(); ++i) {
				String jsonPurchaseInfo = purchaseDataList.get(i);
				String signature = signatureList.get(i);
				// String sku = ownedSkus.get(i);
				boolean verified = verifyPurchase(jsonPurchaseInfo, signature);
				Purchase purchase = new Purchase(jsonPurchaseInfo, signature, verified);
				if (TextUtils.isEmpty(purchase.getPurchaseToken())) {
					logWarn("BUG: empty/null token!");
					logDebug("Purchase data: " + jsonPurchaseInfo);
				}
				response.addPurchase(purchase);
			}
			continueToken = ownedItems.getString(INAPP_CONTINUATION_TOKEN);
			logDebug("continueToken: " + continueToken);
		} while (!TextUtils.isEmpty(continueToken));
		return response;
	}

	/**
	 * 購入結果から購入情報レスポンスを生成する
	 * 
	 * @param data 購入結果
	 * @return 購入情報レスポンス
	 * @throws JSONException
	 */
	public PurchaseResponse makePurchase(Intent data) throws JSONException {
		if (data == null) {
			logError("Intent is null.");
			throw new IllegalArgumentException("Intent is null.");
		}
		logDebug("Parameter. intent:" + data.toString());
		ResponseStatus code = getResponseStatusFromIntent(data);
		if (code != ResponseStatus.OK) {
			return new PurchaseResponse(code);
		}
		String jsonPurchaseInfo = data.getStringExtra(RESPONSE_INAPP_PURCHASE_DATA);
		String signature = data.getStringExtra(RESPONSE_INAPP_SIGNATURE);
		boolean verified = verifyPurchase(jsonPurchaseInfo, signature);
		Purchase purchase = new Purchase(jsonPurchaseInfo, signature, verified);
		if (TextUtils.isEmpty(purchase.getPurchaseToken())) {
			logWarn("BUG: empty/null token!");
			logDebug("Purchase data: " + jsonPurchaseInfo);
		}
		PurchaseResponse response = new PurchaseResponse(code);
		response.addPurchase(purchase);
		return response;
	}

	/**
	 * 購入情報の検証をする
	 * 
	 * <p>
	 * 検証はサーバで行うことを推奨します。
	 * アプリ内で検証を行う場合はこのメソッドをオーバーライドして下さい。
	 * </p>
	 * 
	 * @param jsonPurchaseInfo 購入情報のjson文字列
	 * @param signature シグニチャー
	 * @return 検証結果が正しい場合は{@code true}
	 */
	protected boolean verifyPurchase(String jsonPurchaseInfo, String signature) {
		return true;
	}

	/**
	 * Bundleからステータスを取得する
	 * 
	 * @param b
	 * @return レスポンスステータス
	 */
	private ResponseStatus getResponseStatusFromBundle(Bundle b) {
		Object o = b.get(RESPONSE_CODE);
		if (o == null) {
			// 存在しなかったらOK扱い
			logWarn("Bundle with no response code, assuming OK (known issue)");
			return ResponseStatus.OK;
		} else if (o instanceof Integer) {
			int code = ((Integer) o).intValue();
			return ResponseStatus.find(code);
		} else if (o instanceof Long) {
			int code = (int) ((Long) o).longValue();
			return ResponseStatus.find(code);
		} else {
			String n = o.getClass().getName();
			logError("Unexpected type for bundle. response code:" + n);
			throw new RuntimeException("Unexpected type for bundle. response code:" + n);
		}
	}

	/**
	 * Intentからステータスを取得する
	 * 
	 * @param i
	 * @return レスポンスステータス
	 */
	private ResponseStatus getResponseStatusFromIntent(Intent i) {
		Object o = i.getExtras().get(RESPONSE_CODE);
		if (o == null) {
			// 存在しなかったらOK扱い
			logWarn("Intent with no response code, assuming OK (known issue)");
			return ResponseStatus.OK;
		} else if (o instanceof Integer) {
			int code = ((Integer) o).intValue();
			return ResponseStatus.find(code);
		} else if (o instanceof Long) {
			int code = (int) ((Long) o).longValue();
			return ResponseStatus.find(code);
		} else {
			String n = o.getClass().getName();
			logError("Unexpected type for intent. response code.");
			throw new RuntimeException("Unexpected type for intent. response code:" + n);
		}
	}
}
