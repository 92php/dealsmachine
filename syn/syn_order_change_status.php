<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require('../lib/lib.f.order.php');
$_ACT   = empty($_GET['act'])?'':trim($_GET['act']);   //方式
$_ORN   = empty($_GET['orn'])?'':trim($_GET['orn']);   //订单号	
$_USR   = empty($_GET['user'])?'':trim($_GET['user']); //同步操作人
$order_status = 0;
include(ROOT_PATH.'languages/en/user.php');
/*Demo
	把取消的改成发货状态	
	http://www.dealsmachine.com/syn/syn_order_change_status.php?act=add_track&st=&sn=&orn=E1104260431540158
	act:操作
	st:物流方式
	sn:物流单号
	orn:订单号	
*/
switch ($_ACT){
	case 'stock_up':  //已经收到款了，将状态改为备货状态
		$order_status = 2;
		break;
	case 'add_track':  //已经发货了
		$order_status = 3;
		break;	
	case 'modify_track':  //修改物流单号
		$order_status = 3;
		break;
	case 'refunded':   //退款
		$order_status = 10;
		break;
	case 'cancel':     //取消
		$order_status = 11;
		//判断退款金额是否等于订单金额
		//$zmoney = $db->getOne("select order_amount+realpay from ".ORDERINFO." where order_sn='".$_ORN."'");
		//if($zmoney>0){
		//	$order_status = 3;
		//}
		//退回所用积分
		$sql = "select used_point,user_id from ".ORDERINFO." where order_sn='".$_ORN."'";
	    $pointArr = $db->selectinfo($sql);
		if(!empty($pointArr) && $pointArr['used_point'] > 0 && $pointArr['user_id'] > 0)	{
			//检查是否已经有退回了
			$sql = "select count(*) from eload_point_record where note like  'Order:# {$_ORN} return%'";
			if(!$db->getOne($sql)){
				$note = "Order:# $_ORN return ".$pointArr['used_point']." points";
				add_point($pointArr['user_id'],$pointArr['used_point'],2,$note);				
			}
		}				
		break;
	default:
		break;
}
$get_points = caculate_order_get_point($_ORN);  //计算订单可以获得的积分数
$order_info = $db->selectInfo("select order_status,user_id from ".ORDERINFO." where order_sn='".$_ORN."'");
if(is_array($order_info)&&$order_status){
	act_caculate_point($order_status,$order_info['order_status'],$order_info['user_id'],$get_points,$_ORN); //积分加减
}
if ($order_status){
	//修复支付时间
	$pay_time = '';
	if (empty($odrArr['pay_time'])){
		$pay_time = " , pay_time = '".gmtime()."'";
	}
	$is_display = '';
	if($is_display_track_goods == 1)
	{
		$is_display = " , is_display_track_goods = 1 ";
	}
	$sql = " order_status = '$order_status' $pay_time $is_display"; 
	$db->update(ORDERINFO,$sql," order_sn = '".$_ORN."'");
	echo 'order_status_ok';
}

//添加
if ($order_status &&  $_ACT == 'add_track')
{
	$_Arr['shipping_name'] = empty($_GET['st'])?'':$_GET['st'];
	$_Arr['shipping_no']   = empty($_GET['sn'])?'':$_GET['sn'];
	$_Arr['order_sn']      = $_ORN;
	//$_Arr['add_time']      = gmtime();
	$_Arr['add_time']      = empty($_GET['submit_time'])?gmtime():gmstr2time(urldecode($_GET['submit_time']));
	$_Arr['demo']          = empty($_GET['demo'])?'':$_GET['demo'];	
	$is_display_track_goods = empty($_GET['is_all_shipped'])?0:intval($_GET['is_all_shipped']);	//是否是订单的最后一个物流单号，在商城订单详情页显示物流单号的商品明细（0:不显示，1：显示）	
	$track_goods_arr0 = empty($_GET['out_warehouse_product'])?'':trim($_GET['out_warehouse_product']);//物流单对应商品信息
	$track_goods_arr1 = explode("|",$track_goods_arr0);		
	$track_goods_arr2 = array();
	foreach ($track_goods_arr1 as $key => $res)
	{
		$list_goods = explode("_",$res);
		$track_goods_arr2[$key]['goods_sn'] = $list_goods[0];
		$track_goods_arr2[$key]['goods_num'] = $list_goods[1];
	}
	$_Arr['track_goods'] = serialize($track_goods_arr2);	//序列化之后的物流单对应商品信息				
	$sql = "select order_id,pay_time from ".ORDERINFO." where order_sn='".$_ORN."'";
	$odrArr = $db->selectinfo($sql);
	if (!empty($odrArr['order_id'])){
		$sql = "select count(*) from ".SHIPDETAILS." where shipping_no = '".$_Arr['shipping_no']."' and order_sn = '".$_ORN."' ";
		if (!$db->getOne($sql))
		   $db->autoExecute(SHIPDETAILS, $_Arr);
	}
}


//修改物流单号
if ($order_status &&  $_ACT == 'modify_track')
{
	$st = empty($_GET['st'])?'':$_GET['st'];
	$sn   = empty($_GET['sn'])?'':$_GET['sn'];
	$nsn   = empty($_GET['nsn'])?'':$_GET['nsn'];	
	$is_display_track_goods = empty($_GET['is_all_shipped'])?0:intval($_GET['is_all_shipped']);	//是否是订单的最后一个物流单号，在商城订单详情页显示物流单号的商品明细（0:不显示，1：显示）	
	$track_goods_arr0 = empty($_GET['out_warehouse_product'])?'':trim($_GET['out_warehouse_product']);//物流单对应商品信息
	$track_goods_arr1 = explode("|",$track_goods_arr0);		
	$track_goods_arr2 = array();
	foreach ($track_goods_arr1 as $key => $res)
	{
		$list_goods = explode("_",$res);
		$track_goods_arr2[$key]['goods_sn'] = $list_goods[0];
		$track_goods_arr2[$key]['goods_num'] = $list_goods[1];
	}
	$track_goods_str = serialize($track_goods_arr2);	//序列化之后的物流单对应商品信息	
	$sql = "update ".SHIPDETAILS." set shipping_no = '$nsn', shipping_name='$st', track_goods='$track_goods_str' where shipping_no = '".$sn."'";
	$db->query($sql);
}
$_LANG['os'][0] = '<font color=blue>未付款</font>';
$_LANG['os'][1] = '已付款';
$_LANG['os'][2] = '备货';
$_LANG['os'][3] = '已发货';
$_LANG['os'][4] = '已收到货';
$_LANG['os'][10] = '退款';
$_LANG['os'][11] = '取消';

//删除某个物流单号
if ($_ACT == 'del_track')
{
	$sn   = empty($_GET['sn'])?'':$_GET['sn'];	
	$is_display_track_goods = empty($_GET['is_all_shipped'])?0:intval($_GET['is_all_shipped']);	//是否是订单的最后一个物流单号，在商城订单详情页显示物流单号的商品明细（0:不显示，1：显示）
	
	$sql = "delete from  ".SHIPDETAILS." where shipping_no = '".$sn."' and order_sn ='$_ORN' ";
	$db->query($sql);
	echo $sn.' deleted';
	$log_info = $_USR." $_ORN 删除运单号$sn  ";
}else{	
	$log_info = empty($order_info) ? '' : $_USR."同步更新订单 $_ORN ".$_LANG['os'][$order_info['order_status']].' ->'.$_LANG['os'][$order_status];
}

if($_ACT){	
	$sql = 'INSERT INTO ' . ALOGS . ' (log_time, user_id, log_info, ip_address) ' .
			" VALUES ('" . gmtime() . "', '0', '" . addslashes($log_info) . "', '" . real_ip() . "')";
	$db->query($sql);
}

function reduce_stock($order_number){  //减库存
	global $db;
	$sql = "select g.goods_id,g.goods_number as kucun,og.goods_number as salenumber from eload_order_goods og ,eload_order_info oi,eload_goods g where og.order_id=oi.order_id and og.goods_id=g.goods_id and order_status <>3 and g.goods_number<100 and g.goods_number>0 and oi.order_sn='$order_number'";
	$order = $db->arrQuery($sql);
	foreach ($order as $o){
		if($o['kucun'] >0 && $o['kucun']<100){
			$new_kucun = $o['kucun']-$o['salenumber'];
			if($new_kucun<0)$new_kucun=0;
			$db->query("update eload_goods set goods_number = $new_kucun where goods_id=".$o['goods_id']);
		}
	}
}

//计算订单有效的积分数
function caculate_order_get_point($order_sn){
	require_once('../lib/lib.f.transaction.php');
	global $db;
	$order_info = $GLOBALS['db']->selectInfo("select order_id,point_money,order_sn,promotion_code from ".ORDERINFO." where order_sn='$order_sn'");
    //判断是不是代金券的
	$sql = "select is_applay from eload_promotion_code where code = '".$order_info['promotion_code']."'";
    $is_applay = $GLOBALS['db']->getOne($sql);
	if(is_array($order_info)&&$order_info['point_money'] == 0 && empty($is_applay)){	
	    $sql = "select order_id from ".ORDERINFO." where order_sn = '$order_sn' ";
	    $order_id = $db->getOne($sql);
	    if ($order_id === false){
			return 0;
	     }
		/* 订单详情 */	     	    
		/*	   
		$order = get_order_detail($order_id, 0);	
		return $order['goods_amount'];
		*/	     
		
		/*获取订单返回的积分 
	     * 使用当前产品表中的该产品积分比率(point_rate)乘以订单产品表中的产品价格(goods_price
	     * */
		$sql = "SELECT o.goods_price as product_price, o.goods_number AS goods_number, g.point_rate FROM " . ODRGOODS . " AS o " . "LEFT JOIN " . GOODS . " AS g ON o.goods_id = g.goods_id  WHERE o.order_id = {$order_id} ";
		$arr = $db->arrQuery ( $sql );
		$pointtotal = '';
		foreach ( $arr as $row ) {
			$pointtotal += price_format ( $row ['product_price'] * $row ['goods_number'] * $row ['point_rate'] );
		}	
		return $pointtotal;
	}
	else {
		return 0;
	}		
}

exit;
?>