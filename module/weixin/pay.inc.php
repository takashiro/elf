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
	showmsg('inaccessible_if_not_logged_in', 'index.php?mod=user&action=login');

$paymentconfig = readdata('payment');
if(empty($paymentconfig['enabled_method'][Wallet::ViaWeChat])){
	showmsg('wechatpay_is_disabled');
}

$_G['wechatpaytrade'] = array(
	'out_trade_no' => '',
);

runhooks('wechatpay_started');

if(empty($_G['wechatpaytrade']['out_trade_no']))
	showmsg('illegal_operation');

$config = readdata('wxsv');

$nonce_str = randomstr(32);
$arguments = array(
	'appid' => $config['app_id'],
	'mch_id' => $config['mch_id'],
	'product_id' => $_G['wechatpaytrade']['out_trade_no'],
	'time_stamp' => TIMESTAMP,
	'nonce_str' => $nonce_str,
);
ksort($arguments);
$arguments = http_build_query($arguments);
$sign = strtoupper(md5($arguments.'&key='.$config['mch_key']));

$qrcode_url = 'weixin://wxpay/bizpayurl?sign='.$sign.'&'.$arguments;

include view('pay_qrcode');
