<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/syn_public_fun.php');


//$content =  var_export($_POST, true);
//file_put_contents(realpath('post.txt'),$content);
//exit;

$keys_code = empty($_POST['keys_code'])?'':$_POST['keys_code'];
if ($keys_code!=$_CFG['keys_code']){die('Error,key code error');}


$goods_id = 0;
$cat_id= 0;
$yuan_shop_price = 0;
$goods_sn   = !empty($_POST['goods_sn']) ? $_POST['goods_sn'] : '';

if ($goods_sn!=''){
	$sql = "select goods_id,cat_id,shop_price from ".GOODS." WHERE  goods_sn = '$goods_sn' ";
	$goods_info = $db->selectinfo($sql);
	$goods_id   = $goods_info['goods_id'];
	$cat_id     = $goods_info['cat_id'];
	$yuan_shop_price = $goods_info['shop_price'];
}

if (!$goods_id) die('Can not find the corresponding products');

/* 处理商品数据 */
$shop_price = !empty($_POST['shop_price']) ? floatval($_POST['shop_price']) : 0;
$chuhuo_price = $shop_price;
if(!$shop_price) die('同步失败，出货价格不能为零！');

$goods_weight = !empty($_POST['goods_weight']) ? floatval($_POST['goods_weight'] ): 0;
$goods_state = !empty($_POST['goods_state']) ? intval($_POST['goods_state'])  : 0;
$is_on_sale = ($goods_state == 1)?1:0;


$fenleiArr      = get_zhuijia_price_and_fenlei_bili($cat_id,$chuhuo_price);  //根据出货价取出相应的比例，追加价格，个数分级
$grade          = $fenleiArr['bili'];   //比例 1.27|1.25|1.24|1.23
$fenji          = $fenleiArr['grade'];  //比例 1|2---9|10-49|50---max
$zhuijia_price  = round(($fenleiArr['zhuijia_price']/HUILV),2); //5

$rate = explode('|',$grade);

//转成美元
$shop_price = round(($shop_price/HUILV),2);
$shipping_fee = get_shipping_fee($shop_price,$goods_weight);
$shop_price = round($shop_price * $rate[0],2);  //加追加价格

$import_url = 'http://www.davismicro.com.cn/';
//$import_url = 'http://support.davismicro.com/';
//$import_url = 'http://www.stock.com/';

$sql = "UPDATE " . GOODS . " SET ";

//是否需要同步更新价格
$xianshop_price = $shop_price + $shipping_fee  + $zhuijia_price;
if ($xianshop_price != $yuan_shop_price){
	$market_price = get_market_price($xianshop_price);
	$sql .= " shop_price = '$xianshop_price', market_price = '$market_price', chuhuo_price = '$chuhuo_price', ";
}


if ($goods_state == 2 || $goods_state == 3){
	$sql .= " goods_number = '0', " ;
}

$sql .= "goods_weight = '$goods_weight'," .
		//"is_on_sale = '$is_on_sale', " .
		"update_user = '$_POST[update_user]', ".
		"last_update = '". gmtime() ."'  ".
		"WHERE goods_id = '$goods_id' LIMIT 1";
$db->query($sql);
/* 商品编号 */


if ($xianshop_price != $yuan_shop_price){
	$fenjiArr = explode('|',$fenji);
	$_POST['volume_number'][] = $fenjiArr[0]; 
	$_POST['volume_number'][] = $fenjiArr[1]; 
	$_POST['volume_number'][] = $fenjiArr[2]; 
	$_POST['volume_number'][] = $fenjiArr[3]; 
	

	$_POST['volume_price'][]  = $shop_price  + $zhuijia_price + $shipping_fee; 
	$_POST['volume_price'][]  = round(($shop_price*$rate[1])/$rate[0],2) + $zhuijia_price + $shipping_fee; 
	$_POST['volume_price'][]  = round(($shop_price*$rate[2])/$rate[0],2) + $zhuijia_price + $shipping_fee; 
	$_POST['volume_price'][]  = round(($shop_price*$rate[3])/$rate[0],2) + $zhuijia_price + $shipping_fee; 
	
	if (isset($_POST['volume_number']) && isset($_POST['volume_price']))
	{
		$temp_num = array_count_values($_POST['volume_number']);
		foreach($temp_num as $v)
		{
			if ($v > 1)
			{
				sys_msg("优惠数量重复！", 1, array(), false);
				break;
			}
		}
		handle_volume_price($goods_id, $_POST['volume_number'], $_POST['volume_price']);
	}
}



echo 'success';
exit();
?>