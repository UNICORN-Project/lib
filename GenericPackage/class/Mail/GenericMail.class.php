<?php

class GenericMail
{
	const SUPPORT_MAIL = 'support@3bk.jp';
	const SUBJECT = '';
	const DEFAULT_CHARSET = 'iso-2022-jp';

	const VIA_MAIL_TYPE_SES = 'SES';
	
	public static function sendMail($argViaMailType, $to, $subject, $body, $from = null, $cc = null, $bcc = null) {
		$result = FALSE;
		if (self::VIA_MAIL_TYPE_SES === strtoupper($argViaMailType)){
			$result = self::sendMailSes($to, $subject, $body, $from, $cc, $bcc);
		} else {
			$result = self::sendMailSmtp($to, $subject, $body, $from, $cc, $bcc);
		}
		return $result;
	}
	
	public static function sendMailSes($to, $subject, $body, $from = null, $cc = null, $bcc = null, $replyTo = null, $returnPath = null) {
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
		if (is_string($subject) && 0 < strlen($subject)){
			$message['Subject'] = array('Data' => $subject, 'Charset' => self::DEFAULT_CHARSET);
		} else {
			$message['Subject'] = array('Data' => self::SUBJECT, 'Charset' => self::DEFAULT_CHARSET);
		}
		// body
		if (is_array($body)){
			$message['Body'] = array();
			if (array_key_exists('text', $body) && 0 < strlen($body['text'])){
				$textMessage = array('Text' => array('Data' => $body['text'], 'Charset' => self::DEFAULT_CHARSET));
				$message['Body'] = array_merge($message['Body'], $textMessage);
			}
			if (array_key_exists('html', $body) && 0 < strlen($body['html'])){
				$htmlMessage = array('Html' => array('Data' => $body['html'], 'Charset' => self::DEFAULT_CHARSET));
				$message['Body'] = array_merge($message['Body'], $htmlMessage);
			}
		} else {
			return FALSE;
		}
		// from
		if (NULL === $from){
			$from = self::SUPPORT_MAIL;
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
		$mail = new SimpleEmail();
		$result = $mail->sendSimpleMail($contents);
		return $result;
	}
	
	public static function sendMailSmtp($to, $subject, $body, $from = null, $cc = null, $bcc = null) {
		return FALSE;
	}
	
	/**
	 * 外部smtpサーバに接続してメールを送信する
	 * @return
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param string $from
	 * @param string $bcc
	 * @param string メールアドレス(エラーメール返信先)
	 * @param string 拡張ヘッダー
	 */
	public function sendEMail($smtp_host, $to = null, $subject = null, $message = null, $from, $bcc = null, $sender = null, $other_header = null) {
	
		$message = preg_replace("/\n/", "\r\n", $message);
		$other_header = preg_replace("/\n/", "\r\n", $other_header);
	
		if ( isset ($sender) && !$sender) {
			$sender = $from;
		}
	
		$connect = fsockopen($smtp_host, 25, $errno, $errstr, 30);
	
		if (!$connect) {
			return false;
		}
		if (!fgets($connect, 1024)) {
			return false;
		}
		fputs($connect, "HELO ".getenv('HOSTNAME')."\r\n");
		if (!fgets($connect, 1024)) {
			return false;
		}
		fputs($connect, "MAIL FROM:$sender"."\r\n");
		if (!fgets($connect, 1024)) {
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
				fputs($connect, "RCPT TO:$rcptTo\r\n");
				if (!fgets($connect, 1024)) {
					return false;
				}
			}
		}
		fputs($connect, "DATA\n");
		if (!fgets($connect, 1024)) {
			return false;
		}
		fputs($connect, "From: $from\r\n");
		fputs($connect, "To: ".join(",", $toArray)."\r\n");
		fputs($connect, "X-SM-Envelope-From: ".$sender."\r\n");
		fputs($connect, "Subject: ".mb_encode_mimeheader($subject, "JIS", "SJIS")."\r\n");
		if ($other_header) {
			fputs($connect, $other_header."\r\n");
		}
		fputs($connect, "Date: ".date("r")."\r\n");
		fputs($connect, "Content-type: text/plain; charset=\"ISO-2022-JP\"\r\n");
		fputs($connect, "Content-Transfer-Encoding: 7bit\r\n");
		fputs($connect, "MIME-Version: 1.0\r\n");
		fputs($connect, "\r\n");
		fputs($connect, mb_convert_encoding($message, "JIS", "SJIS")."\r\n");
		fputs($connect, ".\r\n");
		if (!fgets($connect, 1024)) {
			return false;
		}
		fputs($connect, "QUIT\n");
		if (!fgets($connect, 1024)) {
			return false;
		}
		fclose($connect);
	
		return true;
	
	}
}

?>