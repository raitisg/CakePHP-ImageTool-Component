<?php

/**
 * Image tool 1.1.2
 *
 * Different tools/functions to perform various tasks w/ images
 */
class ImageToolComponent extends Component {

	/**
	 * Place watermark on image
	 *
	 * Options:
	 * - 'input' Input file (path or gd resource)
	 * - 'output' Output path. If not specified, gd resource is returned
	 * - 'watermark' Watermark file (path or gd resource)
	 * - 'quality' Output image quality (JPG only). Value from 0 to 100
	 * - 'compression' Output image compression (PNG only). Value from 0 to 9
	 * - 'chmod' What permissions should be applied to destination image
	 * - 'scale' If true, watermark will be scaled fullsize ('position' and 'repeat' won't be taken into account)
	 * - 'strech' If true and scale also set to true, strech watermark to cover whole image
	 * - 'repeat' Should watermark be repeated? This is ignored if 'scale' is set to true or 'position' is custom (array)
	 * - 'position' Watermark position. Possible values: 'top-left', 'top-right', 'bottom-right', 'bottom-left', 'center' or array(x, y)
	 * - 'opacity' Watermark image's opacity (0-100). Default = 100
	 * - 'afterCallbacks' Functions to be executed after this one
	 *
	 * @param array $options An array of options.
	 * @return mixed boolean or GD resource if output was set to null
	 */
	function watermark($options = array()) {
		$options = array_merge(array(
			'afterCallbacks' => null,
			'scale' => false,
			'strech' => false,
			'repeat' => false,
			'watermark' => null,
			'output' => null,
			'input' => null,
			'position' => 'center',
			'compression' => 9,
			'quality' => 100,
			'chmod' => null,
			'opacity' => 100
		), $options);

		// if output path (directories) doesn't exist, try to make whole path
		if (!$this->createPath($options['output'])) {
			return false;
		}

		$img = $this->openImage($options['input']);
		unset($options['input']);
		if (empty($img)) {
			return false;
		}

		$src_wm = $this->openImage($options['watermark']);
		unset($options['watermark']);
		if (empty($src_wm)) {
			return false;
		}

		// image size
		$img_im_w = imagesx($img);
		$img_im_h = imagesy($img);

		// watermark size
		$img_wm_w = imagesx($src_wm);
		$img_wm_h = imagesy($src_wm);

		if ($options['scale']) {
			if ($options['strech']) {
				$r = imagecopyresampled($img, $src_wm, 0, 0, 0, 0, $img_im_w, $img_im_h, $img_wm_w, $img_wm_h);
			} else {
				$x = 0;
				$y = 0;
				$w = $img_im_w;
				$h = $img_im_h;

				if (($img_im_w / $img_im_h) > ($img_wm_w / $img_wm_h)) {
					$ratio = $img_im_h / $img_wm_h;
					$w = $ratio * $img_wm_w;
					$x = round(($img_im_w - $w) / 2);
				} else {
					$ratio = $img_im_w / $img_wm_w;
					$h = $ratio * $img_wm_h;
					$y = round(($img_im_h - $h) / 2);
				}

				$r = imagecopyresampled($img, $src_wm, $x, $y, 0, 0, $w, $h, $img_wm_w, $img_wm_h);
			}
		} else if ($options['repeat']) {
			if (is_array($options['position'])) {
				$options['position'] = 5;
			}

			switch ($options['position']) {
				case 'top-left':
					for ($y=0; $y<$img_im_h; $y+=$img_wm_h) {
						for ($x=0; $x<$img_im_w; $x+=$img_wm_w) {
							$r = $this->imagecopymerge_alpha($img, $src_wm, $x, $y, 0, 0, $img_wm_w, $img_wm_h, $options['opacity']);
						}
					}
				break;

				case 'top-right':
					for ($y=0; $y<$img_im_h; $y+=$img_wm_h) {
						for ($x=$img_im_w; $x>-$img_wm_w; $x-=$img_wm_w) {
							$r = $this->imagecopymerge_alpha($img, $src_wm, $x, $y, 0, 0, $img_wm_w, $img_wm_h, $options['opacity']);
						}
					}
				break;

				case 'bottom-right':
					for ($y=$img_im_h; $y>-$img_wm_h; $y-=$img_wm_h) {
						for ($x=$img_im_w; $x>-$img_wm_w; $x-=$img_wm_w) {
							$r = $this->imagecopymerge_alpha($img, $src_wm, $x, $y, 0, 0, $img_wm_w, $img_wm_h, $options['opacity']);
						}
					}
				break;

				case 'bottom-left':
					for ($y=$img_im_h; $y>-$img_wm_h; $y-=$img_wm_h) {
						for ($x=0; $x<$img_im_w; $x+=$img_wm_w) {
							$r = $this->imagecopymerge_alpha($img, $src_wm, $x, $y, 0, 0, $img_wm_w, $img_wm_h, $options['opacity']);
						}
					}
				break;

				case 'center':
				default:
					$pos_x = -(($img_im_w%$img_wm_w)/2);
					$pos_y = -(($img_im_h%$img_wm_h)/2);

					for ($y=$pos_y; $y<$img_im_h; $y+=$img_wm_h) {
						for ($x=$pos_x; $x<$img_im_w; $x+=$img_wm_w) {
							$r = $this->imagecopymerge_alpha($img, $src_wm, $x, $y, 0, 0, $img_wm_w, $img_wm_h, $options['opacity']);
						}
					}
				break;
			}
		} else {
			// custom location
			if (is_array($options['position'])) {
				list($pos_x, $pos_y) = $options['position'];
			} else {
				// predefined location
				switch ($options['position']) {
					case 'top-left':
						$pos_x = 0;
						$pos_y = 0;
					break;

					case 'top-right':
						$pos_x = $img_im_w - $img_wm_w;
						$pos_y = 0;
					break;

					case 'bottom-right':
						$pos_x = $img_im_w - $img_wm_w;
						$pos_y = $img_im_h - $img_wm_h;
					break;

					case 'bottom-left':
						$pos_x = 0;
						$pos_y = $img_im_h - $img_wm_h;
					break;

					case 'center':
					default:
						$pos_x = round(($img_im_w - $img_wm_w) / 2);
						$pos_y = round(($img_im_h - $img_wm_h) / 2);
					break;
				}
			}

			$r = $this->imagecopymerge_alpha($img, $src_wm, $pos_x, $pos_y, 0, 0, $img_wm_w, $img_wm_h, $options['opacity']);
		}

		if (!$r) {
			return false;
		}

		if (!$this->afterCallbacks($img, $options['afterCallbacks'])) {
			return false;
		}

		return $this->saveImage($img, $options);
	}

	/**
	 * Resize image
	 *
	 * Options:
	 * - 'input' Input file (path or gd resource)
	 * - 'output' Output path. If not specified, gd resource is returned
	 * - 'quality' Output image quality (JPG only). Value from 0 to 100
	 * - 'compression' Output image compression (PNG only). Value from 0 to 9
	 * - 'units' Scale units. Percents ('%') and pixels ('px') are avalaible
	 * - 'enlarge' if set to false and width or height of the destination image is bigger than source image's width or height, then leave source image's dimensions untouched
	 * - 'chmod' What permissions should be applied to destination image
	 * - 'keepRatio' If true and both output width and height is specified and crop is set to false, image is resized with respect to it's original ratio. If false (default), image is simple scaled.
	 * - 'paddings' If not empty and both output width and height is specified and keepRatio is set to true, padding borders are applied. You can specify color here. If true, then white color will be applied
	 * - 'afterCallbacks' Functions to be executed after resize. Example: array(array('unsharpMask', 70, 3.9, 0)); First passed argument is f-ion name. Executed function's first argument must be gd image instance
	 * - 'crop' If true (default) crop excess portions of image to fit in specified size
	 * - 'height' Output image's height. If left empty, this is auto calculated (if possible)
	 * - 'width' Output image's width. If left empty, this is auto calculated (if possible)
	 *
	 * @param array $options An array of options
	 * @return mixed boolean or GD resource if output was set to null
	 */
	function resize($options = array()) {
		$options = array_merge(array(
			'afterCallbacks' => null,
			'compression' => null,
			'keepRatio' => false,
			'paddings' => true,
			'enlarge' => true,
			'quality' => null,
			'chmod' => null,
			'units' => 'px',
			'height' => null,
			'output' => null,
			'width' => null,
			'input' => null,
			'crop' => true
		), $options);

		// if output path (directories) doesn't exist, try to make whole path
		if (!$this->createPath($options['output'])) {
			return false;
		}

		$input_extension = $this->getExtension($options['input']);
		$output_extension = $this->getExtension($options['output']);

		$src_im = $this->openImage($options['input']);
		unset($options['input']);

		if (!$src_im) {
			return false;
		}

		// calculate new w, h, x and y

		if (!empty($options['width']) && !is_numeric($options['width'])) {
			return false;
		}
		if (!empty($options['height']) && !is_numeric($options['height'])) {
			return false;
		}

		// get size of the original image
		$input_width = imagesx($src_im);
		$input_height = imagesy($src_im);

		//calculate destination image w/h

		// turn % into px
		if ($options['units'] == '%') {
			if ($options['height'] != null) {
				$options['height'] = round($input_height * $options['height'] / 100);
			}

			if ($options['width'] != null) {
				$options['width'] = round($input_width * $options['width'] / 100);
			}
		}

		// if keepRatio is set to true, check output width/height and update them
		// as neccessary
		if ($options['keepRatio'] && $options['width'] != null && $options['height'] != null) {
			$input_ratio = $input_width / $input_height;
			$output_ratio = $options['width'] / $options['height'];

			$original_width = $options['width'];
			$original_height = $options['height'];

			if ($input_ratio > $output_ratio) {
				$options['height'] = $input_height * $options['width'] / $input_width;
			} else {
				$options['width'] = $input_width * $options['height'] / $input_height;
			}
		}

		// calculate missing width/height (if any)
		if ($options['width'] == null && $options['height'] == null) {
			$options['width'] = $input_width;
			$options['height'] = $input_height;
		} else if ($options['height'] == null) {
			$options['height'] = round(($options['width'] * $input_height) / $input_width);
		} else if ($options['width'] == null) {
			$options['width'] = round(($options['height'] * $input_width) / $input_height);
		}

		$src_x = 0;
		$src_y = 0;
		$src_w = $input_width;
		$src_h = $input_height;

		if ($options['enlarge'] == false && ($options['width'] > $input_width || $options['height'] > $input_height)) {
			$options['width'] = $input_width;
			$options['height'] = $input_height;
		} else if ($options['crop'] == true) {
			if (($input_width / $input_height) > ($options['width'] / $options['height'])) {
				$ratio = $input_height / $options['height'];
				$src_w = $ratio * $options['width'];
				$src_x = round(($input_width - $src_w) / 2);
			} else {
				$ratio = $input_width / $options['width'];
				$src_h = $ratio * $options['height'];
				$src_y = round(($input_height - $src_h) / 2);
			}
		}

		$dst_im = imagecreatetruecolor($options['width'], $options['height']);

		if (!$dst_im) {
			imagedestroy($src_im);
			return false;
		}

		// transparency or white bg instead of black
		if (in_array($input_extension, array('png', 'gif'))) {
			if (in_array($output_extension, array('png', 'gif'))) {
				imagealphablending($dst_im, false);
				imagesavealpha($dst_im,true);
				$transparent = imagecolorallocatealpha($dst_im, 255, 255, 255, 127);
				imagefilledrectangle($dst_im, 0, 0,$options['width'], $options['height'], $transparent);
			} else {
				$white = imagecolorallocate($dst_im, 255, 255, 255);
				imagefilledrectangle($dst_im, 0, 0, $options['width'], $options['height'], $white);
			}
		}

		$r = imagecopyresampled($dst_im, $src_im, 0, 0, $src_x, $src_y, $options['width'], $options['height'], $src_w, $src_h);

		if (!$r) {
			imagedestroy($src_im);
			return false;
		}

		if ($options['keepRatio'] && $options['paddings']) {
			if ($options['width'] != $original_width || $options['height'] != $original_height) {
				$bg_im = imagecreatetruecolor($original_width, $original_height);

				if (!$bg_im) {
					imagedestroy($bg_im);
					return false;
				}

				if ($options['paddings'] === true) {
					$rgb = array(255, 255, 255);
				} else {
					$rgb = $this->readColor($options['paddings']);
					if (!$rgb) {
						$rgb = array(255, 255, 255);
					}
				}

				$color = imagecolorallocate($bg_im, $rgb[0], $rgb[1], $rgb[2]);
				imagefilledrectangle($bg_im, 0, 0, $original_width, $original_height, $color);

				$x = round(($original_width - $options['width']) / 2);
				$y = round(($original_height - $options['height']) / 2);

				imagecopy($bg_im, $dst_im, $x, $y, 0, 0, $options['width'], $options['height']);

				$dst_im = $bg_im;
			}
		}

		if (!$this->afterCallbacks($dst_im, $options['afterCallbacks'])) {
			return false;
		}

		return $this->saveImage($dst_im, $options);
	}

	/**
	 * Apply unsharp mask to image
	 *
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	 * Unsharp Mask for PHP - version 2.1.1
	 * Unsharp mask algorithm by Torstein Hønsi 2003-07.
	 * thoensi_at_netcom_dot_no.
	 * Please leave this notice.
	 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	 *
	 * Options:
	 * - 'input' Input file (path or gd resource)
	 * - 'output' Output path. If not specified, gd resource is returned
	 * - 'quality' Output image quality (JPG only). Value from 0 to 100
	 * - 'compression' Output image compression (PNG only). Value from 0 to 9
	 * - 'afterCallbacks' Functions to be executed after this one
	 * - 'chmod' What permissions should be applied to destination image
	 * - 'threshold'
	 * - 'amount'
	 * - 'radius'
	 *
	 * @param array $options An array of options.
	 * @return mixed boolean or GD resource if output was set to null
	 */
	function unsharpMask($options = array()) {
		$options = array_merge(array(
			'afterCallbacks' => null,
			'compression' => null,
			'quality' => null,
			'threshold' => 3,
			'amount' => 50,
			'radius' => 0.5,
			'output' => null,
			'input' => null,
			'chmod' => null
		), $options);

		$img = $this->openImage($options['input']);
		unset($options['input']);

		if (!$img) {
			return false;
		}

		// Attempt to calibrate the parameters to Photoshop:

		if ($options['amount'] > 500) {
			$options['amount'] = 500;
		}

		$options['amount'] = $options['amount'] * 0.016;

		if ($options['radius'] > 50) {
			$options['radius'] = 50;
		}

		$options['radius'] = $options['radius'] * 2;

		if ($options['threshold'] > 255) {
			$options['threshold'] = 255;
		}

		// Only integers make sense.
		$options['radius'] = abs(round($options['radius']));

		if ($options['radius'] == 0) {
			return $this->saveImage($img, $options);
		}

		$w = imagesx($img);
		$h = imagesy($img);

		$imgCanvas = imagecreatetruecolor($w, $h);
		$imgBlur = imagecreatetruecolor($w, $h);

		// PHP >= 5.1
		if (function_exists('imageconvolution')) {
			$matrix = array(
				array(1, 2, 1),
				array(2, 4, 2),
				array(1, 2, 1)
			);
			imagecopy ($imgBlur, $img, 0, 0, 0, 0, $w, $h);
			imageconvolution($imgBlur, $matrix, 16, 0);
		} else {
			// Move copies of the image around one pixel at the time and merge them with weight
			// according to the matrix. The same matrix is simply repeated for higher radii.
			for ($i = 0; $i < $options['radius']; $i++) {
				imagecopy ($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h); // left
				imagecopymerge ($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50); // right
				imagecopymerge ($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50); // center
				imagecopy ($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);
				imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333 ); // up
				imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); // down
			}
		}

		if($options['threshold'] > 0) {
			// Calculate the difference between the blurred pixels and the original
			// and set the pixels
			for ($x = 0; $x < $w-1; $x++) { // each row
				for ($y = 0; $y < $h; $y++) { // each pixel
					$rgbOrig = ImageColorAt($img, $x, $y);
					$rOrig = (($rgbOrig >> 16) & 0xFF);
					$gOrig = (($rgbOrig >> 8) & 0xFF);
					$bOrig = ($rgbOrig & 0xFF);

					$rgbBlur = ImageColorAt($imgBlur, $x, $y);

					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					// When the masked pixels differ less from the original
					// than the threshold specifies, they are set to their original value.
					$rNew = (abs($rOrig - $rBlur) >= $options['threshold']) ? max(0, min(255, ($options['amount'] * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
					$gNew = (abs($gOrig - $gBlur) >= $options['threshold']) ? max(0, min(255, ($options['amount'] * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
					$bNew = (abs($bOrig - $bBlur) >= $options['threshold']) ? max(0, min(255, ($options['amount'] * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;

					if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
						$pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
						ImageSetPixel($img, $x, $y, $pixCol);
					}
				}
			}
		} else {
			for ($x = 0; $x < $w; $x++) { // each row
				for ($y = 0; $y < $h; $y++) { // each pixel
					$rgbOrig = ImageColorAt($img, $x, $y);
					$rOrig = (($rgbOrig >> 16) & 0xFF);
					$gOrig = (($rgbOrig >> 8) & 0xFF);
					$bOrig = ($rgbOrig & 0xFF);

					$rgbBlur = ImageColorAt($imgBlur, $x, $y);

					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					$rNew = ($options['amount'] * ($rOrig - $rBlur)) + $rOrig;

					if($rNew>255){$rNew=255;}
					elseif($rNew<0){$rNew=0;}
					$gNew = ($options['amount'] * ($gOrig - $gBlur)) + $gOrig;
					if($gNew>255){$gNew=255;}
					elseif($gNew<0){$gNew=0;}
					$bNew = ($options['amount'] * ($bOrig - $bBlur)) + $bOrig;
					if($bNew>255){$bNew=255;}
					elseif($bNew<0){$bNew=0;}

					$rgbNew = ($rNew << 16) + ($gNew <<8) + $bNew;
					ImageSetPixel($img, $x, $y, $rgbNew);
				}
			}
		}

		if (!$this->afterCallbacks($img, $options['afterCallbacks'])) {
			return false;
		}

		return $this->saveImage($img, $options);
	}

	/**
	 * Get file extension
	 *
	 * @param string $filename Filename
	 * @return string
	 */
	function getExtension($filename) {
		if (!is_string($filename)) {
			return '';
		}

		$pos = strrpos($filename, '.');

		if ($pos === false) {
			return '';
		}

		return strtolower(substr($filename, $pos + 1));
	}

	/**
	 * Open image as gd resource
	 *
	 * @param string $input Input (path) image
	 * @return mixed
	 */
	function openImage($input) {
		if (is_resource($input)) {
			if (get_resource_type($input) == 'gd') {
				return $input;
			}
		} else {
			switch ($this->getImageType($input)) {
				case 'jpg':
					return imagecreatefromjpeg($input);
				break;

				case 'png':
					return imagecreatefrompng($input);
				break;

				case 'gif':
					return imagecreatefromgif($input);
				break;
			}
		}

		return false;
	}

	/**
	 * Get image type from file
	 *
	 * @param string $input Input (path) image
	 * @param string $extension (optional) Extension (type)
	 * @param boolean $extension If true, check by extension
	 * @return string
	 */
	function getImageType($input, $extension = false) {
		if ($extension) {
			switch ($this->getExtension($input)) {
				case 'jpg':
					return 'jpg';
				break;

				case 'png':
					return 'png';
				break;

				case 'gif':
					return 'gif';
				break;
			}
		} else if (is_string($input) && is_file($input)) {
			$info = getimagesize($input);

			switch ($info['mime']) {
				case 'image/pjpeg':
				case 'image/jpeg':
				case 'image/jpg':
					return 'jpg';
				break;

				case 'image/x-png':
				case 'image/png':
					return 'png';
				break;

				case 'image/gif':
					return 'gif';
				break;
			}
		}

		return '';
	}

	/**
	 * Save image gd resource as image
	 *
	 * Image type is determined by $output extension so it must be present.
	 *
	 * Options:
	 * - 'compression' Output image's compression. Currently only PNG (value 0-9) supports this
	 * - 'quality' Output image's quality. Currently only JPG (value 0-100) supports this
	 * - 'output' Output path. If not specified, image resource is returned
	 *
	 * @param mixed $im Image resource
	 * @param string $output Output path
	 * @param mixed $options An array of additional options
	 * @return boolean
	 */
	function saveImage(&$im, $options = array()) {
		foreach (array('compression', 'quality', 'chmod') as $v) {
			if (is_null($options[$v])) {
				unset($options[$v]);
			}
		}

		$options = array_merge(array(
			'compression' => 9,
			'quality' => 100,
			'output' => null
		), $options);

		switch ($this->getImageType($options['output'], true)) {
			case 'jpg':
				if (ImageJPEG($im, $options['output'], $options['quality'])) {
					if (!empty($options['chmod'])) {
						chmod($options['output'], $options['chmod']);
					}
					return true;
				}
			break;

			case 'png':
				if (ImagePNG($im, $options['output'], $options['compression'])) {
					if (!empty($options['chmod'])) {
						chmod($options['output'], $options['chmod']);
					}
					return true;
				}
			break;

			case 'gif':
				if (ImageGIF($im, $options['output'])) {
					if (!empty($options['chmod'])) {
						chmod($options['output'], $options['chmod']);
					}
					return true;
				}
			break;

			case '':
				return $im;
			break;
		}

		unset($im);
		return false;
	}

	/**
	 * Try to create specified path
	 *
	 * If specified path is empty, return true
	 *
	 * @param string $output_path
	 * @param mixed $chmod Each folder's permissions
	 * @return boolean
	 */
	function createPath($output_path, $chmod = 0777) {
		if (empty($output_path)) {
			return true;
		}

		$arr_output_path = explode(DIRECTORY_SEPARATOR, $output_path);

		unset($arr_output_path[count($arr_output_path)-1]);

		$dir_path = implode($arr_output_path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

		if (!file_exists($dir_path)) {
			if (!mkdir($dir_path, $chmod, true)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Autorotate image
	 *
	 * Options:
	 * - 'input' Input file (path or gd resource)
	 * - 'output' Output path. If not specified, gd resource is returned
	 * - 'afterCallbacks' Functions to be executed after this one
	 * - 'quality' Output image quality (JPG only). Value from 0 to 100
	 * - 'compression' Output image compression (PNG only). Value from 0 to 9
	 * - 'chmod' What permissions should be applied to destination image
	 *
	 * @param mixed $options An array of options
	 * @return mixed boolean or GD resource if output was set to null
	 */
	function autorotate($options = array()) {
		$options = array_merge(array(
			'afterCallbacks' => null,
			'compression' => null,
			'quality' => null,
			'input' => null,
			'output' => null,
			'chmod' => null
		), $options);

		$type = $this->getImageType($options['input']);

		if ($type == 'jpg' && function_exists('exif_read_data')) {
			$exif = exif_read_data($options['input']);
		}

		$src_im = $this->openImage($options['input']);
		unset($options['input']);

		if (!$src_im) {
			return false;
		}

		if (!empty($exif['Orientation'])) {
			$orientation = $exif['Orientation'];
		} else if (!empty($exif['IFD0']['Orientation'])) {
			$orientation = $exif['IFD0']['Orientation'];
		} else {
			return $this->saveImage($src_im, $options);
		}

    switch ($orientation) {
			case 1:
				return $this->saveImage($src_im, $options);
			break;

			case 2: // horizontal flip
				$dst_im = $this->flip(array('input' => $src_im, 'mode' => 'horizontal'));
			break;

			case 3: // 180 rotate left
				$dst_im = $this->rotate(array('input' => $src_im, 'degrees' => 180));
			break;

			case 4: // vertical flip
				$dst_im = $this->flip(array('input' => $src_im, 'mode' => 'vertical'));
			break;

			case 5: // vertical flip + 90 rotate right
				$dst_im = $this->flip(array('input' => $src_im, 'mode' => 'vertical'));
				$dst_im = $this->rotate(array('input' => $src_im, 'degrees' => 90));
			break;

			case 6: // 90 rotate right
				$dst_im = $this->rotate(array('input' => $src_im, 'degrees' => 90));
			break;

			case 7: // horizontal flip + 90 rotate right
				$dst_im = $this->flip(array('input' => $src_im, 'mode' => 'horizontal'));
				$dst_im = $this->rotate(array('input' => $src_im, 'degrees' => 90));
			break;

			case 8: // 90 rotate left
				$dst_im = $this->rotate(array('input' => $src_im, 'degrees' => 270));
			break;

			default:
				return $this->saveImage($src_im, $options);
    }

    if (!$dst_im) {
			return false;
		}

		if (!$this->afterCallbacks($dst_im, $options['afterCallbacks'])) {
			return false;
		}

    return $this->saveImage($dst_im, $options);
	}


	/**
	 * Rotate image by specified angle (only agles divisable by 90 are supported)
	 *
	 * Options:
	 * - 'input' Input file (path or gd resource)
	 * - 'output' Output path. If not specified, gd resource is returned
	 * - 'degrees' Degrees to rotate by (divisible by 90)
	 * - 'afterCallbacks' Functions to be executed after this one
	 * - 'quality' Output image quality (JPG only). Value from 0 to 100
	 * - 'compression' Output image compression (PNG only). Value from 0 to 9
	 * - 'chmod' What permissions should be applied to destination image
	 *
	 * @param mixed $options An array of options
	 * @return mixed boolean or GD resource if output was set to null
	 */
	function rotate($options = array()) {
		$options = array_merge(array(
			'afterCallbacks' => null,
			'compression' => null,
			'quality' => null,
			'input' => null,
			'output' => null,
			'degrees' => 'horizontal',
			'chmod' => null
		), $options);

		$src_im = $this->openImage($options['input']);
		unset($options['input']);

		if (!$src_im) {
			return false;
		}

		$w = imagesx($src_im);
		$h = imagesy($src_im);

		switch ($options['degrees']) {
			case 90:
				$dst_im = imagecreatetruecolor($h, $w);
			break;

			case 180:
				$dst_im = imagecreatetruecolor($w, $h);
			break;

			case 270:
				$dst_im = imagecreatetruecolor($h, $w);
			break;

			case 360:
			case 0:
				return $this->saveImage($src_im, $options);
			break;

			default:
				return false;
		}

		if (!$dst_im) {
			return false;
		}

		for ($i=0; $i<$w; $i++) {
			for ($j=0; $j<$h; $j++) {
				$reference = imagecolorat($src_im, $i, $j);
				switch ($options['degrees']) {
					case 90:
						if (!@imagesetpixel($dst_im, ($h-1)-$j, $i, $reference)) {
							return false;
						}
					break;

					case 180:
						if (!@imagesetpixel($dst_im, $w-$i, ($h-1)-$j, $reference)) {
							return false;
						}
					break;

					case 270:
						if (!@imagesetpixel($dst_im, $j, $w-$i, $reference)) {
							return false;
						}
					break;
				}
			}
		}

		if (!$this->afterCallbacks($dst_im, $options['afterCallbacks'])) {
			return false;
		}

		return $this->saveImage($dst_im, $options);
	}

	/**
	 * Flip image
	 *
	 * Options:
	 * - 'input' Input file (path or gd resource)
	 * - 'output' Output path. If not specified, gd resource is returned
	 * - 'mode' Flip mode: horizontal, vertical, both
	 * - 'afterCallbacks' Functions to be executed after this one
	 * - 'quality' Output image quality (JPG only). Value from 0 to 100
	 * - 'compression' Output image compression (PNG only). Value from 0 to 9
	 * - 'chmod' What permissions should be applied to destination image
	 *
	 * @param mixed $options An array of options
	 * @return mixed boolean or GD resource if output was set to null
	 */
	function flip($options = array()) {
		$options = array_merge(array(
			'afterCallbacks' => null,
			'compression' => null,
			'quality' => null,
			'input' => null,
			'output' => null,
			'mode' => 'horizontal',
			'chmod' => null
		), $options);

		$src_im = $this->openImage($options['input']);
		unset($options['input']);

		if (!$src_im) {
			return false;
		}

		$w = imagesx($src_im);
		$h = imagesy($src_im);
		$dst_im = imagecreatetruecolor($w, $h);

		switch ($options['mode']) {
			case 'horizontal':
				for ($x=0 ; $x<$w ; $x++) {
					for ($y=0 ; $y<$h ; $y++) {
						imagecopy($dst_im, $src_im, $w-$x-1, $y, $x, $y, 1, 1);
					}
				}
			break;

			case 'vertical':
				for ($x=0 ; $x<$w ; $x++) {
					for ($y=0 ; $y<$h ; $y++) {
						imagecopy($dst_im, $src_im, $x, $h-$y-1, $x, $y, 1, 1);
					}
				}
			break;

			case 'both':
				for ($x=0 ; $x<$w ; $x++) {
					for ($y=0 ; $y<$h ; $y++) {
						imagecopy($dst_im, $src_im, $w-$x-1, $h-$y-1, $x, $y, 1, 1);
					}
				}
			break;

			default:
				return false;
		}

		if (!$this->afterCallbacks($dst_im, $options['afterCallbacks'])) {
			return false;
		}

		return $this->saveImage($dst_im, $options);
	}

	/**
	 * Get image's average color
	 *
	 * Options:
	 * - 'input' Input file (path or gd resource)
	 * - 'format' Output format (int, hex)
	 *
	 * @param array $options An array of options.
	 * @returm mixed string|boolean
	 */
	function averageColor($options = array()) {
		$options = array_merge(array(
			'input' => null,
			'format' => 'int'
		), $options);

		$img = $this->openImage($options['input']);
		unset($options['input']);

		if (!$img) {
			return false;
		}

		$dst_im = imagecreatetruecolor(1, 1);

		if (!$dst_im || !imagecopyresampled($dst_im, $img, 0, 0, 0, 0, 1, 1, imagesx($img), imagesy($img))) {
			return false;
		}

		$color = imagecolorat($dst_im, 0, 0);

		switch ($options['format']) {
			case 'hex':
				return str_pad(dechex($color), 6, '0', STR_PAD_LEFT);
			break;

			case 'int':
				return $color;
			break;
		}

		return false;
	}

	/**
	 * Get image's dominating color
	 *
	 * Options:
	 * - 'input' Input file (path or gd resource)
	 * - 'format' Output format (int, hex)
	 *
	 * @param array $options An array of options.
	 * @returm mixed string|boolean
	 */
	function dominatingColor($options = array()) {
		$options = array_merge(array(
			'input' => null,
			'format' => 'int'
		), $options);

		$img = $this->openImage($options['input']);
		unset($options['input']);

		if (!$img) {
			return false;
		}

		$dst_im = imagecreatetruecolor(100, 100);

		if (!$dst_im || !imagecopyresampled($dst_im, $img, 0, 0, 0, 0, 100, 100, imagesx($img), imagesy($img))) {
			return false;
		}

		$colors = array();

		for ($y=0; $y<50; $y++) {
			for ($x=0; $x<50; $x++) {
				$color = imagecolorat($dst_im, $x, $y);
				if (isset($colors[$color])) {
					$colors[$color]++;
				} else {
					$colors[$color] = 1;
				}
			}
		}

		arsort($colors);

		$color = array_shift(array_keys($colors));

		switch ($options['format']) {
			case 'hex':
				return str_pad(dechex($color), 6, '0', STR_PAD_LEFT);
			break;

			case 'int':
				return $color;
			break;
		}

		return false;
	}

	/**
	 * PNG ALPHA CHANNEL SUPPORT for imagecopymerge();
	 * This is a f-ion like imagecopymerge but it handle alpha channel well!!!
	 **/
	function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
		// getting the watermark width
		$w = imagesx($src_im);
		// getting the watermark height
		$h = imagesy($src_im);

		// creating a cut resource
		$cut = imagecreatetruecolor($src_w, $src_h);
		// copying that section of the background to the cut
		imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);

		// placing the watermark now
		imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
		return imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct);
	}

	/**
	 * Pixelate image
	 *
	 * Options:
	 * - 'input' Input file (path or gd resource)
	 * - 'output' Output path. If not specified, gd resource is returned
	 * - 'blocksize' Size of each pixel
	 * - 'afterCallbacks' Functions to be executed after this one
	 * - 'quality' Output image quality (JPG only). Value from 0 to 100
	 * - 'compression' Output image compression (PNG only). Value from 0 to 9
	 * - 'chmod' What permissions should be applied to destination image
	 *
	 * @param mixed $options An array of options
	 * @return mixed boolean or GD resource if output was set to null
	 */
	function pixelate($options) {
		$options = array_merge(array(
			'afterCallbacks' => null,
			'compression' => null,
			'quality' => null,
			'blocksize' => 10,
			'output' => null,
			'input' => null,
			'chmod' => null
		), $options);

		$img = $this->openImage($options['input']);
		unset($options['input']);

		if (!$img) {
			return false;
		}

		$w = imagesx($img);
		$h = imagesy($img);

		for($x=0; $x<$w; $x+=$options['blocksize']) {
			for($y=0; $y<$h; $y+=$options['blocksize']) {
				$colors = array(
					'alpha' => 0,
					'red' => 0,
					'green' => 0,
					'blue' => 0,
					'total' => 0
				);

				for ($block_x = 0 ; $block_x < $options['blocksize'] ; $block_x++) {
					for ($block_y = 0 ; $block_y < $options['blocksize'] ; $block_y++) {
						$current_block_x = $x + $block_x;
						$current_block_y = $y + $block_y;

						if ($current_block_x >= $w || $current_block_y >= $h) {
							continue;
						}

						$color = imagecolorat($img, $current_block_x, $current_block_y);
						imagecolordeallocate($img, $color);

						$colors['alpha'] += ($color >> 24) & 0xFF;
						$colors['red'] += ($color >> 16) & 0xFF;
						$colors['green'] += ($color >> 8) & 0xFF;
						$colors['blue'] += $color & 0xFF;
						$colors['total']++;
					}
				}

				$color = imagecolorallocatealpha(
					$img,
					$colors['red'] / $colors['total'],
					$colors['green'] / $colors['total'],
					$colors['blue'] / $colors['total'],
					$colors['alpha'] / $colors['total']
				);

				imagefilledrectangle($img, $x, $y, ($x + $options['blocksize'] - 1), ($y + $options['blocksize'] - 1), $color);
			}
		}

		if (!$this->afterCallbacks($img, $options['afterCallbacks'])) {
			return false;
		}

		return $this->saveImage($img, $options);
	}

	/**
	 * Meshify image
	 *
	 * Options:
	 * - 'input' Input file (path or gd resource)
	 * - 'output' Output path. If not specified, gd resource is returned
	 * - 'afterCallbacks' Functions to be executed after this one
	 * - 'blocksize' Size between two filled pixels
	 * - 'quality' Output image quality (JPG only). Value from 0 to 100
	 * - 'compression' Output image compression (PNG only). Value from 0 to 9
	 * - 'chmod' What permissions should be applied to destination image
	 * - 'color' Mesh color (array of rgb values)
	 *
	 * @param mixed $options An array of options
	 * @return mixed boolean or GD resource if output was set to null
	 */
	function meshify($options) {
		$options = array_merge(array(
			'afterCallbacks' => null,
			'compression' => null,
			'quality' => null,
			'blocksize' => 2,
			'output' => null,
			'input' => null,
			'chmod' => null,
			'color' => array(0, 0, 0)
		), $options);

		$src_im = $this->openImage($options['input']);
		unset($options['input']);

		$w = imagesx($src_im);
		$h = imagesy($src_im);

		$rgb = $this->readColor($options['color']);
		if (!$rgb) {
			$rgb = array(0, 0, 0);
		}

		$color = imagecolorallocate($src_im, $rgb[0], $rgb[1], $rgb[2]);

		for($x=0; $x<$w; $x+=$options['blocksize']) {
			for($y=0; $y<$h; $y+=$options['blocksize']) {
				imagesetpixel($src_im, $x, $y, $color);
			}
		}

		if (!$this->afterCallbacks($src_im, $options['afterCallbacks'])) {
			return false;
		}

		return $this->saveImage($src_im, $options);
	}

	/**
	 * Make image black and white
	 *
	 * Options:
	 * - 'input' Input file (path or gd resource)
	 * - 'output' Output path. If not specified, gd resource is returned
	 * - 'afterCallbacks' Functions to be executed after grayscaling
	 * - 'quality' Output image quality (JPG only). Value from 0 to 100
	 * - 'compression' Output image compression (PNG only). Value from 0 to 9
	 * - 'chmod' What permissions should be applied to destination image
	 *
	 * @return mixed boolean or GD resource if output was set to null
	 */
	function grayscale($options) {
		$options = array_merge(array(
			'afterCallbacks' => null,
			'compression' => null,
			'quality' => null,
			'output' => null,
			'input' => null,
			'chmod' => null
		), $options);

		$img = $this->openImage($options['input']);
		unset($options['input']);

		if (!$img) {
			return false;
		}

		$w = imagesx($img);
		$h = imagesy($img);

		$palette = array();

		for ($c=0; $c<256; $c++) {
			$palette[$c] = imagecolorallocate($img, $c, $c, $c);
		}

		for ($y=0; $y<$h; $y++) {
			for ($x=0; $x<$w; $x++) {
				$rgb = imagecolorat($img, $x, $y);

				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				$gs = $this->yiq($r, $g, $b);

				imagesetpixel($img, $x, $y, $palette[$gs]);
			}
		}

		if (!$this->afterCallbacks($img, $options['afterCallbacks'])) {
			return false;
		}

		return $this->saveImage($img, $options);
	}

	/**
	 * Helper function to covert color to grayscale
	 */
	function yiq($r, $g, $b) {
		return (($r*0.299)+($g*0.587)+($b*0.114));
	}

	/**
	 * Perform afterCallbacks on specified image
	 *
	 * @param resource $im Image to perform callback on
	 * @param mixed $functions Callback functions and their arguments
	 * @return boolean
	 */
	function afterCallbacks(&$im, $functions) {
		if (empty($functions)) {
			return true;
		}

		foreach ($functions as $v) {
			$v[1]['input'] = $im;

			$im = $this->$v[0]($v[1]);

			if (!$im) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Read color (convert various formats into rgb)
	 *
	 * Supported values: rgb (array), hex (string), int (integer)
	 *
	 * @param mixed $color Input color
	 * @return array Array of rgb values
	 */
	function readColor($color) {
		if (is_array($color)) {
			if (count($color) == 3) {
				return $color;
			}
		} else if (is_string($color)) {
			return $this->hex2rgb($color);
		} else if (is_int($color)) {
			return $this->hex2rgb(dechex($color));
		}

		return false;
	}

	/**
	 * Convert HEX color to RGB
	 *
	 * @param string $color HEX color (3 or 6 chars)
	 * @return mixed
	 */
	function hex2rgb($color) {
		if ($color[0] == '#') {
			$color = substr($color, 1);
		}

		if (strlen($color) == 6) {
			list($r, $g, $b) = array($color[0].$color[1], $color[2].$color[3], $color[4].$color[5]);
		} else if (strlen($color) == 3) {
			list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
		} else {
			return false;
		}

		return array(hexdec($r), hexdec($g), hexdec($b));
	}

}

?>