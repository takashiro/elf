<?php

/***********************************************************************
Orchard Hut Online Shop
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

class SmsMainModule extends AdminControlPanelModule{

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		if($_POST){
			@$config = array(
				'account' => $_POST['account'],
				'pwmd5' => $_POST['pwmd5'],
			);

			writedata('sms', $config);
			showmsg('successfully_updated_sms_config', 'refresh');
		}

		$config = readdata('sms');
		include view('config');
	}

	public function testAction(){
		if($_POST){
			$template = intval($_POST['template']);
			$mobile = trim($_POST['mobile']);
			$content = trim($_POST['content']);

			$sms = new SMS;
			$result = $sms->send($template, $mobile, $content);
			showmsg($result, 'back');
		}else{
			showmsg('illegal operation');
		}
	}

}
