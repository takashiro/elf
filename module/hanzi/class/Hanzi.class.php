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

class Hanzi{

	static public function UpdateAcronym($table_name, $condition, $field, $value){
		global $db;
		$table = $db->select_table($table_name.'acronym');
		$table->delete($condition);

		foreach(self::ToAcronym($value) as $acronym){
			$row = $condition;
			$row[$field] = $acronym;
			$table->insert($row);
		}
	}

	static public function QueryAcronym($table_name, $field, $value, $fields = 'id'){
		global $tpre;
		$value = addslashes($value);
		return "SELECT $fields FROM {$tpre}{$table_name}acronym WHERE `$field` LIKE '$value%'";
	}

	static public function ToAcronym($str){
		$chars = self::utf8_str_split($str);
		$chars[] = null;

		$capitals = array();
		global $db;
		$table = $db->select_table('pinyin');
		$rows = $table->fetch_all('DISTINCT hanzi,capital', 'hanzi IN (\''.implode('\',\'', $chars).'\')');
		foreach($rows as $r){
			$capitals[$r['hanzi']][] = $r['capital'];
		}
		if(empty($capitals)){
			return array();
		}

		$results = array('');
		foreach($chars as $char){
			if(isset($capitals[$char])){
				$backup = $results;
				$results = array();
				foreach($capitals[$char] as $capital) {
					$cur = $backup;
					foreach($cur as &$result){
						$result.= $capital;
					}
					unset($result);
					$results = array_merge($results, $cur);
				}
			}elseif(preg_match('/^\w$/', $char)){
				foreach($results as &$result){
					$result.= strtolower($char);
				}
				unset($result);
			}
		}

		while(empty($results[0])){
			array_shift($results[0]);
		}

		return $results;
	}

	private static function utf8_str_split($str,$split_len = 1){
		if(!preg_match('/^[0-9]+$/', $split_len) || $split_len < 1){
			return FALSE;
		}
		$len = mb_strlen($str, 'UTF-8');
		if ($len <= $split_len){
			return array($str);
		}
		preg_match_all('/.{'.$split_len.'}|[^\x00]{1,'.$split_len.'}$/us', $str, $ar);
		return $ar[0];
	}
}
