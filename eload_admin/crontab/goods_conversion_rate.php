<?php
/**
 * goods_conversion_rate.php    产品转化率
 * 点击量标准值= 该分类产品最大点击量开三次方
 IP价值标准值=该分类IP价值前5位产品的平均值

 点击量得分 = （每个产品点击量开三次方/点击量标准值）*40

 IP价值得分= （产品的IP价值/IP价值标准值）*60

 排序方法= 点击量得分+ IP价值得分 倒序排列
 *
 * @author                      mashanling(msl-138@163.com)
 * @date                        2012-09-13 14:25:22
 * @last modify                 2012-11-12 09:11:59 by mashanling
 */

set_time_limit(0);
define('INI_WEB', true);
require ('../../lib/global.php');
require (ROOT_PATH . '/lib/time.fun.php');
($filename = ROOT_PATH . 'lib/class.function.php') && require($filename);

$time_start  = microtime(true);
$start       = gmtime() - 86400 * 15;//销售15天内

//从数据库
$db_slave   = defined('IS_LOCAL') && IS_LOCAL ? $db : (function_exists('get_slave_db') ? get_slave_db() : $db);

$sql = 'SELECT (SELECT SUM(hitnum) FROM ' . GOODS_HITS . ' WHERE goods_id=og.goods_id AND daytime>=' . $start . ') AS hitnum,
og.goods_id,CAST(c.node AS UNSIGNED) AS cat_id,g.shop_price,COUNT(oi.order_id) AS order_num FROM ' . ODRGOODS . ' AS og,' . GOODS . ' AS g,' . ORDERINFO . ' AS oi,' . CATALOG . ' AS c
WHERE og.order_id=oi.order_id AND g.goods_id = og.goods_id AND g.cat_id=c.cat_id AND oi.order_status>0 AND oi.order_status<9 and oi.is_dao = 0 AND oi.add_time>=' . $start . ' AND og.main_goods_id = 0
GROUP BY og.goods_id HAVING(order_num>0)' . (isset($_GET['limit']) ? ' LIMIT ' . $_GET['limit'] : '');

//临时表 by mashanling on 2012-09-17 14:24:16
$table =  GOODS_CONVERSION_RATE . '_can_delete';
//从服务器无create权限 by mashanling on 2012-11-12 09:27:01
/*$db_slave->query("CREATE TABLE IF NOT EXISTS {$table} (
  `goods_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品id',
  `cat_id` smallint(4) unsigned NOT NULL DEFAULT 0 COMMENT '分类id',
  shop_price double(10,2) unsigned NOT NULL DEFAULT 0.00 COMMENT '商品价格',
  `hit_num` mediumint(6) unsigned NOT NULL DEFAULT 0 COMMENT '点击数',
  `order_num` smallint(5) unsigned NOT NULL DEFAULT 0 COMMENT '订单数',
  PRIMARY KEY(goods_id),
  KEY (cat_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='产品一个月转化率临时表'");*/
$db_slave->query('DELETE FROM ' . $table);

//写数据进临时表
$db_slave->query("INSERT INTO {$table}(hit_num,goods_id,cat_id,shop_price,order_num) " . $sql);
$values   = '';
/*$rate_arr = array();//前五位转化率
$hit_arr      = array();//最高点击量
$sql_hit_arr  = 'SELECT * FROM (SELECT cat_id,hit_num FROM ' . $table . ' ORDER BY hit_num DESC) AS t GROUP BY cat_id';
$arr = $db_slave->arrQuery($sql_hit_arr);

foreach ($arr as $row) {
    $cat_id = $row['cat_id'];
    $hit_arr[$cat_id] = pow($row['hit_num'], 1/3);
    $rate_arr[$cat_id] = $db_slave->getOne("SELECT AVG(rate) FROM (SELECT cat_id,order_num/hit_num*shop_price AS rate FROM {$table} WHERE cat_id={$cat_id} ORDER BY rate DESC LIMIT 5) AS t");
}*/

$arr     = $db_slave->arrQuery('SELECT * FROM ' . $table);

foreach($arr as $row) {
    $cat_id   = $row['cat_id'];

    /*$conversion_rate = $row['order_num'] / $row['hit_num'] * $row['shop_price'] / $rate_arr[$cat_id] * 60;//转化率
    $conversion_rate+= pow($row['hit_num'], 1/3) / $hit_arr[$cat_id] * 40;*/
    $conversion_rate = $row['hit_num'] > 0 ? $row['order_num'] * $row['shop_price'] / $row['hit_num'] : 0;//转化率
    $conversion_rate = round($conversion_rate, 2);
    $conversion_rate = max(0.01, $conversion_rate);
    $conversion_rate2 = $row['order_num'] > 1 ? $conversion_rate * 10000 : $conversion_rate;//一级类，先排订单大于2，此*10000就可实现 by mashanling on 2013-04-05 15:51:27

    $values .= ",({$row['goods_id']},{$conversion_rate},{$conversion_rate2})";
}

if ($values) {
    $db->query('DELETE FROM ' . GOODS_CONVERSION_RATE);
    $db->query('INSERT INTO ' . GOODS_CONVERSION_RATE . ' VALUES ' . substr($values, 1));
}

//写日志
$log = var_export($hit_arr, true) . PHP_EOL . var_export($rate_arr, true) . PHP_EOL . str_replace('),', '),' . PHP_EOL, $values) . PHP_EOL;
Logger::filename(LOG_FILENAME_PATH);
trigger_error($log);
echo 'ok';