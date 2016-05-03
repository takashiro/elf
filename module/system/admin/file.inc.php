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

if(!defined('IN_ADMINCP')) exit('access denied');

class SystemFileModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('system');
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$lost_files = array();
		$modified_files = array();

		$lines = file('data/sha1.inc.php');
		$line_count = count($lines);
		for($i = 1; $i < $line_count; $i++){
			list($standard_sha1, $filename) = explode("\t", $lines[$i]);
			$standard_sha1 = trim($standard_sha1);
			$filename = trim($filename);

			if(file_exists(S_ROOT.$filename)){
				$current_sha1 = self::FileSHA1($filename);
				if($standard_sha1 != $current_sha1){
					$modified_files[] = array(
						'file_name' => $filename,
						'modified_time' => filemtime($filename),
					);
				}
			}else{
				$lost_files[] = $filename;
			}
		}

		$standard_update_time = filemtime('data/sha1.inc.php');

		$writable_directories = array(
			'./data/',
			'./data/attachment/',
			'./data/cache/',
			'./data/error/',
			'./data/js/',
			'./data/log/',
			'./data/template/',
		);

		$directory_results = array();
		foreach($writable_directories as $dir){
			$directory_results[] = array(
				'path' => $dir,
				'is_writable' => is_writable(S_ROOT.$dir),
			);
		}

		include view('file');
	}

	static private $BinaryExtensions = array('.jpg', '.png');
	static private function IsBinary($filename){
		foreach(self::$BinaryExtensions as $ext){
			if(substr_compare($filename, $ext, -strlen($ext)) == 0){
				return true;
			}
		}
		return false;
	}

	static private function FileSHA1($filename){
		$data = file_get_contents(S_ROOT.$filename);
		if(!self::IsBinary($filename)){
			$data = str_replace("\r\n", "\n", $data);
		}
		return sha1('blob '.strlen($data).chr(0).$data);
	}
}
