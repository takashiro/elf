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

class WeChatPay extends CUrl{

	protected $appId;
	protected $appSecret;
	protected $merchantId;
	protected $merchantKey;

	public function __construct($config = null){
		parent::__construct();
		$this->setServer('https://api.mch.weixin.qq.com/');

		$config === null && $config = readdata('wxsv');

		isset($config['app_id']) && $this->appId = $config['app_id'];
		isset($config['app_secret']) && $this->appSecret = $config['app_secret'];
		isset($config['mch_id']) && $this->merchantId = $config['mch_id'];
		isset($config['mch_key']) && $this->merchantKey = $config['mch_key'];
	}

	public function getAppId(){
		return $this->appId;
	}

	public function getMerchantId(){
		return $this->merchantId;
	}

	public function createOrder($data){
		$data = array(
			'appid' => $this->appId,
			'body' => $data['body'],
			'mch_id' => $this->merchantId,
			'notify_url' => $data['notify_url'],
			'openid' => $data['openid'],
			'out_trade_no' => $data['out_trade_no'],
			'spbill_create_ip' => User::ip(),
			'total_fee' => $data['total_fee'],
			'trade_type' => $data['trade_type'],
		);

		$this->signData($data);

		$content = '<xml>';
		foreach($data as $key => $value){
			$content.= '<'.$key.'>'.$value.'</'.$key.'>';
		}
		$content.= '</xml>';

		return $this->request('pay/unifiedorder', $content);
	}

	public function signData(&$data){
		$data['nonce_str'] = randomstr(32);
		$data['sign'] = $this->generateSignature($data);
	}

	public function generateSignature($data){
		ksort($data);
		$str = array();
		foreach($data as $key => $value)
			empty($value) || $str[] = $key.'='.$value;
		$str = implode('&', $str);
		return strtoupper(md5($str.'&key='.$this->merchantKey));
	}

	public function checkSource($data){
		return $data['appid'] == $this->appId && $data['mch_id'] == $this->merchantId;
	}

	public function checkSignature($data){
		if(!isset($data['sign']))
			return false;

		$sign = $data['sign'];
		unset($data['sign']);

		return $sign == $this->generateSignature($data);
	}
}
