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

class AlipayMainModule extends AdminControlPanelModule{

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$fields = array(
			'partner',
			'transport',
			'notify_url',
			'private_key_path',
			'ali_public_key_path',
		);

		if($_POST){
			$alipay = array();
			foreach($fields as $field){
				$alipay[$field] = isset($_POST['alipay'][$field]) ? trim($_POST['alipay'][$field]) : '';
			}
			$alipay['enable_single_trade_query'] = !empty($_POST['alipay']['enable_single_trade_query']);

			writedata('alipay', $alipay);
			showmsg('edit_succeed', 'refresh');
		}

		$alipay = readdata('alipay');
		foreach($fields as $field)
			isset($alipay[$field]) || $alipay[$field] = '';

		include view('config');
	}

}
