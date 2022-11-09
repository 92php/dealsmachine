<?php
set_time_limit(0);
define('INI_WEB', true);
$_BEGINTIME = microtime(TRUE);
require('../lib/global.php');              //引入全局文件
require('../lib/time.fun.php');  
require(ROOT_PATH . 'lib/lib.f.order.php');





$xindb = new MySql('127.0.0.1', 'root', 'everOe4r5t', 'ahappydeal_8_24');


$pernum       = 10;
$page   = empty($_GET['page'])?1:intval($_GET['page']);
$total_record = $xindb->getOne("select count(*) from `eload_order_info` where add_time > 1314106607 ");
$total_page   = ceil($total_record/$pernum);
$start        = ($page - 1) * $pernum;


echo '总共'.$total_record.'<br>';


$sql = "select * from `eload_order_info` where add_time > 1314106607   LIMIT   $start ,$pernum ";
$orderArr = $xindb->arrQuery($sql);
foreach($orderArr as $row){
	
	//到正式上取user_id
	$user_id = $db->getOne("select user_id from eload_users where email = '".$row['email']."'");
	$order_id = $db->getOne("select order_id from eload_order_info where order_sn = '".$row['order_sn']."'");
	
	 
	 $goodsArr = $xindb->arrQuery("select * from " .ODRGOODS ." where order_id = '".$row['order_id']."' ");
	 $mubiao =   $db->getOne("select count(*) from " .ODRGOODS ." where order_id = '".$row['order_id']."' ");
	 
	 $gnum = count($goodsArr);
	 
	 
	 if($gnum>1 && $mubiao != $gnum){	 
	   echo  $row['order_sn'].' '.'共有'.count($goodsArr).'个产品<br>';
			 
			 $sql = "select goods_id from ".ODRGOODS." where order_id = '".$order_id."' ";
			 $egoods_id = $db->getOne($sql);
			 foreach($goodsArr as $gArr){
				 if( $egoods_id != $gArr['goods_id']){
				   unset($gArr['rec_id']);		 
				   $gArr['order_id'] = $order_id;
				   $db->autoExecute(ODRGOODS, $gArr, 'INSERT');	
				 }
				   
			 }
	 }
		 //xie zai order_id
		 
	 
		//echo $row['order_sn'].' '.$row['email'].' '.$row['user_id'].'  ' . $user_id.'  '.$order_num.' <br>';
	
	//print_r($row);
}


//exit;
$page++;
echo "<meta http-equiv=refresh content='5;URL=?page=$page'>";
//echo  $xindb->getOne($sql).'<br>';





exit;



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

