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
if(isset($_SERVER['HTTP_USER_AGENT'])){
	if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false)
		$in_wechat = true;
	elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'GT-I9500') !== false && strpos($_SERVER['HTTP_USER_AGENT'], 'MQQBrowser') !== false)
		$in_wechat = true;
}
$is_client = !empty($_GET['is_client']);


if($in_wechat){
	$config = readdata('wxsv');
}elseif($is_client){
	$config = readdata('wxapp');
}else{
	$config = readdata('wxsns');
}

if(empty($_GET['code'])){
	$action = $_GET['action'] ?? 'login';
	$actions = array('login', 'updateavatar');
	$action = in_array($action, $actions) ? $action : $actions[0];
	if($action == 'login' && $_G['user']->isLoggedIn()){
		redirect($_GET['referrer'] ?? 'index.php');
	}

	$redirect_url = $_G['site_url'].'index.php?mod=weixin:connect&action='.$action;
	$referrer = $_GET['referrer'] ?? '';
	if($referrer){
		$redirect_url.= '&referrer='.$referrer;
	}

	$parameters = array(
		'appid' => $config['app_id'],
		'redirect_uri' => $redirect_url,
		'response_type' => 'code',
		'scope' => $in_wechat ? 'snsapi_userinfo' : 'snsapi_login',
	);

	$url = 'https://open.weixin.qq.com/connect/'.($in_wechat ? 'oauth2/authorize' : 'qrconnect').'?'.http_build_query($parameters).'#wechat_redirect';
	redirect($url);
}

$api = new WeixinSNS($config['app_id'], $config['app_secret']);

$result = $api->getAccessToken($_GET['code']);
if($api->hasError())
	exit($api->getErrorMessage());

function downloadfile($url, $localpath){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$avatar_binary = curl_exec($ch);
	curl_close($ch);

	$fp = fopen($localpath, 'wb');
	fwrite($fp, $avatar_binary);
	fclose($fp);
}

if($_GET['action'] == 'login'){
	if($_G['user']->isLoggedIn())
		redirect('index.php');

	if(empty($result['unionid'])){
		$result = $api->getUserInfo($result['access_token'], $result['openid']);
		if(empty($result['unionid']))
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
		$user->nickname = empty($result['nickname']) ? lang('message', 'wxuser') : $result['nickname'];
		if(!empty($result['headimgurl'])){
			$user->avatar = GdImage::JPG;
			downloadfile($result['headimgurl'], $user->getImage('avatar'));
		}

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

}elseif($_GET['action'] == 'updateavatar'){
	if(!$_G['user']->isLoggedIn()){
		redirect('index.php?mod=weixin:connect');
	}

	$result = $api->getUserInfo($result['access_token'], $result['openid']);
	if(empty($result['headimgurl']))
		showmsg('failed_to_fetch_avatar_url');

	if($_G['user']->avatar != GdImage::JPG){
		$_G['user']->removeImage('avatar');
		$_G['user']->avatar = GdImage::JPG;
	}
	downloadfile($result['headimgurl'], $_G['user']->getImage('avatar'));

	showmsg('successfully_updated_your_avatar', $_GET['referrer'] ?? 'index.php');
}

showmsg('illegal_operation');
