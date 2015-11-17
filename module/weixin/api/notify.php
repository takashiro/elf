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

$input = file_get_contents('php://input');
if(empty($input))
	exit('access denied');

$xml = new XML;
$xml->loadXML($input);
$xml = $xml->toArray();
if(empty($xml['xml']))
	exit('invalid input');
$input = $xml['xml'];

require_once '../class/WeChatPay.class.php';
$api = new WeChatPay;

if(!$api->checkSource($input))
	exit('invalid source');

if(!$api->checkSignature($input))
	exit('invalid signature');

runhooks('wechatpay_notified', array($input));

echo '<xml><return_code>SUCCESS</return_code><return_msg>OK</return_msg></xml>';
