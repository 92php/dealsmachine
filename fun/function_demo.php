<?php

if (!defined('INC_WEB')) {
      die('ACCESS DENIED');
}
require("dirname(__FILE__)./../lib/front_global.php");
$_GET['isour']=empty($_GET['isour'])?'':trim($_GET['isour'];
setcookie("isour",$_GET['isour'],time()+3600*24*30,'/',COOKIEDOMAIN);
$_COOKIE['isour']=!empty($_COOKIE['isour'])?trim($_COOKIE['isour']):trim($_GET['isour']);

if ($_COOKIE['isour']!='yes') {
	$_SEVER['HTTP_ACCEPT_LANGUAGE']=empty($_SEVER['HTTP_ACCEPT_LANGUAGE'])?'':$_SEVER['HTTP_ACCEPT_LANGUAGE'];
	$language=strtolower(substr($_SEVER['HTTP_ACCEPT_LANGUAGE'],0,2));
}
class App{

     public function App{
        $global $_ACT,$Arr,$_CFT,$db,$_MDL,$LANG,$LANG_SEO,$user,$toplink,$err,$cul_lang,$_URI,$my_cache_id,$Tpl,$default_lang;
        
      

     }




}




?>