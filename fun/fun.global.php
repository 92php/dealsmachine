<?php
if (!defined('INI_WEB')){die('访问拒绝');}
global $_ACT,$Arr,$_CFG,$db,$_MDL,$_LANG,$user,$toplink,$err,$cur_lang,$_URI,$my_cache_id,$Tpl;

require_once(ROOT_PATH.'lib/time.fun.php');
require_once(ROOT_PATH.'lib/inc.base.php');
include_once(ROOT_PATH.'config/db_config.php');
include_once(ROOT_PATH.'config/config.php');
include_once(ROOT_PATH.'lib/inc.constant.php');
include_once(ROOT_PATH.'config/str_config.php');

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
$_COOKIE['PHPSESSID'] = empty($_COOKIE['PHPSESSID'])?'':$_COOKIE['PHPSESSID'];
if ($_SESSION['email'] ==''){
	define('SESS_ID', $_COOKIE['PHPSESSID']);
}else{
	define('SESS_ID', $_SESSION['email']);
}

/* 对用户传入的变量进行转义操作。*/

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
require_once(ROOT_PATH."lib/class.mysql.php");
$db = new MySql(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);

//热门搜索
$cat_id = empty($_GET['id'])?0:intval($_GET['id']);
$m      = empty($_GET['m'])?'':$_GET['m'];
if($m =='goods' && $_GET['id']){
	$cat_id = $db->getOne('select cat_id from '.GOODS.' where goods_id='.intval($_GET['id']));
}
$Arr['hotKeywordsArr'] = get_hot_keywords($cat_id);//print_r($Arr['hotKeywordsArr']);
$Arr['cur_lang']       = $cur_lang;

function get_hot_keywords($cat){
    global $db,$_CFG,$cur_lang_url,$cur_lang;
    $str = "";
    require_once(ROOT_PATH.'eload_admin/cache_files/hot_keywords.php');
    if($cat)
    {
        $sql = "select hot_search,parent_id from ".CATALOG." where cat_id=".intval($cat);
        $c   = $db->selectInfo($sql);
        $hot_search = $c['hot_search'];
        if(empty($hot_search)&&$c['parent_id']){
        	$cat= $c['parent_id'];
	        $sql = "select hot_search,parent_id from ".CATALOG." where cat_id=".intval($cat);
	        $c=$db->selectInfo($sql);
	        $hot_search = $c['hot_search'];
        }
        if(!empty($hot_search))
        {
           $str=$hot_search;
        }
        else
        {
            $str=$_CFG["hot_keywords"];
        }
    }
    else
    {
        $str=$_CFG["hot_keywords"];
    }
    $str = str_replace(',',"\n",$str);
	$arr = explode("\n",$str);
    $hot_search_arr = array();
    for($i=0, $n = count($arr); $i<$n; $i++ )
    {
    	$arr_k=explode(';',$arr[$i]);
    	if(count($arr_k)>1){
    		$arr_k[1] = str_replace('http://www.dealsmachine.com', '', $arr_k[1]);
    		$hot_search_arr[$i]=array("url"      => ($cur_lang_url?'/'.str_replace('/','',$cur_lang_url):'').$arr_k[1],
                                      "keywords" => $arr_k[0]);
    	}else {
        	$hot_search_arr[$i]=array("url"      => "/$cur_lang_url"."Wholesale-".str_replace(" ","-",$arr[$i]).".html",
                                      "keywords" => $arr[$i]);
    	}
    }

	return $hot_search_arr;
}
?>