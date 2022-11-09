<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
$now_time = gmtime();

$sql = "select goods_title,goods_id,is_groupbuy,shop_price,market_price,groupbuy_price,groupbuy_final_price,groupbuy_people_first_number,
groupbuy_people_final_number,groupbuy_start_date,goods_sn,groupbuy_end_date,goods_img,groupbuy_ad_desc,groupbuy_bought,groupbuy_chengjiao_price  from eload_goods where is_groupbuy = 1 and 
groupbuy_end_date > ".$now_time." and groupbuy_start_date < ".$now_time." and is_delete =0  order by groupbuy_start_date desc ";
// echo $sql;
$goodsArr = $db->arrQuery($sql);
foreach($goodsArr as $row){
	$buyers = get_groupbuyer($row['goods_id']);
	
	$MuBiaoNum = $row['groupbuy_people_first_number'] - $buyers;
	
	$thistime =  $row['groupbuy_end_date'] - $now_time;
		
	//if ($MuBiaoNum > rand(0,3) ){
	
		$parent = array(
			'user_id'       => '0',
			'session_id'    => 'system'.local_date('Y-m-d H:i:s'),
			'custom_size'   => '',
			'goods_id'      => $row['goods_id'],
			'goods_sn'      => addslashes($row['goods_sn']),
			'goods_name'    => addslashes($row['goods_title']),
			'market_price'  => $row['market_price'],
			'goods_price'   => $row['shop_price'],
			'goods_attr'    => '',
			'goods_number'  => '1',
			'goods_attr_id' => '',
			'is_groupbuy'   => $row['is_groupbuy'],
			'attr_goods_sn' => '',
			'addtime'       => $now_time,
		);
		
		$GLOBALS['db']->autoExecute(CART, $parent, 'INSERT');
	//}
	

}




echo 'success';
exit();
?>