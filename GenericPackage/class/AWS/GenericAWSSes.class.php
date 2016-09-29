<?php

// AWS利用クラスの定義
// use Aws\Common\Aws;
// use Aws\Common\Enum\Region;
use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;

/**
 * Amazon Simple Email Service(Amazon SES)を利用してメールの送信を行います。
 *
 * @author atarun
 * @see <a href="http://aws.amazon.com/ses/">Amazon Simple Email Service</a>
 * @see <a href="http://docs.aws.amazon.com/aws-sdk-php/guide/latest/service-ses.html">AWS SDK for PHP documentation</a>
 */
class GenericAWSSes
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
	 * 初期化します。
	 *
	 * Configureの値をもとにAmazon SESの初期化を行います。
	 */
	protected function _init(){
		if(FALSE === $this->_initialized){
			$apiKey = NULL;
			$apiSecret = NULL;
			$region = NULL;
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_KEY')){
				$apiKey = Configure::AWS_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_SES_API_KEY')){
				$apiKey = Configure::AWS_SES_API_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_SECRET')){
				$apiSecret = Configure::AWS_SECRET;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_SES_API_SECRET')){
				$apiSecret = Configure::AWS_SES_API_SECRET;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_REGION')){
				$region = Configure::AWS_REGION;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('AWS_SES_REGION')){
				$region = Configure::AWS_SES_REGION;
			}
			elseif(defined('PROJECT_NAME') && strlen(PROJECT_NAME) > 0 && class_exists(PROJECT_NAME . 'Configure')){
				$ProjectConfigure = PROJECT_NAME . 'Configure';
				if(NULL !== $ProjectConfigure::constant('AWS_KEY')){
					$apiKey = $ProjectConfigure::AWS_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_SES_API_KEY')){
					$apiKey = $ProjectConfigure::AWS_SES_API_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_SECRET')){
					$apiSecret = $ProjectConfigure::AWS_SECRET;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_SES_API_SECRET')){
					$apiSecret = $ProjectConfigure::AWS_SES_API_SECRET;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_REGION')){
					$region = $ProjectConfigure::AWS_REGION;
				}
				if(NULL !== $ProjectConfigure::constant('AWS_SES_REGION')){
					$region = $ProjectConfigure::AWS_SES_REGION;
				}
			}
			$this->_initialized = TRUE;
			if (NULL === $this->_AWS) {
				$this->_AWS = new SesClient(array(
						'version' => 'latest',
						'region'  => $region,
						'credentials' => array(
							'key'     => $apiKey,
							'secret'  => $apiSecret,
						),
				));
			}
		}
	}

	public function sendSimpleMail($contents) {
		if ($this->_initialized === FALSE) {
			$this->_init();
		}
		try {
			//SESからメール送信
			$mail = $this->_AWS->sendEmail($contents);
			$response = $mail->toArray();
			if (200 !== (int)$response['@metadata']['statusCode']) {
				debug("SendEmail Error!! MessageId:{$response['MessageId']}, StatusCode:{$response['@metadata']['statusCode']}");
				return FALSE;
			}
		} catch (SesException $Exception) {
			debug('ses exception!');
			logging($Exception->__toString(), 'exception');
			// メール送信失敗
			return FALSE;
		}
		return TRUE;
	}
}

?>