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

class Wallet{
	const RechargeLog = 0;
	const OrderRefundLog = 1;
	const OrderPaymentLog = 2;
	const TransferLog = 3;
	const OrderRewardLog = 4;
	const AdminModLog = 5;

	static public $LogType = array();

	//Payment Method
	public static $PaymentMethod;
	public static $PaymentInterface;
	const ViaCash = 0;
	const ViaAlipay = 1;
	const ViaWallet = 2;
	const ViaBestpay = 3;
	const ViaWeChat = 4;

	//Price Unit
	//@to-do: configuration
	public static $PriceUnit = '元';

	//Trade State
	public static $TradeState;
	public static $TradeStateEnum;
	const WaitBuyerPay = 1;		//交易创建，等待买家付款。
	const TradeClosed = 2;		//在指定时间段内未支付时关闭的交易；在交易完成全额退款成功时关闭的交易。
	const TradeSuccess = 3;		//交易成功，且可对该交易做操作，如：多级分润、退款等。
	const TradePending = 4;		//等待卖家收款（买家付款后，如果卖家账号被冻结）。
	const TradeFinished = 5;	//交易成功且结束，即不可再做任何操作

	private $user;

	public function __construct($user){
		$this->user = $user;
	}

	public function pay($order, $is_credit = false){
		global $db, $tpre;
		$extra = $is_credit ? '' : "AND wallet>={$order->totalprice}";
		$db->query("UPDATE {$tpre}user SET wallet=wallet-{$order->totalprice} WHERE id={$this->user->id} $extra");
		if($db->affected_rows > 0){
			$order->tradestate = Wallet::TradeSuccess;
			$order->paymentmethod = Wallet::ViaWallet;
			$order->tradetime = TIMESTAMP;
			$log = array(
				'uid' => $this->user->id,
				'type' => self::OrderPaymentLog,
				'dateline' => TIMESTAMP,
				'delta' => -$order->totalprice,
				'orderid' => $order->id,
			);
			$table = $db->select_table('userwalletlog');
			$table->insert($log);

			global $_G;
			$_G['user']->lastpaymentmethod = Wallet::ViaAlipay;

			return true;
		}
		return false;
	}

	static public function ReadConfig(){
		$config = readdata('payment');
		if(!empty($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false){
			foreach($config['method'] as &$method){
				if($method['id'] == self::ViaAlipay){
					$method['enabled'] = false;
				}
			}
			unset($method);
			$config['enabled_method'][self::ViaAlipay] = false;
		}

		global $_G;
		if($_G['user']->isLoggedIn() && $_G['user']->lastpaymentmethod !== null){
			foreach($config['method'] as $i => $method){
				if($method['id'] == $_G['user']->lastpaymentmethod){
					array_splice($config['method'], $i, 1);
					array_unshift($config['method'], $method);
					break;
				}
			}
		}

		return $config;
	}

	const TRADE_PREFIX = 'W';

	static public function __on_trade_started($method){
		if(isset($_GET['recharge'])){
			global $_G, $db;

			$log = array(
				'uid' => $_G['user']->id,
				'dateline' => TIMESTAMP,
				'cost' => floatval($_GET['recharge']),
				'type' => self::RechargeLog,
				'paymentmethod' => $method,
			);

			$log['cost'] = round($log['cost'] * 100) / 100;

			if($log['cost'] > 0){
				$table = $db->select_table('userwalletlog');
				$table->insert($log);
				$id = $table->insert_id();

				$trade = &$_G['trade'];
				$trade['out_trade_no'] = self::TRADE_PREFIX.$id;
				$trade['subject'] = $_G['config']['sitename'].$trade['out_trade_no'];
				$trade['total_fee'] = $log['cost'];
			}else{
				showmsg('the_number_you_must_be_kidding_me', 'back');
			}
		}elseif(isset($_GET['rechargeid'])){
			global $_G, $db;

			$logid = intval($_GET['rechargeid']);
			$table = $db->select_table('userwalletlog');
			$log = $table->fetch_first('*', array('id' => $logid, 'type' => self::RechargeLog));

			if(!empty($log['id']) && isset($log['cost']) && $log['cost'] > 0){
				$trade = &$_G['trade'];
				$trade['out_trade_no'] = self::TRADE_PREFIX.$log['id'];
				$trade['subject'] = $_G['config']['sitename'].$trade['out_trade_no'];
				$trade['total_fee'] = $log['cost'];
			}else{
				showmsg('illegal_operation');
			}
		}
	}

	static public function __on_trade_notified($id, $method, $trade_id, $trade_status, $extra){
		if(strncmp($id, self::TRADE_PREFIX, 1) != 0){
			return;
		}

		global $db;
		$id = intval(substr($id, 1));

		$log = array(
			'paymentmethod' => $method,
			'tradeid' => $trade_id,
			'tradestate' => $trade_status,
		);
		$table = $db->select_table('userwalletlog');
		$table->update($log, array('id' => $id));

		if($log['tradestate'] == Wallet::TradeSuccess || $log['tradestate'] == Wallet::TradeFinished){
			self::TakeRechargeEffect($id);
		}
	}

	static public function __on_trade_callback_executed($id, $method, $trade_id, $trade_status, $extra){
		if(strncmp($id, self::TRADE_PREFIX, 1) != 0){
			return;
		}

		self::__on_trade_notified($id, $method, $trade_id, $trade_status, $extra);
		showmsg('wallet_is_successfully_recharged', 'index.php?mod=payment');
	}

	static protected function TakeRechargeEffect($id){
		global $db, $tpre;
		$db->query("UPDATE {$tpre}userwalletlog SET recharged=1 WHERE id='$id'");
		if($db->affected_rows > 0){
			$log = $db->fetch_first("SELECT uid,cost FROM {$tpre}userwalletlog WHERE id='$id'");
			$fee = $log['cost'];
			$timestamp = TIMESTAMP;
			$extrafee = $db->result_first("SELECT reward
				FROM {$tpre}prepaidreward
				WHERE minamount<=$fee AND maxamount>=$fee
					AND etime_start<=$timestamp AND etime_end>=$timestamp
				ORDER BY reward DESC
				LIMIT 1");
			$fee += $extrafee;

			$db->query("UPDATE {$tpre}userwalletlog SET delta=$fee WHERE id='$id'");
			$db->query("UPDATE {$tpre}user SET wallet=wallet+$fee WHERE id={$log['uid']}");

			runhooks('user_wallet_changed', array($log['uid'], $log['cost']));
		}
	}

	static public function __on_order_canceled($order){
		if(($order->tradestate == Wallet::TradeSuccess || $order->tradestate == Wallet::TradeFinished) && $order->paymentmethod != Wallet::ViaCash){
			global $db, $tpre;
			$db->query("UPDATE {$tpre}user SET wallet=wallet+{$order->totalprice} WHERE id={$order->userid}");
			if ($db->affected_rows > 0){
				$log = array(
					'uid' => $order->userid,
					'dateline' => TIMESTAMP,
					'type' => self::OrderRefundLog,
					'delta' => $order->totalprice,
					'orderid' => $order->id,
				);
				$table = $db->select_table('userwalletlog');
				$table->insert($log);
			}
		}
	}
}

Wallet::$LogType = array(
	Wallet::RechargeLog => lang('common', 'recharge'),
	Wallet::OrderRefundLog => lang('common', 'refund'),
	Wallet::OrderPaymentLog => lang('common', 'payment'),
	Wallet::TransferLog => lang('common', 'transfer'),
	Wallet::OrderRewardLog => lang('common', 'reward'),
	Wallet::AdminModLog => lang('common', 'system'),
);


Wallet::$PaymentMethod = array(
	Wallet::ViaCash => lang('common', 'wallet_via_cash'),
	Wallet::ViaAlipay => lang('common', 'wallet_via_alipay'),
	Wallet::ViaWallet => lang('common', 'wallet_via_wallet'),
	Wallet::ViaBestpay => lang('common', 'wallet_via_bestpay'),
	Wallet::ViaWeChat => lang('common', 'wallet_via_wechat'),
);

Wallet::$PaymentInterface = array(
	Wallet::ViaCash => '',
	Wallet::ViaAlipay => 'alipay',
	Wallet::ViaWallet => 'payment',
	Wallet::ViaBestpay => 'bestpay',
	Wallet::ViaWeChat => 'weixin:pay',
);

Wallet::$TradeState = array(
	Wallet::WaitBuyerPay => lang('common', 'wallet_waitbuyerpay'),
	Wallet::TradeSuccess => lang('common', 'wallet_tradesuccess'),
	Wallet::TradeClosed => lang('common', 'wallet_tradeclosed'),
	Wallet::TradePending => lang('common', 'wallet_tradepending'),
	Wallet::TradeFinished => lang('common', 'wallet_tradefinished'),
);
