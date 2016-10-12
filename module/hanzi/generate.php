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


require_once '../../core/init.inc.php';
require_once 'class/Hanzi.class.php';


//Administrator automatically logs in via cookie records
$_G['admin'] = new Administrator;
$_G['admin']->login();

//Handle user login or logout requests
if(!$_G['admin']->isLoggedIn() || !$_G['admin']->isSuperAdmin()){
	exit('access denied');
}

if(isset($_GET['table']) && isset($_GET['field']) && isset($_GET['key'])){
	$table = addslashes(trim($_GET['table']));
	$field = addslashes(trim($_GET['field']));
	$key = addslashes(trim($_GET['key']));
}else{
	exit('invalid input');
}

$query = $db->query("SELECT `$key`,`$field` FROM `{$tpre}{$table}`");
while($row = $query->fetch_array()){
	$id = addslashes($row[0]);
	$acronyms = Hanzi::ToAcronym($row[1]);

	$table = $db->select_table($table.'acronym');
	$table->delete("`$key`='$id' AND `$field` IS NOT NULL");
	foreach($acronyms as $acronym){
		$row = array(
			$key => $id,
			$field => $acronym,
		);
		$table->insert($row);
	}
}

exit('ok');
