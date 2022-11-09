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

$GLOBALS['_CFG']['timezone'] = 8;//time.fun.php用到

header('Pragma: public');
header('Cache-Control: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 86400) . ' GMT');

require('db_config.php');
require('class.mysql.php');
require('time.fun.php');
$db = new MySql(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$time      = microtime(true);
$num = 0;

$sql        = 'INSERT INTO ' . GOODS_HITS . '(goods_id, daytime, hitnum, cat_id, top_cat_id) SELECT goods_id,daytime,hitnum,cat_id,top_cat_id FROM ' . GOODS_HITS_TEMP . ' AS t ON DUPLICATE KEY UPDATE ' . GOODS_HITS . '.hitnum=' . GOODS_HITS . '.hitnum + t.hitnum';
$res = $db->query($sql);

//转移至评论库商品表 by mashanling on 2013-03-25 15:24:27
$sql        = 'INSERT INTO ' . REVIEWLIB_GOODS . '(goods_id,add_time,hits) SELECT g.goods_id,g.add_time,t.hitnum FROM ' . GOODS_HITS_TEMP . ' AS t JOIN ' . GOODS . ' AS g ON t.goods_id=g.goods_id ON DUPLICATE KEY UPDATE ' . REVIEWLIB_GOODS . '.hits=' . REVIEWLIB_GOODS . '.hits + t.hitnum';
$db->query($sql);
$sql        = 'DELETE a FROM ' . REVIEWLIB_GOODS . ' AS a JOIN ' . GOODS . ' AS g ON a.goods_id=g.goods_id WHERE g.goods_number<=0 OR g.is_on_sale=0 OR g.is_delete=1 OR g.is_alone_sale=0';
$db->query($sql);//删除评论库表无效产品

$db->query('DELETE FROM ' . GOODS_HITS_TEMP);

$affected_num = $db->affectedRows();
$msg          = "转移{$num}, 删除{$affected_num}";
//Func::crontab_log($_SERVER['SCRIPT_NAME'], $msg, $time);