<?php

/**
 * ImageInfo
 *
 * @version 0.9.0
 *
 * 概要：
 * PNG、Jpeg、GIFの各種画像ファイルから画像サイズ等のヘッダー情報を取得します。
 * 趣味で作っている物なので業務用には使用できません。
 *
 * 現状の問題点：
 *  - 壊れているファイルを読み込ませた場合に誤動作する可能性がある。
 *  - 画像ファイルの判別処理が甘い。特にGIFとPNGはファイルのシグネチャが
 *    合っていればGIF画像と認識するので、ファイルのシグネチャだけが合っている
 *    偽物のファイルも認識してしまう。
 *  - ロスレス、拡張ベースライン方式のJpeg画像に対応していない。
 *  - 色数を取得できない。
 *
 * 簡単な使用方法：
 *
 * <code>
 * $result = ImageInfo::getInfoFromFile('test.jpg');
 * if($result->isError())
 	* {
 *     die('エラーコード：'.$result->getErrorCode());
 * }
 * else
 	* {
 *     echo $result->getW()."<br>";
 *     echo $result->getH()."<br>";
 * }
 * </code>
 *
 * @author ひろき
 * @copyright 2006 GASOLINE STAND
 * @link http://hrgs.itbdns.com/
 *
 * modified saimushi
 * modify date 2015/10/07
 */


//--------------------------------------------------------------------
// エラーコード
//--------------------------------------------------------------------

/**
 * 原因不明のエラー
 */
define('ImageInfo_ERROR_UNKNOWN',     1);

/**
 * ヘッダー情報の取得に失敗
*/
define('ImageInfo_ERROR_HEADERDATA',  2);


/**
 * サポートされていないフォーマット
*/
define('ImageInfo_ERROR_UNSUPPORTED', 3);


//--------------------------------------------------------------------
// その他の定数
//--------------------------------------------------------------------

/**
 * ベースライン方式のJPEG
 * @access private
*/
define('ImageInfo_JPEG_TYPE_BASELINE',    0);

/**
 * プログレッシブ形式のJPEG
 * @access private
*/
define('ImageInfo_JPEG_TYPE_PROGRESSIVE', 2);


//--------------------------------------------------------------------
// クラス定義
//--------------------------------------------------------------------

/**
 * ファイル、またはバイナリデータから画像の情報を取得するクラス
 * インスタンス化せず、以下のように静的に呼び出して使用します。
 *
 * $result = ImageInfo::getInfoFromFile('/xxx/xxx.xxx');
*/
class ImageInfo
{
	/**
	 * ファイルからImageInfoResultオブジェクトを生成して返す
	 *
	 * @static
	 * @param string $filename ファイル名
	 * @return object ImageInfoResultオブジェクト
	 */
	public static function &getInfoFromFile($filename)
	{
		$null = null;
		$fp = fopen($filename, 'rb');
		if(!$fp)
		{
			return $null;
		}

		$buffer = '';
		while(!feof($fp))
		{
			$buffer .= fread($fp, 8192);
		}
		fclose($fp);

		$res = &ImageInfo::getInfoFromData($buffer);
		return $res;
	}


	/**
	 * バイナリデータからImageInfoResultオブジェクトを生成して返す
	 *
	 * @static
	 * @param string &$data バイナリデータ
	 * @return object ImageInfoResultオブジェクト
	 */
	public static function &getInfoFromData(&$data)
	{
		$image_info['w'] = 0;
		$image_info['h'] = 0;
		$image_info['colors'] = 0;
		$image_info['type'] = 0;
		$image_info['error'] = 0;

		if(ImageInfo_Jpeg::isValid($data))
		{
			//JPEG画像の場合
			$obj = new ImageInfo_Jpeg();
		}
		elseif(ImageInfo_Png::isValid($data))
		{
			//PNG画像の場合
			$obj = new ImageInfo_Png();
		}
		elseif(ImageInfo_Gif::isValid($data))
		{
			//GIF画像の場合
			$obj = new ImageInfo_Gif();
		}
		else
		{
			//サポートしていないか、無効な画像データ
			$image_info['error'] = ImageInfo_ERROR_UNSUPPORTED;
			$result = new ImageInfoResult($image_info);
			return $result;
		}
		
		//画像の情報を取得する
		$obj->getInfo($data, $image_info);
		$result = new ImageInfoResult($image_info);
		return $result;
	}
};


/**
 * 画像データの情報を取得した結果を格納するクラス
 */
class ImageInfoResult
{
	/**
	 * 画像の横幅
	 * @access private
	 * @var int
	 */
	var $w;

	/**
	 * 画像の縦幅
	 * @access private
	 * @var int
	 */
	var $h;

	/**
	 * 画像の種類(IMAGETYPE_XXX)
	 * @access private
	 * @var int
	 */
	var $type;

	/**
	 * 画像の拡張子(jpg|gif|png)
	 * @access private
	 * @var string
	 */
	var $extension;

	/**
	 * 画像の色数
	 * @access private
	 * @var int
	 */
	var $colors;

	/**
	 * エラーがあったかどうか
	 * @access private
	 * @var boolean
	 */
	var $error_flag;

	/**
	 * エラーコード
	 * @access private
	 * @var int
	 */
	var $error_code;

	/**
	 * コンストラクタ。画像の情報をセットする。
	 *
	 * @param array $image_info 画像の情報を含む配列
	 *                        - $arr['w']         = 横幅
	 *                        - $arr['h']         = 横幅
	 *                        - $arr['type']      = 画像の種類(IMAGETYPE_XXX)
	 *                        - $arr['extension'] = 画像の拡張子(jpg|gif|png)
	 *                        - $arr['colors']    = 色数
	 *                        - $arr['error']     = エラーコード
	 */
	function ImageInfoResult($image_info)
	{
		$this->w = 0;
		$this->h = 0;
		$this->type = 0;
		$this->extension = NULL;
		$this->colors = 0;
		$this->error_flag = false;
		$this->error_code = 0;

		if(!is_array($image_info))
		{
			$this->error_flag = true;
			$this->error_code = ImageInfo_ERROR_UNKNOWN;
			return;
		}

		//$image_info配列から値を取り出す
		$w           = (isset($image_info['w']))           ? $image_info['w']           : '';
		$h           = (isset($image_info['h']))           ? $image_info['h']           : '';
		$type        = (isset($image_info['type']))        ? $image_info['type']        : '';
		$extension   = (isset($image_info['extension']))   ? $image_info['extension']   : '';
		$colors      = (isset($image_info['colors']))      ? $image_info['colors']      : '';
		$error       = (isset($image_info['error']))       ? $image_info['error']       : '';

		//各値が正しい値でなければエラー
		if(!ImageInfoResult::isInt($w) || !ImageInfoResult::isInt($h) || !ImageInfoResult::isInt($type) || !ImageInfoResult::isInt($colors) || !ImageInfoResult::isInt($error))
		{
			$this->error_flag = true;
			$this->error_code = ImageInfo_ERROR_UNKNOWN;
			return;
		}

		$this->w = $w;
		$this->h = $h;
		$this->type = $type;
		$this->extension = $extension;
		$this->colors = $colors;
		$this->error_code = $error;

		if($this->error_code != 0)
		{
			//エラーコードが0以外の場合はエラー
			$this->error_flag = true;
		}
	}

	/**
	 * 横幅を返す
	 * @return int 横幅
	 */
	function getW()
	{
		return $this->w;
	}

	/**
	 * 縦幅を返す
	 * @return int 縦幅
	 */
	function getH()
	{
		return $this->h;
	}

	/**
	 * 画像の種類を返す
	 * @return int 画像の種類。(IMAGETYPE_XXX)
	 */
	function getType()
	{
		return $this->type;
	}

	/**
	 * 色数を返す
	 * @return int 色数
	 */
	function getColors()
	{
		return $this->colors;
	}

	/**
	 * エラーがあったかどうかを返す
	 * @return boolean true  => エラーあり
	 *                 false => エラーなし
	 */
	function isError()
	{
		return $this->error_flag;
	}

	/**
	 * エラーコードを返す
	 * @return int エラーコード。ImageInfo_ERROR_XXX
	 */
	function getErrorCode()
	{
		return $this->error_code;
	}

	/**
	 * バイナリデータを任意の整数に変換する。(unpack関数のショートカット)
	 *
	 * @access private
	 * @param string $bin    バイナリデータ
	 * @param string $format unpack時のフォーマット文字列。デフォルトは「n」
	 * @return int 任意の整数
	 */
	public static function bin2int($bin, $format='n')
	{
		list(,$unpacked) = unpack($format."*", $bin);
		return $unpacked;
	}
	
	/**
	 * 整数かを判定する
	 *
	 * @access private
	 * @param string $val 判定の対象となる値
	 * @return boolean true  => 整数である
	 *                 false => 整数ではない
	 */
	public static function isInt(&$val)
	{
		return ($val !== '' && ctype_digit((string)$val));
	}	
}

/**
 * JPEG画像の情報を処理するクラス
 *
 * ベースライン方式、プログレッシブ形式のみに対応しています。
 * @access private
 */
class ImageInfo_Jpeg
{
	var $jpeg_type;

	/**
	 * バイナリデータからJPEG画像の情報を取得して返す
	 *
	 * @param string &$data        画像のバイナリデータ
	 * @param array  &$image_info  取得した画像情報を格納する配列
	 *
	 * @return boolean true  => 情報の取得に成功
	 *                 false => エラー発生
	 */
	function getInfo(&$data, &$image_info)
	{
		//画像タイプにJPEGを設定。
		$image_info['type'] = IMAGETYPE_JPEG;
		$image_info['extension'] = 'jpg';


		$sof0_pos = $sof2_pos = 0;

		//横幅、縦幅の情報を取得する
		//SOF0、またはSOF2セグメントを探す
		$sof0_pos = strpos($data, chr(0xFF).chr(0xC0));
		if(!$sof0_pos)
		{
			//SOF0が見つからない場合はSOF2セグメントを探す
			$sof2_pos = strpos($data, chr(0xFF).chr(0xC2));
		}

		$sof_pos = 0;
		if($sof0_pos)
		{
			//SOF0が見つかった場合はベースラインJPEG
			$this->jpeg_type = ImageInfo_JPEG_TYPE_BASELINE;
			$sof_pos = $sof0_pos;
		}
		elseif($sof2_pos)
		{
			//SOF0が見つかった場合はプログレッシブJPEG
			$this->jpeg_type = ImageInfo_JPEG_TYPE_PROGRESSIVE;
			$sof_pos = $sof2_pos;
		}
		else
		{
			//どちらもみつからない場合はエラー
			$image_info['error'] = ImageInfo_ERROR_HEADERDATA;
			return false;
		}

		//SOF0、またはSOF2のセグメントから画像のサイズを取得する
		$bin_h = substr($data, $sof_pos+5, 2);
		$bin_w = substr($data, $sof_pos+7, 2);

		$image_info['h'] = ImageInfoResult::bin2int($bin_h);
		$image_info['w'] = ImageInfoResult::bin2int($bin_w);
		return true;
	}

	/**
	 * 有効なJPEGファイルのデータであるかを判定する
	 *
	 * @static
	 * @param string &$data 画像のバイナリデータ
	 * @return boolean true  => JPEG画像
	 *                 false => JPEG画像ではない
	 */
	public static function isValid(&$data)
	{
		//SOIセグメントの取得。
		//先頭の2バイト。
		$soi = ImageInfoResult::bin2int(substr($data, 0, 2));
		if((int)$soi != (int)0xFFD8)
		{
			return false;
		}
		// APP0セグメントの無いファイルに遭遇した！！？
// 		//APP0セグメントを探す
// 		$app0_pos = strpos($data, chr(0xFF).chr(0xE0));
// 		debug('imageas jpege??'.var_export($app0_pos, true));
// 		if(!$app0_pos)
// 		{
// 		debug('imageas jpege??'.chr(0xFF).':'.chr(0xE0));
// 		debug('imageas jpege??'.chr(0xFF).':'.chr(0xE0));
// 		if(substr($data, 0, 2) !== "\xff\xd8"){
// 			return false;
// 		}
// 		}

// 		//app0セグメントが見つかったら、JFIFのシグネチャを調べる
// 		$sig = substr($data, $app0_pos+4, 5);
// 		if(strcmp($sig, 'JFIF'.chr(0)) != 0)
// 		{
// 		debug('imageas jpege???');
// 			return false;
// 		}
// 		debug('imageas jpege!!');
		
		return true;
	}
};


/**
 * PNG画像の情報を処理するクラス
 * @access private
 */
class ImageInfo_Png
{
	/**
	 * バイナリデータからPNG画像の情報を取得して返す
	 *
	 * @param string &$data        画像のバイナリデータ
	 * @param array  &$image_info  取得した画像情報を格納する配列
	 *
	 * @return boolean true  => 情報の取得に成功
	 *                 false => エラー発生
	 */
	function getInfo(&$data, &$image_info)
	{
		//画像タイプにJPEGを設定。
		$image_info['type'] = IMAGETYPE_PNG;
		$image_info['extension'] = 'png';

		//IHDRチャンクを探す
		$ihdr_pos = strpos($data, 'IHDR');
		if(!$ihdr_pos)
		{
			$image_info['error'] = ImageInfo_ERROR_HEADERDATA;
			return false;
		}

		$bin_w = substr($data, $ihdr_pos+4, 4);
		$bin_h = substr($data, $ihdr_pos+8, 4);

		$image_info['w'] = ImageInfoResult::bin2int($bin_w, 'N');
		$image_info['h'] = ImageInfoResult::bin2int($bin_h, 'N');
		return true;
	}


	/**
	 * 有効なJPEGファイルのデータであるかを判定する
	 *
	 * @static
	 * @param string &$data 画像のバイナリデータ
	 * @return boolean true  => PNG画像
	 *                 false => PNG画像ではない
	 */
	public static function isValid(&$data)
	{
		$bin_buf = substr($data, 0, 8);
		$hex_buf = bin2hex($bin_buf);

		//PNGのシグネイチャは16進数で「89  50  4e  47  0d  0a  1a  0a」でなければならない。
		if( strcasecmp($hex_buf, '89504E470D0A1A0A') != 0 )
		{
			return false;
		}

		return true;
	}
};

/**
 * GIF画像の情報を処理するクラス
 * @access private
 */
class ImageInfo_Gif
{
	/**
	 * バイナリデータからGIF画像の情報を取得して返す
	 *
	 * @param string &$data        画像のバイナリデータ
	 * @param array  &$image_info  取得した画像情報を格納する配列
	 *
	 * @return boolean true  => 情報の取得に成功
	 *                 false => エラー発生
	 */
	function getInfo(&$data, &$image_info)
	{
		//画像タイプにJPEGを設定。
		$image_info['type'] = IMAGETYPE_GIF;
		$image_info['extension'] = 'gif';

		$bin_w = substr($data, 6, 2);
		$bin_h = substr($data, 8, 2);

		$image_info['w'] = ImageInfoResult::bin2int($bin_w, 'v');
		$image_info['h'] = ImageInfoResult::bin2int($bin_h, 'v');
		return true;
	}


	/**
	 * 有効なJPEGファイルのデータであるかを判定する
	 *
	 * @static
	 * @param string &$data 画像のバイナリデータ
	 * @return boolean true  => PNG画像
	 *                 false => PNG画像ではない
	 */
	public static function isValid(&$data)
	{
		$sig = substr($data, 0, 3);
		$ver = substr($data, 3, 3);

		if(strcmp($sig, 'GIF') != 0)
		{
			return false;
		}

		if((strcmp($ver, '87a') != 0) && (strcmp($ver, '89a') != 0))
		{
			return false;
		}
		return true;
	}
};

?>