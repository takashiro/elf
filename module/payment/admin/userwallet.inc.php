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

class PaymentUserWalletModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('payment');
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$query_string = array();
		$condition = array();

		if(isset($_GET['userid'])){
			$userid = intval($_GET['userid']);
			$condition[] = 'l.uid='.$userid;
			$query_string['userid'] = $userid;
		}

		if(!empty($_GET['tradeid'])){
			$tradeid = trim($_GET['tradeid']);
			$condition[] = 'l.tradeid=\''.addslashes($tradeid).'\'';
			$query_string['tradeid'] = $tradeid;
			unset($_GET['time_start'], $_GET['time_end']);
		}

		if(!empty($_GET['time_start'])){
			$time_start = rstrtotime($_GET['time_start']);
			$condition[] = "l.dateline>=$time_start";
		}else{
			$time_start = '';
		}
		$query_string['time_start'] = $time_start;

		if(!empty($_GET['time_end'])){
			$time_end = rstrtotime($_GET['time_end']);
			$condition[] = "l.dateline<=$time_end";
		}else{
			$time_end = '';
		}
		$query_string['time_end'] = $time_end;

		$condition = empty($condition) ? '1' : implode(' AND ', $condition);

		$limit = 20;
		$offset = ($page - 1) * $limit;
		$logs = $db->fetch_all("SELECT l.*, u.nickname
			FROM {$tpre}userwalletlog l
				LEFT JOIN {$tpre}user u ON u.id=l.uid
			WHERE $condition
			ORDER BY l.id DESC
			LIMIT $offset, $limit");

		$time_start && $time_start = rdate($time_start);
		$time_end && $time_end = rdate($time_end);

		$pagenum = $db->result_first("SELECT COUNT(*)
			FROM {$tpre}userwalletlog l
			WHERE $condition");

		$totalwallet = array(
			'amount' => $db->result_first("SELECT SUM(wallet) FROM {$tpre}user"),
			'gifted' => $db->result_first("SELECT SUM(delta-cost) FROM {$tpre}userwalletlog WHERE recharged=1"),
			'realcharged' => $db->result_first("SELECT SUM(delta) FROM {$tpre}userwalletlog WHERE recharged=1"),
		);

		include view('userwallet_log');
	}

	public function updateTradeStateAction(){
		if(empty($_GET['rechargeid']))
			exit('invalid recharge id');

		$rechargeid = intval($_GET['rechargeid']);
		if($rechargeid <= 0)
			exit('invalid recharge id');

		require module('alipay/config');

		$parameter = array(
			'service' => 'single_trade_query',
			'partner' => $alipay_config['partner'],
			'out_trade_no' => 'W'.$orderid,
			'_input_charset' => $alipay_config['input_charset'],
		);

		$alipaySubmit = new AlipaySubmit($alipay_config);
		$html_text = $alipaySubmit->buildRequestHttp($parameter);

		$doc = new XML;
		$doc->loadXML($html_text, 'alipay');
		$xml = $doc->toArray();
		if(isset($xml['is_success']) && $xml['is_success'] == 'T'){
			if(isset($xml['response']['trade'])){
				$trade = $xml['response']['trade'];

				$arguments = array(
					//商户订单号
					$trade['out_trade_no'],

					//支付宝交易号
					$trade['trade_no'],

					//交易状态
					$trade['trade_status'],
				);

				runhooks('alipay_notified', $arguments);
			}

			showmsg('successfully_updated_recharge_trade_state', 'refresh');
		}

		showmsg('order_not_exist_failed_to_update_recharge_trade_state', 'refresh');
	}

}

?>
