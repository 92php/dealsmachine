<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');


$goods_sn = empty($_POST['goods_sn'])?'':$_POST['goods_sn'];
$keys_code = empty($_POST['keys_code'])?'':$_POST['keys_code'];

if ($keys_code != $_CFG['keys_code']){
	echo 'feifacenshu.';
	exit;
}

if ($goods_sn!=''){
	$goods_id = $db->getOne("select goods_id from ".GOODS." where goods_sn = '$goods_sn'");
	if ($goods_id>0){
	    echo 'success';
	}else{
	   echo '不存在商品';
	}
	
}


?>

