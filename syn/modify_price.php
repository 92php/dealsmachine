<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/syn_public_fun.php');


$page   = empty($_GET['page'])?1:intval($_GET['page']);
$where  = " where  g.is_delete = 0 and left(g.goods_sn,2)<>'MB' ";
$pernum = 10;
$total_record = $db->getOne("SELECT count(*) FROM " . GOODS ." as g $where ");
$total_page   = ceil($total_record/$pernum);                                    //zong ye shu
$start        = ($page - 1) * $pernum;

if($page>$total_page){
	echo "$total_record 完成 ";
	exit;
}else{
	echo "总计：".$total_record." 当前第 $page 页，已经处理了$start 个产品。 <br>";
}
		

$sql = "SELECT g.shop_price,g.cat_id,g.goods_id,g.goods_sn,g.goods_weight FROM " . GOODS ." as g  $where   LIMIT $start ,$pernum "; 
$res = $GLOBALS['db']->arrQuery($sql);

if($cur_lang != $default_lang){
	$catArr =  read_static_cache($cur_lang.'_category_c_key',2);
}else {
	$catArr =  read_static_cache('category_c_key',2);
}

foreach ($res as $goodsArr){
	$_POST = array();
	
	$goods_id = $goodsArr['goods_id'];
	$goods_sn = $goodsArr['goods_sn'];
	$cat_id = $goodsArr['cat_id'];
	$yuan_shop_price = $goodsArr['shop_price'];
	$goods_weight = $goodsArr['goods_weight'];
	
	$yuan_yunfei = round((get_weight($goods_weight)*120+15)/6.6,2);  
	$gongshijiage  = round(($goods_weight*110+15)/6.6,2); //现在的运费

	$first_price = $yuan_shop_price - $yuan_yunfei + $gongshijiage;
	$market_price = get_market_price($first_price);
	
	$second_gongshijiage =  round(($goods_weight*90+5)/6.6,2); //3个以上的运费
	$jinhuojia = $first_price - $gongshijiage;
	
	echo $goods_sn.'重量:'.$goods_weight.' 原价：'.$yuan_shop_price.' 现价'.$first_price.' <br>';
	
	$sql = " update ".GOODS." set shop_price = '$first_price'  where goods_id = '".$goods_id ."' ";
	$db->query($sql);
	
	if (empty($catArr[$cat_id])){
		echo '分类ID：'.$cat_id.'不存在！';
	}else{
		$grade  = $catArr[$cat_id]['zhekou'];
		$fenji  = $catArr[$cat_id]['grade'];
		if (strpos($grade,'|')!==false){
			$rate = explode('|',$grade);
		 }	
		$fenjiArr = explode('|',$fenji);
		$_POST['volume_number'][] = $fenjiArr[0]; 
		$_POST['volume_number'][] = $fenjiArr[1]; 
		$_POST['volume_number'][] = $fenjiArr[2]; 
		$_POST['volume_number'][] = $fenjiArr[3]; 
		
		$_POST['volume_price'][]  = $first_price; 
		$_POST['volume_price'][]  = round(($jinhuojia*$rate[1])/$rate[0],2) + $second_gongshijiage;//round(($first_price*$rate[1])/$rate[0],2); 
		$_POST['volume_price'][]  = round(($jinhuojia*$rate[2])/$rate[0],2) + $second_gongshijiage; //round(($first_price*$rate[2])/$rate[0],2); 
		$_POST['volume_price'][]  = round(($jinhuojia*$rate[3])/$rate[0],2) + $second_gongshijiage; //round(($first_price*$rate[3])/$rate[0],2); 

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
}

$page++;
echo "<META HTTP-EQUIV='Refresh' Content='1;URL=modify_price.php?page=".$page."'>";
exit;

?>

