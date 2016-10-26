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

$id = null;
if(isset($_GET['transaction_id'])){
	$id = trim($_GET['transaction_id']);
	$is_transaction_id = true;
}elseif(isset($_GET['out_trade_no'])){
	$id = trim($_GET['out_trade_no']);
	$is_transaction_id = false;
}else{
	exit('illegal operation');
}

require_once '../class/WeChatPay.class.php';
$api = new WeChatPay;

$reply = $api->queryOrder($id, $is_transaction_id);

if(!$reply || $reply['return_code'] != 'SUCCESS'){
	showmsg('failed_to_query_order_due_to_network_error');
}

if($reply && isset($reply['transaction_id']) && isset($reply['trade_state']) && isset($reply['out_trade_no'])){
	runhooks('wechatpay_callback_executed', array($reply));
}else{
	showmsg('order_has_not_been_paid');
}
