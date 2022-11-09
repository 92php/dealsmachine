<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件

$keys_code = empty ( $_GET['keys_code'] ) ? '' : $_GET['keys_code'];
if ($keys_code != $_CFG ['keys_code']) {
	die ( 'Error,key code error' );
}

$goodsStr = empty($_GET['goodssn'])?'':$_GET['goodssn'];
if(!$goodsStr) die ('empty');

$goods_sns = str_replace(',',"','",$goodsStr);
$goods_sns = "'".$goods_sns."'";
$temp = array();
$sql = "select goods_sn from eload_goods where goods_sn in ($goods_sns)";
$goodss = $db->arrQuery($sql);
if (!empty($goodss))
    foreach($goodss as $goods)  $temp[] = $goods['goods_sn'];

$goodsAtr = explode(',',$goodsStr);

$return_data = array();
if (!empty($goodsAtr))
    foreach($goodsAtr as $goods) {
		if (in_array($goods,$temp)) {
			$return_data[$goods] = 1;
		}else{
			$return_data[$goods] = 0;
		}
	}

echo serialize($return_data);
?>