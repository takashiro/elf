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

class WeixinSNS extends CUrl{

	protected $appId;
	protected $appSecret;

	protected $error = array();

	public function __construct($appId = NULL, $appSecret = NULL){
		parent::__construct();
		$this->setServer('https://api.weixin.qq.com/sns/');

		if($appId && $appSecret){
			$this->appId = $appId;
			$this->appSecret = $appSecret;
		}else{
			$this->restoreConfig();
		}
	}

	public function restoreConfig(){
		$config = readata('wxsns');
	}

	public function request($url, $data = NULL){
		if($data !== NULL && !is_string($data)){
			$data = json_encode($data, JSON_UNESCAPED_UNICODE);
		}
		$result = json_decode(parent::request($url, $data), true);
		$this->error = isset($result['errcode']) ? $result : array();
		return $result;
	}

	public function hasError(){
		return isset($this->error['errcode']) && $this->error['errcode'] != 0;
	}

	public function getError(){
		return $this->error;
	}

	public function getErrorMessage(){
		return isset($this->error['errmsg']) ? $this->error['errmsg'] : '';
	}

	public function getAccessToken($code){
		return $this->request('oauth2/access_token?appid='.$this->appId.'&secret='.$this->appSecret.'&code='.$code.'&grant_type=authorization_code');
	}

	public function getUserInfo($access_token, $openid){
		return $this->request('userinfo?access_token='.$access_token.'&openid='.$openid);
	}

}
