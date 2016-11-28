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
require_once '../class/Alipay.class.php';

$alipay = new Alipay;
if($_GET){
	if(isset($_GET['trade_no'])){
		$data = $alipay->queryOrder($_GET['trade_no']);
	}elseif(isset($_GET['out_trade_no'])){
		$data = $alipay->queryOrder($_GET['out_trade_no']);
	}

	if(!isset($data['alipay_trade_query_response']['trade_status'])){
		showmsg('failed_to_retrieve_trade_state', 'index.php');
	}

	$trade = $data['alipay_trade_query_response'];
	runhooks('trade_callback_executed', array(
		$_GET['out_trade_no'],
		Wallet::ViaAlipay,
		$_GET['trade_no'],
		Alipay::$TradeStateEnum[$trade['trade_status']],
		$trade,
	));
}else{
	showmsg('failed_to_retrieve_trade_state', 'index.php');
}
