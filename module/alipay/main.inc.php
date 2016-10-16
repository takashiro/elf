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

/* To make use of the following codes, you have to add the rewrite rules below.
RewriteEngine On
RewriteBase /
RewriteRule ^weixinguard(.*)\.htm$ index.php?mod=alipay&get=$1
*/

if(empty($_GET['get'])){
	function isWeixin(){
		return strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false;
	}

	if(empty($_GET['skipprotector']) && isWeixin()){
		if(!$_G['user']->isLoggedIn()){
			showmsg('inaccessible_if_not_logged_in', 'index.php?mod=user:login');
		}

		$get = $_GET;
		unset($get['mod']);
		$get = http_build_query($get);
		rheader('Location: weixinguard'.urlencode($get).'.htm');
		exit;
	}
}else{
	$protected_url = 'index.php?mod=alipay&'.urldecode($_GET['get']).'&skipprotector=1';
	include view('protector');
	exit;
}

if(!$_G['user']->isLoggedIn()){
	showmsg('inaccessible_if_not_logged_in', 'index.php?mod=user:login');
}

$paymentconfig = readdata('payment');
if(empty($paymentconfig['enabled_method'][Wallet::ViaAlipay])){
	showmsg('alipay_is_disabled');
}

$_G['alipaytrade'] = array(
	'out_trade_no' => '',
	'subject' => '',
	'total_fee' => 0.00,
	'show_url' => 'index.php',
);

runhooks('alipay_started');

if(empty($_G['alipaytrade']['out_trade_no']) || empty($_G['alipaytrade']['subject']) || !is_numeric($_G['alipaytrade']['total_fee']))
	showmsg('illegal_operation');

require_once MOD_ROOT.'class/Alipay.class.php';
$alipay = new Alipay;
$alipay->createOrder($_G['alipaytrade']['out_trade_no'], $_G['alipaytrade']['total_fee'], $_G['alipaytrade']['subject']);
