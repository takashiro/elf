<?php

/***********************************************************************
Orchard Hut Online Shop
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

class SMS extends CUrl{

	protected $account;
	protected $pwmd5;

	public function __construct($config = null){
		parent::__construct();

		if($config === null){
			$config = readdata('sms');
		}

		if(isset($config['account']) && isset($config['pwmd5'])){
			$this->account = $config['account'];
			$this->pwmd5 = $config['pwmd5'];
		}

		$this->setServer('http://api.sms.cn/sms/');
	}

	public function send($template, $mobile, $content){
		$data = array(
			'uid' => $this->account,
			'pwd' => $this->pwmd5,
			'template' => intval($template),
			'mobile' => trim($mobile),
			'content' => is_string($content) ? $content : json_encode($content, JSON_UNESCAPED_UNICODE),
		);

		$result = $this->request('?ac=send'.http_build_query($data));
		return json_decode($result, true);
	}

}
