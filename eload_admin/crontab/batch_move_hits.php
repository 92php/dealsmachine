<?php
/**
 * batch_move_hits.php      定时转移商品临时点击数移至总表
 *
 * @author                  wuwenlong mashanling(msl-138@163.com)
 * @date
 * @last modify             2011-12-14 by mashanling
 */

set_time_limit(0);
define('INI_WEB', true);
require ('../../lib/global.php');
require (ROOT_PATH . 'lib/time.fun.php');
//require (ROOT_PATH . 'lib/class.function.php');

//unset($db);
$db = get_slave_db();

$time      = microtime(true);
$sql       = 'SELECT * FROM ' . GOODS_HITS_TEMP;
$goods_arr = $db->arrQuery($sql);

if (!empty($goods_arr)) {
    $num = 0;

    foreach ($goods_arr as $row) {
        $db->query('INSERT INTO ' . GOODS_HITS . "(goods_id, daytime, hitnum) VALUES({$row['goods_id']}, {$row['daytime']}, {$row['hitnum']}) ON DUPLICATE KEY UPDATE hitnum=hitnum+{$row['hitnum']}");
        $num++;
    }

    $db->query('DELETE FROM ' . GOODS_HITS_TEMP);

    $affected_num = $db->affectedRows();
    $msg          = "转移{$num}, 删除{$affected_num}";
   // Func::crontab_log($_SERVER['SCRIPT_NAME'], $msg, $time);
}
?>