package com.unicorn.billing;

import org.json.JSONException;
import org.json.JSONObject;

/**
 * アイテム情報
 * 
 * @author c1718
 *
 */
public class Item {
	private String productId;
	private String type;
	private String price;
	private String title;
	private String description;
	private String jsonItem;

	/**
	 * @param jsonItemInfo アイテム情報のjson文字列
	 * @throws JSONException
	 */
	public Item(String jsonItemInfo) throws JSONException {
		this.jsonItem = jsonItemInfo;
		JSONObject o = new JSONObject(jsonItem);
		this.productId = o.optString("productId");
		this.type = o.optString("type");
		this.price = o.optString("price");
		this.title = o.optString("title");
		this.description = o.optString("description");
	}

	/**
	 * 課金APIでは'sku'と表現されることもある
	 * 
	 * @return 商品コード
	 */
	public String getProductId() {
		return productId;
	}

	/**
	 * @return アイテムの種類
	 */
	public ItemType getType() {
		return ItemType.find(type);
	}

	/**
	 * @return 価格
	 */
	public String getPrice() {
		return price;
	}

	/**
	 * @return タイトル
	 */
	public String getTitle() {
		return title;
	}

	/**
	 * @return 説明
	 */
	public String getDescription() {
		return description;
	}

	@Override
	public String toString() {
		return "Item:" + jsonItem;
	}
}
