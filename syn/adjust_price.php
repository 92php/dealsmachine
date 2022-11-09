<?php
define('INI_WEB', true);
require_once('../lib/global.php');              //引入全局文件
require_once('../lib/time.fun.php');
require_once('../lib/syn_public_fun.php');

//$content =  var_export($_REQUEST, true);
//file_put_contents(realpath('post.txt'),$content);

$sql = "select g.goods_id,goods_sn,shop_price,chuhuo_price,cat_id,is_free_shipping,goods_weight,g.chuhuo_price from eload_goods g ,eload_volume_price v where g.goods_id=v.goods_id and volume_number='1' and g.shop_price <>v.volume_price limit 20 ";
$arr_goods=$db->arrQuery($sql);

foreach($arr_goods as $k=>$g){
	$goods_sn = $g['goods_sn'];
	$sa_user = 'system';
	
	//$goods_state = empty($_REQUEST['goods_state'])?0:intval($_REQUEST['goods_state']);
	$goods_weight = $g['goods_weight'];
	
	//$shop_price = empty($_REQUEST['shop_price'])?0:floatval($_REQUEST['shop_price']);
	$chuhuo_price = $g['chuhuo_price'];
	$shop_price =$chuhuo_price;
	
	//if ($goods_sn!=''){
		//$sql = "select goods_id,shop_price,chuhuo_price,cat_id,is_free_shipping from ".GOODS." WHERE goods_sn = '$goods_sn' ";
		//$goodsArr = $db->selectinfo($sql);
		$goods_id = $g['goods_id'];
		$cat_id = $g['cat_id'];
		$yuan_shop_price = $g['chuhuo_price'];
	    $is_free_shipping=$g['is_free_shipping'];
	//}
	
	//echo print_r(goods_sn);
	
	if ($goods_sn!='' &&  $goods_id!=0 &&!empty($chuhuo_price)){
		
		if ($goods_id>0 ){
		
			//获取价格分级；
			//
			if ($chuhuo_price){
				
				$fenleiArr      = get_zhuijia_price_and_fenlei_bili($cat_id,$shop_price);  //根据出货价取出相应的比例，追加价格，个数分级
				$grade          = $fenleiArr['bili'];   //比例 1.27|1.25|1.24|1.23
				//echo $grade&"<br>";
			
				$fenji          = $fenleiArr['grade'];  //比例 1|2---9|10-49|50---max
				$zhuijia_price  = round(($fenleiArr['zhuijia_price']/HUILV),2); //5
				
				$rate = explode('|',$grade);
				if(empty($grade)||empty($fenji))continue;
				//转成美元
				$shop_price = round(($shop_price/HUILV),2);
	
	            $shipping_fee=0;
	            if($is_free_shipping==1)//如果是免运费,+平邮价格
	            {
	               $shipping_fee = get_shipping_fee($shop_price,$goods_weight);
	            }
	
				$shop_price = round($shop_price * $rate[0],2);  //加追加价格
				
				$first_price = $shop_price + $shipping_fee + $zhuijia_price;
				$market_price = get_market_price($first_price);
				
				$sql = " update ".GOODS." set shop_price = '$first_price'  where goods_sn = '".$goods_sn ."' ";
				//echo $sql;
				//exit;
				$db->query($sql);
				
				$fenjiArr = explode('|',$fenji);
				$_REQUEST = array();
				
				$_REQUEST['volume_number'][] = $fenjiArr[0]; 
				$_REQUEST['volume_number'][] = $fenjiArr[1]; 
				$_REQUEST['volume_number'][] = $fenjiArr[2]; 
				$_REQUEST['volume_number'][] = $fenjiArr[3];			
			
				$_REQUEST['volume_price'][]  = $shop_price  + $zhuijia_price + $shipping_fee; 
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
					echo $goods_sn.';'.$goods_id.';'.$chuhuo_price.';'.$first_price.'<br>';
					//print_r($_REQUEST);
					handle_volume_price($goods_id, $_REQUEST['volume_number'], $_REQUEST['volume_price']);
				}
			}
			
			
						admin_log('',$sa_user , '同步修改'.$goods_sn." 价格 $yuan_shop_price => ".$first_price);
			//if ($goods_state){			
				//$is_on_sale = ($goods_state == 1)?1:0;
				//$sql = " update ".GOODS." set is_on_sale = $is_on_sale where goods_sn = '".$goods_sn ."' ";
				//$db->query($sql);
			//}
			
		}
		//exit;
		
	}
}

echo "success:".$yuan_shop_price;//";// => $first_price

?>