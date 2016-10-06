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

class SystemCpConfigModule extends AdminControlPanelModule{

	public function getRequiredPermissions(){
		return array('system');
	}

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$module_list = array();
		$cpconfig = readdata('cpconfig');
		isset($cpconfig['menu_order']) || $cpconfig['menu_order'] = array();
		$menu_order = &$cpconfig['menu_order'];

		if(!empty($_POST['menu_order']) && is_array($_POST['menu_order'])){
			foreach($_POST['menu_order'] as $module_name => $order){
				$menu_order[$module_name] = $order;
			}
			writedata('cpconfig', $cpconfig);
			showmsg('edit_succeed', 'refresh');
		}

		foreach($_G['module_list'] as $module){
			if($module['admin_modules']){
				if(isset($menu_order[$module['name']])){
					$module['displayorder'] = $menu_order[$module['name']];
				}else{
					$main_module = $module['root_path'].'admin/main.inc.php';
					if(file_exists($main_module)){
						require_once $main_module;
						$class_name = $module['name'].'MainModule';
						$main_module = new $class_name;
						$module['displayorder'] = $main_module->getDisplayOrder();
					}else{
						$module['displayorder'] = 0;
					}
				}
				$module_list[] = $module;
			}
		}

		usort($module_list, function($m1, $m2){
			return $m1['displayorder'] > $m2['displayorder'];
		});

		include view('cpconfig');
	}

}
