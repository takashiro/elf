<?php

require_once './core/init.inc.php';

$limit = 10;
$offset = ($page - 1) * $limit;
$db->select_table('userwalletlog');
$pagenum = $db->RESULTF('COUNT(*)');
$walletlog = $db->MFETCH('*', "1 ORDER BY dateline LIMIT $offset,$limit");

$prepaidreward = $db->fetch_all("SELECT * FROM {$tpre}prepaidreward WHERE etime_start<=$timestamp AND etime_end>=$timestamp");
foreach($prepaidreward as &$r){
	foreach(array('minamount', 'maxamount', 'reward') as $var)
		$r[$var] = floatval($r[$var]);
}
unset($r);

include view('wallet');

?>