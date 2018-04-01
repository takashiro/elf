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

define('S_ROOT', dirname(dirname(__FILE__)).'/');
define('IN_ADMINCP', true);
//error_reporting(0);//Debug

if(PHP_VERSION < '7.0'){
	exit('Elf Web App requires PHP 7.0 or later.');
}

spl_autoload_register(function($classname){
	require_once S_ROOT.'./class/'.$classname.'.class.php';
});

function writeconfig($config, $value){
	file_put_contents(S_ROOT.'./data/'.$config.'.inc.php', '<?php return '.var_export($value, true).';?>');
}

require_once S_ROOT.'./core/global.func.php';

$_G = array();
@$_G['config'] = array_merge(@include S_ROOT.'./data/config.inc.php', @include S_ROOT.'./data/stconfig.inc.php');
@$_G['config']['db'] = include S_ROOT.'./data/dbconfig.inc.php';
$_CONFIG = &$_G['config'];
isset($_CONFIG['timezone']) || $_CONFIG['timezone'] = 8;
empty($_CONFIG['db']['host']) && $_CONFIG['db']['host'] = 'localhost';
foreach(array('user', 'pw', 'name') as $var)
	empty($_CONFIG['db'][$var]) && $_CONFIG['db'][$var] = '';
empty($_CONFIG['db']['tpre']) && $_CONFIG['db']['tpre'] = 'pre_';

define('TIMEZONE', $_CONFIG['timezone']);
$_G['style'] = 'admin';
$_G['timestamp'] = time();
define('TIMESTAMP', $_G['timestamp']);

if($_POST){
	if(file_exists(S_ROOT.'./data/install.lock'))
		exit('Elf Web App has been installed. ./data/install.lock must be removed before you reinstall the system.');

	if(empty($_POST['db']['name']))
		exit('Please fill in database name.');

	$config = array(
		'timezone' => intval($_POST['config']['timezone']),
		'timefix' => 0,
		'cookiepre' => randomstr(3).'_',
		'charset' => 'utf-8',
		'sitename' => $_POST['config']['sitename'],
		'style' => 'default',
		'refversion' => randomstr(3),
	);

	$stconfig = array(
		'salt' => randomstr(32),
	);

	$dbconfig = array(
		'type' => 'mysql',
		'charset' => 'utf8',
		'host' => $_POST['db']['host'],
		'user' => $_POST['db']['user'],
		'tpre' => $_POST['db']['tpre'],
		'pw' => $_POST['db']['pw'],
		'name' => $_POST['db']['name'],
		'pconnect' => intval($_POST['db']['pconnect']),
	);

	$_G['config'] = array_merge($config, $stconfig);
	$_G['config']['db'] = $dbconfig;

	$db = new Database($dbconfig['host'], $dbconfig['user'], $dbconfig['pw']);
	$db->set_table_prefix($dbconfig['tpre']);

	$db->query('CREATE DATABASE IF NOT EXISTS `'.$dbconfig['name'].'` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
	$databases = $db->fetch_all('SHOW DATABASES');
	$database_exists = false;
	foreach($databases as $d){
		if($d['Database'] == $dbconfig['name']){
			$database_exists = true;
			break;
		}
	}
	if(!$database_exists){
		exit('Database '.$dbconfig['name'].' does not exist and can not be created without proper permission.');
	}
	$db->select_db($dbconfig['name']);

	$db->query('SET NAMES '.$dbconfig['charset']);


	$sql_files = array('install.sql');
	$module_list = loadmodule();
	foreach($module_list as $module){
		$file = $module['root_path'].'install.sql';
		file_exists($file) && $sql_files[] = $file;
	}
	unset($module_dirs);

	foreach($sql_files as $sql_file){
		$line = file($sql_file);
		$line_max = count($line);

		//Clear utf-8 BOM
		if(ord($line[0]{0}) == 0xEF && ord($line[0]{1}) == 0xBB && ord($line[0]{2}) == 0xBF){
			$line[0] = substr($line[0], 3);
		}

		$in_sql = false;
		$sql = '';
		for($i = 0; $i < $line_max; $i++){
			if((!$in_sql && substr_compare($line[$i], '--', 0, 2) == 0)){
				continue;
			}

			$line[$i] = trim($line[$i]);
			if(empty($line[$i])){
				continue;
			}

			$in_sql = true;
			$sql.= $line[$i];

			if(substr_compare($line[$i], ';', -1) === 0){
				$in_sql = false;
				if($dbconfig['tpre'] != 'pre_'){
					$sql = preg_replace('/`pre\_(.*?)`/is', "`{$dbconfig['tpre']}\\1`", $sql);
				}
				$db->query($sql);
				$sql = '';
			}
		}
	}

	Administrator::Register($_POST['admin']);
	$admin = new Administrator;
	$admin->login($_POST['admin']['account'], $_POST['admin']['password']);
	$admin->permissions = 'all';
	unset($admin);

	//保存配置
	writeconfig('config', $config);
	writeconfig('config_bak_'.randomstr(3), $config);
	writeconfig('stconfig', $stconfig);
	writeconfig('stconfig_bak_'.randomstr(3), $stconfig);
	writeconfig('dbconfig', $dbconfig);
	writeconfig('dbconfig_bak_'.randomstr(3), $dbconfig);

	//安装标记
	touch(S_ROOT.'./data/install.lock');

	exit('Elf Web App is successfully installed.');
}

$php_extension = array(
	'mysqli' => class_exists('mysqli'),
	'cURL' => function_exists('curl_init'),
	'openssl' => function_exists('openssl_get_privatekey'),
	'DOM' => class_exists('DOMDocument'),
	'mcrypt' => function_exists('mcrypt_generic_init'),
	'EXIF' => function_exists('exif_imagetype'),
);

$writable_directories = array(
	'./data/',
	'./data/attachment/',
	'./data/cache/',
	'./data/error/',
	'./data/js/',
	'./data/log/',
	'./data/template/',
);

$directory_results = array();
foreach($writable_directories as $dir){
	$directory_results[] = array(
		'path' => $dir,
		'is_writable' => is_writable(S_ROOT.$dir),
	);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Elf 安装程序</title>
<link href="common.css" type="text/css" rel="stylesheet" />
</head>

<body>

<div class="container">

	<header>
		<h2>Elf 安装程序</h2>
	</header>

	<div class="content">
		<h3>目录权限检查</h3>
		<table>
		<?php foreach($directory_results as $dir){ ?>
			<tr>
				<th><?php echo $dir['path'];?>：</th>
				<td><?php if($dir['is_writable']){ ?>可写<?php }else{ ?>不可写<?php }?></td>
			</tr>
		<?php } ?>
		</table>

		<h3>PHP扩展</h3>
		<table>
		<?php foreach($php_extension as $name => $installed){ ?>
			<tr>
				<th><?php echo $name;?>：</th>
				<td><?php if($installed){ ?>已<?php }else{ ?>未<?php }?>安装</td>
			</tr>
		<?php } ?>
		</table>

	<?php if(file_exists(S_ROOT.'./data/install.lock')){ ?>
		<p>Elf Web App has been installed. ./data/install.lock must be removed before you reinstall the system.</p>
	<?php }else{ ?>
		<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post">
		<h3>数据库配置</h3>
		<table>
			<tr>
				<th><label>数据库类型：</label></th>
				<td>MySQL / MariaDB</td>
			</tr>
			<tr>
				<th><label>数据库字符集：</label></th>
				<td>UTF-8</td>
			</tr>
			<tr>
				<th><label>数据库服务器地址：</label></th>
				<td><input type="text" id="db[host]" name="db[host]" value="<?php echo $_G['config']['db']['host']?>" /></td>
			</tr>
			<tr>
				<th><label>数据库账号：</label></th>
				<td><input type="text" id="db[user]" name="db[user]" value="<?php echo $_G['config']['db']['user']?>" /></td>
			</tr>
			<tr>
				<th><label>数据库密码：</label></th>
				<td><input type="text" id="db[pw]" name="db[pw]" value="<?php echo $_G['config']['db']['pw']?>" /></td>
			</tr>
			<tr>
				<th><label>数据库表前缀：</label></th>
				<td><input type="text" id="db[tpre]" name="db[tpre]" value="<?php echo $_G['config']['db']['tpre']?>" /></td>
			</tr>
			<tr>
				<th><label>数据库名：</label></th>
				<td><input type="text" id="db[name]" name="db[name]" value="<?php echo $_G['config']['db']['name']?>" /></td>
			</tr>
			<tr>
				<th><label>是否持续链接：</label></th>
				<td><input type="radio" id="db[pconnect]" name="db[pconnect]" value="0" checked="checked" />否 <input type="radio" id="db[pconnect]" name="db[pconnect]" value="0" />是</td>
			</tr>
		</table>

		<h3>系统设置</h3>
		<table>
			<tr>
				<th><label>站点名称：</label></th>
				<td><input type="text" id="config[sitename]" name="config[sitename]" /></td>
			</tr>
			<tr>
				<th><label>时区设置：</label></th>
				<td><input type="text" id="config[timezone]" name="config[timezone]" value="<?php echo $_G['config']['timezone']?>" /></td>
			</tr>
			<tr>
				<th><label>初始管理员账号：</label></th>
				<td><input type="text" id="admin[account]" name="admin[account]" /></td>
			</tr>
			<tr>
				<th><label>初始管理员密码：</label></th>
				<td><input type="text" id="admin[password]" name="admin[password]" /></td>
			</tr>
		</table>

		<button type="submit">开始安装</button>

		</form>
	<?php } ?>
	</div>

	<footer>
		<div class="copyright">
			<p><a href="###" target="_blank">Elf Web App</a>, Powered By <a href="http://takashiro.me" target="_blank">Kazuichi Takashiro</a></p>
		</div>
	</footer>

</div>

</body>
</html>
