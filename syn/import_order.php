<?php
set_time_limit(0);
define('INI_WEB', true);
$_BEGINTIME = microtime(TRUE);
require('../lib/global.php');              //引入全局文件
require('../lib/time.fun.php');  
require(ROOT_PATH . 'lib/lib.f.order.php');
//查地址信息 随机取地址

$nutilltime  =  local_strtotime('2011-06-06');


$str = "";
$guojia = read_static_cache('area_key',2);	
//查导入产品
$sql = "SELECT order_id,email,order_sn,country,consignee,tel,order_amount,order_status FROM `eload_order_info`   where add_time > '$nutilltime'";
$goodsArr = $db->arrQuery($sql);
foreach($goodsArr as $key => $val){
	$order_id = $val['order_id'];
	
	$sql = "select goods_id from eload_order_goods where order_id = '".$order_id."';";
	if (!$db->getOne($sql)){
	   $country = $guojia[$val['country']]['region_name']	;	
	   $str .= $val['order_sn'].','.$val['email'].','.$val['consignee'].','.$val['tel'].','.$country.','.$val['order_amount'].','.$val['order_status']."<br>";
	}
	
	
}

echo $str;

?>

