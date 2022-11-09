<?php
/**
 * shipping_fee.php         订单运费
 * 
 * @author                  mashanling(msl-138@163.com)
 * @date                    2012-01-28 11:23:46
 * @last modify             2012-01-28 11:23:46 by mashanling
 */
define('INI_WEB', true);
require_once('../../lib/global.php');
require_once(LIB_PATH . 'is_loging.php');
require_once(LIB_PATH . 'time.fun.php');
require_once(LIB_PATH . 'param.class.php');
require_once(LIB_PATH . 'lib.f.order.php');
require_once(LIB_PATH . 'syn_public_fun.php');

$free_total_fee = 0;
$weight         = 0;
$price          = Param::get('price', 'float');
$goods          = Param::get('goods');
$order_sn       = Param::get('order_sn');
$country        = Param::get('country', 'int');

if ($order_sn) {
    $sql = 'SELECT og.goods_price,og.goods_number,g.goods_weight,o.goods_amount,o.country FROM ' . ODRGOODS . ' AS og JOIN ' . ORDERINFO . ' AS o ON o.order_id=og.order_id  JOIN ' . GOODS . " AS g ON g.goods_id=og.goods_id WHERE o.order_sn='{$order_sn}'";
    //echo $sql;
    
    $db->query($sql);
    
    while (($info = $db-> fetchArray()) !== false) {
        $price   = $info['goods_amount'];
        $country = $info['country']; 
        $free_total_fee += get_shipping_fee($info['goods_price'], $info['goods_weight']) * $info['goods_number'];
        $weight         += $info['goods_weight'] * $info['goods_number'];
    }
}
else {
    foreach (explode(',', $goods) as $item) {
        $data = explode('|', $item);//0: sn, 1: num
        $info = $db->selectInfo('SELECT shop_price,goods_weight FROM ' . GOODS . " WHERE goods_sn='{$data[0]}'");
        
        if (!empty($info)) {
            $free_total_fee += get_shipping_fee($info['shop_price'], $info['goods_weight']) * $data[1];
            $weight         += $info['goods_weight'] * $data[1];
        }
        else {
            echo $data[0], '不存在<br />';
        }
    }
}
$shipping_list     = available_shipping_list();
$shipping_fee      = read_static_cache('shipping_fee', 2);

foreach ($shipping_list as &$v) {
    $qizhong_price = 0;
    $xuzhong_price = 0;
    
    switch ($v['id']) {
        case '3' :
            $qizhong_price = $shipping_fee[$country]['exp_fee'];
            $xuzhong_price = $shipping_fee[$country]['exp_xu_fee'];
            break;
            
        case '2' :
            $qizhong_price = $shipping_fee[$country]['org_tran_fee'];
            $xuzhong_price = $shipping_fee[$country]['org_tran_xu_fee'];
            break;
    }
    
    if ($v['id'] == 1) {
        $v['shippping_fee']   = 0.00;
        $v['free_ship_price'] = 0.00;
    }
    else {
        $v['shippping_fee']   = count_shipping_fee($qizhong_price, $xuzhong_price, $weight);
        $v['free_ship_price'] = $v['shippping_fee'] - $free_total_fee;
    }
    
    if ($price > 100) {
        $youhuiduan = ceil($v['free_ship_price'] / 5);
        $v['real_free_ship_price'] = $youhuiduan > 1 ? $youhuiduan * 5 - 5.01 : 0;
    }
}
var_dump($free_total_fee, $country, $price);
var_export($shipping_list);
?>