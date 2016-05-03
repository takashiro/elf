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

class Authkey extends DBObject{
	const TABLE_NAME = 'authkey';
	const PRIMARY_KEY = 'code';

	public static $ExpiryTime;

	public function __construct($code){
		parent::__construct();
		$this->fetch('*', array('code' => $code));
	}

	public function isExpired(){
		if($this->expiry < TIMESTAMP){
			if($this->code){
				$this->deleteFromDB();
			}
			return true;
		}
		return false;
	}

	public function match($key){
		return $this->authkey == $key;
	}

	public function matchOnce($key){
		$result = $this->match($key);
		if($result){
			$this->deleteFromDB();
		}
		return $result;
	}

	public static function Generate($code, $expiry = NULL){
		global $db;
		$table = $db->select_table('authkey');

		$authkey = array(
			'code' => $code,
			'authkey' => randomstr(32),
			'expiry' => $expiry == NULL ? TIMESTAMP + self::$ExpiryTime : $expiry,
		);

		$table->insert($authkey, true);

		return $authkey['authkey'];
	}
}

Authkey::$ExpiryTime = 5 * 60;
