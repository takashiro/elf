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

require_once '../../../core/init.inc.php';

$wx = readdata('wxconnect');
$weixin = new WeixinServer($wx['app_id'], $wx['token'], $wx['account']);
$weixin->setEncodingMode($wx['encoding_mode']);
$weixin->setAesKey($wx['aes_key']);

$request = $weixin->getRequest();
if(!$request){
	if($weixin->isValidRequest() && isset($_GET['echostr'])){
		exit($_GET['echostr']);
	}
	exit('access denied');
}

$targetKeyword = '';
if($request['MsgType'] == 'event'){
	if($request['Event'] == 'subscribe'){
		$weixin->replyTextMessage($wx['subscribe_text']);
	}elseif($request['Event'] == 'CLICK'){
		$targetKeyword = $request['EventKey'];
	}elseif($request['Event'] == 'VIEW'){
		if(strpos($request['EventKey'], 'weixin:connect') !== false){
			$api = new WeixinAPI;
			$info = $api->getUserInfo($request['FromUserName']);
			if(isset($info['unionid'])){
				$user = new User;
				$user->fetch('*', array('wxunionid' => $info['unionid']));
				if(empty($user->wxopenid)){
					isset($info['openid']) && $user->wxopenid = $info['openid'];
					isset($info['nickname']) && $user->nickname = $info['nickname'];
				}
			}
		}
	}elseif(strncmp($request['Event'], 'scancode_', 9) == 0){
		$result = $request['ScanCodeInfo']['ScanResult'];
		if(strncmp($result, $_G['site_url'], strlen($_G['site_url'])) == 0){
			if(strpos($result, 'referrerid=') !== false){
				$referrerid = explode('referrerid=', $result);
				$referrerid = $referrerid[1];
				$referrerid = explode('&', $referrerid);
				$referrerid = intval($referrerid[0]);

				$referrer = new User;
				$referrer->fetch('id,nickname,regtime', array('id' => $referrerid));
				if($referrer->exists()){
					$user = new User;
					$user->fetch('id,referrerid,regtime', array('wxopenid' => $request['FromUserName']));

					if($user->exists()){
						if($user->id == $referrerid){
							$user = $request['FromUserName'];
							$key = Authkey::Generate($user);
							$weixin->replyTextMessage("<a href=\"{$_G['site_url']}?mod=weixin&action=login&user=$user&key=$key\">".lang('message', 'click_and_enter_site').'</a>');
						}elseif($user->referrerid > 0){
							$referrer = new User;
							$referrer->fetch('id,nickname', array('id' => $user->referrerid));
							$weixin->replyTextMessage(lang('message', 'your_referrer_is').$referrer->nickname);
						}else{
							if($referrer->regtime < $user->regtime){
								$user->referrerid = $referrerid;
								$weixin->replyTextMessage(lang('message', 'your_referrer_is').$referrer->nickname);
							}else{
								$weixin->replyTextMessage(lang('message', 'you_registered_earlier_than_the_referrer'));
							}
						}
					}else{
						$user = $request['FromUserName'];
						$key = Authkey::Generate($user);
						$weixin->replyTextMessage("<a href=\"{$_G['site_url']}?mod=weixin&action=login&referrerid=$referrerid&user=$user&key=$key\">".lang('message', 'click_and_enter_site').'</a>');
					}
				}else{
					$weixin->replyTextMessage(lang('message', 'invalid_qrcode'));
				}
			}
		}else{
			$weixin->replyTextMessage($result);
		}
	}
}elseif($request['MsgType'] == 'text'){
	$targetKeyword = $request['Content'];
}

if(!empty($targetKeyword)){
	if(isset($wx['bind_keyword']) && Autoreply::MatchKeywords($wx['bind_keyword'], $targetKeyword)){
		if(User::Exist($request['FromUserName'], 'wxopenid'))
			$weixin->replyTextMessage(lang('message', 'your_account_has_been_binded_please_login'));

		$user = $request['FromUserName'];
		$key = Authkey::Generate($user);
		$weixin->replyTextMessage("<a href=\"{$_G['site_url']}?mod=weixin&action=bind&user=$user&key=$key\">".lang('message', 'click_and_bind').'</a>');
	}

	if(isset($wx['entershop_keyword']) && Autoreply::MatchKeywords($wx['entershop_keyword'], $targetKeyword)){
		$user = $request['FromUserName'];
		$key = Authkey::Generate($user);
		$weixin->replyTextMessage("<a href=\"{$_G['site_url']}?mod=weixin&action=login&user=$user&key=$key\">".lang('message', 'click_and_enter_site').'</a>');
	}

	$reply = Autoreply::Find($targetKeyword);
	if($reply != NULL){
		$weixin->replyTextMessage($reply);
	}

	if($targetKeyword == 'openid'){
		$weixin->replyTextMessage($request['FromUserName']);
	}
}
