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

if(!defined('IN_ADMINCP')) exit('access denied');

class PaymentMainModule extends AdminControlPanelModule{

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		if($_POST){
			$payment = array();
			foreach(Order::$PaymentMethod as $methodid => $name){
				$payment['enabled_method'][$methodid] = !empty($_POST['payment']['enabled_method'][$methodid]);
			}

			@$alipay = array(
				'partner' => trim($_POST['alipay']['partner']),
				'transport' => trim($_POST['alipay']['transport']),
				'private_key_path' => trim($_POST['alipay']['private_key_path']),
				'ali_public_key_path' => trim($_POST['alipay']['ali_public_key_path']),
			);

			@$bestpay = array(
				'key' => trim($_POST['bestpay']['key']),
				'merchantid' => trim($_POST['bestpay']['merchantid']),
			);

			writedata('payment', $payment);
			writedata('alipay', $alipay);
			writedata('bestpay', $bestpay);
			showmsg('edit_succeed', 'refresh');
		}

		$payment = readdata('payment');
		$alipay = readdata('alipay');
		$bestpay = readdata('bestpay');

		include view('config');
	}

}

?>
