<?php
/**
 * new_delete_goods.php       删除商品，包括图片，商品相册及关联表
 * 
 * @author                    mashanling(msl-138@163.com)
 * @date                      2012-02-24 09:23:12
 * @last modify               2012-02-24 17:24:29 by mashanling
 */

set_time_limit(0);
define('INI_WEB', true);
require_once('../../lib/global.php');
require_once(LIB_PATH . 'is_loging.php');
require_once(LIB_PATH . 'time.fun.php');
require_once(LIB_PATH . 'param.class.php');
require_once(LIB_PATH . 'class.function.php');

while (ob_get_level() != 0) {
    ob_end_clean();
}

ob_implicit_flush(true);

$goods_sn   = Param::get('goods_sn');    //商品编码

if (!$goods_sn) {//未传商品编码
    $file = 'delete_goods.txt';
    
    if (file_exists($file)) {
        $goods_sn = file_get_contents($file);
        $goods_sn = str_replace("\n", ',', $goods_sn);
    }
}

!$goods_sn && exit('无商品');

$goods_sn = explode(',', $goods_sn);
$goods_sn = array_map('trim', $goods_sn);
$goods_sn = join("','", $goods_sn);
$goods_sn = "'{$goods_sn}'";

//商品id
$goods_id_arr = $db->getCol('SELECT DISTINCT goods_id FROM ' . GOODS . " WHERE goods_sn IN({$goods_sn})");

empty($goods_id_arr) && exit('无商品');

$goods_ids = join(',', $goods_id_arr);

Func::delete_goods($goods_ids);
?>