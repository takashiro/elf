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

if(!defined('S_ROOT')) exit('access denied');

class Alipay extends CUrl{

    protected $config;

    public function __construct($config = null){
        parent::__construct();
        $this->server = 'https://openapi.alipay.com/gateway.do';

        if($config === null){
            $this->config = readdata('alipay');
        }else{
            $this->config = $config;
        }
    }

    public function createOrder($out_trade_no, $total_amount, $subject, $body = null){
        $data = array(
            'body' => $body ?? $subject,
            'subject' => $subject,
            'out_trade_no' => $out_trade_no,
            'timeout_express' => '90m',
            'total_amount' => $total_amount,
            'product_code' => 'QUICK_WAP_PAY',
        );

        if(self::IsClient()){
			$request = $this->createRequest('alipay.trade.app.pay', $data);
			echo http_build_query($request);
			exit;
		}else{
			$request = $this->createRequest('alipay.trade.wap.pay', $data);
			$this->redirectRequest($request);
		}
    }

    public function queryOrder($trade_no, $is_trade_no = true){
        $data = array(
            ($is_trade_no ? 'trade_no' : 'out_trade_no') => $trade_no,
        );

        $request = $this->createRequest('alipay.trade.query', $data);
        return $this->postRequest($request);
    }

    public function receiveNotification(){
        if(empty($_POST['sign'])){
            return false;
        }
        $sign = $_POST['sign'];

        $data = $_POST;
        unset($data['sign'], $data['sign_type']);
        if(!$this->verifyData($data, $sign)){
            return false;
        }

        return $data;
    }

    protected function createRequest($method, $data = null){
        global $_G;
        $parameters = array(
            'app_id' => $this->config['app_id'],
            'method' => $method,
            'format' => 'json',
            'return_url' => $_G['site_url'].'module/alipay/api/callback.php',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'timestamp' => rdate(TIMESTAMP, 'Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => !empty($this->config['notify_url']) ? $this->config['notify_url'] : $_G['site_url'].'module/alipay/api/notify.php',
            'biz_content' => $data ? json_encode($data) : '',
        );

        $parameters['sign'] = $this->signData($parameters);
        return $parameters;
    }

    protected function redirectRequest($parameters){
        $html = '<!DOCTYPE html><html><head><title>Loading...</title></head><body><form id="alipay-form" action="'.$this->server.'" method="post">';
        foreach($parameters as $name => $value){
            $html.= '<input type="hidden" name="'.$name.'" value="'.str_replace('"', '&quot;', $value).'">';
        }
        $html.= '<script>document.getElementById("alipay-form").submit();</script>';
        $html.= '</form>Loading...</body></html>';
        exit($html);
    }

    protected function postRequest($parameters){
        $response = parent::request('', $parameters);
        return json_decode($response, true);
    }

    private function signData($data){
        if(is_array($data)){
            $data = self::ParseSignContent($data);
        }
        $res = openssl_get_privatekey($this->config['private_key']);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        return base64_encode($sign);
    }

    private function verifyData($data, $sign){
        if(is_array($data)){
            $data = self::ParseSignContent($data);
        }
        $res = openssl_get_publickey($this->config['ali_public_key']);
        $result = (bool) openssl_verify($data, base64_decode($sign), $res);
        openssl_free_key($res);
        return $result;
    }

	private static function IsClient(){
		return !empty($_GET['is_client']) || (!empty($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'NativeApp') !== false);
	}

    private static function ParseSignContent($data){
        ksort($data);
        $strs = array();
        foreach($data as $key => $value){
            if($value){
                $strs[] = $key.'='.$value;
            }
        }
        return implode('&', $strs);
    }

	public static $TradeStateEnum;
}

Alipay::$TradeStateEnum = array(
	'WAIT_BUYER_PAY' => Wallet::WaitBuyerPay,
	'TRADE_CLOSED' => Wallet::TradeClosed,
	'TRADE_SUCCESS' => Wallet::TradeSuccess,
	'TRADE_PENDING' => Wallet::TradePending,
	'TRADE_FINISHED' => Wallet::TradeFinished,
);
