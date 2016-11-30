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

class Template{
	const SOURCE_DIR = 'extension/view/';

	static protected $StatementNext = 6;

	protected $type;
	protected $style;
	protected $name;

	function __construct($type, $style, $name){
		$this->type = $type;
		$this->style = $style;
		$this->name = $name;
	}

	public function getLastModifiedTime(){
		$sourcePath = $this->getSourcePath();
		return file_exists($sourcePath) ? filemtime($sourcePath) : -1;
	}

	public function getType(){
		return $this->type;
	}

	public function getStyle(){
		return $this->name;
	}

	public function getName(){
		return $this->name;
	}

	public function getDirectoryPath(){
		return S_ROOT.self::SOURCE_DIR.$this->type.'/'.$this->style.'/';
	}

	public function getOriginalSourcePath(){
		$htmpath = $this->getDirectoryPath().$this->name.'.htm';
		if(!file_exists($htmpath))
			$htmpath = $this->getDirectoryPath().$this->name.'.php';
		return $htmpath;
	}

	public function getSourcePath(){
		if(defined('MOD_ROOT')){
			$htmpath = MOD_ROOT.'view/'.$this->name.'.htm';
			if(file_exists($htmpath))
				return $htmpath;
			$htmpath = MOD_ROOT.'view/'.$this->name.'.php';
			if(file_exists($htmpath))
				return $htmpath;
		}

		$htmpath = $this->getDirectoryPath().$this->name.'.htm';
		if(!file_exists($htmpath)){
			$htmpath = $this->getDirectoryPath().$this->name.'.php';

			if(!file_exists($htmpath)){
				$htmpath = S_ROOT.'view/'.$this->type.'/'.$this->name.'.htm';
				if($this->type != 'user' && !file_exists($htmpath)){
					$htmpath = S_ROOT.'view/user/'.$this->name.'.htm';
				}
			}
		}
		return $htmpath;
	}

	public function getTemplateRoot(){
		$url = $this->getStaticUrl();
		if($this->style == 'default'){
			return $url.'view/'.$this->type.'/';
		}else{
			return $url.'extension/view/'.$this->type.'/'.$this->style.'/';
		}
	}

	public function getModuleRoot(){
		if(defined('MOD_NAME')){
			$module_path = 'module/'.MOD_NAME.'/';
			file_exists($module_path) || $module_path = 'extension/'.$module_path;
			defined('IN_ADMINCP') && $module_path.= 'admin/';
			return $this->getStaticUrl().$module_path;
		}
		return '';
	}

	public function getStaticUrl(){
		global $_G;
		return $_G['config']['static_url'] ?? ($_G['root_url'] ?? '');
	}

	public function parse(){
		$source_path = $this->getSourcePath();
		$template = file_get_contents($source_path);
		if($template === false)
			exit("Current template file {$source_path} not found or have no access!");

		if(substr_compare($source_path, '.php', -4) == 0)
			return $template;

		//Remove duplicated spaces
		$template = preg_replace('/^\s+/s', "\n", $template);
		$template = preg_replace('/[\r\n]+\s+/s', "\n", $template);

		//Remove commented statements <!--{...}--> into {...}
		$template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);

		//{lang *type* *text*}
		$template = preg_replace_callback("/\{lang\s+([a-zA-Z0-9_]+?)\s+([a-zA-Z0-9_]+?)\}/is", function($matches){
			return lang($matches[1], $matches[2]);
		}, $template);

		$template = preg_replace("/\{lang\s+([a-zA-Z0-9_]+?)\s+(.+?)\}/is", '<?php echo lang(\'\\1\', \\2);?>', $template);

		//{template *template_name*}
		$parse_subtemplate = function($matches){
			return "\n".file_get_contents(view($matches[1]))."\n";
		};
		$template = preg_replace_callback("/[\n\r\t]*\{template\s+([a-z0-9_]+)\}[\n\r\t]*/is", $parse_subtemplate, $template);
		$template = preg_replace_callback("/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/i", $parse_subtemplate, $template);

		//{eval *expression*}
		$template = preg_replace("/[\n\r\t]*\{eval\s+(.+?)\}[\n\r\t]*/is", '<?php \\1 ?>', $template);

		//{echo *expression*}
		$template = preg_replace("/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/is", '<?php echo \\1;?>', $template);

		//{elseif}
		$template = preg_replace("/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/is", '\\1<?php } elseif(\\2) { ?>\\3', $template);

		//{else}
		$template = preg_replace("/([\n\r\t]*)\{else\}([\n\r\t]*)/is", '\\1<?php } else { ?>\\2', $template);

		//{loop $var $key $value}
		for($i = 0; $i < self::$StatementNext; $i++){
			$template = preg_replace(
				"/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r]*(.+?)[\n\r]*\{\/loop\}[\n\r\t]*/is",
				'<?php if(is_array(\\1)) { foreach(\\1 as \\2) { ?>\\3<?php } } ?>',
				$template
			);
			$template = preg_replace(
				"/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*(.+?)[\n\r\t]*\{\/loop\}[\n\r\t]*/is",
				'<?php if(is_array(\\1)) { foreach(\\1 as \\2 => \\3) { ?>\\4<?php } } ?>',
				$template
			);
			$template = preg_replace(
				"/([\n\r\t]*)\{if\s+(.+?)\}([\n\r]*)(.+?)([\n\r]*)\{\/if\}([\n\r\t]*)/is",
				'\\1<?php if(\\2) { ?>\\3\\4\\5<?php } ?>\\6',
				$template
			);
		}

		//Combine PHP blocks
		$template = preg_replace('/\?\>\s*\<\?(php)?/s', '', $template);

		//Variable
		$var_regexp = '\$[a-z_][a-z0-9_]*(?:\[[a-z0-9_\-."\'\[\]$]+\])*';
		$echovar = function($match){
			return '<?php echo '.str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $match)).';?>';
		};

		for($i = 0; $i < strlen($template); $i++){
			if($template{$i} == '<'){
				if($i + 1 < strlen($template) && (substr_compare($template, '?php', $i + 1, 4) == 0 || $template{$i + 1} == '?')){
					$i += 2;
					while($i < strlen($template) && substr_compare($template, '?>', $i, 2) != 0)
						$i++;
					$i++;
					if($i >= strlen($template))
						break;
				}
			}elseif(substr_compare($template, '{$', $i, 2) == 0){
				if(preg_match("/\{($var_regexp)\}/i", $template, $matches, PREG_OFFSET_CAPTURE, $i)){
					if($matches[0][1] == $i){
						$length = strlen($matches[0][0]);
						$new_str = $echovar($matches[1][0]);
						$template = substr_replace($template, $new_str, $i, $length);
						$i += strlen($new_str) - 1;
					}
				}
			}elseif($template{$i} == '$'){
				if(preg_match("/$var_regexp/i", $template, $matches, PREG_OFFSET_CAPTURE, $i)){
					if($matches[0][1] == $i){
						$length = strlen($matches[0][0]);
						$new_str = $echovar($matches[0][0]);
						$template = substr_replace($template, $new_str, $i, $length);
						$i += strlen($new_str) - 1;
					}
				}
			}
		}

		//Constants
		$const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";

		$th = $this;
		$template = preg_replace_callback("/\{$const_regexp\}/s", function($matches) use(&$th){
			$method = 'get'.str_replace('_', '', $matches[1]);
			if(method_exists($th, $method)){
				return $th->$method();
			}else{
				return '<?php echo '.$matches[1].';?>';
			}
		}, $template);

		$template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);

		$template = preg_replace_callback('/"(\w+\:\/\/)?[0-9a-z.]+\?[^"]+?"/i', function($matches){
			$str = str_replace('&', '&amp;', $matches[0]);
			$str = str_replace('&amp;amp;', '&amp;', $str);
			$str = str_replace('\"', '"', $str);
			return $str;
		}, $template);

		//Combine PHP blocks
		$template = preg_replace('/\?\>\s*\<\?php/s', '', $template);

		return $template;
	}

	/*
		The function generates a select element.
		$name is the name of the select element, as an identifier in its form.
		$option is an array whose values are the texts of each option and keys are the corresponding value submitted to server
		$selected is the value of the selected element. Its default value is NULL, not 0, for 0 equals(==) any string other than pure digits like "123"
	*/
	static public function select($name, $option, $selected = NULL){
		$html = '<select id="'.$name.'" name="'.$name.'">';
		if(!is_array($option)){
			$option = explode(',', $option);
		}
		foreach($option as $value => $text){
			$html.= '<option value="'.$value.'"'.($value == $selected ? ' selected="selected"' : '').'>'.$text.'</option>';
		}
		$html.= '</select>';

		return $html;
	}

	static public function radio($name, $option, $value = 0){
		$html = '';
		if(!is_array($option)){
			$option = explode(',', $option);
		}
		foreach($option as $key => $val){
			$html.= '<label><input type="radio" name="'.$name.'" value="'.$key.'"'.($value == $key ? ' checked="checked"' : '').' /><span class="checkbox">'.$val.'</span></label>';
		}

		return $html;
	}

	/*
		Function mpage (short for multiple page) generate HTML codes for multiple pages. It's a paging function.
		$totalnum is the total number of rows of data.
		$page is the current page number.
		$limit is the number of rows on each page.
		$url is the basic URL, that a new parameter "page" (via GET method), which represents the target page, will be appended to.
		@TO-DO: it does not support language packs, for chinese words are already written, as follows, inside the function.
	*/
	static public function mpage($totalnum, $page, $limit, $url = ''){
		if(!$url){
			$url = $_SERVER['SCRIPT_FILENAME'].($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '');
		}
		$delimeter = strpos($url, '?') !== false ? '&' : '?';

		$maxpage = ceil($totalnum / $limit);
		if($maxpage <= 1){
			return '';
		}

		$page = min($maxpage, max(1, intval($page)));

		$html = '<div class="mpage">';

		$html.= '<span class="total">'.$totalnum.'</span>';

		if($page > 1){
			if($page > 2){
				$html.= '<a class="first" href="'.$url.$delimeter.'page=1'.'">'."首页".'</a>';
			}
			$html.= '<a class="prev" href="'.$url.$delimeter.'page='.max(1,$page - 1).'">'."上一页".'</a>';
		}

		$faraway = min($maxpage, $page + 5 + max(0, 6 - $page));
		for($i = max(1, $page - 5 - max(0,$page + 5 - $maxpage)); $i <= $faraway; $i++){
			if($i == $page){
				$html.= '<a href="###" class="current">'.$i.'</a>';
			}else{
				$html.= '<a href="'.$url.$delimeter.'page='.$i.'">'.$i.'</a>';
			}
		}

		if($page < $maxpage){
			$html.= '<a class="next" href="'.$url.$delimeter.'page='.($page + 1).'">'."下一页".'</a>';
			if($page < $maxpage - 1){
				$html.= '<a class="last" href="'.$url.$delimeter.'page='.$maxpage.'">'."尾页".'</a>';
			}
		}

		$html.= '</div>';

		return $html;
	}

	static public function checkbox($name, $tips, $value = false){
		return '<input type="checkbox" id="'.$name.'" name="'.$name.'"'.($value ? ' checked="checked"' : '').' /><label for="'.$name.'">'.$tips.'</label>';
	}

	static public function split($str, $span, $param = '-'){
		$i = $span = intval($span);
		$length = strlen($str);

		while($i < $length){
			$str = substr($str, 0, $i).$param.substr($str, $i);
			$i += $span + 1;
			$length++;
		}

		return $str;
	}

	static public function StyleList($target = 'user'){
		$style_list = array('default');

		$styledir = S_ROOT.'extension/view/'.$target.'/';
		if(is_dir($styledir) && is_readable($styledir)){
			$view = opendir($styledir);
			while($style = readdir($view)){
				if($style{0} === '.'){
					continue;
				}

				if(is_dir($styledir.$style)){
					$style_list[$style] = $style;
				}
			}
			closedir($view);
		}

		return $style_list;
	}
}
