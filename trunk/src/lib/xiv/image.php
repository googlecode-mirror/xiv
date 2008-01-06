<?php
/**
 * Image handler
 * ---
 * Written by Marioly Garza Lozano <marioly@hackerss.com>
 *						J. Carlos Nieto <xiam@menteslibres.org>
 * Copyright (c) 2007 Marioly Garza Lozano
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Marioly Garza Lozano <marioly@hackerss.com>
 * @author          J. Carlos Nieto <xiam@menteslibres.org>
 * @copyright       Copyright (c) 2007-2008, Marioly Garza Lozano
 * @link            http://www.textmotion.org
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

/*
require_once "constants.php";
require_once "object.php";
*/

class image extends tm_object {

	/**
	* Data buffer
	* @var resource
	*/
	public $buff = null;

	/**
	* Width of the buffer's image
	* @var int
	*/
	public $width = 0;

	/**
	* Height of the buffer's image
	* @var int
	*/
	public $height = 0;

	/**
	* Full path of the source file
	* @var string
	*/
	public $source = null;

	/**
	* Constructor
	*/
	function __construct(&$params = null) {
		parent::__construct($params);
	}

	/**
	* Loads the image from a physical file into memory
	* @param string $source Full path of the image file 
	*/
	function load($source) {
		if ($this->accept($source)) {
			$f = null;	
			switch ($this->type) {
				case 'jpg': case 'jpeg': $f = 'imagecreatefromjpeg'; break;
				case 'png': $f = 'imagecreatefrompng'; break;
				case 'gif': $f = 'imagecreatefromgif'; break;
				case 'bmp': $f = 'imagecreatefromwbmp'; break;
			}
			if (function_exists($f)) {
				$this->buff = $f($source);	
				if ($this->buff) {
					$size = getimagesize($source);
					$this->width = $size[0];
					$this->height = $size[1];
					$this->source = $source;
					return true;
				} else {
					$this->error(__('Failed to allocate memory for "%s".', basename($source)));
				}
			} else {
				$this->error(__('This PHP installation cannot hold "%s" files.', $this->type));
			}
		} else {
			$this->error(__('Unsupported image format (%s).', basename($source)));
		}
	}
	
	/**
	* Sets the default font
	* @param string $font Full path of the font file.
	*/
	public function set_font($font) {
		if (file_exists($font) && preg_match('/.*\.ttf$/i', $font)) {
			$this->font = $font;
			return true;
		} else {
			$env =& $this->using('env');
			$env->error(__('Invalid font file'));
		}
	}

	/**
	* Determines if a image file exists and is loadable (?)
	* @var string $source Full path of the file.
	*/
	public function accept($source) {
		extract(
			$this->using(
				'env',
				'archive'
			)
		);
		if (file_exists($source)) {
			$this->type = $archive->extension($source);
			return true;
		} else {
			$env->error(__('File "%s" do not exist.', $source));
		}
		return false;
	}
	
	public function data_dump() {
		return $this->buff;
	}

	/**
	* Copies an image buffer onto another
	* @param resource $dst destination buffer
	* @param resource $src source buffer
	* @param int $dst_x destination X
	* @param int $dst_y destination Y
	* @param int $src_x source X
	* @param int $src_y source Y
	* @param int $dst_w destination width
	* @param int $dst_h destination height
	* @param int $src_w source width
	* @param int $src_h source height
	*/
	public function copy(&$dst, &$src, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
		if (function_exists('imagecopyresampled')) {
			$f = 'imagecopyresampled';
		} else if (function_exists('imagecopyresized')) {
			$f = 'imagecopyresized';
		} else {
			$env =& $this->using('env');
			$env->error(__('Could not copy the image.'));
		}
		return $f($dst, $src, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
	}

	/**
	* Creates an image buffer
	* @param int $width
	* @param int $height
	*/
	public function create($width, $height) {
		// creating a buffer (into a referenced variable)
		if (function_exists('imagecreatetruecolor')) {
			$f = 'imagecreatetruecolor';
		} else if (function_exists('imagecreate')) {
			$f = 'imagecreate';
		} else {
			$env =& $this->using('env');
			$env->error(__('Could not create a new image.'));
		}
		return $f($width, $height);
	}

	public function thumbnail($largest_side) {
		return $this->resize($largest_side);
	}
	
	/**
	* Resizes an image
	* @param int $larger_side The size of the side you want to be the largest
	*/
	public function resize($largest_side) {
		
		$archive =& $this->using('archive');

		if ($this->buff) {

			$largest_side = explode('x', $largest_side);

			if (isset($largest_side[1])) {

				$new_width = $largest_side[0];
				$new_height = $largest_side[1];
			
				$new_buff = $this->create($new_width, $new_height);

				if (function_exists('imageantialias')) {
					// transparent png resize
					imageantialias($new_buff, true);
					imagealphablending($new_buff, false);
					imagesavealpha($new_buff, true);
					$backg = imagecolorallocate($new_buff, 0, 255, 255);
					imagecolortransparent($new_buff, $backg);
				}

				$min = ($this->width < $this->height) ? $this->width : $this->height;
				$max = ($this->width > $this->height) ? $this->width : $this->height;

				$this->copy($new_buff, $this->buff, 0, 0, ($this->width-$min)/2, ($this->height-$min)/2, $new_width, $new_height, $min, $min);

				$this->buff = $new_buff;
				$this->width = imagesx($this->buff);
				$this->height = imagesy($this->buff);
			} else {

				$largest_side = $largest_side[0];

				// getting the largest side and obtaining size variation's ratio
				if ($this->width > $this->height) {
					$ratio = $largest_side/$this->width;
				} else {
					$ratio = $largest_side/$this->height;
				}

				// new width and height values using the ratio
				$new_width = round($this->width*$ratio); 
				$new_height = round($this->height*$ratio);

				$new_buff = $this->create($new_width, $new_height);

				$this->copy($new_buff, $this->buff, 0, 0, 0, 0, $new_width, $new_height, $this->width, $this->height);

				$this->buff = $new_buff;
				$this->width = imagesx($this->buff);
				$this->height = imagesy($this->buff);
			}
			return true;
		} else {
			$env->error(__('You have not loaded an image yet!'));
		}
		return false;
	}

	/**
	* Adds a watermark onto the current buffer
	* @param string $watermark Watermark full path file
	*/
	public function watermark($watermark) {
		if (file_exists($watermark)) {
			$wmark = new image();
			$wmark->load($watermark);

			// bottom left
			$wmark_x = $this->width - $wmark->width - 10;
			$wmark_y = $this->height - $wmark->height - 10;

			if (function_exists('imagealphablending')) {
				imagealphablending($wmark->buff, true);
			}
			
			$this->copy($this->buff, $wmark->buff, $wmark_x, $wmark_y, 0, 0, $wmark->width, $wmark->height, $wmark->width, $wmark->height);
		}
	}

	/**
	* Physically saves an image to disk (overwrite if exists)
	* @param $dest Destination file
	*/
	public function save($dest = null) {
		if (!$dest) {
			$dest = $this->source;
		}
		$archive =& $this->using('archive');
		$type = $archive->extension($dest);
		switch ($type) {
			case 'jpg': case 'jpeg': $f = 'imagejpeg'; break;
			case 'png': $f = 'imagepng'; break;
			case 'gif': $f = 'imagegif'; break;
			case 'bmp': $f = 'imagewbmp'; break;
		}
		$f($this->buff, $dest);
	}

	/**
	* Unit test
	* @param string $file 
	*/
	public static function run_test() {
		$img = new image();
		$img->load('/tmp/test.jpg');
		$img->resize(700);
		$img->watermark('/tmp/wm.png');
		$img->resize(200);
		$img->save('/tmp/success.png');
	}

}
/*
image::run_test();
*/
?>