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

$limit = 10;
$offset = ($page - 1) * $limit;
$table = $db->select_table('userwalletlog');
$pagenum = $table->result_first('COUNT(*)', "uid={$_USER['id']}");
$walletlog = $table->fetch_all('*', "uid={$_USER['id']} ORDER BY dateline DESC LIMIT $offset,$limit");

$prepaidreward = $db->fetch_all("SELECT * FROM {$tpre}prepaidreward WHERE etime_start<=$timestamp AND etime_end>=$timestamp");
foreach($prepaidreward as &$r){
	foreach(array('minamount', 'maxamount', 'reward') as $var)
		$r[$var] = floatval($r[$var]);
}
unset($r);

include view('log');
