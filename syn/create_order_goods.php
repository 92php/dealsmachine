<?php
set_time_limit(0);
define('INI_WEB', true);
$_BEGINTIME = microtime(TRUE);
require('../lib/global.php');              //引入全局文件



$con_lei   = array('393','99','1162','1192','1202','1111111');
$sql_limit = array('260','250','660','360','255','200');
$for_cishu = array('70','70','920','250','372','260');
$goods_num = array('3','5','9','2','4','3');

//http://www.dealsmachine.com/Wholesale-Outdoors-Sports-b-1192.html
//http://www.dealsmachine.com/bigclass1202.html

$ck = 1;
echo $con_lei[$ck];
$cat_id = $con_lei[$ck]; 
$children = ' 1 ';//get_children($cat_id);

$sql = "select AVG(shop_price) as vv,sum(shop_price) as ss  from (select shop_price from ".GOODS." as g where  g.is_on_sale = 1   AND  g.is_delete = 0 AND $children  and g.is_alone_sale = 1  order by click_count desc limit ".$sql_limit[$ck].") a ";
$pingjun = $db->selectinfo($sql);

echo '均价：'.$pingjun['vv'].' 总价：'.$pingjun['ss'];
echo '<br>';



$sql = "select  goods_id,goods_sn,goods_name,goods_title,shop_price,click_count,market_price from ".GOODS." as g where  g.is_on_sale = 1   AND  g.is_delete = 0 AND $children  and g.is_alone_sale = 1 order by click_count desc limit ".$sql_limit[$ck]."";
$goodsArr = $db->arrQuery($sql);
//print_r($goodsArr);

//exit;

$amount = 0;
$znum = 0;
for($i=1;$i<=$for_cishu[$ck];$i++)
{
	$goods_key = rand(0,($sql_limit[$ck] - 1 ));
	
	$goods = $goodsArr[$goods_key];
	
	$goods_number =  rand(1,$goods_num[$ck]);
	$goods_name   = addslashes($goods['goods_title']);
	$goods_sn = $goods['goods_sn'];
	$goods_id = $goods['goods_id'];
	$market_price = $goods['market_price'];
	$goods_price = $goods['shop_price'];
	
	//echo $goods_key.' '. $goods_number.'<br>';
	
	$sql = "insert into eload_order_goods_temp (order_id,goods_id,goods_name,goods_sn,goods_number,market_price,goods_price,goods_attr,lei) values ('$cat_id','$goods_id','$goods_name','$goods_sn','$goods_number','$market_price','$goods_price',' ','$cat_id') ";
	 //echo $sql.'<br>';
	$db->query($sql);
	$znum = $znum + $goods_number;
	$amount =  $amount + $goodsArr[$goods_key]['shop_price'] * $goods_number;
} 


echo $znum.'<br>';
echo $amount.'<br>';



exit;


echo '重复 '.$cn.'  成功'.$ln.'<br>';
$page++;
echo "<META HTTP-EQUIV='Refresh' Content='1;URL=?page=".$page."&n=".$n."'>";
?>

