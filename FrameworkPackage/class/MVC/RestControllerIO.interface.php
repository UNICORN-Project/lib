<?php

interface RestControllerIO {

	/**
	 * GETメソッド
	 */
	public function get($argRequestParams=NULL);

	/**
	 * POSTメソッド
	 */
	public function post($argRequestParams=NULL);

	/**
	 * PUTメソッド
	 */
	public function put($argRequestParams=NULL);

	/**
	 * DELETEメソッド
	 */
	public function delete($argRequestParams=NULL);

	/**
	 * HEADメソッド
	 */
	public function head($argRequestParams=NULL);

	/**
	 * Restコントローラであるかの確認メソッド
	 */
	public static function isRestController();
}

?>