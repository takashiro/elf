<?php

/***********************************************************************
Elf Web App
Copyright (C) 2013-2015  Kazuichi Takashiro

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

takashiro@qq.com
************************************************************************/

class GdImage{

	private $image = null;
	private $extension = '';

	public function __construct($path){
		if(!file_exists($path) || !is_readable($path)){
			return;
		}

		$file_info = pathinfo($path);
		$this->extension = strtolower($file_info['extension']);
		if($this->extension == 'jpg' || $this->extension == 'jpeg'){
			$this->image = imagecreatefromjpeg($path);
			$this->extension = 'jpg';
		}elseif($this->extension == 'bmp'){
			$this->image = imagecreatefromwbmp($path);
		}else{
			$func = 'imagecreatefrom'.$this->extension;
			$this->image = $func($path);
		}
	}

	public function __destruct(){
		if($this->image){
			imagedestroy($this->image);
		}
	}

	public function isValid(){
		return $image !== null;
	}

	public function save($path, $type = null){
		$type = $type ?? $this->extension;
		if($type == 'jpg'){
			imagejpeg($this->image, $path);
		}elseif($type == 'bmp'){
			imagewbmp($this->image, $path);
		}else{
			$func = 'image'.$type;
			$func($this->image, $path);
		}
	}

	public function scale($new_width, $new_height = -1, $mode = IMG_BILINEAR_FIXED){
		$image = imagescale($this->image, $new_width, $new_height, $mode);
		imagedestroy($this->image);
		$this->image = $image;
	}

	public function crop($x, $y, $width, $height){
		$image = imagecreatetruecolor($width, $height);
		imagecopy($image, $this->image, 0, 0, $x, $y, $width, $height);
		imagedestroy($this->image);
		$this->image = $image;
	}

	public function thumb($width, $height){
		$original_width = imagesx($this->image);
		$original_height = imagesy($this->image);
		$original_ratio = $original_width / $original_height;
		if($original_ratio < $width / $height){
			$original_height = $width / $original_ratio;
			$this->scale($width, $original_height);
			$y = ($original_height - $height) / 2;
			$this->crop(0, $y, $width, $height);
		}else{
			$original_width = $height * $original_ratio;
			$this->scale($original_width, $height);
			$x = ($original_width - $width) / 2;
			$this->crop($x, 0, $width, $height);
		}
	}

	public function getExtension(){
		return $this->extension;
	}

	private static $ExtensionId = array(
		'',
		'png',
		'jpg',
		'gif',
		'bmp',
		'webp',
	);
	const PNG = 1;
	const JPG = 2;
	const GIF = 3;
	const BMP = 4;
	const WEBP = 5;

	public function getExtensionId(){
		foreach(self::$ExtensionId as $id => $extension){
			if($extension == $this->extension){
				return $id;
			}
		}
		return -1;
	}

	static public function Extension($id){
		return self::$ExtensionId[$id] ?? '';
	}
}
