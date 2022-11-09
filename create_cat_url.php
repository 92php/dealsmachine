<?php
/**
 * dealsmachine定时导订单
 * 
 * @author wuwenlong <2011-07-26>
 */

set_time_limit(0);
define('INI_WEB', true);
require_once('lib/global.php');              //引入全局文件
require_once('lib/time.fun.php');              //引入全局文件

$sql = "select * from ".CATALOG;
$cat_arr=$db->arrQuery($sql);
$cat_save= array();
foreach ($cat_arr as $k=>$v){
	$cat_save['url_title'] =title_to_url($v['cat_name'])."-c-".$v['cat_id'].".html";
	$db->autoExecute(CATALOG,$cat_save,'update','cat_id='.$v['cat_id']);
}

?>