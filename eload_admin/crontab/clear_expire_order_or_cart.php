<?php
/**
 * 定时删除购物车和订单信息
 * 大于1个月的购物车产品自动清除
 * 大于2个月的未付款订单自动清除
 * */
set_time_limit(0);

define('INI_WEB', true);
require('../../lib/global.php');              //引入全局文件
require(LIB_PATH . 'time.fun.php');
require(LIB_PATH . 'class.function.php');
$time_start = microtime(true);
$now_time = gmtime();
$one_month = $now_time - 30*24*60*60;	//30天前时间戳
$two_month = $now_time - 60*24*60*60;	//60天前时间戳

//清除大于1个月的购物车产品
/*$sql = "SELECT COUNT(*) FROM eload_cart WHERE addtime <= " . $one_month;
echo $sql."\r\n";*/
$sql = "DELETE FROM eload_cart WHERE addtime <= " . $one_month;
$db->query($sql);
//echo $db->affectedRows()."\r\n";
$log = '删除购物车：' . $db->affectedRows();

if (1 == local_date('j')) {//先统计付款率统计,防止清除掉未付款订单 by mashanling on 2014-05-30 11:25:45
    $url    = 'http://' . $_SERVER['HTTP_HOST'] . '/eload_admin/payment_rate.php?act=setMonthData';
    $ch     = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

//清除大于2个月的未付款订单信息
/*$sql = "SELECT COUNT(*) FROM eload_order_info WHERE order_status < 1 AND add_time <= " . $two_month;
echo $sql."\r\n";*/
$deleted    = 0;
$order_id   = '';
$order_sql  = 'SELECT user_id,order_id,order_sn,used_point FROM ' . ORDERINFO . ' WHERE order_status=0 AND add_time<=' . $two_month;
$db->query($order_sql);

while (($row = $db->fetchArray()) !== false) {
    $order_id .= $row['order_id'] . ',';
    $row['used_point'] > 0 && add_point($row['user_id'], $row['used_point'], 2, 'Order #' . $row['order_sn'] . ' has been deleted');
}

if ($order_id) {
    $order_id = trim($order_id, ',');
    $db->delete(ORDERINFO, 'order_id IN(' . trim($order_id, ',') . ')');
    //echo $db->affectedRows()."\r\n";
    $log .= '，删除未付款订单：' . $db->affectedRows();

    //清除清除大于2个月的未付款订单的商品列表信息
    /*$sql = "SELECT COUNT(*) FROM eload_order_goods WHERE order_id IN (SELECT order_id FROM eload_order_info WHERE order_status < 1 AND add_time <= " . $two_month . ")";
    echo $sql."\r\n";*/
    $sql = "DELETE FROM eload_order_goods WHERE order_id IN ({$order_id})";
    $db->query($sql);
    //echo $db->affectedRows()."\r\n";
    $log .= '，删除未付款订单商品：' . $db->affectedRows();
}

echo $log;
/*//购物车
SELECT count(*) FROM eload_cart WHERE addtime <= 1314463087

SELECT count(*) FROM eload_cart

DELETE FROM eload_cart WHERE addtime <= 1314463087

//订单商品列表
SELECT order_id FROM eload_order_info WHERE order_status < 1 AND add_time <= 1311871087
SELECT order_id FROM eload_order_info WHERE order_status < 1
DELETE FROM eload_order_goods WHERE order_id IN (SELECT order_id FROM eload_order_info WHERE order_status < 1 AND add_time <= 1311871087)

DELETE FROM eload_order_info WHERE order_status < 1 AND add_time <= 1311871087*/

Logger::filename(LOG_FILENAME_PATH);
trigger_error($log);
?>