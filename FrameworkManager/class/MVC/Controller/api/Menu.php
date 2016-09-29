<?php

class Menu extends RestControllerBase {

	public $virtualREST = TRUE;
	
	public function get($argRequestParams=NULL){
		// プロジェクトに紐付くメニューを返す
		return ProjectManager::getProjectManageMenu($_GET['target_project']);
	}

	public function post($argRequestParams=NULL){
		// このRESTは実行出来ない
		throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, 405);
	}

	public function put($argRequestParams=NULL){
		// このRESTは実行出来ない
		throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, 405);
	}

	public function delete($argRequestParams=NULL){
		// このRESTは実行出来ない
		throw new RESTException(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, 405);
	}
}

?>