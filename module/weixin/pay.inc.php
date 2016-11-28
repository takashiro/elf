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

$paymentconfig = Wallet::ReadConfig();
if(empty($paymentconfig['enabled_method'][Wallet::ViaWeChat])){
	showmsg('wechatpay_is_disabled');
}

$_G['trade'] = array(
	'out_trade_no' => '',
	'subject' => '',
	'total_fee' => 0.00,
);

runhooks('trade_started', array(Wallet::ViaWeChat));

$trade = &$_G['trade'];
if(empty($trade['out_trade_no']))
	showmsg('illegal_operation');


require_once MOD_ROOT.'class/WeChatPay.class.php';
$wechat = new WeChatPay;

if(!empty($_GET['enable_trade_query'])){
	$reply = $wechat->queryOrder($trade['out_trade_no'], false);
	if($reply && isset($reply['return_code']) && $reply['return_code'] == 'SUCCESS'){
		if(isset($reply['trade_state']) && $reply['trade_state'] == 'SUCCESS'){
			runhooks('trade_callback_executed', array(
				$trade['out_trade_no'],
				Wallet::ViaWeChat,
				$reply['transaction_id'],
				Wallet::TradeSuccess,
				$reply
			));
			exit;
		}
	}
}

$reply = $wechat->createOrder($trade['out_trade_no'], $trade['total_fee'], $trade['subject']);

$_G['user']->lastpaymentmethod = Wallet::ViaAlipay;

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
	$response['out_trade_no'] = $trade['out_trade_no'];
	echo json_encode($response);
	exit;
}else{
	$qrcode_url = $reply['code_url'];
	include view('pay_qrcode');
}
