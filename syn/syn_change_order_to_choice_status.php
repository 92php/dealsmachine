<?php
/**
 * 修改订单状态成备货状态接口
 * 
 * */
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
//require_once('../lib/time.fun.php');
//require_once('../lib/lib_order.php');
require('../lib/lib.f.order.php');
//include(ROOT_PATH.'languages/en/user.php');


//接收订单列表
$get_order_sn_list = $_REQUEST['order_list'];

$return_order_sn_list = '';
if(empty($get_order_sn_list))
{
	exit('parameters error!');
}
else 
{
	$get_order_sn_list = urldecode($get_order_sn_list);
	$get_order_sn_list = stripcslashes($get_order_sn_list);	//反引用一个使用 addcslashes() 转义的字符串
	
	//更新订单（未付款，已付款）状态为备货状态
	$sql = "UPDATE " . ORDERINFO . " SET order_status = 2 WHERE order_sn IN (" . $get_order_sn_list . ") AND (order_status in(0,1,6) )";
	$db->query($sql);
	//echo $sql;
	//获得更新成功订单的订单列表
	$sql = "SELECT order_sn FROM " . ORDERINFO . " WHERE order_sn IN (" . $get_order_sn_list . ") AND order_status >= 2";
	$return_order_sn_array = $db->getCol($sql);
	$return_order_sn_list = "'".implode("','",$return_order_sn_array)."'";
	
	echo $return_order_sn_list;
}
exit;
?>