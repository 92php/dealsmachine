<?php
if (!defined('INI_WEB')){die('访问拒绝');}
require(dirname(__FILE__)."/../lib/front_global.php");
//判断是不是自己人
$_GET['isour'] = !empty($_GET['isour'])?trim($_GET['isour']):'';
setcookie("isour", $_GET['isour'], time()+3600*24*30, '/', COOKIESDIAMON);
$_COOKIE['isour'] = !empty($_COOKIE['isour'])?trim($_COOKIE['isour']):$_GET['isour'];
if ($_COOKIE['isour'] != 'yes'){
	$_SERVER['HTTP_ACCEPT_LANGUAGE'] = empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])?'':$_SERVER['HTTP_ACCEPT_LANGUAGE'];
	$language = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2));//浏览器语言
}

class App
{
	public function App()
	{				
		global $_ACT,$Arr,$_CFG,$db,$_MDL,$_LANG,$_LANG_SEO,$user,$toplink,$err,$cur_lang,$_URI,$my_cache_id,$Tpl,$default_lang;
		$Arr['lang']   =  $_LANG;
		$blang = $GLOBALS ['language'];
		include(ROOT_PATH."fun/fun.$_MDL.php");
		$Arr['ArticleCatArr'] = get_foothelp_article();	
		$_ACT = $_MDL;
		if(empty($Arr['new_products_s']) && empty($Arr['hot_products_s'])  && empty($Arr['SpecialOffer_s'])  && empty($Arr['dropship_s']) )$Arr['home_s'] = '_s';
		$Arr['cmodel'] = $_MDL;
		$Arr['cact'] = empty($_GET['a'])?'':$_GET['a'];
		$Arr['ppid'] = empty($ppid)?'0':$ppid;
		$Arr['main_domain'] = MAIN_DOMAIN;
		$Arr['domain_img'] = DOMAIN_IMG;
	}
	public function run()
	{
		require_once(ROOT_PATH.'lib/time.fun.php');
		temp_disp();
	}
}?>
