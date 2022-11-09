<?php
set_time_limit(0);
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/syn_public_fun.php');

$stime = date('Y-m-d', gmstr2time('-14 day'));
$etime = date('Y-m-d', gmtime());
//不参与排序的产品
$fliterArr = array('CON0039','MBE9193','CON0635','CON0634','CON0633');


$sql = "select sum( o.goods_number ) AS num, o.goods_id ,o.goods_sn
FROM `eload_order_goods` AS o, eload_order_info AS oi , eload_goods as g
WHERE o.order_id = oi.order_id and  o.goods_id = g.goods_id and g.goods_number > 0 and g.is_delete = 0 and oi.order_status > 0 and oi.order_status < 9 AND oi.add_time >= '".(gmtime() - 14 * 86400)."' and o.goods_price >= 0.45  group by o.goods_id   having num > 0 order by num desc";

$goodsArr = $db->arrQuery($sql);
$db->query("update ".GOODS."  SET week2sale =0");

foreach($goodsArr as $val){
	if (!in_array(strtoupper($val['goods_sn']),$fliterArr)){
		$UpdateSql = "update ".GOODS."  SET week2sale = '".$val['num']."'  where goods_id = '".$val['goods_id']."'";
		$db->query($UpdateSql);
	}
}

echo count($goodsArr).'类2个星期销售量更新成功';		

?>