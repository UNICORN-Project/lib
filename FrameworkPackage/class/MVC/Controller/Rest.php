<?php

class Rest extends RestControllerBase {

	/**
	 * リソースの参照
	 * @return mixed 成功時は最新のリソース配列 失敗時はFALSE
	 */
	public function get($argRequestParams = NULL){
		return parent::get($argRequestParams);
	}

	/**
	 * リソースの作成・更新・インクリメント・デクリメント
	 * @return mixed 成功時は最新のリソース配列 失敗時はFALSE
	 */
	public function post($argRequestParams = NULL){
		return parent::post($argRequestParams);
	}

	/**
	 * リソースの作成・更新
	 * @return mixed 成功時は最新のリソース配列 失敗時はFALSE
	 */
	public function put($argRequestParams = NULL){
		return parent::put($argRequestParams);
	}

	/**
	 * リソースの削除
	 * @return boolean
	 */
	public function delete($argRequestParams = NULL){
		return parent::delete($argRequestParams);
	}

	/**
	 * リソースの情報の取得
	 * @return boolean
	 */
	public function head($argRequestParams = NULL){
		return parent::head($argRequestParams);
	}
}

?>