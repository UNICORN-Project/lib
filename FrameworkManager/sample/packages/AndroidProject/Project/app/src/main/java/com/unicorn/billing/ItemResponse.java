package com.unicorn.billing;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * アイテム情報のレスポンス
 * 
 * @author c1718
 *
 */
public class ItemResponse extends Response {

	private Map<String, Item> items = new HashMap<String, Item>();

	/**
	 * @param status ステータス
	 */
	public ItemResponse(ResponseStatus status) {
		super(status);
	}

	/**
	 * アイテム情報を1件でも所有しているか調べる
	 * 
	 * @return 1件でも所有している場合は{@code true}
	 */
	public boolean hasItems() {
		if (items == null) {
			return false;
		}
		return !items.isEmpty();
	}

	/**
	 * すべてのアイテム情報を取得する
	 * 
	 * @return アイテム情報のリスト
	 */
	public List<Item> getItems() {
		return new ArrayList<Item>(items.values());
	}

	/**
	 * 指定された商品コードのアイテム情報が存在するか調べる
	 * 
	 * @param productId 商品コード
	 * @return アイテム情報が存在する場合は{@code true}
	 */
	public boolean existItem(String productId) {
		if (!hasItems()) {
			return false;
		}
		return items.containsKey(productId);
	}

	/**
	 * 指定された商品コードのアイテム情報を取得する
	 * 
	 * @param productId 商品コード
	 * @return アイテム情報
	 */
	public Item getItem(String productId) {
		if (!existItem(productId)) {
			return null;
		}
		return items.get(productId);
	}

	/**
	 * アイテム情報を追加する
	 * 
	 * @param item アイテム情報
	 */
	public void addItem(Item item) {
		// 同じアイテムが重複することはないから大丈夫
		this.items.put(item.getProductId(), item);
	}

	@Override
	public String toString() {
		if (items == null) {
			return getStatus().toString();
		}
		return getStatus().toString() + items.toString();
	}
}
