<?php
/**
 * 定时清除过期配件信息
 * */
set_time_limit(0);
define('INI_WEB', true);
require('../../lib/global.php');
require(LIB_PATH . 'time.fun.php');
$now_time = gmtime();
$sql = "DELETE FROM eload_group_goods WHERE end_date<=" . $now_time ." AND end_date>0";
$db->query($sql);
