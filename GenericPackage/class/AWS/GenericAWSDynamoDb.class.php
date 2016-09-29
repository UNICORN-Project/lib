<?php

// AWS利用クラスの定義
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\DynamoDbClient;
/**
 * Amazon DynamoDBを利用したストレージクラス。
 *
 * @author takatsuki
 * @see <a href="http://aws.amazon.com/jp/dynamodb/">Amazon DynamoDB</a>
 * @see <a href="http://docs.aws.amazon.com/aws-sdk-php/v2/guide/service-dynamodb.html">AWS SDK for PHP documentation</a>
 */
class GenericAWSDynamoDb
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
	 * コンストラクタ
	 * @param  string テーブル名
	 * @param  string テーブルのプライマリーキー名
	 */
    function __construct($argTableName, $argPkeyName) {
		$this->_init();
		if( FALSE === $this->existTable($argTableName) ){
			$this->createTable($argTableName, $argPkeyName);
		}	
    }
	
	/**
	 * 初期化します。
	 *
	 * Configureの値をもとにAmazon DynamoDBの初期化を行います。
	 */
	protected function _init(){
		if(FALSE === $this->_initialized){
			//---------------------------------------------
			// AWS DynamoDb接続定義を取得
			//---------------------------------------------
			$baseArn = NULL;
			$apiKey = NULL;
			$apiSecret = NULL;
			$region = NULL;
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_KEY')){
				$apiKey = Configure::AWS_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_DYNAMODB_API_KEY')){
				$apiKey = Configure::AWS_DYNAMODB_API_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_SECRET')){
				$apiSecret = Configure::AWS_SECRET;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_DYNAMODB_API_SECRET')){
				$apiSecret = Configure::AWS_DYNAMODB_API_SECRET;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_REGION')){
				$region = Configure::AWS_REGION;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_DYNAMODB_REGION')){
				$region = Configure::AWS_DYNAMODB_REGION;
			}
			elseif(defined('PROJECT_NAME') && strlen(PROJECT_NAME) > 0 && class_exists(PROJECT_NAME . 'Configure')){
				$ProjectConfigure = PROJECT_NAME . 'Configure';
				if(NULL !== $ProjectConfigure::constant('AWS_KEY')){
					$apiKey = $ProjectConfigure::AWS_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_DYNAMODB_API_KEY')){
					$apiKey = $ProjectConfigure::AWS_DYNAMODB_API_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_SECRET')){
					$apiSecret = $ProjectConfigure::AWS_SECRET;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_DYNAMODB_API_SECRET')){
					$apiSecret = $ProjectConfigure::AWS_DYNAMODB_API_SECRET;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_REGION')){
					$region = $ProjectConfigure::AWS_REGION;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_DYNAMODB_REGION')){
					$region = $ProjectConfigure::AWS_DYNAMODB_REGION;
				}
			}
			$this->_initialized = TRUE;
			//---------------------------------------------
			// AWS DynamoDb接続
			//---------------------------------------------			
			if (NULL === $this->_AWS) {
				$this->_region = $region;

				$this->_AWS= new DynamoDbClient(array(		
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
				if(NULL === $this->_AWS){
					throw new Exception('dynamodb connect feild.');
				}
				else{
					logging(array('AWS'=>'get dynamodb','key'=>$apiKey, 'secret'=>$apiSecret, 'region'=>$this->_region), 'dynamodb');
				}				
			}

		}
	}

	/**
	 * DynamoDBから単一項目を取得する
	 * @param  string テーブル名
	 * @param  string テーブルのプライマリーキー
	 * @param  string テーブルのプライマリーキー名
	 * @return array  取得レコード
	 */
	public function getData($argTableName, $argPkey, $argPkeyName) {
		$item = array();
		$responseBool = FALSE;
		if(FALSE === $this->_initialized){
			$this->_init();
		}		
		try {
			if ( NULL !== $argTableName && NULL !== $argPkey && NULL !== $argPkeyName) {
				$response = $this->_AWS->getItem([
					'TableName' => $argTableName,
					'Key' => [
						$argPkeyName => [ 'S' => $argPkey]
					]					
				]);

				if( NULL !== $response['Item'] ){
					$response = $response['Item'];
					foreach ($response as $key => $value) {
						$item[$key] = current($value);
						debug('$item['.$key.']='.current($value));
					}
					$responseBool = TRUE;					
				}
			}
			logging(array('dynamodb'=>'getItem','key'=>$argPkey, 'response'=>$responseBool), 'dynamodb');
		} catch (DynamoDbException $ex) {
			logging($ex->__toString(), 'exception');
		}
		
		return $item;
	}	

	/**
	 * DynamoDBの項目更新
	 * @param  string テーブル名
	 * @param  array  テーブルにプットするレコード
	 * @return bool   更新結果(TRUE:成功 FALSE:失敗)
	 */
	public function putData($argTableName, $argItem) {
		$responseBool = FALSE;
		$response = array();
		if(FALSE === $this->_initialized){
			$this->_init();
		}		
		try {
			if ( NULL !== $argTableName && count($argItem) > 0 ) {
				$response = $this->_AWS->putItem([
					'TableName' => $argTableName,
					'ReturnConsumedCapacity' => 'TOTAL'	,					
					'Item' => $argItem
				]);
			}
			logging(array('dynamodb'=>'putItem','val'=>$argItem, 'response'=>$response), 'dynamodb');	
		} catch (DynamoDbException $ex) {
			$responseBool = FALSE;
			logging($ex->__toString(), 'exception');
		} 
		
		if( count($response) > 0 ){
			$responseBool = TRUE;
		}else{
			$responseBool = FALSE;
		}

		return $responseBool;
	}

	/**
	 * DynamoDBの項目削除
	 * @param  string テーブル名
	 * @param  string テーブルのプライマリーキー
	 * @param  string テーブルのプライマリーキー名
	 * @return bool   更新結果(TRUE:成功 FALSE:失敗)
	 */
	public function deleteData($argTableName,$argPkey,$argPkeyName) {
		if(FALSE === $this->_initialized){
			$this->_init();
		}		
		try {
			if (NULL !== $argTableName  && NULL !== $argPkey && NULL !== $argPkeyName){
				$this->_AWS->deleteItem([
					'TableName' => $argTableName,
					'Key' => [
						$argPkeyName => [
							'S' => $argPkey
						]
					]
				]);
			}
		} catch (DynamoDbException $ex) {
			logging($ex->__toString(), 'exception');
		}
		logging(array('dynamodb'=>'deleteItem','key'=>$argPkey), 'dynamodb');
		return TRUE;
	}
	/**
	 * DynamoDBにテーブルを作成する。
	 * @param  string テーブル名
	 * @return bool   更新結果(TRUE:成功 FALSE:失敗)
	 */
	public function createTable($argTableName, $argPkeyName) {
		$responseBool = FALSE;
		if(FALSE === $this->_initialized){
			$this->_init();
		}		
		try{
				//セッションテーブル
				$responseBool = $this->_AWS->createTable(array(
					'TableName' => $argTableName,
					'AttributeDefinitions' => array(
						array(
							'AttributeName' => $argPkeyName,
							'AttributeType' => 'S'
						)
					),
					'KeySchema' => array(
						array(
							'AttributeName' => $argPkeyName,
							'KeyType'       => 'HASH'
						)
					),
					'ProvisionedThroughput' => array(
						'ReadCapacityUnits'  => 5,
						'WriteCapacityUnits' => 5
					)
				));
		}
		catch(DynamoDbException $ex){
			$responseBool = FALSE;
			logging($ex->__toString(), 'exception');
		}

		logging(array('dynamodb'=>'create_table','table'=>$argTableName, 'response'=>$responseBool), 'dynamodb');
		return TRUE;
	}	
	/**
	 * DynamoDBのテーブルの存在確認をする
	 * @param  string テーブル名
	 * @return bool   存在の有無
	 */
	public function existTable($argTableName) {
		$existBool = FALSE;
		if(FALSE === $this->_initialized){
			$this->_init();
		}		
		try{
			$param = array();
			$response = $this->_AWS->ListTables(array(
				'Limit'=>10
			));

			$tableName =$response['TableNames'];
			foreach ($tableName as $key => $value){
				if($value === $argTableName){
					$existBool = TRUE;
					break;
				}else{
					$existBool = FALSE;
				}
			}
			logging(array('dynamodb'=>'listTables','table'=>$argTableName, 'exist'=>$existBool,'response'=>$response), 'dynamodb');			
		}
		catch(DynamoDbException $ex){
			$existBool = FALSE;
			logging($ex->__toString(), 'exception');
		}
		return $existBool;
	}	
}

?>