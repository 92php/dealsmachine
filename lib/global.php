<?php
define('ROOT_PATH', str_replace('lib/global.php', '', str_replace('\\', '/', __FILE__)));
define('SKIN', "skin3/");
require_once(ROOT_PATH.'config/path_config.php');  //配置模板路径
include_once(ROOT_PATH.'config/config.php');
require_once(ROOT_PATH.'config/memcache_config.php');
ini_set('session.cookie_domain',COOKIESDIAMON);
session_start();
header("Content-Type:text/html; charset=utf-8");
$_BEGINTIME = microtime(TRUE);
$_BEGINMEM = memory_get_usage();
if (!defined('INI_WEB')){die('Http error');}
date_default_timezone_set('America/Whitehorse');
require_once('inc.fun.php');
include_once(ROOT_PATH.'config/db_config.php');
include_once(ROOT_PATH.'lib/inc.constant.php');
require_once(ROOT_PATH."lib/inc.base.php");
require_once(ROOT_PATH."lib/class.mysql.php");
$_CFG = include(ROOT_PATH . ADMIN_STATIC_CACHE_PATH . 'website_info.php');
include_once(ROOT_PATH.'config/str_config.php');
require_once(ROOT_PATH.'config/path_config.php');  //配置模板路径
define("SMARTY_TMPL", ROOT_PATH . "temp/".SKIN."{$path}");
define("SMARTY_TMPL_C", ROOT_PATH . "temp_c/".SKIN."{$path}");
define("SMARTY_TMPL_CACHE", ROOT_PATH . "temp_cache/".SKIN."{$path}");

bootstrap();//启动,设置自定义错误处理等

if (empty($_SESSION['user_id'])){
	$_SESSION['user_id']     = 0;
	$_SESSION['user_name']   = '';
	$_SESSION['email']       = '';
	$_SESSION['user_rank']   = 0;
	if (!isset($_SESSION['login_fail']))
	{
		$_SESSION['login_fail'] = 0;
	}
}
$_COOKIE['PHPSESSID'] = empty($_COOKIE['PHPSESSID'])?session_id():$_COOKIE['PHPSESSID'];
if ($_SESSION['email'] ==''){
	define('SESS_ID', $_COOKIE['PHPSESSID']);
}else{
	define('SESS_ID', $_SESSION['email']);
}
/* 对用户传入的变量进行转义操作。*/
if (!get_magic_quotes_gpc())
{
    if (!empty($_GET))
    {
        $_GET  = addslashes_deep($_GET);
    }
    if (!empty($_POST))
    {
        $_POST = addslashes_deep($_POST);
    }

    $_COOKIE   = addslashes_deep($_COOKIE);
    $_REQUEST  = addslashes_deep($_REQUEST);
}
require_once(ROOT_PATH."lib/setSmarty.php");
$db = new MySql(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
