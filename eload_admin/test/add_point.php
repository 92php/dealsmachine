<?php
/**
 * add_point.php     查看文件
 * 
 * @author           mashanling(msl-138@163.com)
 * @date             2011-11-23
 * @last modify      2011-11-30 by mashanling
 */
define('INI_WEB', true);
require_once('../../lib/global.php');
require_once('../../lib/is_loging.php');
require_once('../../lib/time.fun.php');
require_once('../../lib/param.class.php');

$order_sn  = Param::get('order_sn');
$old_state = Param::get('old_state', 'int');
$new_state = Param::get('new_state', 'int');
$new_state = $new_state ? $new_state : 3;

!$order_sn && exit('请输入订单号');

$points   = caculate_order_get_point($order_sn);

$points === false && exit("订单 #{$order_sn} 不存在");

$points = ceil($points);

$points > 0 && act_caculate_point($new_state, $old_state, $_GET['user_id'], $points, $order_sn);
/**
 * 计算订单可获取积分数
 * 
 * @param  string $order_sn 订单号
 * @return mixed 如果订单存在，返回可获取积分数，否则返回false
 */
function caculate_order_get_point($order_sn) {
    global $db;
    
    $order_info = $db->selectInfo('SELECT order_id,user_id FROM ' . ORDERINFO . " WHERE order_sn='{$order_sn}'");
    
    if (empty($order_info)) {
        return false;
    }
    
    $points = 0;
    $sql    = 'SELECT o.goods_price,o.goods_number,g.point_rate FROM ' . ODRGOODS . ' AS o JOIN ' . GOODS . " AS g ON o.goods_id=g.goods_id WHERE o.order_id={$order_info['order_id']}";
    $db->query($sql);
    
    while (($row = $db->fetchArray()) !== false) {
        $points += price_format($row['goods_price'] * $row['goods_number'] * $row['point_rate']);
    }
    
    $_GET['user_id'] = $order_info['user_id'];
    
    return $points;
}
?>