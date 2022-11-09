<?php
/**
 * new_abc_index_keyword.php    新abc索引关键字管理
 *
 * @author                      mashanling(msl-138@163.com)
 * @last modify                 2013-07-26 14:19:52 by mashanling
 */
set_time_limit(0);
define('INI_WEB', true);
require_once('../lib/global.php'); //引入全局文件
require_once(LIB_PATH . 'is_loging.php');
require_once(LIB_PATH . 'time.fun.php');
require(ROOT_PATH . 'lib/seo/class.seo_admin.php');

admin_priv('abckeyword'); //检查权限

$_ACT   = isset($_GET['act']) ? $_GET['act'] : 'list';

new SEO_Admin($_ACT);
$_ACT   = 'abckeyword_' . $_ACT;
temp_disp();