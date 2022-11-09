<?php
/**
 * class_link.php    获取指定分类所有链接
 * 
 * @author           mashanling(msl-138@163.com)
 * @date             2011-10-20
 * @last modify      2011-10-21 by mashanling
 * 
 */
define('INI_WEB', true);
require_once('../lib/global.php');
require_once('../lib/class.function.php');
require_once('../lib/param.class.php');

Func::ob_start();

if($cur_lang != $default_lang){
	$cat_all =  read_static_cache($cur_lang.'_category_c_key',2);
}else {
	$cat_all =  read_static_cache('category_c_key',2);
}

foreach ($cat_all as $v) {
    $p       = empty($v['parent_id']) ? true : false;
	$url     =  creat_nav_url($v['url_title'], $v['cat_id'], $p);
	echo '<a href="http://www.bestafford.com', $url, '">', $v['cat_name'], '</a>', PHP_EOL;
	
	ob_flush();
	flush();
}
?>