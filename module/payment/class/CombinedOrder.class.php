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

class CombinedOrder extends DBObject{

	const TABLE_NAME = 'combinedorder';

	private $items = array();

	public function __construct(int $id = 0){
		parent::__construct();
		if($id > 0){
			$this->fetch('*', 'id='.$id);
		}else{
			$this->price = 0;
		}
	}

	public function insert($extra = ''){
		$id = parent::insert($extra);
		global $db;
		$table = $db->select_table('combinedorderitem');
		foreach($this->items as $item){
			$table->insert(array(
				'orderid' => $id,
				'out_trade_no' => $item,
			));
		}
		return $id;
	}

	public function add($item, $price){
		$this->items[] = $item;
		$this->price += $price;
	}

	const TRADE_PREFIX = 'C';

	static public function __on_trade_started($method){
		if(isset($_GET['combinedorderid']) && $id = intval($_GET['combinedorderid'])){
			global $_G;
			$order = new CombinedOrder($id);
			if($order->exists() && $order->userid == $_G['user']->id){
				$trade = &$_G['trade'];
				$trade['out_trade_no'] = self::TRADE_PREFIX.$id;
				$trade['subject'] = $_G['config']['sitename'].$trade['out_trade_no'];
				$trade['total_fee'] = $order->price;
				$trade['show_url'] = 'index.php?mod=user';
			}else{
				showmsg('order_not_exist');
			}
		}
	}

	static public function __on_trade_notified($id, $method, $tradeid, $status, $extra){
		if(strncmp($id, self::TRADE_PREFIX, 1) != 0){
			return;
		}
		$id = intval(substr($id, 1));
		global $db, $tpre;
		$query = $db->query("SELECT out_trade_no FROM {$tpre}combinedorderitem WHERE orderid=$id");
		while($r = $query->fetch_row()){
			runhooks('trade_notified', array($r[0], $method, $tradeid, $status, $extra));
		}
	}

	static public function __on_trade_callback_executed($id, $method, $tradeid, $status, $extra){
		if(strncmp($id, self::TRADE_PREFIX, 1) != 0){
			return;
		}
		$id = intval(substr($id, 1));
		global $db, $tpre;
		$query = $db->query("SELECT out_trade_no FROM {$tpre}combinedorderitem WHERE orderid=$id");
		while($r = $query->fetch_row()){
			runhooks('trade_callback_executed', array($r[0], $method, $tradeid, $status, $extra));
		}
	}

}
