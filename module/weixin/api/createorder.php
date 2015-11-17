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

require_once '../../../core/init.inc.php';

$input = file_get_contents('php://input');
if(empty($input))
	exit('access denied');

$xml = new XML;
$xml->loadXML($input);
$xml = $xml->toArray();
if(empty($xml['xml']))
	exit('invalid input');
$input = $xml['xml'];

require_once '../class/WeChatPay.class.php';
$api = new WeChatPay;

$result = array(
	'return_code' => 'FAIL',			//此字段是通信标识，非交易标识，交易是否成功需要查看result_code来判断
	'return_msg' => '',					//返回信息，如非空，为错误原因;签名失败;具体某个参数格式校验错误.
	'appid' => $api->getAppId(),
	'mch_id' => $api->getMerchantId(),
	'prepay_id' => '',					//调用统一下单接口生成的预支付ID
	'result_code' => 'FAIL',				//SUCCESS/FAIL
	'err_code_des' => '',				//当result_code为FAIL时，商户展示给用户的错误提示
);
function output_result(){
	global $api, $result;
	$api->signData($result);
	echo '<xml>';
	foreach($result as $key => $value){
		echo '<', $key, '>', $value, '</', $key, '>';
	}
	echo '</xml>';
	exit;
}

if(!$api->checkSource($input)){
	$result['err_code_des'] = $result['return_msg'] = 'invalid source';
	output_result();
}

if(!$api->checkSignature($input)){
	$result['err_code_des'] = $result['return_msg'] = 'invalid signature';
	output_result();
}

$_G['wechatpaytrade'] = array(
	'out_trade_no' => $input['product_id'],
	'total_fee' => 0.0,
	'subject' => '',
	'valid' => false,
);
runhooks('wechatpay_createorder');

if(empty($_G['wechatpaytrade']['valid'])){
	$result['err_code_des'] = $result['return_msg'] = 'invalid trade';
	output_result();
}

$reply = $api->createOrder(array(
	'body' => $_G['wechatpaytrade']['subject'],
	'notify_url' => $_G['root_url'].'module/weixin/api/notify.php',
	'openid' => $input['openid'],
	'out_trade_no' => $input['product_id'],
	'total_fee' => round($_G['wechatpaytrade']['total_fee'] * 100),
	'trade_type' => 'NATIVE',
));

$xml = new XML;
$xml->loadXML($reply);
$reply = $xml->toArray();
$reply = $reply['xml'];

$result['return_code'] = 'SUCCESS';
isset($reply['result_code']) && $result['result_code'] = $reply['result_code'];
isset($reply['err_code_des']) && $result['err_code_des'] = $reply['err_code_des'];
isset($reply['prepay_id']) && $result['prepay_id'] = $reply['prepay_id'];
output_result();
