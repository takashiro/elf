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
	protected $tradeType = 'NATIVE';

	public function __construct($config = null){
		parent::__construct();
		$this->setServer('https://api.mch.weixin.qq.com/');

		if($config === null){
			if(!empty($_GET['is_client']) || empty($_SERVER['HTTP_USER_AGENT']) || strpos($_SERVER['HTTP_USER_AGENT'], 'NativeApp') !== false){
				$config = readdata('wxapp');
				$this->tradeType = 'APP';
			}else{
				$config = readdata('wxsv');
				$this->tradeType = 'NATIVE';
			}
		}

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

	public function getTradeType(){
		return $this->tradeType;
	}

	public function createOrder($out_trade_no, $total_fee, $subject){
		global $_G;
		$data = array(
			'appid' => $this->appId,
			'mch_id' => $this->merchantId,
			'body' => $subject,
			'notify_url' => $_G['site_url'].'module/weixin/api/notify.php',
			'out_trade_no' => $out_trade_no,
			'spbill_create_ip' => User::ip(),
			'total_fee' => round($total_fee * 100),
			'trade_type' => $this->tradeType,
		);

		$this->signData($data);

		$content = '<xml>';
		foreach($data as $key => $value){
			$content.= '<'.$key.'>'.$value.'</'.$key.'>';
		}
		$content.= '</xml>';

		$reply = $this->request('pay/unifiedorder', $content);
		$xml = new XML;
		$xml->loadXML($reply);
		$reply = $xml->toArray();
		$reply = $reply['xml'];
		return $reply;
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
