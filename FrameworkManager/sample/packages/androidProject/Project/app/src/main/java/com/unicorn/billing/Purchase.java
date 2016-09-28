package com.unicorn.billing;

import org.json.JSONException;

/**
 * 購入情報
 * 
 * @author c1718
 *
 */
public class Purchase extends PurchaseBase {

	/** 付加情報に使用する分割用文字列 */
	public final static String SEPARATOR = "/";

	private String productType;
	private String extraData;
	private Item item;
	private boolean verified;

	/**
	 * @param jsonPurchaseInfo 購入情報のjson文字列
	 * @param signature シグニチャー
	 * @param verified 検証結果が正しい場合は{@code true}
	 * @throws JSONException
	 */
	public Purchase(String jsonPurchaseInfo, String signature, boolean verified)
			throws JSONException {
		super(jsonPurchaseInfo, signature);
		this.verified = verified;
		String p = getDeveloperPayload();
		if (p.contains(SEPARATOR)) {
			String[] data = p.split(SEPARATOR);
			if (data.length < 2) {
				// SEPARATORがあるにも関わらず要素が2以上ない
				throw new IllegalArgumentException(
						"Not exist extraData in DeveloperPayload. DeveloperPayload:" + p
								+ " SEPARATOR:" + SEPARATOR);
			}
			productType = data[0];
			extraData = data[1];
		} else {
			productType = p;
		}
	}

	/**
	 * 商品の種類と付加情報を連結する
	 * 
	 * <p>
	 * 付加情報には{@link Purchase#SEPARATOR}以外の文字を使用して下さい。
	 * </p>
	 * 
	 * @param productType 商品の種類
	 * @param extraData 付加情報
	 * @return 開発者用の付加情報
	 */
	public static String makeDeveloperPayload(ProductType productType, String extraData) {
		if (extraData == null || extraData.isEmpty()) {
			return productType.getKey();
		}
		// TODO エスケープするなどしてSEPARATORの文字も使えるようにする
		if (extraData.contains(SEPARATOR)) {
			throw new IllegalArgumentException("Can not use '" + SEPARATOR
					+ "' in extraData. extraData:" + extraData);
		}
		return productType.getKey() + SEPARATOR + extraData;
	}

	/**
	 * @return 購入ステータス
	 */
	public PurchaseState getState() {
		return PurchaseState.find(getPurchaseState());
	}

	/**
	 * @return 商品の種類
	 */
	public ProductType getProductType() {
		return ProductType.find(productType);
	}

	/**
	 * @return 付加情報
	 */
	public String getExtraData() {
		return extraData;
	}

	/**
	 * @return アイテム情報を所有している場合は{@code true}
	 */
	public boolean hasItem() {
		return item != null;
	}

	/**
	 * @return アイテム情報(所有していない場合はnull)
	 */
	public Item getItem() {
		return item;
	}

	/**
	 * @param item アイテム情報
	 */
	public void setItem(Item item) {
		this.item = item;
	}

	/**
	 * 検証結果を調べる
	 * 
	 * @return 正しい購入情報の場合は{@code true}
	 */
	public boolean isVerified() {
		return verified;
	}

	@Override
	public String toString() {
		StringBuilder s = new StringBuilder("Purchase:{jsonPurchase:").append(getJsonPurchase())
				.append(" signature:").append(getSignature()).append(" verified:").append(verified);
		if (item != null) {
			s.append(item.toString());
		}
		return s.append("}").toString();
	}

}
