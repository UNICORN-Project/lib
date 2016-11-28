<?php

// AWS利用クラスの定義
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

/**
 * Amazon Simple Storage Service(Amazon S3)を利用したストレージクラス。
 *
 * @author saimushi
 * @see <a href="http://aws.amazon.com/s3/">Amazon Simple Notification Service</a>
 * @see <a href="http://docs.aws.amazon.com/aws-sdk-php/guide/latest/service-s3.html">AWS SDK for PHP documentation</a>
 */
class GenericAWSS3
{
	/**
	 * @var integer 初期化フラグ
	 */
	protected $_initialized = FALSE;

	/**
	 * @var Aws Amazon SDKのインスタンス
	 */
	protected $_AWS = NULL;

	/**
	 * @var AWSリージョン値
	 */
	protected $_region = NULL;

	/**
	 * @var 作業ファイル保存先ディレクトリパス
	 */
	protected $_tmpPath = NULL;

	/**
	 * 初期化します。
	 *
	 * Configureの値をもとにAmazon S3の初期化を行います。
	 */
	protected function _init(){
		if(FALSE === $this->_initialized){
			$baseArn = NULL;
			$apiKey = NULL;
			$apiSecret = NULL;
			$region = NULL;
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_KEY')){
				$apiKey = Configure::AWS_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_S3_API_KEY')){
				$apiKey = Configure::AWS_S3_API_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_SECRET')){
				$apiSecret = Configure::AWS_SECRET;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_S3_API_SECRET')){
				$apiSecret = Configure::AWS_S3_API_SECRET;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_REGION')){
				$region = Configure::AWS_REGION;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_S3_REGION')){
				$region = Configure::AWS_S3_REGION;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('TMP_PATH')){
				$this->_tmpPath = Configure::TMP_PATH;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_S3_TMP_DIR')){
				$this->_tmpPath = Configure::AWS_S3_TMP_DIR;
			}
			if(defined('PROJECT_NAME') && strlen(PROJECT_NAME) > 0 && class_exists(PROJECT_NAME . 'Configure')){
				$ProjectConfigure = PROJECT_NAME . 'Configure';
				if(NULL !== $ProjectConfigure::constant('AWS_KEY')){
					$apiKey = $ProjectConfigure::AWS_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_S3_API_KEY')){
					$apiKey = $ProjectConfigure::AWS_S3_API_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_SECRET')){
					$apiSecret = $ProjectConfigure::AWS_SECRET;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_S3_API_SECRET')){
					$apiSecret = $ProjectConfigure::AWS_S3_API_SECRET;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_REGION')){
					$region = $ProjectConfigure::AWS_REGION;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_S3_REGION')){
					$region = $ProjectConfigure::AWS_S3_REGION;
				}
				if(NULL !== $ProjectConfigure::constant('TMP_PATH')){
					$this->_tmpPath = $ProjectConfigure::TMP_PATH;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_S3_TMP_DIR')){
					$this->_tmpPath = $ProjectConfigure::AWS_S3_TMP_DIR;
				}
			}
			debug('tmpPath='.$this->_tmpPath);
			$this->_initialized = TRUE;
			if (NULL === $this->_AWS) {
				$this->_region = $region;
				$this->_AWS = new S3Client(array(
						'version' => 'latest',
						'region'  => $this->_region,
						'curl.options' => array(
								'CURLOPT_CONNECTTIMEOUT' => 300
						),
						'credentials' => array(
							'key'     => $apiKey,
							'secret'  => $apiSecret,
						),
				));
			}
		}
	}

	/**
	 * ファイル( or バイナリ)を指定されたS3バケットに保存します。
	 */
	public function saveBinary($argFileKey, $argBinary, $argFileMimeType=NULL, $argACL=FALSE, $argExpires=NULL, $argStorageClass='REDUCED_REDUNDANCY', $argTimeout=300) {
		$this->_init();
		if(NULL === $this->_tmpPath){
			// XXX 定義エラー
			// 一次領域の定義が無い時、saveBinaryは機能しない！
			return FALSE;
		}
		$this->_tmpPath = str_replace('//', '/', $this->_tmpPath);
		@exec('mkdir '.$this->_tmpPath);
		@exec('chmod -R 0777 ' .$this->_tmpPath);
		// 一次領域に保存
		$filePath = str_replace('//', '/', $this->_tmpPath.'/s3tmp'.sha1($argFileKey.time()));
		if(FALSE === @file_put_contents($filePath, $argBinary)){
			return FALSE;
		}
		$res = $this->save($argFileKey, $filePath, $argFileMimeType, $argACL, $argExpires, $argStorageClass, $argTimeout);
		// ゴミ掃除
		@unlink($filePath);
		return $res;
	}

	/**
	 * 指定されたファイルパス上のファイルを指定されたS3バケットに保存します。
	 */
	public function save($argFileKey, $argFilePath, $argFileMimeType=NULL, $argACL=FALSE, $argExpires=NULL, $argStorageClass='REDUCED_REDUNDANCY', $argTimeout=300) {
		$filePath = NULL;
		$this->_init();
		// S3の指定バケットにファイルをアップロードする
		try {
			$paths = explode('/', $argFileKey);
			$bucket = $paths[0];
			$bucketKey = substr($argFileKey, strlen($bucket)+1);
			$property = array('Bucket' => $bucket,
					'Key'    => $bucketKey,
					'SourceFile' => $argFilePath,
					'StorageClass' => $argStorageClass,
					// XXX 暗号化の設定はまた今度
					// 'ServerSideEncryption' => 'AES256', 暗号はせずにファイルをアップロード
			);
			if (NULL !== $argFileMimeType){
				$property['ContentType'] = $argFileMimeType;
			}
			if (NULL !== $argExpires){
				$property['Expires'] = $argExpires;
			}
			if (FALSE !== $argACL){
				$property['ACL'] = $argACL;
			}
			$response = $this->_AWS->putObject($property);
			$filePath = '//s3-' . $this->_region . '.amazonaws.com/' . $bucket . '/' . $bucketKey;
		}
		catch (S3Exception $Exception) {
			logging($Exception->__toString(), 'exception');
			// S3へのアップロード失敗
			return FALSE;
		}
		return $filePath;
	}

	/**
	 * 指定されたファイルパスのファイルがS3にある場合にTRUEを返します。
	 */
	public function is($argFileKey, $argTimeout=300) {
		$this->_init();
		// S3の指定バケットにファイルをアップロードする
		try {
			$domain = '//s3-' . $this->_region . '.amazonaws.com/';
			if (FALSE !== strpos($argFileKey, $domain)){
				$argFileKey = str_replace($domain, '', $argFileKey);
			}
			$paths = explode('/', $argFileKey);
			$bucket = $paths[0];
			$bucketKey = substr($argFileKey, strlen($bucket)+1);
			$property = array('Bucket' => $bucket,
				'Key'    => $bucketKey,
			);
			$response = $this->_AWS->getObjectAcl($property);
			if(!(isset($response['Owner']) && is_array($response['Owner']))){
				return FALSE;
			}
		}
		catch (S3Exception $Exception) {
			logging($Exception->__toString(), 'exception');
			// S3へのアップロード失敗
			return FALSE;
		}
		return TRUE;
	}


	/**
	 * 指定されたファイルパスのファイルがS3にある場合にTRUEを返します。
	 */
	public function unlink($argFileKey, $argTimeout=300) {
		$this->_init();
		// S3の指定バケットにファイルをアップロードする
		try {
			$domain = '//s3-' . $this->_region . '.amazonaws.com/';
			if (FALSE !== strpos($argFileKey, $domain)){
				$argFileKey = str_replace($domain, '', $argFileKey);
			}
			$paths = explode('/', $argFileKey);
			$bucket = $paths[0];
			$bucketKey = substr($argFileKey, strlen($bucket)+1);
			$property = array('Bucket' => $bucket,
				'Key'    => $bucketKey,
			);
			$response = $this->_AWS->deleteObject($property);
			if(!(isset($response['RequestCharged']) && is_string($response['RequestCharged']))){
				return FALSE;
			}
		}
		catch (S3Exception $Exception) {
			logging($Exception->__toString(), 'exception');
			// S3へのアップロード失敗
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * ファイルをコピーします(ムーブします。)
	 */
	public function copy($argFromFileKey, $argToFileKey, $argMoveEnabled=FALSE, $argTimeout=300) {
		$this->_init();
		// S3の指定バケットにファイルをアップロードする
		try {
			$domain = '//s3-' . $this->_region . '.amazonaws.com/';
			if (FALSE !== strpos($argFromFileKey, $domain)){
				$argFromFileKey = str_replace($domain, '', $argFromFileKey);
			}
				if (FALSE !== strpos($argToFileKey, $domain)){
				$argToFileKey = str_replace($domain, '', $argToFileKey);
			}
			if (TRUE !== $this->is($argFromFileKey, $argTimeout)){
				return FALSE;
			}
			$paths = explode('/', $argToFileKey);
			$bucket = $paths[0];
			$bucketKey = substr($argToFileKey, strlen($bucket)+1);
			$bucketKeyFrom = substr($argFromFileKey, strlen($bucket)+1);
			$property = array('Bucket' => $bucket,
				'Key' => $bucketKey,
				'CopySource' => $bucket.'/'.rawurlencode($bucketKeyFrom)
			);
			$response = $this->_AWS->copyObject($property);
			if(!(isset($response['CopySourceVersionId']) && is_string($response['CopySourceVersionId']))){
				return FALSE;
			}
			// 移動判定
			if (TRUE === $argMoveEnabled){
				// 移動なので、元ファイルを削除
				return $this->unlink($argFromFileKey, $argTimeout);
			}
		}
		catch (S3Exception $Exception) {
			logging($Exception->__toString(), 'exception');
			// S3へのアップロード失敗
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * 指定されたファイルパスのファイルのS3URLを返します。
	 */
	public function load($argFileKey, $argFileMimeType=NULL, $argACL=FALSE, $argTimeout=300) {
		$filePath = NULL;
		$this->_init();
		if (TRUE === $this->is($argFileKey, $argTimeout)){
			$filePath = '//s3-' . $this->_region . '.amazonaws.com/' . $argFileKey;
		}
		return $filePath;
	}

	/**
	 * 指定されたファイルパスのファイルをS3バケットから取得します。
	 */
	public function loadBinary($argFileKey, $argFileMimeType=NULL, $argACL=FALSE, $argTimeout=300) {
		$Binary = NULL;
		$this->_init();
		// S3の指定バケットにファイルをアップロードする
		try {
			$paths = explode('/', $argFileKey);
			$bucket = $paths[0];
			$bucketKey = substr($argFileKey, strlen($bucket)+1);
			$property = array('Bucket' => $bucket,
				'Key'    => $bucketKey,
			);
			$response = $this->_AWS->getObject($property);
			if(!(isset($response['Body']) && is_object($response['Body']))){
				return FALSE;
			}
			$Binary = $response['Body'];
		}
		catch (S3Exception $Exception) {
			logging($Exception->__toString(), 'exception');
			// S3へのアップロード失敗
			return FALSE;
		}
		return $Binary;
	}

	/**
	 * 期限付きURLの取得
	 */
	public function getURL($argFileKey, $argExpires='+10 minutes', $argTimeout=300) {
		$url = FALSE;
		$this->_init();
		// S3の指定バケットにファイルをアップロードする
		try {
			$paths = explode('/', $argFileKey);
			$bucket = $paths[0];
			$bucketKey = substr($argFileKey, strlen($bucket)+1);
			$cmd = $this->_AWS->getCommand('GetObject', [
				'Bucket' => $bucket,
				'Key'    => $bucketKey
			]);
			$request = $this->_AWS->createPresignedRequest($cmd, $argExpires);
			$response = (string)$request->getUri();
			if(!(0 < strlen($response))){
				return FALSE;
			}
			$url = $response;
		}
		catch (S3Exception $Exception) {
			logging($Exception->__toString(), 'exception');
			// S3へのアップロード失敗
			return FALSE;
		}
		return $url;
	}
}

?>