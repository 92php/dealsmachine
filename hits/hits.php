<?php
/**
 * hits.php                 商品点击率统计
 *
 * @author                  mashanling(msl-138@163.com)
 * @date                    2012-11-06 14:33:01
 * @last modify             2012-11-07 10:52:35 by mashanling
 */

define('INI_WEB', true);

date_default_timezone_set('America/Whitehorse');

$GLOBALS['_CFG']['timezone'] = 8;//time.fun.php用到

header('Pragma: public');
header('Cache-Control: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 86400) . ' GMT');

require('db_config.php');
require('class.mysql.php');
require('time.fun.php');

$item_id  = isset($_GET['itemId']) ? intval($_GET['itemId']) : 0;//商品id
$callback = isset($_GET['jsoncallback']) ? $_GET['jsoncallback'] : '';
$_ACT     = isset($_GET['act']) ? $_GET['act'] : '';

if (!$item_id || !$callback || !$_ACT) {
    exit();
};

$db = new MySql(DB_HOST, DB_USER, DB_PWD, DB_NAME);

switch ($_ACT) {
    case 'goods_stat'://点击率及digg统计
        goods_stat($item_id, $callback);
        break;

    case 'set_diggs'://统计digg
        $db->query('INSERT INTO ' . GOODS_DIGG . " VALUES({$item_id},1) ON DUPLICATE KEY UPDATE digg_num=digg_num+1");
        echo $callback . '({"success": true})';
        break;
}

/**
 * 统计点击及返回digg数
 *
 * @author       mashanling(msl-138@163.com)
 * @date         2012-11-07 10:58:09
 * @last modify  2012-11-07 10:58:09 by mashanling
 *
 * @param int    $item_id  商品id
 * @param string $callback jsoncallback
 *
 * @return void 无返回值
 */
function goods_stat($item_id, $callback) {
    global $db;
	$sql = 'SELECT goods_id,cat_id FROM '. GOODS .' WHERE goods_id = '. $item_id .'';
	$res = $db->arrQuery($sql);
	if($res[0]['cat_id']) {
		$cat_id = $res[0]['cat_id'];
		$sql_c = 'SELECT cat_id,parent_id,node FROM eload_category WHERE cat_id = '. $cat_id .'';
		$res_c = $db->arrQuery($sql_c);
		$top_cat_id = explode(",", $res_c[0]['node']);
		if(!empty($top_cat_id[0])) {
			$top_cat_id = $top_cat_id[0];	
		} else {
			$top_cat_id = $res_c[0]['parent_id'];
		}
		
	}	
    $sql = 'INSERT INTO ' . GOODS_HITS_TEMP . "(goods_id, daytime, hitnum,cat_id,top_cat_id) VALUES({$item_id}, " . local_strtotime(local_date('Y-m-d')) . ', 1,'.$cat_id.','.$top_cat_id.') ON DUPLICATE KEY UPDATE hitnum=hitnum+1';
    $db->query($sql);//统计点击  
}