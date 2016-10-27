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

if(!$_G['user']->isLoggedIn()){
	redirect('index.php?mod=user:login');
}

$paymentconfig = Wallet::ReadConfig();
if(isset($_GET['orderid'])){
	if(empty($paymentconfig['enabled_method'][Wallet::ViaWallet])){
		showmsg('wallet_payment_is_disabled');
	}

	$order = new Order($_GET['orderid']);
	if(!$order->exists() || $order->status == Order::Canceled){
		showmsg('order_not_exist', 'back');
	}

	if(!empty($order->tradestate)){
		//@todo: Judge payment method
		showmsg('your_alipay_wallet_is_processing_the_order', 'back');
	}

	if($_G['user']->wallet < $order->totalprice){
		showmsg('wallet_is_insufficient', 'back');
	}

	$wallet = new Wallet($_G['user']);
	if(!$wallet->pay($order)){
		showmsg('wallet_is_insufficient', 'back');
	}

	showmsg('order_is_successfully_paid', 'back');
}

include view('wallet');
