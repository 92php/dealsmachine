<?php
ob_start();
session_set_cookie_params(14400);
define('ROOT_PATH', str_replace('lib/front_global.php', '', str_replace('\\', '/', __FILE__)));
define('SKIN', "skin3/");
require_once(ROOT_PATH.'config/path_config.php');  //配置模板路径
include_once(ROOT_PATH.'config/config.php');
require_once(ROOT_PATH.'config/memcache_config.php');
ini_set('session.cookie_domain',COOKIESDIAMON);
session_start();
//$_BEGINTIME = microtime(TRUE);
$_BEGINMEM = memory_get_usage();
if (!defined('INI_WEB')){die('Http error');}
header("Content-Type:text/html; charset=utf-8");
date_default_timezone_set('America/Whitehorse');
require_once('inc.fun.php');
define("SMARTY_TMPL", ROOT_PATH . "temp/".SKIN."{$path}");
define("SMARTY_TMPL_C", ROOT_PATH . "temp_c/".SKIN."{$path}");
define("SMARTY_TMPL_CACHE", ROOT_PATH . "temp_cache/".SKIN."{$path}");
require_once(ROOT_PATH."lib/setSmarty.php");
$_CFG = include(ROOT_PATH . ADMIN_STATIC_CACHE_PATH . 'website_info.php');
$lang_arr = array('en','es','ru','fr','de','pt');  //语言配置
$cur_lang = (!empty($_GET['lang']) && in_array($_GET['lang'],$lang_arr))?$_GET['lang']:(empty($_COOKIE['cookie_lang']) ? 'am_en' : (($_COOKIE['cookie_lang'] == 'am_en' || $_COOKIE['cookie_lang'] == 'sp_en')? 'am_en' : $_COOKIE['cookie_lang']));
$_URI     = $_SERVER['REQUEST_URI'];
preg_match("/^\/\w{2}\//", $_URI,$match);
if(empty($match[0])){
	$cur_lang = 'en';
}else {
	$lang= substr($match[0],1,2);
	if(in_array($lang,$lang_arr))
		$cur_lang = $lang;
	else
		$cur_lang = 'en';
}
$default_lang     = 'en'; //默认语言 fangxin 2013/07/05
$_URI             = str_replace("/$cur_lang", '', $_URI);
$_SERVER['REQUEST_URI'] = $_URI;
$_GET                   = getQueryString();     //分析URL
$_GET['m']              = empty($_GET['m'])?'':$_GET['m'];
$Arr['request_uri']     = $_GET['m']=='index'&&$_GET['a']=='index'?'/':$_SERVER['REQUEST_URI'];  //首页uri改为 '\'
$Arr['request_uri']     = $Arr['request_uri']=='/index.php'||$Arr['request_uri']=='index.php'?'/':$Arr['request_uri'];
if($cur_lang != $default_lang) {
	$url_cur_lang = '/' . $cur_lang;
} else {
	$url_cur_lang = '';
}
if(!empty($_GET['m'])){
	switch ($_GET['m']){
		case 'flow':
			if(defined("DOMAIN_CART")&&'http://'.$_SERVER['HTTP_HOST'] !=DOMAIN_CART){
			}
			break;
		case 'users':
			if(defined("DOMAIN_USER")&&'http://'.$_SERVER['HTTP_HOST'] !=DOMAIN_USER&&$_GET['a'] !='act_sign'){
				header( "HTTP/1.1 301 Moved Permanently");
				header("Location:".DOMAIN_USER.$url_cur_lang.$_SERVER['REQUEST_URI']);
				exit();
			}
			break;
		case 'webad':
			if(defined("DOMAIN_USER")&&'http://'.$_SERVER['HTTP_HOST'] !=DOMAIN_USER){
				header( "HTTP/1.1 301 Moved Permanently");
				header("Location:".DOMAIN_USER.$url_cur_lang.$_SERVER['REQUEST_URI']);
				exit();
			}
			break;
		default:
			if(defined("DOMAIN")&&'http://'.$_SERVER['HTTP_HOST'] !=DOMAIN){
				Header( "HTTP/1.1 301 Moved Permanently");
				header("Location:".DOMAIN.$url_cur_lang.$_SERVER['REQUEST_URI']);
				exit();
			}
			break;
	}
}
$cur_lang_url = $cur_lang == 'en'?'':$cur_lang.'/';
$Arr['cur_lang_url'] = $cur_lang_url;
$Arr['cur_lang_url'] = $cur_lang_url;
require(ROOT_PATH . 'languages/common.php'); //载入公共语言文件
require(ROOT_PATH . 'languages/' . $cur_lang . '/common.php');  //载入当前语言文件
require(ROOT_PATH . 'languages/' . $cur_lang . '/seo/meta_seo.php');  //载入当前语言META文件
setcookie("cookie_lang",$cur_lang, time()+3600*24*30, '/', COOKIESDIAMON);
$Arr['cur_lang']     = $cur_lang;
$Arr['default_lang'] = $default_lang;
$my_cache_id         = $cur_lang.$_URI;
$my_cache_id         = sprintf('%X', crc32($my_cache_id));
$_FLAG  = ($_URI=='/' || file_exists(ROOT_PATH.$_URI))?true:false;
$_START = $_FLAG?'index':'page_not_found';
$_MDL   = empty($_GET['m'])?$_START:$_GET['m'];
$_ACT   = empty($_GET['a'])?'index':$_GET['a'];
$_MDL   = file_exists(ROOT_PATH.'fun/fun.'.$_MDL.'.php')?$_MDL:'index';
//谷歌再营销代码
$google_tag_params = array(
	'prodid' => "''",
	'pagetype' => "'siteview'",
	'totalvalue' => "''",
	'currency' => "''",
	'pcat' => "''"
);
$Arr['google_tag_params'] = $google_tag_params;
