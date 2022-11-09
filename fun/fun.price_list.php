<?php
/**
 * 商品分类
*/

if (!defined('INI_WEB')){die('访问拒绝');}

/* 获得请求的分类 ID */
$cat_id = 0;
$cat_id = empty($_GET['id'])?0:intval($_GET['id']);  
if ($cat_id != 0){$cat_id = intval($cat_id);
}else{ 
   header("Location: /$cur_lang_url"."m-page_not_found.htm");
   exit;

}


$price_max = isset($_GET['price_max']) && intval($_GET['price_max']) > 0 ? intval($_GET['price_max']) : 0;
$price_min = isset($_GET['price_min']) && intval($_GET['price_min']) > 0 ? intval($_GET['price_min']) : 0;


$my_cache_id = $cat_id . '-'.$price_max.'-'.$cur_lang.'-'.$price_min ;
$my_cache_id = sprintf('%X', crc32($my_cache_id));
if (!$Tpl->is_cached('goods.htm', $my_cache_id))
{
	
	require_once(ROOT_PATH . 'fun/fun.global.php');
	require_once(ROOT_PATH . 'fun/fun.public.php');
	require_once('lib/lib.f.goods.php');
	require_once('lib/class.page.php');
	//导航
	if($cur_lang != $default_lang){
		$typeArray =  read_static_cache($cur_lang.'_category_c_key',2);
	}else {
		$typeArray =  read_static_cache('category_c_key',2);
	}	
	if (empty($typeArray[$cat_id]["parent_id"])) {
	   header("Location: /$cur_lang_url"."m-page_not_found.htm");
	   exit;
	}
	$nav_title = getNavTitle($typeArray,$typeArray[$cat_id]["parent_id"]);
	
	$thisurl = creat_nav_url($typeArray[$cat_id]["url_title"],$cat_id);
	$cat_name = $typeArray[$cat_id]["cat_name"];
	$cat_url  =  $thisurl;
	$nav_title = $nav_title.' &raquo; <a href="'.$thisurl.'" title = "'.$typeArray[$cat_id]["cat_name"].'">'.$cat_name.'</a>';
	$Arr['nav_title']  =  $nav_title;
	
	
	
	//价格导航
	$children = get_children($cat_id);
	
	
	$Price_Arr = get_price_nav_arr($children,$cat_id,$typeArray);
	
	foreach($Price_Arr as $k => $val){
		$Price_Arr[$k]['goods_list'] = get_price_list($Price_Arr[$k][0],$Price_Arr[$k][1],$children);
		$Price_Arr[$k]['goods_len'] =  count($Price_Arr[$k]['goods_list']);
		$Price_Arr[$k]['cat_name'] =  $typeArray[$cat_id]["cat_name"];;
	}
	
	$same_cat = array();
	foreach($typeArray as $s => $v){
		if($v['parent_id'] == $typeArray[$cat_id]["parent_id"]){
	/*			$PriceStr  = $v["price_nav"];
				if (strpos($PriceStr,'|') !== false){
					$Price_Arr = explode('|',$PriceStr);
					foreach($Price_Arr as $k => $val){
						$Price_Arr[$k]        = explode('-',$val);
						$Price_Arr[$k]['url'] = '/'.$typeArray[$cat_id]["url_title"].'-'.$cat_id.'-'.$Price_Arr[$k][0].'-'.$Price_Arr[$k][1].'-Wholesale.html';
					}
				}
	*/		$same_cat[$s]['cat_name'] = $v['cat_name'];
			$same_cat[$s]["price_nav"] = $Price_Arr;
		}
	}
	$Arr['Price_Arr'] = $Price_Arr;
	
	$Arr['same_cat'] = $same_cat;
	
	
	
	
	
	
	$price_str = '';
	if ($price_min!=0) $price_str .= ' $'.$price_min;
	if ($price_max!=0) $price_str .= ' $'.$price_max;
	
	$is_sub_cat = 0; //等于0 为子类
	
	
	//seo标题
	$cat_name = $typeArray[$cat_id]["cat_name"];
	if($price_str!=''){
		$seo_title   = "China $price_str $cat_name Wholesale - ".$_CFG['shop_name'];  //大类
	}else{
		if($typeArray[$cat_id]["parent_id"] == 0){
			$seo_title   = 'China '.$cat_name.' Wholesale - '.$_CFG['shop_name'];  //大类
			$is_sub_cat = 1;
		}else{
			$bseo_title  = $typeArray[$typeArray[$cat_id]["parent_id"]]["cat_name"];
			$seo_title   = $bseo_title.' Wholesale China '.$cat_name.' Suppliers  - '.$_CFG['shop_name'];   //小类
		}
	}
	
	$Arr['is_sub_cat'] = $is_sub_cat;
	
	//seo关键字
	$seo_keyword = $typeArray[$cat_id]["keywords"];
	
	if($price_str!=''){
		$seo_keyword   = 'China '.$price_str.' '.$cat_name.' Wholesale ';  
	}else{
	
		$geshu  = 0;
		if ($seo_keyword == '') {
			
			$flag = false;
			foreach ($typeArray as $key => $val){
				if($typeArray[$key]["parent_id"] == $cat_id ){
					$flag = true;
				}
			}
			
			if($flag) {
				$cur_cat_id = $cat_id;
				$loop = 10;
			}else{
				$cur_cat_id = $typeArray[$cat_id]["parent_id"];
				$loop = 5;
			}
			
			foreach ($typeArray as $key => $val){
				if($typeArray[$key]["parent_id"] == $cur_cat_id && $geshu <$loop){
					$seo_keyword .= $typeArray[$key]['cat_name'].' Wholesale,';
					$geshu ++;
					if ($geshu >=$loop) break;
				}
			}
		}
	
	}
	$sub_cat_str =  str_replace('Wholesale','',$seo_keyword);
	$Arr['sub_cat_str'] = $sub_cat_str;
	$seo_desc    = $typeArray[$cat_id]["cat_desc"];
	
	if ($seo_desc == '') {
		if($price_str!=''){
			$seo_desc   = $_CFG['shop_name'].", who provide all kinds of high quality and low price $price_str $cat_name products Wholesale";  //价格描述
		}else{
			if($flag) {
				$seo_desc ="We are China $cat_name  wholesaler, who provide all kinds of high quality and low price  $cat_name  products.China ".$_CFG['shop_name']; //大类描述
			}else{
				$seo_desc ="China $cat_name Wholesale, Best quality and factory price $cat_name products Wholesale ".$_CFG['shop_name'];  //小类描述
			}
		}
	}
	
	$cat_url = creat_nav_url($typeArray[$cat_id]["url_title"],$cat_id);
	if($is_sub_cat == '0' && $price_str=='') {
		$parent_id = $typeArray[$cat_id]["parent_id"];
		$cat_parent_name = $typeArray[$parent_id]["cat_name"];
		$cat_parent_url  = creat_nav_url($typeArray[$parent_id]["url_title"],$parent_id);
		$Arr['cat_parent_name']  =  $cat_parent_name;
		$Arr['cat_parent_url']  =  $cat_parent_url;
	}
	
	$Arr['shop_name']  =  $_CFG['shop_name'];
	
	$Arr['cat_name']   =  $cat_name;
	$Arr['cat_url']   =  $cat_url;
	$Arr['seo_title'] =  $seo_title;
	$Arr['seo_keywords']   =  $seo_keyword;
	$Arr['seo_description']   =  $seo_desc;
}











function get_price_list($Price_min,$Price_max,$children){
	$price_str = '';
		if ($Price_min!=0) $price_str .= ' $'.$Price_min;
		if ($Price_max!=0) $price_str .= ' $'.$Price_max;
		$children = 
    $where = "g.is_on_sale = 1  AND ".
            "g.is_delete = 0 AND ($children)  and g.is_alone_sale = 1  ";
			
    if ($Price_min > 0)
    {
        $where .= " AND g.shop_price >= $Price_min ";
    }

    if ($Price_max > 0)
    {
        $where .= " AND g.shop_price <= $Price_max ";
    }
	
    $sql = 'SELECT g.goods_id, g.goods_title, g.goods_name_style,g.cat_id,g.url_title , g.market_price,g.goods_weight,g.is_free_shipping,g.goods_grid, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ' .
                " g.shop_price , g.promote_price, g.goods_type, g.promote_price ," .
                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
            'FROM ' . GOODS . ' AS g ' .
            "WHERE  $where  ORDER BY sort_order,goods_id desc limit 30 ";
    $res = $GLOBALS['db']->arrQuery($sql);
	$arr = array();
     foreach ($res as $row)
    {
		$arr[$row['goods_id']]['goods_id']         = $row['goods_id'];
		$arr[$row['goods_id']]['goods_title']       = $row['goods_title'];
        $arr[$row['goods_id']]['cat_id']           = $row['cat_id'];
        $arr[$row['goods_id']]['market_price']     = price_format($row['market_price']);
        $arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
       // $arr[$row['goods_id']]['promote_price']    = ($promote_price > 0) ? price_format($promote_price) : '';
        $arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
		$arr[$row['goods_id']]['is_free_shipping'] = $row['is_free_shipping'];
		$arr[$row['goods_id']]['saveprice']        = price_format($row['market_price'] - $row['shop_price']);
		$arr[$row['goods_id']]['saveperce']        = ($row['market_price'] == 0 || is_null($row['market_price']))?'0': price_format(($row['market_price'] - $row['shop_price'])/$row['market_price'])*100;
		$arr[$row['goods_id']]['url_title']        = get_details_link($row['goods_id'],$row['url_title']);
		
    }
	
    return $arr;
	
}














?>
