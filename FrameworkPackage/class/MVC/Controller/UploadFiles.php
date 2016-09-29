<?php

class UploadFiles extends RestControllerBase
{
	public $virtualREST = TRUE;

	public static function getTargetDir(){
		$targetPath = Storage::UPLOAD_TARGET_TYPE_S3;
		if (Storage::UPLOAD_TARGET_TYPE_S3 !== getConfig('UPLOAD_TARGET_TYPE_S3')){
			// 非S3
			$targetPath = getConfig('TMP_PATH');
		}
		return $targetPath;
	}

	public static function getSaveDir($argAuthorized=FALSE){
		$saveDir = getConfig('FILE_UPLOAD_DIR');
		if (TRUE === $argAuthorized){
			$Auth = Auth::getCertifiedUser();
			$saveDir .= '/'.sha256($Auth->pkey);
			$saveDir = str_replace('//', '/', $saveDir);
		}
		return $saveDir;
	}

	public function save($argAuthorized=FALSE) {
		// $_FILESを指定の場所に保存
		$mimetype = NULL;
		$resizewidth = NULL;
		$resizeheigth = NULL;
		$proportional = NULL;
		$acl = FALSE;
		// XXX リクエストメソッドによる分岐はココで行うべし！
		if (isset($_POST['mimetype']) && 0 < strlen($_POST['mimetype']) && 0 < strpos($_POST['mimetype'], '/')){
			$mimetype = $_POST['mimetype'];
		}
		if (isset($_POST['resizewidth']) && is_numeric($_POST['resizewidth'])){
			$resizewidth = (int)$_POST['resizewidth'];
		}
		if (isset($_POST['resizeheigth']) && is_numeric($_POST['resizeheigth'])){
			$resizeheigth = (int)$_POST['resizeheigth'];
		}
		if (isset($_POST['proportional']) && 1 === (int)$_POST['proportional']){
			$proportional = 1;
		}
		if (isset($_POST['acl'])){
			$acl = $_POST['acl'];
		}
		return Storage::save(self::getTargetDir(), self::getSaveDir($argAuthorized), NULL, NULL, $mimetype, $resizewidth, $resizeheigth, $proportional, $acl);
	}

	public function load($argAuthorized=FALSE) {
		return Storage::load(self::getTargetDir(), self::getSaveDir($argAuthorized), $_GET['_path_']);
	}

	/*
	 * RESTからの処理用
	 */
	public function get($argRequestParams=NULL) {
		// 通常のMVCにバイパスする
		return $this->load(TRUE);
	}

	/*
	 * RESTからの処理用
	 */
	public function post($argRequestParams=NULL) {
		return $this->save(TRUE);
	}
}

?>