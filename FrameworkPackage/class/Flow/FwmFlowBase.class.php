<?php

class FwmFlowBase extends WebFlowControllerBase {

	public $permission = NULL;

	protected function _initWebFlow(){
		static $tokenChecked = FALSE;
		// この機能のパーミッションを取得する
		$thisPermission = ProjectManager::getProjectManagePermission(basename(getConfig('PROJECT_ROOT_PATH')), get_class($this));
		if (NULL === $thisPermission){
			// メニューから取れていない場合は自分自身のパーミッションを採用する
			$thisPermission = $this->permission;
		}
		// フレームワークマネージャー用のプライベートフローは、サーバー間認証必須
		if (FALSE === $tokenChecked && FALSE === AccessTokenAuth::validate($thisPermission)){
			// アクセス禁止
			$this->httpStatus = 405;
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, 405);
		}
		$tokenChecked = TRUE;
		return parent::_initWebFlow();
	}
}

?>