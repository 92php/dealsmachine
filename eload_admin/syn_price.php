<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');

$shop_price = empty($_POST['jinhuo_price'])?0:floatval($_POST['jinhuo_price']);
$is_on_sale = empty($_POST['is_on_sale'])?0:intval($_POST['is_on_sale']);

$goods_sn   = !empty($_POST['goods_sn']) ? $_POST['goods_sn'] : '';
$shop_price = round($shop_price,2);
$cat_id     = 0;


if ($goods_sn!=''){
	$sql = "select goods_id,shop_price,cat_id from ".GOODS." WHERE is_free_shipping = 0 AND  goods_sn = '$goods_sn' ";
	$goodsArr = $db->selectinfo($sql);
	$goods_id = $goodsArr['goods_id'];
	$cat_id = $goodsArr['cat_id'];
	$yuan_shop_price = $goodsArr['shop_price'];
}


if ($goods_sn!='' &&  $goods_id!=0){
	
	
	//获取价格分级；
	$catArr = read_static_cache('category_c_key',2);
	$grade  = $catArr[$cat_id]['zhekou'];
	
	if (strpos($grade,'|')!==false){
		$rate = explode('|',$grade);
	}
	$first_price = round($shop_price*$rate[0],2);
    $market_price = $first_price+10;
	$sql = " update ".GOODS." set market_price = '$market_price',shop_price = '$first_price',is_on_sale ='$is_on_sale' where goods_id = ".$goods_id ." and  is_free_shipping = 0 AND  goods_sn = '$goods_sn' ";
	$db->query($sql);
	
	$_POST['volume_number'][] = '1'; 
	$_POST['volume_number'][] = '2 ---- 9'; 
	$_POST['volume_number'][] = '10 ---- 49'; 
	$_POST['volume_number'][] = '50 ----- max'; 
	
	$_POST['volume_price'][]  = $first_price; 
	$_POST['volume_price'][]  = round(($first_price*$rate[1])/$rate[0],2); 
	$_POST['volume_price'][]  = round(($first_price*$rate[2])/$rate[0],2); 
	$_POST['volume_price'][]  = round(($first_price*$rate[3])/$rate[0],2); 
	
	
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
	
	echo $goods_sn.'价格同步完成,价格由原来的'.$yuan_shop_price.'改成了'.$first_price.'<br>';
}else{
	echo '<font color="#ff0000">'.$goods_sn.'不存在,同步失败。</font><br>';
}










/**
 * 保存某商品的优惠价格
 * @param   int     $goods_id    商品编号
 * @param   array   $number_list 优惠数量列表
 * @param   array   $price_list  价格列表
 * @return  void
 */
function handle_volume_price($goods_id, $number_list, $price_list)
{
    $sql = "DELETE FROM " . VPRICE .
           " WHERE price_type = '1' AND goods_id = '$goods_id'";
    $GLOBALS['db']->query($sql);

    /* 循环处理每个优惠价格 */
    foreach ($price_list AS $key => $price)
    {
        /* 价格对应的数量上下限 */
        $volume_number = $number_list[$key];

        if (!empty($price))
        {
            $sql = "INSERT INTO " . VPRICE .
                   " (price_type, goods_id, volume_number, volume_price) " .
                   "VALUES ('1', '$goods_id', '$volume_number', '$price')";
            $GLOBALS['db']->query($sql);
        }
    }
}

?>

