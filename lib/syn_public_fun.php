<?php
/**
 * 保存某商品的优惠价格
 * @param   int     $goods_id    商品编号
 * @param   array   $number_list 优惠数量列表
 * @param   array   $price_list  价格列表
 * @return  void
 */
function handle_volume_price($goods_id, $number_list, $price_list)
{	
	foreach ($number_list AS $key => $num)
    {
    	if(empty($num))sys_msg('区间数量有误，请检查。');
    }
	foreach ($price_list AS $key => $price)
    {
    	$price = floatval($price);
    	if(!$price)sys_msg('区间价格有误，请检查。');
    }      
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

function cmp($a, $b)
{
    if ($a == $b) {
        return 0;
    }
    return ($a > $b) ? -1 : 1;
}

function get_market_price($price){
	return round(($price*1.2 + (rand(0,1)*$price)/2),2);
}

//运费重量
function get_weight($weight){
	if ($weight > 0.5 ) {
		$int = intval($weight); //取整
		$flt = $weight - $int;  //取小数部分
		
		if($flt >= 0.5 ) {
		   return $int + 1;
		}else{
		   return $int + 0.5;
		}
	}else{
		return $weight;
	}
}

//$shop_price   单位美元
//$goods_weight 单位KG
/*function get_shipping_fee($shop_price,$goods_weight){
    $shipping_fee = 0;
	if ($shop_price<40){ //40美金以下的
		$shipping_fee = $goods_weight*85+0.5;
	}else{
		$shipping_fee = $goods_weight*105+13;
	}	
	$shipping_fee = round(($shipping_fee/HUILV),2); //转成美元
	return $shipping_fee;
}*/

//免邮，加的是平邮价格，不分国家
function get_shipping_fee($shop_price,$goods_weight){
    $shipping_fee = 0;
    $shipping_fee = ($goods_weight*95)/HUILV;
    return $shipping_fee;
}

//分类ID
//出货价格
//返回数组 array(bili,zhuijia_price,grade)
function get_zhuijia_price_and_fenlei_bili($cat_id,$chuhuo_price){
	global $cur_lang, $default_lang;
	$ReturnArr = array('bili'=>'','zhuijia_price'=>0,'grade'=>'');
	if($cur_lang != $default_lang){
		$catArr =  read_static_cache($cur_lang.'_category_c_key',2);
	}else {
		$catArr =  read_static_cache('category_c_key',2);
	}		
	$catArr = $catArr[$cat_id];
	if(empty($catArr)){
		echo 'category not found';
		exit();
	}
	if($catArr['zhuijia_price']===null)$catArr['zhuijia_price']=0;
	$zhekouArr        = explode('<BR>',strtoupper($catArr['zhekou']));       
	$chuhuo_qujianArr = explode('<BR>',strtoupper($catArr['chuhuo_qujian']));
	$zhuijia_priceArr = explode('<BR>',strtoupper($catArr['zhuijia_price']));		
	$mykey = 0;
	foreach($chuhuo_qujianArr as $key => $val){
		$priceArr = explode('-',$val);
		if ($chuhuo_price>$priceArr[0] &&!empty($priceArr[1])&& $chuhuo_price<=$priceArr[1]){
			$mykey = $key;
			break;
		}
	}
	$ReturnArr['grade']         = $catArr['grade'];
	$ReturnArr['bili']          = $zhekouArr[$mykey];
	$ReturnArr['zhuijia_price'] = $zhuijia_priceArr[$mykey];
	unset($catArr);
	return $ReturnArr;
}

/**
 * 根据规则修改价格
 *
 * @author        fangxin
 * @date          2013-10-08 AM
 * @last modify   2013-10-08 AM
 * @param mixed   $shop_price    商品价格
 * @return int 
 *
 * 规则如下：
 * 新价格=原售价取整 + x
 *	1）原售价在10.00-29.99美元时
 *	   a）如果原售价取整后余数是0-0.49, x=0.49
 *	   b）如果原售价取整后余数是0.5-0.99 x=0.99
 *	2）原售价在30.00美元以上时 x=0.99 
 *  3) 原售价小于10.00美元不变
 *
 */
function format_price($price) {
	if(!empty($price)) {
		$price_suffix_array = explode(".", $price);
		$price_suffix = $price_suffix_array[1];
		if(strlen($price_suffix) == 1) {
			$price_suffix = $price_suffix * 10;
		}
		if($price < 10.00) {
			$price = $price_suffix_array[0] . '.' . $price_suffix;
			return $price;
		}elseif($price >= 10.00 and $price <= 30.00) {
			if($price_suffix < 50 and $price_suffix >= 0) {
				$price_suffix = 49;
			}elseif($price_suffix < 99 and $price_suffix >= 50) {
				$price_suffix = 99;
			}
		}elseif($price > 30.00) {
			$price_suffix = 99;
		}
		$price = $price_suffix_array[0] . '.' . $price_suffix;
	}
	return $price;
}
?>