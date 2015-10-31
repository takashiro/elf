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

if(!defined('S_ROOT')) exit('access denied');

$wxsns = readdata('wxsns');

if(empty($_GET['action'])){
	$parameters = array(
		'appid' => $wxsns['app_id'],
		'redirect_uri' => 'http://weifruit.cn/?mod=weixin:qrconnect&action=login',
		'response_type' => 'code',
		'scope' => 'snsapi_login',
	);

	$url = 'https://open.weixin.qq.com/connect/qrconnect?'.http_build_query($parameters).'#wechat_redirect';
	redirect($url);

}elseif($_GET['action'] == 'login'){
	if(empty($_GET['code']))
		exit('Parameter code is required.');

	$api = new WeixinSNS($wxsns['app_id'], $wxsns['app_secret']);

	$result = $api->getAccessToken($_GET['code']);
	if($api->hasError())
		exit($api->getErrorMessage());

	if(empty($result['unionid'])){
		showmsg('failed_to_login_for_no_unionid');
	}

	$user = new User;
	$user->fetch('*', array('wxunionid' => $result['unionid']));
	if($user->id <= 0){
		$user->account = null;
		$user->pwmd5 = '';
		$user->wxopenid = null;
		$user->regtime = TIMESTAMP;
		$user->logintime = TIMESTAMP;
		$user->wxunionid = $result['unionid'];
		$user->nickname = lang('message', 'wxuser');

		//@to-do: remove this
		if(!empty($_COOKIE['referrerid'])){
			$referrerid = intval($_COOKIE['referrerid']);
			rsetcookie('referrerid');
		}elseif(!empty($_GET['referrerid'])){
			$referrerid = intval($_GET['referrerid']);
		}else{
			$referrerid = 0;
		}

		if($referrerid > 0 && User::Exist($referrerid)){
			$user->referrerid = $referrerid;
		}

		$user->insert('IGNORE');
		if($db->affected_rows <= 0){
			$user = new User;
			$user->fetch('*', array('wxunionid' => $result['unionid']));
		}
	}

	$user->force_login();
	showmsg('successfully_logged_in', 'index.php');
}

showmsg('illegal_operation');
