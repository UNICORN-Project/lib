<?php

/**
 * ImgaeInfoと依存して、画像を色々する画像Util
 *
 * @author saimushi
 */
class GenericImage
{
	public static function info($argTarget, $argStringEnabled=NULL) {
		if(TRUE === $argStringEnabled){
			// バイナリの明示指定
			return ImageInfo::getInfoFromData($argTarget);
		}
		// バイナリ or ファイルパスの自動判別
		else if(1024 >= strlen($argTarget) && FALSE !== realpath($argTarget) && TRUE === file_exists(realpath($argTarget))){
			return ImageInfo::getInfoFromFile($argTarget);
		}
		else {
			return ImageInfo::getInfoFromData($argTarget);
		}
		return FALSE;
	}

	public static function checkMimeType($argInfoResut, $argTargetMimeType) {
		if (NULL === $argInfoResut || !is_object($argInfoResut)){
			// 失敗
			return FALSE;
		}
		if (FALSE === (isset($argInfoResut->type) && is_numeric($argInfoResut->type) && 0 < $argInfoResut->type)){
			// 失敗
			return FALSE;
		}
		if(image_type_to_mime_type($argInfoResut->type) !== $argTargetMimeType){
			// 失敗
			return FALSE;
		}
		return TRUE;
	}

	public static function crop($argTarget, $argCropLeft = 0, $argCropTop = 0, $argCropRight = 0, $argCropBottom = 0) {
		$info = NULL;
		$binary = NULL;

		// バイナリ or ファイルパスの自動判別
		if(1024 >= strlen($argTarget) && FALSE !== realpath($argTarget) && TRUE === file_exists(realpath($argTarget))){
			$info = ImageInfo::getInfoFromFile($argTarget);
			$binary = file_get_contents(realpath($argTarget));
		}
		else {
			$info = ImageInfo::getInfoFromData($argTarget);
			$binary = $argTarget;
		}
		if (!is_object($info)){
			// 失敗
			return FALSE;
		}

		$image = imagecreatefromstring($binary);

		$final_width = 0;
		$final_height = 0;
		$width_old = $info->w;
		$height_old = $info->h;

		$newWidth = $argCropRight - $argCropLeft;
		$newHeight = $argCropBottom - $argCropTop;

		$final_width = ( $newWidth <= 0 ) ? $width_old : $newWidth;
		$final_height = ( $newHeight <= 0 ) ? $height_old : $newHeight;

		$resizedImageData = imagecreatetruecolor( $final_width, $final_height );

		if ( ($info->type == IMAGETYPE_GIF) || ($info->type == IMAGETYPE_PNG) ) {
			$trnprt_indx = imagecolortransparent($image);

			// If we have a specific transparent color
			if ($trnprt_indx >= 0) {

				// Get the original image's transparent color's RGB values
				$trnprt_color    = imagecolorsforindex($image, $trnprt_indx);

				// Allocate the same color in the new image resource
				$trnprt_indx    = imagecolorallocate($resizedImageData, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);

				// Completely fill the background of the new image with allocated color.
				imagefill($resizedImageData, 0, 0, $trnprt_indx);

				// Set the background color for new image to transparent
				imagecolortransparent($resizedImageData, $trnprt_indx);


			}
			// Always make a transparent background color for PNGs that don't have one allocated already
			elseif ($info->type == IMAGETYPE_PNG) {

				// Turn off transparency blending (temporarily)
				imagealphablending($resizedImageData, false);

				// Create a new transparent color for image
				$color = imagecolorallocatealpha($resizedImageData, 255, 255, 255, 127);

				// Completely fill the background of the new image with allocated color.
				imagefill($resizedImageData, 0, 0, $color);

				// Restore transparency blending
				imagesavealpha($resizedImageData, true);
			}
		}

		//echo $final_width, $final_height;
		imagecopyresampled($resizedImageData, $argImageResouceID, 0, 0, $argCropLeft, $argCropTop, $final_width, $final_height, $final_width, $final_height);

		$imageBinary = NULL;

		ob_start();
		if(IMAGETYPE_JPEG == $info->type){
			imagejpeg($resizedImageData);
		}
		elseif(IMAGETYPE_GIF == $info->type){
			imagegif($resizedImageData);
		}
		elseif(IMAGETYPE_PNG == $info->type){
			imagepng($resizedImageData);
		}
		$imageBinary = ob_get_clean();
		imagedestroy($resizedImageData);

		return $imageBinary;
	}

	public static function resize($argTarget, $width = 0, $height = 0, $proportional = false) {
		if ( $height <= 0 && $width <= 0 ) {
			return false;
		}

		$info = NULL;
		$binary = NULL;
		
		// バイナリ or ファイルパスの自動判別
		if(1024 >= strlen($argTarget) && FALSE !== realpath($argTarget) && TRUE === file_exists(realpath($argTarget))){
			$info = ImageInfo::getInfoFromFile($argTarget);
			$binary = file_get_contents(realpath($argTarget));
		}
		else {
			$info = ImageInfo::getInfoFromData($argTarget);
			$binary = $argTarget;
		}
		if (!is_object($info)){
			// 失敗
			return FALSE;
		}
		
		$image = imagecreatefromstring($binary);
		
		$final_width = 0;
		$final_height = 0;
		$width_old = $info->w;
		$height_old = $info->h;

		if (false !== $proportional) {
			// サイズ比率維持(間延びはしない！)
			if ($width == 0) $factor = $height/$height_old;
			elseif ($height == 0) $factor = $width/$width_old;
			else $factor = min ( $width / $width_old, $height / $height_old);

			$final_width = round ($width_old * $factor);
			$final_height = round ($height_old * $factor);
		}
		else {
			// 渡された縦横で固定サイズ(間延びはしない！)
			$final_width = $width;
			$final_height = $height;

			$width_gap = $width_old / $width;
			$height_gap = $height_old / $height;
		}

		$resizedImageData = imagecreatetruecolor($final_width, $final_height);

		if ( ($info->type == IMAGETYPE_GIF) || ($info->type == IMAGETYPE_PNG) ) {
			$trnprt_indx = imagecolortransparent($image);

			// If we have a specific transparent color
			if ($trnprt_indx >= 0) {
				// Get the original image's transparent color's RGB values
				$trnprt_color    = imagecolorsforindex($image, $trnprt_indx);

				// Allocate the same color in the new image resource
				$trnprt_indx    = imagecolorallocate($resizedImageData, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);

				// Completely fill the background of the new image with allocated color.
				imagefill($resizedImageData, 0, 0, $trnprt_indx);

				// Set the background color for new image to transparent
				imagecolortransparent($resizedImageData, $trnprt_indx);
			}
			// Always make a transparent background color for PNGs that don't have one allocated already
			elseif ($info->type == IMAGETYPE_PNG) {
				// Turn off transparency blending (temporarily)
				imagealphablending($resizedImageData, false);

				// Create a new transparent color for image
				$color = imagecolorallocatealpha($resizedImageData, 255, 255, 255, 127);

				// Completely fill the background of the new image with allocated color.
				imagefill($resizedImageData, 0, 0, $color);

				// Restore transparency blending
				imagesavealpha($resizedImageData, true);
			}
		}
		
		if (false !== $proportional) {
			imagecopyresampled($resizedImageData, $image, 0, 0, 0, 0, $final_width, $final_height, $width_old, $height_old);
		}
		else {
			//横より縦の比率が大きい場合は、求める画像サイズより縦長なので縦の上下をカット
			if($width_gap < $height_gap){
				$cut = ceil((($height_gap - $width_gap) * $final_height) / 2);
				imagecopyresampled($resizedImageData, $image, 0, 0, 0, $cut, $final_width, $final_height, $width_old, $height_old - ($cut * 2));
					
				//縦より横の比率が大きい場合は、求める画像サイズより横長なので横の左右をカット
			}elseif($width_gap > $height_gap){
				$cut = ceil((($width_gap - $height_gap) * $final_width) / 2);
				imagecopyresampled($resizedImageData, $image, 0, 0, $cut, 0, $final_width, $final_height, $width_old - ($cut * 2), $height_old);
					
				//縦横比が同じなら、そのまま縮小
			}else{
				imagecopyresampled($resizedImageData, $image, 0, 0, 0, 0, $final_width, $final_height, $width_old, $height_old);
			}
		}
		
		$imageBinary = NULL;

		ob_start();
		if(IMAGETYPE_JPEG == $info->type){
			imagejpeg($resizedImageData);
		}
		elseif(IMAGETYPE_GIF == $info->type){
			imagegif($resizedImageData);
		}
		elseif(IMAGETYPE_PNG == $info->type){
			imagepng($resizedImageData);
		}
		$imageBinary = ob_get_clean();
		imagedestroy($resizedImageData);

		return $imageBinary;
	}
}

?>