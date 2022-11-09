<?php
/**
 * 商品点击率压缩脚本（超过两个月的点击率按照商品ID合并成一个月一条数据）
 * */
set_time_limit(0);

define('INI_WEB', true);
$GLOBALS['_CFG']['timezone'] = 8;//time.fun.php用到

header('Pragma: public');
header('Cache-Control: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 86400) . ' GMT');

require('db_config.php');
require('class.mysql.php');
require('time.fun.php');

$jisuan_time = gmstr2time("-2 month");

$month_day_num = local_date("t",$jisuan_time);
$date_str = local_date("Y-m",$jisuan_time);
list($year , $month) = explode("-",$date_str);
$start_time = local_mktime(0,0,0,$month,1,$year);
$end_time = local_mktime(23,59,59,$month,$month_day_num,$year);
//echo '$start_time='.$start_time."<br>";
//echo '$end_time='.$end_time."<br>";
//echo local_date("Y-m-d H:i:s",$start_time)."<br>";
//echo local_date("Y-m-d H:i:s",$end_time);
$db = new MySql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$sql ="SELECT goods_id , daytime , sum(hitnum) AS hitnum_s FROM " . GOODS_HITS . " WHERE daytime >= " . $start_time ." AND daytime <= " . $end_time ." GROUP BY goods_id";
//echo $sql;
$goods_hits = $db->arrQuery($sql);
foreach ($goods_hits AS $key => $goods)
{
	$sql_2 = "DELETE FROM " . GOODS_HITS . " WHERE daytime >= " . $start_time ." AND daytime <= " . $end_time ." AND goods_id = " . $goods["goods_id"];
	$db->query($sql_2);
	
	$sql_1 = "INSERT INTO " . GOODS_HITS . "(goods_id , daytime , hitnum) VALUES (".$goods["goods_id"]."," . $start_time ."," . $goods["hitnum_s"] .")";
	$db->query($sql_1);
}
$db->close();
?>