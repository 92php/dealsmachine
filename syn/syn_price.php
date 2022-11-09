<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/syn_public_fun.php');
//$content =  var_export($_REQUEST, true);
//file_put_contents(realpath('post.txt'),$content);
$keys_code = empty($_REQUEST['keys_code'])?'':$_REQUEST['keys_code'];
if ($keys_code!=$_CFG['keys_code']){die('Error,key code error');}
$goods_sn = empty($_REQUEST['goods_sn'])?'':$_REQUEST['goods_sn'];
$sa_user = empty($_REQUEST['sa_user'])?'':$_REQUEST['sa_user'];
$goods_state = empty($_REQUEST['goods_state'])?0:intval($_REQUEST['goods_state']);
$goods_weight = !empty($_REQUEST['goods_weight']) ? floatval($_REQUEST['goods_weight'] ): 0;
$shop_price = empty($_REQUEST['shop_price'])?0:floatval($_REQUEST['shop_price']);
$chuhuo_price = $shop_price;
if ($goods_sn!=''){
	$sql = "select goods_id,shop_price,chuhuo_price,cat_id,is_free_shipping,promote_price,promote_lv from ".GOODS." WHERE goods_sn = '$goods_sn' ";
	$goodsArr = $db->selectinfo($sql);
	$goods_id = $goodsArr['goods_id'];
	$cat_id = $goodsArr['cat_id'];
	$yuan_shop_price = $goodsArr['chuhuo_price'];
    $is_free_shipping=$goodsArr['is_free_shipping'];
    $promote_price = $goodsArr['promote_price'];
    $promote_lv = $goodsArr['promote_lv'];		
}

if ($goods_sn!='' &&  $goods_id!=0){	
	if ($goods_id>0 ){	
		//获取价格分级；
		//
		if ($shop_price){			
			$fenleiArr      = get_zhuijia_price_and_fenlei_bili($cat_id,$shop_price);  //根据出货价取出相应的比例，追加价格，个数分级
			$grade          = $fenleiArr['bili'];   //比例 1.27|1.25|1.24|1.23
			$fenji          = $fenleiArr['grade'];  //比例 1|2---9|10-49|50---max
			$zhuijia_price  = round(($fenleiArr['zhuijia_price']/HUILV),2); //5			
			$rate = explode('|',$grade);			
			//转成美元
			$shop_price = round(($shop_price/HUILV),2);
			$cost_shop_price = $shop_price;
            $shipping_fee=0;
            if($is_free_shipping==1)//如果是免运费,+平邮价格
            {
               $shipping_fee = get_shipping_fee($shop_price,$goods_weight);
            }
			$shop_price = round($shop_price * $rate[0],2);  //加追加价格			
			if(empty($shop_price)){
				echo "price can't be zero,pls check";
				exit();
			}						
			$first_price = format_price($shop_price + $shipping_fee + $zhuijia_price);  //修改销售价 fangxin 2013/10/14
			$market_price = get_market_price($first_price);								
			$sql = " update ".GOODS." set market_price = '$market_price',shop_price = '$first_price',chuhuo_price = '$chuhuo_price' ";
			if(!empty($promote_lv)){
				$promote_price = round($cost_shop_price*$promote_lv+$shipping_fee,2);
				$promote_price = format_price($promote_price); //修改促销价 fangxin 2013/10/14
				$sql .= " , promote_price = $promote_price";
			}			
			$sql .= " where goods_sn = '".$goods_sn ."' ";
			$db->query($sql);			
			$fenjiArr = explode('|',$fenji);
			$_REQUEST = array();			
			$_REQUEST['volume_number'][] = $fenjiArr[0]; 
			$_REQUEST['volume_number'][] = $fenjiArr[1]; 
			$_REQUEST['volume_number'][] = $fenjiArr[2]; 
			$_REQUEST['volume_number'][] = $fenjiArr[3];					
			$_REQUEST['volume_price'][]  = format_price($shop_price  + $zhuijia_price + $shipping_fee);  //修改阶梯价 fangxin 2013/10/14
			$_REQUEST['volume_price'][]  = round(($shop_price*$rate[1])/$rate[0],2) + $zhuijia_price + $shipping_fee; 
			$_REQUEST['volume_price'][]  = round(($shop_price*$rate[2])/$rate[0],2) + $zhuijia_price + $shipping_fee; 
			$_REQUEST['volume_price'][]  = round(($shop_price*$rate[3])/$rate[0],2) + $zhuijia_price + $shipping_fee; 			
			if (isset($_REQUEST['volume_number']) && isset($_REQUEST['volume_price']))
			{
				$temp_num = array_count_values($_REQUEST['volume_number']);
				foreach($temp_num as $v)
				{
					if ($v > 1)
					{
						sys_msg("优惠数量重复！", 1, array(), false);
						break;
					}
				}
				handle_volume_price($goods_id, $_REQUEST['volume_number'], $_REQUEST['volume_price']);
			}
			admin_log('',$sa_user , '同步修改'.$goods_sn." 价格 $yuan_shop_price => ".$first_price);
		}	
		//if ($goods_state){			
			//$is_on_sale = ($goods_state == 1)?1:0;
			//$sql = " update ".GOODS." set is_on_sale = $is_on_sale where goods_sn = '".$goods_sn ."' ";
			//$db->query($sql);
		//}		
	}	
}
echo "success:".$yuan_shop_price;//";// => $first_price
?>

