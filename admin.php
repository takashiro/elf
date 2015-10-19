<?php

/***********************************************************************
Elf Web App Framework
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

define('IN_ADMINCP', true);
require_once './core/init.inc.php';

//Administrator automatically logs in via cookie records
$_G['admin'] = new Administrator;
$_G['admin']->login();

//Handle user login or logout requests
if(!$_G['admin']->isLoggedIn()){
	if($_POST && !empty($_POST['account']) && !empty($_POST['password'])){
		$result = $_G['admin']->login($_POST['account'], $_POST['password']);

		if($result){
			redirect('admin.php');
		}else{
			showmsg('invalid_account_or_password', 'back');
		}
	}

	include view('login');
	exit;
}

$_ADMIN = $_G['admin']->toReadable();

//Include the requested module
class AdminControlPanelModule{

	public function getAlias(){
		return '';
	}

	public function getPermissions(){
		return array();
	}

	public function getRequiredPermissions(){
		return array();
	}

	public function defaultAction(){
		exit('invalid action');
	}
}

$mod = isset($_GET['mod']) ? trim($_GET['mod']) : 'home';
$module_path = submodule('admin', $mod);
$mod_url = 'admin.php?mod='.$mod;
if(!file_exists($module_path)){
	$mods = explode(':', $mod);
	if(preg_match('/^\w+$/', $mod[0])){
		if(empty($mods[1]) || !preg_match('/^\w+$/', $mods[1])){
			$mods[1] = 'main';
		}
		$module_path = 'module/'.$mods[0].'/admin/'.$mods[1].'.inc.php';
	}

	if(file_exists($module_path)){
		define('MOD_NAME', $mods[0]);
		define('MOD_ROOT', S_ROOT.'module/'.$mods[0].'/');
	}else{
		$mod = 'home';
		$mod_url = 'admin.php?mod=home';
		$module_path = submodule('admin', 'home');
	}
	unset($mods);
}

if(!$_G['admin']->hasPermission($mod)){
	showmsg('no_permission', 'back');
}
require_once $module_path;

$classname = $mod.'Module';
if(!class_exists($classname, false)){
	exit('invalid module');
}
$module = new $classname;

$module_list = modulelist();
$action = isset($_REQUEST['action']) ? $_REQUEST['action'].'Action' : 'defaultAction';
if(method_exists($module, $action)){
	$module->$action();
}else{
	$module->defaultAction();
}

?>
