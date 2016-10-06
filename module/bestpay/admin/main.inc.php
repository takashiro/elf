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

class BestpayMainModule extends AdminControlPanelModule{

	public function __construct(){
		$this->display_order = 24;
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$fields = array(
			'key',
			'merchantid',
		);

		if($_POST){
			$bestpay = array();
			foreach($fields as $field){
				$bestpay[$field] = isset($_POST['bestpay']['key']) ? trim($_POST['bestpay']['key']) : '';
			}

			writedata('bestpay', $bestpay);
			showmsg('edit_succeed', 'refresh');
		}

		$bestpay = readdata('bestpay');

		include view('config');
	}

}
