<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');


$goods_sn = empty($_POST['goods_sn'])?'':$_POST['goods_sn'];
$goods_id = empty($_POST['goods_id'])?0:$_POST['goods_id'];

if ($goods_sn!='' &&  $goods_id!=0){
	$sql = " update ".GOODS." set goods_sn = '$goods_sn'  where goods_id = ".$goods_id ." and goods_id <=3396 ";
	$db->query($sql);
	echo '修复完成';
}
?>

