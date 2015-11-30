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

			$payment['enabled_method'] = array();

			foreach(Wallet::$PaymentMethod as $methodid => $name){
				if(isset($_POST['payment']['method'][$methodid])){
					$input = $_POST['payment']['method'][$methodid];
					$payment['method'][$methodid] = array(
						'id' => $methodid,
						'enabled' => !empty($input['enabled']),
						'recommended' => !empty($input['recommended']),
						'displayorder' => !isset($input['displayorder']) ? 0 : intval($input['displayorder']),
					);
					$payment['enabled_method'][$methodid] = !empty($input['enabled']);
				}else{
					$payment['method'][$methodid] = array(
						'id' => $methodid,
						'enabled' => false,
						'recommended' => false,
						'displayorder' => 0,
					);
					$payment['enabled_method'][$methodid] = false;
				}
			}
			usort($payment['method'], function($m1, $m2){
				return $m1['displayorder'] > $m2['displayorder'];
			});

			@$alipay = array(
				'partner' => trim($_POST['alipay']['partner']),
				'transport' => trim($_POST['alipay']['transport']),
				'private_key_path' => trim($_POST['alipay']['private_key_path']),
				'ali_public_key_path' => trim($_POST['alipay']['ali_public_key_path']),
				'enable_single_trade_query' => !empty($_POST['alipay']['enable_single_trade_query']),
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

		foreach(Wallet::$PaymentMethod as $methodid => $name){
			if(isset($payment['method'][$methodid]))
				continue;

			$payment['method'][$methodid] = array(
				'id' => $methodid,
				'enabled' => false,
				'recommended' => false,
				'displayorder' => 0,
			);
		}

		include view('config');
	}

}

?>
