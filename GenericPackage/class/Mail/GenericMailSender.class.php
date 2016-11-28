<?php

class GenericMailSender
{
	const MAIL_TYPE_SES = 'SES';
	const MAIL_TYPE_SMTP = 'smtp';

	protected static $_initialized = FALSE;
	public static $mailType = 'smtp';
	public static $fromAddr = 'webmaster@example.com';
	public static $charset = 'iso-2022-jp';
	public static $smtpHost = 'tls://smtp.gmail.com';
	public static $smtpPort = '465';
	public static $smtpUser = '';
	public static $smtpPass = '';

	public static function init() {
		if (FALSE === self::$_initialized){
			self::$_initialized = TRUE;
			if(class_exists('Configure') && NULL !== Configure::constant('MAIL_TYPE')){
				self::$mailType = Configure::MAIL_TYPE;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('MAIL_FROM')){
				self::$fromAddr = Configure::MAIL_FROM;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SMTP_HOST')){
				self::$smtpHost = Configure::SMTP_HOST;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SMTP_PORT')){
				self::$smtpPort = Configure::SMTP_PORT;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SMTP_USER')){
				self::$smtpUser = Configure::SMTP_USER;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SMTP_PASS')){
				self::$smtpPass = Configure::SMTP_PASS;
			}
			if(defined('PROJECT_NAME') && strlen(PROJECT_NAME) > 0 && class_exists(PROJECT_NAME . 'Configure')){
				$ProjectConfigure = PROJECT_NAME . 'Configure';
				if(NULL !== $ProjectConfigure::constant('MAIL_TYPE')){
					self::$mailType = $ProjectConfigure::MAIL_TYPE;
				}
				if(NULL !== $ProjectConfigure::constant('MAIL_FROM')){
					self::$fromAddr = $ProjectConfigure::MAIL_FROM;
				}
				if(NULL !== $ProjectConfigure::constant('SMTP_HOST')){
					self::$smtpHost = $ProjectConfigure::SMTP_HOST;
				}
				if(NULL !== $ProjectConfigure::constant('SMTP_PORT')){
					self::$smtpPort = $ProjectConfigure::SMTP_PORT;
				}
				if(NULL !== $ProjectConfigure::constant('SMTP_USER')){
					self::$smtpUser = $ProjectConfigure::SMTP_USER;
				}
				if(NULL !== $ProjectConfigure::constant('SMTP_PASS')){
					self::$smtpPass = $ProjectConfigure::SMTP_PASS;
				}
			}
		}
	}

	public static function send($to, $subject, $body, $from = NULL, $cc = NULL, $bcc = NULL, $replyTo = NULL, $returnPath = NULL, $argViaMailType = NULL, $argSMTPHost = NULL, $argSMTPUser = NULL, $argSMTPPass = NULL) {
		self::init();
		$result = FALSE;
		if (NULL === $argViaMailType){
			if (self::MAIL_TYPE_SES === self::$mailType){
				$result = self::sendMailSes($to, $subject, $body, $from, $cc, $bcc, $replyTo, $returnPath);
			}
			else {
				$result = self::sendMailSmtp($to, $subject, $body, $from, $cc, $bcc, $replyTo, $returnPath, $argSMTPHost, $argSMTPUser, $argSMTPPass);
			}
		}
		else if (self::MAIL_TYPE_SES === $argViaMailType){
			$result = self::sendMailSes($to, $subject, $body, $from, $cc, $bcc, $replyTo, $returnPath);
		}
		else {
			$result = self::sendMailSmtp($to, $subject, $body, $from, $cc, $bcc, $replyTo, $returnPath, $argSMTPHost, $argSMTPUser, $argSMTPPass);
		}
		return $result;
	}

	public static function sendMailSes($to, $subject, $body, $from = NULL, $cc = NULL, $bcc = NULL, $replyTo = NULL, $returnPath = NULL) {
		self::init();
		// to
		$toAddress = array();
		$destination = array();
		$message = array();
		if (is_array($to)){
			foreach ($to as $value){
				if (preg_match('/^[0-9a-z_\.\/\?-]+[0-9a-z_\.+\/\?-]+@([0-9a-z-]+\.)+[0-9a-z-]+$/i', $value)){
					$destination['ToAddresses'][] = $value;
				}
			}
		} else if (is_string($to) && 0 < strlen($to)){
			if (preg_match('/^[0-9a-z_\.\/\?-]+[0-9a-z_\.+\/\?-]+@([0-9a-z-]+\.)+[0-9a-z-]+$/i', $to)){
				$destination['ToAddresses'][] = $to;
			}
		}
		// 宛先は必ず指定する必要がある
		if (empty($destination['ToAddresses'])){
			return FALSE;
		}
		// cc
		if (is_array($cc)){
			foreach ($cc as $value){
				if (preg_match('/^[0-9a-z_\.\/\?-]+[0-9a-z_\.+\/\?-]+@([0-9a-z-]+\.)+[0-9a-z-]+$/i', $value)){
					$destination['CcAddresses'][] = $value;
				}
			}
		} else if (is_string($cc) && 0 < strlen($cc)){
			if (preg_match('/^[0-9a-z_\.\/\?-]+[0-9a-z_\.+\/\?-]+@([0-9a-z-]+\.)+[0-9a-z-]+$/i', $cc)){
				$destination['CcAddresses'][] = $cc;
			}
		}
		// bcc
		if (is_array($bcc)){
			foreach ($bcc as $value){
				if (preg_match('/^[0-9a-z_\.\/\?-]+[0-9a-z_\.+\/\?-]+@([0-9a-z-]+\.)+[0-9a-z-]+$/i', $value)){
					$destination['BccAddresses'][] = $value;
				}
			}
		} else if (is_string($bcc) && 0 < strlen($bcc)){
			if (preg_match('/^[0-9a-z_\.\/\?-]+[0-9a-z_\.+\/\?-]+@([0-9a-z-]+\.)+[0-9a-z-]+$/i', $bcc)){
				$destination['BccAddresses'][] = $bcc;
			}
		}
		// subject
		$message['Subject'] = array('Data' => $subject, 'Charset' => self::$charset);
		// body
		if (is_array($body)){
			$message['Body'] = array();
			if (is_array($body) && array_key_exists('text', $body) && 0 < strlen($body['text'])){
				$textMessage = array('Text' => array('Data' => $body['text'], 'Charset' => self::$charset));
				$message['Body'] = array_merge($message['Body'], $textMessage);
			}
			if (is_array($body) && array_key_exists('html', $body) && 0 < strlen($body['html'])){
				$htmlMessage = array('Html' => array('Data' => $body['html'], 'Charset' => self::$charset));
				$message['Body'] = array_merge($message['Body'], $htmlMessage);
			}
		}
		else if (is_string($body)){
			$textMessage = array('Text' => array('Data' => $body, 'Charset' => self::$charset));
			$message['Body'] = array_merge($message['Body'], $textMessage);
		}
		else {
			return FALSE;
		}
		// from
		if (NULL === $from){
			$from = self::$fromAddr;
		}
		// replyTo
		if (NULL === $replyTo) {
			$replyTo = array($from);
		} else if (FALSE === is_array($replyTo)) {
			$replyTo = array($replyTo);
		}
		$contents = array(
			'Source' => $from,
			'Destination' => $destination,
			'Message' => $message,
			'ReplyToAddresses' => $replyTo,
// 			'ReturnPath' => '<string>',
// 			'ReturnPathArn' => '<string>',
		);
		$mail = new SimpleEmailSender();
		$result = $mail->send($contents);
		return $result;
	}

	/**
	 * 外部smtpサーバに接続してメールを送信する
	 * ※ GmailアカウントのSMTPを利用する場合、こちらにアクセスして安全性の低いアプリのアクセスをオンにするを選択してください。
	 *   > https://www.google.com/settings/security/lesssecureapps
	 * @return
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param string $from
	 * @param string $bcc
	 * @param string メールアドレス(エラーメール返信先)
	 * @param string 拡張ヘッダー
	 */
	public static function sendMailSmtp($to, $subject, $body, $from = NULL, $cc = NULL, $bcc = NULL, $replyTo = NULL, $returnPath = NULL, $argSMTPHost = NULL, $argSMTPPort = NULL, $argSMTPUser = NULL, $argSMTPPass = NULL) {
		self::init();
		$body = preg_replace("/\n/", "\r\n", $body);

		if (NULL === $from){
			$from = self::$fromAddr;
		}
		if (NULL === $argSMTPHost){
			$argSMTPHost = self::$smtpHost;
		}
		if (NULL === $argSMTPPort){
			$argSMTPPort = self::$smtpPort;
		}
		if (NULL === $argSMTPUser){
			$argSMTPUser = self::$smtpUser;
		}
		if (NULL === $argSMTPPass){
			$argSMTPPass = self::$smtpPass;
		}

		$connect = fsockopen($argSMTPHost, $argSMTPPort, $errno, $errstr, 30);
	
		if (!$connect) {
			return false;
		}

		if (0 < strlen($argSMTPUser)){
			//fputs($connect, 'HELO '.$_SERVER['SERVER_ADDR']."\r\n");
			//fputs($connect, 'AUTH XOAUTH'."\r\n");
			// XXX Gmail-smtp 「安全性の低いアプリの許可」-有効に一旦最適化
			fputs($connect, 'HELO '.$_SERVER['SERVER_ADDR']."\r\n");
			$res = fgets($connect, 1024);
			fputs($connect, 'AUTH LOGIN'."\r\n");
			if (!$res) {
				return false;
			}
			fputs($connect, base64_encode($argSMTPUser)."\r\n");
			fputs($connect, base64_encode($argSMTPPass)."\r\n");
		}
		else {
			fputs($connect, "HELO ".$_SERVER['SERVER_NAME']."\r\n");
		}

		$res = fgets($connect, 1024);
		if (!$res) {
			return false;
		}

		fputs($connect, "MAIL FROM: <$from>\r\n");
		$res = fgets($connect, 1024);
		if (!$res) {
			return false;
		}

		$rcptToArray = array ();
		$toArray = array ();
		$bccArray = array ();
	
		if (is_array($to)) {
			$rcptToArray = array_merge($rcptToArray, $to);
			$toArray = array_merge($toArray, $to);
		}
		else {
			$rcptToArray = array_merge($rcptToArray, preg_split("/,/", $to));
			$toArray = array_merge($toArray, preg_split("/,/", $to));
		}
		if (is_array($bcc)) {
			$rcptToArray = array_merge($rcptToArray, $bcc);
			$bccArray = array_merge($bccArray, $bcc);
		}
		else {
			$rcptToArray = array_merge($rcptToArray, explode(",", $bcc));
			$bccArray = array_merge($bccArray, preg_split("/,/", $bcc));
		}
		foreach ($rcptToArray as $rcptTo) {
			if ($rcptTo) {
				fputs($connect, "RCPT TO: <$rcptTo>\r\n");
				$res = fgets($connect, 1024);
				if (!$res) {
					return false;
				}
			}
		}
		fputs($connect, "DATA\r\n");
		$res = fgets($connect, 1024);
		if (!$res) {
			return false;
		}
		// from
		fputs($connect, "From: $from\r\n");
		fputs($connect, "To: ".join(",", $toArray)."\r\n");
		fputs($connect, "X-SM-Envelope-From: ".$from."\r\n");
		fputs($connect, "Content-type: text/plain; charset=\"".strtoupper(self::$charset)."\"\r\n");
		fputs($connect, "Content-Transfer-Encoding: 7bit\r\n");
		fputs($connect, "Subject: ".mb_encode_mimeheader($subject)."\r\n");
		fputs($connect, "Date: ".date("r")."\r\n");
		fputs($connect, "Content-Transfer-Encoding: 7bit\r\n");
		fputs($connect, "MIME-Version: 1.0\r\n");
		fputs($connect, "\r\n");
		if (is_array($body) && array_key_exists('text', $body) && 0 < strlen($body['text'])){
			$body = $body['text'];
		}
		if (is_array($body) && array_key_exists('html', $body) && 0 < strlen($body['html'])){
			$body = $body['html'];
		}
		fputs($connect, mb_convert_encoding($body, strtoupper(self::$charset), 'UTF-8')."\r\n");
		fputs($connect, ".\r\n");
		$res = fgets($connect, 1024);
		if (!$res) {
			return false;
		}
		fputs($connect, "QUIT\n");
		$res = fgets($connect, 1024);
		if (!$res) {
			return false;
		}
		fclose($connect);
	
		return true;
	
	}
}

?>