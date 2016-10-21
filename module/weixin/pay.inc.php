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

if(!$_G['user']->isLoggedIn())
	showmsg('inaccessible_if_not_logged_in', 'index.php?mod=user:login');

$paymentconfig = readdata('payment');
if(empty($paymentconfig['enabled_method'][Wallet::ViaWeChat])){
	showmsg('wechatpay_is_disabled');
}

$_G['wechatpaytrade'] = array(
	'out_trade_no' => '',
	'subject' => '',
	'total_fee' => 0.00,
);

runhooks('wechatpay_started');

if(empty($_G['wechatpaytrade']['out_trade_no']))
	showmsg('illegal_operation');

$trade = &$_G['wechatpaytrade'];
require_once MOD_ROOT.'class/WeChatPay.class.php';
$wechat = new WeChatPay;
$reply = $wechat->createOrder($trade['out_trade_no'], $trade['total_fee'], $trade['subject']);

if($wechat->getTradeType() == 'APP'){
	$response = array(
		'appid' => $wechat->getAppId(),
		'partnerid' => $wechat->getMerchantId(),
		'prepayid' => $reply['prepay_id'],
		'package' => 'Sign=WXPay',
		'timestamp' => TIMESTAMP + 8 * 3600,
		'noncestr' => randomstr(32),
	);
	$response['sign'] = $wechat->generateSignature($response);
	echo json_encode($response);
	exit;
}else{
	$qrcode_url = $reply['code_url'];
	include view('pay_qrcode');
}
