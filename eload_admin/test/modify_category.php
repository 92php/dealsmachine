<?php
/**
 * modify_category.php   修改分类
 *
 * @author               mashanling(msl-138@163.com)
 * @date                 2011-12-03
 * @last modify          2011-12-03 by mashanling
 *
 */
define('INI_WEB', true);
require_once('../../lib/global.php');
!IS_LOCAL && require_once('../../lib/is_loging.php');
require_once('../../lib/param.class.php');

$array = array();
$cat_arr = read_static_cache('category_c_key', 2);
$cat_all = read_static_cache('category_children', 2);
$time = microtime(true);
foreach ($cat_all as $k => $v) {
        $array[$k]['level'] = 1;
        $array[$k]['node'] = $k;
        $db->query('UPDATE ' . CATALOG . " SET level=1,node='{$k}' WHERE cat_id={$k}");

    foreach ($v['children'] as $kk) {
        $_t = get_parent_id($kk);
        $_t = $kk . ',' . substr($_t, 0, -3);
        $_t = explode(',', $_t);
        $_t = array_reverse($_t);
        $array[$kk]['level'] = count($_t);
        $array[$kk]['node'] = join(',', $_t);
        $db->query('UPDATE ' . CATALOG . " SET level={$array[$kk]['level']},node='{$array[$kk]['node']}' WHERE cat_id={$kk}");
    }
}
var_dump(execute_time($time));
$msg = var_export($array, true);

Logger::filename(LOG_FILENAME_PATH);
trigger_error($msg);