<?php
define('INI_WEB', true);
require_once('../lib/global.php');//引入全局文件
require_once('../lib/is_loging.php');
require_once('../lib/is_loging.php');
//$_CFG = read_static_cache('website_info',2);

//$_CFG = include(ROOT_PATH.'eload_admin/cache_files/website_info.php');
//print_r($_CFG);
//write_static_cache('website_info',$_CFG,2); 
//$area_Arr = read_static_cache('area_key',2);
//print_r($_CFG);
if($_POST)
{
    $hot_keywords  = $_POST["hot_keywords"];
    $shop_keywords = $_POST["shop_keywords"];
    $shop_desc     = $_POST["shop_desc"];
    $str.="<?php ";
    $str.='$shop_config_ARR=array();';
    $str.='$shop_config_ARR["hot_keywords"]="'.$hot_keywords.'";';
    $str.=" ?>";

    file_put_contents("./cache_files/hot_keywords.php",$str);  
    $_CFG['hot_keywords'] = $hot_keywords;
    $_CFG['shop_keywords'] =$shop_keywords;
    $_CFG['shop_desc'] =$shop_desc;
    //print_r($_CFG);
    //exit();
    //print_r($_CFG);
   // exit();
    write_static_cache('website_info', $_CFG,2); 
    $_CFG = read_static_cache('website_info',2);
}
//var_dump($_CFG["creat_html_domain"]);
//exit();
//$_CFG["creat_html_domain"] = "http://".$_SERVER['HTTP_HOST']."/";
//require_once('./cache_files/hot_keywords.php');
$Arr['hot_keywords']=$_CFG["hot_keywords"]; 
$Arr['shop_keywords']=$_CFG["shop_keywords"]; 
$Arr['shop_desc']=$_CFG["shop_desc"]; 
$_ACT ='shop_config';
temp_disp();
?>