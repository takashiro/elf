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
error_reporting(E_ALL);

$alipay = new Alipay;
if($data = $alipay->receiveNotification()){//验证成功
	$arguments = array(
		//商户订单号
		$data['out_trade_no'],

		//支付宝交易号
		$data['trade_no'],

		//交易状态
		$data['trade_status'],
	);

	runhooks('alipay_notified', $arguments);
	exit('success');

}else{
    //验证失败
	writelog('alipay_notify', json_encode($_POST));
    exit('fail');
}
