<?php
define('INI_WEB', true);
require('../lib/global.php');              //引入全局文件
require('../lib/time.fun.php');
require(ROOT_PATH.'eload_admin/email_temp/mail_conf.php');  //取得模版标题
include(ROOT_PATH . 'languages/en/common.php');
include(ROOT_PATH . 'languages/en/user.php');
$Arr['lang']   =  $_LANG;
$Tpl->caching = false;        //使用缓存
$now_time = gmtime();


$result_msg = '';

echo  '<pre>';
$sql = "select * from eload_goods_hits_temp ";
$goodsArr = $db->arrQuery($sql);
if (!empty($goodsArr))
foreach($goodsArr as $row){
	$sql = "select id from eload_goods_hits where goods_id = '".$row['goods_id']."' and daytime = '".$row['daytime']."'";
	$hid = $db->getOne($sql);
	if($hid>0){
		$sql = "update eload_goods_hits set hitnum = (hitnum + '".$row['hitnum']."') where id = '".$hid."'";
	}else{
		$sql = "insert into eload_goods_hits set hitnum = '".$row['hitnum']."', goods_id = '".$row['goods_id']."', daytime = '".$row['daytime']."'";
	}
	//echo $sql.'<br>';
	$db->query($sql);
	$sql = "delete from eload_goods_hits_temp where goods_id = '".$row['goods_id']."' and daytime = '".$row['daytime']."'";
	$db->query($sql);
}

?>

