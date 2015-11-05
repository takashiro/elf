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

$in_wechat = false;
if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false)
	$in_wechat = true;
elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'GT-I9500') !== false && strpos($_SERVER['HTTP_USER_AGENT'], 'MQQBrowser') !== false)
	$in_wechat = true;
$in_wechat = true;


if($in_wechat){
	$config = readdata('wxsv');
}else{
	$config = readdata('wxsns');
}

if(empty($_GET['action'])){
	$parameters = array(
		'appid' => $config['app_id'],
		'redirect_uri' => $_G['root_url'].'index.php?mod=weixin:connect&action=login',
		'response_type' => 'code',
		'scope' => $in_wechat ? 'snsapi_base' : 'snsapi_login',
	);

	$url = 'https://open.weixin.qq.com/connect/'.($in_wechat ? 'oauth2/authorize' : 'qrconnect').'?'.http_build_query($parameters).'#wechat_redirect';
	redirect($url);

}elseif($_GET['action'] == 'login'){
	if(empty($_GET['code']))
		exit('Parameter code is required.');

	$api = new WeixinSNS($config['app_id'], $config['app_secret']);

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
	redirect('index.php');
}

showmsg('illegal_operation');
