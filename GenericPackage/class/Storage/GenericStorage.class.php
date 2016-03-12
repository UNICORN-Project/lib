<?php

ini_set("memory_limit" ,"128M");

class GenericStorage
{
	const UPLOAD_TARGET_TYPE_S3 = 'S3';

	public static function isS3($argSaveDir, $argFilePath){
		return self::is(self::UPLOAD_TARGET_TYPE_S3, $argSaveDir, $argFilePath);
	}

	public static function loadS3($argSaveDir, $argFilePath){
		return self::load(self::UPLOAD_TARGET_TYPE_S3, $argSaveDir, $argFilePath);
	}

	public static function saveS3($argSaveDir, $argFileBodies=NULL, $argFileNames=NULL, $argMimeType=NULL, $argResizeWidth=NULL, $argResizeHeigth=NULL, $argProportional=NULL, $argACL=FALSE){
		return self::save(self::UPLOAD_TARGET_TYPE_S3, $argSaveDir, $argFileBodies, $argFileNames, $argMimeType, $argResizeWidth, $argResizeHeigth, $argProportional, $argACL);
	}

	public static function is($argUploadTargetDir, $argSaveDir, $argFilePath){
		if (self::UPLOAD_TARGET_TYPE_S3 === strtoupper($argUploadTargetDir)){
			// S3上に上がっているファイルなのかどうか
			$StorageEngine = new WebStorage();
			if(TRUE !== $StorageEngine->is($argFilePath)){
				return FALSE;
			}
		}
		else {
			// 通常のアップロード済みファイルの取得処理
			$saveDir = str_replace('//', '/', $argUploadTargetDir . '/' . $argSaveDir);
			if (!is_dir($saveDir)){
				return FALSE;
			}
			$filePath = str_replace('//', '/', $argUploadTargetDir . '/' . $argSaveDir . '/' . $argFilePath);
			if (0 === strpos($argFilePath, $argSaveDir)){
				$filePath = str_replace('//', '/', $argUploadTargetDir . '/' . $argFilePath);
			}
			if(!is_file($filePath)){
				return FALSE;
			}
		}
		return TRUE;
	}

	public static function load($argUploadTargetDir, $argSaveDir, $argFilePath){
		$fileBody = NULL;
		if (self::UPLOAD_TARGET_TYPE_S3 === strtoupper($argUploadTargetDir)){
			// S3からダウンロード
			$StorageEngine = new WebStorage();
			$fileBody = $StorageEngine->loadBinary($argFilePath);
			if(!(0 < strlen($fileBody))){
				// 取得エラー
				throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
			}
		}
		else {
			// 通常のアップロード済みファイルの取得処理
			$saveDir = str_replace('//', '/', $argUploadTargetDir . '/' . $argSaveDir);
			if (!is_dir($saveDir)){
				// 参照エラー(403 Forbidden)
				throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, 403);
			}
			$filePath = str_replace('//', '/', $argUploadTargetDir . '/' . $argSaveDir . '/' . $argFilePath);
			if (0 === strpos($argFilePath, $argSaveDir)){
				$filePath = str_replace('//', '/', $argUploadTargetDir . '/' . $argFilePath);
			}
			$fileBody = file_get_contents($filePath);
			if(!(0 < strlen($fileBody))){
				// 参照エラー(404 Not Found)
				throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, 404);
			}
		}
		return $fileBody;
	}

	public static function save($argUploadTargetDir, $argSaveDir, $argFileBodies=NULL, $argFileNames=NULL, $argMimeType=NULL, $argResizeWidth=NULL, $argResizeHeigth=NULL, $argProportional=NULL, $argACL=FALSE){
		$files = NULL;
		$names = array();
		if (NULL === $argFileBodies){
			if (isset($_FILES) && 0 < count($_FILES)){
				$files = $_FILES;
			}
		}
		else {
			$files = $argFileBodies;
		}
		if (NULL === $files){
			// アップロード処理するものが無かった
			return NULL;
		}
		if (0 === strpos($argSaveDir, '/')){
			// 相対パスで無い場合はエラー
			return FALSE;
		}

		$fileIdx = -1;
		foreach($files as $file){
			$fileIdx++;
			$fileCnt = 1;
			$subFileKeys = NULL;
			// multipartのファイルアップロード
			if (isset($file['name']) && isset($file['tmp_name']) && isset($file['type'])){
				if (isset($file['error']) && is_array($file['error'])){
					$fileCnt = count($file['error']);
					$subFileKeys = array_keys($file['error']);
				}
			}
			for ($fileSIdx=0; $fileSIdx < $fileCnt; $fileSIdx++){
				$mimeType = NULL;
				$suffix = '';
				$fileBody = NULL;
				$fileName = NULL;
				if (isset($file['name']) && isset($file['tmp_name']) && isset($file['type'])){
					if (1 < $fileCnt && NULL !== $subFileKeys && is_array($subFileKeys)){
						$subFileKey = $subFileKeys[$fileSIdx];
						if (isset($file['error']) && isset($file['error'][$subFileKey]) && UPLOAD_ERR_OK == $file['error'][$subFileKey]){
							$suffix = pathinfo($file['name'][$subFileKey], PATHINFO_EXTENSION);
							$fileBody = file_get_contents($file['tmp_name'][$subFileKey]);
							$fileName = $file['tmp_name'][$subFileKey];
						}
						else {
							// アップロードエラー
							throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
						}
					}
					else if(UPLOAD_ERR_OK == $file['error']){
						$suffix = pathinfo($file['name'], PATHINFO_EXTENSION);
						$fileBody = file_get_contents($file['tmp_name']);
						$fileName = $file['tmp_name'];
					}
					else {
						// アップロードエラー
						throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
					}
				}
				else {
					$suffix = pathinfo($argFileNames[$fileIdx], PATHINFO_EXTENSION);
					$fileBody = $file;
					$fileName = $argFileNames[$fileIdx];
				}
				if (NULL === $fileBody || 0 >= strlen($fileBody)){
					continue;
				}
				// mimeTypeのチェック
				if (NULL !== $argMimeType){
					if (0 === strpos($argMimeType, 'image/')){
						// 画像の場合
						$info = Image::info($fileBody);
						if (FALSE === $info){
							continue;
						}
						if(FALSE === Image::checkMimeType($info, $argMimeType)){
							continue;
						}
					}
					// XXX 画像以外の場合にmiimeTypeのチェックがあればココに実装
				}
				// 画像のりサイズ
				$useResize = FALSE;
				$resizeWidth = 0;
				$resizeHeigth = 0;
				if (NULL !== $argResizeWidth && is_numeric($argResizeWidth)){
					$resizeWidth = $argResizeWidth;
					$useResize = TRUE;
				}
				if (NULL !== $argResizeHeigth && is_numeric($argResizeHeigth)){
					$resizeHeigth = $argResizeHeigth;
					$useResize = TRUE;
				}
				if (TRUE === $useResize){
					$proportional = FALSE;
					if (NULL !== $argProportional && 1 === (int)$argProportional){
						$proportional = TRUE;
					}
					$fileBody = Image::resize($fileBody, $resizeWidth, $resizeHeigth, $proportional);
				}
				// mimeTypeチェックがOKだったので、Upload処理を実行
				$DateObj = new DateTime();
				$DateObj->setTimezone(new DateTimeZone('GMT'));
				$now = $DateObj->format('Ymdhis');
				$fileName = sha1($fileName).$now;
				if (0 < strlen($suffix)){
					if ('jpeg' === $suffix){
						$suffix = 'jpg';
					}
					$fileName .= '.' . strtolower($suffix);
				}
				$fileName = str_replace('//', '/', $argSaveDir . '/' . $fileName);
				if (self::UPLOAD_TARGET_TYPE_S3 === strtoupper($argUploadTargetDir)){
					// S3にアップロード
					$StorageEngine = new WebStorage();
					$res = $StorageEngine->saveBinary($fileName, $fileBody, NULL, $argACL);
					if(FALSE === $res){
						// 保存エラー
						throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
					}
					if ('public-read' === strtolower($argACL)){
						// Public readならS3のURLをそのまま帰す
						$fileName = $res;
					}
				}
				else {
					// 通常アップロード処理
					$saveDir = str_replace('//', '/', $argUploadTargetDir . '/' . $argSaveDir);
					if (!is_dir($saveDir)){
						@mkdir($saveDir, 0666, true);
						@exec('chmod -R 0666 ' .$saveDir);
					}
					$fileName = str_replace('//', '/', $argSaveDir . '/' . $fileName);
					if(!file_put_contents(str_replace('//', '/', $argUploadTargetDir . '/' . $fileName), $fileBody)){
						// 保存エラー
						throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
					}
				}
				unset($fileBody);
				$names[] = $fileName;
			}
		}
		return $names;
	}
}

?>