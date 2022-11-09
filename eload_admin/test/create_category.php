<?php
/**
 * create_category.php   创建分类
 * 
 * @author               mashanling(msl-138@163.com)
 * @date                 2011-10-26
 * @last modify          2011-11-30 by mashanling
 * 
 */
define('INI_WEB', true);
require_once('../../lib/global.php');
require_once('../../lib/is_loging.php');
require_once('../../lib/param.class.php');

creat_category();
var_export(read_static_cache('category_children', 2));
?>