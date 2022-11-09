<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/lib_order.php');

$_ACT   = empty($_GET['act'])?'':trim($_GET['act']);
$_ORN   = empty($_GET['orn'])?'':trim($_GET['orn']);

$order_status = 0;





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
	break;


	default:
	break;
}



if ($order_status &&  $_ACT == 'add_track')
{
	$_Arr['shipping_name'] = empty($_GET['st'])?'':$_GET['st'];
	$_Arr['shipping_no']   = empty($_GET['sn'])?'':$_GET['sn'];
	$_Arr['order_sn']      = $_ORN;
	$_Arr['add_time']      = gmtime();
	$_Arr['demo']          = empty($_GET['demo'])?'':$_GET['demo'];
	
	$sql = "select count(*) from ".ORDERINFO." where order_sn='".$_ORN."'";
	if ($db->getOne($sql)){
	    $sql = "select count(*) from ".SHIPDETAILS." where shipping_no='".$_Arr['shipping_no']."'";
		if ($db->getOne($sql)){
			
			//$db->autoExecute(SHIPDETAILS, $_Arr);
			//if ($order_status){
			//	$sql = " order_status = '$order_status' "; 
			//	$db->update(ORDERINFO,$sql," order_sn = '".$_ORN."'");
			//}
			echo 'yes_order';
		}else{
			echo 'no_order';
		}
	}
}




exit;
?>