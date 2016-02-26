<?php

/**
 * TwitterSessionクラスのインターフェース定義
 * @author saimushi
 */
Interface TwitterSessionIO {

	/**
	 * セッションを開始する
	 * @param string cookieの対象ドメイン指定
	*/
	public static function start($argDomain=NULL, $argExpiredtime=NULL, $argDSN=NULL);

	/**
	 * セッションを明示的に適用する
	 * @param string cookieの対象ドメイン指定
	 */
	public static function flush($argDomain=NULL, $argExpiredtime=NULL, $argDSN=NULL);

	/**
	 * 指定されたキー名のセッションデータを返す
	 * @param string キー名
	*/
	public static function get($argKey = NULL);

	/**
	 * セッションデータに指定されたキー名で指定された値を格納する
	 * @param string キー名
	 * @param mixed 値
	*/
	public static function set($argKey, $argment);

	/**
	 * identifierに紐づくセッションデータレコードをクリアする
	 * @param string セッションデータのプライマリーキー
	 * @param int 有効期限の直指定
	 * @param mixed DBDSN情報の直指定
	 */
	public static function clear($argPKey=NULL);

	/**
	 * 不要になっているハズのセッションを全てクリーンする
	*/
	public static function clean($argExpiredtime=NULL);
}

?>