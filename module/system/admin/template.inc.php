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

class SystemTemplateModule extends AdminControlPanelModule {

	public function getRequiredPermissions() {
		return array('system');
	}

	public function defaultAction() {
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$config = readdata('tplconfig');
		$config['static_mod_url'] = !empty($config['static_mod_url']);
		isset($config['static_url']) || $config['static_url'] = '';
		isset($config['static_version']) || $config['static_version'] = '';
		isset($config['jquery_cdn']) || $config['jquery_cdn'] = '';

		if ($_POST) {
			foreach($config as $var => $oldvalue){
				if(isset($_POST[$var])){
					$config[$var] = trim($_POST[$var]);
				}
			}

			$config['static_mod_url'] = !empty($config['static_mod_url']);
			writedata('tplconfig', $config);

			showmsg('successfully_updated_system_config', 'back');
		}

		include view('template');
	}

}
